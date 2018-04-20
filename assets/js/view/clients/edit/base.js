(function($) {
	var ns = birchpress.namespace('birchschedule.view.clients.edit', {

		__init__: function() {
			birchschedule.view.initCountryStateField('birs_client_country', 'birs_client_state');
		}
	});
})(jQuery);