<?php

birch_ns( 'birchschedule.ccode.upgrader', function( $ns ) {

		global $birchschedule;

		$_ns_data = new stdClass();

		$ns->init = function() use ( $ns, $birchschedule ) {
			$ns->init_data();

			$birchschedule->upgrade_module->when( $birchschedule->ccode->is_module_ccode, $ns->upgrade_module );
		};

		$ns->init_data = function() use ( $ns, $_ns_data ) {
			$_ns_data->default_options_custom_code_1_0 = array(
				'php' => '',
				'css' => ''
			);

			$_ns_data->default_options_custom_code_1_1 = $_ns_data->default_options_custom_code_1_0;
			$_ns_data->default_options_custom_code_1_1['css'] = array();
			$_ns_data->default_options_custom_code_1_1['version'] = '1.1';

			$_ns_data->default_options_custom_code_1_2 = $_ns_data->default_options_custom_code_1_1;
			$_ns_data->default_options_custom_code_1_2['javascript'] = '';
			$_ns_data->default_options_custom_code_1_2['php'] = '';
			$_ns_data->default_options_custom_code_1_2['version'] = '1.2';

			$_ns_data->default_options_custom_code = $_ns_data->default_options_custom_code_1_2;
		};

		$ns->init_db = function() use ( $ns, $_ns_data ) {
			$options = get_option( 'birchschedule_options_custom_code' );
			if ( $options === false ) {
				add_option( 'birchschedule_options_custom_code',
					$ns->get_default_options_custom_code() );
			}
		};

		$ns->get_default_options_custom_code = function() use ( $_ns_data ) {
			return $_ns_data->default_options_custom_code;
		};

		$ns->upgrade_module = function() use( $ns ) {
			$ns->init_db();
			$ns->upgrade_options_1_0_to_1_1();
			$ns->upgrade_options_1_1_to_1_2();
		};

		$ns->upgrade_options_1_0_to_1_1 = function() use ( $ns ) {
			$options = get_option( 'birchschedule_options_custom_code' );
			$version = $ns->get_db_version_options();
			if ( $version !== '1.0' ) {
				return;
			}
			$css_bookingform = $options['css'];
			$options['css'] =
			array(
				'bp-scheduler-bookingform' => $css_bookingform
			);
			$options['version'] = '1.1';
			update_option( "birchschedule_options_custom_code", $options );
		};

		$ns->upgrade_options_1_1_to_1_2 = function() use ( $ns ) {
			$options = get_option( 'birchschedule_options_custom_code' );
			$version = $ns->get_db_version_options();
			if ( $version !== '1.1' ) {
				return;
			}
			$options['javascript'] = '';
			$options['version'] = '1.2';
			update_option( "birchschedule_options_custom_code", $options );
		};

		$ns->get_db_version_options = function() {
			$options = get_option( 'birchschedule_options_custom_code' );
			if ( isset( $options['version'] ) ) {
				return $options['version'];
			} else {
				return '1.0';
			}
		};

	} );
