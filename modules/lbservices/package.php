<?php

birch_ns( 'birchschedule.lbservices', function( $ns ) {

		global $birchschedule;

		$ns->init = function() use( $ns ) {

			add_action( 'init', array( $ns, 'wp_init' ) );

			add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );

			add_filter( 'birchschedule_model_cpt_service_post_get',
				array( $ns, 'post_get_service' ) );

			add_filter( 'birchschedule_model_cpt_service_pre_save',
				array( $ns, 'pre_save_service' ), 20, 3 );

			add_filter( 'birchschedule_model_get_locations_services_map',
				array( $ns, 'get_locations_services_map' ), 20 );

			add_filter( 'birchschedule_model_get_services_locations_map',
				array( $ns, 'get_services_locations_map' ), 20 );

		};

        $ns->is_module_lbservices = function( $module ) {
            return $module['module'] === 'lbservices';
        };

		$ns->wp_init = function() {};

		$ns->wp_admin_init = function() use( $ns ) {

			add_action( 'birchschedule_view_services_save_post_after',
				array( $ns, 'save_service_data' ) );

			add_action( 'birchschedule_view_services_load_page_edit_after',
				array( $ns, 'add_meta_boxes' ) );

		};

		$ns->add_meta_boxes = function() use( $ns ) {

			add_meta_box( 'birchschedule-service-locations', __( 'Locations', 'birchschedule' ),
				array( $ns, 'render_service_location' ), 'birs_service', 'side', 'default' );
		};

		$ns->pre_save_service = function( $service, $service_orig, $config ) use( $ns ) {

			if ( isset( $service['_birs_service_assigned_locations'] ) ) {
				$service['_birs_service_assigned_locations'] =
				serialize( $service['_birs_service_assigned_locations'] );
			}
			return $service;
		};

		$ns->post_get_service = function( $service ) {

			if ( isset( $service['_birs_service_assigned_locations'] ) ) {
				$service['_birs_service_assigned_locations'] =
				unserialize( $service['_birs_service_assigned_locations'] );
				if ( !$service['_birs_service_assigned_locations'] ) {
					$service['_birs_service_assigned_locations'] = array();
				}
			}
			return $service;
		};

		$ns->get_services_locations_map = function( $map ) use( $ns, $birchschedule ) {

			$map = array();
			$services = $birchschedule->model->query(
				array(
					'post_type' => 'birs_service'
				),
				array(
					'base_keys' => array(),
					'meta_keys' => array(
						'_birs_service_assigned_locations'
					)
				)
			);
			foreach ( $services as $service ) {
				$assigned_locations = $service['_birs_service_assigned_locations'];
				if ( $assigned_locations ) {
					$locations_map = array();
					foreach ( $assigned_locations as $location_id => $value ) {
						$location = $birchschedule->model->get( $location_id, array(
								'base_keys' => array( 'post_title' ),
								'meta_keys' => array()
							) );
						if ( $location ) {
							$locations_map[$location_id] = $location['post_title'];
						}
					}
					$map[$service['ID']] = $locations_map;
				} else {
					$map[$service['ID']] = array();
				}
			}
			return $map;
		};

		$ns->get_locations_services_map = function( $map ) use( $ns, $birchschedule ) {

			$map = array();
			$locations = $birchschedule->model->query(
				array(
					'post_type' => 'birs_location'
				),
				array(
					'meta_keys' => array(),
					'base_keys' => array()
				)
			);
			foreach ( $locations as $location ) {
				$services = $birchschedule->model->query(
					array(
						'post_type' => 'birs_service',
						'order' => 'ASC',
						'orderby' => 'title'
					),
					array(
						'meta_keys' => array(
							'_birs_service_assigned_locations'
						),
						'base_keys' => array(
							'post_title'
						)
					)
				);
				$assigned_services = array();
				foreach ( $services as $service ) {
					$assigned_locations = $service['_birs_service_assigned_locations'];
					if ( $assigned_locations ) {
						if ( isset( $assigned_locations[$location['ID']] ) ) {
							$assigned_services[$service['ID']] = $service['post_title'];
						}
					}
				}
				$map[$location['ID']] = $assigned_services;
			}
			return $map;
		};

		$ns->save_service_data = function( $post ) use( $ns, $birchschedule ) {
			if ( isset( $_POST['birs_service_assigned_locations'] ) ) {
				$assigned_locations = $_POST['birs_service_assigned_locations'];
			} else {
				$assigned_locations = array();
			}
			$assigned_locations = serialize( $assigned_locations );
			update_post_meta( $post['ID'], '_birs_service_assigned_locations', $assigned_locations );
		};

		$ns->render_location_checkboxes = function( $locations, $assigned_locations ) {
			foreach ( $locations as $location ) {
				if ( array_key_exists( $location->ID, $assigned_locations ) ) {
					$checked = 'checked="checked"';
				} else {
					$checked = '';
				}
				echo '<li><label>' .
				"<input type=\"checkbox\" " .
				"name=\"birs_service_assigned_locations[$location->ID]\" $checked >" .
				$location->post_title .
				'</label></li>';
			}
		};

		$ns->render_service_location = function( $post ) use( $ns, $birchschedule ) {
			$locations = get_posts(
				array(
					'post_type' => 'birs_location',
					'nopaging' => true
				)
			);
			$assigned_locations = get_post_meta( $post->ID, '_birs_service_assigned_locations', true );
			$assigned_locations = unserialize( $assigned_locations );
			if ( $assigned_locations === false ) {
				$assigned_locations = array();
			}
?>
        <div class="panel-wrap birchschedule">
            <?php if ( sizeof( $locations ) > 0 ): ?>
                <p><?php _e( 'Assign locations that can perform this service:', 'birchschedule' ); ?></p>
                <div><ul>
                        <?php $ns->render_location_checkboxes( $locations, $assigned_locations ); ?>
                    </ul></div>
            <?php else: ?>
                <p>
                    <?php
				printf( __( 'There is no location to assign. Click %s here %s to add one.', 'birchschedule' ), '<a
                        href="post-new.php?post_type=birs_location">', '</a>' );
?>
                </p>
            <?php endif; ?>
        </div>
        <?php
		};

	} );
