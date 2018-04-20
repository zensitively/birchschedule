<?php

birch_ns( 'birchschedule.ppayment', function( $ns ) {

        global $birchschedule;

        $_ns_data = new stdClass();

        $ns->init_data = function() use ( $ns, $_ns_data ) {

            $_ns_data->SAVE_ACTION_NAME = "birchschedule_save_options_payments";
            $_ns_data->meta_box_category = $ns->get_tab_name() . '_main';
        };

        $ns->get_meta_box_category = function() use( $ns, $_ns_data ) {
            return $_ns_data->meta_box_category;
        };

        $ns->is_module_ppayment = function( $module ) {
            return $module['module'] === 'ppayment';
        };

        $ns->is_tab_payments = function( $tab ) use ( $ns ) {
            return $tab['tab'] === $ns->get_tab_name();
        };

        $ns->get_tab_name = function() {
            return 'payments';
        };

        $ns->init = function() use( $ns, $birchschedule ) {

            $ns->init_data();

            $ns->redefine_functions();

            $birchschedule->view->settings->init_tab->when( $ns->is_tab_payments, $ns->init_tab );

            add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );

            add_filter( 'birchschedule_view_settings_get_tabs', array( $ns, 'add_tab' ) );

            add_action( 'birchschedule_view_services_render_service_info_after',
                array( $ns, 'render_service_settings_prepayment' ), 30 );

            add_filter( 'birchschedule_model_is_prepayment_enabled',
                array( $ns, 'is_prepayment_enabled' ), 10, 2 );

            add_filter( 'birchschedule_view_bookingform_get_success_message',
                array( $ns, 'get_success_message' ), 30, 2 );

            add_action( 'birchschedule_view_register_common_scripts_after',
                array( $ns, 'register_scripts' ) );

            add_action( 'birchschedule_view_bookingform_enqueue_scripts_after',
                array( $ns, 'enqueue_script_bookingform' ) );

            add_action( 'wp_ajax_nopriv_birchschedule_ppayment_confirm_paylater',
                array( $ns, 'ajax_confirm_paylater' ) );

            add_action( 'wp_ajax_birchschedule_ppayment_confirm_paylater',
                array( $ns, 'ajax_confirm_paylater' ) );
        };

        $ns->redefine_functions = function() use( $ns, $birchschedule ) {

            $birchschedule->view->bookingform->change_appointment1on1_status = $ns->change_appointment1on1_status;
        };

        $ns->register_scripts = function() use( $ns, $birchschedule ) {

            $version = $birchschedule->get_product_version();

            wp_register_script( 'birchschedule_ppayment_bookingform',
                $birchschedule->plugin_url() . '/modules/ppayment/assets/js/bookingform/base.js',
                array( 'birchschedule_view_bookingform' ), "$version" );
        };

        $ns->enqueue_script_bookingform = function() use ( $ns, $birchschedule ) {
            $birchschedule->view->enqueue_scripts(
                array(
                    'birchschedule_ppayment_bookingform'
                )
            );
        };

        $ns->ajax_confirm_paylater = function() use ( $ns, $birchschedule ) {
            $appointment1on1_id = $_POST['appointment1on1_id'];
            $status = 'publish';
            $birchschedule->model->booking->change_appointment1on1_status( $appointment1on1_id, $status );
            remove_filter( 'birchschedule_view_bookingform_get_success_message',
                array( $ns, 'get_success_message' ), 30 );
            $success = $birchschedule->view->bookingform->get_success_message( $appointment1on1_id );
            $birchschedule->view->render_ajax_success_message( $success );
        };

        $ns->change_appointment1on1_status = function( $appointment1on1_id ) use( $ns, $birchschedule ) {

            $appointment1on1 = $ns->get_appointment1on1_merge_values( $appointment1on1_id );
            $is_prepayment = $birchschedule->model->is_prepayment_enabled( $appointment1on1['_birs_appointment_service'] );
            if ( !$is_prepayment || $appointment1on1['_birs_appointment_pre_payment_fee'] < 0.01 ) {
                $status = 'publish';
                $birchschedule->model->booking->change_appointment1on1_status( $appointment1on1_id, $status );
            } else {
                $status = 'pending';
                $birchschedule->model->booking->change_appointment1on1_status( $appointment1on1_id, $status );
            }
        };

        $ns->wp_admin_init = function() use( $ns, $_ns_data ) {
            add_action( 'birchschedule_view_services_save_post_after',
                array( $ns, 'save_service_data' ) );

            add_action( 'admin_post_' . $_ns_data->SAVE_ACTION_NAME, array( $ns, 'save_options' ) );
        };

        $ns->add_tab = function( $tabs ) use( $ns ) {

            $tabs[$ns->get_tab_name()] = array(
                'title' => __( 'Payments', 'birchschedule' ),
                'action' => array( $ns, 'render_payments_page' ),
                'order' => 30
            );

            return $tabs;
        };

        $ns->init_tab = function() use( $ns ) {
            wp_enqueue_script( 'postbox' );
            wp_enqueue_script( 'birchschedule_view_admincommon' );
            wp_enqueue_style( 'birchschedule_admincommon' );
            $screen = $ns->get_screen();
            add_meta_box( 'birs_payments_pre_payment', __( 'Prepayment Settings', 'birchschedule' ),
                array( $ns, 'render_settings_prepayment' ), $screen,
                $ns->get_meta_box_category(), 'default' );
        };

        $ns->get_page_hook = function() {
            return "birchschedule_page_settings_tab_payments";
        };

        $ns->get_screen = function() use( $ns, $birchschedule ) {
            $page_hook = $ns->get_page_hook();
            $screen = $birchschedule->view->get_screen( $page_hook );
            return $screen;
        };

        $ns->render_settings_prepayment = function() use( $ns, $birchschedule ) {
            $options = get_option( 'birchschedule_options_payments' );
            $confirm_message = $options['pre_payment']['confirm_message'];
?>
        <table class="form-table">
            <tbody>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="birs_pre_payment_confirm_message">
                            <?php _e( 'Prepayment Message', 'birchschedule' ); ?>
                        </label>
                    </th>
                    <td>
                        <textarea style="width:99%;"
                            rows="8"
                            id="birs_pre_payment_confirm_message"
                            name="birchschedule_options_payments[pre_payment][confirm_message]"
                            ><?php echo $confirm_message; ?></textarea>
                    </td>
                </tr>
            </tbody>
        </table>
<?php
        };

        $ns->process_prepayment = function( $appointment1on1_id, $payment ) use( $ns, $birchschedule ) {
            global $birchpress;

            if ( !isset( $payment['birs_payment_3rd_txn_id'] ) ||
                !$payment['birs_payment_3rd_txn_id'] || !$appointment1on1_id ) {
                return;
            }
            $appointment1on1 = $birchschedule->model->get( $appointment1on1_id, array(
                    'keys' => array( '_birs_appointment_id', '_birs_client_id' )
                ) );
            if ( !$appointment1on1 ) {
                return;
            }
            $payments = $birchschedule->model->query(
                array(
                    'post_type' => 'birs_payment',
                    'meta_query' => array(
                        array(
                            'key' => '_birs_payment_3rd_txn_id',
                            'value' => $payment['birs_payment_3rd_txn_id']
                        ),
                        array(
                            'key' => '_birs_payment_appointment',
                            'value' => $appointment1on1['_birs_appointment_id']
                        ),
                        array(
                            'key' => '_birs_payment_client',
                            'value' => $appointment1on1['_birs_client_id']
                        )
                    )
                ),
                array(
                    'meta_keys' => array(),
                    'base_keys' => array()
                )
            );
            if ( $payments ) {
                return;
            }
            $payment_config = array(
                'meta_keys' => $birchschedule->model->get_payment_fields(),
                'base_keys' => array()
            );
            $payment_info = $birchschedule->view->merge_request(
                array(
                    'post_type' => 'birs_payment'
                ),
                $payment_config, $payment );
            $payment_id = $birchschedule->model->booking->make_payment( $payment_info );
            if ( $payment_id && !$birchpress->util->is_error( $payment_id ) ) {
                $birchschedule->model->booking->change_appointment1on1_status( $appointment1on1_id, 'publish' );
            }
        };

        $ns->is_prepayment_enabled = function( $is_enabled, $service_id ) use( $ns, $birchschedule ) {
            $service = $birchschedule->model->get( $service_id, array(
                    'meta_keys' => array(
                        '_birs_service_enable_pre_payment'
                    ),
                    'base_keys' => array()
                ) );
            return $service['_birs_service_enable_pre_payment'];
        };

        $ns->get_confirm_message_template = function() {
            $options = get_option( 'birchschedule_options_payments' );
            return $options['pre_payment']['confirm_message'];
        };

        $ns->get_appointment1on1_merge_values = function( $appointment1on1_id ) use( $birchschedule ) {
            return $birchschedule->model->mergefields->get_appointment1on1_merge_values( $appointment1on1_id );
        };

        $ns->get_success_message = function( $response, $appointment1on1_id ) use( $ns, $birchschedule ) {
            $appointment1on1 = $ns->get_appointment1on1_merge_values( $appointment1on1_id );
            $is_prepayment =
            $birchschedule->model->is_prepayment_enabled( $appointment1on1['_birs_appointment_service'] );
            if ( !$is_prepayment ) {
                return $response;
            }
            $confirm_message_template = $ns->get_confirm_message_template();
            if ( $appointment1on1['_birs_appointment_pre_payment_fee'] < 0.01 ) {
                return $response;
            }
            $confirm_message =
            $birchschedule->model->mergefields->apply_merge_fields( $confirm_message_template, $appointment1on1 );
            $confirm_message .= $ns->get_prepayment_methods_html( $appointment1on1_id );
            $confirm_message .= $ns->get_place_order_html();
            $confirm_message .= '<input type="hidden" id="birs_appointment1on1_id" value="' . $appointment1on1_id . '" />';
            return array(
                'code' => 'prepayment',
                'message' => $confirm_message
            );
        };

        $ns->is_paylater_enabled = function() {
            return false;
        };

        $ns->get_prepayment_methods = function() use ( $ns ) {
            $methods = array();
            if ( $ns->is_paylater_enabled() ) {
                $methods['paylater'] = array(
                    'title' => __( 'Pay later', 'birchschedule' ),
                    'description' => __( "", 'birchschedule' )
                );
            }
            return $methods;
        };

        $ns->get_prepayment_method_html = function( $method_name, $method, $appointment1on1_id ) {
            ob_start();
?>
        <label class="birs_payment_method_title" for="<?php echo 'birs_payment_method_' . $method_name; ?>">
            <?php echo $method['title']; ?>
        </label>
        <div class="birs_payment_method_description">
            <?php echo $method['description']; ?>
        </div>
        <?php
            return ob_get_clean();
        };

        $ns->get_prepayment_methods_html = function( $appointment1on1_id ) use( $ns, $birchschedule ) {
            ob_start();
?>
        <div id="birs_payment_methods" class="birs_payment_methods">
            <ul>
            <?php
            $methods = $ns->get_prepayment_methods();
            $i = 0;
            foreach ( $methods as $method_name => $method ) {
                if ( $i === 0 ) {
                    $checked = 'checked="checked"';
                } else {
                    $checked = "";
                }
                $id = 'birs_payment_method_' . $method_name;
?>
                <li>
                    <input id="<?php echo $id; ?>" name="birs_payment_method" type="radio" <?php echo $checked; ?> value="<?php echo $method_name; ?>"/>
                    <?php echo $ns->get_prepayment_method_html( $method_name, $method, $appointment1on1_id ); ?>
                </li>
<?php
                $i++;
            }
?>
            </ul>
        </div>
<?php
            return ob_get_clean();
        };

        $ns->get_place_order_html = function() use( $ns, $birchschedule ) {
            ob_start();
?>
        <div>
            <div style="display:none;" id="birs_please_wait"><?php _e( 'Please wait...', 'birchschedule' ) ?></div>
            <input type="button" id="birs_place_order" name="birs_place_order" value="<?php _e( 'Place order', 'birchschedule' ); ?>" />
        </div>
        <script type="text/javascript">
            jQuery(function($){
                birchschedule.ppayment.bookingform.initPlaceOrderForm();
            });
        </script>
<?php
            return ob_get_clean();
        };

        $ns->save_options = function() use( $ns, $_ns_data, $birchschedule ) {
            check_admin_referer( $_ns_data->SAVE_ACTION_NAME );
            if ( isset( $_POST['birchschedule_options_payments'] ) ) {
                $options = stripslashes_deep( $_POST['birchschedule_options_payments'] );
                update_option( "birchschedule_options_payments", $options );
            }
            set_transient( "birchschedule_payments_info", __( "Payments Settings Updated" ), 60 );
            $orig_url = $_POST['_wp_http_referer'];
            wp_redirect( $orig_url );
            exit;
        };

        $ns->render_payments_page = function() use( $ns, $birchschedule, $_ns_data ) {
            $screen = $ns->get_screen();
?>
        <style type="text/css">
            #notification_main-sortables .hndle {
                cursor: pointer;
            }
            #notification_main-sortables .wp-tab-panel {
                max-height: 500px;
            }
        </style>
        <div id="birchschedule_payments" class="wrap">
            <form method="post" action="<?php echo admin_url( 'admin-post.php' ) ?>">
                <?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>
                <?php wp_nonce_field( $_ns_data->SAVE_ACTION_NAME ); ?>
                <input type="hidden" name="action" value="<?php echo $_ns_data->SAVE_ACTION_NAME; ?>" />
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-1">
                        <div id="postbox-container-1" class="postbox-container">
                            <?php do_meta_boxes( $screen, $ns->get_meta_box_category(), array() ) ?>
                        </div>
                    </div>
                    <br class="clear" />
                </div>
                <input type="submit" name="submit"
                    value="<?php _e( 'Save changes', 'birchschedule' ); ?>"
                    class="button-primary" />
            </form>
        </div>
        <script type="text/javascript">
            //<![CDATA[
            jQuery(document).ready( function($) {
                postboxes.init = function() {};
                postboxes.add_postbox_toggles('<?php echo $ns->get_page_hook(); ?>');
                <?php
            $payments_info = get_transient( "birchschedule_payments_info" );
            if ( false !== $payments_info ) {
?>
                $.jGrowl('<?php echo esc_js( $payments_info ); ?>', {
                        life: 1000,
                        position: 'center',
                        header: '<?php _e( '&nbsp', 'birchschedule' ); ?>'
                    });
                <?php
                delete_transient( "birchschedule_payments_info" );
            }
?>
            });
            //]]>
        </script>
        <?php
        };

        $ns->get_fixed_booking_fee_html = function( $fixed ) {
            ob_start();
?>
        <input type='text'
            name='birs_service_pre_payment_fee[fixed]'
            class="birs_service_pre_payment_fee"
            value="<?php echo $fixed; ?>" />
        <?php
            return ob_get_clean();
        };

        $ns->get_percent_booking_fee_html = function( $percent ) {
            ob_start();
?>
        <input type='text'
            name='birs_service_pre_payment_fee[percent]'
            class="birs_service_pre_payment_fee"
            value="<?php echo $percent; ?>" />
<?php
            return ob_get_clean();
        };

        $ns->save_service_data = function( $service ) {
            if ( isset( $_POST['birs_service_enable_pre_payment'] ) ) {
                $enable_pre_payment = $_POST['birs_service_enable_pre_payment'];
            } else {
                $enable_pre_payment = false;
            }
            update_post_meta( $service['ID'], '_birs_service_enable_pre_payment',
                $enable_pre_payment );
            if ( isset( $_POST['birs_service_pre_payment_fee'] ) ) {
                $payment_fee = $_POST['birs_service_pre_payment_fee'];
                $payment_fee['fixed'] = floatval( $payment_fee['fixed'] );
                $payment_fee['percent'] = floatval( $payment_fee['percent'] );
                update_post_meta( $service['ID'], '_birs_service_pre_payment_fee',
                    serialize( $payment_fee ) );
            }
        };

        $ns->render_service_settings_prepayment = function( $post ) use( $ns, $birchschedule ) {
            $service = $birchschedule->model->get( $post->ID, array(
                    'meta_keys' => array(
                        '_birs_service_enable_pre_payment',
                        '_birs_service_pre_payment_fee'
                    ),
                    'base_keys' => array()
                ) );
            $checked_html = " ";
            if ( $service['_birs_service_enable_pre_payment'] ) {
                $checked_html = "checked='checked'";
            }
            $pre_payment_fee = array(
                'pre_payment_type' => 'fixed',
                'fixed' => 10,
                'percent' => 10,
            );
            $pre_payment_fee = array_merge( $pre_payment_fee, $service['_birs_service_pre_payment_fee'] );
            $fixed = $pre_payment_fee['fixed'];
            $percent = $pre_payment_fee['percent'];
            $fixed_checked = '';
            $percent_checked = '';
            if ( isset( $pre_payment_fee['pre_payment_type'] ) &&
                $pre_payment_fee['pre_payment_type'] == 'fixed' ) {

                $fixed_checked = ' checked="checked" ';
            }
            if ( isset( $pre_payment_fee['pre_payment_type'] ) &&
                $pre_payment_fee['pre_payment_type'] == 'percent' ) {

                $percent_checked = ' checked="checked" ';
            }
?>
        <style type="text/css">
            .form-field input[type="text"].birs_service_pre_payment_fee {
                width: 6em;
            }
            .form-field ul.birs_service_pre_payment_fee {
                margin-left: 2em;
            }
        </style>
        <div class="panel-wrap birchschedule">
            <table class="form-table">
                <tr class="form-field">
                    <th><label><?php _e( 'Prepayment', 'birchschedule' ); ?> </label>
                    </th>
                    <td>
                        <div>
                            <input type="checkbox"
                                name="birs_service_enable_pre_payment"
                                value="on"
                                <?php echo $checked_html; ?>
                                id="birs_service_enable_pre_payment"/>
                            <label for="birs_service_enable_pre_payment">
                                <?php _e( 'Enable prepayment', 'birchschedule' ); ?>
                            </label>
                        </div>
                        <div id="birs_service_pre_payment_settings">
                            <ul class="birs_service_pre_payment_fee">
                                <li>
                                    <input type='radio'
                                        name="birs_service_pre_payment_fee[pre_payment_type]"
                                        value="fixed"
                                        <?php echo $fixed_checked; ?> />
                                    <label>
                                        <?php
            $currency_code = $birchschedule->model->get_currency_code();
            $fixed_fee_html = $birchschedule->model->apply_currency_symbol(
                $ns->get_fixed_booking_fee_html( $fixed ), $currency_code );
            printf( __( 'Charge %s fixed booking fee.', 'birchschedule' ),
                $fixed_fee_html );
?>
                                    </label>
                                </li>
                                <li>
                                    <input type='radio'
                                        name="birs_service_pre_payment_fee[pre_payment_type]"
                                        value="percent"
                                        <?php echo $percent_checked; ?> />
                                    <label>
                                        <?php
            printf( __( 'Charge %s percent of service value.', 'birchschedule' ),
                $ns->get_percent_booking_fee_html( $percent ) );
?>
                                    </label>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        <script type="text/javascript">
            jQuery(function($){
                var showPrePaymentSettings = function() {
                    if($('#birs_service_enable_pre_payment').is(':checked')) {
                        $('#birs_service_pre_payment_settings').show();
                    } else {
                        $('#birs_service_pre_payment_settings').hide();
                    }
                }
                showPrePaymentSettings();
                $('#birs_service_enable_pre_payment').change(function(){
                    showPrePaymentSettings();
                });
            });
        </script>
        <?php
        };

    } );
