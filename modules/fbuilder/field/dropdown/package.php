<?php

birch_ns( 'birchschedule.fbuilder.field.dropdown', function( $ns ) {

        global $birchschedule;

        $ns->init = function() use( $ns, $birchschedule ) {
            add_filter( 'birchschedule_fbuilder_field_get_default_field_config', function( $config ) {
                    $config['drop_down'] = array(
                        'category' => 'custom_fields',
                        'label' => __( 'Untitled', 'birchschedule' ),
                        'type' => 'drop_down',
                        'visibility' => 'both',
                        'required' => false,
                        'choices' => array(
                            'First Choice' => __( 'First Choice', 'birchschedule' ),
                            'Second Choice' => __( 'Second Choice', 'birchschedule' ),
                            'Third Choice' => __( 'Third Choice', 'birchschedule' )
                        )
                    );
                    return $config;
                } );

            $birchschedule->fbuilder->field->render_field_elements->when( $ns->is_field_type_drop_down, $ns->render_field_elements );

            $birchschedule->fbuilder->field->render_options_editing->when( $ns->is_field_type_drop_down, $ns->render_options_editing );

            $birchschedule->fbuilder->field->get_field_default_value->when( $ns->is_field_type_drop_down, $ns->get_field_default_value );

            $birchschedule->fbuilder->field->render_field_editing->when( $ns->is_field_type_drop_down, $ns->render_field_editing );
        };

        $ns->is_field_type_drop_down = function( $field ) {
            return $field['type'] === 'drop_down';
        };

        $ns->render_field_elements = function( $field, $value=false ) use( $ns, $birchschedule ) {

            global $birchpress;

?>
                <select name="<?php echo $birchschedule->fbuilder->field->get_dom_name( $field ); ?>" id="<?php echo $birchschedule->fbuilder->field->get_dom_name( $field ); ?>">
<?php
            $birchpress->util->render_html_options( $field['choices'],
                $value, $birchschedule->fbuilder->field->get_field_default_value( $field ) );
?>
                </select>
<?php
        };

        $ns->render_options_editing = function( $field ) use( $ns, $birchschedule ) {
            $birchschedule->fbuilder->field->selectable->render_options_editing( $field );
        };

        $ns->get_field_default_value = function( $field ) use ( $ns, $birchschedule ) {
            return $birchschedule->fbuilder->field->selectable->get_field_default_value( $field );
        };

        $ns->render_field_editing = function( $field ) use ( $ns, $birchschedule ) {
            $birchschedule->fbuilder->field->selectable->render_field_editing( $field );
        };
    } );
