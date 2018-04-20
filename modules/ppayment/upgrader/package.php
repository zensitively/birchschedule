<?php

birch_ns( 'birchschedule.ppayment.upgrader', function( $ns ) {

        global $birchschedule;

        $_ns_data = new stdClass();

        $ns->init = function() use( $ns, $birchschedule ) {
            $ns->init_data();

            $birchschedule->upgrade_module->when( $birchschedule->ppayment->is_module_ppayment, $ns->upgrade_module );
        };

        $ns->init_data = function() use( $ns, $_ns_data ) {

            $_ns_data->default_options_payments_1_0 = array(
                'pre_payment' => array(
                    'confirm_message' => $ns->get_prepayment_confirm_message()
                )
            );
            $_ns_data->default_options_payments = $_ns_data->default_options_payments_1_0;
        };

        $ns->init_db = function() use( $ns ) {
            $options = get_option( 'birchschedule_options_payments' );
            if ( $options === false ) {
                add_option( 'birchschedule_options_payments', $ns->get_default_options_payments() );
            }
        };

        $ns->get_default_options_payments = function() use( $_ns_data ) {
            return $_ns_data->default_options_payments;
        };

        $ns->get_prepayment_confirm_message = function() {
            ob_start();
?>
<h3>
    <?php _e( 'Please make a payment of {deposit} to hold your appointment.',
                'birchschedule' ); ?>
</h3>
<div>
    <ul>
        <li>
            <h4><?php _e( 'Location', 'birchschedule' ); ?>:</h4>
            <p>{location_name}</p>
        </li>
        <li>
            <h4><?php _e( 'Service', 'birchschedule' ); ?>:</h4>
            <p>{service_name}</p>
        </li>
        <li>
            <h4><?php _e( 'Provider', 'birchschedule' ); ?>:</h4>
            <p>{staff_name}</p>
        </li>
        <li>
            <h4><?php _e( 'Time', 'birchschedule' ); ?>:</h4>
            <p>{datetime}</p>
        </li>
    </ul>
</div>
        <?php
            return ob_get_clean();
        };

        $ns->upgrade_module = function() use( $ns ) {
            $ns->init_db();
        };

    } );
