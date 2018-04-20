(function($) {
    var ns = birchpress.namespace('birchschedule.view.appointments.edit.clientlist.edit', {

        __init__: function() {
            birchschedule.view.appointments.edit.clientlist.render.when(ns.isViewStateEdit, ns.render);
            $('.wp-list-table.birs_clients .birs_row a.row-title').click(function(eventObject) {
                var clientId = $(eventObject.target).attr('data-client-id');
                birchschedule.view.appointments.edit.clientlist.setViewState({
                    view: 'edit',
                    clientId: clientId
                });
            });
            $('.wp-list-table.birs_clients .row-actions .edit a').click(function(eventObject) {
                var clientId = $(eventObject.target).attr('data-item-id');
                birchschedule.view.appointments.edit.clientlist.setViewState({
                    view: 'edit',
                    clientId: clientId
                });
            });
        },

        isViewStateEdit: function(state) {
            return state.view === 'edit';
        },

        render: function(viewState) {
            birchschedule.view.appointments.edit.clientlist.render.defaultMethod(viewState);
            var clientId = viewState.clientId;
            if (viewState.view === 'edit') {
                var row = $('#birs_client_list_row_' + clientId);
                var editRow = $('#birs_client_list_row_edit_' + clientId);

                var data = editRow.attr('data-edit-html');
                editRow.find('td').html(data);
                ns.initForm();
                row.hide();
                editRow.show();
                birchpress.util.scrollTo(editRow);
            }
        },

        save: function() {
            var ajaxUrl = birchschedule.model.getAjaxUrl();
            var i18nMessages = birchschedule.view.getI18nMessages();
            var save_button = $('#birs_appointment_client_edit_save');
            var postData = $('form').serialize();
            postData += '&' + $.param({
                action: 'birchschedule_view_appointments_edit_clientlist_edit_save'
            });
            $.post(ajaxUrl, postData, function(data, status, xhr) {
                var result = birchschedule.model.parseAjaxResponse(data);
                if (result.errors) {
                    birchschedule.view.showFormErrors(result.errors);
                    save_button.val(i18nMessages['Save']);
                    save_button.prop('disabled', false);
                } else if (result.success) {
                    window.location.reload();
                }
            });
            save_button.val(i18nMessages['Please wait...']);
            save_button.prop('disabled', true);
        },

        initForm: function() {
            birchschedule.view.initCountryStateField('birs_client_country', 'birs_client_state');
            $('#birs_appointment_client_edit_cancel').click(function() {
                birchschedule.view.appointments.edit.clientlist.setViewState({
                    view: 'list'
                });
            });
            $('#birs_appointment_client_edit_save').click(function() {
                ns.save();
            });
        }
    });

})(jQuery);