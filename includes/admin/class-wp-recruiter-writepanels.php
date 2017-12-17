<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Job_Manager_Writepanels' ) ) {
	include( JOB_MANAGER_PLUGIN_DIR . '/includes/admin/class-wp-job-manager-writepanels.php' );
}

class WP_Recruiter_Writepanels extends WP_Job_Manager_Writepanels {

	public function __construct() {

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_post' ), 1, 2 );
		add_action( 'recruiter_save_company', array( $this, 'save_company_data' ), 1, 2 );

		add_filter( 'job_manager_job_listing_data_fields', array( $this, 'modify_job_listing_data_fields' ), 2, 1);
	}

	public function company_fields() {
		global $post;

		$fields = array(
			'_company_logo' => array(
				'label'		  => __( 'Company Logo', 'wp-job-manager-recruiter' ),
				'placeholder' => __( 'URL to the company logo', 'wp-job-manager-recruiter' ),
				'type'		  => 'file'
			),
			'_company_website' => array(
				'label'		  => __( 'Company Website', 'wp-job-manager-recruiter' ),
				'placeholder' => __( 'URL to the company website', 'wp-job-manager-recruiter' ),
				'type'		  => 'text'
			),
			'_company_video' => array(
				'label'		  => __( 'Company Video', 'wp-job-manager-recruiter' ),
				'placeholder' => __( 'URL to the company video', 'wp-job-manager-recruiter' ),
				'type'		  => 'file'
			),
			'_company_video_thumbnail' => array(
				'label'		  => __( 'Company Video Thumbnail', 'wp-job-manager-recruiter' ),
				'placeholder' => __( 'URL to the company video thumbnail', 'wp-job-manager-recruiter' ),
				'type'		  => 'file'
			),
			'_company_location' => array(
				'label'		  => __( 'Company Location', 'wp-job-manager-recruiter' ),
				'placeholder' => __( 'e.g. "123 King St Sydney, NSW 2000"', 'wp-job-manager-recruiter' ),
				'description' => ''
			),
			'_company_size' => array(
				'label'		  => __( 'Number of Employees', 'wp-job-manager-recruiter' ),
				'placeholder' => __( 'e.g. "10"', 'wp-job-manager-recruiter' ),
				'description' => ''
			),
			'_featured' => array(
				'label' => __( 'Feature this Company?', 'wp-job-manager-recruiter' ),
				'type'	=> 'checkbox',
				'description' => __( 'Featured companies will be sticky during searches, and can be styled differently.', 'wp-job-manager-recruiter' )
			),
			'_company_author' => array(
				'label' => __( 'Posted by', 'wp-job-manager-recruiter' ),
				'type'	=> 'author'
			),
		);

		$fields = apply_filters( 'recruiter_company_data_fields', $fields, $post->ID );

		uasort( $fields, array( $this, 'sort_by_priority' ) );

		return $fields;
	}

	/**
	 * Sorts array of custom fields by priority value.
	 *
	 * @param array $a
	 * @param array $b
	 * @return int
	 */
	protected function sort_by_priority( $a, $b ) {
	    if ( ! isset( $a['priority'] ) || ! isset( $b['priority'] ) || $a['priority'] === $b['priority'] ) {
	        return 0;
	    }
	    return ( $a['priority'] < $b['priority'] ) ? -1 : 1;
	}

	public function add_meta_boxes() {

		add_meta_box( 'company_data', __( 'Company Information', 'wp-job-manager-recruiter' ), array( $this, 'company_data' ), 'company', 'normal', 'high' );
		add_meta_box( 'company_url_data', __( 'URL(s)', 'wp-job-manager-recruiter' ), array( $this, 'url_data' ), 'company', 'side', 'low' );
	}

	public function company_data( $post ) {
		global $post, $thepostid;

		$thepostid = $post->ID;

		echo '<div class="wp_recruiter_meta_data wp_job_manager_meta_data">';

			wp_nonce_field( 'save_meta_data', 'recruiter_nonce' );

			do_action( 'recruiter_company_data_start', $thepostid );

			foreach ( $this->company_fields() as $key => $field ) {

				$type = ! empty( $field['type'] ) ? $field['type'] : 'text';

				if( has_action( 'recruiter_input_' . $type ) ) {
					do_action( 'recruiter_input_' . $type, $key, $field );
				} elseif( method_exists( $this, 'input_' . $type ) ) {
					call_user_func( array( $this, 'input_' . $type ), $key, $field );
				}

			}

			do_action( 'recruiter_company_data_end', $thepostid );

		echo '</div>';
	}

