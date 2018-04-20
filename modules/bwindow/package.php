<?php

birch_ns( 'birchschedule.bwindow', function( $ns ) {

	global $birchschedule;

	$ns->init = function() use( $ns, $birchschedule ) {
		add_action( 'init', array( $ns, 'wp_init' ) );

		add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );

		add_action( 'birchschedule_view_staff_enqueue_scripts_edit_after',
					array( $ns, 'add_scripts' ) );
	};

	$ns->wp_init = function() use( $ns ) {
		add_filter( 'birchschedule_model_schedule_get_staff_schedule_date_start',
					array( $ns, 'get_staff_schedule_date_start' ), 20, 4 );

		add_filter( 'birchschedule_model_schedule_get_staff_schedule_date_end',
					array( $ns, 'get_staff_schedule_date_end' ), 20, 4 );

		add_filter( 'birchschedule_model_schedule_get_staff_exception_date_start',
					array( $ns, 'get_staff_schedule_exception_date_start' ), 20, 4 );

		add_filter( 'birchschedule_model_schedule_get_staff_exception_date_end',
					array( $ns, 'get_staff_schedule_exception_date_end' ), 20, 4 );
	};

	$ns->wp_admin_init = function() use( $ns ) {
		add_action( 'birchschedule_view_staff_render_schedule_after',
					array( $ns, 'render_schedule_period' ), 10, 4 );
		add_action( 'birchschedule_tblocking_render_exception_after',
					array( $ns, 'render_schedule_exception_period' ), 10, 4 );
	};

	$ns->get_staff_schedule_by_location = function( $staff_id, $location_id )
		use( $birchschedule ) {
		return $birchschedule->model->get_staff_schedule_by_location( $staff_id, $location_id );
	};

	$ns->get_staff_schedule_date_start = function( $date_start, $staff_id, $location_id, $schedule_id ) use( $ns, $birchschedule ) {
		$work_schedule = $ns->get_staff_schedule_by_location( $staff_id, $location_id );
		if ( isset( $work_schedule['schedules'] ) &&
			 isset( $work_schedule['schedules'][$schedule_id] ) &&
			 isset( $work_schedule['schedules'][$schedule_id]['date_start'] ) ) {
			return $work_schedule['schedules'][$schedule_id]['date_start'];
		} else {
			return '';
		}
	};

	$ns->get_staff_schedule_date_end = function( $date_start, $staff_id, $location_id, $schedule_id ) use( $ns ) {
		$work_schedule = $ns->get_staff_schedule_by_location( $staff_id, $location_id );
		if ( isset( $work_schedule['schedules'] ) &&
			 isset( $work_schedule['schedules'][$schedule_id] ) &&
			 isset( $work_schedule['schedules'][$schedule_id]['date_end'] ) ) {
			return $work_schedule['schedules'][$schedule_id]['date_end'];
		} else {
			return '';
		}
	};

	$ns->get_staff_schedule_exception_date_start = function( $date_start, $staff_id, $location_id, $exception_id ) use( $ns ) {
		$work_schedule = $ns->get_staff_schedule_by_location( $staff_id, $location_id );
		if ( isset( $work_schedule['exceptions'] ) &&
			 isset( $work_schedule['exceptions'][$exception_id] ) &&
			 isset( $work_schedule['exceptions'][$exception_id]['date_start'] ) ) {
			return $work_schedule['exceptions'][$exception_id]['date_start'];
		} else {
			return '';
		}
	};

	$ns->get_staff_schedule_exception_date_end = function( $date_start, $staff_id, $location_id, $exception_id ) use( $ns ) {
		$work_schedule = $ns->get_staff_schedule_by_location( $staff_id, $location_id );
		if ( isset( $work_schedule['exceptions'] ) &&
			 isset( $work_schedule['exceptions'][$exception_id] ) &&
			 isset( $work_schedule['exceptions'][$exception_id]['date_end'] ) ) {
			return $work_schedule['exceptions'][$exception_id]['date_end'];
		} else {
			return '';
		}
	};

	$ns->add_scripts = function() use( $ns, $birchschedule ) {
		global $birchpress;

		$product_version = $birchschedule->get_product_version();
		$module_dir = $birchschedule->plugin_url() . '/modules/bwindow/';
		wp_register_script( 'birchschedule_booking_window',
							$module_dir . 'assets/js/booking-window.js',
							array( 'birchschedule_view_staff_edit', 'jquery-ui-datepicker' ), $product_version );
		$jquery_date_format = $birchpress->util->date_time_format_php_to_jquery( get_option( 'date_format' ) );
		$booking_window_params = array(
			'jquery_date_format' => $jquery_date_format,
			'datepicker_i18n_options' => $birchpress->util->get_datepicker_i18n_params()
		);
		wp_enqueue_script( 'birchschedule_booking_window' );
		wp_localize_script( 'birchschedule_booking_window', 'birs_booking_window_params', $booking_window_params );
	};

	$ns->render_period = function( $belong_to, $location_id, $uid, $schedule ) use( $ns ) {
		$date_start_id = "birs_staff_schedule_" . $location_id .
		"_" . $belong_to . "_" . $uid . "_date_start";
		$date_start_name = "birs_staff_schedule[$location_id][$belong_to][$uid][date_start]";
		$date_end_id = "birs_staff_schedule_" . $location_id .
		"_" . $belong_to . "_" . $uid . "_date_end";
		$date_end_name = "birs_staff_schedule[$location_id][$belong_to][$uid][date_end]";
		if ( isset( $schedule['date_start'] ) ) {
			$date_start = $schedule['date_start'];
		} else {
			$date_start = "";
		}
		if ( isset( $schedule['date_end'] ) ) {
			$date_end = $schedule['date_end'];
		} else {
			$date_end = "";
		}
?>
        <ul>
            <li>
                <span class="birs_schedule_field_label"><?php _e( 'Start Date', 'birchschedule' ); ?></span>
                <div class="birs_schedule_field_content">
                    <input
                        id="<?php echo $date_start_id; ?>"
                        type="text"
                        class="birs_staff_schedule_datepicker"
                        value="" />
                    <input name="<?php echo $date_start_name; ?>"
                        type="hidden"
                        value="<?php echo $date_start; ?>" />
                    <span><?php _e( '(Optional)', 'birchschedule' ); ?></span>
                </div>
            </li>
            <li>
                <span class="birs_schedule_field_label"><?php _e( 'End Date', 'birchschedule' ); ?></span>
                <div class="birs_schedule_field_content">
                    <input
                        id="<?php echo $date_end_id; ?>"
                        type="text"
                        class="birs_staff_schedule_datepicker"
                        value="" />
                    <input name="<?php echo $date_end_name; ?>"
                        type="hidden"
                        value="<?php echo $date_end; ?>" />
                    <span><?php _e( '(Optional)', 'birchschedule' ); ?></span>
                </div>
            </li>
        </ul>
        <script type="text/javascript">
            jQuery(function($){
                var params = birs_booking_window_params;
                var startDateSelector = '#' + "<?php echo $date_start_id; ?>";
                var startDateFieldName = "<?php echo $date_start_name; ?>";
                var endDateSelector = '#' + "<?php echo $date_end_id; ?>";
                var endDateFieldName = "<?php echo $date_end_name; ?>";
                var numberOfMonths = 2;
                var dateFormat = 'mm/dd/yy';

                var startDatepickerOptions = $.extend(params.datepicker_i18n_options, {
                    'showWeek': true,
                    'numberOfMonths': numberOfMonths,
                    'changeMonth': true,
                    'dateFormat': params.jquery_date_format,
                    'onClose': function( selectedDate ) {
                        $(endDateSelector).datepicker( "option", "minDate", selectedDate );
                        var date = $(startDateSelector).datepicker('getDate');
                        var dateValue = $.datepicker.formatDate(dateFormat, date);
                        $('input[name="' + startDateFieldName + '"]').val(dateValue);
                    }
                });
                $(startDateSelector).datepicker(startDatepickerOptions);
                var date_start = $('input[name="' + startDateFieldName + '"]').val();
                $('#ui-datepicker-div').css('display','none');
                $(startDateSelector).datepicker('setDate', $.datepicker.parseDate(dateFormat, date_start));

                var endDatepickerOptions = $.extend(params.datepicker_i18n_options, {
                    'showWeek': true,
                    'numberOfMonths': numberOfMonths,
                    'changeMonth': true,
                    'dateFormat': params.jquery_date_format,
                    'onClose': function( selectedDate ) {
                        $(startDateSelector).datepicker( "option", "maxDate", selectedDate );
                        var date = $(endDateSelector).datepicker('getDate');
                        var dateValue = $.datepicker.formatDate(dateFormat, date);
                        $('input[name="' + endDateFieldName + '"]').val(dateValue);
                    }
                });
                $(endDateSelector).datepicker(endDatepickerOptions);
                var date_end = $('input[name="' + endDateFieldName + '"]').val();
                $('#ui-datepicker-div').css('display','none');
                $(endDateSelector).datepicker('setDate', $.datepicker.parseDate(dateFormat, date_end));
            });
        </script>
<?php
	};

	$ns->render_schedule_period = function( $location_id, $uid, $schedule ) use( $ns ) {
		$ns->render_period( 'schedules', $location_id, $uid, $schedule );
	};

	$ns->render_schedule_exception_period = function( $location_id, $uid, $exception ) use( $ns ) {
		$ns->render_period( 'exceptions', $location_id, $uid, $exception );
	};

} );
