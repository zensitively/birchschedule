'use strict';

var _ = require('underscore');

var root = {};
if (typeof window !== 'undefined') {
  root = window;
} else if (typeof global !== 'undefined') {
  root = global;
} else if (typeof self !== 'undefined') {
  root = self;
}

var actions = {};
var filters = {};
var namespaces = {};

var SPECIAL_WORDS = [
  '__call__', '__new__', '__init__',
  '__mixins__', '__statics__', '__construct__',
  '__fullname__'
];

var _assert = function(assertion, message) {
  if (!assertion) {
    throw new Error(message);
  }
};

var isNamespace = function(obj) {
  return _.isFunction(obj) && _.has(obj, '__fullname__');
};

var createNamespace = function(fullName) {
  var ns = function ns() {
    return ns.__call__.apply(ns, arguments);
  };
  ns.__call__ = function() {
    throw new Error('__call__ is not supported by namespace ' + fullName);
  };
  ns.__fullname__ = fullName;
  namespaces[fullName] = ns;
  return ns;
};

var mixin = function mixin(clazzes) {
  var result = {};
  _.each(clazzes, function(clazz) {
    var keys = _.keys(clazz);
    _.each(keys, function(key) {
      if (key === '__statics__') {
        result.__statics__ = _.union(result.__statics__, clazz.__statics__);
      } else {
        result[key] = clazz[key];
      }
    });
  });
  return result;
};

var wrappedActionFns = [];
var wrappedFilterFns = [];

var wrapActionFn = function(self, fn) {
  var realFn;
  var index = findIndex(wrappedActionFns, function(item) {
    return item[0] === self && item[1] === fn;
  });
  if (-1 === index) {
    realFn = function() {
      var args = argumentsToArray(arguments);
      var instance = args.shift();
      if (self === instance) {
        fn.apply(instance, args);
      }
    };
    wrappedActionFns.push([self, fn, realFn]);
  } else {
    realFn = wrappedActionFns[index][2];
  }
  return realFn;
};

var wrapFilterFn = function(self, fn) {
  var realFn;
  var index = findIndex(wrappedFilterFns, function(item) {
    return item[0] === self && item[1] === fn;
  });
  if (-1 === index) {
    realFn = function() {
      var args = argumentsToArray(arguments);
      var result = args.shift();
      var instance = args.shift();
      if (self === instance) {
        args.unshift(result);
        result = fn.apply(instance, args);
      }
      return result;
    };
    wrappedFilterFns.push([self, fn, realFn]);
  } else {
    realFn = wrappedFilterFns[index][2];
  }
  return realFn;
};

var classifyNamespace = function(ns, customSpec) {
  var spec = {
    __call__: function() {
      var args = argumentsToArray(arguments);
      var newArgs = args.slice(0);
      newArgs.unshift(ns);
      var self = ns.__new__.apply(ns, newArgs);
      var constructArgs = args.slice(0);
      constructArgs.unshift(self);
      ns.__construct__.apply(self, constructArgs);
      return self;
    },
    __new__: function(clazz) {
      var self = {};
      var keys = _.keys(clazz);
      var statics = ns.__statics__;
      _.each(keys, function(key) {
        if (_.isFunction(clazz[key]) && !_.contains(SPECIAL_WORDS, key)) {
          if (!_.contains(statics, key)) {
            self[key] = _.partial(clazz[key], self);
          } else {
            self[key] = clazz[key];
          }
        }
      });
      self.__class__ = clazz;
      self._data = {};
      return self;
    },
    __mixins__: [],
    __statics__: [],
    __init__: function(clazz) {},
    __construct__: function() {},
    __fullname__: ns.__fullname__,
    addAction: function(self, hookName, fn, priority) {
      var realHookName = self.__class__.__fullname__ + '.' + hookName;
      var realFn = wrapActionFn(self, fn);
      addAction(realHookName, realFn, priority);
    },
    removeAction: function(self, hookName, fn, priority) {
      var realHookName = self.__class__.__fullname__ + '.' + hookName;
      var realFn = wrapActionFn(self, fn);
      removeAction(realHookName, realFn, priority);
    },
    addFilter: function(self, hookName, fn, priority) {
      var realHookName = self.__class__.__fullname__ + '.' + hookName;
      var realFn = wrapFilterFn(self, fn);
      addFilter(realHookName, realFn, priority);
    },
    removeFilter: function(self, hookName, fn, priority) {
      var realHookName = self.__class__.__fullname__ + '.' + hookName;
      var realFn = wrapFilterFn(self, fn);
      removeFilter(realHookName, realFn, priority);
    }
  };
  _.extend(spec, customSpec);
  var clazzes = spec.__mixins__.slice(0);
  clazzes.push(spec);
  var clazz = mixin(clazzes);

  _.each(clazz, function(value, key) {
    if (_.isFunction(value)) {
      ns[key] = defineFunction(ns, key, value);
    } else {
      ns[key] = value;
    }
  });
  return ns;
};

