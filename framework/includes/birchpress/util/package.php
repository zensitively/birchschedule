<?php

birch_ns( 'birchpress.util', function( $ns ) {

		$_ns_data = new stdClass();

		$ns->init = function() use ( $ns, $_ns_data ) {
			$_ns_data->request_actions = array();
			add_filter( 'http_api_transports', array( $ns, 'http_api_transports' ), 20 );
			add_action( 'init', array( $ns, 'call_request_function' ), 100 );
		};

		$ns->http_api_transports = function( $transports ) {
			return array( 'streams', 'curl' );
		};

		$ns->call_request_function = function() use ( $ns, $_ns_data ) {
			$action = empty( $_GET['birchpress_action'] ) ? '' : $_GET['birchpress_action'];
			if ( !empty( $action ) ) {
				$args = empty( $_REQUEST['args'] ) ? '[]' : $_REQUEST['args'];
				$args = json_decode( stripslashes_deep( $args ), true );
				if ( isset( $_ns_data->request_actions[$action] ) ) {
					$fn = $_ns_data->request_actions[$action];
					call_user_func_array( $fn, $args );
				}
				die;
			}
		};

		$ns->get_remote_call_url = function( $action ) use ( $ns ) {
			$url = add_query_arg( array(
					'birchpress_action' => $action
				), home_url( '/index.php' ) );
			return $url;
		};

		$ns->async_run_task = function( $task ) use ( $ns ) {
			$url = $ns->get_remote_call_url( $task['action'] );
			wp_remote_post( $url, array(
					'timeout' => 0.01,
					'blocking' => false,
					'sslverify' => apply_filters( 'https_local_ssl_verify', true ),
					'body' => array(
						'args' => json_encode( $task['args'] )
					)
				) );
		};

		$ns->enable_remote_call = function( $fn ) use ( $ns, $_ns_data ) {
			if ( !is_a( $fn, 'Birch_Fn' ) ) {
				return;
			}
			$fn_name = $fn->get_full_name();
			$_ns_data->request_actions[$fn_name] = $fn;
		};

		$ns->get_wp_screen = function( $hook_name ) use ( $ns ) {
			if ( substr( $hook_name, -4 ) !== '.php' ) {
				$hook_name = $hook_name . '.php';
			}
			$screen = WP_Screen::get( $hook_name );
			return $screen;
		};

		$ns->has_shortcode = function( $shortcode = NULL ) use( $ns ) {

			$post_to_check = get_post( get_the_ID() );

			$found = false;

			if ( !$shortcode ) {
				return $found;
			}
			if ( stripos( $post_to_check->post_content, '[' . $shortcode ) !== FALSE && stripos( $post_to_check->post_content, '[[' . $shortcode ) == FALSE ) {
				$found = TRUE;
			}

			return $found;
		};

		$ns->render_html_options = function( $options, $selection = false, $default = false ) use ( $ns ) {
			if ( $selection == false && $default != false ) {
				$selection = $default;
			}
			foreach ( $options as $val => $text ) {
				if ( $selection == $val ) {
					$selected = ' selected="selected" ';
				} else {
					$selected = '';
				}
				echo "<option value='$val' $selected>$text</option>";
			}
		};

		$ns->get_first_day_of_week = function() use ( $ns ) {
			return get_option( 'start_of_week', 0 );
		};

		$ns->starts_with = function ( $haystack, $needle ) {
			return !strncmp( $haystack, $needle, strlen( $needle ) );
		};

		$ns->ends_with = function ( $haystack, $needle ) {
			$length = strlen( $needle );
			if ( $length == 0 ) {
				return true;
			}

			return substr( $haystack, -$length ) === $needle;
		};

		$ns->current_page_url = function () use ( $ns ) {
			$pageURL = 'http';
			if ( isset( $_SERVER["HTTPS"] ) && $_SERVER["HTTPS"] == "on" ) {
				$pageURL .= "s";
			}
			$pageURL .= "://";
			if ( $_SERVER["SERVER_PORT"] != "80" ) {
				$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
			} else {
				$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
			}
			return $pageURL;
		};

		$ns->new_error = function ( $code = '', $message = '', $data = '' ) use ( $ns ) {
			return new WP_Error( $code, $message, $data );
		};

		$ns->add_error_item = function ( $errors, $code, $message, $data = '' ) use( $ns ) {
			$errors->add( $code, $message, $data );
			return $errors;
		};

		$ns->get_error_codes = function ( $errors ) use ( $ns ) {
			return $errors->get_error_codes();
		};

		$ns->get_error_code = function ( $errors ) use( $ns ) {
			return $errors->get_error_code();
		};

		$ns->get_error_message = function ( $errors, $code ) use ( $ns ) {
			return $errors->get_error_message( $code );
		};

		$ns->is_error = function ( $errors ) use ( $ns ) {
			return is_wp_error( $errors );
		};

		$ns->merge_errors = function () use ( $ns ) {
			$errors = $ns->new_error();
			$args = func_get_args();
			foreach ( $args as $arg ) {
				if ( $ns->is_error( $arg ) ) {
					$codes = $ns->get_error_codes();
					foreach ( $codes as $code ) {
						$message = $ns->get_error_message( $code );
						$ns->add_error_item( $errors, $code, $message );
					}
				}
				else if ( is_array( $arg ) ) {
					foreach ( $arg as $code => $message ) {
						$ns->add_error_item( $errors, $code, $message );
					}
				}
			}
			return $errors;
		};

		$ns->urlencode = function ( $arg ) use ( $ns ) {
			if ( is_array( $arg ) ) {
				$new_array = array();
				foreach ( $arg as $field_name => $field_value ) {
					$new_array[$field_name] = $ns->urlencode( $field_value );
				}
				return $new_array;
			}
			if ( is_string( $arg ) ) {
				return urlencode( $arg );
			} else {
				return $arg;
			}
		};

		require 'i18n.php';

	} );
