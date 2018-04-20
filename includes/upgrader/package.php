<?php

birch_ns( 'birchschedule.upgrader', function( $ns ) {

		$ns->init = function() use ( $ns ) {
			add_action( 'birchschedule_upgrade_core_after', array( $ns, 'upgrade_core' ) );
		};

		$ns->get_staff_all_schedule_1_0 = function( $staff ) {
			$schedule = $staff['_birs_staff_schedule'];
			if ( !isset( $schedule ) ) {
				$schedule = array();
			} else {
				$schedule = unserialize( $schedule );
			}
			$schedule = $schedule ? $schedule : array();
			return $schedule;
		};

		$ns->upgrade_staff_schedule_from_1_0_to_1_1 = function() use ( $ns ) {
			global $birchpress;

			$version = $ns->get_staff_schedule_version();
			if ( $version != '1.0' ) {
				return;
			}
			$staff = $birchpress->db->query(
				array(
					'post_type' => 'birs_staff'
				),
				array(
					'meta_keys' => array(
						'_birs_staff_schedule'
					),
					'base_keys' => array()
				)
			);
			foreach ( $staff as $thestaff ) {
				$schedules = $ns->get_staff_all_schedule_1_0( $thestaff );
				$new_all_schedules = array();
				foreach ( $schedules as $location_id => $schedule ) {
					if ( isset( $schedule['exceptions'] ) ) {
						$exceptions = $schedule['exceptions'];
					} else {
						$exceptions = array();
					}
					$new_schedules = array();
					foreach ( $schedule as $week_day => $day_schedule ) {
						if ( isset( $day_schedule['enabled'] ) ) {
							$start = $day_schedule['minutes_start'];
							$end = $day_schedule['minutes_end'];
							$new_schedule = array(
								'minutes_start' => $day_schedule['minutes_start'],
								'minutes_end' => $day_schedule['minutes_end'],
								'weeks' => array(
									$week_day => 'on'
								)
							);
							if ( isset( $new_schedules['s'. $start. $end] ) ) {
								$new_schedules['s'. $start. $end]['weeks'][$week_day] = 'on';
							} else {
								$new_schedules['s'. $start. $end] = $new_schedule;
							}
						}
					}
					$new_loc_schedules = array();
					foreach ( $new_schedules as $tmp_id => $new_schedule ) {
						$uid = uniqid();
						$new_loc_schedules[$uid] = $new_schedule;
					}
					$new_all_schedules[$location_id] = array(
						'schedules' => $new_loc_schedules,
						'exceptions' => $exceptions
					);
					update_post_meta( $thestaff['ID'], '_birs_staff_schedule', serialize( $new_all_schedules ) );
				}
			}
			update_option( 'birs_staff_schedule_version', '1.1' );
		};

		$ns->get_staff_schedule_version = function() {
			return get_option( 'birs_staff_schedule_version', '1.0' );
		};

		$ns->upgrade_appointment_from_1_0_to_1_1 = function() use ( $ns ) {
			global $birchpress, $birchschedule;

			$version = $ns->get_db_version_appointment();
			if ( $version != '1.0' ) {
				return;
			}
			$appointment_fields = array(
				'_birs_appointment_price', '_birs_appointment_client',
				'_birs_appointment_notes', '_birs_appointment_payment_status',
				'_birs_appointment_reminded'
			);
			$appointment1on1_fields = array(
				'_birs_appointment_id',
				'_birs_client_id',
				'_birs_appointment1on1_payment_status',
				'_birs_appointment1on1_reminded',
				'_birs_appointment1on1_price',
				'_birs_appointment1on1_uid'
			);

			$appointment1on1_custom_fields = array(
				'_birs_appointment_notes'
			);

			$options = get_option( 'birchschedule_options_form' );
			if ( $options != false ) {
				$fields_options = $options['fields'];
				foreach ( $fields_options as $field_id => $field ) {
					if ( $field['belong_to'] == 'appointment' ) {
						$meta_key = '_birs_' . $field_id;
						if ( !in_array( $meta_key, array( '_birs_appointment_notes' ) ) ) {
							$appointment_fields[] = $meta_key;
							$appointment1on1_custom_fields[] = $meta_key;
						}
					}
				}
			}
			$appointments = $birchschedule->model->query(
				array(
					'post_type' => 'birs_appointment',
					'post_status' => array( 'publish', 'pending' ),
					'meta_query' => array(
						array(
							'key' => '_birs_appointment_timestamp',
							'value' => time(),
							'compare' => '>=',
							'type' => 'SIGNED'
						)
					)
				),
				array(
					'base_keys' => array(),
					'meta_keys' => $appointment_fields
				)
			);
			foreach ( $appointments as $appointment ) {
				if ( isset( $appointment['ID'] ) && isset( $appointment['_birs_appointment_client'] ) ) {
					$appointment1on1 = array(
						'post_type' => 'birs_appointment1on1',
						'_birs_appointment_id' => $appointment['ID'],
						'_birs_client_id' => $appointment['_birs_appointment_client']
					);

					if ( isset( $appointment['_birs_appointment_price'] ) ) {
						$appointment1on1['_birs_appointment1on1_price'] = $appointment['_birs_appointment_price'];
					}

					if ( isset( $appointment['_birs_appointment_payment_status'] ) ) {
						$appointment1on1['_birs_appointment1on1_payment_status'] =
						$appointment['_birs_appointment_payment_status'];
					}

					if ( isset( $appointment['_birs_appointment_reminded'] ) ) {
						$appointment1on1['_birs_appointment1on1_reminded'] = $appointment['_birs_appointment_reminded'];
					}
					$appointment1on1['_birs_appointment1on1_uid'] = uniqid();

					foreach ( $appointment1on1_custom_fields as $appointment1on1_custom_field ) {
						if ( isset( $appointment[$appointment1on1_custom_field] ) ) {
							$appointment1on1[$appointment1on1_custom_field] =
							$appointment[$appointment1on1_custom_field];
						}
					}

					$appointment1on1_meta_keys = array_merge( $appointment1on1_fields, $appointment1on1_custom_fields );
					$birchpress->db->save( $appointment1on1, array(
							'base_keys' => array(),
							'meta_keys' => $appointment1on1_meta_keys
						)
					);
					foreach ( $appointment1on1_custom_fields as $appointment1on1_custom_field ) {
						delete_post_meta( $appointment['ID'], $appointment1on1_custom_field );
					}
					delete_post_meta( $appointment['ID'], '_birs_appointment_client' );
					delete_post_meta( $appointment['ID'], '_birs_appointment_notes' );
					delete_post_meta( $appointment['ID'], '_birs_appointment_reminded' );
					delete_post_meta( $appointment['ID'], '_birs_appointment_price' );
					delete_post_meta( $appointment['ID'], '_birs_appointment_payment_status' );
				}

			}
			update_option( 'birs_db_version_appointment', '1.1' );
		};

		$ns->upgrade_appointment_from_1_1_to_1_2 = function() use ( $ns ) {
			global $birchpress, $birchschedule;

			$version = $ns->get_db_version_appointment();
			if ( $version != '1.1' ) {
				return;
			}
			$appointments = $birchschedule->model->query(
				array(
					'post_type' => 'birs_appointment',
					'post_status' => array( 'any' ),
					'meta_query' => array(
						array(
							'key' => '_birs_appointment_timestamp',
							'value' => time(),
							'compare' => '>=',
							'type' => 'SIGNED'
						)
					)
				),
				array(
					'base_keys' => array( 'post_author' ),
					'meta_keys' => array( '_birs_appointment_staff' )
				)
			);
			if ( $appointments ) {
				foreach ( $appointments as $appointment_id => $appointment ) {
					$staff = $birchschedule->model->get( $appointment['_birs_appointment_staff'],
						array(
							'base_keys' => array(),
							'meta_keys' => array( '_birs_staff_email' )
						)
					);
					if ( $staff ) {
						$user = WP_User::get_data_by( 'email', $staff['_birs_staff_email'] );
						if ( $user ) {
							$appointment['post_author'] = $user->ID;
							$birchschedule->model->save( $appointment, array(
									'base_keys' => array( 'post_author' ),
									'meta_keys' => array()
								) );
						}
					}
				}
			}
			update_option( 'birs_db_version_appointment', '1.2' );
		};

		$ns->get_db_version_appointment = function() {
			return get_option( 'birs_db_version_appointment', '1.0' );
		};

		$ns->upgrade_core = function() use ( $ns ) {
			$ns->upgrade_staff_schedule_from_1_0_to_1_1();
			$ns->upgrade_appointment_from_1_0_to_1_1();
			$ns->upgrade_appointment_from_1_1_to_1_2();
		};

	} );
