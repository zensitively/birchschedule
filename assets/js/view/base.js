(function($) {
    var params = birchschedule_view;

    var ns = birchpress.namespace('birchschedule.view', {

        getDatepickerI18nOptions: function() {
            return params.datepicker_i18n_options;
        },

        getFullcalendarI18nOptions: function() {
            return params.fc_i18n_options;

        },

        getI18nMessages: function() {
            return params.i18n_messages;
        },

        getI18nCountries: function() {
            return params.i18n_countries;
        },

        getI18nStates: function() {
            return params.i18n_states;
        },

        getPluginUrl: function() {
            return params.plugin_url;
        },

        getAvaliableStaffIds: function(staffId) {
            if (staffId != -1) {
                return [staffId];
            } else {
                var staffIdsStr = $('#birs_appointment_avaliable_staff').val();
                var staffIds = staffIdsStr.split(',');
                staffIds = _.without(staffIds, '-1');
                return staffIds;
            }
        },

        ifShowDayForDatepicker: function(date, staffId, locationId, serviceId) {
            if (!birchschedule.model.isDayAvaliableByNow(date)) {
                return [false, ""];
            }
            var day = date.getDay();
            var staffIds = ns.getAvaliableStaffIds(staffId);

            var showByStaff = false;
            _.each(staffIds, function(staffId) {
                var showByDaysOff = birchschedule.model.isDayAvaliableByDaysOff(date, staffId);
                var showBySchedules = birchschedule.model.isDayAvaliableBySchedules(date, staffId,
                    locationId, day);
                var fullyBooked = birchschedule.model.isDayFullyBooked(date, staffId, locationId, serviceId);
                showByStaff = showByStaff || (showByDaysOff && showBySchedules && !fullyBooked);
            });
            if (!showByStaff) {
                return [false, ""];
            }
            var showByPreferences = birchschedule.model.isDayAvaliableByBookingPreferences(date);
            if (!showByPreferences) {
                return [false, ""];
            }
            return [true, ""];
        },

        initCountryStateField: function(countryId, stateId) {
            var initStateField = function(stateId) {
                var stateEl = $('#' + stateId);
                var stateSelectEl = $('#' + stateId + '_select');
                stateSelectEl.change(function() {
                    var state = stateSelectEl.val();
                    stateEl.val(state);
                });
            }
            var switchStateProvince = function(countryId, stateId) {
                var countries = ns.getI18nCountries();
                var states = ns.getI18nStates();
                var countryEl = $('#' + countryId);
                var stateEl = $('#' + stateId);
                var stateSelectEl = $('#' + stateId + '_select');
                var country = countryEl.val();
                var state = stateEl.val();

                if (_.has(states, country)) {
                    stateEl.hide();
                    var options = "";
                    _.each(states[country], function(value, key) {
                        options += "<option value='" + key + "'>" + value + "</option>";
                    });
                    if (stateSelectEl.select2) {
                        stateSelectEl.select2('container').show();
                    } else {
                        stateSelectEl.show();
                    }
                    stateSelectEl.html(options);
                    stateSelectEl.change();
                } else {
                    stateEl.show();
                    stateEl.val('');
                    if (stateSelectEl.select2) {
                        stateSelectEl.select2('container').hide();
                    } else {
                        stateSelectEl.hide();
                    }
                }
            }
            var switchStateProvince2 = function() {
                switchStateProvince(countryId, stateId);
            };
            initStateField(stateId);
            $('#' + countryId).change(switchStateProvince2);
        },

        initDatepicker: function(config) {
            config = config || {};
            if (!config.datepickerId) {
                config.datepickerId = 'birs_appointment_datepicker';
            }
            if (!config.dateFieldId) {
                config.dateFieldId = 'birs_appointment_date';
            }
            if (!config.ifOnlyShowAvailable) {
                config.ifOnlyShowAvailable = function() {
                    return true;
                };
            }
            if (!config.getLocationId) {
                config.getLocationId = function() {
                    return $('#birs_appointment_location').val();
                };
            }
            if (!config.getStaffId) {
                config.getStaffId = function() {
                    return $('#birs_appointment_staff').val();
                };
            }
            if (!config.getServiceId) {
                config.getServiceId = function() {
                    return $('#birs_appointment_service').val();
                };
            }
            if (!config.gotoDate) {
                config.gotoDate = birchschedule.model.getNow4Locale();
            }
            if (!config.ifShowDayForDatepicker) {
                config.ifShowDayForDatepicker = birchschedule.view.ifShowDayForDatepicker;
            }
            var datepickerI18nOptions = birchschedule.view.getDatepickerI18nOptions();
            var clearHighlight = function() {
                var dateValue = $('#' + config.dateFieldId).val();
                if (!dateValue) {
                    window.setTimeout(function() {
                        var el = $('#' + config.datepickerId).find('.ui-state-active');
                        el.removeClass('ui-state-active');
                    }, 0);
                }
            };
            var datepickerOptions = $.extend(datepickerI18nOptions, {
                changeMonth: false,
                changeYear: false,
                'dateFormat': 'mm/dd/yy',
                beforeShowDay: function(date) {
                    if (config.ifOnlyShowAvailable()) {
                        var locationId = config.getLocationId();
                        var staffId = config.getStaffId();
                        var serviceId = config.getServiceId();
                        var result = config.ifShowDayForDatepicker(date, staffId, locationId, serviceId);
                        var dateValue = $('#' + config.dateFieldId).val();
                        if (dateValue === $.datepicker.formatDate('mm/dd/yy', date)) {
                            if (!result[0]) {
                                $('#' + config.dateFieldId).val('').trigger('change');
                            }
                        }
                        return result;
                    } else {
                        return [true, ""];
                    }
                },
                'onSelect': function(dateText, instance) {
                    var date = datepicker.datepicker('getDate');
                    var dateValue = $.datepicker.formatDate('mm/dd/yy', date);
                    $('#' + config.dateFieldId).val(dateValue).trigger('change');
                },
                'onChangeMonthYear': function(year, month, inst) {
                    clearHighlight();
                }
            });
            var datepicker = $('#' + config.datepickerId).datepicker(datepickerOptions);
            var dateValue = $('#' + config.dateFieldId).val();
            if (dateValue) {
                datepicker.datepicker('setDate', dateValue);
            } else {
                datepicker.datepicker('setDate', config.gotoDate);
                $('#' + config.datepickerId + ' .ui-state-active').removeClass('ui-state-active');
            }
            return {
                datepickerId: config.datepickerId,
                dateFieldId: config.dateFieldId,
                clearHighlight: clearHighlight
            }
        },

        refreshDatepicker: function(datepicker) {
            $('#' + datepicker.datepickerId).datepicker('refresh');
            datepicker.clearHighlight();
        },

        getOptionsHtml: function(options) {
            var html = "";
            $.each(options.order, function(index, key) {
                var text = options.options[key];
                html += '<option value="' + key + '">' +
                    text + '</option>';
            });
            return html;
        },

        showFormErrors: function(errors) {
            $('.birs_error').hide('');
            _.each(errors, function(value, key) {
                var tagId = key + '_error';
                $('#' + tagId).html(value);
                $('#' + tagId).show('slow');
            });
            setTimeout(function() {
                birchpress.util.scrollTo(
                    $(".birs_error:visible:first").parent(),
                    600, -40);
            }, 800);
        }
    });

})(jQuery);