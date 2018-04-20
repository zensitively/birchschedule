<?php

birch_ns( 'birchschedule.eadmin', function( $ns ) {

		global $birchschedule;

		$ns->init = function() use( $ns ) {
				add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );

				add_action( 'birchschedule_view_register_common_scripts_after',
					array( $ns, 'register_scripts' ) );
			};

		$ns->wp_admin_init = function() use( $ns ) {
				add_action( 'birchschedule_view_appointments_new_enqueue_scripts_post_new_after',
					array( $ns, 'enqueue_scripts' ) );

				add_action( 'birchschedule_view_appointments_edit_enqueue_scripts_post_edit_after',
					array( $ns, 'enqueue_scripts' ) );

				add_action( 'wp_ajax_birchschedule_eadmin_load_selected_client',
					array( $ns, 'ajax_load_selected_client' ) );

				add_action( 'wp_ajax_birchschedule_eadmin_search_clients',
					array( $ns, 'ajax_search_clients' ) );

				add_action( 'birchschedule_view_appointments_new_render_client_info_header_after',
					array( $ns, 'render_client_selector' ), 20 );

				add_action( 'birchschedule_gbooking_render_client_info_header_after',
					array( $ns, 'render_client_selector' ), 20 );

				add_action( 'birchschedule_view_appointments_edit_add_meta_boxes_after',
					array( $ns, 'add_metabox_change_duration' ) );

				add_action( 'wp_ajax_birchschedule_eadmin_change_appointment_duration',
					array( $ns, 'ajax_change_appointment_duration' ) );

				add_filter( 'birchschedule_model_booking_get_appointment_title',
					array( $ns, 'get_calendar_appointment_title' ), 20, 2 );
			};

		$ns->register_scripts = function() use( $ns, $birchschedule ) {
				$version = $birchschedule->get_product_version();

				wp_register_script( 'birchschedule_eadmin',
					$birchschedule->plugin_url() .
					'/modules/eadmin/assets/js/base.js',
					array( 'jquery-ui-autocomplete' ), "$version" );
			};

		$ns->enqueue_scripts = function() use( $ns, $birchschedule ) {
				$birchschedule->view->enqueue_scripts(
					array(
						'birchschedule_eadmin'
					)
				);
			};

		$ns->title_like_where = function( $where ) use( $ns, $birchschedule ) {
				global $wpdb, $wp_version;

				if ( isset( $_REQUEST['term'] ) ) {
					$post_title_like = $_REQUEST['term'];
					if ( version_compare( $wp_version, '4.0' ) < 0 ) {
						$escaped_title_like = like_escape( $post_title_like );
					} else {
						$escaped_title_like = $wpdb->esc_like( $post_title_like );
					}
					$where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . esc_sql( $escaped_title_like ) . '%\'';
				}
				return $where;
			};

		$ns->ajax_load_selected_client = function() use( $ns, $birchschedule ) {
				if ( isset( $_REQUEST['birs_client_id'] ) ) {
					$client_id = $_REQUEST['birs_client_id'];
				} else {
					$client_id = 0;
				}
				echo $birchschedule->view->appointments->edit->clientlist->edit->get_client_info_html( $client_id );
?>
        <script type="text/javascript">
            jQuery(function($) {
                birchschedule.eadmin.initClientInfo();
            });
        </script>
        <?php
				exit;
			};

		$ns->ajax_search_clients = function() use( $ns, $birchschedule ) {
				add_filter( 'posts_where',
					array( $ns, 'title_like_where' ), 10 );
				$clients = $birchschedule->model->query(
					array(
						'post_type' => 'birs_client',
						'orderby'=>'title',
						'order'=>'asc'
					),
					array(
						'base_keys' => array( 'post_title' ),
						'meta_keys' => array(
							'_birs_client_name_first', '_birs_client_name_last'
						)
					)
				);
				remove_filter( 'posts_where',
					array( $ns, 'title_like_where' ), 10 );

				$results = array();
				foreach ( $clients as $client ) {
					$el = array(
						'id' => $client['ID'],
						'label' => $client['post_title'],
						'value' => $client['post_title']
					);
					$results[] = $el;
				}
				$success = array(
					'code' => 'success',
					'message' => json_encode( $results )
				);
				$birchschedule->view->render_ajax_success_message( $success );
			};

		$ns->render_client_selector = function() use( $ns, $birchschedule ) {
				$ui_anim_url = $birchschedule->plugin_url() . "/assets/images/ui-anim_basic_16x16.gif";
				$placeholder = __( 'Search for an existing client', 'birchschedule' );
?>
        <ul>
            <li class="birs_form_field">
                <label>&nbsp;</label>
                <div class="birs_field_content">
                    <input id="birs_client_selector" type="text" placeholder="<?php echo $placeholder; ?>">
                </div>
            </li>
        </ul>
        <style type="text/css">
            .ui-autocomplete-loading {
                background: white url('<?php echo $ui_anim_url; ?>') right center no-repeat;
            }
            .ui-autocomplete {
                max-height: 100px;
                overflow-y: auto;
                /* prevent horizontal scrollbar */
                overflow-x: hidden;
            }
        </style>
        <?php
			};

		$ns->add_metabox_change_duration = function() use( $ns ) {
				add_meta_box( 'birs_metabox_appointment_change_duration', __( 'Duration', 'birchschedule' ),
					array( $ns, 'render_change_appointment_duration' ), 'birs_appointment', 'side', 'high' );
			};

		$ns->ajax_change_appointment_duration = function() use( $ns, $birchschedule ) {
				$appointment = array(
					'post_type' => 'birs_appointment'
				);
				$appointment['_birs_appointment_duration'] = $_POST['birs_appointment_duration'];
				$appointment['ID'] = $_POST['birs_appointment_id'];
				$birchschedule->model->save( $appointment, array(
						'meta_keys' => array( '_birs_appointment_duration' )
					) );
				$birchschedule->view->render_ajax_success_message(
					array(
						'code' => 'success',
						'message' => ''
					)
				);
			};

		$ns->render_change_appointment_duration = function( $post ) use( $ns, $birchschedule ) {
				$appointment = $birchschedule->model->get( $post->ID, array(
						'meta_keys' => array( '_birs_appointment_duration' )
					) );
				$duration = $appointment['_birs_appointment_duration'];
?>
        <ul>
            <li class="birs_form_field">
                <div class="birs_field_content">
                    <input type="text" name="birs_appointment_duration"
                        id="birs_appointment_duration"
                        value="<?php echo $duration; ?>"
                        style="width:60%;" />
                    <?php _e( 'minutes', 'birchschedule' ); ?>
                </div>
            </li>
            <li class="birs_form_field">
                <div class="birs_field_content">
                    <input type="button" class="button-primary"
                        id="birs_appointment_actions_change_duration"
                        name="birs_appointment_actions_change_duration"
                        value="<?php _e( 'Change', 'birchschedule' ); ?>" />
                </div>
            </li>
        </ul>
        <?php
			};

		$ns->get_calendar_appointment_title_template = function() {
				return false;
			};

		$ns->get_calendar_appointment_title = function( $appointment_title, $appointment ) use ( $ns, $birchschedule ) {
				$template = $ns->get_calendar_appointment_title_template();
				if ( $template === false ) {
					return $appointment_title;
				}
				$seperator = "\n";

				$description = '';

				$appointment1on1s = $appointment['appointment1on1s'];
				$index = 0;
				foreach ( $appointment1on1s as $appointment1on1 ) {
					$appointment1on1_values =
					$birchschedule->model->mergefields->get_appointment1on1_merge_values( $appointment1on1['ID'] );
					$appointment1on1_description = $birchschedule->model->mergefields->apply_merge_fields( $template, $appointment1on1_values );
					if ( $index !== 0 ) {
						$description .= $seperator . $appointment1on1_description;
					} else {
						$description .= $appointment1on1_description;
					}
					$index++;
				}
				return $description;
			};

	} );
