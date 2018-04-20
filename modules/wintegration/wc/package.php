<?php

birch_ns( 'birchschedule.wintegration.wc', function( $ns ) {

        global $birchschedule;

        $_ns_data = new stdClass();

        $ns->init = function() use( $ns, $_ns_data, $birchschedule ) {

            $_ns_data->booking_errors = array();

            $_ns_data->save_action_name = 'birchschedule_save_options_woocommerce';

            add_action( 'init', array( $ns, 'wp_init' ) );

            if ( $ns->is_woocommerce_activated() &&
                $birchschedule->wintegration->is_wc_integration_enabled() ) {

                add_filter( 'woocommerce_get_price_html',
                    array( $ns, 'get_price_html' ), 100, 2 );

                add_filter( 'woocommerce_loop_add_to_cart_link',
                    array( $ns, 'loop_add_to_cart_link' ), 20, 2 );

                add_filter( 'woocommerce_add_to_cart_validation',
                    array( $ns, 'add_to_cart_validation' ), 20, 3 );

                add_filter( 'woocommerce_add_cart_item_data',
                    array( $ns, 'add_cart_item_data' ), 20, 2 );

                add_action( 'woocommerce_is_purchasable',
                    array( $ns, 'is_product_purchasable' ), 20, 2 );

                add_action( 'woocommerce_before_add_to_cart_button',
                    array( $ns, 'render_booking_form' ) );

                add_filter( 'birchschedule_wintegration_wc_get_booking_attributes',
                    array( $ns, 'calculate_booking_attributes' ) );

                add_filter( 'woocommerce_get_item_data',
                    array( $ns, 'get_item_data' ), 20, 2 );

                add_filter( 'woocommerce_get_cart_item_from_session',
                    array( $ns, 'get_cart_item_from_session' ), 20, 3 );

                add_filter( 'birchschedule_model_schedule_get_staff_busy_time',
                    array( $ns, 'add_appointments_in_cart' ), 20, 4 );

                add_action( 'woocommerce_checkout_update_order_meta',
                    array( $ns, 'checkout_update_order_meta' ), 20, 2 );

                add_action( 'woocommerce_order_status_changed',
                    array( $ns, 'order_status_changed' ), 20, 3 );

                add_action( 'woocommerce_add_order_item_meta',
                    array( $ns, 'add_order_item_meta' ), 20, 2 );

                add_action( 'woocommerce_admin_order_item_values',
                    array( $ns, 'admin_order_item_values' ), 20, 3 );

                add_action( 'woocommerce_admin_order_item_headers',
                    array( $ns, 'admin_order_item_headers' ) );

                add_filter( 'woocommerce_get_product_from_item',
                    array( $ns, 'get_product_from_item' ), 20, 3 );

                add_filter( 'woocommerce_payment_complete_order_status',
                    array( $ns, 'payment_complete_order_status' ), 20, 2 );

                add_filter( 'woocommerce_is_sold_individually',
                    array( $ns, 'is_sold_individually' ), 20, 2 );

                add_filter( 'woocommerce_checkout_process', array( $ns, 'validate_booking_info' ), 20 );

            }

            add_action( 'wp_ajax_birchschedule_wintegration_wc_validate_booking_info',
                array( $ns, 'ajax_validate_booking_info' ) );

            add_action( 'wp_ajax_nopriv_birchschedule_wintegration_wc_validate_booking_info',
                array( $ns, 'ajax_validate_booking_info' ) );

        };

        $ns->wp_init = function() use( $ns, $birchschedule ) {
            if ( $ns->is_woocommerce_activated() &&
                $birchschedule->wintegration->is_wc_integration_enabled() ) {

                if ( $ns->is_wc_above_2_1() ) {
                    add_filter( 'woocommerce_order_item_name',
                        array( $ns, 'order_table_product_title' ), 20, 2 );
                } else {
                    add_filter( 'woocommerce_order_table_product_title',
                        array( $ns, 'order_table_product_title' ), 20, 2 );
                }
            }
        };

        $ns->is_woocommerce_activated = function() {
            return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
        };

        $ns->if_validate_booking_info = function() {
            return false;
        };

        $ns->is_product_appointment = function( $product_id ) use( $ns, $birchschedule ) {
            $options = $birchschedule->wintegration->get_options();
            $appointment_settings = array();
            if ( isset( $options['appointment_settings'] ) ) {
                $appointment_settings = $options['appointment_settings'];
            }
            $products_ids = array();
            foreach ( $appointment_settings as $product_settings ) {
                $products_ids[] = $product_settings['product_id'];
            }
            if ( in_array( $product_id, $products_ids ) ) {
                return true;
            } else {
                return false;
            }
        };

        $ns->ajax_validate_booking_info = function() use( $ns, $birchschedule ) {
            $appointment_errors = $birchschedule->view->bookingform->validate_appointment_info();
            $appointment1on1_errors = $birchschedule->view->bookingform->validate_appointment1on1_info();
            $errors = array_merge( $appointment_errors, $appointment1on1_errors );
            if ( $errors ) {
                $birchschedule->view->render_ajax_error_messages( $errors );
            } else {
                $birchschedule->view->render_ajax_success_message( array(
                        'code' => 'success',
                        'message' => ''
                    ) );
            }
        };

        $ns->format_price = function( $price ) use ( $ns, $birchschedule ) {
            $currency_code = $birchschedule->model->get_currency_code();
            $price = $birchschedule->model->number_format( $price );
            $formatted_price = $birchschedule->model->apply_currency_symbol( $price, $currency_code );
            return $formatted_price;
        };

        $ns->get_service_price_html = function( $min, $max ) use ( $ns, $birchschedule ) {
            if ( $min === $max ) {
                $formatted_price = $ns->format_price( $min );
                return $formatted_price;
            } else {
                $formatted_min = $ns->format_price( $min );
                $formatted_max = $ns->format_price( $max );
                return $formatted_min . '-' . $formatted_max;
            }
        };

        $ns->get_price_html = function( $price, $product ) use( $ns, $birchschedule ) {
            $product_id = method_exists($product, 'get_id') ? $product->get_id() : $product->id;
            if ( $ns->is_product_appointment( $product_id ) ) {
                $product_attrs = $product->get_attributes();
                $attributes = $ns->get_booking_attributes( $product_attrs );
                $service_ids = $attributes['service_ids'];
                $service_prices = array_map( function( $service_id ) use ( $birchschedule ) {
                        return $birchschedule->model->get_service_pre_payment_fee( $service_id );
                    }, $service_ids );
                $max_price = max( $service_prices );
                $min_price = min( $service_prices );

                return $ns->get_service_price_html( $min_price, $max_price );
            } else {
                return $price;
            }
        };

        $ns->loop_add_to_cart_link = function( $html, $product ) use( $ns, $birchschedule ) {
            $product_id = method_exists($product, 'get_id') ? $product->get_id() : $product->id;
            if ( $ns->is_product_appointment( $product_id ) ) {
                return '';
            }
            return $html;
        };

        $ns->get_cart_item_from_session = function( $cart_item, $cart_item_values, $cart_item_key ) use( $ns, $birchschedule ) {

            $product_id = $cart_item['product_id'];
            if ( $ns->is_product_appointment( $product_id ) ) {
                if ( isset( $cart_item_values['birchschedule'] ) ) {
                    $cart_item['birchschedule'] = $cart_item_values['birchschedule'];
                    $product = $cart_item['data'];
                    $service_id = $cart_item['birchschedule']['appointment']['_birs_appointment_service'];
                    $price = $birchschedule->model->get_service_pre_payment_fee( $service_id );
                    $product->set_price( $price );
                    $cart_item['data'] = $product;
                }
            }
            return $cart_item;
        };

        $ns->get_booking_array_attribute_names = function() {
            return array( 'location_ids', 'service_ids', 'staff_ids' );
        };

        $ns->get_booking_attributes = function( $product_attrs ) use( $ns, $birchschedule ) {
            $attributes = array();
            $array_attributes_names = $ns->get_booking_array_attribute_names();
            foreach ( $product_attrs as $product_attr ) {
                if ( in_array( $product_attr['name'], $array_attributes_names ) ) {
                    $value = $product_attr['value'];
                    $array_values = explode( '|', $value );
                    $trimed_values = array();
                    foreach ( $array_values as $array_value ) {
                        $trimed_values[] = trim( $array_value );
                    }
                    $attributes[$product_attr['name']] = $trimed_values;
                } else {
                    $attributes[$product_attr['name']] = $product_attr['value'];
                }
            }
            return $attributes;
        };

        $ns->calculate_booking_attributes = function( $attributes ) {
            $int_array_attrs = array( 'location_ids', 'service_ids', 'staff_ids' );
            foreach ( $int_array_attrs as $array_attr ) {
                if ( isset( $attributes[$array_attr] ) ) {
                    $array_values = $attributes[$array_attr];
                    $int_array_values = array();
                    foreach ( $array_values as $array_value ) {
                        $int_array_values[] = intval( $array_value );
                    }
                    $attributes[$array_attr] = $int_array_values;
                }
            }
            return $attributes;
        };

        $ns->get_appointment_display_data = function( $birs_data ) use ( $ns, $birchschedule ) {
            global $birchpress;

            $data = array();
            $appointment_data = $birs_data['appointment'];
            $appointment1on1_data = $birs_data['appointment1on1'];
            $fields_labels = $birchschedule->view->bookingform->get_fields_labels();
            $location = $birchschedule->model->
            get(
                $appointment_data['_birs_appointment_location'],
                array(
                    'base_keys' => array( 'post_title' ),
                    'meta_keys' => array()
                )
            );
            $data['_birs_appointment_location'] = array(
                'name' => $fields_labels['location'],
                'value' => $appointment_data['_birs_appointment_location'],
                'display' => $location['post_title']
            );
            $service = $birchschedule->model->
            get(
                $appointment_data['_birs_appointment_service'],
                array(
                    'base_keys' => array( 'post_title' ),
                    'meta_keys' => array()
                )
            );
            $data['_birs_appointment_service'] = array(
                'name' => $fields_labels['service'],
                'value' => $appointment_data['_birs_appointment_service'],
                'display' => $service['post_title']
            );
            $staff = $birchschedule->model->
            get(
                $appointment_data['_birs_appointment_staff'],
                array(
                    'base_keys' => array( 'post_title' ),
                    'meta_keys' => array()
                )
            );
            $data['_birs_appointment_staff'] = array(
                'name' => $fields_labels['service_provider'],
                'value' => $appointment_data['_birs_appointment_staff'],
                'display' => $staff['post_title']
            );
            $datetime =
            $birchpress->util->get_wp_datetime( $appointment_data['_birs_appointment_timestamp'] );
            $data['_birs_appointment_date'] = array(
                'name' => $fields_labels['date'],
                'value' => $datetime->format( 'm/d/Y' ),
                'display' => $birchpress->util->wp_format_date( $datetime )
            );
            $data['_birs_appointment_time'] = array(
                'name' => $fields_labels['time'],
                'value' => $birchpress->util->get_day_minutes( $datetime ),
                'display' => $birchpress->util->wp_format_time( $datetime )
            );
            $fields_names = $birs_data['appointment1on1_keys'];
            foreach ( $fields_names as $field_name ) {
                $display_value =
                $birchschedule->model->mergefields->get_merge_field_display_value(
                    $appointment1on1_data[$field_name]
                );
                $data[$field_name] = array(
                    'name' => $fields_labels[substr( $field_name, 6 )],
                    'value' => $appointment1on1_data[$field_name],
                    'display' => $display_value
                );
            }
            return $data;
        };

        $ns->get_item_data = function( $cart_item_data, $cart_item ) use ( $ns, $birchschedule ) {
            global $birchpress;

            $product_id = $cart_item['product_id'];
            if ( $ns->is_product_appointment( $product_id ) ) {
                if ( isset( $cart_item['birchschedule'] ) ) {
                    $birs_data = $cart_item['birchschedule'];
                    $appointment_display_data = $ns->get_appointment_display_data( $birs_data );
                    foreach ( $appointment_display_data as $field_data ) {
                        $cart_item_data[] = $field_data;
                    }
                }
            }
            return $cart_item_data;
        };

        $ns->add_cart_item_data = function( $cart_item_data, $product_id ) use( $ns, $birchschedule ) {
            global $birchpress;

            if ( $ns->is_woocommerce_activated() &&
                $birchschedule->wintegration->is_wc_integration_enabled() ) {
                if ( $ns->is_product_appointment( $product_id ) ) {
                    $fields = $birchschedule->model->get_appointment_fields();
                    $appointment_config = array(
                        'base_keys' => array(),
                        'meta_keys' => $fields
                    );
                    $appointment_data =
                    $birchschedule->view->merge_request( array(), $appointment_config, $_POST );
                    $appointment_data['_birs_appointment_padding_before'] =
                    $birchschedule->model->get_service_padding_before(
                        $appointment_data['_birs_appointment_service']
                    );
                    $appointment_data['_birs_appointment_padding_after'] =
                    $birchschedule->model->get_service_padding_after(
                        $appointment_data['_birs_appointment_service']
                    );
                    $datetime = array(
                        'date' => $_POST['birs_appointment_date'],
                        'time' => $_POST['birs_appointment_time']
                    );
                    $datetime = $birchpress->util->get_wp_datetime( $datetime );
                    $timestamp = $datetime->format( 'U' );
                    $appointment_data['_birs_appointment_timestamp'] = $timestamp;

                    if ( isset( $_POST['birs_appointment_fields'] ) ) {
                        $custom_fields = $_POST['birs_appointment_fields'];
                    } else {
                        $custom_fields = $birchschedule->model->get_appointment1on1_custom_fields();
                    }
                    $appointment1on1_config = array(
                        'base_keys' => array(),
                        'meta_keys' => $custom_fields
                    );
                    $appointment1on1_data =
                    $birchschedule->view->merge_request( array(), $appointment1on1_config, $_POST );

                    $cart_item_data['birchschedule'] = array();
                    $cart_item_data['birchschedule']['appointment'] = $appointment_data;
                    $cart_item_data['birchschedule']['appointment1on1'] = $appointment1on1_data;
                    $cart_item_data['birchschedule']['appointment_keys'] = $fields;
                    $cart_item_data['birchschedule']['appointment1on1_keys'] = $custom_fields;
                }
            }
            return $cart_item_data;
        };

        $ns->add_to_cart_validation = function( $result, $product_id ) use( $ns, $birchschedule, $_ns_data ) {
            global $woocommerce;

            if ( $ns->is_product_appointment( $product_id ) ) {
                $appointment_errors = $birchschedule->view->bookingform->validate_appointment_info();
                $appointment1on1_errors = $birchschedule->view->bookingform->validate_appointment1on1_info();
                $_ns_data->booking_errors = array_merge( $appointment_errors, $appointment1on1_errors );
                if ( $_ns_data->booking_errors ) {
                    $ns->add_wc_error( __( 'There was a problem with your submission. Please check error messages below.', 'birchschedule' ) );
                    return false;
                } else {
                    if ( $ns->is_wc_above_2_1() ) {
                        wc_clear_notices();
                    }
                    return $result;
                }
            } else {
                return $result;
            }
        };

        $ns->checkout_update_order_meta = function( $order_id, $posted ) use( $ns, $birchschedule ) {
            $order = new WC_Order( $order_id );
            $items = $order->get_items();
            $client = $birchschedule->model->get_client_by_email(
                $posted['billing_email'],
                array(
                    'base_keys' => array(),
                    'meta_keys' => array()
                )
            );
            if ( !$client ) {
                $client = array();
            }
            $client = array_merge(
                $client,
                array(
                    '_birs_client_name_first' => $posted['billing_first_name'],
                    '_birs_client_name_last' => $posted['billing_last_name'],
                    '_birs_client_address1' => $posted['billing_address_1'],
                    '_birs_client_address2' => $posted['billing_address_2'],
                    '_birs_client_city' => $posted['billing_city'],
                    '_birs_client_zip' => $posted['billing_postcode'],
                    '_birs_client_country' => $posted['billing_country'],
                    '_birs_client_state' => $posted['billing_state'],
                    '_birs_client_email' => $posted['billing_email'],
                    '_birs_client_phone' => $posted['billing_phone'],
                    'post_type' => 'birs_client'
                )
            );
            $client_config = array(
                'base_keys' => array(
                    'post_title'
                ),
                'meta_keys' => array(
                    '_birs_client_name_first', '_birs_client_name_last',
                    '_birs_client_address1', '_birs_client_address2',
                    '_birs_client_city', '_birs_client_zip',
                    '_birs_client_country', '_birs_client_state',
                    '_birs_client_email', '_birs_client_phone'
                )
            );
            $client_id = $birchschedule->model->save( $client, $client_config );
            foreach ( $items as $item_id => $item ) {
                $product_id = $item['product_id'];
                if ( $ns->is_product_appointment( $product_id ) ) {
                    if ( isset( $item['birchschedule'] ) ) {
                        $birs_data = maybe_unserialize( $item['birchschedule'] );
                        $appointment = $birs_data['appointment'];
                        $appointment1on1 = $birs_data['appointment1on1'];
                        $appointment1on1['_birs_client_id'] = $client_id;
                        $appointment1on1 = array_merge( $appointment, $appointment1on1 );
                        $appointment1on1_id = $birchschedule->model->booking->make_appointment1on1( $appointment1on1 );
                        $birchschedule->model->booking->change_appointment1on1_status( $appointment1on1_id, 'pending' );
                        $birs_data['appointment1on1']['ID'] = $appointment1on1_id;
                        woocommerce_update_order_item_meta( $item_id, 'birchschedule', $birs_data );
                    }
                }
            }
        };

        $ns->can_process_payment = function( $order_id, $old_status, $new_status ) {
            return $new_status == 'completed';
        };

        $ns->order_status_changed = function( $order_id, $old_status, $new_status ) use( $ns, $birchschedule ) {
            if ( $ns->can_process_payment( $order_id, $old_status, $new_status ) ) {
                $order = new WC_Order( $order_id );
                $items = $order->get_items();
                foreach ( $items as $item_id => $item ) {
                    $product_id = $item['product_id'];
                    if ( $ns->is_product_appointment( $product_id ) &&
                        isset( $item['birchschedule'] ) ) {

                        $birs_data = maybe_unserialize( $item['birchschedule'] );
                        $appointment1on1 = $birs_data['appointment1on1'];
                        if ( isset( $appointment1on1['ID'] ) && $appointment1on1['ID'] ) {
                            $appointment1on1 =
                            $birchschedule->model->get( $appointment1on1['ID'], array(
                                    'base_keys' => array(),
                                    'meta_keys' => array(
                                        '_birs_appointment_id', '_birs_client_id'
                                    )
                                ) );
                            $order_url = admin_url( sprintf( 'post.php?post=%s&action=edit', $order_id ) );
                            $order_link = sprintf( '<a href="%s">#%s</a>', $order_url, $order_id );
                            $payment = array(
                                'birs_payment_appointment' => $appointment1on1['_birs_appointment_id'],
                                'birs_payment_client' => $appointment1on1['_birs_client_id'],
                                'birs_payment_amount' => $item['line_subtotal'],
                                'birs_payment_currency' => $birchschedule->model->get_currency_code(),
                                'birs_payment_type' => 'woocommerce',
                                'birs_payment_trid' => uniqid(),
                                'birs_payment_notes' => sprintf( __( "WooCommerce Order ID: %s", 'birchschedule' ), $order_link ),
                                'birs_payment_timestamp' => strtotime( $order->modified_date ),
                                'birs_payment_3rd_txn_id' => $order_id
                            );
                            $birchschedule->wintegration->process_payment( $appointment1on1['ID'], $payment );
                        }
                    }
                }
            }
        };

        $ns->add_order_item_meta = function( $item_id, $values ) {
            if ( isset( $values['birchschedule'] ) ) {
                woocommerce_add_order_item_meta( $item_id, 'birchschedule', $values['birchschedule'], true );
            }
        };

        $ns->admin_order_item_values = function( $product, $item, $item_id ) use ( $ns, $birchschedule ) {

            global $theorder;

            if ( $theorder->has_appointment ) {
                if ( $ns->is_product_appointment( method_exists($product, 'get_id') ? $product->get_id() : $product->id ) ) {
                    if ( isset( $item['birchschedule'] ) ) {
?>
                    <td>
                    <?php
                        $fields_labels = $birchschedule->view->bookingform->get_fields_labels();
                        $appointment_data = maybe_unserialize( $item['birchschedule'] );
                        $appointment_display_data = $ns->get_appointment_display_data( $appointment_data );
?>
                    <style type="text/css">
                        dl.birchschedule {
                            margin: 0;
                        }
                        dl.birchschedule dt {
                            float: left;
                            clear: left;
                            font-weight: bold;
                            margin-right: 4px;
                        }
                        dl.birchschedule dd {
                            margin: 0;
                        }
                    </style>
                    <dl class="birchschedule">
                    <?php
                        foreach ( $appointment_display_data as $field_display_data ) {
?>
                        <dt><?php echo $field_display_data['name']; ?>:</dt>
                        <dd><?php echo $field_display_data['display']; ?></dd>
                    <?php
                        }
?>
                    </dl>
                    </td>
                    <?php
                    }
                } else {
?>
                <td></td>
                <?php
                }
            }
        };

        $ns->admin_order_item_headers = function() use ( $ns, $birchschedule ) {
            global $thepostid, $theorder, $woocommerce;

            if ( ! is_object( $theorder ) )
            $theorder = new WC_Order( $thepostid );

            $order = $theorder;
            $order_items = $order->get_items( apply_filters( 'woocommerce_admin_order_item_types', array( 'line_item', 'fee' ) ) );
            $has_appointment = false;
            foreach ( $order_items as $item_id => $item ) {
                switch ( $item['type'] ) {
                case 'line_item' :
                    $_product = $order->get_product_from_item( $item );
                    if ( $ns->is_product_appointment( method_exists($_product, 'get_id') ? $_product->get_id() : $_product->id ) ) {
                        $has_appointment = true;
                    }
                    break;
                }
            }
            $theorder->has_appointment = $has_appointment;
            if ( $has_appointment ) {
?>
        <th>
            <?php _e( 'Appointment Info', 'birchschedule' ); ?>
        </th>
        <?php
            }
        };

        $ns->get_product_from_item = function( $product, $item, $order ) use( $ns ) {
            if ( $ns->is_product_appointment( method_exists($product, 'get_id') ? $product->get_id() : $product->id ) ) {
            }
            return $product;
        };

        $ns->order_table_product_title = function( $title, $item ) use( $ns, $birchschedule ) {
            if ( !isset( $item['birchschedule'] ) ) {
                return $title;
            }
            $fields_labels = $birchschedule->view->bookingform->get_fields_labels();
            $appointment_data = maybe_unserialize( $item['birchschedule'] );
            $appointment_display_data = $ns->get_appointment_display_data( $appointment_data );
            ob_start();
?>
        <style type="text/css">
            dl.birchschedule {
                margin: 0;
            }
            dl.birchschedule dt {
                float: left;
                clear: left;
                font-weight: bold;
                margin-right: 4px;
            }
            dl.birchschedule dd {
                margin: 0;
            }
        </style>
        <dl class="birchschedule">
            <?php
            foreach ( $appointment_display_data as $field_display_data ) {
?>
                <dt><?php echo $field_display_data['name']; ?>:</dt>
                <dd><?php echo $field_display_data['display']; ?></dd>
            <?php
            }
?>
        </dl>
        <?php
            $title .= ob_get_clean();
            return $title;
        };

        $ns->add_appointments_in_cart = function( $busy_times, $staff_id, $location_id, $date ) use ( $ns, $birchschedule ) {

            global $woocommerce, $birchpress;

            if ( empty( $woocommerce->cart ) ) {
                return $busy_times;
            }

            $wc_cart = $woocommerce->cart->cart_contents;
            foreach ( $wc_cart as $cart_item_key => $values ) {
                if ( isset( $values['birchschedule'] ) ) {
                    $appointment_data = $values['birchschedule']['appointment'];
                    $datetime =
                    $birchpress->util->get_wp_datetime( $appointment_data['_birs_appointment_timestamp'] );
                    if ( $date->format( 'm/d/Y' ) == $datetime->format( 'm/d/Y' ) &&
                        $staff_id == $appointment_data['_birs_appointment_staff'] ) {

                        $start_time = $birchpress->util->get_day_minutes( $datetime ) -
                        $appointment_data['_birs_appointment_padding_before'];
                        $duration = $appointment_data['_birs_appointment_duration'] +
                        $appointment_data['_birs_appointment_padding_before'] +
                        $appointment_data['_birs_appointment_padding_after'];
                        $busy_times[] = array(
                            'busy_time' => $start_time,
                            'duration' => $duration
                        );
                    }
                }
            }
            return $busy_times;
        };

        $ns->payment_complete_order_status = function( $order_status, $order_id ) use( $ns, $birchschedule ) {

            if ( !$birchschedule->wintegration->if_autocomplete_order() ) {
                return $order_status;
            }

            $order = new WC_Order( $order_id );
            $appointment_order = false;
            if ( 'processing' == $order_status &&
                ( 'on-hold' == $order->status || 'pending' == $order->status || 'failed' == $order->status ) ) {
                if ( count( $order->get_items() ) > 0 ) {
                    foreach ( $order->get_items() as $item ) {
                        if ( 'line_item' == $item['type'] ) {
                            $product = $order->get_product_from_item( $item );
                            if ( $ns->is_product_appointment( method_exists($product, 'get_id') ? $product->get_id() : $product->id ) ) {
                                $appointment_order = true;
                                break;
                            }
                        }
                    }
                }
            }
            if ( $appointment_order ) {
                return 'completed';
            } else {
                return $order_status;
            }
        };

        $ns->is_sold_individually = function( $return, $product ) use( $ns, $birchschedule ) {
            if ( $ns->is_product_appointment( method_exists($product, 'get_id') ? $product->get_id() : $product->id ) ) {
                return true;
            } else {
                return $return;
            }
        };

        $ns->is_product_purchasable = function( $purchasable, $product ) use ( $ns, $birchschedule ) {
            if ( $ns->is_product_appointment( method_exists($product, 'get_id') ? $product->get_id() : $product->id ) ) {
                return true;
            } else {
                return $purchasable;
            }
        };

        $ns->render_booking_form = function() use( $ns, $birchschedule, $_ns_data ) {
            global $birchpress, $product;

            $product_id = method_exists($product, 'get_id') ? $product->get_id() : $product->id;
            if ( $ns->is_product_appointment( $product_id ) ) {
                $product_attrs = $product->get_attributes();
                $attributes = $ns->get_booking_attributes( $product_attrs );
                $custom_css = "";
                if ( isset( $attributes['custom_css'] ) ) {
                    $custom_css = $attributes['custom_css'];
                }
                if ( $birchschedule->view->bookingform->is_sc_attrs_empty() ) {
                    $birchschedule->view->bookingform->set_sc_attrs( $attributes );
                }
                $birchschedule->view->register_3rd_scripts();
                $birchschedule->view->register_3rd_styles();
                $birchschedule->view->enqueue_scripts( 'birchschedule_view_bookingform' );
                $birchschedule->view->enqueue_scripts( 'birchschedule_wintegration_bookingform' );
                wp_enqueue_style( 'birchschedule_bookingform' );
                wp_enqueue_style( 'birchschedule_wintegration_bookingform' );
                $fields_options = $birchschedule->fbuilder->get_fields_options();
                $field_order = $birchschedule->fbuilder->get_field_order();
?>
            <style type="text/css">
                <?php echo $custom_css; ?>
            </style>
            <div class="birchschedule" id="birs_booking_box">
                <div id="birs_appointment_form">
                    <input type="hidden" name="add-to-cart" value="<?php echo $product_id; ?>" />
                    <input type="hidden" id="birs_appointment_duration" name="birs_appointment_duration" />
                    <input type="hidden" id="birs_appointment_alternative_staff" name="birs_appointment_alternative_staff" value="" />
                    <div>
                        <ul>
                        <?php
                foreach ( $field_order as $field_name ) {
                    if ( isset( $fields_options[$field_name] ) ) {
                        $field_options = $fields_options[$field_name];

                        if ( ( $field_options['belong_to'] === 'appointment' &&
                                $field_options['visibility'] !== 'admin' ) ||
                            $field_name === 'appointment_section' ) {
                            $birchschedule->fbuilder->field->render_field_view_frontend( $field_options, false, $_ns_data->booking_errors );
                        }
                    }
                }
?>
                            <li>
                                <div>
                                    <span class="price">
                                        <span class="amount"></span>
                                    </span>
                                </div>
                            </li>
                            <li class="birs_footer">
                                <div class="birs_error" id="birs_booking_error" style="display: none;"></div>
                                <div style="display:none;" id="birs_please_wait"><?php _e( 'Please wait...', 'birchschedule' ); ?></div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <?php
            }
        };

        $ns->validate_booking_info = function() use ( $ns ) {
            global $woocommerce, $birchschedule, $birchpress;

            if ( !$ns->if_validate_booking_info() ) {
                return;
            }

            if ( empty( $woocommerce->cart ) ) {
                return;
            }

            $wc_cart = $woocommerce->cart->cart_contents;
            foreach ( $wc_cart as $cart_item_key => $values ) {
                if ( isset( $values['birchschedule'] ) ) {
                    $appointment_data = $values['birchschedule']['appointment'];
                    $staff_id = $appointment_data['_birs_appointment_staff'];
                    $location_id = $appointment_data['_birs_appointment_location'];
                    $service_id = $appointment_data['_birs_appointment_service'];
                    $datetime = $birchpress->util->get_wp_datetime( $appointment_data['_birs_appointment_timestamp'] );
                    $date_text = $datetime->format( 'm/d/Y' );
                    $date = $birchpress->util->get_wp_datetime(
                        array(
                            'date' => $date_text,
                            'time' => 0
                        )
                    );

                    $time_options = $birchschedule->model->schedule->get_staff_avaliable_time( $staff_id, $location_id,
                        $service_id, $date );
                    $time = $birchpress->util->get_day_minutes( $datetime );
                    $valid = array_key_exists( $time, $time_options ) && $time_options[$time]['avaliable'];

                    if ( !$valid ) {
                        wc_add_notice( __( 'The appointment time you choose is not available any more.', 'birchschedule' ), 'error' );
                        return;
                    }
                }
            }
        };

        $ns->is_wc_above_2_1 = function() {
            return function_exists( 'wc_add_notice' );
        };

        $ns->add_wc_error = function( $message ) use( $ns, $birchschedule ) {
            global $woocommerce;

            if ( $ns->is_wc_above_2_1() ) {
                wc_add_notice( $message, 'error' );
            } else {
                $woocommerce->add_error( $message );
            }
        };

    } );
