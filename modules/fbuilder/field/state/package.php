<?php

birch_ns( 'birchschedule.fbuilder.field.state', function( $ns ) {

        global $birchschedule;

        $ns->init = function() use( $ns, $birchschedule ) {
            $birchschedule->fbuilder->field->render_field_elements->when( $ns->is_field_type_state_province, $ns->render_field_elements );

            $birchschedule->fbuilder->field->render_options_editing->when( $ns->is_field_type_state_province, $ns->render_options_editing );

            $birchschedule->fbuilder->field->get_value_field_name->when( $ns->is_field_type_state_province, $ns->get_value_field_name );
        };

        $ns->is_field_type_state_province = function( $field ) {
            return $field['type'] === 'state_province';
        };

        $ns->render_field_elements = function( $field, $value = false ) use ( $birchschedule ) {
            global $birchpress;

            if ( $value ) {
                if ( isset( $value['_birs_client_country'] ) && $value['_birs_client_country'] ) {
                    $country = $value['_birs_client_country'];
                } else {
                    $country = $birchschedule->model->get_default_country();
                }
                if ( isset( $value['_birs_client_state'] ) && $value['_birs_client_state'] ) {
                    $state = $value['_birs_client_state'];
                } else {
                    $state = $birchschedule->model->get_default_state();
                }
            } else {
                $country = $birchschedule->model->get_default_country();
                $state = $birchschedule->model->get_default_state();
            }
            $states = $birchpress->util->get_states();
            if ( isset( $states[$country] ) ) {
                $select_display = "";
                $text_display = "display:none;";
            } else {
                $select_display = "display:none;";
                $text_display = "";
            }
?>
                <select name="birs_client_state_select" id ="birs_client_state_select" style="<?php echo $select_display; ?>">
<?php
            if ( isset( $states[$country] ) ) {
                $birchpress->util->render_html_options( $states[$country], $state );
            }
?>
                </select>
                <input type="text" name="birs_client_state" id="birs_client_state" value="<?php echo esc_attr( $state ); ?>" style="<?php echo $text_display; ?>" />
<?php
        };

        $ns->render_options_editing = function( $field ) use ( $birchschedule ) {

            global $birchpress;

            $birchschedule->fbuilder->field->render_options_editing->call_default( $field );
            $all_states = $birchpress->util->get_states();
            $fields_options = $birchschedule->fbuilder->get_fields_options();
            $default_country = $fields_options['client_country']['default_value'];
            if ( !isset( $all_states[$default_country] ) ) {
                return;
            }
            $states = $all_states[$default_country];
            $state_options = $fields_options['client_state'];
            if ( isset( $state_options['default_value'] ) ) {
                $default_state = $state_options['default_value'];
            } else {
                $default_state = false;
            }
?>
                <li>
                    <label><?php _e( 'Default State/Province', 'birchschedule' ); ?></label>
                    <div>
                        <select name="birchschedule_fields_options[client_state][default_value]">
                            <?php $birchpress->util->render_html_options( $states, $default_state ); ?>
                        </select>
                    </div>
                </li>
<?php
        };

        $ns->get_value_field_name = function() {
            return array( '_birs_client_country', '_birs_client_state' );
        };

    } );
