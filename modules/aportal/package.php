<?php

birch_ns( 'birchschedule.aportal', function( $ns ) {

		global $birchschedule;

		$_ns_data = new stdClass();

		$ns->init_data = function() use( $_ns_data ) {
			$_ns_data->SC_CANCEL_APPOINTMENT = 'bpscheduler_cancel_appointment';

			$_ns_data->SC_RESCHEDULE_APPOINTMENT = 'bpscheduler_reschedule_appointment';

			$_ns_data->current_appointment1on1 = null;
		};

		$ns->init = function() use( $ns ) {
			$ns->init_data();

			add_action( 'init', array( $ns, 'wp_init' ) );

			add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );

			add_action( 'birchschedule_view_get_shortcodes', array( $ns, 'add_shortcode' ), 20 );
		};

		$ns->wp_admin_init = function() use( $ns ) {
			add_action( 'birchschedule_bpreferences_render_booking_policies_after',
				array( $ns, 'render_rescheduling_policies' ), 25 );

			add_action( 'birchschedule_enotification_render_settings_client_after',
				array( $ns, 'render_reschedule_pages_settings' ), 25 );
		};

		$ns->wp_init = function() use( $ns, $_ns_data, $birchschedule ) {
			add_shortcode( $_ns_data->SC_CANCEL_APPOINTMENT,
				array( $ns, 'get_sc_cancel_appointment_content' ) );

			add_shortcode( $_ns_data->SC_RESCHEDULE_APPOINTMENT,
				array( $ns, 'get_sc_reschedule_appointment_content' ) );

			add_action( 'wp_ajax_nopriv_birchschedule_aportal_cancel_appointment',
				array( $ns, 'ajax_cancel_appointment' ) );

			add_action( 'wp_ajax_birchschedule_aportal_cancel_appointment',
				array( $ns, 'ajax_cancel_appointment' ) );

			add_action( 'wp_ajax_nopriv_birchschedule_aportal_get_avaliable_time_options',
				array( $ns, 'ajax_get_avaliable_time_options' ) );

			add_action( 'wp_ajax_birchschedule_aportal_get_avaliable_time_options',
				array( $ns, 'ajax_get_avaliable_time_options' ) );

			add_action( 'wp_ajax_nopriv_birchschedule_aportal_reschedule_appointment',
				array( $ns, 'ajax_reschedule_appointment' ) );

			add_action( 'wp_ajax_birchschedule_aportal_reschedule_appointment',
				array( $ns, 'ajax_reschedule_appointment' ) );

			add_filter( 'birchschedule_model_mergefields_get_appointment1on1_merge_values',
				array( $ns, 'add_merge_values' ), 10, 2 );

			add_filter( 'birchschedule_model_get_time_before_cancel',
				array( $ns, 'get_time_before_cancel' ) );

			add_filter( 'birchschedule_model_get_time_before_reschedule',
				array( $ns, 'get_time_before_reschedule' ) );

			$module_dir = $birchschedule->plugin_url() . '/modules/aportal/';
			$product_version = $birchschedule->get_product_version();
			wp_register_script(
				'birchschedule_aportal_cancel',
				$module_dir . 'assets/js/cancel.js',
				array( 'birchschedule_view' ), $product_version );
			wp_register_script(
				'birchschedule_aportal_reschedule',
				$module_dir . 'assets/js/reschedule.js',
				array( 'birchschedule_view', 'select2', 'jquery-ui-datepicker' ), $product_version );
		};

		$ns->enqueue_scripts_sc_cancel_appointment = function() use( $birchschedule ) {
			$birchschedule->view->register_3rd_scripts();
			$birchschedule->view->register_3rd_styles();
			$birchschedule->view->enqueue_scripts( 'birchschedule_aportal_cancel' );
		};

		$ns->enqueue_scripts_sc_reschedule_appointment = function() use( $birchschedule ) {
			$birchschedule->view->register_3rd_scripts();
			$birchschedule->view->register_3rd_styles();
			$birchschedule->view->enqueue_scripts( 'birchschedule_aportal_reschedule' );
			$birchschedule->view->enqueue_styles( 'select2' );
			$birchschedule->view->enqueue_styles( 'jquery-ui-bootstrap' );
		};

		$ns->add_shortcode = function( $shortcodes ) use( $_ns_data ) {
			$shortcodes[] = $_ns_data->SC_CANCEL_APPOINTMENT;
			$shortcodes[] = $_ns_data->SC_RESCHEDULE_APPOINTMENT;
			return $shortcodes;
		};

		$ns->add_merge_values = function( $appointment1on1, $appointment1on1_id )
		use( $ns, $_ns_data, $birchschedule ) {

			if ( !$appointment1on1 ) {
				return $appointment1on1;
			}
			$options = $birchschedule->enotification->get_options();
			$cancel_page = $options['client']['cancel_page'];
			$reschedule_page = $options['client']['reschedule_page'];
			$cancel_page_url = get_permalink( $cancel_page );
			if ( $cancel_page_url ) {
				$cancel_page_url = add_query_arg(
					array(
						'apt_key' => $ns->encode_appointment1on1_id( $appointment1on1_id )
					),
					$cancel_page_url
				);
				$appointment1on1['cancel_url'] = $cancel_page_url;
			}
			$reschedule_page_url = get_permalink( $reschedule_page );
			if ( $reschedule_page_url ) {
				$reschedule_page_url = add_query_arg(
					array(
						'apt_key' => $ns->encode_appointment1on1_id( $appointment1on1_id )
					),
					$reschedule_page_url
				);
				$appointment1on1['reschedule_url'] = $reschedule_page_url;
			}
			return $appointment1on1;
		};

		$ns->render_reschedule_pages_settings = function() use( $ns, $birchschedule, $_ns_data ) {
			global $birchpress;

			$pages = $birchpress->db->query(
				array(
					'post_type' => 'page',
					'orderby' => 'title',
					'order' => 'ASC'
				),
				array(
					'base_keys' => array( 'post_title' ),
					'meta_keys' => array()
				)
			);
			$pages_r = array(
				'-1' => ''
			);
			foreach ( $pages as $id => $page ) {
				$pages_r[$id] = $page['post_title'];
			}
			$options = $birchschedule->enotification->get_options();
			$cancel_page = $options['client']['cancel_page'];
			$reschedule_page = $options['client']['reschedule_page'];
?>
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label>
							<?php _e( 'Cancel Page', 'birchschedule' ); ?>
						</label>
					</th>
					<td>
						<select name="birchschedule_options_notification[client][cancel_page]">
							<?php $birchpress->util->render_html_options( $pages_r, $cancel_page ); ?>
						</select>
						<?php _e( 'This page should contain shortcode [bpscheduler_cancel_appointment].', 'birchschedule' ); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label>
							<?php _e( 'Reschedule Page', 'birchschedule' ); ?>
						</label>
					</th>
					<td>
						<select name="birchschedule_options_notification[client][reschedule_page]">
							<?php $birchpress->util->render_html_options( $pages_r, $reschedule_page ); ?>
						</select>
						<?php _e( 'This page should contain shortcode [bpscheduler_reschedule_appointment].', 'birchschedule' ); ?>
					</td>
				</tr>
			</tbody>
		</table>
<?php
		};

		$ns->get_time_before_cancel = function() use( $ns, $birchschedule ) {
			$booking_preferences = $birchschedule->bpreferences->get_booking_preferences();
			$time_before_cancel = $booking_preferences['time_before_cancel'];
			return $time_before_cancel;
		};

		$ns->get_time_before_reschedule = function() use( $ns, $birchschedule ) {
			$booking_preferences = $birchschedule->bpreferences->get_booking_preferences();
			$time_before_reschedule = $booking_preferences['time_before_reschedule'];
			return $time_before_reschedule;
		};

		$ns->get_times_before_cancel = function() use( $birchschedule ) {
			return $birchschedule->bpreferences->get_cut_off_times();
		};

		$ns->get_times_before_reschedule = function() use( $birchschedule ) {
			return $birchschedule->bpreferences->get_cut_off_times();
		};

		$ns->get_time_before_cancel_html = function() use( $ns, $birchschedule ) {
			global $birchpress;

			$booking_preferences = $birchschedule->bpreferences->get_booking_preferences();
			$time_before_cancel = $booking_preferences['time_before_cancel'];
			ob_start();
?>
		<select name="birchschedule_options[booking_preferences][time_before_cancel]">
			<?php
			$birchpress->util->render_html_options( $ns->get_times_before_cancel(),
				$time_before_cancel );
?>
		</select>
<?php
			return ob_get_clean();
		};

		$ns->get_time_before_reschedule_html = function() use( $ns, $birchschedule ) {
			global $birchpress;

			$booking_preferences = $birchschedule->bpreferences->get_booking_preferences();
			$time_before_reschedule = $booking_preferences['time_before_reschedule'];
			ob_start();
?>
		<select name="birchschedule_options[booking_preferences][time_before_reschedule]">
			<?php
			$birchpress->util->render_html_options( $ns->get_times_before_reschedule(),
				$time_before_reschedule );
?>
		</select>
<?php
			return ob_get_clean();
		};

		$ns->render_rescheduling_policies = function() use( $ns, $birchschedule ) {
?>
		<ul style="margin: 0;">
			<li>
				<?php printf( __( 'Clients can cancel appointments %s in advance.', 'birchschedule' ),
				$ns->get_time_before_cancel_html() ); ?>
			</li>
			<li>
				<?php printf( __( 'Clients can reschedule appointments %s in advance.', 'birchschedule' ),
				$ns->get_time_before_reschedule_html() ); ?>
			</li>
		</ul>
<?php
		};

		$ns->encode_appointment1on1_id = function( $appointment1on1_id ) use( $ns, $birchschedule ) {
			$appointment1on1 = $birchschedule->model->get(
				$appointment1on1_id,
				array(
					'base_keys' => array(),
					'meta_keys' => array( '_birs_appointment_id' )
				)
			);
			$appointment_id = $appointment1on1['_birs_appointment_id'];
			$appointment = $birchschedule->model->get( $appointment_id,
				array(
					'base_keys' => array(),
					'meta_keys' => array( '_birs_appointment_timestamp' )
				) );
			$timestamp = $appointment['_birs_appointment_timestamp'];
			return base64_encode( $appointment1on1_id . '-'. $timestamp );
		};

		$ns->decode_appointment1on1_id = function( $apt_key ) use( $ns, $birchschedule ) {
			$decoded = base64_decode( $apt_key );
			$decoded = explode( '-', $decoded );
			if ( isset( $decoded[0] ) ) {
				return $decoded[0];
			} else {
				return -1;
			}
		};

		$ns->get_sc_cancel_appointment_nonexist = function() use( $ns, $birchschedule ) {
			$i18n_messages = $birchschedule->view->get_frontend_i18n_messages();
			ob_start();
?>
		<p>
			<?php echo $i18n_messages["The appointment doesn't exist or has been cancelled."]; ?>
		</p>
<?php
			return ob_get_clean();
		};

		$ns->get_sc_cancel_appointment_styles = function() {
			ob_start();
?>
		<style type="text/css">
			#birs_appointment_details ul {
				list-style-type: none;
				margin: 0;
				padding: 0;
			}
			#birs_appointment_details .birs_form_field {
				margin: 0;
				padding: 6px 1% 9px 1%;
			}
			#birs_appointment_details .birs_form_field label {
				margin: 4px 0 4px 0;
				font-weight: bold;
			}
		</style>
