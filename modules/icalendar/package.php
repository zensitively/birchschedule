<?php

require_once dirname( __FILE__ ) . '/../../lib/icalcreator/include.php';
require_once dirname( __FILE__ ) . '/classes/timezone_parser.php';

birch_ns( 'birchschedule.icalendar', function( $ns ) {

		global $birchschedule;

		$ns->init = function() {

		};

		$ns->export_appointments_as_ics = function( $appointments ) use ( $ns ) {
			header( "Cache-Control: no-cache, no-store, must-revalidate" ); // HTTP 1.1.
			header( "Pragma: no-cache" ); // HTTP 1.0.
			header( "Expires: 0" ); // Proxies.
			header( 'Content-type: text/calendar; charset=utf-8' );
			echo $ns->get_appointments_as_ics( $appointments );
		};

		$ns->export_appointment1on1s_as_ics = function( $appointment1on1s ) use ( $ns ) {
			header( "Cache-Control: no-cache, no-store, must-revalidate" ); // HTTP 1.1.
			header( "Pragma: no-cache" ); // HTTP 1.0.
			header( "Expires: 0" ); // Proxies.
			header( 'Content-type: text/calendar; charset=utf-8' );
			echo $ns->get_appointment1on1s_as_ics( $appointment1on1s );
		};

		$ns->get_vcalendar_properties = function() {
			return array(
				'calscale' => 'GREGORIAN',
				'method' => 'PUBLISH',
				'X-WR-CALNAME' => get_bloginfo( 'name' ),
				'X-WR-CALDESC' => get_bloginfo( 'description' ),
				'X-FROM-URL' => home_url()
			);
		};

		$ns->get_appointments_as_ics = function( $appointments ) use ( $ns, $birchschedule ) {

			$c = $ns->create_vcalendar();
			foreach ( $appointments as $appointment_id => $appointment ) {
				$new_appointment = $birchschedule->model->mergefields->get_appointment_merge_values( $appointment_id );
				$appointment = array_merge( $new_appointment, $appointment );
				$e = $ns->create_vevent( $c, $appointment );
				$summary = $ns->get_appointment_summary( $appointment );
				$e->setProperty(
					'summary',
					$ns->_sanitize_value( $summary )
				);
				$description = $ns->get_appointment_description( $appointment );
				$e->setProperty( 'description', $ns->_sanitize_value( $description ) );
			}

			$str = ltrim( $c->createCalendar() );
			return $str;
		};

		$ns->get_appointment1on1s_as_ics = function( $appointment1on1s ) use ( $ns, $birchschedule ) {

			$c = $ns->create_vcalendar();
			foreach ( $appointment1on1s as $appointment1on1_id => $appointment1on1 ) {
				$appointment1on1 =
				$birchschedule->model->mergefields->get_appointment1on1_merge_values( $appointment1on1['ID'] );
				$e = $ns->create_vevent( $c, $appointment1on1 );
				$summary = $ns->get_appointment1on1_summary( $appointment1on1 );
				$e->setProperty(
					'summary',
					$ns->_sanitize_value( $summary )
				);
				$description = $ns->get_appointment1on1_description( $appointment1on1 );
				$e->setProperty( 'description', $ns->_sanitize_value( $description ) );
			}

			$str = ltrim( $c->createCalendar() );
			return $str;
		};

		$ns->create_vcalendar = function() use ( $ns, $birchschedule ) {

			birchschedule_lib_icalcreator_load();
			$c = new vcalendar();
			$properties = $ns->get_vcalendar_properties();
			foreach ( $properties as $key => $value ) {
				$c->setProperty( $key, $value );
			}
			$tz = get_option( 'timezone_string' );
			if ( $tz ) {
				$c->setProperty( 'X-WR-TIMEZONE', $tz );
				$tz_xprops = array( 'X-LIC-LOCATION' => $tz );
				iCalUtilityFunctions::createTimezone( $c, $tz, $tz_xprops );
			}
			return $c;
		};

		$ns->create_vevent = function( $calendar, $appointment ) use ( $ns, $birchschedule ) {

			$tz = get_option( 'timezone_string' );
			$e = & $calendar->newComponent( 'vevent' );
			$uid = $appointment['_birs_appointment_uid'];
			$e->setProperty( 'uid', $uid );
			$e->setProperty(
				'dtstart',
				array(
					"timestamp" => $appointment['_birs_appointment_timestamp']
				)
			);
			$end_ts = $appointment['_birs_appointment_timestamp'] + $appointment['_birs_appointment_duration'] * 60;
			$e->setProperty(
				'dtend',
				array(
					"timestamp" => $end_ts
				)
			);
			$location = $ns->get_appointment1on1_location_template();
			$location = $birchschedule->model->mergefields->apply_merge_fields( $location, $appointment );
			$e->setProperty( "location", $location );
			return $e;
		};

		$ns->merge_map = function() {

		};

		$ns->get_appointment_summary = function( $appointment ) use ( $ns, $birchschedule ) {
			$template = $ns->get_appointment_summary_template();
			if ( $template === false ) {
				return $birchschedule->model->booking->get_appointment_title( $appointment );
			}
			$seperator = $ns->get_summary_separator();

			$appointment1on1s = $appointment['appointment1on1s'];
			$description = implode( $seperator, array_map( function( $el ) use ( $birchschedule, $template ) {
						$values = $birchschedule->model->mergefields->get_appointment1on1_merge_values( $el['ID'] );
						return $birchschedule->model->mergefields->apply_merge_fields( $template, $values );
					}, $appointment1on1s ) );

			return $description;
		};

		$ns->get_appointment_summary_template = function() {
			return false;
		};

		$ns->get_summary_separator = function() {
			return ' | ';
		};

		$ns->get_appointment_description = function( $appointment ) use ( $ns, $birchschedule ) {
			$appointment1on1s = $appointment['appointment1on1s'];
			$description = '';
			$seperator = $ns->get_description_separator();
			$description = implode( $seperator, array_map( function( $el ) use ( $ns, $birchschedule ) {
						$values = $birchschedule->model->mergefields->get_appointment1on1_merge_values( $el['ID'] );
						return $ns->get_appointment1on1_description( $values );
					}, $appointment1on1s ) );

			return $description;
		};

		$ns->get_description_separator = function() {
			return "\n---------------------------------------------\n";
		};

		$ns->get_appointment1on1_description = function( $appointment1on1 ) use ( $ns, $birchschedule ) {
			$template = $ns->get_appointment1on1_description_template();
			$description = $birchschedule->model->mergefields->apply_merge_fields( $template, $appointment1on1 );
			return $description;
		};

		$ns->get_appointment1on1_summary = function( $appointment1on1 ) use ( $ns, $birchschedule ) {
			$template = $ns->get_appointment1on1_summary_template();
			$summary = $birchschedule->model->mergefields->apply_merge_fields( $template, $appointment1on1 );
			return $summary;
		};

		$ns->get_appointment1on1_summary_template = function() {
			$summary = "{service_name} - {client_name}";
			return $summary;
		};

		$ns->get_appointment1on1_location_template = function() {
			return "{location_name}";
		};

		$ns->get_appointment1on1_description_template = function() use ( $ns ) {
			return $ns->get_appointment_export_template();
		};

		//legacy hook - filter birchschedule_icalendar_get_appointment_export_template
		$ns->get_appointment_export_template = function() {
			return "APPOINTMENT DETAILS\n" .
			"What: {service_name} \n" .
			"When: {datetime} \n" .
			"Where: {location_name} \n\n" .
			"CLIENT DETAILS\n" .
			"Contact: {client_name} \n" .
			"Email: {client_email} \n" .
			"Phone: {client_phone} \n" .
			"Notes: {appointment_notes} \n";
		};

		$ns->_sanitize_value = function( $value ) {
			if ( ! is_scalar( $value ) ) {
				return $value;
			}
			$safe_eol = "\n";
			$value    = strtr(
				trim( $value ),
				array(
					"\r\n" => $safe_eol,
					"\r"   => $safe_eol,
					"\n"   => $safe_eol,
				)
			);
			$value = addcslashes( $value, '\\' );
			return $value;
		};

	} );
