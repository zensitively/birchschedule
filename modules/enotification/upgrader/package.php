<?php

birch_ns( 'birchschedule.enotification.upgrader', function( $ns ) {

        global $birchschedule;

        $_ns_data = new stdClass();

		$ns->init = function() use( $ns, $birchschedule ) {
                $ns->init_data();

				$birchschedule->upgrade_module->when( $birchschedule->enotification->is_module_enotification, $ns->upgrade_module );
            };

		$ns->init_data = function() use( $ns, $birchschedule, $_ns_data ) {
                $_ns_data->DEFAULT_OPTIONS_NOTIFICATION_1_0 = array(
                    'staff' => array(
                        'enable' => 'off',
                        'from_name' => '{site_title}',
                        'from_email' => '{admin_email}',
                        'bcc' => '{admin_email}',
                        'template' => array(
                            'new' => array(
                                'subject' => 'New Appointment from {client_name}',
                                'message' => '',
                                'content_type' => 'text/html'
                            ),
                            'modified' => array(
                                'subject' => 'Appointment changed for {client_name}',
                                'message' => '',
                                'content_type' => 'text/html'
                            ),
                            'cancelled' => array(
                                'subject' => 'Appointment cancellation for {client_name}',
                                'message' => '',
                                'content_type' => 'text/html'
                            )
                        )
                    ),
                    'client' => array(
                        'enable' => 'off',
                        'from_name' => '{site_title}',
                        'from_email' => '{admin_email}',
                        'bcc' => '',
                        'reply_to' => '',
                        'template' => array(
                            'new' => array(
                                'subject' => 'New Appointment on {datetime}',
                                'message' => '',
                                'content_type' => 'text/html'
                            ),
                            'modified' => array(
                                'subject' => 'Your recent appointment has been changed',
                                'message' => '',
                                'content_type' => 'text/html'
                            ),
                            'cancelled' => array(
                                'subject' => 'Your appointment has been cancelled',
                                'message' => '',
                                'content_type' => 'text/html'
                            )
                        )
                    )
                );
                ob_start();
                require_once 'templates/staff-new.php';
                $_ns_data->DEFAULT_OPTIONS_NOTIFICATION_1_0['staff']['template']['new']['message'] = ob_get_clean();
                ob_start();
                require_once 'templates/staff-modified.php';
                $_ns_data->DEFAULT_OPTIONS_NOTIFICATION_1_0['staff']['template']['modified']['message'] = ob_get_clean();
                ob_start();
                require_once 'templates/staff-cancelled.php';
                $_ns_data->DEFAULT_OPTIONS_NOTIFICATION_1_0['staff']['template']['cancelled']['message'] = ob_get_clean();
                ob_start();
                require_once 'templates/client-new.php';
                $_ns_data->DEFAULT_OPTIONS_NOTIFICATION_1_0['client']['template']['new']['message'] = ob_get_clean();
                ob_start();
                require_once 'templates/client-modified.php';
                $_ns_data->DEFAULT_OPTIONS_NOTIFICATION_1_0['client']['template']['modified']['message'] = ob_get_clean();
                ob_start();
                require_once 'templates/client-cancelled.php';
                $_ns_data->DEFAULT_OPTIONS_NOTIFICATION_1_0['client']['template']['cancelled']['message'] = ob_get_clean();

                $_ns_data->DEFAULT_OPTIONS_NOTIFICATION_1_1 = $_ns_data->DEFAULT_OPTIONS_NOTIFICATION_1_0;
                $_ns_data->DEFAULT_OPTIONS_NOTIFICATION_1_1['client']['enable_reminder'] = 'off';
                $_ns_data->DEFAULT_OPTIONS_NOTIFICATION_1_1['client']['reminder_prior_length'] = 12;
                ob_start();
                require_once 'templates/client-reminder.php';
                $_ns_data->DEFAULT_OPTIONS_NOTIFICATION_1_1['client']['template']['reminder'] =
                array(
                    'subject' => 'Appointment Reminder',
                    'message' => ob_get_clean(),
                    'content_type' => 'text/html'
                );
                $_ns_data->DEFAULT_OPTIONS_NOTIFICATION_1_1['version'] = '1.1';

                $_ns_data->DEFAULT_OPTIONS_NOTIFICATION_1_2 = $_ns_data->DEFAULT_OPTIONS_NOTIFICATION_1_1;
                $_ns_data->DEFAULT_OPTIONS_NOTIFICATION_1_2['client']['cancel_page'] = -1;
                $_ns_data->DEFAULT_OPTIONS_NOTIFICATION_1_2['client']['reschedule_page'] = -1;
                $_ns_data->DEFAULT_OPTIONS_NOTIFICATION_1_2['version'] = '1.2';
                $_ns_data->DEFAULT_OPTIONS_NOTIFICATION = $_ns_data->DEFAULT_OPTIONS_NOTIFICATION_1_2;
            };

		$ns->get_default_options_notification = function() use( $_ns_data ) {
                return $_ns_data->DEFAULT_OPTIONS_NOTIFICATION;
            };

		$ns->upgrade_module = function() use( $ns ) {
                $ns->upgrade_options_1_0_to_1_1();
                $ns->upgrade_options_1_1_to_1_2();
            };

		$ns->upgrade_options_1_0_to_1_1 = function() use( $ns, $_ns_data, $birchschedule ) {
                $options = $birchschedule->enotification->get_options();
                if ( isset( $options['version'] ) ) {
                    return;
                }
                $options['client']['enable_reminder'] =
                $_ns_data->DEFAULT_OPTIONS_NOTIFICATION_1_1['client']['enable_reminder'];
                $options['client']['reminder_prior_length'] =
                $_ns_data->DEFAULT_OPTIONS_NOTIFICATION_1_1['client']['reminder_prior_length'];
                $options['client']['template']['reminder'] =
                $_ns_data->DEFAULT_OPTIONS_NOTIFICATION_1_1['client']['template']['reminder'];
                $options['version'] = '1.1';
                update_option( "birchschedule_options_notification", $options );
            };

		$ns->upgrade_options_1_1_to_1_2 = function() use( $ns, $_ns_data, $birchschedule ) {
                $options = $birchschedule->enotification->get_options();
                if ( $options['version'] != '1.1' ) {
                    return;
                }
                $options['client']['cancel_page'] = -1;
                $options['client']['reschedule_page'] = -1;
                $options['version'] = '1.2';
                update_option( "birchschedule_options_notification", $options );
            };

    } );
