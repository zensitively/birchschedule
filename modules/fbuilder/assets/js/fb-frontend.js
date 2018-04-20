(function($) {
    var ns = birchpress.namespace('birchschedule.fbuilder.frontend', {
        __init__: function() {
            birchpress.addFilter('birchschedule.view.bookingform.bookSucceed', function(fns) {
                fns['redirect'] = function(message) {
                    window.location = message;
                }
                return fns;
            });

            function changeClientType(el) {
                var clientType = $(el).val();
                $("li[data-shown-client-type]").hide();
                $('.birs_error').hide();
                $("li[data-shown-client-type~='" + clientType + "']").show();
                if (clientType === "new") {
                    $('#birs_client_forget_password').hide();
                } else {
                    $('#birs_client_forget_password').show();
                }
            }

            birchschedule.view.initCountryStateField('birs_client_country', 'birs_client_state');

            $('input[name="birs_client_type"]').change(function() {
                changeClientType(this);
            });
            changeClientType('input[name="birs_client_type"]:checked');
        }
    });
})(jQuery);