(function($) {
    var params = birs_timeblocking_params;

    var ns = birchpress.namespace('birchschedule.tblocking', {
        __init__: function() {
            var dayOffs = $.parseJSON($('input[name=birs_staff_dayoffs]').val());
            if (_.isNull(dayOffs) || _.isUndefined(dayOffs)) {
                dayOffs = [];
            }
            var dateFormat = "mm/dd/yy";
            var datepickerOptions = $.extend(params.datepicker_i18n_options, {
                numberOfMonths: 2,
                changeMonth: true,
                changeYear: true,
                dateFormat: dateFormat,
                beforeShowDay: function(date) {
                    var dateText = $.datepicker.formatDate(dateFormat, date);
                    if (_.contains(dayOffs, dateText)) {
                        return [true, 'ui-state-highlight birs_inactive'];
                    } else {
                        return [true, 'birs_inactive'];
                    }
                },
                onSelect: function(dateText, instance) {
                    if (!_.contains(dayOffs, dateText)) {
                        dayOffs.push(dateText);
                    } else {
                        dayOffs = _.without(dayOffs, dateText);
                    }
                    $('input[name=birs_staff_dayoffs]').val(JSON.stringify(dayOffs));
                }
            });
            var dayOffsPicker = $('#birs_staff_dayoffs').datepicker(datepickerOptions);
            $('.birs_schedule_exception_new').click(function() {
                var locationId = $(this).attr('data-location-id');

                var postData = {
                    birs_location_id: locationId,
                    action: 'birchschedule_tblocking_new_staff_schedule_exception'
                };
                $.post(params.ajax_url, postData, function(data, status, xhr) {
                    $('#birs_schedule_exceptions_' + locationId).append(data);
                }, 'html');
            });
        }
    });
})(jQuery);