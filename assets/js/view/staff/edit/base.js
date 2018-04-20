(function($) {

    var ns = birchpress.namespace('birchschedule.view.staff.edit', {

        __init__: function() {
            var ajaxUrl = birchschedule.model.getAjaxUrl();
            var locationList = $('div#location_list li');
            locationList.click(function() {
                var locationId = $(this).attr('data-location-id');
                $('div#timetable > div').each(function(i, el) {
                    if ($(this).attr('data-location-id') === locationId) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
                locationList.each(function(i, el) {
                    if ($(this).attr('data-location-id') === locationId) {
                        $(this).addClass('current');
                    } else {
                        $(this).removeClass('current');
                    }
                });
            });
            $('.birs_schedule_new').click(function() {
                var locationId = $(this).attr('data-location-id');

                var postData = {
                    birs_location_id: locationId,
                    action: 'birchschedule_view_staff_new_schedule'
                };
                $.post(ajaxUrl, postData, function(data, status, xhr) {
                    $('#birs_schedule_' + locationId).append(data);
                }, 'html');
            });
        }
    });

})(jQuery);