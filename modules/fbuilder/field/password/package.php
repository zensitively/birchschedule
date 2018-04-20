<?php

birch_ns( 'birchschedule.fbuilder.field.password', function( $ns ) {

        global $birchschedule;

        $ns->init = function() use( $ns, $birchschedule ) {
            $birchschedule->fbuilder->field->get_field_title->when( $ns->is_field_type_password, $ns->get_field_title );

            $birchschedule->fbuilder->field->render_field_view_frontend->when( $ns->is_field_type_password, $ns->render_field_view_frontend );

            $birchschedule->fbuilder->field->render_options_editing->when( $ns->is_field_type_password, $ns->render_options_editing );

            $birchschedule->fbuilder->field->render_field_view->when( $ns->is_field_type_password, $ns->render_field_view );

            $birchschedule->fbuilder->field->validate->when( $ns->is_field_type_password, $ns->validate );

            $birchschedule->fbuilder->field->render_field_view_builder->when( $ns->is_field_type_password, $ns->render_field_view_builder );

        };

        $ns->is_field_type_password = function( $field ) {
            return $field['type'] === 'password';
        };

        $ns->get_field_title = function( $field ) {

            $title = __( 'Predefined', 'birchschedule' ) . ' - ' . $field['label'];
            return $title;
        };

        $ns->render_field_view_frontend = function( $field, $value=false, $errors=false ) use( $birchschedule ) {

            $login_disabled = $birchschedule->fbuilder->is_login_disabled();
            if ( !$login_disabled ) {
                $birchschedule->fbuilder->field->render_field_view( $field, $value, $errors );
            }
        };

        $ns->render_options_editing = function( $field ) use( $birchschedule ) {

            $label = $field['label'];
            $input_id = $field['field_id'] . '_label';
            $labels = $field['labels'];
            $labels['retype_password'] = esc_attr( $labels['retype_password'] );
?>
                <li>
                    <label><?php _e( 'Labels', 'birchschedule' ); ?></label>
                    <table style="width: 100%;">
                    <tr>
                        <td><label><?php _e( 'Password', 'birchschedule' ); ?></label></td>
                        <td><input type="text" id="<?php echo $input_id; ?>" name="birchschedule_fields_options[<?php echo $field['field_id']; ?>][label]" value="<?php echo $label; ?>"/></td>
                    </tr>
                    <tr>
                    <td><label><?php _e( 'Retype Password', 'birchschedule' ); ?></label></td>
                    <td>
                        <input type="text" name="birchschedule_fields_options[<?php echo $field['field_id']; ?>][labels][retype_password]" value="<?php echo $labels['retype_password']; ?>"/>
                    </td>
                    </tr>
                    </table>
                </li>
<?php
        };

        $ns->render_field_view = function( $field, $value=false, $errors=false ) use ( $ns, $birchschedule ) {

            $fields_options = $birchschedule->fbuilder->get_fields_options();
            $client_type = $fields_options['client_section']['client_type_settings']['default_client_type'];
            if ( $client_type === 'new' ) {
                $retype_display = "";
            } else {
                $retype_display = "display: none;";
            }
?>
                <li class="birs_form_field birs_client_password" data-shown-client-type="new returning">
                    <label for="birs_client_password"><?php echo $field['label'] ?></label>
                    <div class="birs_field_content">
                        <input type="password" name="birs_client_password" id="birs_client_password" />
                        <?php $birchschedule->fbuilder->field->render_field_hidden( $field ); ?>
                        <?php $birchschedule->fbuilder->field->render_field_error( $field, $value, $errors ); ?>
                    </div>
                </li>
                <li class="birs_form_field birs_client_password_retype" style="<?php echo $retype_display; ?>" data-shown-client-type="new">
                    <label for="birs_client_password_retype"><?php echo $field['labels']['retype_password'] ?></label>
                    <div class="birs_field_content">
                        <input type="password" name="birs_client_password_retype" id="birs_client_password_retype" />
                        <div class="birs_error" id="birs_client_password_retype_error"></div>
                    </div>
                </li>
<?php
        };

        $ns->validate = function( $field ) use( $birchschedule ) {

            $error = array();
            $login_disabled = $birchschedule->fbuilder->is_login_disabled();
            if ( $login_disabled ) {
                return $error;
            }
            $client_type = $birchschedule->fbuilder->get_client_type();
            if ( $client_type === 'new' ) {
                if ( !isset( $_REQUEST['birs_client_password'] ) && !isset( $_REQUEST['birs_client_password_retype'] ) ) {
                    $error['birs_client_password'] = __( 'Password is required', 'birchschedule' );
                } else
                    if ( isset( $_REQUEST['birs_client_password'] ) && isset( $_REQUEST['birs_client_password_retype'] ) &&
                        $_REQUEST['birs_client_password'] === $_REQUEST['birs_client_password_retype'] ) {
                    if ( strlen( $_REQUEST['birs_client_password'] ) < 6 ) {
                        $error['birs_client_password'] = __( 'Password must be at least 6 digits.', 'birchschedule' );
                    }
                } else {
                    $error['birs_client_password_retype'] = __( 'Both passwords should match', 'birchschedule' );
                }
            }
            return $error;
        };

        $ns->render_field_view_builder = function( $field ) use ( $birchschedule ) {
?>
                <div class="birchschedule-field">
                    <ul>
<?php
            $birchschedule->fbuilder->field->render_field_view( $field );
?>
                    </ul>
                </div>
<?php
        };

    } );
