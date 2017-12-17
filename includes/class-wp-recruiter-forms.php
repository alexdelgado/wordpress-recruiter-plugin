<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_Recruiter_Forms {

	public function __construct() {
		add_action( 'init', array( $this, 'load_posted_form' ) );
	}

	public function load_posted_form() {

		if ( ! empty( $_POST['recruiter_form'] ) ) {
			$this->load_form_class( sanitize_title( $_POST['recruiter_form'] ) );
		}
	}

	private function load_form_class( $form_name = null ) {

		if ( ! class_exists( 'WP_Job_Manager_Form' ) ) {
			include( JOB_MANAGER_PLUGIN_DIR . '/includes/abstracts/abstract-wp-job-manager-form.php' );
		}

		// Now try to load the form_name
		$form_class  = 'WP_Recruiter_Form_' . str_replace( '-', '_', $form_name );
		$form_file   = RECRUITER_PLUGIN_DIR . '/includes/forms/class-wp-recruiter-form-' . $form_name . '.php';

		if ( class_exists( $form_class ) ) {
			return call_user_func( array( $form_class, 'instance' ) );
		}

		if ( ! file_exists( $form_file ) ) {
			return false;
		}

		if ( ! class_exists( $form_class ) ) {
			include $form_file;
		}

		// Init the form
		return call_user_func( array( $form_class, 'instance' ) );
	}

	public function get_form( $form_name, $atts = array() ) {

		if ( $form = $this->load_form_class( $form_name ) ) {
			ob_start();
			$form->output( $atts );
			return ob_get_clean();
		}

	}
}
