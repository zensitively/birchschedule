<?php

birch_ns( 'birchschedule.fbuilder.upgrader', function( $ns ) {

        global $birchschedule;

        $_ns_data = new stdClass();

        $ns->init = function() use( $ns, $birchschedule ) {
            $ns->init_data();
            $birchschedule->upgrade_module->when( $birchschedule->fbuilder->is_module_fbuilder, $ns->upgrade_module );
        };

        $ns->init_data = function() use ( $ns, $_ns_data ) {
            global $birchpress;

            $_ns_data->default_options_form_1_2 = array(
                'fields' => array(
                    'appointment_section' => array(
                        'category' => 'system_fields',
                        'label' => __( 'Appointment Info', 'birchschedule' ),
                        'type' => 'appointment_section',
                        'belong_to' => 'none',
                        'visibility' => 'frontend',
                        'appointment_details' => array(
                            'labels' => array(
                                'location' => __( 'Location', 'birchschedule' ),
                                'service' => __( 'Service', 'birchschedule' ),
                                'service_provider' => __( 'Provider', 'birchschedule' ),
                                'datetime' => __( 'Date & Time', 'birchschedule' )
                            )
                        )
                    ),
                    'appointment_notes' => array(
                        'category' => 'system_fields',
                        'label' => __( 'Notes', 'birchschedule' ),
                        'type' => 'paragraph_text',
                        'belong_to' => 'appointment',
                        'visibility' => 'both',
                        'required' => false
                    ),
                    'client_section' => array(
                        'category' => 'system_fields',
                        'label' => __( 'Your Info', 'birchschedule' ),
                        'type' => 'client_section',
                        'belong_to' => 'none',
                        'visibility' => 'frontend',
                        'client_type_settings' => array(
                            'labels' => array(
                                'new_or_returning' => __( 'Are you a new or returning user?', 'birchschedule' ),
                                'new_user' => __( 'New User', 'birchschedule' ),
                                'returning_user' => __( 'Returning User', 'birchschedule' )
                            ),
                            'default_client_type' => 'new'
                        )
                    ),
                    'client_title' => array(
                        'category' => 'system_fields',
                        'label' => __( 'Title', 'birchschedule' ),
                        'type' => 'client_title',
                        'belong_to' => 'client',
                        'visibility' => 'admin',
                        'default_value' => 'Mr',
                        'required' => false,
                        'choices' => $birchpress->util->get_client_title_options()
                    ),
                    'client_name_first' => array(
                        'category' => 'system_fields',
                        'label' => __( 'First Name', 'birchschedule' ),
                        'type' => 'single_line_text',
                        'belong_to' => 'client',
                        'visibility' => 'both',
                        'required' => true
                    ),
                    'client_name_last' => array(
                        'category' => 'system_fields',
                        'label' => __( 'Last Name', 'birchschedule' ),
                        'type' => 'single_line_text',
                        'belong_to' => 'client',
                        'visibility' => 'both',
                        'required' => true
                    ),
                    'client_email' => array(
                        'category' => 'system_fields',
                        'label' => __( 'Email', 'birchschedule' ),
                        'type' => 'client_email',
                        'belong_to' => 'client',
                        'visibility' => 'both',
                        'required' => true
                    ),
                    'client_password' => array(
                        'category' => 'system_fields',
                        'label' => __( 'Password', 'birchschedule' ),
                        'type' => 'password',
                        'belong_to' => 'client',
                        'visibility' => 'frontend',
                        'required' => true,
                        'labels' => array(
                            'retype_password' => __( 'Retype Password', 'birchschedule' )
                        )
                    ),
                    'client_phone' => array(
                        'category' => 'system_fields',
                        'label' => __( 'Phone', 'birchschedule' ),
                        'type' => 'single_line_text',
                        'belong_to' => 'client',
                        'visibility' => 'both',
                        'required' => true
                    ),
                    'client_address' => array(
                        'category' => 'system_fields',
                        'label' => __( 'Address', 'birchschedule' ),
                        'type' => 'address',
                        'belong_to' => 'client',
                        'visibility' => 'admin',
                        'required' => false
                    ),
                    'client_city' => array(
                        'category' => 'system_fields',
                        'label' => __( 'City', 'birchschedule' ),
                        'type' => 'single_line_text',
                        'belong_to' => 'client',
                        'visibility' => 'admin',
                        'required' => false
                    ),
                    'client_state' => array(
                        'category' => 'system_fields',
                        'label' => __( 'State/Province', 'birchschedule' ),
                        'type' => 'state_province',
                        'belong_to' => 'client',
                        'visibility' => 'admin',
                        'required' => false
                    ),
                    'client_country' => array(
                        'category' => 'system_fields',
                        'label' => __( 'Country', 'birchschedule' ),
                        'type' => 'country',
                        'belong_to' => 'client',
                        'visibility' => 'admin',
                        'required' => false,
                        'default_value' => 'US',
                        'choices' => $birchpress->util->get_countries()
                    ),
                    'client_zip' => array(
                        'category' => 'system_fields',
                        'label' => __( 'Zip Code', 'birchschedule' ),
                        'type' => 'single_line_text',
                        'belong_to' => 'client',
                        'visibility' => 'admin',
                        'required' => false
                    ),
                    'submit' => array(
                        'category' => 'system_fields',
                        'label' => __( 'Submit', 'birchschedule' ),
                        'type' => 'submit',
                        'belong_to' => 'none',
                        'visibility' => 'frontend',
                        'required' => false,
                        'labels' => array(
                            'forget_password' => __( 'Lost your password?', 'birchschedule' )
                        ),
                        'confirmation' => array(
                            'type' => 'text',
                            'text' => array(
                                'template' => ''
                            ),
                            'redirect' => array(
                                'url' => 'http://'
                            )
                        )
                    )
                ),
                'next_field_id' => 1,
                'version' => '1.2'
            );
            ob_start();
?>
        <h3>Your appointment has been booked successfully.</h3>
        <div>
            <ul>
                <li>
                    <h4>Location</h4>
                    <p>{location_name}</p>
                </li>
                <li>
                    <h4>Service</h4>
                    <p>{service_name}</p>
                </li>
                <li>
                    <h4>Time</h4>
                    <p>{datetime}</p>
                </li>
            </ul>
        </div>
        <?php
            $_ns_data->default_options_form_1_2['fields']['submit']['confirmation']['text']['template'] = ob_get_clean();

            $_ns_data->default_options_form_1_3 = $_ns_data->default_options_form_1_2;
            $_ns_data->default_options_form_1_3['fields']['client_section']['client_type_settings']['disable_login'] = false;
            $_ns_data->default_options_form_1_3['version'] = '1.3';

            $_ns_data->default_options_form_1_4 = $_ns_data->default_options_form_1_3;
            $_ns_data->default_options_form_1_4['fields']['appointment_section']['appointment_details']['labels']['date'] = __( 'Date', 'birchschedule' );
            $_ns_data->default_options_form_1_4['fields']['appointment_section']['appointment_details']['labels']['time'] = __( 'Time', 'birchschedule' );
            unset( $_ns_data->default_options_form_1_4['fields']['appointment_section']['appointment_details']['labels']['datetime'] );
            $_ns_data->default_options_form_1_4['fields']['client_section']['client_type_settings']['disable_register'] = false;
            $_ns_data->default_options_form_1_4['version'] = '1.4';

            $_ns_data->default_options_form_1_5 = $_ns_data->default_options_form_1_4;
            $_ns_data->default_options_form_1_5['fields']['submit']['belong_to'] = 'none';
            $_ns_data->default_options_form_1_5['version'] = '1.5';

            $_ns_data->default_options_form_1_6 = $_ns_data->default_options_form_1_5;
            $_ns_data->default_options_form_1_6['version'] = '1.6';

            $_ns_data->default_options_form_1_7 = $_ns_data->default_options_form_1_6;
            $_ns_data->default_options_form_1_7['fields']['submit']['belong_to'] = 'actions';
            $_ns_data->default_options_form_1_7['fields']['appointment_section']['belong_to'] = 'appointment';
            $_ns_data->default_options_form_1_7['fields']['client_section']['belong_to'] = 'client';
            $_ns_data->default_options_form_1_7['version'] = '1.7';

            $_ns_data->default_options_form_1_8 = $_ns_data->default_options_form_1_7;
            $_ns_data->default_options_form_1_8['fields']['client_title']['visibility'] = 'both';
            $_ns_data->default_options_form_1_8['fields']['client_address']['visibility'] = 'both';
            $_ns_data->default_options_form_1_8['fields']['client_city']['visibility'] = 'both';
            $_ns_data->default_options_form_1_8['fields']['client_state']['visibility'] = 'both';
            $_ns_data->default_options_form_1_8['fields']['client_country']['visibility'] = 'both';
            $_ns_data->default_options_form_1_8['fields']['client_zip']['visibility'] = 'both';
            $_ns_data->default_options_form_1_8['fields']['client_title']['belong_to'] = 'none';
            $_ns_data->default_options_form_1_8['fields']['client_address']['belong_to'] = 'none';
            $_ns_data->default_options_form_1_8['fields']['client_city']['belong_to'] = 'none';
            $_ns_data->default_options_form_1_8['fields']['client_state']['belong_to'] = 'none';
            $_ns_data->default_options_form_1_8['fields']['client_country']['belong_to'] = 'none';
            $_ns_data->default_options_form_1_8['fields']['client_zip']['belong_to'] = 'none';
            $_ns_data->default_options_form_1_8['field_order'] = array(
                'appointment_section', 'appointment_notes', 'client_section',
                'client_name_first', 'client_name_last', 'client_phone',
                'client_email', 'client_password', 'submit',
                'client_title', 'client_address', 'client_city',
                'client_state', 'client_country', 'client_zip'
            );
            $_ns_data->default_options_form_1_8['version'] = '1.8';

            $_ns_data->default_options_form = $_ns_data->default_options_form_1_8;
        };

        $ns->get_default_options_form = function() use ( $_ns_data ) {
            return $_ns_data->default_options_form;
        };

        $ns->upgrade_module = function() use ( $ns ) {
            $ns->upgrade_form_options_1_0_to_1_1();
            $ns->upgrade_form_options_1_1_to_1_2();
            $ns->upgrade_form_options_1_2_to_1_3();
            $ns->upgrade_form_options_1_3_to_1_4();
            $ns->upgrade_form_options_1_4_to_1_5();
            $ns->upgrade_form_options_1_5_to_1_6();
            $ns->upgrade_form_options_1_6_to_1_7();
            $ns->upgrade_form_options_1_7_to_1_8();
        };

        $ns->upgrade_form_options_1_7_to_1_8 = function() use ( $ns, $_ns_data, $birchschedule ) {
            global $birchschedule;

            $form_options = $birchschedule->fbuilder->get_form_options();
            $version = $form_options['version'];
            if ( "1.7" !== $version ) {
                return;
            }

            $form_options['version'] = '1.8';
            $birchschedule->fbuilder->update_form_options( $form_options );
        };

        $ns->upgrade_form_options_1_6_to_1_7 = function() use ( $ns, $_ns_data, $birchschedule ) {
            global $birchschedule;

            $form_options = $birchschedule->fbuilder->get_form_options();
            $version = $form_options['version'];
            if ( "1.6" !== $version ) {
                return;
            }

            $form_options['fields']['submit']['belong_to'] = 'actions';
            $form_options['fields']['appointment_section']['belong_to'] = 'appointment';
            $form_options['fields']['client_section']['belong_to'] = 'client';
            $form_options['version'] = '1.7';
            $birchschedule->fbuilder->update_form_options( $form_options );
        };

        $ns->upgrade_form_options_1_5_to_1_6 = function() use ( $ns, $_ns_data, $birchschedule ) {
            global $birchschedule;

            $form_options = $birchschedule->fbuilder->get_form_options();
            $version = $form_options['version'];
            if ( $version !== "1.5" ) {
                return;
            }
            $clients = $birchschedule->model->query(
                array(
                    'post_type' => 'birs_client'
                ),
                array(
                    'base_keys' => array(),
                    'meta_keys' => array(
                        '_birs_client_state',
                        '_birs_client_province',
                        '_birs_client_country'
                    )
                )
            );
            foreach ( $clients as $client ) {
                if ( isset( $client['_birs_client_country'] ) && $client['_birs_client_country'] != 'US' ) {
                    if ( isset( $client['_birs_client_province'] ) ) {
                        $client['_birs_client_state'] = $client['_birs_client_province'];
                        $birchschedule->model->save( $client, array(
                                'base_keys' => array(),
                                'meta_keys' => array(
                                    '_birs_client_state'
                                )
                            ) );
                    }
                }
            }
            $form_options['version'] = '1.6';
            $birchschedule->fbuilder->update_form_options( $form_options );
        };

        $ns->upgrade_form_options_1_4_to_1_5 = function() use ( $ns, $_ns_data, $birchschedule ) {
            $form_options = $birchschedule->fbuilder->get_form_options();
            $version = $form_options['version'];
            if ( $version !== "1.4" ) {
                return;
            }
            $form_options['fields']['submit']['belong_to'] = 'none';
            $form_options['version'] = '1.5';
            $birchschedule->fbuilder->update_form_options( $form_options );
        };

        $ns->upgrade_form_options_1_3_to_1_4 = function() use( $ns, $_ns_data, $birchschedule ) {
            $form_options = $birchschedule->fbuilder->get_form_options();
            $version = $form_options['version'];
            if ( $version !== "1.3" ) {
                return;
            }
            $form_options['fields']['appointment_section']['appointment_details']['labels']['date'] = __( 'Date', 'birchschedule' );
            $form_options['fields']['appointment_section']['appointment_details']['labels']['time'] = __( 'Time', 'birchschedule' );
            unset( $form_options['fields']['appointment_section']['appointment_details']['labels']['datetime'] );
            $form_options['fields']['client_section']['client_type_settings']['disable_register'] = false;
            $form_options['version'] = '1.4';
            $birchschedule->fbuilder->update_form_options( $form_options );
        };

        $ns->upgrade_form_options_1_2_to_1_3 = function() use ( $ns, $_ns_data, $birchschedule ) {
            $form_options = $birchschedule->fbuilder->get_form_options();
            $version = $form_options['version'];
            if ( $version !== "1.2" ) {
                return;
            }
            $default_form_options = $_ns_data->default_options_form_1_3;
            $form_options['fields']['client_section']['client_type_settings'] =
            $default_form_options['fields']['client_section']['client_type_settings'];
            $form_options['version'] = "1.3";
            $birchschedule->fbuilder->update_form_options( $form_options );
        };

        $ns->upgrade_form_options_1_1_to_1_2 = function() use ( $ns, $_ns_data, $birchschedule ) {
            $form_options = $birchschedule->fbuilder->get_form_options();
            $default_form_options = $_ns_data->default_options_form_1_2;
            $version = $form_options['version'];
            if ( $version !== "1.1" ) {
                return;
            }
            $form_options['fields']['client_password'] = $default_form_options['fields']['client_password'];
            $form_options['fields']['submit'] = $default_form_options['fields']['submit'];
            $form_options['fields']['client_title'] = $default_form_options['fields']['client_title'];
            $form_options['fields']['client_country'] = $default_form_options['fields']['client_country'];

            $form_options['fields']['client_section']['client_type_settings'] =
            $default_form_options['fields']['client_section']['client_type_settings'];
            $form_options['fields']['client_email']['type'] =
            $default_form_options['fields']['client_email']['type'];

            $field_order = $birchschedule->fbuilder->get_field_order();
            $email_pos = array_search( 'client_email', $field_order );
            array_splice( $field_order, $email_pos + 1, 0, 'client_password' );
            $field_order[] = 'submit';
            $form_options['field_order'] = $field_order;
            $form_options['version'] = "1.2";
            $birchschedule->fbuilder->update_form_options( $form_options );
        };

        $ns->upgrade_form_options_1_0_to_1_1 = function() use ( $ns, $_ns_data, $birchschedule ) {
            $form_options = $birchschedule->fbuilder->get_form_options();
            $fields_options = $form_options['fields'];
            $field_order = $birchschedule->fbuilder->get_field_order();
            if ( isset( $form_options['version'] ) ) {
                return;
            }
            $appointment_fields = array(
                'appointment_section' => array(
                    'category' => 'system_fields',
                    'label' => __( 'Appointment Info', 'birchschedule' ),
                    'type' => 'appointment_section',
                    'belong_to' => 'none',
                    'visibility' => 'frontend',
                    'appointment_details' => array(
                        'labels' => array(
                            'location' => __( 'Location', 'birchschedule' ),
                            'service' => __( 'Service', 'birchschedule' ),
                            'service_provider' => __( 'Provider', 'birchschedule' ),
                            'datetime' => __( 'Date & Time', 'birchschedule' )
                        )
                    )
                )
            );
            $client_fields = array(
                'client_section' => array(
                    'category' => 'system_fields',
                    'label' => __( 'Your Info', 'birchschedule' ),
                    'type' => 'client_section',
                    'belong_to' => 'none',
                    'visibility' => 'frontend',
                    'client_type_settings' => array(
                        'labels' => array(
                            'new_or_returning' => __( 'Are you a new or returning user?', 'birchschedule' ),
                            'new_user' => __( 'New User', 'birchschedule' ),
                            'returning_user' => __( 'Returning User', 'birchschedule' )
                        ),
                        'default_client_type' => 'new'
                    )
                )
            );
            foreach ( $field_order as $field_name ) {
                $field_option = $fields_options[$field_name];
                if ( $field_option["belong_to"] == "appointment" ) {
                    $appointment_fields[$field_name] = $field_option;
                }
                if ( $field_option["belong_to"] == "client" ) {
                    $client_fields[$field_name] = $field_option;
                }
            }
            $fields_options = array_merge( $appointment_fields, $client_fields );
            $form_options['fields'] = $fields_options;
            $form_options['version'] = "1.1";
            unset( $form_options['field_order'] );
            $birchschedule->fbuilder->update_form_options( $form_options );
        };

    } );
