<?php

birch_ns( 'birchschedule.gcalsync', function( $ns ) {

		global $birchschedule;

		$ns->init = function() use ( $ns ) {
				global $birchpress;

				add_action( 'init', array( $ns, 'wp_init' ) );

				add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );

				add_action( 'birchschedule_view_register_common_scripts_after',
					array( $ns, 'register_scripts' ) );

				$birchpress->util->enable_remote_call( $ns->update_event );
				$birchpress->util->enable_remote_call( $ns->delete_event );
			};

		$ns->wp_init = function() use ( $ns ) {
				$credentials = $ns->get_credentials();
				if ( $credentials ) {
					add_action( 'birchschedule_model_booking_change_appointment1on1_status_after',
						array( $ns, 'on_appointment1on1_status_change' ), 20, 3 );

					add_action( 'birchschedule_model_booking_reschedule_appointment1on1_after',
						array( $ns, 'on_appointment1on1_reschedule' ), 20, 3 );

					add_action( 'birchschedule_model_booking_cancel_appointment1on1_after',
						array( $ns, 'on_appointment1on1_cancel' ), 20, 2 );
				}
			};

		$ns->wp_admin_init = function() use ( $ns ) {
				$credentials = $ns->get_credentials();
				if ( $credentials ) {
					add_action( 'birchschedule_cintegration_render_staff_calendar_integration_after',
						array( $ns, 'render_staff_sync_settings' ) );

					add_action( 'birchschedule_view_staff_enqueue_scripts_edit_after',
						array( $ns, 'enqueue_scripts' ) );

					add_action( 'wp_ajax_birchschedule_gcalsync_authorize',
						array( $ns, 'ajax_authorize' ) );

					add_action( 'birchschedule_view_staff_save_post_after',
						array( $ns, 'save_staff_data' ) );
				}
			};

		$ns->get_credentials = function() use( $ns ) {
				return get_option( 'birs_google_api_credentials', false );
			};

		$ns->ajax_authorize = function() use ( $ns, $birchschedule ) {
				if ( empty( $_POST['birs_staff_gcal_authorization_code'] ) ) {
					exit;
				}
				$code = trim( $_POST['birs_staff_gcal_authorization_code'] );
				try {
					$gclient = $ns->create_google_client();
					$access_token = $gclient->authenticate( $code );
					$staff_id = $_POST['birs_staff_id'];
					$ns->save_access_token( $staff_id, $access_token );
				}
				catch( Exception $ex ) {
				}
				exit;
			};

		$ns->get_appointment = function( $appointment_id ) use ( $ns, $birchschedule ) {
				$appointment_keys = $birchschedule->model->get_appointment_fields();
				$client_keys = $birchschedule->model->get_client_fields();
				$appointment = $birchschedule->model->booking->get_appointment( $appointment_id, array(
						'status' => 'publish',
						'appointment_keys' => $appointment_keys,
						'client_keys' => $client_keys
					) );
				return $appointment;
			};

		$ns->on_appointment1on1_status_change = function( $appointment1on1_id, $new_status, $old_status ) use( $ns, $birchschedule ) {
				if ( $new_status !== 'publish' ) {
					return;
				}
				$appointment1on1 = $birchschedule->model->get( $appointment1on1_id, array(
						'keys' => array( '_birs_appointment_id' )
					) );
				$appointment_id = $appointment1on1['_birs_appointment_id'];
				$appointment = $ns->get_appointment( $appointment_id );
				$ns->async_update_event( $appointment );
			};

		$ns->on_appointment1on1_reschedule = function( $appointment1on1_id, $appointment_info, $old_appointment1on1 ) use( $ns, $birchschedule ) {
				global $birchpress;
				if ( !$old_appointment1on1 || $birchpress->util->is_error( $old_appointment1on1 ) ) {
					return;
				}
				$appointment1on1 = $birchschedule->model->get( $appointment1on1_id, array(
						'keys' => array( '_birs_appointment_id' )
					) );
				$appointment_id = $appointment1on1['_birs_appointment_id'];
				$appointment = $ns->get_appointment( $appointment_id );
				$old_appointment = $ns->get_appointment( $old_appointment1on1['_birs_appointment_id'] );
				$ns->async_update_event( $appointment );
				if ( $old_appointment ) {
					$ns->async_update_event( $old_appointment );
				} else {
					$ns->async_delete_event( $old_appointment1on1 );
				}
			};

		$ns->on_appointment1on1_cancel = function( $appointment1on1_id, $appointment1on1 ) use( $ns, $birchschedule ) {
				if ( !$appointment1on1 ) {
					return;
				}
				$appointment_id = $appointment1on1['_birs_appointment_id'];
				$appointment = $ns->get_appointment( $appointment_id );
				if ( $appointment ) {
					$ns->async_update_event( $appointment );
				} else {
					$ns->async_delete_event( $appointment1on1 );
				}
			};

		$ns->create_event_id = function( $appointment_uid ) {
				return $appointment_uid;
			};

		$ns->get_quota_user = function() {
				$str = preg_replace('#^https?://#', '', home_url());
				return $str;
			};

		$ns->update_event = function( $appointment ) use ( $ns, $birchschedule ) {
				global $birchpress;
				try {
					$staff_id = $appointment['_birs_appointment_staff'];
					$appointment_uid = $appointment['_birs_appointment_uid'];
					$event_id = $ns->create_event_id( $appointment_uid );
					$calendar_id = $ns->get_gcal_calendar_id( $staff_id );
					if ( $calendar_id == -1 ) {
						return;
					}
					if( $ns->is_authorized( $staff_id ) !== true ) {
						return;
					}
					$gclient = $ns->create_google_client();
					$access_token = $ns->get_access_token( $staff_id );
					$gclient->setAccessToken( $access_token );
					$service = new Google_Service_Calendar( $gclient );
					try {
						$event = $service->events->get( $calendar_id, $event_id, array(
								'quotaUser' => $ns->get_quota_user()
							) );
						$action = 'update';
					} catch( Exception $ex ) {
						if ( $ex->getCode() == 404 ) {
							$event = new Google_Service_Calendar_Event();
							$action = 'insert';
						} else {
							return;
						}
					}
					$summary = $ns->get_appointment_summary( $appointment );
					$description = $ns->get_appointment_description( $appointment );
					$event->setSummary( $summary );
					$event->setDescription( $description );
					$event->setStatus( 'confirmed' );
					$duration = intval( $appointment['_birs_appointment_duration'] );
					$time_start = $appointment['_birs_appointment_timestamp'];
					$time_end = $time_start + $duration * 60;
					$time_start = $birchpress->util->get_wp_datetime( $time_start )->format( 'c' );
					$time_end = $birchpress->util->get_wp_datetime( $time_end )->format( 'c' );
					$start = new Google_Service_Calendar_EventDateTime();
					$start->setDateTime( $time_start );
					$event->setStart( $start );
					$end = new Google_Service_Calendar_EventDateTime();
					$end->setDateTime( $time_end );
					$event->setEnd( $end );
					$source = new Google_Service_Calendar_EventSource();
					$source->setTitle( $summary );
					$url = $appointment['_birs_appointment_admin_url'];
					$source->setUrl( $url );
					$event->setSource( $source );
					if ( $action === 'update' ) {
						$service->events->update( $calendar_id, $event->getId(), $event, array(
								'quotaUser' => $ns->get_quota_user()
							)  );
					} else {
						$event->setId( $event_id );
						$service->events->insert( $calendar_id, $event, array(
								'quotaUser' => $ns->get_quota_user()
							) );
					}
				} catch ( Exception $ex ) {
					birch_log( $ex->getMessage() );
				}
			};

		$ns->delete_event = function( $appointment ) use ( $ns, $birchschedule ) {
				try {
					$staff_id = $appointment['_birs_appointment_staff'];
					$appointment_uid = $appointment['_birs_appointment_uid'];
					$calendar_id = $ns->get_gcal_calendar_id( $staff_id );
					if ( $calendar_id == -1 ) {
						return;
					}
					if( $ns->is_authorized( $staff_id ) !== true ) {
						return;
					}
					$gclient = $ns->create_google_client();
					$access_token = $ns->get_access_token( $staff_id );
					$gclient->setAccessToken( $access_token );
					$service = new Google_Service_Calendar( $gclient );
					$service->events->delete( $calendar_id, $appointment_uid, array(
							'quotaUser' => $ns->get_quota_user()
						) );
				}
				catch ( Exception $ex ) {
					birch_log( $ex->getMessage() );
				}
			};

		$ns->async_update_event = function( $appointment ) use ( $ns ) {
				global $birchpress;

				$args = array($appointment);
				$birchpress->util->async_run_task( array(
						'action' => 'birchschedule.gcalsync.update_event',
						'args' => $args
					) );
			};

		$ns->async_delete_event = function( $appointment ) use ( $ns ) {
				global $birchpress;

				$args = array($appointment);
				$birchpress->util->async_run_task( array(
						'action' => 'birchschedule.gcalsync.delete_event',
						'args' => $args
					) );
			};

		$ns->get_appointment_summary = function( $appointment ) use ( $ns, $birchschedule ) {
				return $birchschedule->icalendar->get_appointment_summary( $appointment );
			};

		$ns->get_end_symbol = function() {
				return "\n—\nSynced from BirchPress Scheduler";
			};

		$ns->change_appointment_description = function( $description, $appointment ) {
				$description .= "\n" . $appointment['_birs_appointment_admin_url'] . "\n";
				return $description;
			};

		$ns->get_appointment_description = function( $appointment ) use ( $ns, $birchschedule ) {
				add_filter( 'birchschedule_icalendar_get_appointment_description',
					array( $ns, 'change_appointment_description' ), 50, 2 );
				$description = $birchschedule->icalendar->get_appointment_description( $appointment );
				$description = $description . $ns->get_end_symbol();
				return $description;
			};

		$ns->register_scripts = function() use( $ns, $birchschedule ) {
				$version = $birchschedule->get_product_version();

				wp_register_script( 'birchschedule_gcalsync_admin',
					$birchschedule->plugin_url() . '/modules/gcalsync/assets/js/admin.js',
					array( 'birchschedule_view_staff_edit' ), "$version" );
			};

		$ns->enqueue_scripts = function() use ( $ns, $birchschedule ) {
				$birchschedule->view->enqueue_scripts( array( 'birchschedule_gcalsync_admin' ) );
			};

		$ns->render_need_authorization = function( $staff_id ) use( $ns ) {
				$gclient = $ns->create_google_client();
				$grant_access_url = $gclient->createAuthUrl();
?>
                <tr>
                    <th>
                        <label for=''>
                            <?php echo __( 'Sync to Google Calendar', 'birchschedule' ); ?>
                        </label>
                    </th>
                    <td>
<?php
				echo sprintf( __( 'To setup Google Calendar Sync, you must first <a href="%s" target="_blank">grant authorization</a>. Then paste the authorization code below and click update.' ),
					$grant_access_url );
?>
                        <div>
                        	<input id="birs_staff_gcal_authorization_code" name="birs_staff_gcal_authorization_code"
                        		type="text" placeholder="<?php _e( 'Authorization code', 'birchschedule' ); ?>" />
                        	<input id="birs_staff_action_gcal_authorize" class="button button-primary button-small"
                        		value="<?php _e( 'Update', 'birchschedule' ); ?>" type="button" />
                        </div>
                    </td>
                </tr>
<?php

			};

		$ns->get_gcal_calendar_list = function( $staff_id ) use ( $ns, $birchschedule ) {
				global $birchpress;

				try {
					$gclient = $ns->create_google_client();
					$access_token = $ns->get_access_token( $staff_id );
					$gclient->setAccessToken( $access_token );
					$service = new Google_Service_Calendar( $gclient );
					$items = $service->calendarList->listCalendarList( array(
							'quotaUser' => $ns->get_quota_user()
						) )->getItems();
					$calendars = array(
						-1 => __( 'Select...', 'birchschedule' )
					);
					foreach ( $items as $item ) {
						if ( $item->getAccessRole() === 'owner' ) {
							$calendars[$item->getId()] = $item->getSummary();
						}
					}
					return $calendars;
				}
				catch( Exception $ex ) {
					$code = $ex->getCode();
					if( $code === 401 ) {
						$ns->delete_gcal_settings( $staff_id );
					}
					$message = $ex->getMessage();
					birch_log( $message );
					$error = $birchpress->util->new_error( 'google_service_error', $message );
					return $error;
				}
			};

		$ns->render_authorized_settings = function( $calendars, $calendar_id ) use( $ns ) {
				global $birchpress;

				$revoke_url = 'https://accounts.google.com/IssuedAuthSubTokens';
?>
                <tr>
                    <th>
                        <label for=''>
                            <?php echo __( 'Sync to Google Calendar', 'birchschedule' ); ?>
                        </label>
                    </th>
                    <td>
                    	<select name="bir_staff_gcal_calendar_id">
                    		<?php $birchpress->util->render_html_options( $calendars, $calendar_id ); ?>
                    	</select>
<?php
				echo sprintf( __( '<a href="%s" target="_blank">Revoke access</a>', 'birchschedule' ), $revoke_url );
?>
                    </td>
                </tr>
<?php

			};

		$ns->save_staff_data = function( $post ) use ( $ns, $birchschedule ) {
				if(!empty($_POST['bir_staff_gcal_calendar_id'])) {
					$staff = $birchschedule->model->get( $post['ID'], array(
							'keys' => array(
								'_birs_staff_gcal_calendar_id',
							)
						) );
					$staff['_birs_staff_gcal_calendar_id'] = $_POST['bir_staff_gcal_calendar_id'];
					$birchschedule->model->save( $staff, array(
							'keys' => array(
								'_birs_staff_gcal_calendar_id'
							)
						) );					
				}
			};

		$ns->get_gcal_calendar_id = function( $staff_id ) use ( $ns, $birchschedule ) {
				$staff = $birchschedule->model->get( $staff_id, array(
						'keys' => array( '_birs_staff_gcal_calendar_id' )
					) );
				if ( empty( $staff['_birs_staff_gcal_calendar_id'] ) ) {
					return -1;
				} else {
					return $staff['_birs_staff_gcal_calendar_id'];
				}
			};

		$ns->get_access_token = function( $staff_id ) use( $ns, $birchschedule ) {
				$staff = $birchschedule->model->get( $staff_id, array(
						'keys' => array( '_birs_staff_gcal_access_token' )
					) );
				if ( empty( $staff['_birs_staff_gcal_access_token'] ) ) {
					return null;
				} else {
					return $staff['_birs_staff_gcal_access_token'];
				}
			};

		$ns->save_access_token = function( $staff_id, $access_token ) use ( $ns, $birchschedule ) {
				$staff = array(
					'ID' => $staff_id,
					'_birs_staff_gcal_access_token' => $access_token,
					'post_type' => 'birs_staff'
				);
				$birchschedule->model->save( $staff, array(
						'keys' => array( '_birs_staff_gcal_access_token' )
					) );
			};

		$ns->create_google_client = function() use ( $ns ) {
				global $birchschedule;
				if ( ! function_exists( 'google_api_php_client_autoload' ) ) {
					require_once $birchschedule->plugin_dir_path() . 'lib/google/apiclient/autoload.php';
				}
				$redirect_url = 'urn:ietf:wg:oauth:2.0:oob';
				$credentials = $ns->get_credentials();
				$client = new Google_Client();
				$client->setApplicationName( 'BirchPress Scheduler' );
				$client->setScopes( array(
						Google_Service_Calendar::CALENDAR,
						Google_Service_Oauth2::USERINFO_EMAIL
					) );
				$client->setClientId( $credentials['client_id'] );
				$client->setClientSecret( $credentials['client_secret'] );
				$client->setRedirectUri( $redirect_url );
				$client->setAccessType( 'offline' );
				return $client;
			};

		$ns->delete_gcal_settings = function($staff_id) {
				delete_post_meta( $staff_id, '_birs_staff_gcal_access_token' );
				delete_post_meta( $staff_id, '_birs_staff_gcal_calendar_id' );
			};

		$ns->is_authorized = function( $staff_id ) use ( $ns ) {
				global $birchpress;

				$access_token = $ns->get_access_token( $staff_id );
				if ( empty( $access_token ) ) {
					$ns->delete_gcal_settings( $staff_id );
					return false;
				}
				try {
					$gclient = $ns->create_google_client();
					$gclient->setAccessToken( $access_token );
					if ( $gclient->isAccessTokenExpired() ) {
						$gclient->refreshToken( $gclient->getRefreshToken() );
						if ( $gclient->isAccessTokenExpired() ) {
							$ns->delete_gcal_settings( $staff_id );
							return false;
						} else {
							$access_token = $gclient->getAccessToken();
							$ns->save_access_token( $staff_id, $access_token );
							return true;
						}
					} else {
						return true;
					}
				}
				catch( Exception $ex ) {
					$message = $ex->getMessage();
					if ( strpos( $message, 'invalid_grant' ) !== false ) {
						$ns->delete_gcal_settings( $staff_id );
					}
					$error = $birchpress->util->new_error( 'google_auth_error', $message );
					return $error;
				}

			};

		$ns->render_staff_sync_settings = function( $post ) use( $ns ) {
?>
        <div class="panel-wrap birchschedule">
            <table class="form-table">
<?php
				if ( $ns->is_authorized( $post->ID ) === true ) {
					$calendars = $ns->get_gcal_calendar_list( $post->ID );
					if ( is_array( $calendars ) ) {
						$calendar_id = $ns->get_gcal_calendar_id( $post->ID );
						$ns->render_authorized_settings( $calendars, $calendar_id );
					} else {
						$ns->render_need_authorization( $post->ID );
					}
				} else {
					$ns->render_need_authorization( $post );
				}
?>
            </table>
        </div>
<?php
			};


	} );
