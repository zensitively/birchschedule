<?php

add_filter( 'birchschedule_view_bookingform_validate_booking_info', 'birchschedule_validate_booking_form_info', 100 );

function birchschedule_validate_booking_form_info( $errors ) {
	return apply_filters( 'birchschedule_validate_booking_form_info', $errors );
}

add_filter( 'birchschedule_model_get_future_time', 'birchschedule_booking_preferences_future_time', 100 );

function birchschedule_booking_preferences_future_time( $future_time ) {
	return apply_filters( 'birchschedule_booking_preferences_future_time', $future_time );
}

add_filter( 'birchschedule_enotification_has_attachment', 'birchschedule_notification_has_attachment', 100, 3 );

function birchschedule_notification_has_attachment( $attach, $to, $template_name ) {
	return apply_filters( 'birchschedule_notification_has_attachment', $attach, $to, $template_name );
}

add_filter( 'birchschedule_enotification_if_appointment_changed',
	'birchschedule_notification_appointment_changed', 100, 4 );

function birchschedule_notification_appointment_changed( $changed, $new_appointment, $old_appointment, $to ) {
	$new_appointment = json_decode( json_encode( $new_appointment ), false );
	$old_appointment = json_decode( json_encode( $old_appointment ), false );
	return apply_filters( 'birchschedule_notification_appointment_changed',
		$changed, $new_appointment, $old_appointment, $to );
}

add_filter( 'birchpress_util_get_datetime_separator', 'birchschedule_datetime_separator', 100 );

function birchschedule_datetime_separator( $separator ) {
	return apply_filters( 'birchschedule_datetime_separator', $separator );
}

add_filter( 'birchschedule_model_schedule_get_staff_busy_time', 'birchschedule_staff_busy_time', 100, 4 );

function birchschedule_staff_busy_time( $busy_time, $staff_id, $location_id, $date ) {
	return apply_filters( 'birchschedule_staff_busy_time', $busy_time, $staff_id, $location_id, $date );
}

add_filter( 'birchschedule_model_schedule_get_staff_avaliable_time',
	'birchschedule_booking_time_options', 100, 5 );

function birchschedule_booking_time_options( $avaliable_times, $staff_id,
	$location_id, $service_id, $date ) {
	return apply_filters( 'birchschedule_booking_time_options', $avaliable_times, $service_id, $date );
}

add_filter( 'birchschedule_view_bookingform_get_booking_response',
	'birchschedule_ajax_booking_response', 100, 3 );

function birchschedule_ajax_booking_response( $response, $appointment_id, $errors ) {
	return apply_filters( 'birchschedule_ajax_booking_response', $response, $appointment_id, $errors );
}

add_filter( 'birchschedule_model_mergefields_get_appointment_merge_values',
	'birchschedule_get_appointment_merge_fields_values', 100, 2 );

function birchschedule_get_appointment_merge_fields_values( $appointment, $appointment_id ) {
	return apply_filters( 'birchschedule_get_appointment_merge_fields_values', $appointment, $appointment_id );
}

add_filter( 'birchschedule_icalendar_get_appointment_export_template',
	'birchschedule_appointment_export_template', 100 );

function birchschedule_appointment_export_template( $template ) {
	return apply_filters( 'birchschedule_appointment_export_template', $template );
}

add_filter( 'birchschedule_model_get_services_listing_order',
	'birchschedule_service_listing_order', 100 );

function birchschedule_service_listing_order( $order ) {
	return apply_filters( 'birchschedule_service_listing_order', $order );
}

add_filter( 'birchschedule_view_bookingform_get_fields_html',
	'birchschedule_booking_form_fields', 100 );

function birchschedule_booking_form_fields( $html ) {
	return apply_filters( 'birchschedule_booking_form_fields', $html );
}

add_action( 'birchschedule_model_booking_change_appointment1on1_status_after',
	function( $appointment1on1_id, $new_status, $old_status ) {
		global $birchpress;
		if ( !$birchpress->util->is_error( $old_status ) ) {
			do_action( 'birchschedule_model_booking_do_change_appointment1on1_status_after',
				$appointment1on1_id, $new_status, $old_status );
		}
	}, 10, 3 );

add_action( 'birchschedule_model_booking_reschedule_appointment1on1_after',
	function( $appointment1on1_id, $appointment_info, $old_appointment1on1 ) {
		global $birchpress;
		if ( $old_appointment1on1 && !$birchpress->util->is_error( $old_appointment1on1 ) ) {
			do_action( 'birchschedule_model_booking_do_reschedule_appointment1on1_after',
				$appointment1on1_id, $appointment_info, $old_appointment1on1 );
		}
	}, 10, 3 );
