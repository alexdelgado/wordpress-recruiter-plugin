<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_Recruiter_Form_Submit_Employee extends WP_Job_Manager_Form {

	public	  $form_name = 'submit-employee';
	protected $employee_id;

	protected static $_instance = null;

	public function __construct() {

		add_action( 'wp', array( $this, 'process' ) );

		$this->steps  = (array) apply_filters(
			'submit_employee_steps',
			array(
				'submit' => array(
					'name'	   => __( 'Submit Details', 'wp-job-manager-recruiter' ),
					'view'	   => array( $this, 'submit' ),
					'handler'  => array( $this, 'submit_handler' ),
					'priority' => 10
				),
				'done' => array(
					'name'	   => __( 'Done', 'wp-job-manager-recruiter' ),
					'view'	   => array( $this, 'done' ),
					'handler'  => '',
					'priority' => 20
				)
			)
		);

		uasort( $this->steps, array( $this, 'sort_by_priority' ) );

		// Get step/employee
		if ( ! empty( $_REQUEST['step'] ) ) {
			$this->step = ( is_numeric( $_REQUEST['step'] ) ? max( absint( $_REQUEST['step'] ), 0 ) : array_search( $_REQUEST['step'], array_keys( $this->steps ) ) );
		}

		$this->employee_id = ( ! empty( $_REQUEST['employee_id'] ) ? absint( $_REQUEST[ 'employee_id' ] ) : 0 );

		if ( ! recruiter_can_user_edit_employee( $this->employee_id ) ) {
			$this->employee_id = 0;
		}

		// Load employee details
		if ( $this->employee_id ) {

			if ( 0 === $this->step && empty( $_POST['employee_application_submit_button'] ) ) {

				$this->employee_id = 0;
				$this->step		   = 0;

			}
		}
	}

	public function init_fields() {

		if ( $this->fields ) {
			return;
		}

		$this->fields = apply_filters(
			'submit_employee_form_fields',
			array(
				'employee_fields' => array(
					'employee_first' => array(
						'label'		  => __( 'First name', 'wp-job-manager-recruiter' ),
						'type'		  => 'text',
						'required'	  => true,
						'placeholder' => '',
						'priority'	  => 1
					),
					'employee_last' => array(
						'label'		  => __( 'Last name', 'wp-job-manager-recruiter' ),
						'type'		  => 'text',
						'required'	  => true,
						'placeholder' => '',
						'priority'	  => 2
					),
					'employee_email' => array(
						'label'		  => __( 'Email address', 'wp-job-manager-recruiter' ),
						'type'		  => 'text',
						'required'	  => true,
						'placeholder' => '',
						'priority'	  => 3,
					),
					'companies' => array(
						'label'		  => __( 'Companies', 'wp-job-manager-recruiter' ),
						'add_row'	  => __( 'Add Company', 'wp-job-manager-recruiter' ),
						'type'		  => 'companies', // repeated
						'required'	  => true,
						'placeholder' => '',
						'description' => __( 'Select which companies this employee belongs to.', 'wp-job-manager-recruiter' ),
						'priority'	  => 4,
						'fields'	  => array(
							'company_id' => array(
								'label'		  => __( 'Name', 'wp-job-manager-recruiter' ),
								'type'		  => 'select',
								'required'	  => true,
								'placeholder' => '',
								'priority'	  => 1,
								'options'	  => $this->get_user_company_options()
							),
							'company_role' => array(
								'label'		  => __( 'Role', 'wp-job-manager-recruiter' ),
								'type'		  => 'select',
								'required'	  => true,
								'placeholder' => '',
								'priority'	  => 2,
								'options'	  => array(
									'manage_listings' => 'LIMITED, this employee can only manage company job listings.',
									'manage_company'  => 'ALL-ACCESS, this employee can manage my company profile, employees, and job listings.',
								)
							)
						)
					)
				)
			)
		);
	}

	public function get_user_company_options() {

		$companies = recruiter_get_user_companies_options( $this->employee_id, 'company' );

		$options = array();

		if ( ! empty( $companies ) ) {
			foreach ( $companies as $company ) {
				$options[ $company->ID ] = $company->post_title;
			}
		}

		return $options;
	}

	public function get_field_template( $key, $field ) {

		switch ( $field['type'] ) {

			case 'repeated' :
			case 'companies' :
				get_job_manager_template(
					'form-fields/repeated-field.php',
					array(
						'key' => $key,
						'field' => $field,
						'class' => $this
					),
					'wp-job-manager-recruiter',
					RECRUITER_PLUGIN_DIR . '/templates/'
				);
				break;

			default :
				get_job_manager_template(
					'form-fields/' . $field['type'] . '-field.php',
					array(
						'key' => $key,
						'field' => $field,
						'class' => $this
					)
				);
				break;
		}
	}

	public function get_repeated_field( $field_prefix, $fields ) {

		$items		 = array();
		$field_keys	 = array_keys( $fields );

		if ( ! empty( $_POST[ 'repeated-row-' . $field_prefix ] ) && is_array( $_POST[ 'repeated-row-' . $field_prefix ] ) ) {

			$indexes = array_map( 'absint', $_POST[ 'repeated-row-' . $field_prefix ] );

			foreach ( $indexes as $index ) {

				$item = array();

				foreach ( $fields as $key => $field ) {

					$field_name = "{$field_prefix}_{$key}_{$index}";

					switch ( $field['type'] ) {

						case 'textarea' :
							$item[ $key ] = wp_kses_post( stripslashes( $_POST[ $field_name ] ) );
							break;

						case 'file' :
							$file = $this->upload_file( $field_name, $field );

							if ( ! $file ) {
								$file = $this->get_posted_field( 'current_' . $field_name, $field );
							} elseif ( is_array( $file ) ) {
								$file = array_filter( array_merge( $file, (array) $this->get_posted_field( 'current_' . $field_name, $field ) ) );
							}

							$item[ $key ] = $file;
							break;

						default :
							if ( is_array( $_POST[ $field_name ] ) ) {
								$item[ $key ] = array_filter( array_map( 'sanitize_text_field', array_map( 'stripslashes', $_POST[ $field_name ] ) ) );
							} else {
								$item[ $key ] = sanitize_text_field( stripslashes( $_POST[ $field_name ] ) );
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

		return $items;
	}

	public function submit() {
		global $job_manager, $post;

		$this->init_fields();

		// Load data if neccessary
		if ( $this->employee_id ) {

			$employee = get_userdata( $this->employee_id );

			foreach ( $this->fields as $group_key => $fields ) {

				foreach ( $fields as $key => $field ) {

					switch ( $key ) {

						case 'employee_first' :
							$this->fields[ $group_key ][ $key ]['value'] = $employee->first_name;
							break;

						case 'employee_last' :
							$this->fields[ $group_key ][ $key ]['value'] = $employee->last_name;
							break;

						case 'employee_email' :
							$this->fields[ $group_key ][ $key ]['value'] = $employee->last_name;
							break;

					}
				}
			}

			$this->fields = apply_filters( 'submit_employee_form_fields_get_employee_data', $this->fields, $employee );

		}

		get_job_manager_template(
			'employee-submit.php',
			array(
				'class'				 => $this,
				'form'				 => $this->form_name,
				'employee_id'		 => absint( $this->employee_id ),
				'action'			 => $this->get_action(),
				'employee_fields'	 => $this->get_fields( 'employee_fields' ),
				'step'				 => $this->get_step(),
				'submit_button_text' => apply_filters( 'submit_employee_form_submit_button_text', __( 'Continue &rarr;', 'wp-job-manager-recruiter' ) )
			),
			'wp-job-manager-recruiter',
			RECRUITER_PLUGIN_DIR . '/templates/'
		);
	}

	public function submit_handler() {

		try {

			// Init fields
			$this->init_fields();

			// Get posted values
			$values = $this->get_posted_fields();

			if ( empty( $_POST['submit_employee'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'submit_form_posted' ) ) {
				return;
			}

			// Validate required
			if ( is_wp_error( ( $return = $this->validate_fields( $values ) ) ) ) {
				throw new Exception( $return->get_error_message() );
			}

			if ( ! is_user_logged_in() ) {
				throw new Exception( __( 'You must be signed in to manage your employees.', 'wp-job-manager-recruiter' ) );
			}

			// Update the employee
			$this->save_employee( $values['employee_fields'] );

			// Successful, show next step
			$this->step ++;

		} catch ( Exception $e ) {
			$this->add_error( $e->getMessage() );
			return;
		}
	}

	public function get_posted_repeated_field( $key, $field ) {
		return apply_filters( 'submit_employee_form_fields_get_repeated_field_data', $this->get_repeated_field( $key, $field['fields'] ) );
	}

	public function get_posted_companies_field( $key, $field ) {
		return apply_filters( 'submit_resume_form_fields_get_companies_data', $this->get_repeated_field( $key, $field['fields'] ) );
	}

	public function done() {

		do_action( 'recruiter_employee_submitted', $this->employee_id );

		get_job_manager_template(
			'employee-submitted.php',
			array( 'employee' => get_post( $this->employee_id ) ),
			'wp-job-manager-recruiter',
			RECRUITER_PLUGIN_DIR . '/templates/'
		);
	}

	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	protected function validate_fields( $values ) {

		foreach ( $this->fields as $group_key => $fields ) {

			foreach ( $fields as $key => $field ) {

				if ( $field['required'] && empty( $values[ $group_key ][ $key ] ) ) {
					return new WP_Error( 'validation-error', sprintf( __( '%s is a required field', 'wp-job-manager-recruiter' ), $field['label'] ) );
				}

				if ( ! empty( $field['taxonomy'] ) && in_array( $field['type'], array( 'term-checklist', 'term-select', 'term-multiselect' ) ) ) {

					if ( is_array( $values[ $group_key ][ $key ] ) ) {

						foreach ( $values[ $group_key ][ $key ] as $term ) {

							if ( ! term_exists( $term, $field['taxonomy'] ) ) {
								return new WP_Error( 'validation-error', sprintf( __( '%s is invalid', 'wp-job-manager-recruiter' ), $field['label'] ) );
							}

						}

					} elseif ( ! empty( $values[ $group_key ][ $key ] ) ) {

						if ( ! term_exists( $values[ $group_key ][ $key ], $field['taxonomy'] ) ) {
							return new WP_Error( 'validation-error', sprintf( __( '%s is invalid', 'wp-job-manager-recruiter' ), $field['label'] ) );
						}

					}

				}
			}
		}

		return apply_filters( 'submit_resume_form_validate_fields', true, $this->fields, $values );
	}

	protected function save_employee( $values = array() ) {

		if ( ! empty( $this->employee_id ) ) {

			if ( ! recruiter_can_user_edit_employee( $this->employee_id ) ) {
				return false;
			}

		} else if ( ! current_user_can( 'manage_company' ) ) {
			return false;
		}

		$data = apply_filters( 'submit_employee_form_save_employee_data', $values, $this );

		// get posted companies
		$companies_instance = WP_Recruiter_Companies::instance();
		$posted_companies = $this->get_posted_companies_field('companies', $this->fields['employee_fields']['companies']);

		$user = array(
			'ID'		 => $this->employee_id,
			'first_name' => sanitize_text_field( $data['employee_first'] ),
			'last_name'	 => sanitize_text_field( $data['employee_last'] ),
			'user_email' => sanitize_text_field( $data['employee_email'] ),
			'role'		 => $companies_instance->set_employee_role( $posted_companies )
		);

		if ( $this->employee_id ) {

			wp_update_user( $user );

		} else {

			if ( ! empty( $user['user_email'] ) ) {

				$create_account = recruiter_create_employee(
					array(
						'first_name' => $user['first_name'],
						'last_name'	 => $user['last_name'],
						'username'	 => '',
						'email'		 => $user['user_email'],
						'role'		 => $user['role'],
					)
				);

			}

			if ( is_wp_error( $create_account ) ) {
				throw new Exception( $create_account->get_error_message() );
			}

			$this->employee_id = $create_account;

		}

		// get current companies
		$current_companies = $companies_instance->get_user_company_ids( $this->employee_id );

		if ( ! empty( $posted_companies ) ) {

			foreach ( $posted_companies as $company ) {

				$key = array_search( $company['company_id'], $current_companies );

				// remove company from $old_companies
				if ( false !== $key ) {
					unset( $current_companies[ $key ] );
				}

				$companies_instance->update_user_role( $company['company_id'], $company['company_role'], $this->employee_id );
			}
		}

		// delete values in $current_companies
		if ( ! empty( $current_companies ) ) {
			foreach ( $current_companies as $company ) {
				$companies_instance->delete_user_role( $company, $this->employee_id );
			}
		}
	}

}
