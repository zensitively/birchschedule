<?php

birch_ns( 'birchschedule.view.appointments.edit.clientlist.edit', function( $ns ) {

	global $birchschedule;

	$ns->init = function() use ( $ns ) {
		add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );

		add_action( 'birchschedule_view_register_common_scripts_after',
					array( $ns, 'register_scripts' ) );
	};

	$ns->wp_admin_init = function() use ( $ns, $birchschedule ) {
		add_action( 'wp_ajax_birchschedule_view_appointments_edit_clientlist_edit_save',
					array( $ns, 'ajax_save' ) );

		add_action( 'birchschedule_view_enqueue_scripts_post_edit_after',
					array( $ns, 'enqueue_scripts_post_edit' ) );

		add_action( 'birchschedule_view_appointments_edit_clientlist_render_more_rows_after',
					array( $ns, 'render_row' ), 20, 3 );

		add_filter( 'birchschedule_view_appointments_edit_clientlist_get_item_actions',
					array( $ns, 'add_item_action' ), 20, 2 );
	};

	$ns->register_scripts = function() use( $birchschedule ) {
		$version = $birchschedule->get_product_version();

		wp_register_script( 'birchschedule_view_appointments_edit_clientlist_edit',
							$birchschedule->plugin_url() . '/assets/js/view/appointments/edit/clientlist/edit/base.js',
							array( 'birchschedule_view_admincommon', 'birchschedule_view' ), "$version" );
	};

	$ns->enqueue_scripts_post_edit = function( $arg ) {
		global $birchschedule;
		if ( $arg['post_type'] != 'birs_appointment' ) {
			return;
		}

		$birchschedule->view->register_3rd_scripts();
		$birchschedule->view->register_3rd_styles();
		$birchschedule->view->enqueue_scripts(
			array(
				'birchschedule_view_appointments_edit_clientlist_edit'
			)
		);
	};

	$ns->add_item_action = function( $item_actions, $item ) {
		$action_html = '<a href="javascript:void(0);" data-item-id="%s">%s</a>';
		$item_actions['edit'] = sprintf( $action_html, $item['ID'], __( 'Edit', 'birchschedule' ) );
		return $item_actions;
	};

	$ns->render_row = function( $wp_list_table, $item, $row_class ) use( $ns ) {
		$client_id = $item['ID'];
		$appointment_id = $wp_list_table->appointment_id;
		$column_count = $wp_list_table->get_column_count();
		$edit_html = $ns->get_client_edit_html( $appointment_id, $client_id );
?>
                <tr class="<?php echo $row_class; ?> birs_row_edit"
                    id="birs_client_list_row_edit_<?php echo $client_id; ?>"
                    data-item-id = "<?php echo $client_id; ?>"
                    data-edit-html = "<?php echo esc_attr( $edit_html ); ?>">

                    <td colspan = "<?php echo $column_count; ?>">
                    </td>
                </tr>
<?php
	};

	$ns->ajax_save = function() use ( $ns ) {
		global $birchpress, $birchschedule;

		$appointment1on1_errors = $ns->validate_appointment1on1_info();
		$client_errors = $ns->validate_client_info();
		$email_errors = $ns->validate_duplicated_email();
		$errors = array_merge( $appointment1on1_errors, $client_errors, $email_errors );
		if ( $errors ) {
			$birchschedule->view->render_ajax_error_messages( $errors );
		}
		$client_config = array(
			'base_keys' => array(),
			'meta_keys' => $_POST['birs_client_fields']
		);
		$client_info = $birchschedule->view->merge_request( array(), $client_config, $_POST );
		$client_info['ID'] = $_POST['birs_client_id'];
		$client_id = $birchschedule->model->booking->save_client( $client_info );
		if ( isset( $_POST['birs_appointment_fields'] ) ) {
			$appointment1on1s_fields = $_POST['birs_appointment_fields'];
		} else {
			$appointment1on1s_fields = array();
		}
		$appointment1on1s_config = array(
			'base_keys' => array(),
			'meta_keys' => $appointment1on1s_fields
		);
		$appointment1on1s_info = $birchschedule->view->merge_request( array(), $appointment1on1s_config, $_POST );
		$appointment1on1s_info['_birs_client_id'] = $_POST['birs_client_id'];
		$appointment1on1s_info['_birs_appointment_id'] = $_POST['birs_appointment_id'];
		$appointment1on1s_info['_birs_appointment_fields'] = $appointment1on1s_fields;
		$birchschedule->model->booking->change_appointment1on1_custom_info( $appointment1on1s_info );
		$birchschedule->view->render_ajax_success_message( array(
			'code' => 'success',
			'message' => ''
		) );
	};

	$ns->validate_duplicated_email = function() {
		global $birchschedule;

		$errors = array();
		$client_id = $_POST['birs_client_id'];
		if ( !isset( $_POST['birs_client_email'] ) ) {
			return $errors;
		}
		$email = $_POST['birs_client_email'];
		if ( $birchschedule->model->booking->if_email_duplicated( $client_id, $email ) ) {
			$errors['birs_client_email'] = __( 'Email already exists.', 'birchschedule' ) . ' (' . $email. ')';
		}
		return $errors;
	};

	$ns->validate_client_info = function() {
		$errors = array();
		if ( !$_POST['birs_client_name_first'] ) {
			$errors['birs_client_name_first'] = __( 'This field is required', 'birchschedule' );
		}
		if ( !$_POST['birs_client_name_last'] ) {
			$errors['birs_client_name_last'] = __( 'This field is required', 'birchschedule' );
		}
		if ( !$_POST['birs_client_email'] ) {
			$errors['birs_client_email'] = __( 'Email is required', 'birchschedule' );
		} else if ( !is_email( $_POST['birs_client_email'] ) ) {
			$errors['birs_client_email'] = __( 'Email is incorrect', 'birchschedule' );
		}
		if ( !$_POST['birs_client_phone'] ) {
			$errors['birs_client_phone'] = __( 'This field is required', 'birchschedule' );
		}

		return $errors;
	};

	$ns->validate_appointment1on1_info = function() {
		return array();
	};

	$ns->get_client_edit_actions = function() {
?>
                <ul>
                    <li class="birs_form_field">
                        <label>
                            &nbsp;
                        </label>
                        <div class="birs_field_content">
                            <input name="birs_appointment_client_edit_save"
                                id="birs_appointment_client_edit_save"
                                type="button" class="button-primary"
                                value="<?php _e( 'Save', 'birchschedule' ); ?>" />
                            <a href="javascript:void(0);"
                                id="birs_appointment_client_edit_cancel"
                                style="padding: 4px 0 0 4px; display: inline-block;">
                                <?php _e( 'Cancel', 'birchschedule' ); ?>
                            </a>
                        </div>
                    </li>
                </ul>
<?php
	};

    $ns->get_client_edit_html = function( $appointment_id, $client_id ) use ( $ns ) {
		ob_start();
?>
                <div style="overflow:hidden;">
                    <h4><?php _e( 'Edit Client', 'birchschedule' ); ?></h4>
                    <?php echo $ns->get_client_info_html( $client_id ); ?>
                    <input type="hidden" name="birs_client_id" id="birs_client_id" value="<?php echo $client_id; ?>" />
                    <div style="border-bottom: 1px solid #EEEEEE;"></div>
                    <?php echo $ns->get_appointment1on1_info_html( $appointment_id, $client_id ); ?>
                    <?php echo $ns->get_client_edit_actions(); ?>
                </div>
<?php
		return ob_get_clean();
	};

	$ns->get_appointment1on1_info_html = function( $appointment_id, $client_id ) use ( $ns ) {
		global $birchschedule;

		$appointment1on1s = $birchschedule->model->query(
			array(
				'post_type' => 'birs_appointment1on1',
				'meta_query' => array(
					array(
						'key' => '_birs_client_id',
						'value' => $client_id
					),
					array(
						'key' => '_birs_appointment_id',
						'value' => $appointment_id
					)
				)
			),
			array(
				'base_keys' => array(),
				'meta_keys' => array( '_birs_appointment_notes' )
			)
		);
		$notes = '';
		if ( $appointment1on1s ) {
			$appointment1on1s = array_values( $appointment1on1s );
			$appointment_ext = $appointment1on1s[0];
			if ( isset( $appointment_ext['_birs_appointment_notes'] ) ) {
				$notes = $appointment_ext['_birs_appointment_notes'];
			}
		}
		ob_start();
?>
                <ul>
                    <li class="birs_form_field">
                        <label>
                            <?php _e( 'Notes', 'birchschedule' ); ?>
                        </label>
                        <div class="birs_field_content">
                            <textarea id="birs_appointment_notes" name="birs_appointment_notes"><?php echo $notes; ?></textarea>
                            <input type="hidden" name="birs_appointment_fields[]" value="_birs_appointment_notes" />
                        </div>
                    </li>
                </ul>
<?php
		$content = ob_get_clean();
		return $content;
	};

	$ns->get_client_info_html = function( $client_id ) use ( $ns ) {
		global $birchpress, $birchschedule;

		$client_titles = $birchpress->util->get_client_title_options();
		$client_title = get_post_meta( $client_id, '_birs_client_title', true );
		$first_name = get_post_meta( $client_id, '_birs_client_name_first', true );
		$last_name = get_post_meta( $client_id, '_birs_client_name_last', true );
		$addresss1 = get_post_meta( $client_id, '_birs_client_address1', true );
		$addresss2 = get_post_meta( $client_id, '_birs_client_address2', true );
		$email = get_post_meta( $client_id, '_birs_client_email', true );
		$phone = get_post_meta( $client_id, '_birs_client_phone', true );
		$city = get_post_meta( $client_id, '_birs_client_city', true );
		$zip = get_post_meta( $client_id, '_birs_client_zip', true );
		$state = get_post_meta( $client_id, '_birs_client_state', true );
		$country = get_post_meta( $client_id, '_birs_client_country', true );
		if ( !$country ) {
			$country = $birchschedule->model->get_default_country();
		}
		$states = $birchpress->util->get_states();
		$countries = $birchpress->util->get_countries();
		if ( isset( $states[$country] ) ) {
			$select_display = "";
			$text_display = "display:none;";
		} else {
			$select_display = "display:none;";
			$text_display = "";
		}
		ob_start();
?>
                <ul>
                    <li class="birs_form_field birs_client_title">
                        <label for="birs_client_title"><?php _e( 'Title', 'birchschedule' ); ?></label>
                        <div class="birs_field_content">
                            <select name="birs_client_title" id="birs_client_title">
                                <?php $birchpress->util->render_html_options( $client_titles, $client_title ); ?>
                            </select>
                            <input type="hidden" name="birs_client_fields[]" value="_birs_client_title">
                        </div>
                        <div class="birs_error" id="birs_client_title_error">
                        </div>
                    </li>
                    <li class="birs_form_field birs_client_name_first">
                        <label for="birs_client_name_first"><?php _e( 'First Name', 'birchschedule' ); ?></label>
                        <div class="birs_field_content">
                            <input type="text" name="birs_client_name_first" id="birs_client_name_first" value="<?php echo esc_attr( $first_name ); ?>">
                            <input type="hidden" name="birs_client_fields[]" value="_birs_client_name_first">
                        </div>
                        <div class="birs_error" id="birs_client_name_first_error">
                        </div>
                    </li>
                        <li class="birs_form_field birs_client_name_last">
                        <label for="birs_client_name_last"><?php _e( 'Last Name', 'birchschedule' ); ?></label>
                        <div class="birs_field_content">
                            <input type="text" name="birs_client_name_last" id="birs_client_name_last" value="<?php echo esc_attr( $last_name ); ?>">
                            <input type="hidden" name="birs_client_fields[]" value="_birs_client_name_last">
                        </div>
                        <div class="birs_error" id="birs_client_name_last_error">
                        </div>
                    </li>
                        <li class="birs_form_field birs_client_email">
                        <label for="birs_client_email"><?php _e( 'Email', 'birchschedule' ); ?></label>
                        <div class="birs_field_content">
                            <input type="text" name="birs_client_email" id="birs_client_email" value="<?php echo esc_attr( $email ); ?>">
                            <input type="hidden" name="birs_client_fields[]" value="_birs_client_email">
                        </div>
                        <div class="birs_error" id="birs_client_email_error">
                        </div>
                    </li>
                    <li class="birs_form_field birs_client_phone">
                        <label for="birs_client_phone"><?php _e( 'Phone', 'birchschedule' ); ?></label>
                        <div class="birs_field_content">
                            <input type="text" name="birs_client_phone" id="birs_client_phone" value="<?php echo esc_attr( $phone ); ?>">
                            <input type="hidden" name="birs_client_fields[]" value="_birs_client_phone">
                        </div>
                        <div class="birs_error" id="birs_client_phone_error">
                        </div>
                    </li>
                    <li class="birs_form_field birs_client_address">
                        <label for="birs_client_address1"><?php _e( 'Address', 'birchschedule' ); ?></label>
                        <div class="birs_field_content">
                            <input type="text" name="birs_client_address1" id="birs_client_address1" style="display: block;" value="<?php echo esc_attr( $addresss1 ); ?>">
                            <input type="text" name="birs_client_address2" id="birs_client_address2" value="<?php echo esc_attr( $addresss2 ); ?>">
                            <input type="hidden" name="birs_client_fields[]" value="_birs_client_address1">
                            <input type="hidden" name="birs_client_fields[]" value="_birs_client_address2">
                        </div>
                        <div class="birs_error" id="birs_client_address_error">
                        </div>
                    </li>
                    <li class="birs_form_field birs_client_city">
                        <label for="birs_client_city"><?php _e( 'City', 'birchschedule' ); ?></label>
                        <div class="birs_field_content">
                            <input type="text" name="birs_client_city" id="birs_client_city" value="<?php echo esc_attr( $city ); ?>">
                            <input type="hidden" name="birs_client_fields[]" value="_birs_client_city">
                        </div>
                        <div class="birs_error" id="birs_client_city_error">
                        </div>
                    </li>
                    <li class="birs_form_field birs_client_state">
                        <label for="birs_client_state"><?php _e( 'State/Province', 'birchschedule' ); ?></label>
                        <div class="birs_field_content">
                            <select name="birs_client_state_select" id ="birs_client_state_select" style="<?php echo $select_display; ?>">
<?php
		if ( isset( $states[$country] ) ) {
			$birchpress->util->render_html_options( $states[$country], $state );
		}
?>
                            </select>
                            <input type="text" name="birs_client_state" id="birs_client_state" value="<?php echo esc_attr( $state ); ?>" style="<?php echo $text_display; ?>" />
                            <input type="hidden" name="birs_client_fields[]" value="_birs_client_state">
                        </div>
                        <div class="birs_error" id="birs_client_state_error">
                        </div>
                    </li>
                    <li class="birs_form_field birs_client_country">
                        <label for="birs_client_country"><?php _e( 'Country', 'birchschedule' ); ?></label>
                        <div class="birs_field_content">
                            <select name="birs_client_country" id="birs_client_country">
                                <?php $birchpress->util->render_html_options( $countries, $country ); ?>
                            </select>
                            <input type="hidden" name="birs_client_fields[]" value="_birs_client_country">
                        </div>
                        <div class="birs_error" id="birs_client_country_error">
                        </div>
                    </li>
                    <li class="birs_form_field birs_client_zip">
                        <label for="birs_client_zip"><?php _e( 'Zip Code', 'birchschedule' ); ?></label>
                        <div class="birs_field_content">
                            <input type="text" name="birs_client_zip" id="birs_client_zip" value="<?php echo esc_attr( $zip ); ?>">
                            <input type="hidden" name="birs_client_fields[]" value="_birs_client_zip">
                        </div>
                        <div class="birs_error" id="birs_client_zip_error">
                        </div>
                    </li>
                </ul>
<?php
		return ob_get_clean();
	};

} );
