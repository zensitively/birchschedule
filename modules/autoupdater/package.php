<?php

require_once 'classes/birs-edd-plugin-updater.php';

birch_ns('birchschedule.autoupdater', function($ns){

	global $birchschedule;

	$_ns_data = new stdClass();

	$ns->init_data = function() use($ns, $birchschedule, $_ns_data) {
		$_ns_data->api_url = 'https://www.birchpress.com/';

		$_ns_data->purchase_url = 'https://www.birchpress.com';

		$_ns_data->plugin_slug = 'birchschedule';

		$_ns_data->product_name = $ns->get_edd_item_name($birchschedule->get_product_code());

		$_ns_data->product_version = $birchschedule->get_product_version();

		$_ns_data->plugin_file_path = $birchschedule->plugin_file_path();

		$_ns_data->edd_updater = new BIRS_EDD_Plugin_Updater($_ns_data->api_url, $_ns_data->plugin_file_path,
			 array(
				 'version' => $_ns_data->product_version,
				 'license' => $ns->get_subscription_key(),
				 'item_name' => $_ns_data->product_name,
				 'author' => 'BirchPress'
			 )
		);

	};

	$ns->init = function() use($ns) {
		$ns->init_data();

		add_action('admin_init', array($ns, 'wp_admin_init'));

		add_action('birchschedule_gsettings_add_settings_sections_after',
			array($ns, 'add_account_section'), 40);

		add_action('update_option_birchschedule_options',
			array($ns, 'on_update_options'), 10, 2);
	};

	$ns->if_init_google_api_credentials = function() use ($ns) {
		return true;
	};

	$ns->wp_admin_init = function() use ($ns) {
		if( $ns->if_init_google_api_credentials() ) {
			$ns->init_google_api_credentials();
		}
	};

	$ns->get_edd_item_name = function($product_code) {
		if(strpos($product_code, 'birchschedule-developer') !== false) {
			return 'BirchPress Scheduler Developer';
		}
		if(strpos($product_code, 'birchschedule-businessplus') !== false) {
			return 'BirchPress Scheduler Business Plus';
		}
		if(strpos($product_code, 'birchschedule-personal') !== false) {
			return 'BirchPress Scheduler Personal';
		}
		if(strpos($product_code, 'birchschedule-business') !== false) {
			return 'BirchPress Scheduler Business';
		}
		return '';
	};

	$ns->add_account_section = function() use($ns) {
		add_settings_section('birchschedule_birchpress_account', __('BirchPress', 'birchschedule'),
							 array($ns, 'render_section_account'), 'birchschedule_settings');

		add_settings_field('birchschedule_subscription_key', __('Support License Key', 'birchschedule'),
						   array($ns, 'render_subscription_key'), 'birchschedule_settings', 'birchschedule_birchpress_account');
	};

	$ns->get_subscription_key = function() {
		$options = get_option('birchschedule_options');
		if (isset($options['subscription_key'])) {
			$subscription_key = $options['subscription_key'];
		} else {
			$subscription_key = "";
		}
		return trim($subscription_key);
	};

	$ns->render_section_account = function() {
		echo '';
	};

	$ns->on_update_options = function($old_value, $new_value) use($ns) {
		if($old_value['subscription_key'] === $new_value['subscription_key']) {
			return;
		}
		$license = $new_value['subscription_key'];
		$ns->activate_license($license);
	};

	$ns->activate_license = function($license) use($ns, $_ns_data) {
		$api_params = array(
			'edd_action'=> 'activate_license',
			'license'   => $license,
			'item_name' => urlencode( $_ns_data->product_name )
		);

		$request_url = add_query_arg($api_params, $_ns_data->api_url);
		$response = wp_remote_get($request_url, array( 'timeout' => 15 ) );

		if ( is_wp_error( $response ) ) {
			return false;
		}
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		update_option('birs_license_status', $license_data);
		return $license_data;
	};

	$ns->check_license = function($license) use($ns, $_ns_data) {
		$store_url = $_ns_data->api_url;
		$item_name = $_ns_data->product_name;

		$api_params = array(
			'edd_action' => 'check_license',
			'license' => $license,
			'item_name' => urlencode( $item_name )
		);

		$response = wp_remote_get( add_query_arg( $api_params, $store_url ), array( 'timeout' => 15, 'sslverify' => false ) );

		if ( is_wp_error( $response ) )
			return false;

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		update_option('birs_license_status', $license_data);
		return $license_data;
	};

	$ns->get_license_status = function() use ( $ns ) {
		$subscription_key = $ns->get_subscription_key();
		$status = get_option('birs_license_status');
		if(!$status) {
			$status = $ns->check_license($subscription_key);
		}

		return $status;
	};

	$ns->render_subscription_key = function() use($ns, $_ns_data) {
		global $birchpress;

		$subscription_key = $ns->get_subscription_key();
		$status = $ns->check_license( $subscription_key );
		if($subscription_key) {
			if(!$status) {
				$status_html = '<label class="birs_license_status birs_warning">' . __('Can not connect to the license server', 'birchschedule') . '</label>';
			} else {
				$expiration = $status->expires;
				$license_status = $status->license;
				if($license_status === 'valid') {
					$datetime = $birchpress->util->get_wp_datetime($expiration);
					$exp_date = $birchpress->util->wp_format_date($datetime);
					$status_html = '<label class="birs_license_status">' . '√ ' .
						__('Expiration Date: ', 'birchschedule') . $exp_date . '</label>';
				}
				else if($license_status === 'expired') {
					$datetime = $birchpress->util->get_wp_datetime($expiration);
					$exp_date = $birchpress->util->wp_format_date($datetime);
					$status_html = '<label class="birs_license_status birs_warning">' .
						__('Expiration Date: ', 'birchschedule') . $exp_date . '</label>';
				}
				else if($license_status === 'site_inactive') {
					$status_html = '<label class="birs_license_status birs_warning">' . __('Site inactive', 'birchschedule') . '</label>';
				}
				else {
					$status_html = '<label class="birs_license_status birs_warning">' . __('Invalid license', 'birchschedule') . '</label>';
				}
			}

		} else {
			$status_html = '<label class="birs_license_status birs_remind">' .
			__('Fill out the license key to receive access to automatic upgrades and support', 'birchschedule') .
			'</label>';
		}
?>
		<style type="text/css">
			.birs_license_status {
				margin: 0 0 0 4px;
			}
			.birs_license_status.birs_remind {
				color: blue;
			}
			.birs_license_status.birs_warning {
				color: red;
			}
		</style>
		<input type="text" name="birchschedule_options[subscription_key]"
			id="birchschedule_options_subscription_key" value="<?php echo $subscription_key; ?>"
			class="regular-text" />
	<?php
		echo $status_html;
	};

	$ns->init_google_api_credentials = function() use ( $ns, $_ns_data ) {
		$credentials = get_option( 'birs_google_api_credentials', false );

		if( !$credentials ) {
			$api_params = array(
				'action' => 'birchadmin_misc_get_google_api_credentials'
			);
			$ajax_url = $_ns_data->api_url . 'wp-admin/admin-ajax.php';
			$response = wp_remote_get( add_query_arg( $api_params, $ajax_url ), array( 'timeout' => 15, 'sslverify' => false ) );
			if ( is_wp_error( $response ) ) {
				return;
			}
			$body = wp_remote_retrieve_body( $response );
			$new_credentials = json_decode( $body, true );
			if( is_array( $new_credentials ) && $new_credentials ) {
				update_option( 'birs_google_api_credentials', $new_credentials );
			}
		}
	};

} );