var namespace = function(nsName, obj) {
  _assert(_.isString(nsName));

  var ns = nsName.split('.');
  var currentStr = ns[0];
  if (!isNamespace(root[currentStr])) {
    root[currentStr] = createNamespace(currentStr);
  }
  var current = root[currentStr];
  var sub = ns.slice(1);
  var len = sub.length;
  for (var i = 0; i < len; ++i) {
    currentStr = currentStr + '.' + sub[i];
    if (!isNamespace(current[sub[i]])) {
      current[sub[i]] = createNamespace(currentStr);
    }
    current = current[sub[i]];
  }

  return classifyNamespace(current, obj);
};

var argumentsToArray = function(args) {
  return Array.prototype.slice.call(args);
};

var doAction = function() {
  var args = argumentsToArray(arguments);
  _assert(args.length >= 1, 'At least one argument is required. The arguments are ' + args);
  _assert(_.isString(args[0]), 'The hook name should be string.');

  var context = this;
  var hookName = args[0];
  var fnArgs = args.slice(1);
  if (_.has(actions, hookName)) {
    var hookDef = actions[hookName];
    if (_.isArray(hookDef)) {
      _.each(hookDef, function(priorityDef, priority) {
        if (_.isArray(priorityDef)) {
          _.each(priorityDef, function(fn, index) {
            fn.apply(context, fnArgs);
          });
        }
      });
    }
  }
};

var applyFilters = function() {
  var args = argumentsToArray(arguments);
  _assert(args.length >= 2, 'At least two arguments are required. The arguments are ' + args);
  _assert(_.isString(args[0]), 'The hook name should be string.');

  var context = this;
  var hookName = args[0];
  var value = args[1];
  if (_.has(filters, hookName)) {
    var hookDef = filters[hookName];
    if (_.isArray(hookDef)) {
      _.each(hookDef, function(priorityDef, priority) {
        if (_.isArray(priorityDef)) {
          _.each(priorityDef, function(fn, index) {
            var fnArgs = args.slice(2);
            fnArgs.unshift(value);
            value = fn.apply(context, fnArgs);
          });
        }
      });
    }
  }
  return value;
};

var defineFunction = function(ns, fnName, fn) {
  var hookable = newHookableMultimethod(ns, fnName, fn);
  ns[fnName] = hookable;

  return hookable;
};

var parsePriority = function(arg) {
  arg = parseInt(arg);
  if (_.isNaN(arg) || arg < 0) {
    arg = 10;
  }
  return arg;
};

var addHookFunction = function(fnMap, hookName, fn, priority) {
  _assert(_.isString(hookName), 'The hook name should be a string.');
  _assert(_.isFunction(fn), 'The action or filter should be a function');

  priority = parsePriority(priority);
  if (_.has(fnMap, hookName)) {
    var hookDef = fnMap[hookName];
    if (_.isArray(hookDef[priority])) {
      hookDef[priority].push(fn);
    } else {
      hookDef[priority] = [fn];
    }
  } else {
    fnMap[hookName] = [];
    fnMap[hookName][priority] = [fn];
  }
};

