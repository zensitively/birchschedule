<?php

birch_ns( 'birchpress.view', function( $ns ) {

		$ns->init = function() use ( $ns ) {
			add_action( 'init', array( $ns, 'wp_init' ) );
			add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );
		};

		$ns->wp_init = function() use ( $ns ) {
		};

		$ns->wp_admin_init = function() use ( $ns ) {
		};

		$ns->register_3rd_scripts = function() use ( $ns ) {
			global $birchpress;

			wp_register_script( 'underscore.string',
				$birchpress->get_framework_url() . '/lib/assets/js/underscore.string/underscore.string.js',
				array( 'underscore' ), '2.3.0' );
		};

		$ns->register_3rd_styles = function() use ( $ns ) {
			global $birchpress;			
		};

		$ns->register_core_scripts = function() use ( $ns ) {
			global $birchpress;

			$version = $birchpress->get_version();
			wp_register_script( 'birchpress',
				$birchpress->get_framework_url() . '/assets/js/birchpress/index.bundle.js',
				array( 'underscore', 'underscore.string', 'jquery' ), "$version" );
		};

	} );
