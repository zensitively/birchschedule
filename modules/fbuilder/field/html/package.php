<?php

birch_ns( 'birchschedule.fbuilder.field.html', function( $ns ) {

        global $birchschedule;

        $ns->init = function() use( $ns, $birchschedule ) {
            add_filter( 'birchschedule_fbuilder_field_get_default_field_config', function( $config ) {
                    $config['html'] = array(
                        'category' => 'custom_fields',
                        'label' => __( 'HTML Block', 'birchschedule' ),
                        'type' => 'html',
                        'visibility' => 'both',
                        'required' => false
                    );
                    return $config;
                } );

            $birchschedule->fbuilder->field->render_field_view->when( $ns->is_field_type_html, $ns->render_field_view );

            $birchschedule->fbuilder->field->render_options_editing->when( $ns->is_field_type_html, $ns->render_options_editing );
        };

        $ns->is_field_type_html = function( $field ) {
            return $field['type'] === 'html';
        };

        $ns->render_field_view = function( $field, $value = false ) use( $birchschedule ) {
            $content = empty( $field['content'] ) ? '' : $field['content'];
            echo $content;
        };


        $ns->render_options_editing = function( $field ) use( $ns, $birchschedule ) {
            $birchschedule->fbuilder->field->render_option_label( $field );
            $ns->render_html_editor( $field );
        };

        $ns->render_html_editor = function( $field ) {
            $content = empty( $field['content'] ) ? '' : $field['content'];
?>
                <li>
                    <label><?php _e( 'Content', 'birchschedule' ); ?></label>
                    <div style="margin: 4px 0 0 0;">
                        <textarea style="width: 100%; height: 200px;"
                            name="birchschedule_fields_options[<?php echo $field['field_id']; ?>][content]"
                            ><?php echo $content; ?></textarea>
                    </div>
                </li>
<?php
        };
    } );
