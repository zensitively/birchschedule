<?php

birch_ns( 'birchschedule.view', function( $ns ) {

        $scripts_data = array();

        $enqueued_scripts = array();

        $localized_scripts = array();

        $printed_scripts = array();

        $_ns_data = new stdClass();

        $ns->init = function() use ( $ns, $_ns_data ) {

            global $birchschedule;

            $_ns_data->page_hooks = array();

            add_action( 'init', array( $ns, 'wp_init' ) );

            add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );

            add_action( 'admin_menu', array( $ns, 'create_admin_menus' ) );

            add_action( 'custom_menu_order', array( $ns, 'if_change_custom_menu_order' ), 100 );

            add_action( 'menu_order', array( $ns, 'change_admin_menu_order' ), 100 );

            add_action( 'plugins_loaded', array( $ns, 'load_i18n' ) );
        };

        $ns->wp_init = function() use ( $ns ) {
            global $birchpress;

            if ( !defined( 'DOING_AJAX' ) ) {
                $birchpress->view->register_core_scripts();
                $ns->register_common_scripts();
                $ns->register_common_scripts_data_fns();
                $ns->register_3rd_styles();
                $ns->register_common_styles();
                add_action( 'wp_print_scripts',
                    array( $ns, 'localize_scripts' ) );

                if ( is_admin() ) {
                    add_action( 'admin_print_footer_scripts',
                        array( $ns, 'localize_scripts' ), 9 );
                    add_action( 'admin_print_footer_scripts',
                        array( $ns, 'post_print_scripts' ), 11 );
                } else {
                    add_action( 'wp_print_footer_scripts',
                        array( $ns, 'localize_scripts' ), 9 );
                    add_action( 'wp_print_footer_scripts',
                        array( $ns, 'post_print_scripts' ), 11 );
                }
            }
        };

        $ns->wp_admin_init = function() use ( $ns ) {
            add_action( 'load-post.php', array( $ns, 'on_load_post' ) );
            add_action( 'load-post-new.php', array( $ns, 'on_load_post_new' ) );
            add_action( 'admin_enqueue_scripts', array( $ns, 'on_admin_enqueue_scripts' ) );
            add_action( 'save_post', array( $ns, 'on_save_post' ), 10, 2 );
            add_filter( 'wp_insert_post_data', array( $ns, 'on_wp_insert_post_data' ), 10, 2 );
        };

        $ns->localize_scripts = function() use ( $ns, &$enqueued_scripts, &$printed_scripts ) {
            global $wp_scripts;

            $wp_scripts->all_deps( $enqueued_scripts, true );
            $all_scripts = $wp_scripts->to_do;

            foreach ( $all_scripts as $script ) {
                $ns->localize_script( $script );
            }
            $printed_scripts = $all_scripts;
        };

        $ns->localize_script = function( $script ) use ( $ns, &$scripts_data, &$localized_scripts ) {
            if ( isset( $scripts_data[$script] ) &&
                !in_array( $script, $localized_scripts ) ) {
                foreach ( $scripts_data[$script] as $data_name => $data_fn ) {
                    $data = call_user_func( $data_fn );
                    wp_localize_script( $script, $data_name, $data );
                }
                $localized_scripts[] = $script;
                $localized_scripts = array_unique( $localized_scripts );
            }
        };

        $ns->post_print_scripts = function() use ( $ns, &$printed_scripts ) {
            foreach ( $printed_scripts as $script ) {
                $ns->post_print_script( $script );
            }
        };

        $ns->post_print_script = function( $script ) {};

        $ns->on_load_post = function() use ( $ns ) {
            $post_type = $ns->get_current_post_type();
            $ns->load_post_edit( array(
                    'post_type' => $post_type
                ) );
            $ns->load_page_edit( array(
                    'post_type' => $post_type
                ) );
        };

        $ns->on_load_post_new = function() use ( $ns ) {
            $post_type = $ns->get_current_post_type();
            $ns->load_post_new( array(
                    'post_type' => $post_type
                ) );
            $ns->load_page_edit( array(
                    'post_type' => $post_type
                ) );
        };

        $ns->on_admin_enqueue_scripts = function( $hook ) use ( $ns ) {
            $post_type = $ns->get_current_post_type();
            if ( $hook == 'post-new.php' ) {
                $ns->enqueue_scripts_post_new( array(
                        'post_type' => $post_type
                    ) );
                $ns->enqueue_scripts_edit( array(
                        'post_type' => $post_type
                    ) );
            }
            if ( $hook == 'post.php' ) {
                $ns->enqueue_scripts_post_edit( array(
                        'post_type' => $post_type
                    ) );
                $ns->enqueue_scripts_edit( array(
                        'post_type' => $post_type
                    ) );
            }
            if ( $hook == 'edit.php' && isset( $_GET['post_type'] ) ) {
                $post_type = $_GET['post_type'];
                $ns->enqueue_scripts_list( array(
                        'post_type' => $post_type
                    ) );
            }
        };

        $ns->on_save_post = function( $post_id, $post ) use ( $ns ) {
            if ( !isset( $_POST['action'] ) || $_POST['action'] !== 'editpost' ) {
                return;
            }
            if ( empty( $post_id ) || empty( $post ) || empty( $_POST ) )
            return;
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
            return;
            if ( is_int( wp_is_post_revision( $post ) ) )
            return;
            if ( is_int( wp_is_post_autosave( $post ) ) )
            return;

            $post_a = (array)$post;
            if( empty( $post_a['post_type'] ) ) {
                return;
            }
            $ns->save_post( $post_a );
        };

        $ns->on_wp_insert_post_data = function( $post_data, $post_attr ) use ( $ns ) {
            if ( !isset( $_POST['action'] ) || $_POST['action'] !== 'editpost' ) {
                return $post_data;
            }

            if ( $post_data['post_status'] == 'auto-draft' ) {
                return $post_data;
            }
            return $ns->pre_save_post( $post_data, $post_attr );
        };

        $ns->enqueue_scripts_post_new = function( $arg ) {};

        $ns->enqueue_scripts_post_edit = function( $arg ) {};

        $ns->enqueue_scripts_edit = function( $arg ) {};

        $ns->enqueue_scripts_list = function( $arg ) {};

        $ns->load_page_edit = function( $arg ) {};

        $ns->load_post_edit = function( $arg ) {};

        $ns->load_post_new = function( $arg ) {};

        $ns->save_post = function( $post_a ) {};

        $ns->pre_save_post = function( $post_data, $post_attr ) {
            return $post_data;
        };

        $ns->get_current_post_type = function() {
            global $current_screen;

            if ( $current_screen && $current_screen->post_type ) {
                return $current_screen->post_type;
            }

            return '';
        };

        $ns->enqueue_scripts = function( $scripts ) use ( $ns, &$enqueued_scripts ) {
            if ( is_string( $scripts ) ) {
                $scripts = array( $scripts );
            }
            foreach ( $scripts as $script ) {
                wp_enqueue_script( $script );
            }
            $enqueued_scripts = array_merge( $enqueued_scripts, $scripts );
            $enqueued_scripts = array_unique( $enqueued_scripts );
        };

        $ns->enqueue_styles = function( $styles ) use ( $ns ) {
            if ( is_string( $styles ) ) {
                wp_enqueue_style( $styles );
                return;
            }
            if ( is_array( $styles ) ) {
                foreach ( $styles as $style ) {
                    if ( is_string( $style ) ) {
                        wp_enqueue_style( $style );
                    }
                }
            }
        };

        $ns->merge_request = function( $model, $config, $request ) {
            global $birchschedule;

            return $birchschedule->model->merge_data( $model, $config, $request );
        };

        $ns->apply_currency_to_label = function( $label, $currency_code = false ) {
            global $birchpress, $birchschedule;

            if ( $currency_code === false ) {
                $currency_code = $birchschedule->model->get_currency_code();
            }
            $currencies = $birchpress->util->get_currencies();
            $currency = $currencies[$currency_code];
            $symbol = $currency['symbol_right'];
            if ( $symbol == '' ) {
                $symbol = $currency['symbol_left'];
            }
            return $label = $label . ' (' . $symbol . ')';
        };

        $ns->render_errors = function() use ( $ns ) {
            $errors = $ns->get_errors();
            if ( $errors && sizeof( $errors ) > 0 ) {
                echo '<div id="birchschedule_errors" class="error fade">';
                foreach ( $errors as $error ) {
                    echo '<p>' . $error . '</p>';
                }
                echo '</div>';
                update_option( 'birchschedule_errors', '' );
            }
        };

        $ns->get_errors = function() {
            return get_option( 'birchschedule_errors' );
        };

        $ns->has_errors = function() use ( $ns ) {
            $errors = $ns->get_errors();
            if ( $errors && sizeof( $errors ) > 0 ) {
                return true;
            } else {
                return false;
            }
        };

        $ns->save_errors = function( $errors ) {
            update_option( 'birchschedule_errors', $errors );
        };

        $ns->get_screen = function( $hook_name ) {
            global $birchpress;

            return $birchpress->util->get_wp_screen( $hook_name );
        };

        $ns->show_notice = function() {};

        $ns->add_page_hook = function( $key, $hook ) use ( $ns, $_ns_data ) {
            $_ns_data->page_hooks[$key] = $hook;
        };

        $ns->get_page_hook = function( $key ) use ( $ns, $_ns_data ) {
            if ( isset( $_ns_data->page_hooks[$key] ) ) {
                return $_ns_data->page_hooks[$key];
            } else {
                return '';
            }
        };

        $ns->get_custom_code_css = function( $shortcode ) {
            return '';
        };

        $ns->get_shortcodes = function() {
            return array();
        };

        $ns->get_languages_dir = function() {
            return 'birchschedule/languages';
        };

        $ns->load_i18n = function() use ( $ns ) {
            $lan_dir = $ns->get_languages_dir();
            load_plugin_textdomain( 'birchschedule', false, $lan_dir );
        };

        $ns->create_admin_menus = function() use ( $ns ) {
            $ns->create_menu_scheduler();
            $ns->reorder_submenus();
        };

        $ns->if_change_custom_menu_order = function() {
            return true;
        };

        $ns->change_admin_menu_order = function( $menu_order ) {

            $custom_menu_order = array();

            $client_menu = array_search( 'edit.php?post_type=birs_client', $menu_order );

            foreach ( $menu_order as $index => $item ) {

                if ( ( ( 'edit.php?post_type=birs_appointment' ) == $item ) ) {
                    $custom_menu_order[] = $item;
                    $custom_menu_order[] = 'edit.php?post_type=birs_client';
                    unset( $menu_order[$client_menu] );
                } else {
                    if ( 'edit.php?post_type=birs_client' != $item )
                    $custom_menu_order[] = $item;
                }
            }

            return $custom_menu_order;
        };

        $ns->create_menu_scheduler = function() use ( $ns ) {
            $page_hook_calendar =
            add_submenu_page( 'edit.php?post_type=birs_appointment', __( 'Calendar', 'birchschedule' ),
                __( 'Calendar', 'birchschedule' ), 'edit_birs_appointments', 'birchschedule_calendar',
                array( $ns, 'render_calendar_page' ) );
            $ns->add_page_hook( 'calendar', $page_hook_calendar );

            $page_hook_settings =
            add_submenu_page( 'edit.php?post_type=birs_appointment',
                __( 'BirchPress Scheduler Settings', 'birchschedule' ),
                __( 'Settings', 'birchschedule' ), 'manage_birs_settings',
                'birchschedule_settings', array( $ns, 'render_settings_page' ) );
            $ns->add_page_hook( 'settings', $page_hook_settings );

            $page_hook_help = add_submenu_page( 'edit.php?post_type=birs_appointment',
                __( 'Help', 'birchschedule' ), __( 'Help', 'birchschedule' ),
                'read', 'birchschedule_help', array( $ns, 'render_help_page' ) );
            $ns->add_page_hook( 'help', $page_hook_help );
        };

        $ns->render_calendar_page = function() {};

        $ns->render_settings_page = function() {};

        $ns->render_help_page = function() {};

        $ns->reorder_submenus = function() use ( $ns ) {
            global $submenu;

            $sub_items = &$submenu['edit.php?post_type=birs_appointment'];
            $location = $ns->get_submenu( $sub_items, 'location' );
            $staff = $ns->get_submenu( $sub_items, 'staff' );
            $service = $ns->get_submenu( $sub_items, 'service' );
            $settings = $ns->get_submenu( $sub_items, 'settings' );
            $help = $ns->get_submenu( $sub_items, 'help' );
            $calendar = $ns->get_submenu( $sub_items, 'calendar' );
            $new_appointment = $ns->get_submenu( $sub_items, 'post-new.php?post_type=birs_appointment' );

            $sub_items = array(
                $calendar,
                $new_appointment,
                $location,
                $staff,
                $service,
                $settings,
                $help
            );
        };

        $ns->get_submenu = function( $submenus, $name ) use ( $ns ) {
            foreach ( $submenus as $submenu ) {
                $pos = strpos( $submenu[2], $name );
                if ( $pos || $pos === 0 ) {
                    return $submenu;
                }
            }
            return false;
        };

        $ns->register_script_data_fn = function( $handle, $data_name, $fn ) use ( $ns, &$scripts_data ) {

            if ( isset( $scripts_data[$handle] ) ) {
                $scripts_data[$handle][$data_name] = $fn;
            } else {
                $scripts_data[$handle] = array(
                    $data_name => $fn
                );
            }
        };

        $ns->get_admin_i18n_messages = function() {
            global $birchschedule;
            return $birchschedule->view->get_frontend_i18n_messages();
        };

        $ns->get_frontend_i18n_messages = function() {
            return array(
                'Loading...' => __( 'Loading...', 'birchschedule' ),
                'Loading appointments...' => __( 'Loading appointments...', 'birchschedule' ),
                'Saving...' => __( 'Saving...', 'birchschedule' ),
                'Save' => __( 'Save', 'birchschedule' ),
                'Please wait...' => __( 'Please wait...', 'birchschedule' ),
                'Schedule' => __( 'Schedule', 'birchschedule' ),
                'Are you sure you want to cancel this appointment?' => __( 'Are you sure you want to cancel this appointment?', 'birchschedule' ),
                'Your appointment has been cancelled successfully.' => __( 'Your appointment has been cancelled successfully.', 'birchschedule' ),
                "The appointment doesn't exist or has been cancelled." => __( "The appointment doesn't exist or has been cancelled.", 'birchschedule' ),
                'Your appointment has been rescheduled successfully.' => __( 'Your appointment has been rescheduled successfully.', 'birchschedule' ),
                'Your appointment can not be cancelled now according to our booking policies.' => __( 'Your appointment can not be cancelled now according to our booking policies.', 'birchschedule' ),
                'Your appointment can not be rescheduled now according to our booking policies.' => __( 'Your appointment can not be rescheduled now according to our booking policies.', 'birchschedule' ),
                'There are no available times.' => __( 'There are no available times.', 'birchschedule' ),
                '(Deposit)' => __( '(Deposit)', 'birchschedule' ),
                'Reschedule' => __( 'Reschedule', 'birchschedule' ),
                'Change' => __( 'Change', 'birchschedule' ),
                'No Preference' => __( 'No Preference', 'birchschedule' ),
                'All Locations' => __( 'All Locations', 'birchschedule' ),
                'All Providers' => __( 'All Providers', 'birchschedule' )
            );
        };

        $ns->render_ajax_success_message = function( $success ) {
?>
        <div id="birs_success" code="<?php echo $success['code']; ?>">
            <?php echo $success['message']; ?>
        </div>
<?php
            exit;
        };

        $ns->render_ajax_error_messages = function( $errors ) {
            global $birchpress;

            if ( $birchpress->util->is_error( $errors ) ) {
                $error_arr = array();
                $codes = $birchpress->util->get_error_codes( $errors );
                foreach ( $codes as $code ) {
                    $error_arr[$code] = $birchpress->util->get_error_message( $errors, $code );
                }
            } else {
                $error_arr = $errors;
            }
?>
        <div id="birs_errors">
            <?php foreach ( $error_arr as $error_id => $message ): ?>
                <div id="<?php echo $error_id; ?>"><?php echo $message; ?></div>
            <?php endforeach; ?>
        </div>
<?php
            exit;
        };
        $ns->get_query_array = function( $query, $keys ) {
            $source = array();
            $result = array();
            if ( is_string( $query ) ) {
                wp_parse_str( $query, $source );
            }
            else if ( is_array( $query ) ) {
                $source = $query;
            }
            foreach ( $keys as $key ) {
                if ( isset( $source[$key] ) ) {
                    $result[$key] = $source[$key];
                }
            }
            return $result;
        };

        $ns->get_query_string = function( $query, $keys ) use ( $ns ) {
            return http_build_query( $ns->get_query_array( $query, $keys ) );
        };

        $ns->get_script_data_fn_model = function() {
            global $birchschedule, $birchpress;
            return array(
                'admin_url' => admin_url(),
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'all_schedule' => $birchschedule->model->schedule->get_all_calculated_schedule(),
                'all_daysoff' => $birchschedule->model->get_all_daysoff(),
                'gmt_offset' => $birchpress->util->get_gmt_offset(),
                'future_time' => $birchschedule->model->get_future_time(),
                'cut_off_time' => $birchschedule->model->get_cut_off_time(),
                'fully_booked_days' => $birchschedule->model->schedule->get_fully_booked_days()
            );
        };

        $ns->get_script_data_fn_view = function() use ( $ns ) {
            global $birchpress, $birchschedule;

            return array(
                'datepicker_i18n_options' => $birchpress->util->get_datepicker_i18n_params(),
                'fc_i18n_options' => $birchpress->util->get_fullcalendar_i18n_params(),
                'i18n_messages' => $ns->get_frontend_i18n_messages(),
                'i18n_countries' => $birchpress->util->get_countries(),
                'i18n_states' => $birchpress->util->get_states(),
                'plugin_url' => $birchschedule->plugin_url()
            );
        };

        $ns->get_script_data_fn_admincommon = function() use ( $ns ) {
            return array(
                'i18n_messages' => $ns->get_admin_i18n_messages()
            );
        };

        $ns->register_common_scripts_data_fns = function() use ( $ns ) {
            $ns->register_script_data_fn( 'birchschedule_model', 'birchschedule_model',
                array( $ns, 'get_script_data_fn_model' ) );
            $ns->register_script_data_fn( 'birchschedule_view', 'birchschedule_view',
                array( $ns, 'get_script_data_fn_view' ) );
            $ns->register_script_data_fn( 'birchschedule_view_admincommon', 'birchschedule_view_admincommon',
                array( $ns, 'get_script_data_fn_admincommon' ) );
        };

        $ns->register_3rd_scripts = function() {
            global $birchpress, $birchschedule;

            $version = $birchschedule->get_product_version();

            $birchpress->view->register_3rd_scripts();
            wp_register_script( 'moment',
                $birchschedule->plugin_url() . '/lib/assets/js/moment/moment.min.js',
                array(), '1.7.0' );

            wp_register_script( 'jgrowl',
                $birchschedule->plugin_url() . '/lib/assets/js/jgrowl/jquery.jgrowl.js',
                array( 'jquery' ), '1.4.0' );

            wp_register_script( 'jscolor',
                $birchschedule->plugin_url() . '/lib/assets/js/jscolor/jscolor.js',
                array(), '1.4.0' );

            wp_register_script( 'select2',
                '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.full.min.js',
                array( 'jquery' ), '4.0.3' );

            wp_register_script( 'fullcalendar_birchpress',
                $birchschedule->plugin_url() . '/lib/assets/js/fullcalendar/fullcalendar_birchpress.js',
                array( 'jquery-ui-draggable', 'jquery-ui-resizable',
                    'jquery-ui-dialog', 'jquery-ui-datepicker',
                    'jquery-ui-tabs', 'jquery-ui-autocomplete' ), '1.6.4' );

            wp_register_script( 'filedownload_birchpress',
                $birchschedule->plugin_url() . '/lib/assets/js/filedownload/jquery.fileDownload.js',
                array( 'jquery' ), '1.4.0' );
        };

        $ns->register_3rd_styles = function() {
            global $birchschedule;

            $version = $birchschedule->get_product_version();

            wp_register_style( 'fullcalendar_birchpress',
                $birchschedule->plugin_url() . '/lib/assets/js/fullcalendar/fullcalendar.css',
                array(), '1.5.4' );

            wp_register_style( 'jquery-ui-bootstrap',
                $birchschedule->plugin_url() . '/lib/assets/css/jquery-ui-bootstrap/jquery-ui-1.9.2.custom.css',
                array(), '0.22' );
            wp_register_style( 'jquery-ui-no-theme',
                $birchschedule->plugin_url() . '/lib/assets/css/jquery-ui-no-theme/jquery-ui-1.9.2.custom.css',
                array(), '1.9.2' );
            wp_register_style( 'jquery-ui-smoothness',
                $birchschedule->plugin_url() . '/lib/assets/css/jquery-ui-smoothness/jquery-ui-1.9.2.custom.css',
                array(), '1.9.2' );

            wp_register_style( 'select2',
                '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.css',
                array(), '4.0.3' );

            wp_register_style( 'jgrowl',
                $birchschedule->plugin_url() . '/lib/assets/js/jgrowl/jquery.jgrowl.css',
                array(), '1.4.0' );
        };

        $ns->register_common_scripts = function() {
            global $birchschedule;

            $version = $birchschedule->get_product_version();

            wp_register_script( 'birchschedule',
                $birchschedule->plugin_url() . '/assets/js/base.js',
                array( 'jquery', 'birchpress' ), "$version" );

            wp_register_script( 'birchschedule_model',
                $birchschedule->plugin_url() . '/assets/js/model/base.js',
                array( 'jquery', 'birchpress', 'birchschedule' ), "$version" );

            wp_register_script( 'birchschedule_view',
                $birchschedule->plugin_url() . '/assets/js/view/base.js',
                array( 'jquery', 'birchpress', 'birchschedule', 'birchschedule_model' ), "$version" );

            wp_register_script( 'birchschedule_view_admincommon',
                $birchschedule->plugin_url() . '/assets/js/view/admincommon/base.js',
                array( 'jquery', 'birchpress', 'birchschedule', 'jgrowl' ), "$version" );

            wp_register_script( 'birchschedule_view_clients_edit',
                $birchschedule->plugin_url() . '/assets/js/view/clients/edit/base.js',
                array( 'birchschedule_view_admincommon', 'birchschedule_view' ), "$version" );

            wp_register_script( 'birchschedule_view_locations_edit',
                $birchschedule->plugin_url() . '/assets/js/view/locations/edit/base.js',
                array( 'birchschedule_view_admincommon', 'birchschedule_view' ), "$version" );

            wp_register_script( 'birchschedule_view_services_edit',
                $birchschedule->plugin_url() . '/assets/js/view/services/edit/base.js',
                array( 'birchschedule_view_admincommon', 'birchschedule_view' ), "$version" );

            wp_register_script( 'birchschedule_view_staff_edit',
                $birchschedule->plugin_url() . '/assets/js/view/staff/edit/base.js',
                array( 'birchschedule_view_admincommon', 'birchschedule_view',
                    'jscolor' ), "$version" );

            wp_register_script( 'birchschedule_view_calendar',
                $birchschedule->plugin_url() . '/assets/js/view/calendar/base.js',
                array( 'birchschedule_view_admincommon', 'birchschedule_view',
                    'fullcalendar_birchpress', 'moment' ), "$version" );

            wp_register_script( 'birchschedule_view_bookingform',
                $birchschedule->plugin_url() . '/assets/js/view/bookingform/base.js',
                array( 'jquery-ui-datepicker', 'birchschedule_view' ), "$version" );
        };

        $ns->register_common_styles = function() {
            global $birchschedule;

            $version = $birchschedule->get_product_version();

            wp_register_style( 'birchschedule_admincommon',
                $birchschedule->plugin_url() . '/assets/css/admincommon/base.css',
                array( 'jgrowl', 'select2' ), "$version" );

            wp_register_style( 'birchschedule_calendar',
                $birchschedule->plugin_url() . '/assets/css/calendar/base.css',
                array( 'jgrowl' ), "$version" );

            wp_register_style( 'birchschedule_appointments_common',
                $birchschedule->plugin_url() . '/assets/css/appointments/common/base.css',
                array( 'jquery-ui-no-theme', 'birchschedule_admincommon' ), "$version" );

            wp_register_style( 'birchschedule_appointments_edit',
                $birchschedule->plugin_url() . '/assets/css/appointments/edit/base.css',
                array( 'jquery-ui-no-theme', 'birchschedule_appointments_common' ), "$version" );

            wp_register_style( 'birchschedule_appointments_new',
                $birchschedule->plugin_url() . '/assets/css/appointments/new/base.css',
                array( 'jquery-ui-no-theme', 'birchschedule_appointments_common' ), "$version" );

            wp_register_style( 'birchschedule_services_edit',
                $birchschedule->plugin_url() . '/assets/css/services/edit/base.css',
                array(), "$version" );

            wp_register_style( 'birchschedule_staff_edit',
                $birchschedule->plugin_url() . '/assets/css/staff/edit/base.css',
                array(), "$version" );

            wp_register_style( 'birchschedule_locations_edit',
                $birchschedule->plugin_url() . '/assets/css/locations/edit/base.css',
                array(), "$version" );

            wp_register_style( 'birchschedule_bookingform',
                $birchschedule->plugin_url() . '/assets/css/bookingform/base.css',
                array( 'jquery-ui-no-theme' ), "$version" );
        };

    } );
