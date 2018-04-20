<?php

birch_ns( 'birchschedule.acontrol', function( $ns ) {

	global $birchschedule;

	$ns->init = function() use ( $ns ) {
		add_action( 'init', array( $ns, 'wp_init' ) );
		add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );
		$ns->init_roles();
	};

	$ns->wp_admin_init = function() use ( $ns ) {
		add_filter( 'birchschedule_view_appointments_new_get_staff_listing_order',
					array( $ns, 'filter_staff_listing_order' ) );

		add_filter( 'birchschedule_view_appointments_edit_get_staff_listing_order',
					array( $ns, 'filter_staff_listing_order' ) );

		add_filter( 'birchschedule_view_appointments_new_get_services_listing_order',
					array( $ns, 'filter_services_listing_order' ) );

		add_filter( 'birchschedule_view_appointments_edit_get_services_listing_order',
					array( $ns, 'filter_services_listing_order' ) );

		add_filter( 'birchschedule_view_calendar_get_locations_listing_order',
					array( $ns, 'filter_locations_listing_order' ) );

		add_filter( 'birchschedule_view_appointments_new_get_locations_listing_order',
					array( $ns, 'filter_locations_listing_order' ) );

		add_filter( 'birchschedule_view_appointments_edit_get_locations_listing_order',
					array( $ns, 'filter_locations_listing_order' ) );

		add_filter( 'pre_get_posts',
					array( $ns, 'filter_others_for_author' ) );

		add_filter( 'birchschedule_view_appointments_edit_get_action_cancel_html',
					array( $ns, 'filter_view_appointments_edit_get_action_cancel_html' ) );

		add_filter( 'birchschedule_view_appointments_edit_clientlist_get_item_actions',
					array( $ns, 'filter_view_appointments_edit_clientlist_get_item_actions' ), 20, 2 );

		add_action( 'birchschedule_view_staff_save_post_after',
					array( $ns, 'set_appointments_authors' ) );

		add_action( 'user_register',
					array( $ns, 'update_staff_settings' ) );
	};

	$ns->wp_init = function() use( $ns ) {
		add_filter( 'birchschedule_view_calendar_get_locations_staff_map',
					array( $ns, 'add_all_staff' ), 30 );
	};

	$ns->filter_locations_listing_order = function( $order ) use ( $ns, $birchschedule ) {
		if ( current_user_can( 'edit_birs_appointments' ) &&
			 current_user_can( 'edit_others_birs_appointments' ) ) {

			return $order;
		}

		if ( current_user_can( 'edit_birs_appointments' ) &&
			 !current_user_can( 'edit_others_birs_appointments' ) ) {

			$current_user = wp_get_current_user();
			$staff = $birchschedule->model->get_staff_by_user( $current_user );
			if ( $staff ) {
				foreach ( $staff as $the_staff ) {
					$new_order = array();
					foreach ( $order as $location_id ) {
						$staff_ids = $birchschedule->model->get_staff_by_location( $location_id );
						if ( isset( $staff_ids[$the_staff['ID']] ) ) {
							$new_order[] = $location_id;
						}
					}
				}
				return $new_order;
			} else {
				return array();
			}
		}
	};

	$ns->filter_services_listing_order = function( $order ) use( $ns, $birchschedule ) {
		if ( current_user_can( 'edit_birs_appointments' ) &&
			 current_user_can( 'edit_others_birs_appointments' ) ) {

			return $order;
		}

		if ( current_user_can( 'edit_birs_appointments' ) &&
			 !current_user_can( 'edit_others_birs_appointments' ) ) {

			$current_user = wp_get_current_user();
			$staff = $birchschedule->model->get_staff_by_user( $current_user );
			if ( $staff ) {
				foreach ( $staff as $the_staff ) {
					$service_ids = $birchschedule->model->get_services_by_staff( $the_staff['ID'] );
					$new_order = array();
					foreach ( $order as $service_id ) {
						if ( isset( $service_ids[$service_id] ) ) {
							$new_order[] = $service_id;
						}
					}
				}
				return $new_order;
			} else {
				return array();
			}
		}
	};

	$ns->filter_staff_listing_order = function( $order ) use( $ns, $birchschedule ) {
		if ( current_user_can( 'edit_birs_appointments' ) &&
			 current_user_can( 'edit_others_birs_appointments' ) ) {

			return $order;
		}

		if ( current_user_can( 'edit_birs_appointments' ) &&
			 !current_user_can( 'edit_others_birs_appointments' ) ) {

			$current_user = wp_get_current_user();
			$staff = $birchschedule->model->get_staff_by_user( $current_user );
			if ( $staff ) {
				$new_order = array();
				foreach ( $staff as $the_staff ) {
					$new_order[] = $the_staff['ID'];
				}
				return $new_order;
			} else {
				return array();
			}
		}
	};

	$ns->add_all_staff = function( $map ) use ( $ns, $birchschedule ) {
		global $birchschedule;

		$i18n_msgs = $birchschedule->view->get_frontend_i18n_messages();
		$new_map = array();
		foreach ( $map as $location_id => $staff ) {
			if ( current_user_can( 'edit_others_birs_appointments' ) ) {
				if ( sizeof( $staff ) > 0 ) {
					$staff[-1] = $i18n_msgs['All Providers'];
				}
				$new_map[$location_id] = $staff;
			}
			if ( current_user_can( 'edit_birs_appointments' ) &&
				 !current_user_can( 'edit_others_birs_appointments' ) ) {
				$current_user = wp_get_current_user();
				$current_user_email = $current_user->user_email;
				$new_map[$location_id] = array();
				foreach ( $staff as $staff_id => $staff_name ) {
					$the_staff = $birchschedule->model->get( $staff_id, array(
						'meta_keys' => array( '_birs_staff_email' ),
						'base_keys' => array()
					) );
					if ( $the_staff['_birs_staff_email'] === $current_user_email ) {
						$new_map[$location_id][$staff_id] = $staff_name;
					}
				}
			}
		}
		return $new_map;
	};

	$ns->filter_view_appointments_edit_get_action_cancel_html = function( $html ) {
		if ( !current_user_can( 'delete_birs_appointments' ) ||
			 !current_user_can( 'delete_published_birs_appointments' ) ) {
			return '';
		} else {
			return $html;
		}
	};

	$ns->filter_view_appointments_edit_clientlist_get_item_actions = function( $actions, $item ) {
		if ( !current_user_can( 'delete_birs_appointments' ) ||
			 !current_user_can( 'delete_published_birs_appointments' ) ) {

			unset( $actions['cancel'] );
			return $actions;
		} else {
			return $actions;
		}
	};

	$ns->filter_others_for_author = function( $query ) use( $ns, $birchschedule ) {
		global $pagenow;

		if ( 'edit.php' != $pagenow || !$query->is_admin )
			return $query;

		if ( current_user_can( 'manage_options' ) ) {
			return $query;
		}

		$post_types = $birchschedule->view->settings->get_post_types();
		if ( !isset( $query->query_vars['post_type'] ) ||
			 !in_array( $query->query_vars['post_type'], $post_types ) ) {

			return $query;
		}
		$post_type = $query->query_vars['post_type'];
		if ( current_user_can( 'edit_' . $post_type . 's' ) &&
			 !current_user_can( 'edit_others_' . $post_type . 's' ) ) {

			global $user_ID;
			$query->set( 'author', $user_ID );
		}

		return $query;
	};

	$ns->set_appointments_authors = function( $post ) use( $ns, $birchschedule ) {
		$user = $birchschedule->model->get_user_by_staff( $post['ID'] );
		if ( $user ) {
			$user_ids = array( $user->ID );
			$appointments = $birchschedule->model->query(
				array(
					'post_type' => 'birs_appointment',
					'post_status' => array( 'any' ),
					'author__not_in' => $user_ids,
					'meta_query' => array(
						array(
							'key' => '_birs_appointment_staff',
							'value' => $post['ID']
						)
					)
				),
				array(
					'base_keys' => array( 'post_author' ),
					'meta_keys' => array()
				)
			);
			foreach ( $appointments as $appointment ) {
				$appointment['post_author'] = $user->ID;
				$birchschedule->model->save( $appointment,
											 array(
												 'base_keys' => array( 'post_author' )
											 )
				);
			}
		}
	};

	$ns->update_staff_settings = function( $user_id ) use( $ns, $birchschedule ) {
		$user = get_user_by( 'id', $user_id );
		$email = $user->user_email;
		$staff = $birchschedule->model->query(
			array(
				'post_type' => 'birs_staff',
				'meta_query' => array(
					array(
						'key' => '_birs_staff_email',
						'value' => $email
					)
				)
			),
			array(
				'meta_keys' => array( '_birs_staff_email' ),
				'base_keys' => array(
					'post_title', 'post_content',
					'post_author'
				)
			)
		);
		if ( $staff ) {
			$staff = array_values( $staff );
			$thestaff = $staff[0];
			$thestaff['post_author'] = $user_id;
			$birchschedule->model->save( $thestaff,
										 array(
											 'base_keys' => array(
												 'post_author', 'post_title',
												 'post_content'
											 )
										 )
			);
			$ns->set_appointments_authors( $thestaff );
		}
	};

	$ns->init_roles = function() use( $ns, $birchschedule ) {
		global $wp_roles, $birchschedule;

		if ( class_exists( 'WP_Roles' ) )
			if ( ! isset( $wp_roles ) )
				$wp_roles = new WP_Roles();

		if ( is_object( $wp_roles ) ) {

			add_role( 'birs_staff_member', __( 'Staff Member', 'birchschedule' ), array(
				'read'                      => true,
				'edit_posts'                => true,

				'publish_birs_appointments' => true,
				'edit_birs_appointments' => true,
				'edit_published_birs_appointments' => true,
				'edit_private_birs_appointments' => true,
				'delete_birs_appointments' => true,
				'delete_published_birs_appointments' => true,
				'delete_private_birs_appointments' => true,

				'publish_birs_clients' => true,
				'edit_birs_clients' => true,
				'edit_others_birs_clients' => true,
				'edit_published_birs_clients' => true,
				'edit_private_birs_clients' => true,

				'edit_birs_staffs' => true,
				'edit_published_birs_staffs' => true
			) );

			add_role( 'birs_business_manager', __( 'Business Manager', 'birchschedule' ), array(
				'level_9'                => true,
				'level_8'                => true,
				'level_7'                => true,
				'level_6'                => true,
				'level_5'                => true,
				'level_4'                => true,
				'level_3'                => true,
				'level_2'                => true,
				'level_1'                => true,
				'level_0'                => true,
				'read'                   => true,
				'read_private_pages'     => true,
				'read_private_posts'     => true,
				'edit_users'             => true,
				'edit_posts'             => true,
				'edit_pages'             => true,
				'edit_published_posts'   => true,
				'edit_published_pages'   => true,
				'edit_private_pages'     => true,
				'edit_private_posts'     => true,
				'edit_others_posts'      => true,
				'edit_others_pages'      => true,
				'publish_posts'          => true,
				'publish_pages'          => true,
				'delete_posts'           => true,
				'delete_pages'           => true,
				'delete_private_pages'   => true,
				'delete_private_posts'   => true,
				'delete_published_pages' => true,
				'delete_published_posts' => true,
				'delete_others_posts'    => true,
				'delete_others_pages'    => true,
				'manage_categories'      => true,
				'manage_links'           => true,
				'moderate_comments'      => true,
				'unfiltered_html'        => true,
				'upload_files'           => true,
				'export'                 => true,
				'import'                 => true
			) );

			$capabilities = $birchschedule->view->settings->get_core_capabilities();

			foreach ( $capabilities as $cap_group ) {
				foreach ( $cap_group as $cap ) {
					$wp_roles->add_cap( 'birs_business_manager', $cap );
				}
			}
		}
	};

} );
