'use strict';

var $ = require('jquery');
var lang = require('../lang');

var ns = lang.provide('birchpress.util', {

  isMobile: function isMobile() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test(navigator.userAgent);
  },

  getNow4Locale: function getNow4Locale(serverGmtOffset) {
    var now = new Date();
    return ns.getDate4Locale(now, serverGmtOffset);
  },

  getDate4Locale: function getDate4Locale(date, serverGmtOffset) {
    var localOffset = date.getTimezoneOffset();
    var timestamp = date.getTime() + (localOffset - serverGmtOffset) * 60 * 1000;
    return new Date(timestamp);
  },

  getDate4Server: function getDate4Server(date, serverGmtOffset) {
    var localOffset = date.getTimezoneOffset();
    var timestamp = date.getTime() + (serverGmtOffset - localOffset) * 60 * 1000;
    return new Date(timestamp);
  },

  scrollTo: function scrollTo(selector, duration, addition) {
    var _duration;
    var _addition;

    _duration = typeof duration !== 'undefined' ? duration : 600;
    _addition = typeof addition !== 'undefined' ? addition : 0;

    $('html, body').animate({
      scrollTop: $(selector).offset().top + _addition
    }, _duration);
  },

  getUnixTimestamp: function getUnixTimestamp(timestamp) {
    return Math.round(timestamp / 1000);
  },

  parseParams: function parseParams(query) {
    var re = /([^&=]+)=?([^&]*)/g;
    var e;
    var k;
    var v;
    var _query;

    var decode = function decode(str) {
      return decodeURIComponent(str.replace(/\+/g, ' '));
    };
    var params = {};
    if (query) {
      if (query.substr(0, 1) === '?') {
        _query = query.substr(1);
      } else {
        _query = query;
      }

      e = re.exec(_query);
      while (e) {
        k = decode(e[1]);
        v = decode(e[2]);
        if (params[k] !== undefined) {
          if (!$.isArray(params[k])) {
            params[k] = [params[k]];
          }
          params[k].push(v);
        } else {
          params[k] = v;
        }
        e = re.exec(_query);
      }
    }
    return params;
  },

  parseAjaxResponse: function parseAjaxResponse(doc) {
    var success = false;
    var errors = false;
    var _doc;
    var code;
    var message;
    var errorEls;

    _doc = '<div>' + doc + '</div>';
    if ($(_doc).find('#birs_success').length > 0) {
      code = $(_doc).find('#birs_success').attr('code');
      message = _.unescape($(_doc).find('#birs_success').html());
      success = {
        'code': code,
        'message': message
      };
    }
    if ($(_doc).find('#birs_errors').length > 0) {
      errors = {};
      errorEls = $(_doc).find('#birs_errors').children();
      errorEls.each(function(index, elDom) {
        var el = $(elDom);
        errors[el.attr('id')] = el.html();
      });
    }
    return {
      'success': success,
      'errors': errors
    };
  }
});

module.exports = ns;
