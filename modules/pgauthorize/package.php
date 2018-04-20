<?php

birch_ns( 'birchschedule.pgauthorize', function( $ns ) {

		global $birchschedule;

		$ns->init = function() use( $ns, $birchschedule ) {

				add_action( 'birchschedule_ppayment_init_tab_after',
					array( $ns, 'init_tab' ), 30 );

				if ( $ns->is_authorize_net_enabled() ) {
					add_action( 'init', array( $ns, 'do_authorize_net_callback' ), 100 );

					add_filter( 'birchschedule_model_booking_get_payment_types',
						array( $ns, 'add_payment_type' ) );

					add_filter( 'birchschedule_ppayment_get_prepayment_methods',
						array( $ns, 'add_prepayment_method' ), 20 );

					add_filter( 'birchschedule_ppayment_get_prepayment_method_html',
						array( $ns, 'get_prepayment_method_html' ), 20, 4 );
				}
				require_once $birchschedule->plugin_dir_path() . 'lib/authorize.net/include.php';
			};

		$ns->init_tab = function() use( $ns, $birchschedule ) {

				$screen = $birchschedule->ppayment->get_screen();

				$meta_box_category = $birchschedule->ppayment->get_meta_box_category();

				add_meta_box( 'birs_payments_authorize_net_integration', __( 'Authorize.net Integration', 'birchschedule' ),
					array( $ns, 'render_settings_authorize_net' ), $screen,
					$meta_box_category, 'default' );
			};

		$ns->add_payment_type = function( $payment_types ) {
				$payment_types['authorize_net'] = 'Authorize.net';
				return $payment_types;
			};

		$ns->add_prepayment_method = function( $methods ) {
				$methods['authorize_net'] = array(
					'title' => __( 'Credit card (Authorize.net)', 'birchschedule' ),
					'description' => __( 'Pay with your credit card via Authorize.net', 'birchschedule' )
				);
				return $methods;
			};

		$ns->is_authorize_net_enabled = function() {
				$options = get_option( 'birchschedule_options_payments', array() );
				$enabled = false;
				if ( isset( $options['authorize_net'] ) ) {
					if ( isset( $options['authorize_net']['enabled'] ) ) {
						$enabled = $options['authorize_net']['enabled'];
					}
				}
				return $enabled;
			};

		$ns->do_authorize_net_callback = function() use( $ns, $birchschedule ) {

				if ( !isset( $_POST['x_response_code'] ) ) {
					return;
				}
				$code = $_POST['x_response_code'];
				$status_txt = $_POST['x_response_reason_text'];
				$redirect_url = $_POST['birs_page_thankyou'];
				$appointment1on1_id = $_POST['birs_appointment1on1_id'];
				$appointment1on1 = $birchschedule->model->get( $appointment1on1_id,
					array(
						'meta_keys' => array( '_birs_appointment_id', '_birs_client_id' )
					)
				);
				$payment_currency = $ns->get_currency_code();
				if ( $code == 1 ) {
					$payment = array(
						'birs_payment_appointment' => $appointment1on1['_birs_appointment_id'],
						'birs_payment_client' => $appointment1on1['_birs_client_id'],
						'birs_payment_amount' => $_POST['x_amount'],
						'birs_payment_currency' => $payment_currency,
						'birs_payment_type' => 'authorize_net',
						'birs_payment_trid' => uniqid(),
						'birs_payment_notes' =>
						sprintf( __( "Authorize.net Transaction ID: %s", 'birchschedule' ), $_POST['x_trans_id'] ),
						'birs_payment_timestamp' => time(),
						'birs_payment_3rd_txn_id' => $_POST['x_trans_id']
					);
					$birchschedule->ppayment->process_prepayment( $appointment1on1_id, $payment );
				}
?>
    <html xmlns="http://www.w3.org/1999/xhtml">
      <head>
        <title><?php echo $status_txt ?></title>
        <meta http-equiv="refresh" content="0;URL='<?php echo $redirect_url; ?>'" />
      </head>
      <body>
        <p><?php echo $status_txt ?><a href="<?php echo $redirect_url; ?>">
            <?php _e( 'Click here if not redirected.', 'birchschedule' ); ?></a></p>
      </body>
    </html>
    <?php
				exit;
			};

		$ns->get_authorize_net_login_id = function() use( $ns, $birchschedule ) {
				$options = get_option( 'birchschedule_options_payments', array() );
				$login_id = "";
				if ( isset( $options['authorize_net'] ) ) {
					$login_id = stripslashes( $options['authorize_net']['login_id'] );
				}
				return trim( $login_id );
			};

		$ns->get_authorize_net_transation_key = function() use( $ns, $birchschedule ) {
				$options = get_option( 'birchschedule_options_payments', array() );
				$transation_key = "";
				if ( isset( $options['authorize_net'] ) ) {
					$transation_key = stripslashes( $options['authorize_net']['transation_key'] );
				}
				return trim( $transation_key );
			};

		$ns->get_authorize_net_gateway_url = function() {
				return "https://secure.authorize.net/gateway/transact.dll";
			};

		$ns->get_shortcode_page_url = function() {
				$shortcode_page_url = $_POST['birs_shortcode_page_url'];
				return $shortcode_page_url;
			};

		$ns->get_finger_print = function( $amount, $fp_sequence, $fp_timestamp, $currency=false ) use( $ns, $birchschedule ) {

				$api_login_id = $ns->get_authorize_net_login_id();
				$transaction_key = $ns->get_authorize_net_transation_key();
				if ( $currency ) {
					$str = $api_login_id . "^" . $fp_sequence . "^" . $fp_timestamp . "^" . $amount . "^" . $currency;
				} else {
					$str = $api_login_id . "^" . $fp_sequence . "^" . $fp_timestamp . "^" . $amount;
				}
				if ( function_exists( 'hash_hmac' ) ) {
					return hash_hmac( "md5", $str, $transaction_key );
				}
				return bin2hex( mhash( MHASH_MD5, $str, $transaction_key ) );
			};

		$ns->get_currency_code = function() use( $birchschedule ) {
				return $birchschedule->model->get_currency_code();
			};

		$ns->get_return_url = function( $arg ) use( $ns, $birchschedule ) {
				$shortcode_page_url = $ns->get_shortcode_page_url();
				$thankyou_url = add_query_arg( $arg, $shortcode_page_url );
				return $thankyou_url;
			};

		$ns->get_prepayment_method_html = function( $html, $method_name, $method, $appointment1on1_id ) use( $ns, $birchschedule ) {

				if ( $method_name !== 'authorize_net' ) {
					return $html;
				}
				$enabled = $ns->is_authorize_net_enabled();
				$login_id = $ns->get_authorize_net_login_id();
				$transaction_key = $ns->get_authorize_net_transation_key();
				if ( !$enabled ) {
					return $html;
				}
				$appointment1on1 =
				$birchschedule->model->mergefields->get_appointment1on1_merge_values( $appointment1on1_id );

				$amount = $appointment1on1['_birs_appointment_pre_payment_fee'];
				$fp_timestamp = time();
				$fp_sequence = rand( 1, 100 ) . time();
				$relay_url = $ns->get_shortcode_page_url();
				$gateway_url = $ns->get_authorize_net_gateway_url();
				$currency = $ns->get_currency_code();
				$fingerprint = $ns->get_finger_print( $amount, $fp_sequence, $fp_timestamp, $currency );
				$thankyou_url = $ns->get_return_url(
					array(
						'apt1on1_id' => $appointment1on1_id,
						'thankyou' => 'yes'
					)
				);
				ob_start();
?>
        <form id="birs_pg_authorize_net_form" method='post' action="<?php echo $gateway_url; ?>" style="display:none;">
            <input type='hidden' name="x_login" value="<?php echo $login_id?>" />
            <input type='hidden' name="x_fp_hash" value="<?php echo $fingerprint?>" />
            <input type='hidden' name="x_amount" value="<?php echo $amount?>" />
            <input type='hidden' name='x_description' value='<?php echo $appointment1on1['_birs_service_name']; ?>' />
            <input type='hidden' name="x_fp_timestamp" value="<?php echo $fp_timestamp?>" />
            <input type='hidden' name="x_fp_sequence" value="<?php echo $fp_sequence?>" />
            <input type='hidden' name="x_version" value="3.1">
            <input type='hidden' name="x_show_form" value="payment_form">
            <input type='hidden' name="x_test_request" value="false" />
            <input type='hidden' name='x_relay_response' value="true" />
            <input type='hidden' name='x_relay_url' value="<?php echo $relay_url; ?>" />
            <input type='hidden' name='x_relay_always' value="false" />
            <input type='hidden' name="x_method" value="cc" />
            <?php
				if ( $currency ) {
?>
            <input type='hidden' name="x_currency_code" value="<?php echo $currency; ?>" />
                <?php
				}
?>
            <input type='hidden' name="birs_appointment1on1_id" value="<?php echo $appointment1on1_id; ?>" />
            <input type="hidden" name="birs_page_thankyou" value="<?php echo $thankyou_url; ?>" />
        </form>
        <script type="text/javascript">
            (function($){
                birchpress.addFilter('birchschedule.ppayment.bookingform.getPlaceOrderFuncs', function(funcs) {
                    funcs['authorize_net'] = function() {
                        $('#birs_pg_authorize_net_form').submit();
                    };
                    return funcs;
                });
            })(jQuery);
        </script>
        <?php
				return $html . ob_get_clean();
			};

		$ns->render_settings_authorize_net = function() use( $ns, $birchschedule ) {

				$enabled = $ns->is_authorize_net_enabled();
				$login_id = esc_attr( $ns->get_authorize_net_login_id() );
				$transation_key = esc_attr( $ns->get_authorize_net_transation_key() );
				$enabled_check = '';
				if ( $enabled ) {
					$enabled_check = ' checked="checked" ';
				}
?>
        <table class="form-table">
            <tbody>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="birchschedule_options_payments_authorize_net_enabled">
                            <?php _e( 'Enable/Disable', 'birchschedule' ); ?>
                        </label>
                    </th>
                    <td>
                        <input name="birchschedule_options_payments[authorize_net][enabled]"
                             id="birchschedule_options_payments_authorize_net_enabled"
                             type="checkbox" value="on"
                             <?php echo $enabled_check; ?> />
                        <label for="birchschedule_options_payments_authorize_net_enabled">
                             <?php _e( 'Enable Authorize.net', 'birchschedule' ); ?>
                         </label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="birchschedule_options_payments_authorize_net_login_id">
                            <?php _e( 'Login ID', 'birchschedule' ); ?>
                        </label>
                    </th>
                    <td>
                        <input name="birchschedule_options_payments[authorize_net][login_id]"
                            class="regular-text"
                            id="birchschedule_options_payments_authorize_net_login_id"
                            type="text"
                            value="<?php echo $login_id; ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="birchschedule_options_payments_authorize_net_transation_key">
                            <?php _e( 'Transaction Key', 'birchschedule' ); ?>
                        </label>
                    </th>
                    <td>
                        <input name="birchschedule_options_payments[authorize_net][transation_key]"
                            class="regular-text"
                            id="birchschedule_options_payments_authorize_net_transation_key"
                            type="text"
                            value="<?php echo $transation_key; ?>" />
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
			};

	} );
