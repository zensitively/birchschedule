(function($) {
	var ns = birchpress.namespace('birchschedule.view.locations.edit', {

		__init__: function() {
			birchschedule.view.initCountryStateField('birs_location_country', 'birs_location_state');
		}
	});
})(jQuery);