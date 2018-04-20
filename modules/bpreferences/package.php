<?php

birch_ns( 'birchschedule.bpreferences', function( $ns ) {

	global $birchschedule;

	$ns->init = function() use( $ns ) {
		add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );

		add_filter( 'birchschedule_model_schedule_get_staff_avaliable_time',
					array( $ns, 'filter_booking_time_options' ), 20, 5 );

		add_filter( 'birchschedule_model_get_cut_off_time',
					array( $ns, 'get_cut_off_time' ) );

		add_filter( 'birchschedule_model_get_future_time',
					array( $ns, 'get_future_time' ) );
	};

	$ns->wp_admin_init = function() use( $ns ) {
		add_action( 'birchschedule_gsettings_add_settings_sections_after',
					array( $ns, 'add_booking_preferences_section' ), 20 );
	};

	$ns->filter_booking_time_options = function( $time_options, $staff_id, $location_id, $service_id, $date ) use( $ns, $birchschedule ) {
		$current_timestamp = time();
		$cut_off_hours = $birchschedule->model->get_cut_off_time( $staff_id, $location_id, $service_id );
		foreach ( $time_options as $key => $value ) {
			$selected_timestamp = $date->format( 'U' ) + $key * 60;
			$difference_hours = ( $selected_timestamp - $current_timestamp ) / 3600;
			if ( $difference_hours < $cut_off_hours ) {
				$time_options[$key]['avaliable'] = false;
			}
		}
		return $time_options;
	};

	$ns->get_cut_off_time = function( $cut_off_time ) use( $ns ) {
		$booking_preferences = $ns->get_booking_preferences();
		if ( isset( $booking_preferences['cut_off_time'] ) ) {
			return $booking_preferences['cut_off_time'];
		} else {
			return $cut_off_time;
		}
	};

	$ns->get_future_time = function( $future_time ) use( $ns ) {
		$booking_preferences = $ns->get_booking_preferences();
		if ( isset( $booking_preferences['future_time'] ) ) {
			return $booking_preferences['future_time'];
		} else {
			return $future_time;
		}
	};

	$ns->get_cut_off_times = function() {
		return array(
			1 => sprintf( __( "%s hour", "birchschedule" ), "1" ),
			2 => sprintf( __( "%s hours", "birchschedule" ), "2" ),
			3 => sprintf( __( "%s hours", "birchschedule" ), "3" ),
			6 => sprintf( __( "%s hours", "birchschedule" ), "6" ),
			12 => sprintf( __( "%s hours", "birchschedule" ), "12" ),
			24 => sprintf( __( "%s hours", "birchschedule" ), "24" ),
			48 => sprintf( __( "%s days", "birchschedule" ), "2" ),
			72 => sprintf( __( "%s days", "birchschedule" ), "3" ),
			96 => sprintf( __( "%s days", "birchschedule" ), "4" ),
			120 => sprintf( __( "%s days", "birchschedule" ), "5" ),
			168 => sprintf( __( "%s days", "birchschedule" ), "7" ),
			240 => sprintf( __( "%s days", "birchschedule" ), "10" ),
			336 => sprintf( __( "%s days", "birchschedule" ), "14" ),
		);
	};

	$ns->get_future_times = function() {
		return array(
			30 => sprintf( __( "%s month", "birchschedule" ), "1" ),
			60 => sprintf( __( "%s months", "birchschedule" ), "2" ),
			90 => sprintf( __( "%s months", "birchschedule" ), "3" ),
			180 => sprintf( __( "%s months", "birchschedule" ), "6" ),
			360 => sprintf( __( "%s months", "birchschedule" ), "12" ),
			720 => sprintf( __( "%s months", "birchschedule" ), "24" ),
		);
	};

	$ns->add_booking_preferences_section = function() use( $ns, $birchschedule ) {

		add_settings_section( 'birchschedule_booking_preferences',
							  __( 'Booking Preferences', 'birchschedule' ),
							  array( $ns, 'render_booking_preferences_section' ),
							  'birchschedule_settings' );

		add_settings_field( 'birchschedule_booking_policies',
							__( 'Policies', 'birchschedule' ),
							array( $ns, 'render_booking_policies' ),
							'birchschedule_settings', 'birchschedule_booking_preferences' );
	};

	$ns->render_booking_preferences_section = function() {
		echo "";
	};

	$ns->get_booking_preferences = function() {
		$options = get_option( 'birchschedule_options' );
		return $options['booking_preferences'];
	};

	$ns->get_cut_off_times_html = function() use( $ns, $birchschedule ) {
		global $birchpress;

		$booking_preferences = $ns->get_booking_preferences();
		$cut_off_time = $booking_preferences['cut_off_time'];
		ob_start();
?>
        <select name="birchschedule_options[booking_preferences][cut_off_time]">
            <?php $birchpress->util->render_html_options( $ns->get_cut_off_times(), $cut_off_time ); ?>
        </select>
<?php
		return ob_get_clean();
	};

	$ns->get_future_times_html = function() use( $ns, $birchschedule ) {
		global $birchpress;

		$booking_preferences = $ns->get_booking_preferences();
		$future_time = $booking_preferences['future_time'];
		ob_start();
?>
        <select name="birchschedule_options[booking_preferences][future_time]">
            <?php $birchpress->util->render_html_options( $ns->get_future_times(), $future_time ); ?>
        </select>
<?php
		return ob_get_clean();
	};

	$ns->render_booking_policies = function() use( $ns, $birchschedule ) {
		$booking_preferences = $ns->get_booking_preferences();
		$version = $booking_preferences['version'];
?>
        <ul style="margin: 0;">
            <input type='hidden' name='birchschedule_options[booking_preferences][version]' value='<?php echo $version; ?>'>
            <li>
                <?php printf( __( 'Clients can book appointments up to %s before start time', 'birchschedule' ),
					$ns->get_cut_off_times_html() ); ?>
            </li>
            <li>
                <?php printf( __( 'Clients can book appointments up to %s in the future', 'birchschedule' ),
					$ns->get_future_times_html() ); ?>
            </li>
        </ul>
<?php
	};

} );
