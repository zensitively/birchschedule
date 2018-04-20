<?php

if ( !function_exists( 'birchpress_load' ) ) {

	function birchpress_load() {

		require_once 'birch.php';

		require_once 'birchpress/package.php';
		require_once 'birchpress/util/package.php';
		require_once 'birchpress/db/package.php';
		require_once 'birchpress/model/package.php';
		require_once 'birchpress/view/package.php';

		global $birchpress;

		$birchpress->set_version( 'master.98b73c1' );

		$birchpress->init_package( $birchpress );

	}

	birchpress_load();

}