<?php
			return ob_get_clean();
		};

		$ns->get_sc_cancel_appointment_confirm = function( $appointment1on1 )
		use( $ns, $birchschedule, $_ns_data ) {

			$apt_key = $ns->encode_appointment1on1_id( $appointment1on1['ID'] );
			$labels = $birchschedule->view->bookingform->get_fields_labels();
			$i18n_messages = $birchschedule->view->get_frontend_i18n_messages();
			ob_start();
			echo $ns->get_sc_cancel_appointment_styles();
?>
		<style type="text/css">
		<?php
			echo $birchschedule->view->get_custom_code_css( $_ns_data->SC_CANCEL_APPOINTMENT );
?>
		</style>
		<div id='birs_cancel_appointment'>
			<input type='hidden' name='birs_appointment_key' value='<?php echo $apt_key; ?>' />
			<p id='birs_confirm_message'>
				<?php echo $i18n_messages["Are you sure you want to cancel this appointment?"]; ?>
			</p>
			<div id='birs_appointment_details'>
				<ul>
					<li class="birs_form_field birs_appointment_location">
						<label><?php echo $labels['location'] ?></label>
						<div class="birs_field_content">
							<?php echo $appointment1on1['_birs_location_name']; ?>
						</div>
					</li>
					<li class="birs_form_field birs_appointment_service">
						<label><?php echo $labels['service']; ?></label>
						<div class="birs_field_content">
							<?php echo $appointment1on1['_birs_service_name']; ?>
						</div>
					</li>
					<li class="birs_form_field birs_appointment_staff">
						<label><?php echo $labels['service_provider']; ?></label>
						<div class="birs_field_content">
							<?php echo $appointment1on1['_birs_staff_name']; ?>
						</div>
					</li>
					<li class="birs_form_field birs_appointment_time">
						<label><?php echo $labels['time']; ?></label>
						<div class="birs_field_content">
							<?php echo $appointment1on1['_birs_appointment_datetime']; ?>
						</div>
					</li>
					<li class="birs_form_field birs_actions">
						<div class="birs_field_content">
							<input type='button' id='birs_cancel_appointment_yes' value='<?php echo __( 'Yes, cancel this appointment', 'birchschedule' ); ?>' />
						</div>
					</li>
				</ul>
			</div>
		</div>
<?php
			return ob_get_clean();
		};

		$ns->get_sc_cancel_appointment_success = function() use( $ns, $birchschedule ) {
			$i18n_messages = $birchschedule->view->get_frontend_i18n_messages();
			ob_start();
?>
		<p>
			<?php echo $i18n_messages['Your appointment has been cancelled successfully.']; ?>
		</p>
<?php
			return ob_get_clean();
		};

		$ns->get_sc_cancel_appointment_outoftime = function() use( $ns, $birchschedule ) {
			$i18n_messages = $birchschedule->view->get_frontend_i18n_messages();
			ob_start();
?>
		<p>
			<?php echo $i18n_messages['Your appointment can not be cancelled now according to our booking policies.']; ?>
		</p>
<?php
			return ob_get_clean();
		};

		$ns->get_sc_cancel_appointment_content = function( $attr ) use( $ns, $birchschedule ) {
			global $birchpress;

			$ns->enqueue_scripts_sc_cancel_appointment();
			$appointment_nonexist = $ns->get_sc_cancel_appointment_nonexist();
			if ( !isset( $_REQUEST['apt_key'] ) ) {
				return $appointment_nonexist;
			}
			$apt_key = $_REQUEST['apt_key'];
			$appointment1on1_id = $ns->decode_appointment1on1_id( $apt_key );
			$appointment1on1 = $birchschedule->model->get(
				$appointment1on1_id,
				array(
					'base_keys' => array( 'post_status' ),
					'meta_keys' => array( '_birs_appointment_id' )
				)
			);
			if ( !$appointment1on1 || 'publish' != $appointment1on1['post_status'] ) {
				return $appointment_nonexist;
			}
			$appointment_id = $appointment1on1['_birs_appointment_id'];
			$can_cancel = $birchschedule->model->booking->if_cancel_appointment_outoftime( $appointment_id );
			if ( !$birchpress->util->is_error( $can_cancel ) && !$can_cancel ) {
				return $ns->get_sc_cancel_appointment_outoftime();
			}
			$appointment1on1 = $ns->get_appointment1on1_merge_values( $appointment1on1['ID'] );
			return $ns->get_sc_cancel_appointment_confirm( $appointment1on1 );
		};

		$ns->ajax_cancel_appointment = function() use( $ns, $birchschedule ) {
			global $birchpress;

			$errors = array();
			$appointment_nonexist = $ns->get_sc_cancel_appointment_nonexist();
			if ( !isset( $_REQUEST['apt_key'] ) ) {
				$errors['appointment_nonexist'] = $appointment_nonexist;
				$birchschedule->view->render_ajax_error_messages( $errors );
			}
			$apt_key = $_REQUEST['apt_key'];
			$config = array(
				'meta_keys' => array(),
				'base_keys' => array()
			);
			$appointment1on1_id = $ns->decode_appointment1on1_id( $apt_key );
			$appointment1on1 = $birchschedule->model->get( $appointment1on1_id, $config );
			if ( !$appointment1on1 ) {
				$errors['appointment_nonexist'] = $appointment_nonexist;
				$birchschedule->view->render_ajax_error_messages( $errors );
			}
			$result = $birchschedule->model->booking->cancel_appointment1on1( $appointment1on1_id );
			$success_message= $ns->get_sc_cancel_appointment_success();
			if ( $birchpress->util->is_error( $result ) ) {
				$errors['appointment_nonexist'] = $appointment_nonexist;
				$birchschedule->view->render_ajax_error_messages( $errors );
			} else {
				$success = array(
					'code' => 'cancelled',
					'message' => $success_message
				);
				$birchschedule->view->render_ajax_success_message( $success );
			}
		};

		$ns->get_sc_reschedule_appointment_styles = function() use( $ns, $birchschedule ) {
			ob_start();
?>
		<style type="text/css">
			#birs_appointment_form ul {
				list-style-type: none;
				margin: 0;
				padding: 0;
			}
			#birs_appointment_form .birs_form_field {
				margin: 0;
				padding: 6px 1% 9px 1%;
			}
			#birs_appointment_form .birs_form_field label {
				margin: 4px 0 4px 0;
				font-weight: bold;
			}
			#birs_appointment_form .birs_field_content {
				width: 100%;
				max-width: 17em;
			}
			#birs_appointment_form .birs_form_field input[type=text],
			#birs_appointment_form .birs_form_field select {
				width: 100%;
			}
			#birs_appointment_form .birs_error {
				color: red;
			}
			#birs_time_waiting {
				display: none;
			}
		</style>
