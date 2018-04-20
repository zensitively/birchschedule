<?php

birch_ns( 'birchschedule.model.cpt.appointment', function( $ns ) {

		global $birchschedule;

		$ns->init = function() use ( $ns, $birchschedule ) {
			$birchschedule->model->pre_save->when( $ns->is_model_appointment, $ns->pre_save );
			$birchschedule->model->post_get->when( $ns->is_model_appointment, $ns->post_get );
		};

		$ns->is_model_appointment = function($model) {
			return $model['post_type'] === 'birs_appointment';
		};

		$ns->pre_save = function( $appointment, $config ) {
			birch_assert( is_array( $appointment ) && isset( $appointment['post_type'] ) );
			global $birchschedule;

			if ( isset( $appointment['_birs_appointment_duration'] ) ) {
				$appointment['_birs_appointment_duration'] = (int) $appointment['_birs_appointment_duration'];
			}
			return $appointment;
		};

		$ns->post_get = function( $appointment ) {
			birch_assert( is_array( $appointment ) && isset( $appointment['post_type'] ) );
			global $birchpress;

			if ( isset( $appointment['_birs_appointment_timestamp'] ) ) {
				$timestamp = $appointment['_birs_appointment_timestamp'];
				$appointment['_birs_appointment_datetime'] =
				$birchpress->util->convert_to_datetime( $timestamp );
			}
			if ( !isset( $appointment['appointment1on1s'] ) ) {
				$appointment['appointment1on1s'] = array();
			}
			$appointment['_birs_appointment_admin_url'] = admin_url(
				sprintf( 'post.php?post=%s&action=edit', $appointment['ID'] ) );
			return $appointment;
		};
	} );
