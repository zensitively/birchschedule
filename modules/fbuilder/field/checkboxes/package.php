<?php

birch_ns( 'birchschedule.fbuilder.field.checkboxes', function( $ns ) {

        global $birchschedule;

        $ns->init = function() use( $ns, $birchschedule ) {
            add_filter( 'birchschedule_fbuilder_field_get_default_field_config', function( $config ) {
                    $config['checkboxes'] =array(
                        'category' => 'custom_fields',
                        'label' => __( 'Untitled', 'birchschedule' ),
                        'type' => 'checkboxes',
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

            $birchschedule->fbuilder->field->render_field_label->when( $ns->is_field_type_checkboxes, $ns->render_field_label );

            $birchschedule->fbuilder->field->get_field_default_value->when( $ns->is_field_type_checkboxes, $ns->get_field_default_value );

            $birchschedule->fbuilder->field->render_field_elements->when( $ns->is_field_type_checkboxes, $ns->render_field_elements );

            $birchschedule->fbuilder->field->selectable->render_choice_edit_items->when( $ns->is_field_type_checkboxes, $ns->render_choice_edit_items );

            $birchschedule->fbuilder->field->selectable->render_choice_edit_box->when( $ns->is_field_type_checkboxes, $ns->render_choice_edit_box );

            $birchschedule->fbuilder->field->render_options_editing->when( $ns->is_field_type_checkboxes, $ns->render_options_editing );

            $birchschedule->fbuilder->field->render_field_editing->when( $ns->is_field_type_checkboxes, $ns->render_field_editing );
        };

        $ns->is_field_type_checkboxes = function( $field ) {
            return $field['type'] === 'checkboxes';
        };

        $ns->render_field_label = function( $field ) {
?>
                <label><?php echo $field['label'] ?></label>
<?php
        };

        $ns->get_field_default_value = function( $field ) {

            $default_value = array();
            if ( isset( $field['default_value'] ) ) {
                $default_value = $field['default_value'];
            }
            if ( !is_array( $default_value ) ) {
                $default_value = array();
            }
            return $default_value;
        };

        $ns->render_field_elements = function( $field, $value = false ) use( $ns, $birchschedule ) {

            $default_value = $birchschedule->fbuilder->field->get_field_default_value( $field );
            if ( !is_array( $value ) ) {
                $value = $default_value;
            }
?>
                <ul class="birchschedule-radio-buttons">
<?php
            $index = 0;
            foreach ( $field['choices'] as $choice_value => $choice_text ):
            if ( in_array( $choice_value, $value ) ) {
                $checked = " checked='checked' ";
            } else {
                $checked = '';
            }
            $id = $birchschedule->fbuilder->field->get_dom_name( $field ) . '_' . $index++;
            $choice_value = esc_attr( $choice_value );
?>
                <li>
                    <input type="checkbox" name="<?php echo $birchschedule->fbuilder->field->get_dom_name( $field ) . '[]'; ?>" id="<?php echo $id; ?>" value="<?php echo $choice_value; ?>" <?php echo $checked; ?> />
                    <label for="<?php echo $id; ?>"><?php echo $choice_text; ?></label>
                </li>
<?php
            endforeach;
?>
                </ul>
<?php
        };

        $ns->render_options_editing = function( $field ) use( $ns, $birchschedule ) {
            $birchschedule->fbuilder->field->selectable->render_options_editing( $field );
        };

        $ns->render_field_editing = function( $field ) use ( $ns, $birchschedule ) {
            $birchschedule->fbuilder->field->selectable->render_field_editing( $field );
        };

        $ns->render_choice_edit_items = function( $field ) use( $ns, $birchschedule ) {

            $default_value = $birchschedule->fbuilder->field->get_field_default_value( $field );
            foreach ( $field['choices'] as $choice_value => $choice_text ) {
                if ( in_array( $choice_value, $default_value ) ) {
                    $checked = " checked='checked' ";
                } else {
                    $checked = '';
                }
                $birchschedule->fbuilder->field->selectable->render_choice_edit_item( $field, $choice_value, $choice_text, $checked );
            }
        };

        $ns->render_choice_edit_box = function( $field, $choice_value, $checked ) {
?>
                <input type="checkbox" name="birchschedule_fields_options[<?php echo $field['field_id']; ?>][default_value][]" value="<?php echo $choice_value; ?>" <?php echo $checked; ?>/>
<?php
        };

    } );
