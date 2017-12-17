<?php

 // Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function get_companies( $args = array() ) {
	global $wpdb, $recruiter_keyword;

	$args = wp_parse_args(
		$args,
		array(
			'search_location'   => '',
			'search_keywords'   => '',
			'search_categories' => array(),
			'offset'            => '',
			'posts_per_page'    => '-1',
			'orderby'           => 'date',
			'order'             => 'DESC',
			'featured'          => null,
			'fields'            => 'all',
			'meta_query'        => array()
		)
	);

	$query_args = array(
		'post_type'              => 'company',
		'post_status'            => 'publish',
		'ignore_sticky_posts'    => 1,
		'offset'                 => absint( $args['offset'] ),
		'posts_per_page'         => intval( $args['posts_per_page'] ),
		'orderby'                => $args['orderby'],
		'order'                  => $args['order'],
		'tax_query'              => array(),
		'meta_query'             => $args['meta_query'],
		'update_post_term_cache' => false,
		'update_post_meta_cache' => false,
		'cache_results'          => false,
		'fields'                 => $args['fields']
	);

	if ( $args['posts_per_page'] < 0 ) {
		$query_args['no_found_rows'] = true;
	}

	if ( ! empty( $args['search_location'] ) ) {

		$location_meta_keys = array( 'geolocation_formatted_address', '_company_location', 'geolocation_state_long' );
		$location_search    = array( 'relation' => 'OR' );

		foreach ( $location_meta_keys as $meta_key ) {

			$location_search[] = array(
				'key'     => $meta_key,
				'value'   => $args['search_location'],
				'compare' => 'like'
			);
		}

		$query_args['meta_query'][] = $location_search;
	}

	if ( ! is_null( $args['featured'] ) ) {

		$query_args['meta_query'][] = array(
			'key'     => '_featured',
			'value'   => '1',
			'compare' => $args['featured'] ? '=' : '!='
		);
	}

	if ( ! empty( $args['search_categories'] ) ) {

		$field    = ( is_numeric( $args['search_categories'][0] ) ? 'term_id' : 'slug' );
		$operator = ( 'all' === get_option( 'recruiter_category_filter_type', 'all' ) && sizeof( $args['search_categories'] ) > 1 ? 'AND' : 'IN' );

		$query_args['tax_query'][] = array(
			'taxonomy'         => 'company_category',
			'field'            => $field,
			'terms'            => array_values( $args['search_categories'] ),
			'include_children' => $operator !== 'AND' ,
			'operator'         => $operator
		);
	}

	if ( 'featured' === $args['orderby'] ) {

		$query_args['orderby'] = array(
			'menu_order' => 'ASC',
			'title'      => 'DESC'
		);
	}

	if ( $recruiter_keyword = sanitize_text_field( $args['search_keywords'] ) ) {

		// Does nothing but needed for unique hash
		$query_args['_keyword'] = $recruiter_keyword;

		add_filter( 'posts_clauses', 'get_companies_keyword_search' );
	}

	$query_args = apply_filters( 'recruiter_get_companies', $query_args, $args );

	if ( empty( $query_args['meta_query'] ) ) {
		unset( $query_args['meta_query'] );
	}

	if ( empty( $query_args['tax_query'] ) ) {
		unset( $query_args['tax_query'] );
	}

	// Filter args
	$query_args = apply_filters( 'get_companies_query_args', $query_args, $args );

	// Generate hash
	$to_hash         = ( defined( 'ICL_LANGUAGE_CODE' ) ? json_encode( $query_args ) . ICL_LANGUAGE_CODE : json_encode( $query_args ) );
	$query_args_hash = 'jmr_' . md5( $to_hash ) . WP_Job_Manager_Cache_Helper::get_transient_version( 'get_company_listings' );

	do_action( 'before_get_companies', $query_args, $args );

	if ( false === ( $result = get_transient( $query_args_hash ) ) ) {
		$result = new WP_Query( $query_args );
		set_transient( $query_args_hash, $result, DAY_IN_SECONDS * 30 );
	}

	do_action( 'after_get_companies', $query_args, $args );

	remove_filter( 'posts_clauses', 'get_companies_keyword_search' );

	return $result;
}

function get_companies_keyword_search( $args ) {
	global $wpdb, $recruiter_keyword;

	// Meta searching - Query matching ids to avoid more joins
	$post_ids = $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_value LIKE '%" . esc_sql( $recruiter_keyword ) . "%'" );

	// Term searching
	$post_ids = array_merge( $post_ids, $wpdb->get_col( "SELECT object_id FROM {$wpdb->term_relationships} AS tr LEFT JOIN {$wpdb->terms} AS t ON tr.term_taxonomy_id = t.term_id WHERE t.name LIKE '" . esc_sql( $recruiter_keyword ) . "%'" ) );

	// Title and content searching
	$conditions = array();
	$conditions[] = "{$wpdb->posts}.post_title LIKE '%" . esc_sql( $recruiter_keyword ) . "%'";
	$conditions[] = "{$wpdb->posts}.post_content RLIKE '[[:<:]]" . esc_sql( $recruiter_keyword ) . "[[:>:]]'";

	if ( $post_ids ) {
		$conditions[] = "{$wpdb->posts}.ID IN (" . esc_sql( implode( ',', array_unique( $post_ids ) ) ) . ")";
	}

	$args['where'] .= " AND ( " . implode( ' OR ', $conditions ) . " ) ";

	return $args;
}

