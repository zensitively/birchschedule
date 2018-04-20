(function($) {
	var params = birs_export_clients_params;

	var ns = birchpress.namespace('birchschedule.cexport', {
		__init__: function() {
			var exportBtnHtml = "<input id='birs_clients_export' type='button' class='button' value='Export' />";
			var exportDialogHtml = "<div id='birs_clients_export_dialog'></div>";
			$(exportBtnHtml).insertAfter('#post-query-submit');
			$(exportDialogHtml).insertAfter('#wpbody');

			var downloadUrl = params.ajax_url + "?action=birchschedule_cexport_export_clients";
			$('#birs_clients_export').click(function() {
				$.fileDownload(downloadUrl, {
					preparingMessageHtml: params.i18n.prepare_file,
					failMessageHtml: params.i18n.generating_file_failed
				});
			});
		}
	});
})(jQuery);