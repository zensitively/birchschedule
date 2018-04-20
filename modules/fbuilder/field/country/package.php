<?php

birch_ns( 'birchschedule.fbuilder.field.country', function( $ns ) {

        global $birchschedule;

        $ns->init = function() use( $ns, $birchschedule ) {
            $birchschedule->fbuilder->field->render_field_elements->when( $ns->is_field_type_country, $ns->render_field_elements );

            $birchschedule->fbuilder->field->render_options_editing->when( $ns->is_field_type_country, $ns->render_options_editing );

            $birchschedule->fbuilder->field->render_field_editing->when( $ns->is_field_type_country, $ns->render_field_editing );

            $birchschedule->fbuilder->field->get_field_default_value->when( $ns->is_field_type_country, $ns->get_field_default_value );
        };

        $ns->is_field_type_country = function( $field ) {
            return $field['type'] === 'country';
        };

        $ns->get_field_default_value = function( $field ) use ( $ns, $birchschedule ) {
            return $birchschedule->fbuilder->field->selectable->get_field_default_value( $field );
        };

        $ns->render_field_elements = function( $field, $value=false ) use( $ns, $birchschedule ) {

            global $birchpress;

            $countries = $birchpress->util->get_countries();
?>
        <select name="<?php echo $birchschedule->fbuilder->field->get_dom_name( $field ); ?>" id="<?php echo $birchschedule->fbuilder->field->get_dom_name( $field ); ?>">
            <?php
            $birchpress->util->render_html_options( $countries,
                $value, $birchschedule->fbuilder->field->get_field_default_value( $field ) );
?>
        </select>
        <?php
        };

        $ns->render_options_editing = function( $field ) use( $ns, $birchschedule ) {

            global $birchpress;

            $birchschedule->fbuilder->field->render_options_editing->call_default( $field );
            $countries = $birchpress->util->get_countries();
            $fields_options = $birchschedule->fbuilder->get_fields_options();
            $default_country = $fields_options['client_country']['default_value'];
?>
        <li>
            <label><?php _e( 'Default Country', 'birchschedule' ); ?></label>
            <div id="birchschedule_fields_options_client_country_container">
                <select id="birchschedule_fields_options_client_country_default_value" name="birchschedule_fields_options[client_country][default_value]">
                    <?php $birchpress->util->render_html_options( $countries, $default_country ); ?>
                </select>
                <input type="hidden" id="birchschedule_fields_options_client_state_default_value" name="birchschedule_fields_options[client_state][default_value]" value="" disabled />
            </div>
        </li>
        <script type="text/javascript">
            jQuery(function($){
                $('#birchschedule_fields_options_client_country_default_value').change(function(){
                    $('#birchschedule_fields_options_client_state_default_value').prop('disabled', false);
                });
            });
        </script>
        <?php
        };

        $ns->render_field_editing = function( $field ) use( $birchschedule ) {

            $birchschedule->fbuilder->field->render_field_editing->call_default( $field );
        };

    } );
