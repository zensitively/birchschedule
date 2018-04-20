(function($) {
	var ns = birchpress.namespace('birchschedule.eadmin', {
		__init__: function() {
			ns.initClientSelector();
			birchpress.addAction('birchschedule.gbooking.initAddClientFormAfter', ns.initClientSelector);
			$('#birs_appointment_actions_change_duration').click(function() {
				ns.changeAppointmentDuration();
			});
		},

		initClientInfo: function() {
			birchschedule.view.initCountryStateField('birs_client_country', 'birs_client_state');
		},

		filterClients: function(request, response) {
			var ajaxUrl = birchschedule.model.getAjaxUrl();
			var postData = $.param({
				action: 'birchschedule_eadmin_search_clients',
				term: request.term
			});
			$.post(ajaxUrl, postData, function(data, status, xhr) {
				var result = birchschedule.model.parseAjaxResponse(data);
				var clients = [];
				if (result.success) {
					clients = $.parseJSON(result.success.message);
				}
				response(clients);
			});
		},

		initClientSelector: function() {
			var ajaxUrl = birchschedule.model.getAjaxUrl();
			$('#birs_client_selector').autocomplete({
				'source': ns.filterClients,
				'minLength': 2,
				'select': function(event, ui) {
					var clientId = ui.item.id;
					var postData = $.param({
						action: 'birchschedule_eadmin_load_selected_client',
						'birs_client_id': clientId
					});
					$.post(ajaxUrl, postData, function(data, status, xhr) {
						$('#birs_client_info_container').html(data);
					});
				}
			});
		},

		changeAppointmentDuration: function() {
			var ajaxUrl = birchschedule.model.getAjaxUrl();
			var i18nMessages = birchschedule.view.getI18nMessages();
			var postData = $.param({
				action: 'birchschedule_eadmin_change_appointment_duration',
				birs_appointment_duration: $('#birs_appointment_duration').val(),
				birs_appointment_id: $('#birs_appointment_id').val()
			});
			$.post(ajaxUrl, postData, function(data, status, xhr) {
				window.location.reload();
				$('#birs_appointment_actions_change_duration').val(i18nMessages['Change']);
				$('#birs_appointment_actions_change_duration').prop('disabled', false);
			});
			$('#birs_appointment_actions_change_duration').val(i18nMessages['Please wait...']);
			$('#birs_appointment_actions_change_duration').prop('disabled', true);
		}
	});
})(jQuery);