(function($) {
    var ns = birchpress.namespace('birchschedule.fbuilder', {
        __init__: function() {
            postboxes.pbhide = function(id) {
                $('#' + id).toggleClass('closed');
            }
            postboxes.save_state = function() {};

            $('#client_email_required').attr('disabled', true);
            $('#client_password_required').attr('disabled', true);
            $('input[name="birchschedule_fields_options[client_email][visibility]"]').attr('disabled', true);

            var showConfirmationSettings = function(type) {
                $('.birs_confirmation_settings').hide();
                $('#birchschedule_fields_options_submit_confirmation_settings_' + type).show();
            };
            $('input[name="birchschedule_fields_options[submit][confirmation][type]"]').change(function() {
                showConfirmationSettings($(this).val());
            });
            showConfirmationSettings($('input[name="birchschedule_fields_options[submit][confirmation][type]"]:checked').val());

            $('#birs_toolbox_actions_add_field').click(function() {
                var url = $('#birs_toolbox_field_type').val();
                window.location.href = url;
            });

            ns.changeFieldsAppearance();
            $('.meta-box-sortables').on('sortstop', function() {
                ns.changeFieldsAppearance();
            });
        },

        changeFieldsAppearance: function() {
            $('.meta-box-sortables .postbox').removeClass('birs_removed_field');
            $('.meta-box-sortables .postbox').find('select, input').prop('disabled', false);
            $("#birs_submit_box").nextAll().addClass('birs_removed_field');
            $('#birs_submit_box').nextAll().find('select, input').prop('disabled', true);
        }
    });
}(jQuery));