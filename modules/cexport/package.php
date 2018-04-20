<?php

birch_ns( 'birchschedule.cexport', function( $ns ) {

	global $birchschedule;

	$ns->init = function() use( $ns ) {
		add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );
	};

	$ns->wp_admin_init = function() use( $ns ) {
		add_action( 'birchschedule_view_enqueue_scripts_list_after',
					array( $ns, 'add_scripts' ) );

		add_action( 'wp_ajax_birchschedule_cexport_export_clients',
					array( $ns, 'ajax_export_clients' ) );
	};

	$ns->ajax_export_clients = function() use( $ns ) {
		$title = "clients";
		$date = new DateTime();
		$timestamp = $date->format( "Y-m-d_H-i-s" );
		header( 'Set-Cookie: fileDownload=true; path=/' );
		header( 'Cache-Control: max-age=60, must-revalidate' );
		header( "Content-type: text/csv" );
		header( 'Content-Disposition: attachment; filename="'.$title.'-' . $timestamp . '.csv"' );
		echo $ns->get_clients_as_csv();
		die;
	};

	$ns->get_clients_as_csv = function() use( $ns, $birchschedule ) {

		$meta_keys = $birchschedule->model->get_client_fields();
		$default_country = $birchschedule->model->get_default_country();
		$key_to_remove = '_birs_client_state';
		if ( $default_country === 'US' ) {
			$key_to_remove = '_birs_client_province';
		}
		$meta_keys = array_diff( $meta_keys, array( $key_to_remove ) );
		$clients = $birchschedule->model->query( array(
			'post_type' => 'birs_client'
		), array(
			'meta_keys' => $meta_keys,
			'base_keys' => array()
		) );
		$result = "";
		foreach ( $meta_keys as $meta_key ) {
			$meta_label = $birchschedule->model->get_meta_key_label( $meta_key );
			$meta_label = str_replace( '"', '""', $meta_label );
			$result .= '"' . $meta_label . '",';
		}
		$result .= "\n";
		foreach ( $clients as $client_id => $client ) {
			foreach ( $meta_keys as $meta_key ) {
				$meta_value = $client[$meta_key];
				$meta_value = $birchschedule->model->mergefields->get_merge_field_display_value( $meta_value );
				$meta_value = str_replace( '"', '""', $meta_value );
				$result .= '"' . $meta_value . '",';
			}
			$result .= "\n";
		}

		return $result;
	};

	$ns->add_scripts = function( $arg ) use( $ns, $birchschedule ) {
		if ( $arg['post_type'] != 'birs_client' ) {
			return;
		}
		$product_version = $birchschedule->get_product_version();
		$plugin_url = $birchschedule->plugin_url();
		$module_dir = $plugin_url . '/modules/cexport/';
		$params = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'i18n' => array(
				'prepare_file' => 'We are preparing your file, please wait...',
				'generating_file_failed' => 'There was a problem generating your file, please try again.'
			)
		);
		wp_register_script(
			'birchschedule_clients_export_admin',
			$module_dir . 'assets/js/admin.js',
			array( 'birchschedule_view', 'jquery-ui-dialog', 'filedownload_birchpress' ), $product_version );
		$birchschedule->view->enqueue_scripts(
			array(
				'birchschedule_clients_export_admin', 'birchschedule_model',
				'birchschedule_view_admincommon', 'birchschedule_view'
			)
		);
		wp_localize_script( 'birchschedule_clients_export_admin', 'birs_export_clients_params', $params );
		wp_register_style(
			'birchschedule_clients_export_admin',
			$module_dir . 'assets/css/clients-export.css',
			array( 'wp-jquery-ui-dialog' ), $product_version );
		wp_enqueue_style( 'birchschedule_clients_export_admin' );
		do_action( 'birchschedule_enqueue_scripts_client_list' );
	};

} );
