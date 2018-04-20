<?php

birch_ns( 'birchschedule.model.cpt.payment', function( $ns ) {

		global $birchschedule;

		$ns->init = function() use ( $ns, $birchschedule ) {
			$birchschedule->model->pre_save->when( $ns->is_model_payment, $ns->pre_save );
			$birchschedule->model->post_get->when( $ns->is_model_payment, $ns->post_get );
		};

		$ns->is_model_payment = function( $model ) {
			return $model['post_type'] === 'birs_payment';
		};

		$ns->pre_save = function( $payment, $config ) {
			birch_assert( is_array( $payment ) && isset( $payment['post_type'] ) );

			if ( isset( $payment['_birs_payment_amount'] ) ) {
				$payment['_birs_payment_amount'] = floatval( $payment['_birs_payment_amount'] );
			}
			return $payment;
		};

		$ns->post_get = function( $payment ) {
			birch_assert( is_array( $payment ) && isset( $payment['post_type'] ) );
			$payment['_birs_payment_amount'] = floatval( $payment['_birs_payment_amount'] );
			return $payment;
		};

	} );