	public function url_data( $post ) {

		echo '<p>' . __( 'Optionally provide links to any of your websites or social network profiles.', 'wp-job-manager-recruiter' ) . '</p>';

		$fields = $this->company_links_fields();

		$this->repeated_rows_html( __( 'URL', 'wp-job-manager-recruiter' ), $fields, get_post_meta( $post->ID, '_links', true ) );
	}

	public static function company_links_fields() {

		return apply_filters(
			'recruiter_company_links_fields',
			array(
				'name' => array(
					'label'		  => __( 'Name', 'wp-job-manager-recruiter' ),
					'name'		  => 'company_url_name[]',
					'placeholder' => __( 'Your site', 'wp-job-manager-recruiter' ),
					'description' => '',
					'required'	  => true
				),
				'url' => array(
					'label'		  => __( 'URL', 'wp-job-manager-recruiter' ),
					'name'		  => 'company_url[]',
					'placeholder' => 'http://',
					'description' => '',
					'required'	  => true
				)
			)
		);
	}

	public static function company_employee_fields() {

		return apply_filters(
			'recruiter_company_employee_fields',
			array(
				'name' => array(
					'label'		  => __( 'Name', 'wp-job-manager-recruiter' ),
					'name'		  => 'company_employee_name[]',
					'placeholder' => __( 'Name', 'wp-job-manager-recruiter' ),
					'description' => '',
					'required'	  => true
				),
			)
		);
	}

	public static function repeated_rows_html( $group_name = null, $fields = array(), $data = array() ) {
		?>
		<table class="wc-job-manager-recruiter-repeated-rows">
			<thead>
				<tr>
					<th class="sort-column">&nbsp;</th>
					<?php foreach ( $fields as $field ) : ?>
						<th><label><?php echo esc_html( $field['label'] ); ?></label></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="<?php echo sizeof( $fields ) + 1; ?>">
						<div class="submit">
							<input type="submit" class="button recruiter_add_row" value="<?php printf( __( 'Add %s', 'wp-job-manager-recruiter' ), $group_name ); ?>" data-row="<?php
								ob_start();
								echo '<tr>';
								echo '<td class="sort-column" width="1%">&nbsp;</td>';
								foreach ( $fields as $key => $field ) {
									echo '<td>';
									$type			= ! empty( $field['type'] ) ? $field['type'] : 'text';
									$field['value'] = '';

									if ( method_exists( __CLASS__, 'input_' . $type ) ) {
										call_user_func( array( __CLASS__, 'input_' . $type ), $key, $field );
									} else {
										do_action( 'recruiter_input_' . $type, $key, $field );
									}
									echo '</td>';
								}
								echo '</tr>';
								echo esc_attr( ob_get_clean() );
							?>" />
						</div>
					</td>
				</tr>
			</tfoot>
			<tbody>
				<?php
					if ( $data ) {
						foreach ( $data as $item ) {
							echo '<tr>';
							echo '<td class="sort-column" width="1%">&nbsp;</td>';
							foreach ( $fields as $key => $field ) {
								echo '<td>';
								$type			= ! empty( $field['type'] ) ? $field['type'] : 'text';
								$field['value'] = isset( $item[ $key ] ) ? $item[ $key ] : '';

								if ( method_exists( __CLASS__, 'input_' . $type ) ) {
									call_user_func( array( __CLASS__, 'input_' . $type ), $key, $field );
								} else {
									do_action( 'recruiter_input_' . $type, $key, $field );
								}
								echo '</td>';
							}
							echo '</tr>';
						}
					}
				?>
			</tbody>
		</table>
		<?php
	}