var removeHookFunction = function(fnMap, hookName, fn, priority) {
  _assert(_.isString(hookName), 'The hook name should be a string.');
  _assert(_.isFunction(fn), 'The action or filter should be a function');

  priority = parsePriority(priority);
  if (_.has(fnMap, hookName)) {
    var hookDef = fnMap[hookName];
    if (_.isArray(hookDef) && _.isArray(hookDef[priority])) {
      var index = findIndex(hookDef[priority], function(item) {
        return item === fn;
      });
      if (-1 !== index) {
        hookDef[priority].splice(index, 1);
      }
    }
  }
};

var addAction = function(hookName, fn, priority) {
  addHookFunction(actions, hookName, fn, priority);
};

var addFilter = function(hookName, fn, priority) {
  addHookFunction(filters, hookName, fn, priority);
};

var removeAction = function(hookName, fn, priority) {
  removeHookFunction(actions, hookName, fn, priority);
};

var removeFilter = function(hookName, fn, priority) {
  removeHookFunction(filters, hookName, fn, priority);
};

var findIndex = function(array, predicate) {
  if (_.has(_, 'findIndex')) {
    return _.findIndex(array, predicate);
  } else {
    for (var i = 0; i < array.length; i++) {
      if (predicate(array[i]) === true) {
        return i;
      }
    }
    return -1;
  }
};

var newHookableMultimethod = function(ns, fnName, fn) {
  _assert(isNamespace(ns), 'The namespace(1st argument) should be a namespace object.');
  _assert(_.isString(fnName), 'The function name(2nd argument) should be a string.');
  _assert(_.isFunction(fn), 'The 3rd argument should be a function');

  var _matchFns = [];
  var _methods = [];
  var _defaultMethod = fn;

  var _findMethod = function() {
    var args = argumentsToArray(arguments);
    var index = findIndex(_matchFns, function(matchFn) {
      return matchFn.apply(ns, args);
    });
    if (index === -1) {
      return _defaultMethod;
    } else {
      return _methods[index];
    }
  };

  var self = function() {
    var args = argumentsToArray(arguments);
    var filterName = ns.__fullname__ + '.' + fnName;
    var preFilterName = filterName + 'Args';

    var actionBefore = filterName + 'Before';
    var actionAfter = filterName + 'After';

    var beforeArgs = args.slice(0);
    beforeArgs.unshift(actionBefore);
    doAction.apply(ns, beforeArgs);

    var preArgs = [];
    preArgs.unshift(preFilterName, args);
    args = applyFilters.apply(ns, preArgs);

    var realFn = _findMethod.apply(ns, args);
    var result = realFn.apply(ns, args);

    var fArgs = args.slice(0);
    fArgs.unshift(filterName, result);
    result = applyFilters.apply(ns, fArgs);

    var afterArgs = args.slice(0);
    afterArgs.unshift(actionAfter);
    afterArgs.push(result);
    doAction.apply(ns, afterArgs);
    return result;
  };

  self.when = function(matchFn, method) {
    var index = _.indexOf(_matchFns, matchFn);
    if (index === -1) {
      _matchFns.push(matchFn);
      _methods.push(method);
    } else {
      _methods[index] = method;
    }
    return self;
  };

  Object.defineProperty(self, 'defaultMethod', {
    get: function() {
      return _defaultMethod;
    },
    set: function(value) {
      _defaultMethod = value;
    }
  });

  return self;
};

var getNamespaces = function() {
  return namespaces;
};

module.exports = {

  provide: namespace,

  addAction: addAction,

  addFilter: addFilter,

  removeAction: removeAction,

  removeFilter: removeFilter,

  getNamespaces: getNamespaces,

  getSpecialWords: function() {
    return SPECIAL_WORDS;
  },

  assert: _assert,

  // backward compatibity
  defineFunction: defineFunction
};
