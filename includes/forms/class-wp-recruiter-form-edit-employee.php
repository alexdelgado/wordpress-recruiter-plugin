<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once( 'class-wp-recruiter-form-submit-employee.php' );

class WP_Recruiter_Form_Edit_Employee extends WP_Recruiter_Form_Submit_Employee {

	public $form_name = 'edit-employee';

	protected static $_instance = null;

	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {

		$this->employee_id = ( ! empty( $_REQUEST['employee_id'] ) ? absint( $_REQUEST[ 'employee_id' ] ) : 0 );

		if	( ! recruiter_can_user_edit_employee( $this->employee_id ) ) {
			$this->employee_id = 0;
		}
	}

	public function output( $atts = array() ) {
		$this->submit_handler();
		$this->submit();
	}

	public function submit_handler() {

		if ( empty( $_POST['submit_employee'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'submit_form_posted' ) ) {
			return;
		}

		try {

			// Init fields
			$this->init_fields();

			// Get posted values
			$values = $this->get_posted_fields();

			// Validate required
			if ( is_wp_error( ( $return = $this->validate_fields( $values ) ) ) ) {
				throw new Exception( $return->get_error_message() );
			}

			// Update the employee
			$this->save_employee( $values['employee_fields'] );

			// Successful
			echo '<div class="job-manager-message">' . __( 'Your changes have been saved.', 'wp-job-manager-recruiter' ) . '</div>';

		} catch ( Exception $e ) {
			echo '<div class="job-manager-error">' . $e->getMessage() . '</div>';
			return;
		}
	}

	public function submit() {
		global $post;

		if ( empty( $this->employee_id	) || ! $employee = get_userdata( $this->employee_id ) ) {
			echo apply_filters( 'recruiter_invalid_employee', wpautop( __( 'Invalid employee', 'wp-job-manager-recruiter' ) ) );
			return;
		}

		$this->init_fields();

		foreach ( $this->fields as $group_key => $group_fields ) {

			foreach ( $group_fields as $key => $field ) {

				if ( ! isset( $this->fields[ $group_key ][ $key ]['value'] ) ) {

					if ( 'employee_first' === $key ) {

						$this->fields[ $group_key ][ $key ]['value'] = $employee->first_name;

					} elseif ( 'employee_last' === $key ) {

						$this->fields[ $group_key ][ $key ]['value'] = $employee->last_name;

					} elseif ( 'employee_email' === $key ) {

						$this->fields[ $group_key ][ $key ]['value'] = $employee->user_email;

					} elseif ( 'companies' === $key ) {

						$this->fields[ $group_key ][ $key ]['value'] = $this->get_user_companies();
					}
				}
			}
		}

		$this->fields = apply_filters( 'submit_employee_form_fields_get_employee_data', $this->fields, $employee );

		get_job_manager_template(
			'employee-submit.php',
			array(
				'class'				 => $this,
				'form'				 => $this->form_name,
				'employee_id'		 => absint( $this->employee_id ),
				'action'			 => $this->get_action(),
				'employee_fields'	 => $this->get_fields( 'employee_fields' ),
				'step'				 => $this->get_step(),
				'submit_button_text' => __( 'Save changes', 'wp-job-manager-recruiter' )
			),
			'wp-job-manager-recruiter',
			RECRUITER_PLUGIN_DIR . '/templates/' );
	}

	public function get_user_companies() {

		$options = array();
		$companies = recruiter_get_user_companies_options( $this->employee_id, 'employee' );

		if ( ! empty( $companies ) ) {
			foreach( $companies as $company ) {
				$options[$company->ID] = array(
					'company_id' => $company->ID,
					'company_role' => $company->role
				);
			}
		}

		return $options;
	}
}
