<?php

birch_ns( 'birchschedule.model.cpt.service', function( $ns ) {

		global $birchschedule;

		$ns->init = function() use ( $ns, $birchschedule ) {
			$birchschedule->model->pre_save->when( $ns->is_model_service, $ns->pre_save );
			$birchschedule->model->post_get->when( $ns->is_model_service, $ns->post_get );
		};

		$ns->is_model_service = function( $model ) {
			return $model['post_type'] === 'birs_service';
		};

		$ns->pre_save = function( $service, $config ) {
			if ( isset( $service['_birs_service_pre_payment_fee'] ) ) {
				$service['_birs_service_pre_payment_fee'] =
				serialize( $service['_birs_service_pre_payment_fee'] );
			}
			if ( isset( $service['_birs_assigned_staff'] ) ) {
				$service['_birs_assigned_staff'] =
				serialize( $service['_birs_assigned_staff'] );
			}
			return $service;
		};

		$ns->post_get = function( $service ) {
			birch_assert( is_array( $service ) && isset( $service['post_type'] ) );
			if ( isset( $service['_birs_service_pre_payment_fee'] ) ) {
				$service['_birs_service_pre_payment_fee'] =
				unserialize( $service['_birs_service_pre_payment_fee'] );
				if ( !$service['_birs_service_pre_payment_fee'] ) {
					$service['_birs_service_pre_payment_fee'] = array();
				}
			}
			if ( isset( $service['_birs_assigned_staff'] ) ) {
				$service['_birs_assigned_staff'] =
				unserialize( $service['_birs_assigned_staff'] );
				if ( !$service['_birs_assigned_staff'] ) {
					$service['_birs_assigned_staff'] = array();
				}
			}
			if ( isset( $service['post_title'] ) ) {
				$service['_birs_service_name'] = $service['post_title'];
			}

			if ( isset( $service['post_content'] ) ) {
				$service['_birs_service_description'] = $service['post_content'];
			}

			return $service;
		};

	} );
