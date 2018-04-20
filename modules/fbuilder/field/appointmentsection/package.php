<?php

birch_ns( 'birchschedule.fbuilder.field.appointmentsection', function( $ns ) {

		global $birchschedule;

		$ns->init = function() use ( $ns, $birchschedule ) {
			$birchschedule->fbuilder->field->render_field_view_frontend->when( $ns->is_field_type_appointment_section, $ns->render_field_view_frontend );

			$birchschedule->fbuilder->field->render_field_view->when( $ns->is_field_type_appointment_section, $ns->render_field_view );

			$birchschedule->fbuilder->field->get_field_title->when( $ns->is_field_type_appointment_section, $ns->get_field_title );

			$birchschedule->fbuilder->field->render_options_editing->when( $ns->is_field_type_appointment_section, $ns->render_options_editing );
		};

        $ns->is_field_type_appointment_section = function( $field ) {
            return $field['type'] === 'appointment_section';
        };

		$ns->render_field_view_frontend = function( $field, $value=false, $errors=false ) use ( $ns, $birchschedule ) {

			$labels = $field['appointment_details']['labels'];
?>
				<li class="birs_form_field birs_appointment_section">
					<h2 class="birs_section"><?php echo $field['label'] ?></h2>
				</li>
				<li class="birs_form_field birs_appointment_location">
					<label><?php echo $labels['location']; ?></label>
					<div class="birs_field_content">
						<select id="birs_appointment_location" name="birs_appointment_location"></select>
					</div>
				</li>
				<li class="birs_form_field birs_appointment_service">
					<label><?php echo $labels['service']; ?></label>
					<div class="birs_field_content">
						<select id="birs_appointment_service" name="birs_appointment_service"></select>
					</div>
				</li>
				<li class="birs_form_field birs_appointment_staff">
					<label><?php echo $labels['service_provider']; ?></label>
					<div class="birs_field_content">
						<select id="birs_appointment_staff" name="birs_appointment_staff"></select>
						<input type="hidden" id="birs_appointment_avaliable_staff" name="birs_appointment_avaliable_staff" />
					</div>
					<div class="birs_error" id="birs_appointment_service_error" style="<?php echo $birchschedule->fbuilder->field->get_error_display_style( $errors, 'birs_appointment_service' ); ?>">
						<?php echo $birchschedule->fbuilder->field->get_error_message( $errors, 'birs_appointment_service' ); ?>
					</div>
				</li>
				<li class="birs_form_field birs_appointment_date">
					<label><?php echo $labels['date']; ?></label>
					<div class="birs_field_content">
						<input id="birs_appointment_date" name="birs_appointment_date" type="hidden">
						<div id="birs_appointment_datepicker">
						</div>
					</div>
					<div class="birs_error" id="birs_appointment_date_error" style="<?php echo $birchschedule->fbuilder->field->get_error_display_style( $errors, 'birs_appointment_date' ); ?>">
						<?php echo $birchschedule->fbuilder->field->get_error_message( $errors, 'birs_appointment_date' ); ?>
					</div>
				</li>
				<li class="birs_form_field birs_appointment_time">
					<label><?php echo $labels['time']; ?></label>
					<div class="birs_field_content">
					</div>
					<div class="birs_error" id="birs_appointment_time_error" style="<?php echo $birchschedule->fbuilder->field->get_error_display_style( $errors, 'birs_appointment_time' ); ?>">
						<?php echo $birchschedule->fbuilder->field->get_error_message( $errors, 'birs_appointment_time' ); ?>
					</div>
				</li>
<?php
		};

		$ns->render_field_view = function( $field ) use ( $ns, $birchschedule ) {

			$birchschedule->fbuilder->field->section->render_field_view( $field );
?>
				<h3 id="birchschedule_appointment_details">
					<?php _e( "Appointment Details ('Location','Service','Provider' & 'Date&Time' fields are predefined, click 'edit' to change the labels)", "birchschedule" ); ?>
				</h3>
<?php
		};

		$ns->get_field_title = function( $field ) {

			$title = __( 'Predefined', 'birchschedule' ) . ' - ' . $field['label'];
			return $title;
		};

		$ns->render_options_editing = function( $field ) use ( $ns, $birchschedule ) {

			$label = $field['label'];
			$input_id = $field['field_id'] . '_label';
			$labels = $field['appointment_details']['labels'];
			$location_label = esc_attr( $labels['location'] );
			$service_label = esc_attr( $labels['service'] );
			$service_provider_label = esc_attr( $labels['service_provider'] );
			$date_label = esc_attr( $labels['date'] );
			$time_label = esc_attr( $labels['time'] );
?>
				<li>
					<label><?php _e( 'Labels', 'birchschedule' ); ?></label>
					<table style="width: 100%;">
					<tr>
						<td><label><?php _e( 'Appointment Info', 'birchschedule' ); ?></label></td>
						<td><input type="text" id="<?php echo $input_id; ?>" name="birchschedule_fields_options[<?php echo $field['field_id']; ?>][label]" value="<?php echo $label; ?>"/></td>
					</tr>
					<tr>
						<td><label><?php _e( 'Location', 'birchschedule' ); ?></label>
						<td><input type="text" name="birchschedule_fields_options[<?php echo $field['field_id']; ?>][appointment_details][labels][location]" value="<?php echo $location_label; ?>"/></td>
					</tr>
					<tr>
						<td><label><?php _e( 'Service', 'birchschedule' ); ?></label>
						<td><input type="text" name="birchschedule_fields_options[<?php echo $field['field_id']; ?>][appointment_details][labels][service]" value="<?php echo $service_label; ?>"/></td>
					</tr>
					<tr>
						<td><label><?php _e( 'Provider', 'birchschedule' ); ?></label>
						<td><input type="text" name="birchschedule_fields_options[<?php echo $field['field_id']; ?>][appointment_details][labels][service_provider]" value="<?php echo $service_provider_label; ?>"/></td>
					</tr>
					<tr>
						<td><label><?php _e( 'Date', 'birchschedule' ); ?></label>
						<td><input type="text" name="birchschedule_fields_options[<?php echo $field['field_id']; ?>][appointment_details][labels][date]" value="<?php echo $date_label; ?>"/></td>
					</tr>
					<tr>
						<td><label><?php _e( 'Time', 'birchschedule' ); ?></label>
						<td><input type="text" name="birchschedule_fields_options[<?php echo $field['field_id']; ?>][appointment_details][labels][time]" value="<?php echo $time_label; ?>"/></td>
					</tr>
					</table>
				</li>
<?php
		};

	} );
