<?php

birch_ns( 'birchschedule.fbuilder.field.clienttitle', function( $ns ) {

		global $birchschedule;

		$ns->init = function() use ( $ns, $birchschedule ) {
			$birchschedule->fbuilder->field->render_field_elements->when( $ns->is_field_type_client_title, $ns->render_field_elements );

			$birchschedule->fbuilder->field->render_options_editing->when( $ns->is_field_type_client_title, $ns->render_options_editing );

			$birchschedule->fbuilder->field->get_field_default_value->when( $ns->is_field_type_client_title, $ns->get_field_default_value );

			$birchschedule->fbuilder->field->render_field_editing->when( $ns->is_field_type_client_title, $ns->render_field_editing );
		};

		$ns->is_field_type_client_title = function( $field ) {
			return $field['type'] === 'client_title';
		};

		$ns->render_field_elements = function( $field, $value=false ) use( $ns, $birchschedule ) {
			$birchschedule->fbuilder->field->dropdown->render_field_elements( $field, $value );
		};

		$ns->render_options_editing = function( $field ) use( $ns, $birchschedule ) {
			$birchschedule->fbuilder->field->selectable->render_options_editing( $field );
		};

		$ns->get_field_default_value = function( $field ) use ( $ns, $birchschedule ) {
			return $birchschedule->fbuilder->field->selectable->get_field_default_value( $field );
		};

		$ns->render_field_editing = function( $field ) use ( $ns, $birchschedule ) {
			$birchschedule->fbuilder->field->selectable->render_field_editing( $field );
		};

	} );
