<?php

birch_ns( 'birchschedule.model.schedule', function( $ns ) {

		$_ns_data = new stdClass();

		$ns->init = function() use ( $ns, $_ns_data ) {
			$_ns_data->step_length = 5;
		};

		$ns->get_staff_calculated_schedule_by_location = function(
			$staff_id, $location_id ) use ( $ns ) {
			global $birchschedule;

			$location_schedule = $birchschedule->model->
			get_staff_schedule_by_location( $staff_id, $location_id );
			$new_schedules = array();
			if ( isset( $location_schedule['schedules'] ) ) {
				$schedules = $location_schedule['schedules'];
				for ( $week_day = 0; $week_day < 7; $week_day++ ) {
					$new_schedules[] = array();
				}
				foreach ( $schedules as $schedule_id => $schedule ) {
					$schedule_date_start =
					$birchschedule->model->schedule->
					get_staff_schedule_date_start( $staff_id, $location_id, $schedule_id );
					$schedule_date_end =
					$birchschedule->model->schedule->
					get_staff_schedule_date_end( $staff_id, $location_id, $schedule_id );
					foreach ( $new_schedules as $week_day => $new_schedule ) {
						if ( isset( $schedule['weeks'][$week_day] ) ) {
							$new_schedules[$week_day][] = array(
								'minutes_start' => $schedule['minutes_start'],
								'minutes_end' => $schedule['minutes_end'],
								'date_start' => $schedule_date_start,
								'date_end' => $schedule_date_end
							);
						}
					}
				}
			}
			$location_schedule['schedules'] = $new_schedules;
			return $location_schedule;
		};

		$ns->get_staff_calculated_schedule = function( $staff_id ) use ( $ns ) {
			global $birchschedule;

			$staff = $birchschedule->model->get( $staff_id, array(
					'base_keys' => array(),
					'meta_keys' => array( '_birs_staff_schedule' )
				) );
			$staff_schedule = $staff['_birs_staff_schedule'];
			$new_all_schedule = array();
			foreach ( $staff_schedule as $location_id => $schedule ) {
				$new_all_schedule[$location_id] =
				$birchschedule
				->model->schedule->get_staff_calculated_schedule_by_location( $staff_id, $location_id );
			}
			return $new_all_schedule;
		};

		$ns->get_all_calculated_schedule = function() use ( $ns ) {
			global $birchschedule;

			$staff = $birchschedule->model->query(
				array(
					'post_type' => 'birs_staff'
				)
			);
			$allschedule = array();
			foreach ( $staff as $thestaff ) {
				$schedule = $birchschedule->model->schedule->get_staff_calculated_schedule( $thestaff['ID'] );
				$allschedule[$thestaff['ID']] = $schedule;
			}
			return $allschedule;
		};

		$ns->get_staff_schedule_date_start = function( $staff_id, $location_id, $schedule_id ) {
			return '';
		};

		$ns->get_staff_schedule_date_end = function( $staff_id, $location_id, $schedule_id ) {
			return '';
		};

		$ns->get_staff_exception_date_start = function( $staff_id, $location_id, $exception_id ) {
			return '';
		};

		$ns->get_staff_exception_date_end = function( $staff_id, $location_id, $exception_id ) {
			return '';
		};

		$ns->get_avaliable_schedules_by_date = function( $schedules, $date ) use ( $ns ) {
			global $birchpress;

			$new_schedules = array();
			$mysql_format = 'Y-m-d';
			foreach ( $schedules as $schedule ) {
				if ( $schedule['date_start'] ) {
					$date_start = $birchpress->util->get_wp_datetime(
						array(
							'date' => $schedule['date_start'],
							'time' => 0
						)
					)->format( $mysql_format );
				} else {
					$date_start = "";
				}
				if ( $schedule['date_end'] ) {
					$date_end = $birchpress->util->get_wp_datetime(
						array(
							'date' => $schedule['date_end'],
							'time' => 0
						)
					)->format( $mysql_format );
				} else {
					$date_end = 'a';
				}
				$current_date = $date->format( $mysql_format );
				if ( $date_start <= $current_date &&
					$date_end >= $current_date ) {
					$new_schedules[] = array(
						'minutes_start' => $schedule['minutes_start'],
						'minutes_end' => $schedule['minutes_end']
					);
				}
			}
			return $new_schedules;
		};

		$ns->get_exceptions_blocks = function( $exceptions ) use ( $ns, $_ns_data ) {
			$exceptions_blocks = array();
			$min_blocks = array();
			foreach ( $exceptions as $exception ) {
				$start = $exception['minutes_start'];
				$end = $exception['minutes_end'];
				if ( $end - $start === $_ns_data->step_length ) {
					$min_blocks[] = $start;
				} else {
					$exception_blocks = array();
					for ( $i = $start + $_ns_data->step_length; $i < $end; $i += $_ns_data->step_length ) {
						$exception_blocks[] = $i;
					}
					$exceptions_blocks =
					array_unique( array_merge( $exceptions_blocks, $exception_blocks ) );
				}
			}
			sort( $exceptions_blocks );
			$new_min_blocks = array();
			foreach ( $min_blocks as $min_block ) {
				if ( !in_array( $min_block, $exceptions_blocks ) &&
					!in_array( $min_block + $_ns_data->step_length, $exceptions_blocks ) ) {
					$new_min_blocks[] = $min_block;
				}
			}
			return array(
				'min' => $new_min_blocks,
				'others' => $exceptions_blocks
			);
		};

		$ns->get_schedules_blocks = function( $schedules ) use ( $ns, $_ns_data ) {
			$schedules_blocks = array();
			foreach ( $schedules as $schedule ) {
				$start = $schedule['minutes_start'];
				$end = $schedule['minutes_end'];
				$schedule_blocks = array();
				for ( $i = $start; $i <= $end; $i += $_ns_data->step_length ) {
					$schedule_blocks[] = $i;
				}
				$schedules_blocks =
				array_unique( array_merge( $schedules_blocks, $schedule_blocks ) );
			}
			sort( $schedules_blocks );
			return $schedules_blocks;
		};

		$ns->merge_schedules = function( $schedules, $exceptions ) use ( $ns, $_ns_data ) {
			$schedules_blocks = $ns->get_schedules_blocks( $schedules );
			$exceptions_blocks = $ns->get_exceptions_blocks( $exceptions );
			$min_exceptions_blocks = $exceptions_blocks['min'];
			$other_exceptions_blocks = $exceptions_blocks['others'];

			$all_blocks = array();
			$max_step  = 1440 / $_ns_data->step_length;
			for ( $i = 0; $i < $max_step; $i ++ ) {
				$block = $i * $_ns_data->step_length;
				if ( in_array( $block, $schedules_blocks ) ) {
					$all_blocks[$block] = true;
				} else {
					$all_blocks[$block] = false;
				}
				if ( in_array( $block, $min_exceptions_blocks ) ) {
					$all_blocks[$block] = 'min';
				}
				if ( in_array( $block, $other_exceptions_blocks ) ) {
					$all_blocks[$block] = false;
				}
			}
			$all_blocks[1440] = false;
			$merged = array();
			$started = false;
			foreach ( $all_blocks as $block => $block_value ) {
				if ( $started === false ) {
					if ( $block_value === true ) {
						$new_schedule = array(
							'minutes_start' => $block,
							'minutes_end' => $block
						);
						$started = true;
					}
					if ( $block_value === 'min' ) {
						$started = 'min';
					}
				}
				if ( $started === true ) {
					if ( $block_value === true ) {
						$new_schedule['minutes_end'] = $block;
					}
					if ( $block_value === false ) {
						$started = false;
						$merged[] = $new_schedule;
					}
					if ( $block_value === 'min' ) {
						$new_schedule['minutes_end'] = $block;
						$merged[] = $new_schedule;
						$started = 'min';
					}
				}
				if ( $started === 'min' ) {
					if ( $block_value === true ) {
						$new_schedule = array(
							'minutes_start' => $block,
							'minutes_end' => $block
						);
						$started = true;
					}
					if ( $block_value === false ) {
						$started = false;
					}
				}
			}
			return $merged;
		};

		$ns->get_staff_busy_time = function( $staff_id, $location_id, $date ) use ( $ns ) {
			global $birchschedule, $birchpress;

			$timestamp = $date->format( 'U' );
			$criteria = array(
				'start' => $timestamp,
				'end' => $timestamp + 3600 * 24,
				'location_id' => -1,
				'staff_id' => $staff_id,
				'status' => array( 'publish', 'pending' ),
				'blocking' => true
			);
			$appointments = $birchschedule->model->booking->query_appointments(
				$criteria,
				array(
					'appointment_keys' => array(
						'_birs_appointment_timestamp', '_birs_appointment_duration',
						'_birs_appointment_service', '_birs_appointment_padding_before',
						'_birs_appointment_padding_after'
					)
				)
			);
			$appointments_time = array();
			foreach ( $appointments as $appointment ) {
				$busy_time = $appointment['_birs_appointment_timestamp'];
				$datetime = $birchpress->util->get_wp_datetime( $busy_time );
				$busy_time = $birchpress->util->get_day_minutes( $datetime ) -
				$appointment['_birs_appointment_padding_before'];
				$appointment_duration = $appointment['_birs_appointment_duration'] +
				$appointment['_birs_appointment_padding_before'] +
				$appointment['_birs_appointment_padding_after'];
				$appointments_time[] = array(
					'busy_time' => $busy_time,
					'duration' => $appointment_duration,
					'type' => 'appointment'
				);
			}
			return $appointments_time;
		};

		$ns->convert_busy_times_to_exceptions = function( $busy_times ) use ( $ns ) {
			$exceptions = array();
			foreach ( $busy_times as $busy_time ) {
				$minutes_start = $busy_time['busy_time'];
				if ( $minutes_start % 5 !== 0 ) {
					$minutes_start = floor( $minutes_start / 5 ) * 5;
				}
				$minutes_end = $busy_time['busy_time'] + $busy_time['duration'];
				if ( $minutes_end % 5 !== 0 ) {
					$minutes_end = ( floor( $minutes_end / 5 ) + 1 ) * 5;
				}
				$exceptions[] = array(
					'minutes_start' => $minutes_start,
					'minutes_end' => $minutes_end
				);
			}
			return $exceptions;
		};

		$ns->get_avaliable_time_options = function( $schedule, $service_id ) use ( $ns ) {
			global $birchpress, $birchschedule;

			$timeslot = $birchschedule->model->get_service_timeslot( $service_id );
			$padding_before = $birchschedule->model->get_service_padding_before( $service_id );
			$service_length = $birchschedule->model->get_service_length( $service_id );
			$padding_after = $birchschedule->model->get_service_padding_after( $service_id );
			$capacity = $birchschedule->model->get_service_capacity( $service_id );

			$time_options = array();
			$start = $schedule['minutes_start'];
			$end = $schedule['minutes_end'];
			if ( $timeslot % 5 != 0 ) {
				$timeslot = ( floor( $timeslot / 5 ) + 1 ) * 5;
			}
			if ( $padding_before % 5 != 0 ) {
				$padding_before = ( floor( $padding_before / 5 ) + 1 ) * 5;
			}
			for ( $i = $start + $padding_before; $i < $end; $i += $timeslot ) {
				if ( $i + $service_length + $padding_after <= $end ) {
					$avaliable = true;
				} else {
					$avaliable = false;
				}
				$time_options[$i] = array(
					'text' => $birchpress->util->convert_mins_to_time_option( $i ),
					'avaliable' => $avaliable,
					'capacity' => $capacity
				);
			}
			return $time_options;
		};

		$ns->get_staff_avaliable_time = function(
			$staff_id, $location_id, $service_id, $date ) use ( $ns ) {

			global $birchschedule;

			$wday = $date->format( 'w' );
			$staff_schedule = $birchschedule->model->schedule->get_staff_calculated_schedule_by_location( $staff_id, $location_id );
			if ( isset( $staff_schedule['schedules'][$wday] ) ) {
				$day_schedules = $staff_schedule['schedules'][$wday];
			} else {
				return array();
			}
			if ( isset( $staff_schedule['exceptions'][$wday] ) ) {
				$day_exceptions = $staff_schedule['exceptions'][$wday];
			} else {
				$day_exceptions = array();
			}
			$avaliable_schedules =
			$ns->get_avaliable_schedules_by_date( $day_schedules, $date );

			$busy_times = $birchschedule->model->schedule->get_staff_busy_time( $staff_id, $location_id, $date );
			$avaliable_exceptions =
			$ns->get_avaliable_schedules_by_date( $day_exceptions, $date );
			$busy_exceptions = $ns->convert_busy_times_to_exceptions( $busy_times );
			$avaliable_exceptions = array_merge( $avaliable_exceptions, $busy_exceptions );
			$merged_schedules =
			$ns->merge_schedules( $avaliable_schedules, $avaliable_exceptions );
			$all_time_options = array();
			foreach ( $merged_schedules as $merged_schedule ) {
				$time_options =
				$ns->get_avaliable_time_options( $merged_schedule, $service_id );
				$all_time_options = $all_time_options + $time_options;
			}
			return $all_time_options;
		};

		$ns->get_fully_booked_days = function() use ( $ns ) {
			return get_option( 'birchschedule_schedule_fully_booked', array() );
		};

		$ns->clean_past_fully_booked = function( $fully_booked ) use ( $ns ) {
			global $birchschedule, $birchpress;

			$result = array();
			foreach ( $fully_booked as $date_text => $value ) {
				$today = $birchpress->util->get_wp_datetime( time() );
				$today_text = $today->format( 'Y-m-d' );
				if ( strcmp( $date_text, $today_text ) >= 0 ) {
					$result[$date_text] = $value;
				}
			}
			return $result;
		};

		$ns->mark_fully_booked_day = function(
			$staff_id, $location_id, $service_id, $date_text ) use ( $ns ) {

			global $birchschedule;

			$fully_booked = $birchschedule->model->schedule->get_fully_booked_days();
			$fully_booked = $birchschedule->model->schedule->clean_past_fully_booked( $fully_booked );
			if ( $staff_id != -1 ) {
				$fully_booked[$date_text][$staff_id][$location_id][$service_id] = true;
			}
			update_option( 'birchschedule_schedule_fully_booked', $fully_booked );
		};

		$ns->unmark_fully_booked_day = function(
			$staff_id, $location_id, $service_id, $date_text ) use ( $ns ) {

			global $birchschedule;

			$fully_booked = $birchschedule->model->schedule->get_fully_booked_days();
			$fully_booked = $birchschedule->model->schedule->clean_past_fully_booked( $fully_booked );
			if ( $staff_id != -1 ) {
				if ( isset( $fully_booked[$date_text] ) && isset( $fully_booked[$date_text][$staff_id] ) &&
					isset( $fully_booked[$date_text][$staff_id][$location_id] ) &&
					isset( $fully_booked[$date_text][$staff_id][$location_id][$service_id] ) ) {

					unset( $fully_booked[$date_text][$staff_id][$location_id][$service_id] );
				}
			}
			update_option( 'birchschedule_schedule_fully_booked', $fully_booked );
		};

	} );
