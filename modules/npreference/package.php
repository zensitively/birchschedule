<?php

birch_ns( 'birchschedule.npreference', function( $ns ) {

		global $birchschedule;

		$ns->init = function() use ( $ns ) {
				add_action( 'init', array( $ns, 'wp_init' ) );
			};

		$ns->wp_init = function() use ( $ns ) {
				if ( $ns->is_no_preference_enabled() ) {

					add_filter( 'birchschedule_view_bookingform_get_staff_listing_order',
						array( $ns, 'add_staff_no_preference' ) );

					add_filter( "birchschedule_wintegration_get_staff_options",
						array( $ns, 'add_wc_staff_options_no_preference' ) );

					add_filter( 'birchschedule_model_get_locations_staff_map',
						array( $ns, 'add_staff_np_locations_map' ) );

					add_filter( 'birchschedule_model_get_services_staff_map',
						array( $ns, 'add_staff_np_services_map' ) );

					add_action( 'birchschedule_view_bookingform_validate_appointment_info_before',
						array( $ns, 'select_staff_from_alternatives' ) );

					add_filter( 'birchschedule_model_schedule_get_staff_avaliable_time',
						array( $ns, 'get_staff_avaliable_time' ), 60, 5 );
				}

				add_filter( 'birchschedule_model_get_staff_listing_order',
					array( $ns, 'random_staff_listing_order' ), 20 );

				add_filter( 'birchschedule_model_get_staff_listing_order',
					array( $ns, 'change_staff_order_in_turn' ), 20 );

				add_action( 'birchschedule_model_booking_change_appointment1on1_status_after',
					array( $ns, 'record_last_appointment1on1' ), 20, 3 );
			};

		$ns->is_no_preference_enabled = function() {
				return true;
			};

		$ns->add_staff_no_preference = function( $staff_order ) {
				array_unshift( $staff_order, -1 );
				return $staff_order;
			};

		$ns->add_wc_staff_options_no_preference = function( $staff_options ) use ( $ns, $birchschedule ) {
				$i18n_messages = $birchschedule->view->get_frontend_i18n_messages();
				array_unshift( $staff_options, array(
						'id' => -1,
						'text' => $i18n_messages['No Preference']
					) );
				return $staff_options;
			};

		$ns->add_staff_np_locations_map = function( $map ) use ( $ns, $birchschedule ) {
				$i18n_messages = $birchschedule->view->get_frontend_i18n_messages();
				$new_map = array();
				foreach ( $map as $location_id => $staffs ) {
					$staffs[-1] = $i18n_messages['No Preference'];
					$new_map[$location_id] = $staffs;
				}
				return $new_map;
			};

		$ns->add_staff_np_services_map = function( $map ) use( $ns, $birchschedule ) {
				$i18n_messages = $birchschedule->view->get_frontend_i18n_messages();
				$new_map = array();
				foreach ( $map as $service_id => $staffs ) {
					$staffs[-1] = $i18n_messages['No Preference'];
					$new_map[$service_id] = $staffs;
				}
				return $new_map;
			};

		$ns->get_staff_listing_order = function() use ( $birchschedule ) {
				return $birchschedule->model->get_staff_listing_order();
			};

		$ns->get_staff_avaliable_time = function( $time_options, $staff_id, $location_id, $service_id, $date ) use( $ns, $birchschedule ) {

				if ( $staff_id != -1 ) {
					return $time_options;
				}
				$staff_by_service = $birchschedule->model->get_staff_by_service( $service_id );
				$staff_by_service = array_keys( $staff_by_service );
				$staff_by_location = $birchschedule->model->get_staff_by_location( $location_id );
				$staff_by_location = array_keys( $staff_by_location );
				$staff_order = $birchschedule->model->get_staff_listing_order();
				if ( isset( $_POST['birs_appointment_avaliable_staff'] ) ) {
					$avaliable_staff = explode( ',', $_POST['birs_appointment_avaliable_staff'] );
				} else {
					$avaliable_staff = $staff_order;
				}
				$time_options = array();
				foreach ( $staff_order as $thestaff_id ) {
					if ( in_array( $thestaff_id, $staff_by_service ) &&
						in_array( $thestaff_id, $staff_by_location ) &&
						in_array( $thestaff_id, $avaliable_staff ) ) {

						$staff_time_options = $birchschedule->model->schedule->get_staff_avaliable_time(
							$thestaff_id, $location_id,
							$service_id, $date
						);
						foreach ( $staff_time_options as $time => $staff_time_option ) {
							if ( isset( $time_options[$time] ) ) {
								$time_options[$time]['avaliable'] =
								$time_options[$time]['avaliable'] || $staff_time_option['avaliable'];
								if ( $staff_time_option['avaliable'] ) {
									$time_options[$time]['capacity'] += $staff_time_option['capacity'];
									$time_options[$time]['alternative_staff'][] = $thestaff_id;
								}
							} else {
								$time_options[$time] = $staff_time_option;
								$time_options[$time]['alternative_staff'] = array( $thestaff_id );
							}
						}
					}
				}
				ksort( $time_options );
				return $time_options;
			};

		$ns->select_staff_from_alternatives = function() use( $ns ) {
				if ( !isset( $_POST['birs_appointment_time'] ) || !$_POST['birs_appointment_time'] ) {
					return;
				} else {
					if ( isset( $_POST['birs_appointment_staff'] ) && $_POST['birs_appointment_staff'] == -1 ) {
						if ( isset( $_POST['birs_appointment_alternative_staff'] ) ) {
							$alternatives = explode( ',', $_POST['birs_appointment_alternative_staff'] );
							if ( $alternatives && $alternatives[0] ) {
								$_POST['birs_appointment_staff'] = $alternatives[0];
							} else {
								unset( $_POST['birs_appointment_staff'] );
							}
						} else {
							unset( $_POST['birs_appointment_staff'] );
						}
					}
				}
			};

		$ns->random_staff_listing_order = function( $order ) use( $ns, $birchschedule ) {
				$order_type = $birchschedule->model->get_staff_listing_order_type();
				if ( $order_type != 'random' ) {
					return $order;
				}
				$len = sizeof( $order );
				if ( $len <= 1 ) {
					return $order;
				}
				$rstart = rand( 0, $len - 1 );
				if ( $rstart == 0 ) {
					return $order;
				}
				$new_order = array_merge( array_slice( $order, $rstart ), array_slice( $order, 0, $rstart ) );

				return $new_order;
			};

		$ns->get_last_appointment1on1 = function() {
				$appointment1on1_id = get_option( 'birs_last_appointment1on1' );
				return $appointment1on1_id;
			};

		$ns->change_staff_order_in_turn = function( $order ) use( $ns, $birchschedule ) {
				$order_type = $birchschedule->model->get_staff_listing_order_type();
				if ( $order_type != 'in_turn' ) {
					return $order;
				}
				$appointment1on1_id = $ns->get_last_appointment1on1();
				if ( $appointment1on1_id ) {
					$appointment1on1 =
					$birchschedule->model->mergefields->get_appointment1on1_merge_values( $appointment1on1_id );
					if ( $appointment1on1 ) {
						$last_staff_id = $appointment1on1['_birs_appointment_staff'];
						$new_order = array();
						foreach ( $order as $staff_id ) {
							if ( $last_staff_id != $staff_id ) {
								$new_order[] = $staff_id;
							}
						}
						$new_order[] = $last_staff_id;
						return $new_order;
					} else {
						return $order;
					}
				} else {
					return $order;
				}
			};

		$ns->record_last_appointment1on1 = function( $appointment1on1_id, $new_status, $old_status ) {
				global $birchpress;
				if ( !$birchpress->util->is_error( $old_status ) ) {
					update_option( 'birs_last_appointment1on1', $appointment1on1_id );
				}
			};

	} );
