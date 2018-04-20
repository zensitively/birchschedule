<?php

birch_ns( 'birchschedule.fbuilder.field.paragraph', function( $ns ) {

        global $birchschedule;

        $ns->init = function() use( $ns, $birchschedule ) {
            add_filter( 'birchschedule_fbuilder_field_get_default_field_config', function( $config ) {
                    $config['paragraph_text'] = array(
                        'category' => 'custom_fields',
                        'label' => __( 'Untitled', 'birchschedule' ),
                        'type' => 'paragraph_text',
                        'visibility' => 'both',
                        'required' => false
                    );
                    return $config;
                } );

            $birchschedule->fbuilder->field->render_field_elements->when( $ns->is_field_type_paragraph_text, $ns->render_field_elements );

            $birchschedule->fbuilder->field->get_field_content_class->when( $ns->is_field_type_paragraph_text, $ns->get_field_content_class );

        };

        $ns->is_field_type_paragraph_text = function( $field ) {
            return $field['type'] === 'paragraph_text';
        };

        $ns->render_field_elements = function( $field, $value=false ) use ( $birchschedule ) {

?>
                <textarea name="<?php echo $birchschedule->fbuilder->field->get_dom_name( $field ); ?>" id="<?php echo $birchschedule->fbuilder->field->get_dom_name( $field ); ?>"><?php echo $value; ?></textarea>
<?php
        };

        $ns->get_field_content_class = function() {

            return 'birs_field_content birs_field_paragraph';
        };

    } );
