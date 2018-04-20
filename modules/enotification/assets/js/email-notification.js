(function($) {
    var showTab = birchschedule.view.admincommon.showTab;

    var ns = birchpress.namespace('birchschedule.enotification', {
        __init__: function() {
            $('.wp-tab-bar li').click(function() {
                showTab($(this));
                return false;
            });
            var showDetail = function(checkbox) {
                var detailsId = checkbox.attr("data-details-id");
                if (checkbox.is(":checked")) {
                    $("#" + detailsId).show();
                } else {
                    $("#" + detailsId).hide();
                }
            };
            $('input[data-details-id]').change(function() {
                showDetail($(this));
            });
            $('a.birs-toggle-templates-editor').click(function() {
                var templatesEditorSel = $(this).attr('href');
                $(templatesEditorSel).toggle();
                if ($(templatesEditorSel).is(':visible')) {
                    $(this).children('span').html('-');
                } else {
                    $(this).children('span').html('+');
                }
                return false;
            });
        }
    });
})(jQuery);