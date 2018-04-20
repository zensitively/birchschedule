(function($) {
    var params = birchschedule_view_calendar;
    var addAppointmentTitle = params.add_appointment_title;
    var editAppointmentTitle = params.edit_appointment_title;
    var locationMap = params.location_map;
    var locationStaffMap = params.location_staff_map;
    var staffOrder = params.staff_order;
    var locationOrder = params.location_order;
    var defaultView = params.default_calendar_view;
    var slotMinutes = parseInt(params.slot_minutes);
    var firstHour = parseInt(params.first_hour);

    var gmtOffset = birchschedule.model.getServerGmtOffset();
    var ns = birchpress.namespace('birchschedule.view.calendar', {

        __init__: function() {

            $('body').append('<div id="birs_calendar_status1" class="center"></div>');
            $('body').append('<div id="birs_calendar_status2" class="center"></div>');


            ns.onHashChange();
            $(window).on('hashchange', ns.onHashChange);

            $('#birs_calendar_location').change(function() {
                ns.changeHash();
            });
            $('#birs_calendar_staff').change(function() {
                ns.changeHash();
            });
            $('#birs_calendar_refresh').click(function() {
                var hash = document.location.hash;
                if (_.str.endsWith(hash, '&')) {
                    hash = _.str.strLeftBack(hash, '&');
                } else {
                    hash = hash + '&';
                }
                document.location.hash = hash;
            });

            $('input[name="birs_calendar_view_choice"]').change(function() {
                $('input[name="birs_calendar_view"]').val($(this).val()).change();
            });

            $('input[name="birs_calendar_view"]').change(function() {
                ns.changeHash();
            });

            $('input[name="birs_calendar_current_date"]').change(function() {
                ns.changeHash();
            });

            $('#birs_add_appointment').click(function() {
                var url = ns.getNewAppointmentUrl();
                window.location = url;
            });

            $('#birs_calendar_today').click(function() {
                $('input[name="birs_calendar_current_date"]').
                val(birchpress.util.getNow4Locale(gmtOffset).getTime()).change();
            });

            $('#birs_calendar_header .fc-header-left button').click(function() {
                var view = $('input[name="birs_calendar_view"]').val();
                var currentDate = parseInt($('input[name="birs_calendar_current_date"]').val());
                var newDate = moment(currentDate).subtract('d', 1);
                if (view == 'agendaWeek') {
                    newDate = moment(currentDate).subtract('w', 1);
                } else if (view == 'month') {
                    newDate = moment(currentDate).subtract('M', 1);
                }
                $('input[name="birs_calendar_current_date"]').val(newDate.valueOf()).change();
            });
            $('#birs_calendar_header .fc-header-right button').click(function() {
                var view = $('input[name="birs_calendar_view"]').val();
                var currentDate = parseInt($('input[name="birs_calendar_current_date"]').val());
                var newDate = moment(currentDate).add('d', 1);
                if (view == 'agendaWeek') {
                    newDate = moment(currentDate).add('w', 1);
                } else if (view == 'month') {
                    newDate = moment(currentDate).add('M', 1);
                }
                $('input[name="birs_calendar_current_date"]').val(newDate.valueOf()).change();
            });
        },

        changeLocationOptions: function() {
            var html = '';
            $.each(locationOrder, function(index, key) {
                if (_(locationMap).has(key)) {
                    html += '<option value="' + key + '">' +
                        locationMap[key].post_title + '</option>';
                }
            });
            $('#birs_calendar_location').html(html);
        },

        changeStaffOptions: function() {
            var locationId = $('#birs_calendar_location').val();
            var assignedStaff = locationStaffMap[locationId];
            var html = '';
            if (!assignedStaff) {
                assignedStaff = {};
            }
            $.each(staffOrder, function(index, key) {
                if (_(assignedStaff).has(key)) {
                    var value = assignedStaff[key];
                    html += '<option value="' + key + '">' + value + '</option>';
                }
            });
            $selected_staff = $('#birs_calendar_staff').val();
            $('#birs_calendar_staff').html(html);
            if ($selected_staff && _(assignedStaff).has($selected_staff)) {
                $('#birs_calendar_staff').val($selected_staff);
            }
        },

        activeViewButton: function(view) {
            var selector = 'input[name="birs_calendar_view_choice"][value="' + view + '"]';
            $(selector).prop("checked", true);
        },

        parseHash: function() {
            var hash = document.location.hash.substring(1);
            var params = birchpress.util.parseParams(hash);
            return params;
        },

        makeHash: function() {
            var calView = $('input[name="birs_calendar_view"]').val();
            var locationId = $('#birs_calendar_location').val();
            var staffId = $('#birs_calendar_staff').val();
            var currentDate = $('input[name="birs_calendar_current_date"]').val();
            return $.param({
                'calview': calView,
                'locationid': locationId,
                'staffid': staffId,
                'currentdate': currentDate
            });
        },

        onHashChange: function() {
            var params = ns.parseHash();
            params = _.extend({
                'calview': defaultView,
                'currentdate': birchpress.util.getNow4Locale(gmtOffset).getTime()
            }, params);

            $('input[name="birs_calendar_view"]').val(params.calview);
            $('input[name="birs_calendar_current_date"]').val(params.currentdate);

            ns.activeViewButton(params.calview);

            ns.changeLocationOptions();
            if (params.locationid) {
                $('#birs_calendar_location').val(params.locationid);
            }
            ns.changeStaffOptions();
            if (params.staffid) {
                $('#birs_calendar_staff').val(params.staffid);
            }

            ns.renderCalendar(params);
        },

        changeHash: function() {
            document.location.hash = ns.makeHash();
        },

        getEditAppointmentUrl: function(appointmentId) {
            var aptEditUrl = birchschedule.model.getAdminUrl() + 'post.php?post=' + appointmentId + '&action=edit';
            var hash = document.location.hash.substring(1);
            if (hash) {
                aptEditUrl = aptEditUrl + '&' + hash;
            }
            return aptEditUrl;
        },

        getFcOptions: function(params) {
            var ajaxUrl = birchschedule.model.getAjaxUrl();
            var showMessage = birchschedule.view.admincommon.showMessage;
            var hideMessage = birchschedule.view.admincommon.hideMessage;

            var fcI18nOptions = birchschedule.view.getFullcalendarI18nOptions();
            var i18n = birchschedule.view.admincommon.getI18nMessages();

            var fcOptions = $.extend(fcI18nOptions, {
                header: '',
                defaultView: params.calview,
                ignoreTimezone: true,
                gmtOffset: gmtOffset,
                weekMode: 'liquid',
                editable: true,
                disableDragging: true,
                disableResizing: true,
                selectable: false,
                allDaySlot: true,
                slotMinutes: slotMinutes,
                firstHour: firstHour,
                lazyFetching: true,
                dayClick: function(date, allDay, jsEvent, view) {
                    if (view.name === 'month') {
                        $('input[name="birs_calendar_current_date"]').
                        val(date.getTime());
                        $('input[name="birs_calendar_view"]').val('agendaDay').change();
                    }
                },
                eventClick: function(calEvent, jsEvent, view) {
                    if (!calEvent.editable) {
                        return;
                    }
                },
                eventAfterRender: function(event, element) {
                    var appointmentUrl = ns.getEditAppointmentUrl(event.id);
                    var linkableTitle = _.str.sprintf('<a href="%s">%s</a>',
                        appointmentUrl, event['title']);
                    var timeText = element.find('.fc-event-time').html();
                    var linkableTime = _.str.sprintf('<a href="%s">%s</a>',
                        appointmentUrl, timeText);
                    if (event.editable) {
                        element.find('.fc-event-time').html(linkableTime);
                        element.find('.fc-event-title').html(linkableTitle);
                    }
                },
                events: function(start, end, callback) {
                    var locationId = $('#birs_calendar_location').attr('value');
                    var staffId = $('#birs_calendar_staff').attr('value');
                    start = moment(start).format('YYYY-MM-DD HH:mm:ss');
                    end = moment(end).format('YYYY-MM-DD HH:mm:ss');
                    var handleEvents = function(doc, callback) {
                        doc = '<div>' + doc + '</div>';
                        var events = $.parseJSON($(doc).find('#birs_response').text());
                        callback(events);
                    }
                    $.ajax({
                        url: ajaxUrl,
                        dataType: 'html',
                        data: {
                            action: 'birchschedule_view_calendar_query_appointments',
                            birs_time_start: start,
                            birs_time_end: end,
                            birs_location_id: locationId,
                            birs_staff_id: staffId
                        },
                        success: function(doc) {
                            hideMessage('');
                            handleEvents(doc, callback);
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            hideMessage('');
                            if (jqXHR.status == 500) {
                                var doc = jqXHR.responseText;
                                handleEvents(doc, callback);
                            }
                        }
                    });
                    showMessage('', i18n['Loading appointments...'], {
                        sticky: true
                    });
                }
            });
            return fcOptions;
        },

        renderCalendar: function(params) {
            var fcOptions = ns.getFcOptions(params);
            var currentDate = new Date(parseInt(params.currentdate));
            var year = $.fullCalendar.formatDate(currentDate, 'yyyy');
            var month = $.fullCalendar.formatDate(currentDate, 'M') - 1;
            var date = $.fullCalendar.formatDate(currentDate, 'd');
            fcOptions = _.extend(fcOptions, {
                'year': year,
                'month': month,
                'date': date
            });
            $('#birs_calendar').fullCalendar('destroy');
            $('#birs_calendar').fullCalendar(fcOptions);
            var view = $('#birs_calendar').fullCalendar('getView');
            $('#birs_calendar_header .fc-header-title h2').html(view.title);
        },

        getNewAppointmentUrl: function() {
            var aptEditUrl = birchschedule.model.getAdminUrl() + 'post-new.php?post_type=birs_appointment';
            var hash = document.location.hash.substring(1);
            if (hash) {
                aptEditUrl = aptEditUrl + '&' + hash;
            }
            return aptEditUrl;
        }
    });
})(jQuery);