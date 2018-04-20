(function($) {
    var nsState;
    var ns = birchpress.namespace('birchschedule.archive', {

        __init__: function() {
            ns.renderPanel();
        },

        getCss: function() {
            var styles =
                '<style type="text/css"' +
                '   ' +
                '</style>';
            return styles;
        },

        getPanelHtml: function() {
            var gState = ns.getState();
            var str =
                '<table class="form-table">' +
                '   <tbody>' +
                '       <tr>' +
                '           <th>' +
                '               <label for="birs_staff_archive_year"><%= i18n.messages["Year"] %></label>' +
                '           </th>' +
                '           <td>' + ns.getYearsHtml() +
                '           </td>' +
                '       </tr>' +
                '       <tr>' +
                '           <th>' +
                '               <label for="birs_staff_archive_month"><%= i18n.messages["Month"] %></label>' +
                '           </th>' +
                '           <td>' + ns.getMonthsHtml() +
                '           </td>' +
                '       </tr>' +
                '   </tbody>' +
                '   <tfoot>' +
                '       <tr>' +
                '           <th>' +
                '               <label>&nbsp;</label>' +
                '           </th>' +
                '           <td>' + ns.getActionHtml() +
                '           </td>' +
                '       </tr>' +
                '   </tfoot>' +
                '</table>';
            return _.template(str)(gState);
        },

        getActionHtml: function() {
            var gState = ns.getState();
            if (gState.action.state === 'display') {
                return _.template(
                    '<input id="birs_staff_actions_archive" type="button" class="button-primary" value="<%= i18n.messages["Archive"] %>" />'
                )(gState);
            }
            if (gState.action.state === 'archiving') {
                return _.template(
                    '<input type="button" class="button-primary" value="<%= i18n.messages["Please wait..."] %>" disabled />'
                )(gState);
            }
            if (gState.action.state === 'confirm') {
                return _.template(
                    '<div>' +
                    '   <%= i18n.messages["This operation cannot be undone. Would you like to proceed?"] %>' +
                    '</div>' +
                    '<div>' +
                    '   <input id="birs_staff_actions_archive_confirm_no" type="button" class="button-primary" value="<%= i18n.messages["No"] %>" />' +
                    '   <a id="birs_staff_actions_archive_confirm_yes" href="javascript:void(0);"><%= i18n.messages["Yes"] %></a>' +
                    '</div>'
                )(gState);
            }
        },

        getYearsHtml: function() {
            var gState = ns.getState();
            var disabled = 'disabled';
            if ('display' === gState.action.state) {
                disabled = '';
            }
            var html = '';
            var template = _.template(
                '<label>' +
                '   <input name="birs_staff_archive_year" ' +
                '       type="radio" value="<%= year %>" <%= checked %> <%= disabled %> />' +
                '   <%= year %>' +
                '</label>'
            );
            _.each(gState.dateRange, function(value, index) {
                var checked = '';
                if (gState.defaultYear === index) {
                    checked = ' checked="checked" ';
                }
                html += template({
                    year: index,
                    checked: checked,
                    disabled: disabled
                });
            });
            return html;
        },

        getMonthsHtml: function(arg) {
            var html = '';
            _.each(ns.getState().dateRange, function(value, index) {
                html += ns.getMonthsOfYearHtml({
                    year: index,
                    months: value
                });
            });
            return html;
        },

        getMonthsOfYearHtml: function(arg) {
            var gState = ns.getState();
            var disabled = 'disabled';
            if ('display' === gState.action.state) {
                disabled = '';
            }
            var year = arg.year;
            var months = arg.months;
            var html = '';
            if (gState.defaultYear === year) {
                html += '<div>';
                _.each(months, function(value, index) {
                    html += ns.getMonthHtml({
                        year: year,
                        month: index,
                        state: value,
                        disabled: disabled
                    });
                });
                html += '</div>';
                if (_.contains(gState.errors, 'requireMonth')) {
                    html += _.template('<div class="birs_error"><%= gState.i18n.errorMessages.requireMonth %></div>')({
                        'gState': gState
                    });
                }
            }
            return html;
        },

        getMonthHtml: function(arg) {
            var state = arg.state;
            var template;
            if (state.archived) {
                var staff_id = $('#post_ID').val();
                state.link = birchschedule.model.getAdminUrl() + 
                    'admin-post.php?action=birchschedule_archive_get_archived_calendar' + 
                    '&staff_id=' + staff_id + 
                    '&year=' + arg.year +
                    '&month=' + arg.month;
                template = _.template(
                    '<label>' +
                    '   <a href="<%= state.link %>" target="_blank" style="margin-right: 4px;margin-left:4px;" >' +
                    '       <%= state.text %>' +
                    '   </a>' +
                    '</label>'
                );
            } else {
                var checked = '';
                var defaultMonth = ns.getState().defaultMonths[arg.year];
                if (defaultMonth === arg.month) {
                    checked = ' checked="checked" ';
                }
                arg.checked = checked;
                template = _.template(
                    '<label>' +
                    '   <input name="birs_staff_archive_month" ' +
                    '       type="radio" value="<%= month %>" <%= checked %> <%= disabled %> />' +
                    '   <%= state.text %>' +
                    '</label>'
                );
            }
            return template(arg);
        },

        renderPanel: function() {
            var html = ns.getPanelHtml();
            $('#birchschedule_staff_archive .panel-wrap').html(html);
            $('input[name=birs_staff_archive_year]').change(function() {
                var gState = ns.getState();
                gState.defaultYear = $(this).val();
                ns.setState(gState);
            });
            $('input[name=birs_staff_archive_month]').change(function() {
                var gState = ns.getState();
                gState.defaultMonths[gState.defaultYear] = $(this).val();
                ns.setState(gState);
            });
            $('#birs_staff_actions_archive').click(function() {
                var gState = ns.getState();
                gState.errors = [];
                if (_.isEmpty(gState.defaultMonths[gState.defaultYear])) {
                    gState.errors.push('requireMonth');
                    gState.action.state = 'display';
                } else {
                    gState.action.state = 'confirm';
                }
                ns.setState(gState);
            });
            $('#birs_staff_actions_archive_confirm_no').click(function() {
                var gState = ns.getState();
                gState.action.state = 'display';
                ns.setState(gState);
            });
            $('#birs_staff_actions_archive_confirm_yes').click(function() {
                var gState = ns.getState();
                gState.action.state = 'archiving';
                ns.setState(gState);
                var ajaxUrl = birchschedule.model.getAjaxUrl();
                var postData = $.param({
                    action: 'birchschedule_archive_do_archive',
                    year: $('input[name="birs_staff_archive_year"]:checked').val(),
                    month: $('input[name="birs_staff_archive_month"]:checked').val(),
                    staff_id: $('#post_ID').val()
                });
                $.post(ajaxUrl, postData, function(data, status, xhr) {
                    var state = ns.getState();
                    state.dateRange = data['selections'];
                    state.action.state = 'display';
                    ns.setState(state);
                }, 'json');
            });
        },

        getState: function() {
            var nsMockData = {
                dateRange: birchschedule_archive.selections,
                defaultYear: birchschedule_archive.default_year,
                defaultMonths: {
                },
                action: {
                    state: 'display' //display, archiving, confirm
                },
                i18n: {
                    messages: {
                        'Year': 'Year',
                        'Month': 'Month',
                        'Archive': 'Archive',
                        'Please wait...': 'Please wait...',
                        'This operation cannot be undone. Would you like to proceed?': 'This operation cannot be undone. Would you like to proceed?',
                        'Yes': 'Yes',
                        'No': 'No'
                    },
                    errorMessages: {
                        'requireMonth': 'Please choose a month'
                    }
                },
                errors: []
            };
            if(!nsState) {
                nsState = nsMockData;
            }
            return nsState;
        },

        setState: function(value) {
            nsState = value;
            ns.renderPanel();
        }

    });
})(jQuery);