<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_Recruiter_Shortcodes {

	private $employee_dashboard_message = '';
	private $company_dashboard_message = '';

	public function __construct() {

		add_action( 'wp', array( $this, 'handle_redirects' ) );
		add_action( 'wp', array( $this, 'shortcode_action_handler' ) );

		add_shortcode( 'submit_company_form', array( $this, 'submit_company_form' ) );
		add_shortcode( 'company_dashboard', array( $this, 'company_dashboard' ) );
		add_shortcode( 'companies', array( $this, 'output_companies' ) );

		add_shortcode( 'submit_employee_form', array( $this, 'submit_employee_form' ) );
		add_shortcode( 'employee_dashboard', array( $this, 'employee_dashboard' ) );

		add_action( 'recruiter_output_companies_no_results', array( $this, 'output_no_results' ) );
	}

	public function handle_redirects() {

		if ( ! get_current_user_id() || ! empty( $_REQUEST['company_id'] ) || ! empty( $_REQUEST['employee_id'] )  ) {
			return;
		}

		$submit_company_form_page_id = get_option( 'recruiter_submit_company_form_page_id' );
		$company_dashboard_page_id   = get_option( 'recruiter_company_dashboard_page_id' );
		$company_submission_limit    = get_option( 'recruiter_company_submission_limit' );
		$company_count               = recruiter_count_user_companies();

		// prevent user from creating excess companies
		if ( $submit_company_form_page_id && $company_dashboard_page_id && $company_submission_limit ) {
			if ( $company_count >= $company_submission_limit && is_page( $submit_company_form_page_id ) ) {
				wp_redirect( get_permalink( $company_dashboard_page_id ) );
				exit;
			}
		}

		// prevent user from creating excess employees
		$submit_employee_form_page_id = get_option( 'recruiter_submit_employee_form_page_id' );
		$employee_dashboard_page_id   = get_option( 'recruiter_employee_dashboard_page_id' );
		$employee_submission_limit    = get_option( 'recruiter_employee_submission_limit' );
		$employee_count               = recruiter_count_user_employees();

		if ( $submit_employee_form_page_id && $employee_dashboard_page_id && $employee_submission_limit ) {
			if ( $employee_count >= $employee_submission_limit && is_page( $submit_employee_form_page_id ) ) {
				wp_redirect( get_permalink( $employee_dashboard_page_id ) );
				exit;
			}
		}
	}

	public function shortcode_action_handler() {
		global $post;

		if ( is_page() && strstr( $post->post_content, '[company_dashboard' ) ) {
			$this->company_dashboard_handler();
		}

		if ( is_page() && strstr( $post->post_content, '[employee_dashboard' ) ) {
			$this->employee_dashboard_handler();
		}
	}

	public function company_dashboard_handler() {

		if ( ! empty( $_REQUEST['action'] ) && ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'recruiter_my_company_actions' ) ) {

			$action     = sanitize_title( $_REQUEST['action'] );
			$company_id = absint( $_REQUEST['company_id'] );

			try {

				if ( ! recruiter_can_user_edit_company( $company_id ) ) {
					throw new Exception( __( 'Invalid Company ID', 'wp-job-manager-recruiter' ) );
				}

				$company = get_post( $company_id );

				switch ( $action ) {

					case 'delete' :
						wp_trash_post( $company_id );
						$this->company_dashboard_message = '<div class="job-manager-message">' . sprintf( __( '%s has been deleted', 'wp-job-manager-recruiter' ), $company->post_title ) . '</div>';
						break;

					case 'hide' :
						if ( $company->post_status === 'publish' ) {
							wp_update_post( array( 'ID' => $company_id, 'post_status' => 'hidden' ) );
							$this->company_dashboard_message = '<div class="job-manager-message">' . sprintf( __( '%s has been hidden', 'wp-job-manager-recruiter' ), $company->post_title ) . '</div>';
						}
						break;

					case 'publish' :
						if ( $company->post_status === 'hidden' ) {
							wp_update_post( array( 'ID' => $company_id, 'post_status' => 'publish' ) );
							$this->company_dashboard_message = '<div class="job-manager-message">' . sprintf( __( '%s has been published', 'wp-job-manager-recruiter' ), $company->post_title ) . '</div>';
						}
						break;

					case 'relist' :
						wp_redirect( add_query_arg( array( 'company_id' => absint( $company_id ) ), get_permalink( get_option( 'recruiter_submit_company_form_page_id' ) ) ) );
						break;
				}

				do_action( 'recruiter_my_company_do_action', $action, $company_id );

			} catch ( Exception $e ) {
				$this->company_dashboard_message = '<div class="job-manager-error">' . $e->getMessage() . '</div>';
			}
		}
	}

	public function employee_dashboard_handler() {

		if ( ! empty( $_REQUEST['action'] ) && ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'recruiter_my_employee_actions' ) ) {

			$action      = sanitize_title( $_REQUEST['action'] );
			$employee_id = absint( $_REQUEST['employee_id'] );

			try {

				// Check ownership
				if ( ! recruiter_can_user_edit_employee( $employee_id ) ) {
					throw new Exception( __( 'Invalid Employee ID', 'wp-job-manager-recruiter' ) );
				}

				$current_user = get_current_user_id();
				$employee     = get_user_by( 'id', $employee_id );

				switch ( $action ) {

					case 'delete' :
						recruiter_delete_employee( $employee_id );
						$this->employee_dashboard_message = '<div class="job-manager-message">' . sprintf( __( '%s has been deleted', 'wp-job-manager-recruiter' ), $employee->display_name ) . '</div>';
						break;

					case 'relist' :
						wp_redirect( add_query_arg( array( 'employee_id' => absint( $employee_id ) ), get_permalink( get_option( 'recruiter_submit_employee_form_page_id' ) ) ) );
						break;
				}

				do_action( 'recruiter_my_employee_do_action', $action, $employee_id );

			} catch ( Exception $e ) {
				$this->employee_dashboard_message = '<div class="job-manager-error">' . $e->getMessage() . '</div>';
			}
		}
	}

	public function company_dashboard( $atts = array() ) {
		global $recruiter;

		if ( ! is_user_logged_in() ) {

			ob_start();

			get_job_manager_template(
				'company-dashboard-login.php',
				array(),
				'wp-job-manager-recruiter',
				RECRUITER_PLUGIN_DIR . '/templates/'
			);

			return ob_get_clean();
		}

		extract(
			shortcode_atts(
				array(
					'posts_per_page' => '-1',
				),
				$atts
			)
		);

		wp_enqueue_script( 'wp-recruiter-company-dashboard' );

		// If doing an action, show conditional content if needed....
		if ( ! empty( $_REQUEST['action'] ) ) {

			$action     = sanitize_title( $_REQUEST['action'] );
			$company_id = absint( $_REQUEST['company_id'] );

			switch ( $action ) {
				case 'edit' :
					return $recruiter->forms->get_form( 'edit-company' );
			}
		}

		ob_start();

		echo $this->company_dashboard_message;

		$company_dashboard_columns = apply_filters(
			'recruiter_company_dashboard_columns',
			array(
				'company-title'    => __( 'Name', 'wp-job-manager-recruiter' ),
				'company-location' => __( 'Location', 'wp-job-manager-recruiter' ),
				'company-category' => __( 'Category', 'wp-job-manager-recruiter' ),
			)
		);

		if ( ! get_option( 'recruiter_enable_company_categories' ) ) {
			unset( $company_dashboard_columns['company-category'] );
		}

		get_job_manager_template(
			'company-dashboard.php',
			array(
				'companies'                 => recruiter_get_user_companies(),
				'max_num_pages'             => null,
				'company_dashboard_columns' => $company_dashboard_columns
			),
			'wp-job-manager-recruiter',
			RECRUITER_PLUGIN_DIR . '/templates/'
		);

		return ob_get_clean();
	}

	public function employee_dashboard( $atts = array() ) {
		global $recruiter;

		if ( ! is_user_logged_in() ) {

			ob_start();

			get_job_manager_template(
				'employee-dashboard-login.php',
				array(),
				'wp-job-manager-recruiter',
				RECRUITER_PLUGIN_DIR . '/templates/'
			);

			return ob_get_clean();
		}

		extract(
			shortcode_atts(
				array(
					'posts_per_page' => '25',
				),
				$atts
			)
		);

		wp_enqueue_script( 'wp-recruiter-employee-dashboard' );

		// If doing an action, show conditional content if needed....
		if ( ! empty( $_REQUEST['action'] ) ) {

			$action      = sanitize_title( $_REQUEST['action'] );
			$employee_id = absint( $_REQUEST['employee_id'] );

			switch ( $action ) {
				case 'edit' :
					return $recruiter->forms->get_form( 'edit-employee' );
			}

		}

		ob_start();

		echo $this->employee_dashboard_message;

		$employee_dashboard_columns = apply_filters(
			'recruiter_employee_dashboard_columns',
			array(
				'employee-name'  => __( 'Name', 'wp-job-manager-recruiter' ),
				'employee-email' => __( 'Email', 'wp-job-manager-recruiter' ),
				'employee-role'  => __( 'Role', 'wp-job-manager-recruiter' ),
			)
		);

		get_job_manager_template(
			'employee-dashboard.php',
			array(
				'employees'                  => recruiter_get_user_employees(),
				'max_num_pages'              => null,
				'employee_dashboard_columns' => $employee_dashboard_columns
			),
			'wp-job-manager-recruiter',
			RECRUITER_PLUGIN_DIR . '/templates/'
		);

		return ob_get_clean();
	}

	public function submit_company_form( $atts = array() ) {
		return $GLOBALS['recruiter']->forms->get_form( 'submit-company', $atts );
	}

	public function submit_employee_form( $atts = array() ) {
		return $GLOBALS['recruiter']->forms->get_form( 'submit-employee', $atts );
	}

	public function output_companies( $atts = array() ) {
		global $recruiter;

		ob_start();

		if ( ! recruiter_can_user_browse_companies() ) {
			get_job_manager_template_part( 'access-denied', 'browse-companies', 'wp-job-manager-recruiter', RECRUITER_PLUGIN_DIR . '/templates/' );
			return ob_get_clean();
		}

		extract(
			$atts = shortcode_atts(
				apply_filters(
					'recruiter_output_companies_defaults',
					array(
						'per_page'                  => get_option( 'recruiter_per_page' ),
						'order'                     => 'DESC',
						'orderby'                   => 'featured',
						'show_filters'              => true,
						'show_categories'           => get_option( 'recruiter_enable_company_categories' ),
						'categories'                => '',
						'featured'                  => null, // True to show only featured, false to hide featured, leave null to show both.
						'show_category_multiselect' => get_option( 'recruiter_enable_default_category_multiselect', false ),
						'selected_category'         => '',
						'show_pagination'           => false,
						'show_more'                 => true,
					)
				),
				$atts
			)
		);

		$categories = array_filter( array_map( 'trim', explode( ',', $categories ) ) );
		$keywords   = '';
		$location   = '';

		// String and bool handling
		$show_filters              = $this->string_to_bool( $show_filters );
		$show_categories           = $this->string_to_bool( $show_categories );
		$show_category_multiselect = $this->string_to_bool( $show_category_multiselect );
		$show_more                 = $this->string_to_bool( $show_more );
		$show_pagination           = $this->string_to_bool( $show_pagination );

		if ( ! is_null( $featured ) ) {
			$featured = ( is_bool( $featured ) && $featured ) || in_array( $featured, array( '1', 'true', 'yes' ) ? true : false );
		}

		if ( ! empty( $_GET['search_keywords'] ) ) {
			$keywords = sanitize_text_field( $_GET['search_keywords'] );
		}

		if ( ! empty( $_GET['search_location'] ) ) {
			$location = sanitize_text_field( $_GET['search_location'] );
		}

		if ( ! empty( $_GET['search_category'] ) ) {
			$selected_category = sanitize_text_field( $_GET['search_category'] );
		}

		if ( $show_filters ) {

			get_job_manager_template(
				'company-filters.php',
				array(
					'per_page' => $per_page,
					'orderby' => $orderby,
					'order' => $order,
					'show_categories' => $show_categories,
					'categories' => $categories,
					'selected_category' => $selected_category,
					'atts' => $atts,
					'location' => $location,
					'keywords' => $keywords,
					'show_category_multiselect' => $show_category_multiselect
				),
				'wp-job-manager-recruiter',
				RECRUITER_PLUGIN_DIR . '/templates/'
			);

			get_job_manager_template( 'companies-start.php', array(), 'wp-job-manager-recruiter', RECRUITER_PLUGIN_DIR . '/templates/' );
			get_job_manager_template( 'companies-end.php', array(), 'wp-job-manager-recruiter', RECRUITER_PLUGIN_DIR . '/templates/' );

			if ( ! $show_pagination && $show_more ) {
				echo '<a class="load_more_companies" href="#" style="display:none;"><strong>' . __( 'Load more companies', 'wp-job-manager-recruiter' ) . '</strong></a>';
			}

		} else {

			$companies = get_companies(
				apply_filters(
					'recruiter_output_companies_args',
					array(
						'search_categories' => $categories,
						'orderby'           => $orderby,
						'order'             => $order,
						'posts_per_page'    => $per_page,
						'featured'          => $featured
					)
				)
			);

			if ( $companies->have_posts() ) {

				get_job_manager_template( 'companies-start.php', array(), 'wp-job-manager-recruiter', RECRUITER_PLUGIN_DIR . '/templates/' );

				while ( $companies->have_posts() ) {
					$companies->the_post();
					get_job_manager_template_part( 'content', 'company', 'wp-job-manager-recruiter', RECRUITER_PLUGIN_DIR . '/templates/' );
				}

				get_job_manager_template( 'companies-end.php', array(), 'wp-job-manager-recruiter', RECRUITER_PLUGIN_DIR . '/templates/' );

				if ( $companies->found_posts > $per_page && $show_more ) {

					wp_enqueue_script( 'wp-recruiter-ajax-filters' );

					if ( $show_pagination ) {
						echo get_job_listing_pagination( $companies->max_num_pages );
					} else {
						printf('<a class="load_more_companies" href="#"><strong>%s</strong></a>', __( 'Load more companies', 'wp-job-manager-recruiter' ) );
					}

				};

			} else {
				do_action( 'recruiter_output_companies_no_results' );
			}

			wp_reset_postdata();
		}

		$data_attributes_string = '';

		$data_attributes = array(
			'location'        => $location,
			'keywords'        => $keywords,
			'show_filters'    => ( $show_filters ? 'true' : 'false' ),
			'show_pagination' => ( $show_pagination ? 'true' : 'false' ),
			'per_page'        => $per_page,
			'orderby'         => $orderby,
			'order'           => $order,
			'categories'      => implode( ',', $categories )
		);

		if ( ! is_null( $featured ) ) {
			$data_attributes[ 'featured' ] = ( $featured ? 'true' : 'false' );
		}

		foreach ( $data_attributes as $key => $value ) {
			$data_attributes_string .= 'data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '" ';
		}

		return '<div class="companies" ' . $data_attributes_string . '>' . ob_get_clean() . '</div>';
	}

	public function output_no_results() {
		get_job_manager_template( 'content-no-companies-found.php', array(), 'wp-job-manager-recruiter', RECRUITER_PLUGIN_DIR . '/templates/' );
	}

	public function string_to_bool( $value ) {
		return ( is_bool( $value ) && $value ) || in_array( $value, array( '1', 'true', 'yes' ) ) ? true : false;
	}
}

new WP_Recruiter_Shortcodes();
