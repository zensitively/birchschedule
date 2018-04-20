<?php

birch_ns( 'birchschedule.fbuilder.field.singleline', function( $ns ) {

        global $birchschedule;

        $ns->init = function() use( $ns, $birchschedule ) {
            add_filter( 'birchschedule_fbuilder_field_get_default_field_config', function( $config ) {
                    $config['single_line_text'] = array(
                        'category' => 'custom_fields',
                        'label' => __( 'Untitled', 'birchschedule' ),
                        'type' => 'single_line_text',
                        'visibility' => 'both',
                        'required' => false
                    );
                    return $config;
                } );

            $birchschedule->fbuilder->field->render_field_elements->when( $ns->is_field_type_single_line_text, $ns->render_field_elements );
        };

        $ns->is_field_type_single_line_text = function( $field ) {
            return $field['type'] === 'single_line_text';
        };

        $ns->render_field_elements = function( $field, $value = false ) use( $birchschedule ) {

            if ( $value === false ) {
                $value = "";
            }
            $value = esc_attr( $value );
?>
        <input type="text" name="<?php echo $birchschedule->fbuilder->field->get_dom_name( $field ); ?>" id="<?php echo $birchschedule->fbuilder->field->get_dom_name( $field ); ?>" value="<?php echo $value; ?>"/>
<?php
        };

    } );
