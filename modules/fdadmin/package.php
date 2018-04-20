<?php

birch_ns( 'birchschedule.fdadmin', function( $ns ) {

		global $birchschedule;

		$ns->init = function() use ( $ns ) {

				add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );

				add_action( 'birchschedule_view_register_common_scripts_after',
					array( $ns, 'register_scripts' ) );
			};

		$ns->wp_admin_init = function() use ( $ns ) {

				add_action( 'birchschedule_view_appointments_new_enqueue_scripts_post_new_after',
					array( $ns, 'enqueue_scripts' ) );

				add_action( 'birchschedule_view_appointments_edit_enqueue_scripts_post_edit_after',
					array( $ns, 'enqueue_scripts' ) );

				add_filter( 'birchschedule_view_appointments_new_get_appointment_info_html',
					array( $ns, 'add_filter_avaliable_times_checkbox' ) );

				add_filter( 'birchschedule_view_appointments_edit_clientlist_reschedule_get_appointment_info_html',
					array( $ns, 'add_filter_avaliable_times_checkbox' ) );

				add_action( 'wp_ajax_birchschedule_fdadmin_get_available_time_options',
					array( $ns, 'ajax_get_available_time_options' ) );

				add_action( 'wp_ajax_birchschedule_fdadmin_get_available_reschedule_time_options',
					array( $ns, 'ajax_get_available_reschedule_time_options' ) );
			};

		$ns->register_scripts = function() use( $ns, $birchschedule ) {

				$version = $birchschedule->get_product_version();

				wp_register_script( 'birchschedule_fdadmin',
					$birchschedule->plugin_url() .
					'/modules/fdadmin/assets/js/base.js',
					array(), "$version" );
			};

		$ns->enqueue_scripts = function() use( $ns, $birchschedule ) {

				$birchschedule->view->enqueue_scripts(
					array(
						'birchschedule_fdadmin'
					)
				);
			};

		$ns->add_filter_avaliable_times_checkbox = function( $html ) use( $ns, $birchschedule ) {
				ob_start();
?>
        <ul>
            <li class="birs_form_field">
                <label>&nbsp;</label>
                <div class="birs_field_content">
                    <input type="checkbox" name="birs_show_all_time" id="birs_show_all_time" value="true" />
                    <label for="birs_show_all_time"><?php _e( 'Show all time slots', 'birchschedule' ); ?></label>
                </div>
            </li>
        </ul>
        <?php
				return $html . ob_get_clean();
			};

		$ns->ajax_get_available_time_options = function() use( $ns, $birchschedule ) {
				global $birchpress;

				if ( isset( $_POST['birs_show_all_time'] ) && $_POST['birs_show_all_time'] ) {
					$time_options = $birchpress->util->get_time_options( 5 );
					$birchpress->util->render_html_options( $time_options );
					exit;
				}
				if ( empty( $_POST['birs_appointment_staff'] ) ||
					empty( $_POST['birs_appointment_location'] ) ||
					empty( $_POST['birs_appointment_service'] ) ||
					empty( $_POST['birs_appointment_date'] ) ) {
					exit;
				}
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
				foreach ( $time_options as $key => $value ) {
					if ( $value['avaliable'] ) {
						$text = $value['text'];
						$alternative_staff = '';
						if ( isset( $value['alternative_staff'] ) ) {
							$alternative_staff = implode( ',', $value['alternative_staff'] );
						}
?>
                <option value='<?php echo $key; ?>'
                        data-alternative-staff="<?php echo $alternative_staff; ?>"><?php echo $text; ?></option>
                <?php
					}
				}
				exit;
			};

		$ns->add_scheduled_time = function( $time_options, $staff_id, $location_id, $service_id, $date )
			use ( $ns, $birchschedule ) {

				global $birchpress;

				$appointment_id = $_POST['birs_appointment_id'];
				$appointment = $birchschedule->model->get( $appointment_id, array(
						'base_keys' => array(),
						'meta_keys' => $birchschedule->model->get_appointment_fields()
					) );

				if ( $appointment ) {
					$datetime = $birchpress->util->get_wp_datetime( $appointment['_birs_appointment_timestamp'] );
					$time_mins = $datetime->format( 'H' ) * 60 + $datetime->format( 'i' );
					if ( $location_id == $appointment['_birs_appointment_location'] &&
						$service_id == $appointment['_birs_appointment_service'] &&
						$staff_id == $appointment['_birs_appointment_staff'] &&
						$datetime->format( 'm/d/Y' ) == $date->format( 'm/d/Y' ) ) {

						$time_options[$time_mins]['avaliable'] = true;
						if ( !isset( $time_options[$time_mins]['text'] ) ) {
							$time_options[$time_mins]['text'] =
							$birchpress->util->convert_mins_to_time_option( $time_mins );
						}
					}
				}
				return $time_options;
			};

		$ns->ajax_get_available_reschedule_time_options = function() use ( $ns ) {

				add_filter( 'birchschedule_model_schedule_get_staff_avaliable_time',
					array( $ns, 'add_scheduled_time' ), 60, 5 );
				
				$ns->ajax_get_available_time_options();
			};

	} );
