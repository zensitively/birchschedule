(function($) {
    var ns = birchpress.namespace('birchschedule.view.appointments.edit.clientlist.payments', {

        __init__: function() {
            birchschedule.view.appointments.edit.clientlist.render.when(ns.isViewStatePayments, ns.render);
            $('.wp-list-table.birs_clients .row-actions .payments a').click(function(eventObject) {
                var clientId = $(eventObject.target).attr('data-item-id');
                birchschedule.view.appointments.edit.clientlist.setViewState({
                    view: 'payments',
                    clientId: clientId
                });
            });
        },

        isViewStatePayments: function(state) {
            return state.view === 'payments';
        },

        render: function(viewState) {
            birchschedule.view.appointments.edit.clientlist.render.defaultMethod(viewState);
            var clientId = viewState.clientId;
            if (viewState.view === 'payments') {
                var row = $('#birs_client_list_row_' + clientId);
                var paymentsRow = $('#birs_client_list_row_payments_' + clientId);

                var data = paymentsRow.attr('data-payments-html');
                paymentsRow.find('td').html(data);
                ns.initForm();
                row.hide();
                paymentsRow.show();
                birchpress.util.scrollTo(paymentsRow, 600, -20);
            }
        },

        initNewPayment: function() {
            $('#birs_payments_table tbody tr .row-actions .delete a').click(function() {
                var paymentTRID = $(this).attr('data-payment-trid');
                $('#birs_payments_table tbody tr[data-payment-trid="' +
                    paymentTRID + '"]').remove();
            });
        },

        addPayment: function() {
            var ajaxUrl = birchschedule.model.getAjaxUrl();
            var postData = $('form').serialize();
            var i18nMessages = birchschedule.view.getI18nMessages();
            postData += '&' + $.param({
                action: 'birchschedule_view_appointments_edit_clientlist_payments_add_new_payment'
            });
            $.post(ajaxUrl, postData, function(data, status, xhr) {
                $(data).prependTo('#birs_payments_table tbody');
                ns.initNewPayment();
                ns.save();
            }, 'html');
            $('#birs_add_payment').val(i18nMessages['Please wait...']);
            $('#birs_add_payment').prop('disabled', true);
        },

        save: function() {
            var ajaxUrl = birchschedule.model.getAjaxUrl();
            var postData = $('form').serialize();
            postData += '&' + $.param({
                action: 'birchschedule_view_appointments_edit_clientlist_payments_make_payments'
            });
            $.post(ajaxUrl, postData, function(data, status, xhr) {
                var result = birchschedule.model.parseAjaxResponse(data);
                if (result.errors) {
                    birchschedule.view.showFormErrors(result.errors);
                } else if (result.success) {
                    window.location.reload();
                }
            });
        },

        initForm: function() {
            $('#birs_appointment_actions_add_payment').click(function() {
                $('#birs_appointment_client_payments_add_form').show();
                $('#birs_appointment_actions_add_payment').hide();
                var add_payment_form = $('#birs_appointment_client_payments_add_form');
                birchpress.util.scrollTo(add_payment_form, 600, -20);
            });
            $('#birs_add_payment_cancel').click(function() {
                $('#birs_appointment_client_payments_add_form').hide();
                $('#birs_appointment_actions_add_payment').show();
            });
            $('#birs_add_payment').click(function() {
                ns.addPayment();
            });
            $('#birs_appointment_client_payments_cancel').click(function() {
                birchschedule.view.appointments.edit.clientlist.setViewState({
                    view: 'list'
                });
            });
        }
    });
})(jQuery);