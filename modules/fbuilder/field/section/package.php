<?php

birch_ns( 'birchschedule.fbuilder.field.section', function( $ns ) {

        global $birchschedule;

        $ns->init = function() use( $ns, $birchschedule ) {
            add_filter( 'birchschedule_fbuilder_field_get_default_field_config', function( $config ) {
                    $config['section_break'] =array(
                        'category' => 'custom_fields',
                        'label' => __( 'Untitled', 'birchschedule' ),
                        'type' => 'section_break',
                        'belong_to' => 'none',
                        'visibility' => 'frontend',
                        'required' => false
                    );
                    return $config;
                } );

            $birchschedule->fbuilder->field->render_options_editing->when( $ns->is_field_type_section_break, $ns->render_options_editing );

            $birchschedule->fbuilder->field->render_field_view->when( $ns->is_field_type_section_break, $ns->render_field_view );
        };


        $ns->is_field_type_section_break = function( $field ) {
            return $field['type'] === 'section_break';
        };

        $ns->render_options_editing = function( $field ) use( $birchschedule ) {

            $birchschedule->fbuilder->field->render_option_label( $field );
        };

        $ns->render_field_view = function( $field, $value = false ) {

?>
                <h2 class="birs_section"><?php echo $field['label'] ?></h2>
<?php
        };

    } );
