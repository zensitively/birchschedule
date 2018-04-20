<?php

birch_ns( 'birchschedule.fbuilder.field.address', function( $ns ) {

        global $birchschedule;

        $ns->init = function() use ( $ns, $birchschedule ) {
            $birchschedule->fbuilder->field->render_field_label->when( $ns->is_field_type_address, $ns->render_field_label );

            $birchschedule->fbuilder->field->render_field_elements->when( $ns->is_field_type_address, $ns->render_field_elements );

            $birchschedule->fbuilder->field->render_field_hidden->when( $ns->is_field_type_address, $ns->render_field_hidden );

            $birchschedule->fbuilder->field->validate->when( $ns->is_field_type_address, $ns->validate );

            $birchschedule->fbuilder->field->get_meta_field_name->when( $ns->is_field_type_address, $ns->get_meta_field_name );

            $birchschedule->fbuilder->field->get_field_merge_tag->when( $ns->is_field_type_address, $ns->get_field_merge_tag );
        };

        $ns->is_field_type_address = function( $field ) {
            return $field['type'] === 'address';
        };

        $ns->render_field_label = function( $field ) use( $ns, $birchschedule ) {
?>
                <label for="<?php echo $birchschedule->fbuilder->field->get_dom_name( $field ) . '1'; ?>"><?php echo $field['label'] ?></label>
<?php
        };

        $ns->render_field_elements = function( $field, $value = false ) use ( $ns, $birchschedule ) {

            if ( $value === false ) {
                $value = array(
                    '_birs_client_address1' => '',
                    '_birs_client_address2' => ''
                );
            }
            $address1_value = esc_attr( $value['_birs_client_address1'] );
            $address2_value = esc_attr( $value['_birs_client_address2'] );
?>
                <input type='text' name="<?php echo $birchschedule->fbuilder->field->get_dom_name( $field ) . '1'; ?>" id="<?php echo $birchschedule->fbuilder->field->get_dom_name( $field ) . '1' ?>" style="display: block;" value="<?php echo $address1_value; ?>"/>
                <input type="text" name="<?php echo $birchschedule->fbuilder->field->get_dom_name( $field ) . '2'; ?>" id="<?php echo $birchschedule->fbuilder->field->get_dom_name( $field ) . '2'; ?>" value="<?php echo $address2_value; ?>" />
<?php
        };

        $ns->render_field_hidden = function( $field ) {
?>
                <input type="hidden" name="birs_client_fields[]" value="_birs_client_address1" />
                <input type="hidden" name="birs_client_fields[]" value="_birs_client_address2" />
<?php
        };

        $ns->validate = function( $field ) use( $ns, $birchschedule ) {
            $error = array();
            if ( $field['required'] ) {
                $address1 = $birchschedule->fbuilder->field->get_dom_name( $field ) . '1';
                $address2 = $birchschedule->fbuilder->field->get_dom_name( $field ) . '2';
                if ( ( !isset( $_REQUEST[$address1] ) || !$_REQUEST[$address1] ) && ( !isset( $_REQUEST[$address2] ) || !$_REQUEST[$address2] ) ) {
                    $error[$birchschedule->fbuilder->field->get_dom_name( $field )] = __( 'This field is required', 'birchschedule' );
                }
            }
            return $error;
        };

        $ns->get_meta_field_name = function( $field ) {
            return array( '_birs_client_address1', '_birs_client_address2' );
        };

        $ns->get_field_merge_tag = function( $field ) {
            return '{client_address1}, {client_address2}';
        };

    } );
