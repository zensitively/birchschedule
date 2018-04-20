<?php

birch_ns( 'birchschedule.pintegration', function( $ns ) {

		global $birchschedule;

		$ns->init = function() use( $ns, $birchschedule ) {

			add_action( 'birchschedule_ppayment_init_tab_after',
				array( $ns, 'init_tab' ) );

			if ( $ns->is_paypal_enabled() ) {
				add_action( 'init', array( $ns, 'handle_paypal_ipn' ), 100 );

				add_filter( 'birchschedule_model_booking_get_payment_types',
					array( $ns, 'add_payment_type' ) );

				add_filter( 'birchschedule_ppayment_get_prepayment_methods',
					array( $ns, 'add_prepayment_method' ) );

				add_filter( 'birchschedule_ppayment_get_prepayment_method_html',
					array( $ns, 'get_prepayment_method_html' ), 20, 4 );
			}
		};

		$ns->init_tab = function() use( $ns, $birchschedule ) {

			$screen = $birchschedule->ppayment->get_screen();
			$meta_box_category = $birchschedule->ppayment->get_meta_box_category();
			add_meta_box( 'birs_payments_paypal_integration', __( 'PayPal Integration', 'birchschedule' ),
				array( $ns, 'render_settings_paypal' ), $screen,
				$meta_box_category, 'default' );
		};

		$ns->add_payment_type = function( $payment_types ) {
			$payment_types['paypal'] = 'PayPal';
			return $payment_types;
		};

		$ns->add_prepayment_method = function( $methods ) {
			$new_methods = array();
			$new_methods['paypal'] = array(
				'title' => __( 'PayPal', 'birchschedule' ) .
				'<img alt="PayPal" src="https://fpdbs.paypal.com/dynamicimageweb?cmd=_dynamic-image&amp;buttontype=ecmark">',
				'description' => __( "Pay via PayPal; you can pay with your credit card if you don't have a PayPal account", 'birchschedule' )
			);
			return array_merge( $new_methods, $methods );
		};

		$ns->handle_paypal_ipn = function() use( $ns, $birchschedule ) {

			if ( !isset( $_GET['bpscheduler_listener'] ) ||
				$_GET['bpscheduler_listener'] !== 'paypal_ipn' ) {
				return;
			}
			if ( !isset( $_POST['txn_id'] ) ||
				!isset( $_POST['payer_email'] ) ||
				!isset( $_POST['receiver_email'] ) ||
				!isset( $_POST['mc_gross'] ) ) {
				die;
			}
			$item_name = $_POST['item_name'];
			$item_number = $_POST['item_number'];
			$payment_status = $_POST['payment_status'];
			$payment_amount = $_POST['mc_gross'];
			$payment_currency = $_POST['mc_currency'];
			$payment_date = $_POST['payment_date'];
			$txn_id = $_POST['txn_id'];
			$receiver_email = trim( $_POST['receiver_email'] );
			$payer_email = $_POST['payer_email'];
			$custom = $_POST['custom'];

			$req = 'cmd=_notify-validate';
			foreach ( $_POST as $key => $value ) {
				$value = urlencode( stripslashes( $value ) );
				$req .= "&$key=$value";
			}
			$header = "POST /cgi-bin/webscr HTTP/1.0\r\n";
			$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
			$header .= "Content-Length: " . strlen( $req ) . "\r\n\r\n";
			$fp = fsockopen( 'ssl://www.paypal.com', 443, $errno, $errstr, 30 );
			if ( $fp ) {
				fputs( $fp, $header . $req );
				while ( !feof( $fp ) ) {
					$res = fgets( $fp, 1024 );
					if ( strcmp( $res, "VERIFIED" ) == 0 ) {
						$appointment1on1 = $birchschedule->model->mergefields->get_appointment1on1_merge_values( $custom );
						$paypal_email = trim( $ns->get_paypal_email( $custom ) );
						if ( strncmp( $receiver_email, $paypal_email ) == 0 &&
							$payment_amount >= $appointment1on1['_birs_appointment_pre_payment_fee'] &&
							strncmp( trim( $payment_currency ), $birchschedule->model->get_currency_code() ) == 0 ) {
							$payment = array(
								'birs_payment_appointment' => $appointment1on1['_birs_appointment_id'],
								'birs_payment_client' => $appointment1on1['_birs_client_id'],
								'birs_payment_amount' => $payment_amount,
								'birs_payment_currency' => $payment_currency,
								'birs_payment_type' => 'paypal',
								'birs_payment_trid' => uniqid(),
								'birs_payment_notes' =>
								sprintf( __( "PayPal Transaction ID: %s", 'birchschedule' ), $txn_id ),
								'birs_payment_timestamp' => strtotime( $payment_date ),
								'birs_payment_3rd_txn_id' => $txn_id
							);
							$birchschedule->ppayment->process_prepayment( $custom, $payment );
						}
						break;
					}
				}
				fclose( $fp );
			}
			die;
		};

		$ns->get_paypal_button_html = function( $post_data ) use( $ns, $birchschedule ) {
			ob_start();
?>
        <form id="birs_pg_paypal_form" action="https://www.paypal.com/cgi-bin/websc" method="post" style="display:none;">
            <input type="hidden" name="business"
                value="<?php echo esc_attr( $post_data['business'] ) ;?>">
            <input type="hidden" name="item_name"
                value="<?php echo esc_attr( $post_data['item_name'] ) ;?>">
            <input type="hidden" name="amount"
                value="<?php echo esc_attr( $post_data['amount'] ) ;?>">
            <input type="hidden" name="return"
                value="<?php echo esc_attr( $post_data['return'] ) ;?>">
            <input type="hidden" name="cancel_return"
                value="<?php echo esc_attr( $post_data['cancel_return'] ) ;?>">
            <input type="hidden" name="notify_url"
                value="<?php echo esc_attr( $post_data['notify_url'] ) ;?>">

            <input type="hidden" name="cmd" value="_xclick">
            <input type="hidden" name="currency_code"
                value="<?php echo esc_attr( $post_data['currency_code'] ) ;?>">
            <input type="hidden" name="charset" value="utf-8" />
            <input type="hidden" name="rm" value="1" />
            <input type="hidden" name="no_note" value="0">
            <input type="hidden" name="custom"
                value="<?php echo esc_attr( $post_data['custom'] ) ;?>">
            <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
         </form>
        <?php
			return ob_get_clean();
		};

		$ns->get_bookingform_script = function() {
			ob_start();
?>
        <script type="text/javascript">
            (function($){
                birchpress.addFilter('birchschedule.ppayment.bookingform.getPlaceOrderFuncs', function(funcs) {
                    funcs['paypal'] = function() {
                        $('#birs_pg_paypal_form').submit();
                    };
                    return funcs;
                });
            })(jQuery);
        </script>
        <?php
			return ob_get_clean();
		};

		$ns->get_shortcode_page_url = function() {
			$shortcode_page_url = $_POST['birs_shortcode_page_url'];
			return $shortcode_page_url;
		};

		$ns->is_paypal_enabled = function() {
			$options = get_option( 'birchschedule_options_payments', array() );
			$enabled = false;
			if ( isset( $options['paypal'] ) ) {
				if ( isset( $options['paypal']['enabled'] ) ) {
					$enabled = $options['paypal']['enabled'];
				}
			}
			return $enabled;
		};

		$ns->get_paypal_email = function( $appointment1on1_id ) {
			$options = get_option( 'birchschedule_options_payments', array() );
			$paypal_email = "";
			if ( isset( $options['paypal'] ) ) {
				$paypal_email = stripslashes( $options['paypal']['email'] );
			}
			return trim( $paypal_email );
		};

		$ns->get_return_url = function( $arg ) use( $ns, $birchschedule ) {
			$shortcode_page_url = $ns->get_shortcode_page_url();
			$thankyou_url = add_query_arg( $arg, $shortcode_page_url );
			return $thankyou_url;
		};

		$ns->get_cancel_return_url = function() use( $ns, $birchschedule ) {
			return $ns->get_shortcode_page_url();
		};

		$ns->get_notify_url = function() {
			return home_url() . '/index.php?bpscheduler_listener=paypal_ipn';
		};

		$ns->get_prepayment_method_html = function( $html, $method_name, $method, $appointment1on1_id ) use( $ns, $birchschedule ) {

			if ( $method_name !== 'paypal' ) {
				return $html;
			}
			$enabled = $ns->is_paypal_enabled();
			$paypal_email = $ns->get_paypal_email( $appointment1on1_id );
			if ( !$enabled ) {
				return $html;
			}
			$appointment1on1 =
			$birchschedule->model->mergefields->get_appointment1on1_merge_values( $appointment1on1_id );
			$thankyou_url = $ns->get_return_url(
				array(
					'apt1on1_id' => $appointment1on1_id,
					'thankyou' => 'yes'
				)
			);
			$query_data = array(
				'business' => $paypal_email,
				'item_name' => $appointment1on1['_birs_service_name'],
				'amount' => $appointment1on1['_birs_appointment_pre_payment_fee'],
				'return' => $thankyou_url,
				'cancel_return' => $ns->get_cancel_return_url(),
				'notify_url' => $ns->get_notify_url(),
				'cmd' => '_xclick',
				'currency_code' => $birchschedule->model->get_currency_code(),
				'custom' => $appointment1on1_id
			);
			$html .= $ns->get_paypal_button_html( $query_data );
			$html .= $ns->get_bookingform_script();
			return $html;
		};

		$ns->render_settings_paypal = function() use( $ns, $birchschedule ) {
			$enabled = $ns->is_paypal_enabled();
			$paypal_email = esc_attr( $ns->get_paypal_email( null ) );
			$enabled_check = '';
			if ( $enabled ) {
				$enabled_check = ' checked="checked" ';
			}
?>
        <table class="form-table">
            <tbody>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="birchschedule_options_payments_paypal_enabled">
                            <?php _e( 'Enable/Disable', 'birchschedule' ); ?>
                        </label>
                    </th>
                    <td>
                        <input name="birchschedule_options_payments[paypal][enabled]"
                             id="birchschedule_options_payments_paypal_enabled"
                             type="checkbox" value="on"
                             <?php echo $enabled_check; ?> />
                        <label for="birchschedule_options_payments_paypal_enabled">
                             <?php _e( 'Enable PayPal', 'birchschedule' ); ?>
                         </label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="birchschedule_options_payments_paypal_email">
                            <?php _e( 'PayPal Email', 'birchschedule' ); ?>
                        </label>
                    </th>
                    <td>
                        <input name="birchschedule_options_payments[paypal][email]"
                            class="regular-text"
                            id="birchschedule_options_payments_paypal_email"
                            type="text"
                            value="<?php echo $paypal_email; ?>" />
                    </td>
                </tr>
            </tbody>
        </table>
		<?php
		};

	} );
