(function($) {
    var ns = birchpress.namespace('birchschedule.view.services.edit', {

        __init__: function() {
            var priceTypeEl = $("#birs_service_price_type");
            var priceEl = $('#birs_service_price');

            if (priceTypeEl.attr('value') !== 'fixed') {
                priceEl.hide();
            }
            priceTypeEl.change(function() {
                if ($(this).attr('value') === 'fixed') {
                    priceEl.show();
                } else {
                    priceEl.hide();
                }
            });
        }
    });

})(jQuery);