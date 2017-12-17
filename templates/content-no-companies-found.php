<?php if ( defined( 'DOING_AJAX' ) ) : ?>
	<li class="no_companies_found"><?php _e( 'There are no companies matching your search.', 'wp-job-manager-recruiter' ); ?></li>
<?php else : ?>
	<p class="no_companies_found"><?php _e( 'There are currently no companies.', 'wp-job-manager-recruiter' ); ?></p>
<?php endif; ?>
