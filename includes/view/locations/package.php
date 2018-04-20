<?php

birch_ns( 'birchschedule.view.locations', function( $ns ) {

		global $birchschedule;

		$ns->init = function() use( $ns, $birchschedule ) {
			add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );

			add_action( 'init', array( $ns, 'wp_init' ) );

			$birchschedule->view->load_page_edit->when( $ns->is_post_type_location, $ns->load_page_edit );

			$birchschedule->view->enqueue_scripts_edit->when( $ns->is_post_type_location, $ns->enqueue_scripts_edit );

			$birchschedule->view->enqueue_scripts_list->when( $ns->is_post_type_location, $ns->enqueue_scripts_list );

			$birchschedule->view->save_post->when( $ns->is_post_type_location, $ns->save_post );
		};

		$ns->wp_init = function() use ( $ns ) {
			$ns->register_post_type();
		};

		$ns->wp_admin_init = function() use ( $ns ) {
			add_filter( 'manage_edit-birs_location_columns', array( $ns, 'get_edit_columns' ) );
			add_action( 'manage_birs_location_posts_custom_column', array( $ns, 'render_custom_columns' ), 2 );
		};

		$ns->is_post_type_location = function( $post ) {
			return $post['post_type'] === 'birs_location';
		};

		$ns->get_register_options = function() {
			return array(
				'labels' => array(
					'name' => __( 'Locations', 'birchschedule' ),
					'singular_name' => __( 'Location', 'birchschedule' ),
					'add_new' => __( 'Add Location', 'birchschedule' ),
					'add_new_item' => __( 'Add New Location', 'birchschedule' ),
					'edit' => __( 'Edit', 'birchschedule' ),
					'edit_item' => __( 'Edit Location', 'birchschedule' ),
					'new_item' => __( 'New Location', 'birchschedule' ),
					'view' => __( 'View Location', 'birchschedule' ),
					'view_item' => __( 'View Location', 'birchschedule' ),
					'search_items' => __( 'Search Locations', 'birchschedule' ),
					'not_found' => __( 'No Locations found', 'birchschedule' ),
					'not_found_in_trash' => __( 'No Locations found in trash', 'birchschedule' ),
					'parent' => __( 'Parent Location', 'birchschedule' )
				),
				'description' => __( 'This is where locations are stored.', 'birchschedule' ),
				'public' => false,
				'show_ui' => true,
				'capability_type' => 'birs_location',
				'map_meta_cap' => true,
				'publicly_queryable' => false,
				'exclude_from_search' => true,
				'show_in_menu' => 'edit.php?post_type=birs_appointment',
				'hierarchical' => false,
				'show_in_nav_menus' => false,
				'rewrite' => false,
				'query_var' => true,
				'supports' => array( 'title' ),
				'has_archive' => false
			);
		};

		$ns->register_post_type = function() use( $ns ) {
			register_post_type( 'birs_location', $ns->get_register_options() );
		};

		$ns->get_edit_columns = function( $columns ) {
			$columns = array();

			$columns["cb"] = "<input type=\"checkbox\" />";
			$columns["title"] = __( "Location Name", 'birchschedule' );
			$columns["birs_location_address"] = __( "Address", 'birchschedule' );
			$columns["birs_location_city"] = __( "City", 'birchschedule' );
			$columns["birs_location_state"] = __( "State/Province", 'birchschedule' );
			return $columns;
		};

		$ns->get_updated_messages = function( $messages ) {
			global $post, $post_ID;

			$messages['birs_location'] = array(
				0 => '', // Unused. Messages start at index 1.
				1 => __( 'Location updated.', 'birchschedule' ),
				2 => __( 'Custom field updated.', 'birchschedule' ),
				3 => __( 'Custom field deleted.', 'birchschedule' ),
				4 => __( 'Location updated.', 'birchschedule' ),
				5 => isset( $_GET['revision'] ) ? sprintf( __( 'Location restored to revision from %s', 'birchschedule' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
				6 => __( 'Location updated.', 'birchschedule' ),
				7 => __( 'Location saved.', 'birchschedule' ),
				8 => __( 'Location submitted.', 'birchschedule' ),
				9 => sprintf( __( 'Location scheduled for: <strong>%1$s</strong>.', 'birchschedule' ), date_i18n( 'M j, Y @ G:i', strtotime( $post->post_date ) ) ),
				10 => __( 'Location draft updated.', 'birchschedule' )
			);

			return $messages;
		};

		$ns->render_custom_columns = function( $column ) {
			global $post, $birchpress;

			if ( $column === 'birs_location_address' ) {
				$address1 = get_post_meta( $post->ID, '_birs_location_address1', true );
				$address2 = get_post_meta( $post->ID, '_birs_location_address2', true );
				$value = $address1 . '<br>' . $address2;
			} else {
				$value = get_post_meta( $post->ID, '_' . $column, true );
			}

			if ( $column === 'birs_location_state' ) {
				$country = get_post_meta( $post->ID, '_birs_location_country', true );
				$all_states = $birchpress->util->get_states();
				$value = get_post_meta( $post->ID, '_birs_location_state', true );
				if ( isset( $all_states[$country] ) && isset( $all_states[$country][$value] ) ) {
					$value = $all_states[$country][$value];
				}
			}
			echo $value;
		};

		$ns->load_page_edit = function( $arg ) use( $ns ) {
			add_action( 'add_meta_boxes', array( $ns, 'add_meta_boxes' ) );
			add_filter( 'post_updated_messages', array( $ns, 'get_updated_messages' ) );
		};

		$ns->enqueue_scripts_edit = function( $arg ) use ( $ns ) {
			global $birchschedule;

			$birchschedule->view->register_3rd_scripts();
			$birchschedule->view->register_3rd_styles();
			$birchschedule->view->enqueue_scripts(
				array(
					'birchschedule_view_locations_edit', 'birchschedule_model',
					'birchschedule_view_admincommon', 'birchschedule_view'
				)
			);
			$birchschedule->view->enqueue_styles( array( 'birchschedule_admincommon', 'birchschedule_locations_edit' ) );
		};

		$ns->enqueue_scripts_list = function( $arg ) use ( $ns, $birchschedule ) {
			$birchschedule->view->enqueue_styles( 'birchschedule_admincommon' );
		};

		$ns->save_post = function( $post ) use ( $ns ) {
			global $birchschedule;

			$config = array(
				'meta_keys' => array(
					'_birs_location_phone', '_birs_location_address1',
					'_birs_location_address2', '_birs_location_city',
					'_birs_location_state', '_birs_location_country',
					'_birs_location_zip'
				),
				'base_keys' => array()
			);
			$post_data = $birchschedule->view->merge_request( $post, $config, $_REQUEST );
			$birchschedule->model->save( $post_data, $config );
		};

		$ns->add_meta_boxes = function() use ( $ns ) {
			remove_meta_box( 'slugdiv', 'birs_location', 'normal' );
			remove_meta_box( 'postcustom', 'birs_location', 'normal' );
			add_meta_box( 'birchschedule-location-info', __( 'Location Details', 'birchschedule' ),
				array( $ns, 'render_location_info' ), 'birs_location', 'normal', 'high' );
		};

		$ns->render_location_info = function( $post ) use ( $ns ) {
			global $birchpress, $birchschedule;

			$post_id = $post->ID;
			$addresss1 = get_post_meta( $post_id, '_birs_location_address1', true );
			$addresss2 = get_post_meta( $post_id, '_birs_location_address2', true );
			$phone = get_post_meta( $post_id, '_birs_location_phone', true );
			$city = get_post_meta( $post_id, '_birs_location_city', true );
			$zip = get_post_meta( $post_id, '_birs_location_zip', true );
			$state = get_post_meta( $post_id, '_birs_location_state', true );
			$country = get_post_meta( $post_id, '_birs_location_country', true );
			if ( !$country ) {
				$country = $birchschedule->model->get_default_country();
			}
			$countries = $birchpress->util->get_countries();
			$all_states = $birchpress->util->get_states();
			if ( isset( $all_states[$country] ) ) {
				$select_display = "";
				$text_display = "display:none;";
				$states = $all_states[$country];
			} else {
				$select_display = "display:none;";
				$text_display = "";
				$states = array();
			}
?>
        <div class="panel-wrap birchschedule">
            <table class="form-table">
                <tr class="form-field">
                    <th><label><?php _e( 'Phone Number', 'birchschedule' ); ?> </label>
                    </th>
                    <td><input type="text" name="birs_location_phone"
                               id="birs_location_phone" value="<?php echo esc_attr( $phone ); ?>">
                    </td>
                </tr>
                <tr class="form-field">
                    <th><label><?php _e( 'Address', 'birchschedule' ); ?> </label>
                    </th>
                    <td><input type="text" name="birs_location_address1"
                               id="birs_location_address1"
                               value="<?php echo esc_attr( $addresss1 ); ?>"> <br> <input type="text"
                               name="birs_location_address2" id="birs_location_address2"
                               value="<?php echo esc_attr( $addresss2 ); ?>">
                    </td>
                </tr>
                <tr class="form-field">
                    <th><label><?php _e( 'City', 'birchschedule' ); ?> </label>
                    </th>
                    <td><input type="text" name="birs_location_city"
                               id="birs_location_city" value="<?php echo esc_attr( $city ); ?>">
                    </td>
                </tr>
                <tr class="form-field">
                    <th><label><?php _e( 'State/Province', 'birchschedule' ); ?> </label>
                    </th>
                    <td>
                        <select name="birs_location_state_select" id="birs_location_state_select" style="<?php echo $select_display; ?>">
                            <?php $birchpress->util->render_html_options( $states, $state ); ?>
                        </select>
                        <input type="text" name="birs_location_state" id="birs_location_state" value="<?php echo esc_attr( $state ); ?>" style="<?php echo $text_display; ?>">
                    </td>
                </tr>
                <tr class="form-field">
                    <th><label><?php _e( 'Country', 'birchschedule' ); ?></label></th>
                    <td>
                        <select name="birs_location_country" id="birs_location_country">
                            <?php $birchpress->util->render_html_options( $countries, $country ); ?>
                        </select>
                    </td>
                </tr>
                <tr class="form-field">
                    <th><label><?php _e( 'Zip Code', 'birchschedule' ); ?> </label>
                    </th>
                    <td><input type="text" name="birs_location_zip"
                               id="birs_location_zip" value="<?php echo esc_attr( $zip ); ?>">
                    </td>
                </tr>
            </table>
        </div>
<?php
		};

	} );
