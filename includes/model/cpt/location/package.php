<?php

birch_ns( 'birchschedule.model.cpt.location', function( $ns ) {
		global $birchschedule;

		$ns->init = function() use ( $ns, $birchschedule ) {
			$birchschedule->model->pre_save->when( $ns->is_model_location, $ns->pre_save );
			$birchschedule->model->post_get->when( $ns->is_model_location, $ns->post_get );
		};

		$ns->is_model_location = function( $model ) {
			return $model['post_type'] === 'birs_location';
		};

		$ns->pre_save = function( $location, $config ) {
			birch_assert( is_array( $location ) && isset( $location['post_type'] ) );
			return $location;
		};

		$ns->post_get = function( $location ) {
			birch_assert( is_array( $location ) && isset( $location['post_type'] ) );
			if ( isset( $location['post_title'] ) ) {
				$location['_birs_location_name'] = $location['post_title'];
			}
			if ( isset( $location['post_content'] ) ) {
				$location['_birs_location_description'] = $location['post_content'];
			}
			return $location;
		};

	} );
