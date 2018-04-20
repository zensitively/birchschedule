<?php

birch_ns( 'birchschedule.archive', function( $ns ) {

		global $birchschedule;

		$ns->init = function() use ( $ns ) {
			add_action( 'init', array( $ns, 'wp_init' ) );
			add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );
		};

		$ns->wp_admin_init = function() use ( $ns, $birchschedule ) {

			if ( !$ns->is_enabled()  || empty( $_GET['post'] ) ) {
				return;
			}
			add_action( 'birchschedule_view_staff_load_page_edit_after',
				array( $ns, 'add_meta_boxes' ), 100 );

			add_action( 'birchschedule_view_staff_enqueue_scripts_edit_after',
				array( $ns, 'enqueue_scripts_edit' ), 40 );

			$module_dir = $birchschedule->plugin_url() . '/modules/archive/';
			$product_version = $birchschedule->get_product_version();
			wp_register_script(
				'birchschedule_archive',
				$module_dir . 'assets/js/index.js',
				array( 'birchschedule_view' ), $product_version );
			$birchschedule->view->register_script_data_fn(
				'birchschedule_archive', 'birchschedule_archive',
				array( $ns, 'get_script_data_fn_archive' ) );
			add_action( 'wp_ajax_birchschedule_archive_do_archive',
				array( $ns, 'ajax_do_archive' ) );
			add_action( 'admin_post_birchschedule_archive_get_archived_calendar',
				array( $ns, 'get_archived_calendar' ) );
		};

		$ns->wp_init = function() use( $ns ) {

		};

		$ns->is_enabled = function() {
			return false;
		};

		$ns->get_all_selections = function( $staff_id ) {
			global $birchpress;

			$timestamp = get_post_time( 'U', true, $staff_id );
			$staff_created = $birchpress->util->get_wp_datetime( $timestamp );
			$last_time = $birchpress->util->get_wp_datetime( time() - 60 * 60 * 24 * 90 );
			$first_year = intval( $staff_created->format( 'Y' ) );
			$first_month = intval( $staff_created->format( 'm' ) );
			$last_year = intval( $last_time->format( 'Y' ) );
			$last_month = intval( $last_time->format( 'm' ) );
			$result = array();
			$year = $first_year;
			while ( $year <= $last_year ) {
				if ( !isset( $result[$year] ) ) {
					$result[$year] = array();
				}
				for ( $month = 1; $month <= 12; $month++ ) {
					if ( $year == $first_year && $month < $first_month ) {
						continue;
					}
					if ( $year == $last_year && $month >= $last_month ) {
						continue;
					}
					$month_text = sprintf( "%02d", $month );
					$result[$year][$month] = array(
						'text' => $month_text,
						'archived' => false
					);
				}
				$year += 1;
			}
			return $result;
		};

		$ns->get_archive_selections = function( $staff_id ) use( $ns, $birchschedule ) {
			$all_selections = $ns->get_all_selections( $staff_id );
			$staff = $birchschedule->model->get( $staff_id, array(
					'keys' => array( '_birs_staff_archives' )
				) );
			$selections = $staff['_birs_staff_archives'];
			if ( $selections ) {
				return array_replace_recursive( $all_selections, $selections );
			} else {
				$staff['_birs_staff_archives'] = $all_selections;
				$birchschedule->model->save( $staff, array(
						'keys' => array( '_birs_staff_archives' )
					) );
				return $all_selections;
			}
		};

		$ns->get_archive_meta_key = function( $year, $month ) {
			return sprintf( '_birs_staff_archive_%s_%02d', $year, $month );
		};

		$ns->do_archive = function( $staff_id, $year, $month ) use ( $ns, $birchschedule ) {
			global $birchpress;

			$timezone = $birchpress->util->get_wp_timezone();
			$start = new DateTime( sprintf( "%s-%02d-01 00:00:00", $year, $month ), $timezone );
			$start = $start->format( 'U' );
			$days = cal_days_in_month( CAL_GREGORIAN, $month, $year );
			$end = $start + 60 * 60 * 24 * $days;
			$criteria = array(
				'start' => $start,
				'end' => $end,
				'location_id' => -1,
				'staff_id' => $staff_id
			);
			$appointments = $birchschedule->model->booking->query_appointments( $criteria, array(
					'client_keys' => array( 'post_title' )
				) );
			$ics = $birchschedule->icalendar->get_appointments_as_ics( $appointments );
			$ics = base64_encode( $ics );
			$archive_key = $ns->get_archive_meta_key( $year, $month );
			update_post_meta( $staff_id, $archive_key, $ics );
			foreach ( $appointments as $appointment ) {
				$birchschedule->model->booking->delete_appointment( $appointment['ID'] );
			}
		};

		$ns->ajax_do_archive = function() use ( $ns, $birchschedule ) {
			$staff_id = $_POST['staff_id'];
			$year = $_POST['year'];
			$month = $_POST['month'];
			$staff = $birchschedule->model->get( $staff_id, array(
					'keys' => array( '_birs_staff_archives' )
				) );
			$selections = $staff['_birs_staff_archives'];
			if ( isset( $selections[$year][$month] ) && !$selections[$year][$month]['archived'] ) {
				$ns->do_archive( $staff_id, $year, $month );
				$selections[$year][$month]['archived'] = true;
			}
			$staff['_birs_staff_archives'] = $selections;
			$birchschedule->model->save( $staff, array(
					'keys' => array( '_birs_staff_archives' )
				) );
			echo json_encode( array(
					'selections' => $selections
				) );
			exit;
		};

		$ns->get_script_data_fn_archive = function() use ( $ns ) {
			$staff_id = $_GET['post'];
			$selections = $ns->get_archive_selections( $staff_id );
			$years = array_keys( $selections );
			return array(
				'selections' => $selections,
				'default_year' => $years[0]
			);
		};

		$ns->add_meta_boxes = function() use( $ns ) {
			add_meta_box( 'birchschedule_staff_archive',
				__( 'Archives', 'birchschedule' ),
				array( $ns, 'render_staff_archive' ),
				'birs_staff', 'normal', 'low' );
		};

		$ns->enqueue_scripts_edit = function( $arg ) use ( $ns ) {

			global $birchschedule;

			$birchschedule->view->enqueue_scripts(
				array(
					'birchschedule_archive'
				)
			);
		};

		$ns->get_i18n = function() {
			$i18n = array(
				'messages' => array(
					'Year' => __( 'Year', 'birchschedule' ),
					'Month' => __( 'Month', 'birchschedule' ),
					'Archive' => __( 'Archive', 'birchschedule' ),
					'Please wait...' => __( 'Please wait...', 'birchschedule' ),
					'This operation cannot be undone. Would you like to proceed?' => __( 'This operation cannot be undone. Would you like to proceed?', 'birchschedule' ),
					'Yes' => __( 'Yes', 'birchschedule' ),
					'No' => __( 'No', 'birchschedule' )
				),
				'errorMessages' => array(
					'requireMonth' => 'Please choose a month'
				)
			);
			return $i18n;
		};

		$ns->get_archived_calendar = function() use ( $ns ) {
			$staff_id = $_GET['staff_id'];
			$year = $_GET['year'];
			$month = $_GET['month'];
			$meta_key = $ns->get_archive_meta_key( $year, $month );
			$ics_encoded = get_post_meta( $staff_id, $meta_key, true );
			$filename = sprintf( 'provider-%s_%s_%02d', $staff_id, $year, $month );
			header( 'Content-type: text/calendar; charset=utf-8' );
			header( sprintf( 'Content-Disposition: inline; filename=%s.ics', $filename ) );
			echo base64_decode( $ics_encoded );
			exit;
		};

		$ns->render_staff_archive = function( $post ) use ( $ns ) {
?>
			<div class="panel-wrap birchschedule">

		    </div>
<?php
		};

	} );
