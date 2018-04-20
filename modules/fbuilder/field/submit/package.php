<?php

birch_ns( 'birchschedule.fbuilder.field.submit', function( $ns ) {

        global $birchschedule;

        $ns->init = function() use( $ns, $birchschedule ) {
            $birchschedule->fbuilder->field->get_field_title->when( $ns->is_field_type_submit, $ns->get_field_title );

            $birchschedule->fbuilder->field->render_options_editing->when( $ns->is_field_type_submit, $ns->render_options_editing );

            $birchschedule->fbuilder->field->render_field_view_frontend->when( $ns->is_field_type_submit, $ns->render_field_view_frontend );

            $birchschedule->fbuilder->field->render_field_view->when( $ns->is_field_type_submit, $ns->render_field_view );

            $birchschedule->fbuilder->field->render_field_view_builder->when( $ns->is_field_type_submit, $ns->render_field_view_builder );
        };

        $ns->is_field_type_submit = function( $field ) {
            return $field['type'] === 'submit';
        };

        $ns->get_field_title = function( $field ) {

            $title = __( 'Predefined', 'birchschedule' ) . ' - ' . $field['label'];
            return $title;
        };

        $ns->render_options_editing = function( $field ) use ( $ns, $birchschedule ) {

            $label = $field['label'];
            $input_id = $field['field_id'] . '_label';
            $labels = $field['labels'];
            $labels['forget_password'] = esc_attr( $labels['forget_password'] );
            $template = $field['confirmation']['text']['template'];
            $url = $field['confirmation']['redirect']['url'];
            $url = esc_attr( $url );
            $confirmation_type = $field['confirmation']['type'];
?>
                <li>
                    <label><?php _e( 'Labels', 'birchschedule' ); ?></label>
                    <table style="width: 100%;">
                    <tr>
                        <td><label><?php _e( 'Submit', 'birchschedule' ); ?></label></td>
                        <td><input type="text" id="<?php echo $input_id; ?>" name="birchschedule_fields_options[<?php echo $field['field_id']; ?>][label]" value="<?php echo $label; ?>"/></td>
                    </tr>
                    <tr>
                    <td><label><?php _e( 'Lost your password?', 'birchschedule' ); ?></label></td>
                    <td>
                        <input type="text" name="birchschedule_fields_options[<?php echo $field['field_id']; ?>][labels][forget_password]" value="<?php echo $labels['forget_password']; ?>"/>
                    </td>
                    </tr>
                    </table>
                </li>
                <li>
                    <label><?php _e( 'Confirmation', 'birchschedule' ); ?></label>
                    <div style="margin: 4px 0 0 0;">
                        <input type="radio" id="birchschedule_fields_options_submit_confirmation_type_text"
                            name="birchschedule_fields_options[<?php echo $field['field_id']; ?>][confirmation][type]"
                            <?php if ( $confirmation_type === 'text' ) {
                echo 'checked="checked"';
            } ?>
                            value="text" />
                        <label for="birchschedule_fields_options_submit_confirmation_type_text"><?php _e( 'Text', 'birchschedule' ); ?></label>
                        <input type="radio" id="birchschedule_fields_options_submit_confirmation_type_redirect"
                            name="birchschedule_fields_options[<?php echo $field['field_id']; ?>][confirmation][type]"
                            <?php if ( $confirmation_type === 'redirect' ) {
                echo 'checked="checked"';
            } ?>
                            value="redirect" />
                        <label for="birchschedule_fields_options_submit_confirmation_type_redirect"><?php _e( 'Redirect', 'birchschedule' ); ?>
                        </label>
                    </div>
                    <div id="birchschedule_fields_options_submit_confirmation_settings_text"
                        class="birs_confirmation_settings">
                        <textarea style="width: 100%; height: 200px;"
                            name="birchschedule_fields_options[<?php echo $field['field_id']; ?>][confirmation][text][template]"
                            ><?php echo $template; ?></textarea>
                    </div>
                    <div id="birchschedule_fields_options_submit_confirmation_settings_redirect"
                        class="birs_confirmation_settings">
                        <input type="text"
                            name="birchschedule_fields_options[<?php echo $field['field_id']; ?>][confirmation][redirect][url]"
                            value="<?php echo $url; ?>" />
                    </div>
                    <style>
                        .birs_confirmation_settings {
                            margin: 6px 0 0 0;
                        }
                    </style>
                </li>
<?php
        };

        $ns->render_field_view_frontend = function( $field, $value=false, $errors=false )
        use ( $birchschedule ) {

            $birchschedule->fbuilder->field->render_field_view( $field, $value, $errors );
        };

        $ns->get_client_forget_password_link = function() {
            return wp_lostpassword_url();
        };

        $ns->render_field_view = function( $field, $value=false, $errors=false )
        use ( $ns, $birchschedule ) {

            $fields_options = $birchschedule->fbuilder->get_fields_options();
            $client_type = $fields_options['client_section']['client_type_settings']['default_client_type'];
            if ( $client_type === 'new' ) {
                $forget_display = "display: none;";
            } else {
                $forget_display = "";
            }
            $forget_password_link = $ns->get_client_forget_password_link();
            $login_disabled = $birchschedule->fbuilder->is_login_disabled();
            $logged_in = is_user_logged_in();
?>
                <li class="birs_footer">
<?php
    if(!$logged_in && function_exists('gglcptch_display')) {
        echo gglcptch_display();
    }
?>
                    <div class="birs_error" id="birs_booking_error" style="<?php echo $birchschedule->fbuilder->field->get_error_display_style( $errors, 'birs_booking_error' ); ?>">
                        <?php echo $birchschedule->fbuilder->field->get_error_message( $errors, 'birs_booking_error' ); ?>
                    </div>
                    <div style="display:none;" id="birs_please_wait"><?php _e( 'Please wait...', 'birchschedule' ); ?></div>
                    <div>
                        <input type="button" value="<?php echo $field['label']; ?>" class="button" id="birs_book_appointment">
<?php
            if ( !$login_disabled && !$logged_in ):
?>
                        <a id="birs_client_forget_password"
                            href="<?php echo $forget_password_link; ?>"
                            style="<?php echo $forget_display; ?>"
                            target="_blank">
                            <?php echo $field['labels']['forget_password']; ?>
                        </a>
<?php
            endif;
?>
                    </div>
                </li>
<?php
        };

        $ns->render_field_view_builder = function( $field ) use ( $birchschedule ) {
?>
                <div class="birchschedule-field">
                    <ul>
                    <?php $birchschedule->fbuilder->field->render_field_view( $field ); ?>
                    </ul>
                </div>
<?php
        };

    } );
