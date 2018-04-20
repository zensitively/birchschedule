<?php

birch_ns( 'birchschedule.enotification', function( $ns ) {

        global $birchschedule;

        $_ns_data = new stdClass();

        $ns->init = function() use( $ns, $birchschedule ) {
            global $birchpress;

            $ns->init_data();

            add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );

            add_action( 'init', array( $ns, 'wp_init' ) );

            $birchschedule->view->settings->init_tab->when( $ns->is_tab_email_notification, $ns->init_tab );

            add_filter( 'birchschedule_view_settings_get_tabs',
                array( $ns, 'add_tab' ) );

            $birchpress->util->enable_remote_call( $ns->send_email );
        };

        $ns->init_data = function() use( $ns, $_ns_data ) {
            $_ns_data->SAVE_ACTION_NAME = "birchschedule_save_options_notification";

            $_ns_data->meta_box_category = $ns->get_tab_name() . '_main';

            $_ns_data->attachment_path = WP_CONTENT_DIR . '/uploads/bp-scheduler/appointment1on1.ics';
        };

        $ns->get_tab_name = function() {
            return 'email_notification';
        };

        $ns->is_module_enotification = function( $module ) {
            return $module['module'] === 'enotification';
        };

        $ns->is_tab_email_notification = function( $tab ) use ( $ns ) {
            return $tab['tab'] === $ns->get_tab_name();
        };

        $ns->get_meta_box_category = function() use( $ns, $_ns_data ) {
            return $_ns_data->meta_box_category;
        };

        $ns->get_attachment_path = function() use( $ns, $_ns_data ) {
            return $_ns_data->attachment_path;
        };

        $ns->wp_init = function() use( $ns, $birchschedule ) {
            add_action( 'birchschedule_model_booking_change_appointment1on1_status_after',
                array( $ns, 'notify_schedule' ), 20, 3 );

            add_action( 'birchschedule_model_booking_reschedule_appointment1on1_after',
                array( $ns, 'notify_reschedule' ), 20, 3 );

            add_action( 'birchschedule_model_booking_cancel_appointment1on1_after',
                array( $ns, 'notify_cancel' ), 20, 2 );

            add_action( 'birchschedule.enotification.remind_clients',
                array( $ns, 'remind_clients' ) );

            add_filter( 'cron_schedules', array( $ns, 'add_client_reminder_interval' ) );

            $ns->schedule_client_reminder();
        };

        $ns->wp_admin_init = function() use( $ns, $_ns_data, $birchschedule ) {
            add_action( 'birchschedule_view_staff_load_page_edit_after',
                array( $ns, 'add_meta_boxes' ), 15 );

            add_action( 'birchschedule_view_staff_save_post_after',
                array( $ns, 'save_staff_data' ) );

            add_action( 'admin_post_' . $_ns_data->SAVE_ACTION_NAME, array( $ns, 'save_options' ) );

            add_action( 'birchschedule_enotification_render_settings_items_after',
                array( $ns, 'render_settings_reminder_items' ), 20, 3 );
        };

        $ns->schedule_client_reminder = function() {
            if ( !wp_next_scheduled( 'birchschedule.enotification.remind_clients' ) ) {
                wp_schedule_event( time(), 'birs_client_reminder_interval',
                    'birchschedule.enotification.remind_clients' );
            }
        };

        $ns->add_client_reminder_interval = function( $schedules ) {
            $schedules['birs_client_reminder_interval'] = array(
                'interval' => 60 * 30,
                'display' => __( 'Client Reminder Interval', 'birchschedule' )
            );
            return $schedules;
        };

        $ns->remind_clients = function() use( $ns, $birchschedule ) {
            $options = $ns->get_options();
            $options_client = $options['client'];
            if ( !isset( $options_client['enable_reminder'] ) || $options_client['enable_reminder'] != "on" ) {
                return;
            }
            $interval = ( (double)$options['client']['reminder_prior_length'] ) * 60 * 60;
            $start = time();
            $end = $start + $interval;
            $appointments = $birchschedule->model->booking->query_appointments(
                array(
                    'start' => $start,
                    'end' => $end,
                    'status' => 'publish',
                ),
                array(
                    'appointment1on1_keys' => array(
                        '_birs_appointment1on1_reminded'
                    )
                )
            );
            foreach ( $appointments as $appointment ) {
                $appointment1on1s = $appointment['appointment1on1s'];
                foreach ( $appointment1on1s as $appointment1on1 ) {
                    if ( !isset( $appointment1on1['_birs_appointment1on1_reminded'] ) ||
                        !$appointment1on1['_birs_appointment1on1_reminded'] ) {

                        $appointment1on1_merge_values =
                        $ns->get_appointment1on1_merge_values( $appointment1on1['ID'] );
                        $ns->remind_client( $appointment1on1_merge_values );
                        update_post_meta( $appointment1on1['ID'], '_birs_appointment1on1_reminded', 1 );
                    }
                }
            }
        };

        $ns->remind_client = function( $appointment1on1 ) use( $ns ) {
            $has_attachment = $ns->has_attachment( 'client', 'reminder' );
            $ns->notify_to( 'client', 'reminder', $appointment1on1, $has_attachment );
        };

        $ns->render_settings_reminder_items = function( $to, $to_text, $options ) use( $ns, $birchschedule ) {
            if ( $to !== 'client' ) {
                return;
            }
            if ( isset( $options['client']['enable_reminder'] ) &&
                $options['client']['enable_reminder'] == "on" ) {
                $reminder_checked = "checked='checked'";
            } else {
                $reminder_checked = "";
            }
            $prior_length = $options['client']['reminder_prior_length'];
            $hour_html = "<input type='text'
            name='birchschedule_options_notification[client][reminder_prior_length]'
            style='width: 4em;'
            value='$prior_length' />"
?>
        <li>
            <input type="checkbox" name="birchschedule_options_notification[client][enable_reminder]"
                   id="birs_notification_client_enable_reminder" <?php echo $reminder_checked; ?>
                   value='on' />
            <label>
                   <?php printf( __( 'Send alerts to clients %s Hrs prior to their appointment.', 'birchschedule' ),
                $hour_html ); ?>
            </label>
        </li>
        <?php
        };

        $ns->has_attachment = function( $to, $template_name ) {
            if ( $template_name === 'cancelled' || $template_name === 'reminder' ) {
                return false;
            } else {
                return true;
            }
        };

        $ns->get_appointment1on1_merge_values = function( $appointment1on1_id ) use( $birchschedule ) {
            return $birchschedule->model->mergefields->get_appointment1on1_merge_values( $appointment1on1_id );
        };

        $ns->notify_schedule = function( $appointment1on1_id, $new_status, $old_status ) use( $ns, $birchschedule ) {
            if ( $new_status !== 'publish' ) {
                return;
            }
            $appointment1on1 = $ns->get_appointment1on1_merge_values( $appointment1on1_id );
            $has_attachment = $ns->has_attachment( 'client', 'new' );
            $ns->notify_to( 'client', 'new', $appointment1on1, $has_attachment );
            $has_attachment = $ns->has_attachment( 'staff', 'new' );
            $ns->notify_to( 'staff', 'new', $appointment1on1, $has_attachment );
        };

        $ns->notify_reschedule = function( $appointment1on1_id, $appointment_info, $old_appointment1on1 ) use( $ns, $birchschedule ) {
            global $birchpress;

            if ( $old_appointment1on1 && !$birchpress->util->is_error( $old_appointment1on1 ) ) {
                $appointment1on1 = $ns->get_appointment1on1_merge_values( $appointment1on1_id );
                $has_attachment = $ns->has_attachment( 'client', 'modified' );
                $ns->notify_to( 'client', 'modified', $appointment1on1, $has_attachment );
                $has_attachment = $ns->has_attachment( 'staff', 'modified' );
                $ns->notify_to( 'staff', 'modified', $appointment1on1, $has_attachment );
            }
        };

        $ns->notify_cancel = function( $appointment1on1_id, $appointment1on1 ) use( $ns ) {
            if ( !$appointment1on1 ) {
                return;
            }
            $has_attachment = $ns->has_attachment( 'client', 'cancelled' );
            $ns->notify_to( 'client', 'cancelled', $appointment1on1, $has_attachment );
            $has_attachment = $ns->has_attachment( 'staff', 'cancelled' );
            $ns->notify_to( 'staff', 'cancelled', $appointment1on1, $has_attachment );
        };

        $ns->is_enabled = function( $to, $template_name ) {
            return true;
        };

        $ns->notify_to = function( $to, $template_name, $appointment1on1, $attach ) use( $ns, $birchschedule ) {
            $notification_enabled = $ns->is_enabled( $to, $template_name );
            if ( !$notification_enabled ) {
                return;
            }
            remove_all_filters( 'wp_mail_from' );
            remove_all_filters( 'wp_mail_from_name' );
            $options = $ns->get_options();
            $to_options = $options[$to];
            if ( $to_options['enable'] == 'off' ) {
                return;
            }
            $template = $to_options['template'];
            $message = $template[$template_name]['message'];
            $subject = $template[$template_name]['subject'];
            if ( $to == 'client' ) {
                $to = $appointment1on1["_birs_client_email"];
            } else {
                $to = $appointment1on1["_birs_staff_email"];
            }
            $headers = array();
            $headers[] = "From: $to_options[from_name] <$to_options[from_email]>";
            if ( isset( $to_options['reply_to'] ) && $to_options['reply_to'] ) {
                $headers[] = "Reply-To: $to_options[reply_to]";
            }
            if ( isset( $to_options['bcc'] ) && $to_options['bcc'] ) {
                $headers[] = "Bcc: $to_options[bcc]";
            }
            $headers[] = "Content-Type: " . $template[$template_name]['content_type'] .
            "; charset=" . get_option( 'blog_charset' );
            $message = $birchschedule->model->mergefields->apply_merge_fields( $message, $appointment1on1 );
            $subject = $birchschedule->model->mergefields->apply_merge_fields( $subject, $appointment1on1 );
            $new_headers = array();
            foreach ( $headers as $header ) {
                $new_headers[] = $birchschedule->model->mergefields->apply_merge_fields( $header, $appointment1on1 );
            }
            $attachments = array();
            if ( $attach ) {
                $ns->generate_appointment1on1_ics( $appointment1on1 );
                $attachments[] = $ns->get_attachment_path();
            }
            $ns->async_send_email( $to, $subject, $message, $new_headers, $attachments );
        };

        $ns->async_send_email = function( $to, $subject, $message, $headers, $attachments ) use ( $ns ) {
            global $birchpress;
            $args = array( $to, $subject, $message, $headers, $attachments );
            $birchpress->util->async_run_task( array(
                    'action' => 'birchschedule.enotification.send_email',
                    'args' => $args
                ) );
        };

        $ns->send_email = function( $to, $subject, $message, $headers, $attachments ) {
            wp_mail( $to, $subject, $message, $headers, $attachments );
        };

        $ns->generate_appointment1on1_ics = function( $appointment1on1 ) use( $ns, $birchschedule ) {
            add_filter( 'birchschedule_icalendar_get_vcalendar_properties', function( $properties ) {
                    unset( $properties['X-WR-CALNAME'] );
                    unset( $properties['X-WR-CALDESC'] );
                    return $properties;
                } );
            $file_path = $ns->get_attachment_path();
            $content = $birchschedule->icalendar->get_appointment1on1s_as_ics( array(
                    $appointment1on1['ID'] => $appointment1on1
                ) );
            if ( !file_exists( dirname( $file_path ) ) ) {
                mkdir( dirname( $file_path ), 0755, true );
            }
            file_put_contents( $file_path, $content );
            chmod( $file_path, 0644 );
        };

        $ns->add_meta_boxes = function() use( $ns ) {
            add_meta_box( 'birchschedule-staff-additional', __( 'Additional Info', 'birchschedule' ),
                array( $ns, 'render_staff_additional' ), 'birs_staff', 'normal', 'default' );
        };

        $ns->save_staff_data = function( $staff ) {
            if ( isset( $_POST['birs_staff_email'] ) ) {
                $email = $_POST['birs_staff_email'];
                update_post_meta( $staff['ID'], '_birs_staff_email', $email );
            }
        };

        $ns->render_staff_additional = function( $post ) use( $ns, $birchschedule ) {
            $staff = $birchschedule->model->get( $post->ID, array(
                    'meta_keys' => array( '_birs_staff_email' ),
                    'base_keys' => array()
                ) );
            $email = $staff['_birs_staff_email'];
?>
        <div class="panel-wrap birchschedule">
            <table class="form-table">
                <tr>
                    <th>
                        <label for='birs_staff_email'><?php echo __( 'Email Address', 'birchschedule' ); ?></label>
                    </th>
                    <td>
                        <input name="birs_staff_email" id="birs_staff_email" class="regular-text" value="<?php echo $email; ?>"/>
                    </td>
                </tr>
            </table>
        </div>
        <?php
        };

        $ns->add_tab = function( $tabs ) use ( $ns ) {
            $tabs[$ns->get_tab_name()] = array(
                'title' => __( 'Notifications', 'birchschedule' ),
                'action' => array( $ns, 'render_page' ),
                'order' => 10
            );

            return $tabs;
        };

        $ns->init_tab = function() use( $ns, $birchschedule ) {
            $screen = $ns->get_screen();
            add_meta_box( 'birs_notification_staff', __( 'Notification to Provider', 'birchschedule' ),
                array( $ns, 'render_settings_staff' ),
                $screen, $ns->get_meta_box_category(), 'default' );
            add_meta_box( 'birs_notification_client', __( 'Notification to Client', 'birchschedule' ),
                array( $ns, 'render_settings_client' ),
                $screen, $ns->get_meta_box_category(), 'default' );
            $ns->enqueue_scripts();
        };

        $ns->enqueue_scripts = function() use( $ns, $birchschedule ) {
            $product_version = $birchschedule->get_product_version();
            wp_register_script( 'birchschedule_email_notification',
                $birchschedule->plugin_url() . '/modules/enotification/assets/js/email-notification.js',
                array( 'birchschedule_view_admincommon', 'postbox', 'jgrowl' ), $product_version );
            wp_enqueue_script( 'postbox' );
            wp_enqueue_script( 'birchschedule_email_notification' );
            wp_enqueue_style( 'birchschedule_admincommon' );
        };

        $ns->get_options = function() use( $ns ) {
            $options = get_option( 'birchschedule_options_notification' );
            if ( $options === false ) {
                $options = $ns->upgrader->get_default_options_notification();
                add_option( 'birchschedule_options_notification', $options );
            }
            return $options;
        };

        $ns->save_options = function() use( $ns, $_ns_data, $birchschedule ) {
            check_admin_referer( $_ns_data->SAVE_ACTION_NAME );
            $options = $_POST['birchschedule_options_notification'];
            $old_options = $ns->get_options();
            foreach ( array( 'staff', 'client' ) as $to ) {
                if ( !isset( $options[$to]['enable'] ) ) {
                    $options[$to]['enable'] = 'off';
                }
            }
            $options['client']['reminder_prior_length'] =
            (double)( $options['client']['reminder_prior_length'] );
            $options = stripslashes_deep( $options );
            if ( isset( $old_options['version'] ) ) {
                $options['version'] = $old_options['version'];
            }
            update_option( "birchschedule_options_notification", $options );
            set_transient( "birchschedule_notification_info", __( "Notification Settings Updated", 'birchschedule' ), 60 );
            $orig_url = $_POST['_wp_http_referer'];
            wp_redirect( $orig_url );
            exit;
        };

        $ns->sanitize_text_for_attr = function( $value ) {
            return esc_attr( $value );
        };

        $ns->get_templates_names = function( $to ) {
            $trigger_names = array(
                'new' => __( 'New Appointment', 'birchschedule' ),
                'modified' => __( 'Appointment Modified', 'birchschedule' ),
                'cancelled' => __( 'Appointment Cancelled', 'birchschedule' )
            );
            if ( $to === 'client' ) {
                $trigger_names['reminder'] = __( 'Appointment Reminder', 'birchschedule' );
            }
            return $trigger_names;
        };

        $ns->render_settings_items = function( $to, $to_text, $options ) use( $ns ) {
?>
       <li>
            <label for="birs_notification_<?php echo $to; ?>_from_name"><?php _e( 'From Name', 'birchschedule' ); ?></label>
            <div>
                <input name="birchschedule_options_notification[<?php echo $to; ?>][from_name]"
                       id="birs_notification_<?php echo $to; ?>_from_name" class="regular-text"
                       type="text"
                       value="<?php echo $ns->sanitize_text_for_attr( $options[$to]['from_name'] ); ?>" />
            </div>
        </li>
        <li>
            <label for="birs_notification_<?php echo $to; ?>_from_email"><?php _e( 'From Email', 'birchschedule' ); ?></label>
            <div>
                <input name="birchschedule_options_notification[<?php echo $to; ?>][from_email]"
                       id="birs_notification_<?php echo $to; ?>_from_email" class="regular-text"
                       type="text"
                       value="<?php echo $ns->sanitize_text_for_attr( $options[$to]['from_email'] ) ?>"
                       />
            </div>
        </li>
        <?php if ( $to == 'client' ): ?>
            <li>
                <label for="birs_notification_<?php echo $to; ?>_reply_to"><?php _e( 'Reply To', 'birchschedule' ); ?></label>
                <div>
                    <input name="birchschedule_options_notification[<?php echo $to; ?>][reply_to]"
                           id="birs_notification_<?php echo $to; ?>_reply_to" class="regular-text"
                           type="text"
                           value="<?php echo $ns->sanitize_text_for_attr( $options[$to]['reply_to'] ) ?>"
                           />
                </div>
            </li>
        <?php endif; ?>
        <li>
            <label for="birs_notification_<?php echo $to; ?>_bcc"><?php _e( 'BCC', 'birchschedule' ) ?></label>
            <div>
                <input name="birchschedule_options_notification[<?php echo $to; ?>][bcc]"
                       id="birs_notification_<?php echo $to; ?>_bcc" class="regular-text"
                       type="text"
                       value="<?php echo $ns->sanitize_text_for_attr( $options[$to]['bcc'] ) ?>"
                       />
            </div>
        </li>
        <?php
        };

        $ns->render_settings_templates = function( $to, $to_text, $options ) use( $ns, $birchschedule, $_ns_data ) {
?>
        <li>
            <h4>
                <?php $templates_editor_id =  "birs_notification_" . $to . "_templates_editor"; ?>
                <a href="#<?php echo $templates_editor_id; ?>" class="birs-toggle-templates-editor">
                    <span>+</span> <?php _e( 'Edit Email Templates', 'birchschedule' ); ?>
                </a>
            </h4>
        </li>
        <li id="<?php echo $templates_editor_id; ?>" style="display:none;">
            <ul class="wp-tab-bar">
                <?php
            $trigger_names = $ns->get_templates_names( $to );
            foreach ( $trigger_names as $trigger_name => $trigger_text ) {
                if ( $trigger_name == 'new' ) {
                    $tab_class = "wp-tab-active";
                } else {
                    $tab_class = "";
                }
                $block_id = "birs_notification_" . $to . "_template_" . $trigger_name;
?>
                    <li class="<?php echo $tab_class; ?>">
                        <a href="#<?php echo $block_id; ?>"><?php echo $trigger_text; ?></a>
                    </li>
                    <?php
            }
?>
            </ul>
            <?php
            foreach ( $trigger_names as $trigger_name => $trigger_text ) {
                if ( $trigger_name == "new" ) {
                    $block_style = "";
                } else {
                    $block_style = "display:none";
                }
                $block_id = "birs_notification_" . $to . "_template_" . $trigger_name;
?>
                <div id="<?php echo $block_id; ?>"
                     class="wp-tab-panel" style="<?php echo $block_style; ?>">
                    <ul>
                        <li>
                            <label for="birs_notification_<?php echo $to; ?>_template_<?php echo $trigger_name; ?>_subject">
                                <?php _e( "Subject", "birchschedule" ); ?>
                            </label>
                            <div>
                                <input id="birs_notification_<?php echo $to; ?>_template_<?php echo $trigger_name; ?>_subject"
                                       name="birchschedule_options_notification[<?php echo $to; ?>][template][<?php echo $trigger_name; ?>][subject]"
                                       class="large-text" type="text"
                                       value="<?php echo $ns->sanitize_text_for_attr( $options[$to]["template"][$trigger_name]["subject"] ); ?>"
                                       />
                            </div>
                        </li>
                        <li>
                            <label>
                                <?php _e( "Message", "birchschedule" ); ?>
                            </label>
                            <div>
                                <?php
                $textarea_id = "birs_notification_" . $to . "_template_" . $trigger_name . "_message";
                $textarea_name = "birchschedule_options_notification[$to][template][$trigger_name][message]";
                $content = $options[$to]["template"][$trigger_name]["message"];
                wp_editor( $content, $textarea_id, array(
                        'textarea_name' => $textarea_name
                    ) );
?>
                                <input type="hidden"
                                    name="birchschedule_options_notification[<?php echo $to; ?>][template][<?php echo $trigger_name; ?>][content_type]"
                                    value="text/html" />
                            </div>
                        </li>
                    </ul>
                </div>
                <?php
            }
?>
        </li>
        <?php
        };

        $ns->render_settings = function( $to, $to_text ) use( $ns, $birchschedule ) {
            $options = $ns->get_options();
            if ( $options[$to]['enable'] == "on" ) {
                $notification_checked = "checked='checked'";
                $details_style = "";
            } else {
                $notification_checked = "";
                $details_style = "display:none;";
            }
            $details_id = "birs_notification_" . $to . "_details";
?>
        <div class="panel-wrap">
            <label for="birs_notification_<?php echo $to; ?>_enable">
                <input type="checkbox" name="birchschedule_options_notification[<?php echo $to; ?>][enable]"
                       id="birs_notification_<?php echo $to; ?>_enable" <?php echo $notification_checked; ?>
                       data-details-id="<?php echo $details_id; ?>" />
                       <?php printf( __( 'Enable email notification to %s', 'birchschedule' ), $to_text ); ?>
            </label>
            <ul id="<?php echo $details_id; ?>" style="<?php echo $details_style; ?>">
                <?php $ns->render_settings_items( $to, $to_text, $options ); ?>
                <?php $ns->render_settings_templates( $to, $to_text, $options ); ?>
            </ul>
        </div>
        <?php
        };

        $ns->render_settings_staff = function() use( $ns ) {
            $ns->render_settings( 'staff', __( 'providers', 'birchschedule' ) );
        };

        $ns->render_settings_client = function() use( $ns ) {
            $ns->render_settings( 'client', __( 'clients', 'birchschedule' ) );
        };

        $ns->get_page_hook = function() {
            return "birchschedule_page_settings_tab_notification";
        };

        $ns->get_screen = function() use( $ns, $birchschedule ) {
            $page_hook = $ns->get_page_hook();
            $screen = $birchschedule->view->get_screen( $page_hook );
            return $screen;
        };

        $ns->render_page = function() use( $ns, $birchschedule, $_ns_data ) {
            $screen = $ns->get_screen();
?>
        <style type="text/css">
            #birchschedule_email_notification .wp-tab-panel {
                max-height: 1000px;
            }
            #birchschedule_email_notification .regular-text {
                width: 100%;
                max-width: 27em;
            }
        </style>
        <div id="birchschedule_email_notification" class="wrap">
            <form method="post" action="<?php echo admin_url( 'admin-post.php' ) ?>">
                <?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>
                <?php wp_nonce_field( $_ns_data->SAVE_ACTION_NAME ); ?>
                <input type="hidden" name="action" value="<?php echo $_ns_data->SAVE_ACTION_NAME; ?>" />
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-1">
                        <div id="postbox-container-1" class="postbox-container">
                            <?php do_meta_boxes( $screen, $ns->get_meta_box_category(), array() ) ?>
                        </div>
                    </div>
                    <br class="clear" />
                </div>
                <input type="submit" name="submit" value="<?php _e( 'Save changes', 'birchschedule' ); ?>" class="button-primary" />
            </form>
        </div>
        <style type="text/css">
          .wp-editor-container iframe, .wp-editor-container textarea.wp-editor-area {
            height: 20em !important;
          }
        </style>
        <script type="text/javascript">
            jQuery(document).ready( function($) {
                postboxes.init = function() {};
                postboxes.add_postbox_toggles('<?php echo $ns->get_page_hook(); ?>');
        <?php
            $notification_info = get_transient( "birchschedule_notification_info" );
            if ( false !== $notification_info ) {
?>
                $.jGrowl('<?php echo esc_js( $notification_info ); ?>', {
                        life: 1000,
                        position: 'center',
                        header: '<?php _e( '&nbsp', 'birchschedule' ); ?>'
                    });
            <?php
                delete_transient( "birchschedule_notification_info" );
            }
?>
            });
        </script>
        <?php
        };

    } );
