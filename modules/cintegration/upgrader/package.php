<?php

birch_ns( 'birchschedule.cintegration.upgrader', function( $ns ) {

		global $birchschedule;

		$ns->init = function() use ( $ns, $birchschedule ) {
			$birchschedule->upgrade_module->when( $birchschedule->cintegration->is_module_cintegration, $ns->upgrade_module );
		};

		$ns->upgrade_module = function() use( $ns ) {
			$ns->upgrade_1_0_to_1_1();
			$ns->upgrade_1_1_to_1_2();
			$ns->upgrade_1_2_to_1_3();
		};

		$ns->get_db_version_calendar_integration = function() use( $ns ) {
			return get_option( 'birs_db_version_calendar_integration', '1.0' );
		};

		$ns->upgrade_1_0_to_1_1 = function() use( $ns, $birchschedule ) {
			global $birchpress;

			$version = $ns->get_db_version_calendar_integration();
			if ( $version !== '1.0' ) {
				return;
			}
			$staff = $birchpress->db->query(
				array(
					'post_type' => 'birs_staff'
				),
				array(
					'meta_keys' => array(
						'_birs_staff_ical_id'
					),
					'base_keys' => array()
				)
			);
			foreach ( $staff as $staff_id => $the_staff ) {
				if ( !$the_staff['_birs_staff_ical_id'] ) {
					$the_staff['_birs_staff_ical_id'] =
					$birchschedule->cintegration->generate_ical_id( $staff_id );
					$birchpress->db->save( $the_staff, array(
							'meta_keys' => array(
								'_birs_staff_ical_id'
							),
							'base_keys' => array()
						)
					);
				}
			}
			$appointments = $birchpress->db->query(
				array(
					'post_type' => 'birs_appointment'
				),
				array(
					'meta_keys' => array(
						'_birs_appointment_uid'
					),
					'base_keys' => array()
				)
			);
			foreach ( $appointments as $appointment_id => $appointment ) {
				if ( !$appointment['_birs_appointment_uid'] ) {
					$appointment['_birs_appointment_uid'] = uniqid( rand(), true );
					$birchpress->db->save( $appointment, array(
							'meta_keys' => array(
								'_birs_appointment_uid'
							),
							'base_keys' => array()
						)
					);
				}
			}
			update_option( 'birs_db_version_calendar_integration', '1.1' );
		};

		$ns->upgrade_1_1_to_1_2 = function() use( $ns, $birchschedule ) {
			$version = $ns->get_db_version_calendar_integration();
			if ( $version !== '1.1' ) {
				return;
			}
			$birchschedule->cintegration->force_sync();
			update_option( 'birs_db_version_calendar_integration', '1.2' );
		};

		$ns->upgrade_1_2_to_1_3 = function() use( $ns, $birchschedule ) {
			$version = $ns->get_db_version_calendar_integration();
			if ( $version !== '1.2' ) {
				return;
			}

			$staff = $birchschedule->model->query(
				array(
					'post_type' => 'birs_staff'
				),
				array(
					'keys' => array( 'post_type' )
				)
			);
			foreach ( $staff as $staff_id => $the_staff ) {
				$the_staff['_birs_staff_calendar_import_icals'] = array();
				$the_staff['_birs_staff_calendar_import_recurring_icals'] = array();
				$birchschedule->model->save( $the_staff, array(
						'meta_keys' => array(
							'_birs_staff_calendar_import_icals',
							'_birs_staff_calendar_import_recurring_icals',
						),
						'base_keys' => array()
					) );
			}
			$birchschedule->cintegration->force_sync();
			update_option( 'birs_db_version_calendar_integration', '1.3' );
		};

	} );
