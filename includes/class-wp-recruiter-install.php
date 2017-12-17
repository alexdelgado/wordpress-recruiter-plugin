<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_Recruiter_Install {

	public function __construct() {
		$this->init_user_roles();
		$this->create_companies_table();
	}

	public function init_user_roles() {
		global $wp_roles;

		$admin_capability = 'manage_company';

		if ( null !== ( $employer = get_role( 'employer' ) ) && null === get_role( 'recruiter' ) ) {

			$capabilities = $employer->capabilities;

			// prevent recruiters from updating company profile
			unset( $capabilities[ $admin_capability ] );

			add_role(
				'recruiter',
				__( 'Recruiter', 'wp-job-manager-recruiter' ),
				$capabilities
			);
		}

		if ( class_exists( 'WP_Roles' ) && ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		if ( is_object( $wp_roles ) ) {

			$wp_roles->add_cap( 'administrator', $admin_capability );
			$wp_roles->add_cap( 'employer', $admin_capability );

			$wp_roles->add_cap( 'administrator', 'edit_job_listings' );
			$wp_roles->add_cap( 'employer', 'edit_job_listings' );
			$wp_roles->add_cap( 'recruiter', 'edit_job_listings' );


		}
	}

	public function create_companies_table() {
		global $wpdb;

		 $table_name = "{$wpdb->prefix}job_manager_company_meta";
		 $charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			company_id bigint(20) unsigned NOT NULL DEFAULT '0',
			meta_key varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
			meta_value longtext COLLATE utf8mb4_unicode_ci,
			PRIMARY KEY	 (meta_id),
			KEY company_id (company_id),
			KEY meta_key (meta_key)
		) $charset_collate;";

		 require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		 dbDelta( $sql );

	}

}

new WP_Recruiter_Install();
