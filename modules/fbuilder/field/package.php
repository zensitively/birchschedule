<?php

birch_ns( 'birchschedule.fbuilder.field', function( $ns ) {

        $_ns_data = new stdClass();

        $ns->get_default_field_config = function() {
            return array();
        };

        $ns->new_field = function( $type ) use( $ns ) {
            global $birchschedule;

            $form_options = $birchschedule->fbuilder->get_form_options();
            $new_field_id = $form_options['next_field_id'];
            $new_field_name = 'field_' . $new_field_id;
            $default_field_config = $ns->get_default_field_config();
            $field = $default_field_config[$type];
            if ( !isset( $field['belong_to'] ) ) {
                $field['belong_to'] = "client";
            }
            $field['field_id'] = $new_field_name;
            $form_options['fields'][$new_field_name] = $field;
            $form_options['next_field_id'] = ++$new_field_id;
            $field_order = $birchschedule->fbuilder->get_field_order();
            $insert_pos = array_search( 'submit', $field_order );
            array_splice( $field_order, $insert_pos, 0, $new_field_name );
            $form_options['field_order'] = $field_order;
            update_option( 'birchschedule_options_form', $form_options );
            return $field;
        };

        $ns->delete_field = function( $field_id ) use( $ns ) {
            global $birchschedule;

            $form_options = $birchschedule->fbuilder->get_form_options();
            unset( $form_options['fields'][$field_id] );
            if ( isset( $form_options['field_order'] ) ) {
                $key = array_search( $field_id, $form_options['field_order'] );
                if ( $key !== false && $key !== null ) {
                    array_splice( $form_options['field_order'], $key, 1 );
                }
            }
            update_option( 'birchschedule_options_form', $form_options );
        };

        $ns->is_editing = function( $field ) {
            return isset( $_GET['action'] ) && $_GET['action'] === 'edit'
            && isset( $_GET['field'] ) && $_GET['field'] === $field['field_id'];
        };

        $ns->_render_field = function( $post, $metabox ) use( $ns ) {
            birch_assert( isset( $metabox['args']['field'] ) );
            $field = $metabox['args']['field'];
            $ns->render_field( $field );
        };

        $ns->render_field = function( $field ) use( $ns ) {
            if ( $ns->is_editing( $field ) ) {
                $ns->render_field_editing( $field );
            } else {
                $ns->render_field_view_builder( $field );
            }
        };

        $ns->get_dom_name = function( $field ) {
            return 'birs_' . $field['field_id'];
        };

        $ns->get_dom_error_id = function( $field ) use( $ns ) {
            return $ns->get_dom_name( $field ) . '_error';
        };

        $ns->get_meta_field_name = function( $field ) {
            return '_birs_' . $field['field_id'];
        };

        $ns->get_value_field_name = function( $field ) use( $ns ) {
            return $ns->get_meta_field_name( $field );
        };

        $ns->render_field_label = function( $field ) use( $ns ) {
            $label = $field['label'];
?>
            <label for="<?php echo $ns->get_dom_name( $field ); ?>">
                <?php echo $label; ?>
            </label>
<?php
        };

        $ns->get_error_display_style = function( $errors, $error_id ) {
            if ( $errors && isset( $errors[$error_id] ) ) {
                return "display:block;";
            } else {
                return "";
            }
        };

        $ns->get_error_message = function( $errors, $error_id ) {
            if ( $errors && isset( $errors[$error_id] ) ) {
                return $errors[$error_id];
            } else {
                return "";
            }
        };

        $ns->render_field_error = function( $field, $errors=false ) use( $ns ) {
            $error_dom_id = $ns->get_dom_error_id( $field );
            $error_id = $ns->get_dom_name( $field );
            $style = $ns->get_error_display_style( $errors, $error_id );
?>
                    <div class="birs_error" id="<?php echo $error_dom_id ?>" style="<?php echo $style; ?>">
                    <?php echo $ns->get_error_message( $errors, $error_id ); ?>
                    </div>
<?php
        };

        $ns->render_field_hidden = function( $field ) use( $ns ) {
            if ( $field['belong_to'] === 'client' ) {
                $name = 'birs_client_fields[]';
            } else {
                $name = 'birs_appointment_fields[]';
            }
?>
                    <input type="hidden" name="<?php echo $name; ?>" value="<?php echo $ns->get_meta_field_name( $field ); ?>"/>
<?php
        };

        $ns->render_field_view = function( $field, $value=false ) use( $ns ) {
            $ns->render_field_label( $field );
            $field_content_class = $ns->get_field_content_class( $field );
?>
                    <div class="<?php echo $field_content_class; ?>">
                        <?php $ns->render_field_elements( $field, $value ); ?>
                        <?php $ns->render_field_hidden( $field ); ?>
                    </div>
<?php
        };

        $ns->get_field_content_class = function( $field ) use( $ns ) {
            return 'birs_field_content';
        };

        $ns->get_attr_data_shown_client_type = function( $field ) {
            if ( $field['belong_to'] === 'client' ) {
                return "data-shown-client-type='new'";
            } else {
                return "";
            }
        };

        $ns->render_field_view_frontend = function( $field, $value=false, $errors=false ) use( $ns ) {
            $shown_client_type = $ns->get_attr_data_shown_client_type( $field );
            $field_class = "birs_" . $field['field_id'];
?>
                    <li class="birs_form_field <?php echo $field_class; ?>" <?php echo $shown_client_type; ?>>
<?php
            $ns->render_field_view( $field, $value );
            $ns->render_field_error( $field, $errors );
?>
                    </li>
<?php
        };

        $ns->render_field_elements = function( $field, $value=false ) {};

        $ns->render_field_view_builder = function( $field ) use( $ns ) {
?>
                <div class="birchschedule-field">
                    <?php $ns->render_field_view( $field ); ?>
                </div>
<?php
        };

        $ns->render_field_editing_general = function( $field ) use( $ns ) {
?>
                <div class="birchschedule-field-edit">
                    <ul>
<?php
            $ns->render_options_editing( $field );
            $ns->render_field_save( $field );
?>
                    </ul>
                </div>
                <script type="text/javascript">
                    //<![CDATA[
                    jQuery(document).ready( function($) {
                        birchschedule.fbuilder.editing_field_box_id = '<?php echo $ns->get_field_box_id( $field ); ?>';
                    });
                    //]]>
                </script>
<?php
        };

        $ns->render_field_editing = function( $field ) use( $ns ) {
            $ns->render_field_editing_general( $field );
        };

        $ns->render_options_editing = function( $field ) use( $ns ) {
            $ns->render_option_label( $field );
            $ns->render_option_required( $field );
            $ns->render_option_visibility( $field );
        };

        $ns->render_option_label = function( $field ) use( $ns ) {
            $label = esc_attr( $field['label'] );
            $input_id = $field['field_id'] . '_label';
?>
                <li>
                    <label for="<?php echo $input_id; ?>"><?php _e( 'Field Label', 'birchschedule' ); ?></label>
                    <div>
                        <input type="text" id="<?php echo $input_id; ?>" name="birchschedule_fields_options[<?php echo $field['field_id']; ?>][label]" value="<?php echo $label; ?>"/>
                    </div>
                </li>
<?php
        };

        $ns->render_option_required = function( $field ) use( $ns ) {
            $required = $field['required'];
            $checked = $required ? 'checked="checked"' : '';
            $checkbox_id = $field['field_id'] . '_required';
?>
                <li>
                    <div>
                        <input type="checkbox" id="<?php echo $checkbox_id; ?>" name="birchschedule_fields_options[<?php echo $field['field_id']; ?>][required]" <?php echo $checked; ?>/>
                        <label for="<?php echo $checkbox_id; ?>">
                            <?php _e( 'The field is mandatory', 'birchschedule' ); ?>
                        </label>
                    </div>
                </li>
<?php
        };

        $ns->render_option_visibility = function( $field ) use( $ns ) {
            $visibility = $field['visibility'];
            $radio_both_id = $field['field_id'] . '_visibility_both';
            $radio_admin_id = $field['field_id'] . '_visibility_admin';
?>
                <li>
                    <label>
                        <?php _e( 'Visibility' ); ?>
                    </label>
                    <div>
                        <input type="radio" id="<?php echo $radio_both_id; ?>" name="birchschedule_fields_options[<?php echo $field['field_id']; ?>][visibility]" value="both" <?php
            if ( $visibility == 'both' ) {
                echo "checked='checked'";
            }
            ?>/>
                        <label for="<?php echo $radio_both_id; ?>">
                            <?php _e( 'Customers and Admin', 'birchschedule' ); ?>
                        </label>
                        <input type="radio" id="<?php echo $radio_admin_id; ?>" name="birchschedule_fields_options[<?php echo $field['field_id']; ?>][visibility]" value="admin" <?php
            if ( $visibility == 'admin' ) {
                echo "checked='checked'";
            }
            ?>/>
                        <label for="<?php echo $radio_admin_id; ?>">
                            <?php _e( 'Admin Only', 'birchschedule' ); ?>
                        </label>
                    </div>
                </li>
<?php
        };

        $ns->render_field_save = function( $field ) use( $ns ) {
            global $birchschedule;

            $action_name = $birchschedule->fbuilder->get_action_name( 'save' );
            $base_url = $birchschedule->fbuilder->get_base_url();
?>
                    <li>
                        <?php wp_nonce_field( $action_name ); ?>
                        <input type="hidden" name="action" value="birchschedule_save_field_options" />
                        <input type="hidden" name="birchschedule_field_box_id" value="<?php echo $ns->get_field_box_id( $field ); ?>" />
                        <input type="submit" name="birchschedule_save_field_options" class="button-primary" value="<?php _e( 'Save', 'birchschedule' ); ?>"/>
                        <a href="<?php echo $base_url; ?>"><?php _e( 'Cancel', 'birchschedule' ); ?></a>
                    </li>
<?php
        };

        $ns->validate = function( $field ) use( $ns ) {
            $error = array();
            if ( isset( $field['required'] ) && $field['required'] ) {
                $request_name = $ns->get_dom_name( $field );
                if ( !isset( $_REQUEST[$request_name] ) || !$_REQUEST[$request_name] ) {
                    $error[$request_name] = __( 'This field is required', 'birchschedule' );
                }
            }
            return $error;
        };

        $ns->add_field_box = function( $field ) use( $ns ) {
            global $birchschedule;

            $title = esc_html( $ns->get_field_title( $field ) );
            $actions = $ns->get_field_actions_html( $field );
            $field_header = $ns->get_field_header( $field, $title, $actions );
            $field_box_id = $ns->get_field_box_id( $field );
            $screen = $birchschedule->fbuilder->get_screen();
            add_meta_box( $field_box_id, $field_header, array( $ns, '_render_field' ),
                $screen, $birchschedule->fbuilder->get_fields_column_id(), 'default',
                array( 'field' => $field ) );
        };

        $ns->get_field_box_id = function( $field ) {
            return 'birs_' . $field['field_id'] . '_box';
        };

        $ns->get_action_url = function( $field, $action ) use( $ns ) {
            global $birchschedule;

            $query_string = "?page=birchschedule_settings&tab=form_builder";
            $form_builder_url = admin_url( 'admin.php' . $query_string );
            $post_url = admin_url( 'admin-post.php' . $query_string );
            $field_id = $field['field_id'];
            if ( $action == 'edit' ) {
                $url = $form_builder_url . "&action=$action&field=$field[field_id]#" . $ns->get_field_box_id( $field );
            } else
                if ( $action == 'birchschedule_delete_field' ) {
                $url = wp_nonce_url( $post_url . "&action=$action&field=$field[field_id]", $birchschedule->fbuilder->get_action_name( 'delete_field' ) );
            }
            return $url;
        };

        $ns->get_field_action_html = function( $field, $href, $text ) {
            return "<a href='$href' class='edit-box'>$text</a>";
        };

        $ns->get_field_actions_html = function( $field ) use( $ns ) {
            if ( $ns->is_editing( $field ) ) {
                $edit_action = '';
            } else {
                $edit_action =
                $ns->get_field_action_html( $field, $ns->get_action_url( $field, 'edit' ), __( 'Edit', 'birchschedule' ) );
            }
            if ( $field['category'] == 'custom_fields' ) {
                $delete_action =
                $ns->get_field_action_html( $field, $ns->get_action_url( $field, 'birchschedule_delete_field' ), __( 'Delete', 'birchschedule' ) );
            } else {
                $delete_action = '';
            }
            return $edit_action . $delete_action;
        };

        $ns->get_field_title = function( $field ) use( $ns ) {
            global $birchschedule;

            if ( $field['category'] == 'custom_fields' ) {
                $title = $birchschedule->fbuilder->get_field_type_text( $field['type'] ) . ' - ' . $field['label'];
            } else {
                $title = __( 'Predefined', 'birchschedule' ) . ' - ' . $field['label'];
            }
            $title .= ' - ' . $ns->get_field_merge_tag( $field );
            return $title;
        };

        $ns->get_field_merge_tag = function( $field ) use( $ns ) {
            return '{' . $field['field_id'] . '}';
        };

        $ns->get_field_header = function( $field, $title, $actions ) {
            return sprintf( '<span>%s<span class="postbox-title-action">%s</span></span>', $title, $actions );
        };

        $ns->get_field_default_value = function( $field ) {
            return '';
        };

    } );
