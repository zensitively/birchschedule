<?php

birch_ns( 'birchschedule.wcredit', function( $ns ) {

		global $birchschedule;

		$ns->init = function() use( $ns, $birchschedule ) {

			add_action( 'init', array( $ns, 'wp_init' ) );

			add_filter( 'birchschedule_view_get_frontend_i18n_messages',
				array( $ns, 'add_frontend_i18n_messages' ) );

			add_action( 'woocommerce_cart_loaded_from_session',
				array( $ns, 'wc_load_credit_from_session' ), 20 );

			add_action( 'birchschedule_wintegration_init_tab_after',
				array( $ns, 'init_settings_tab' ) );

		};

		$ns->wp_init = function() use( $ns ) {

			$ns->register_post_type();

			$ns->register_scripts();

			$ns->add_wc_actions();

			$ns->add_credit_actions();
		};

		$ns->init_settings_tab = function() use( $ns, $birchschedule ) {

			$tab_name = $birchschedule->wintegration->get_tab_name();

			$page_hook = $birchschedule->view->settings->get_tab_page_hook( $tab_name );

			$metabox_category = $birchschedule->view->settings->get_tab_metabox_category( $tab_name );

			$screen = $birchschedule->view->get_screen( $page_hook );

			add_meta_box( 'birs_woocommerce_credit', __( 'WooCommerce Credit', 'birchschedule' ),
				array( $ns, 'render_settings_credit' ), $screen,
				$metabox_category, 'default' );
		};

		$ns->add_frontend_i18n_messages = function( $messages ) {
			$messages['The credit should be positive.'] = __( 'The credit should be positive.', 'birchschedule' );
			$messages['The credit is not enough.'] = __( 'The credit is not enough.', 'birchschedule' );
			$messages['Used by order No. %s'] = __( 'Used by order No. %s', 'birchschedule' );
			$messages['Recharged by order No. %s'] = __( 'Recharged by order No. %s', 'birchschedule' );
			return $messages;
		};

		$ns->is_wc_credit_enabled = function() use( $ns, $birchschedule ) {
			$options = $birchschedule->wintegration->get_options();
			if ( isset( $options['credit_enabled'] ) && $options['credit_enabled'] ) {
				return true;
			} else {
				return false;
			}
		};

		$ns->is_wc_credit_product = function( $wc_product_id ) use( $ns, $birchschedule ) {
			$options = $birchschedule->wintegration->get_options();
			$credit_products = "";
			if ( isset( $options['credit_products'] ) && $options['credit_products'] ) {
				$credit_products = $options['credit_products'];
			}
			$credit_products = explode( ',', $credit_products );
			return in_array( $wc_product_id, $credit_products );
		};

		$ns->add_wc_actions = function() use( $ns, $birchschedule ) {
			if ( !$birchschedule->wintegration->wc->is_woocommerce_activated() ) {
				return;
			}
			if ( !$ns->is_wc_credit_enabled() ) {
				return;
			}
			add_action( 'woocommerce_before_checkout_form',
				array( $ns, 'wc_render_credit' ), 20 );

			add_action( 'woocommerce_after_calculate_totals',
				array( $ns, 'wc_apply_credit' ), 20 );

			add_action( 'woocommerce_cart_updated',
				array( $ns, 'wc_save_credit_to_session' ), 20 );

			add_action( 'woocommerce_checkout_order_processed',
				array( $ns, 'wc_redeem_credit' ), 20, 2 );

			add_action( 'woocommerce_cart_item_removed',
				array( $ns, 'wc_remove_applied_credit' ), 20 );

			add_action( 'woocommerce_cart_emptied',
				array( $ns, 'wc_empty_applied_credit' ), 20 );

			add_action( 'woocommerce_order_status_changed',
				array( $ns, 'wc_add_credit' ), 20, 3 );

			$ns->wc_add_credit_to_cart();
		};

		$ns->add_credit_actions = function() use( $ns, $birchschedule ) {

			if ( !$ns->is_wc_credit_enabled() ) {
				return;
			}
			add_action( 'birchschedule_view_clients_load_page_edit_after',
				array( $ns, 'load_page_edit' ) );

			add_action( 'birchschedule_view_clients_enqueue_scripts_edit_after',
				array( $ns, 'enqueue_scripts_edit' ) );

			add_action( 'birchschedule_view_clients_save_post_after',
				array( $ns, 'save_credit' ) );
		};

		$ns->register_post_type = function() use( $ns ) {
			register_post_type( 'birs_wc_credit', array(
					'labels' => array(
						'name' => __( 'WooCommerce Credit', 'birchschedule' ),
						'singular_name' => __( 'WooCommerce Credit', 'birchschedule' ),
						'add_new' => __( 'Add WooCommerce Credit', 'birchschedule' ),
						'add_new_item' => __( 'Add New WooCommerce Credit', 'birchschedule' ),
						'edit' => __( 'Edit', 'birchschedule' ),
						'edit_item' => __( 'Edit WooCommerce Credit', 'birchschedule' ),
						'new_item' => __( 'New WooCommerce Credit', 'birchschedule' ),
						'view' => __( 'View WooCommerce Credit', 'birchschedule' ),
						'view_item' => __( 'View WooCommerce Credit', 'birchschedule' ),
						'search_items' => __( 'Search WooCommerce credit', 'birchschedule' ),
						'not_found' => __( 'No WooCommerce credit found', 'birchschedule' ),
						'not_found_in_trash' => __( 'No WooCommerce credit found in trash', 'birchschedule' ),
						'parent' => __( 'Parent WooCommerce Credit', 'birchschedule' )
					),
					'description' => __( 'This is where WooCommerce credit are stored.', 'birchschedule' ),
					'public' => false,
					'show_ui' => false,
					'capability_type' => 'post',
					'publicly_queryable' => false,
					'exclude_from_search' => true,
					'show_in_menu' => 'birchschedule_schedule',
					'hierarchical' => false,
					'show_in_nav_menus' => false,
					'rewrite' => false,
					'query_var' => true,
					'supports' => array( 'custom-fields' ),
					'has_archive' => false
				) );
		};

		$ns->register_scripts = function() use( $ns, $birchschedule ) {
			$version = $birchschedule->get_product_version();
			wp_register_script( 'birchschedule_wcredit_clients',
				$birchschedule->plugin_url() . '/modules/wcredit/assets/js/clients.js',
				array( 'birchschedule_view_admincommon', 'birchschedule_view' ), "$version" );
		};

		$ns->enqueue_scripts_edit = function() {
			wp_enqueue_script( 'birchschedule_wcredit_clients' );
		};

		$ns->load_page_edit = function() use( $ns ) {
			add_action( 'add_meta_boxes', array( $ns, 'add_meta_boxes' ) );
		};

		$ns->add_meta_boxes = function() use( $ns ) {
			add_meta_box( 'birchschedule-client-wc-credit', __( 'WooCommerce Credit', 'birchschedule' ),
				array( $ns, 'render_credit_info' ), 'birs_client', 'normal', 'high' );
		};

		$ns->get_credit_history_html = function( $client_id ) use( $ns, $birchschedule ) {
			global $birchpress;

			$credit = $birchschedule->model->query(
				array(
					'post_type' => 'birs_wc_credit',
					'post_status' => 'publish',
					'meta_query' => array(
						array(
							'key' => '_birs_wc_credit_client',
							'value' => $client_id
						)
					)
				),
				array(
					'base_keys' => array(),
					'meta_keys' => array(
						'_birs_wc_credit_timestamp',
						'_birs_wc_credit_amount',
						'_birs_wc_credit_notes'
					)
				)
			);
?>
        <table class="wp-list-table fixed widefat" id="birs_payments_table">
            <thead>
                <tr>
                    <th><?php _e( 'Date', 'birchschedule' ); ?></th>
                    <th class="column-author"><?php _e( 'Amount', 'birchschedule' ); ?></th>
                    <th><?php _e( 'Notes', 'birchschedule' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
			foreach ( $credit as $credit_id => $credit ) {
				$credit_datetime =
				$birchpress->util->convert_to_datetime( $credit['_birs_wc_credit_timestamp'] );
				$amount = $credit['_birs_wc_credit_amount'];
				$currency_code = $birchschedule->model->get_currency_code();
?>
                <tr>
                    <td><?php echo $credit_datetime ?></td>
                    <td>
                        <?php echo $birchschedule->model->apply_currency_symbol( $amount, $currency_code ); ?>
                    </td>
                    <td>
                        <?php echo $credit['_birs_wc_credit_notes']; ?>
                    </td>
                </tr>
                <?php
			}
?>
            </tbody>
        </table>
        <?php
		};

		$ns->get_credit_balance = function( $client_id ) use( $ns, $birchschedule ) {
			$credit = $birchschedule->model->query(
				array(
					'post_type' => 'birs_wc_credit',
					'post_status' => 'publish',
					'meta_query' => array(
						array(
							'key' => '_birs_wc_credit_client',
							'value' => $client_id
						)
					)
				),
				array(
					'base_keys' => array(),
					'meta_keys' => array(
						'_birs_wc_credit_amount'
					)
				)
			);
			$balance = 0;
			foreach ( $credit as $credit ) {
				$balance += floatval( $credit['_birs_wc_credit_amount'] );
			}
			return $balance;
		};

		$ns->get_current_credit_balance = function() use( $ns, $birchschedule ) {
			$current_user = wp_get_current_user();
			$client = $birchschedule->model->get_client_by_email( $current_user->user_email, array(
					'base_keys' => array(),
					'meta_keys' => array()
				) );
			if ( $client ) {
				$credit_balance = $ns->get_credit_balance( $client['ID'] );
			} else {
				$credit_balance = 0;
			}
			return $credit_balance;
		};

		$ns->get_credit_add_html = function( $client_id ) use( $ns, $birchschedule ) {
?>
        <table class="form-table">
            <tbody>
                <tr>
                <th>
                    <label for="birs_wc_credit_amount">Add or subtract a credit value</label>
                </th>
                <td>
                    <input name="birs_wc_credit_amount" id="birs_wc_credit_amount" class="regular-text" value="">
                    <div>

                    </div>
                </td>
                </tr>
                <tr>
                    <th><label>Notes</label></th>
                    <td><textarea name="birs_wc_credit_notes" rows="5" cols="50"></textarea></td>
                </tr>
                <tr>
                    <th><input name="birs_save_wc_credit" type="submit" class="button button-primary" value="Save"></th>
                    <td>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
		};

		$ns->get_credit_info_html = function( $client_id ) use( $ns, $birchschedule ) {
			$balance = $ns->get_credit_balance( $client_id );
			$currency_code = $birchschedule->model->get_currency_code();
			ob_start();
?>
        <div>
            <table class="form-table">
                <tr>
                <th>
                    <label for="birs_client_wc_credit"><?php _e( 'Credit Balance', 'birchschedule' ); ?></label>
                </th>
                <td>
                    <?php echo $birchschedule->model->apply_currency_symbol( $balance, $currency_code ); ?>
                </td>
                </tr>
                <tr>
                    <th>
                        <a href="javascript::void(0);" id="birs_wc_credit_add"><?php _e( '+ Add Credit', 'birchschedule' ); ?></a>
                    </th>
                    <td></td>
                </tr>
            </table>
            <div id="birs_wc_credit_add_block" style="display:none;">
                <?php echo $ns->get_credit_add_html( $client_id ); ?>
            </div>
            <div id="birs_wc_credit_history_block">
                <?php echo $ns->get_credit_history_html( $client_id ); ?>
            </div>
        </div>
        <?php
			return ob_get_clean();
		};

		$ns->render_credit_info = function( $post ) use ( $ns ) {
			echo $ns->get_credit_info_html( $post->ID );
		};

		$ns->save_credit = function( $client ) use( $ns, $birchschedule ) {
			$credit = array(
				'post_type' => 'birs_wc_credit',
				'post_status' => 'publish',
				'_birs_wc_credit_timestamp' => time(),
				'_birs_wc_credit_amount' => floatval( $_POST['birs_wc_credit_amount'] ),
				'_birs_wc_credit_notes' => $_POST['birs_wc_credit_notes'],
				'_birs_wc_credit_client' => $client['ID']
			);
			$birchschedule->model->save( $credit, array(
					'base_keys' => array( 'post_type', 'post_status' ),
					'meta_keys' => array(
						'_birs_wc_credit_timestamp', '_birs_wc_credit_amount',
						'_birs_wc_credit_notes', '_birs_wc_credit_client'
					)
				) );
		};

		$ns->render_settings_credit = function() use( $ns, $birchschedule ) {
			global $birchpress;

			$options = $birchschedule->wintegration->get_options();
			if ( isset( $options['credit_enabled'] ) && $options['credit_enabled'] ) {
				$enabled_check = ' checked="checked" ';
			} else {
				$enabled_check = "";
			}
			$credit_products = "";
			if ( isset( $options['credit_products'] ) && $options['credit_products'] ) {
				$credit_products = $options['credit_products'];
			}
			$credit_products = explode( ',', $credit_products );
			$products = $birchpress->db->query(
				array(
					'post_type' => 'product'
				),
				array(
					'base_keys' => array( 'post_title' ),
					'meta_keys' => array()
				)
			);
			$all_products = array();
			foreach ( $products as $product ) {
				$wc_product = function_exists('wc_get_product') ? wc_get_product( $product['ID'] ) : get_product( $product['ID'] );
				$product_type = method_exists($wc_product, 'get_type') ? $wc_product->get_type() : $wc_product->product_type;
				if ( $product_type === 'simple' ) {
					$all_products[] = array(
						'id' => $product['ID'],
						'text' => $product['post_title']
					);
				}
			}
?>
    <table class="form-table">
        <tbody>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="birchschedule_options_woocommerce_credit_enabled">
                        <?php _e( 'Enable/Disable', 'birchschedule' ); ?>
                    </label>
                </th>
                <td>
                    <input name="birchschedule_options_woocommerce[credit_enabled]"
                         id="birchschedule_options_woocommerce_credit_enabled"
                         type="checkbox" value="on"
                         <?php echo $enabled_check; ?> />
                    <label for="birchschedule_options_woocommerce_credit_enabled">
                         <?php _e( 'Enable WooCommerce Credit', 'birchschedule' ); ?>
                    </label>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label>
                        <?php _e( 'Buy Credit Amount from WooCommerce Products', 'birchschedule' ); ?>
                    </label>
                </th>
                <td>
                    <input 
                        id="birchschedule_options_woocommerce_credit_products_input"
                        name="birchschedule_options_woocommerce[credit_products]"
                        type="hidden" value="<?php echo $options['credit_products']; ?>" />
                    <select 
                        style="width:100%;max-width:24em;"
                        id="birchschedule_options_woocommerce_credit_products"
                        multiple="multiple">
                        <?php foreach($all_products as $product) {
                            $selected = "";
                            if(in_array($product['id'], $credit_products)) {
                                $selected = 'selected="selected"';
                            }
                         ?>
                            <option value="<?php echo $product['id']; ?>" <?php echo $selected ?>>
                                <?php echo $product['text']; ?>
                            </option>
                        <?php } ?>
                    </select>
                    <div>
                        <label><?php _e( '(Only simple products are listed here)', 'birchschedule' ); ?></label>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
    <script type="text/javascript">
        jQuery(function($){
            $('#birchschedule_options_woocommerce_credit_products').select2();
            $('#birchschedule_options_woocommerce_credit_products').on('change', function(event) {
                $('#birchschedule_options_woocommerce_credit_products_input').val($('#birchschedule_options_woocommerce_credit_products').select2('val'));
            });
        });
    </script>
    <?php
		};

		$ns->wc_if_cart_has_credit_product = function() use( $ns, $birchschedule ) {
			global $woocommerce;

			$wc_cart = $woocommerce->cart->cart_contents;
			foreach ( $wc_cart as $cart_item_key => $values ) {
				$product_id = $values['product_id'];
				if ( $ns->is_wc_credit_product( $product_id ) ) {
					return true;
				}
			}
			return false;
		};

		$ns->wc_render_credit = function() use( $ns, $birchschedule ) {
			if ( !$ns->is_wc_credit_enabled() ) {
				return;
			}
			if ( $ns->wc_if_cart_has_credit_product() ) {
				return;
			}
			echo $ns->wc_get_custom_credit_html();
		};

		$ns->wc_get_custom_credit_html = function() use( $ns, $birchschedule ) {
			ob_start();
?>
        <style type='text/css'>
            .woocommerce-page .birs_checkout_credit {
                border: 1px solid #e0dadf;
                padding: 20px;
                margin: 2em 0 2em 0;
                text-align: left;
                border-radius: 5px;
                -webkit-border-radius: 5px;
                -moz-border-radius: 5px;
            }
        </style>
        <div>
            <h3><?php _e( 'Customer Credit', 'birchschedule' ); ?></h3>
            <?php
			if ( is_user_logged_in() ) {
				echo $ns->wc_get_apply_credit_html();
			} else {
				echo $ns->wc_get_require_login_html();
			}
?>
        </div>
        <?php
			return ob_get_clean();
		};

		$ns->wc_get_require_login_html = function() use( $ns, $birchschedule ) {
			ob_start();
?>
        <div class="birs_checkout_credit">
            <p>
                <?php _e( 'Please login to use your credit.', 'birchschedule' );  ?>
            </p>
        </div>
        <?php
			return ob_get_clean();
		};

		$ns->wc_get_apply_credit_html = function() use( $ns, $birchschedule ) {
			global $woocommerce;

			if ( empty( $woocommerce->cart->birs_credit ) ) {
				$applied_credit = 0;
			} else {
				$applied_credit = $woocommerce->cart->birs_credit;
			}
			$credit_balance = $ns->get_current_credit_balance() - $applied_credit;
			$currency_code = $birchschedule->model->get_currency_code();
			$credit_balance = $birchschedule->model->apply_currency_symbol( $credit_balance, $currency_code );
			ob_start();
?>
        <form class="birs_checkout_credit" method="post" style="">
            <p>
                <?php printf( __( 'Your current credit balance is %s. <br/>Enter a credit amount if you want to pay by customer credit.', 'birchschedule' ), $credit_balance ); ?>
            </p>
            <p class="form-row form-row-first">
                <input type="text" name="birs_credit" class="input-text" id="birs_credit" value="" />
            </p>

            <p class="form-row form-row-last">
                <?php wp_nonce_field( 'birchschedule_wcredit_wc_add_credit_to_cart' ); ?>
                <input type="hidden" name="action" value="birchschedule_wcredit_wc_add_credit_to_cart" />
                <input type="submit" class="button" name="birs_apply_credit" value="<?php _e( 'Apply Custom Credit', 'birchschedule' ); ?>" />
            </p>

            <div class="clear"></div>
        </form>
        <?php
			return ob_get_clean();
		};

		$ns->wc_add_credit_to_cart = function() use( $ns, $birchschedule ) {
			if ( !$ns->is_wc_credit_enabled() ) {
				return;
			}
			if ( !is_user_logged_in() ) {
				return;
			}
			if ( !isset( $_POST['action'] ) || $_POST['action'] != 'birchschedule_wcredit_wc_add_credit_to_cart' ) {
				return;
			}
			if ( $ns->wc_if_cart_has_credit_product() ) {
				return;
			}

			global $woocommerce;

			if ( empty( $woocommerce->session->birs_credit ) ) {
				$woocommerce->session->birs_credit = 0;
			}
			$credit_balance = $ns->get_current_credit_balance();

			$apply_credit = min( floatval( $_POST['birs_credit'] ), $credit_balance - $woocommerce->session->birs_credit );
			$woocommerce->session->birs_credit += $apply_credit;
			$orig_url = $_POST['_wp_http_referer'];
			wp_redirect( $orig_url );
			exit;
		};

		$ns->wc_apply_credit = function( $wc_cart ) use( $ns, $birchschedule ) {
			if ( !$ns->is_wc_credit_enabled() ) {
				return;
			}
			if ( $ns->wc_if_cart_has_credit_product() ) {
				return;
			}
			if ( !is_user_logged_in() ) {
				return;
			}
			if ( empty( $wc_cart->birs_credit ) ) {
				$wc_cart->birs_credit = 0;
			}
			$wc_cart->total -= $wc_cart->birs_credit;
		};

		$ns->wc_save_credit_to_session = function() use( $ns, $birchschedule ) {
			if ( !$ns->is_wc_credit_enabled() ) {
				return;
			}
			if ( $ns->wc_if_cart_has_credit_product() ) {
				return;
			}
			if ( !is_user_logged_in() ) {
				return;
			}
			global $woocommerce;

			if ( empty( $woocommerce->cart->birs_credit ) ) {
				$woocommerce->cart->birs_credit = 0;
			}
			$woocommerce->session->birs_credit = $woocommerce->cart->birs_credit;
		};

		$ns->wc_load_credit_from_session = function( $wc_cart ) use( $ns, $birchschedule ) {
			if ( !$ns->is_wc_credit_enabled() ) {
				return;
			}
			if ( $ns->wc_if_cart_has_credit_product() ) {
				return;
			}
			if ( !is_user_logged_in() ) {
				return;
			}
			global $woocommerce;
			if ( empty( $woocommerce->session->birs_credit ) ) {
				$woocommerce->session->birs_credit = 0;
			}
			$woocommerce->cart->birs_credit = $woocommerce->session->birs_credit;
		};

		//It is not used currently.
		$ns->wc_validate_credit = function() use( $ns, $birchschedule ) {
			if ( !$ns->is_wc_credit_enabled() ) {
				return;
			}
			if ( $ns->wc_if_cart_has_credit_product() ) {
				return;
			}
			if ( !is_user_logged_in() ) {
				return;
			}

			global $woocommerce;

			$messages = $birchschedule->view->get_frontend_i18n_messages();
			$applying_credit = $woocommerce->cart->birs_credit;
			if ( $applying_credit < 0 ) {
				$birchschedule->wintegration->wc->add_wc_error( $messages['The credit should be positive.'] );
			}
			$avaliable_credit = $ns->get_current_credit_balance();
			if ( $applying_credit > $avaliable_credit ) {
				$birchschedule->wintegration->wc->add_wc_error( $messages['The credit is not enough.'] );
			}
		};

		$ns->wc_redeem_credit = function( $order_id, $data ) use( $ns, $birchschedule ) {
			if ( !$ns->is_wc_credit_enabled() ) {
				return;
			}
			if ( $ns->wc_if_cart_has_credit_product() ) {
				return;
			}
			if ( !is_user_logged_in() ) {
				return;
			}

			global $woocommerce;

			$messages = $birchschedule->view->get_frontend_i18n_messages();
			$applyed_credit = $woocommerce->cart->birs_credit;
			if ( $applyed_credit == 0 ) {
				return;
			}
			$current_user = wp_get_current_user();
			$client = $birchschedule->model->get_client_by_email( $current_user->user_email, array(
					'base_keys' => array(),
					'meta_keys' => array(

					)
				) );
			if ( !$client ) {
				return;
			}
			$order_url = admin_url( sprintf( 'post.php?post=%s&action=edit', $order_id ) );
			$order_link = sprintf( '<a href="%s">#%s</a>', $order_url, $order_id );
			$notes = sprintf( $messages['Used by order No. %s'], $order_link );
			$credit = array(
				'post_type' => 'birs_wc_credit',
				'post_status' => 'publish',
				'_birs_wc_credit_timestamp' => time(),
				'_birs_wc_credit_amount' => -floatval( $applyed_credit ),
				'_birs_wc_credit_notes' => $notes,
				'_birs_wc_credit_client' => $client['ID']
			);
			$birchschedule->model->save( $credit, array(
					'base_keys' => array( 'post_type', 'post_status' ),
					'meta_keys' => array(
						'_birs_wc_credit_timestamp', '_birs_wc_credit_amount',
						'_birs_wc_credit_notes', '_birs_wc_credit_client'
					)
				) );
		};

		$ns->wc_remove_applied_credit = function() {
			global $woocommerce;

			$item_count = sizeof( $woocommerce->cart->cart_contents );
			if ( $item_count === 0 ) {
				unset( $woocommerce->cart->birs_credit );
			}
		};

		$ns->wc_empty_applied_credit = function() {
			global $woocommerce;

			unset( $woocommerce->session->birs_credit );
			unset( $woocommerce->cart->birs_credit );
		};

		$ns->wc_add_credit = function( $order_id, $old_status, $new_status ) use( $ns, $birchschedule ) {
			if ( $new_status != 'completed' ) {
				return;
			}
			$messages = $birchschedule->view->get_frontend_i18n_messages();
			$order = new WC_Order( $order_id );
			$items = $order->get_items();
			$credit = 0;
			foreach ( $items as $item_id => $item ) {
				$product_id = $item['product_id'];
				if ( $ns->is_wc_credit_product( $product_id ) ) {
					$product = function_exists('wc_get_product') ? wc_get_product( $product_id ) : get_product( $product_id );
					$qty = $item['qty'];
					$credit += $product->regular_price * $qty;
				}
			}
			if ( $credit == 0 ) {
				return;
			}
			if ( !empty( $order->customer_user ) ) {
				$customer_user = get_user_by( 'id', $order->customer_user );
				$user_email = $customer_user->user_email;
			} else {
				$user_email = $order->billing_email;
			}
			$client = $birchschedule->model->get_client_by_email( $user_email, array(
					'base_keys' => array(),
					'meta_keys' => array(

					)
				) );
			if ( !$client ) {
				return;
			}
			$order_url = admin_url( sprintf( 'post.php?post=%s&action=edit', $order_id ) );
			$order_link = sprintf( '<a href="%s">#%s</a>', $order_url, $order_id );
			$notes = sprintf( $messages['Recharged by order No. %s'], $order_link );
			$credit = array(
				'post_type' => 'birs_wc_credit',
				'post_status' => 'publish',
				'_birs_wc_credit_timestamp' => time(),
				'_birs_wc_credit_amount' => floatval( $credit ),
				'_birs_wc_credit_notes' => $notes,
				'_birs_wc_credit_client' => $client['ID']
			);
			$birchschedule->model->save( $credit, array(
					'base_keys' => array( 'post_type', 'post_status' ),
					'meta_keys' => array(
						'_birs_wc_credit_timestamp', '_birs_wc_credit_amount',
						'_birs_wc_credit_notes', '_birs_wc_credit_client'
					)
				) );
		};

	} );
