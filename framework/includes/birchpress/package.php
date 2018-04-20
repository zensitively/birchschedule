<?php

birch_ns( 'birchpress', function( $ns ) {

		$_ns_data = new stdClass();

		$ns->set_version = function( $new_version ) use ( $_ns_data ) {
			$_ns_data->version = $new_version;
		};

		$ns->get_version = function() use ( $_ns_data ) {
			return $_ns_data->version;
		};

		$ns->set_framework_url = function( $framework_url ) use ( $_ns_data ) {
			$_ns_data->framework_url = $framework_url;
		};

		$ns->get_framework_url = function() use ( $_ns_data ) {
			return $_ns_data->framework_url;
		};

		$ns->load_package = function( $dir ) use( $ns ) {
			if ( is_dir( $dir ) ) {
				$package_file = $dir . '/package.php';
				if ( is_file( $package_file ) ) {
					include_once $package_file;
				}
				$sub_packages = scandir( $dir );
				if ( $sub_packages ) {
					foreach ( $sub_packages as $sub_package ) {
						if ( $sub_package != '.' && $sub_package != '..' ) {
							$sub_package_dir = $dir . '/' . $sub_package;
							if ( is_dir( $sub_package_dir ) ) {
								$ns->load_package( $sub_package_dir );
							}
						}
					}
				}
			}
		};

		$ns->init_package = function( $package ) use ( $ns ) {
			if ( !empty( $package->init ) && is_callable( $package->init ) ) {
				$package->init();
			}
			$sub_ns_keys = $package->get_sub_ns_keys();
			foreach ( $sub_ns_keys as $key ) {
				$sub_package = $package[$key];
				$ns->init_package( $sub_package );
			}
		};

		$ns->load_modules = function( $modules_dir, $includes = false ) use ( $ns ) {
			global $birchpress;

			$loaded_modules = array();
			$module_names = scandir( $modules_dir );
			foreach ( $module_names as $module_name ) {
				if ( $module_name != '.' && $module_name != '..' ) {

					if ( $includes === false || in_array( $module_name, $includes ) ) {
						$loaded_modules[] = $module_name;
						$module_dir = $modules_dir . '/' . $module_name;
						$birchpress->load_package( $module_dir );
					}
				}
			}
			return $loaded_modules;
		};

	} );
