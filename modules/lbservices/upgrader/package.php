<?php

birch_ns( 'birchschedule.lbservices.upgrader', function( $ns ) {

        global $birchschedule;

        $ns->init = function() use( $ns, $birchschedule ) {

            $birchschedule->upgrade_module->when( $birchschedule->lbservices->is_module_lbservices, $ns->upgrade_module );

        };

        $ns->upgrade_module = function() use( $ns ) {
            $ns->upgrade_1_0_to_1_1();
        };

        $ns->get_db_version_location_based_services = function() {
            return get_option( 'birs_db_version_location_based_services', '1.0' );
        };

        $ns->upgrade_1_0_to_1_1 = function() use( $ns ) {
            global $birchpress;

            $version = $ns->get_db_version_location_based_services();
            if ( $version !== '1.0' ) {
                return;
            }
            $services = $birchpress->db->query(
                array(
                    'post_type' => 'birs_service'
                ),
                array(
                    'meta_keys' => array( '_birs_service_assigned_locations' ),
                    'base_keys' => array()
                )
            );
            $locations = $birchpress->db->query(
                array(
                    'post_type' => 'birs_location'
                ),
                array(
                    'meta_keys' => array(),
                    'base_keys' => array()
                )
            );
            $assigned_locations = array();
            foreach ( $locations as $location_id => $location ) {
                $assigned_locations[$location_id] = true;
            }
            $assigned_locations = serialize( $assigned_locations );
            foreach ( $services as $service_id => $service ) {
                $service['_birs_service_assigned_locations'] = $assigned_locations;
                $birchpress->db->save( $service, array(
                        'meta_keys' => array( '_birs_service_assigned_locations' ),
                        'base_keys' => array()
                    ) );
            }
            update_option( 'birs_db_version_location_based_services', '1.1' );
        };

    } );
