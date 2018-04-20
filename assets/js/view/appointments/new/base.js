(function($) {
    var params = birchschedule_view_appointments_new;
    var locationsMap = params.locations_map;
    var locationsOrder = params.locations_order;
    var locationsStaffMap = params.locations_staff_map;
    var staffOrder = params.staff_order;
    var locationsServicesMap = params.locations_services_map;
    var servicesStaffMap = params.services_staff_map;
    var servicesOrder = params.services_order;
    var servicePriceMap = params.services_prices_map;
    var serviceDurationMap = params.services_duration_map;

    var datepicker;
    var ns = birchpress.namespace('birchschedule.view.appointments.new', {

        __init__: function() {
            var ajaxUrl = birchschedule.model.getAjaxUrl();

            ns.setLocationOptions();
            ns.setLocation();
            ns.setServiceOptions();
            ns.setStaffOptions();
            ns.setStaffValue();
            ns.setDuration();

            datepicker = ns.initDatepicker();

            ns.reloadTimeOptions();

            $('#birs_appointment_location').change(function() {
                ns.setServiceOptions();
                ns.setStaffOptions();
                ns.setDuration();
                ns.refreshDatepicker();
                ns.reloadTimeOptions();
            });

            $('#birs_appointment_service').change(function() {
                ns.setStaffOptions();
                ns.setDuration();
                ns.refreshDatepicker();
                ns.reloadTimeOptions();
            });

            $('#birs_appointment_staff').change(function() {
                ns.refreshDatepicker();
                ns.reloadTimeOptions();
            });

            $('#birs_appointment_date').on('change', function() {
                ns.reloadTimeOptions();
            });

            ns.initClientInfo();

            $('#birs_appointment_actions_schedule').click(ns.schedule);
        },

        refreshDatepicker: function() {
            birchschedule.view.refreshDatepicker(datepicker);
        },

        setLocationOptions: function() {
            var options = birchschedule.model.getLocationOptions(locationsMap, locationsOrder);
            var html = birchschedule.view.getOptionsHtml(options);
            $('#birs_appointment_location').html(html);
        },

        setServiceOptions: function() {
            var locationId = $('#birs_appointment_location').val();
            var options = birchschedule.model.getServiceOptions(locationsServicesMap,
                locationId, servicesOrder);
            var html = birchschedule.view.getOptionsHtml(options);

            var serviceId = $('#birs_appointment_service').val();
            $('#birs_appointment_service').html(html);

            if (serviceId && _(options.order).has(serviceId)) {
                $('#birs_appointment_service').val(serviceId);
            }
        },

        setStaffOptions: function() {
            var locationId = $('#birs_appointment_location').val();
            var serviceId = $('#birs_appointment_service').val();
            var options = birchschedule.model.getStaffOptions(locationsStaffMap, servicesStaffMap,
                locationId, serviceId, staffOrder);
            var html = birchschedule.view.getOptionsHtml(options);

            var staffId = $('#birs_appointment_staff').val();
            $('#birs_appointment_staff').html(html);

            if (staffId && _(options.order).has(staffId)) {
                $('#birs_appointment_staff').val(staffId);
            }
        },


        setLocation: function() {
            var appointmentLocationId = Number($('#birs_appointment_location')
                .attr('data-value'));
            if (appointmentLocationId) {
                $('#birs_appointment_location').val(appointmentLocationId);
            }
        },

        setStaffValue: function() {
            var appointmentStaffId = Number($('#birs_appointment_staff')
                .attr('data-value'));
            if (appointmentStaffId) {
                $('#birs_appointment_staff').val(appointmentStaffId);
            }
        },

        setDuration: function() {
            var serviceId = $('#birs_appointment_service').val();
            if (serviceId) {
                var duration = serviceDurationMap[serviceId]['duration'];
                if (duration !== null || duration !== undefined) {
                    $('#birs_appointment_duration').val(duration);
                }
            }
        },

        ifOnlyShowAvailable: function() {
            return false;
        },

        initDatepicker: function() {
            var config = {
                ifOnlyShowAvailable: ns.ifOnlyShowAvailable
            };
            return birchschedule.view.initDatepicker(config);
        },

        initClientInfo: function() {
            birchschedule.view.initCountryStateField('birs_client_country', 'birs_client_state');
        },

        reloadTimeOptions: function() {

        },

        schedule: function() {
            var ajaxUrl = birchschedule.model.getAjaxUrl();
            var i18nMessages = birchschedule.view.getI18nMessages();
            var postData = $('form').serialize();
            postData += '&' + $.param({
                action: 'birchschedule_view_appointments_new_schedule'
            });
            $.post(ajaxUrl, postData, function(data, status, xhr) {
                var result = birchschedule.model.parseAjaxResponse(data);
                if (result.errors) {
                    birchschedule.view.showFormErrors(result.errors);
                    $('#birs_appointment_actions_schedule').val(i18nMessages['Schedule']);
                    $('#birs_appointment_actions_schedule').prop('disabled', false);
                } else if (result.success) {
                    var url = $.parseJSON(result.success.message).url;
                    window.location = _.unescape(url);
                }
            });
            $('#birs_appointment_actions_schedule').val(i18nMessages['Please wait...']);
            $('#birs_appointment_actions_schedule').prop('disabled', true);
        }
    });
})(jQuery);