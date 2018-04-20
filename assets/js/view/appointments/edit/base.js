(function($) {

    var ns = birchpress.namespace('birchschedule.view.appointments.edit', {

        __init__: function() {
            var datepickerI18nOptions = birchschedule.view.getDatepickerI18nOptions();
            var datepickerOptions = $.extend(datepickerI18nOptions, {
                changeMonth: false,
                changeYear: false,
                'dateFormat': 'mm/dd/yy',
                beforeShowDay: function(date) {
                    return [true, ""];
                },
                hideIfNoPrevNext: true
            });
            var datepicker = $('#birs_appointment_view_datepicker').datepicker(datepickerOptions);
            var dateValue = $('#birs_appointment_view_datepicker').attr('data-date-value');
            datepicker.datepicker('setDate', dateValue);
        }
    });

})(jQuery);