<?php wp_enqueue_script( 'wp-recruiter-company-submission' ); ?>

<form action="<?php echo $action; ?>" method="post" id="submit-company-form" class="job-manager-form" enctype="multipart/form-data">

	<?php do_action( 'submit_company_form_start' ); ?>

	<?php if ( recruiter_can_user_create_company() ) : ?>

		<!-- Company Fields -->
		<?php do_action( 'submit_company_form_company_fields_start' ); ?>

		<?php foreach ( $company_fields as $key => $field ) : ?>
			<fieldset class="fieldset-<?php esc_attr_e( $key ); ?>">
				<label for="<?php esc_attr_e( $key ); ?>"><?php echo $field['label'] . apply_filters( 'submit_company_form_required_label', $field['required'] ? '' : ' <small>' . __( '(optional)', 'wp-job-manager-recruiter' ) . '</small>', $field ); ?></label>
				<div class="field">
					<?php $class->get_field_template( $key, $field ); ?>
				</div>
			</fieldset>
		<?php endforeach; ?>

		<?php do_action( 'submit_company_form_company_fields_end' ); ?>

		<p>
			<?php wp_nonce_field( 'submit_form_posted' ); ?>
			<input type="hidden" name="recruiter_form" value="<?php echo $form; ?>" />
			<input type="hidden" name="company_id" value="<?php echo esc_attr( $company_id ); ?>" />
			<input type="hidden" name="step" value="<?php echo esc_attr( $step ); ?>" />
			<input type="submit" name="submit_company" class="button" value="<?php esc_attr_e( $submit_button_text ); ?>" />
		</p>

	<?php else : ?>

		<?php do_action( 'submit_company_form_disabled' ); ?>

	<?php endif; ?>

	<?php do_action( 'submit_company_form_end' ); ?>
</form>
