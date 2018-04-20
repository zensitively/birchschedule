<?php

birch_ns( 'birchschedule.fbuilder.field.clientsection', function( $ns ) {

        global $birchschedule;

        $ns->init = function() use( $ns, $birchschedule ) {
            $birchschedule->fbuilder->field->get_field_title->when( $ns->is_field_type_client_section, $ns->get_field_title );

            $birchschedule->fbuilder->field->render_field_view_frontend->when( $ns->is_field_type_client_section, $ns->render_field_view_frontend );

            $birchschedule->fbuilder->field->render_field_view_builder->when( $ns->is_field_type_client_section, $ns->render_field_view_builder );

            $birchschedule->fbuilder->field->render_options_editing->when( $ns->is_field_type_client_section, $ns->render_options_editing );

            $birchschedule->fbuilder->field->get_attr_data_shown_client_type->when( $ns->is_field_type_client_section, $ns->get_attr_data_shown_client_type );

            $birchschedule->fbuilder->field->render_field_view->when( $ns->is_field_type_client_section, $ns->render_field_view );
        };

        $ns->is_field_type_client_section = function( $field ) {
            return $field['type'] === 'client_section';
        };

        $ns->get_field_title = function( $field ) {

            $title = __( 'Predefined', 'birchschedule' ) . ' - ' . $field['label'];
            return $title;
        };

        $ns->get_attr_data_shown_client_type = function( $field ) {
            return "";
        };

        $ns->render_field_view = function( $field, $value = false ) use ( $birchschedule ) {
            $birchschedule->fbuilder->field->section->render_field_view( $field, $value );
        };

        $ns->render_field_view_frontend = function( $field, $value=false, $errors=false ) use ( $ns, $birchschedule ) {

            $login_disabled = $birchschedule->fbuilder->is_login_disabled();
            $logged_in = is_user_logged_in();
            if ( !$login_disabled && $logged_in ) {
                $ns->render_logged_in_info( $field );
            } else {
                $birchschedule->fbuilder->field->render_field_view_frontend->call_default( $field, $value, $errors );
                $register_disabled = $birchschedule->fbuilder->is_register_disabled();
                if ( $login_disabled || $register_disabled ) {
                    $hidden_style = "display:none;";
                } else {
                    $hidden_style = "";
                }
?>
            <li class="birs_form_field birs_client_type" style="<?php echo $hidden_style; ?>">
            <?php $ns->render_client_type( $field, $value ); ?>
            </li>
            <?php
            }
        };

        $ns->get_client_logout_link = function() {
            global $birchpress;

            $current_page_url = $birchpress->util->current_page_url();
            return wp_logout_url( $current_page_url );
        };

        $ns->render_logged_in_info = function() use( $ns, $birchschedule ) {
            global $birchpress;

            $user = wp_get_current_user();
            $user_fullname = $user->user_firstname . ' ' . $user->user_lastname;
            $logout_url = $ns->get_client_logout_link();
?>
        <li class="birs_form_field">
        <?php
            printf( __( "Logged in as %s.", 'birchschedule' ), $user_fullname );
?>
            <a id="birs_client_logout"
                href="<?php echo $logout_url; ?>">
                <?php echo __( 'Log out?', 'birchschedule' ); ?>
            </a>
        </li>
        <?php
        };

        $ns->render_client_type = function( $field, $value ) use ( $ns, $birchschedule ) {

            $labels = $field['client_type_settings']['labels'];
            $new_user_checked = ' checked="checked" ';
            $returning_user_checked = '';
            $login_disabled = $birchschedule->fbuilder->is_login_disabled();
            $register_disabled = $birchschedule->fbuilder->is_register_disabled();
            if ( !$login_disabled && $register_disabled ) {
                $new_user_checked = '';
                $returning_user_checked = ' checked="checked" ';
            }
?>
        <label><?php echo $labels['new_or_returning']; ?></label>
        <div class="birs_field_content">
            <ul class="birchschedule-radio-buttons">
                <li>
                    <input type="radio" id="birs_client_type_new" name="birs_client_type" <?php echo $new_user_checked; ?> value="new">
                    <label for="birs_client_type_new"><?php echo $labels['new_user']; ?></label>
                </li>
                <li>
                    <input type="radio" id="birs_client_type_returning" name="birs_client_type" <?php echo $returning_user_checked; ?> value="returning">
                    <label for="birs_client_type_returning"><?php echo $labels['returning_user']; ?></label>
                </li>
            </ul>
        </div>
        <?php
        };

        $ns->render_field_view_builder = function( $field ) use ( $ns, $birchschedule ) {
?>
        <div class="birchschedule-field">
            <?php
            $birchschedule->fbuilder->field->render_field_view( $field );
            $login_disabled = $birchschedule->fbuilder->is_login_disabled();
            $register_disabled = $birchschedule->fbuilder->is_register_disabled();
            if ( !$login_disabled && !$register_disabled ) {
                $ns->render_client_type( $field, 'new' );
            }
?>
        </div>
        <?php
        };

        $ns->render_options_editing = function( $field ) use( $ns, $birchschedule ) {

            $label = $field['label'];
            $input_id = $field['field_id'] . '_label';
            $labels = $field['client_type_settings']['labels'];
            $labels['new_or_returning'] = esc_attr( $labels['new_or_returning'] );
            $labels['new_user'] = esc_attr( $labels['new_user'] );
            $labels['returning_user'] = esc_attr( $labels['returning_user'] );
            $disable_login = $birchschedule->fbuilder->is_login_disabled();
            $disable_register = $birchschedule->fbuilder->is_register_disabled();
            if ( $disable_login ) {
                $enable_login_checked = "";
            } else {
                $enable_login_checked = " checked='checked' ";
            }
            if ( $disable_register ) {
                $enable_register_checked = "";
            } else {
                $enable_register_checked = " checked='checked' ";
            }
?>
        <li>
            <label><?php _e( 'Labels', 'birchschedule' ); ?></label>
            <table style="width: 100%;">
            <tr>
                <td style="width:40%;"><label><?php _e( 'Your Info', 'birchschedule' ); ?></label></td>
                <td>
                    <input type="text" id="<?php echo $input_id; ?>" name="birchschedule_fields_options[<?php echo $field['field_id']; ?>][label]" value="<?php echo $label; ?>"/>
                </td>
            </tr>
            <tr>
                <td><label><?php _e( 'Are you a new or returning user?', 'birchschedule' ); ?></td>
                <td>
                    <input type="text" name="birchschedule_fields_options[<?php echo $field['field_id']; ?>][client_type_settings][labels][new_or_returning]" value="<?php echo $labels['new_or_returning']; ?>"/>
                </td>
            </tr>
            <tr>
                <td><label><?php _e( 'I am a new user.', 'birchschedule' ); ?></td>
                <td>
                    <input type="text" name="birchschedule_fields_options[<?php echo $field['field_id']; ?>][client_type_settings][labels][new_user]" value="<?php echo $labels['new_user']; ?>"/>
                </td>
            </tr>
            <tr>
                <td><label><?php _e( 'I am a returning user.', 'birchschedule' ); ?></td>
                <td>
                    <input type="text" name="birchschedule_fields_options[<?php echo $field['field_id']; ?>][client_type_settings][labels][returning_user]" value="<?php echo $labels['returning_user']; ?>"/>
                </td>
            </tr>
            </table>
            <label><?php _e( 'Settings', 'birchschedule' ); ?></label>
            <input type="hidden" name="birchschedule_fields_options[<?php echo $field['field_id']; ?>][client_type_settings][default_client_type]" value="new" />
            <table>
            <tr>
                <td>
                    <input type="hidden" id="birchschedule_disable_login_hidden" name="birchschedule_fields_options[<?php echo $field['field_id']; ?>][client_type_settings][disable_login]" value="<?php echo $disable_login; ?>" />
                    <input type="checkbox" id="birchschedule_enable_login_checkbox" <?php echo $enable_login_checked; ?> />
                    <label><?php _e( 'Enable user login', 'birchschedule' ); ?></label>
                </td>
            </tr>
            <tr id="birchschedule_disable_register_tr">
                <td>
                    <input type="hidden" id="birchschedule_disable_register_hidden" name="birchschedule_fields_options[<?php echo $field['field_id']; ?>][client_type_settings][disable_register]" value="<?php echo $disable_register; ?>" />
                    <input type="checkbox" id="birchschedule_enable_register_checkbox" <?php echo $enable_register_checked; ?> />
                    <label><?php _e( 'Enable new user registration', 'birchschedule' ); ?></label>
                </td>
            </tr>
            </table>
            <script type="text/javascript">
                //<![CDATA[
                jQuery(document).ready( function($) {
                    var showDisableRegister = function() {
                        if($('#birchschedule_enable_login_checkbox').is(':checked')) {
                            $('#birchschedule_disable_register_tr').show();
                            $('#birchschedule_disable_login_hidden').val('');
                        } else {
                            $('#birchschedule_disable_register_tr').hide();
                            $('#birchschedule_disable_login_hidden').val('on');
                        }
                    }
                    var setDisableRegisterVal = function() {
                        if($('#birchschedule_enable_register_checkbox').is(':checked')) {
                            $('#birchschedule_disable_register_hidden').val('');
                        } else {
                            $('#birchschedule_disable_register_hidden').val('on');
                        }
                    };
                    $('#birchschedule_enable_login_checkbox').change(function(){
                        showDisableRegister();
                    });
                    $('#birchschedule_enable_register_checkbox').change(function(){
                        setDisableRegisterVal();
                    });
                    showDisableRegister();
                });
                //]]>
            </script>
        </li>
        <?php
        };

    } );
