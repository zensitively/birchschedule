<?php

birch_ns( 'birchschedule.wintegration', function( $ns ) {

        global $birchschedule;

        $_ns_data = new stdClass();

        $ns->init = function() use ( $ns, $_ns_data, $birchschedule ) {

            $_ns_data->save_action_name = 'birchschedule_save_options_woocommerce';

            add_action( 'init', array( $ns, 'wp_init' ) );

            add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );

            add_filter( 'birchschedule_view_settings_get_tabs',
                array( $ns, 'add_tab' ) );

            add_action( 'update_option_birchschedule_options_woocommerce',
                array( $ns, 'setup_wc_products' ), 20, 2 );

            add_action( 'birchschedule_view_services_save_post_after',
                array( $ns, 'setup_wc_products' ), 20 );

            $birchschedule->view->settings->init_tab->when( $ns->is_tab_woocommerce, $ns->init_tab );
        };

        $ns->is_module_wintegration = function( $module ) {
            return $module['module'] === 'wintegration';
        };

        $ns->is_tab_woocommerce = function( $tab ) use ( $ns ) {
            return $tab['tab'] === $ns->get_tab_name();
        };

        $ns->get_tab_name = function() {
            return 'woocommerce';
        };

        $ns->wp_init = function() use( $ns ) {

            $ns->register_scripts();

            add_filter( 'birchschedule_model_booking_get_payment_types',
                array( $ns, 'add_payment_type' ) );
        };

        $ns->wp_admin_init = function() use( $ns, $_ns_data ) {

            add_action( 'admin_post_' . $_ns_data->save_action_name, array( $ns, 'save_options' ) );

            add_action( 'wp_ajax_birchschedule_wintegration_new_wc_product_settings',
                array( $ns, 'ajax_new_wc_product_settings' ) );
        };

        $ns->register_scripts = function() use ( $ns, $birchschedule ) {

            $version = $birchschedule->get_product_version();

            wp_register_script( 'birchschedule_wintegration_bookingform',
                $birchschedule->plugin_url() . '/modules/wintegration/assets/js/bookingform.js',
                array( 'birchschedule_view_bookingform' ), "$version" );

            wp_register_script( 'birchschedule_wintegration_settings',
                $birchschedule->plugin_url() . '/modules/wintegration/assets/js/settings.js',
                array(
                    'birchschedule_view_admincommon', 'select2',
                    'birchschedule_view'
                ), $version );

            wp_register_style( 'birchschedule_wintegration_bookingform',
                $birchschedule->plugin_url() . '/modules/wintegration/assets/css/bookingform.css',
                array(), $version );
        };

        $ns->add_tab = function( $tabs ) use( $ns ) {
            $tabs[$ns->get_tab_name()] = array(
                'title' => __( 'WooCommerce', 'birchschedule' ),
                'action' => array( $ns, 'render_settings_page' ),
                'order' => 45
            );

            return $tabs;
        };

        $ns->init_tab = function() use( $ns, $birchschedule ) {
            wp_enqueue_style( 'birchschedule_admincommon' );
            $birchschedule->view->enqueue_scripts(
                array(
                    'birchschedule_wintegration_settings', 'birchschedule_view_admincommon',
                    'postbox', 'birchschedule_view', 'birchschedule_model'
                )
            );
            $tab_name = $ns->get_tab_name();
            $page_hook = $birchschedule->view->settings->get_tab_page_hook( $tab_name );
            $metabox_category = $birchschedule->view->settings->get_tab_metabox_category( $tab_name );
            $screen = $birchschedule->view->get_screen( $page_hook );
            add_meta_box( 'birs_woocommerce_general', __( 'WooCommerce Integration', 'birchschedule' ),
                array( $ns, 'render_settings_general' ), $screen,
                $metabox_category, 'default' );
        };

        $ns->process_payment = function( $appointment1on1_id, $payment ) use( $ns, $birchschedule ) {
            $birchschedule->ppayment->process_prepayment( $appointment1on1_id, $payment );
        };

        $ns->add_payment_type = function( $payment_types ) {
            $payment_types['woocommerce'] = 'WooCommerce';
            return $payment_types;
        };

        $ns->is_wc_integration_enabled = function() use( $ns ) {
            $options = $ns->get_options();
            if ( isset( $options['enabled'] ) ) {
                return $options['enabled'];
            } else {
                return false;
            }
        };

        $ns->if_autocomplete_order = function() use( $ns ) {
            $options = $ns->get_options();
            if ( isset( $options['autocomplete'] ) ) {
                return $options['autocomplete'];
            } else {
                return false;
            }
        };

        $ns->render_settings_general = function() use( $ns, $birchschedule ) {
            $options = $ns->get_options();
            if ( isset( $options['enabled'] ) && $options['enabled'] ) {
                $enabled_check = ' checked="checked" ';
            } else {
                $enabled_check = "";
            }
            if ( isset( $options['autocomplete'] ) && $options['autocomplete'] ) {
                $autocomplete_check = ' checked="checked" ';
            } else {
                $autocomplete_check = "";
            }
            if ( isset( $options['appointment_settings'] ) ) {
                $appointment_settings = $options['appointment_settings'];
            } else {
                $appointment_settings = array();
            }
?>
    <style type="text/css">
        .form-table.birs_wc_product {
            margin: 0;
            border-bottom: 1px solid #DFDFDF;
            border-top: 1px solid white;
        }
        .birs_wc_new_product {
            border-top: 1px solid white;
            padding-top: 4px;
        }
        .birs_wc_delete_product {
            float: right;
        }
        .form-table.birs_wc_product input[type=text] {
            width: 100%;
            max-width: 24em;
        }
        .form-table.birs_wc_product select {
            width: 100%;
            max-width: 24em;
        }
        .form-table.birs_wc_product textarea {
            width: 100%;
            max-width: 31em;
        }
    </style>
    <table class="form-table">
        <tbody>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="birchschedule_options_woocommerce_enabled">
                        <?php _e( 'Enable/Disable', 'birchschedule' ); ?>
                    </label>
                </th>
                <td>
                    <input name="birchschedule_options_woocommerce[enabled]"
                         id="birchschedule_options_woocommerce_enabled"
                         type="checkbox" value="on"
                         <?php echo $enabled_check; ?> />
                    <label for="birchschedule_options_woocommerce_enabled">
                         <?php _e( 'Enable WooCommerce integration', 'birchschedule' ); ?>
                    </label>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="birchschedule_options_woocommerce_autocomplete">
                        <?php _e( 'Autocomplete Order', 'birchschedule' ); ?>
                    </label>
                </th>
                <td>
                    <input name="birchschedule_options_woocommerce[autocomplete]"
                         id="birchschedule_options_woocommerce_autocomplete"
                         type="checkbox" value="on"
                         <?php echo $autocomplete_check; ?> />
                    <label for="birchschedule_options_woocommerce_autocomplete">
                         <?php _e( 'Enable WooCommerce automatically set appointment orders on Completed status right after a successfull payment.', 'birchschedule' ); ?>
                    </label>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label>
                        <?php _e( 'Products Settings', 'birchschedule' ); ?>
                    </label>
                </th>
                <td>
                    <div id="birs_wc_products">
                        <?php
            foreach ( $appointment_settings as $setting_id => $product_settings ) {
                $ns->render_wc_product_settings( $setting_id, $product_settings );
            }
?>
                    </div>
                    <div class="birs_wc_new_product" id="birs_wc_new_product">
                        <a href="javascript:void(0);"><?php _e( '+ Add Product' ); ?></a>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
        };

        $ns->ajax_new_wc_product_settings = function() use( $ns, $birchschedule ) {
            $uid = uniqid();
            $locations = $birchschedule->model->query(
                array(
                    'post_type' => 'birs_location'
                ),
                array(
                    'base_keys' => array( 'post_title' ),
                    'meta_keys' => array()
                )
            );
            $location_ids = array_keys( $locations );
            $services = $birchschedule->model->query(
                array(
                    'post_type' => 'birs_service'
                ),
                array(
                    'base_keys' => array( 'post_title' ),
                    'meta_keys' => array()
                )
            );
            $service_ids = array_keys( $services );
            $staff = $birchschedule->model->query(
                array(
                    'post_type' => 'birs_staff'
                ),
                array(
                    'base_keys' => array( 'post_title' ),
                    'meta_keys' => array()
                )
            );
            $staff_ids = array_keys( $staff );
            $settings = array(
                'product_id' => '',
                'location_ids' => implode( ',', $location_ids ),
                'service_ids' => implode( ',', $service_ids ),
                'staff_ids' => implode( ',', $staff_ids ),
                'custom_css' => ''
            );
            $ns->render_wc_product_settings( $uid, $settings );
            exit;
        };

        $ns->get_staff_options = function() use( $ns, $birchschedule ) {
            $staff = $birchschedule->model->query(
                array(
                    'post_type' => 'birs_staff'
                ),
                array(
                    'base_keys' => array( 'post_title' ),
                    'meta_keys' => array()
                )
            );
            $staff_options = array();
            foreach ( $staff as $thestaff_id => $thestaff ) {
                $staff_options[] = array(
                    'id' => $thestaff_id,
                    'text' => $thestaff['post_title']
                );
            }
            return $staff_options;
        };

        $ns->render_wc_product_settings = function( $uid, $settings ) use( $ns, $birchschedule ) {
            global $birchpress;

            $el_id_prefix = 'birchschedule_options_woocommerce_appointment_settings_' . $uid . '_';
            $product_id = $settings['product_id'];
            $location_ids = explode( ',', $settings['location_ids'] );
            $service_ids = explode( ',', $settings['service_ids'] );
            $staff_ids = explode( ',', $settings['staff_ids'] );
            $products = $birchpress->db->query(
                array(
                    'post_type' => 'product'
                ),
                array(
                    'base_keys' => array( 'post_title' ),
                    'meta_keys' => array()
                )
            );
            $products_options = array();
            foreach ( $products as $product ) {
                $wc_product = function_exists('wc_get_product') ? wc_get_product( $product['ID'] ) : get_product( $product['ID'] );
                $product_type = method_exists($wc_product, 'get_type') ? $wc_product->get_type() : $wc_product->product_type;

                if ( $product_type === 'simple' ) {
                    $products_options[$product['ID']] = $product['post_title'];
                }
            }
            $locations = $birchschedule->model->query(
                array(
                    'post_type' => 'birs_location'
                ),
                array(
                    'base_keys' => array( 'post_title' ),
                    'meta_keys' => array()
                )
            );
            $locations_options = array();
            foreach ( $locations as $location_id => $location ) {
                $locations_options[] = array(
                    'id' => $location_id,
                    'text' => $location['post_title']
                );
            }
            $services = $birchschedule->model->query(
                array(
                    'post_type' => 'birs_service'
                ),
                array(
                    'base_keys' => array( 'post_title' ),
                    'meta_keys' => array()
                )
            );
            $services_options = array();
            foreach ( $services as $service_id => $service ) {
                $services_options[] = array(
                    'id' => $service_id,
                    'text' => $service['post_title']
                );
            }
            $staff_options = $ns->get_staff_options();
?>
        <table class="form-table birs_wc_product" id="<?php echo $el_id_prefix; ?>settings">
            <tbody>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label>
                            <?php _e( 'WooCommerce Product', 'birchschedule' ); ?>
                        </label>
                    </th>
                    <td>
                        <div>
                            <select
                                name="birchschedule_options_woocommerce[appointment_settings][<?php echo $uid; ?>][product_id]"
                                id="<?php echo $el_id_prefix; ?>product_id" >
                                <?php $birchpress->util->render_html_options( $products_options, $product_id ); ?>
                            </select>
                            <a href="javascript:void(0);" class="birs_wc_delete_product"
                                id="<?php echo $el_id_prefix; ?>delete">
                                <?php _e( 'Delete', 'birchschedule' ); ?>
                            </a>
                        </div>
                        <div>
                            <label><?php _e( '(Only simple products are listed here)', 'birchschedule' ); ?></label>
                        </div>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label>
                            <?php _e( 'Locations', 'birchschedule' ); ?>
                        </label>
                    </th>
                    <td>
                        <input 
                            id="<?php echo $el_id_prefix; ?>location_ids_input"
                            name="birchschedule_options_woocommerce[appointment_settings][<?php echo $uid; ?>][location_ids]"
                            type="hidden" value="<?php echo $settings['location_ids']; ?>" />
                        <select 
                            style="width:100%;max-width:24em;"
                            id="<?php echo $el_id_prefix; ?>location_ids"
                            multiple="multiple">
                            <?php foreach($locations_options as $location_option) {
                                $selected = "";
                                if(in_array($location_option['id'], $location_ids)) {
                                    $selected = 'selected="selected"';
                                }
                             ?>
                                <option value="<?php echo $location_option['id']; ?>" <?php echo $selected ?>>
                                    <?php echo $location_option['text']; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label>
                            <?php _e( 'Services', 'birchschedule' ); ?>
                        </label>
                    </th>
                    <td>
                        <input 
                            id="<?php echo $el_id_prefix; ?>service_ids_input"
                            name="birchschedule_options_woocommerce[appointment_settings][<?php echo $uid; ?>][service_ids]"
                            type="hidden" value="<?php echo $settings['service_ids']; ?>" />
                        <select 
                            style="width:100%;max-width:24em;"
                            id="<?php echo $el_id_prefix; ?>service_ids"
                            multiple="multiple">
                            <?php foreach($services_options as $service_option) {
                                $selected = "";
                                if(in_array($service_option['id'], $service_ids)) {
                                    $selected = 'selected="selected"';
                                }
                             ?>
                                <option value="<?php echo $service_option['id']; ?>" <?php echo $selected ?>>
                                    <?php echo $service_option['text']; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label>
                            <?php _e( 'Providers', 'birchschedule' ); ?>
                        </label>
                    </th>
                    <td>
                        <input 
                            id="<?php echo $el_id_prefix; ?>staff_ids_input"
                            name="birchschedule_options_woocommerce[appointment_settings][<?php echo $uid; ?>][staff_ids]"
                            type="hidden" value="<?php echo $settings['staff_ids']; ?>" />
                        <select 
                            style="width:100%;max-width:24em;"
                            id="<?php echo $el_id_prefix; ?>staff_ids"
                            multiple="multiple">
                            <?php foreach($staff_options as $staff_option) {
                                $selected = "";
                                if(in_array($staff_option['id'], $staff_ids)) {
                                    $selected = 'selected="selected"';
                                }
                             ?>
                                <option value="<?php echo $staff_option['id']; ?>" <?php echo $selected ?>>
                                    <?php echo $staff_option['text']; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label>
                            <?php _e( 'Custom CSS', 'birchschedule' ); ?>
                        </label>
                    </th>
                    <td>
                        <textarea
                            rows="5"
                            name="birchschedule_options_woocommerce[appointment_settings][<?php echo $uid; ?>][custom_css]"
                            id="<?php echo $el_id_prefix; ?>custom_css"><?php echo $settings['custom_css']; ?></textarea>
                    </td>
                </tr>
            </tbody>
            <script type="text/javascript">
                jQuery(function($){
                    var idPrefix = "<?php echo $el_id_prefix; ?>";
                    var productSettingsElId = idPrefix + 'settings';
                    var deleteElId = idPrefix + 'delete';
                    var productElId = idPrefix + 'product_id';
                    var locationElId = idPrefix + 'location_ids';
                    var serviceElId = idPrefix + 'service_ids';
                    var staffElId = idPrefix + 'staff_ids';

                    $('#' + locationElId).select2();
                    $('#' + locationElId).on('change', function(event) {
                        $('#' + locationElId + '_input').val($('#' + locationElId).select2('val'));
                    });
                    $('#' + serviceElId).select2();
                    $('#' + serviceElId).on('change', function(event) {
                        $('#' + serviceElId + '_input').val($('#' + serviceElId).select2('val'));
                    });
                    $('#' + staffElId).select2();
                    $('#' + staffElId).on('change', function(event) {
                        $('#' + staffElId + '_input').val($('#' + staffElId).select2('val'));
                    });

                    $('#' + deleteElId).click(function(){
                        $('#' + productSettingsElId).remove();
                    });
                });
            </script>
        </table>
        <?php
        };

        $ns->render_settings_page = function() use( $ns, $birchschedule ) {
            $birchschedule->view->settings->render_tab_page( $ns->get_tab_name() );
        };

        $ns->get_options = function() {
            return get_option( 'birchschedule_options_woocommerce' );
        };

        $ns->save_options = function() use( $ns, $birchschedule ) {
            $tab_name = $ns->get_tab_name();
            $message = __( 'WooCommerce Settings Updated', 'birchschedule' );
            $birchschedule->view->settings->save_tab_options( $tab_name, $message );
        };

        $ns->setup_wc_products = function() use( $ns, $birchschedule ) {
            global $woocommerce;

            $options = $ns->get_options();
            if ( isset( $options['appointment_settings'] ) ) {
                $products_settings = $options['appointment_settings'];
            } else {
                $products_settings = array();
            }
            foreach ( $products_settings as $product_settings ) {
                $product_id = $product_settings['product_id'];
                update_post_meta( $product_id, '_virtual', 'yes' );
                update_post_meta( $product_id, '_sold_individually', 'yes' );
                $product_attributes =
                array(
                    array(
                        'name' => 'location_ids',
                        'value' => str_replace( ',', '|', $product_settings['location_ids'] ),
                        'position' => 0,
                        'is_visible' => 0,
                        'is_variation' => 0,
                        'is_taxonomy' => 0
                    ),
                    array(
                        'name' => 'service_ids',
                        'value' => str_replace( ',', '|', $product_settings['service_ids'] ),
                        'position' => 1,
                        'is_visible' => 0,
                        'is_variation' => 0,
                        'is_taxonomy' => 0
                    ),
                    array(
                        'name' => 'staff_ids',
                        'value' => str_replace( ',', '|', $product_settings['staff_ids'] ),
                        'position' => 2,
                        'is_visible' => 0,
                        'is_variation' => 0,
                        'is_taxonomy' => 0
                    ),
                    array(
                        'name' => 'custom_css',
                        'value' => $product_settings['custom_css'],
                        'position' => 3,
                        'is_visible' => 0,
                        'is_variation' => 0,
                        'is_taxonomy' => 0
                    )
                );
                update_post_meta( $product_id, '_product_attributes', $product_attributes );
            }
        };

    } );
