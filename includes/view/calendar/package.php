<?php

birch_ns( 'birchschedule.view.calendar', function( $ns ) {

	$ns->init = function() use ( $ns ) {
		global $birchschedule;

		add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );

		$birchschedule->view->register_script_data_fn(
			'birchschedule_view_calendar', 'birchschedule_view_calendar',
			array( $ns, 'get_script_data_fn_view_calendar' ) );
	};

	$ns->wp_admin_init = function() use ( $ns ) {
		global $birchschedule;

		add_action( 'admin_enqueue_scripts', function( $hook ) {
			global $birchschedule;

			if ( $birchschedule->view->get_page_hook( 'calendar' ) !== $hook ) {
				return;
			}

			$birchschedule->view->calendar->enqueue_scripts();
		} );

		add_action( 'wp_ajax_birchschedule_view_calendar_query_appointments',
					array( $ns, 'ajax_query_appointments' ) );

		add_action( 'birchschedule_view_render_calendar_page_after', array( $ns, 'render_admin_page' ) );
	};

	$ns->get_script_data_fn_view_calendar = function() use ( $ns ) {
		return array(
			'default_calendar_view' => $ns->get_default_view(),
			'location_map' => $ns->get_locations_map(),
			'location_staff_map' => $ns->get_locations_staff_map(),
			'location_order' => $ns->get_locations_listing_order(),
			'staff_order' => $ns->get_staff_listing_order(),
			'slot_minutes' => 15,
			'first_hour' => 9
		);
	};

	$ns->enqueue_scripts = function() use ( $ns ) {
		global $birchschedule;

		$birchschedule->view->register_3rd_scripts();
		$birchschedule->view->register_3rd_styles();
		$birchschedule->view->enqueue_scripts(
			array(
				'birchschedule_view_calendar', 'birchschedule_view',
				'birchschedule_view_admincommon', 'birchschedule_model'
			)
		);
		$birchschedule->view->enqueue_styles(
			array(
				'fullcalendar_birchpress',
				'birchschedule_admincommon', 'birchschedule_calendar',
				'select2', 'jgrowl'
			)
		);
	};

	$ns->get_default_view = function() {
		return 'agendaWeek';
	};

	$ns->query_appointments = function( $start, $end, $location_id, $staff_id ) use ( $ns ) {
		global $birchschedule, $birchpress;

		$criteria = array(
			'start' => $start,
			'end' => $end,
			'location_id' => $location_id,
			'staff_id' => $staff_id
		);
		$appointments =
		$birchschedule->model->booking->query_appointments( $criteria,
															array(
																'appointment_keys' => array(
																	'_birs_appointment_duration', '_birs_appointment_price',
																	'_birs_appointment_timestamp', '_birs_appointment_service'
																),
																'client_keys' => array( 'post_title' )
															) );
		$apmts = array();
		foreach ( $appointments as $appointment ) {
			$title = $birchschedule->model->booking->get_appointment_title( $appointment );
			$appointment['post_title'] = $title;
			$duration = intval( $appointment['_birs_appointment_duration'] );
			$price = $appointment['_birs_appointment_price'];
			$time_start = $appointment['_birs_appointment_timestamp'];
			$time_end = $time_start + $duration * 60;
			$time_start = $birchpress->util->get_wp_datetime( $time_start )->format( 'c' );
			$time_end = $birchpress->util->get_wp_datetime( $time_end )->format( 'c' );
			$apmt = array(
				'id' => $appointment['ID'],
				'title' => $appointment['post_title'],
				'start' => $time_start,
				'end' => $time_end,
				'allDay' => false,
				'editable' => true
			);
			$apmts[] = $apmt;
		}

		return $apmts;
	};

	$ns->get_locations_map = function() use ( $ns ) {
		global $birchschedule;

		$i18n_msgs = $birchschedule->view->get_frontend_i18n_messages();
		$locations_map = $birchschedule->model->get_locations_map();
		$locations_map[-1] = array(
			'post_title' => $i18n_msgs['All Locations']
		);
		return $locations_map;
	};

	$ns->get_locations_staff_map = function() use ( $ns ) {
		global $birchschedule;

		$i18n_msgs = $birchschedule->view->get_frontend_i18n_messages();
		$map = $birchschedule->model->get_locations_staff_map();
		$allstaff = $birchschedule->model->query(
			array(
				'post_type' => 'birs_staff'
			),
			array(
				'meta_keys' => array(),
				'base_keys' => array( 'post_title' )
			)
		);
		$new_allstaff = array(
			'-1' => $i18n_msgs['All Providers']
		);
		foreach ( $allstaff as $staff_id => $staff ) {
			$new_allstaff[$staff_id] = $staff['post_title'];
		}
		$map[-1] = $new_allstaff;
		return $map;
	};

	$ns->get_locations_services_map = function() {
		global $birchschedule;

		return $birchschedule->model->get_locations_services_map();
	};

	$ns->get_services_staff_map = function() {
		global $birchschedule;

		return $birchschedule->model->get_services_staff_map();
	};

	$ns->get_locations_listing_order = function() {
		global $birchschedule;

		$locations = $birchschedule->model->get_locations_listing_order();
		$locations = array_merge( array( -1 ), $locations );
		return $locations;
	};

	$ns->get_staff_listing_order = function() {
		global $birchschedule;

		return $birchschedule->model->get_staff_listing_order();
	};

	$ns->get_services_listing_order = function() {
		global $birchschedule;

		return $birchschedule->model->get_services_listing_order();
	};

	$ns->get_services_prices_map = function() {
		global $birchschedule;

		return $birchschedule->model->get_services_prices_map();
	};

	$ns->get_services_duration_map = function() {
		global $birchschedule;

		return $birchschedule->model->get_services_duration_map();
	};

	$ns->ajax_query_appointments = function() use ( $ns ) {
		global $birchschedule, $birchpress;

		$start = $_GET['birs_time_start'];
		$start = $birchpress->util->get_wp_datetime( $start )->format( 'U' );
		$end = $_GET['birs_time_end'];
		$end = $birchpress->util->get_wp_datetime( $end )->format( 'U' );
		$location_id = $_GET['birs_location_id'];
		$staff_id = $_GET['birs_staff_id'];

		$apmts = $birchschedule->view->calendar->query_appointments( $start, $end, $location_id, $staff_id );
?>
        <div id="birs_response">
<?php
		echo json_encode( $apmts );
?>
        </div>
<?php
		exit;
	};

	$ns->render_admin_page = function() use ( $ns ) {
		global $birchschedule;

		$birchschedule->view->show_notice();
		$ns->render_calendar_scene();
	};

	$ns->render_calendar_scene = function() {
?>
        <div class="birs_scene" id="birs_calendar_scene">
            <h2 style="display:none;"></h2>
            <div id="birs_calendar_menu">
                <div class="birs_group">
                    <button type="button" class="button"
                        id="birs_add_appointment">
                        <?php _e( 'New Appointment', 'birchschedule' ); ?>
                    </button>
                    <button type="button" class="button"
                        id="birs_calendar_refresh">
                           <span class="dashicons dashicons-update"></span>
                    </button>
                </div>
                <div class="birs_group">
                    <button type="button" class="button"
                        id="birs_calendar_today">
                        <?php _e( 'Today', 'birchschedule' ); ?>
                    </button>
                </div>
                <div class="birs_group birs_radio_group">
                    <label>
                        <input type="radio" name="birs_calendar_view_choice" value="month">
                        <?php _e( 'Month', 'birchschedule' ); ?>
                    </label>
                    <label>
                        <input type="radio" name="birs_calendar_view_choice" value="agendaWeek">
                        <?php _e( 'Week', 'birchschedule' ); ?>
                    </label>
                    <label>
                        <input type="radio" name="birs_calendar_view_choice" value="agendaDay">
                        <?php _e( 'Day', 'birchschedule' ); ?>
                    </label>
                    <input type="hidden" name="birs_calendar_view" />
                    <input type="hidden" name="birs_calendar_current_date" />
                </div>
                <div class="birs_group">
                    <select id="birs_calendar_location">
                    </select>
                    <select id="birs_calendar_staff">
                    </select>
                </div>
                <div style="clear: both;"></div>
            </div>
            <div id="birs_calendar_header">
                <table class="fc-header" style="width:100%">
                    <tbody>
                        <tr>
                            <td class="fc-header-left">
                                <button type="button" class="button">
                                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                                </button>
                            </td>
                            <td class="fc-header-center">
                                <span class="fc-header-title">
                                    <h2></h2>
                                </span>
                            </td>
                            <td class="fc-header-right">
                                <button type="button" class="button">
                                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="birs_calendar">
            </div>
        </div>
<?php
	};

} );
