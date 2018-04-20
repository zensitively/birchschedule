(function($) {
    var params = birchschedule_model;

    var ns = birchpress.namespace('birchschedule.model', {

        getAjaxUrl: function() {
            return params.ajax_url;
        },

        getAdminUrl: function() {
            return params.admin_url;
        },

        getAllSchedule: function() {
            return params.all_schedule;
        },

        getServerGmtOffset: function() {
            return params.gmt_offset;
        },

        getAllDaysOff: function() {
            return params.all_daysoff;
        },

        getFutureTime: function() {
            return params.future_time;
        },

        getCutOffTime: function() {
            return params.cut_off_time;
        },

        getFullyBookedDays: function() {
            return params.fully_booked_days;
        },

        getNow4Locale: function() {
            var gmtOffset = ns.getServerGmtOffset();
            return birchpress.util.getNow4Locale(gmtOffset);
        },

        getDate4Locale: function(date) {
            var gmtOffset = ns.getServerGmtOffset();
            return birchpress.util.getDate4Locale(date, gmtOffset);
        },

        getDate4Server: function(date) {
            var gmtOffset = ns.getServerGmtOffset();
            return birchpress.util.getDate4Server(date, gmtOffset);
        },

        isDayAvaliableByBookingPreferences: function(date) {
            var futureTime = ns.getFutureTime();
            var cutOffTime = ns.getCutOffTime();
            var serverNow = ns.getNow4Locale();
            var timeOfServer = serverNow.getTime();
            var timeOfSelect = date.getTime();
            var hoursBetween = (timeOfSelect - timeOfServer) / (1000 * 60 * 60) + 24;
            var daysBetween = hoursBetween / 24;
            if (daysBetween > futureTime || hoursBetween < cutOffTime) {
                return false;
            }
            return true;
        },

        isDayAvaliableBySchedules: function(date, staffId, locationId, day) {
            var allSchedule = ns.getAllSchedule();
            if (!_.has(allSchedule, staffId)) {
                return false;
            }
            var staffSchedule = allSchedule[staffId];
            if (!_.has(staffSchedule, locationId)) {
                return false;
            }
            var staffLocationSchedule = staffSchedule[locationId];
            var schedules_of_weekday = staffLocationSchedule['schedules'][day];
            var avalibility = false;
            _.each(schedules_of_weekday, function(schedule, index) {
                var selectedDay = $.datepicker.formatDate('yy-mm-dd', date);
                var dateStart = $.datepicker.formatDate('yy-mm-dd',
                    $.datepicker.parseDate('mm/dd/yy', schedule['date_start']));
                var dateEnd = $.datepicker.formatDate('yy-mm-dd',
                    $.datepicker.parseDate('mm/dd/yy', schedule['date_end']));
                if ((selectedDay >= dateStart || !dateStart) &&
                    (selectedDay <= dateEnd || !dateEnd)) {
                    avalibility = true;
                }
            });
            return avalibility;
        },

        isDayAvaliableByDaysOff: function(date, staffId) {
            var allDaysoff = ns.getAllDaysOff();
            if (_.has(allDaysoff, staffId)) {
                var dayoffsJson = allDaysoff[staffId];
                var dayoffs = $.parseJSON(dayoffsJson);
                var selectedDay = $.datepicker.formatDate('mm/dd/yy', date);
                if (dayoffs && _.contains(dayoffs, selectedDay)) {
                    return false;
                }
            }
            return true;
        },

        isDayAvaliableByNow: function(date) {
            var serverNow = ns.getNow4Locale();
            var serverToday = $.datepicker.formatDate('yy-mm-dd', serverNow);
            var selectedDay = $.datepicker.formatDate('yy-mm-dd', date);
            if (serverToday > selectedDay) {
                return false;
            }
            return true;
        },

        isDayFullyBooked: function(date, staffId, locationId, serviceId) {
            var selectedDay = $.datepicker.formatDate('yy-mm-dd', date);
            var bookedDays = ns.getFullyBookedDays();
            if (_.has(bookedDays, selectedDay) && _.has(bookedDays[selectedDay], staffId) &&
                _.has(bookedDays[selectedDay][staffId], locationId) &&
                _.has(bookedDays[selectedDay][staffId][locationId], serviceId)) {

                return true;
            }
            return false;
        },

        parseAjaxResponse: function(doc) {
            return birchpress.util.parseAjaxResponse(doc);
        },

        getLocationOptions: function(locationMap, locationOrder) {
            var options = {
                'order': [],
                'options': {}
            };
            $.each(locationOrder, function(index, key) {
                if (_(locationMap).has(key)) {
                    var text = locationMap[key].post_title;
                    options.order.push(key);
                    options.options[key] = text;
                }
            });
            return options;
        },

        getServiceOptions: function(locationServiceMap, locationId, serviceOrder) {
            var options = {
                'order': [],
                'options': {}
            };
            var avaliableServices = locationServiceMap[locationId];
            $.each(serviceOrder, function(index, key) {
                if (_(avaliableServices).has(key)) {
                    var text = avaliableServices[key];
                    options.order.push(key);
                    options.options[key] = text;
                }
            });
            return options;
        },

        getStaffOptions: function(locationStaffMap, serviceStaffMap, locationId, serviceId, staffOrder) {

            var options = {
                'order': [],
                'options': {}
            };
            var locationStaffMap = locationStaffMap[locationId];
            var serviceStaffMap = serviceStaffMap[serviceId];
            var availableStaff = {};
            if (serviceStaffMap && locationStaffMap) {
                availableStaff = _.pick(serviceStaffMap, _.keys(locationStaffMap));
            }
            $.each(staffOrder, function(index, key) {
                if (_(availableStaff).has(key)) {
                    var text = availableStaff[key];
                    options.order.push(key);
                    options.options[key] = text;
                }
            });
            return options;
        }

    });

})(jQuery);