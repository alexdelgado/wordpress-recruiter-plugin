<?php if ( company_has_links() ) : ?>
	<ul class="company-links">
		<?php foreach( get_company_links() as $link ) : ?>
			<?php get_job_manager_template( 'content-company-link.php', array( 'post' => $post, 'link' => $link ), 'wp-job-manager-recruiter', RESUME_MANAGER_PLUGIN_DIR . '/templates/' ); ?>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
