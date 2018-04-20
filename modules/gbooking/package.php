<?php

birch_ns( 'birchschedule.gbooking', function( $ns ) {

		global $birchschedule;

		$ns->init = function() use ( $ns ) {
				add_action( 'init', array( $ns, 'wp_init' ) );
				add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );

				add_action( 'birchschedule_view_register_common_scripts_after',
					array( $ns, 'register_scripts' ) );

				add_filter( 'birchschedule_model_get_service_capacity',
					array( $ns, 'get_service_capacity' ), 20, 2 );

			};

		$ns->wp_init = function() {

			};

		$ns->wp_admin_init = function() use ( $ns ) {

				add_action( 'birchschedule_view_services_render_service_info_after',
					array( $ns, 'render_gbooking_settings' ), 15 );

				add_action( 'birchschedule_view_appointments_edit_enqueue_scripts_post_edit_after',
					array( $ns, 'enqueue_scripts' ) );

				add_action( 'wp_ajax_birchschedule_gbooking_add_client',
					array( $ns, 'ajax_add_client' ) );

				add_action( 'wp_ajax_birchschedule_gbooking_change_appointment_capacity',
					array( $ns, 'ajax_change_appointment_capacity' ) );

				add_action( 'birchschedule_view_appointments_edit_add_meta_boxes_after',
					array( $ns, 'add_meta_boxes' ) );

				add_action( 'birchschedule_view_appointments_edit_clientlist_render_clients_after',
					array( $ns, 'render_actions_add_client' ) );

				add_action( 'birchschedule_view_services_save_post_after',
					array( $ns, 'save_service_capacity' ) );

			};

		$ns->add_meta_boxes = function() use ( $ns ) {
				add_meta_box( 'birs_metabox_appointment_change_capacity', __( 'Capacity', 'birchschedule' ),
					array( $ns, 'render_change_appointment_capacity' ), 'birs_appointment', 'side', 'high' );
			};

		$ns->register_scripts = function() use ( $ns, $birchschedule ) {
				$version = $birchschedule->get_product_version();

				wp_register_script( 'birchschedule_gbooking',
					$birchschedule->plugin_url() .
					'/modules/gbooking/assets/js/base.js',
					array( 'birchschedule_view', 'birchschedule_model' ), "$version" );
			};

		$ns->enqueue_scripts = function() use ( $birchschedule ) {
				$birchschedule->view->enqueue_scripts(
					array( 'birchschedule_gbooking' )
				);
			};

		$ns->ajax_change_appointment_capacity = function() use ( $ns, $birchschedule ) {
				$appointment = array(
					'post_type' => 'birs_appointment'
				);
				$appointment['_birs_appointment_capacity'] = $_POST['birs_appointment_capacity'];
				$appointment['ID'] = $_POST['birs_appointment_id'];
				$birchschedule->model->save( $appointment, array(
						'meta_keys' => array( '_birs_appointment_capacity' )
					) );
				$birchschedule->view->render_ajax_success_message(
					array(
						'code' => 'success',
						'message' => ''
					)
				);
			};

		$ns->ajax_add_client = function() use ( $ns, $birchschedule ) {
				global $birchpress;

				$errors = array();
				$client_errors = $ns->validate_client_info();
				$appointment1on1_errors = $ns->validate_appointment1on1_info();

				$errors = array_merge( $client_errors, $appointment1on1_errors );

				if ( $errors ) {
					$birchschedule->view->render_ajax_error_messages( $errors );
				}
				$client_config = array(
					'base_keys' => array(),
					'meta_keys' => $_POST['birs_client_fields']
				);
				$client_info = $birchschedule->view->merge_request( array(), $client_config, $_POST );
				unset( $client_info['ID'] );
				$client_id = $birchschedule->model->booking->save_client( $client_info );
				$appointment1on1_config = array(
					'base_keys' => array(),
					'meta_keys' => array_merge(
						$birchschedule->model->get_appointment1on1_fields(),
						$birchschedule->model->get_appointment1on1_custom_fields()
					)
				);
				$appointment1on1_info =
				$birchschedule->view->merge_request( array(), $appointment1on1_config, $_POST );
				$appointment1on1_info['_birs_client_id'] = $client_id;
				unset( $appointment1on1_info['ID'] );
				$appointment1on1_id = $birchschedule->model->booking->make_appointment1on1( $appointment1on1_info );
				$birchschedule->model->booking->change_appointment1on1_status( $appointment1on1_id, 'publish' );
				$birchschedule->view->render_ajax_success_message( array(
						'code' => 'success',
						'message' => ''
					) );
			};

		$ns->save_service_capacity = function( $post ) use ( $ns, $birchschedule ) {
				if ( isset( $_POST['birs_service_capacity'] ) ) {
					$capacity = intval( $_POST['birs_service_capacity'] );
					if ( $capacity < 1 ) {
						$capacity = 1;
					}
				} else {
					$capacity = 1;
				}
				update_post_meta( $post['ID'], '_birs_service_capacity',
					$capacity );
			};

		$ns->get_service_capacity = function( $capacity, $service_id ) use ( $ns, $birchschedule ) {
				$service = $birchschedule->model->get( $service_id, array(
						'meta_keys' => array( '_birs_service_capacity' )
					) );
				if ( isset( $service['_birs_service_capacity'] ) ) {
					$capacity = intval( $service['_birs_service_capacity'] );
					if ( $capacity < 1 ) {
						$capacity = 1;
					}
				} else {
					$capacity = 1;
				}
				return $capacity;
			};

		$ns->render_gbooking_settings = function( $post ) use ( $ns, $birchschedule ) {
				$capacity = $birchschedule->model->get_service_capacity( $post->ID );
?>
        <div class="panel-wrap birchschedule">
            <table class="form-table">
                <tr class="form-field">
                    <th><label><?php _e( 'Capacity', 'birchschedule' ); ?> </label>
                    </th>
                    <td>
                        <input type="text" name="birs_service_capacity"
                               id="birs_service_capacity"
                               value="<?php echo $capacity; ?>">
                    </td>
                </tr>
            </table>
        </div>
        <?php

			};

		$ns->render_change_appointment_capacity = function( $post ) use ( $ns, $birchschedule ) {
				$capacity = $birchschedule->model->booking->get_appointment_capacity( $post->ID );
?>
        <ul>
            <li class="birs_form_field">
                <div class="birs_field_content">
                    <input type="text" name="birs_appointment_capacity"
                        id="birs_appointment_capacity"
                        value="<?php echo $capacity; ?>" />
                </div>
            </li>
            <li class="birs_form_field">
                <div class="birs_field_content">
                    <input type="button" class="button-primary"
                        id="birs_appointment_actions_change_capacity"
                        name="birs_appointment_actions_change_capacity"
                        value="<?php _e( 'Change', 'birchschedule' ); ?>" />
                </div>
            </li>
        </ul>
        <?php

			};

		$ns->render_actions_add_client = function() use ($ns){
			$add_client_html = $ns->get_add_client_html();
?>
        <div>
            <a href="javascript:void(0);" id="birs_appointment_actions_add_client">
                <?php _e( '+ Add Client', 'birchschedule' ); ?>
            </a>
        </div>
        <div id="birs_appointment_add_client_form" data-add-client-html="<?php echo esc_attr($add_client_html); ?>">
        </div>
        <?php
			};

		$ns->render_client_info_header = function() {
?>
        <h3 class="birs_section"><?php _e( '+ Add Client', 'birchschedule' ); ?></h3>
        <?php
			};

		$ns->get_client_info_html = function() use ( $birchschedule ) {
				return $birchschedule->view->appointments->edit->clientlist->edit->get_client_info_html( 0 );
			};

		$ns->get_appointment1on1_info_html = function() use ( $birchschedule ) {
				return $birchschedule->view->appointments->edit->clientlist->edit->get_appointment1on1_info_html( 0, 0 );
			};


		$ns->get_add_client_html = function() use ( $ns, $birchschedule ) {
				ob_start();
				$ns->render_client_info_header();
?>
        <div id="birs_client_info_container">
            <?php echo $ns->get_client_info_html(); ?>
        </div>
        <h3 class="birs_section"><?php _e( 'Additional Info', 'birchschedule' ); ?></h3>
        <?php
				echo $ns->get_appointment1on1_info_html();
?>
        <ul>
            <li class="birs_form_field birs_please_wait" style="display:none;">
                <label>
                    &nbsp;
                </label>
                <div class="birs_field_content">
                    <div><?php _e( 'Please wait...', 'birchschedule' ); ?></div>
                </div>
            </li>
            <li class="birs_form_field">
                <label>
                    &nbsp;
                </label>
                <div class="birs_field_content">
                    <input type="button" class="button-primary"
                        id="birs_appointment_actions_add_client_save"
                        name="birs_appointment_actions_add_client_save"
                        value="<?php _e( 'Save', 'birchschedule' ); ?>" />
                    <a href="javascript:void(0);"
                        id="birs_appointment_actions_add_client_cancel"
                        style="padding: 4px 0 0 4px; display: inline-block;">
                        <?php _e( 'Cancel', 'birchschedule' ); ?>
                    </a>
                </div>
            </li>
        </ul>
        <script type="text/javascript">
            jQuery(function($) {
                birchschedule.gbooking.initAddClientForm();
            });
        </script>
        <?php
				return ob_get_clean();
			};

		$ns->validate_client_info = function() use ( $birchschedule ) {
				return $birchschedule->view->appointments->new->validate_client_info();
			};

		$ns->validate_appointment1on1_info = function() use ( $birchschedule ) {
				return $birchschedule->view->appointments->new->validate_appointment1on1_info();
			};

		$ns->add_client = function() use ( $ns, $birchschedule ) {
				global $birchpress;

				$errors = array();
				$client_errors = $ns->validate_client_info();
				$apt_ext_errors = $ns->validate_appointment1on1_info();

				$errors = array_merge( $client_errors, $apt_ext_errors );

				if ( $errors ) {
					return $birchpress->util->to_wp_error( $errors );
				}

				$fields = $birchschedule->model->get_client_fields();
				$config = array(
					'meta_keys' => $fields,
					'base_keys' => array(
						'post_title'
					)
				);
				$email = $_REQUEST['birs_client_email'];
				$client = $birchschedule->model->get_client_by_email( $email, $config );
				if ( !$client ) {
					$client = array();
				}
				$client = $birchschedule->view->merge_request( $client, $config, $_REQUEST );
				$client['post_type'] = 'birs_client';
				$client_id = $birchschedule->model->save( $client, $config );

				$appointment_id = $_REQUEST['birs_appointment_id'];
				$appointment_ext = $birchschedule->model->get_appointment1on1( $appointment_id, $client_id );
				if ( $appointment_ext ) {
					if ( $appointment_ext['post_status'] == 'publish' ) {
						$errors['birs_client_email'] = __( 'This client has been added.', 'birchschedule' );
						return $birchpress->util->to_wp_error( $errors );
					}
					if ( $appointment_ext['post_status'] == 'pending' ) {
						return true;
					}
					if ( $appointment_ext['post_status'] == 'trash' ) {
						$appointment_ext['post_status'] = 'publish';
						$birchschedule->model->save( $appointment_ext, array(
								'base_keys' => array( 'post_status' ),
								'meta_keys' => array()
							) );
						return true;
					}
				} else {
					$ext_keys = $birchschedule->model->get_appointment1on1_custom_fields();
					$config = array(
						'base_keys' => array(),
						'meta_keys' => $ext_keys
					);
					$appointment_ext = $birchschedule->view->merge_request( array(), $config, $_REQUEST );
					$appointment_ext = array_merge( $appointment_ext, array(
							'_birs_appointment_client' => $client_id,
							'_birs_appointment_id' => $appointment_id,
							'post_status' => 'pending',
							'post_type' => 'birs_appointment1on1'
						) );
					$config['base_keys'] = array(
						'post_status'
					);
					$config['meta_keys'] = array_merge( $config['meta_keys'], array(
							'_birs_appointment_id', '_birs_appointment_client'
						) );
					$birchschedule->model->save( $appointment_ext, $config );
				}
			};

	} );