<?php
			return ob_get_clean();
		};

		$ns->remove_current_appointment = function( $appointments ) use( $ns, $_ns_data ) {

			$appointment1on1 = $_ns_data->current_appointment1on1;
			if ( isset( $appointments[$appointment1on1['_birs_appointment_id']] ) ) {
				unset( $appointments[$appointment1on1['_birs_appointment_id']] );
			}
			return $appointments;
		};

		$ns->get_avaliable_time_options = function( $staff_id, $location_id, $service_id, $date )
		use( $ns, $birchschedule, $_ns_data ) {

			global $birchpress;

			add_filter( 'birchschedule_model_booking_query_appointments', array( $ns, 'remove_current_appointment' ) );
			$time_options = $birchschedule->model->schedule->
			get_staff_avaliable_time( $staff_id, $location_id, $service_id, $date );
			remove_filter( 'birchschedule_model_booking_query_appointments', array( $ns, 'remove_current_appointment' ) );
			$datetime = $birchpress->util->get_wp_datetime( $_ns_data->current_appointment1on1['_birs_appointment_timestamp'] );
			$time = $datetime->format( 'H' ) * 60 + $datetime->format( 'i' );
			$time_options_r = array();
			foreach ( $time_options as $key => $time_option ) {
				if ( $time_option['avaliable'] ) {
					$time_options_r[$key] = $time_option['text'];
				}
			}
			ob_start();
			$birchpress->util->render_html_options( $time_options_r, $time );
			return ob_get_clean();
		};

		$ns->ajax_get_avaliable_time_options = function() use( $ns, $birchschedule, $_ns_data ) {
			global $birchpress;

			$apt_key = $_POST['apt_key'];
			$appointment1on1_id = $ns->decode_appointment1on1_id( $apt_key );
			$_ns_data->current_appointment1on1 = $ns->get_appointment1on1_merge_values( $appointment1on1_id );
			$staff_id = $_POST['birs_appointment_staff'];
			$location_id = $_POST['birs_appointment_location'];
			$service_id = $_POST['birs_appointment_service'];
			$date = $_POST['birs_appointment_date'];
			$time = 0;
			$datetime = $birchpress->util->get_wp_datetime( array(
					'date' => $date,
					'time' => $time
				) );
			echo $ns->get_avaliable_time_options( $staff_id, $location_id, $service_id, $datetime );
			die;
		};

		$ns->get_sc_reschedule_appointment_nonexist = function() use( $ns ) {
			return $ns->get_sc_cancel_appointment_nonexist();
		};

		$ns->get_sc_reschedule_appointment_success = function() use( $ns, $birchschedule ) {
			$i18n_messages = $birchschedule->view->get_frontend_i18n_messages();
			ob_start();
?>
			<p>
				<?php echo $i18n_messages['Your appointment has been rescheduled successfully.']; ?>
			</p>
	<?php
			return ob_get_clean();
		};

		$ns->get_sc_reschedule_appointment_confirm = function( $appointment1on1 ) use( $ns, $birchschedule, $_ns_data ) {
			global $birchpress;

			$datetime = $birchpress->util->get_wp_datetime( $appointment1on1['_birs_appointment_timestamp'] );
			$date = $datetime->format( 'm/d/Y' );
			$apt_key = $ns->encode_appointment1on1_id( $appointment1on1['ID'] );
			$labels = $birchschedule->view->bookingform->get_fields_labels();
			$_ns_data->current_appointment1on1 = $appointment1on1;
			$date_0 = $birchpress->util->get_wp_datetime(
				array(
					'date' => $date,
					'time' => 0
				)
			);
			$time_options = $ns->get_avaliable_time_options( $appointment1on1['_birs_appointment_staff'],
				$appointment1on1['_birs_appointment_location'],
				$appointment1on1['_birs_appointment_service'],
				$date_0 );
			ob_start();
			echo $ns->get_sc_reschedule_appointment_styles();
?>
			<style type="text/css">
			<?php
			echo $birchschedule->view->get_custom_code_css( $_ns_data->SC_RESCHEDULE_APPOINTMENT );
?>
			</style>
			<div id='birs_reschedule_appointment'>
				<input type='hidden' name='birs_appointment_key' value='<?php echo $apt_key; ?>' />
				<div id='birs_appointment_form'>
					<ul>
						<li class="birs_form_field birs_appointment_location">
							<label>
								<?php echo $labels['location']; ?>
							</label>
							<div class="birs_field_content">
								<?php echo $appointment1on1['_birs_location_name']; ?>
								<input id="birs_appointment_location" name="birs_appointment_location" type='hidden' value='<?php echo $appointment1on1['_birs_appointment_location']; ?>'
							</div>
						</li>
						<li class="birs_form_field birs_appointment_service">
							<label>
								<?php echo $labels['service']; ?>
							</label>
							<div class="birs_field_content">
								<?php echo $appointment1on1['_birs_service_name']; ?>
								<input id="birs_appointment_service" name="birs_appointment_service" type='hidden' value='<?php echo $appointment1on1['_birs_appointment_service']; ?>'
							</div>
						</li>
						<li class="birs_form_field birs_appointment_staff">
							<label>
								<?php echo $labels['service_provider']; ?>
							</label>
							<div class="birs_field_content">
								<?php echo $appointment1on1['_birs_staff_name']; ?>
								<input id="birs_appointment_staff" name="birs_appointment_staff" type='hidden' value='<?php echo $appointment1on1['_birs_appointment_staff']; ?>'
							</div>
						</li>
						<li class="birs_form_field birs_appointment_date">
							<label>
								<?php echo $labels['date']; ?>
							</label>
							<div class="birs_field_content">
								<input id="birs_appointment_datepicker" type="text" name="birs_appointment_datepicker" readonly="readonly">
								<input id="birs_appointment_date" name="birs_appointment_date" type="hidden" value="<?php echo $date; ?>">
								<div class="birs_error" id="birs_appointment_date_error"></div>
							</div>
						</li>
						<li class="birs_form_field birs_appointment_time">
							<label>
								<?php echo $labels['time']; ?>
							</label>
							<div class="birs_field_content">
								<img id="birs_time_waiting" src="<?php echo $birchschedule->plugin_url() . '/assets/images/ajax-loader.gif' ?>" >
								<select id="birs_appointment_time" name="birs_appointment_time">
									<?php echo $time_options; ?>
								</select>
								<div class="birs_error" id="birs_appointment_time_error"></div>
							</div>
						</li>
						<li class="birs_form_field birs_actions">
							<div class="birs_field_content">
								<input type='button' id='birs_reschedule_appointment_submit' value='<?php echo __( 'Reschedule', 'birchschedule' ); ?>' />
							</div>
						</li>
					</ul>
				</div>
			</div>
	<?php
			return ob_get_clean();
		};

		$ns->get_sc_reschedule_appointment_outoftime = function() use( $ns, $birchschedule ) {
			$i18n_messages = $birchschedule->view->get_frontend_i18n_messages();
			ob_start();
?>
			<p>
				<?php echo $i18n_messages['Your appointment can not be rescheduled now according to our booking policies.']; ?>
			</p>
	<?php
			return ob_get_clean();
		};

		$ns->get_sc_reschedule_appointment_content = function( $attr ) use( $ns, $birchschedule, $_ns_data ) {
			global $birchpress;

			$ns->enqueue_scripts_sc_reschedule_appointment();
			$appointment_nonexist = $ns->get_sc_cancel_appointment_nonexist();
			if ( !isset( $_REQUEST['apt_key'] ) ) {
				return $appointment_nonexist;
			}
			$apt_key = $_REQUEST['apt_key'];
			$config = array(
				'meta_keys' => array(
					'_birs_appointment_id'
				),
				'base_keys' => array(
					'post_status',
				),
			);
			$appointment1on1_id = $ns->decode_appointment1on1_id( $apt_key );
			$appointment1on1 = $birchschedule->model->get( $appointment1on1_id, $config );
			if ( !$appointment1on1 || 'publish' != $appointment1on1['post_status'] ) {
				return $appointment_nonexist;
			}
			$can_reschedule =
			$birchschedule->model->booking->if_reschedule_appointment_outoftime(
				$appointment1on1['_birs_appointment_id']
			);
			if ( !$birchpress->util->is_error( $can_reschedule ) && !$can_reschedule ) {
				return $ns->get_sc_reschedule_appointment_outoftime();
			}
			$appointment1on1 = $ns->get_appointment1on1_merge_values( $appointment1on1['ID'] );
			return $ns->get_sc_reschedule_appointment_confirm( $appointment1on1 );
		};

		$ns->get_appointment1on1_merge_values = function( $appointment1on1_id ) use( $ns, $birchschedule ) {
			return $birchschedule->model->mergefields->get_appointment1on1_merge_values( $appointment1on1_id );
		};

		$ns->validate_reschedule_info = function() use( $ns ) {
			$errors = array();
			if ( !isset( $_POST['birs_appointment_date'] ) || !$_POST['birs_appointment_date'] ) {
				$errors['required:birs_appointment_date'] = __( 'Date is required', 'birchschedule' );
			}
			if ( !isset( $_POST['birs_appointment_time'] ) || !$_POST['birs_appointment_time'] ) {
				$errors['required:birs_appointment_time'] = __( 'Time is required', 'birchschedule' );
			}
			if ( !isset( $_POST['apt_key'] ) ) {
				$errors['appointment_nonexist'] = $ns->get_sc_reschedule_appointment_nonexist();
			}
			return $errors;
		};

		$ns->ajax_reschedule_appointment = function() use( $ns, $birchschedule, $_ns_data ) {
			global $birchpress;

			$errors = $ns->validate_reschedule_info();
			if ( $errors ) {
				$birchschedule->view->render_ajax_error_messages( $errors );
			}
			$apt_key = $_POST['apt_key'];
			$config = array(
				'meta_keys' => array(
					'_birs_appointment_date',
					'_birs_appointment_time'
				),
				'base_keys' => array()
			);
			$appointment1on1_id = $ns->decode_appointment1on1_id( $apt_key );
			$appointment1on1 = $birchschedule->model->get( $appointment1on1_id, $config );
			if ( !$appointment1on1 ) {
				$errors['appointment_nonexist'] = $ns->get_sc_reschedule_appointment_nonexist();
				$birchschedule->view->render_ajax_error_messages( $errors );
			}
			$date = $_POST['birs_appointment_date'];
			$time = $_POST['birs_appointment_time'];
			$datetime = $birchpress->util->get_wp_datetime( array(
					'date' => $date,
					'time' => $time
				) );
			$appointment1on1['_birs_appointment_timestamp'] = $datetime->format( 'U' );
			$birchschedule->model->booking->reschedule_appointment1on1( $appointment1on1_id, $appointment1on1 );
			$success = array(
				'code' => 'rescheduled',
				'message' => $ns->get_sc_reschedule_appointment_success()
			);
			$birchschedule->view->render_ajax_success_message( $success );
		};

	} );
