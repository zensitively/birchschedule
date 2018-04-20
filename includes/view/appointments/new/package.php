<?php

birch_ns( 'birchschedule.view.appointments.new', function( $ns ) {

	global $birchschedule;

	$ns->init = function() use ( $ns, $birchschedule ) {
		add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );

		add_action( 'init', array( $ns, 'wp_init' ) );

		add_action( 'birchschedule_view_register_common_scripts_after',
					array( $ns, 'register_scripts' ) );

		$birchschedule->view->load_post_new->when( $birchschedule->view->appointments->is_post_type_appointment, $ns->load_post_new );

		$birchschedule->view->enqueue_scripts_post_new->when( $birchschedule->view->appointments->is_post_type_appointment, $ns->enqueue_scripts_post_new );
	};

	$ns->wp_init = function() use ( $ns ) {
		global $birchschedule;

		$birchschedule->view->register_script_data_fn(
			'birchschedule_view_appointments_new', 'birchschedule_view_appointments_new',
			array( $ns, 'get_script_data_fn_view_appointments_new' ) );
	};

	$ns->wp_admin_init = function() use ( $ns ) {
		add_action( 'wp_ajax_birchschedule_view_appointments_new_schedule',
					array( $ns, 'ajax_schedule' ) );
	};

	$ns->get_script_data_fn_view_appointments_new = function() use( $ns ) {
		return array(
			'services_staff_map' => $ns->get_services_staff_map(),
			'services_prices_map' => $ns->get_services_prices_map(),
			'services_duration_map' => $ns->get_services_duration_map(),
			'locations_map' => $ns->get_locations_map(),
			'locations_staff_map' => $ns->get_locations_staff_map(),
			'locations_services_map' => $ns->get_locations_services_map(),
			'locations_order' => $ns->get_locations_listing_order(),
			'staff_order' => $ns->get_staff_listing_order(),
			'services_order' => $ns->get_services_listing_order(),
		);
	};

	$ns->get_locations_map = function() {
		global $birchschedule;

		return $birchschedule->model->get_locations_map();
	};

	$ns->get_locations_staff_map = function() {
		global $birchschedule;

		return $birchschedule->model->get_locations_staff_map();
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

		return $birchschedule->model->get_locations_listing_order();
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

	$ns->load_post_new = function( $arg ) use ( $ns ) {
		add_action( 'add_meta_boxes',
					array( $ns, 'add_meta_boxes' ) );
	};

	$ns->register_scripts = function() {
		global $birchschedule;

		$version = $birchschedule->get_product_version();

		wp_register_script( 'birchschedule_view_appointments_new',
							$birchschedule->plugin_url() . '/assets/js/view/appointments/new/base.js',
							array( 'birchschedule_view_admincommon', 'birchschedule_view', 'jquery-ui-datepicker' ), "$version" );
	};

	$ns->enqueue_scripts_post_new = function( $arg ) {
		global $birchschedule;

		$birchschedule->view->register_3rd_scripts();
		$birchschedule->view->register_3rd_styles();
		$birchschedule->view->enqueue_scripts(
			array(
				'birchschedule_view_appointments_new'
			)
		);
		$birchschedule->view->enqueue_styles( array( 'birchschedule_appointments_new' ) );
	};

	$ns->add_meta_boxes = function() use ( $ns ) {
		add_meta_box( 'meta_box_birs_appointment_new_booking', __( 'Appointment Info', 'birchschedule' ),
					  array( $ns, 'render_booking_info' ), 'birs_appointment', 'normal', 'high' );
		add_meta_box( 'meta_box_birs_appointment_new_actions', __( 'Actions', 'birchschedule' ),
					  array( $ns, 'render_actions' ), 'birs_appointment', 'side', 'high' );
	};

	$ns->get_time_options = function( $time ) {
		global $birchpress;

		$options = $birchpress->util->get_time_options( 5 );
		ob_start();
		$birchpress->util->render_html_options( $options, $time );
		return ob_get_clean();
	};

	$ns->get_appointment_info_html = function() use ( $ns ) {
		global $birchpress;

		if ( isset( $_GET['apttimestamp'] ) ) {
			$timestamp = $birchpress->util->get_wp_datetime( $_GET['apttimestamp'] );
			$date = $timestamp->format( 'm/d/Y' );
			$time = $timestamp->format( 'H' ) * 60 + $timestamp->format( 'i' );
		} else {
			$date = '';
			$time = 540;
		}
		$location_id = 0;
		$service_id = 0;
		$staff_id = 0;
		if ( isset( $_GET['locationid'] ) && $_GET['locationid'] != -1 ) {
			$location_id = $_GET['locationid'];
		}
		if ( isset( $_GET['staffid'] ) && $_GET['staffid'] != -1 ) {
			$staff_id = $_GET['staffid'];
		}
		ob_start();
?>
        <ul>
            <li class="birs_form_field birs_appointment_location">
                <label>
                    <?php _e( 'Location', 'birchschedule' ); ?>
                </label>
                <div class="birs_field_content">
                    <select id="birs_appointment_location" name="birs_appointment_location"
                        data-value="<?php echo $location_id; ?>">
                    </select>
                </div>
            </li>
            <li class="birs_form_field birs_appointment_service">
                <label>
                    <?php _e( 'Service', 'birchschedule' ); ?>
                </label>
                <div class="birs_field_content">
                    <select id="birs_appointment_service" name="birs_appointment_service"
                        data-value="<?php echo $service_id; ?>">
                    </select>
                </div>
            </li>
            <li class="birs_form_field birs_appointment_staff">
                <label>
                    <?php _e( 'Provider', 'birchschedule' ); ?>
                </label>
                <div class="birs_field_content">
                    <select id="birs_appointment_staff" name="birs_appointment_staff"
                        data-value="<?php echo $staff_id; ?>">
                    </select>
                </div>
                <div class="birs_error" id="birs_appointment_service_error"></div>
            </li>
            <li class="birs_form_field birs_appointment_date">
                <label>
                    <?php _e( 'Date', 'birchschedule' ); ?>
                </label>
                <input id="birs_appointment_date" name="birs_appointment_date" type="hidden" value="<?php echo $date; ?>">
                <div  class="birs_field_content">
                    <div id="birs_appointment_datepicker">
                    </div>
                </div>
                <div class="birs_error" id="birs_appointment_date_error"></div>
            </li>
            <li class="birs_form_field birs_appointment_time">
                <label>
                    <?php _e( 'Time', 'birchschedule' ); ?>
                </label>
                <div class="birs_field_content">
                    <select id="birs_appointment_time" name="birs_appointment_time" size='1'>
                        <?php echo $ns->get_time_options( $time ); ?>
                    </select>
                </div>
                <div class="birs_error" id="birs_appointment_time_error"></div>
            </li>
        </ul>
<?php
		return ob_get_clean();
	};

	$ns->get_client_info_html = function() {
		global $birchschedule;

		return $birchschedule->view->appointments->edit->clientlist->edit->get_client_info_html( 0 );
	};

	$ns->get_appointment1on1_info_html = function() {
		global $birchschedule;

		return $birchschedule->view->appointments->edit->clientlist->edit->get_appointment1on1_info_html( 0, 0 );
	};

	$ns->render_client_info_header = function() {
?>
		<h3 class="birs_section"><?php _e( 'Client Info', 'birchschedule' ); ?></h3>
<?php
	};

	$ns->render_booking_info = function( $post ) use ( $ns ) {
		echo $ns->get_appointment_info_html();
?>
		<input type="hidden" id="birs_appointment_duration" name="birs_appointment_duration" />
<?php
		$ns->render_client_info_header();
?>
		<div id="birs_client_info_container">
<?php
		echo $ns->get_client_info_html();
?>
		</div>
		<h3 class="birs_section"><?php _e( 'Additional Info', 'birchschedule' ); ?></h3>
<?php
		echo $ns->get_appointment1on1_info_html();
?>
                <ul>
                    <li class="birs_form_field birs_please_wait" style="display:none;">
                        <label>
                            &nbsp;
                        </label>
                        <div class="birs_field_content">
                            <div><?php _e( 'Please wait...', 'birchschedule' ); ?></div>
                        </div>
                    </li>
                    <li class="birs_form_field">
                        <label>
                            &nbsp;
                        </label>
                        <div class="birs_field_content">
                            <input type="button" id="birs_appointment_actions_schedule" class="button-primary" value="<?php _e( 'Schedule', 'birchschedule' ); ?>" />
                        </div>
                    </li>
                </ul>
<?php
	};

	$ns->render_actions = function() {
		global $birchschedule;

		$back_url = $birchschedule->view->appointments->edit->get_back_to_calendar_url();
?>
                <div class="submitbox">
                    <div style="float:left;">
                        <a href="<?php echo $back_url; ?>">
                            <?php _e( 'Back to Calendar', 'birchschedule' ); ?>
                        </a>
                    </div>
                    <div class="clear"></div>
                </div>
<?php
	};

	$ns->validate_appointment_info = function() {
		global $birchpress;

		$errors = array();
		if ( !isset( $_POST['birs_appointment_staff'] ) || !isset( $_POST['birs_appointment_service'] ) ) {
			$errors['birs_appointment_service'] = __( 'Please select a service and a provider', 'birchschedule' );
		}
		if ( !isset( $_POST['birs_appointment_date'] ) || !$_POST['birs_appointment_date'] ) {
			$errors['birs_appointment_date'] = __( 'Date is required', 'birchschedule' );
		}
		if ( !isset( $_POST['birs_appointment_time'] ) || !$_POST['birs_appointment_time'] ) {
			$errors['birs_appointment_time'] = __( 'Time is required', 'birchschedule' );
		}
		if ( isset( $_POST['birs_appointment_date'] ) && $_POST['birs_appointment_date'] &&
			 isset( $_POST['birs_appointment_time'] ) && $_POST['birs_appointment_time'] ) {
			$datetime = array(
				'date' => $_POST['birs_appointment_date'],
				'time' => $_POST['birs_appointment_time']
			);
			$datetime = $birchpress->util->get_wp_datetime( $datetime );
			if ( !$datetime ) {
				$errors['birs_appointment_datetime'] = __( 'Date & time is incorrect', 'birchschedule' );
			}
		}
		return $errors;
	};

	$ns->validate_client_info = function() {
		$errors = array();
		if ( !$_POST['birs_client_name_first'] ) {
			$errors['birs_client_name_first'] = __( 'This field is required', 'birchschedule' );
		}
		if ( !$_POST['birs_client_name_last'] ) {
			$errors['birs_client_name_last'] = __( 'This field is required', 'birchschedule' );
		}
		if ( !$_POST['birs_client_email'] ) {
			$errors['birs_client_email'] = __( 'Email is required', 'birchschedule' );
		} else if ( !is_email( $_POST['birs_client_email'] ) ) {
			$errors['birs_client_email'] = __( 'Email is incorrect', 'birchschedule' );
		}
		if ( !$_POST['birs_client_phone'] ) {
			$errors['birs_client_phone'] = __( 'This field is required', 'birchschedule' );
		}

		return $errors;
	};

	$ns->validate_appointment1on1_info = function() {
		return array();
	};

	$ns->ajax_schedule = function() use ( $ns ) {
		global $birchpress, $birchschedule;

		$appointment_errors = $ns->validate_appointment_info();
		$appointment1on1_errors = $ns->validate_appointment1on1_info();
		$client_errors = $ns->validate_client_info();
		$errors = array_merge( $appointment_errors, $appointment1on1_errors, $client_errors );
		if ( $errors ) {
			$birchschedule->view->render_ajax_error_messages( $errors );
		}
		$client_config = array(
			'base_keys' => array(),
			'meta_keys' => $_POST['birs_client_fields']
		);
		$client_info = $birchschedule->view->merge_request( array(), $client_config, $_POST );
		unset( $client_info['ID'] );
		$client_id = $birchschedule->model->booking->save_client( $client_info );
		$appointment1on1_config = array(
			'base_keys' => array(),
			'meta_keys' => array_merge(
				$birchschedule->model->get_appointment_fields(),
				$birchschedule->model->get_appointment1on1_fields(),
				$birchschedule->model->get_appointment1on1_custom_fields()
			)
		);
		$appointment1on1_info =
		$birchschedule->view->merge_request( array(), $appointment1on1_config, $_POST );
		$datetime = array(
			'date' => $_POST['birs_appointment_date'],
			'time' => $_POST['birs_appointment_time']
		);
		$datetime = $birchpress->util->get_wp_datetime( $datetime );
		$timestamp = $datetime->format( 'U' );
		$appointment1on1_info['_birs_appointment_timestamp'] = $timestamp;
		$appointment1on1_info['_birs_client_id'] = $client_id;
		unset( $appointment1on1_info['ID'] );
		unset( $appointment1on1_info['_birs_appointment_id'] );
		$appointment1on1_id = $birchschedule->model->booking->make_appointment1on1( $appointment1on1_info );
		$birchschedule->model->booking->change_appointment1on1_status( $appointment1on1_id, 'publish' );

		if ( $appointment1on1_id ) {
			$cal_url = admin_url( 'admin.php?page=birchschedule_calendar' );
			$refer_query = parse_url( wp_get_referer(), PHP_URL_QUERY );
			$hash_string = $birchschedule->view->get_query_string( $refer_query,
																   array(
																	   'calview', 'locationid', 'staffid', 'currentdate'
																   )
			);
			if ( $hash_string ) {
				$cal_url = $cal_url . '#' . $hash_string;
			}
			$birchschedule->view->render_ajax_success_message( array(
				'code' => 'success',
				'message' => json_encode( array(
					'url' => htmlentities( $cal_url )
				) )
			) );
		}
	};

} );
