<?php

birch_ns( 'birchschedule.model', function( $ns ) {

		global $birchschedule;

		$ns->init = function() use ( $ns ) {
			add_filter( 'birchbase_db_get_essential_post_columns',
				array( $ns, 'add_more_essential_columns' ), 20, 2 );
		};

		$ns->save = function( $model, $config = false ) {
			global $birchschedule, $birchpress;

			$model = $birchschedule->model->pre_save( $model, $config );
			return $birchpress->db->save( $model, $config );
		};

		$ns->pre_save = function( $model, $config ) {
			return $model;
		};

		$ns->post_get = function( $model ) {
			return $model;
		};

		$ns->is_valid_id = function( $id ) {
			global $birchpress;

			return $birchpress->db->is_valid_id( $id );
		};

		$ns->add_more_essential_columns = function( $columns, $post_type ) {
			if ( $post_type == 'birs_staff' ) {
				$columns[] = 'post_title';
				$columns[] = 'post_content';
			}
			if ( $post_type == 'birs_service' ) {
				$columns[] = 'post_title';
				$columns[] = 'post_content';
			}
			return $columns;
		};

		$ns->get_service_pre_payment_fee = function( $service_id ) {
			global $birchschedule;

			if ( !$birchschedule->model->is_valid_id( $service_id ) ) {
				return 0;
			}

			$service = $birchschedule->model->get( $service_id,
				array(
					'base_keys' => array(),
					'meta_keys' => array(
						'_birs_service_pre_payment_fee',
						'_birs_service_price'
					)
				) );

			$is_prepayment_enabled = $birchschedule->model->is_prepayment_enabled( $service_id );
			if ( !$is_prepayment_enabled ) {
				return 0;
			}

			$service_pre_payment_fee = $service['_birs_service_pre_payment_fee'];
			if ( $service_pre_payment_fee ) {
				if ( $service_pre_payment_fee['pre_payment_type'] == 'fixed' ) {
					return floatval( $service_pre_payment_fee['fixed'] );
				}
				else if ( $service_pre_payment_fee['pre_payment_type'] == 'percent' ) {
					return $service_pre_payment_fee['percent'] * 0.01 *
					$service['_birs_service_price'];
				} else {
					return 0;
				}
			} else {
				return 0;
			}
		};

		$ns->get_service_padding = function( $service_id, $type ) {
			global $birchschedule;
			birch_assert( $birchschedule->model->is_valid_id( $service_id ) );
			birch_assert( $type === 'before' || $type === 'after' );

			$service = $birchschedule->model->get( $service_id,
				array(
					'meta_keys' => array(
						'_birs_service_padding', '_birs_service_padding_type'
					),
					'base_keys' => array()
				) );

			$padding_type = $service['_birs_service_padding_type'];
			if ( $padding_type === 'before-and-after' || $padding_type === $type ) {
				$padding = $service['_birs_service_padding'];
			} else {
				$padding = 0;
			}
			return $padding;
		};

		$ns->get_service_padding_before = function( $service_id ) use ( $ns ) {
			return $ns->get_service_padding( $service_id, 'before' );
		};

		$ns->get_service_padding_after = function( $service_id ) use ( $ns ) {
			return $ns->get_service_padding( $service_id, 'after' );
		};

		$ns->get_service_length = function( $service_id ) {
			global $birchschedule;
			birch_assert( $birchschedule->model->is_valid_id( $service_id ) );

			$service = $birchschedule->model->get( $service_id,
				array(
					'meta_keys' => array(
						'_birs_service_length', '_birs_service_length_type'
					),
					'base_keys' => array()
				) );
			$length = $service['_birs_service_length'];
			$length_type = $service['_birs_service_length_type'];
			if ( $length_type == 'hours' ) {
				$length = $length * 60;
			}
			return $length;
		};

		$ns->get_service_length_with_paddings = function( $service_id ) use ( $ns ) {
			return $ns->get_service_length( $service_id ) +
			$ns->get_service_padding_before( $service_id ) +
			$ns->get_service_padding_after( $service_id );
		};

		$ns->get_service_price = function( $service_id ) {
			global $birchschedule;

			$service = $birchschedule->model->get( $service_id,
				array(
					'base_keys' => array(),
					'meta_keys' => array(
						'_birs_service_price'
					)
				) );
			if ( isset( $service['_birs_service_price'] ) ) {
				return floatval( $service['_birs_service_price'] );
			} else {
				return 0;
			}
		};

		$ns->get_service_timeslot = function( $service_id ) {
			return 15;
		};

		$ns->get_service_capacity = function( $service_id ) {
			return 1;
		};

		$ns->get = function( $post, $config = false ) {
			global $birchpress, $birchschedule;

			$model = $birchpress->db->get( $post, $config );
			if ( $model ) {
				return $birchschedule->model->post_get( $model );
			} else {
				return false;
			}
		};

		$ns->delete = function( $id ) {
			global $birchpress;
			return $birchpress->db->delete( $id );
		};

		$ns->query = function( $criteria, $config = false ) use ( $ns ) {
			global $birchschedule, $birchpress;

			if ( !is_array( $config ) ) {
				$config = array();
			}
			$config['fn_get'] = array( $ns, 'get' );
			$models = $birchpress->db->query( $criteria, $config );
			return $models;
		};

		$ns->get_staff_schedule_by_location = function( $staff_id, $location_id ) {
			global $birchschedule;

			$schedules = array();
			$staff = $birchschedule->model->get( $staff_id, array(
					'base_keys' => array(),
					'meta_keys' => array( '_birs_staff_schedule' )
				) );
			$staff_schedule = $staff['_birs_staff_schedule'];
			if ( isset( $staff_schedule[$location_id] ) ) {
				$location_schedule = $staff_schedule[$location_id];
			} else {
				$location_schedule = array();
			}
			return $location_schedule;
		};

		$ns->get_default_country = function() {
			return 'US';
		};

		$ns->get_default_state = function() {
			return false;
		};

		$ns->update_model_relations = function( $source_id, $source_key,
			$target_type, $target_key ) {

			global $birchschedule;

			$assigned_targets = get_post_meta( $source_id, $source_key, true );
			if ( $assigned_targets ) {
				$assigned_targets = unserialize( $assigned_targets );
			}
			if ( !$assigned_targets ) {
				$assigned_targets = array();
			}
			$targets = $birchschedule->model->query(
				array(
					'post_type' => $target_type
				),
				array(
					'base_keys' => array(),
					'meta_keys' => array()
				)
			);
			foreach ( $targets as $target ) {
				$assigned_sources = get_post_meta( $target['ID'], $target_key, true );
				$assigned_sources = unserialize( $assigned_sources );
				if ( array_key_exists( $target['ID'], $assigned_targets ) ) {
					$assigned_sources[$source_id] = 'on';
				} else {
					unset( $assigned_sources[$source_id] );
				}
				update_post_meta( $target['ID'], $target_key, serialize( $assigned_sources ) );
			}
		};

		$ns->is_prepayment_enabled = function( $service_id ) {
			global $birchschedule;

			$service = $birchschedule->model->get( $service_id, array(
					'meta_keys' => array(
						'_birs_service_enable_pre_payment'
					),
					'base_keys' => array()
				) );
			if ( isset( $service['_birs_service_enable_pre_payment'] ) ) {
				return $service['_birs_service_enable_pre_payment'];
			} else {
				return false;
			}
		};

		$ns->check_password = function( $email, $password ) {
			$user = get_user_by( 'email', $email );
			if ( !$user ) {
				return false;
			}
			return wp_check_password( $password, $user->user_pass, $user->ID );
		};

		$ns->get_appointment_fields = function() {
			$meta_keys = array(
				'_birs_appointment_service', '_birs_appointment_staff',
				'_birs_appointment_location', '_birs_appointment_timestamp',
				'_birs_appointment_uid', '_birs_appointment_duration',
				'_birs_appointment_padding_before', '_birs_appointment_padding_after',
				'_birs_appointment_capacity'
			);
			return $meta_keys;
		};

		$ns->get_appointment1on1_fields = function() {
			return
			array(
				'_birs_appointment_id',
				'_birs_client_id',
				'_birs_appointment1on1_payment_status',
				'_birs_appointment1on1_reminded',
				'_birs_appointment1on1_price',
				'_birs_appointment1on1_uid'
			);
		};

		$ns->get_appointment1on1_custom_fields = function() {
			return array( '_birs_appointment_notes' );
		};

		$ns->get_client_by_email = function( $email, $config ) {
			global $birchschedule;
			$criteria = array(
				'post_type' => 'birs_client',
				'post_status' => 'publish',
				'meta_query' => array(
					array(
						'key' => '_birs_client_email',
						'value' => $email
					)
				)
			);
			$clients = $birchschedule->model->query( $criteria, $config );
			if ( sizeof( $clients ) > 0 ) {
				$clients_values = array_values( $clients );
				$client = array_shift( $clients_values );
				return $client;
			}
			return false;
		};

		$ns->get_client_fields = function() {
			$client_meta_keys = array(
				'_birs_client_name_first', '_birs_client_name_last',
				'_birs_client_email', '_birs_client_phone',
				'_birs_client_address1', '_birs_client_address2',
				'_birs_client_city', '_birs_client_state',
				'_birs_client_province', '_birs_client_country',
				'_birs_client_zip'
			);
			return $client_meta_keys;
		};

		$ns->get_payment_fields = function() {
			return array(
				'_birs_payment_appointment', '_birs_payment_client',
				'_birs_payment_amount', '_birs_payment_type',
				'_birs_payment_trid', '_birs_payment_notes',
				'_birs_payment_timestamp', '_birs_payment_currency',
				'_birs_payment_3rd_txn_id'
			);
		};

		$ns->get_meta_key_label = function( $meta_key ) {
			return '';
		};

		$ns->get_services_by_location = function( $location_id ) {
			global $birchschedule;
			birch_assert( $birchschedule->model->is_valid_id( $location_id ) );

			$location = array(
				'ID' => $location_id
			);
			$services = $birchschedule->model->query(
				array(
					'post_type' => 'birs_service',
					'order' => 'ASC',
					'orderby' => 'title'
				),
				array(
					'meta_keys' => array(
						'_birs_service_assigned_locations'
					),
					'base_keys' => array(
						'post_title'
					)
				)
			);
			$assigned_services = array();
			foreach ( $services as $service ) {
				$assigned_locations = $service['_birs_service_assigned_locations'];
				if ( $assigned_locations ) {
					if ( isset( $assigned_locations[$location_id] ) ) {
						$assigned_services[$service['ID']] = $service['post_title'];
					}
				}
			}
			return $assigned_services;
		};

		$ns->get_staff_by_location = function( $location_id ) {
			global $birchschedule;

			$staff = $birchschedule->model->query(
				array(
					'post_type' => 'birs_staff',
					'order' => 'ASC',
					'orderby' => 'title'
				),
				array(
					'meta_keys' => array(
						'_birs_staff_schedule'
					),
					'base_keys' => array(
						'post_title'
					)
				)
			);
			$assigned_staff = array();
			foreach ( $staff as $the_staff ) {
				$staff_schedule = $the_staff['_birs_staff_schedule'];
				if ( isset( $staff_schedule[$location_id] ) ) {
					$location_schedule = $staff_schedule[$location_id];
				} else {
					$location_schedule = array();
				}
				if ( isset( $location_schedule['schedules'] ) &&
					sizeof( $location_schedule['schedules'] ) > 0 ) {

					$assigned_staff[$the_staff['ID']] = $the_staff['post_title'];
				}
			}
			return $assigned_staff;
		};

		$ns->get_services_by_staff = function( $staff_id ) {
			$assigned_services = get_post_meta( $staff_id, '_birs_assigned_services', true );
			$assigned_services = unserialize( $assigned_services );
			if ( $assigned_services === false ) {
				$assigned_services = array();
			}
			return $assigned_services;
		};

		$ns->get_staff_by_service = function( $service_id ) {
			$assigned_staff = get_post_meta( $service_id, '_birs_assigned_staff', true );
			$assigned_staff = unserialize( $assigned_staff );
			if ( $assigned_staff === false ) {
				$assigned_staff = array();
			}
			return $assigned_staff;
		};

		$ns->get_locations_map = function() {
			global $birchschedule;
			$locations = $birchschedule->model->query(
				array(
					'post_type' => 'birs_location',
					'order' => 'ASC',
					'orderby' => 'title'
				),
				array(
					'base_keys' => array(
						'post_title'
					),
					'meta_keys' => array()
				)
			);
			return $locations;
		};

		$ns->get_services_map = function() {
			global $birchschedule;
			$services = $birchschedule->model->query(
				array(
					'post_type' => 'birs_service',
					'order' => 'ASC',
					'orderby' => 'title'
				),
				array(
					'base_keys' => array(
						'post_title'
					),
					'meta_keys' => array()
				)
			);
			return $services;
		};

		$ns->get_locations_services_map = function() {
			global $birchschedule;

			$map = array();
			$locations = $birchschedule->model->query(
				array(
					'post_type' => 'birs_location'
				),
				array(
					'base_keys' => array(),
					'meta_keys' => array()
				)
			);
			$services = $birchschedule->model->query(
				array(
					'post_type' => 'birs_service',
					'order' => 'ASC',
					'orderby' => 'title'
				),
				array(
					'base_keys' => array(
						'post_title'
					),
					'meta_keys' => array()
				)
			);
			$services_map = array();
			foreach ( $services as $service_id => $service ) {
				$services_map[$service_id] = $service['post_title'];
			}
			foreach ( $locations as $location ) {
				$map[$location['ID']] = $services_map;
			}
			return $map;
		};

		$ns->get_locations_staff_map = function() {
			global $birchschedule;

			$map = array();
			$locations = $birchschedule->model->query(
				array(
					'post_type' => 'birs_location'
				),
				array(
					'base_keys' => array(),
					'meta_keys' => array()
				)
			);
			foreach ( $locations as $location ) {
				$map[$location['ID']] = $birchschedule->model->get_staff_by_location( $location['ID'] );
			}
			return $map;
		};

		$ns->get_services_staff_map = function() {
			global $birchschedule;

			$map = array();
			$services = $birchschedule->model->query(
				array(
					'post_type' => 'birs_service'
				),
				array(
					'meta_keys' => array(
						'_birs_assigned_staff'
					),
					'base_keys' => array()
				)
			);
			foreach ( $services as $service ) {
				$assigned_staff_ids = $service['_birs_assigned_staff'];
				$staff = $birchschedule->model->query(
					array(
						'post_type' => 'birs_staff'
					),
					array(
						'base_keys' => array(
							'post_title'
						),
						'meta_keys' => array()
					)
				);
				$assigned_staff = array();
				foreach ( $staff as $thestaff ) {
					if ( array_key_exists( $thestaff['ID'], $assigned_staff_ids ) ) {
						$assigned_staff[$thestaff['ID']] = $thestaff['post_title'];
					}
					$map[$service['ID']] = $assigned_staff;
				}
			}
			return $map;
		};

		$ns->get_services_locations_map = function() {
			global $birchschedule;

			$map = array();
			$services = $birchschedule->model->query(
				array(
					'post_type' => 'birs_service'
				),
				array(
					'base_keys' => array(),
					'meta_keys' => array()
				)
			);
			$locations = $birchschedule->model->query(
				array(
					'post_type' => 'birs_location',
					'order' => 'ASC',
					'orderby' => 'title'
				),
				array(
					'base_keys' => array(
						'post_title'
					),
					'meta_keys' => array()
				)
			);
			$locations_map = array();
			foreach ( $locations as $location_id => $location ) {
				$locations_map[$location_id] = $location['post_title'];
			}
			foreach ( $services as $service ) {
				$map[$service['ID']] = $locations_map;
			}
			return $map;
		};

		$ns->get_locations_listing_order = function() {
			global $birchschedule;

			$locations = $birchschedule->model->query(
				array(
					'post_type' => 'birs_location',
					'order' => 'ASC',
					'orderby' => 'title'
				),
				array(
					'base_keys' => array(
						'post_title'
					),
					'meta_keys' => array()
				)
			);
			$locations_order = array_keys( $locations );
			return $locations_order;
		};

		$ns->get_staff_listing_order_type = function() {
			return 'by_title';
		};

		$ns->get_staff_listing_order = function() {
			global $birchschedule;

			$staff = $birchschedule->model->query(
				array(
					'post_type' => 'birs_staff',
					'order' => 'ASC',
					'orderby' => 'title'
				),
				array(
					'base_keys' => array(
						'post_title'
					),
					'meta_keys' => array()
				)
			);
			$staff_order = array_keys( $staff );
			return $staff_order;
		};

		$ns->get_services_listing_order = function() {
			global $birchschedule;

			$services = $birchschedule->model->query(
				array(
					'post_type' => 'birs_service',
					'order' => 'ASC',
					'orderby' => 'title'
				),
				array(
					'base_keys' => array(
						'post_title'
					),
					'meta_keys' => array()
				)
			);
			return array_keys( $services );
		};

		$ns->get_services_prices_map = function() use( $ns, $birchschedule ) {
			$services = $birchschedule->model->query(
				array( 'post_type' => 'birs_service' ),
				array(
					'meta_keys' => array( '_birs_service_price', '_birs_service_price_type' ),
					'base_keys' => array()
				)
			);
			$price_map = array();
			foreach ( $services as $service ) {
				$pre_payment_fee = $ns->get_service_pre_payment_fee( $service['ID'] );
				$formatted_pre_payment_fee = $ns->format_price( $pre_payment_fee );

				$price = ( double )$service['_birs_service_price'];
				$formatted_price = $ns->format_price( $price );
				$price_map[$service['ID']] = array(
					'price' => $price,
					'formatted_price' => $formatted_price,
					'price_type' => $service['_birs_service_price_type'],
					'pre_payment_fee' => $pre_payment_fee,
					'formatted_pre_payment_fee' => $formatted_pre_payment_fee
				);
			}
			return $price_map;
		};

		$ns->get_services_duration_map = function() {
			global $birchschedule;
			$services = $birchschedule->model->query(
				array( 'post_type' => 'birs_service' ),
				array(
					'meta_keys' => array(
						'_birs_service_length',
						'_birs_service_length_type'
					),
					'base_keys' => array()
				)
			);
			$duration_map = array();
			foreach ( $services as $service ) {
				$duration_map[$service['ID']] = array(
					'duration' => $birchschedule->model->get_service_length( $service['ID'] )
				);
			}
			return $duration_map;
		};

		$ns->format_price = function( $price ) use( $ns ) {
			$currency_code = $ns->get_currency_code();
			$formatted_number = $ns->number_format( $price );
			$formatted_price = $ns->apply_currency_symbol( $formatted_number, $currency_code );
			return $formatted_price;
		};

		$ns->apply_currency_symbol = function( $val, $curreny_code ) {
			global $birchpress;

			$currencies = $birchpress->util->get_currencies();
			$currency = $currencies[$curreny_code];
			$symbol = $currency['symbol_right'];
			if ( '' === $symbol ) {
				$symbol = $currency['symbol_left'];
			}
			if ( $currency['symbol_right'] ) {
				$val .= $symbol;
			} else {
				$val = $symbol . $val;
			}

			return $val;
		};

		$ns->get_currency_code = function() {
			return 'USD';
		};

		$ns->get_cut_off_time = function( $staff_id = -1, $location_id = -1, $service_id = -1 ) {
			return 1;
		};

		$ns->get_future_time = function() {
			return 360;
		};

		$ns->get_time_before_cancel = function() {
			return 24;
		};

		$ns->get_time_before_reschedule = function() {
			return 24;
		};

		$ns->get_staff_daysoff = function( $staff_id ) {
			global $birchschedule;

			$staff = $birchschedule->model->get( $staff_id, array(
					'base_keys' => array(),
					'meta_keys' => array( '_birs_staff_dayoffs' )
				) );
			$daysoff = json_encode( array() );
			if ( $staff['_birs_staff_dayoffs'] ) {
				$daysoff = $staff['_birs_staff_dayoffs'];
			}
			return $daysoff;
		};

		$ns->get_all_daysoff = function() {
			global $birchschedule;

			$staff = $birchschedule->model->query(
				array(
					'post_type' => 'birs_staff'
				),
				array(
					'meta_keys' => array(),
					'base_keys' => array()
				)
			);
			$dayoffs = array();
			foreach ( array_values( $staff ) as $thestaff ) {
				$dayoffs[$thestaff['ID']] =
				$birchschedule->model->get_staff_daysoff( $thestaff['ID'] );
			}
			return $dayoffs;
		};

		$ns->get_user_by_staff = function( $staff_id ) {
			global $birchschedule;

			$staff = $birchschedule->model->get( $staff_id,
				array(
					'base_keys' => array(),
					'meta_keys' => array( '_birs_staff_email' )
				)
			);
			if ( $staff ) {
				$user = WP_User::get_data_by( 'email', $staff['_birs_staff_email'] );
				return $user;
			} else {
				return false;
			}
		};

		$ns->get_staff_by_user = function( $user, $config = array() ) {
			global $birchschedule;

			$email = $user->user_email;
			$staff = $birchschedule->model->query(
				array(
					'post_type' => 'birs_staff',
					'meta_query' => array(
						array(
							'key' => '_birs_staff_email',
							'value' => $email
						)
					)
				),
				$config
			);
			if ( $staff ) {
				return array_values( $staff );
			} else {
				return false;
			}
		};

		$ns->merge_data = function( $model, $config, $data ) {
			if ( !is_array( $config ) ) {
				$config = array();
			}
			if ( !isset( $config['base_keys'] ) ) {
				$config['base_keys'] = array();
			}
			if ( !isset( $config['meta_keys'] ) ) {
				$config['meta_keys'] = array();
			}
			foreach ( $config['base_keys'] as $key ) {
				if ( isset( $data[$key] ) ) {
					$model[$key] = $data[$key];
				} else {
					$model[$key] = null;
				}
			}
			foreach ( $config['meta_keys'] as $key ) {
				$req_key = substr( $key, 1 );
				if ( isset( $data[$key] ) ) {
					$model[$key] = $data[$key];
				} else if ( isset( $data[$req_key] ) ) {
					$model[$key] = $data[$req_key];
				} else {
					$model[$key] = null;
				}
			}
			return $model;
		};

		$ns->number_format = function( $number, $currency_code = false ) use ( $ns ) {

			global $birchpress;

			if ( !$currency_code ) {
				$currency_code = $ns->get_currency_code();
			}
			$currencies = $birchpress->util->get_currencies();
			$currency = $currencies[$currency_code];
			return number_format( ( double )$number, $currency['decimal_places'], $currency['decimal_point'], $currency['thousands_point'] );
		};

	} );
