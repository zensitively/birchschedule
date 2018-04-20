<?php

birch_ns( 'birchschedule.view.appointments.edit.clientlist.payments', function( $ns ) {

	$ns->init = function() use ( $ns ) {
		add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );
		$ns->register_post_type();
		add_action( 'birchschedule_view_register_common_scripts_after',
					array( $ns, 'register_scripts' ) );
	};

	$ns->wp_admin_init = function() use ( $ns ) {
		add_action( 'birchschedule_view_enqueue_scripts_post_edit_after',
					array( $ns, 'enqueue_scripts_post_edit' ) );

		add_action( 'birchschedule_view_appointments_edit_clientlist_render_more_rows_after',
					array( $ns, 'render_row' ), 30, 3 );

		add_filter( 'birchschedule_view_appointments_edit_clientlist_get_item_actions',
					array( $ns, 'add_item_action' ), 30, 2 );

		add_action(
			'wp_ajax_birchschedule_view_appointments_edit_clientlist_payments_add_new_payment',
			array( $ns, 'ajax_add_new_payment' )
		);

		add_action(
			'wp_ajax_birchschedule_view_appointments_edit_clientlist_payments_make_payments',
			array( $ns, 'ajax_make_payments' )
		);
	};

	$ns->register_post_type = function() {
		register_post_type( 'birs_payment', array(
			'labels' => array(
				'name' => __( 'Payments', 'birchschedule' ),
				'singular_name' => __( 'Appointment', 'birchschedule' ),
				'add_new' => __( 'Add Payment', 'birchschedule' ),
				'add_new_item' => __( 'Add New Payment', 'birchschedule' ),
				'edit' => __( 'Edit', 'birchschedule' ),
				'edit_item' => __( 'Edit Payment', 'birchschedule' ),
				'new_item' => __( 'New Payment', 'birchschedule' ),
				'view' => __( 'View Payment', 'birchschedule' ),
				'view_item' => __( 'View Payment', 'birchschedule' ),
				'search_items' => __( 'Search Payments', 'birchschedule' ),
				'not_found' => __( 'No Payments found', 'birchschedule' ),
				'not_found_in_trash' => __( 'No Payments found in trash', 'birchschedule' ),
				'parent' => __( 'Parent Payment', 'birchschedule' )
			),
			'description' => __( 'This is where payments are stored.', 'birchschedule' ),
			'public' => false,
			'show_ui' => false,
			'capability_type' => 'birs_payment',
			'map_meta_cap' => true,
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

	$ns->register_scripts = function() use ( $ns ) {
		global $birchschedule;

		$version = $birchschedule->get_product_version();

		wp_register_script( 'birchschedule_view_appointments_edit_clientlist_payments',
							$birchschedule->plugin_url() . '/assets/js/view/appointments/edit/clientlist/payments/base.js',
							array( 'birchschedule_view_admincommon', 'birchschedule_view' ), "$version" );
	};

	$ns->enqueue_scripts_post_edit = function( $arg ) {
		if ( $arg['post_type'] != 'birs_appointment' ) {
			return;
		}

		global $birchschedule;

		$birchschedule->view->register_3rd_scripts();
		$birchschedule->view->register_3rd_styles();
		$birchschedule->view->enqueue_scripts(
			array(
				'birchschedule_view_appointments_edit_clientlist_payments'
			)
		);
	};

	$ns->get_payment_types = function() {
		global $birchschedule;

		return $birchschedule->model->booking->get_payment_types();
	};

	$ns->add_item_action = function( $item_actions, $item ) {
		$action_html = '<a href="javascript:void(0);" data-item-id="%s">%s</a>';
		$item_actions['payments'] = sprintf( $action_html, $item['ID'], __( 'Payments', 'birchschedule' ) );
		return $item_actions;
	};

	$ns->ajax_make_payments = function() {
		global $birchschedule;

		$appointment_id = $_POST['birs_appointment_id'];
		$client_id = $_POST['birs_client_id'];
		$appointment1on1_config =
			array(
				'appointment1on1_keys' => array(
					'_birs_appointment1on1_price'
				)
			);
		$appointment1on1 =
			$birchschedule->model->booking->get_appointment1on1(
				$appointment_id,
				$client_id,
				$appointment1on1_config
			);
		$appointment1on1['_birs_appointment1on1_price'] = $_POST['birs_appointment1on1_price'];
		$birchschedule->model->save( $appointment1on1, $appointment1on1_config );
		$payments = array();
		if ( isset( $_POST['birs_appointment_payments'] ) ) {
			$payments = $_POST['birs_appointment_payments'];
		}
		$config = array(
			'meta_keys' => $birchschedule->model->get_payment_fields(),
			'base_keys' => array()
		);
		foreach ( $payments as $payment_trid => $payment ) {
			$payment_info = $birchschedule->view->merge_request( array(), $config, $payment );
			$payment_info['_birs_payment_appointment'] = $appointment_id;
			$payment_info['_birs_payment_client'] = $client_id;
			$payment_info['_birs_payment_trid'] = $payment_trid;
			$payment_info['_birs_payment_currency'] = $birchschedule->model->get_currency_code();
			$birchschedule->model->booking->make_payment( $payment_info );
		}

		$birchschedule->view->render_ajax_success_message( array(
			'code' => 'success',
			'message' => ''
		) );
	};

    $ns->render_row = function( $wp_list_table, $item, $row_class ) use( $ns ) {
		$client_id = $item['ID'];
		$appointment_id = $wp_list_table->appointment_id;
		$column_count = $wp_list_table->get_column_count();
		$payments_html = $ns->get_payments_details_html( $appointment_id, $client_id );
?>
                <tr class="<?php echo $row_class; ?> birs_row_payments"
                    id="birs_client_list_row_payments_<?php echo $item['ID']; ?>"
                    data-item-id = "<?php echo $item['ID']; ?>"
                    data-payments-html = "<?php echo esc_attr( $payments_html ); ?>">

                    <td colspan = "<?php echo $column_count; ?>"></td>
                </tr>
<?php
	};

	$ns->get_payments_details_html = function( $appointment_id, $client_id ) use ( $ns ) {
		global $birchschedule, $birchpress;

		$price = 0;
		$payment_types = $ns->get_payment_types();
		$payments = array();

		$currency_code = $birchschedule->model->get_currency_code();
		if ( $appointment_id ) {
			$appointment1on1 = $birchschedule->model->booking->get_appointment1on1(
				$appointment_id,
				$client_id,
				array(
					'appointment1on1_keys' => array(
						'_birs_appointment1on1_price'
					),
					'base_keys' => array()
				) );
			$price = $appointment1on1['_birs_appointment1on1_price'];
			$price_formatted = $birchschedule->model->number_format( $price );
			$payments = $birchschedule->model->booking->get_payments_by_appointment1on1( $appointment_id, $client_id );
			$paid = array_sum( array_map(
				function( $e ) {
					return $e['_birs_payment_amount'];
				},
				$payments
			) );
			$paid_formatted = $birchschedule->model->number_format( $paid );
			$due = $price - $paid;
			$due_formatted = $birchschedule->model->number_format( $due );
		}
		ob_start();
?>
                <ul>
                    <li class="birs_form_field" style="float:left;">
                        <a href="javascript:void(0);"
                            class="button"
                            id="birs_appointment_client_payments_cancel">
                            <?php echo __( 'Back', 'birchschedule' ); ?>
                        </a>
                    </li>
                    <li class="birs_form_field" style="float:right;">
                        <span>
<?php
		echo $birchschedule->view->apply_currency_to_label( __( 'Price', 'birchschedule' ), $currency_code );
		echo ': ' . $price_formatted;
?>
                        </span>
                        <span style="margin-left: 20px;">
                            <?php echo __( 'Paid', 'birchschedule' ); echo ": " . $paid_formatted; ?>
                        </span>
                        <span style="margin: 0 20px 0 20px;">
                            <?php echo __( 'Due', 'birchschedule' ); echo ": " . $due_formatted; ?>
                        </span>
                    </li>
                    <li class="birs_form_field" style="clear:both;"></li>
                    <li class="birs_form_field" style="display:none;">
                        <label>
                            <?php _e( 'Payment History', 'birchschedule' ); ?>
                        </label>
                    </li>
                </ul>
                <table class="wp-list-table fixed widefat" id="birs_payments_table">
                    <thead>
                        <tr>
                            <th><?php _e( 'Date', 'birchschedule' ); ?></th>
                            <th class="column-author"><?php _e( 'Amount', 'birchschedule' ); ?></th>
                            <th class="column-author"><?php _e( 'Type', 'birchschedule' ); ?></th>
                            <th><?php _e( 'Notes', 'birchschedule' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
<?php
		foreach ( $payments as $payment_id => $payment ) {
			$payment_datetime =
				$birchpress->util->convert_to_datetime( $payment['_birs_payment_timestamp'] );
			$amount = $payment['_birs_payment_amount'];
?>
                        <tr data-payment-amount="<?php echo $amount; ?>">
                            <td><?php echo $payment_datetime; ?></td>
                            <td>
                                <?php echo $birchschedule->model->apply_currency_symbol(
                        $payment['_birs_payment_amount'],
                        $payment['_birs_payment_currency'] ); ?>
                            </td>
                            <td>
                                <?php echo $payment_types[$payment['_birs_payment_type']]; ?>
                            </td>
                            <td>
                                <?php echo $payment['_birs_payment_notes']; ?>
                            </td>
                        </tr>
<?php
		}
?>
                    </tbody>
                </table>

                <div style="margin:6px;">
                    <a id="birs_appointment_actions_add_payment"
                        href="javascript:void(0);"
                        style="text-decoration:underline;">
                        + <?php _e( 'Add Payment', 'birchschedule' ); ?>
                    </a>
                </div>
                <div class="splitter" style="margin:4px;"></div>
                <input type="hidden" name="birs_client_id" id="birs_client_id" value="<?php echo $client_id; ?>" />
                <div id="birs_appointment_client_payments_add_form" style="display:none">
                    <ul>
                        <li class="birs_form_field">
                            <label>
<?php
		echo $birchschedule->view->apply_currency_to_label( __( 'Price', 'birchschedule' ), $currency_code );
?>
                            </label>
                            <div class="birs_field_content">
                                <span class="birs_money"
                                    id="birs_appointment1on1_price">
                                    <?php echo $price_formatted; ?>
                                </span>
                            </div>
                        </li>
                        <li class="birs_form_field">
                            <label>
                                <?php _e( 'Paid', 'birchschedule' ); ?>
                            </label>
                            <div class="birs_field_content">
                                <span class="birs_money"
                                    id="birs_appointment1on1_paid">
                                    <?php echo $paid_formatted; ?>
                                </span>
                            </div>
                        </li>
                        <li class="birs_form_field">
                            <label>
                                <?php _e( 'Due', 'birchschedule' ); ?>
                            </label>
                            <div class="birs_field_content">
                                <span class="birs_money"
                                    id="birs_appointment1on1_due">
                                    <?php echo $due_formatted; ?>
                                </span>
                            </div>
                        </li>
                    </ul>
                    <div class="splitter"></div>
                    <ul>
                        <li class="birs_form_field">
                            <label>
                                <?php _e( 'Amount to Pay', 'birchschedule' ); ?>
                            </label>
                            <div class="birs_field_content">
                                <input type="text" id="birs_appointment1on1_amount_to_pay"
                                    name="birs_payment_amount"
                                    value="" >
                            </div>
                        </li>
                        <li class="birs_form_field">
                            <label>
                                <?php _e( 'Payment Type', 'birchschedule' ); ?>
                            </label>
                            <div class="birs_field_content">
                                <select name="birs_payment_type">
                                    <?php $birchpress->util->render_html_options( $payment_types ); ?>
                                </select>
                            </div>
                        </li>
                        <li class="birs_form_field">
                            <label>
                                <?php _e( 'Payment Notes', 'birchschedule' ); ?>
                            </label>
                            <div class="birs_field_content">
                                <textarea name="birs_payment_notes"></textarea>
                            </div>
                        </li>
                        <li class="birs_form_field">
                            <label>
                                &nbsp;
                            </label>
                            <div class="birs_field_content">
                                <input id="birs_add_payment"
                                    type="button"
                                   class="button-primary"
                                    value="<?php _e( 'Add Payment', 'birchschedule' ); ?>" />
                                <a id="birs_add_payment_cancel"
                                    href="javascript:void(0);">
                                    <?php _e( 'Cancel', 'birchschedule' ); ?>
                                </a>
                            </div>
                        </li>
                    </ul>
                </div>

<?php
		$content = ob_get_clean();
		return $content;
	};

	$ns->ajax_add_new_payment = function() use ( $ns ) {
		global $birchpress, $birchschedule;

		$payment_types = $ns->get_payment_types();
		$timestamp = time();
		$amount = 0;
		if ( isset( $_POST['birs_payment_amount'] ) ) {
			$amount = floatval( $_POST['birs_payment_amount'] );
		}
		$payment_type = $_POST['birs_payment_type'];
		if ( isset( $_POST['birs_payment_notes'] ) ) {
			$payment_notes = $_POST['birs_payment_notes'];
		}
		$payment_trid = uniqid();
?>
                <tr data-payment-amount="<?php echo $amount; ?>"
                    data-payment-trid="<?php echo $payment_trid; ?>" >
                    <td>
                        <?php echo $birchpress->util->convert_to_datetime( $timestamp ); ?>
                        <input type="hidden"
                             name="birs_appointment_payments[<?php echo $payment_trid ?>][birs_payment_timestamp]"
                             value="<?php echo $timestamp; ?>" />
                        <div class="row-actions">
                            <span class="delete">
                                <a href="javascript:void(0);"
                                    data-payment-trid="<?php echo $payment_trid; ?>">
                                    <?php _e( 'Delete', 'birchschedule' ); ?>
                                </a>
                            </span>
                        </div>
                    </td>
                    <td>
<?php
		$currency_code = $birchschedule->model->get_currency_code();
		echo $birchschedule->model->apply_currency_symbol( $amount, $currency_code );
?>
                        <input type="hidden"
                             name="birs_appointment_payments[<?php echo $payment_trid ?>][birs_payment_amount]"
                             value="<?php echo $amount; ?>" />
                    </td>
                    <td>
                        <?php echo $payment_types[$payment_type]; ?>
                        <input type="hidden"
                             name="birs_appointment_payments[<?php echo $payment_trid ?>][birs_payment_type]"
                             value="<?php echo $payment_type; ?>" />
                    </td>
                    <td>
                        <?php echo $payment_notes; ?>
                        <input type="hidden"
                             name="birs_appointment_payments[<?php echo $payment_trid ?>][birs_payment_notes]"
                             value="<?php echo $payment_notes; ?>" />
                    </td>
                </tr>
<?php
		die();
	};

} );
