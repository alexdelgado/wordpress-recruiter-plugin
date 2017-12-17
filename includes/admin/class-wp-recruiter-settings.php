<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_Recruiter_Settings extends WP_Job_Manager_Settings {

	public function __construct() {

		$this->settings_group = 'wp-job-manager-recruiter';

		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	protected function init_settings() {

		// Prepare roles option
		$roles		   = get_editable_roles();
		$account_roles = array();

		foreach ( $roles as $key => $role ) {

			if ( $key == 'administrator' ) {
				continue;
			}

			$account_roles[ $key ] = $role['name'];
		}

		$this->settings = apply_filters(
			'recruiter_settings',
			array(
				'company_listings' => array(
					__( 'Company Listings', 'wp-job-manager-recruiter' ),
					array(
						array(
							'name'		  => 'companies_per_page',
							'std'		  => '10',
							'placeholder' => '',
							'label'		  => __( 'Companies Per Page', 'wp-job-manager-recruiter' ),
							'desc'		  => __( 'How many companies should be shown per page by default?', 'wp-job-manager-recruiter' ),
							'attributes'  => array()
						),
						array(
							'name'		 => 'recruiter_enable_company_categories',
							'std'		 => '0',
							'label'		 => __( 'Categories', 'wp-job-manager-recruiter' ),
							'cb_label'	 => __( 'Enable company categories', 'wp-job-manager-recruiter' ),
							'desc'		 => __( 'Choose whether to enable company categories. Categories must be setup by an admin for users to choose during company submission.', 'wp-job-manager-recruiter' ),
							'type'		 => 'checkbox',
							'attributes' => array()
						),
						array(
							'name'		 => 'recruiter_enable_default_category_multiselect',
							'std'		 => '0',
							'label'		 => __( 'Multi-select Categories', 'wp-job-manager-recruiter' ),
							'cb_label'	 => __( 'Enable category multiselect by default', 'wp-job-manager-recruiter' ),
							'desc'		 => __( 'If enabled, the category select box will default to a multiselect on the [companies] shortcode.', 'wp-job-manager-recruiter' ),
							'type'		 => 'checkbox',
							'attributes' => array()
						),
						array(
							'name'		 => 'recruiter_category_filter_type',
							'std'		 => 'any',
							'label'		 => __( 'Category Filter Type', 'wp-job-manager-recruiter' ),
							'desc'		 => __( 'If enabled, the category select box will default to a multiselect on the [companies] shortcode.', 'wp-job-manager-recruiter' ),
							'type'		 => 'select',
							'options' => array(
								'any' => __( 'Companies will be shown if within ANY selected category', 'wp-job-manager-recruiter' ),
								'all' => __( 'Companies will be shown if within ALL selected categories', 'wp-job-manager-recruiter' ),
							)
						)
					),
				),
				'company_submission' => array(
					__( 'Company Submission', 'wp-job-manager-recruiter' ),
					array(
						array(
							'name'		  => 'recruiter_submission_requires_approval',
							'std'		  => '1',
							'label'		  => __( 'Approval Required', 'wp-job-manager-recruiter' ),
							'cb_label'	  => __( 'New submissions require admin approval', 'wp-job-manager-recruiter' ),
							'desc'		  => __( 'If enabled, new submissions will be inactive, pending admin approval.', 'wp-job-manager-recruiter' ),
							'type'		  => 'checkbox',
							'attributes'  => array()
						),
						array(
							'name'		  => 'recruiter_can_user_edit_pending_submissions',
							'std'		  => '0',
							'label'		  => __( 'Allow Pending Edits', 'wp-job-manager-recruiter' ),
							'cb_label'	  => __( 'Submissions awaiting approval can be edited', 'wp-job-manager-recruiter' ),
							'desc'		  => __( 'If enabled, submissions awaiting admin approval can be edited by the user.', 'wp-job-manager-recruiter' ),
							'type'		  => 'checkbox',
							'attributes'  => array()
						),
						array(
							'name'		  => 'recruiter_submission_notification',
							'std'		  => '1',
							'label'		  => __( 'Email New Submissions', 'wp-job-manager-recruiter' ),
							'cb_label'	  => __( 'Email resume details to the admin/notification recipient after submission.', 'wp-job-manager-recruiter' ),
							'desc'		  => sprintf( __( 'If enabled, all company details for new submissions will be emailed to %s.', 'wp-job-manager-recruiter' ), get_option( 'recruiter_email_notifications' ) ? get_option( 'recruiter_email_notifications' ) : get_option( 'admin_email' ) ),
							'type'		  => 'checkbox',
							'attributes'  => array()
						),
						array(
							'name'		  => 'recruiter_email_notifications',
							'std'		  => '',
							'placeholder' => get_option( 'admin_email' ),
							'label'		  => __( 'Notify Email Address(es)', 'wp-job-manager-recruiter' ),
							'desc'		  => __( 'Instead of the admin, email notifications to these folks instead. Comma separate addresses.', 'wp-job-manager-recruiter' ),
							'type'		  => 'input'
						),
						array(
							'name'		  => 'recruiter_submission_limit',
							'std'		  => '',
							'label'		  => __( 'Listing Limit', 'wp-job-manager-recruiter' ),
							'desc'		  => __( 'How many companies are users allowed to create. Can be left blank to allow unlimited companies per account.', 'wp-job-manager-recruiter' ),
							'attributes'  => array(),
							'placeholder' => __( 'No limit', 'wp-job-manager-recruiter' )
						),
						array(
							'name'		  => 'recruiter_default_employee_role',
							'std'		  => 'recruiter',
							'label'		  => __( 'Employee Role', 'wp-job-manager-recruiter' ),
							'desc'		  => __( 'Choose a default role for company employees.', 'wp-job-manager-recruiter' ),
							'type'		  => 'select',
							'options'	  => $account_roles
						),
					)
				),
				'company_pages' => array(
					__( 'Pages', 'wp-job-manager' ),
					array(
						array(
							'name'		=> 'recruiter_company_dashboard_page_id',
							'std'		=> '',
							'label'		=> __( 'Company Dashboard Page', 'wp-job-manager-recruiter' ),
							'desc'		=> __( 'Select the page where you have placed the [company_dashboard] shortcode. This lets the plugin know where the company dashboard is located.', 'wp-job-manager-recruiter' ),
							'type'		=> 'page'
						),
						array(
							'name'		=> 'recruiter_submit_company_form_page_id',
							'std'		=> '',
							'label'		=> __( 'Submit Company Page', 'wp-job-manager-recruiter' ),
							'desc'		=> __( 'Select the page where you have placed the [submit_company_form] shortcode. This lets the plugin know where the company profile is located.', 'wp-job-manager-recruiter' ),
							'type'		=> 'page'
						),
						array(
							'name'		=> 'recruiter_companies_page_id',
							'std'		=> '',
							'label'		=> __( 'Company Listings Page', 'wp-job-manager-recruiter' ),
							'desc'		=> __( 'Select the page where you have placed the [companies] shortcode. This lets the plugin know where the company listings page is located.', 'wp-job-manager-recruiter' ),
							'type'		=> 'page'
						),
						array(
							'name'		=> 'recruiter_employee_dashboard_page_id',
							'std'		=> '',
							'label'		=> __( 'Employee Dashboard Page', 'wp-job-manager-recruiter' ),
							'desc'		=> __( 'Select the page where you have placed the [employee_dashboard] shortcode. This lets the plugin know where the employee dashboard is located.', 'wp-job-manager-recruiter' ),
							'type'		=> 'page'
						),
						array(
							'name'		=> 'recruiter_submit_employee_form_page_id',
							'std'		=> '',
							'label'		=> __( 'Submit Employee Page', 'wp-job-manager-recruiter' ),
							'desc'		=> __( 'Select the page where you have placed the [submit_employee_form] shortcode. This lets the plugin know where the employee profile is located.', 'wp-job-manager-recruiter' ),
							'type'		=> 'page'
						),
					)
				),
				'company_visibility' => array(
					__( 'Company Visibility', 'wp-job-manager-recruiter' ),
					array(
						array(
							'name'		 => 'recruiter_view_companies_capability',
							'std'		 => '',
							'label'		 => __( 'View Company Profile Capability', 'wp-job-manager-recruiter' ),
							'type'		 => 'input',
							'desc'		 => sprintf( __( 'Enter the <a href="%s">capability</a> required in order to view company profiles. Supports a comma separated list of roles/capabilities.', 'wp-job-manager-recruiter' ), 'http://codex.wordpress.org/Roles_and_Capabilities' )
						),
						array(
							'name'		 => 'recruiter_browse_companies_capability',
							'std'		 => '',
							'label'		 => __( 'Browse Company Capability', 'wp-job-manager-recruiter' ),
							'type'		 => 'input',
							'desc'		 => sprintf( __( 'Enter the <a href="%s">capability</a> required in order to browse companies. Supports a comma separated list of roles/capabilities.', 'wp-job-manager-recruiter' ), 'http://codex.wordpress.org/Roles_and_Capabilities' )
						),
					),
				),
			)
		);
	}
}
