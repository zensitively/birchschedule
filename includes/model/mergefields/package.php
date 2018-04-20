<?php

birch_ns( 'birchschedule.model.mergefields', function( $ns ) {

	$_ns_data = new stdClass();

	$ns->init = function() use ( $_ns_data ) {

		$_ns_data->appointment_for_merge_field = array();

		$_ns_data->merge_fields_map = array();

	};

	$ns->get_appointment_merge_values = function( $appointment_id ) use ( $ns ) {
		global $birchschedule, $birchpress;

		$meta_keys = $birchschedule->model->get_appointment_fields();
		$appointment = $birchschedule->model->get( $appointment_id, array(
			'meta_keys' => $meta_keys,
			'base_keys' => array( 'post_status', 'post_title' )
		) );

		if ( !$appointment || $appointment['post_type'] != 'birs_appointment' ) {
			return false;
		}

		$appointment['_birs_appointment_pre_payment_fee'] =
		$birchschedule->model->get_service_pre_payment_fee( $appointment['_birs_appointment_service'] );
		$currency_code = $birchschedule->model->get_currency_code();
		$appointment['_birs_appointment_pre_payment_fee_with_currency'] =
		$birchschedule->model->
		apply_currency_symbol( $appointment['_birs_appointment_pre_payment_fee'], $currency_code );

		$staff = $birchschedule->model->get( $appointment['_birs_appointment_staff'], array(
			'base_keys' => array(
				'post_title', 'post_content'
			),
			'meta_keys' => array(
				'_birs_staff_email', '_birs_staff_color'
			)
		) );
		if ( !$staff ) {
			$staff = array();
		}
		$service = $birchschedule->model->get( $appointment['_birs_appointment_service'], array(
			'base_keys' => array(
				'post_title', 'post_content'
			),
			'meta_keys' => array(
			)
		) );
		if ( !$service ) {
			$service = array();
		}
		$location_meta_keys = array(
			'_birs_location_phone', '_birs_location_address1',
			'_birs_location_address2', '_birs_location_city',
			'_birs_location_state', '_birs_location_country',
			'_birs_location_zip'
		);
		$location = $birchschedule->model->get( $appointment['_birs_appointment_location'], array(
			'base_keys' => array(
				'post_title', 'post_content'
			),
			'meta_keys' => $location_meta_keys
		) );
		if ( !$location ) {
			$location = array();
		}
		$appointment = array_merge( $service, $staff, $location, $appointment );
		$appointment['site_title'] = get_option( 'blogname' );
		$appointment['admin_email'] = get_option( 'admin_email' );
		return $appointment;
	};

	$ns->get_client_merge_values = function( $client_id ) use ( $ns ) {
		global $birchschedule;

		$client_meta_keys = $birchschedule->model->get_client_fields();
		$client = $birchschedule->model->get( $client_id, array(
			'meta_keys' => $client_meta_keys,
			'base_keys' => array( 'post_title' )
		) );
		if ( !$client ) {
			$client = array();
		}
		return $client;
	};

	$ns->get_appointment1on1_merge_values = function( $appointment1on1_id ) use ( $ns ) {
		global $birchschedule;

		$std_fields = $birchschedule->model->get_appointment1on1_fields();
		$custom_fields = $birchschedule->model->get_appointment1on1_custom_fields();
		$fields = array_merge( $std_fields, $custom_fields );
		$appointment1on1 = $birchschedule->model->get( $appointment1on1_id, array(
			'meta_keys' => $fields,
			'base_keys' => array( 'post_status' )
		) );
		if ( !$appointment1on1 ) {
			return array();
		}
		$appointment = $ns->get_appointment_merge_values( $appointment1on1['_birs_appointment_id'] );
		$client = $ns->get_client_merge_values( $appointment1on1['_birs_client_id'] );

		$appointment1on1 = array_merge( $appointment, $client, $appointment1on1 );

		$appointment1on1['_birs_appointment1on1_owing'] = $appointment1on1['_birs_appointment1on1_price'];
		$payments = $birchschedule->model->booking->get_payments_by_appointment1on1(
			$appointment1on1['_birs_appointment_id'], $appointment1on1['_birs_client_id']
		);
		foreach ( $payments as $payment ) {
			$appointment1on1['_birs_appointment1on1_owing'] -= $payment['_birs_payment_amount'];
		}
		$currency_code = $birchschedule->model->get_currency_code();
		$appointment1on1['_birs_appointment1on1_amount_due'] =
		$birchschedule->model->
		apply_currency_symbol( $appointment1on1['_birs_appointment1on1_owing'], $currency_code );

		return $appointment1on1;
	};

	$ns->get_merge_fields_map = function() {
		$merge_fields_map = array(
			'phone' => '_birs_location_phone',
			'address1' => '_birs_location_address1',
			'address2' => '_birs_location_address2',
			'city' => '_birs_location_city',
			'state' => '_birs_location_state',
			'province' => '_birs_location_state',
			'country' => '_birs_location_country',
			'zip_code' => '_birs_location_zip',
			'datetime' => '_birs_appointment_datetime',
			'client_first_name' => '_birs_client_name_first',
			'client_last_name' => '_birs_client_name_last',
			'deposit' => '_birs_appointment_pre_payment_fee_with_currency',
			'amount_due' => '_birs_appointment1on1_amount_due',
			'appointment_price' => '_birs_appointment1on1_price',
			'admin_email' => 'admin_email',
			'site_title' => 'site_title'
		);
		return $merge_fields_map;
	};

	$ns->get_merge_field_display_value = function( $field_value ) use ( $ns ) {
		$field_value_display = '';
		if ( is_array( $field_value ) ) {
			$field_value_display = implode( ',', $field_value );
		} else {
			$field_value_display = $field_value;
		}
		return $field_value_display;
	};

	$ns->apply_merge_field = function( $matches ) use ( $ns, $_ns_data ) {
		global $birchschedule;

		$name = $matches[1];
		$appointment = $_ns_data->appointment_for_merge_field;
		$map = $_ns_data->merge_fields_map;
		if ( isset( $appointment['_birs_client_country'] ) &&
			 $appointment['_birs_client_country'] !== 'US' ) {
			$map['client_state'] = '_birs_client_province';
		}
		if ( !isset( $appointment[$name] ) ) {
			if ( isset( $map[$name] ) ) {
				$name = $map[$name];
			} else {
				$name = '_birs_' . $name;
			}
		}
		if ( isset( $appointment[$name] ) ) {
			$field_value = $appointment[$name];
			$field_value_display =
			$birchschedule->model->mergefields->get_merge_field_display_value( $field_value );
			return $field_value_display;
		} else {
			return "";
		}
	};

	$ns->apply_merge_fields = function( $template, $appointment, $map = false ) use ( $ns, $_ns_data ) {
		global $birchschedule;

		$_ns_data->appointment_for_merge_field = $appointment;
		if ( $map === false ) {
			$_ns_data->merge_fields_map = $birchschedule->model->mergefields->get_merge_fields_map();
		} else {
			$_ns_data->merge_fields_map = $map;
		}
		$template = preg_replace_callback( '/\{([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\}/',
										   array( $ns, 'apply_merge_field' ), $template );
		return $template;
	};

} );
