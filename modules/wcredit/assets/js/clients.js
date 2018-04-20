(function($) {
	var ns = birchpress.namespace('birchschedule.wcredits.clients', {
		__init__: function() {
			$('#birs_wc_credit_add').click(function() {
				$('#birs_wc_credit_add_block').toggle();
			});
		}
	});
})(jQuery);