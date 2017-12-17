<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WP_Recruiter_Ajax class.
 */
class WP_Recruiter_Ajax {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_nopriv_recruiter_get_companies', array( $this, 'get_companies' ) );
		add_action( 'wp_ajax_recruiter_get_companies', array( $this, 'get_companies' ) );
	}

	/**
	 * Get companies via ajax
	 */
	public function get_companies() {
		global $wpdb;

		ob_start();

		$search_location   = sanitize_text_field( stripslashes( $_POST['search_location'] ) );
		$search_keywords   = sanitize_text_field( stripslashes( $_POST['search_keywords'] ) );
		$search_categories = isset( $_POST['search_categories'] ) ? $_POST['search_categories'] : '';

		if ( is_array( $search_categories ) ) {
			$search_categories = array_map( 'sanitize_text_field', array_map( 'stripslashes', $search_categories ) );
		} else {
			$search_categories = array( sanitize_text_field( stripslashes( $search_categories ) ), 0 );
		}

		$search_categories = array_filter( $search_categories );

		$visibility = array('public');

		if(is_user_logged_in()) {
			array_push($visibility, 'protected');
		}

		$args = array(
			'search_location'   => $search_location,
			'search_keywords'   => $search_keywords,
			'search_categories' => $search_categories,
			'orderby'           => sanitize_text_field( $_POST['orderby'] ),
			'order'             => sanitize_text_field( $_POST['order'] ),
			'offset'            => ( absint( $_POST['page'] ) - 1 ) * absint( $_POST['per_page'] ),
			'posts_per_page'    => absint( $_POST['per_page'] ),
			'meta_query'        => array(
				array(
					'key' => '_visibility',
					'value' => $visibility,
					'compare' => 'IN'
				)
			)
		);

		if ( isset( $_POST['featured'] ) && ( $_POST['featured'] === 'true' || $_POST['featured'] === 'false' ) ) {
			$args['featured'] = $_POST['featured'] === 'true' ? true : false;
		}

		$companies = get_companies( apply_filters( 'recruiter_get_companies_args', $args ) );

		$result = array();
		$result['found_companies'] = false;

		if ( $companies->have_posts() ) : $result['found_companies'] = true; ?>

			<?php while ( $companies->have_posts() ) : $companies->the_post(); ?>

				<?php get_job_manager_template_part( 'content', 'company', 'wp-job-manager-recruiter', RECRUITER_PLUGIN_DIR . '/templates/' ); ?>

			<?php endwhile; ?>

		<?php else : ?>

			<li class="no_companies_found"><?php _e( 'No companies found matching your selection.', 'wp-job-manager-recruiter' ); ?></li>

		<?php endif;

		$result['html']    = ob_get_clean();

		// Generate 'showing' text
		if ( $search_keywords || $search_location || $search_categories || apply_filters( 'recruiter_get_companies_custom_filter', false ) ) {

			$showing_categories = array();

			if ( $search_categories ) {
				foreach ( $search_categories as $category ) {
					if ( ! is_numeric( $category ) ) {
						$category_object = get_term_by( 'slug', $category, 'company_category' );
					}
					if ( is_numeric( $category ) || is_wp_error( $category_object ) || ! $category_object ) {
						$category_object = get_term_by( 'id', $category, 'company_category' );
					}
					if ( ! is_wp_error( $category_object ) ) {
						$showing_categories[] = $category_object->name;
					}
				}
			}

			if ( $search_keywords ) {
				$showing_companies  = sprintf( __( 'Showing &ldquo;%s&rdquo; %scompanies', 'wp-job-manager-recruiter' ), $search_keywords, implode( ', ', $showing_categories ) );
			} else {
				$showing_companies  = sprintf( __( 'Showing all %scompanies', 'wp-job-manager-recruiter' ), implode( ', ', $showing_categories ) . ' ' );
			}

			$showing_location  = $search_location ? sprintf( ' ' . __( 'located in &ldquo;%s&rdquo;', 'wp-job-manager-recruiter' ), $search_location ) : '';

			$result['showing'] = apply_filters( 'recruiter_get_companies_custom_filter_text', $showing_companies . $showing_location );

		} else {
			$result['showing'] = '';
		}

		// Generate RSS link
		$result['showing_links'] = recruiter_get_filtered_links( array(
			'search_location'   => $search_location,
			'search_categories' => $search_categories,
			'search_keywords'   => $search_keywords
		) );

		// Generate pagination
		if ( isset( $_POST['show_pagination'] ) && $_POST['show_pagination'] === 'true' ) {
			$result['pagination'] = get_job_listing_pagination( $companies->max_num_pages, absint( $_POST['page'] ) );
		}

		$result['max_num_pages'] = $companies->max_num_pages;
		$result['query'] = $companies->request;

		echo '<!--WPJM-->';
		echo json_encode( $result );
		echo '<!--WPJM_END-->';

		die();
	}
}

new WP_Recruiter_Ajax();
