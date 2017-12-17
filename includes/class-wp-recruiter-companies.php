<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_Recruiter_Companies {

	protected $company_meta;
	protected $company_roles;

	protected static $_instance = null;

	public function __construct() {
		global $wpdb;

		$this->company_meta = "{$wpdb->prefix}job_manager_company_meta";
		$this->company_roles = $this->get_company_roles();

		add_action( 'delete_post', array( $this, 'delete_company' ), 10, 1 );

		add_filter( 'job_manager_get_dashboard_jobs_args', array( $this, 'modify_dashboard_jobs_args' ), 2, 1 );
		add_filter( 'job_manager_user_can_edit_job', array( $this, 'can_user_edit_job' ), 2, 2 );
	}

	public function modify_dashboard_jobs_args( $args = array() ) {

		if ( current_user_can('manage_company') ) {
			$args['author'] = '';

			if ( ! current_user_can( 'manage_options' ) ) {

				$args['meta_key']	  = '_company_id';
				$args['meta_value']	  = $this->get_user_company_ids();
				$args['meta_compare'] = 'IN';

			}
		}

		return $args;
	}

	public function can_user_edit_job( $can_edit = false, $job_id = null ) {

		if ( false === $can_edit ) {
			$can_edit = ( in_array( get_post_meta( $job_id, '_company_id', true ), $this->get_user_company_ids() ) );
		}

		return $can_edit;
	}

	public function get_user_companies() {

		$companies = array();

		if ( is_user_logged_in() && current_user_can( 'manage_company' ) ) {

			$args = apply_filters(
				'recruiter_get_user_companies_args',
				array(
					'post_type'			  => 'company',
					'post_status'		  => array( 'expired','pending', 'publish', 'hidden' ),
					'ignore_sticky_posts' => 1,
					'posts_per_page'	  => -1,
					'offset'			  => ( ( max( 1, get_query_var('paged') ) - 1 ) * 25 ),
					'orderby'			  => 'title'
				)
			);

			// if user is not administrator restrict posts
			if ( ! current_user_can( 'manage_options' ) ) {
				global $wpdb;

				 $company_ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT company_id
						FROM {$this->company_meta}
						WHERE meta_key = 'manage_company'
						AND meta_value = '%d'",
						get_current_user_id()
					)
				);

				$args['post__in'] = ( ! empty( $company_ids ) ? $company_ids : array( 0 ) );
			}

			$companies = get_posts( $args );
		}

		return apply_filters( 'recruiter_get_user_companies', $companies );
	}

	public function get_user_company_ids( $user_id = null ) {
		global $wpdb;

		if ( current_user_can( 'manage_options' ) && empty( $user_id ) ) {

			$sql =
				"SELECT ID FROM {$wpdb->posts}
				WHERE post_type = 'company'
				AND post_status IN ('publish', 'expired', 'pending', 'hidden')";

		} else {

			$sql = $wpdb->prepare(
				"SELECT company_id as ID FROM {$this->company_meta}
				WHERE meta_key IN ({$this->company_roles})
				AND meta_value = '%d'",
				( ! empty( $user_id ) ? $user_id : get_current_user_id() )
			);

		}

		$company_ids = $wpdb->get_col( $sql );

		if (empty($company_ids)) {
			$company_ids = array(0);
		}

		return apply_filters( 'recruiter_get_user_company_ids', $company_ids, $user_id );
	}

	public function get_user_companies_options( $user_id = null, $scope = null ) {
		global $wpdb;

		if ( 'company' === $scope && current_user_can( 'manage_options' ) ) {

			$sql = "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = 'company'";

		} else {

			if ( 'company' === $scope && current_user_can( 'manage_company' ) ) {
				$user_id = get_current_user_id();
			}

			$sql = $wpdb->prepare(
				"SELECT cm.company_id as ID, cm.meta_key as role, p.post_title
				FROM {$this->company_meta} AS cm
				INNER JOIN {$wpdb->posts} AS p ON p.ID = cm.company_id
				WHERE cm.meta_key IN ({$this->company_roles})
				AND cm.meta_value = '%d'
				AND p.post_type = 'company'",
				( ! empty( $user_id ) ? $user_id : get_current_user_id() )
			);

		}

		$companies = $wpdb->get_results( $sql );

		return apply_filters( 'recruiter_get_user_companies_options', $companies, $user_id, $scope );
	}

	public function can_user_create_company() {

		$can_post = false;

		if ( is_user_logged_in() && current_user_can( 'manage_company' ) ) {
			$can_post = true;
		}

		return apply_filters( 'can_user_create_company', $can_post );
	}

	public function can_user_edit_company( $company_id = null ) {

		$can_edit = false;

		// company_id can't be empty and user must be logged in
		if ( ! empty( $company_id ) && is_user_logged_in() && current_user_can( 'manage_company' ) ) {

			$company = get_post( $company_id );

			// company post must exist and user must belong to company
			if ( ! empty( $company ) && 'company' === $company->post_type ) {


				if ( current_user_can( 'manage_options' ) ) {

					$can_edit = true;

				} else {

					$user_companies = $this->get_user_companies_options( get_current_user_id(), 'employee' );

					if ( ! empty( $user_companies ) ) {
						foreach( $user_companies as $user_company ) {
							if ( $user_company->ID == $company_id && 'manage_company' == $user_company->role ) {
								$can_edit = true;
							}
						}
					}
				}
			}
		}

		return apply_filters( 'recruiter_can_user_edit_company', $can_edit, $company_id );
	}

	public function delete_company( $company_id = null ) {
		global $wpdb;

		if ( 'company' !== get_post_type( $company_id ) ) {
			return false;
		}

		$wpdb->query(
			$wpdb->prepare( "DELETE FROM {$this->company_meta} WHERE company_id = %d", $company_id )
		);
	}

	public function get_company_employees( $company_id = null ) {
		global $wpdb;

		$employees = array();

		if ( ! empty( $company_id ) ) {

			$employees = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT ID, user_login, user_nicename, user_email, display_name
					FROM {$wpdb->users} WHERE ID IN (
						SELECT meta_value FROM {$this->company_meta}
						WHERE company_id = %d AND meta_key IN ($this->company_roles)
					)",
					$company_id
				)
			);
		}

		return apply_filters( 'recruiter_get_company_employees', $employees );
	}

	public function get_user_employees() {
		global $wpdb;

		$employees = array();

		if ( is_user_logged_in() ) {

			if ( current_user_can( 'manage_options' ) ) {

				$employees = get_users();

			} else if ( current_user_can( 'manage_company' ) ) {

				$employee_ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT meta_value FROM {$this->company_meta}
						WHERE company_id IN (
							SELECT company_id
							FROM {$this->company_meta}
							WHERE meta_key = 'manage_company'
							AND meta_value = '%d'
						) AND meta_key IN ({$this->company_roles})",
						get_current_user_id()
					)
				);

				if ( ! empty( $employee_ids ) ) {
					$employees = get_users( array( 'include' => $employee_ids ) );
				}

			}

		}

		return apply_filters( 'recruiter_get_user_employees', $employees );
	}

	public function can_user_create_employee() {

		$can_post = false;

		if ( is_user_logged_in() && current_user_can( 'manage_company' ) ) {
			$can_post = true;
		}

		return apply_filters( 'recruiter_can_user_create_employee', $can_post );
	}

	public function can_user_edit_employee( $employee_id = null, $company_id = null ) {

		$can_edit = false;

		if ( is_user_logged_in() && current_user_can( 'manage_company' ) ) {

			// check if current user is an administrator
			if ( current_user_can( 'manage_options' ) ) {

				$can_edit = true;

			} else {

				// can the current user manage the given company
				if ( ! empty( $company_id ) && $this->can_user_edit_company( $company_id ) ) {

					$can_edit = true;

				} else {

					// can the current user manage any of the employee's companies
					$intersect = array_intersect(
						$this->get_user_company_ids( $employee_id ),
						$this->get_user_company_ids()
					);

					if ( ! empty( $intersect ) ) {
						$can_edit = true;
					}
				}
			}
		}

		return apply_filters( 'recruiter_can_user_edit_employee', $can_edit, $employee_id );
	}

	public function set_employee_role( $companies = array() ) {

		$default_role = get_option( 'recruiter_default_employee_role', 'recruiter' );

		if ( ! empty( $companies ) ) {
			foreach( $companies as $company ) {
				if ( 'manage_company' === $company['company_role'] ) {
					$default_role = 'employer';
				}
			}
		}

		return $default_role;
	}

	public function create_employee( $args = array() ) {

		if ( is_user_logged_in() && current_user_can( 'manage_company' ) ) {

			$defaults = array(
				'first_name' => '',
				'last_name'	 => '',
				'username'	 => '',
				'email'		 => '',
				'password'	 => wp_generate_password(),
				'role'		 => get_option( 'recruiter_default_employee_role' ),
				'company_id' => ''
			);

			extract( wp_parse_args( $args, $defaults ) );

			$username = sanitize_user( $username );
			$email	  = apply_filters( 'user_registration_email', sanitize_email( $email ) );

			if ( empty( $first_name ) ) {
				return new WP_Error( 'validation-error', __( 'Invalid first name.', 'wp-job-manager-recruiter' ) );
			} else {
				$first_name = sanitize_text_field( $first_name );
			}

			if ( empty( $last_name ) ) {
				return new WP_Error( 'validation-error', __( 'Invalid last name.', 'wp-job-manager-recruiter' ) );
			} else {
				$last_name = sanitize_text_field( $last_name );
			}

			if ( empty( $email ) ) {
				return new WP_Error( 'validation-error', __( 'Invalid email address.', 'wp-job-manager-recruiter' ) );
			}

			if ( empty( $username ) ) {
				$username = sanitize_user( current( explode( '@', $email ) ) );
			}

			if ( ! is_email( $email ) ) {
				return new WP_Error( 'validation-error', __( 'Your email address isn&#8217;t correct.', 'wp-job-manager-recruiter' ) );
			}

			if ( email_exists( $email ) ) {
				return new WP_Error( 'validation-error', __( 'This email is already registered, please choose another one.', 'wp-job-manager' ) );
			}

			// Ensure username is unique
			$append		= 1;
			$o_username = $username;

			while ( username_exists( $username ) ) {
				$username = $o_username . $append;
				$append ++;
			}

			// Final error checking
			$reg_errors = new WP_Error();
			$reg_errors = apply_filters( 'job_manager_registration_errors', $reg_errors, $username, $email );

			do_action( 'job_manager_register_post', $username, $email, $reg_errors );

			if ( $reg_errors->get_error_code() ) {
				return $reg_errors;
			}

			// Create account
			$new_user = array(
				'first_name' => $first_name,
				'last_name'	 => $last_name,
				'user_login' => $username,
				'user_pass'	 => $password,
				'user_email' => $email,
				'role'		 => $role
			);

			$user_id = wp_insert_user( apply_filters( 'job_manager_create_account_data', $new_user ) );

			if ( is_wp_error( $user_id ) ) {
				return $user_id;
			}

			// Notify
			do_action( 'woocommerce_created_customer', $user_id, $new_user, true );

			return $user_id;
		}

		return false;
	}

	public function delete_employee( $employee_id = null ) {

		if ( $this->can_user_edit_employee( $employee_id ) ) {

			global $wpdb;

			// delete user record from company meta
			$wpdb->get_results(
				$wpdb->prepare(
					"DELETE FROM {$this->company_meta}
					WHERE meta_key IN ({$this->company_roles})
					AND meta_value = %d",
					$employee_id
				)
			);

			return wp_delete_user( absint( $employee_id ), get_current_user_id() );

		}

		return false;
	}

	public function update_user_role( $company_id = null, $role = null, $employee_id = null ) {
		global $wpdb;

		if ( ! $this->can_user_edit_employee( $employee_id, $company_id ) ) {
			return false;
		}

		$meta_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_id FROM {$this->company_meta} WHERE company_id = %d AND meta_value = %d",
				$company_id,
				$employee_id
			)
		);

		if ( ! empty( $meta_id ) ) {
			$this->update_company_meta( $company_id, $role, $employee_id, array( 'meta_id' => $meta_id ) );
		} else {
			$this->insert_company_meta( $company_id, $role, $employee_id );
		}
	}

	public function delete_user_role( $company_id = null, $employee_id = null ) {
		global $wpdb;

		$sql = $wpdb->prepare(
			"DELETE FROM {$this->company_meta}
			WHERE company_id = %d
			AND meta_key IN ({$this->company_roles})
			AND meta_value = %d",
			$company_id,
			$employee_id
		);

		return $wpdb->get_results( $sql );
	}

	public function insert_company_meta( $company_id = null, $meta_key = null, $meta_value = null ) {
		global $wpdb;

		$company_id = absint( $company_id );

		if ( empty( $company_id ) || empty( $meta_key ) ) {
			return false;
		}

		$data = array(
			'company_id' => $company_id,
			'meta_key'	 => sanitize_text_field( $meta_key ),
			'meta_value' => sanitize_text_field( $meta_value )
		);

		return (bool) $wpdb->insert( $this->company_meta, $data );
	}

	public function get_company_meta( $company_id = null, $meta_key = null, $meta_value = null, $limit = null ) {
		global $wpdb;

		$sql = "SELECT * FROM {$this->company_meta} WHERE 1=1";

		if ( ! empty( $company_id ) ) {
			$sql .= sprintf( ' AND company_id = "%d"', absint( $company_id ) );
		}

		if ( ! empty( $meta_key ) ) {
			$sql .= sprintf( ' AND meta_key = "%s"', sanitize_text_field( $meta_key ) );
		}

		if ( ! empty( $meta_value ) ) {
			$sql .= sprintf( ' AND meta_value = "%s"', sanitize_text_field( $meta_value ) );
		}

		if ( 0 <= ( $limit = intval( $limit ) ) ) {
			$sql .= ' LIMIT ' . ( ! empty( $limit ) ? $limit : 10 );
		}

		return $wpdb->get_results( $this->company_meta, $sql );
	}

	public function update_company_meta( $company_id = null, $meta_key = null, $meta_value = null, $where = array() ) {
		global $wpdb;

		$company_id = absint( $company_id );

		if ( empty( $company_id ) || empty( $meta_key ) || empty( $where ) ) {
			return false;
		}

		$data = array();

		if( ! empty( $company_id ) ) {
			$data['company_id'] = $company_id;
		}

		if( ! empty( $meta_key ) ) {
			$data['meta_key'] = sanitize_text_field( $meta_key );
		}

		if( ! empty( $meta_value ) ) {
			$data['meta_value'] = sanitize_text_field( $meta_value );
		}

		$_where = array();

		foreach( $where as $key => $value ) {
			$_where[ sanitize_text_field( $key ) ] = sanitize_text_field( $value );
		}

		return (bool) $wpdb->update( $this->company_meta, $data, $_where );
	}

	public function delete_company_meta( $where = array() ) {
		global $wpdb;

		if ( empty( $where ) ) {
			return false;
		}

		$_where = array();

		foreach( $where as $key => $value ) {
			$_where[ sanitize_text_field( $key ) ] = sanitize_text_field( $value );
		}

		return (bool) $wpdb->delete( $this->company_meta, $data, $_where );
	}

	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	protected function get_company_roles() {

		$roles = array( 'manage_company', 'manage_listings' );

		return "'" . implode( "', '", apply_filters( 'recruiter_get_company_roles', $roles ) ) . "'";
	}
}
