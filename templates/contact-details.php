<?php
global $company_preview;

if ( $company_preview ) {
	return;
}

if ( recruiter_can_user_view_contact_details( $post->ID ) ) :
	wp_enqueue_script( 'wp-recruiter-company-contact-details' );
?>

	<div class="company_contact">
		<input class="company_contact_button" type="button" value="<?php _e( 'Contact', 'wp-job-manager-recruiter' ); ?>" />

		<div class="company_contact_details">
			<?php do_action( 'recruiter_contact_details' ); ?>
		</div>
	</div>

<?php else : ?>

	<?php get_job_manager_template_part( 'access-denied', 'contact-details', 'wp-job-manager-recruiter', RESUME_MANAGER_PLUGIN_DIR . '/templates/' ); ?>

<?php endif; ?>
