<?php wp_enqueue_script( 'wp-recruiter-ajax-filters' ); ?>

<?php do_action( 'recruiter_company_filters_before', $atts ); ?>

<form class="company_filters">

	<div class="search_companies">

		<?php do_action( 'recruiter_company_filters_search_companies_start', $atts ); ?>

		<div class="search_keywords company-filter">
			<label for="search_keywords"><?php _e( 'Keywords', 'wp-job-manager-recruiter' ); ?></label>
			<input type="text" name="search_keywords" id="search_keywords" placeholder="<?php _e( 'All Companies', 'wp-job-manager-recruiter' ); ?>" value="<?php echo esc_attr( $keywords ); ?>" />
		</div>

		<div class="search_location company-filter">
			<label for="search_location"><?php _e( 'Location', 'wp-job-manager-recruiter' ); ?></label>
			<input type="text" name="search_location" id="search_location" placeholder="<?php _e( 'Any Location', 'wp-job-manager-recruiter' ); ?>" value="<?php echo esc_attr( $location ); ?>" />
		</div>

		<?php if ( $categories ) : ?>

			<?php foreach ( $categories as $category ) : ?>
				<input type="hidden" name="search_categories[]" value="<?php echo sanitize_title( $category ); ?>" />
			<?php endforeach; ?>

		<?php elseif ( $show_categories && get_option( 'recruiter_enable_company_categories' ) && ! is_tax( 'company_category' ) && get_terms( 'company_category' ) ) : ?>

			<div class="search_categories company-filter">
				<label for="search_categories"><?php _e( 'Category', 'wp-job-manager-recruiter' ); ?></label>
				<?php if ( $show_category_multiselect ) : ?>
					<?php job_manager_dropdown_categories( array( 'taxonomy' => 'company_category', 'hierarchical' => 1, 'name' => 'search_categories', 'orderby' => 'name', 'selected' => $selected_category, 'hide_empty' => false ) ); ?>
				<?php else : ?>
					<?php wp_dropdown_categories( array( 'taxonomy' => 'company_category', 'hierarchical' => 1, 'show_option_all' => __( 'Any category', 'wp-job-manager-recruiter' ), 'name' => 'search_categories', 'orderby' => 'name', 'selected' => $selected_category ) ); ?>
				<?php endif; ?>
			</div>

		<?php endif; ?>

		<?php do_action( 'recruiter_company_filters_search_companies_end', $atts ); ?>

	</div>

	<div class="showing_companies"></div>

</form>

<?php do_action( 'recruiter_company_filters_after', $atts ); ?>
