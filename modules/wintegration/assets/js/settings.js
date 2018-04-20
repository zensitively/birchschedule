(function($) {
    var ns = birchpress.namespace('birchschedule.wintegration.settings', {
        __init__: function() {
            var ajaxUrl = birchschedule.model.getAjaxUrl();
            $('#birs_wc_new_product').click(function() {
                var postData = {
                    action: 'birchschedule_wintegration_new_wc_product_settings'
                };
                $.post(ajaxUrl, postData, function(data, status, xhr) {
                    $('#birs_wc_products').append(data);
                }, 'html');
            });
        }
    });
})(jQuery);