	public function save_post( $post_id = null, $post = null ) {

		if ( empty( $post_id ) || empty( $post ) || empty( $_POST ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE || is_int( wp_is_post_autosave( $post ) ) ) {
			return;
		}

		if ( is_int( wp_is_post_revision( $post ) ) ) {
			return;
		}

		if ( empty( $_POST['recruiter_nonce'] ) || ! wp_verify_nonce( $_POST['recruiter_nonce'], 'save_meta_data' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( $post->post_type != 'company' ) {
			return;
		}

		do_action( 'recruiter_save_company', $post_id, $post );
	}

	public function save_company_data( $post_id, $post ) {
		global $wpdb;

		// These need to exist
		add_post_meta( $post_id, '_featured', 0, true );

		foreach ( $this->company_fields() as $key => $field ) {

			if ( '_company_location' === $key ) {

				if ( update_post_meta( $post_id, $key, sanitize_text_field( $_POST[ $key ] ) ) ) {
					do_action( 'recruiter_company_location_edited', $post_id, sanitize_text_field( $_POST[ $key ] ) );
				} elseif ( apply_filters( 'recruiter_geolocation_enabled', true ) && ! WP_Job_Manager_Geocode::has_location_data( $post_id ) ) {
					WP_Job_Manager_Geocode::generate_location_data( $post_id, sanitize_text_field( $_POST[ $key ] ) );
				}

				continue;

			} elseif( '_company_author' === $key ) {

				$wpdb->update( $wpdb->posts, array( 'post_author' => $_POST[ $key ] > 0 ? absint( $_POST[ $key ] ) : 0 ), array( 'ID' => $post_id ) );

			// Everything else
			} else {

				$type = ! empty( $field['type'] ) ? $field['type'] : '';

				switch ( $type ) {
					case 'textarea' :
						update_post_meta( $post_id, $key, wp_kses_post( stripslashes( $_POST[ $key ] ) ) );
						break;

					case 'checkbox' :
						if ( isset( $_POST[ $key ] ) ) {
							update_post_meta( $post_id, $key, 1 );
						} else {
							update_post_meta( $post_id, $key, 0 );
						}
						break;

					default :
						if ( is_array( $_POST[ $key ] ) ) {
							update_post_meta( $post_id, $key, array_filter( array_map( 'sanitize_text_field', $_POST[ $key ] ) ) );
						} else {
							update_post_meta( $post_id, $key, sanitize_text_field( $_POST[ $key ] ) );
						}
						break;
				}

			}

		}

		$save_repeated_fields = array(
			'_links' => $this->company_links_fields()
		);

		foreach ( $save_repeated_fields as $meta_key => $fields ) {
			$this->save_repeated_row( $post_id, $meta_key, $fields );
		}
	}

	public function modify_job_listing_data_fields( $fields = array() ) {

		$options = array();
		$companies = recruiter_get_user_companies();

		if ( ! empty( $companies ) ) {

			foreach( $companies as $company ) {
				$options[ $company->ID	] = $company->post_title;
			}

			$fields['_company_id'] = array(
				'label'		  => __( 'Company Affilliation', 'wp-job-manager' ),
				'placeholder' => '',
				'priority'	  => 13,
				'type'		  => 'select',
				'options'	  => $options,
				'value'		  => get_post_meta( get_the_ID(), '_company_id', true )
			);

		}

		return $fields;
	}

	public static function save_repeated_row( $post_id = null, $meta_key = null, $fields = array() ) {

		$items			  = array();
		$first_field	  = current( $fields );
		$first_field_name = str_replace( '[]', '', $first_field['name'] );

		if ( ! empty( $_POST[ $first_field_name ] ) && is_array( $_POST[ $first_field_name ] ) ) {

			$keys = array_keys( $_POST[ $first_field_name ] );

			foreach ( $keys as $posted_key ) {

				$item = array();

				foreach ( $fields as $key => $field ) {

					$input_name = str_replace( '[]', '', $field['name'] );
					$type		= ! empty( $field['type'] ) ? $field['type'] : 'text';

					switch ( $type ) {
						case 'textarea' :
							$item[ $key ] = wp_kses_post( stripslashes( $_POST[ $input_name ][ $posted_key ] ) );
							break;
						default :
							if ( is_array( $_POST[ $input_name ][ $posted_key ] ) ) {
								$item[ $key ] = array_filter( array_map( 'sanitize_text_field', array_map( 'stripslashes', $_POST[ $input_name ][ $posted_key ] ) ) );
							} else {
								$item[ $key ] = sanitize_text_field( stripslashes( $_POST[ $input_name ][ $posted_key ] ) );
							}
							break;
					}

					if ( empty( $item[ $key ] ) && ! empty( $field['required'] ) ) {
						continue 2;
					}

				}

				$items[] = $item;
			}

		}

		update_post_meta( $post_id, $meta_key, $items );
	}
}

new WP_Recruiter_Writepanels();
