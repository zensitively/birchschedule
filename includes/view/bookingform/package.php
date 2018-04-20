<?php

birch_ns( 'birchschedule.view.bookingform', function( $ns ) {

		$_ns_data = new stdClass();

		$ns->init = function() use ( $ns ) {
			$ns->init_data();

			add_action( 'init', array( $ns, 'wp_init' ) );

			add_filter( 'birchschedule_view_get_shortcodes', array( $ns, 'add_shortcode' ) );
		};

		$ns->init_data = function() use ( $_ns_data ) {
			$_ns_data->SC_BOOKING_FORM_LEGACY = 'bp-scheduler-bookingform';

			$_ns_data->SC_BOOKING_FORM = 'bpscheduler_booking_form';

			$_ns_data->temp_data = array();

			$_ns_data->sc_attrs = false;
		};

		$ns->wp_init = function() use ( $ns, $_ns_data ) {
			global $birchschedule, $birchpress;

			add_shortcode( $_ns_data->SC_BOOKING_FORM, array( $ns, 'get_shortcode_content' ) );

			add_shortcode( $_ns_data->SC_BOOKING_FORM_LEGACY, array( $ns, 'get_shortcode_content' ) );

			add_filter( 'the_content', array( $ns, 'replace_shortcode_id_with_html' ), 1000 );

			add_filter( 'widget_text', 'do_shortcode', 11 );

			add_filter( 'widget_text', array( $ns, 'replace_shortcode_id_with_html' ), 1000 );

			add_action( 'wp_ajax_nopriv_birchschedule_view_bookingform_schedule',
				array( $ns, 'ajax_schedule' ) );

			add_action( 'wp_ajax_birchschedule_view_bookingform_schedule',
				array( $ns, 'ajax_schedule' ) );

			add_action( 'wp_ajax_nopriv_birchschedule_view_bookingform_get_avaliable_time',
				array( $ns, 'ajax_get_avaliable_time' ) );

			add_action( 'wp_ajax_birchschedule_view_bookingform_get_avaliable_time',
				array( $ns, 'ajax_get_avaliable_time' ) );

			$birchschedule->view->register_script_data_fn(
				'birchschedule_view_bookingform', 'birchschedule_view_bookingform',
				array( $ns, 'get_script_data_fn_view_bookingform' ) );

			$birchschedule->view->register_script_data_fn(
				'birchschedule_view_bookingform', 'birchschedule_view_bookingform_sc_attrs',
				array( $ns, 'get_script_data_fn_view_bookingform_sc_attrs' ) );

			if ( $ns->is_time_slots_select_field() ) {
				add_action( 'birchschedule_view_bookingform_ajax_get_avaliable_time_before',
					array( $ns, 'ajax_get_avaliable_time2' ), 20 );
			}
		};

		$ns->get_script_data_fn_view_bookingform = function() use ( $ns ) {
			global $birchschedule, $birchpress;

			return array(
				'services_prices_map' => $ns->get_services_prices_map(),
				'services_duration_map' => $ns->get_services_duration_map(),
				'services_staff_map' => $ns->get_services_staff_map(),
				'locations_map' => $ns->get_locations_map(),
				'locations_staff_map' => $ns->get_locations_staff_map(),
				'locations_services_map' => $ns->get_locations_services_map(),
				'staff_order' => $ns->get_staff_listing_order(),
				'services_order' => $ns->get_services_listing_order(),
				'locations_order' => $ns->get_locations_listing_order(),
			);
		};

		$ns->is_sc_attrs_empty = function() use ( $ns, $_ns_data ) {
			return !$_ns_data->sc_attrs;
		};

		$ns->set_sc_attrs = function( $attrs ) use ( $ns, $_ns_data ) {
			$_ns_data->sc_attrs = $attrs;
		};

		$ns->get_script_data_fn_view_bookingform_sc_attrs = function() use ( $ns, $_ns_data ) {
			return $_ns_data->sc_attrs;
		};

		$ns->add_shortcode = function( $shortcodes ) use ( $ns, $_ns_data ) {
			$shortcodes[] = $_ns_data->SC_BOOKING_FORM;
			return $shortcodes;
		};

		$ns->enqueue_scripts = function() {
			global $birchschedule, $birchpress;

			$birchschedule->view->enqueue_scripts(
				array(
					'birchschedule_view_bookingform'
				)
			);
		};

		$ns->enqueue_styles = function() {
			global $birchschedule;

			$birchschedule->view->enqueue_styles( 'birchschedule_bookingform' );
		};

		$ns->get_all_schedule = function() {
			global $birchschedule;

			return $birchschedule->model->schedule->get_all_calculated_schedule();
		};

		$ns->get_all_daysoff = function() {
			global $birchschedule;

			return $birchschedule->model->get_all_daysoff();
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
				$errors['birs_client_name_first'] = __( 'First name is required', 'birchschedule' );
			}
			if ( !$_POST['birs_client_name_last'] ) {
				$errors['birs_client_name_last'] = __( 'Last name is required', 'birchschedule' );
			}
			if ( !$_POST['birs_client_email'] ) {
				$errors['birs_client_email'] = __( 'Email is required', 'birchschedule' );
			} else if ( !is_email( $_POST['birs_client_email'] ) ) {
				$errors['birs_client_email'] = __( 'Email is incorrect', 'birchschedule' );
			}
			if ( !$_POST['birs_client_phone'] ) {
				$errors['birs_client_phone'] = __( 'Phone is required', 'birchschedule' );
			}

			return $errors;
		};

		$ns->validate_booking_time = function() {
			global $birchschedule;

			$errors = array();
			if ( !isset( $_POST['birs_appointment_time'] ) || !$_POST['birs_appointment_time'] ) {
				$errors['birs_appointment_time'] = __( 'Time is required', 'birchschedule' );
				return $errors;
			}
			$avaliable_times = $birchschedule->view->bookingform->get_avaliable_time();
			$time = $_POST['birs_appointment_time'];
			$valid = array_key_exists( $time, $avaliable_times ) && $avaliable_times[$time]['avaliable'];
			if ( !$valid ) {
				$errors = array_merge(
					array(
						'birs_appointment_time' => __( 'Time is unavailable', 'birchschedule'
						) ), $errors );
			}
			return $errors;
		};

		$ns->validate_appointment1on1_info = function() {
			return array();
		};

		$ns->validate_booking_info = function() {
			global $birchschedule;

			$appointment_errors = $birchschedule->view->bookingform->validate_appointment_info();
			$client_errors = $birchschedule->view->bookingform->validate_client_info();
			$appointment1on1_errors = $birchschedule->view->bookingform->validate_appointment1on1_info();

			return array_merge( $appointment_errors, $client_errors, $appointment1on1_errors );
		};

		$ns->validate_recaptcha = function() {
			$errors = array();
			if( !is_user_logged_in() && function_exists( 'gglcptch_check' ) ) {
				$result = gglcptch_check();
				if( !$result['response'] ) {
					$errors['birs_booking'] = __( 'reCAPTCHA is incorrect', 'birchschedule' );
				}
			}
			return $errors;
		};

		$ns->validate_wp_nonce = function() {
			return check_ajax_referer( "birs_save_appointment-0", '_wpnonce', false );
		};

		$ns->get_avaliable_time = function() {
			global $birchschedule, $birchpress;

			$staff_id = $_POST['birs_appointment_staff'];
			$location_id = $_POST['birs_appointment_location'];
			$service_id = $_POST['birs_appointment_service'];
			$date_text = $_POST['birs_appointment_date'];
			$date = $birchpress->util->get_wp_datetime(
				array(
					'date' => $date_text,
					'time' => 0
				)
			);

			$time_options = $birchschedule->model->schedule->get_staff_avaliable_time( $staff_id, $location_id,
				$service_id, $date );

			return $time_options;
		};

		$ns->get_success_message = function( $appointment1on1_id ) {
			global $birchschedule;

			$appointment1on1 =
			$birchschedule->model->mergefields->get_appointment1on1_merge_values( $appointment1on1_id );
			$duration_str = __( 'Duration', 'birchschedule' ) . __( 'mins', 'birchschedule' );
			ob_start();
?>
        <h3><?php _e( 'Your appointment has been booked successfully.', 'birchschedule' ); ?></h3>
        <div>
            <ul>
                <li>
                    <h4><?php _e( 'Location', 'birchschedule' ); ?></h4>
                    <p><?php echo $appointment1on1['_birs_location_name']; ?></p>
                </li>
                <li>
                    <h4><?php _e( 'Service', 'birchschedule' ); ?></h4>
                    <p><?php echo $appointment1on1['_birs_service_name']; ?></p>
                </li>
                <li>
                    <h4><?php _e( 'Time', 'birchschedule' ); ?></h4>
                    <p><?php echo $appointment1on1['_birs_appointment_datetime']; ?></p>
                </li>
            </ul>
        </div>
        <?php
			$message = ob_get_clean();
			$success = array(
				'code' => 'text',
				'message' => $message
			);
			return $success;
		};

		$ns->save_client = function() {
			global $birchschedule;

			$client_config = array(
				'base_keys' => array(),
				'meta_keys' => $_POST['birs_client_fields']
			);
			$client_info = $birchschedule->view->merge_request( array(), $client_config, $_POST );
			unset( $client_info['ID'] );
			$client_id = $birchschedule->model->booking->save_client( $client_info );
			return $client_id;
		};

		$ns->schedule = function() use ( $ns ) {
			global $birchpress, $birchschedule;

			$client_id = $ns->save_client();
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
			$ns->change_appointment1on1_status( $appointment1on1_id );
			return $appointment1on1_id;
		};

		$ns->change_appointment1on1_status = function( $appointment1on1_id ) {
			global $birchschedule;

			$birchschedule->model->booking->change_appointment1on1_status( $appointment1on1_id, 'publish' );
		};

		$ns->ajax_schedule = function() use ( $ns ) {
			global $birchschedule;

			$ns = $birchschedule->view->bookingform;
			$permitted = $ns->validate_wp_nonce();
			$appointment_id = 0;
			if ( $permitted ) {
				$errors = $ns->validate_booking_info();
				if ( $errors ) {
					$birchschedule->view->render_ajax_error_messages( $errors );
				} else {
					$appointment1on1_id = $ns->schedule();
					$success = $ns->get_success_message( $appointment1on1_id );
					$birchschedule->view->render_ajax_success_message( $success );
				}
			} else {
				$errors = array(
					'birs_booking' => __( 'Please refresh the page and book again.', 'birchschedule' )
				);
				$birchschedule->view->render_ajax_error_messages( $errors );
			}
		};

		$ns->is_time_slots_select_field = function() {
			return false;
		};

		$ns->ajax_get_avaliable_time = function() use ( $ns ) {
			global $birchschedule;

			$ns = $birchschedule->view->bookingform;
			$i18n_messages = $birchschedule->view->get_frontend_i18n_messages();
?>
        <input id="birs_appointment_time" name="birs_appointment_time" type="hidden" />
        <div id="birs_appointment_timeoptions">
<?php
			$time_options = $ns->get_avaliable_time();
			$empty = true;
			foreach ( $time_options as $key => $value ) {
				if ( $value['avaliable'] ) {
					$text = $value['text'];
					$alternative_staff = '';
					if ( isset( $value['alternative_staff'] ) ) {
						$alternative_staff = implode( ',', $value['alternative_staff'] );
					}
?>
                <span><a class='birs_option'
                        data-time='<?php echo $key; ?>'
                        data-alternative-staff="<?php echo $alternative_staff; ?>"
                        href='javascript:void(0);'><?php echo $text; ?></a></span>
<?php
					$empty = false;
				}
			}
			if ( $empty ) {
				echo "<p>" . $i18n_messages['There are no available times.'] . "</p>";
			}
?>
        </div>
<?php
			exit;
		};

		$ns->ajax_get_avaliable_time2 = function() {
			global $birchschedule;

			$ns = $birchschedule->view->bookingform;
			$i18n_messages = $birchschedule->view->get_frontend_i18n_messages();
?>
        <select id="birs_appointment_timeoptions">
            <option class='birs_option'
                      data-time=''
                      data-alternative-staff="" ><?php _e( 'Please select time...', 'birchschedule' ); ?></option>
<?php
			$time_options = $ns->get_avaliable_time();
			foreach ( $time_options as $key => $value ) {
				if ( $value['avaliable'] ) {
					$text = $value['text'];
					$alternative_staff = '';
					if ( isset( $value['alternative_staff'] ) ) {
						$alternative_staff = implode( ',', $value['alternative_staff'] );
					}
?>
            <option class='birs_option'
                      data-time='<?php echo $key; ?>'
                      data-alternative-staff="<?php echo $alternative_staff; ?>" ><?php echo $text; ?></option>
<?php
				}
			}
?>
        </select>
        <input id="birs_appointment_time" name="birs_appointment_time" type="hidden" />
<?php
			exit;
		};

		$ns->replace_shortcode_id_with_html = function( $content ) use ( $ns, $_ns_data ) {
			global $birchschedule;

			if ( !isset( $_ns_data->temp_data['shortcodes'] ) ) {
				return $content;
			}
			$shortcodes = $_ns_data->temp_data['shortcodes'];
			foreach ( $shortcodes as $uid => $html ) {
				$content = str_replace( $uid, $html, $content );
			}
			return $content;
		};

		$ns->get_shortcode_html = function( $attr ) use ( $ns ) {
			global $birchschedule, $birchpress;

			ob_start();
?>
                <div class="birchschedule" id="birs_booking_box">
                    <form id="birs_appointment_form">
                        <input type="hidden" id="birs_appointment_price" name="birs_appointment_price" />
                        <input type="hidden" id="birs_appointment_duration" name="birs_appointment_duration" />
                        <input type="hidden" id="birs_appointment_alternative_staff" name="birs_appointment_alternative_staff" value="" />
                        <input type="hidden" id="birs_shortcode_page_url" name="birs_shortcode_page_url" value="<?php echo esc_attr( $birchpress->util->current_page_url() ); ?>" />
                        <div>
                            <?php wp_nonce_field( "birs_save_appointment-0" ); ?>
                            <?php echo $birchschedule->view->bookingform->get_fields_html(); ?>
                        </div>
                    </form>
                </div>
                <div id="birs_booking_success">
                </div>
<?php
			$html = ob_get_clean();
			$html = $ns->minify_html( $html );
			return $html;
		};

		$ns->minify_html = function( $buffer ) {
			$search = array(
				'/\>[^\S ]+/s',  // strip whitespaces after tags, except space
				'/[^\S ]+\</s',  // strip whitespaces before tags, except space
				'/(\s)+/s',       // shorten multiple whitespace sequences
				'/\>\s+\</s'
			);

			$replace = array(
				'>',
				'<',
				'\\1',
				'><'
			);

			$buffer = preg_replace( $search, $replace, $buffer );

			return $buffer;
		};

		$ns->get_shortcode_uid = function( $attr ) use ( $ns, $_ns_data ) {
			global $birchschedule;

			$uid = 'birchpress_shortcode_' . uniqid();
			$html = $birchschedule->view->bookingform->get_shortcode_html( $attr );
			$_ns_data->temp_data['shortcodes'][$uid] = $html;

			return $uid;
		};

		$ns->get_shortcode_attrs = function( $attr ) {
			return array();
		};

		$ns->get_thankyou_content = function( $appointment1on1_id ) {
			global $birchschedule;

			$appointment1on1 =
			$birchschedule->model->mergefields->get_appointment1on1_merge_values( $appointment1on1_id );
			if ( !$appointment1on1 ) {
				return '';
			}
			if ( $appointment1on1['post_status'] != 'publish' ) {
				return "<p>" . __( 'Your appointment has not been booked successfully due to some errors.', 'birchschedule' ) . '</p>';
			}
			ob_start();
?>
        <div id="birs_booking_success" style="display:block;">
            <h3><?php _e( 'Your appointment has been booked successfully.', 'birchschedule' ); ?></h3>
            <div>
                <ul>
                    <li>
                        <h4><?php _e( 'Location', 'birchschedule' ); ?></h4>
                        <p><?php echo $appointment1on1['_birs_location_name']; ?></p>
                    </li>
                    <li>
                        <h4><?php _e( 'Service', 'birchschedule' ); ?></h4>
                        <p><?php echo $appointment1on1['_birs_service_name']; ?></p>
                    </li>
                    <li>
                        <h4><?php _e( 'Time', 'birchschedule' ); ?></h4>
                        <p><?php echo $appointment1on1['_birs_appointment_datetime']; ?></p>
                    </li>
                </ul>
            </div>
        </div>
<?php
			return ob_get_clean();
		};

		$ns->get_shortcode_content = function( $attr ) use( $ns, $_ns_data ) {
			global $birchschedule;

			$a = $birchschedule->view->bookingform->get_shortcode_attrs( $attr );
			if ( $ns->is_sc_attrs_empty() ) {
				$ns->set_sc_attrs( $a );
			}
			$birchschedule->view->register_3rd_styles();
			$birchschedule->view->bookingform->enqueue_styles();
			$birchschedule->view->register_3rd_scripts();
			$birchschedule->view->bookingform->enqueue_scripts();
			ob_start();
?>
        <style type="text/css">
<?php
			echo $birchschedule->view->get_custom_code_css( $_ns_data->SC_BOOKING_FORM );
?>
        </style>
<?php
			$content = ob_get_clean();
			if ( isset( $_GET['thankyou'] ) && $_GET['thankyou'] == 'yes' &&
				isset( $_GET['apt1on1_id'] ) ) {

				return $content . $ns->get_thankyou_content( $_GET['apt1on1_id'] );
			}
			if ( isset( $a['nowrap'] ) && $a['nowrap'] == 'yes' ) {
				return $content . $ns->get_shortcode_uid( $a );
			} else {
				return $content . $birchschedule->view->bookingform->get_shortcode_html( $a );
			}
		};

		$ns->get_fields_labels = function() {
			return array(
				'location' => __( 'Location', 'birchschedule' ),
				'service' => __( 'Service', 'birchschedule' ),
				'service_provider' => __( 'Provider', 'birchschedule' ),
				'date' => __( 'Date', 'birchschedule' ),
				'time' => __( 'Time', 'birchschedule' ),
				'appointment_notes' => __( 'Notes', 'birchschedule' ),
				'client_name_first' => __( 'First Name', 'birchschedule' ),
				'client_name_last' => __( 'Last Name', 'birchschedule' ),
				'client_email' => __( 'Email', 'birchschedule' ),
				'client_phone' => __( 'Phone', 'birchschedule' )
			);
		};

		$ns->get_fields_html = function() {
			global $birchschedule;

			$labels = $birchschedule->view->bookingform->get_fields_labels();
			ob_start();
?>
                <ul>
                <li class="birs_form_field birs_appointment_section">
                    <h2 class="birs_section"><?php _e( 'Appointment Info', 'birchschedule' ); ?></h2>
                </li>
                <li class="birs_form_field birs_appointment_location">
                    <label><?php echo $labels['location']; ?></label>
                    <div class="birs_field_content">
                        <select id="birs_appointment_location" name="birs_appointment_location">
                        </select>
                    </div>
                </li>
                <li class="birs_form_field birs_appointment_service">
                    <label><?php echo $labels['service']; ?></label>
                    <div class="birs_field_content">
                        <select id="birs_appointment_service" name="birs_appointment_service">
                        </select>
                    </div>
                </li>
                <li class="birs_form_field birs_appointment_staff">
                    <label><?php echo $labels['service_provider']; ?></label>
                    <div class="birs_field_content">
                        <select id="birs_appointment_staff" name="birs_appointment_staff">
                        </select>
                        <input type="hidden" id="birs_appointment_avaliable_staff" name="birs_appointment_avaliable_staff" />
                    </div>
                    <div class="birs_error" id="birs_appointment_service_error"></div>
                </li>
                <li class="birs_form_field birs_appointment_date">
                    <label><?php echo $labels['date']; ?></label>
                    <input id="birs_appointment_date" name="birs_appointment_date" type="hidden">
                    <div  class="birs_field_content">
                        <div id="birs_appointment_datepicker">
                        </div>
                    </div>
                    <div class="birs_error" id="birs_appointment_date_error"></div>
                </li>
                <li class="birs_form_field birs_appointment_time">
                    <label><?php echo $labels['time']; ?></label>
                    <div class="birs_field_content">
                    </div>
                    <div class="birs_error" id="birs_appointment_time_error"></div>
                </li>
                <li class="birs_form_field birs_appointment_notes">
                    <label><?php echo $labels['appointment_notes']; ?></label>
                    <div class="birs_field_content birs_field_paragraph">
                        <textarea id="birs_appointment_notes" name="birs_appointment_notes"></textarea>
                        <input type="hidden" name="birs_appointment_fields[]" value="_birs_appointment_notes" />
                    </div>
                </li>
                <li class="birs_form_field birs_client_section">
                    <h2 class="birs_section"><?php _e( 'Your Info', 'birchschedule' ); ?></h2>
                </li>
                <li class="birs_form_field birs_client_name_first">
                    <label><?php echo $labels['client_name_first']; ?></label>
                    <div class="birs_field_content">
                        <input id="birs_client_name_first" name="birs_client_name_first" type="text">
                        <input type="hidden" name="birs_client_fields[]" value="_birs_client_name_first" />
                    </div>
                    <div class="birs_error" id="birs_client_name_first_error"></div>
                </li>
                <li class="birs_form_field birs_client_name_last">
                    <label><?php echo $labels['client_name_last']; ?></label>
                    <div class="birs_field_content">
                        <input id="birs_client_name_last" name="birs_client_name_last" type="text">
                        <input type="hidden" name="birs_client_fields[]" value="_birs_client_name_last" />
                    </div>
                    <div class="birs_error" id="birs_client_name_last_error"></div>
                </li>
                <li class="birs_form_field birs_client_email">
                    <label><?php echo $labels['client_email']; ?></label>
                    <div class="birs_field_content">
                        <input id="birs_client_email" name="birs_client_email" type="text">
                        <input type="hidden" name="birs_client_fields[]" value="_birs_client_email" />
                    </div>
                    <div class="birs_error" id="birs_client_email_error"></div>
                </li>
                <li class="birs_form_field birs_client_phone">
                    <label><?php echo $labels['client_phone']; ?></label>
                    <div class="birs_field_content">
                        <input id="birs_client_phone" name="birs_client_phone" type="text">
                        <input type="hidden" name="birs_client_fields[]" value="_birs_client_phone" />
                    </div>
                    <div class="birs_error" id="birs_client_phone_error"></div>
                </li>
                <li class="birs_footer">
                    <div class="birs_error" id="birs_booking_error"></div>
                    <div style="display:none;" id="birs_please_wait"><?php _e( 'Please wait...', 'birchschedule' ); ?></div>
                    <div class="birs_field_content">
                        <input type="button" value="<?php _e( 'Submit', 'birchschedule' ); ?>" class="button" id="birs_book_appointment">
                    </div>
                </li>
                </ul>

<?php
			$html = ob_get_clean();
			return $html;
		};

	} );
