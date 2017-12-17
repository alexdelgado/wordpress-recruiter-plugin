<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_Recruiter_Post_Types {

	public function __construct() {
		add_action( 'init', array( $this, 'register_post_types' ), 0 );

		add_action( 'save_post', array( $this, 'flush_get_companies_cache' ) );
		add_action( 'recruiter_my_company_do_action', array( $this, 'recruiter_my_company_do_action' ) );
	}

	public function register_post_types() {

		if ( post_type_exists( 'company' ) )
			return;

		$admin_capability = 'manage_company';

		if ( get_option( 'recruiter_enable_company_categories' ) ) {

			$singular  = __( 'Company Category', 'wp-job-manager-recruiter' );
			$plural	   = __( 'Company Categories', 'wp-job-manager-recruiter' );

			if ( current_theme_supports( 'recruiter-templates' ) ) {

				$rewrite =
					array(
						'slug'		   => _x( 'company-category', 'Company category slug - resave permalinks after changing this', 'wp-job-manager-recruiter' ),
						'with_front'   => false,
						'hierarchical' => false
					);

			} else {

				$rewrite = false;

			}

			register_taxonomy(
				'company_category',
				array( 'company' ),
				array(
					'hierarchical'			=> true,
					'update_count_callback' => '_update_post_term_count',
					'label'					=> $plural,
					'labels' => array(
						'name'				=> $plural,
						'singular_name'		=> $singular,
						'search_items'		=> sprintf( __( 'Search %s', 'wp-job-manager-recruiter' ), $plural ),
						'all_items'			=> sprintf( __( 'All %s', 'wp-job-manager-recruiter' ), $plural ),
						'parent_item'		=> sprintf( __( 'Parent %s', 'wp-job-manager-recruiter' ), $singular ),
						'parent_item_colon' => sprintf( __( 'Parent %s:', 'wp-job-manager-recruiter' ), $singular ),
						'edit_item'			=> sprintf( __( 'Edit %s', 'wp-job-manager-recruiter' ), $singular ),
						'update_item'		=> sprintf( __( 'Update %s', 'wp-job-manager-recruiter' ), $singular ),
						'add_new_item'		=> sprintf( __( 'Add New %s', 'wp-job-manager-recruiter' ), $singular ),
						'new_item_name'		=> sprintf( __( 'New %s Name', 'wp-job-manager-recruiter' ),  $singular )
					),
					'show_ui'				=> true,
					'query_var'				=> true,
					'capabilities'			=> array(
						'manage_terms'		=> $admin_capability,
						'edit_terms'		=> $admin_capability,
						'delete_terms'		=> $admin_capability,
						'assign_terms'		=> $admin_capability,
					),
					'rewrite'				=> $rewrite,
				)
			);
		}

		$singular  = __( 'Company', 'wp-job-manager-recruiter' );
		$plural	   = __( 'Companies', 'wp-job-manager-recruiter' );

		if ( current_theme_supports( 'recruiter-templates' ) ) {
			$has_archive = _x( 'companies', 'Post type archive slug - resave permalinks after changing this', 'wp-job-manager-recruiter' );
		} else {
			$has_archive = false;
		}

		$rewrite =
			array(
				'slug'		 => _x( 'company', 'Company permalink - resave permalinks after changing this', 'wp-job-manager-recruiter' ),
				'with_front' => false,
				'feeds'		 => false,
				'pages'		 => false
			);

		register_post_type(
			'company',
			apply_filters(
				'register_post_type_company',
				array(
					'labels' => array(
						'name'					=> $plural,
						'singular_name'			=> $singular,
						'menu_name'				=> $plural,
						'all_items'				=> sprintf( __( 'All %s', 'wp-job-manager-recruiter' ), $plural ),
						'add_new'				=> __( 'Add New', 'wp-job-manager-recruiter' ),
						'add_new_item'			=> sprintf( __( 'Add %s', 'wp-job-manager-recruiter' ), $singular ),
						'edit'					=> __( 'Edit', 'wp-job-manager-recruiter' ),
						'edit_item'				=> sprintf( __( 'Edit %s', 'wp-job-manager-recruiter' ), $singular ),
						'new_item'				=> sprintf( __( 'New %s', 'wp-job-manager-recruiter' ), $singular ),
						'view'					=> sprintf( __( 'View %s', 'wp-job-manager-recruiter' ), $singular ),
						'view_item'				=> sprintf( __( 'View %s', 'wp-job-manager-recruiter' ), $singular ),
						'search_items'			=> sprintf( __( 'Search %s', 'wp-job-manager-recruiter' ), $plural ),
						'not_found'				=> sprintf( __( 'No %s found', 'wp-job-manager-recruiter' ), $plural ),
						'not_found_in_trash'	=> sprintf( __( 'No %s found in trash', 'wp-job-manager-recruiter' ), $plural ),
						'parent'				=> sprintf( __( 'Parent %s', 'wp-job-manager-recruiter' ), $singular )
					),
					'description' => __( 'This is where you can create and manage companies.', 'wp-job-manager-recruiter' ),
					'public'					=> true,
					'show_ui'					=> true,
					'menu_position'				=> 20,
					'menu_icon'					=> 'dashicons-store',
					'capability_type'			=> 'post',
					'capabilities' => array(
						'publish_posts'			=> $admin_capability,
						'edit_posts'			=> $admin_capability,
						'edit_others_posts'		=> $admin_capability,
						'delete_posts'			=> $admin_capability,
						'delete_others_posts'	=> $admin_capability,
						'read_private_posts'	=> $admin_capability,
						'edit_post'				=> $admin_capability,
						'delete_post'			=> $admin_capability,
						'read_post'				=> $admin_capability
					),
					'hierarchical'				=> false,
					'supports'					=> array( 'title', 'editor', 'excerpt', 'custom-fields' ),
					'has_archive'				=> $has_archive,
					'rewrite'					=> $rewrite,
					'query_var'					=> true,
					'delete_with_user'			=> true,
				)
			)
		);

	}

	public function flush_get_companies_cache( $post_id ) {
		if ( 'company' === get_post_type( $post_id ) ) {
			WP_Job_Manager_Cache_Helper::get_transient_version( 'get_company_listings', true );
		}
	}

	public function recruiter_my_company_do_action( $action ) {
		WP_Job_Manager_Cache_Helper::get_transient_version( 'get_company_listings', true );
	}
}
