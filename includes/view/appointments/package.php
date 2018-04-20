<?php

birch_ns( 'birchschedule.view.appointments', function( $ns ) {

		$ns->init = function() use ( $ns ) {
			add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );
			add_action( 'init', array( $ns, 'wp_init' ) );
		};

		$ns->wp_init = function() use ( $ns ) {
			$ns->register_post_type();
		};

		$ns->wp_admin_init = function() use ( $ns ) {
			add_filter( 'post_updated_messages',
				array( $ns, 'get_updated_messages' ) );
		};

		$ns->is_post_type_appointment = function( $post ) {
			return $post['post_type'] === 'birs_appointment';
		};

		$ns->register_post_type = function() use ( $ns ) {
			register_post_type( 'birs_appointment', array(
					'labels' => array(
						'name' => __( 'Appointments', 'birchschedule' ),
						'singular_name' => __( 'Appointment', 'birchschedule' ),
						'add_new' => __( 'New Appointment', 'birchschedule' ),
						'add_new_item' => __( 'New Appointment', 'birchschedule' ),
						'edit' => __( 'Edit', 'birchschedule' ),
						'edit_item' => __( 'Edit Appointment', 'birchschedule' ),
						'new_item' => __( 'New Appointment', 'birchschedule' ),
						'view' => __( 'View Appointment', 'birchschedule' ),
						'view_item' => __( 'View Appointment', 'birchschedule' ),
						'search_items' => __( 'Search Appointments', 'birchschedule' ),
						'not_found' => __( 'No Appointments found', 'birchschedule' ),
						'not_found_in_trash' => __( 'No Appointments found in trash', 'birchschedule' ),
						'parent' => __( 'Parent Appointment', 'birchschedule' )
					),
					'description' => __( 'This is where appointments are stored.', 'birchschedule' ),
					'public' => false,
					'show_ui' => true,
					'menu_icon' => 'dashicons-calendar',
					'capability_type' => 'birs_appointment',
					'map_meta_cap' => true,
					'publicly_queryable' => false,
					'exclude_from_search' => true,
					'show_in_menu' => true,
					'hierarchical' => false,
					'show_in_nav_menus' => false,
					'rewrite' => false,
					'query_var' => true,
					'supports' => array( '' ),
					'has_archive' => false
				)
			);
			register_post_type( 'birs_appointment1on1', array(
					'labels' => array(
						'name' => __( 'One-on-one appointments', 'birchschedule' ),
						'singular_name' => __( 'One-on-one appointment', 'birchschedule' ),
						'add_new' => __( 'New One-on-one Appointment', 'birchschedule' ),
						'add_new_item' => __( 'New One-on-one Appointment', 'birchschedule' ),
						'edit' => __( 'Edit', 'birchschedule' ),
						'edit_item' => __( 'Edit One-on-one Appointment', 'birchschedule' ),
						'new_item' => __( 'New One-on-one Appointment', 'birchschedule' ),
						'view' => __( 'View One-on-one Appointment', 'birchschedule' ),
						'view_item' => __( 'View One-on-one Appointment', 'birchschedule' ),
						'search_items' => __( 'Search One-on-one Appointments', 'birchschedule' ),
						'not_found' => __( 'No One-on-one Appointments found', 'birchschedule' ),
						'not_found_in_trash' => __( 'No One-on-one Appointments found in trash', 'birchschedule' ),
						'parent' => __( 'Parent One-on-one Appointment', 'birchschedule' )
					),
					'description' => __( 'This is where one-on-one appointments are stored.', 'birchschedule' ),
					'public' => false,
					'show_ui' => false,
					'menu_icon' => 'dashicons-calendar',
					'capability_type' => 'birs_appointment',
					'map_meta_cap' => true,
					'publicly_queryable' => false,
					'exclude_from_search' => true,
					'show_in_menu' => false,
					'hierarchical' => false,
					'show_in_nav_menus' => false,
					'rewrite' => false,
					'query_var' => true,
					'supports' => array( '' ),
					'has_archive' => false
				)
			);
		};

		$ns->get_updated_messages = function( $messages ) {
			global $post, $post_ID;

			$messages['birs_appointment'] = array(
				0 => '', // Unused. Messages start at index 1.
				1 => __( 'Appointment updated.', 'birchschedule' ),
				2 => __( 'Custom field updated.', 'birchschedule' ),
				3 => __( 'Custom field deleted.', 'birchschedule' ),
				4 => __( 'Appointment updated.', 'birchschedule' ),
				5 => isset( $_GET['revision'] ) ? sprintf( __( 'Appointment restored to revision from %s', 'birchschedule' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
				6 => __( 'Appointment updated.', 'birchschedule' ),
				7 => __( 'Appointment saved.', 'birchschedule' ),
				8 => __( 'Appointment submitted.', 'birchschedule' ),
				9 => sprintf( __( 'Appointment scheduled for: <strong>%1$s</strong>.', 'birchschedule' ), date_i18n( 'M j, Y @ G:i', strtotime( $post->post_date ) ) ),
				10 => __( 'Appointment draft updated.', 'birchschedule' )
			);

			return $messages;
		};

	} );
