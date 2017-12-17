<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_Recruiter_Form_Submit_Company extends WP_Job_Manager_Form {

	public    $form_name = 'submit-company';
	protected $company_id;
	protected $preview_company;

	protected static $_instance = null;

	public function __construct() {

		add_action( 'wp', array( $this, 'process' ) );

		$this->steps  = (array) apply_filters(
			'submit_company_steps',
			array(
				'submit' => array(
					'name'     => __( 'Submit Details', 'wp-job-manager-recruiter' ),
					'view'     => array( $this, 'submit' ),
					'handler'  => array( $this, 'submit_handler' ),
					'priority' => 10
				),
				'preview' => array(
					'name'     => __( 'Preview', 'wp-job-manager-recruiter' ),
					'view'     => array( $this, 'preview' ),
					'handler'  => array( $this, 'preview_handler' ),
					'priority' => 20
				),
				'done' => array(
					'name'     => __( 'Done', 'wp-job-manager-recruiter' ),
					'view'     => array( $this, 'done' ),
					'handler'  => '',
					'priority' => 30
				)
			)
		);

		uasort( $this->steps, array( $this, 'sort_by_priority' ) );

		// Get step/company
		if ( ! empty( $_REQUEST['step'] ) ) {
			$this->step = (
				is_numeric( $_REQUEST['step'] ) ?
					max( absint( $_REQUEST['step'] ), 0 ) :
					array_search( $_REQUEST['step'], array_keys( $this->steps ) )
			);
		}

		$this->company_id = ( ! empty( $_REQUEST['company_id'] ) ? absint( $_REQUEST[ 'company_id' ] ) : 0 );

		if ( ! recruiter_can_user_edit_company( $this->company_id ) ) {
			$this->company_id = 0;
		}

		// Load company details
		if ( $this->company_id ) {

			$company_status = get_post_status( $this->company_id );

			if ( 0 === $this->step && empty( $_POST['company_application_submit_button'] ) ) {

				if ( ! in_array( $company_status, apply_filters( 'recruiter_valid_submit_company_statuses', array( 'preview' ) ) ) ) {

					$this->company_id = 0;
					$this->step       = 0;

				}

			}
		}
	}

	/**
	 * Gets the submitted company ID.
	 *
	 * @return int
	 */
	public function get_company_id() {
		return absint( $this->company_id );
	}

	public function init_fields() {

		if ( $this->fields ) {
			return;
		}

		$this->fields = apply_filters(
			'submit_company_form_fields',
			array(
				'company_fields' => array(
					'company_name' => array(
						'label'       => __( 'Company name', 'wp-job-manager-recruiter' ),
						'type'        => 'text',
						'required'    => true,
						'placeholder' => __( 'My Company', 'wp-job-manager-recruiter' ),
						'priority'    => 1
					),
					'company_website' => array(
						'label'       => __( 'Company website', 'wp-job-manager-recruiter' ),
						'type'        => 'text',
						'required'    => false,
						'placeholder' => __( 'http://', 'wp-job-manager-recruiter' ),
						'priority'    => 2
					),
					'company_content' => array(
						'label'       => __( 'Company description', 'wp-job-manager-recruiter' ),
						'type'        => 'wp-editor',
						'required'    => true,
						'placeholder' => '',
						'priority'    => 3
					),
					'company_category' => array(
						'label'       => __( 'Company category', 'wp-job-manager-recruiter' ),
						'type'        => 'term-multiselect',
						'taxonomy'    => 'company_category',
						'required'    => true,
						'placeholder' => '',
						'priority'    => 4
					),
					'company_size' => array(
						'label'       => __( 'Number of Employees', 'wp-job-manager-recruiter' ),
						'type'        => 'text',
						'required'    => false,
						'placeholder' => __( 'e.g. 10', 'wp-job-manager-recruiter' ),
						'priority'    => 5
					),
					'company_address_street' => array(
						'label'       => __( 'Street Address', 'wp-job-manager-recruiter' ),
						'type'        => 'text',
						'required'    => true,
						'placeholder' => __( 'e.g. 123 King St', 'wp-job-manager-recruiter' ),
						'priority'    => 6
					),
					'company_address_suburb' => array(
						'label'       => __( 'Suburb, State, Postcode', 'wp-job-manager-recruiter' ),
						'type'        => 'text',
						'required'    => true,
						'placeholder' => __( 'e.g. Sydney, NSW 200', 'wp-job-manager-recruiter' ),
						'priority'    => 7
					),
					'company_address_country' => array(
						'label'       => __( 'Country', 'wp-job-manager-recruiter' ),
						'type'        => 'select',
						'required'    => true,
						'placeholder' => __( 'e.g. Sydney, NSW', 'wp-job-manager-recruiter' ),
						'priority'    => 8,
						'options'     => array(
							''                                             => 'Select One',
							'Afghanistan'                                  => 'Afghanistan',
							'Åland Islands'                                => 'Åland Islands',
							'Albania'                                      => 'Albania',
							'Algeria'                                      => 'Algeria',
							'American Samoa'                               => 'American Samoa',
							'Andorra'                                      => 'Andorra',
							'Angola'                                       => 'Angola',
							'Anguilla'                                     => 'Anguilla',
							'Antarctica'                                   => 'Antarctica',
							'Antigua and Barbuda'                          => 'Antigua and Barbuda',
							'Argentina'                                    => 'Argentina',
							'Armenia'                                      => 'Armenia',
							'Aruba'                                        => 'Aruba',
							'Australia'                                    => 'Australia',
							'Austria'                                      => 'Austria',
							'Azerbaijan'                                   => 'Azerbaijan',
							'Bahamas'                                      => 'Bahamas',
							'Bahrain'                                      => 'Bahrain',
							'Bangladesh'                                   => 'Bangladesh',
							'Barbados'                                     => 'Barbados',
							'Belarus'                                      => 'Belarus',
							'Belgium'                                      => 'Belgium',
							'Belize'                                       => 'Belize',
							'Benin'                                        => 'Benin',
							'Bermuda'                                      => 'Bermuda',
							'Bhutan'                                       => 'Bhutan',
							'Bolivia, Plurinational State of'              => 'Bolivia, Plurinational State of',
							'Bonaire, Sint Eustatius and Saba'             => 'Bonaire, Sint Eustatius and Saba',
							'Bosnia and Herzegovina'                       => 'Bosnia and Herzegovina',
							'Botswana'                                     => 'Botswana',
							'Bouvet Island'                                => 'Bouvet Island',
							'Brazil'                                       => 'Brazil',
							'British Indian Ocean Territory'               => 'British Indian Ocean Territory',
							'Brunei Darussalam'                            => 'Brunei Darussalam',
							'Bulgaria'                                     => 'Bulgaria',
							'Burkina Faso'                                 => 'Burkina Faso',
							'Burundi'                                      => 'Burundi',
							'Cambodia'                                     => 'Cambodia',
							'Cameroon'                                     => 'Cameroon',
							'Canada'                                       => 'Canada',
							'Cape Verde'                                   => 'Cape Verde',
							'Cayman Islands'                               => 'Cayman Islands',
							'Central African Republic'                     => 'Central African Republic',
							'Chad'                                         => 'Chad',
							'Chile'                                        => 'Chile',
							'China'                                        => 'China',
							'Christmas Island'                             => 'Christmas Island',
							'Cocos (Keeling) Islands'                      => 'Cocos (Keeling) Islands',
							'Colombia'                                     => 'Colombia',
							'Comoros'                                      => 'Comoros',
							'Congo'                                        => 'Congo',
							'Congo, the Democratic Republic of the'        => 'Congo, the Democratic Republic of the',
							'Cook Islands'                                 => 'Cook Islands',
							'Costa Rica'                                   => 'Costa Rica',
							'Côte d\'Ivoire'                               => 'Côte d\'Ivoire',
							'Croatia'                                      => 'Croatia',
							'Cuba'                                         => 'Cuba',
							'Curaçao'                                      => 'Curaçao',
							'Cyprus'                                       => 'Cyprus',
							'Czech Republic'                               => 'Czech Republic',
							'Denmark'                                      => 'Denmark',
							'Djibouti'                                     => 'Djibouti',
							'Dominica'                                     => 'Dominica',
							'Dominican Republic'                           => 'Dominican Republic',
							'Ecuador'                                      => 'Ecuador',
							'Egypt'                                        => 'Egypt',
							'El Salvador'                                  => 'El Salvador',
							'Equatorial Guinea'                            => 'Equatorial Guinea',
							'Eritrea'                                      => 'Eritrea',
							'Estonia'                                      => 'Estonia',
							'Ethiopia'                                     => 'Ethiopia',
							'Falkland Islands (Malvinas)'                  => 'Falkland Islands (Malvinas)',
							'Faroe Islands'                                => 'Faroe Islands',
							'Fiji'                                         => 'Fiji',
							'Finland'                                      => 'Finland',
							'France'                                       => 'France',
							'French Guiana'                                => 'French Guiana',
							'French Polynesia'                             => 'French Polynesia',
							'French Southern Territories'                  => 'French Southern Territories',
							'Gabon'                                        => 'Gabon',
							'Gambia'                                       => 'Gambia',
							'Georgia'                                      => 'Georgia',
							'Germany'                                      => 'Germany',
							'Ghana'                                        => 'Ghana',
							'Gibraltar'                                    => 'Gibraltar',
							'Greece'                                       => 'Greece',
							'Greenland'                                    => 'Greenland',
							'Grenada'                                      => 'Grenada',
							'Guadeloupe'                                   => 'Guadeloupe',
							'Guam'                                         => 'Guam',
							'Guatemala'                                    => 'Guatemala',
							'Guernsey'                                     => 'Guernsey',
							'Guinea'                                       => 'Guinea',
							'Guinea-Bissau'                                => 'Guinea-Bissau',
							'Guyana'                                       => 'Guyana',
							'Haiti'                                        => 'Haiti',
							'Heard Island and McDonald Islands'            => 'Heard Island and McDonald Islands',
							'Holy See (Vatican City State)'                => 'Holy See (Vatican City State)',
							'Honduras'                                     => 'Honduras',
							'Hong Kong'                                    => 'Hong Kong',
							'Hungary'                                      => 'Hungary',
							'Iceland'                                      => 'Iceland',
							'India'                                        => 'India',
							'Indonesia'                                    => 'Indonesia',
							'Iran, Islamic Republic of'                    => 'Iran, Islamic Republic of',
							'Iraq'                                         => 'Iraq',
							'Ireland'                                      => 'Ireland',
							'Isle of Man'                                  => 'Isle of Man',
							'Israel'                                       => 'Israel',
							'Italy'                                        => 'Italy',
							'Jamaica'                                      => 'Jamaica',
							'Japan'                                        => 'Japan',
							'Jersey'                                       => 'Jersey',
							'Jordan'                                       => 'Jordan',
							'Kazakhstan'                                   => 'Kazakhstan',
							'Kenya'                                        => 'Kenya',
							'Kiribati'                                     => 'Kiribati',
							'Korea, Democratic People\'s Republic of'      => 'Korea, Democratic People\'s Republic of',
							'Korea, Republic of'                           => 'Korea, Republic of',
							'Kuwait'                                       => 'Kuwait',
							'Kyrgyzstan'                                   => 'Kyrgyzstan',
							'Lao People\'s Democratic Republic'            => 'Lao People\'s Democratic Republic',
							'Latvia'                                       => 'Latvia',
							'Lebanon'                                      => 'Lebanon',
							'Lesotho'                                      => 'Lesotho',
							'Liberia'                                      => 'Liberia',
							'Libya'                                        => 'Libya',
							'Liechtenstein'                                => 'Liechtenstein',
							'Lithuania'                                    => 'Lithuania',
							'Luxembourg'                                   => 'Luxembourg',
							'Macao'                                        => 'Macao',
							'Macedonia, the former Yugoslav Republic of'   => 'Macedonia, the former Yugoslav Republic of',
							'Madagascar'                                   => 'Madagascar',
							'Malawi'                                       => 'Malawi',
							'Malaysia'                                     => 'Malaysia',
							'Maldives'                                     => 'Maldives',
							'Mali'                                         => 'Mali',
							'Malta'                                        => 'Malta',
							'Marshall Islands'                             => 'Marshall Islands',
							'Martinique'                                   => 'Martinique',
							'Mauritania'                                   => 'Mauritania',
							'Mauritius'                                    => 'Mauritius',
							'Mayotte'                                      => 'Mayotte',
							'Mexico'                                       => 'Mexico',
							'Micronesia, Federated States of'              => 'Micronesia, Federated States of',
							'Moldova, Republic of'                         => 'Moldova, Republic of',
							'Monaco'                                       => 'Monaco',
							'Mongolia'                                     => 'Mongolia',
							'Montenegro'                                   => 'Montenegro',
							'Montserrat'                                   => 'Montserrat',
							'Morocco'                                      => 'Morocco',
							'Mozambique'                                   => 'Mozambique',
							'Myanmar'                                      => 'Myanmar',
							'Namibia'                                      => 'Namibia',
							'Nauru'                                        => 'Nauru',
							'Nepal'                                        => 'Nepal',
							'Netherlands'                                  => 'Netherlands',
							'New Caledonia'                                => 'New Caledonia',
							'New Zealand'                                  => 'New Zealand',
							'Nicaragua'                                    => 'Nicaragua',
							'Niger'                                        => 'Niger',
							'Nigeria'                                      => 'Nigeria',
							'Niue'                                         => 'Niue',
							'Norfolk Island'                               => 'Norfolk Island',
							'Northern Mariana Islands'                     => 'Northern Mariana Islands',
							'Norway'                                       => 'Norway',
							'Oman'                                         => 'Oman',
							'Pakistan'                                     => 'Pakistan',
							'Palau'                                        => 'Palau',
							'Palestinian Territory, Occupied'              => 'Palestinian Territory, Occupied',
							'Panama'                                       => 'Panama',
							'Papua New Guinea'                             => 'Papua New Guinea',
							'Paraguay'                                     => 'Paraguay',
							'Peru'                                         => 'Peru',
							'Philippines'                                  => 'Philippines',
							'Pitcairn'                                     => 'Pitcairn',
							'Poland'                                       => 'Poland',
							'Portugal'                                     => 'Portugal',
							'Puerto Rico'                                  => 'Puerto Rico',
							'Qatar'                                        => 'Qatar',
							'Réunion'                                      => 'Réunion',
							'Romania'                                      => 'Romania',
							'Russian Federation'                           => 'Russian Federation',
							'Rwanda'                                       => 'Rwanda',
							'Saint Barthélemy'                             => 'Saint Barthélemy',
							'Saint Helena, Ascension and Tristan da Cunha' => 'Saint Helena, Ascension and Tristan da Cunha',
							'Saint Kitts and Nevis'                        => 'Saint Kitts and Nevis',
							'Saint Lucia'                                  => 'Saint Lucia',
							'Saint Martin (French part)'                   => 'Saint Martin (French part)',
							'Saint Pierre and Miquelon'                    => 'Saint Pierre and Miquelon',
							'Saint Vincent and the Grenadines'             => 'Saint Vincent and the Grenadines',
							'Samoa'                                        => 'Samoa',
							'San Marino'                                   => 'San Marino',
							'Sao Tome and Principe'                        => 'Sao Tome and Principe',
							'Saudi Arabia'                                 => 'Saudi Arabia',
							'Senegal'                                      => 'Senegal',
							'Serbia'                                       => 'Serbia',
							'Seychelles'                                   => 'Seychelles',
							'Sierra Leone'                                 => 'Sierra Leone',
							'Singapore'                                    => 'Singapore',
							'Sint Maarten (Dutch part)'                    => 'Sint Maarten (Dutch part)',
							'Slovakia'                                     => 'Slovakia',
							'Slovenia'                                     => 'Slovenia',
							'Solomon Islands'                              => 'Solomon Islands',
							'Somalia'                                      => 'Somalia',
							'South Africa'                                 => 'South Africa',
							'South Georgia and the South Sandwich Islands' => 'South Georgia and the South Sandwich Islands',
							'South Sudan'                                  => 'South Sudan',
							'Spain'                                        => 'Spain',
							'Sri Lanka'                                    => 'Sri Lanka',
							'Sudan'                                        => 'Sudan',
							'Suriname'                                     => 'Suriname',
							'Svalbard and Jan Mayen'                       => 'Svalbard and Jan Mayen',
							'Swaziland'                                    => 'Swaziland',
							'Sweden'                                       => 'Sweden',
							'Switzerland'                                  => 'Switzerland',
							'Syrian Arab Republic'                         => 'Syrian Arab Republic',
							'Taiwan, Province of China'                    => 'Taiwan, Province of China',
							'Tajikistan'                                   => 'Tajikistan',
							'Tanzania, United Republic of'                 => 'Tanzania, United Republic of',
							'Thailand'                                     => 'Thailand',
							'Timor-Leste'                                  => 'Timor-Leste',
							'Togo'                                         => 'Togo',
							'Tokelau'                                      => 'Tokelau',
							'Tonga'                                        => 'Tonga',
							'Trinidad and Tobago'                          => 'Trinidad and Tobago',
							'Tunisia'                                      => 'Tunisia',
							'Turkey'                                       => 'Turkey',
							'Turkmenistan'                                 => 'Turkmenistan',
							'Turks and Caicos Islands'                     => 'Turks and Caicos Islands',
							'Tuvalu'                                       => 'Tuvalu',
							'Uganda'                                       => 'Uganda',
							'Ukraine'                                      => 'Ukraine',
							'United Arab Emirates'                         => 'United Arab Emirates',
							'United Kingdom'                               => 'United Kingdom',
							'United States'                                => 'United States',
							'United States Minor Outlying Islands'         => 'United States Minor Outlying Islands',
							'Uruguay'                                      => 'Uruguay',
							'Uzbekistan'                                   => 'Uzbekistan',
							'Vanuatu'                                      => 'Vanuatu',
							'Venezuela, Bolivarian Republic of'            => 'Venezuela, Bolivarian Republic of',
							'Viet Nam'                                     => 'Viet Nam',
							'Virgin Islands, British'                      => 'Virgin Islands, British',
							'Virgin Islands, U.S.'                         => 'Virgin Islands, U.S.',
							'Wallis and Futuna'                            => 'Wallis and Futuna',
							'Western Sahara'                               => 'Western Sahara',
							'Yemen'                                        => 'Yemen',
							'Zambia'                                       => 'Zambia',
							'Zimbabwe'                                     => 'Zimbabwe'
						)
					),
					'company_logo' => array(
						'label'       => __( 'Logo', 'wp-job-manager-recruiter' ),
						'type'        => 'file',
						'required'    => false,
						'placeholder' => '',
						'priority'    => 9,
						'ajax'        => true,
						'allowed_mime_types' => array(
							'jpg'  => 'image/jpeg',
							'jpeg' => 'image/jpeg',
							'gif'  => 'image/gif',
							'png'  => 'image/png'
						)
					),
					'company_video' => array(
						'label'       => __( 'Video', 'wp-job-manager-resumes' ),
						'type'        => 'text',
						'required'    => false,
						'priority'    => 10,
						'placeholder' => __( 'A link to a video about your company', 'wp-job-manager-resumes' ),
					),
					'links' => array(
						'label'       => __( 'URL(s)', 'wp-job-manager-recruiter' ),
						'add_row'     => __( 'Add URL', 'wp-job-manager-recruiter' ),
						'type'        => 'links', // repeated
						'required'    => false,
						'placeholder' => '',
						'description' => __( 'Optionally provide links to any of your websites or social network profiles.', 'wp-job-manager-recruiter' ),
						'priority'    => 11,
						'fields'      => array(
							'name' => array(
								'label'       => __( 'Name', 'wp-job-manager-recruiter' ),
								'type'        => 'text',
								'required'    => true,
								'placeholder' => '',
								'priority'    => 1
							),
							'url' => array(
								'label'       => __( 'URL', 'wp-job-manager-recruiter' ),
								'type'        => 'text',
								'required'    => true,
								'placeholder' => '',
								'priority'    => 2
							)
						)
					),
					'visibility' => array(
						'label'  => __( 'Visibility', 'wp-job-manager-recruiter' ),
						'type'        => 'select', // repeated
						'required'    => false,
						'description' => __( 'Select who can see your company profile.', 'wp-job-manager-recruiter' ),
						'priority'    => 12,
						'options'     => array(
							'hidden'   => 'Hidden',
							'protected' => 'Candidates and Recruiters',
							'public'    => 'Public'
						)
					)
				)
			)
		);

		if ( ! get_option( 'recruiter_enable_company_categories' ) || wp_count_terms( 'company_category' ) == 0 ) {
			unset( $this->fields['company_fields']['company_category'] );
		}
	}

	public function get_field_template( $key = null, $field = array() ) {

		switch ( $field['type'] ) {

			case 'repeated' :
			case 'links' :
				get_job_manager_template(
					'form-fields/repeated-field.php',
					array(
						'key'   => $key,
						'field' => $field,
						'class' => $this
					),
					'wp-job-manager-recruiter',
					RECRUITER_PLUGIN_DIR . '/templates/'
				);
				break;

			default :
				get_job_manager_template(
					'form-fields/' . $field['type'] . '-field.php',
					array(
						'key' => $key,
						'field' => $field,
						'class' => $this
					)
				);
				break;
		}
	}

	public function get_repeated_field( $field_prefix, $fields ) {

		$items       = array();
		$field_keys  = array_keys( $fields );

		if ( ! empty( $_POST[ 'repeated-row-' . $field_prefix ] ) && is_array( $_POST[ 'repeated-row-' . $field_prefix ] ) ) {

			$indexes = array_map( 'absint', $_POST[ 'repeated-row-' . $field_prefix ] );

			foreach ( $indexes as $index ) {

				$item = array();

				foreach ( $fields as $key => $field ) {

					$field_name = "{$field_prefix}_{$key}_{$index}";

					switch ( $field['type'] ) {

						case 'textarea' :
							$item[ $key ] = wp_kses_post( stripslashes( $_POST[ $field_name ] ) );
							break;

						case 'file' :
							$file = $this->upload_file( $field_name, $field );

							if ( ! $file ) {
								$file = $this->get_posted_field( 'current_' . $field_name, $field );
							} elseif ( is_array( $file ) ) {
								$file = array_filter( array_merge( $file, (array) $this->get_posted_field( 'current_' . $field_name, $field ) ) );
							}

							$item[ $key ] = $file;
							break;

						default :
							if ( is_array( $_POST[ $field_name ] ) ) {
								$item[ $key ] = array_filter( array_map( 'sanitize_text_field', array_map( 'stripslashes', $_POST[ $field_name ] ) ) );
							} else {
								$item[ $key ] = sanitize_text_field( stripslashes( $_POST[ $field_name ] ) );
							}
							break;
					}

					if ( empty( $item[ $key ] ) && ! empty( $field['required'] ) ) {
						continue 2;
					}
				}

				$items[] = $item;

			}
		}

		return $items;
	}

	public function submit() {
		global $job_manager, $post;

		$this->init_fields();

		// Load data if neccessary
		if ( $this->company_id ) {

			$company = get_post( $this->company_id );

			foreach ( $this->fields as $group_key => $fields ) {

				foreach ( $fields as $key => $field ) {

					switch ( $key ) {

						case 'company_name' :
							$this->fields[ $group_key ][ $key ]['value'] = $company->post_title;
							break;

						case 'company_content' :
							$this->fields[ $group_key ][ $key ]['value'] = $company->post_content;
							break;

						case 'company_category' :
							$this->fields[ $group_key ][ $key ]['value'] = wp_get_object_terms( $company->ID, 'company_category', array( 'fields' => 'ids' ) );
							break;

						default:
							$this->fields[ $group_key ][ $key ]['value'] = get_post_meta( $company->ID, '_' . $key, true );
							break;
					}
				}
			}

			$this->fields = apply_filters( 'submit_company_form_fields_get_company_data', $this->fields, $company );

		}

		get_job_manager_template(
			'company-submit.php',
			array(
				'class'              => $this,
				'form'               => $this->form_name,
				'company_id'         => absint( $this->company_id ),
				'action'             => $this->get_action(),
				'company_fields'     => $this->get_fields( 'company_fields' ),
				'step'               => $this->get_step(),
				'submit_button_text' => apply_filters( 'submit_company_form_submit_button_text', __( 'Preview &rarr;', 'wp-job-manager-recruiter' ) )
			),
			'wp-job-manager-recruiter',
			RECRUITER_PLUGIN_DIR . '/templates/'
		);
	}

	public function submit_handler() {

		try {

			if ( empty( $_POST['submit_company'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'submit_form_posted' ) ) {
				return;
			}

			if ( ! is_user_logged_in() ) {
				throw new Exception( __( 'You must be signed in to create your company.', 'wp-job-manager-recruiter' ) );
			}

			// Init fields
			$this->init_fields();

			// Get posted values
			$values = $this->get_posted_fields();

			// Validate required
			if ( is_wp_error( ( $return = $this->validate_fields( $values ) ) ) ) {
				throw new Exception( $return->get_error_message() );
			}

			// Update the company
			$this->save_company( $values['company_fields']['company_name'], $values['company_fields']['company_content'], ( $this->company_id ? '' : 'preview' ), $values );
			$this->update_company_data( $values );

			// Successful, show next step
			$this->step ++;

		} catch ( Exception $e ) {
			$this->add_error( $e->getMessage() );
			return;
		}
	}

	public function get_posted_repeated_field( $key, $field ) {
		return apply_filters( 'submit_company_form_fields_get_repeated_field_data', $this->get_repeated_field( $key, $field['fields'] ) );
	}

	public function get_posted_links_field( $key, $field ) {
		return apply_filters( 'submit_company_form_fields_get_links_data', $this->get_repeated_field( $key, $field['fields'] ) );
	}

	public function preview() {
		global $post, $company_preview;

		wp_enqueue_script( 'wp-job-manager-recruiter-submission' );

		if ( $this->company_id ) {

			$company_preview = true;
			$post = get_post( $this->company_id );
			setup_postdata( $post );

			get_job_manager_template(
				'company-preview.php',
				array( 'form' => $this ),
				'wp-job-manager-recruiter',
				RECRUITER_PLUGIN_DIR . '/templates/'
			);

			wp_reset_postdata();
		}
	}

	public function preview_handler() {

		if ( ! $_POST ) {
			return;
		}

		// Edit = show submit form again
		if ( ! empty( $_POST['edit_company'] ) ) {
			$this->step --;
		}

		// Continue = change job status then show next screen
		if ( ! empty( $_POST['continue'] ) ) {

			$company = get_post( $this->company_id );

			if ( in_array( $company->post_status, array( 'preview', 'expired' ) ) ) {

				// Reset expiry
				delete_post_meta( $company->ID, '_company_expires' );

				// Update listing
				$update_company                  = array();
				$update_company['ID']            = $company->ID;
				$update_company['post_date']     = current_time( 'mysql' );
				$update_company['post_date_gmt'] = current_time( 'mysql', 1 );
				$update_company['post_author']   = get_current_user_id();
				$update_company['post_status']   = apply_filters( 'submit_company_post_status', get_option( 'recruiter_submission_requires_approval' ) ? 'pending' : 'publish', $company );

				wp_update_post( $update_company );
			}

			$this->step ++;

			if( 'before' !== get_option( 'recruiter_paid_listings_flow' ) ){
				wp_safe_redirect( esc_url_raw( add_query_arg( array( 'step' => $this->step, 'company_id' => $this->company_id ) ) ) );
				exit;
			}

		}
	}

	public function done() {

		do_action( 'recruiter_company_submitted', $this->company_id );

		get_job_manager_template(
			'company-submitted.php',
			array( 'company' => get_post( $this->company_id ) ),
			'wp-job-manager-recruiter',
			RECRUITER_PLUGIN_DIR . '/templates/'
		);
	}

	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	protected function save_company( $post_title, $post_content, $status = 'preview', $values = array() ) {

		if ( ! empty( $this->company_id ) ) {

			if ( ! recruiter_can_user_edit_company( $this->company_id ) ) {
				return false;
			}

		} else if ( ! current_user_can( 'manage_company' ) ) {
			return false;
		}

		$company_slug   = array();

		if ( ! empty( $values['company_fields']['company_name'] ) ) {
			$company_slug[] = $values['company_fields']['company_name'];
		}

		if ( ! empty( $values['company_fields']['company_location'] ) ) {
			$company_slug[] = $values['company_fields']['company_location'];
		}

		$data = array(
			'post_title'     => $post_title,
			'post_content'   => $post_content,
			'post_type'      => 'company',
			'comment_status' => 'closed',
			'post_password'  => '',
			'post_name'      => sanitize_title( implode( '-', $company_slug ) )
		);

		if ( $status ) {
			$data['post_status'] = $status;
		}

		if ( ! empty( $values['company_fields']['visibility'] ) && 'hidden' === $values['company_fields']['visibility'] ) {
			$data['post_status'] = 'hidden';
		}

		$data = apply_filters( 'submit_company_form_save_company_data', $data, $post_title, $post_content, $status, $values, $this );

		if ( $this->company_id ) {

			$data['ID'] = $this->company_id;

			wp_update_post( $data );

		} else {

			$this->company_id = wp_insert_post( $data );

			// add user to companies meta table
			WP_Recruiter_Companies::instance()->insert_company_meta( $this->company_id, 'manage_company', get_current_user_id() );
		}
	}

	protected function update_company_data( $values ) {

		// Set defaults
		add_post_meta( $this->company_id, '_featured', 0, true );

		$maybe_attach = array();

		// Loop fields and save meta and term data
		foreach ( $this->fields as $group_key => $group_fields ) {

			foreach ( $group_fields as $key => $field ) {

				// Save taxonomies
				if ( ! empty( $field['taxonomy'] ) ) {

					if ( is_array( $values[ $group_key ][ $key ] ) ) {
						wp_set_object_terms( $this->company_id, $values[ $group_key ][ $key ], $field['taxonomy'], false );
					} else {
						wp_set_object_terms( $this->company_id, array( $values[ $group_key ][ $key ] ), $field['taxonomy'], false );
					}

				// Save meta data
				} else {

					if ( 'company_address_street' === $key ) {
						$location = $values[ $group_key ][ 'company_address_suburb' ]  .', '. $values[ $group_key ][ 'company_address_country' ];

						update_post_meta( $this->company_id, '_company_location', $location );
					}

					update_post_meta( $this->company_id, '_' . $key, $values[ $group_key ][ $key ] );

				}

				// Handle attachments
				if ( 'file' === $field['type'] ) {

					// Must be absolute
					if ( is_array( $values[ $group_key ][ $key ] ) ) {

						foreach ( $values[ $group_key ][ $key ] as $file_url ) {
							$maybe_attach[] = str_replace( array( WP_CONTENT_URL, site_url() ), array( WP_CONTENT_DIR, ABSPATH ), $file_url );
						}

					} else {
						$maybe_attach[] = str_replace( array( WP_CONTENT_URL, site_url() ), array( WP_CONTENT_DIR, ABSPATH ), $values[ $group_key ][ $key ] );
					}
				}
			}
		}

		// Handle attachments
		if ( sizeof( $maybe_attach ) && apply_filters( 'recruiter_attach_uploaded_files', false ) ) {

			/** WordPress Administration Image API */
			include_once( ABSPATH . 'wp-admin/includes/image.php' );

			// Get attachments
			$attachments     = get_posts( 'post_parent=' . $this->company_id . '&post_type=attachment&fields=ids&post_mime_type=image&numberposts=-1' );
			$attachment_urls = array();

			// Loop attachments already attached to the job
			foreach ( $attachments as $attachment_key => $attachment ) {
				$attachment_urls[] = str_replace( array( WP_CONTENT_URL, site_url() ), array( WP_CONTENT_DIR, ABSPATH ), wp_get_attachment_url( $attachment ) );
			}

			foreach ( $maybe_attach as $attachment_url ) {

				if ( ! in_array( $attachment_url, $attachment_urls ) ) {

					$attachment = array(
						'post_title'   => get_the_title( $this->company_id ),
						'post_content' => '',
						'post_status'  => 'inherit',
						'post_parent'  => $this->company_id,
						'guid'         => $attachment_url
					);

					if ( $info = wp_check_filetype( $attachment_url ) ) {
						$attachment['post_mime_type'] = $info['type'];
					}

					$attachment_id = wp_insert_attachment( $attachment, $attachment_url, $this->company_id );

					if ( ! is_wp_error( $attachment_id ) ) {
						wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $attachment_url ) );
					}
				}
			}
		}

		do_action( 'recruiter_update_company_data', $this->company_id, $values );
	}

	protected function validate_fields( $values ) {

		foreach ( $this->fields as $group_key => $fields ) {

			foreach ( $fields as $key => $field ) {

				if ( $field['required'] && empty( $values[ $group_key ][ $key ] ) ) {
					return new WP_Error( 'validation-error', sprintf( __( '%s is a required field', 'wp-job-manager-recruiter' ), $field['label'] ) );
				}

				if ( ! empty( $field['taxonomy'] ) && in_array( $field['type'], array( 'term-checklist', 'term-select', 'term-multiselect' ) ) ) {

					if ( is_array( $values[ $group_key ][ $key ] ) ) {

						foreach ( $values[ $group_key ][ $key ] as $term ) {

							if ( ! term_exists( $term, $field['taxonomy'] ) ) {
								return new WP_Error( 'validation-error', sprintf( __( '%s is invalid', 'wp-job-manager-recruiter' ), $field['label'] ) );
							}

						}

					} elseif ( ! empty( $values[ $group_key ][ $key ] ) ) {

						if ( ! term_exists( $values[ $group_key ][ $key ], $field['taxonomy'] ) ) {
							return new WP_Error( 'validation-error', sprintf( __( '%s is invalid', 'wp-job-manager-recruiter' ), $field['label'] ) );
						}

					}

				}

				if ( 'company_email' === $key ) {
					if ( ! empty( $values[ $group_key ][ $key ] ) && ! is_email( $values[ $group_key ][ $key ] ) ) {
						throw new Exception( __( 'Please enter a valid email address', 'wp-job-manager-recruiter' ) );
					}
				}
			}
		}

		return apply_filters( 'submit_company_form_validate_fields', true, $this->fields, $values );
	}
}
