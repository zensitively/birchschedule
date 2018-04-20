<?php

birch_ns( 'birchschedule.model.booking', function( $ns ) {

		global $birchschedule;

		$ns->init = function() use ( $ns, $birchschedule ) {
			global $birchpress;

			add_action( 'birchschedule_model_schedule_get_staff_avaliable_time_after',
				array( $ns, 'record_fully_booked_day' ), 20, 5 );

			add_action( 'birchschedule_model_booking_change_appointment1on1_status_after',
				array( $ns, 'async_checking_if_fully_booked_for' ), 20, 1 );

			add_action( 'birchschedule_model_booking_reschedule_appointment1on1_after',
				array( $ns, 'async_checking_if_fully_booked_for' ), 20, 1 );

			add_action( 'birchschedule_model_booking_cancel_appointment1on1_after',
				array( $ns, 'async_checking_if_fully_booked_for' ), 20, 1 );

			$birchpress->util->enable_remote_call( $ns->check_if_fully_booked_for );
			$birchpress->util->enable_remote_call( $ns->recheck_fully_booked_days );
		};


		$ns->get_appointment_title = function( $appointment ) use ( $ns ) {
			global $birchschedule;

			$service = $birchschedule->model->get( $appointment['_birs_appointment_service'],
				array(
					'base_keys' => array( 'post_title' ),
					'meta_keys' => array()
				) );
			$appointment1on1s = $appointment['appointment1on1s'];
			if ( sizeof( $appointment1on1s ) > 1 ) {
				$title = $service['post_title'] . ' - ' . sprintf( __( '%s Clients', 'birchschedule' ),
					sizeof( $appointment1on1s ) );
			}
			else if ( sizeof( $appointment1on1s ) == 1 ) {
				$appointment1on1s = array_values( $appointment1on1s );
				$appointment1on1 = $appointment1on1s[0];
				$title = $service['post_title'] . ' - ' . $appointment1on1['_birs_client_name'];
			}
			else {
				$title = $service['post_title'];
			}
			return $title;

		};

		$ns->get_appointment = function( $appointment_id, $config = array() ) use ( $ns, $birchschedule ) {
			$config = array_merge(
				array(
					'status' => 'any'
				),
				$config
			);
			$appointments = $ns->query_appointments(
				array(
					'status' => $config['status'],
					'appointment_id' => $appointment_id
				),
				$config
			);
			if ( $appointments ) {
				return $appointments[$appointment_id];
			} else {
				return false;
			}
		};

		$ns->get_appointment1on1 = function( $appointment_id, $client_id, $config = array() ) use ( $ns ) {
			global $birchschedule;

			if ( empty( $config['appointment1on1_keys'] ) ) {
				$config['appointment1on1_keys'] =  array(
					'_birs_client_id', '_birs_appointment_id', 'post_status'
				);
			}

			$config = array_merge(
				array(
					'status' => 'any'
				),
				$config
			);
			$appointments = $ns->query_appointments(
				array(
					'client_id' => $client_id,
					'appointment_id' => $appointment_id,
					'status' => $config['status']
				),
				$config
			);
			if ( $appointments ) {
				$appointment1on1s = $appointments[$appointment_id]['appointment1on1s'];
				$appointment1on1s = array_values( $appointment1on1s );
				return $appointment1on1s[0];
			} else {
				return false;
			}
		};

		$ns->get_pending_appointment1on1_transient = function( $appointment1on1_id ) {
			$transient_name = 'birchschedule_pending_appointment1on1_' . $appointment1on1_id;
			return get_transient( $transient_name );
		};

		$ns->get_pending_time = function() {
			return 10 * 60;
		};

		$ns->set_pending_appointment1on1_transient = function( $appointment1on1_id ) use ( $ns ) {
			$transient_name = 'birchschedule_pending_appointment1on1_' . $appointment1on1_id;
			$length = $ns->get_pending_time();
			return set_transient( $transient_name, true, $length );
		};

		$ns->is_appointment1on1_blocking = function( $appointment1on1_id ) use ( $ns ) {
			global $birchschedule;

			$appointment1on1 = $birchschedule->model->get( $appointment1on1_id, array(
					'keys' => array( 'post_status' )
				) );
			if ( $appointment1on1 ) {
				$status = $appointment1on1['post_status'];
				if ( $status === 'publish' ) {
					return true;
				}
				if ( $status === 'pending' ) {
					$transient = $ns->get_pending_appointment1on1_transient( $appointment1on1_id );
					if ( $transient === false ) {
						return false;
					} else {
						return true;
					}
				} else {
					return false;
				}
				return false;
			} else {
				return false;
			}
		};

		$ns->query_appointments = function( $criteria, $config = array() ) use ( $ns ) {

			global $birchschedule;

			if ( !is_array( $criteria ) ) {
				$criteria = array();
			}

			$default = array(
				'appointment_id' => -1,
				'client_id' => -1,

				'start' => time(),
				'end' => time() + 24 * 60 * 60,
				'location_id' => -1,
				'staff_id' => -1,
				'service_id' => -1,
				'status' => 'publish',
				'blocking' => false,
				'cache_results' => false
			);

			$criteria = array_merge( $default, $criteria );

			$start = $criteria['start'];
			$end = $criteria['end'];
			$location_id = $criteria['location_id'];
			$staff_id = $criteria['staff_id'];
			$service_id = $criteria['service_id'];
			$status = $criteria['status'];
			$cache_results = $criteria['cache_results'];

			if ( !is_array( $config ) || !$config ) {
				$config = array();
			}
			if ( isset( $config['appointment_keys'] ) ) {
				$appointment_keys = $config['appointment_keys'];
			} else {
				$appointment_keys = array();
			}
			if ( isset( $config['appointment1on1_keys'] ) ) {
				$appointment1on1_keys = $config['appointment1on1_keys'];
			} else {
				$appointment1on1_keys = array();
			}
			if ( isset( $config['client_keys'] ) ) {
				$client_keys = $config['client_keys'];
			} else {
				$client_keys = array();
			}

			$appointments_criteria = array(
				'post_type' => 'birs_appointment',
				'post_status' => 'publish',
				'meta_query' => array(
					array(
						'key' => '_birs_appointment_timestamp',
						'value' => $start,
						'compare' => '>=',
						'type' => 'SIGNED'
					),
					array(
						'key' => '_birs_appointment_timestamp',
						'value' => $end,
						'compare' => '<=',
						'type' => 'SIGNED'
					)
				),
				'cache_results' => $cache_results
			);

			if ( $location_id != -1 ) {
				if ( !is_array( $location_id ) ) {
					$appointments_criteria['meta_query'][] = array(
						'key' => '_birs_appointment_location',
						'value' => $location_id,
						'type' => 'UNSIGNED'
					);
				} else {
					$appointments_criteria['meta_query'][] = array(
						'key' => '_birs_appointment_location',
						'value' => $location_id,
						'compare' => 'IN',
						'type' => 'UNSIGNED'
					);
				}
			}
			if ( $staff_id != -1 ) {
				if ( !is_array( $staff_id ) ) {
					$appointments_criteria['meta_query'][] = array(
						'key' => '_birs_appointment_staff',
						'value' => $staff_id,
						'type' => 'UNSIGNED'
					);
				} else {
					$appointments_criteria['meta_query'][] = array(
						'key' => '_birs_appointment_staff',
						'value' => $staff_id,
						'compare' => 'IN',
						'type' => 'UNSIGNED'
					);
				}
			}
			if ( $service_id != -1 ) {
				if ( !is_array( $service_id ) ) {
					$appointments_criteria['meta_query'][] = array(
						'key' => '_birs_appointment_service',
						'value' => $service_id,
						'type' => 'UNSIGNED'
					);
				} else {
					$appointments_criteria['meta_query'][] = array(
						'key' => '_birs_appointment_service',
						'value' => $service_id,
						'compare' => 'IN',
						'type' => 'UNSIGNED'
					);
				}
			}

			$appointment_id = $criteria['appointment_id'];
			if ( $appointment_id != -1 ) {
				unset( $appointments_criteria['meta_query'] );
				if ( !is_array( $appointment_id ) ) {
					$appointments_criteria['p'] = $appointment_id;
				} else {
					$appointments_criteria['post__in'] = $appointment_id;
				}
			}

			if ( $appointment_id == -1 || $appointment_keys ) {
				$appointments = $birchschedule->model->query( $appointments_criteria,
					array(
						'keys' => $appointment_keys
					)
				);
				$appointment_ids = array_keys( $appointments );
			} else {
				$appointment_ids = ( array )$appointment_id;
				$appointments = array();
				foreach ( $appointment_ids as $appointment_id ) {
					$appointments[$appointment_id] = array(
						'appointment1on1s' => array()
					);
				}
			}

			$appointment1on1_keys = array_merge( $appointment1on1_keys,
				array( '_birs_appointment_id', '_birs_client_id', 'post_status' ) );
			$appointment1on1s_critera = array(
				'post_type' => 'birs_appointment1on1',
				'post_status' => $status,
				'meta_query' => array(
					array(
						'key' => '_birs_appointment_id',
						'value' => array_merge( $appointment_ids, array( 0 ) ),
						'compare' => 'IN',
						'type' => 'UNSIGNED'
					)
				),
				'cache_results' => $cache_results
			);
			$client_id = $criteria['client_id'];
			if ( $client_id != -1 ) {
				if ( !is_array( $client_id ) ) {
					$appointment1on1s_critera['meta_query'][] = array(
						'key' => '_birs_client_id',
						'value' => $client_id,
						'type' => 'UNSIGNED'
					);
				} else {
					$appointment1on1s_critera['meta_query'][] = array(
						'key' => '_birs_client_id',
						'value' => array_merge( $client_id, array( 0 ) ),
						'compare' => 'IN',
						'type' => 'UNSIGNED'
					);
				}
			}
			$appointment1on1s = $birchschedule->model->query( $appointment1on1s_critera,
				array(
					'keys' => $appointment1on1_keys
				)
			);
			$new_appointments = array();
			foreach ( $appointment1on1s as $appointment1on1_id => $appointment1on1 ) {
				if ( $criteria['blocking'] && !$ns->is_appointment1on1_blocking( $appointment1on1_id ) ) {
					continue;
				}
				$client_id = $appointment1on1['_birs_client_id'];
				if ( $client_keys ) {
					$client = $birchschedule->model->get( $client_id, array(
							'keys' => $client_keys
						) );
					if ( !$client ) {
						$client = array();
					}
				} else {
					$client = array();
				}
				if ( !$client ) {
					$client = array();
				}
				$appointment1on1 = array_merge( $client, $appointment1on1 );
				if ( !isset( $client['_birs_client_name'] ) &&
					isset( $client['post_title'] ) ) {
					$appointment1on1['_birs_client_name'] = $client['post_title'];
				}

				$appointment_id = $appointment1on1['_birs_appointment_id'];
				if ( isset( $new_appointments[$appointment_id] ) ) {
					$appointment = $new_appointments[$appointment_id];
				} else {
					$appointment = $appointments[$appointment_id];
				}

				if ( !isset( $appointment['appointment1on1s'] ) ) {
					$appointment['appointment1on1s'] = array(
						$appointment1on1_id => $appointment1on1
					);
				} else {
					$appointment['appointment1on1s'][$appointment1on1_id] = $appointment1on1;
				}
				$new_appointments[$appointment_id] = $appointment;
			}

			return $new_appointments;
		};

		$ns->if_cancel_appointment_outoftime = function( $appointment_id ) use ( $ns ) {
			global $birchpress, $birchschedule;

			$time_before_cancel = $birchschedule->model->get_time_before_cancel();
			$appointment = $birchschedule->model->get( $appointment_id, array(
					'base_keys' => array(),
					'meta_keys' => array(
						'_birs_appointment_timestamp'
					)
				) );
			if ( !$appointment ) {
				return $birchpress->util->new_error( 'appointment_nonexist', __( 'The appointment does not exist.', 'birchschedule' ) );
			}
			if ( $appointment['_birs_appointment_timestamp'] - time() > $time_before_cancel * 60 * 60 ) {
				return true;
			} else {
				return false;
			}
		};

		$ns->if_reschedule_appointment_outoftime = function( $appointment_id ) use ( $ns ) {
			global $birchpress, $birchschedule;

			$time_before_reschedule = $birchschedule->model->get_time_before_reschedule();
			$appointment = $birchschedule->model->get( $appointment_id, array(
					'base_keys' => array(),
					'meta_keys' => array(
						'_birs_appointment_timestamp'
					)
				) );
			if ( !$appointment ) {
				return $birchpress->util->new_error( 'appointment_nonexist', __( 'The appointment does not exist.', 'birchschedule' ) );
			}
			if ( $appointment['_birs_appointment_timestamp'] - time() > $time_before_reschedule * 60 * 60 ) {
				return true;
			} else {
				return false;
			}
		};

		$ns->if_appointment_cancelled = function( $appointment_id ) use ( $ns ) {
			$appointment = $ns->get_appointment(
				$appointment_id,
				array(
					'status' => 'publish'
				)
			);
			if ( $appointment && $appointment['appointment1on1s'] ) {
				return false;
			} else {
				return true;
			}
		};

		$ns->if_appointment1on1_cancelled = function( $appointment1on1_id ) use ( $ns ) {
			global $birchpress, $birchschedule;

			$appointment1on1 = $birchschedule->model->get(
				$appointment1on1_id,
				array(
					'base_keys' => array( 'post_status' ),
					'meta_keys' => array(
						'_birs_appointment_id'
					)
				)
			);
			if ( !$appointment1on1 ) {
				return $birchpress->util->new_error( 'appointment_nonexist', __( 'The appointment does not exist.', 'birchschedule' ) );
			}
			if ( $appointment1on1['post_status'] == 'cancelled' ) {
				return true;
			} else {
				return false;
			}
		};

		$ns->if_email_duplicated = function( $client_id, $email ) use ( $ns ) {
			global $birchschedule;

			$clients = $birchschedule->model->query(
				array(
					'post_type' => 'birs_client',
					'meta_query' => array(
						array(
							'key' => '_birs_client_email',
							'value' => $email
						)
					)
				),
				array(
					'base_keys' => array(),
					'meta_keys' => array()
				)
			);
			if ( sizeof( $clients ) > 1 ) {
				return true;
			}
			elseif ( sizeof( $clients ) === 1 ) {
				$client_ids = array_keys( $clients );
				$exist_client_id = array_shift( $client_ids );
				return $client_id != $exist_client_id;
			} else {
				return false;
			}
		};

		$ns->save_client = function( $client_info ) use ( $ns ) {
			global $birchschedule;

			if ( isset( $client_info['_birs_client_fields'] ) ) {
				$fields = $client_info['_birs_client_fields'];
			} else {
				$fields = $birchschedule->model->get_client_fields();
			}
			$config = array(
				'meta_keys' => $fields,
				'base_keys' => array(
					'post_title'
				)
			);
			if ( isset( $client_info['_birs_client_email'] ) &&
				!isset( $client_info['ID'] ) ) {
				$email = $client_info['_birs_client_email'];
				$client = $birchschedule->model->get_client_by_email( $email,
					array(
						'base_keys' => array(),
						'meta_keys' => array()
					) );
				if ( $client ) {
					$client_info['ID'] = $client['ID'];
				}
			}
			$client_info['post_type'] = 'birs_client';
			$client_id = $birchschedule->model->save( $client_info, $config );
			return $client_id;
		};

		$ns->get_user_by_staff = function( $staff_id ) use ( $ns ) {
			global $birchschedule;

			$staff = $birchschedule->model->get( $staff_id,
				array(
					'base_keys' => array(),
					'meta_keys' => array( '_birs_staff_email' )
				)
			);
			if ( !$staff ) {
				return false;
			}
			$user = get_user_by( 'email', $staff['_birs_staff_email'] );
			return $user;
		};

		$ns->get_appointment_capacity = function( $appointment_id ) use ( $ns ) {
			global $birchschedule;

			$appointment = $birchschedule->model->get( $appointment_id, array(
					'meta_keys' => array( '_birs_appointment_capacity' )
				) );
			if ( isset( $appointment['_birs_appointment_capacity'] ) ) {
				$capacity = intval( $appointment['_birs_appointment_capacity'] );
				if ( $capacity < 1 ) {
					$capacity = 1;
				}
			} else {
				$capacity = 1;
			}
			return $capacity;
		};

		$ns->make_appointment = function( $appointment_info ) use ( $ns ) {
			birch_assert( isset( $appointment_info['_birs_appointment_location'] ) );
			birch_assert( isset( $appointment_info['_birs_appointment_service'] ) );
			birch_assert( isset( $appointment_info['_birs_appointment_staff'] ) );
			birch_assert( isset( $appointment_info['_birs_appointment_timestamp'] ) );

			global $birchschedule;

			$appointments = $birchschedule->model->query(
				array(
					'post_type' => 'birs_appointment',
					'post_status' => array( 'publish' ),
					'meta_query' => array(
						array(
							'key' => '_birs_appointment_location',
							'value' => $appointment_info['_birs_appointment_location']
						),
						array(
							'key' => '_birs_appointment_service',
							'value' => $appointment_info['_birs_appointment_service']
						),
						array(
							'key' => '_birs_appointment_staff',
							'value' => $appointment_info['_birs_appointment_staff']
						),
						array(
							'key' => '_birs_appointment_timestamp',
							'value' => $appointment_info['_birs_appointment_timestamp']
						),
					)
				),
				array(
					'base_keys' => array(),
					'meta_keys' => array()
				)
			);
			if ( $appointments ) {
				$appointment_ids = array_keys( $appointments );
				return $appointment_ids[0];
			} else {
				$appointment_info['_birs_appointment_uid'] = uniqid( rand() );
				if ( !isset( $appointment_info['_birs_appointment_capacity'] ) ) {
					$appointment_info['_birs_appointment_capacity'] =
					$birchschedule->model->get_service_capacity(
						$appointment_info['_birs_appointment_service']
					);
				}
				if ( !isset( $appointment_info['_birs_appointment_duration'] ) ) {
					$appointment_info['_birs_appointment_duration'] =
					$birchschedule->model->get_service_length(
						$appointment_info['_birs_appointment_service']
					);
				}
				if ( !isset( $appointment_info['_birs_appointment_padding_before'] ) ) {
					$appointment_info['_birs_appointment_padding_before'] =
					$birchschedule->model->get_service_padding_before(
						$appointment_info['_birs_appointment_service']
					);
				}
				if ( !isset( $appointment_info['_birs_appointment_padding_after'] ) ) {
					$appointment_info['_birs_appointment_padding_after'] =
					$birchschedule->model->get_service_padding_after(
						$appointment_info['_birs_appointment_service']
					);
				}
			}
			$base_keys = array();
			$user = $ns->get_user_by_staff( $appointment_info['_birs_appointment_staff'] );
			if ( $user ) {
				$appointment_info['post_author'] = $user->ID;
				$base_keys[] = 'post_author';
			}
			$config = array(
				'base_keys' => $base_keys,
				'meta_keys' => $birchschedule->model->get_appointment_fields()
			);
			$appointment_info['post_type'] = 'birs_appointment';
			$appointment_id = $birchschedule->model->save( $appointment_info, $config );
			$ns->remove_auto_draft_appointments();
			return $appointment_id;
		};

		$ns->remove_empty_appointment = function( $appointment_id ) use ( $ns ) {
			global $birchschedule;

			$appointment = $ns->get_appointment( $appointment_id );
			if ( $appointment && !$appointment['appointment1on1s'] ) {
				$birchschedule->model->delete( $appointment_id );
				$appointment_keys = $birchschedule->model->get_appointment_fields();
				foreach ( $appointment_keys as $key ) {
					delete_post_meta( $appointment_id, $key );
				}
			}
		};

		$ns->remove_auto_draft_appointments = function() use ( $ns ) {
			global $birchpress;

			$appointments = $birchpress->db->query(
				array(
					'post_type' => 'birs_appointment',
					'post_status' => 'auto-draft'
				),
				array(
					'base_keys' => array(),
					'meta_keys' => array()
				)
			);
			foreach ( $appointments as $appointment ) {
				$birchpress->db->delete( $appointment['ID'] );
			}
		};

		$ns->make_appointment1on1 = function( $appointment1on1_info, $status = 'draft' ) use ( $ns ) {
			birch_assert( isset( $appointment1on1_info['_birs_client_id'] ), 'no client id' );

			global $birchschedule;

			if ( isset( $appointment1on1_info['_birs_appointment_id'] ) ) {
				$appointment_id = $appointment1on1_info['_birs_appointment_id'];
				$appointment = $birchschedule->model->get( $appointment_id, array(
						'meta_keys' => array( '_birs_appointment_service' )
					) );
				$appointment1on1_info['_birs_appointment_service'] = $appointment['_birs_appointment_service'];
			} else {
				birch_assert( isset( $appointment1on1_info['_birs_appointment_location'] ), 'no location' );
				birch_assert( isset( $appointment1on1_info['_birs_appointment_service'] ), 'no service' );
				birch_assert( isset( $appointment1on1_info['_birs_appointment_staff'] ), 'no staff' );
				birch_assert( isset( $appointment1on1_info['_birs_appointment_timestamp'] ), 'no timestamp' );
				$appointment_id = $ns->make_appointment( $appointment1on1_info );
				$appointment1on1_info['_birs_appointment_id'] = $appointment_id;
			}
			$client_id = $appointment1on1_info['_birs_client_id'];
			$appointment1on1 = $ns->get_appointment1on1( $appointment_id, $client_id );
			if ( $appointment1on1 ) {
				return $appointment1on1['ID'];
			}
			$appointment1on1_info['_birs_appointment1on1_uid'] = uniqid( rand() );
			if ( !isset( $appointment1on1_info['_birs_appointment1on1_price'] ) ) {
				$appointment1on1_info['_birs_appointment1on1_price'] =
				$birchschedule->model->get_service_price(
					$appointment1on1_info['_birs_appointment_service']
				);
			}
			$appointment1on1_info['_birs_appointment1on1_payment_status'] = 'not-paid';
			$appointment1on1_info['post_status'] = $status;
			if ( isset( $appointment1on1_info['_birs_appointment_fields'] ) ) {
				$custom_fields = $appointment1on1_info['_birs_appointment_fields'];
			} else {
				$custom_fields = $birchschedule->model->get_appointment1on1_custom_fields();
			}
			$std_fields = $birchschedule->model->get_appointment1on1_fields();
			$all_fields = array_merge( $std_fields, $custom_fields );
			$appointment1on1_info['post_type'] = 'birs_appointment1on1';
			$base_keys = array(
				'post_status'
			);
			$appointment1on1_id = $birchschedule->model->save( $appointment1on1_info, array(
					'base_keys' => $base_keys,
					'meta_keys' => $all_fields
				) );
			return $appointment1on1_id;
		};

		$ns->change_appointment1on1_status = function( $appointment1on1_id, $status ) use ( $ns ) {
			global $birchpress, $birchschedule;

			$appointment1on1_info = array(
				'ID' => $appointment1on1_id,
				'post_status' => $status,
				'post_type' => 'birs_appointment1on1'
			);
			$config = array(
				'base_keys' => array(
					'post_status'
				),
				'meta_keys' => array()
			);
			$appointment1on1 = $birchschedule->model->get( $appointment1on1_id, $config );
			if ( !$appointment1on1 ) {
				return $birchpress->util->new_error( 'appointment1on1_nonexist', __( 'The appointment does not exist.', 'birchschedule' ) );
			}
			$old_status = $appointment1on1['post_status'];
			$birchschedule->model->save( $appointment1on1_info, $config );
			if ( $status === 'pending' ) {
				$ns->set_pending_appointment1on1_transient( $appointment1on1_id );
			}
			return $old_status;
		};

		$ns->change_appointment1on1_custom_info = function( $appointment1on1_info ) use ( $ns ) {
			global $birchpress, $birchschedule;

			$assertion = isset( $appointment1on1_info['ID'] ) ||
			( isset( $appointment1on1_info['_birs_appointment_id'] ) &&
				isset( $appointment1on1_info['_birs_client_id'] ) );
			birch_assert( $assertion, 'ID or (_birs_appointment_id and _birs_client_id) should be in the info.' );
			if ( isset( $appointment1on1_info['_birs_appointment_fields'] ) ) {
				$custom_fields = $appointment1on1_info['_birs_appointment_fields'];
			} else {
				$custom_fields = $birchschedule->model->get_appointment1on1_custom_fields();
			}
			if ( !isset( $appointment1on1_info['ID'] ) ) {
				$appointment1on1 = $ns->get_appointment1on1(
					$appointment1on1_info['_birs_appointment_id'],
					$appointment1on1_info['_birs_client_id']
				);
				if ( !$appointment1on1 ) {
					return $birchpress->util->new_error( 'appointment1on1_nonexist', __( 'The appointment does not exist.', 'birchschedule' ) );
				} else {
					$appointment1on1_info['ID'] = $appointment1on1['ID'];
				}
			}
			$appointment1on1_info['post_type'] = 'birs_appointment1on1';
			$appointment1on1_id = $birchschedule->model->save( $appointment1on1_info, array(
					'base_keys' => array(),
					'meta_keys' => $custom_fields
				) );
			return $appointment1on1_id;
		};

		$ns->reschedule_appointment = function( $appointment_id, $appointment_info ) use ( $ns ) {
			birch_assert( isset( $appointment_info['_birs_appointment_staff'] ) ||
				isset( $appointment_info['_birs_appointment_timestamp'] ) );

			global $birchpress, $birchschedule;

			$appointment = $ns->get_appointment( $appointment_id,
				array(
					'status' => 'publish'
				)
			);
			if ( $appointment && $appointment['appointment1on1s'] ) {
				foreach ( $appointment['appointment1on1s'] as $appointment1on1_id => $appointment1on1 ) {
					$ns->reschedule_appointment1on1( $appointment1on1_id, $appointment_info );
				}
			}
		};

		$ns->reschedule_appointment1on1 = function( $appointment1on1_id, $appointment_info ) use ( $ns ) {
			global $birchpress, $birchschedule;

			birch_assert( isset( $appointment_info['_birs_appointment_staff'] ) ||
				isset( $appointment_info['_birs_appointment_timestamp'] ) );
			$appointment1on1 = $birchschedule->model->get( $appointment1on1_id, array(
					'base_keys' => array(),
					'meta_keys' => array(
						'_birs_appointment_id',
						'_birs_client_id'
					)
				) );
			if ( !$appointment1on1 ) {
				return $birchpress->util->new_error( 'appointment1on1_nonexist', __( 'The appointment does not exist.', 'birchschedule' ) );
			}
			$appointment = $birchschedule->model->get( $appointment1on1['_birs_appointment_id'],
				array(
					'base_keys' => array(),
					'meta_keys' => array(
						'_birs_appointment_location',
						'_birs_appointment_service',
						'_birs_appointment_staff',
						'_birs_appointment_timestamp',
						'_birs_appointment_duration',
						'_birs_appointment_padding_before',
						'_birs_appointment_padding_after',
						'_birs_appointment_uid'
					)
				)
			);
			if ( !$appointment ) {
				return $birchpress->util->new_error( 'appointment_nonexist', __( 'The appointment does not exist.', 'birchschedule' ) );
			}
			if ( !isset( $appointment_info['_birs_appointment_staff'] ) ) {
				$appointment_info['_birs_appointment_staff'] = $appointment['_birs_appointment_staff'];
			}
			if ( !isset( $appointment_info['_birs_appointment_timestamp'] ) ) {
				$appointment_info['_birs_appointment_timestamp'] = $appointment['_birs_appointment_timestamp'];
			}
			if ( !isset( $appointment_info['_birs_appointment_location'] ) ) {
				$appointment_info['_birs_appointment_location'] = $appointment['_birs_appointment_location'];
			}
			if ( !isset( $appointment_info['_birs_appointment_service'] ) ) {
				$appointment_info['_birs_appointment_service'] = $appointment['_birs_appointment_service'];
			}
			if ( $appointment['_birs_appointment_staff'] === $appointment_info['_birs_appointment_staff'] &&
				$appointment['_birs_appointment_timestamp'] === $appointment_info['_birs_appointment_timestamp'] &&
				$appointment['_birs_appointment_location'] === $appointment_info['_birs_appointment_location'] &&
				$appointment['_birs_appointment_service'] === $appointment_info['_birs_appointment_service'] ) {
				return false;
			}

			$old_appointment1on1 = array_merge( $appointment, $appointment1on1 );
			$appointment['_birs_appointment_staff'] = $appointment_info['_birs_appointment_staff'];
			$appointment['_birs_appointment_timestamp'] = $appointment_info['_birs_appointment_timestamp'];
			$appointment['_birs_appointment_location'] = $appointment_info['_birs_appointment_location'];
			$appointment['_birs_appointment_service'] = $appointment_info['_birs_appointment_service'];
			unset( $appointment['ID'] );
			$appointment_id = $ns->make_appointment( $appointment );
			$orig_appointment_id = $appointment1on1['_birs_appointment_id'];
			$appointment1on1['_birs_appointment_id'] = $appointment_id;
			$birchschedule->model->save( $appointment1on1, array(
					'base_keys' => array(),
					'meta_keys' => array(
						'_birs_appointment_id'
					)
				) );
			$payments = $birchschedule->model->query(
				array(
					'post_type' => 'birs_payment',
					'meta_query' => array(
						array(
							'key' => '_birs_payment_appointment',
							'value' => $orig_appointment_id
						),
						array(
							'key' => '_birs_payment_client',
							'value' => $appointment1on1['_birs_client_id']
						)
					)
				),
				array(
					'meta_keys' => array(),
					'base_keys' => array()
				)
			);
			foreach ( $payments as $payment ) {
				$payment['_birs_payment_appointment'] = $appointment_id;
				$birchschedule->model->save( $payment, array(
						'base_keys' => array(),
						'meta_keys' => array(
							'_birs_payment_appointment'
						)
					) );
			}
			$ns->remove_empty_appointment( $orig_appointment_id );
			return $old_appointment1on1;
		};

		$ns->cancel_appointment = function( $appointment_id ) use ( $ns ) {
			$appointment = $ns->get_appointment( $appointment_id,
				array(
					'status' => 'publish'
				)
			);
			if ( $appointment && $appointment['appointment1on1s'] ) {
				foreach ( $appointment['appointment1on1s'] as $appointment1on1_id => $appointment1on1 ) {
					$ns->cancel_appointment1on1( $appointment1on1_id );
				}
			}
		};

		$ns->cancel_appointment1on1 = function( $appointment1on1_id ) use ( $ns ) {

			global $birchschedule;

			$appointment1on1 = $birchschedule->model->mergefields->get_appointment1on1_merge_values(
				$appointment1on1_id
			);
			if ( !$appointment1on1 ) {
				return false;
			} else {
				$appointment_id = $appointment1on1['_birs_appointment_id'];
				if ( $appointment1on1['post_status'] == 'cancelled' ) {
					return false;
				}
				$new_appointment1on1 = array(
					'post_status' => 'cancelled',
					'post_type' => 'birs_appointment1on1',
					'ID' => $appointment1on1_id
				);
				$birchschedule->model->save( $new_appointment1on1, array(
						'base_keys' => array( 'post_status' ),
						'meta_keys' => array()
					) );
				return $appointment1on1;
			}
		};

		$ns->get_payments_by_appointment1on1 = function( $appointment_id, $client_id ) use ( $ns ) {
			global $birchschedule;

			$payments = $birchschedule->model->query(
				array(
					'post_type' => 'birs_payment',
					'meta_query' => array(
						array(
							'key' => '_birs_payment_appointment',
							'value' => $appointment_id
						),
						array(
							'key' => '_birs_payment_client',
							'value' => $client_id
						)
					)
				),
				array(
					'meta_keys' => $birchschedule->model->get_payment_fields(),
					'base_keys' => array()
				)
			);
			return $payments;
		};

		$ns->make_payment = function( $payment_info ) use ( $ns ) {
			global $birchpress, $birchschedule;

			$appointment_id = $payment_info['_birs_payment_appointment'];
			$client_id = $payment_info['_birs_payment_client'];
			$appointment1on1 = $ns->get_appointment1on1( $appointment_id, $client_id );
			if ( !$appointment1on1 ) {
				return $birchpress->util->new_error( 'appointment1on1_nonexist', __( 'The appointment does not exist.', 'birchschedule' ) );
			}
			$config = array(
				'meta_keys' => $birchschedule->model->get_payment_fields(),
				'base_keys' => array()
			);
			$payment_info['post_type'] = 'birs_payment';
			$payment_id = $birchschedule->model->save( $payment_info, $config );
			$appointment_price = $appointment1on1['_birs_appointment1on1_price'];
			$all_payments = $ns->get_payments_by_appointment1on1( $appointment_id, $client_id );
			$paid = 0;
			foreach ( $all_payments as $the_payment_id => $payment ) {
				$paid += $payment['_birs_payment_amount'];
			}
			$payment_status = 'not-paid';
			if ( $paid > 0 && $appointment_price - $paid >= 0.01 ) {
				$payment_status = 'partially-paid';
			}
			if ( $paid > 0 && $appointment_price - $paid < 0.01 ) {
				$payment_status = 'paid';
			}
			$appointment1on1['_birs_appointment1on1_payment_status'] = $payment_status;
			$birchschedule->model->save( $appointment1on1, array(
					'base_keys' => array(),
					'meta_keys' => array( '_birs_appointment1on1_payment_status' )
				) );
			return $payment_id;
		};

		$ns->get_payment_types = function() {
			return array(
				'credit_card' => __( 'Credit Card', 'birchschedule' ),
				'cash' => __( 'Cash', 'birchschedule' )
			);
		};

		$ns->delete_payment = function( $payment_id ) use ( $ns ) {
			global $birchschedule;

			$fields = $birchschedule->model->get_payment_fields();
			foreach ( $fields as $field ) {
				delete_post_meta( $payment_id, $field );
			}
			wp_delete_post( $payment_id, true );
		};

		$ns->delete_appointment1on1 = function( $appointment1on1_id ) use ( $ns ) {
			global $birchschedule;

			$appointment1on1 = $birchschedule->model->get( $appointment1on1_id, array(
					'keys' => array( '_birs_appointment_id', '_birs_client_id' )
				) );
			if ( $appointment1on1 ) {
				$appointment_id = $appointment1on1['_birs_appointment_id'];
				$client_id = $appointment1on1['_birs_client_id'];
				$payments = $birchschedule->model->query(
					array(
						'post_type' => 'birs_payment',
						'meta_query' => array(
							array(
								'key' => '_birs_payment_appointment',
								'value' => $appointment_id
							),
							array(
								'key' => '_birs_payment_client',
								'value' => $client_id
							)
						)
					),
					array(
						'keys' => array()
					)
				);
				foreach ( $payments as $payment_id => $payment ) {
					$ns->delete_payment( $payment_id );
				}
			}
			$fields = $birchschedule->model->get_appointment1on1_fields();
			$custom_fields = $birchschedule->model->get_appointment1on1_custom_fields();
			$fields = array_merge( $fields, $custom_fields );
			foreach ( $fields as $field ) {
				delete_post_meta( $appointment1on1_id, $field );
			}
			wp_delete_post( $appointment1on1_id, true );
		};

		$ns->delete_appointment = function( $appointment_id ) use ( $ns ) {
			global $birchschedule;

			$appointment = $ns->get_appointment( $appointment_id, array( 'status' => 'any' ) );
			if ( $appointment ) {
				$appointment1on1s = $appointment['appointment1on1s'];
				foreach ( $appointment1on1s as $appointment1on1_id => $appointment1on1 ) {
					$ns->delete_appointment1on1( $appointment1on1_id );
				}
				$fields = $birchschedule->model->get_appointment_fields();
				foreach ( $fields as $field ) {
					delete_post_meta( $appointment_id, $field );
				}
				wp_delete_post( $appointment_id, true );
			}
		};

		$ns->record_fully_booked_day = function( $staff_id, $location_id, $service_id, $date, $time_options ) use ( $ns ) {
			global $birchschedule;

			$empty = true;
			foreach ( $time_options as $key => $value ) {
				if ( $value['avaliable'] ) {
					$empty = false;
					break;
				}
			}
			$date_text = $date->format( 'Y-m-d' );
			if ( $empty ) {
				$birchschedule->model->schedule->mark_fully_booked_day(
					$staff_id, $location_id, $service_id, $date_text );
			} else {
				$birchschedule->model->schedule->unmark_fully_booked_day(
					$staff_id, $location_id, $service_id, $date_text );
			}
		};

		$ns->async_checking_if_fully_booked_for = function( $appointment1on1_id ) use ( $ns ) {
			global $birchpress;

			$args = array( $appointment1on1_id );
			$birchpress->util->async_run_task( array(
					'action' => 'birchschedule.model.booking.check_if_fully_booked_for',
					'args' => $args
				) );
		};

		$ns->check_if_fully_booked_for = function( $appointment1on1_id ) use( $ns, $birchschedule ) {
			global $birchpress;

			$appointment1on1 = $birchschedule->model->mergefields->get_appointment1on1_merge_values( $appointment1on1_id );
			$staff_id = $appointment1on1['_birs_appointment_staff'];
			$timestamp = $appointment1on1['_birs_appointment_timestamp'];
			$date = $birchpress->util->get_wp_datetime( $timestamp );
			$date_text = $date->format( 'Y-m-d' );
			$ns->check_if_fully_booked( $staff_id, $date_text );
		};

		$ns->check_if_fully_booked = function( $staff_id, $date_text ) use ( $ns ) {
			global $birchpress, $birchschedule;

			$services = $birchschedule->model->get_services_by_staff( $staff_id );
			$locations = $birchschedule->model->get_locations_listing_order();
			foreach ( $services as $service_id => $value ) {
				foreach ( $locations as $location_id ) {
					$date = $birchpress->util->get_wp_datetime( "$date_text 00:00:00" );

					$time_options = $birchschedule->model->schedule->get_staff_avaliable_time(
						$staff_id, $location_id, $service_id, $date
					);
				}
			}
		};

		$ns->async_recheck_fully_booked_days = function() use ( $ns ) {
			global $birchpress;

			$args = array();
			$birchpress->util->async_run_task( array(
					'action' => 'birchschedule.model.booking.recheck_fully_booked_days',
					'args' => $args
				) );
		};

		$ns->recheck_fully_booked_days = function() use ( $ns ) {
			global $birchschedule;

			$fully_booked = $birchschedule->model->schedule->get_fully_booked_days();
			foreach ( $fully_booked as $date_text => $staff_arr ) {
				foreach ( $staff_arr as $staff_id => $arr ) {
					$ns->check_if_fully_booked( $staff_id, $date_text );
				}
			}
		};
	} );
