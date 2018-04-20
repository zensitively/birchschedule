<?php

birch_ns( 'birchschedule.view.appointments.edit.clientlist.cancel', function( $ns ) {

	$ns->init = function() use( $ns ) {
		add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );
		add_action( 'birchschedule_view_register_common_scripts_after',
					array( $ns, 'register_scripts' ) );
	};

	$ns->wp_admin_init = function() use ( $ns ) {
		add_action( 'wp_ajax_birchschedule_view_appointments_edit_clientlist_cancel_cancel',
					array( $ns, 'ajax_cancel' ) );

		add_action( 'birchschedule_view_enqueue_scripts_post_edit_after',
					array( $ns, 'enqueue_scripts_post_edit' ) );

		add_filter( 'birchschedule_view_appointments_edit_clientlist_get_item_actions',
					array( $ns, 'add_item_action' ), 40, 2 );
	};

	$ns->register_scripts = function() {
		global $birchschedule;

		$version = $birchschedule->get_product_version();

		wp_register_script( 'birchschedule_view_appointments_edit_clientlist_cancel',
							$birchschedule->plugin_url() . '/assets/js/view/appointments/edit/clientlist/cancel/base.js',
							array( 'birchschedule_view_admincommon', 'birchschedule_view' ), "$version" );
	};

	$ns->add_item_action = function( $item_actions, $item ) {
		$action_html = '<a href="javascript:void(0);" data-item-id="%s">%s</a>';
		$item_actions['cancel'] = sprintf( $action_html, $item['ID'], __( 'Cancel', 'birchschedule' ) );
		return $item_actions;
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
				'birchschedule_view_appointments_edit_clientlist_cancel'
			)
		);
	};

	$ns->ajax_cancel = function() use ( $ns ) {
		global $birchschedule;

		$client_id = $_POST['birs_client_id'];
		$appointment_id = $_POST['birs_appointment_id'];
		$appointment1on1 = $birchschedule->model->booking->get_appointment1on1(
			$appointment_id, $client_id );
		$success = array(
			'code' => 'reload',
			'message' => ''
		);
		if ( $appointment1on1 ) {
			$birchschedule->model->booking->cancel_appointment1on1( $appointment1on1['ID'] );
			$cancelled = $birchschedule->model->booking->if_appointment_cancelled( $appointment_id );
			if ( $cancelled ) {
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
				$success = array(
					'code' => 'redirect_to_calendar',
					'message' => json_encode( array(
						'url' => htmlentities( $cal_url )
					) )
				);
			}
		}
		$birchschedule->view->render_ajax_success_message( $success );
	};

} );
