<?php

birch_ns( 'birchschedule.fbuilder.field.email', function( $ns ) {

        global $birchschedule;

        $ns->init = function() use( $ns, $birchschedule ) {
            add_filter( 'birchschedule_fbuilder_field_get_default_field_config', function( $config ) {
                    $config['email'] = array(
                        'category' => 'custom_fields',
                        'label' => __( 'Untitled', 'birchschedule' ),
                        'type' => 'email',
                        'visibility' => 'both',
                        'required' => false
                    );
                    return $config;
                } );

            $birchschedule->fbuilder->field->validate->when( $ns->is_field_type_email, $ns->validate );
            $birchschedule->fbuilder->field->render_field_elements->when( $ns->is_field_type_email, $ns->render_field_elements );
        };

        $ns->is_field_type_email = function( $field ) {
            return $field['type'] === 'email';
        };

        $ns->render_field_elements = function( $field, $value = false ) use( $birchschedule ) {
            $birchschedule->fbuilder->field->singleline->render_field_elements( $field, $value );
        };

        $ns->validate = function( $field ) use( $birchschedule ) {

            $error = $birchschedule->fbuilder->field->validate->call_default( $field );
            if ( !$error ) {
                $value = $_REQUEST[$birchschedule->fbuilder->field->get_dom_name( $field )];
                if ( !is_email( $value ) ) {
                    $error[$birchschedule->fbuilder->field->get_dom_name( $field )] = __( 'Please input a valid email address', 'birchschedule' );
                }
            }
            return $error;
        };

    } );
