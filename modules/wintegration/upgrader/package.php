<?php

birch_ns( 'birchschedule.wintegration.upgrader', function( $ns ) {

        global $birchschedule;

        $_ns_data = new stdClass();

        $ns->init = function() use ( $ns, $birchschedule ) {
            $ns->init_data();

            $birchschedule->upgrade_module->when( $birchschedule->wintegration->is_module_wintegration, $ns->upgrade_module );
        };

        $ns->init_data = function() use ( $_ns_data ) {

            $_ns_data->default_options_woocommerce_1_0 = array(
                'version' => '1.0',
                'enabled' => true,
                'autocomplete' => true
            );
            $_ns_data->default_options_woocommerce = $_ns_data->default_options_woocommerce_1_0;
        };

        $ns->get_default_options = function() use( $_ns_data ) {
            return $_ns_data->default_options_woocommerce;
        };

        $ns->upgrade_module = function() use( $ns ) {
            $ns->init_db();
        };

        $ns->init_db = function() use( $ns ) {
            $options = get_option( 'birchschedule_options_woocommerce' );
            if ( $options === false ) {
                add_option( 'birchschedule_options_woocommerce', $ns->get_default_options() );
            }
        };

    } );
