(function($) {
    var params = birchschedule_view_admincommon;

    var ns = birchpress.namespace('birchschedule.view.admincommon', {

        __init__: function() {
            ns.disableEnterKey();
        },

        getI18nMessages: function() {
            return params.i18n_messages;
        },

        showMessage: function(selector, message, options) {
            options = _.extend({
                life: 1000,
                position: 'bottom-right'
            }, options);
            if (selector === '') {
                $.jGrowl(message, options);
            } else {
                $(selector).jGrowl(message, options);
                $(selector).jGrowl('update');
            }
        },

        hideMessage: function(selector) {
            if (selector === '') {
                $.jGrowl('close');
            } else {
                $(selector).jGrowl("close");
            }
        },

        showTab: function(tab) {
            tab.addClass("wp-tab-active");
            tab.siblings().removeClass("wp-tab-active");
            var blockSelector = tab.children("a").attr('href');
            $(blockSelector).show();
            $(blockSelector).siblings('.wp-tab-panel').hide();
        },

        disableEnterKey: function() {
            $('form input, form select').on("keyup keypress", function(e) {
                var code = e.keyCode || e.which;
                if (code == 13) {
                    e.preventDefault();
                    return false;
                }
            });
        }

    });
})(jQuery);