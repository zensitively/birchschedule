<?php

birch_ns( 'birchschedule.gbooking.frontend', function( $ns ) {

		global $birchschedule;

		$ns->init = function() use ( $ns ) {
			add_action( 'init', array( $ns, 'wp_init' ) );
		};

		$ns->wp_init = function() use ( $ns ) {
			add_filter( 'birchschedule_model_schedule_get_staff_avaliable_time',
				array( $ns, 'add_avaliable_appointments' ), 15, 5 );
		};

		$ns->filter_appointments = function( $appointments, $service_id ) use ( $ns, $birchschedule ) {
			$new_appointments = array();
			foreach ( $appointments as $appointment ) {
				if ( $appointment['_birs_appointment_service'] == $service_id ) {
					$capacity =
					$birchschedule->model->booking->get_appointment_capacity( $appointment['ID'] );
					$appointment1on1s = $appointment['appointment1on1s'];
					if ( $appointment1on1s && sizeof( $appointment1on1s ) < $capacity ) {
						$appointment['_birs_appointment_capacity_left'] = $capacity - sizeof( $appointment1on1s );
						$new_appointments[$appointment['ID']] = $appointment;
					}
				}
			}
			return $new_appointments;
		};

		$ns->add_avaliable_appointments = function( $time_options, $staff_id, $location_id, $service_id, $date ) use ( $ns, $birchschedule ) {

			global $birchpress;

			$start = intval( $date->format( 'U' ) );
			$end = $start + 60 * 60 * 24;
			$criteria = array(
				'start' => $start,
				'end' => $end,
				'location_id' => $location_id,
				'staff_id' => $staff_id,
				'status' => array( 'publish', 'pending' ),
				'blocking' => true
			);
			$config = array(
				'appointment_keys' => array(
					'_birs_appointment_service',
					'_birs_appointment_timestamp'
				)
			);
			$appointments =
			$birchschedule->model->booking->query_appointments( $criteria, $config );
			$appointments = $ns->filter_appointments( $appointments, $service_id );
			foreach ( $appointments as $appointment ) {
				if ( isset( $appointment['_birs_appointment_capacity_left'] ) ) {
					$datetime = $birchpress->util->get_wp_datetime( $appointment['_birs_appointment_timestamp'] );
					$day_mins = $birchpress->util->get_day_minutes( $datetime );
					$time_options[$day_mins] = array(
						'text' => $birchpress->util->convert_mins_to_time_option( $day_mins ),
						'avaliable' => true,
						'capacity' => $appointment['_birs_appointment_capacity_left']
					);
				}
			}
			ksort( $time_options );
			return $time_options;
		};

	} );
