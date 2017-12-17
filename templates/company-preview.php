<form method="post" id="company_preview">
    <div class="company_preview_title">
        <input type="submit" name="continue" id="company_preview_submit_button" class="button" value="<?php echo apply_filters( 'submit_company_step_preview_submit_text', __( 'Submit Company &rarr;', 'wp-job-manager-recruiter' ) ); ?>" />
        <input type="submit" name="edit_company" class="button" value="<?php _e( '&larr; Edit company', 'wp-job-manager-recruiter' ); ?>" />
        <h2><?php _e( 'Preview', 'wp-job-manager-recruiter' ); ?></h2>
    </div>
    <div class="company_preview single-company">
        <?php get_job_manager_template_part( 'content-single', 'company', 'wp-job-manager-recruiter', RECRUITER_PLUGIN_DIR . '/templates/' ); ?>

        <input type="hidden" name="company_id" value="<?php echo esc_attr( $form->get_company_id() ); ?>" />
		<input type="hidden" name="step" value="<?php echo esc_attr( $form->get_step() ); ?>" />
		<input type="hidden" name="recruiter_form" value="<?php echo $form->form_name; ?>" />
    </div>
</form>
