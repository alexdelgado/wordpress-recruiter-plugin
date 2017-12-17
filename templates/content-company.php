<?php $category = get_the_company_category(); ?>
<li <?php company_class(); ?>>
	<a href="<?php the_company_permalink(); ?>">
		<div class="company-column">
			<h3><?php the_title(); ?></h3>
		</div>
		<div class="company-location-column">
			<?php the_company_location( false ); ?>
		</div>
		<div class="company-posted-column <?php if ( $category ) : ?>company-meta<?php endif; ?>">
			<date><?php printf( __( '%s ago', 'wp-job-manager-recruiter' ), human_time_diff( get_post_time( 'U' ), current_time( 'timestamp' ) ) ); ?></date>

			<?php if ( $category ) : ?>
				<div class="company-category">
					<?php echo $category ?>
				</div>
			<?php endif; ?>
		</div>
	</a>
</li>
