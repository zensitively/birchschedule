<?php

birch_ns( 'birchschedule.bpreferences.upgrader', function( $ns ) {

	global $birchschedule;

	$_ns_data = new stdClass();

	$ns->init = function() use( $ns, $_ns_data ) {
		$ns->init_data();

		add_action( 'birchschedule_gsettings_upgrader_upgrade_module_after', array( $ns, 'upgrade_module' ) );
	};

	$ns->init_data = function() use( $_ns_data ) {
		$_ns_data->default_booking_preferences_1_0 = array(
			'cut_off_time' => 1,
			'future_time' => 30
		);
		$_ns_data->default_booking_preferences_1_1 = $_ns_data->default_booking_preferences_1_0;
		$_ns_data->default_booking_preferences_1_1['version'] = '1.1';

		$_ns_data->default_booking_preferences_1_2 = $_ns_data->default_booking_preferences_1_1;
		$_ns_data->default_booking_preferences_1_2['time_before_cancel'] = 24;
		$_ns_data->default_booking_preferences_1_2['time_before_reschedule'] = 24;
		$_ns_data->default_booking_preferences_1_2['version'] = '1.2';

		$_ns_data->default_booking_preferences = $_ns_data->default_booking_preferences_1_2;
	};

	$ns->init_db = function() use ( $ns, $_ns_data ) {
		$options = get_option( 'birchschedule_options' );
		if ( !isset( $options['booking_preferences'] ) ) {
			$options['booking_preferences'] = $_ns_data->default_booking_preferences;
			update_option( 'birchschedule_options', $options );
		}
	};

	$ns->upgrade_module = function() use( $ns ) {
		$ns->init_db();
		$ns->upgrade_1_0_to_1_1();
		$ns->upgrade_1_1_to_1_2();
	};

	$ns->upgrade_1_0_to_1_1 = function() use( $ns ) {
		$version = $ns->get_db_version_booking_preferences();
		if ( $version !== '1.0' ) {
			return;
		}
		$options = get_option( 'birchschedule_options' );
		$options['booking_preferences']['version'] = '1.1';
		update_option( 'birchschedule_options', $options );
	};

	$ns->upgrade_1_1_to_1_2 = function() use( $ns ) {
		$version = $ns->get_db_version_booking_preferences();
		if ( $version !== '1.1' ) {
			return;
		}
		$options = get_option( 'birchschedule_options' );
		$options['booking_preferences']['time_before_cancel'] = 24;
		$options['booking_preferences']['time_before_reschedule'] = 24;
		$options['booking_preferences']['version'] = '1.2';
		update_option( 'birchschedule_options', $options );
	};

	$ns->get_db_version_booking_preferences = function() use( $ns ) {
		$options = get_option( 'birchschedule_options' );
		$booking_preferences = $options['booking_preferences'];
		if ( isset( $booking_preferences['version'] ) ) {
			return $booking_preferences['version'];
		} else {
			return '1.0';
		}
	};

} );
