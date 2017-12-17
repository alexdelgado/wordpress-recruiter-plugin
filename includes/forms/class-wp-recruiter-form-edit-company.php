<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once( 'class-wp-recruiter-form-submit-company.php' );

class WP_Recruiter_Form_Edit_Company extends WP_Recruiter_Form_Submit_Company {

	public $form_name = 'edit-company';

	protected static $_instance = null;

	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {

		$this->company_id = ( ! empty( $_REQUEST['company_id'] ) ? absint( $_REQUEST[ 'company_id' ] ) : 0 );

		if	( ! recruiter_can_user_edit_company( $this->company_id ) ) {
			$this->company_id = 0;
		}
	}

	public function output( $atts = array() ) {
		$this->submit_handler();
		$this->submit();
	}

	public function submit_handler() {

		if ( empty( $_POST['submit_company'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'submit_form_posted' ) ) {
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

			// Update the company
			$this->save_company( $values['company_fields']['company_name'], $values['company_fields']['company_content'], get_post_status( $this->company_id ), $values );
			$this->update_company_data( $values );

			// Successful
			echo '<div class="job-manager-message">' . __( 'Your changes have been saved.', 'wp-job-manager-recruiter' ), ' <a href="' . get_permalink( $this->company_id ) . '">' . __( 'View Company &rarr;', 'wp-job-manager-recruiter' ) . '</a>' . '</div>';

		} catch ( Exception $e ) {
			echo '<div class="job-manager-error">' . $e->getMessage() . '</div>';
			return;
		}
	}

	public function submit() {
		global $post;

		if ( empty( $this->company_id  ) ) {
			echo apply_filters( 'recruiter_invalid_company', wpautop( __( 'Invalid company', 'wp-job-manager-recruiter' ) ) );
			return;
		}

		$company = get_post( $this->company_id );

		if ( $company->post_status !== 'publish' && $company->post_status !== 'hidden' ) {

			if( ! get_option( 'recruiter_can_user_edit_pending_submissions' ) ) {
				echo wpautop( __( 'Invalid company', 'wp-job-manager-recruiter' ) );
				return;
			}
		}

		$this->init_fields();

		foreach ( $this->fields as $group_key => $group_fields ) {

			foreach ( $group_fields as $key => $field ) {

				if ( ! isset( $this->fields[ $group_key ][ $key ]['value'] ) ) {

					if ( 'company_name' === $key ) {

						$this->fields[ $group_key ][ $key ]['value'] = $company->post_title;

					} elseif ( 'company_content' === $key ) {

						$this->fields[ $group_key ][ $key ]['value'] = $company->post_content;

					} elseif ( ! empty( $field['taxonomy'] ) ) {

						$this->fields[ $group_key ][ $key ]['value'] = wp_get_object_terms( $company->ID, $field['taxonomy'], array( 'fields' => 'ids' ) );

					} else {

						$this->fields[ $group_key ][ $key ]['value'] = get_post_meta( $company->ID, '_' . $key, true );
					}
				}
			}
		}

		$this->fields = apply_filters( 'submit_company_form_fields_get_company_data', $this->fields, $company );

		get_job_manager_template(
			'company-submit.php',
			array(
				'class'				 => $this,
				'form'				 => $this->form_name,
				'company_id'		 => absint( $this->company_id ),
				'action'			 => $this->get_action(),
				'company_fields'	 => $this->get_fields( 'company_fields' ),
				'step'				 => $this->get_step(),
				'submit_button_text' => __( 'Save changes', 'wp-job-manager-recruiter' )
			),
			'wp-job-manager-recruiter',
			RECRUITER_PLUGIN_DIR . '/templates/'
		);
	}
}
