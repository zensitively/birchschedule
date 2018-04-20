<?php

birch_ns( 'birchschedule.mstaff', function( $ns ) {

		global $birchschedule;

		$_ns_data = new stdClass();

		$ns->init = function() use ( $ns, $_ns_data ) {

				$_ns_data->default_calendar_color = '#2EA2CC';

				$ns->redefine_functions();

				add_action( 'init', array( $ns, 'wp_init' ) );

				add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );
			};

		$ns->redefine_functions = function() use ( $ns, $birchschedule ) {

				$birchschedule->view->bookingform->get_shortcode_attrs = $ns->get_sc_bookingform_attrs;
			};

		$ns->wp_admin_init = function() use ( $ns, $birchschedule ) {

				add_action( 'admin_enqueue_scripts', array( $ns, 'enqueue_scripts' ) );
			};

		$ns->wp_init = function() use ( $ns, $birchschedule ) {

				add_action( 'birchschedule_view_staff_load_page_edit_after',
					array( $ns, 'add_meta_boxes' ) );

				add_action( 'birchschedule_view_staff_save_post_after',
					array( $ns, 'save_staff_data' ) );

				add_filter( 'birchschedule_view_calendar_query_appointments',
					array( $ns, 'add_appointments_color' ) );

				add_filter( 'birchschedule_view_calendar_get_locations_staff_map',
					array( $ns, 'add_all_staff' ), 20 );

				add_filter( 'birchschedule_view_calendar_get_staff_listing_order',
					array( $ns, 'add_all_staff_listing_order' ), 20 );

			};

		$ns->explode_ids = function( $ids ) use ( $ns ) {
				if ( !$ids ) {
					return false;
				}
				$ids = explode( ',', $ids );
				$new_ids = array();
				foreach ( $ids as $id ) {
					$new_ids[] = intval( $id );
				}
				return $new_ids;
			};

		$ns->get_sc_bookingform_attrs = function( $attr ) use ( $ns, $birchschedule ) {

				if ( isset( $attr['location_ids'] ) ) {
					$attr['location_ids'] = $birchschedule->mstaff->explode_ids( $attr['location_ids'] );
				}
				if ( isset( $attr['service_ids'] ) ) {
					$attr['service_ids'] = $birchschedule->mstaff->explode_ids( $attr['service_ids'] );
				}
				if ( isset( $attr['staff_ids'] ) ) {
					$attr['staff_ids'] = $birchschedule->mstaff->explode_ids( $attr['staff_ids'] );
				}
				return $attr;
			};


		$ns->enqueue_scripts = function( $hook ) use ( $ns, $birchschedule ) {

				if ( $birchschedule->view->get_page_hook( 'calendar' ) !== $hook ) {
					return;
				}
				$ns->add_scripts();
				$ns->add_styles();
			};

		$ns->add_scripts = function() use ( $ns, $birchschedule ) {

				$product_version = $birchschedule->get_product_version();
				$module_dir = $birchschedule->plugin_url() . '/modules/mstaff/';
				wp_register_script( 'birchschedule_multi_staff',
					$module_dir . 'assets/js/multi-staff.js',
					array( 'birchschedule_view_calendar', 'select2' ), $product_version );
				$params = array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'staff_color_map' => $ns->get_staff_color_map()
				);
				wp_enqueue_script( 'birchschedule_multi_staff' );
				wp_localize_script( 'birchschedule_multi_staff', 'birs_multi_staff_params', $params );
			};

		$ns->add_styles = function() use ( $ns, $birchschedule ) {

				$product_version = $birchschedule->get_product_version();

				$module_dir = $birchschedule->plugin_url() . '/modules/mstaff/';
				wp_register_style( 'birchschedule_multi_staff',
					$module_dir . 'assets/css/multi-staff.css',
					array( 'birchschedule_admincommon', 'select2' ), $product_version );
				wp_enqueue_style( 'birchschedule_multi_staff' );
			};

		$ns->add_all_staff = function( $map ) use ( $ns, $birchschedule ) {

				$i18n_msgs = $birchschedule->view->get_frontend_i18n_messages();
				$new_map = array();
				foreach ( $map as $location_id => $staff ) {
					if ( sizeof( $staff ) > 0 ) {
						$staff[-1] = $i18n_msgs['All Providers'];
					}
					$new_map[$location_id] = $staff;
				}
				return $new_map;
			};

		$ns->add_all_staff_listing_order = function( $map ) {
				return array_merge( array( -1 ), $map );
			};

		$ns->get_staff_color_map = function() use ( $ns, $birchschedule, $_ns_data ) {

				$staff = $birchschedule->model->query(
					array(
						'post_type' => 'birs_staff'
					),
					array(
						'meta_keys' => array( '_birs_staff_color' ),
						'base_keys' => array()
					)
				);
				$map = array(
					0 => '#FFFFFF'
				);
				foreach ( $staff as $the_staff ) {
					if ( isset( $the_staff['_birs_staff_color'] ) ) {
						$map[$the_staff['ID']] = $the_staff['_birs_staff_color'];
					} else {
						$map[$the_staff['ID']] = $_ns_data->default_calendar_color;
					}
				}
				return $map;
			};

		$ns->add_meta_boxes = function() use ( $ns, $birchschedule ) {

				add_meta_box( 'birchschedule-staff-color',
					__( 'Calendar Color', 'birchschedule' ),
					array( $ns, 'render_staff_color' ),
					'birs_staff', 'side', 'default' );
			};

		$ns->save_staff_data = function( $post ) use ( $ns ) {
				if ( isset( $_POST['birs_staff_color'] ) ) {
					$color = $_POST['birs_staff_color'];
					update_post_meta( $post['ID'], '_birs_staff_color', $color );
				}
			};

		$ns->render_staff_color = function( $post ) use ( $ns, $birchschedule, $_ns_data ) {

				$staff = $birchschedule->model->get( $post->ID, array(
						'meta_keys' => array( '_birs_staff_color' ),
						'base_keys' => array()
					) );
				$color = $_ns_data->default_calendar_color;
				if ( isset( $staff['_birs_staff_color'] ) && $staff['_birs_staff_color'] ) {
					$color = $staff['_birs_staff_color'];
				}
?>
        <div class="panel-wrap birchschedule">
            <input name="birs_staff_color" id="birs_staff_color"
            class="color {hash:true}" value="<?php echo $color; ?>"/>
        </div>
        <?php
			};

		$ns->add_appointments_color = function( $appointments ) use ( $ns, $birchschedule, $_ns_data ) {

				$new_appointments = array();
				foreach ( $appointments as $appointment ) {
					$appointment_m = $birchschedule->model->get( $appointment['id'],
						array(
							'meta_keys' => array( '_birs_appointment_staff' ),
							'base_keys' => array()
						)
					);
					$staff_id = $appointment_m['_birs_appointment_staff'];
					$staff = $birchschedule->model->get( $staff_id, array(
							'meta_keys' => array( '_birs_staff_color' ),
							'base_keys' => array()
						)
					);
					if ( isset( $staff['_birs_staff_color'] ) ) {
						$color = $staff['_birs_staff_color'];
					} else {
						$color = $_ns_data->default_calendar_color;
					}
					$appointment['color'] = $color;
					$appointment['className'] = 'provider-' . $staff_id;
					$new_appointments[] = $appointment;
				}
				return $new_appointments;
			};

	} );
