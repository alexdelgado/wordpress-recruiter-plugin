<?php
/**
 * Plugin Name: WP Job Manager - Recruiter
 * Plugin URI:
 * Description: Allow recruiters to create and manage their agency profiles.
 * Version: 0.1.0
 * Author: Alex Delgado
 * Author URI: http://alexdelgado.github.io
 * Requires at least: 4.1
 * Tested up to: 4.5
 * Copyright: 2017 Alex Delgado
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Recruiter class.
 */
class WP_Recruiter {

	/**
	 * __construct function.
	 */
	public function __construct() {

		// Define constants
		define( 'RECRUITER_VERSION', '0.1.0' );
		define( 'RECRUITER_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'RECRUITER_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );

		// Includes
		include( 'includes/wp-recruiter-functions.php' );
		include( 'includes/wp-recruiter-template.php' );
		include( 'includes/class-wp-recruiter-post-types.php' );
		include( 'includes/class-wp-recruiter-companies.php' );
		include( 'includes/class-wp-recruiter-forms.php' );
		include( 'includes/class-wp-recruiter-ajax.php' );
		include( 'includes/class-wp-recruiter-shortcodes.php' );

		// Init classes
		$this->post_types = new WP_Recruiter_Post_Types();
		$this->forms = new WP_Recruiter_Forms();
		$this->companies = new WP_Recruiter_Companies();

		// Activation - works with symlinks
		register_activation_hook( basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ), array( $this->post_types, 'register_post_types' ), 10 );
		register_activation_hook( basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ), create_function( '', "include_once( 'includes/class-wp-recruiter-install.php' );" ), 10 );
		register_activation_hook( basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ), 'flush_rewrite_rules', 15 );

		// Actions
		add_action( 'admin_notices', array( $this, 'version_check' ) );
		add_action( 'plugins_loaded', array( $this, 'admin' ) );
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
		add_action( 'switch_theme', array( $this->post_types, 'register_post_types' ), 10 );
		add_action( 'switch_theme', 'flush_rewrite_rules', 15 );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
		add_action( 'admin_init', array( $this, 'updater' ) );

	}

	/**
	 * Check JM version
	 */
	public function version_check() {

		$required_jm_version = '1.22.0';

		if ( ! defined( 'JOB_MANAGER_VERSION' ) ) {

			printf( '<div class="error"><p>%s</p></div>', __( 'Recruiter requires WP Job Manager to be installed!', 'wp-job-manager-applications' ) );

		} elseif ( version_compare( JOB_MANAGER_VERSION, $required_jm_version, '<' ) ) {

			printf( '<div class="error"><p>%s</p></div>', __( 'Recruiter requires WP Job Manager %s (you are using %s)', 'wp-job-manager-applications' ), $required_jm_version, JOB_MANAGER_VERSION );

		}
	}

	/**
	 * Handle Updates
	 */
	public function updater() {

		if ( version_compare( RECRUITER_VERSION, get_option( 'wp_recruiter_version' ), '>' ) ) {
			include_once( 'includes/class-wp-recruiter-install.php' );
		}

	}

	/**
	 * Include admin
	 */
	public function admin() {
		if ( is_admin() && class_exists( 'WP_Job_Manager' ) ) {
			include( 'includes/admin/class-wp-recruiter-admin.php' );
		}
	}

	/**
	 * Localisation
	 *
	 * @access private
	 * @return void
	 */
	public function load_plugin_textdomain() {

		$locale = apply_filters( 'plugin_locale', get_locale(), 'wp-job-manager-recruiter' );

		load_textdomain( 'wp-job-manager-recruiter', WP_LANG_DIR . "/wp-job-manager-recruiter/wp-job-manager-recruiter-$locale.mo" );
		load_plugin_textdomain( 'wp-job-manager-recruiter', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * frontend_scripts function.
	 *
	 * @access public
	 * @return void
	 */
	public function frontend_scripts() {
		global $post;

		$ajax_url         = admin_url( 'admin-ajax.php', 'relative' );
		$ajax_filter_deps = array( 'jquery' );

		// WPML workaround until this is standardized
		if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
			$ajax_url = add_query_arg( 'lang', ICL_LANGUAGE_CODE, $ajax_url );
		}

		if ( apply_filters( 'job_manager_chosen_enabled', true ) ) {
			$ajax_filter_deps[] = 'chosen';
		}

		wp_register_script( 'wp-recruiter-ajax-filters', RECRUITER_PLUGIN_URL . '/assets/js/ajax-filters.min.js', $ajax_filter_deps, RECRUITER_VERSION, true );
		wp_register_script( 'wp-recruiter-employee-dashboard', RECRUITER_PLUGIN_URL . '/assets/js/employee-dashboard.min.js', array( 'jquery' ), RECRUITER_VERSION, true );
		wp_register_script( 'wp-recruiter-employee-submission', RECRUITER_PLUGIN_URL . '/assets/js/employee-submission.min.js', array( 'jquery', 'jquery-ui-sortable' ), RECRUITER_VERSION, true );
		wp_register_script( 'wp-recruiter-company-dashboard', RECRUITER_PLUGIN_URL . '/assets/js/company-dashboard.min.js', array( 'jquery' ), RECRUITER_VERSION, true );
		wp_register_script( 'wp-recruiter-company-submission', RECRUITER_PLUGIN_URL . '/assets/js/company-submission.min.js', array( 'jquery', 'jquery-ui-sortable' ), RECRUITER_VERSION, true );
		wp_register_script( 'wp-recruiter-company-contact-details', RECRUITER_PLUGIN_URL . '/assets/js/contact-details.min.js', array( 'jquery' ), RECRUITER_VERSION, true );

		wp_localize_script(
			'wp-recruiter-company-submission',
			'recruiter_company_submission',
			array(
				'i18n_navigate'       => __( 'If you wish to edit the posted details use the "edit company" button instead, otherwise changes may be lost.', 'wp-job-manager-recruiter' ),
				'i18n_confirm_remove' => __( 'Are you sure you want to remove this item?', 'wp-job-manager-recruiter' ),
				'i18n_remove'         => __( 'remove', 'wp-job-manager-recruiter' )
			)
		);

		wp_localize_script(
			'wp-recruiter-ajax-filters',
			'recruiter_ajax_filters',
			array(
				'ajax_url' => $ajax_url
			)
		);

		wp_localize_script(
			'wp-recruiter-company-dashboard',
			'recruiter_company_dashboard',
			array(
				'i18n_confirm_delete' => __( 'Are you sure you want to delete this company?', 'wp-job-manager-recruiter' )
			)
		);

		wp_localize_script(
			'wp-recruiter-employee-dashboard',
			'recruiter_employee_dashboard',
			array(
				'i18n_confirm_delete' => __( 'Are you sure you want to delete this employee?', 'wp-job-manager-recruiter' )
			)
		);

		wp_enqueue_style( 'wp-job-manager-recruiter-frontend', RECRUITER_PLUGIN_URL . '/assets/css/frontend.css' );

		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'submit_company_form') ) {
			wp_enqueue_style( 'wp-recruiter-company-submission', RECRUITER_PLUGIN_URL . '/assets/css/company-submission.css', array(), RECRUITER_VERSION );
		}
	}
}

$GLOBALS['recruiter'] = new WP_Recruiter();
