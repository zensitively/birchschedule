'use strict';

var $ = require('jquery');
var _ = require('underscore');
var lang = require('./lang');
var ns;

var initFramework = function initFramework() {
  var namespaces = lang.getNamespaces();
  _.each(namespaces, function(namespace, fullName) {
    if (_.isFunction(namespace.__init__)) {
      namespace.__init__(namespace);
    }
  });
};

ns = lang.provide('birchpress', {
  initFramework: initFramework
});

birchpress.provide = lang.provide;
birchpress.addAction = lang.addAction;
birchpress.addFilter = lang.addFilter;
birchpress.removeAction = lang.removeAction;
birchpress.removeFilter = lang.removeFilter;
birchpress.assert = lang.assert;

// for backward compatibility
birchpress.defineFunction = lang.defineFunction;
birchpress.namespace = lang.provide;

require('./util');

$(function ready() {
  ns.initFramework();
});

module.exports = ns;
