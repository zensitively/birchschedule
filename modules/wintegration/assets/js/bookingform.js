(function($) {
    var ns = birchpress.namespace('birchschedule.wintegration.bookingform', {
        __init__: function() {
            birchschedule.view.bookingform.getFormQueryData.defaultMethod = ns.getFormQueryData;
            var form = ns.getFormEl();
            var addToCartBtn = form.find('.single_add_to_cart_button');
            addToCartBtn.on('click', function() {
                ns.addToCart();
                return false;
            });
            $('#birs_appointment_service').on('change', function() {
                ns.setPrice();
            });
            ns.setPrice();
        },

        getFormEl: function() {
            var form = $("#birs_appointment_form").parents('form:first');
            return form;
        },

        getFormQueryData: function() {
            var inputFields = ns.getFormEl().find("input[name!='add-to-cart'], textarea, select");
            var postData = inputFields.serialize();

            return postData;
        },

        addToCart: function() {
            var ajaxUrl = birchschedule.model.getAjaxUrl();
            var postData = ns.getFormQueryData();
            postData += '&' + $.param({
                action: 'birchschedule_wintegration_wc_validate_booking_info'
            });
            $.post(ajaxUrl, postData, function(data, status, xhr) {
                $('#birs_please_wait').hide("slow");
                var result = birchschedule.model.parseAjaxResponse(data);
                if (result.errors) {
                    birchschedule.view.showFormErrors(result.errors);
                } else if (result.success) {
                    ns.getFormEl().submit();
                }
            });
            $('.birs_error').hide("");
            $('#birs_please_wait').show("slow");
        },

        setPrice: function() {
            var serviceId = $('#birs_appointment_service').val();
            var pricesMap = birchschedule.view.bookingform.getServicesPricesMap();
            $('#birs_appointment_form .price .amount').html(pricesMap[serviceId].formatted_pre_payment_fee);
        }
    });
})(jQuery);