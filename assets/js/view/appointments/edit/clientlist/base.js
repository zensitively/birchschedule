(function($) {
    var viewState = {
        view: 'list',
        clientId: 0
    };

    var ns = birchpress.namespace('birchschedule.view.appointments.edit.clientlist', {

        __init__: function() {
            ns.render({
                view: 'list',
                clientId: 0
            });
        },

        getViewState: function() {
            return viewState;
        },

        setViewState: function(state) {
            viewState = _.extend(viewState, state);
            ns.render(viewState);
        },

        render: function(viewState) {
            $('.wp-list-table.birs_clients .birs_row').show();
            $('.wp-list-table.birs_clients tbody tr:not(.birs_row)').find('td').html('');
            $('.wp-list-table.birs_clients tbody tr:not(.birs_row)').hide();
        }
    });

})(jQuery);