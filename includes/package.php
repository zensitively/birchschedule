<?php

birch_ns( 'birchschedule', function( $ns ) {

		$plugin_url = '';

		$plugin_file_path = '';

		$module_names = array();

		$product_version = '';

		$product_name = '';

		$product_code = '';

		$ns->set_product_version = function( $_product_version ) use ( $ns, &$product_version ) {
			$product_version = $_product_version;
		};

		$ns->get_product_version = function() use ( $ns, &$product_version ) {
			return $product_version;
		};

		$ns->set_product_name = function( $_product_name ) use ( $ns, &$product_name ) {
			$product_name = $_product_name;
		};

		$ns->get_product_name = function() use ( $ns, &$product_name ) {
			return $product_name;
		};

		$ns->set_product_code = function( $_product_code ) use ( $ns, &$product_code ) {
			$product_code = $_product_code;
		};

		$ns->get_product_code = function() use ( $ns, &$product_code ) {
			return $product_code;
		};

		$ns->set_plugin_file_path = function ( $_plugin_file_path )
		use( $ns, &$plugin_url, &$plugin_file_path ) {

			$plugin_file_path = $_plugin_file_path;
			$plugin_url = plugins_url() . '/' . basename( $plugin_file_path, '.php' );
		};

		$ns->plugin_url = function() use ( $ns, &$plugin_url ) {
			return $plugin_url;
		};

		$ns->plugin_file_path = function() use ( $ns, &$plugin_file_path ) {
			return $plugin_file_path;
		};

		$ns->plugin_dir_path = function () use ( $ns, &$plugin_file_path ) {
			return plugin_dir_path( $plugin_file_path );
		};

		$ns->load_core = function() use( $ns ) {
			global $birchpress;

			$core_dir = $ns->plugin_dir_path() . 'includes';
			$packages = array( 'model', 'view', 'upgrader' );
			foreach ( $packages as $package ) {
				$birchpress->load_package( $core_dir . '/' . $package );
			}
		};

		$ns->load_modules = function() use ( $ns, &$module_names ) {
			global $birchpress;

			$modules_dir = $ns->plugin_dir_path() . 'modules';
			$module_names = $birchpress->load_modules( $modules_dir );
		};

		$ns->upgrade_core = function() {};

		$ns->upgrade_module = function( $module_a ) {};

		$ns->upgrade = function() use ( $ns, &$module_names ) {
			$ns->upgrade_core();
			foreach ( $module_names as $module_name ) {
				$ns->upgrade_module( array(
						'module' => $module_name
					) );
			}
		};

		$ns->init_packages = function() use ( $ns ) {
			global $birchpress;

			$birchpress->init_package( $ns );
		};

		$ns->run = function() use( $ns ) {
			global $birchpress;

			$ns->load_core();
			$ns->load_modules();
			$ns->init_packages();
			add_action( 'init', array( $ns, 'upgrade' ), 5 );
		};

	} );
