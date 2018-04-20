(function($) {
	var params = birs_user_integration_params;

	var ns = birchpress.namespace('birchschedule.uintegration', {
		__init__: function() {
			var syncingDialogHtml = "<div id='birs_clients_syncing_dialog'></div>";
			var checkStatus = function() {
				$.ajax({
					type: 'POST',
					url: params.ajax_url,
					data: {
						'action': 'birchschedule_uintegration_sync_check_status'
					},
					success: function(data) {
						data = '<div>' + data + '</div>';
						var doc = $(data).find('#birs_response');
						var count = doc.find('#synced_client_count').html();
						var if_user_synced = doc.find('#if_user_synced').html();
						$('#birs_clients_synced_count').html(count);
						if (if_user_synced) {
							$('#birs_clients_syncing_dialog').dialog('close');
							location.reload();
						}
					}
				});
			}
			$(syncingDialogHtml).insertAfter('#wpbody');
			if (!params.if_user_synced) {
				$('#birs_clients_syncing_dialog').dialog({
					'modal': true,
					'title': params.i18n.user_sync,
					'dialogClass': 'wp-dialog',
					closeOnEscape: false,
					open: function(event, ui) {
						$(this).parent().find(".ui-dialog-titlebar-close").hide();
						var content = "<p>" + params.i18n.syncing_clients_with_wp_users + "</p>" +
							"<p><span id='birs_clients_synced_count'>" + 0 + "</span> " +
							params.i18n.synced + "</p>";
						content += '<a href="javascript:void(0);" id="birs_clients_actions_skip">' + params.i18n.skip + ' >></a>';
						$('#birs_clients_syncing_dialog').html(content);
						$('#birs_clients_actions_skip').click(function() {
							$.ajax({
								type: 'POST',
								url: params.ajax_url,
								data: {
									'action': 'birchschedule_uintegration_skip'
								},
								success: function(data) {
									location.reload();
								}
							});
						});
						setInterval(checkStatus, 1000);
					}
				});
				if (!params.if_user_synced) {
					$.ajax({
						type: 'POST',
						url: params.ajax_url,
						data: {
							'action': 'birchschedule_uintegration_sync_clients'
						},
						success: function(doc) {}
					});
				}
			}
		}
	});
})(jQuery);