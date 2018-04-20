<?php

birch_ns( 'birchschedule.tblocking', function( $ns ) {

		global $birchschedule;

		$ns->init = function() use( $ns ) {

				add_action( 'init', array( $ns, 'wp_init' ) );

				add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );
			};

		$ns->wp_init = function() use( $ns, $birchschedule ) {

				add_action( 'birchschedule_view_staff_load_page_edit_after',
					array( $ns, 'add_meta_boxes' ), 25 );

				add_action( 'wp_ajax_birchschedule_tblocking_new_staff_schedule_exception',
					array( $ns, 'ajax_new_staff_schedule_exception' ) );

				add_filter( 'birchschedule_model_schedule_get_staff_calculated_schedule_by_location',
					array( $ns, 'get_staff_calculated_schedule_by_location' ), 20, 3 );

				add_filter( 'birchschedule_model_schedule_get_staff_avaliable_time',
					array( $ns, 'remove_avaliable_time' ), 70, 5 );
			};

		$ns->wp_admin_init = function() use( $ns, $birchschedule ) {

				add_action( 'birchschedule_view_staff_enqueue_scripts_edit_after',
					array( $ns, 'enqueue_scripts' ) );

				add_action( 'birchschedule_view_staff_save_post_after',
					array( $ns, 'save_staff_data' ) );

				add_action( 'birchschedule_view_staff_render_timetable_after',
					array( $ns, 'render_timetable_exceptions' ), 10, 2 );
			};

		$ns->add_meta_boxes = function() use( $ns, $birchschedule ) {
				add_meta_box( 'birchschedule-staff-dayoffs', __( 'Days Off', 'birchschedule' ),
					array( $ns, 'render_days_off' ), 'birs_staff', 'normal', 'default' );
			};

		$ns->get_staff_calculated_schedule_by_location = function( $work_schedule, $staff_id, $location_id ) use( $ns, $birchschedule ) {

				$staff_schedule =
				$birchschedule->model->get_staff_schedule_by_location( $staff_id, $location_id );
				$new_exceptions = array();
				if ( isset( $staff_schedule['exceptions'] ) ) {
					$exceptions = $staff_schedule['exceptions'];
					for ( $week_day = 0; $week_day < 7; $week_day++ ) {
						$new_exceptions[] = array();
					}
					foreach ( $exceptions as $exception_id => $exception ) {
						$exception_date_start =
						$birchschedule->model->schedule->get_staff_exception_date_start(
							$staff_id, $location_id, $exception_id );
						$exception_date_end =
						$birchschedule->model->schedule->get_staff_exception_date_end(
							$staff_id, $location_id, $exception_id );
						foreach ( $new_exceptions as $week_day => $new_exception ) {
							if ( isset( $exception['weeks'][$week_day] ) ) {
								$new_exceptions[$week_day][] = array(
									'minutes_start' => $exception['minutes_start'],
									'minutes_end' => $exception['minutes_end'],
									'date_start' => $exception_date_start,
									'date_end' => $exception_date_end
								);
							}
						}
					}
				}
				$work_schedule['exceptions'] = $new_exceptions;
				return $work_schedule;
			};

		$ns->save_staff_data = function( $post ) {
				if ( isset( $_POST['birs_staff_dayoffs'] ) ) {
					$dayoffs = $_POST['birs_staff_dayoffs'];
					update_post_meta( $post['ID'], '_birs_staff_dayoffs', $dayoffs );
				}
			};

		$ns->get_default_new_exception = function() {
				return array(
					'minutes_start' => 720,
					'minutes_end' => 780,
					'weeks' => array(
						1 => 'on',
						2 => 'on',
						3 => 'on',
						4 => 'on',
						5 => 'on'
					)
				);
			};

		$ns->ajax_new_staff_schedule_exception = function() use( $ns, $birchschedule ) {

				$location_id = $_POST['birs_location_id'];
				$uid = uniqid();
				$exception = $birchschedule->tblocking->get_default_new_exception();
				$birchschedule->tblocking->render_exception_block( $location_id, $uid, $exception );
				die;
			};

		$ns->render_exception = function( $location_id, $uid, $exception ) use( $ns, $birchschedule ) {
				global $birchpress;

				$interval = $birchschedule->view->staff->get_schedule_interval();
				$time_options = $birchpress->util->get_time_options( $interval );
				$start = $exception['minutes_start'];
				$end = $exception['minutes_end'];
				$weeks = $birchpress->util->get_weekdays_short();
				$start_of_week = $birchpress->util->get_first_day_of_week();
?>
        <ul>
            <li>
                <span class="birs_schedule_field_label"><?php _e( 'From', 'birchschedule' ); ?></span>
                <div class="birs_schedule_field_content">
                    <select
                        name="birs_staff_schedule[<?php echo $location_id; ?>][exceptions][<?php echo $uid; ?>][minutes_start]">
                            <?php $birchpress->util->render_html_options( $time_options, $start ); ?>
                    </select>
                    <a href="javascript:void(0);"
                        data-exception-id="<?php echo $uid; ?>"
                        class="birs_schedule_exception_delete">
                        <?php echo "Delete"; ?>
                    </a>
                </div>
            </li>
            <li>
                <span class="birs_schedule_field_label"><?php _e( 'To', 'birchschedule' ); ?></span>
                <div class="birs_schedule_field_content">
                    <select
                        name="birs_staff_schedule[<?php echo $location_id; ?>][exceptions][<?php echo $uid; ?>][minutes_end]">
                            <?php $birchpress->util->render_html_options( $time_options, $end ); ?>
                    </select>
                </div>
            </li>
            <li>
                <span class="birs_schedule_field_label"></span>
                <div class="birs_schedule_field_content">
                <?php
				foreach ( $weeks as $week_value => $week_name ):
				if ( $week_value < $start_of_week ) {
					continue;
				}
				if ( isset( $exception['weeks'] ) && isset( $exception['weeks'][$week_value] ) ) {
					$checked_attr = ' checked="checked" ';
				} else {
					$checked_attr = '';
				}
?>
                    <label>
                        <input type="checkbox"
                            name="birs_staff_schedule[<?php echo $location_id; ?>][exceptions][<?php echo $uid; ?>][weeks][<?php echo $week_value; ?>]"
                            <?php echo $checked_attr; ?>/>
                            <?php echo $week_name; ?>
                    </label>
                <?php endforeach; ?>
                <?php
				foreach ( $weeks as $week_value => $week_name ):
				if ( $week_value >= $start_of_week ) {
					continue;
				}
				if ( isset( $exception['weeks'] ) && isset( $exception['weeks'][$week_value] ) ) {
					$checked_attr = ' checked="checked" ';
				} else {
					$checked_attr = '';
				}
?>
                    <label>
                        <input type="checkbox"
                            name="birs_staff_schedule[<?php echo $location_id; ?>][exceptions][<?php echo $uid; ?>][weeks][<?php echo $week_value; ?>]"
                            <?php echo $checked_attr; ?>/>
                            <?php echo $week_name; ?>
                    </label>
                <?php endforeach; ?>
                </div>
            </li>
        </ul>
        <?php
			};

		$ns->render_exception_block = function( $location_id, $uid, $exception ) use( $ns, $birchschedule ) {

				$exception_dom_id = 'birs_schedule_exception_' . $uid;
?>
        <div id="<?php echo $exception_dom_id; ?>"
            class="birs_exception_item">
            <?php
				$birchschedule->tblocking->render_exception( $location_id, $uid, $exception );
?>
        <script type="text/javascript">
            //<![CDATA[
            jQuery(document).ready( function($) {
                var exceptionId = '<?php echo $exception_dom_id; ?>';
                $('#' + exceptionId + ' .birs_schedule_exception_delete').click(function() {
                    $('#' + exceptionId).remove();
                });
            });
            //]]>
        </script>
        </div>
        <?php
			};

		$ns->get_schedule_exceptions = function( $staff_id, $location_id ) use( $ns, $birchschedule ) {

				$exceptions = array();
				$schedule =
				$birchschedule->model->get_staff_schedule_by_location( $staff_id, $location_id );
				if ( isset( $schedule['exceptions'] ) ) {
					$exceptions = $schedule['exceptions'];
				}
				return $exceptions;
			};

		$ns->render_timetable_exceptions = function( $staff_id, $location_id ) use( $ns, $birchschedule ) {

				$exceptions = $ns->get_schedule_exceptions( $staff_id, $location_id );
?>
        <div style="margin-bottom: 20px;">
            <h3><?php _e( 'Exceptions', 'birchschedule' ); ?></h3>
            <div id="<?php echo 'birs_schedule_exceptions_' . $location_id ?>">
            <?php
				foreach ( $exceptions as $uid => $exception ) {
					$birchschedule->tblocking->render_exception_block( $location_id, $uid, $exception );
				}
?>
            </div>
            <div class="birs_schedule_exception_new_box">
                <a href="javascript:void(0);"
                    class="birs_schedule_exception_new"
                    data-location-id="<?php echo $location_id; ?>">
                    <?php _e( '+ Add Exception', 'birchschedule' ); ?>
                </a>
            </div>
        </div>
        <?php
			};

		$ns->render_days_off = function( $post ) use( $ns, $birchschedule ) {

				$staff = $birchschedule->model->get( $post->ID, array(
						'meta_keys' => array( '_birs_staff_dayoffs' ),
						'base_keys' => array()
					) );
				$dayoffs = "[]";
				if ( isset( $staff['_birs_staff_dayoffs'] ) ) {
					$dayoffs = $staff['_birs_staff_dayoffs'];
				}
?>
        <div id="birs_staff_dayoffs"></div>
        <input name="birs_staff_dayoffs" type="hidden" value="<?php echo esc_attr( $dayoffs ); ?>" />
        <?php
			};

		$ns->enqueue_scripts = function() use( $ns, $birchschedule ) {
				global $birchpress;

				$product_version = $birchschedule->get_product_version();
				$module_dir = $birchschedule->plugin_url() . '/modules/tblocking/';
				wp_register_script( 'birchschedule_timeblocking',
					$module_dir . 'assets/js/timeblocking.js',
					array( 'jquery-ui-datepicker', 'birchschedule_view', 'json2' ), $product_version );
				wp_enqueue_script( 'birchschedule_timeblocking' );
				$params = array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'datepicker_i18n_options' => $birchpress->util->get_datepicker_i18n_params()
				);
				wp_localize_script( 'birchschedule_timeblocking', 'birs_timeblocking_params', $params );
				wp_register_style( 'birchschedule_timeblocking',
					$module_dir . 'assets/css/timeblocking.css',
					array( 'jquery-ui-no-theme' ), $product_version );
				wp_enqueue_style( 'birchschedule_timeblocking' );
			};

		$ns->remove_avaliable_time = function( $time_options, $staff_id, $location_id, $service_id, $date ) use( $ns, $birchschedule ) {

				$staff_daysoff = $birchschedule->model->get_staff_daysoff( $staff_id );
				$staff_daysoff = json_decode( $staff_daysoff );
				$date_str = $date->format( 'm/d/Y' );
				if ( in_array( $date_str, $staff_daysoff ) ) {
					return array();
				} else {
					return $time_options;
				}
			};

	} );
