<?php

birch_ns( 'birchschedule.uintegration', function( $ns ) {

		global $birchschedule;

		$_ns_data = new stdClass();

		$ns->init = function() use ( $ns, $birchschedule, $_ns_data ) {
			global $birchpress;

			$_ns_data->old_client = false;

			add_action( 'init', array( $ns, 'wp_init' ) );

			add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );

			add_action( 'wp_ajax_birchschedule_uintegration_sync_clients', array( $ns, 'ajax_sync_clients' ) );

			add_action( 'wp_ajax_birchschedule_uintegration_sync_check_status',
				array( $ns, 'ajax_sync_check_status' ) );

			add_action( 'wp_ajax_birchschedule_uintegration_skip',
				array( $ns, 'ajax_skip' ) );

			$birchpress->util->enable_remote_call( $ns->sync_clients );
		};

		$ns->wp_admin_init = function() use( $ns, $birchschedule ) {

			add_action( 'admin_enqueue_scripts', array( $ns, 'add_scripts' ) );

		};

		$ns->wp_init = function() use( $ns, $birchschedule ) {

			add_filter( 'authenticate', array( $ns, 'email_login_authenticate' ), 40, 3 );

			if ( !$ns->is_user_sync_enabled() ) {
				return;
			}

			add_action( 'birchschedule_model_cpt_client_save_before',
				array( $ns, 'on_save_client_before' ), 10, 2 );

			add_action( 'birchschedule_model_cpt_client_save_after',
				array( $ns, 'on_save_client_after' ), 10, 3 );


			add_action( 'user_register', array( $ns, 'sync_user_to_client' ) );

			add_action( 'profile_update', array( $ns, 'sync_user_to_client' ) );
		};

		$ns->is_user_sync_enabled = function() use( $ns, $birchschedule ) {

			$is_login_disabled = $birchschedule->fbuilder->is_login_disabled();
			return !$is_login_disabled;
		};

		$ns->sync_clients = function() use( $ns, $birchschedule ) {
			$max_execution_time = ini_get( 'max_execution_time' );
			@set_time_limit( 0 );
			$users = get_users();
			$synced_emails = array();
			foreach ( $users as $user ) {
				$ns->sync_user_to_client( $user );
				$synced_emails[] = $user->user_email;
				set_transient( 'birs_synced_emails', $synced_emails );
			}
			$clients = $birchschedule->model->query(
				array(
					'post_type' => 'birs_client'
				),
				array(
					'meta_keys' => array( '_birs_client_email' ),
					'base_keys' => array()
				)
			);
			foreach ( $clients as $client_id => $client ) {
				if ( !in_array( $client['_birs_client_email'], $synced_emails ) ) {
					$ns->sync_client_to_user( $client );
					$synced_emails[] = $client['_birs_client_email'];
					set_transient( 'birs_synced_emails', $synced_emails );
				}
			}
			update_option( 'birs_if_user_synced', true );
			delete_transient( 'birs_synced_emails' );
			@set_time_limit( $max_execution_time );
			exit;
		};

		$ns->ajax_sync_clients = function() use( $ns, $birchschedule ) {
			global $birchpress;

			$if_user_synced = $ns->get_option_if_user_synced();
			if ( $if_user_synced ) {
				exit;
			}
			$birchpress->util->async_run_task( array(
					'action' => 'birchschedule.uintegration.sync_clients',
					'args' => array()
				) );
			exit;
		};

		$ns->ajax_sync_check_status = function() use( $ns ) {
			$synced_emails = get_transient( 'birs_synced_emails' );
			$count = count( $synced_emails );
			$if_user_synced = $ns->get_option_if_user_synced();
?>
        <div id="birs_response">
            <div id="synced_client_count"><?php echo $count; ?></div>
            <div id="if_user_synced"><?php echo $if_user_synced; ?></div>
        </div>
        <?php
			exit;
		};

		$ns->ajax_skip = function() {
			update_option( 'birs_if_user_synced', true );
			exit;
		};

		$ns->get_option_if_user_synced = function() {
			return (bool)get_option( 'birs_if_user_synced', false );
		};

		$ns->add_scripts = function( $hook ) use ( $ns, $birchschedule ) {
			if ( $hook == 'edit.php' ) {
				if ( isset( $_GET['post_type'] ) &&
					( $_GET['post_type'] == 'birs_client' ) ) {

					$product_version = $birchschedule->get_product_version();
					$plugin_url = $birchschedule->plugin_url();
					$module_dir = $plugin_url . '/modules/uintegration/';
					$params = array(
						'ajax_url' => admin_url( 'admin-ajax.php' ),
						'if_user_synced' => $ns->get_option_if_user_synced(),
						'i18n' => array(
							'user_sync' => __( 'User Synchronization', 'birchschedule' ),
							'syncing_clients_with_wp_users' => __( 'Syncing clients with WP users...', 'birchschedule' ),
							'synced' => __( 'synced', 'birchschedule' ),
							'skip' => __( 'Skip', 'birchschedule' )
						)
					);
					wp_register_script(
						'birchschedule_user_integration',
						$module_dir . 'assets/js/user-integration.js',
						array( 'birchschedule_view', 'jquery-ui-dialog' ), $product_version );
					wp_enqueue_style( "wp-jquery-ui-dialog" );
					wp_enqueue_script( 'birchschedule_user_integration' );
					wp_localize_script( 'birchschedule_user_integration', 'birs_user_integration_params', $params );
				}
			}
		};

		$ns->email_login_authenticate = function( $user, $username, $password ) use ( $ns, $birchschedule ) {
			if ( is_a( $user, 'WP_User' ) ) {
				return $user;
			}

			remove_action( 'authenticate', 'wp_authenticate_username_password', 20 );

			if ( !empty( $username ) ) {
				$username = str_replace( '&', '&amp;', stripslashes( $username ) );
				$user = get_user_by( 'email', $username );
				if ( isset( $user, $user->user_login, $user->user_status ) && 0 == (int) $user->user_status )
				$username = $user->user_login;
			}

			return wp_authenticate_username_password( null, $username, $password );
		};

		$ns->sync_client_to_user = function( $client ) use ( $ns, $birchschedule, $_ns_data ) {
			$user_data = array();
			if ( is_int( $client ) ) {
				$client = $birchschedule->model->get( $client, array(
						'meta_keys' => array(
							'_birs_client_email', '_birs_client_name_first',
							'_birs_client_name_last', '_birs_client_password'
						),
						'base_keys' => array()
					) );
			}
			if ( $_ns_data->old_client && !empty( $_ns_data->old_client['_birs_client_email'] ) ) {
				$user_email = $_ns_data->old_client['_birs_client_email'];
			} else {
				$user_email = $client['_birs_client_email'];
			}
			if ( !$user_email || !is_email( $user_email ) ) {
				return;
			}
			$user = get_user_by( 'email', $user_email );
			if ( $user ) {
				$user_data['ID'] = $user->ID;
			} else {
				$nickname = $ns->get_name_from_email( $client['_birs_client_email'] );
				$user_data['user_nicename'] = $nickname;
				$user_data['display_name'] = $ns->get_display_name( $client['_birs_client_name_first'],
					$client['_birs_client_name_last'], $nickname );
				$user_data['nickname'] = $nickname;
				$user_data['user_login'] = uniqid( 'c' );
			}
			$user_data['user_email'] = $client['_birs_client_email'];
			if ( isset( $client['_birs_client_password'] ) ) {
				$user_data['user_pass'] = $client['_birs_client_password'];
			}
			$user_data['first_name'] = $client['_birs_client_name_first'];
			$user_data['last_name'] = $client['_birs_client_name_last'];
			remove_action( 'user_register', array( $ns, 'sync_user_to_client' ) );
			remove_action( 'profile_update', array( $ns, 'sync_user_to_client' ) );
			$user_id = 0;
			if ( $user ) {
				$user_id = wp_update_user( $user_data );
			} else {
				if ( empty( $user_data['user_pass'] ) ) {
					$user_data['user_pass'] = wp_generate_password();
				}
				$user_id = wp_insert_user( $user_data );
			}
			wp_update_post( array(
				'ID' => $client['ID'],
				'post_author' => $user_id
			) );
			add_action( 'user_register', array( $ns, 'sync_user_to_client' ) );
			add_action( 'profile_update', array( $ns, 'sync_user_to_client' ) );
			$ns->delete_meta_password( $client['ID'] );
		};

		$ns->sync_user_to_client = function( $user ) use( $ns, $birchschedule ) {
			if ( is_int( $user ) ) {
				$user = get_user_by( 'id', $user );
			}
			$client_email = $user->user_email;
			if ( !$client_email || !is_email( $client_email ) ) {
				return;
			}
			$config = array(
				'meta_keys' => array(
					'_birs_client_email', '_birs_client_name_first',
					'_birs_client_name_last'
				),
				'base_keys' => array(
					'post_title',
					'post_author'
				)
			);
			$client = $birchschedule->model->get_client_by_email( $client_email, $config );
			if ( !$client ) {
				$client = array(
					'post_type' => 'birs_client'
				);
			}
			$client['_birs_client_email'] = $client_email;
			$client['_birs_client_name_first'] =
			$user->user_firstname;
			$client['_birs_client_name_last'] =
			$user->user_lastname;
			$client['post_author'] = $user->ID;

			remove_action( "birchschedule_model_cpt_client_save_before",
				array( $ns, 'on_save_client_before' ) );
			remove_action( "birchschedule_model_cpt_client_save_after",
				array( $ns, 'on_save_client_after' ) );

			$birchschedule->model->save( $client, $config );

			add_action( "birchschedule_model_cpt_client_save_before",
				array( $ns, 'on_save_client_before' ), 10, 2 );
			add_action( "birchschedule_model_cpt_client_save_after",
				array( $ns, 'on_save_client_after' ), 10, 3 );
		};

		$ns->on_save_client_before = function( $client, $config ) use( $ns, $birchschedule, $_ns_data ) {

			if ( isset( $client['ID'] ) ) {
				$_ns_data->old_client = $birchschedule->model->get( $client['ID'], $config );
			}
		};

		$ns->on_save_client_after = function( $client, $config, $client_id ) use( $ns, $birchschedule, $_ns_data ) {
			$ns->sync_client_to_user( $client );
		};

		$ns->get_name_from_email = function( $email ) {
			$parts = explode( '@', $email );
			return $parts[0];
		};

		$ns->get_display_name = function( $first_name, $last_name, $nickname ) {
			$display_name = trim( $first_name );
			if ( empty( $display_name ) ) {
				$display_name = trim( $last_name );
			}
			if ( empty( $display_name ) ) {
				$display_name = $nickname;
			}
			return $display_name;
		};

		$ns->delete_meta_password = function( $client_id ) {
			delete_post_meta( $client_id, '_birs_client_password' );
		};

	} );
