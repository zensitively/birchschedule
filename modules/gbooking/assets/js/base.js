(function($) {
    var ns = birchpress.namespace('birchschedule.gbooking', {
        __init__: function() {
            birchpress.addAction('birchschedule.gbooking.showAddClientFormBefore', function() {
                birchschedule.view.appointments.edit.clientlist.setViewState({
                    view: 'list'
                });
            });
            birchpress.addAction('birchschedule.view.appointments.edit.clientlist.setViewStateAfter', function(viewState) {
                if (viewState.view !== 'list') {
                    birchschedule.gbooking.hideAddClientForm();
                }
            });

            $('#birs_appointment_actions_add_client').click(function() {
                ns.showAddClientForm();
            });
            $('#birs_appointment_actions_change_capacity').click(function() {
                ns.changeAppointmentCapacity();
            });
        },

        showAddClientForm: function() {
            $('#birs_appointment_actions_add_client').hide();
            var data = $('#birs_appointment_add_client_form').attr('data-add-client-html');
            $('#birs_appointment_add_client_form').html(data);
            birchpress.util.scrollTo('#birs_appointment_add_client_form');
        },

        hideAddClientForm: function() {
            $('#birs_appointment_actions_add_client').show();
            $('#birs_appointment_add_client_form').html('');
        },

        initAddClientForm: function() {
            birchschedule.view.initCountryStateField('birs_client_country', 'birs_client_state');
            $('#birs_appointment_actions_add_client_cancel').click(function() {
                ns.hideAddClientForm();
            });
            $('#birs_appointment_actions_add_client_save').click(function() {
                ns.addClient();
            });
        },

        addClient: function() {
            var ajaxUrl = birchschedule.model.getAjaxUrl();
            var i18nMessages = birchschedule.view.getI18nMessages();
            var postData = $('form').serialize();
            postData += '&' + $.param({
                action: 'birchschedule_gbooking_add_client'
            });
            $.post(ajaxUrl, postData, function(data, status, xhr) {
                var result = birchschedule.model.parseAjaxResponse(data);
                if (result.errors) {
                    birchschedule.view.showFormErrors(result.errors);
                    $('#birs_appointment_actions_add_client_save').val(i18nMessages['Save']);
                    $('#birs_appointment_actions_add_client_save').prop('disabled', false);
                } else if (result.success) {
                    window.location.reload();
                }
            });
            $('#birs_appointment_actions_add_client_save').val(i18nMessages['Please wait...']);
            $('#birs_appointment_actions_add_client_save').prop('disabled', true);
        },

        changeAppointmentCapacity: function() {
            var ajaxUrl = birchschedule.model.getAjaxUrl();
            var i18nMessages = birchschedule.view.getI18nMessages();
            var postData = $.param({
                action: 'birchschedule_gbooking_change_appointment_capacity',
                birs_appointment_capacity: $('#birs_appointment_capacity').val(),
                birs_appointment_id: $('#birs_appointment_id').val()
            });
            $.post(ajaxUrl, postData, function(data, status, xhr) {
                window.location.reload();
                $('#birs_appointment_actions_change_capacity').val(i18nMessages['Change']);
                $('#birs_appointment_actions_change_capacity').prop('disabled', false);
            });
            $('#birs_appointment_actions_change_capacity').val(i18nMessages['Please wait...']);
            $('#birs_appointment_actions_change_capacity').prop('disabled', true);
        }
    });
})(jQuery);