function the_company_title( $before = '', $after = '', $echo = true, $post = null ) {

	$title = get_the_company_title( $post );

	if ( strlen( $title ) == 0 ) {
		return;
	}

	$title = esc_attr( strip_tags( $title ) );
	$title = $before . $title . $after;

	if ( $echo ) {
		echo $title;
	} else {
		return $title;
	}
}

function get_the_company_title( $post = null ) {

	$post = get_post( $post );

	if ( $post->post_type !== 'company' ) {
		return '';
	}

	return apply_filters( 'the_company_title', $post->_company_title, $post );
}

function the_company_location( $map_link = true, $post = null ) {

	$location = get_the_company_location( $post );

	if ( $location ) {

		if ( $map_link ) {
			echo apply_filters( 'the_company_location_map_link', '<a class="google_map_link company-location" href="http://maps.google.com/maps?q=' . urlencode( $location ) . '&zoom=14&size=512x512&maptype=roadmap&sensor=false">' . $location . '</a>', $location, $post );
		} else {
			echo '<span class="company-location">' . $location . '</span>';
		}

	}
}

function get_the_company_location( $post = null ) {

	$post = get_post( $post );

	if ( $post->post_type !== 'company' ) {
		return;
	}

	return apply_filters( 'the_company_location', $post->_company_location, $post );
}

function the_company_status( $post = null ) {
	echo get_the_company_status( $post );
}

function get_the_company_status( $post = null ) {

	$post = get_post( $post );

	$status = $post->post_status;

	if ( $status == 'publish' ) {
		$status = __( 'Published', 'wp-job-manager-recruiter' );
	} elseif ( $status == 'expired' ) {
		$status = __( 'Expired', 'wp-job-manager-recruiter' );
	} elseif ( $status == 'pending' ) {
		$status = __( 'Pending Review', 'wp-job-manager-recruiter' );
	} elseif ( $status == 'hidden' ) {
		$status = __( 'Hidden', 'wp-job-manager-recruiter' );
	} else {
		$status = __( 'Inactive', 'wp-job-manager-recruiter' );
	}

	return apply_filters( 'the_company_status', $status, $post );
}

function is_company_featured( $post = null ) {

	$post = get_post( $post );

	return $post->_featured ? true : false;
}

function the_company_category( $post = null ) {
	echo get_the_company_category( $post );
}

function get_the_company_category( $post = null ) {

	$post = get_post( $post );

	if ( $post->post_type !== 'company' ) {
		return '';
	}

	if ( ! get_option( 'recruiter_enable_company_categories' ) ) {
		return '';
	}

	$categories = wp_get_object_terms( $post->ID, 'company_category', array( 'fields' => 'names' ) );

	if ( is_wp_error( $categories ) ) {
		return '';
	}

	return implode( ', ', $categories );
}

function company_class( $class = '', $post_id = null ) {
	echo 'class="' . join( ' ', get_company_class( $class, $post_id ) ) . '"';
}

function get_company_class( $class = '', $post_id = null ) {

	$post = get_post( $post_id );

	if ( $post->post_type !== 'company' ) {
		return array();
	}

	$classes = explode( ' ', $class );

	if ( empty( $post ) ) {
		return $classes;
	}

	$classes[] = 'company';

	if ( is_company_featured( $post ) ) {
		$classes[] = 'company_featured';
	}

	return get_post_class( $classes, $post->ID );
}

function the_company_permalink( $post = null ) {

	$post = get_post( $post );

	echo get_the_company_permalink( $post );
}

function get_the_company_permalink( $post = null ) {

	$post = get_post( $post );
	$link = get_permalink( $post );

	return apply_filters( 'the_company_permalink', $link, $post );
}

function the_company_video_thumbnail( $post = null ) {

	$thumbnail = get_the_company_video_thumbnail( $post );

	if ( empty( $thumbnail ) ) {
		$thumbnail = get_the_company_logo( $post );
	}

	echo '<img class="company__thumbnail" src="' . $thumbnail . '" alt="" />';
}

function get_the_company_video_thumbnail( $post = null ) {

	$post = get_post( $post );

	if ( $post->post_type !== 'company' )
		return;

	return apply_filters( 'the_company_video_thumbnail', $post->_company_video_thumbnail, $post );
}

function the_company_links( $post = null ) {

	$post = get_post( $post );

	get_job_manager_template(
		'company-links.php',
		array(
			'post' => $post
		),
		'wp-job-manager-recruiter',
		RECRUITER_PLUGIN_DIR . '/templates/'
	);
}

function get_company_links( $post = null ) {

	$post = get_post( $post );

	return array_filter( (array) get_post_meta( $post->ID, '_links', true ) );
}

function company_has_links( $post = null ) {
	return sizeof( get_resume_links( $post ) ) ? true : false;
}
