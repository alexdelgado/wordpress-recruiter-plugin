<?php $submit_employee_form_page_id = get_option( 'recruiter_submit_employee_form_page_id' ); ?>
<div id="recruiter-employee-dashboard">
	<p><?php _e( 'Your employees can be viewed, edited or removed below.', 'wp-job-manager-recruiter' ); ?></p>
	<table class="recruiter-employees">
		<thead>
			<tr>
				<?php foreach ( $employee_dashboard_columns as $key => $column ) : ?>
					<th class="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $column ); ?></th>
				<?php endforeach; ?>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! $employees ) : ?>
				<tr>
					<td colspan="<?php echo sizeof( $employee_dashboard_columns ); ?>"><?php _e( 'You do not have any registered employees.', 'wp-job-manager-recruiter' ); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ( $employees as $employee ) : ?>
					<tr>
						<?php foreach ( $employee_dashboard_columns as $key => $column ) : ?>
							<td class="<?php echo esc_attr( $key ); ?>">
								<?php if ( 'employee-name' === $key ) : ?>
									<?php echo $employee->display_name; ?>
									<ul class="employee-dashboard-actions">
										<?php
											$actions = array();

											$actions['edit'] = array( 'label' => __( 'Edit', 'wp-job-manager-recruiter' ), 'nonce' => false );
											$actions['delete'] = array( 'label' => __( 'Delete', 'wp-job-manager-recruiter' ), 'nonce' => true );

											$actions = apply_filters( 'recruiter_my_employee_actions', $actions, $employee );

											foreach ( $actions as $action => $value ) {

												$action_url = add_query_arg( array( 'action' => $action, 'employee_id' => $employee->ID ) );

												if ( $value['nonce'] ) {
													$action_url = wp_nonce_url( $action_url, 'recruiter_my_employee_actions' );
												}

												echo '<li><a href="' . $action_url . '" class="employee-dashboard-action-' . $action . '">' . $value['label'] . '</a></li>';
											}
										?>
									</ul>
								<?php elseif ( 'employee-email' === $key ) : ?>
									<?php echo $employee->user_email ?>
								<?php elseif ( 'employee-role' === $key ) : ?>
									<?php echo ucwords( implode( ', ', $employee->roles ) ); ?>
								<?php else : ?>
									<?php do_action( 'recruiter_employee_dashboard_column_' . $key, $employee ); ?>
								<?php endif; ?>
							</td>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
		<?php if ( $submit_employee_form_page_id ) : ?>
			<tfoot>
				<tr>
					<td colspan="<?php echo sizeof( $employee_dashboard_columns ); ?>">
						<a href="<?php echo esc_url( get_permalink( $submit_employee_form_page_id ) ); ?>"><?php _e( 'Add Employee', 'wp-job-manager-recruiter' ); ?></a>
					</td>
				</tr>
			</tfoot>
		<?php endif; ?>
	</table>
	<?php get_job_manager_template( 'pagination.php', array( 'max_num_pages' => $max_num_pages ) ); ?>
</div>
