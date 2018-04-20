<?php
//paypal per staff

birch_ns( 'birchschedule.ppstaff', function( $ns ) {

		global $birchschedule;

		$ns->init = function() use( $ns, $birchschedule ) {
				add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );
				add_action( 'init', array( $ns, 'wp_init' ) );
			};

		$ns->wp_init = function() use ( $ns, $birchschedule ) {
				if ( $ns->is_ppstaff_enabled() ) {
					add_filter( 'birchschedule_pintegration_get_paypal_email',
						array( $ns, 'get_paypal_email' ), 20, 2 );
				}
			};

		$ns->wp_admin_init = function() use ( $ns, $birchschedule ) {
				if ( $ns->is_ppstaff_enabled() ) {
					add_action( 'birchschedule_enotification_render_staff_additional_after',
						array( $ns, 'render_staff_paypal' ) );
					add_action( 'birchschedule_view_staff_save_post_after',
						array( $ns, 'save_staff_data' ) );
				}
			};

		$ns->is_ppstaff_enabled = function() {
				return false;
			};

		$ns->get_paypal_email = function( $email, $appointment1on1_id ) use( $ns, $birchschedule ) {
				if ( empty( $appointment1on1_id ) ) {
					return $email;
				}
				$appointment1on1 = $birchschedule->model->get( $appointment1on1_id, array(
						'keys' => array( '_birs_appointment_id' )
					) );
				if ( empty( $appointment1on1 ) || empty( $appointment1on1['_birs_appointment_id'] ) ) {
					return $email;
				}
				$appointment = $birchschedule->model->get( $appointment1on1['_birs_appointment_id'], array(
						'keys' => array( '_birs_appointment_staff' )
					) );
				if ( empty( $appointment ) || empty( $appointment['_birs_appointment_staff'] ) ) {
					return $email;
				}
				$staff = $birchschedule->model->get( $appointment['_birs_appointment_staff'], array(
						'keys' => array( '_birs_staff_paypal' )
					) );
				if ( empty( $staff ) || empty( $staff['_birs_staff_paypal'] ) ) {
					return $email;
				} else {
					return $staff['_birs_staff_paypal'];
				}
			};

		$ns->render_staff_paypal = function( $post ) use ( $ns, $birchschedule ) {

				$staff = $birchschedule->model->get( $post->ID, array(
						'keys' => array( '_birs_staff_paypal' )
					) );
				$paypal = $staff['_birs_staff_paypal'];
?>
        <div class="panel-wrap birchschedule">
            <table class="form-table">
                <tr>
                    <th>
                        <label for='birs_staff_paypal'><?php echo __( 'PayPal Email', 'birchschedule' ); ?></label>
                    </th>
                    <td>
                        <input name="birs_staff_paypal" id="birs_staff_paypal" class="regular-text" value="<?php echo $paypal; ?>"/>
                    </td>
                </tr>
            </table>
        </div>
        <?php
			};

		$ns->save_staff_data = function( $staff ) {
				if ( isset( $_POST['birs_staff_paypal'] ) ) {
					$email = $_POST['birs_staff_paypal'];
					update_post_meta( $staff['ID'], '_birs_staff_paypal', $email );
				}
			};

	} );
