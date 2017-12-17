<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_Recruiter_Setup {

	public function __construct() {

		add_action( 'admin_menu', array( $this, 'admin_menu' ), 12 );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'admin_init', array( $this, 'redirect' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 12 );
	}

	public function admin_menu() {
		add_dashboard_page( __( 'Setup', 'wp-job-manager-recruiter' ), __( 'Setup', 'wp-job-manager-recruiter' ), 'manage_options', 'recruiter-setup', array( $this, 'output' ) );
	}

	public function admin_head() {
		remove_submenu_page( 'index.php', 'recruiter-setup' );
	}

	public function redirect() {

		// Bail if no activation redirect transient is set
	    if ( ! get_transient( '_recruiter_activation_redirect' ) ) {
			return;
	    }

	    if ( ! current_user_can( 'manage_options' ) ) {
	    	return;
	    }

		// Delete the redirect transient
		delete_transient( '_recruiter_activation_redirect' );

		// Bail if activating from network, or bulk, or within an iFrame
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) || defined( 'IFRAME_REQUEST' ) ) {
			return;
		}

		if ( ( isset( $_GET['action'] ) && 'upgrade-plugin' == $_GET['action'] ) && ( isset( $_GET['plugin'] ) && strstr( $_GET['plugin'], 'wp-job-manager-recruiter.php' ) ) ) {
			return;
		}

		wp_redirect( admin_url( 'index.php?page=recruiter-setup' ) );
		exit;
	}

	public function admin_enqueue_scripts() {
		wp_enqueue_style( 'recruiter_setup_css', RECRUITER_PLUGIN_URL . '/assets/css/setup.css', array( 'dashicons' ) );
	}

	public function create_page( $title = null, $content = null, $option = null ) {

		$page_data = array(
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'post_author'    => 1,
			'post_name'      => sanitize_title( $title ),
			'post_title'     => $title,
			'post_content'   => $content,
			'post_parent'    => 0,
			'comment_status' => 'closed'
		);

		$page_id = wp_insert_post( $page_data );

		if ( $option ) {
			update_option( $option, $page_id );
		}
	}

	public function output() {

		$step = ( ! empty( $_GET['step'] ) ? absint( $_GET['step'] ) : 1 );

		if ( 3 === $step && ! empty( $_POST ) ) {

			$create_pages    = ( isset( $_POST['wp-recruiter-create-page'] ) ? $_POST['wp-recruiter-create-page'] : array() );
			$page_titles     = $_POST['wp-recruiter-page-title'];

			$pages_to_create = array(
				'submit_company_form'  => '[submit_company_form]',
				'company_dashboard' => '[company_dashboard]',
				'companies'             => '[companies]'
			);

			foreach ( $pages_to_create as $page => $content ) {

				if ( ! isset( $create_pages[ $page ] ) || empty( $page_titles[ $page ] ) ) {
					continue;
				}

				$this->create_page( sanitize_text_field( $page_titles[ $page ] ), $content, 'recruiter_' . $page . '_page_id' );
			}
		}
		?>
		<div class="wrap wp_job_manager wp_job_manager_addons_wrap">
			<h2><?php _e( 'Recruiter Setup', 'wp-job-manager-recruiter' ); ?></h2>

			<ul class="wp-recruiter-setup-steps">
				<li class="<?php if ( $step === 1 ) echo 'wp-recruiter-setup-active-step'; ?>"><?php _e( '1. Introduction', 'wp-job-manager-recruiter' ); ?></li>
				<li class="<?php if ( $step === 2 ) echo 'wp-recruiter-setup-active-step'; ?>"><?php _e( '2. Page Setup', 'wp-job-manager-recruiter' ); ?></li>
				<li class="<?php if ( $step === 3 ) echo 'wp-recruiter-setup-active-step'; ?>"><?php _e( '3. Done', 'wp-job-manager-recruiter' ); ?></li>
			</ul>

			<?php if ( 1 === $step ) : ?>

				<h3><?php _e( 'Setup Wizard Introduction', 'wp-job-manager-recruiter' ); ?></h3>

				<p><?php _e( 'Thanks for installing <em>Recruiter</em>!', 'wp-job-manager-recruiter' ); ?></p>
				<p><?php _e( 'This setup wizard will help you get started by creating the pages for company management, and company listing.', 'wp-job-manager-recruiter' ); ?></p>
				<!-- <p><?php printf( __( 'If you want to skip the wizard and setup the pages and shortcodes yourself manually, the process is still reletively simple. Refer to the %sdocumentation%s for help.', 'wp-job-manager-recruiter' ), '<a href=https://wpjobmanager.com/documentation/add-ons/recruiter/">', '</a>' ); ?></p> -->

				<p class="submit">
					<a href="<?php echo esc_url( add_query_arg( 'step', 2 ) ); ?>" class="button button-primary"><?php _e( 'Continue to page setup', 'wp-job-manager-recruiter' ); ?></a>
					<a href="<?php echo esc_url( add_query_arg( 'skip-recruiter-setup', 1, admin_url( 'index.php?page=recruiter-setup&step=3' ) ) ); ?>" class="button"><?php _e( 'Skip setup. I will setup the plugin manually', 'wp-job-manager-recruiter' ); ?></a>
				</p>

			<?php endif; ?>

			<?php if ( 2 === $step ) : ?>

				<h3><?php _e( 'Page Setup', 'wp-job-manager-recruiter' ); ?></h3>

				<p><?php printf( __( '<em>Recruiter</em> includes %1$sshortcodes%2$s which can be used within your %3$spages%2$s to output content. These can be created for you below.', 'wp-job-manager-recruiter' ), '<a href="http://codex.wordpress.org/Shortcode" title="What is a shortcode?" target="_blank" class="help-page-link">', '</a>', '<a href="http://codex.wordpress.org/Pages" target="_blank" class="help-page-link">' ); ?></p>

				<form action="<?php echo esc_url( add_query_arg( 'step', 3 ) ); ?>" method="post">
					<table class="wp-recruiter-shortcodes widefat">
						<thead>
							<tr>
								<th>&nbsp;</th>
								<th><?php _e( 'Page Title', 'wp-job-manager-recruiter' ); ?></th>
								<th><?php _e( 'Page Description', 'wp-job-manager-recruiter' ); ?></th>
								<th><?php _e( 'Content Shortcode', 'wp-job-manager-recruiter' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><input type="checkbox" checked="checked" name="wp-recruiter-create-page[company_dashboard]" /></td>
								<td><input type="text" value="<?php echo esc_attr( _x( 'Company Dashboard', 'Default page title (wizard)', 'wp-job-manager-recruiter' ) ); ?>" name="wp-recruiter-page-title[company_dashboard]" /></td>
								<td>
									<p><?php _e( 'This page allows employers to manage and edit their own company from the front-end.', 'wp-job-manager-recruiter' ); ?></p>
									<p><?php _e( 'If you plan on managing all listings from the admin dashboard you can skip creating this page.', 'wp-job-manager-recruiter' ); ?></p>
								</td>
								<td><code>[company_dashboard]</code></td>
							</tr>
							<tr>
								<td><input type="checkbox" checked="checked" name="wp-recruiter-create-page[companies]" /></td>
								<td><input type="text" value="<?php echo esc_attr( _x( 'Companies', 'Default page title (wizard)', 'wp-job-manager-recruiter' ) ); ?>" name="wp-recruiter-page-title[companies]" /></td>
								<td><?php _e( 'This page allows users to browse, search, and filter company listings on the front-end of your site.', 'wp-job-manager-recruiter' ); ?></td>
								<td><code>[companies]</code></td>
							</tr>
						</tbody>
						<tfoot>
							<tr>
								<th colspan="4">
									<input type="submit" class="button button-primary" value="Create selected pages" />
									<a href="<?php echo esc_url( add_query_arg( 'step', 3 ) ); ?>" class="button"><?php _e( 'Skip this step', 'wp-job-manager-recruiter' ); ?></a>
								</th>
							</tr>
						</tfoot>
					</table>
				</form>

			<?php endif; ?>

			<?php if ( 3 === $step ) : ?>

				<h3><?php _e( 'All Done!', 'wp-job-manager-recruiter' ); ?></h3>

				<p><?php _e( 'Looks like you\'re all set to start using the plugin. In case you\'re wondering where to go next:', 'wp-job-manager-recruiter' ); ?></p>

				<ul class="wp-recruiter-next-steps">
					<li><a href="<?php echo admin_url( 'edit.php?post_type=company&page=recruiter-settings' ); ?>"><?php _e( 'Tweak the plugin settings', 'wp-job-manager-recruiter' ); ?></a></li>
					<li><a href="<?php echo admin_url( 'post-new.php?post_type=company' ); ?>"><?php _e( 'Add a company via the back-end', 'wp-job-manager-recruiter' ); ?></a></li>

					<?php if ( $permalink = recruiter_get_permalink( 'submit_company_form' ) ) : ?>
						<li><a href="<?php echo esc_url( $permalink ); ?>"><?php _e( 'Add a company via the front-end', 'wp-job-manager-recruiter' ); ?></a></li>
					<?php endif; ?>

					<?php if ( $permalink = recruiter_get_permalink( 'companies' ) ) : ?>
						<li><a href="<?php echo esc_url( $permalink ); ?>"><?php _e( 'View listed companies', 'wp-job-manager-recruiter' ); ?></a></li>
					<?php endif; ?>

					<?php if ( $permalink = recruiter_get_permalink( 'company_dashboard' ) ) : ?>
						<li><a href="<?php echo esc_url( $permalink ); ?>"><?php _e( 'View the company dashboard', 'wp-job-manager-recruiter' ); ?></a></li>
					<?php endif; ?>
				</ul>

			<?php endif; ?>
		</div>
		<?php
	}
}

new WP_Recruiter_Setup();
