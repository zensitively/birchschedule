(function($) {
    var ns = birchpress.namespace('birchschedule.aportal.cancel', {
        __init__: function() {
            $('#birs_cancel_appointment_yes').click(ns.onCancelClick);
        },

        onCancelClick: function() {
            var ajaxUrl = birchschedule.model.getAjaxUrl();
            var i18nMessages = birchschedule.view.getI18nMessages();

            var queryData = {
                action: 'birchschedule_aportal_cancel_appointment',
                apt_key: $('input[name=birs_appointment_key]').val()
            };
            $.ajax({
                url: ajaxUrl,
                data: queryData,
                success: function(doc) {
                    var response = birchschedule.model.parseAjaxResponse(doc);
                    var message = '';
                    if (response.success) {
                        message = response.success['message'];
                    } else {
                        message = response.errors['appointment_nonexist'];
                    }
                    $('#birs_cancel_appointment').html(message);
                    birchpress.util.scrollTo('#birs_cancel_appointment');
                }
            });
            $('#birs_cancel_appointment_yes').val(i18nMessages['Please wait...']);
        }
    });
})(jQuery);