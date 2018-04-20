(function($) {

	var ns = birchpress.namespace('birchschedule', {
		__init__: function() {},
		initApp: function() {
		}
	});

	birchpress.addAction('birchpress.initFrameworkAfter', ns.initApp);
})(jQuery);