(function($) {
    var params = birchschedule_view_appointments_edit;
    var locationsMap = params.locations_map;
    var servicesMap = params.services_map;
    var locationsOrder = params.locations_order;
    var locationsStaffMap = params.locations_staff_map;
    var staffOrder = params.staff_order;
    var locationsServicesMap = params.locations_services_map;
    var servicesStaffMap = params.services_staff_map;
    var servicesOrder = params.services_order;

    var datepicker;
    var ns = birchpress.namespace('birchschedule.view.appointments.edit.clientlist.reschedule', {

        __init__: function() {
            birchschedule.view.appointments.edit.clientlist.render.when(ns.isViewStateReschedule, ns.render);
            $('.wp-list-table.birs_clients .row-actions .reschedule a').click(function(eventObject) {
                var clientId = $(eventObject.target).attr('data-item-id');
                birchschedule.view.appointments.edit.clientlist.setViewState({
                    view: 'reschedule',
                    clientId: clientId
                });
            });
        },

        isViewStateReschedule: function(state) {
            return state.view === 'reschedule';
        },

        render: function(viewState) {
            birchschedule.view.appointments.edit.clientlist.render.defaultMethod(viewState);
            var clientId = viewState.clientId;
            if (viewState.view === 'reschedule') {
                var row = $('#birs_client_list_row_' + clientId);
                row.hide();

                var rescheduleRow = $('#birs_client_list_row_reschedule_' + clientId);
                var data = rescheduleRow.attr('data-reschedule-html');
                rescheduleRow.find('td').html(data);
                ns.initForm();
                rescheduleRow.show();
                birchpress.util.scrollTo(rescheduleRow);
            }
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

        setLocationValue: function() {
            var appointmentLocationId = Number($('#birs_appointment_location')
                .attr('data-value'));
            if (appointmentLocationId) {
                $('#birs_appointment_location').val(appointmentLocationId);
            }
        },

        setServiceValue: function() {
            var appointmentServiceId = Number($('#birs_appointment_service')
                .attr('data-value'));
            if (appointmentServiceId) {
                $('#birs_appointment_service').val(appointmentServiceId);
            }
        },

        setStaffValue: function() {
            var appointmentStaffId = Number($('#birs_appointment_staff')
                .attr('data-value'));
            if (appointmentStaffId) {
                $('#birs_appointment_staff').val(appointmentStaffId);
            }
        },

        ifOnlyShowAvailable: function() {
            return false;
        },

        reloadTimeOptions: function() {

        },

        initDatepicker: function() {
            var config = {
                ifOnlyShowAvailable: ns.ifOnlyShowAvailable
            };
            return birchschedule.view.initDatepicker(config);
        },

        reschedule: function() {
            var ajaxUrl = birchschedule.model.getAjaxUrl();
            var i18nMessages = birchschedule.view.getI18nMessages();
            var postData = $('form').serialize();
            postData += '&' + $.param({
                action: 'birchschedule_view_appointments_edit_clientlist_reschedule'
            });
            $.post(ajaxUrl, postData, function(data, status, xhr) {
                var result = birchschedule.model.parseAjaxResponse(data);
                if (result.errors) {
                    birchschedule.view.showFormErrors(result.errors);
                    $('#birs_appointment_reschedule').val(i18nMessages['Reschedule']);
                    $('#birs_appointment_reschedule').prop('disabled', false);
                } else if (result.success) {
                    var url = $.parseJSON(result.success.message).url;
                    window.location = _.unescape(url);
                }
            });
            $('#birs_appointment_reschedule').val(i18nMessages['Please wait...']);
            $('#birs_appointment_reschedule').prop('disabled', true);
        },

        refreshDatepicker: function() {
            birchschedule.view.refreshDatepicker(datepicker);
        },

        initForm: function() {
            birchschedule.view.initCountryStateField('birs_client_country', 'birs_client_state');
            var ajaxUrl = birchschedule.model.getAjaxUrl();

            ns.setLocationOptions();
            ns.setLocationValue();
            ns.setServiceOptions();
            ns.setServiceValue();
            ns.setStaffOptions();
            ns.setStaffValue();

            datepicker = ns.initDatepicker();
            ns.reloadTimeOptions();

            $('#birs_appointment_location').change(function() {
                ns.setServiceOptions();
                ns.setStaffOptions();
                ns.refreshDatepicker();
                ns.reloadTimeOptions();
            });

            $('#birs_appointment_service').change(function() {
                ns.setStaffOptions();
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
            $('#birs_appointment_reschedule_cancel').click(function() {
                birchschedule.view.appointments.edit.clientlist.setViewState({
                    view: 'list'
                });
            });
            $('#birs_appointment_reschedule').click(function() {
                ns.reschedule();
            });
        }
    });

})(jQuery);