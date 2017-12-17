<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function recruiter_get_user_companies() {
	return WP_Recruiter_Companies::instance()->get_user_companies();
}

function recruiter_get_user_companies_options( $employee_id = null, $scope = null ) {
	return WP_Recruiter_Companies::instance()->get_user_companies_options( $employee_id, $scope );
}

function recruiter_count_user_companies( $user_id = 0 ) {
	return count( WP_Recruiter_Companies::instance()->get_user_companies() );
}

function recruiter_can_user_browse_companies() {

	$can_browse = true;
	$capability = get_option( 'recruiter_browse_company_capability' );

	if ( ! empty( $capability ) ) {

		$caps = array_filter(
			array_map(
				'trim',
				array_map(
					'strtolower',
					explode( ',', $capability )
				)
			)
		);

		$can_browse = false;

		foreach ( $caps as $cap ) {
			if ( current_user_can( $cap ) ) {
				$can_browse = true;
				break;
			}
		}
	}

	return apply_filters( 'recruiter_can_user_browse_companies', $can_browse );
}

function recruiter_can_user_view_company( $company_id = null ) {

	$can_view = true;
	$company  = get_post( $company_id );

	// Allow previews
	if ( $company->post_status === 'preview' ) {
		return true;
	}

	$caps = array_filter(
		array_map(
			'trim',
			array_map(
				'strtolower',
				explode( ',', get_option( 'recruiter_view_resume_capability' ) )
			)
		)
	);

	if ( $caps ) {

		$can_view = false;

		foreach ( $caps as $cap ) {
			if ( current_user_can( $cap ) ) {
				$can_view = true;
				break;
			}
		}
	}

	if ( $company->post_status === 'expired' ) {
		$can_view = false;
	}

	if ( $company->post_author > 0 && $company->post_author == get_current_user_id() ) {
		$can_view = true;
	}

	if ( ( $key = get_post_meta( $company_id, 'share_link_key', true ) ) && ! empty( $_GET['key'] ) && $key == $_GET['key'] ) {
		$can_view = true;
	}

	return apply_filters( 'recruiter_can_user_view_company', $can_view, $company_id );
}

function recruiter_can_user_create_company() {
	return WP_Recruiter_Companies::instance()->can_user_create_company();
}

function recruiter_can_user_edit_company( $company_id = null ) {
	return WP_Recruiter_Companies::instance()->can_user_edit_company( $company_id );
}

function recruiter_get_user_employees() {
	return WP_Recruiter_Companies::instance()->get_user_employees();
}

function recruiter_count_user_employees( $user_id = 0 ) {
	return count( WP_Recruiter_Companies::instance()->get_user_employees() );
}

function recruiter_can_user_create_employee() {
	return WP_Recruiter_Companies::instance()->can_user_create_employee();
}

function recruiter_create_employee( $args = array() ) {
	return WP_Recruiter_Companies::instance()->create_employee( $args );
}

function recruiter_can_user_edit_employee( $employee_id = null, $company_id = null ) {
	return WP_Recruiter_Companies::instance()->can_user_edit_employee( $employee_id, $company_id );
}

function recruiter_delete_employee( $employee_id = null ) {
	return WP_Recruiter_Companies::instance()->delete_employee( $employee_id );
}

function recruiter_can_user_view_contact_details( $resume_id = null ) {

	$can_view = true;
	$company  = get_post( $company_id );

	$caps = array_filter(
		array_map(
			'trim',
			array_map(
				'strtolower',
				explode( ',', get_option( 'recruiter_contact_company_capability' ) )
			)
		)
	);

	if ( $caps ) {

		$can_view = false;

		foreach ( $caps as $cap ) {
			if ( current_user_can( $cap ) ) {
				$can_view = true;
				break;
			}
		}
	}

	if ( $company->post_author > 0 && $company->post_author == get_current_user_id() ) {
		$can_view = true;
	}

	if ( ( $key = get_post_meta( $company_id, 'share_link_key', true ) ) && ! empty( $_GET['key'] ) && $key == $_GET['key'] ) {
		$can_view = true;
	}

	return apply_filters( 'recruiter_can_user_view_contact_details', $can_view, $company_id );
}

function recruiter_user_requires_account() {

	$required = ( get_option( 'recruiter_user_requires_account' ) == 1 ? true : false );

	return apply_filters( 'recruiter_user_requires_account', $required );
}

function recruiter_enable_registration() {

	$enabled = ( get_option( 'recruiter_enable_registration' ) == 1 ? true : false );

	return apply_filters( 'recruiter_enable_registration', $enabled );
}

function recruiter_get_filtered_links( $args = array() ) {

	$links = apply_filters( 'recruiter_company_filters_showing_companies_links', array(
		'reset' => array(
			'name' => __( 'Reset', 'wp-job-manager-recruiter' ),
			'url'  => '#'
		)
	), $args );

	$return = '';

	foreach ( $links as $key => $link ) {
		$return .= '<a href="' . esc_url( $link['url'] ) . '" class="' . esc_attr( $key ) . '">' . $link['name'] . '</a>';
	}

	return $return;
}

function recruiter_get_job_listings( $company_id = null ) {

	if(!empty($company_id)) {

		$args = array(
			'post_type'              => 'job_listing',
			'meta_key'               => '_company_id',
			'meta_value'             => $company_id,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false
		);

		return new WP_Query($args);

	}

	return false;
}
