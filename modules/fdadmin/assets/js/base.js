(function($) {
    var ns = birchpress.namespace('birchschedule.fdadmin', {
        __init__: function() {
            birchpress.addAction('birchschedule.view.appointments.edit.clientlist.reschedule.initFormBefore', function() {
                ns.redefineFunctionsEdit();
                $('#birs_show_all_time').change(function() {
                    birchschedule.view.appointments.edit.clientlist.reschedule.refreshDatepicker();
                    birchschedule.view.appointments.edit.clientlist.reschedule.reloadTimeOptions();
                });
            });
        },

        getFormQueryData: function() {
            var postData = $('form').serialize();
            return postData;
        },

        ifOnlyShowAvailable: function() {
            var result = !$('#birs_show_all_time').is(':checked');
            return result;
        },

        reloadTimeOptions: function(action) {
            var time = $('#birs_appointment_time').val();
            $('#birs_appointment_time').html('');

            var ajaxUrl = birchschedule.model.getAjaxUrl();
            var postData = ns.getFormQueryData();
            postData += '&' + $.param({
                action: action
            });
            $.post(ajaxUrl, postData, function(data, status, xhr) {
                $('#birs_appointment_time').html(data);
                var options = $('#birs_appointment_time option').toArray();
                var timeValues = _.map(options, function(option) {
                    return $(option).val();
                });
                if (time && _.contains(timeValues, time)) {
                    $('#birs_appointment_time').val(time);
                }
            }, 'html');
        },

        redefineFunctionsNew: function() {
            birchschedule.view.appointments.new.reloadTimeOptions.defaultMethod = function() {
                ns.reloadTimeOptions('birchschedule_fdadmin_get_available_time_options');
            };
            birchschedule.view.appointments.new.ifOnlyShowAvailable.defaultMethod = ns.ifOnlyShowAvailable;
        },

        redefineFunctionsEdit: function() {
            birchschedule.view.appointments.edit.clientlist.reschedule.reloadTimeOptions.defaultMethod = function() {
                ns.reloadTimeOptions('birchschedule_fdadmin_get_available_reschedule_time_options');
            };

            var selectedStaffId = $('#birs_appointment_staff').attr('data-value');
            var selectedDate = $('#birs_appointment_date').val();

            birchschedule.view.appointments.edit.clientlist.reschedule.initDatepicker.defaultMethod = function() {
                var config = {
                    ifOnlyShowAvailable: ns.ifOnlyShowAvailable,
                    ifShowDayForDatepicker: function(date, staffId, locationId, serviceId) {
                        if ($.datepicker.formatDate('mm/dd/yy', date) === selectedDate &&
                            staffId === selectedStaffId) {
                            return [true, ''];
                        } else {
                            return birchschedule.view.ifShowDayForDatepicker(date, staffId, locationId, serviceId);
                        }
                    }
                };
                return birchschedule.view.initDatepicker(config);
            };
        }
    });
    birchpress.addAction('birchschedule.view.appointments.new.__init__Before', function() {
        ns.redefineFunctionsNew();
        $('#birs_show_all_time').change(function() {
            birchschedule.view.appointments.new.refreshDatepicker();
            birchschedule.view.appointments.new.reloadTimeOptions();
        });
    });

})(jQuery);