<?php

birch_ns( 'birchschedule.view.appointments.edit.clientlist.reschedule', function( $ns ) {

	global $birchschedule;

	$ns->init = function() use ( $ns ) {
		add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );

		add_action( 'birchschedule_view_register_common_scripts_after',
					array( $ns, 'register_scripts' ) );
	};

	$ns->wp_admin_init = function() use ( $ns, $birchschedule ) {
		add_action( 'birchschedule_view_enqueue_scripts_post_edit_after',
					array( $ns, 'enqueue_scripts_post_edit' ) );

		add_action( 'birchschedule_view_appointments_edit_clientlist_render_more_rows_after',
					array( $ns, 'render_row' ), 20, 3 );

		add_filter( 'birchschedule_view_appointments_edit_clientlist_get_item_actions',
					array( $ns, 'add_item_action' ), 35, 2 );

		add_action( 'wp_ajax_birchschedule_view_appointments_edit_clientlist_reschedule',
					array( $ns, 'ajax_reschedule' ) );
	};

	$ns->register_scripts = function() use( $birchschedule ) {
		$version = $birchschedule->get_product_version();

		wp_register_script( 'birchschedule_view_appointments_edit_clientlist_reschedule',
							$birchschedule->plugin_url() . '/assets/js/view/appointments/edit/clientlist/reschedule/base.js',
							array( 'birchschedule_view_admincommon', 'birchschedule_view' ), "$version" );
	};

	$ns->enqueue_scripts_post_edit = function( $arg ) {
		if ( $arg['post_type'] != 'birs_appointment' ) {
			return;
		}

		global $birchschedule;

		$birchschedule->view->register_3rd_scripts();
		$birchschedule->view->register_3rd_styles();
		$birchschedule->view->enqueue_scripts(
			array(
				'birchschedule_view_appointments_edit_clientlist_reschedule'
			)
		);
	};

	$ns->add_item_action = function( $item_actions, $item ) {
		$action_html = '<a href="javascript:void(0);" data-item-id="%s">%s</a>';
		$item_actions['reschedule'] = sprintf( $action_html, $item['ID'], __( 'Reschedule', 'birchschedule' ) );
		return $item_actions;
	};

	$ns->render_row = function( $wp_list_table, $item, $row_class ) use( $ns ) {
		$client_id = $item['ID'];
		$appointment_id = $wp_list_table->appointment_id;
		$column_count = $wp_list_table->get_column_count();
		$reschedule_html = $ns->get_reschedule_html( $appointment_id, $client_id );
?>
                <tr class="<?php echo $row_class; ?> birs_row_reschedule"
                    id="birs_client_list_row_reschedule_<?php echo $client_id; ?>"
                    data-item-id = "<?php echo $client_id; ?>"
                    data-reschedule-html = "<?php echo esc_attr( $reschedule_html ); ?>" >

                    <td colspan = "<?php echo $column_count; ?>">
                    </td>
                </tr>
<?php
	};

	$ns->validate_reschedule_info = function() {
		global $birchpress;

		$errors = array();
		if ( !isset( $_POST['birs_appointment_staff'] ) ) {
			$errors['birs_appointment_staff'] = __( 'Please select a provider', 'birchschedule' );
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

	$ns->ajax_reschedule = function() use ( $ns ) {
		global $birchpress, $birchschedule;

		$errors = $ns->validate_reschedule_info();
		if ( $errors ) {
			$birchschedule->view->render_ajax_error_messages( $errors );
		}
		$appointment_info = array();
		$datetime = array(
			'date' => $_POST['birs_appointment_date'],
			'time' => $_POST['birs_appointment_time']
		);
		$datetime = $birchpress->util->get_wp_datetime( $datetime );
		$timestamp = $datetime->format( 'U' );
		$appointment_info['_birs_appointment_timestamp'] = $timestamp;
		$appointment_info['_birs_appointment_staff'] = $_POST['birs_appointment_staff'];
		$appointment_info['_birs_appointment_location'] = $_POST['birs_appointment_location'];
		$appointment_info['_birs_appointment_service'] = $_POST['birs_appointment_service'];
		$appointment_id = $_POST['birs_appointment_id'];
		$client_id = $_POST['birs_client_id'];
		$appointment1on1 = $birchschedule->model->booking->get_appointment1on1($appointment_id, $client_id);
		$birchschedule->model->booking->reschedule_appointment1on1( $appointment1on1['ID'], $appointment_info );
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
	};

	$ns->get_reschedule_html = function( $appointment_id, $client_id ) use ( $ns ) {
		ob_start();
?>
                <div style="overflow:hidden;">
                    <h4><?php _e( 'Reschedule', 'birchschedule' ); ?></h4>
                    <input type="hidden" name="birs_client_id" id="birs_client_id" value="<?php echo $client_id; ?>" />
                    <?php echo $ns->get_appointment_info_html( $appointment_id ); ?>
                    <?php echo $ns->get_actions_html(); ?>
                </div>
<?php
		return ob_get_clean();
	};

	$ns->get_actions_html = function() {
?>
                <ul>
                    <li class="birs_form_field">
                        <label>
                            &nbsp;
                        </label>
                        <div class="birs_field_content">
                            <input name="birs_appointment_reschedule"
                                id="birs_appointment_reschedule"
                                type="button" class="button-primary"
                                value="<?php _e( 'Reschedule', 'birchschedule' ); ?>" />
                            <a href="javascript:void(0);"
                                id="birs_appointment_reschedule_cancel"
                                style="padding: 4px 0 0 4px; display: inline-block;">
                                <?php _e( 'Cancel', 'birchschedule' ); ?>
                            </a>
                        </div>
                    </li>
                </ul>
<?php
	};

	$ns->get_appointment_info_html = function( $appointment_id ) use ( $ns, $birchschedule ) {
		global $birchpress, $birchschedule;

		$appointment = $birchschedule->model->get( $appointment_id, array(
			'base_keys' => array(),
			'meta_keys' => $birchschedule->model->get_appointment_fields()
		) );

		$options = $birchpress->util->get_time_options( 5 );
		if ( $appointment ) {
			$location_id = $appointment['_birs_appointment_location'];
			$service_id = $appointment['_birs_appointment_service'];
			$staff_id = $appointment['_birs_appointment_staff'];
			$timestamp = $birchpress->util->get_wp_datetime( $appointment['_birs_appointment_timestamp'] );
			$date4picker = $timestamp->format( get_option( 'date_format' ) );
			$date = $timestamp->format( 'm/d/Y' );
			$time = $timestamp->format( 'H' ) * 60 + $timestamp->format( 'i' );
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
                        <div class="birs_error" id="birs_appointment_location_error"></div>
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
                        <div class="birs_error" id="birs_appointment_service_error"></div>
                    </li>
                    <li class="birs_form_field">
                        <label>
                            <?php _e( 'Provider', 'birchschedule' ); ?>
                        </label>
                        <div class="birs_field_content">
                            <select id="birs_appointment_staff" name="birs_appointment_staff"
                                data-value="<?php echo $staff_id; ?>">
                            </select>
                        </div>
                        <div class="birs_error" id="birs_appointment_staff_error"></div>
                    </li>
                    <li class="birs_form_field">
                        <label>
                            <?php _e( 'Date', 'birchschedule' ); ?>
                        </label>
                        <div class="birs_field_content">
                            <div id="birs_appointment_datepicker"></div>
                            <input id="birs_appointment_date" name="birs_appointment_date" type="hidden" value="<?php echo $date ?>">
                        </div>
                        <div class="birs_error" id="birs_appointment_date_error"></div>
                    </li>
                    <li class="birs_form_field">
                        <label>
                            <?php _e( 'Time', 'birchschedule' ); ?>
                        </label>
                        <div class="birs_field_content">
                            <select id="birs_appointment_time" name="birs_appointment_time" size="1">
                                <?php $birchpress->util->render_html_options( $options, $time ); ?>
                            </select>
                        </div>
                        <div class="birs_error" id="birs_appointment_time_error"></div>
                    </li>
                </ul>
<?php
		return ob_get_clean();
	};

} );
