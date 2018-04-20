<?php

birch_ns('birchschedule.view.clients', function($ns) {

		global $birchschedule;

		$ns->init = function() use ($ns, $birchschedule) {
			add_action('admin_init', array($ns, 'wp_admin_init'));

			add_action('init', array($ns, 'wp_init'));

			$birchschedule->view->load_page_edit->when( $ns->is_post_type_client, $ns->load_page_edit );

			$birchschedule->view->enqueue_scripts_edit->when( $ns->is_post_type_client, $ns->enqueue_scripts_edit );

			$birchschedule->view->enqueue_scripts_list->when( $ns->is_post_type_client, $ns->enqueue_scripts_list );

			$birchschedule->view->save_post->when( $ns->is_post_type_client, $ns->save_post );

			$birchschedule->view->pre_save_post->when( $ns->is_post_type_client, $ns->pre_save_post );
		};

		$ns->wp_init = function() use ($ns) {
			$ns->register_post_type();
		};

		$ns->wp_admin_init = function() use ($ns) {
			add_filter('manage_edit-birs_client_columns', array($ns, 'get_edit_columns'));
			add_action('manage_birs_client_posts_custom_column', array($ns, 'render_custom_columns'), 2);
		};

		$ns->is_post_type_client = function( $post ) {
			return $post['post_type'] === 'birs_client';
		};

		$ns->register_post_type = function() {
			register_post_type('birs_client', array(
					'labels' => array(
						'name' => __('Clients', 'birchschedule'),
						'singular_name' => __('Client', 'birchschedule'),
						'add_new' => __('Add Client', 'birchschedule'),
						'add_new_item' => __('Add New Client', 'birchschedule'),
						'edit' => __('Edit', 'birchschedule'),
						'edit_item' => __('Edit Client', 'birchschedule'),
						'new_item' => __('New Client', 'birchschedule'),
						'view' => __('View Client', 'birchschedule'),
						'view_item' => __('View Client', 'birchschedule'),
						'search_items' => __('Search Clients', 'birchschedule'),
						'not_found' => __('No Clients found', 'birchschedule'),
						'not_found_in_trash' => __('No Clients found in trash', 'birchschedule'),
						'parent' => __('Parent Client', 'birchschedule')
					),
					'description' => __('This is where clients are stored.', 'birchschedule'),
					'public' => false,
					'show_ui' => true,
					'menu_icon' => 'dashicons-id',
					'capability_type' => 'birs_client',
					'map_meta_cap' => true,
					'publicly_queryable' => false,
					'exclude_from_search' => true,
					'show_in_menu' => true,
					'hierarchical' => false,
					'show_in_nav_menus' => false,
					'rewrite' => false,
					'query_var' => true,
					'supports' => array('custom-fields'),
					'has_archive' => false
				)
			);
		};

		$ns->load_page_edit = function($arg) use ($ns) {
			add_action('add_meta_boxes', array($ns, 'add_meta_boxes'));
			add_filter('post_updated_messages', array($ns, 'get_updated_messages'));
		};

		$ns->enqueue_scripts_edit = function($arg) {
			global $birchschedule;

			$birchschedule->view->register_3rd_scripts();
			$birchschedule->view->register_3rd_styles();
			$birchschedule->view->enqueue_scripts(
				array(
					'birchschedule_view_clients_edit', 'birchschedule_model',
					'birchschedule_view_admincommon', 'birchschedule_view'
				)
			);
			$birchschedule->view->enqueue_styles(array('birchschedule_admincommon'));
		};

		$ns->enqueue_scripts_list = function($arg) {
			global $birchschedule;

			$birchschedule->view->register_3rd_scripts();
			$birchschedule->view->register_3rd_styles();
			$birchschedule->view->enqueue_styles( 'birchschedule_admincommon' );
		};

		$ns->add_meta_boxes = function() use($ns) {
			remove_meta_box('slugdiv', 'birs_client', 'normal');
			remove_meta_box('postcustom', 'birs_client', 'normal');
			add_meta_box('birchschedule-client-info', __('Client Info', 'birchschedule'),
				array($ns, 'render_client_info'), 'birs_client', 'normal', 'high');
		};

		$ns->get_edit_columns = function($columns) {
			$columns = array();

			$columns["cb"] = "<input type=\"checkbox\" />";
			$columns["title"] = __("Client Name", 'birchschedule');
			$columns["birs_client_phone"] = __("Phone", 'birchschedule');
			$columns["birs_client_email"] = __("Email", 'birchschedule');
			$columns["birs_client_address"] = __("Address", 'birchschedule');
			return $columns;
		};

		$ns->render_custom_columns = function($column) {
			global $post;

			if ($column === "birs_client_address") {
				$address1 = get_post_meta($post->ID, '_birs_client_address1', true);
				$address2 = get_post_meta($post->ID, '_birs_client_address2', true);
				$value = $address1 . '<br>' . $address2;
			} else {
				$value = get_post_meta($post->ID, '_' . $column, true);
			}

			echo $value;
		};

		$ns->get_updated_messages = function($messages) {
			global $post, $post_ID, $birchschedule;

			if($birchschedule->view->has_errors()) {
				$messages['birs_client'] = array(
				);
			} else {
				$messages['birs_client'] = array(
					0 => '', // Unused. Messages start at index 1.
					1 => __('Client updated.', 'birchschedule'),
					2 => __('Custom field updated.', 'birchschedule'),
					3 => __('Custom field deleted.', 'birchschedule'),
					4 => __('Client updated.', 'birchschedule'),
					5 => isset($_GET['revision']) ? sprintf(__('Client restored to revision from %s', 'birchschedule'), wp_post_revision_title((int) $_GET['revision'], false)) : false,
					6 => __('Client updated.', 'birchschedule'),
					7 => __('Client saved.', 'birchschedule'),
					8 => __('Client submitted.', 'birchschedule'),
					9 => sprintf(__('Client scheduled for: <strong>%1$s</strong>.', 'birchschedule'), date_i18n('M j, Y @ G:i', strtotime($post->post_date))),
					10 => __('Client draft updated.', 'birchschedule')
				);
			}

			return $messages;
		};

		$ns->save_post = function($post) {

			global $birchschedule;

			if (isset($_POST['birs_client_fields'])) {
				$fields = $_POST['birs_client_fields'];
			} else {
				$fields = array();
			}
			$config = array(
				'meta_keys' => $fields,
				'base_keys' => array()
			);
			$client_data =
			$birchschedule->view->merge_request($post, $config, $_POST);
			$birchschedule->model->save($client_data, $config);
		};

		$ns->pre_save_post = function($post_data, $post_attr) {

			global $birchschedule;

			$errors = $birchschedule->view->clients->validate_data($post_attr['ID']);
			if($errors) {
				$birchschedule->view->save_errors($errors);
				return false;
			}

			if (isset($_POST['birs_client_name_first'])) {
				$first_name = $_POST['birs_client_name_first'];
			} else {
				$first_name = '';
			}
			if (isset($_POST['birs_client_name_last'])) {
				$last_name = $_POST['birs_client_name_last'];
			} else {
				$last_name = '';
			}
			$post_data['post_title'] = $first_name . ' ' . $last_name;
			return $post_data;
		};

		$ns->validate_data = function($client_id) {
			global $birchschedule;

			$errors = array();
			if(!is_email($_POST['birs_client_email'])) {
				$errors[] = __('The email address isn’t correct.', 'birchschedule');
			} else {
				if($birchschedule->model->booking->if_email_duplicated($client_id, $_POST['birs_client_email'])) {
					$errors[] = __('Email already exists.', 'birchschedule').
					' (' . $_POST['birs_client_email']. ')';
				}
			}

			return $errors;
		};

		$ns->get_client_details_html = function($client_id) {
			global $birchpress, $birchschedule;

			$post_id = $client_id;
			$client_titles = $birchpress->util->get_client_title_options();
			$client_title = get_post_meta($post_id, '_birs_client_title', true);
			$first_name = get_post_meta($post_id, '_birs_client_name_first', true);
			$last_name = get_post_meta($post_id, '_birs_client_name_last', true);
			$addresss1 = get_post_meta($post_id, '_birs_client_address1', true);
			$addresss2 = get_post_meta($post_id, '_birs_client_address2', true);
			$email = get_post_meta($post_id, '_birs_client_email', true);
			$phone = get_post_meta($post_id, '_birs_client_phone', true);
			$city = get_post_meta($post_id, '_birs_client_city', true);
			$zip = get_post_meta($post_id, '_birs_client_zip', true);
			$state = get_post_meta($post_id, '_birs_client_state', true);
			$country = get_post_meta($post_id, '_birs_client_country', true);
			if ( !$country ) {
				$country = $birchschedule->model->get_default_country();
			}
			$states = $birchpress->util->get_states();
			$countries = $birchpress->util->get_countries();
			if( isset( $states[$country] ) ) {
				$select_display = "";
				$text_display = "display:none;";
			} else {
				$select_display = "display:none;";
				$text_display = "";
			}
			ob_start();
?>
		<style type="text/css">
			.birchschedule .form-field input[type="text"],
			.birchschedule .form-field select {
				width: 25em;
			}
		</style>
		<div class="panel-wrap birchschedule">
			<table class="form-table">
				<tr class="form-field">
					<th><label><?php _e('Title', 'birchschedule'); ?> </label>
					</th>
					<td>
						<select id="birs_client_title" name="birs_client_title">
							<?php $birchpress->util->render_html_options($client_titles, $client_title); ?>
						</select>
						<input type="hidden" name="birs_client_fields[]" value="_birs_client_title" />
					</td>
				</tr>
				<tr class="form-field">
					<th><label><?php _e('First Name', 'birchschedule'); ?> </label>
					</th>
					<td><input type="text" name="birs_client_name_first" id="birs_client_name_first" value="<?php echo esc_attr($first_name); ?>">
					<input type="hidden" name="birs_client_fields[]" value="_birs_client_name_first" />
					</td>
				</tr>
				<tr class="form-field">
					<th><label><?php _e('Last Name', 'birchschedule'); ?> </label>
					</th>
					<td><input type="text" name="birs_client_name_last" id="birs_client_name_last" value="<?php echo esc_attr($last_name); ?>">
					<input type="hidden" name="birs_client_fields[]" value="_birs_client_name_last" />
					</td>
				</tr>
				<tr class="form-field">
<th><label><?php _e('Phone Number', 'birchschedule'); ?> </label>
                    </th>
                    <td>
                        <input type="text" name="birs_client_phone"
                               id="birs_client_phone" value="<?php echo esc_attr($phone); ?>">
                        <input type="hidden" name="birs_client_fields[]" value="_birs_client_phone" />
                    </td>
                </tr>
                <tr class="form-field">
                    <th><label><?php _e('Email', 'birchschedule'); ?> </label>
                    </th>
                    <td><input type="text" name="birs_client_email"
                               id="birs_client_email" value="<?php echo esc_attr($email); ?>">
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_email" />
                    </td>
                </tr>
                <tr class="form-field">
                    <th><label><?php _e('Address', 'birchschedule'); ?> </label>
                    </th>
                    <td><input type="text" name="birs_client_address1"
                               id="birs_client_address1"
                               value="<?php echo esc_attr($addresss1); ?>"> <br> <input type="text"
                               name="birs_client_address2" id="birs_client_address2"
                               value="<?php echo esc_attr($addresss2); ?>">
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_address1" />
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_address2" />
                    </td>
                </tr>
                <tr class="form-field">
                    <th><label><?php _e('City', 'birchschedule'); ?> </label>
                    </th>
                    <td><input type="text" name="birs_client_city"
                               id="birs_client_city" value="<?php echo esc_attr($city); ?>">
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_city" />
                    </td>
                </tr>
                <tr class="form-field">
                    <th><label><?php _e('State/Province', 'birchschedule'); ?> </label>
                    </th>
                    <td>
                        <select name="birs_client_state_select" id ="birs_client_state_select" style="<?php echo $select_display; ?>">
<?php
			if(isset($states[$country])) {
				$birchpress->util->render_html_options($states[$country], $state);
			}
?>
                        </select>
                        <input type="text" name="birs_client_state" id="birs_client_state" value="<?php echo esc_attr($state); ?>" style="<?php echo $text_display; ?>" />
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_state" />
                    </td>
                </tr>
                <tr class="form-field">
                    <th><label><?php _e('Country', 'birchschedule'); ?></label></th>
                    <td>
                        <select name="birs_client_country" id="birs_client_country">
                            <?php $birchpress->util->render_html_options($countries, $country); ?>
                        </select>
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_country" />
                    </td>
                </tr>
                <tr class="form-field">
                    <th><label><?php _e('Zip Code', 'birchschedule'); ?> </label>
                    </th>
                    <td><input type="text" name="birs_client_zip"
                               id="birs_client_zip" value="<?php echo esc_attr($zip); ?>">
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_zip" />
                    </td>
                </tr>
            </table>
        </div>
<?php
			return ob_get_clean();
		};

		$ns->render_client_info = function($post) {
			global $birchschedule;

			$birchschedule->view->render_errors();
			echo $birchschedule->view->clients->get_client_details_html($post->ID);
		};

	} );
