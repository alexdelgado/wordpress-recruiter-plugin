<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_Recruiter_Admin {

	public function __construct() {

		include_once( 'class-wp-recruiter-cpt.php' );
		include_once( 'class-wp-recruiter-writepanels.php' );
		include_once( 'class-wp-recruiter-settings.php' );
		include_once( 'class-wp-recruiter-setup.php' );

		add_action( 'job_manager_admin_screen_ids', array( $this, 'add_screen_ids' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 20 );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 12 );

		$this->settings_page = new WP_Recruiter_Settings();
	}

	public function add_screen_ids( $screen_ids = array() ) {

		$screen_ids[] = 'edit-company';
		$screen_ids[] = 'company';

		return $screen_ids;
	}

	public function admin_enqueue_scripts() {
		global $wp_scripts;

		$jquery_version = ( isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.9.2' );

		wp_enqueue_style( 'jquery-ui-style', '//ajax.googleapis.com/ajax/libs/jqueryui/' . $jquery_version . '/themes/smoothness/jquery-ui.css' );
		wp_enqueue_style( 'recruiter_admin_css', RECRUITER_PLUGIN_URL . '/assets/css/admin.css' );

		wp_register_script( 'jquery-tiptip', JOB_MANAGER_PLUGIN_URL. '/assets/js/jquery-tiptip/jquery.tipTip.min.js', array( 'jquery' ), JOB_MANAGER_VERSION, true );
		wp_enqueue_script( 'recruiter_admin_js', RECRUITER_PLUGIN_URL. '/assets/js/admin.min.js', array( 'jquery', 'jquery-tiptip', 'jquery-ui-datepicker', 'jquery-ui-sortable' ), RECRUITER_VERSION, true );
	}

	public function admin_menu() {
		add_submenu_page( 'edit.php?post_type=company', __( 'Settings', 'wp-job-manager-recruiter' ), __( 'Settings', 'wp-job-manager-recruiter' ), 'manage_options', 'recruiter-settings', array( $this->settings_page, 'output' ) );
	}
}

new WP_Recruiter_Admin();
