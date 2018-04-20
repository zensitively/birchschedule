<?php

birch_ns( 'birchschedule.gsettings.upgrader', function( $ns ) {

        global $birchschedule;

        $default_options_1_0 = array(
            'currency' => 'USD',
            'default_calendar_view' => 'agendaWeek'
        );

        $default_options_1_1 = $default_options_1_0;
        $default_options_1_1['version'] = '1.1';

        $default_options = $default_options_1_1;

        $ns->init = function() use ( &$default_options, $ns, $birchschedule ) {
            $options = get_option( 'birchschedule_options' );
            if ( $options === false ) {
                add_option( 'birchschedule_options', $default_options );
            }
            $birchschedule->upgrade_module->when( $birchschedule->gsettings->is_module_gsettings, $ns->upgrade_module );
        };

        $ns->upgrade_module = function() use( $ns ) {
            $ns->init();
            $ns->upgrade_1_0_to_1_1();
        };

        $ns->get_db_version_options = function() {
            $options = get_option( 'birchschedule_options' );
            if ( isset( $options['version'] ) ) {
                return $options['version'];
            } else {
                return '1.0';
            }
        };

        $ns->upgrade_1_0_to_1_1 = function() use ( $ns ) {
            $version = $ns->get_db_version_options();
            if ( $version !== '1.0' ) {
                return;
            }
            $options = get_option( 'birchschedule_options' );
            $options['version'] = '1.1';
            update_option( 'birchschedule_options', $options );
        };

    } );
