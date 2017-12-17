<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_Recruiter_CPT {

	public function __construct() {

		add_filter( 'enter_title_here', array( $this, 'enter_title_here' ), 1, 2 );
		add_filter( 'manage_edit-company_columns', array( $this, 'columns' ) );
		add_action( 'manage_company_posts_custom_column', array( $this, 'custom_columns' ), 2 );
		add_filter( 'manage_edit-company_sortable_columns', array( $this, 'sortable_columns' ) );
		add_action( 'parse_query', array( $this, 'search_meta' ) );
		add_filter( 'get_search_query', array( $this, 'search_meta_label' ) );
		add_filter( 'request', array( $this, 'sort_columns' ) );
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
		add_action( 'admin_footer-edit.php', array( $this, 'add_bulk_actions' ) );
		add_action( 'load-edit.php', array( $this, 'do_bulk_actions' ) );
		add_action( 'admin_init', array( $this, 'approve_company' ) );
		add_action( 'admin_notices', array( $this, 'approved_notice' ) );

		if ( get_option( 'recruiter_enable_company_categories' ) ) {
			add_action( "restrict_manage_posts", array( $this, "companies_by_category" ) );
		}
	}

	public function add_bulk_actions() {
		global $post_type;

		if ( $post_type == 'company' ) {
			?>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery('<option>').val('approve_companies').text('<?php _e( 'Approve Companies', 'wp-job-manager-recruiter' )?>').appendTo("select[name='action']");
					jQuery('<option>').val('approve_companies').text('<?php _e( 'Approve Companies', 'wp-job-manager-recruiter' )?>').appendTo("select[name='action2']");
				});
			</script>
			<?php
		}
	}

	public function do_bulk_actions() {

		$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
		$action		   = $wp_list_table->current_action();

		switch( $action ) {

			case 'approve_companies' :
				check_admin_referer( 'bulk-posts' );

				$post_ids			= array_map( 'absint', array_filter( (array) $_GET['post'] ) );
				$approved_companies = array();

				if ( ! empty( $post_ids ) )

					foreach( $post_ids as $post_id ) {

						$company_data =
							array(
								'ID'		  => $post_id,
								'post_status' => 'publish'
							);

						if ( get_post_status( $post_id ) == 'pending' && wp_update_post( $company_data ) ) {
							$approved_companies[] = $post_id;
						}
					}

				wp_redirect( remove_query_arg( 'approve_companies', add_query_arg( 'approved_companies', $approved_companies, admin_url( 'edit.php?post_type=company' ) ) ) );
				exit;
			break;
		}

		return;
	}

	public function approve_company() {

		if ( ! empty( $_GET['approve_company'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'approve_company' ) && current_user_can( 'edit_post', $_GET['approve_company'] ) ) {

			$post_id = absint( $_GET['approve_company'] );

			$company_data = array(
				'ID'		  => $post_id,
				'post_status' => 'publish'
			);

			wp_update_post( $company_data );

			wp_redirect( remove_query_arg( 'approve_company', add_query_arg( 'approved_companies', $post_id, admin_url( 'edit.php?post_type=company' ) ) ) );
			exit;
		}
	}

	public function approved_notice() {
		global $post_type, $pagenow;

		if ( $pagenow == 'edit.php' && $post_type == 'company' && ! empty( $_REQUEST['approved_companies'] ) ) {

			$approved_companies = $_REQUEST['approved_companies'];

			if ( is_array( $approved_companies ) ) {

				$approved_companies = array_map( 'absint', $approved_companies );
				$titles				= array();

				foreach ( $approved_companies as $company_id ) {
					$titles[] = get_the_title( $company_id );
				}

				echo '<div class="updated"><p>' . sprintf( __( '%s approved', 'wp-job-manager-recruiter' ), '&quot;' . implode( '&quot;, &quot;', $titles ) . '&quot;' ) . '</p></div>';

			} else {
				echo '<div class="updated"><p>' . sprintf( __( '%s approved', 'wp-job-manager-recruiter' ), '&quot;' . get_the_title( $approved_companies ) . '&quot;' ) . '</p></div>';
			}
		}
	}

	public function companies_by_category( $show_counts = 1, $hierarchical = 1, $show_uncategorized = 1, $orderby = '' ) {
		global $typenow, $wp_query;

		if ( $typenow != 'company' || ! taxonomy_exists( 'company_category' ) ) {
			return;
		}

		if ( file_exists( JOB_MANAGER_PLUGIN_DIR . '/includes/admin/class-wp-job-manager-category-walker.php' ) ) {
			include_once( JOB_MANAGER_PLUGIN_DIR . '/includes/admin/class-wp-job-manager-category-walker.php' );
		} else {
			include_once( JOB_MANAGER_PLUGIN_DIR . '/includes/class-wp-job-manager-category-walker.php' );
		}

		$r = array(
			'pad_counts'   => 1,
			'hierarchical' => $hierarchical,
			'hide_empty'   => 0,
			'show_count'   => $show_counts,
			'selected'	   => isset( $wp_query->query['company_category'] ) ? $wp_query->query['company_category'] : '',
			'menu_order'   => false,
		);

		if ( $orderby == 'order' ) {
			$r['menu_order'] = 'asc';
		} elseif ( $orderby ) {
			$r['orderby'] = $orderby;
		}

		$terms = get_terms( 'company_category', $r );

		if ( ! $terms ) {
			return;
		}

		$output	 = "<select name='company_category' id='dropdown_company_category'>";
		$output .= '<option value="" ' .  selected( isset( $_GET['company_category'] ) ? $_GET['company_category'] : '', '', false ) . '>'.__( 'Select a category', 'wp-job-manager-recruiter' ).'</option>';
		$output .= $this->_walk_category_dropdown_tree( $terms, 0, $r );
		$output .= "</select>";

		echo $output;
	}

	public function enter_title_here( $text = null, $post = null ) {

		if ( $post->post_type == 'company' ) {
			return __( 'Company Name', 'wp-job-manager-recruiter' );
		}

		return $text;
	}

	public function post_updated_messages( $messages = array() ) {
		global $post, $post_ID;

		$messages['company'] = array(
			0 => '',
			1 => sprintf( __( 'Company updated. <a href="%s">View company</a>', 'wp-job-manager-recruiter' ), esc_url( get_permalink( $post_ID ) ) ),
			2 => __( 'Custom field updated.', 'wp-job-manager-recruiter' ),
			3 => __( 'Custom field deleted.', 'wp-job-manager-recruiter' ),
			4 => __( 'Company updated.', 'wp-job-manager-recruiter' ),
			5 => isset( $_GET['revision'] ) ? sprintf( __( 'Company restored to revision from %s', 'wp-job-manager-recruiter' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __( 'Company published. <a href="%s">View company</a>', 'wp-job-manager-recruiter' ), esc_url( get_permalink( $post_ID ) ) ),
			7 => __('Company saved.', 'wp-job-manager-recruiter'),
			8 => sprintf( __( 'Company submitted. <a target="_blank" href="%s">Preview company</a>', 'wp-job-manager-recruiter' ), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			9 => sprintf( __( 'Company scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview company</a>', 'wp-job-manager-recruiter' ),
			  date_i18n( __( 'M j, Y @ G:i', 'wp-job-manager-recruiter' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) ),
			10 => sprintf( __( 'Company draft updated. <a target="_blank" href="%s">Preview company</a>', 'wp-job-manager-recruiter' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
		);

		return $messages;
	}

	public function columns( $columns = array() ) {

		if ( ! is_array( $columns ) ) {
			$columns = array();
		}

		unset( $columns['title'], $columns['date'] );

		$columns["company"]			 = __( "Company", 'wp-job-manager-recruiter' );
		$columns["company_location"] = __( "Location", 'wp-job-manager-recruiter' );
		$columns['company_status']	 = '<span class="tips" data-tip="' . __( "Status", 'wp-job-manager-recruiter' ) . '">' . __( "Status", 'wp-job-manager-recruiter' ) . '</span>';

		if ( get_option( 'recruiter_enable_company_categories' ) ) {
			$columns["company_category"] = __( "Categories", 'wp-job-manager-recruiter' );
		}

		$columns['featured_company'] = '<span class="tips" data-tip="' . __( "Featured?", 'wp-job-manager-recruiter' ) . '">' . __( "Featured?", 'wp-job-manager-recruiter' ) . '</span>';
		$columns['company_actions']	 = __( "Actions", 'wp-job-manager-recruiter' );

		return $columns;
	}

	public function sortable_columns( $columns = array() ) {

		$custom = array(
			'company'		   => 'title',
			'company_location' => 'company_location',
		);

		return wp_parse_args( $custom, $columns );
	}

	public function search_meta( $wp ) {
		global $pagenow, $wpdb;

		if ( 'edit.php' != $pagenow || empty( $wp->query_vars['s'] ) || $wp->query_vars['post_type'] != 'company' ) {
			return;
		}

		$post_ids = array_unique(
			array_merge(
				$wpdb->get_col(
					$wpdb->prepare(
						"SELECT posts.ID
						FROM {$wpdb->posts} posts
						INNER JOIN {$wpdb->postmeta} p1 ON posts.ID = p1.post_id
						WHERE p1.meta_value LIKE '%%%s%%'
						OR posts.post_title LIKE '%%%s%%'
						OR posts.post_content LIKE '%%%s%%'
						AND posts.post_type = 'company'",
						esc_attr( $wp->query_vars['s'] ),
						esc_attr( $wp->query_vars['s'] ),
						esc_attr( $wp->query_vars['s'] )
					)
				),
				array( 0 )
			)
		);

		// Adjust the query vars
		unset( $wp->query_vars['s'] );

		$wp->query_vars['company_search'] = true;
		$wp->query_vars['post__in'] = $post_ids;
	}

	public function search_meta_label( $query ) {
		global $pagenow, $typenow;

		if ( 'edit.php' != $pagenow || $typenow != 'company' || ! get_query_var( 'company_search' ) ) {
			return $query;
		}

		return wp_unslash( sanitize_text_field( $_GET['s'] ) );
	}

	public function sort_columns( $vars ) {

		if ( isset( $vars['orderby'] ) ) {

			if ( 'company_location' === $vars['orderby'] ) {

				$vars = array_merge(
					$vars,
					array(
						'meta_key'	=> '_company_location',
						'orderby'	=> 'meta_value'
					)
				);
			}

		}

		return $vars;
	}

	public function custom_columns( $column ) {
		global $post;

		switch ( $column ) {

			case "company" :
				echo '<a href="' . admin_url('post.php?post=' . $post->ID . '&action=edit') . '" class="tips company_name" data-tip="' . sprintf( __( 'Company ID: %d', 'wp-job-manager-recruiter' ), $post->ID ) . '">' . $post->post_title . '</a>';
				echo '<div class="company_title">';
					the_company_title();
				echo '</div>';
				break;

			case 'company_location' :
				the_company_location( true, $post );
				break;

			case "company_category" :
				if ( ! $terms = get_the_term_list( $post->ID, $column, '', ', ', '' ) ) {
					echo '<span class="na">&ndash;</span>';
				} else {
					echo $terms;
				}
				break;

			case "featured_company" :
				if ( is_company_featured( $post ) ) echo '&#10004;'; else echo '&ndash;';
				break;

			case "company_status" :
				echo '<span data-tip="' . esc_attr( get_the_company_status( $post ) ) . '" class="tips status-' . esc_attr( $post->post_status ) . '">' . get_the_company_status( $post ) . '</span>';
				break;

			case "company_actions" :
				$admin_actions = array();

				echo '<div class="actions">';

				if ( $post->post_status == 'pending' ) {

					$admin_actions['approve'] =
						array(
							'action'  => 'approve',
							'name'	  => __( 'Approve', 'wp-job-manager-recruiter' ),
							'url'	  =>  wp_nonce_url( add_query_arg( 'approve_company', $post->ID ), 'approve_company' )
						);
				}

				if ( $post->post_status !== 'trash' ) {

					$admin_actions['view'] =
						array(
						'action'  => 'view',
						'name'	  => __( 'View', 'wp-job-manager-recruiter' ),
						'url'	  => get_permalink( $post->ID )
					);

					if ( $email = get_post_meta( $post->ID, '_company_email', true ) ) {

						$admin_actions['email'] =
							array(
								'action'  => 'email',
								'name'	  => __( 'Email Company', 'wp-job-manager-recruiter' ),
								'url'	  =>  'mailto:' . esc_attr( $email )
							);
					}

					$admin_actions['edit']	 =
						array(
							'action'  => 'edit',
							'name'	  => __( 'Edit', 'wp-job-manager-recruiter' ),
							'url'	  => get_edit_post_link( $post->ID )
						);

					$admin_actions['delete'] =
						array(
							'action'  => 'delete',
							'name'	  => __( 'Delete', 'wp-job-manager-recruiter' ),
							'url'	  => get_delete_post_link( $post->ID )
						);
				}

				$admin_actions = apply_filters( 'recruiter_admin_actions', $admin_actions, $post );

				foreach ( $admin_actions as $action ) {

					printf(
						'<a class="icon-%s button tips" href="%s" data-tip="%s">%s</a>',
						esc_attr( $action['action'] ),
						esc_url( $action['url'] ),
						esc_attr( $action['name'] ),
						esc_attr( $action['name'] )
					);

				}

				echo '</div>';

				break;
		}
	}

	private function _walk_category_dropdown_tree() {

		$args = func_get_args();

		// the user's options are the third parameter
		if ( empty($args[2]['walker']) || !is_a($args[2]['walker'], 'Walker') ) {
			$walker = new WP_Job_Manager_Category_Walker;
		} else {
			$walker = $args[2]['walker'];
		}

		return call_user_func_array( array( $walker, 'walk' ), $args );
	}
}

new WP_Recruiter_CPT();
