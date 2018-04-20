<?php

birch_ns( 'birchschedule.view.appointments.edit', function( $ns ) {

		global $birchschedule;

		$ns->init = function() use ( $ns, $birchschedule ) {
			add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );

			add_action( 'init', array( $ns, 'wp_init' ) );

			add_action( 'birchschedule_view_register_common_scripts_after',
				array( $ns, 'register_scripts' ) );

			$birchschedule->view->load_post_edit->when( $birchschedule->view->appointments->is_post_type_appointment, $ns->load_post_edit );

			$birchschedule->view->enqueue_scripts_post_edit->when( $birchschedule->view->appointments->is_post_type_appointment, $ns->enqueue_scripts_post_edit );
		};

		$ns->wp_init = function() use ( $ns ) {
			global $birchschedule;

			$birchschedule->view->register_script_data_fn(
				'birchschedule_view_appointments_edit', 'birchschedule_view_appointments_edit',
				array( $ns, 'get_script_data_fn_view_appointments_edit' ) );
		};

		$ns->wp_admin_init = function() use ( $ns ) {};

		$ns->get_script_data_fn_view_appointments_edit = function() use ( $ns ) {
			return array(
				'services_staff_map' => $ns->get_services_staff_map(),
				'locations_map' => $ns->get_locations_map(),
				'services_map' => $ns->get_services_map(),
				'locations_staff_map' => $ns->get_locations_staff_map(),
				'locations_services_map' => $ns->get_locations_services_map(),
				'locations_order' => $ns->get_locations_listing_order(),
				'staff_order' => $ns->get_staff_listing_order(),
				'services_order' => $ns->get_services_listing_order(),
			);
		};

		$ns->register_scripts = function() use ( $ns ) {
			global $birchschedule;

			$version = $birchschedule->get_product_version();

			wp_register_script( 'birchschedule_view_appointments_edit',
				$birchschedule->plugin_url() . '/assets/js/view/appointments/edit/base.js',
				array( 'birchschedule_view_admincommon', 'birchschedule_view', 'jquery-ui-datepicker' ), "$version" );
		};

		$ns->get_locations_map = function() {
			global $birchschedule;

			return $birchschedule->model->get_locations_map();
		};

		$ns->get_services_map = function() {
			global $birchschedule;

			return $birchschedule->model->get_services_map();
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

		$ns->get_services_locations_map = function() {
			global $birchschedule;

			return $birchschedule->model->get_services_locations_map();
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

		$ns->load_post_edit = function( $arg ) use ( $ns ) {

			add_action( 'add_meta_boxes',
				array( $ns, 'add_meta_boxes' ) );
		};

		$ns->enqueue_scripts_post_edit = function( $arg ) {

			global $birchschedule;

			$birchschedule->view->register_3rd_scripts();
			$birchschedule->view->register_3rd_styles();
			$birchschedule->view->enqueue_scripts(
				array(
					'birchschedule_view_appointments_edit'
				)
			);
			$birchschedule->view->enqueue_styles( array(
					'birchschedule_appointments_edit'
				) );
		};

		$ns->add_meta_boxes = function() use ( $ns ) {
			add_meta_box( 'meta_box_birs_appointment_edit_info', __( 'Appointment Info', 'birchschedule' ),
				array( $ns, 'render_appointment_info' ), 'birs_appointment', 'normal', 'high' );
			add_meta_box( 'meta_box_birs_appointment_edit_actions', __( 'Actions', 'birchschedule' ),
				array( $ns, 'render_actions' ), 'birs_appointment', 'side', 'high' );
		};

		$ns->get_back_to_calendar_url = function() use ( $ns ) {
			global $birchschedule;

			$back_url = admin_url( 'admin.php?page=birchschedule_calendar' );
			$hash_string = $birchschedule->view->get_query_string( $_GET,
				array(
					'calview', 'locationid', 'staffid', 'currentdate'
				)
			);
			if ( $hash_string ) {
				$back_url = $back_url . '#' . $hash_string;
			}
			return $back_url;
		};

		$ns->render_actions = function( $post ) use ( $ns ) {
			$back_url = $ns->get_back_to_calendar_url();
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

		$ns->render_appointment_info = function( $post ) use ( $ns ) {
			global $birchschedule;

			$location_id = 0;
			$staff_id = 0;
			$appointment_id = 0;
			$client_id = 0;
			$appointment_id = $post->ID;
			$back_url = $ns->get_back_to_calendar_url();
?>
                <div>
<?php
			echo $ns->get_appointment_info_html( $appointment_id );
?>
                    <ul>
                        <li>
                            <label>
                                &nbsp;
                            </label>
                            <div>
                            </div>
                        </li>
                    </ul>
                </div>
<?php
		};

		$ns->get_appointment_info_html = function( $appointment_id ) use ( $ns ) {
			global $birchpress, $birchschedule;

			$appointment = $birchschedule->model->get( $appointment_id, array(
					'base_keys' => array(),
					'meta_keys' => $birchschedule->model->get_appointment_fields()
				) );

			$options = $birchpress->util->get_time_options( 5 );
			if ( $appointment ) {
				$location_id = $appointment['_birs_appointment_location'];
				$location = $birchschedule->model->get( $location_id, array( 'keys' => array( 'post_title' ) ) );
				$location_name = $location ? $location['post_title'] : '';

				$service_id = $appointment['_birs_appointment_service'];
				$service = $birchschedule->model->get( $service_id, array( 'keys' => array( 'post_title' ) ) );
				$service_name = $service ? $service['post_title'] : '';

				$staff_id = $appointment['_birs_appointment_staff'];
				$staff = $birchschedule->model->get( $staff_id, array( 'keys' => array( 'post_title' ) ) );
				$staff_name = $staff ? $staff['post_title'] : '';

				$timestamp = $birchpress->util->get_wp_datetime( $appointment['_birs_appointment_timestamp'] );
				$date4picker = $timestamp->format( get_option( 'date_format' ) );
				$date = $timestamp->format( 'm/d/Y' );
				$time = $timestamp->format( get_option( 'time_format' ) );
			}
			ob_start();
?>
            <input type="hidden" name="birs_appointment_id" id="birs_appointment_id" value="<?php echo $appointment_id; ?>">
            <ul style="pointer-events: none;" >
                <li class="birs_form_field">
                    <label>
                        <?php _e( 'Location', 'birchschedule' ); ?>
                    </label>
                    <div class="birs_field_content">
                        <input type="text" value="<?php echo $location_name; ?>" readonly />
                    </div>
                </li>
                <li class="birs_form_field">
                    <label>
                        <?php _e( 'Service', 'birchschedule' ); ?>
                    </label>
                    <div class="birs_field_content">
                        <input type="text" value="<?php echo $service_name; ?>" readonly />
                    </div>
                </li>
                <li class="birs_form_field">
                    <label>
                            <?php _e( 'Provider', 'birchschedule' ); ?>
                        </label>
                        <div class="birs_field_content">
                            <input type="text" value="<?php echo $staff_name; ?>" readonly />
                        </div>
                    </li>
                    <li class="birs_form_field">
                        <label>
                            <?php _e( 'Date', 'birchschedule' ); ?>
                        </label>
                        <div class="birs_field_content">
                            <div id="birs_appointment_view_datepicker"
                                data-date-value="<?php echo $date; ?>"></div>
                        </div>
                    </li>
                    <li class="birs_form_field">
                        <label>
                            <?php _e( 'Time', 'birchschedule' ); ?>
                        </label>
                        <div class="birs_field_content">
                            <input type="text" value="<?php echo $time; ?>" readonly />
                        </div>
                    </li>
                </ul>
<?php
			return ob_get_clean();
		};

	} );
