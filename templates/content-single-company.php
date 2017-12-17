<?php if ( recruiter_can_user_view_company( $post->ID ) ) : ?>

	<div class="single-company-content">

		<?php do_action( 'single_company_start' ); ?>

		<div class="company-aside">
			<?php the_company_links(); ?>
			<p class="job-title"><?php the_company_title(); ?></p>
			<p class="location"><?php the_company_location(); ?></p>

			<?php the_company_video(); ?>
		</div>

		<div class="company_description">
			<?php echo apply_filters( 'the_company_description', get_the_content() ); ?>
		</div>

		<?php if ( ( $skills = wp_get_object_terms( $post->ID, 'company_skill', array( 'fields' => 'names' ) ) ) && is_array( $skills ) ) : ?>
			<h2><?php _e( 'Skills', 'wp-job-manager-recruiter' ); ?></h2>
			<ul class="recruiter-skills">
				<?php echo '<li>' . implode( '</li><li>', $skills ) . '</li>'; ?>
			</ul>
		<?php endif; ?>

		<?php if ( $items = get_post_meta( $post->ID, '_company_education', true ) ) : ?>
			<h2><?php _e( 'Education', 'wp-job-manager-recruiter' ); ?></h2>
			<dl class="recruiter-education">
			<?php
				foreach( $items as $item ) : ?>

					<dt>
						<small class="date"><?php echo esc_html( $item['date'] ); ?></small>
						<h3><?php printf( __( '%s at %s', 'wp-job-manager-recruiter' ), '<strong class="qualification">' . esc_html( $item['qualification'] ) . '</strong>', '<strong class="location">' . esc_html( $item['location'] ) . '</strong>' ); ?></h3>
					</dt>
					<dd>
						<?php echo wpautop( wptexturize( $item['notes'] ) ); ?>
					</dd>

				<?php endforeach;
			?>
			</dl>
		<?php endif; ?>

		<?php if ( $items = get_post_meta( $post->ID, '_company_experience', true ) ) : ?>
			<h2><?php _e( 'Experience', 'wp-job-manager-recruiter' ); ?></h2>
			<dl class="recruiter-experience">
			<?php
				foreach( $items as $item ) : ?>

					<dt>
						<small class="date"><?php echo esc_html( $item['date'] ); ?></small>
						<h3><?php printf( __( '%s at %s', 'wp-job-manager-recruiter' ), '<strong class="job_title">' . esc_html( $item['job_title'] ) . '</strong>', '<strong class="employer">' . esc_html( $item['employer'] ) . '</strong>' ); ?></h3>
					</dt>
					<dd>
						<?php echo wpautop( wptexturize( $item['notes'] ) ); ?>
					</dd>

				<?php endforeach;
			?>
			</dl>
		<?php endif; ?>

		<ul class="meta">
			<?php do_action( 'single_company_meta_start' ); ?>

			<?php if ( get_the_company_category() ) : ?>
				<li class="company-category"><?php the_company_category(); ?></li>
			<?php endif; ?>

			<li class="date-posted" itemprop="datePosted"><date><?php printf( __( 'Updated %s ago', 'wp-job-manager-recruiter' ), human_time_diff( get_the_modified_time( 'U' ), current_time( 'timestamp' ) ) ); ?></date></li>

			<?php do_action( 'single_company_meta_end' ); ?>
		</ul>

		<?php get_job_manager_template( 'contact-details.php', array( 'post' => $post ), 'wp-job-manager-recruiter', RESUME_MANAGER_PLUGIN_DIR . '/templates/' ); ?>

		<?php do_action( 'single_company_end' ); ?>
	</div>
<?php else : ?>

	<?php get_job_manager_template_part( 'access-denied', 'single-company', 'wp-job-manager-recruiter', RESUME_MANAGER_PLUGIN_DIR . '/templates/' ); ?>

<?php endif; ?>
