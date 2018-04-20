(function($) {
	var ns = birchpress.namespace('birchschedule.gcalsync.admin', {
		__init__: function() {
			$('#birs_staff_action_gcal_authorize').click(ns.authorize);
		},

		authorize: function() {
			var ajaxUrl = birchschedule.model.getAjaxUrl();
			var i18nMessages = birchschedule.view.getI18nMessages();

			var queryData = {
				action: 'birchschedule_gcalsync_authorize',
				birs_staff_id: $('#post_ID').val(),
				birs_staff_gcal_authorization_code: $('#birs_staff_gcal_authorization_code').val()
			};
			$.ajax({
				type: 'POST',
				url: ajaxUrl,
				data: queryData,
				complete: function() {
					window.location.reload();
				}
			});
			$('#birs_staff_action_gcal_authorize').attr('disabled', 'disabled');
		}
	});
}(jQuery));