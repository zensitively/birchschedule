<?php

birch_ns( 'birchschedule.gsettings', function( $ns ) {

        $update_info = array();

        $ns->init = function() use( $ns ) {
            add_action( 'init', array( $ns, 'wp_init' ) );

            add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );

        };

        $ns->is_module_gsettings = function( $module ) {
            return $module['module'] === 'gsettings';
        };

        $ns->wp_init = function() use ( $ns ) {
            //add_action( 'birchschedule_view_show_notice', array( $ns, 'show_update_notice' ) );

            add_filter( 'site_transient_update_plugins', array( $ns, 'get_update_info' ), 20 );

            add_filter( 'birchschedule_view_settings_get_tabs', array( $ns, 'add_tab' ) );

            add_filter( 'birchschedule_model_get_currency_code', array( $ns, 'get_option_currency' ) );

            add_filter( 'birchschedule_view_calendar_get_default_view',
                array( $ns, 'get_option_default_calendar_view' ) );
        };

        $ns->wp_admin_init = function() use ( $ns ) {
            register_setting( 'birchschedule_options', 'birchschedule_options', array( $ns, 'sanitize_input' ) );
            $ns->add_settings_sections();
        };

        $ns->add_tab = function( $tabs ) use ( $ns ) {
            $tabs['general'] = array(
                'title' => __( 'General', 'birchschedule' ),
                'action' => array( $ns, 'render_page' ),
                'order' => 0
            );

            return $tabs;
        };

        $ns->add_settings_sections = function() use ( $ns ) {
            add_settings_section( 'birchschedule_general', __( 'General Options', 'birchschedule' ),
                array( $ns, 'render_section_general' ), 'birchschedule_settings' );
            $ns->add_settings_fields();
        };

        $ns->add_settings_fields = function() use ( $ns ) {
            add_settings_field( 'birchschedule_timezone', __( 'Timezone' ),
                array( $ns, 'render_timezone' ), 'birchschedule_settings', 'birchschedule_general' );

            add_settings_field( 'birchschedule_date_time_format', __( 'Date Format, Time Format', 'birchschedule' ),
                array( $ns, 'render_date_time_format' ), 'birchschedule_settings', 'birchschedule_general' );

            add_settings_field( 'birchschedule_start_of_week', __( 'Week Starts On', 'birchschedule' ),
                array( $ns, 'render_start_of_week' ), 'birchschedule_settings', 'birchschedule_general' );

            add_settings_field( 'birchschedule_currency', __( 'Currency', 'birchschedule' ),
                array( $ns, 'render_currency' ), 'birchschedule_settings', 'birchschedule_general' );

            add_settings_field( 'birchschedule_default_calendar_view', __( 'Default Calendar View', 'birchschedule' ),
                array( $ns, 'render_default_calendar_view' ), 'birchschedule_settings', 'birchschedule_general' );

        };

        $ns->get_option_currency = function() use ( $ns ) {
            $options = $ns->get_options();
            return $options['currency'];
        };

        $ns->get_option_default_calendar_view = function() use ( $ns ) {
            $options = $ns->get_options();
            return $options['default_calendar_view'];
        };

        $ns->render_section_general = function() {
            echo '';
        };

        $ns->get_options = function() use ( $ns ) {
            $options = get_option( 'birchschedule_options' );
            return $options;
        };

        $ns->render_timezone = function() {
            $timezone_url = admin_url( 'options-general.php' );
            echo sprintf(
                __( "<label>Timezone settings are located <a href='%s'>here</a>.</label>", 'birchschedule' ),
                $timezone_url );
        };

        $ns->render_date_time_format = function() {
            $timezone_url = admin_url( 'options-general.php' );
            echo sprintf(
                __( "<label>Date format, time format settings are located <a href='%s'>here</a>.</label>", 'birchschedule' ),
                $timezone_url );
        };

        $ns->render_start_of_week = function() {
            $timezone_url = admin_url( 'options-general.php' );
            echo sprintf(
                __( "<label>First day of week setting is located <a href='%s'>here</a>.</label>", 'birchschedule' ),
                $timezone_url );
        };

        $ns->map_currencies = function( $currency ) {
            if ( $currency['symbol_right'] != '' ) {
                return $currency['title'] . ' (' . $currency['symbol_right'] . ')';
            } else {
                return $currency['title'] . ' (' . $currency['symbol_left'] . ')';
            }
        };

        $ns->render_currency = function() use ( $ns ) {
            global $birchpress;

            $currencies = $birchpress->util->get_currencies();
            $currencies = array_map( array( $ns, 'map_currencies' ), $currencies );
            $currency = $ns->get_option_currency();
            echo '<select id="birchschedule_currency" name="birchschedule_options[currency]">';
            $birchpress->util->render_html_options( $currencies, $currency );
            echo '</select>';
        };

        $ns->render_default_calendar_view = function() use ( $ns ) {
            global $birchpress;

            $views = $birchpress->util->get_calendar_views();
            $default_view = $ns->get_option_default_calendar_view();
            echo '<select id="birchschedule_default_calenar_view" name="birchschedule_options[default_calendar_view]">';
            $birchpress->util->render_html_options( $views, $default_view );
            echo '</select>';
        };

        $ns->render_page = function() use ( $ns ) {
            $options = $ns->get_options();
            $version = $options['version'];
            settings_errors();
?>
                <form action="options.php" method="post">
                    <input type='hidden' name='birchschedule_options[version]' value='<?php echo $version; ?>'>
                    <?php settings_fields( 'birchschedule_options' ); ?>
                    <?php do_settings_sections( 'birchschedule_settings' ); ?>
                    <p class="submit">
                        <input name="Submit" type="submit" class="button-primary"
                               value="<?php _e( 'Save changes', 'birchschedule' ); ?>" />
                    </p>
                </form>
<?php
        };

        $ns->sanitize_input = function( $input ) {
            return $input;
        };

        $ns->get_update_info = function( $checked_data ) use ( &$update_info ) {
            $plugin_slug = "birchschedule";
            $slug_str = $plugin_slug . '/' . $plugin_slug . '.php';
            if ( isset( $checked_data->response[$slug_str] ) ) {
                $update_info = $checked_data->response[$slug_str];
                $update_info = array(
                    'version' => $update_info->new_version
                );
            }
            return $checked_data;
        };

        $ns->show_update_notice = function() use ( &$update_info ) {
            global $birchschedule;

            $product_name = $birchschedule->get_product_name();
            $update_url = admin_url( 'update-core.php' );
            $update_text = "%s %s is available! <a href='$update_url'>Please update now</a>.";
            if ( $update_info ):
?>
                <div class="updated inline">
                    <p><?php echo sprintf( $update_text, $product_name, $update_info['version'] ); ?></p>
                </div>
<?php
            endif;
        };

    } );
