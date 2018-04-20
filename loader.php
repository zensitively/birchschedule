<?php

function birchschedule_load( $product ) {

	$plugin_file_path = $product['plugin_file_path'];
	$product_version = $product['product_version'];
	$product_name = $product['product_name'];
	$product_code = $product['product_code'];
	$global_name = $product['global_name'];

	$plugins_dir = dirname( dirname( $plugin_file_path ) );
	$birchpress_plugin_dir = $plugins_dir . '/birchpress';
	$birchpress_submodule_dir = dirname( $plugin_file_path ) . '/birchpress';
	$framework_production_dir = dirname( $plugin_file_path ) . '/framework';

	if ( is_file( $birchpress_plugin_dir . '/birchpress.php' ) ) {
		require_once $birchpress_plugin_dir . '/birchpress.php';
	} elseif ( is_file( $birchpress_submodule_dir . '/framework/includes/birchpress.inc.php' ) ) {
		require_once $birchpress_submodule_dir . '/framework/includes/birchpress.inc.php';
		global $birchpress;
		$birchpress->set_framework_url( plugins_url() . '/' . basename( $plugin_file_path, '.php' ) . '/birchpress/framework' );
	} else {
		require_once $framework_production_dir . '/includes/birchpress.inc.php';
		global $birchpress;
		$birchpress->set_framework_url( plugins_url() . '/' . basename( $plugin_file_path, '.php' ) . '/framework' );
	}

	require_once dirname( $plugin_file_path ) . '/package.php';

	$product_global = birch_ns( $global_name );

	$product_global->set_plugin_file_path( $plugin_file_path );
	$product_global->set_product_version( $product_version );
	$product_global->set_product_name( $product_name );
	$product_global->set_product_code( $product_code );

	$product_global->run();
}
