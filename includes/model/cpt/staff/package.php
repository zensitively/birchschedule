<?php

birch_ns( 'birchschedule.model.cpt.staff', function( $ns ) {

		global $birchschedule;

		$ns->init = function() use ( $ns, $birchschedule ) {
			$birchschedule->model->pre_save->when( $ns->is_model_staff, $ns->pre_save );
			$birchschedule->model->post_get->when( $ns->is_model_staff, $ns->post_get );
		};

		$ns->is_model_staff = function( $model ) {
			return $model['post_type'] === 'birs_staff';
		};

		$ns->pre_save = function( $staff, $config ) {
			birch_assert( is_array( $staff ) && isset( $staff['post_type'] ) );

			if ( isset( $staff['_birs_assigned_services'] ) ) {
				$staff['_birs_assigned_services'] =
				serialize( $staff['_birs_assigned_services'] );
			}

			if ( isset( $staff['_birs_staff_schedule'] ) ) {
				$staff['_birs_staff_schedule'] =
				serialize( $staff['_birs_staff_schedule'] );
			}
			return $staff;
		};

		$ns->post_get = function( $staff ) {
			birch_assert( is_array( $staff ) && isset( $staff['post_type'] ) );
			if ( isset( $staff['post_title'] ) ) {
				$staff['_birs_staff_name'] = $staff['post_title'];
			}
			if ( isset( $staff['_birs_assigned_services'] ) ) {
				$assigned_services = $staff['_birs_assigned_services'];
				$assigned_services = unserialize( $assigned_services );
				$assigned_services = $assigned_services ? $assigned_services : array();
				$staff['_birs_assigned_services'] = $assigned_services;
			}
			if ( isset( $staff['_birs_staff_schedule'] ) ) {
				$schedule = $staff['_birs_staff_schedule'];
				if ( !isset( $schedule ) ) {
					$schedule = array();
				} else {
					$schedule = unserialize( $schedule );
				}
				$schedule = $schedule ? $schedule : array();
				$staff['_birs_staff_schedule'] = $schedule;
			}
			if ( isset( $staff['post_content'] ) ) {
				$staff['_birs_staff_description'] = $staff['post_content'];
			}
			return $staff;
		};

	} );
