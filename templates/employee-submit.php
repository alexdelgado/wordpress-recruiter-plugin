<?php wp_enqueue_script( 'wp-recruiter-employee-submission' ); ?>

<form action="<?php echo $action; ?>" method="post" id="submit-employee-form" class="job-manager-form" enctype="multipart/form-data">

	<?php do_action( 'submit_employee_form_start' ); ?>

	<?php if ( apply_filters( 'submit_employee_form_show_signin', true ) ) : ?>

		<?php get_job_manager_template( 'account-signin.php', array( 'class' => $class ), 'wp-job-manager-recruiter', RECRUITER_PLUGIN_DIR . '/templates/' ); ?>

	<?php endif; ?>

	<?php if ( recruiter_can_user_create_employee() ) : ?>

		<!-- Company Fields -->
		<?php do_action( 'submit_employee_form_employee_fields_start' ); ?>

		<?php foreach ( $employee_fields as $key => $field ) : ?>
			<fieldset class="fieldset-<?php esc_attr_e( $key ); ?>">
				<label for="<?php esc_attr_e( $key ); ?>"><?php echo $field['label'] . apply_filters( 'submit_employee_form_required_label', $field['required'] ? '' : ' <small>' . __( '(optional)', 'wp-job-manager-recruiter' ) . '</small>', $field ); ?></label>
				<div class="field">
					<?php $class->get_field_template( $key, $field ); ?>
				</div>
			</fieldset>
		<?php endforeach; ?>

		<?php do_action( 'submit_employee_form_employee_fields_end' ); ?>

		<p>
			<?php wp_nonce_field( 'submit_form_posted' ); ?>
			<input type="hidden" name="recruiter_form" value="<?php echo $form; ?>" />
			<input type="hidden" name="employee_id" value="<?php echo esc_attr( $employee_id ); ?>" />
			<input type="hidden" name="step" value="<?php echo esc_attr( $step ); ?>" />
			<input type="submit" name="submit_employee" class="button" value="<?php esc_attr_e( $submit_button_text ); ?>" />
		</p>

	<?php else : ?>

		<?php do_action( 'submit_employee_form_disabled' ); ?>

	<?php endif; ?>

	<?php do_action( 'submit_employee_form_end' ); ?>
</form>
