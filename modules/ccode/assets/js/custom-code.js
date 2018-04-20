(function($) {
    var params = birs_custom_code_params;
    var showTab = birchschedule.view.admincommon.showTab;

    var ns = birchpress.namespace('birchschedule.ccode', {
        __init__: function() {
            $('.wp-tab-bar li').click(function() {
                showTab($(this));
                return false;
            });
            var phpTextArea = $('#birs_custom_code_php').get(0);
            var phpCodeMirror = CodeMirror.fromTextArea(phpTextArea, {
                theme: 'neat',
                lineNumbers: true,
                mode: "application/x-httpd-php",
                indentUnit: 4,
                indentWithTabs: true
            });
            var javascriptTextArea = $('#birs_custom_code_javascript').get(0);
            var javascriptCodeMirror = CodeMirror.fromTextArea(javascriptTextArea, {
                theme: 'neat',
                lineNumbers: true,
                mode: "text/javascript",
                indentUnit: 4,
                indentWithTabs: true
            });
            var cssEditors = {};
            $.each(params.shortcodes, function(index, key) {
                var cssTextArea = $('#birs_custom_code_css_' + key).get(0);
                cssEditors[key] = CodeMirror.fromTextArea(cssTextArea, {
                    theme: 'neat',
                    lineNumbers: true,
                    mode: "css",
                    indentUnit: 4,
                    indentWithTabs: true
                });
            });
            $.each(params.shortcodes, function(index, key) {
                if (key != 'bpscheduler_booking_form') {
                    var blockId = "birs_css_" + key;
                    $('#' + blockId).hide();
                }
            });
            $('#birs_custom_code_box form').submit(function() {
                var php = phpCodeMirror.getDoc().getValue();
                $('#birs_custom_code_php').html(php);
                var javascript = javascriptCodeMirror.getDoc().getValue();
                $('#birs_custom_code_javascript').html(php);
                $.each(cssEditors, function(index, key) {
                    var css = key.getDoc().getValue();
                    $('#birs_custom_code_css_' + index).html(css);
                });
            });
        }
    });
})(jQuery);