<?php

birch_ns( 'birchschedule.model.cpt.client', function( $ns ) {

		global $birchschedule;

		$ns->init = function() use ( $ns, $birchschedule ) {
			$birchschedule->model->pre_save->when( $ns->is_model_client, $ns->pre_save );
			$birchschedule->model->save->when( $ns->is_model_client, $ns->save );
			$birchschedule->model->post_get->when( $ns->is_model_client, $ns->post_get );
		};

		$ns->is_model_client = function( $model ) {
			return $model['post_type'] === 'birs_client';
		};

		$ns->pre_save = function( $client, $config ) {
			birch_assert( is_array( $client ) && isset( $client['post_type'] ) );
			$name_first = '';
			$name_last = '';
			if ( isset( $client['_birs_client_name_first'] ) ) {
				$name_first = $client['_birs_client_name_first'];
			}
			if ( isset( $client['_birs_client_name_last'] ) ) {
				$name_last = $client['_birs_client_name_last'];
			}
			$client['post_title'] = $name_first . ' ' . $name_last;
			return $client;
		};

		$ns->save = function( $client, $config ) use( $ns, $birchschedule ) {
			return $birchschedule->model->save->call_default( $client, $config );
		};

		$ns->post_get = function( $client ) {
			birch_assert( is_array( $client ) && isset( $client['post_type'] ) );
			if ( isset( $client['_birs_client_name_first'] ) &&
				isset( $client['_birs_client_name_last'] ) ) {

				$client['_birs_client_name'] = $client['_birs_client_name_first'] . ' ' . $client['_birs_client_name_last'];
			}
			return $client;
		};

	} );
