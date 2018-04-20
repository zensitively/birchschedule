<?php

birch_ns( 'birchschedule.cintegration', function( $ns ) {

		global $birchschedule;

		if ( !class_exists( 'TVarDumper' ) ) {
			require_once $birchschedule->plugin_dir_path() . 'lib/prado/TVarDumper.php';
		}
		if ( version_compare( PHP_VERSION, '7.0.0' ) >= 0 ) {
			require_once $birchschedule->plugin_dir_path() . 'lib/sabre4/autoload.php';
		} else {
			require_once $birchschedule->plugin_dir_path() . 'lib/vendor/autoload.php';
		}

		$_ns_data = new stdClass();

		$ns->init = function() use( $ns, $birchschedule ) {
			global $birchpress;

			add_action( 'init', array( $ns, 'wp_init' ) );

			add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );

			register_activation_hook( $birchschedule->plugin_file_path(),
				array( $ns, 'force_sync' ) );

			add_action( 'upgrader_process_complete',
				array( $ns, 'force_sync' ) );

			add_filter( 'birchschedule_model_cpt_staff_post_get',
				array( $ns, 'post_get_staff' ) );

			add_filter( 'birchschedule_model_cpt_staff_pre_save',
				array( $ns, 'pre_save_staff' ), 20, 3 );

			add_action( 'wp', array( $ns, 'schedule_sync_icals' ) );

			$birchpress->util->enable_remote_call( $ns->sync_icals );
		};

		$ns->is_module_cintegration = function( $module ) {
			return $module['module'] === 'cintegration';
		};

		$ns->wp_init = function() use( $ns ) {
			$ical_request = $ns->is_icalendar_url( $_SERVER['REQUEST_URI'] );
			if ( $ical_request ) {
				$ns->render_icalendar();
				die;
			}

			add_filter( 'birchschedule_model_schedule_get_staff_busy_time',
				array( $ns, 'add_imported_busy_events' ), 20, 4 );
			add_filter( 'birchschedule_model_get_staff_daysoff',
				array( $ns, 'add_imported_days_off' ), 20, 2 );

			add_action( 'birchschedule.cintegration.sync_icals', array( $ns, 'sync_icals' ) );
			add_filter( 'cron_schedules', array( $ns, 'add_sync_icals_interval' ) );
		};

		$ns->wp_admin_init = function() use( $ns ) {
			add_action( 'birchschedule_view_staff_load_page_edit_after',
				array( $ns, 'add_meta_boxes' ), 20 );

			add_action( 'birchschedule_view_staff_save_post_after',
				array( $ns, 'save_staff_data' ) );

			add_action( 'wp_ajax_birchscedule_cintegration_new_calendar_import_url',
				array( $ns, 'ajax_new_calendar_import_url' ) );

			add_filter( 'birchschedule_view_calendar_query_appointments',
				array( $ns, 'add_imported_events' ), 20, 5 );
		};

		$ns->if_sync_past_events = function() use ( $ns ) {
			return false;
		};

		$ns->force_sync = function() use ( $ns ) {
			global $birchpress;

			$birchpress->util->async_run_task( array(
					'action' => 'birchschedule.cintegration.sync_icals',
					'args' => array()
				) );
		};

		$ns->is_icalendar_url = function( $url ) use( $ns ) {
			$ical_request = preg_match( '/\?icalendar=/', $url );
			if ( !$ical_request ) {
				$parsed = parse_url( $url );
				$path = $parsed['path'];
				$path_parts = explode( '/', $path );
				$parts_size = sizeof( $path_parts );
				if ( $parts_size >= 5 ) {
					if ( !strcasecmp( $path_parts[$parts_size - 3], 'scheduler' ) &&
						!strcasecmp( $path_parts[$parts_size - 2], 'icalendar' ) ) {
						$ical_request = true;
					}
				}
			}
			return $ical_request;
		};

		$ns->get_icalendar_id = function( $url ) use( $ns ) {
			$ical_id = '';
			if ( isset( $_GET['icalendar'] ) ) {
				$ical_id = $_GET['icalendar'];
			} else {
				$parsed = parse_url( $url );
				$path = $parsed['path'];
				$path_parts = explode( '/', $path );
				$parts_size = sizeof( $path_parts );
				if ( $parts_size >= 5 ) {
					$ical_id = $path_parts[$parts_size - 1];
				}
			}
			return $ical_id;
		};

		$ns->schedule_sync_icals = function() use( $ns ) {
			if ( !wp_next_scheduled( 'birchschedule.cintegration.sync_icals' ) ) {
				wp_schedule_event( time(), 'birs_sync_icals_interval',
					'birchschedule.cintegration.sync_icals' );
			}
		};

		$ns->add_sync_icals_interval = function( $schedules ) use( $ns ) {
			$schedules['birs_sync_icals_interval'] = array(
				'interval' => 60 * 10,
				'display' => __( 'Sync iCal Interval', 'birchschedule' )
			);
			return $schedules;
		};

		$ns->get_ical_content = function( $import_url ) use( $ns ) {
			$ical_response = wp_remote_get( $import_url, array(
					'timeout' => 300,
					'httpversion' => '1.0'
				) );
			if ( is_array( $ical_response ) && 200 == $ical_response['response']['code'] ) {
				$ical_content = $ical_response['body'];
			} else {
				$ical_content = '';
			}
			return $ical_content;
		};

		$ns->sync_icals = function() use( $ns, $birchschedule ) {
			global $birchpress;

			$max_execution_time = ini_get( 'max_execution_time' );
			@set_time_limit( 0 );
			$staff = $birchschedule->model->query(
				array(
					'post_type' => 'birs_staff'
				),
				array(
					'meta_keys' => array(
						'_birs_staff_calendar_import_urls',
						'_birs_staff_calendar_import_icals',
						'_birs_staff_calendar_import_recurring_icals',
						'_birs_staff_color',
					),
					'base_keys' => array()
				)
			);
			foreach ( $staff as $thestaff ) {
				$calendar_color = $thestaff['_birs_staff_color'];
				$import_urls = $thestaff['_birs_staff_calendar_import_urls'];
				$import_icals = $thestaff['_birs_staff_calendar_import_icals'];
				$import_recurring_icals = $thestaff['_birs_staff_calendar_import_recurring_icals'];

				$new_import_icals = array();
				$new_import_recurring_icals = array();

				foreach ( $import_urls as $import_id => $import_url ) {
					$import_url = str_replace( array( 'WEBCALS://', 'webcals://' ),
						'https://', trim( $import_url ) );
					$import_url = str_replace( array( 'WEBCAL://', 'webcal://' ),
						'http://', $import_url );
					$ical_content = $ns->get_ical_content( $import_url );
					$results = $ns->convert_ical_to_appointments( $ical_content, $thestaff );
					if ( $ical_content && !$birchpress->util->is_error( $results ) ) {
						$new_import_icals[$import_id] = $results['appointments'];
						$new_import_recurring_icals[$import_id] = $results['recurring_ical'];
					} else {
						if ( !empty( $import_icals[$import_id] ) ) {
							$new_import_icals[$import_id] = $import_icals[$import_id];
						} else {
							$new_import_icals[$import_id] = array();
						}

						if ( !empty( $import_recurring_icals[$import_id] ) ) {
							$new_import_recurring_icals[$import_id] = $import_recurring_icals[$import_id];
						} else {
							$new_import_recurring_icals[$import_id] = '';
						}
					}
				}
				$thestaff['_birs_staff_calendar_import_icals'] = $new_import_icals;
				$thestaff['_birs_staff_calendar_import_recurring_icals'] = $new_import_recurring_icals;

				$birchschedule->model->save( $thestaff, array(
						'meta_keys' => array(
							'_birs_staff_calendar_import_icals',
							'_birs_staff_calendar_import_recurring_icals',
						),
						'base_keys' => array()
					) );
			}
			$birchschedule->model->booking->async_recheck_fully_booked_days();
			@set_time_limit( $max_execution_time );
		};

		$ns->post_get_staff = function( $staff ) use( $ns ) {
			if ( empty( $staff['_birs_staff_ical_id'] ) ) {
				$staff['_birs_staff_ical_id'] = '';
			}

			if ( ! empty( $staff['_birs_staff_calendar_import_urls'] ) ) {
				// TODO: potential error if special chars.
				$calendar_import_urls = unserialize( $staff['_birs_staff_calendar_import_urls'] );
			} else {
				$calendar_import_urls = array();
			}
			$staff['_birs_staff_calendar_import_urls'] = $calendar_import_urls;

			if ( ! empty( $staff['_birs_staff_calendar_import_icals'] ) ) {
				// NOTE: updater is not necessary if we allow failure once.
				$calendar_import_icals = json_decode( base64_decode( $staff['_birs_staff_calendar_import_icals'] ), true );
			} else {
				$calendar_import_icals = array();
			}
			$staff['_birs_staff_calendar_import_icals'] = $calendar_import_icals;

			if ( ! empty( $staff['_birs_staff_calendar_import_recurring_icals'] ) ) {
				$calendar_import_recurring_icals = json_decode( base64_decode( $staff['_birs_staff_calendar_import_recurring_icals'] ), true );
			} else {
				$calendar_import_recurring_icals = array();
			}
			$staff['_birs_staff_calendar_import_recurring_icals'] = $calendar_import_recurring_icals;

			return $staff;
		};

		$ns->pre_save_staff = function( $staff, $staff_orig, $config ) use( $ns ) {
			if ( isset( $staff['_birs_staff_calendar_import_urls'] ) ) {
				$staff['_birs_staff_calendar_import_urls'] =
				serialize( $staff['_birs_staff_calendar_import_urls'] );
			}
			if ( isset( $staff['_birs_staff_calendar_import_icals'] ) ) {
				$staff['_birs_staff_calendar_import_icals'] =
				base64_encode( json_encode( $staff['_birs_staff_calendar_import_icals'] ) );
			}
			if ( isset( $staff['_birs_staff_calendar_import_recurring_icals'] ) ) {
				$staff['_birs_staff_calendar_import_recurring_icals'] =
				base64_encode( json_encode( $staff['_birs_staff_calendar_import_recurring_icals'] ) );
			}

			return $staff;
		};

		$ns->ajax_new_calendar_import_url = function() use( $ns ) {
			$new_import_url_id = uniqid( rand() );
			$ns->render_import_url( $new_import_url_id, "" );
			die;
		};

		$ns->add_imported_events = function( $appointments, $start, $end, $location_id, $staff_id ) use ( $ns, $birchschedule ) {
			global $birchpress;

			$staff_ids = array();
			if ( $staff_id == -1 ) {
				$staff = $birchschedule->model->query(
					array(
						'post_type' => 'birs_staff'
					),
					array(
						'meta_keys' => array(),
						'base_keys' => array()
					)
				);
				$staff_ids = array_keys( $staff );
			} else {
				$staff_ids[] = $staff_id;
			}
			foreach ( $staff_ids as $staff_id ) {
				$imported_appointments = $ns->get_imported_appointments( $staff_id, $start, $end );
				foreach ( $imported_appointments as $imported_appointment ) {
					$imported_start = $imported_appointment['start'];
					$imported_end = $imported_appointment['end'];
					if ( $ns->if_imported_event_affect( $imported_appointment, $start, $end ) ) {
						$imported_appointment['start'] = $birchpress->util->get_wp_datetime( $imported_appointment['start'] )->format( 'c' );
						$imported_appointment['end'] = $birchpress->util->get_wp_datetime( $imported_appointment['end'] )->format( 'c' );
						$appointments[] = $imported_appointment;
					}
				}
			}

			return $appointments;
		};

		$ns->if_imported_event_affect = function( $imported_appointment, $start, $end ) {
			$imported_start = $imported_appointment['start'];
			$imported_end = $imported_appointment['end'];
			$cond = ! ( ( $imported_end <= $start ) || ( $imported_start >= $end ) );
			$cond1 = ( $imported_end == $start ) && $imported_appointment['allDay'];
			$cond = $cond || $cond1;
			return $cond;
		};

		$ns->add_imported_days_off = function( $days_off, $staff_id ) use( $ns ) {
			global $birchpress;
			$days_off = json_decode( $days_off );
			$imported_appointments =
			$ns->get_imported_appointments( $staff_id );
			foreach ( $imported_appointments as $imported_appointment ) {
				if ( $imported_appointment['allDay'] ) {
					$start = $imported_appointment['start'];
					$end = $imported_appointment['end'];
					while ( $start <= $end ) {
						$datetime = $birchpress->util->get_wp_datetime( $start );
						$days_off[] = $datetime->format( 'm/d/Y' );
						$start += 60 * 60 * 24;
					}
				}
			}
			$days_off = json_encode( $days_off );
			return $days_off;
		};

		$ns->add_imported_busy_events = function( $busy_times, $staff_id, $location_id, $date ) use ( $ns ) {
			global $birchpress;

			$start = $date->format( 'U' );
			$end = $start + 3600 * 24;
			$imported_appointments = $ns->get_imported_appointments( $staff_id, $start, $end );
			foreach ( $imported_appointments as $imported_appointment ) {
				if ( $ns->if_imported_event_affect( $imported_appointment, $start, $end ) ) {
					$imported_start = $imported_appointment['start'];
					$imported_end = $imported_appointment['end'];

					if ( $imported_appointment['allDay'] ) {
						$start_minutes = 0;
						$duration = 24 * 60;
					} else {
						$busy_start = max( array( $start, $imported_start ) );
						$busy_end = min( array( $end, $imported_end ) );
						$start_datetime = $birchpress->util->get_wp_datetime( $busy_start );
						$start_minutes = $birchpress->util->get_day_minutes( $start_datetime );
						$duration = ( $busy_end - $busy_start ) / 60;
					}
					$busy_times[] = array(
						'busy_time' => $start_minutes,
						'duration' => $duration
					);
				}
			}
			return $busy_times;
		};

		$ns->is_vevent_free = function( $e ) {
			return isset( $e->TRANSP ) && strtoupper( $e->TRANSP ) == 'TRANSPARENT';
		};

		$ns->is_vevent_from_scheduler = function( $e ) use ( $ns, $birchschedule ) {
			$description = $e->DESCRIPTION;
			$end_symbol = $birchschedule->gcalsync->get_end_symbol();
			return stripos( $description, $end_symbol ) !== false;
		};

		$ns->get_vevent_title = function( $e ) {
			return __( 'BLOCKED', 'birchschedule' );
		};

		$ns->convert_vevent_to_appointment = function( $e, $timezone, $staff ) use( $ns, $_ns_data ) {
			if ( $e->RRULE ) {
				return false;
			}

			if ( $ns->is_vevent_free( $e ) ) {
				return false;
			}

			if ( $ns->is_vevent_from_scheduler( $e ) ) {
				return false;
			}

			if ( empty( $e->DTSTART ) ) {
				return false;
			}

			$appointment = array(
				'id' => uniqid( rand() ),
				'title' => $ns->get_vevent_title( $e ),
				'editable' => false,
			);

			$allday = ( ! $e->DTSTART->hasTime() ) || $e->{'X-MICROSOFT-CDO-ALLDAYEVENT'} == 'TRUE';

			if ( version_compare( PHP_VERSION, '7.0.0' ) >= 0 ) {
				$start = $e->DTSTART->getDateTime( $timezone );
				$end = null;
				if ( isset( $e->DTEND ) ) {
					$end = $e->DTEND->getDateTime( $timezone );
				} elseif ( isset( $e->DURATION ) ) {
					$end = $end->add( $e->DURATION->getDateInterval() );
				} elseif ( $allday ) {
					$end = $end->modify( '+1 day' );
				} else {
					$end = $start;
				}
			} else {
				$start = $e->DTSTART->getDateTime( $timezone );
				$end = null;
				if ( isset( $e->DTEND ) ) {
					$end = $e->DTEND->getDateTime( $timezone );
				} elseif ( isset( $e->DURATION ) ) {
					$end = clone $start;
					$end->add( $e->DURATION->getDateInterval() );
				} elseif ( $allday ) {
					$end = clone $start;
					$end->modify( '+1 day' );
				} else {
					$end = clone $start;
				}
			}

			$start = $start->format( 'U' );
			$end = $end->format( 'U' );

			if ( !$ns->if_sync_past_events() ) {
				if ( $end < time() ) {
					return false;
				}
			}

			if ( $allday && $start < $end ) {
				$end -= 60 * 60 * 24;
			}
			$appointment['start'] = $start;
			$appointment['end'] = $end;
			$appointment['allDay'] = $allday;
			$appointment['color'] = $staff['_birs_staff_color'];

			return $appointment;
		};

		$ns->figure_out_timezone = function( $vcalendar ) use( $ns ) {
			global $birchpress;

			try {
				$timezone = new DateTimeZone( $vcalendar->{'X-WR-TIMEZONE'} );
			} catch ( Exception $e ) {
				$timezone = $birchpress->util->get_wp_timezone();
			}

			return $timezone;
		};

		$ns->convert_ical_to_appointments = function( $ical_content, $staff ) use( $ns ) {

			global $birchpress;

			$results = array(
				'appointments' => array(),
				'recurring_ical' => ''
			);

			if ( empty( $ical_content ) ) {
				return $results;
			}

			try {
				$v = Sabre\VObject\Reader::read( $ical_content,
					Sabre\VObject\Reader::OPTION_FORGIVING );
			} catch ( Exception $e ) {
				$error = $birchpress->util->new_error( 'parse_exception' );
				return $error;
			}

			if ( $v->VEVENT ) {
				$timezone = $ns->figure_out_timezone( $v );
				$non_recurring_events = array();
				foreach ( $v->VEVENT as $e ) {
					if ( empty ( $e->RRULE ) ) {
						$appointment = $ns->convert_vevent_to_appointment( $e, $timezone, $staff );
						if ( $appointment ) {
							$results['appointments'][] = $appointment;
						}
						$non_recurring_events[] = $e;
					}
				}
				foreach ( $non_recurring_events as $e ) {
					$v->remove( $e );
				}
				$results['recurring_ical'] = $v->serialize();
			}

			return $results;
		};

		$ns->get_imported_appointments = function( $staff_id, $start = null, $end = null ) use( $ns, $birchschedule ) {
			global $birchpress;

			$staff = $birchschedule->model->get( $staff_id, array(
					'meta_keys' => array(
						'_birs_staff_color',
						'_birs_staff_calendar_import_icals',
						'_birs_staff_calendar_import_recurring_icals',
					),
					'base_keys' => array()
				)
			);
			$calendar_color = $staff['_birs_staff_color'];
			$imported_icals = $staff['_birs_staff_calendar_import_icals'];
			$imported_recurring_icals = $staff['_birs_staff_calendar_import_recurring_icals'];

			$appointments = array();
			if( ! is_array( $imported_icals ) ) {
				return $appointments;
			}
			foreach ( $imported_icals as $imported_url_id => $imported_ical ) {
				if ( is_array( $imported_ical ) ) {
					$appointments = array_merge( $appointments, $imported_ical );
				}
			}

			if ( null === $start || null === $end || empty( $imported_recurring_icals ) ) {
				return $appointments;
			}

			foreach ( $imported_recurring_icals as $imported_url_id => $imported_recurring_ical ) {
				if ( empty( $imported_recurring_ical ) ) {
					continue;
				}

				try {
					$v = Sabre\VObject\Reader::read( $imported_recurring_ical,
						Sabre\VObject\Reader::OPTION_FORGIVING );
				} catch ( Exception $ex ) {
					continue;
				}

				if ( $v->VEVENT ) {
					$timezone = $ns->figure_out_timezone( $v );
					try {
						$v->expand( $birchpress->util->get_wp_datetime( $start ),
							$birchpress->util->get_wp_datetime( $end ), $timezone );
						if ( $v->VEVENT ) {
							foreach ( $v->VEVENT as $e ) {
								$appointment = $ns->convert_vevent_to_appointment( $e, $timezone, $staff );
								if ( $appointment ) {
									$appointments[] = $appointment;
								}
							}
						}
					} catch ( Exception $ex ) {
						continue;
					}
				}
			}

			return $appointments;
		};

		$ns->get_export_appointments_time_start = function() {
			return time() - 60 * 60 * 24 * 30;
		};

		$ns->get_export_appointments_time_end = function() {
			return time() + 60 * 60 * 24 * 365;
		};

		$ns->change_appointment_description = function( $description, $appointment ) {
			$description .= "\n" . $appointment['_birs_appointment_admin_url'];
			return $description;
		};

		$ns->render_icalendar = function() use( $ns, $birchschedule ) {
			$ical_id = $ns->get_icalendar_id( $_SERVER['REQUEST_URI'] );
			$staff = $birchschedule->model->query(
				array(
					'post_type' => 'birs_staff',
					'meta_query' => array(
						array(
							'key' => '_birs_staff_ical_id',
							'value' => $ical_id
						)
					)
				),
				array(
					'meta_keys' => array(),
					'base_keys' => array()
				)
			);
			$staff_id = 0;
			if ( $staff ) {
				$staff_ids = array_keys( $staff );
				$staff_id = $staff_ids[0];
			}
			$start = $ns->get_export_appointments_time_start();
			$end = $ns->get_export_appointments_time_end();
			$criteria = array(
				'start' => $start,
				'end' => $end,
				'location_id' => -1,
				'staff_id' => $staff_id
			);
			$appointments = $birchschedule->model->booking->query_appointments( $criteria, array(
					'client_keys' => array( 'post_title' )
				) );
			add_filter( 'birchschedule_icalendar_get_appointment_description',
				array( $ns, 'change_appointment_description' ), 50, 2 );
			$birchschedule->icalendar->export_appointments_as_ics( $appointments );
		};

		$ns->generate_ical_id = function( $staff_id ) {
			return uniqid( $staff_id );
		};

		$ns->save_staff_data = function( $post ) use( $ns, $birchschedule ) {
			$staff = $birchschedule->model->get( $post['ID'], array(
					'meta_keys' => array(
						'_birs_staff_ical_id',
					),
					'base_keys' => array()
				) );
			if ( !$staff['_birs_staff_ical_id'] ) {
				$staff['_birs_staff_ical_id'] = $ns->generate_ical_id( $post['ID'] );
			}
			if ( isset( $_POST['birs_staff_calendar_import_urls'] ) ) {
				$staff['_birs_staff_calendar_import_urls'] = $_POST['birs_staff_calendar_import_urls'];
			} else {
				$staff['_birs_staff_calendar_import_urls'] = array();
			}
			$birchschedule->model->save( $staff, array(
					'keys' => array(
						'_birs_staff_calendar_import_urls',
						'_birs_staff_ical_id',
					)
				) );

			$ns->force_sync();
		};

		$ns->add_meta_boxes = function() use( $ns ) {
			add_meta_box( 'birchschedule-staff-calendar-integration',
				__( 'Calendar Integration', 'birchschedule' ),
				array( $ns, 'render_staff_calendar_integration' ),
				'birs_staff', 'normal', 'default' );
		};

		$ns->get_icalendar_url = function( $ical_id ) {
			return home_url( '/index.php/birchpress/scheduler/icalendar/' . urlencode( $ical_id ) ) ;
		};

		$ns->render_staff_calendar_integration = function( $post ) use( $ns, $birchschedule ) {
			$staff = $birchschedule->model->get(
				$post->ID,
				array(
					'meta_keys' => array(
						'_birs_staff_ical_id',
						'_birs_staff_calendar_import_urls'
					),
					'base_keys' => array()
				)
			);
			$calendar_address =
			$ns->get_icalendar_url( $staff['_birs_staff_ical_id'] );
			$import_urls = $staff['_birs_staff_calendar_import_urls'];
?>
        <style type="text/css">
            #birs_staff_calendar_add_import_url {
                margin-top: 4px;
            }
            .birs_staff_calendar_import_url_delete {
                float: right;
            }
        </style>
        <div class="panel-wrap birchschedule">
            <table class="form-table">
                <tr>
                    <th>
                        <label for='birs_staff_calendar_address'>
                            <?php echo __( 'Calendar Address (iCal)', 'birchschedule' ); ?>
                        </label>
                    </th>
                    <td>
                        <input type="text" class="regular-text" readonly value= "<?php echo $calendar_address; ?>" />
                    </td>
                </tr>
                <tr>
                    <th>
                        <label>
                            <?php echo __( 'Import Calendars (iCal)', 'birchschedule' ); ?>
                        </label>
                    </th>
                    <td>
                        <div>
                            <div id="birs_staff_calendar_import_urls">
                                <?php
			foreach ( $import_urls as $import_url_id => $import_url ) {
				$ns->render_import_url( $import_url_id, $import_url );
			}
?>
                            </div>
                            <div id="birs_staff_calendar_add_import_url">
                                <a href="javascript:void(0);">
                                    <?php _e( '+ Add by URL', 'birchschedule' ); ?>
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        <script type="text/javascript">
            //<![CDATA[
            jQuery(document).ready( function($) {
                var ajaxUrl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
                $('#birs_staff_calendar_add_import_url').click(function(){
                    var postData = {
                        action: 'birchscedule_cintegration_new_calendar_import_url'
                    };
                    $.post(ajaxUrl, postData, function(data, status, xhr){
                        $('#birs_staff_calendar_import_urls').append(data);
                    }, 'html');
                });
            });
            //]]>
        </script>
<?php
		};

		$ns->render_import_url = function( $url_id, $import_url ) use( $ns ) {
			$dom_id = "birs_staff_calendar_import_url_$url_id";
?>
        <div id="<?php echo $dom_id; ?>">
            <input type="text"
                    name="birs_staff_calendar_import_urls[<?php echo $url_id; ?>]"
                    class="regular-text"
                    value="<?php echo $import_url; ?>" />
            <a href="javascript:void(0);"
                class="birs_staff_calendar_import_url_delete"
                data-url-id="<?php echo $url_id; ?>">
                <?php _e( 'Delete', 'birchschedule' ); ?>
            </a>
        </div>
        <script type="text/javascript">
            //<![CDATA[
            jQuery(document).ready( function($) {
                var domId = '<?php echo $dom_id; ?>';
                $('#' + domId + ' .birs_staff_calendar_import_url_delete').click(function() {
                    $('#' + domId).remove();
                });
            });
            //]]>
        </script>
<?php
		};

	} );
