(function($) {
  var params = birchschedule_pcalendar;
  var scAttrs = birchschedule_pcalendar_sc_attrs;

  var ajaxUrl = params.ajax_url;
  var gmtOffset = params.gmt_offset;
  var locationMap = params.location_map;
  var locationStaffMap = params.location_staff_map;
  var staffOrder = params.staff_order;
  var locationOrder = params.location_order;
  var defaultView = scAttrs['default_view'];

  function getLocationsOrder() {
    var locationIds = params.location_order;
    if (scAttrs['location_ids']) {
      locationIds = _.intersection(scAttrs['location_ids'], locationIds);
    }
    return locationIds;
  }

  function getStaffOrder() {
    var staffIds = params.staff_order;
    if (scAttrs['staff_ids']) {
      staffIds = _.intersection(scAttrs['staff_ids'], staffIds);
    }
    return staffIds;
  }

  var ns = birchpress.namespace('birchschedule.pcalendar', {
    __init__: function() {

      function changeLocationOptions() {
        var html = '';
        var locationOrder = getLocationsOrder();
        $.each(locationOrder, function(index, key) {
          if (_(locationMap).has(key)) {
            html += '<option value="' + key + '">' +
              locationMap[key].post_title + '</option>';
          }
        });
        $('#birs_calendar_location').html(html);
      }

      function changeStaffOptions() {
        var locationId = $('#birs_calendar_location').val();
        var assignedStaff = locationStaffMap[locationId];
        var html = '';
        if (!assignedStaff) {
          assignedStaff = {};
        }
        var staffOrder = getStaffOrder();
        $.each(staffOrder, function(index, key) {
          if (_(assignedStaff).has(key)) {
            var value = assignedStaff[key];
            html += '<option value="' + key + '">' + value + '</option>';
          }
        });
        var selectedStaff = $('#birs_calendar_staff').val();
        $('#birs_calendar_staff').html(html);
        if (selectedStaff) {
          $('#birs_calendar_staff').val(selectedStaff);
        }
      }

      function getFullcalendarI18nOptions() {
        var fcI18nOptions = birchschedule.view.getFullcalendarI18nOptions();
        var fcOptions = $.extend(fcI18nOptions, {
          header: {
            left: 'prev',
            center: 'title',
            right: 'next'
          },
          ignoreTimezone: true,
          gmtOffset: gmtOffset,
          weekMode: 'liquid',
          editable: true,
          disableDragging: true,
          disableResizing: true,
          selectable: false,
          allDaySlot: true,
          slotMinutes: 15,
          firstHour: 9,
          defaultView: defaultView,
          dayClick: function(date, allDay, jsEvent, view) {
            if (view.name === 'month') {
              calendar.fullCalendar('changeView', 'agendaDay');
              calendar.fullCalendar('gotoDate', date);
              setCalendarViewRadio();
            }
          },
          events: function(start, end, callback) {
            var locationId = $('#birs_calendar_location').val();
            var staffId = $('#birs_calendar_staff').val();
            var titleTemplate = $('#birs_appointment_title_template').val();
            var show_external_events = $('#birs_appointment_show_external_events').val();
            start = moment(start).format('YYYY-MM-DD HH:mm:ss');
            end = moment(end).format('YYYY-MM-DD HH:mm:ss');
            $.ajax({
              url: ajaxUrl,
              method: 'POST',
              dataType: 'html',
              data: {
                action: 'birchschedule_pcalendar_query_appointments',
                birs_time_start: start,
                birs_time_end: end,
                birs_location_id: locationId,
                birs_staff_id: staffId,
                title_template: titleTemplate,
                show_external_events: show_external_events
              },
              success: function(doc) {
                doc = '<div>' + doc + '</div>';
                var events = $.parseJSON($(doc).find('#birs_response').text());
                callback(events);
                $('#birs_status').hide();
              }
            });
            $('#birs_status').show();
          }
        });
        return fcOptions;
      }

      function setCalendarViewRadio() {
        var view = calendar.fullCalendar('getView');
        $("input[name=birs_calendar_view][value=" + view.name + "]").attr('checked', 'checked');
      }

      changeLocationOptions();
      changeStaffOptions();
      $('#birs_calendar_location').change(function() {
        changeStaffOptions();
      });

      var fcOptions = getFullcalendarI18nOptions();
      var calendar = $('#birs_calendar').fullCalendar(fcOptions);
      setCalendarViewRadio();
      $('#birs_calendar_location').change(function() {
        calendar.fullCalendar('refetchEvents');
      });
      $('#birs_calendar_staff').change(function() {
        calendar.fullCalendar('refetchEvents');
      });
      $('input[name=birs_calendar_view]').change(function() {
        var view = $('input[name=birs_calendar_view]:checked').val();
        calendar.fullCalendar('changeView', view);
      });

    }

  });
})(jQuery);