<?php

birch_ns( 'birchschedule.fbuilder', function( $ns ) {

        global $birchschedule;

        $_ns_data = new stdClass();

        $ns->init = function() use ( $ns, $birchschedule, $_ns_data ) {

            $ns->init_data();

            $ns->redefine_functions();

            add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );

            add_action( 'init', array( $ns, 'wp_init' ) );

            add_filter( 'birchschedule_view_bookingform_get_fields_html',
                array( $ns, 'get_bookingform_fields_html' ), 20 );

            add_filter( 'birchschedule_model_get_default_country',
                array( $ns, 'get_option_country' ) );

            add_filter( 'birchschedule_model_get_default_state',
                array( $ns, 'get_option_state' ) );

            add_action( 'birchschedule_view_calendar_enqueue_scripts_after',
                array( $ns, 'enqueue_scripts_calendar' ) );

            add_action( 'birchschedule_view_enqueue_scripts_edit_after',
                array( $ns, 'enqueue_scripts_edit' ) );

            add_action( 'birchschedule_view_bookingform_enqueue_scripts_after',
                array( $ns, 'enqueue_scripts_frontend' ) );

            add_filter( 'birchschedule_view_bookingform_get_thankyou_content',
                array( $ns, 'get_thankyou_content' ), 20, 2 );

            add_filter( 'birchschedule_pintegration_get_return_url',
                array( $ns, 'get_return_url' ), 20, 2 );

            add_filter( 'birchschedule_pgauthorize_get_return_url',
                array( $ns, 'get_return_url' ), 20, 2 );

            add_filter( 'birchschedule_view_bookingform_get_success_message',
                array( $ns, 'get_success_message' ), 20, 2 );

            add_filter( 'birchschedule_model_get_meta_key_label',
                array( $ns, 'get_meta_key_label' ), 10, 2 );

            add_filter( 'birchschedule_model_get_client_fields',
                array( $ns, 'get_client_fields' ) );

            add_filter( 'birchschedule_model_get_appointment1on1_custom_fields',
                array( $ns, 'get_appointment1on1_custom_fields' ) );

            add_action( 'updated_user_meta',
                array( $ns, 'update_field_order' ), 10, 4 );

            $birchschedule->view->settings->init_tab->when( $ns->is_tab_form_builder, $ns->init_tab );

        };

        $ns->get_tab_name = function() {
            return 'form_builder';
        };

        $ns->is_module_fbuilder = function( $module ) {
            return $module['module'] === 'fbuilder';
        };

        $ns->is_tab_form_builder = function( $tab ) use ( $ns ) {
            return $tab['tab'] === $ns->get_tab_name();
        };

        $ns->init_data = function() use ( $ns, $_ns_data ) {
            $query_string = "?page=birchschedule_settings&tab=form_builder";
            $_ns_data->base_url = admin_url( 'admin.php' . $query_string );
            $_ns_data->base_post_url = admin_url( 'admin-post.php' . $query_string );

            $_ns_data->field_type_text_map = array(
                'single_line_text' => __( 'Single Line Text', 'birchschedule' ),
                'paragraph_text' => __( 'Paragraph Text', 'birchschedule' ),
                'drop_down' => __( 'Drop Down', 'birchschedule' ),
                'email' => __( 'Email', 'birchschedule' ),
                'checkboxes' => __( 'Checkboxes', 'birchschedule' ),
                'radio_buttons' => __( 'Radio Buttons', 'birchschedule' ),
                'section_break' => __( 'Section Break', 'birchschedule' ),
                'html' => __( 'HTML', 'birchschedule' )
            );

            $_ns_data->action_names = array(
                'save' => 'birchschedule_save_field_options',
                'new_field' => 'birchschedule_new_field',
                'delete_field' => 'birchschedule_delete_field'
            );

            $_ns_data->fields_column_id = $ns->get_tab_name() . '_fields';
        };

        $ns->wp_admin_init = function() {};

        $ns->wp_init = function() use( $ns ) {
            add_filter( 'birchschedule_view_settings_get_tabs',
                array( $ns, 'add_tab' ) );
        };

        $ns->redefine_functions = function() use ( $ns, $birchschedule ) {

            $birchschedule->view->bookingform->save_client = $ns->save_client;

            $birchschedule->view->bookingform->validate_booking_info = $ns->validate_bookingform_info;

            $birchschedule->view->bookingform->validate_appointment1on1_info = $ns->validate_appointment1on1_info;

            $birchschedule->view->bookingform->get_fields_labels = $ns->get_fields_labels;

            $birchschedule->view->appointments->new->validate_client_info = $ns->validate_client_info;

            $birchschedule->view->appointments->new->validate_appointment1on1_info = $ns->validate_appointment1on1_info;

            $birchschedule->view->appointments->edit->clientlist->edit->validate_client_info = $ns->validate_client_info;

            $birchschedule->view->appointments->edit->clientlist->edit->validate_appointment1on1_info = $ns->validate_appointment1on1_info;

            $birchschedule->view->appointments->edit->clientlist->edit->get_appointment1on1_info_html = $ns->get_appointment1on1_html;

            $birchschedule->view->appointments->edit->clientlist->edit->get_client_info_html = $ns->get_client_details_html;

            $birchschedule->view->clients->get_client_details_html = $ns->get_client_details_html;

        };

        $ns->init_tab = function() use ( $ns ) {
            add_action( 'admin_post_birchschedule_delete_field',
                array( $ns, 'delete_field' ) );

            $page_hook = $ns->get_page_hook();
            add_filter( "get_user_option_meta-box-order_$page_hook",
                array( $ns, 'get_meta_box_order' ) );

            add_action( 'admin_post_birchschedule_save_field_options',
                array( $ns, 'save_field_options' ) );

            $ns->route_request();
        };

        $ns->get_action_names_map = function() use ( $_ns_data ) {
            return $_ns_data->action_names;
        };

        $ns->get_action_name = function( $key ) use ( $ns ) {
            $action_names = $ns->get_action_names_map();
            return $action_names[$key];
        };

        $ns->get_base_url = function() use ( $_ns_data ) {
            return $_ns_data->base_url;
        };

        $ns->get_fields_column_id = function() use ( $_ns_data ) {
            return $_ns_data->fields_column_id;
        };

        $ns->get_field_type_text_map = function() use ( $_ns_data ) {
            return $_ns_data->field_type_text_map;
        };

        $ns->get_field_type_text = function( $field_type ) use ( $ns ) {
            $map = $ns->get_field_type_text_map();
            return $map[$field_type];
        };

        $ns->get_base_post_url = function() use ( $_ns_data ) {
            return $_ns_data->base_post_url;
        };

        $ns->is_login_disabled = function() use ( $ns ) {
            $fields_options = $ns->get_fields_options();
            if ( isset( $fields_options['client_section']['client_type_settings']['disable_login'] ) ) {
                return $fields_options['client_section']['client_type_settings']['disable_login'];
            } else {
                return false;
            }
        };

        $ns->is_register_disabled = function() use ( $ns ) {
            $fields_options = $ns->get_fields_options();
            if ( isset( $fields_options['client_section']['client_type_settings']['disable_register'] ) ) {
                return $fields_options['client_section']['client_type_settings']['disable_register'];
            } else {
                return false;
            }
        };

        $ns->get_module_path = function() {
            global $birchschedule;

            $plugin_url = $birchschedule->plugin_url();
            $module_url = $plugin_url . '/modules/fbuilder/';

            return $module_url;
        };

        $ns->enqueue_scripts_calendar = function() use ( $ns ) {
            global $birchschedule;
            $product_version = $birchschedule->get_product_version();
            wp_register_style( 'birchschedule_fb_admin_calendar',
                $ns->get_module_path() . 'assets/css/admin-calendar.css',
                array( 'birchschedule_admincommon' ), $product_version );
            //wp_enqueue_style('birchschedule_fb_admin_calendar');
        };

        $ns->enqueue_scripts_edit = function( $arg ) use ( $ns ) {

            global $birchschedule;

            if ( $arg['post_type'] == 'birs_client' ) {
                $product_version = $birchschedule->get_product_version();

                wp_register_style( 'birchschedule_fb_admin_client_edit',
                    $ns->get_module_path() . 'assets/css/admin-client-edit.css',
                    array( 'birchschedule_admincommon' ), $product_version );

                wp_enqueue_style( 'birchschedule_fb_admin_client_edit' );
            }

        };

        $ns->enqueue_scripts_frontend = function() use ( $ns ) {
            global $birchschedule;

            $product_version = $birchschedule->get_product_version();

            wp_register_script( 'birchschedule_fb_frontend',
                $ns->get_module_path() . 'assets/js/fb-frontend.js',
                array( 'birchschedule_view' ),
                $product_version );
            wp_enqueue_script( 'birchschedule_fb_frontend' );
        };

        $ns->get_client_fields = function( $keys ) use ( $ns, $birchschedule ) {
            $result = array();
            $fields_options = $ns->get_fields_options();
            foreach ( $fields_options as $field_id => $field_options ) {
                if ( $field_options['belong_to'] == 'client' ) {
                    $field_name = $birchschedule->fbuilder->field->get_meta_field_name( $field_options );
                    if ( is_array( $field_name ) ) {
                        $result = array_merge( $result, $field_name );
                    } else {
                        $result[] = $field_name;
                    }
                }
            }
            return $result;
        };

        $ns->get_appointment1on1_custom_fields = function( $keys ) use ( $ns, $birchschedule ) {
            $result = array();
            $fields_options = $ns->get_fields_options();
            foreach ( $fields_options as $field_id => $field_options ) {
                if ( $field_options['belong_to'] == 'appointment' &&
                    'appointment_section' !== $field_id ) {
                    $result[] = $birchschedule->fbuilder->field->get_meta_field_name( $field_options );
                }
            }
            return array_merge( $keys, $result );
        };

        $ns->get_fields_labels = function() use ( $ns ) {
            $labels = array();
            $fields = $ns->get_fields_options();
            foreach ( $fields as $field_id => $field ) {
                $labels[$field_id] = $field['label'];
                if ( $field_id === 'appointment_section' ) {
                    $appointment_labels = $field['appointment_details']['labels'];
                    $labels = array_merge( $labels, $appointment_labels );
                }
            }
            return $labels;
        };

        $ns->get_field_order = function() use ( $ns ) {
            $options = $ns->get_form_options();
            $fields_options = $options['fields'];
            if ( isset( $options['field_order'] ) ) {
                $field_order = $options['field_order'];
            } else {
                $field_order = array_keys( $fields_options );
                $options['field_order'] = $field_order;
                $ns->update_form_options( $options );
            }
            return $field_order;
        };

        $ns->get_fields_options = function() use ( $ns ) {
            $options = $ns->get_form_options();
            $fields_options = $options['fields'];
            $new_fields_options = array();
            foreach ( $fields_options as $field_id => $field ) {
                $field['field_id'] = $field_id;
                $new_fields_options[$field_id] = $field;
            }
            return $new_fields_options;
        };

        $ns->get_meta_key_label = function( $meta_label, $meta_key ) use ( $ns ) {
            $fields_options = $ns->get_fields_options();
            $field_id = substr( $meta_key, 6 );
            if ( $field_id === 'client_province' ) {
                $field_id = 'client_state';
                $field = $fields_options[$field_id];
                return $field['label'];
            }
            if ( $field_id === 'client_address1' ) {
                $field_id = 'client_address';
                $field = $fields_options[$field_id];
                return $field['label'] . ' 1';
            }
            if ( $field_id === 'client_address2' ) {
                $field_id = 'client_address';
                $field = $fields_options[$field_id];
                return $field['label'] . ' 2';
            } else {
                $field = $fields_options[$field_id];
                return $field['label'];
            }
        };

        $ns->get_thankyou_content = function( $content, $appointment1on1_id ) use ( $ns, $birchschedule ) {
            $appointment1on1 =
            $birchschedule->model->mergefields->get_appointment1on1_merge_values( $appointment1on1_id );
            if ( !$appointment1on1 || $appointment1on1['post_status'] != 'publish' ) {
                return $content;
            }
            $fields_options = $ns->get_fields_options();
            $confirmation = $fields_options['submit']['confirmation'];
            $message =
            $birchschedule->model->mergefields->apply_merge_fields( $confirmation['text']['template'],
                $appointment1on1 );
            ob_start();
?>
        <div id="birs_booking_success" style="display:block;">
            <?php echo $message; ?>
        </div>
        <?php
            return ob_get_clean();
        };

        $ns->get_return_url = function( $url, $arg ) use( $ns, $birchschedule ) {
            global $birchpress;

            $fields_options = $ns->get_fields_options();
            $confirmation = $fields_options['submit']['confirmation'];
            if ( $confirmation['type'] === 'redirect' ) {
                if ( isset( $arg['apt1on1_id'] ) ) {
                    $appointment1on1_id = $arg['apt1on1_id'];
                    $appointment1on1 =
                    $birchschedule->model->mergefields->get_appointment1on1_merge_values( $appointment1on1_id );
                    $appointment1on1_new = $birchpress->util->urlencode( $appointment1on1 );
                    $url = $birchschedule->model->mergefields->apply_merge_fields(
                        $confirmation['redirect']['url'], $appointment1on1_new );
                    $url = add_query_arg( $arg, $url );
                }
            }
            return $url;
        };

        $ns->get_success_message = function( $response, $appointment1on1_id ) use( $ns, $birchschedule ) {
            global $birchpress;

            $fields_options = $ns->get_fields_options();
            $confirmation = $fields_options['submit']['confirmation'];
            $appointment1on1 =
            $birchschedule->model->mergefields->get_appointment1on1_merge_values( $appointment1on1_id );
            if ( $confirmation['type'] === 'text' ) {
                $message =
                $birchschedule->model->mergefields->apply_merge_fields( $confirmation['text']['template'],
                    $appointment1on1 );
                return array(
                    'code' => 'text',
                    'message' => $message
                );
            }
            else if ( $confirmation['type'] === 'redirect' ) {
                $appointment1on1_new = $birchpress->util->urlencode( $appointment1on1 );
                $redirect_url =
                $birchschedule->model->mergefields->apply_merge_fields( $confirmation['redirect']['url'],
                    $appointment1on1_new );
                return array(
                    'code' => 'redirect',
                    'message' => $redirect_url
                );
            }
        };

        $ns->get_appointment1on1_html = function( $appointment_id, $client_id ) use( $ns, $birchschedule ) {
            $fields_options = $ns->get_fields_options();
            $field_order = $ns->get_field_order();
            ob_start();
?>
        <ul>
            <?php
            foreach ( $field_order as $field_name ) {
                if ( isset( $fields_options[$field_name] ) ) {
                    $field_options = $fields_options[$field_name];
                    if ( $field_options['belong_to'] === 'appointment' &&
                        $field_options['visibility'] !== 'frontend' ) {

                        $value_field_name =
                        $birchschedule->fbuilder->field->get_value_field_name( $field_options );
                        $appointment1on1 = $birchschedule->model->booking->get_appointment1on1(
                            $appointment_id,
                            $client_id,
                            array(
                                'appointment1on1_keys' => array( $value_field_name )
                            )
                        );
                        if ( $appointment1on1 ) {
                            if ( isset( $appointment1on1[$value_field_name] ) ) {
                                $value = $appointment1on1[$value_field_name];
                            } else {
                                $value = false;
                            }
                        } else {
                            $value = false;
                        }
                        $birchschedule->fbuilder->field->render_field_view_frontend( $field_options, $value );
                    }
                }
            }
?>
        </ul>
        <?php
            $html = ob_get_clean();
            return $html;
        };

        $ns->get_bookingform_fields_html = function( $html ) use( $ns, $birchschedule ) {
            $fields_options = $ns->get_fields_options();
            $field_order = $ns->get_field_order();
            $login_disabled = $ns->is_login_disabled();
            ob_start();
?>
        <ul>
        <?php
            foreach ( $field_order as $field_name ) {
                if ( isset( $fields_options[$field_name] ) ) {
                    $field_options = $fields_options[$field_name];
                    if ( $field_options['visibility'] === 'admin' ) {
                        continue;
                    }
                    if ( $field_options['belong_to'] === 'client' &&
                        is_user_logged_in() && !$login_disabled ) {

                        if ( $field_name !== 'client_section' ) {
                            continue;
                        }
                    }
                    if ( $field_options['belong_to'] === 'none' ) {
                        continue;
                    }
                    $birchschedule->fbuilder->field->render_field_view_frontend( $field_options );
                }
            }
?>
        </ul>
        <?php
            $html = ob_get_clean();
            return $html;
        };

        $ns->get_client_details_html = function( $client_id, $excludes = array() ) use( $ns, $birchschedule ) {
            $fields_options = $ns->get_fields_options();
            $field_order = $ns->get_field_order();
            ob_start();
?>
        <ul>
            <?php
            foreach ( $field_order as $field_name ) {
                if ( in_array( $field_name, $excludes ) ) {
                    continue;
                }
                if ( isset( $fields_options[$field_name] ) ) {
                    $field_options = $fields_options[$field_name];
                    if ( $field_options['belong_to'] === 'client' &&
                        $field_options['visibility'] !== 'frontend' ) {

                        $value_field = $birchschedule->fbuilder->field->get_value_field_name( $field_options );
                        if ( is_array( $value_field ) ) {
                            $meta_keys = array_merge( array(), $value_field );
                        } else {
                            $meta_keys = array( $value_field );
                        }
                        $client = $birchschedule->model->get( $client_id, array(
                                'meta_keys' => $meta_keys,
                                'base_keys' => array()
                            ) );
                        if ( $client ) {
                            if ( is_array( $value_field ) ) {
                                $value = array();
                                foreach ( $value_field as $the_value_field ) {
                                    $value[$the_value_field] = $client[$the_value_field];
                                }
                            } else {
                                $value = $client[$value_field];
                            }
                        } else {
                            $value = false;
                        }
                        $birchschedule->fbuilder->field->render_field_view_frontend( $field_options, $value );
                    }
                }
            }
?>
        </ul>
        <?php
            $html = ob_get_clean();
            return $html;
        };

        $ns->validate_client_info = function() use( $ns, $birchschedule ) {
            $errors = array();
            $fields_options = $ns->get_fields_options();
            foreach ( $fields_options as $field_id => $field_options ) {
                if ( $field_options['belong_to'] === 'client' ) {
                    $error = $birchschedule->fbuilder->field->validate( $field_options );
                    if ( sizeof( $error ) > 0 ) {
                        $errors = array_merge( $errors, $error );
                    }
                }
            }
            return $errors;
        };

        $ns->validate_appointment1on1_info = function() use( $ns, $birchschedule ) {
            $errors = array();
            $fields_options = $ns->get_fields_options();
            foreach ( $fields_options as $field_id => $field_options ) {
                if ( $field_options['belong_to'] === 'appointment' ) {
                    $error = $birchschedule->fbuilder->field->validate( $field_options );
                    if ( sizeof( $error ) > 0 ) {
                        $errors = array_merge( $errors, $error );
                    }
                }
            }
            return $errors;
        };

        $ns->validate_bookingform_info = function() use( $ns, $birchschedule ) {
            $errors = $birchschedule->view->bookingform->validate_appointment_info();
            $fields_options = $ns->get_fields_options();
            $login_fields = array( 'client_email', 'client_password' );
            $login_disabled = $ns->is_login_disabled();
            foreach ( $fields_options as $field_id => $field_options ) {
                if ( $field_options['visibility'] === 'admin' ) {
                    continue;
                }
                if ( $field_options['belong_to'] === 'client' &&
                    is_user_logged_in() && !$login_disabled ) {
                    continue;
                }
                if ( $field_options['belong_to'] === 'client' &&
                    $ns->get_client_type() === 'returning' &&
                    !in_array( $field_id, $login_fields ) ) {
                    continue;
                }
                if ( $field_options['belong_to'] === 'none' ) {
                    continue;
                }
                $error = $birchschedule->fbuilder->field->validate( $field_options );
                if ( sizeof( $error ) > 0 ) {
                    $errors = array_merge( $errors, $error );
                }
            }
            $reCaptchaErrors = $birchschedule->view->bookingform->validate_recaptcha();
            $errors = array_merge( $errors, $reCaptchaErrors );
            return $errors;
        };

        $ns->get_client_type = function() {
            if ( isset( $_REQUEST['birs_client_type'] ) ) {
                return $_REQUEST['birs_client_type'];
            } else {
                return false;
            }
        };

        $ns->handle_field_order = function( $field_name ) {
            $field_name = substr( $field_name, 5 );
            $field_name = substr( $field_name, 0, -4 );
            return $field_name;
        };

        $ns->update_field_order = function( $meta_id, $user_id, $meta_key, $meta_value ) use( $ns ) {
            if ( is_array( $meta_value ) && isset( $meta_value[$ns->get_fields_column_id()] ) ) {
                $setting_page_hook = $ns->get_page_hook();
                if ( $meta_key == "meta-box-order_$setting_page_hook" ) {
                    $form_options = $ns->get_form_options();
                    $orders = explode( ',', $meta_value[$ns->get_fields_column_id()] );
                    $orders = array_map( array( $ns, 'handle_field_order' ), $orders );
                    $form_options['field_order'] = $orders;
                    $ns->update_form_options( $form_options );
                }
            }
        };

        $ns->get_meta_box_order = function( $result ) use( $ns, $birchschedule ) {
            $form_options = $ns->get_form_options();
            if ( isset( $form_options['field_order'] ) ) {
                $orders = $form_options['field_order'];
                $orders_str = '';
                foreach ( $orders as $order ) {
                    $orders_str .= ',birs_' . $order . '_box';
                }
                if ( $orders_str ) {
                    $orders_str = substr( $orders_str, 1 );
                }
                $result[$ns->get_fields_column_id()] = $orders_str;
            } else {
                unset( $result[$ns->get_fields_column_id()] );
            }
            return $result;
        };

        $ns->get_option_country = function( $country = 'US' ) use( $ns ) {
            $form_options = $ns->get_form_options();
            return $form_options['fields']['client_country']['default_value'];
        };

        $ns->get_option_state = function( $state ) use( $ns ) {
            $form_options = $ns->get_form_options();
            $state_options = $form_options['fields']['client_state'];
            if ( isset( $state_options['default_value'] ) ) {
                return $state_options['default_value'];
            } else {
                return false;
            }
        };

        $ns->route_request = function() use( $ns ) {
            if ( isset( $_GET['action'] ) ) {
                if ( $_GET['action'] === 'new' ) {
                    $ns->new_field();
                } else {
                    $ns->edit_form();
                }
            } else {
                $ns->edit_form();
            }
        };

        $ns->delete_field = function() use( $ns, $birchschedule ) {
            if ( isset( $_GET['field'] ) ) {
                $field_id = $_GET['field'];
            } else {
                wp_redirect( $ns->get_base_url() );
                exit;
            }
            check_admin_referer( $ns->get_action_name( 'delete_field' ) );
            $birchschedule->fbuilder->field->delete_field( $field_id );
            set_transient( 'birchschedule_fb_info', __( 'Field Deleted', 'birchschedule' ), 60 );
            wp_redirect( $ns->get_base_url() );
            exit;
        };

        $ns->new_field = function() use( $ns, $birchschedule ) {
            if ( isset( $_GET['type'] ) ) {
                $type = $_GET['type'];
            } else {
                wp_redirect( $ns->get_base_url() );
                exit;
            }
            $default_field_config = $birchschedule->fbuilder->field->get_default_field_config();
            if ( isset( $default_field_config[$type] ) ) {
                check_admin_referer( $ns->get_action_name( 'new_field' ) );
                $field = $birchschedule->fbuilder->field->new_field( $type );
                $field_id = $field['field_id'];
                $field_box_id = $birchschedule->fbuilder->field->get_field_box_id( $field );
                wp_redirect( $ns->get_base_url() . "&action=edit&field=$field_id#" . $field_box_id );
                exit;
            } else {
                wp_redirect( $ns->get_base_url() );
                exit;
            }
        };

        $ns->edit_form = function() use( $ns, $birchschedule ) {
            $screen = $ns->get_screen();
            $ns->enqueue_scripts();
            $ns->init_field_boxes();
        };

        $ns->add_tab = function( $tabs ) use( $ns ) {
            $tabs[$ns->get_tab_name()] = array(
                'title' => __( 'Form Builder', 'birchschedule' ),
                'action' => array( $ns, 'render_page' ),
                'order' => 5
            );

            return $tabs;
        };

        $ns->enqueue_scripts = function() use( $ns ) {
            global $birchschedule;
            $product_version = $birchschedule->get_product_version();
            wp_register_style( 'birchschedule_form_builder',
                $ns->get_module_path() . 'assets/css/form-builder.css',
                array( 'jgrowl' ), $product_version );
            wp_register_script( 'birchschedule_form_builder',
                $ns->get_module_path() . 'assets/js/form-builder.js',
                array( 'birchschedule_view_admincommon', 'postbox', 'jgrowl' ), $product_version );
            wp_enqueue_script( 'postbox' );
            wp_enqueue_script( 'birchschedule_form_builder' );
            wp_enqueue_style( 'birchschedule_form_builder' );
        };

        $ns->get_form_options = function() use( $ns, $birchschedule ) {
            $options = get_option( 'birchschedule_options_form' );
            if ( $options == false ) {
                $options = $birchschedule->fbuilder->upgrader->get_default_options_form();
                add_option( 'birchschedule_options_form', $options );
            }
            return $options;
        };

        $ns->update_form_options = function( $form_options ) use( $ns ) {
            $fields_options = $form_options['fields'];
            if ( isset( $form_options['field_order'] ) ) {
                $field_order = $form_options['field_order'];
            } else {
                $field_order = array_keys( $fields_options );
            }
            $belong_to = "none";
            foreach ( $field_order as $field_name ) {
                if ( $field_name == "appointment_section" ) {
                    $belong_to = "appointment";
                }
                if ( $field_name == "client_section" ) {
                    $belong_to = "client";
                }
                if ( $field_name === 'submit' ) {
                    $form_options['fields']['submit']["belong_to"] = 'actions';
                    $belong_to = "none";
                    continue;
                }
                $form_options['fields'][$field_name]["belong_to"] = $belong_to;
            }
            $form_options = stripslashes_deep( $form_options );
            update_option( 'birchschedule_options_form', $form_options );
        };

        $ns->init_field_boxes = function() use( $ns, $birchschedule ) {
            $fields = $ns->get_fields_options();
            $page_hook = $ns->get_page_hook();
            foreach ( $fields as $field_id => $field ) {
                $birchschedule->fbuilder->field->add_field_box( $field );
            }
        };

        $ns->get_page_hook = function() use ( $ns ) {
            return "birchschedule_page_settings_tab_form_builder";
        };

        $ns->get_screen = function() use( $ns, $birchschedule ) {
            global $birchschedule;

            $page_hook = $ns->get_page_hook();
            $screen = $birchschedule->view->get_screen( $page_hook );
            return $screen;
        };

        $ns->render_page = function() use( $ns, $birchschedule ) {
            $screen = $ns->get_screen();
?>
                <div id="birchschedule_form_builder" class="wrap">
                    <form method="post" action="<?php echo $ns->get_base_post_url(); ?>">
                        <?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>
                        <div id="poststuff">
                            <?php $ns->render_toolbox(); ?>
                            <div id="post-body" class="metabox-holder columns-1">
                                <div id="postbox-container-2" class="postbox-container">
                                    <?php do_meta_boxes( $screen, $ns->get_fields_column_id(), array() ) ?>
                                </div>
                            </div>
                            <div class="clear" style="margin: 0 0 10px 0;"></div>
                        </div>
                        <script type="text/javascript">
                            //<![CDATA[
                            jQuery(document).ready( function($) {
                                $('.if-js-closed').removeClass('if-js-closed').addClass('closed');
                                postboxes.add_postbox_toggles('<?php echo $ns->get_page_hook(); ?>');
                                var editing_field_box_id = 'undefined';
                                if(birchschedule.fbuilder.hasOwnProperty('editing_field_box_id')){
                                    editing_field_box_id = birchschedule.fbuilder.editing_field_box_id;
                                }
                                $('#postbox-container-2 .postbox').not('#' + editing_field_box_id).addClass('view');
                                $('#postbox-container-2 .postbox').hover(function(event){
                                    $(this).removeClass('view');
                                }, function(event){
                                    $(this).not('#' + editing_field_box_id).addClass('view');
                                });
<?php
            $fb_info = get_transient( 'birchschedule_fb_info' );
            if ( false !== $fb_info ):
?>
                                $.jGrowl('<?php echo esc_js( $fb_info ); ?>', {
                                    life: 1000,
                                    position: 'center',
                                    header: '<?php _e( '&nbsp', 'birchschedule' ); ?>'
                                });
<?php
            delete_transient( 'birchschedule_fb_info' );
            endif;
?>
                                });
                            //]]>
                        </script>
                    </form>
                </div>
<?php
        };

        $ns->render_toolbox = function() use( $ns, $birchschedule, $_ns_data ) {
            global $birchpress;

            $href = $ns->get_base_url() . '&action=new&type=';
            $action = $ns->get_action_name( 'new_field' );
            $options = array();
            foreach ( $_ns_data->field_type_text_map as $field_id => $field_text ) {
                if ( in_array( $field_id, array(
                            'single_line_text', 'paragraph_text', 'drop_down',
                            'checkboxes', 'radio_buttons', 'html'
                        ) ) ) {

                    $url = wp_nonce_url( $href . $field_id, $action );
                    $options[$url] = $field_text;
                }
            }
?>
                <div id="birs_toolbox">
                    <select id="birs_toolbox_field_type">
                        <?php $birchpress->util->render_html_options( $options ); ?>
                    </select>
                    <a class="button-primary" id="birs_toolbox_actions_add_field">Add field</a>
                </div>
<?php
        };

        $ns->save_new_client = function() use( $ns, $birchschedule ) {
            $client_config = array(
                'base_keys' => array(),
                'meta_keys' => $_POST['birs_client_fields']
            );
            $client_info = $birchschedule->view->merge_request( array(), $client_config, $_POST );
            unset( $client_info['ID'] );
            $client_id = $birchschedule->model->booking->save_client( $client_info );
            return $client_id;
        };

        $ns->save_client = function() use( $ns, $birchschedule ) {
            $login_disabled = $ns->is_login_disabled();
            if ( $login_disabled ) {
                $client_id = $ns->save_new_client();
                return $client_id;
            } else {
                if ( is_user_logged_in() ) {
                    $current_user = wp_get_current_user();
                    $birchschedule->uintegration->sync_user_to_client( $current_user );
                    $client = $birchschedule->model->
                    get_client_by_email( $current_user->user_email, array(
                            'meta_keys' => array( '_birs_client_email' ),
                            'base_keys' => array(
                                'post_title'
                            )
                        ) );
                    return $client['ID'];
                } else {
                    $client_type = $ns->get_client_type();
                    if ( $client_type == 'returning' ) {
                        $client = $birchschedule->model->get_client_by_email( $_POST['birs_client_email'],
                            array(
                                'base_keys' => array(),
                                'meta_keys' => array()
                            )
                        );
                        $client_id = $client['ID'];
                    } else {
                        $client_id = $ns->save_new_client();
                    }

                    return $client_id;
                }
            }
        };

        $ns->get_bool_option_names = function() {
            return array( 'required' );
        };

        $ns->filter_bool_options = function() use( $ns, $birchschedule ) {
            $fields_options = $_POST['birchschedule_fields_options'];
            foreach ( $fields_options as $field_id => $field_options ) {
                $bool_option_names = $ns->get_bool_option_names();
                foreach ( $bool_option_names as $bool_option_name ) {
                    if ( array_key_exists( $bool_option_name, $field_options ) ) {
                        $_POST['birchschedule_fields_options'][$field_id][$bool_option_name] = true;
                    } else {
                        $_POST['birchschedule_fields_options'][$field_id][$bool_option_name] = false;
                    }
                }
            }
        };

        $ns->save_field_options = function() use( $ns, $birchschedule ) {
            check_admin_referer( $ns->get_action_name( 'save' ) );
            $ns->filter_bool_options();
            $form_options = $ns->get_form_options();
            $fields_db = $form_options['fields'];
            $fields_request = $_POST['birchschedule_fields_options'];
            foreach ( $fields_db as $field_name => $field_options ) {
                if ( array_key_exists( $field_name, $fields_request ) ) {
                    $form_options['fields'][$field_name] =
                    array_merge( $field_options, $fields_request[$field_name] );
                }
            }
            $ns->update_form_options( $form_options );
            set_transient( 'birchschedule_fb_info', __( 'Field Settings Updated' ), 60 );
            $field_box_id = $_POST['birchschedule_field_box_id'];
            wp_redirect( $ns->get_base_url() . '#' . $field_box_id );
            exit;
        };

    } );
