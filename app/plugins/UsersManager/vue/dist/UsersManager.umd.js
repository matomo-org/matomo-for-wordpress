(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory(require("CoreHome"), require("vue"), require("CorePluginsAdmin"));
	else if(typeof define === 'function' && define.amd)
		define(["CoreHome", , "CorePluginsAdmin"], factory);
	else if(typeof exports === 'object')
		exports["UsersManager"] = factory(require("CoreHome"), require("vue"), require("CorePluginsAdmin"));
	else
		root["UsersManager"] = factory(root["CoreHome"], root["Vue"], root["CorePluginsAdmin"]);
})((typeof self !== 'undefined' ? self : this), function(__WEBPACK_EXTERNAL_MODULE__19dc__, __WEBPACK_EXTERNAL_MODULE__8bbf__, __WEBPACK_EXTERNAL_MODULE_a5a2__) {
return /******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "plugins/UsersManager/vue/dist/";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "fae3");
/******/ })
/************************************************************************/
/******/ ({

/***/ "19dc":
/***/ (function(module, exports) {

module.exports = __WEBPACK_EXTERNAL_MODULE__19dc__;

/***/ }),

/***/ "8bbf":
/***/ (function(module, exports) {

module.exports = __WEBPACK_EXTERNAL_MODULE__8bbf__;

/***/ }),

/***/ "a5a2":
/***/ (function(module, exports) {

module.exports = __WEBPACK_EXTERNAL_MODULE_a5a2__;

/***/ }),

/***/ "fae3":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, "CapabilitiesEdit", function() { return /* reexport */ CapabilitiesEdit; });
__webpack_require__.d(__webpack_exports__, "UserPermissionsEdit", function() { return /* reexport */ UserPermissionsEdit; });
__webpack_require__.d(__webpack_exports__, "UserEditForm", function() { return /* reexport */ UserEditForm; });
__webpack_require__.d(__webpack_exports__, "PagedUsersList", function() { return /* reexport */ PagedUsersList; });
__webpack_require__.d(__webpack_exports__, "UsersManager", function() { return /* reexport */ UsersManager; });
__webpack_require__.d(__webpack_exports__, "NewsletterSettings", function() { return /* reexport */ AnonymousSettings; });
__webpack_require__.d(__webpack_exports__, "AnonymousSettings", function() { return /* reexport */ NewsletterSettings; });
__webpack_require__.d(__webpack_exports__, "PersonalSettings", function() { return /* reexport */ PersonalSettings; });

// CONCATENATED MODULE: ./node_modules/@vue/cli-service/lib/commands/build/setPublicPath.js
// This file is imported into lib/wc client bundles.

if (typeof window !== 'undefined') {
  var currentScript = window.document.currentScript
  if (false) { var getCurrentScript; }

  var src = currentScript && currentScript.src.match(/(.+\/)[^/]+\.js(\?.*)?$/)
  if (src) {
    __webpack_require__.p = src[1] // eslint-disable-line
  }
}

// Indicate to webpack that this file can be concatenated
/* harmony default export */ var setPublicPath = (null);

// EXTERNAL MODULE: external "CoreHome"
var external_CoreHome_ = __webpack_require__("19dc");

// EXTERNAL MODULE: external {"commonjs":"vue","commonjs2":"vue","root":"Vue"}
var external_commonjs_vue_commonjs2_vue_root_Vue_ = __webpack_require__("8bbf");

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--12-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--0-1!./plugins/UsersManager/vue/src/CapabilitiesEdit/CapabilitiesEdit.vue?vue&type=template&id=7d385264

var _hoisted_1 = ["title"];
var _hoisted_2 = ["onClick"];
var _hoisted_3 = {
  key: 0,
  class: "addCapability"
};
var _hoisted_4 = {
  class: "ui-confirm confirmCapabilityToggle modal",
  ref: "confirmCapabilityToggleModal"
};
var _hoisted_5 = {
  class: "modal-content"
};
var _hoisted_6 = ["innerHTML"];
var _hoisted_7 = ["innerHTML"];
var _hoisted_8 = {
  class: "modal-footer"
};
function render(_ctx, _cache, $props, $setup, $data, $options) {
  var _component_Field = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Field");

  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", {
    class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["capabilitiesEdit", {
      busy: _ctx.isBusy
    }])
  }, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.actualCapabilities, function (capability) {
    return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", {
      key: capability.id,
      class: "chip"
    }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
      class: "capability-name",
      title: "".concat(capability.description, " ").concat(_ctx.isIncludedInRole(capability) ? "<br/><br/>".concat(_ctx.translate('UsersManager_IncludedInUsersRole')) : '')
    }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(capability.category) + ": " + Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(capability.name), 9, _hoisted_1), !_ctx.isIncludedInRole(capability) ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("span", {
      key: 0,
      class: "icon-close",
      onClick: function onClick($event) {
        _ctx.capabilityToRemoveId = capability.id;

        _ctx.onToggleCapability(false);
      }
    }, null, 8, _hoisted_2)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]);
  }), 128)), _ctx.availableCapabilitiesGrouped.length ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", _hoisted_3, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
    "model-value": _ctx.capabilityToAddId,
    "onUpdate:modelValue": _cache[0] || (_cache[0] = function ($event) {
      _ctx.capabilityToAddId = $event;

      _ctx.onToggleCapability(true);
    }),
    disabled: _ctx.isBusy,
    uicontrol: "expandable-select",
    name: "add_capability",
    "full-width": true,
    options: _ctx.availableCapabilitiesGrouped
  }, null, 8, ["model-value", "disabled", "options"])])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_4, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_5, [_ctx.isAddingCapability ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("h2", {
    key: 0,
    innerHTML: _ctx.$sanitize(_ctx.confirmAddCapabilityToggleContent)
  }, null, 8, _hoisted_6)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), !_ctx.isAddingCapability ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("h2", {
    key: 1,
    innerHTML: _ctx.$sanitize(_ctx.confirmCapabilityToggleContent)
  }, null, 8, _hoisted_7)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_8, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
    href: "",
    class: "modal-action modal-close btn",
    onClick: _cache[1] || (_cache[1] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
      return _ctx.toggleCapability();
    }, ["prevent"]))
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Yes')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
    href: "",
    class: "modal-action modal-close modal-no",
    onClick: _cache[2] || (_cache[2] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
      _ctx.capabilityToAddOrRemove = null;
      _ctx.capabilityToAddId = null;
      _ctx.capabilityToRemoveId = null;
    }, ["prevent"]))
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_No')), 1)])], 512)], 2);
}
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/CapabilitiesEdit/CapabilitiesEdit.vue?vue&type=template&id=7d385264

// EXTERNAL MODULE: external "CorePluginsAdmin"
var external_CorePluginsAdmin_ = __webpack_require__("a5a2");

// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/CapabilitiesStore/CapabilitiesStore.ts
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

/*!
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */



var CapabilitiesStore_CapabilitiesStore = /*#__PURE__*/function () {
  function CapabilitiesStore() {
    var _this = this;

    _classCallCheck(this, CapabilitiesStore);

    _defineProperty(this, "privateState", Object(external_commonjs_vue_commonjs2_vue_root_Vue_["reactive"])({
      isLoading: false,
      capabilities: []
    }));

    _defineProperty(this, "state", Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(function () {
      return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["readonly"])(_this.privateState);
    }));

    _defineProperty(this, "capabilities", Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(function () {
      return _this.state.value.capabilities;
    }));

    _defineProperty(this, "isLoading", Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(function () {
      return _this.state.value.isLoading;
    }));

    _defineProperty(this, "fetchPromise", void 0);
  }

  _createClass(CapabilitiesStore, [{
    key: "init",
    value: function init() {
      return this.fetchCapabilities();
    }
  }, {
    key: "fetchCapabilities",
    value: function fetchCapabilities() {
      var _this2 = this;

      if (!this.fetchPromise) {
        this.privateState.isLoading = true;
        this.fetchPromise = external_CoreHome_["AjaxHelper"].fetch({
          method: 'UsersManager.getAvailableCapabilities'
        }).then(function (capabilities) {
          _this2.privateState.capabilities = capabilities;
          return _this2.capabilities.value;
        }).finally(function () {
          _this2.privateState.isLoading = false;
        });
      }

      return this.fetchPromise;
    }
  }]);

  return CapabilitiesStore;
}();

/* harmony default export */ var src_CapabilitiesStore_CapabilitiesStore = (new CapabilitiesStore_CapabilitiesStore());
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--14-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--0-1!./plugins/UsersManager/vue/src/CapabilitiesEdit/CapabilitiesEdit.vue?vue&type=script&lang=ts




var _window = window,
    $ = _window.$;
/* harmony default export */ var CapabilitiesEditvue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  props: {
    idsite: [String, Number],
    siteName: {
      type: String,
      required: true
    },
    userLogin: {
      type: String,
      required: true
    },
    userRole: {
      type: String,
      required: true
    },
    capabilities: Array
  },
  components: {
    Field: external_CorePluginsAdmin_["Field"]
  },
  data: function data() {
    return {
      theCapabilities: this.capabilities || [],
      isBusy: false,
      isAddingCapability: false,
      capabilityToAddId: null,
      capabilityToRemoveId: null,
      capabilityToAddOrRemove: null
    };
  },
  emits: ['change'],
  watch: {
    capabilities: function capabilities(newValue) {
      if (newValue) {
        this.theCapabilities = newValue;
      }
    }
  },
  created: function created() {
    var _this = this;

    src_CapabilitiesStore_CapabilitiesStore.init();

    if (!this.capabilities) {
      this.isBusy = true;
      external_CoreHome_["AjaxHelper"].fetch({
        method: 'UsersManager.getUsersPlusRole',
        limit: '1',
        filter_search: this.userLogin
      }).then(function (user) {
        if (!user || !user.capabilities) {
          return [];
        }

        return user.capabilities;
      }).then(function (capabilities) {
        _this.theCapabilities = capabilities;
      }).finally(function () {
        _this.isBusy = false;
      });
    } else {
      this.theCapabilities = this.capabilities;
    }
  },
  methods: {
    onToggleCapability: function onToggleCapability(isAdd) {
      var _this2 = this;

      this.isAddingCapability = isAdd;
      var capabilityToAddOrRemoveId = isAdd ? this.capabilityToAddId : this.capabilityToRemoveId;
      this.capabilityToAddOrRemove = null;
      this.availableCapabilities.forEach(function (capability) {
        if (capability.id === capabilityToAddOrRemoveId) {
          _this2.capabilityToAddOrRemove = capability;
        }
      });

      if (this.$refs.confirmCapabilityToggleModal) {
        $(this.$refs.confirmCapabilityToggleModal).modal({
          dismissible: false,
          yes: function yes() {
            return null;
          }
        }).modal('open');
      }
    },
    toggleCapability: function toggleCapability() {
      if (this.isAddingCapability) {
        this.addCapability(this.capabilityToAddOrRemove);
      } else {
        this.removeCapability(this.capabilityToAddOrRemove);
      }
    },
    isIncludedInRole: function isIncludedInRole(capability) {
      return (capability.includedInRoles || []).indexOf(this.userRole) !== -1;
    },
    getCapabilitiesList: function getCapabilitiesList() {
      var _this3 = this;

      var result = [];
      this.availableCapabilities.forEach(function (capability) {
        if (_this3.isIncludedInRole(capability)) {
          return;
        }

        if (_this3.capabilitiesSet[capability.id]) {
          result.push(capability.id);
        }
      });
      return result;
    },
    addCapability: function addCapability(capability) {
      var _this4 = this;

      this.isBusy = true;
      external_CoreHome_["AjaxHelper"].post({
        method: 'UsersManager.addCapabilities'
      }, {
        userLogin: this.userLogin,
        capabilities: capability.id,
        idSites: this.idsite
      }).then(function () {
        _this4.$emit('change', _this4.getCapabilitiesList());
      }).finally(function () {
        _this4.isBusy = false;
        _this4.capabilityToAddOrRemove = null;
        _this4.capabilityToAddId = null;
        _this4.capabilityToRemoveId = null;
      });
    },
    removeCapability: function removeCapability(capability) {
      var _this5 = this;

      this.isBusy = true;
      external_CoreHome_["AjaxHelper"].post({
        method: 'UsersManager.removeCapabilities'
      }, {
        userLogin: this.userLogin,
        capabilities: capability.id,
        idSites: this.idsite
      }).then(function () {
        _this5.$emit('change', _this5.getCapabilitiesList());
      }).finally(function () {
        _this5.isBusy = false;
        _this5.capabilityToAddOrRemove = null;
        _this5.capabilityToAddId = null;
        _this5.capabilityToRemoveId = null;
      });
    }
  },
  computed: {
    availableCapabilities: function availableCapabilities() {
      return src_CapabilitiesStore_CapabilitiesStore.capabilities.value;
    },
    confirmAddCapabilityToggleContent: function confirmAddCapabilityToggleContent() {
      return Object(external_CoreHome_["translate"])('UsersManager_AreYouSureAddCapability', "<strong>".concat(this.userLogin, "</strong>"), "<strong>".concat(this.capabilityToAddOrRemove ? this.capabilityToAddOrRemove.name : '', "</strong>"), "<strong>".concat(this.siteNameText, "</strong>"));
    },
    confirmCapabilityToggleContent: function confirmCapabilityToggleContent() {
      return Object(external_CoreHome_["translate"])('UsersManager_AreYouSureRemoveCapability', "<strong>".concat(this.capabilityToAddOrRemove ? this.capabilityToAddOrRemove.name : '', "</strong>"), "<strong>".concat(this.userLogin, "</strong>"), "<strong>".concat(this.siteNameText, "</strong>"));
    },
    siteNameText: function siteNameText() {
      return external_CoreHome_["Matomo"].helper.htmlEntities(this.siteName);
    },
    availableCapabilitiesGrouped: function availableCapabilitiesGrouped() {
      var _this6 = this;

      var availableCapabilitiesGrouped = this.availableCapabilities.filter(function (c) {
        return !_this6.capabilitiesSet[c.id];
      }).map(function (c) {
        return {
          group: c.category,
          key: c.id,
          value: c.name,
          tooltip: c.description
        };
      });
      availableCapabilitiesGrouped.sort(function (lhs, rhs) {
        if (lhs.group === rhs.group) {
          if (lhs.value === rhs.value) {
            return 0;
          }

          return lhs.value < rhs.value ? -1 : 1;
        }

        return lhs.group < rhs.group ? -1 : 1;
      });
      return availableCapabilitiesGrouped;
    },
    capabilitiesSet: function capabilitiesSet() {
      var _this7 = this;

      var capabilitiesSet = {};
      var capabilities = this.theCapabilities;
      (capabilities || []).forEach(function (capability) {
        capabilitiesSet[capability] = true;
      });
      (this.availableCapabilities || []).forEach(function (capability) {
        if (_this7.isIncludedInRole(capability)) {
          capabilitiesSet[capability.id] = true;
        }
      });
      return capabilitiesSet;
    },
    actualCapabilities: function actualCapabilities() {
      var capabilitiesSet = this.capabilitiesSet;
      return this.availableCapabilities.filter(function (c) {
        return !!capabilitiesSet[c.id];
      });
    }
  }
}));
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/CapabilitiesEdit/CapabilitiesEdit.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/CapabilitiesEdit/CapabilitiesEdit.vue



CapabilitiesEditvue_type_script_lang_ts.render = render

/* harmony default export */ var CapabilitiesEdit = (CapabilitiesEditvue_type_script_lang_ts);
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/CapabilitiesEdit/CapabilitiesEdit.adapter.ts
/*!
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */


/* harmony default export */ var CapabilitiesEdit_adapter = (Object(external_CoreHome_["createAngularJsAdapter"])({
  component: CapabilitiesEdit,
  scope: {
    idsite: {
      angularJsBind: '<'
    },
    siteName: {
      angularJsBind: '<'
    },
    userLogin: {
      angularJsBind: '<'
    },
    userRole: {
      angularJsBind: '<'
    },
    capabilities: {
      angularJsBind: '<'
    },
    onCapabilitiesChange: {
      angularJsBind: '&',
      vue: 'change'
    }
  },
  directiveName: 'piwikCapabilitiesEdit',
  restrict: 'E',
  $inject: ['$timeout'],
  events: {
    change: function change(caps, vm, scope, element, attrs, controller, $timeout) {
      $timeout(function () {
        if (scope.onCapabilitiesChange) {
          scope.onCapabilitiesChange.call({
            capabilities: caps
          });
        }
      });
    }
  }
}));
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--12-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--0-1!./plugins/UsersManager/vue/src/UserPermissionsEdit/UserPermissionsEdit.vue?vue&type=template&id=061f5e4b

var UserPermissionsEditvue_type_template_id_061f5e4b_hoisted_1 = {
  key: 0,
  class: "row"
};
var UserPermissionsEditvue_type_template_id_061f5e4b_hoisted_2 = {
  class: "row to-all-websites"
};
var UserPermissionsEditvue_type_template_id_061f5e4b_hoisted_3 = {
  class: "col s12"
};
var UserPermissionsEditvue_type_template_id_061f5e4b_hoisted_4 = {
  style: {
    "margin-right": "3.5px"
  }
};
var UserPermissionsEditvue_type_template_id_061f5e4b_hoisted_5 = {
  id: "all-sites-access-select",
  style: {
    "margin-right": "3.5px"
  }
};
var UserPermissionsEditvue_type_template_id_061f5e4b_hoisted_6 = {
  style: {
    "margin-top": "18px"
  }
};
var UserPermissionsEditvue_type_template_id_061f5e4b_hoisted_7 = {
  class: "filters row"
};
var UserPermissionsEditvue_type_template_id_061f5e4b_hoisted_8 = {
  class: "col s12 m12 l8"
};
var _hoisted_9 = {
  class: "input-field bulk-actions",
  style: {
    "margin-right": "3.5px"
  }
};
var _hoisted_10 = {
  id: "user-permissions-edit-bulk-actions",
  class: "dropdown-content"
};
var _hoisted_11 = {
  class: "dropdown-trigger",
  "data-target": "user-permissions-bulk-set-access"
};
var _hoisted_12 = {
  id: "user-permissions-bulk-set-access",
  class: "dropdown-content"
};
var _hoisted_13 = ["onClick"];
var _hoisted_14 = {
  class: "input-field site-filter",
  style: {
    "margin-right": "3.5px"
  }
};
var _hoisted_15 = ["value", "placeholder"];
var _hoisted_16 = {
  class: "input-field access-filter",
  style: {
    "margin-right": "3.5px"
  }
};
var _hoisted_17 = {
  key: 0,
  class: "col s12 m12 l4 sites-for-permission-pagination-container"
};
var _hoisted_18 = {
  class: "sites-for-permission-pagination"
};
var _hoisted_19 = {
  class: "counter"
};
var _hoisted_20 = ["textContent"];
var _hoisted_21 = {
  class: "roles-help-notification"
};
var _hoisted_22 = ["innerHTML"];
var _hoisted_23 = {
  class: "capabilities-help-notification"
};
var _hoisted_24 = {
  id: "sitesForPermission"
};
var _hoisted_25 = {
  class: "select-cell"
};
var _hoisted_26 = {
  class: "checkbox-container"
};
var _hoisted_27 = ["checked"];

var _hoisted_28 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", null, null, -1);

var _hoisted_29 = {
  class: "role_header"
};
var _hoisted_30 = ["innerHTML"];

var _hoisted_31 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "icon-help"
}, null, -1);

var _hoisted_32 = [_hoisted_31];
var _hoisted_33 = {
  class: "capabilities_header"
};
var _hoisted_34 = ["innerHTML"];

var _hoisted_35 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "icon-help"
}, null, -1);

var _hoisted_36 = [_hoisted_35];
var _hoisted_37 = {
  key: 0,
  class: "select-all-row"
};
var _hoisted_38 = {
  colspan: "4"
};
var _hoisted_39 = {
  key: 0
};
var _hoisted_40 = ["innerHTML"];
var _hoisted_41 = ["innerHTML"];
var _hoisted_42 = {
  key: 1
};
var _hoisted_43 = ["innerHTML"];
var _hoisted_44 = ["innerHTML"];
var _hoisted_45 = {
  class: "select-cell"
};
var _hoisted_46 = {
  class: "checkbox-container"
};
var _hoisted_47 = ["id", "onUpdate:modelValue"];

var _hoisted_48 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", null, null, -1);

var _hoisted_49 = {
  class: "role-select"
};
var _hoisted_50 = {
  class: "delete-access-confirm-modal modal",
  ref: "deleteAccessConfirmModal"
};
var _hoisted_51 = {
  class: "modal-content"
};
var _hoisted_52 = ["innerHTML"];
var _hoisted_53 = ["innerHTML"];
var _hoisted_54 = {
  class: "modal-footer"
};
var _hoisted_55 = {
  class: "change-access-confirm-modal modal",
  ref: "changeAccessConfirmModal"
};
var _hoisted_56 = {
  class: "modal-content"
};
var _hoisted_57 = ["innerHTML"];
var _hoisted_58 = ["innerHTML"];
var _hoisted_59 = {
  class: "modal-footer"
};
var _hoisted_60 = {
  class: "confirm-give-access-all-sites modal",
  ref: "confirmGiveAccessAllSitesModal"
};
var _hoisted_61 = {
  class: "modal-content"
};
var _hoisted_62 = ["innerHTML"];
var _hoisted_63 = {
  class: "modal-footer"
};
function UserPermissionsEditvue_type_template_id_061f5e4b_render(_ctx, _cache, $props, $setup, $data, $options) {
  var _component_Notification = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Notification");

  var _component_Field = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Field");

  var _component_CapabilitiesEdit = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("CapabilitiesEdit");

  var _directive_dropdown_menu = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveDirective"])("dropdown-menu");

  var _directive_content_table = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveDirective"])("content-table");

  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", {
    class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["userPermissionsEdit", {
      loading: _ctx.isLoadingAccess
    }])
  }, [!_ctx.hasAccessToAtLeastOneSite ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", UserPermissionsEditvue_type_template_id_061f5e4b_hoisted_1, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Notification, {
    context: "warning",
    type: "transient",
    noclear: true
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(function () {
      return [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("strong", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Warning')) + ":", 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(" " + Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_NoAccessWarning')), 1)];
    }),
    _: 1
  })])])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", UserPermissionsEditvue_type_template_id_061f5e4b_hoisted_2, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", UserPermissionsEditvue_type_template_id_061f5e4b_hoisted_3, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", UserPermissionsEditvue_type_template_id_061f5e4b_hoisted_4, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_GiveAccessToAll')) + ":", 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", UserPermissionsEditvue_type_template_id_061f5e4b_hoisted_5, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
    modelValue: _ctx.allWebsitesAccssLevelSet,
    "onUpdate:modelValue": _cache[0] || (_cache[0] = function ($event) {
      return _ctx.allWebsitesAccssLevelSet = $event;
    }),
    uicontrol: "select",
    options: _ctx.filteredAccessLevels,
    "full-width": true
  }, null, 8, ["modelValue", "options"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
    href: "",
    class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["btn", {
      disabled: _ctx.isGivingAccessToAllSites
    }]),
    onClick: _cache[1] || (_cache[1] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
      return _ctx.showChangeAccessAllSitesModal();
    }, ["prevent"]))
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Apply')), 3)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("p", UserPermissionsEditvue_type_template_id_061f5e4b_hoisted_6, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_OrManageIndividually')) + ":", 1)])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", UserPermissionsEditvue_type_template_id_061f5e4b_hoisted_7, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", UserPermissionsEditvue_type_template_id_061f5e4b_hoisted_8, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_9, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
    class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["dropdown-trigger btn", {
      disabled: _ctx.isBulkActionsDisabled
    }]),
    href: "",
    "data-target": "user-permissions-edit-bulk-actions"
  }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_BulkActions')), 1)], 2), [[_directive_dropdown_menu, {
    activates: '#user-permissions-edit-bulk-actions'
  }]]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("ul", _hoisted_10, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("li", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", _hoisted_11, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_SetPermission')), 1)], 512), [[_directive_dropdown_menu, {
    activates: '#user-permissions-bulk-set-access'
  }]]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("ul", _hoisted_12, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.filteredAccessLevels, function (access) {
    return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("li", {
      key: access.key
    }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
      href: "",
      onClick: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
        _ctx.siteAccessToChange = null;
        _ctx.roleToChangeTo = access.key;

        _ctx.showChangeAccessConfirm();
      }, ["prevent"])
    }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(access.value), 9, _hoisted_13)]);
  }), 128))])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("li", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
    href: "",
    onClick: _cache[2] || (_cache[2] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
      _ctx.siteAccessToChange = null;
      _ctx.roleToChangeTo = 'noaccess';

      _ctx.showRemoveAccessConfirm();
    }, ["prevent"]))
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_RemovePermissions')), 1)])])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_14, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("input", {
    type: "text",
    value: _ctx.siteNameFilter,
    onKeydown: _cache[3] || (_cache[3] = function ($event) {
      _ctx.onChangeSiteFilter($event);
    }),
    onChange: _cache[4] || (_cache[4] = function ($event) {
      _ctx.onChangeSiteFilter($event);
    }),
    placeholder: _ctx.translate('UsersManager_FilterByWebsite')
  }, null, 40, _hoisted_15)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_16, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
    modelValue: _ctx.accessLevelFilter,
    "onUpdate:modelValue": _cache[5] || (_cache[5] = function ($event) {
      return _ctx.accessLevelFilter = $event;
    }),
    uicontrol: "select",
    options: _ctx.filteredSelectAccessLevels,
    "full-width": true,
    placeholder: _ctx.translate('UsersManager_FilterByAccess')
  }, null, 8, ["modelValue", "options", "placeholder"])])])]), _ctx.totalEntries > _ctx.limit ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", _hoisted_17, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_18, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
    class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["prev", {
      disabled: _ctx.offset <= 0
    }])
  }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
    class: "pointer",
    onClick: _cache[6] || (_cache[6] = function ($event) {
      return _ctx.gotoPreviousPage();
    })
  }, "« " + Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Previous')), 1)], 2), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", _hoisted_19, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
    textContent: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.paginationText)
  }, null, 8, _hoisted_20)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
    class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["next", {
      disabled: _ctx.offset + _ctx.limit >= _ctx.totalEntries
    }])
  }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
    class: "pointer",
    onClick: _cache[7] || (_cache[7] = function ($event) {
      return _ctx.gotoNextPage();
    })
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Next')) + " »", 1)], 2)])])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_21, [_ctx.isRoleHelpToggled ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_Notification, {
    key: 0,
    context: "info",
    type: "persistent",
    noclear: true
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(function () {
      return [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
        innerHTML: _ctx.$sanitize(_ctx.rolesHelpText)
      }, null, 8, _hoisted_22)];
    }),
    _: 1
  })) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_23, [_ctx.isCapabilitiesHelpToggled ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_Notification, {
    key: 0,
    context: "info",
    type: "persistent",
    noclear: true
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(function () {
      return [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_CapabilitiesHelp')), 1)];
    }),
    _: 1
  })) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("table", _hoisted_24, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("thead", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("tr", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", _hoisted_25, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", _hoisted_26, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("label", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("input", {
    type: "checkbox",
    id: "perm_edit_select_all",
    checked: _ctx.isAllCheckboxSelected,
    onChange: _cache[8] || (_cache[8] = function ($event) {
      return _ctx.onAllCheckboxChange($event);
    })
  }, null, 40, _hoisted_27), _hoisted_28])])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Name')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", _hoisted_29, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
    innerHTML: _ctx.$sanitize("".concat(_ctx.translate('UsersManager_Role'), " "))
  }, null, 8, _hoisted_30), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
    href: "",
    class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["helpIcon", {
      sticky: _ctx.isRoleHelpToggled
    }]),
    onClick: _cache[9] || (_cache[9] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
      return _ctx.isRoleHelpToggled = !_ctx.isRoleHelpToggled;
    }, ["prevent"]))
  }, _hoisted_32, 2)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", _hoisted_33, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
    innerHTML: _ctx.$sanitize("".concat(_ctx.translate('UsersManager_Capabilities'), " "))
  }, null, 8, _hoisted_34), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
    href: "",
    class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["helpIcon", {
      sticky: _ctx.isCapabilitiesHelpToggled
    }]),
    onClick: _cache[10] || (_cache[10] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
      return _ctx.isCapabilitiesHelpToggled = !_ctx.isCapabilitiesHelpToggled;
    }, ["prevent"]))
  }, _hoisted_36, 2)])])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("tbody", null, [_ctx.isAllCheckboxSelected && _ctx.siteAccess.length < _ctx.totalEntries ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("tr", _hoisted_37, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", _hoisted_38, [!_ctx.areAllResultsSelected ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", _hoisted_39, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
    innerHTML: _ctx.$sanitize(_ctx.theDisplayedWebsitesAreSelectedText),
    style: {
      "margin-right": "3.5px"
    }
  }, null, 8, _hoisted_40), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
    href: "#",
    onClick: _cache[11] || (_cache[11] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
      return _ctx.areAllResultsSelected = !_ctx.areAllResultsSelected;
    }, ["prevent"])),
    innerHTML: _ctx.$sanitize(_ctx.clickToSelectAllText)
  }, null, 8, _hoisted_41)])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.areAllResultsSelected ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", _hoisted_42, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
    innerHTML: _ctx.$sanitize(_ctx.allWebsitesAreSelectedText),
    style: {
      "margin-right": "3.5px"
    }
  }, null, 8, _hoisted_43), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
    href: "#",
    onClick: _cache[12] || (_cache[12] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
      return _ctx.areAllResultsSelected = !_ctx.areAllResultsSelected;
    }, ["prevent"])),
    innerHTML: _ctx.$sanitize(_ctx.clickToSelectDisplayedWebsitesText)
  }, null, 8, _hoisted_44)])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)])])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.siteAccess, function (entry, index) {
    return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("tr", {
      key: entry.idsite
    }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", _hoisted_45, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", _hoisted_46, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("label", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("input", {
      type: "checkbox",
      id: "perm_edit_select_row".concat(index),
      "onUpdate:modelValue": function onUpdateModelValue($event) {
        return _ctx.selectedRows[index] = $event;
      },
      onClick: _cache[13] || (_cache[13] = function ($event) {
        return _ctx.onRowSelected();
      })
    }, null, 8, _hoisted_47), [[external_commonjs_vue_commonjs2_vue_root_Vue_["vModelCheckbox"], _ctx.selectedRows[index]]]), _hoisted_48])])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(entry.site_name), 1)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_49, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
      "model-value": entry.role,
      "onUpdate:modelValue": function onUpdateModelValue($event) {
        _ctx.onRoleChange(entry, $event);
      },
      uicontrol: "select",
      options: _ctx.filteredAccessLevels,
      "full-width": true
    }, null, 8, ["model-value", "onUpdate:modelValue", "options"])])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_CapabilitiesEdit, {
      idsite: entry.idsite,
      "site-name": entry.site_name,
      "user-login": _ctx.userLogin,
      "user-role": entry.role,
      capabilities: entry.capabilities,
      onChange: _cache[14] || (_cache[14] = function ($event) {
        return _ctx.fetchAccess();
      })
    }, null, 8, ["idsite", "site-name", "user-login", "user-role", "capabilities"])])])]);
  }), 128))])], 512), [[_directive_content_table]]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_50, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_51, [_ctx.siteAccessToChange ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("h3", {
    key: 0,
    innerHTML: _ctx.$sanitize(_ctx.deletePermConfirmSingleText)
  }, null, 8, _hoisted_52)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), !_ctx.siteAccessToChange ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("p", {
    key: 1,
    innerHTML: _ctx.$sanitize(_ctx.deletePermConfirmMultipleText)
  }, null, 8, _hoisted_53)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_54, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
    href: "",
    class: "modal-action modal-close btn",
    onClick: _cache[15] || (_cache[15] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
      return _ctx.changeUserRole();
    }, ["prevent"])),
    style: {
      "margin-right": "3.5px"
    }
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Yes')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
    href: "",
    class: "modal-action modal-close modal-no",
    onClick: _cache[16] || (_cache[16] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
      _ctx.siteAccessToChange = null;
      _ctx.roleToChangeTo = null;
    }, ["prevent"]))
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_No')), 1)])], 512), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_55, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_56, [_ctx.siteAccessToChange ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("h3", {
    key: 0,
    innerHTML: _ctx.$sanitize(_ctx.changePermToSiteConfirmSingleText)
  }, null, 8, _hoisted_57)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), !_ctx.siteAccessToChange ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("p", {
    key: 1,
    innerHTML: _ctx.$sanitize(_ctx.changePermToSiteConfirmMultipleText)
  }, null, 8, _hoisted_58)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_59, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
    href: "",
    class: "modal-action modal-close btn",
    onClick: _cache[17] || (_cache[17] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
      return _ctx.changeUserRole();
    }, ["prevent"])),
    style: {
      "margin-right": "3.5px"
    }
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Yes')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
    href: "",
    class: "modal-action modal-close modal-no",
    onClick: _cache[18] || (_cache[18] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
      _ctx.siteAccessToChange.role = _ctx.previousRole;
      _ctx.siteAccessToChange = null;
      _ctx.roleToChangeTo = null;
    }, ["prevent"]))
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_No')), 1)])], 512), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_60, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_61, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h3", {
    innerHTML: _ctx.$sanitize(_ctx.changePermToAllSitesConfirmText)
  }, null, 8, _hoisted_62), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("p", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_ChangePermToAllSitesConfirm2')), 1)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_63, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
    href: "",
    class: "modal-action modal-close btn",
    onClick: _cache[19] || (_cache[19] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
      return _ctx.giveAccessToAllSites();
    }, ["prevent"])),
    style: {
      "margin-right": "3.5px"
    }
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Yes')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
    href: "",
    class: "modal-action modal-close modal-no",
    onClick: _cache[20] || (_cache[20] = function ($event) {
      return $event.preventDefault();
    })
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_No')), 1)])], 512)], 2);
}
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/UserPermissionsEdit/UserPermissionsEdit.vue?vue&type=template&id=061f5e4b

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--14-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--0-1!./plugins/UsersManager/vue/src/UserPermissionsEdit/UserPermissionsEdit.vue?vue&type=script&lang=ts




var UserPermissionsEditvue_type_script_lang_ts_window = window,
    UserPermissionsEditvue_type_script_lang_ts_$ = UserPermissionsEditvue_type_script_lang_ts_window.$;
/* harmony default export */ var UserPermissionsEditvue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  props: {
    userLogin: {
      type: String,
      required: true
    },
    limit: {
      type: Number,
      default: 10
    },
    accessLevels: {
      type: Array,
      required: true
    },
    filterAccessLevels: {
      type: Array,
      required: true
    }
  },
  components: {
    Notification: external_CoreHome_["Notification"],
    Field: external_CorePluginsAdmin_["Field"],
    CapabilitiesEdit: CapabilitiesEdit
  },
  directives: {
    DropdownMenu: external_CoreHome_["DropdownMenu"],
    ContentTable: external_CoreHome_["ContentTable"]
  },
  data: function data() {
    return {
      siteAccess: [],
      offset: 0,
      totalEntries: null,
      accessLevelFilter: '',
      siteNameFilter: '',
      isLoadingAccess: false,
      allWebsitesAccssLevelSet: 'view',
      isAllCheckboxSelected: false,
      selectedRows: {},
      isBulkActionsDisabled: true,
      areAllResultsSelected: false,
      previousRole: null,
      hasAccessToAtLeastOneSite: true,
      isRoleHelpToggled: false,
      isCapabilitiesHelpToggled: false,
      isGivingAccessToAllSites: false,
      roleToChangeTo: null,
      siteAccessToChange: null
    };
  },
  emits: ['userHasAccessDetected', 'accessChanged'],
  created: function created() {
    var _this = this;

    this.onChangeSiteFilter = Object(external_CoreHome_["debounce"])(this.onChangeSiteFilter, 300);
    Object(external_commonjs_vue_commonjs2_vue_root_Vue_["watch"])(function () {
      return _this.allPropsWatch;
    }, function () {
      if (_this.limit) {
        _this.fetchAccess();
      }
    });
    this.fetchAccess();
  },
  watch: {
    accessLevelFilter: function accessLevelFilter() {
      this.offset = 0;
      this.fetchAccess();
    }
  },
  methods: {
    onAllCheckboxChange: function onAllCheckboxChange(event) {
      var _this2 = this;

      this.isAllCheckboxSelected = event.target.checked;

      if (!this.isAllCheckboxSelected) {
        this.clearSelection();
      } else {
        this.siteAccess.forEach(function (e, i) {
          _this2.selectedRows[i] = true;
        });
        this.isBulkActionsDisabled = false;
      }
    },
    clearSelection: function clearSelection() {
      this.selectedRows = {};
      this.areAllResultsSelected = false;
      this.isBulkActionsDisabled = true;
      this.isAllCheckboxSelected = false;
      this.siteAccessToChange = null;
    },
    onRowSelected: function onRowSelected() {
      var _this3 = this;

      setTimeout(function () {
        var selectedRowKeyCount = _this3.selectedRowsCount;
        _this3.isBulkActionsDisabled = selectedRowKeyCount === 0;
        _this3.isAllCheckboxSelected = selectedRowKeyCount === _this3.siteAccess.length;
      });
    },
    fetchAccess: function fetchAccess() {
      var _this4 = this;

      this.isLoadingAccess = true;
      return external_CoreHome_["AjaxHelper"].fetch({
        method: 'UsersManager.getSitesAccessForUser',
        limit: this.limit,
        offset: this.offset,
        filter_search: this.siteNameFilter,
        filter_access: this.accessLevelFilter,
        userLogin: this.userLogin
      }, {
        returnResponseObject: true
      }).then(function (helper) {
        var result = helper.getRequestHandle();
        _this4.isLoadingAccess = false;
        _this4.siteAccess = result.responseJSON;
        _this4.totalEntries = parseInt(result.getResponseHeader('x-matomo-total-results'), 10) || 0;
        _this4.hasAccessToAtLeastOneSite = !!result.getResponseHeader('x-matomo-has-some');

        _this4.$emit('userHasAccessDetected', {
          hasAccess: _this4.hasAccessToAtLeastOneSite
        });

        _this4.clearSelection();
      }).catch(function () {
        _this4.isLoadingAccess = false;

        _this4.clearSelection();
      });
    },
    gotoPreviousPage: function gotoPreviousPage() {
      this.offset = Math.max(0, this.offset - this.limit);
      this.fetchAccess();
    },
    gotoNextPage: function gotoNextPage() {
      var newOffset = this.offset + this.limit;

      if (newOffset >= (this.totalEntries || 0)) {
        return;
      }

      this.offset = newOffset;
      this.fetchAccess();
    },
    showRemoveAccessConfirm: function showRemoveAccessConfirm() {
      UserPermissionsEditvue_type_script_lang_ts_$(this.$refs.deleteAccessConfirmModal).modal({
        dismissible: false
      }).modal('open');
    },
    changeUserRole: function changeUserRole() {
      var _this5 = this;

      var getSelectedSites = function getSelectedSites() {
        var result = [];
        Object.keys(_this5.selectedRows).forEach(function (index) {
          if (_this5.selectedRows[index] && _this5.siteAccess[index] // safety check
          ) {
            result.push(_this5.siteAccess[index].idsite);
          }
        });
        return result;
      };

      var getAllSitesInSearch = function getAllSitesInSearch() {
        return external_CoreHome_["AjaxHelper"].fetch({
          method: 'UsersManager.getSitesAccessForUser',
          filter_search: _this5.siteNameFilter,
          filter_access: _this5.accessLevelFilter,
          userLogin: _this5.userLogin,
          filter_limit: '-1'
        }).then(function (access) {
          return access.map(function (a) {
            return a.idsite;
          });
        });
      };

      this.isLoadingAccess = true;
      return Promise.resolve().then(function () {
        if (_this5.siteAccessToChange) {
          return [_this5.siteAccessToChange.idsite];
        }

        if (_this5.areAllResultsSelected) {
          return getAllSitesInSearch();
        }

        return getSelectedSites();
      }).then(function (idSites) {
        return external_CoreHome_["AjaxHelper"].post({
          method: 'UsersManager.setUserAccess'
        }, {
          userLogin: _this5.userLogin,
          access: _this5.roleToChangeTo,
          idSites: idSites
        });
      }).catch(function () {// ignore (errors will still be displayed to the user)
      }).then(function () {
        _this5.$emit('accessChanged');

        return _this5.fetchAccess();
      });
    },
    showChangeAccessConfirm: function showChangeAccessConfirm() {
      UserPermissionsEditvue_type_script_lang_ts_$(this.$refs.changeAccessConfirmModal).modal({
        dismissible: false
      }).modal('open');
    },
    getRoleDisplay: function getRoleDisplay(role) {
      var result = null;
      this.filteredAccessLevels.forEach(function (entry) {
        if (entry.key === role) {
          result = entry.value;
        }
      });
      return result;
    },
    giveAccessToAllSites: function giveAccessToAllSites() {
      var _this6 = this;

      this.isGivingAccessToAllSites = true;
      external_CoreHome_["AjaxHelper"].fetch({
        method: 'SitesManager.getSitesWithAdminAccess'
      }).then(function (allSites) {
        var idSites = allSites.map(function (s) {
          return s.idsite;
        });
        return external_CoreHome_["AjaxHelper"].post({
          method: 'UsersManager.setUserAccess'
        }, {
          userLogin: _this6.userLogin,
          access: _this6.allWebsitesAccssLevelSet,
          idSites: idSites
        });
      }).then(function () {
        return _this6.fetchAccess();
      }).finally(function () {
        _this6.isGivingAccessToAllSites = false;
      });
    },
    showChangeAccessAllSitesModal: function showChangeAccessAllSitesModal() {
      UserPermissionsEditvue_type_script_lang_ts_$(this.$refs.confirmGiveAccessAllSitesModal).modal({
        dismissible: false
      }).modal('open');
    },
    onChangeSiteFilter: function onChangeSiteFilter(event) {
      var _this7 = this;

      setTimeout(function () {
        var inputValue = event.target.value;

        if (_this7.siteNameFilter !== inputValue) {
          _this7.siteNameFilter = inputValue;
          _this7.offset = 0;

          _this7.fetchAccess();
        }
      });
    },
    onRoleChange: function onRoleChange(entry, newRole) {
      this.previousRole = entry.role;
      this.roleToChangeTo = newRole;
      this.siteAccessToChange = entry;
      this.showChangeAccessConfirm();
    }
  },
  computed: {
    rolesHelpText: function rolesHelpText() {
      return Object(external_CoreHome_["translate"])('UsersManager_RolesHelp', '<a href="https://matomo.org/faq/general/faq_70/" target="_blank" rel="noreferrer noopener">', '</a>', '<a href="https://matomo.org/faq/general/faq_69/" target="_blank" rel="noreferrer noopener">', '</a>');
    },
    theDisplayedWebsitesAreSelectedText: function theDisplayedWebsitesAreSelectedText() {
      var text = Object(external_CoreHome_["translate"])('UsersManager_TheDisplayedWebsitesAreSelected', "<strong>".concat(this.siteAccess.length, "</strong>"));
      return "".concat(text, " ");
    },
    clickToSelectAllText: function clickToSelectAllText() {
      return Object(external_CoreHome_["translate"])('UsersManager_ClickToSelectAll', "<strong>".concat(this.totalEntries, "</strong>"));
    },
    allWebsitesAreSelectedText: function allWebsitesAreSelectedText() {
      return Object(external_CoreHome_["translate"])('UsersManager_AllWebsitesAreSelected', "<strong>".concat(this.totalEntries, "</strong>"));
    },
    clickToSelectDisplayedWebsitesText: function clickToSelectDisplayedWebsitesText() {
      return Object(external_CoreHome_["translate"])('UsersManager_ClickToSelectDisplayedWebsites', "<strong>".concat(this.siteAccess.length, "</strong>"));
    },
    deletePermConfirmSingleText: function deletePermConfirmSingleText() {
      return Object(external_CoreHome_["translate"])('UsersManager_DeletePermConfirmSingle', "<strong>".concat(this.userLogin, "</strong>"), "<strong>".concat(this.siteAccessToChangeName, "</strong>"));
    },
    deletePermConfirmMultipleText: function deletePermConfirmMultipleText() {
      return Object(external_CoreHome_["translate"])('UsersManager_DeletePermConfirmMultiple', "<strong>".concat(this.userLogin, "</strong>"), "<strong>".concat(this.affectedSitesCount, "</strong>"));
    },
    changePermToSiteConfirmSingleText: function changePermToSiteConfirmSingleText() {
      return Object(external_CoreHome_["translate"])('UsersManager_ChangePermToSiteConfirmSingle', "<strong>".concat(this.userLogin, "</strong>"), "<strong>".concat(this.siteAccessToChangeName, "</strong>"), "<strong>".concat(this.getRoleDisplay(this.roleToChangeTo), "</strong>"));
    },
    changePermToSiteConfirmMultipleText: function changePermToSiteConfirmMultipleText() {
      return Object(external_CoreHome_["translate"])('UsersManager_ChangePermToSiteConfirmMultiple', "<strong>".concat(this.userLogin, "</strong>"), "<strong>".concat(this.affectedSitesCount, "</strong>"), "<strong>".concat(this.getRoleDisplay(this.roleToChangeTo), "</strong>"));
    },
    changePermToAllSitesConfirmText: function changePermToAllSitesConfirmText() {
      return Object(external_CoreHome_["translate"])('UsersManager_ChangePermToAllSitesConfirm', "<strong>".concat(this.userLogin, "</strong>"), "<strong>".concat(this.getRoleDisplay(this.allWebsitesAccssLevelSet), "</strong>"));
    },
    paginationLowerBound: function paginationLowerBound() {
      return this.offset + 1;
    },
    paginationUpperBound: function paginationUpperBound() {
      if (!this.totalEntries) {
        return '?';
      }

      return Math.min(this.offset + this.limit, this.totalEntries);
    },
    filteredAccessLevels: function filteredAccessLevels() {
      return this.accessLevels.filter(function (entry) {
        return entry.key !== 'superuser';
      });
    },
    filteredSelectAccessLevels: function filteredSelectAccessLevels() {
      return this.filterAccessLevels.filter(function (entry) {
        return entry.key !== 'superuser';
      });
    },
    selectedRowsCount: function selectedRowsCount() {
      var selectedRowKeyCount = 0;
      Object.values(this.selectedRows).forEach(function (v) {
        if (v) {
          selectedRowKeyCount += 1;
        }
      });
      return selectedRowKeyCount;
    },
    affectedSitesCount: function affectedSitesCount() {
      if (this.areAllResultsSelected) {
        return this.totalEntries;
      }

      return this.selectedRowsCount;
    },
    allPropsWatch: function allPropsWatch() {
      // see https://github.com/vuejs/vue/issues/844#issuecomment-390500758
      // eslint-disable-next-line no-sequences
      return this.userLogin, this.limit, this.accessLevels, this.filterAccessLevels, Date.now();
    },
    siteAccessToChangeName: function siteAccessToChangeName() {
      return this.siteAccessToChange ? external_CoreHome_["Matomo"].helper.htmlEntities(this.siteAccessToChange.site_name) : '';
    },
    paginationText: function paginationText() {
      var text = Object(external_CoreHome_["translate"])('General_Pagination', "".concat(this.paginationLowerBound), "".concat(this.paginationUpperBound), "".concat(this.totalEntries));
      return " ".concat(text, " ");
    }
  }
}));
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/UserPermissionsEdit/UserPermissionsEdit.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/UserPermissionsEdit/UserPermissionsEdit.vue



UserPermissionsEditvue_type_script_lang_ts.render = UserPermissionsEditvue_type_template_id_061f5e4b_render

/* harmony default export */ var UserPermissionsEdit = (UserPermissionsEditvue_type_script_lang_ts);
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/UserPermissionsEdit/UserPermissionsEdit.adapter.ts
/*!
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */


/* harmony default export */ var UserPermissionsEdit_adapter = (Object(external_CoreHome_["createAngularJsAdapter"])({
  component: UserPermissionsEdit,
  scope: {
    userLogin: {
      angularJsBind: '<'
    },
    limit: {
      angularJsBind: '<'
    },
    onUserHasAccessDetected: {
      angularJsBind: '&',
      vue: 'userHasAccessDetected'
    },
    onAccessChange: {
      angularJsBind: '&',
      vue: 'accessChanged'
    },
    accessLevels: {
      angularJsBind: '<'
    },
    filterAccessLevels: {
      angularJsBind: '<'
    }
  },
  directiveName: 'piwikUserPermissionsEdit',
  restrict: 'E'
}));
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--12-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--0-1!./plugins/UsersManager/vue/src/UserEditForm/UserEditForm.vue?vue&type=template&id=851b548a

var UserEditFormvue_type_template_id_851b548a_hoisted_1 = {
  class: "row"
};
var UserEditFormvue_type_template_id_851b548a_hoisted_2 = {
  key: 0,
  class: "col m2 entityList"
};
var UserEditFormvue_type_template_id_851b548a_hoisted_3 = {
  class: "listCircle"
};
var UserEditFormvue_type_template_id_851b548a_hoisted_4 = {
  key: 0,
  class: "icon-warning"
};

var UserEditFormvue_type_template_id_851b548a_hoisted_5 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", {
  class: "save-button-spacer hide-on-small-only"
}, null, -1);

var UserEditFormvue_type_template_id_851b548a_hoisted_6 = {
  href: "",
  class: "entityCancelLink"
};
var UserEditFormvue_type_template_id_851b548a_hoisted_7 = {
  class: "visibleTab col m10"
};
var UserEditFormvue_type_template_id_851b548a_hoisted_8 = {
  key: 0,
  class: "basic-info-tab"
};
var UserEditFormvue_type_template_id_851b548a_hoisted_9 = {
  key: 0,
  class: "entityCancel"
};
var UserEditFormvue_type_template_id_851b548a_hoisted_10 = {
  key: 1,
  class: "user-permissions"
};
var UserEditFormvue_type_template_id_851b548a_hoisted_11 = {
  key: 0
};
var UserEditFormvue_type_template_id_851b548a_hoisted_12 = {
  key: 1,
  class: "alert alert-info"
};
var UserEditFormvue_type_template_id_851b548a_hoisted_13 = {
  key: 2,
  class: "superuser-access"
};
var UserEditFormvue_type_template_id_851b548a_hoisted_14 = {
  class: "superuser-confirm-modal modal",
  ref: "superUserConfirmModal"
};
var UserEditFormvue_type_template_id_851b548a_hoisted_15 = {
  class: "modal-content"
};
var UserEditFormvue_type_template_id_851b548a_hoisted_16 = {
  key: 0
};
var UserEditFormvue_type_template_id_851b548a_hoisted_17 = {
  key: 1
};
var UserEditFormvue_type_template_id_851b548a_hoisted_18 = {
  class: "modal-footer"
};
var UserEditFormvue_type_template_id_851b548a_hoisted_19 = {
  key: 3,
  class: "twofa-reset"
};
var UserEditFormvue_type_template_id_851b548a_hoisted_20 = {
  class: "resetTwoFa"
};
var UserEditFormvue_type_template_id_851b548a_hoisted_21 = {
  class: "twofa-confirm-modal modal",
  ref: "twofaConfirmModal"
};
var UserEditFormvue_type_template_id_851b548a_hoisted_22 = {
  class: "modal-content"
};
var UserEditFormvue_type_template_id_851b548a_hoisted_23 = {
  class: "modal-footer"
};
var UserEditFormvue_type_template_id_851b548a_hoisted_24 = {
  class: "change-password-modal modal",
  ref: "changePasswordModal"
};
var UserEditFormvue_type_template_id_851b548a_hoisted_25 = {
  class: "modal-content"
};
var UserEditFormvue_type_template_id_851b548a_hoisted_26 = ["innerHTML"];
var UserEditFormvue_type_template_id_851b548a_hoisted_27 = {
  class: "modal-footer"
};
function UserEditFormvue_type_template_id_851b548a_render(_ctx, _cache, $props, $setup, $data, $options) {
  var _component_Field = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Field");

  var _component_SaveButton = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("SaveButton");

  var _component_UserPermissionsEdit = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("UserPermissionsEdit");

  var _component_ContentBlock = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("ContentBlock");

  var _directive_form = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveDirective"])("form");

  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_ContentBlock, {
    class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["userEditForm", {
      loading: _ctx.isSavingUserInfo
    }]),
    "content-title": "".concat(_ctx.formTitle, " ").concat(!_ctx.isAdd ? "'".concat(_ctx.theUser.login, "'") : '')
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(function () {
      return [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", UserEditFormvue_type_template_id_851b548a_hoisted_1, [!_ctx.isAdd ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", UserEditFormvue_type_template_id_851b548a_hoisted_2, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("ul", UserEditFormvue_type_template_id_851b548a_hoisted_3, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("li", {
        class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])([{
          active: _ctx.activeTab === 'basic'
        }, "menuBasicInfo"])
      }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
        href: "",
        onClick: _cache[0] || (_cache[0] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
          return _ctx.activeTab = 'basic';
        }, ["prevent"]))
      }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_BasicInformation')), 1)], 2), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("li", {
        class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])([{
          active: _ctx.activeTab === 'permissions'
        }, "menuPermissions"])
      }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
        href: "",
        onClick: _cache[1] || (_cache[1] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
          return _ctx.activeTab = 'permissions';
        }, ["prevent"])),
        style: {
          "margin-right": "3.5px"
        }
      }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_Permissions')), 1), !_ctx.userHasAccess && !_ctx.theUser.superuser_access ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("span", UserEditFormvue_type_template_id_851b548a_hoisted_4)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)], 2), _ctx.currentUserRole === 'superuser' ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("li", {
        key: 0,
        class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])([{
          active: _ctx.activeTab === 'superuser'
        }, "menuSuperuser"])
      }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
        href: "",
        onClick: _cache[2] || (_cache[2] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
          return _ctx.activeTab = 'superuser';
        }, ["prevent"]))
      }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_SuperUserAccess')), 1)], 2)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.currentUserRole === 'superuser' && _ctx.theUser.uses_2fa && !_ctx.isAdd ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("li", {
        key: 1,
        class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])([{
          active: _ctx.activeTab === '2fa'
        }, "menuUserTwoFa"])
      }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
        href: "",
        onClick: _cache[3] || (_cache[3] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
          return _ctx.activeTab = '2fa';
        }, ["prevent"]))
      }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_TwoFactorAuthentication')), 1)], 2)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]), UserEditFormvue_type_template_id_851b548a_hoisted_5, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", {
        class: "entityCancel",
        onClick: _cache[4] || (_cache[4] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
          return _ctx.onDoneEditing();
        }, ["prevent"]))
      }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", UserEditFormvue_type_template_id_851b548a_hoisted_6, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('Mobile_NavigationBack')), 1)])])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", UserEditFormvue_type_template_id_851b548a_hoisted_7, [_ctx.activeTab === 'basic' ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", UserEditFormvue_type_template_id_851b548a_hoisted_8, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
        modelValue: _ctx.theUser.login,
        "onUpdate:modelValue": _cache[5] || (_cache[5] = function ($event) {
          return _ctx.theUser.login = $event;
        }),
        disabled: _ctx.isSavingUserInfo || !_ctx.isAdd || _ctx.isShowingPasswordConfirm,
        uicontrol: "text",
        name: "user_login",
        maxlength: 100,
        title: _ctx.translate('General_Username')
      }, null, 8, ["modelValue", "disabled", "title"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
        "model-value": _ctx.theUser.password,
        disabled: _ctx.isSavingUserInfo || _ctx.currentUserRole !== 'superuser' && !_ctx.isAdd || _ctx.isShowingPasswordConfirm,
        "onUpdate:modelValue": _cache[6] || (_cache[6] = function ($event) {
          _ctx.theUser.password = $event;
          _ctx.isPasswordModified = true;
        }),
        uicontrol: "password",
        name: "user_password",
        title: _ctx.translate('General_Password')
      }, null, 8, ["model-value", "disabled", "title"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [_ctx.currentUserRole === 'superuser' || _ctx.isAdd ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_Field, {
        key: 0,
        modelValue: _ctx.theUser.email,
        "onUpdate:modelValue": _cache[7] || (_cache[7] = function ($event) {
          return _ctx.theUser.email = $event;
        }),
        disabled: _ctx.isSavingUserInfo || _ctx.currentUserRole !== 'superuser' && !_ctx.isAdd || _ctx.isShowingPasswordConfirm,
        uicontrol: "text",
        name: "user_email",
        maxlength: 100,
        title: _ctx.translate('UsersManager_Email')
      }, null, 8, ["modelValue", "disabled", "title"])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [_ctx.isAdd ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_Field, {
        key: 0,
        modelValue: _ctx.firstSiteAccess,
        "onUpdate:modelValue": _cache[8] || (_cache[8] = function ($event) {
          return _ctx.firstSiteAccess = $event;
        }),
        disabled: _ctx.isSavingUserInfo,
        uicontrol: "site",
        name: "user_site",
        "ui-control-attributes": {
          onlySitesWithAdminAccess: true
        },
        title: _ctx.translate('UsersManager_FirstWebsitePermission'),
        "inline-help": _ctx.translate('UsersManager_FirstSiteInlineHelp')
      }, null, 8, ["modelValue", "disabled", "title", "inline-help"])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [_ctx.currentUserRole === 'superuser' || _ctx.isAdd ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_SaveButton, {
        key: 0,
        value: _ctx.saveButtonLabel,
        disabled: _ctx.isAdd && (!_ctx.firstSiteAccess || !_ctx.firstSiteAccess.id),
        saving: _ctx.isSavingUserInfo,
        onConfirm: _cache[9] || (_cache[9] = function ($event) {
          return _ctx.saveUserInfo();
        })
      }, null, 8, ["value", "disabled", "saving"])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]), _ctx.isAdd ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", UserEditFormvue_type_template_id_851b548a_hoisted_9, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
        href: "",
        class: "entityCancelLink",
        onClick: _cache[10] || (_cache[10] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
          return _ctx.onDoneEditing();
        }, ["prevent"]))
      }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Cancel')), 1)])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), !_ctx.isAdd ? Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])((Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", UserEditFormvue_type_template_id_851b548a_hoisted_10, [!_ctx.theUser.superuser_access ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", UserEditFormvue_type_template_id_851b548a_hoisted_11, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_UserPermissionsEdit, {
        "user-login": _ctx.theUser.login,
        onUserHasAccessDetected: _cache[11] || (_cache[11] = function ($event) {
          return _ctx.userHasAccess = $event.hasAccess;
        }),
        onAccessChanged: _cache[12] || (_cache[12] = function ($event) {
          return _ctx.isUserModified = true;
        }),
        "access-levels": _ctx.accessLevels,
        "filter-access-levels": _ctx.filterAccessLevels
      }, null, 8, ["user-login", "access-levels", "filter-access-levels"])])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.theUser.superuser_access ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", UserEditFormvue_type_template_id_851b548a_hoisted_12, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_SuperUsersPermissionsNotice')), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)], 512)), [[external_commonjs_vue_commonjs2_vue_root_Vue_["vShow"], _ctx.activeTab === 'permissions']]) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.activeTab === 'superuser' && _ctx.currentUserRole === 'superuser' && !_ctx.isAdd ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", UserEditFormvue_type_template_id_851b548a_hoisted_13, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("p", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_SuperUserIntro1')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("p", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("strong", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_SuperUserIntro2')), 1)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
        modelValue: _ctx.superUserAccessChecked,
        "onUpdate:modelValue": _cache[13] || (_cache[13] = function ($event) {
          return _ctx.superUserAccessChecked = $event;
        }),
        onClick: _cache[14] || (_cache[14] = function ($event) {
          return _ctx.confirmSuperUserChange();
        }),
        disabled: _ctx.isSavingUserInfo,
        uicontrol: "checkbox",
        name: "superuser_access",
        title: _ctx.translate('UsersManager_HasSuperUserAccess')
      }, null, 8, ["modelValue", "disabled", "title"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", UserEditFormvue_type_template_id_851b548a_hoisted_14, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", UserEditFormvue_type_template_id_851b548a_hoisted_15, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h2", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_AreYouSure')), 1), _ctx.theUser.superuser_access ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("p", UserEditFormvue_type_template_id_851b548a_hoisted_16, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_RemoveSuperuserAccessConfirm')), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), !_ctx.theUser.superuser_access ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("p", UserEditFormvue_type_template_id_851b548a_hoisted_17, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_AddSuperuserAccessConfirm')), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
        modelValue: _ctx.passwordConfirmationForSuperUser,
        "onUpdate:modelValue": _cache[15] || (_cache[15] = function ($event) {
          return _ctx.passwordConfirmationForSuperUser = $event;
        }),
        uicontrol: "password",
        name: "currentUserPasswordForSuperUser",
        autocomplete: false,
        "full-width": true,
        title: _ctx.translate('UsersManager_YourCurrentPassword')
      }, null, 8, ["modelValue", "title"])])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", UserEditFormvue_type_template_id_851b548a_hoisted_18, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
        href: "",
        class: "modal-action modal-close btn",
        onClick: _cache[16] || (_cache[16] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
          return _ctx.toggleSuperuserAccess();
        }, ["prevent"])),
        style: {
          "margin-right": "3.5px"
        }
      }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Yes')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
        href: "",
        class: "modal-action modal-close modal-no",
        onClick: _cache[17] || (_cache[17] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
          _ctx.setSuperUserAccessChecked();

          _ctx.passwordConfirmationForSuperUser = '';
        }, ["prevent"]))
      }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_No')), 1)])], 512)])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.currentUserRole === 'superuser' && !_ctx.isAdd ? Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])((Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", UserEditFormvue_type_template_id_851b548a_hoisted_19, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("p", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_ResetTwoFactorAuthenticationInfo')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", UserEditFormvue_type_template_id_851b548a_hoisted_20, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_SaveButton, {
        saving: _ctx.isResetting2FA,
        onConfirm: _cache[18] || (_cache[18] = function ($event) {
          return _ctx.confirmReset2FA();
        }),
        value: _ctx.translate('UsersManager_ResetTwoFactorAuthentication')
      }, null, 8, ["saving", "value"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", UserEditFormvue_type_template_id_851b548a_hoisted_21, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", UserEditFormvue_type_template_id_851b548a_hoisted_22, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h2", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_AreYouSure')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("p", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_ConfirmWithPassword')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
        modelValue: _ctx.passwordConfirmation,
        "onUpdate:modelValue": _cache[19] || (_cache[19] = function ($event) {
          return _ctx.passwordConfirmation = $event;
        }),
        uicontrol: "password",
        name: "currentUserPasswordTwoFa",
        autocomplete: false,
        "full-width": true,
        title: _ctx.translate('UsersManager_YourCurrentPassword')
      }, null, 8, ["modelValue", "title"])])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", UserEditFormvue_type_template_id_851b548a_hoisted_23, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
        href: "",
        class: "modal-action modal-close btn",
        onClick: _cache[20] || (_cache[20] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
          return _ctx.reset2FA();
        }, ["prevent"])),
        style: {
          "margin-right": "3.5px"
        }
      }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Yes')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
        href: "",
        class: "modal-action modal-close modal-no",
        onClick: _cache[21] || (_cache[21] = function ($event) {
          $event.preventDefault();
          _ctx.passwordConfirmation = '';
        })
      }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_No')), 1)])], 512)], 512)), [[external_commonjs_vue_commonjs2_vue_root_Vue_["vShow"], _ctx.activeTab === '2fa']]) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)])], 512), [[_directive_form]]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", UserEditFormvue_type_template_id_851b548a_hoisted_24, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", UserEditFormvue_type_template_id_851b548a_hoisted_25, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h2", {
        innerHTML: _ctx.$sanitize(_ctx.changePasswordTitle)
      }, null, 8, UserEditFormvue_type_template_id_851b548a_hoisted_26), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("p", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_ConfirmWithPassword')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
        modelValue: _ctx.passwordConfirmation,
        "onUpdate:modelValue": _cache[22] || (_cache[22] = function ($event) {
          return _ctx.passwordConfirmation = $event;
        }),
        uicontrol: "password",
        name: "currentUserPasswordChangePwd",
        autocomplete: false,
        "full-width": true,
        title: _ctx.translate('UsersManager_YourCurrentPassword')
      }, null, 8, ["modelValue", "title"])])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", UserEditFormvue_type_template_id_851b548a_hoisted_27, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
        href: "",
        class: "modal-action modal-close btn",
        onClick: _cache[23] || (_cache[23] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
          return _ctx.updateUser();
        }, ["prevent"]))
      }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Yes')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
        href: "",
        class: "modal-action modal-close modal-no",
        onClick: _cache[24] || (_cache[24] = function ($event) {
          $event.preventDefault();
          _ctx.passwordConfirmation = '';
        })
      }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_No')), 1)])], 512)];
    }),
    _: 1
  }, 8, ["class", "content-title"]);
}
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/UserEditForm/UserEditForm.vue?vue&type=template&id=851b548a

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--14-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--0-1!./plugins/UsersManager/vue/src/UserEditForm/UserEditForm.vue?vue&type=script&lang=ts




var DEFAULT_USER = {
  login: '',
  superuser_access: false,
  uses_2fa: false,
  password: '',
  email: ''
};
var UserEditFormvue_type_script_lang_ts_window = window,
    UserEditFormvue_type_script_lang_ts_$ = UserEditFormvue_type_script_lang_ts_window.$;
/* harmony default export */ var UserEditFormvue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  props: {
    user: Object,
    currentUserRole: {
      type: String,
      required: true
    },
    accessLevels: {
      type: Array,
      required: true
    },
    filterAccessLevels: {
      type: Array,
      required: true
    },
    initialSiteId: {
      type: [String, Number],
      required: true
    },
    initialSiteName: {
      type: String,
      required: true
    }
  },
  components: {
    ContentBlock: external_CoreHome_["ContentBlock"],
    Field: external_CorePluginsAdmin_["Field"],
    SaveButton: external_CorePluginsAdmin_["SaveButton"],
    UserPermissionsEdit: UserPermissionsEdit
  },
  directives: {
    Form: external_CorePluginsAdmin_["Form"]
  },
  data: function data() {
    return {
      theUser: this.user || Object.assign({}, DEFAULT_USER),
      activeTab: 'basic',
      permissionsForIdSite: 1,
      isSavingUserInfo: false,
      userHasAccess: true,
      firstSiteAccess: {
        id: this.initialSiteId,
        name: this.initialSiteName
      },
      isUserModified: false,
      passwordConfirmation: '',
      isPasswordModified: false,
      superUserAccessChecked: null,
      passwordConfirmationForSuperUser: '',
      isResetting2FA: false,
      isShowingPasswordConfirm: false
    };
  },
  emits: ['done', 'updated'],
  watch: {
    user: function user(newVal) {
      this.onUserChange(newVal);
    }
  },
  created: function created() {
    this.onUserChange(this.user);
  },
  methods: {
    onUserChange: function onUserChange(newVal) {
      this.theUser = newVal || Object.assign({}, DEFAULT_USER);

      if (!this.theUser.password) {
        this.resetPasswordVar();
      }

      this.setSuperUserAccessChecked();
    },
    confirmSuperUserChange: function confirmSuperUserChange() {
      UserEditFormvue_type_script_lang_ts_$(this.$refs.superUserConfirmModal).modal({
        dismissible: false
      }).modal('open');
    },
    confirmReset2FA: function confirmReset2FA() {
      UserEditFormvue_type_script_lang_ts_$(this.$refs.twofaConfirmModal).modal({
        dismissible: false
      }).modal('open');
    },
    toggleSuperuserAccess: function toggleSuperuserAccess() {
      var _this = this;

      this.isSavingUserInfo = true;
      external_CoreHome_["AjaxHelper"].post({
        method: 'UsersManager.setSuperUserAccess'
      }, {
        userLogin: this.theUser.login,
        hasSuperUserAccess: this.theUser.superuser_access ? '0' : '1',
        passwordConfirmation: this.passwordConfirmationForSuperUser
      }).then(function () {
        _this.theUser.superuser_access = !_this.theUser.superuser_access;
      }).catch(function () {// ignore error (still displayed to user)
      }).then(function () {
        _this.isSavingUserInfo = false;
        _this.isUserModified = true;
        _this.passwordConfirmationForSuperUser = '';

        _this.setSuperUserAccessChecked();
      });
    },
    saveUserInfo: function saveUserInfo() {
      var _this2 = this;

      return Promise.resolve().then(function () {
        if (_this2.isAdd) {
          return _this2.createUser();
        }

        return _this2.confirmUserChange();
      }).then(function () {
        _this2.$emit('updated', {
          user: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["readonly"])(_this2.theUser)
        });
      });
    },
    createUser: function createUser() {
      var _this3 = this;

      this.isSavingUserInfo = true;
      return external_CoreHome_["AjaxHelper"].post({
        method: 'UsersManager.addUser'
      }, {
        userLogin: this.theUser.login,
        password: this.theUser.password,
        email: this.theUser.email,
        initialIdSite: this.firstSiteAccess ? this.firstSiteAccess.id : undefined
      }).catch(function (e) {
        _this3.isSavingUserInfo = false;
        throw e;
      }).then(function () {
        _this3.firstSiteAccess = null;
        _this3.isSavingUserInfo = false;
        _this3.isUserModified = true;

        _this3.resetPasswordVar();

        _this3.showUserSavedNotification();
      });
    },
    resetPasswordVar: function resetPasswordVar() {
      if (!this.isAdd) {
        // make sure password is not stored in the client after update/save
        this.theUser.password = 'XXXXXXXX';
      }
    },
    confirmUserChange: function confirmUserChange() {
      var _this4 = this;

      this.passwordConfirmation = '';
      this.isShowingPasswordConfirm = true;

      var onEnter = function onEnter(event) {
        var keycode = event.keyCode ? event.keyCode : event.which;

        if (keycode === 13) {
          UserEditFormvue_type_script_lang_ts_$(_this4.$refs.changePasswordModal).modal('close');

          _this4.updateUser();
        }
      };

      UserEditFormvue_type_script_lang_ts_$(this.$refs.changePasswordModal).modal({
        dismissible: false,
        onOpenEnd: function onOpenEnd() {
          _this4.isShowingPasswordConfirm = false;
          UserEditFormvue_type_script_lang_ts_$('.modal.open #currentUserPasswordChangePwd').focus().off('keypress').keypress(onEnter);
        }
      }).modal('open');
    },
    showUserSavedNotification: function showUserSavedNotification() {
      external_CoreHome_["NotificationsStore"].show({
        message: Object(external_CoreHome_["translate"])('General_YourChangesHaveBeenSaved'),
        context: 'success',
        type: 'toast'
      });
    },
    reset2FA: function reset2FA() {
      var _this5 = this;

      this.isResetting2FA = true;
      return external_CoreHome_["AjaxHelper"].post({
        method: 'TwoFactorAuth.resetTwoFactorAuth',
        userLogin: this.theUser.login,
        passwordConfirmation: this.passwordConfirmation
      }).catch(function (e) {
        _this5.isResetting2FA = false;
        throw e;
      }).then(function () {
        _this5.isResetting2FA = false;
        _this5.theUser.uses_2fa = false;
        _this5.activeTab = 'basic';

        _this5.showUserSavedNotification();
      }).finally(function () {
        _this5.passwordConfirmation = '';
      });
    },
    updateUser: function updateUser() {
      var _this6 = this;

      this.isSavingUserInfo = true;
      return external_CoreHome_["AjaxHelper"].post({
        method: 'UsersManager.updateUser'
      }, {
        userLogin: this.theUser.login,
        password: this.isPasswordModified && this.theUser.password ? this.theUser.password : undefined,
        passwordConfirmation: this.passwordConfirmation ? this.passwordConfirmation : undefined,
        email: this.theUser.email
      }).then(function () {
        _this6.isSavingUserInfo = false;
        _this6.passwordConfirmation = '';
        _this6.isUserModified = true;
        _this6.isPasswordModified = false;

        _this6.resetPasswordVar();

        _this6.showUserSavedNotification();
      }).catch(function () {
        _this6.isSavingUserInfo = false;
        _this6.passwordConfirmation = '';
      });
    },
    setSuperUserAccessChecked: function setSuperUserAccessChecked() {
      this.superUserAccessChecked = !!this.theUser.superuser_access;
    },
    onDoneEditing: function onDoneEditing() {
      this.$emit('done', {
        isUserModified: this.isUserModified
      });
    }
  },
  computed: {
    formTitle: function formTitle() {
      return this.isAdd ? Object(external_CoreHome_["translate"])('UsersManager_AddNewUser') : Object(external_CoreHome_["translate"])('UsersManager_EditUser');
    },
    saveButtonLabel: function saveButtonLabel() {
      return this.isAdd ? Object(external_CoreHome_["translate"])('UsersManager_CreateUser') : Object(external_CoreHome_["translate"])('UsersManager_SaveBasicInfo');
    },
    isAdd: function isAdd() {
      return !this.user; // purposefully checking input property not theUser state
    },
    changePasswordTitle: function changePasswordTitle() {
      return Object(external_CoreHome_["translate"])('UsersManager_AreYouSureChangeDetails', "<strong>".concat(this.theUser.login, "</strong>"));
    }
  }
}));
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/UserEditForm/UserEditForm.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/UserEditForm/UserEditForm.vue



UserEditFormvue_type_script_lang_ts.render = UserEditFormvue_type_template_id_851b548a_render

/* harmony default export */ var UserEditForm = (UserEditFormvue_type_script_lang_ts);
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/UserEditForm/UserEditForm.adapter.ts
/*!
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */


/* harmony default export */ var UserEditForm_adapter = (Object(external_CoreHome_["createAngularJsAdapter"])({
  component: UserEditForm,
  scope: {
    user: {
      angularJsBind: '<'
    },
    onDoneEditing: {
      angularJsBind: '&',
      vue: 'done'
    },
    currentUserRole: {
      angularJsBind: '<'
    },
    accessLevels: {
      angularJsBind: '<'
    },
    filterAccessLevels: {
      angularJsBind: '<'
    },
    initialSiteId: {
      angularJsBind: '<'
    },
    initialSiteName: {
      angularJsBind: '<'
    },
    onUpdated: {
      angularJsBind: '&',
      vue: 'updated'
    }
  },
  directiveName: 'piwikUserEditForm',
  restrict: 'E'
}));
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--12-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--0-1!./plugins/UsersManager/vue/src/PagedUsersList/PagedUsersList.vue?vue&type=template&id=6767e472

var PagedUsersListvue_type_template_id_6767e472_hoisted_1 = {
  class: "userListFilters row"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_2 = {
  class: "col s12 m12 l6"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_3 = {
  class: "input-field col s12 m4 l4"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_4 = {
  id: "user-list-bulk-actions",
  class: "dropdown-content"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_5 = {
  class: "dropdown-trigger",
  "data-target": "bulk-set-access"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_6 = {
  id: "bulk-set-access",
  class: "dropdown-content"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_7 = ["onClick"];
var PagedUsersListvue_type_template_id_6767e472_hoisted_8 = {
  key: 0
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_9 = {
  class: "input-field col s12 m4 l4"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_10 = {
  class: "permissions-for-selector"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_11 = {
  class: "input-field col s12 m4 l4"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_12 = {
  key: 0,
  class: "input-field col s12 m12 l6 users-list-pagination-container"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_13 = {
  class: "usersListPagination"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_14 = {
  class: "pointer"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_15 = {
  class: "counter"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_16 = {
  class: "pointer"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_17 = {
  key: 0,
  class: "roles-help-notification"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_18 = ["innerHTML"];
var PagedUsersListvue_type_template_id_6767e472_hoisted_19 = {
  class: "select-cell"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_20 = {
  class: "checkbox-container"
};

var PagedUsersListvue_type_template_id_6767e472_hoisted_21 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", null, null, -1);

var PagedUsersListvue_type_template_id_6767e472_hoisted_22 = {
  class: "first"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_23 = {
  class: "role_header"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_24 = {
  style: {
    "margin-right": "3.5px"
  }
};

var PagedUsersListvue_type_template_id_6767e472_hoisted_25 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "icon-help"
}, null, -1);

var PagedUsersListvue_type_template_id_6767e472_hoisted_26 = [PagedUsersListvue_type_template_id_6767e472_hoisted_25];
var PagedUsersListvue_type_template_id_6767e472_hoisted_27 = {
  key: 0
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_28 = ["title"];
var PagedUsersListvue_type_template_id_6767e472_hoisted_29 = {
  key: 2
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_30 = {
  class: "actions-cell-header"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_31 = {
  key: 0,
  class: "select-all-row"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_32 = {
  colspan: "8"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_33 = {
  key: 0
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_34 = ["innerHTML"];
var PagedUsersListvue_type_template_id_6767e472_hoisted_35 = ["innerHTML"];
var PagedUsersListvue_type_template_id_6767e472_hoisted_36 = {
  key: 1
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_37 = ["innerHTML"];
var PagedUsersListvue_type_template_id_6767e472_hoisted_38 = ["innerHTML"];
var PagedUsersListvue_type_template_id_6767e472_hoisted_39 = ["id"];
var PagedUsersListvue_type_template_id_6767e472_hoisted_40 = {
  class: "select-cell"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_41 = {
  class: "checkbox-container"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_42 = ["id", "onUpdate:modelValue"];

var PagedUsersListvue_type_template_id_6767e472_hoisted_43 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", null, null, -1);

var PagedUsersListvue_type_template_id_6767e472_hoisted_44 = {
  id: "userLogin"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_45 = {
  class: "access-cell"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_46 = {
  key: 0,
  id: "email"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_47 = {
  key: 1,
  id: "twofa"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_48 = {
  key: 0,
  class: "icon-ok"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_49 = {
  key: 1,
  class: "icon-close"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_50 = {
  key: 2,
  id: "last_seen"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_51 = {
  class: "center actions-cell"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_52 = ["onClick"];

var PagedUsersListvue_type_template_id_6767e472_hoisted_53 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "icon-edit"
}, null, -1);

var PagedUsersListvue_type_template_id_6767e472_hoisted_54 = [PagedUsersListvue_type_template_id_6767e472_hoisted_53];
var PagedUsersListvue_type_template_id_6767e472_hoisted_55 = ["onClick"];

var PagedUsersListvue_type_template_id_6767e472_hoisted_56 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "icon-delete"
}, null, -1);

var PagedUsersListvue_type_template_id_6767e472_hoisted_57 = [PagedUsersListvue_type_template_id_6767e472_hoisted_56];
var PagedUsersListvue_type_template_id_6767e472_hoisted_58 = {
  class: "delete-user-confirm-modal modal",
  ref: "deleteUserConfirmModal"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_59 = {
  class: "modal-content"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_60 = ["innerHTML"];
var PagedUsersListvue_type_template_id_6767e472_hoisted_61 = ["innerHTML"];
var PagedUsersListvue_type_template_id_6767e472_hoisted_62 = {
  class: "modal-footer"
};
var PagedUsersListvue_type_template_id_6767e472_hoisted_63 = {
  class: "change-user-role-confirm-modal modal",
  ref: "changeUserRoleConfirmModal"
};
var _hoisted_64 = {
  class: "modal-content"
};
var _hoisted_65 = ["innerHTML"];
var _hoisted_66 = {
  key: 1
};
var _hoisted_67 = ["innerHTML"];
var _hoisted_68 = ["innerHTML"];
var _hoisted_69 = {
  class: "modal-footer"
};
function PagedUsersListvue_type_template_id_6767e472_render(_ctx, _cache, $props, $setup, $data, $options) {
  var _component_Field = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Field");

  var _component_ActivityIndicator = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("ActivityIndicator");

  var _component_Notification = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Notification");

  var _component_ContentBlock = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("ContentBlock");

  var _directive_dropdown_menu = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveDirective"])("dropdown-menu");

  var _directive_content_table = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveDirective"])("content-table");

  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", {
    class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["pagedUsersList", {
      loading: _ctx.isLoadingUsers
    }])
  }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", PagedUsersListvue_type_template_id_6767e472_hoisted_1, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", PagedUsersListvue_type_template_id_6767e472_hoisted_2, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", PagedUsersListvue_type_template_id_6767e472_hoisted_3, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
    class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["dropdown-trigger btn bulk-actions", {
      disabled: _ctx.isBulkActionsDisabled
    }]),
    href: "",
    "data-target": "user-list-bulk-actions"
  }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_BulkActions')), 1)], 2), [[_directive_dropdown_menu]]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("ul", PagedUsersListvue_type_template_id_6767e472_hoisted_4, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("li", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", PagedUsersListvue_type_template_id_6767e472_hoisted_5, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_SetPermission')), 1)], 512), [[_directive_dropdown_menu]]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("ul", PagedUsersListvue_type_template_id_6767e472_hoisted_6, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.bulkActionAccessLevels, function (access) {
    return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("li", {
      key: access.key
    }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
      href: "",
      onClick: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
        _ctx.userToChange = null;
        _ctx.roleToChangeTo = access.key;

        _ctx.showAccessChangeConfirm();
      }, ["prevent"])
    }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(access.value), 9, PagedUsersListvue_type_template_id_6767e472_hoisted_7)]);
  }), 128))])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("li", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
    href: "",
    onClick: _cache[0] || (_cache[0] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
      _ctx.userToChange = null;
      _ctx.roleToChangeTo = 'noaccess';

      _ctx.showAccessChangeConfirm();
    }, ["prevent"]))
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_RemovePermissions')), 1)]), _ctx.currentUserRole === 'superuser' ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("li", PagedUsersListvue_type_template_id_6767e472_hoisted_8, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
    href: "",
    onClick: _cache[1] || (_cache[1] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
      return _ctx.showDeleteConfirm();
    }, ["prevent"]))
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_DeleteUsers')), 1)])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", PagedUsersListvue_type_template_id_6767e472_hoisted_9, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", PagedUsersListvue_type_template_id_6767e472_hoisted_10, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
    "model-value": _ctx.userTextFilter,
    "onUpdate:modelValue": _cache[2] || (_cache[2] = function ($event) {
      return _ctx.onUserTextFilterChange($event);
    }),
    name: "user-text-filter",
    uicontrol: "text",
    "full-width": true,
    placeholder: _ctx.translate('UsersManager_UserSearch')
  }, null, 8, ["model-value", "placeholder"])])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", PagedUsersListvue_type_template_id_6767e472_hoisted_11, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
    "model-value": _ctx.accessLevelFilter,
    "onUpdate:modelValue": _cache[3] || (_cache[3] = function ($event) {
      _ctx.accessLevelFilter = $event;

      _ctx.changeSearch({
        filter_access: _ctx.accessLevelFilter,
        offset: 0
      });
    }),
    name: "access-level-filter",
    uicontrol: "select",
    options: _ctx.filterAccessLevels,
    "full-width": true,
    placeholder: _ctx.translate('UsersManager_FilterByAccess')
  }, null, 8, ["model-value", "options", "placeholder"])])])]), _ctx.totalEntries > _ctx.searchParams.limit ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", PagedUsersListvue_type_template_id_6767e472_hoisted_12, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", PagedUsersListvue_type_template_id_6767e472_hoisted_13, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
    class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["btn prev", {
      disabled: _ctx.searchParams.offset <= 0
    }]),
    onClick: _cache[4] || (_cache[4] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
      return _ctx.gotoPreviousPage();
    }, ["prevent"]))
  }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", PagedUsersListvue_type_template_id_6767e472_hoisted_14, "« " + Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Previous')), 1)], 2), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", PagedUsersListvue_type_template_id_6767e472_hoisted_15, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
    class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])({
      visibility: _ctx.isLoadingUsers ? 'hidden' : 'visible'
    })
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Pagination', _ctx.paginationLowerBound, _ctx.paginationUpperBound, _ctx.totalEntries)), 3), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_ActivityIndicator, {
    loading: _ctx.isLoadingUsers
  }, null, 8, ["loading"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
    class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["btn next", {
      disabled: _ctx.searchParams.offset + _ctx.searchParams.limit >= _ctx.totalEntries
    }]),
    onClick: _cache[5] || (_cache[5] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
      return _ctx.gotoNextPage();
    }, ["prevent"]))
  }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", PagedUsersListvue_type_template_id_6767e472_hoisted_16, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Next')) + " »", 1)], 2)])])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]), _ctx.isRoleHelpToggled ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", PagedUsersListvue_type_template_id_6767e472_hoisted_17, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Notification, {
    context: "info",
    type: "persistent",
    noclear: true
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(function () {
      return [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
        innerHTML: _ctx.$sanitize(_ctx.rolesHelpText)
      }, null, 8, PagedUsersListvue_type_template_id_6767e472_hoisted_18)];
    }),
    _: 1
  })])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_ContentBlock, null, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(function () {
      return [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("table", {
        id: "manageUsersTable",
        class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])({
          loading: _ctx.isLoadingUsers
        })
      }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("thead", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("tr", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", PagedUsersListvue_type_template_id_6767e472_hoisted_19, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", PagedUsersListvue_type_template_id_6767e472_hoisted_20, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("label", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("input", {
        type: "checkbox",
        id: "paged_users_select_all",
        checked: "checked",
        "onUpdate:modelValue": _cache[6] || (_cache[6] = function ($event) {
          return _ctx.isAllCheckboxSelected = $event;
        }),
        onChange: _cache[7] || (_cache[7] = function ($event) {
          return _ctx.onAllCheckboxChange();
        })
      }, null, 544), [[external_commonjs_vue_commonjs2_vue_root_Vue_["vModelCheckbox"], _ctx.isAllCheckboxSelected]]), PagedUsersListvue_type_template_id_6767e472_hoisted_21])])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", PagedUsersListvue_type_template_id_6767e472_hoisted_22, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_Username')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", PagedUsersListvue_type_template_id_6767e472_hoisted_23, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", PagedUsersListvue_type_template_id_6767e472_hoisted_24, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_RoleFor')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
        href: "",
        class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["helpIcon", {
          sticky: _ctx.isRoleHelpToggled
        }]),
        onClick: _cache[8] || (_cache[8] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
          return _ctx.isRoleHelpToggled = !_ctx.isRoleHelpToggled;
        }, ["prevent"]))
      }, PagedUsersListvue_type_template_id_6767e472_hoisted_26, 2), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
        class: "permissions-for-selector",
        "model-value": _ctx.permissionsForSite,
        "onUpdate:modelValue": _cache[9] || (_cache[9] = function ($event) {
          _ctx.onPermissionsForUpdate($event);
        }),
        uicontrol: "site",
        "ui-control-attributes": {
          onlySitesWithAdminAccess: _ctx.currentUserRole !== 'superuser'
        }
      }, null, 8, ["model-value", "ui-control-attributes"])])]), _ctx.currentUserRole === 'superuser' ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("th", PagedUsersListvue_type_template_id_6767e472_hoisted_27, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_Email')), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.currentUserRole === 'superuser' ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("th", {
        key: 1,
        title: _ctx.translate('UsersManager_UsesTwoFactorAuthentication')
      }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_2FA')), 9, PagedUsersListvue_type_template_id_6767e472_hoisted_28)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.currentUserRole === 'superuser' ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("th", PagedUsersListvue_type_template_id_6767e472_hoisted_29, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_LastSeen')), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", PagedUsersListvue_type_template_id_6767e472_hoisted_30, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Actions')), 1)])])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("tbody", null, [_ctx.isAllCheckboxSelected && _ctx.users.length && _ctx.users.length < _ctx.totalEntries ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("tr", PagedUsersListvue_type_template_id_6767e472_hoisted_31, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", PagedUsersListvue_type_template_id_6767e472_hoisted_32, [!_ctx.areAllResultsSelected ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", PagedUsersListvue_type_template_id_6767e472_hoisted_33, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
        innerHTML: _ctx.$sanitize(_ctx.translate('UsersManager_TheDisplayedUsersAreSelected', "<strong>".concat(_ctx.users.length, "</strong>"))),
        style: {
          "margin-right": "3.5px"
        }
      }, null, 8, PagedUsersListvue_type_template_id_6767e472_hoisted_34), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
        class: "toggle-select-all-in-search",
        href: "#",
        onClick: _cache[10] || (_cache[10] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
          return _ctx.areAllResultsSelected = !_ctx.areAllResultsSelected;
        }, ["prevent"])),
        innerHTML: _ctx.$sanitize(_ctx.translate('UsersManager_ClickToSelectAll', "<strong>".concat(_ctx.totalEntries, "</strong>")))
      }, null, 8, PagedUsersListvue_type_template_id_6767e472_hoisted_35)])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.areAllResultsSelected ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", PagedUsersListvue_type_template_id_6767e472_hoisted_36, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
        innerHTML: _ctx.$sanitize(_ctx.translate('UsersManager_AllUsersAreSelected', "<strong>".concat(_ctx.totalEntries, "</strong>"))),
        style: {
          "margin-right": "3.5px"
        }
      }, null, 8, PagedUsersListvue_type_template_id_6767e472_hoisted_37), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
        class: "toggle-select-all-in-search",
        href: "#",
        onClick: _cache[11] || (_cache[11] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
          return _ctx.areAllResultsSelected = !_ctx.areAllResultsSelected;
        }, ["prevent"])),
        innerHTML: _ctx.$sanitize(_ctx.translate('UsersManager_ClickToSelectDisplayedUsers', "<strong>".concat(_ctx.users.length, "</strong>")))
      }, null, 8, PagedUsersListvue_type_template_id_6767e472_hoisted_38)])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)])])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.users, function (user, index) {
        return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("tr", {
          id: "row".concat(index),
          key: user.login
        }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", PagedUsersListvue_type_template_id_6767e472_hoisted_40, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", PagedUsersListvue_type_template_id_6767e472_hoisted_41, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("label", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("input", {
          type: "checkbox",
          id: "paged_users_select_row".concat(index),
          "onUpdate:modelValue": function onUpdateModelValue($event) {
            return _ctx.selectedRows[index] = $event;
          },
          onClick: _cache[12] || (_cache[12] = function ($event) {
            return _ctx.onRowSelected();
          })
        }, null, 8, PagedUsersListvue_type_template_id_6767e472_hoisted_42), [[external_commonjs_vue_commonjs2_vue_root_Vue_["vModelCheckbox"], _ctx.selectedRows[index]]]), PagedUsersListvue_type_template_id_6767e472_hoisted_43])])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", PagedUsersListvue_type_template_id_6767e472_hoisted_44, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(user.login), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", PagedUsersListvue_type_template_id_6767e472_hoisted_45, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
          "model-value": user.role,
          "onUpdate:modelValue": function onUpdateModelValue($event) {
            _ctx.userToChange = user;
            _ctx.roleToChangeTo = $event;

            _ctx.showAccessChangeConfirm();
          },
          disabled: user.role === 'superuser',
          uicontrol: "select",
          options: user.login !== 'anonymous' ? _ctx.accessLevels : _ctx.anonymousAccessLevels
        }, null, 8, ["model-value", "onUpdate:modelValue", "disabled", "options"])])]), _ctx.currentUserRole === 'superuser' ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("td", PagedUsersListvue_type_template_id_6767e472_hoisted_46, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(user.email), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.currentUserRole === 'superuser' ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("td", PagedUsersListvue_type_template_id_6767e472_hoisted_47, [user.uses_2fa ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("span", PagedUsersListvue_type_template_id_6767e472_hoisted_48)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), !user.uses_2fa ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("span", PagedUsersListvue_type_template_id_6767e472_hoisted_49)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.currentUserRole === 'superuser' ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("td", PagedUsersListvue_type_template_id_6767e472_hoisted_50, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(user.last_seen ? "".concat(user.last_seen, " ago") : '-'), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", PagedUsersListvue_type_template_id_6767e472_hoisted_51, [user.login !== 'anonymous' ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("button", {
          key: 0,
          class: "edituser table-action",
          title: "Edit",
          onClick: function onClick($event) {
            return _ctx.$emit('editUser', {
              user: user
            });
          }
        }, PagedUsersListvue_type_template_id_6767e472_hoisted_54, 8, PagedUsersListvue_type_template_id_6767e472_hoisted_52)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.currentUserRole === 'superuser' && user.login !== 'anonymous' ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("button", {
          key: 1,
          class: "deleteuser table-action",
          title: "Delete",
          onClick: function onClick($event) {
            _ctx.userToChange = user;

            _ctx.showDeleteConfirm();
          }
        }, PagedUsersListvue_type_template_id_6767e472_hoisted_57, 8, PagedUsersListvue_type_template_id_6767e472_hoisted_55)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)])], 8, PagedUsersListvue_type_template_id_6767e472_hoisted_39);
      }), 128))])], 2), [[_directive_content_table]])];
    }),
    _: 1
  }), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", PagedUsersListvue_type_template_id_6767e472_hoisted_58, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", PagedUsersListvue_type_template_id_6767e472_hoisted_59, [_ctx.userToChange ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("h3", {
    key: 0,
    innerHTML: _ctx.$sanitize(_ctx.translate('UsersManager_DeleteUserConfirmSingle', "<strong>".concat(_ctx.userToChange.login, "</strong>")))
  }, null, 8, PagedUsersListvue_type_template_id_6767e472_hoisted_60)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), !_ctx.userToChange ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("p", {
    key: 1,
    innerHTML: _ctx.$sanitize(_ctx.translate('UsersManager_DeleteUserConfirmMultiple', "<strong>".concat(_ctx.affectedUsersCount, "</strong>")))
  }, null, 8, PagedUsersListvue_type_template_id_6767e472_hoisted_61)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", PagedUsersListvue_type_template_id_6767e472_hoisted_62, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
    href: "",
    class: "modal-action modal-close btn",
    onClick: _cache[13] || (_cache[13] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
      return _ctx.deleteRequestedUsers();
    }, ["prevent"])),
    style: {
      "margin-right": "3.5px"
    }
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Yes')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
    href: "",
    class: "modal-action modal-close modal-no",
    onClick: _cache[14] || (_cache[14] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
      _ctx.userToChange = null;
      _ctx.roleToChangeTo = null;
    }, ["prevent"]))
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_No')), 1)])], 512), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", PagedUsersListvue_type_template_id_6767e472_hoisted_63, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_64, [_ctx.userToChange ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("h3", {
    key: 0,
    innerHTML: _ctx.$sanitize(_ctx.deleteUserPermConfirmSingleText)
  }, null, 8, _hoisted_65)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.userToChange && _ctx.userToChange.login === 'anonymous' && _ctx.roleToChangeTo === 'view' ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("h3", _hoisted_66, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("em", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Note')) + ": ", 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
    innerHTML: _ctx.$sanitize(_ctx.translate('UsersManager_AnonymousUserRoleChangeWarning', 'anonymous', _ctx.getRoleDisplay(_ctx.roleToChangeTo)))
  }, null, 8, _hoisted_67)])])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), !_ctx.userToChange ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("p", {
    key: 2,
    innerHTML: _ctx.$sanitize(_ctx.deleteUserPermConfirmMultipleText)
  }, null, 8, _hoisted_68)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_69, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
    href: "",
    class: "modal-action modal-close btn",
    onClick: _cache[15] || (_cache[15] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
      return _ctx.changeUserRole();
    }, ["prevent"])),
    style: {
      "margin-right": "3.5px"
    }
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Yes')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
    href: "",
    class: "modal-action modal-close modal-no",
    onClick: _cache[16] || (_cache[16] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
      _ctx.userToChange = null;
      _ctx.roleToChangeTo = null;
    }, ["prevent"]))
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_No')), 1)])], 512)], 2);
}
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/PagedUsersList/PagedUsersList.vue?vue&type=template&id=6767e472

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--14-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--0-1!./plugins/UsersManager/vue/src/PagedUsersList/PagedUsersList.vue?vue&type=script&lang=ts



var PagedUsersListvue_type_script_lang_ts_window = window,
    PagedUsersListvue_type_script_lang_ts_$ = PagedUsersListvue_type_script_lang_ts_window.$;
/* harmony default export */ var PagedUsersListvue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  props: {
    initialSiteId: {
      type: [String, Number],
      required: true
    },
    initialSiteName: {
      type: String,
      required: true
    },
    currentUserRole: String,
    isLoadingUsers: Boolean,
    accessLevels: {
      type: Array,
      required: true
    },
    filterAccessLevels: {
      type: Array,
      required: true
    },
    totalEntries: Number,
    users: {
      type: Array,
      required: true
    },
    searchParams: {
      type: Object,
      required: true
    }
  },
  components: {
    Field: external_CorePluginsAdmin_["Field"],
    ActivityIndicator: external_CoreHome_["ActivityIndicator"],
    Notification: external_CoreHome_["Notification"],
    ContentBlock: external_CoreHome_["ContentBlock"]
  },
  directives: {
    DropdownMenu: external_CoreHome_["DropdownMenu"],
    ContentTable: external_CoreHome_["ContentTable"]
  },
  data: function data() {
    return {
      areAllResultsSelected: false,
      selectedRows: {},
      isAllCheckboxSelected: false,
      isBulkActionsDisabled: true,
      userToChange: null,
      roleToChangeTo: null,
      accessLevelFilter: null,
      isRoleHelpToggled: false,
      userTextFilter: '',
      permissionsForSite: {
        id: this.initialSiteId,
        name: this.initialSiteName
      }
    };
  },
  emits: ['editUser', 'changeUserRole', 'deleteUser', 'searchChange'],
  created: function created() {
    this.onUserTextFilterChange = Object(external_CoreHome_["debounce"])(this.onUserTextFilterChange, 300);
  },
  watch: {
    users: function users() {
      this.clearSelection();
    }
  },
  methods: {
    onPermissionsForUpdate: function onPermissionsForUpdate(site) {
      this.permissionsForSite = site;
      this.changeSearch({
        idSite: this.permissionsForSite.id
      });
    },
    clearSelection: function clearSelection() {
      this.selectedRows = {};
      this.areAllResultsSelected = false;
      this.isBulkActionsDisabled = true;
      this.isAllCheckboxSelected = false;
      this.userToChange = null;
    },
    onAllCheckboxChange: function onAllCheckboxChange() {
      if (!this.isAllCheckboxSelected) {
        this.clearSelection();
      } else {
        for (var i = 0; i !== this.users.length; i += 1) {
          this.selectedRows[i] = true;
        }

        this.isBulkActionsDisabled = false;
      }
    },
    changeUserRole: function changeUserRole() {
      this.$emit('changeUserRole', {
        users: this.userOperationSubject,
        role: this.roleToChangeTo
      });
    },
    onRowSelected: function onRowSelected() {
      var _this = this;

      // (angularjs comment): use a timeout since the method is called after the model is updated
      setTimeout(function () {
        var selectedRowKeyCount = _this.selectedCount;
        _this.isBulkActionsDisabled = selectedRowKeyCount === 0;
        _this.isAllCheckboxSelected = selectedRowKeyCount === _this.users.length;
      });
    },
    deleteRequestedUsers: function deleteRequestedUsers() {
      this.$emit('deleteUser', {
        users: this.userOperationSubject
      });
    },
    showDeleteConfirm: function showDeleteConfirm() {
      PagedUsersListvue_type_script_lang_ts_$(this.$refs.deleteUserConfirmModal).modal({
        dismissible: false
      }).modal('open');
    },
    showAccessChangeConfirm: function showAccessChangeConfirm() {
      PagedUsersListvue_type_script_lang_ts_$(this.$refs.changeUserRoleConfirmModal).modal({
        dismissible: false
      }).modal('open');
    },
    getRoleDisplay: function getRoleDisplay(role) {
      var result = null;
      this.accessLevels.forEach(function (entry) {
        if (entry.key === role) {
          result = entry.value;
        }
      });
      return result;
    },
    changeSearch: function changeSearch(changes) {
      var params = Object.assign(Object.assign({}, this.searchParams), changes);
      this.$emit('searchChange', {
        params: params
      });
    },
    gotoPreviousPage: function gotoPreviousPage() {
      this.changeSearch({
        offset: Math.max(0, this.searchParams.offset - this.searchParams.limit)
      });
    },
    gotoNextPage: function gotoNextPage() {
      var newOffset = this.searchParams.offset + this.searchParams.limit;

      if (newOffset >= this.totalEntries) {
        return;
      }

      this.changeSearch({
        offset: newOffset
      });
    },
    onUserTextFilterChange: function onUserTextFilterChange(filter) {
      this.userTextFilter = filter;
      this.changeSearch({
        filter_search: filter,
        offset: 0
      });
    }
  },
  computed: {
    paginationLowerBound: function paginationLowerBound() {
      return this.searchParams.offset + 1;
    },
    paginationUpperBound: function paginationUpperBound() {
      if (this.totalEntries === null) {
        return '?';
      }

      var searchParams = this.searchParams;
      return Math.min(searchParams.offset + searchParams.limit, this.totalEntries);
    },
    userOperationSubject: function userOperationSubject() {
      if (this.userToChange) {
        return [this.userToChange];
      }

      if (this.areAllResultsSelected) {
        return 'all';
      }

      return this.selectedUsers;
    },
    selectedUsers: function selectedUsers() {
      var _this2 = this;

      var users = this.users;
      var result = [];
      Object.keys(this.selectedRows).forEach(function (index) {
        var indexN = parseInt(index, 10);

        if (_this2.selectedRows[index] && users[indexN] // sanity check
        ) {
          result.push(users[indexN]);
        }
      });
      return result;
    },
    rolesHelpText: function rolesHelpText() {
      var faq70 = 'https://matomo.org/faq/general/faq_70/';
      var faq69 = 'https://matomo.org/faq/general/faq_69/';
      return Object(external_CoreHome_["translate"])('UsersManager_RolesHelp', "<a href=\"".concat(faq70, "\" target=\"_blank\" rel=\"noreferrer noopener\">"), '</a>', "<a href=\"".concat(faq69, "\" target=\"_blank\" rel=\"noreferrer noopener\">"), '</a>');
    },
    affectedUsersCount: function affectedUsersCount() {
      if (this.areAllResultsSelected) {
        return this.totalEntries || 0;
      }

      return this.selectedCount;
    },
    selectedCount: function selectedCount() {
      var _this3 = this;

      var selectedRowKeyCount = 0;
      Object.keys(this.selectedRows).forEach(function (key) {
        if (_this3.selectedRows[key]) {
          selectedRowKeyCount += 1;
        }
      });
      return selectedRowKeyCount;
    },
    deleteUserPermConfirmSingleText: function deleteUserPermConfirmSingleText() {
      var _this$userToChange, _this$permissionsForS;

      return Object(external_CoreHome_["translate"])('UsersManager_DeleteUserPermConfirmSingle', "<strong>".concat(((_this$userToChange = this.userToChange) === null || _this$userToChange === void 0 ? void 0 : _this$userToChange.login) || '', "</strong>"), "<strong>".concat(this.getRoleDisplay(this.roleToChangeTo), "</strong>"), "<strong>".concat(external_CoreHome_["Matomo"].helper.htmlEntities(((_this$permissionsForS = this.permissionsForSite) === null || _this$permissionsForS === void 0 ? void 0 : _this$permissionsForS.name) || ''), "</strong>"));
    },
    deleteUserPermConfirmMultipleText: function deleteUserPermConfirmMultipleText() {
      var _this$permissionsForS2;

      return Object(external_CoreHome_["translate"])('UsersManager_DeleteUserPermConfirmMultiple', "<strong>".concat(this.affectedUsersCount, "</strong>"), "<strong>".concat(this.getRoleDisplay(this.roleToChangeTo), "</strong>"), "<strong>".concat(external_CoreHome_["Matomo"].helper.htmlEntities(((_this$permissionsForS2 = this.permissionsForSite) === null || _this$permissionsForS2 === void 0 ? void 0 : _this$permissionsForS2.name) || ''), "</strong>"));
    },
    bulkActionAccessLevels: function bulkActionAccessLevels() {
      return this.accessLevels.filter(function (e) {
        return e.key !== 'noaccess' && e.key !== 'superuser';
      });
    },
    anonymousAccessLevels: function anonymousAccessLevels() {
      return this.accessLevels.filter(function (e) {
        return e.key === 'noaccess' || e.key === 'view';
      });
    }
  }
}));
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/PagedUsersList/PagedUsersList.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/PagedUsersList/PagedUsersList.vue



PagedUsersListvue_type_script_lang_ts.render = PagedUsersListvue_type_template_id_6767e472_render

/* harmony default export */ var PagedUsersList = (PagedUsersListvue_type_script_lang_ts);
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/PagedUsersList/PagedUsersList.adapter.ts
/*!
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */


/* harmony default export */ var PagedUsersList_adapter = (Object(external_CoreHome_["createAngularJsAdapter"])({
  component: PagedUsersList,
  scope: {
    onEditUser: {
      angularJsBind: '&',
      vue: 'editUser'
    },
    onChangeUserRole: {
      angularJsBind: '&',
      vue: 'changeUserRole'
    },
    onDeleteUser: {
      angularJsBind: '&',
      vue: 'deleteUser'
    },
    onSearchChange: {
      angularJsBind: '&',
      vue: 'searchChange'
    },
    initialSiteId: {
      angularJsBind: '<'
    },
    initialSiteName: {
      angularJsBind: '<'
    },
    currentUserRole: {
      angularJsBind: '<'
    },
    isLoadingUsers: {
      angularJsBind: '<'
    },
    accessLevels: {
      angularJsBind: '<'
    },
    filterAccessLevels: {
      angularJsBind: '<'
    },
    totalEntries: {
      angularJsBind: '<'
    },
    users: {
      angularJsBind: '<'
    },
    searchParams: {
      angularJsBind: '<'
    }
  },
  directiveName: 'piwikPagedUsersList',
  restrict: 'E'
}));
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--12-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--0-1!./plugins/UsersManager/vue/src/UsersManager/UsersManager.vue?vue&type=template&id=db26d00e

var UsersManagervue_type_template_id_db26d00e_hoisted_1 = {
  class: "usersManager"
};
var UsersManagervue_type_template_id_db26d00e_hoisted_2 = {
  key: 0
};
var UsersManagervue_type_template_id_db26d00e_hoisted_3 = {
  key: 1
};
var UsersManagervue_type_template_id_db26d00e_hoisted_4 = {
  class: "row add-user-container"
};
var UsersManagervue_type_template_id_db26d00e_hoisted_5 = {
  class: "col s12"
};
var UsersManagervue_type_template_id_db26d00e_hoisted_6 = {
  class: "input-field",
  style: {
    "margin-right": "3.5px"
  }
};
var UsersManagervue_type_template_id_db26d00e_hoisted_7 = {
  key: 0,
  class: "input-field"
};
var UsersManagervue_type_template_id_db26d00e_hoisted_8 = {
  key: 0
};
var UsersManagervue_type_template_id_db26d00e_hoisted_9 = {
  class: "add-existing-user-modal modal",
  ref: "addExistingUserModal"
};
var UsersManagervue_type_template_id_db26d00e_hoisted_10 = {
  class: "modal-content"
};
var UsersManagervue_type_template_id_db26d00e_hoisted_11 = {
  class: "modal-footer"
};
function UsersManagervue_type_template_id_db26d00e_render(_ctx, _cache, $props, $setup, $data, $options) {
  var _component_EnrichedHeadline = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("EnrichedHeadline");

  var _component_PagedUsersList = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("PagedUsersList");

  var _component_UserEditForm = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("UserEditForm");

  var _component_Field = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Field");

  var _directive_content_intro = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveDirective"])("content-intro");

  var _directive_tooltips = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveDirective"])("tooltips");

  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])((Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", UsersManagervue_type_template_id_db26d00e_hoisted_1, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h2", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_EnrichedHeadline, {
    "help-url": "https://matomo.org/docs/manage-users/",
    "feature-name": "Users Management"
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(function () {
      return [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_ManageUsers')), 1)];
    }),
    _: 1
  })]), _ctx.currentUserRole === 'superuser' ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("p", UsersManagervue_type_template_id_db26d00e_hoisted_2, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_ManageUsersDesc')), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.currentUserRole === 'admin' ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("p", UsersManagervue_type_template_id_db26d00e_hoisted_3, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_ManageUsersAdminDesc')), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", UsersManagervue_type_template_id_db26d00e_hoisted_4, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", UsersManagervue_type_template_id_db26d00e_hoisted_5, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", UsersManagervue_type_template_id_db26d00e_hoisted_6, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
    class: "btn add-new-user",
    onClick: _cache[0] || (_cache[0] = function ($event) {
      return _ctx.onAddNewUser();
    })
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_AddUser')), 1)]), _ctx.currentUserRole !== 'superuser' ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", UsersManagervue_type_template_id_db26d00e_hoisted_7, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
    class: "btn add-existing-user",
    onClick: _cache[1] || (_cache[1] = function ($event) {
      return _ctx.showAddExistingUserModal();
    })
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_AddExistingUser')), 1)])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_PagedUsersList, {
    onEditUser: _cache[2] || (_cache[2] = function ($event) {
      return _ctx.onEditUser($event.user);
    }),
    onChangeUserRole: _cache[3] || (_cache[3] = function ($event) {
      return _ctx.onChangeUserRole($event.users, $event.role);
    }),
    onDeleteUser: _cache[4] || (_cache[4] = function ($event) {
      return _ctx.onDeleteUser($event.users);
    }),
    onSearchChange: _cache[5] || (_cache[5] = function ($event) {
      _ctx.searchParams = $event.params;

      _ctx.fetchUsers();
    }),
    "initial-site-id": _ctx.initialSiteId,
    "initial-site-name": _ctx.initialSiteName,
    "is-loading-users": _ctx.isLoadingUsers,
    "current-user-role": _ctx.currentUserRole,
    "access-levels": _ctx.accessLevels,
    "filter-access-levels": _ctx.actualFilterAccessLevels,
    "search-params": _ctx.searchParams,
    users: _ctx.users,
    "total-entries": _ctx.totalEntries
  }, null, 8, ["initial-site-id", "initial-site-name", "is-loading-users", "current-user-role", "access-levels", "filter-access-levels", "search-params", "users", "total-entries"])], 512), [[_directive_content_intro]])], 512), [[external_commonjs_vue_commonjs2_vue_root_Vue_["vShow"], !_ctx.isEditing]]), _ctx.isEditing ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", UsersManagervue_type_template_id_db26d00e_hoisted_8, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_UserEditForm, {
    onDone: _cache[6] || (_cache[6] = function ($event) {
      return _ctx.onDoneEditing($event.isUserModified);
    }),
    user: _ctx.userBeingEdited,
    "current-user-role": _ctx.currentUserRole,
    "access-levels": _ctx.accessLevels,
    "filter-access-levels": _ctx.actualFilterAccessLevels,
    "initial-site-id": _ctx.initialSiteId,
    "initial-site-name": _ctx.initialSiteName,
    onUpdated: _cache[7] || (_cache[7] = function ($event) {
      return _ctx.userBeingEdited = $event.user;
    })
  }, null, 8, ["user", "current-user-role", "access-levels", "filter-access-levels", "initial-site-id", "initial-site-name"])])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", UsersManagervue_type_template_id_db26d00e_hoisted_9, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", UsersManagervue_type_template_id_db26d00e_hoisted_10, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h3", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_AddExistingUser')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("p", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_EnterUsernameOrEmail')) + ":", 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
    modelValue: _ctx.addNewUserLoginEmail,
    "onUpdate:modelValue": _cache[8] || (_cache[8] = function ($event) {
      return _ctx.addNewUserLoginEmail = $event;
    }),
    name: "add-existing-user-email",
    uicontrol: "text"
  }, null, 8, ["modelValue"])])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", UsersManagervue_type_template_id_db26d00e_hoisted_11, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
    href: "",
    class: "modal-action modal-close btn",
    onClick: _cache[9] || (_cache[9] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
      return _ctx.addExistingUser();
    }, ["prevent"])),
    style: {
      "margin-right": "3.5px"
    }
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Add')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
    href: "",
    class: "modal-action modal-close modal-no",
    onClick: _cache[10] || (_cache[10] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
      return _ctx.addNewUserLoginEmail = null;
    }, ["prevent"]))
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Cancel')), 1)])], 512)], 512)), [[_directive_tooltips]]);
}
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/UsersManager/UsersManager.vue?vue&type=template&id=db26d00e

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--14-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--0-1!./plugins/UsersManager/vue/src/UsersManager/UsersManager.vue?vue&type=script&lang=ts
function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread(); }

function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _iterableToArray(iter) { if (typeof Symbol !== "undefined" && iter[Symbol.iterator] != null || iter["@@iterator"] != null) return Array.from(iter); }

function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) return _arrayLikeToArray(arr); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

/* eslint-disable newline-per-chained-call */





var NUM_USERS_PER_PAGE = 20;
var UsersManagervue_type_script_lang_ts_window = window,
    UsersManagervue_type_script_lang_ts_$ = UsersManagervue_type_script_lang_ts_window.$;
/* harmony default export */ var UsersManagervue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  props: {
    currentUserRole: {
      type: String,
      required: true
    },
    initialSiteName: {
      type: String,
      required: true
    },
    initialSiteId: {
      type: String,
      required: true
    },
    accessLevels: {
      type: Array,
      required: true
    },
    filterAccessLevels: {
      type: Array,
      required: true
    }
  },
  components: {
    EnrichedHeadline: external_CoreHome_["EnrichedHeadline"],
    PagedUsersList: PagedUsersList,
    UserEditForm: UserEditForm,
    Field: external_CorePluginsAdmin_["Field"]
  },
  directives: {
    ContentIntro: external_CoreHome_["ContentIntro"],
    Tooltips: external_CoreHome_["Tooltips"]
  },
  data: function data() {
    return {
      isEditing: !!external_CoreHome_["MatomoUrl"].urlParsed.value.showadduser,
      isCurrentUserSuperUser: true,
      users: [],
      totalEntries: null,
      searchParams: {
        offset: 0,
        limit: NUM_USERS_PER_PAGE,
        filter_search: '',
        filter_access: '',
        idSite: this.initialSiteId
      },
      isLoadingUsers: false,
      userBeingEdited: null,
      addNewUserLoginEmail: ''
    };
  },
  created: function created() {
    this.fetchUsers();
  },
  watch: {
    limit: function limit() {
      this.fetchUsers();
    }
  },
  methods: {
    onEditUser: function onEditUser(user) {
      external_CoreHome_["Matomo"].helper.lazyScrollToContent();
      this.isEditing = true;
      this.userBeingEdited = user;
    },
    onDoneEditing: function onDoneEditing(isUserModified) {
      this.isEditing = false;

      if (isUserModified) {
        // if a user was modified, we must reload the users list
        this.fetchUsers();
      }
    },
    showAddExistingUserModal: function showAddExistingUserModal() {
      UsersManagervue_type_script_lang_ts_$(this.$refs.addExistingUserModal).modal({
        dismissible: false
      }).modal('open');
    },
    onChangeUserRole: function onChangeUserRole(users, role) {
      var _this = this;

      this.isLoadingUsers = true;
      Promise.resolve().then(function () {
        if (users === 'all') {
          return _this.getAllUsersInSearch();
        }

        return users;
      }).then(function (usersResolved) {
        return usersResolved.filter(function (u) {
          return u.role !== 'superuser';
        }).map(function (u) {
          return u.login;
        });
      }).then(function (userLogins) {
        var requests = userLogins.map(function (login) {
          return {
            method: 'UsersManager.setUserAccess',
            userLogin: login,
            access: role,
            idSites: _this.searchParams.idSite,
            ignoreSuperusers: 1
          };
        });
        return external_CoreHome_["AjaxHelper"].fetch(requests, {
          createErrorNotification: true
        });
      }).catch(function () {// ignore (errors will still be displayed to the user)
      }).then(function () {
        return _this.fetchUsers();
      });
    },
    getAllUsersInSearch: function getAllUsersInSearch() {
      return external_CoreHome_["AjaxHelper"].fetch({
        method: 'UsersManager.getUsersPlusRole',
        filter_search: this.searchParams.filter_search,
        filter_access: this.searchParams.filter_access,
        idSite: this.searchParams.idSite,
        filter_limit: '-1'
      });
    },
    onDeleteUser: function onDeleteUser(users) {
      var _this2 = this;

      this.isLoadingUsers = true;
      Promise.resolve().then(function () {
        if (users === 'all') {
          return _this2.getAllUsersInSearch();
        }

        return users;
      }).then(function (usersResolved) {
        return usersResolved.map(function (u) {
          return u.login;
        });
      }).then(function (userLogins) {
        var requests = userLogins.map(function (login) {
          return {
            method: 'UsersManager.deleteUser',
            userLogin: login
          };
        });
        return external_CoreHome_["AjaxHelper"].fetch(requests, {
          createErrorNotification: true
        });
      }).catch(function () {// ignore (errors will still be displayed to the user)
      }).then(function () {
        return _this2.fetchUsers();
      });
    },
    fetchUsers: function fetchUsers() {
      var _this3 = this;

      this.isLoadingUsers = true;
      return external_CoreHome_["AjaxHelper"].fetch(Object.assign(Object.assign({}, this.searchParams), {}, {
        method: 'UsersManager.getUsersPlusRole'
      }), {
        returnResponseObject: true
      }).then(function (helper) {
        var result = helper.getRequestHandle();
        _this3.totalEntries = parseInt(result.getResponseHeader('x-matomo-total-results') || '0', 10);
        _this3.users = result.responseJSON;
        _this3.isLoadingUsers = false;
      }).catch(function () {
        _this3.isLoadingUsers = false;
      });
    },
    addExistingUser: function addExistingUser() {
      var _this4 = this;

      this.isLoadingUsers = true;
      return external_CoreHome_["AjaxHelper"].fetch({
        method: 'UsersManager.userExists',
        userLogin: this.addNewUserLoginEmail
      }).then(function (response) {
        if (response && response.value) {
          return _this4.addNewUserLoginEmail;
        }

        return external_CoreHome_["AjaxHelper"].fetch({
          method: 'UsersManager.getUserLoginFromUserEmail',
          userEmail: _this4.addNewUserLoginEmail
        }).then(function (r) {
          return r.value;
        });
      }).then(function (login) {
        return external_CoreHome_["AjaxHelper"].post({
          method: 'UsersManager.setUserAccess'
        }, {
          userLogin: login,
          access: 'view',
          idSites: _this4.searchParams.idSite
        });
      }).then(function () {
        return _this4.fetchUsers();
      }).catch(function () {
        _this4.isLoadingUsers = false;
      });
    },
    onAddNewUser: function onAddNewUser() {
      var parameters = {
        isAllowed: true
      };
      external_CoreHome_["Matomo"].postEvent('UsersManager.initAddUser', parameters);

      if (parameters && !parameters.isAllowed) {
        return;
      }

      this.isEditing = true;
      this.userBeingEdited = null;
    }
  },
  computed: {
    actualFilterAccessLevels: function actualFilterAccessLevels() {
      if (this.currentUserRole === 'superuser') {
        return [].concat(_toConsumableArray(this.filterAccessLevels), [{
          key: 'superuser',
          value: 'Superuser'
        }]);
      }

      return this.filterAccessLevels;
    }
  }
}));
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/UsersManager/UsersManager.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/UsersManager/UsersManager.vue



UsersManagervue_type_script_lang_ts.render = UsersManagervue_type_template_id_db26d00e_render

/* harmony default export */ var UsersManager = (UsersManagervue_type_script_lang_ts);
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/UsersManager/UsersManager.adapter.ts
/*!
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */


/* harmony default export */ var UsersManager_adapter = (Object(external_CoreHome_["createAngularJsAdapter"])({
  component: UsersManager,
  scope: {
    currentUserRole: {
      angularJsBind: '<'
    },
    initialSiteName: {
      angularJsBind: '@'
    },
    initialSiteId: {
      angularJsBind: '@'
    },
    accessLevels: {
      angularJsBind: '<'
    },
    filterAccessLevels: {
      angularJsBind: '<'
    }
  },
  directiveName: 'piwikUsersManager',
  restrict: 'E'
}));
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--12-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--0-1!./plugins/UsersManager/vue/src/AnonymousSettings/AnonymousSettings.vue?vue&type=template&id=49762f75

var AnonymousSettingsvue_type_template_id_49762f75_hoisted_1 = {
  key: 0,
  class: "alert alert-info"
};
var AnonymousSettingsvue_type_template_id_49762f75_hoisted_2 = {
  key: 1
};
function AnonymousSettingsvue_type_template_id_49762f75_render(_ctx, _cache, $props, $setup, $data, $options) {
  var _component_Field = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Field");

  var _component_SaveButton = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("SaveButton");

  var _component_ContentBlock = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("ContentBlock");

  var _directive_form = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveDirective"])("form");

  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_ContentBlock, {
    "content-title": _ctx.title
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(function () {
      return [_ctx.anonymousSites.length === 0 ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", AnonymousSettingsvue_type_template_id_49762f75_hoisted_1, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_NoteNoAnonymousUserAccessSettingsWontBeUsed2')), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.anonymousSites.length > 0 ? Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])((Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", AnonymousSettingsvue_type_template_id_49762f75_hoisted_2, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
        uicontrol: "radio",
        name: "anonymousDefaultReport",
        modelValue: _ctx.defaultReport,
        "onUpdate:modelValue": _cache[0] || (_cache[0] = function ($event) {
          return _ctx.defaultReport = $event;
        }),
        introduction: _ctx.translate('UsersManager_WhenUsersAreNotLoggedInAndVisitPiwikTheyShouldAccess'),
        options: _ctx.defaultReportOptions
      }, null, 8, ["modelValue", "introduction", "options"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
        uicontrol: "select",
        name: "anonymousDefaultReportWebsite",
        modelValue: _ctx.defaultReportWebsite,
        "onUpdate:modelValue": _cache[1] || (_cache[1] = function ($event) {
          return _ctx.defaultReportWebsite = $event;
        }),
        options: _ctx.anonymousSites
      }, null, 8, ["modelValue", "options"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
        uicontrol: "radio",
        name: "anonymousDefaultDate",
        modelValue: _ctx.defaultDate,
        "onUpdate:modelValue": _cache[2] || (_cache[2] = function ($event) {
          return _ctx.defaultDate = $event;
        }),
        introduction: _ctx.translate('UsersManager_ForAnonymousUsersReportDateToLoadByDefault'),
        options: _ctx.availableDefaultDates
      }, null, 8, ["modelValue", "introduction", "options"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_SaveButton, {
        saving: _ctx.loading,
        onConfirm: _cache[3] || (_cache[3] = function ($event) {
          return _ctx.save();
        })
      }, null, 8, ["saving"])], 512)), [[_directive_form]]) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)];
    }),
    _: 1
  }, 8, ["content-title"]);
}
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/AnonymousSettings/AnonymousSettings.vue?vue&type=template&id=49762f75

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--14-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--0-1!./plugins/UsersManager/vue/src/AnonymousSettings/AnonymousSettings.vue?vue&type=script&lang=ts



/* harmony default export */ var AnonymousSettingsvue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  props: {
    title: {
      type: String,
      required: true
    },
    anonymousSites: {
      type: Array,
      required: true
    },
    anonymousDefaultReport: {
      type: [String, Number],
      required: true
    },
    anonymousDefaultSite: {
      type: String,
      required: true
    },
    anonymousDefaultDate: {
      type: String,
      required: true
    },
    availableDefaultDates: {
      type: Object,
      required: true
    },
    defaultReportOptions: {
      type: Object,
      required: true
    }
  },
  components: {
    ContentBlock: external_CoreHome_["ContentBlock"],
    SaveButton: external_CorePluginsAdmin_["SaveButton"],
    Field: external_CorePluginsAdmin_["Field"]
  },
  directives: {
    Form: external_CorePluginsAdmin_["Form"]
  },
  data: function data() {
    return {
      loading: false,
      defaultReport: "".concat(this.anonymousDefaultReport),
      defaultReportWebsite: this.anonymousDefaultSite,
      defaultDate: this.anonymousDefaultDate
    };
  },
  methods: {
    save: function save() {
      var _this = this;

      var postParams = {
        anonymousDefaultReport: this.defaultReport === '1' ? this.defaultReportWebsite : this.defaultReport,
        anonymousDefaultDate: this.defaultDate
      };
      this.loading = true;
      external_CoreHome_["AjaxHelper"].post({
        module: 'UsersManager',
        action: 'recordAnonymousUserSettings',
        format: 'json'
      }, postParams, {
        withTokenInUrl: true
      }).then(function () {
        var id = external_CoreHome_["NotificationsStore"].show({
          message: Object(external_CoreHome_["translate"])('CoreAdminHome_SettingsSaveSuccess'),
          id: 'anonymousUserSettings',
          context: 'success',
          type: 'transient'
        });
        external_CoreHome_["NotificationsStore"].scrollToNotification(id);
      }).finally(function () {
        _this.loading = false;
      });
    }
  }
}));
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/AnonymousSettings/AnonymousSettings.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/AnonymousSettings/AnonymousSettings.vue



AnonymousSettingsvue_type_script_lang_ts.render = AnonymousSettingsvue_type_template_id_49762f75_render

/* harmony default export */ var AnonymousSettings = (AnonymousSettingsvue_type_script_lang_ts);
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/AnonymousSettings/AnonymousSettings.adapter.ts
/*!
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */


/* harmony default export */ var AnonymousSettings_adapter = (Object(external_CoreHome_["createAngularJsAdapter"])({
  component: AnonymousSettings,
  scope: {
    title: {
      angularJsBind: '<'
    },
    anonymousSites: {
      angularJsBind: '<'
    },
    anonymousDefaultReport: {
      angularJsBind: '<'
    },
    anonymousDefaultSite: {
      angularJsBind: '<'
    },
    anonymousDefaultDate: {
      angularJsBind: '<'
    },
    availableDefaultDates: {
      angularJsBind: '<'
    },
    defaultReportOptions: {
      angularJsBind: '<'
    }
  },
  directiveName: 'matomoAnonymousSettings'
}));
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--12-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--0-1!./plugins/UsersManager/vue/src/NewsletterSettings/NewsletterSettings.vue?vue&type=template&id=2cb03bb3

var NewsletterSettingsvue_type_template_id_2cb03bb3_hoisted_1 = {
  id: "newsletterSignup"
};
function NewsletterSettingsvue_type_template_id_2cb03bb3_render(_ctx, _cache, $props, $setup, $data, $options) {
  var _component_Field = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Field");

  var _component_SaveButton = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("SaveButton");

  var _component_ContentBlock = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("ContentBlock");

  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])((Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", NewsletterSettingsvue_type_template_id_2cb03bb3_hoisted_1, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_ContentBlock, {
    "content-title": _ctx.translate('UsersManager_NewsletterSignupTitle')
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(function () {
      return [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
        uicontrol: "checkbox",
        name: "newsletterSignupCheckbox",
        id: "newsletterSignupCheckbox",
        modelValue: _ctx.newsletterSignupCheckbox,
        "onUpdate:modelValue": _cache[0] || (_cache[0] = function ($event) {
          return _ctx.newsletterSignupCheckbox = $event;
        }),
        "full-width": true,
        title: _ctx.signupTitleText
      }, null, 8, ["modelValue", "title"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_SaveButton, {
        id: "newsletterSignupBtn",
        onConfirm: _cache[1] || (_cache[1] = function ($event) {
          return _ctx.signupForNewsletter();
        }),
        disabled: !_ctx.newsletterSignupCheckbox,
        value: _ctx.newsletterSignupButtonTitle,
        saving: _ctx.isProcessingNewsletterSignup
      }, null, 8, ["disabled", "value", "saving"])];
    }),
    _: 1
  }, 8, ["content-title"])], 512)), [[external_commonjs_vue_commonjs2_vue_root_Vue_["vShow"], _ctx.showNewsletterSignup]]);
}
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/NewsletterSettings/NewsletterSettings.vue?vue&type=template&id=2cb03bb3

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--14-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--0-1!./plugins/UsersManager/vue/src/NewsletterSettings/NewsletterSettings.vue?vue&type=script&lang=ts



/* harmony default export */ var NewsletterSettingsvue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  data: function data() {
    return {
      showNewsletterSignup: true,
      newsletterSignupCheckbox: false,
      isProcessingNewsletterSignup: false,
      newsletterSignupButtonTitle: Object(external_CoreHome_["translate"])('General_Save')
    };
  },
  components: {
    ContentBlock: external_CoreHome_["ContentBlock"],
    SaveButton: external_CorePluginsAdmin_["SaveButton"],
    Field: external_CorePluginsAdmin_["Field"]
  },
  computed: {
    signupTitleText: function signupTitleText() {
      return Object(external_CoreHome_["translate"])('UsersManager_NewsletterSignupMessage', '<a href="https://matomo.org/privacy-policy/" target="_blank">', '</a>');
    }
  },
  methods: {
    signupForNewsletter: function signupForNewsletter() {
      var _this = this;

      this.newsletterSignupButtonTitle = Object(external_CoreHome_["translate"])('General_Loading');
      this.isProcessingNewsletterSignup = true;
      external_CoreHome_["AjaxHelper"].fetch({
        module: 'API',
        method: 'UsersManager.newsletterSignup'
      }, {
        withTokenInUrl: true
      }).then(function () {
        _this.isProcessingNewsletterSignup = false;
        _this.showNewsletterSignup = false;
        var id = external_CoreHome_["NotificationsStore"].show({
          message: Object(external_CoreHome_["translate"])('UsersManager_NewsletterSignupSuccessMessage'),
          id: 'newslettersignup',
          context: 'success',
          type: 'transient'
        });
        external_CoreHome_["NotificationsStore"].scrollToNotification(id);
      }).catch(function () {
        _this.isProcessingNewsletterSignup = false;
        var id = external_CoreHome_["NotificationsStore"].show({
          message: Object(external_CoreHome_["translate"])('UsersManager_NewsletterSignupFailureMessage'),
          id: 'newslettersignup',
          context: 'error',
          type: 'transient'
        });
        external_CoreHome_["NotificationsStore"].scrollToNotification(id);
        _this.newsletterSignupButtonTitle = Object(external_CoreHome_["translate"])('General_PleaseTryAgain');
      });
    }
  }
}));
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/NewsletterSettings/NewsletterSettings.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/NewsletterSettings/NewsletterSettings.vue



NewsletterSettingsvue_type_script_lang_ts.render = NewsletterSettingsvue_type_template_id_2cb03bb3_render

/* harmony default export */ var NewsletterSettings = (NewsletterSettingsvue_type_script_lang_ts);
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/NewsletterSettings/NewsletterSettings.adapter.ts
/*!
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */


/* harmony default export */ var NewsletterSettings_adapter = (Object(external_CoreHome_["createAngularJsAdapter"])({
  component: NewsletterSettings,
  scope: {},
  directiveName: 'matomoNewsletterSettings'
}));
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--12-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--0-1!./plugins/UsersManager/vue/src/PersonalSettings/PersonalSettings.vue?vue&type=template&id=94ecac48

var PersonalSettingsvue_type_template_id_94ecac48_hoisted_1 = {
  id: "userSettingsTable"
};
var PersonalSettingsvue_type_template_id_94ecac48_hoisted_2 = {
  key: 0
};
var PersonalSettingsvue_type_template_id_94ecac48_hoisted_3 = {
  id: "languageHelp",
  class: "inline-help-node"
};
var PersonalSettingsvue_type_template_id_94ecac48_hoisted_4 = {
  target: "_blank",
  rel: "noreferrer noopener",
  href: "https://matomo.org/translations/"
};
var PersonalSettingsvue_type_template_id_94ecac48_hoisted_5 = {
  class: "sites_autocomplete"
};
var PersonalSettingsvue_type_template_id_94ecac48_hoisted_6 = {
  class: "modal",
  id: "confirmChangesWithPassword",
  ref: "confirmChangesWithPasswordModal"
};
var PersonalSettingsvue_type_template_id_94ecac48_hoisted_7 = {
  class: "modal-content"
};
var PersonalSettingsvue_type_template_id_94ecac48_hoisted_8 = {
  class: "modal-footer"
};
function PersonalSettingsvue_type_template_id_94ecac48_render(_ctx, _cache, $props, $setup, $data, $options) {
  var _component_Field = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Field");

  var _component_SiteSelector = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("SiteSelector");

  var _component_SaveButton = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("SaveButton");

  var _component_ContentBlock = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("ContentBlock");

  var _directive_form = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveDirective"])("form");

  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_ContentBlock, {
    "content-title": _ctx.title,
    feature: 'true'
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(function () {
      return [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("form", PersonalSettingsvue_type_template_id_94ecac48_hoisted_1, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
        uicontrol: "text",
        name: "username",
        title: _ctx.translate('General_Username'),
        disabled: true,
        modelValue: _ctx.username,
        "onUpdate:modelValue": _cache[0] || (_cache[0] = function ($event) {
          return _ctx.username = $event;
        }),
        "inline-help": _ctx.translate('UsersManager_YourUsernameCannotBeChanged')
      }, null, 8, ["title", "modelValue", "inline-help"])]), _ctx.isUsersAdminEnabled ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", PersonalSettingsvue_type_template_id_94ecac48_hoisted_2, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
        uicontrol: "text",
        name: "email",
        "model-value": _ctx.email,
        "onUpdate:modelValue": _cache[1] || (_cache[1] = function ($event) {
          _ctx.email = $event;
          _ctx.doesRequirePasswordConfirmation = true;
        }),
        maxlength: 100,
        title: _ctx.translate('UsersManager_Email')
      }, null, 8, ["model-value", "title"])])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", PersonalSettingsvue_type_template_id_94ecac48_hoisted_3, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", PersonalSettingsvue_type_template_id_94ecac48_hoisted_4, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('LanguagesManager_AboutPiwikTranslations')), 1)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
        uicontrol: "select",
        name: "language",
        modelValue: _ctx.language,
        "onUpdate:modelValue": _cache[2] || (_cache[2] = function ($event) {
          return _ctx.language = $event;
        }),
        title: _ctx.translate('General_Language'),
        options: _ctx.languageOptions,
        "inline-help": "#languageHelp"
      }, null, 8, ["modelValue", "title", "options"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
        uicontrol: "select",
        name: "timeformat",
        modelValue: _ctx.timeformat,
        "onUpdate:modelValue": _cache[3] || (_cache[3] = function ($event) {
          return _ctx.timeformat = $event;
        }),
        title: _ctx.translate('General_TimeFormat'),
        options: _ctx.timeFormats
      }, null, 8, ["modelValue", "title", "options"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
        uicontrol: "radio",
        name: "defaultReport",
        modelValue: _ctx.theDefaultReport,
        "onUpdate:modelValue": _cache[4] || (_cache[4] = function ($event) {
          return _ctx.theDefaultReport = $event;
        }),
        introduction: _ctx.translate('UsersManager_ReportToLoadByDefault'),
        title: _ctx.translate('General_AllWebsitesDashboard'),
        options: _ctx.defaultReportOptions
      }, null, 8, ["modelValue", "introduction", "title", "options"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", PersonalSettingsvue_type_template_id_94ecac48_hoisted_5, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_SiteSelector, {
        modelValue: _ctx.site,
        "onUpdate:modelValue": _cache[5] || (_cache[5] = function ($event) {
          return _ctx.site = $event;
        }),
        "show-selected-site": true,
        "switch-site-on-select": false,
        "show-all-sites-item": false,
        showselectedsite: true,
        id: "defaultReportSiteSelector"
      }, null, 8, ["modelValue"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
        uicontrol: "radio",
        name: "defaultDate",
        modelValue: _ctx.theDefaultDate,
        "onUpdate:modelValue": _cache[6] || (_cache[6] = function ($event) {
          return _ctx.theDefaultDate = $event;
        }),
        introduction: _ctx.translate('UsersManager_ReportDateToLoadByDefault'),
        options: _ctx.availableDefaultDates
      }, null, 8, ["modelValue", "introduction", "options"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_SaveButton, {
        onConfirm: _cache[7] || (_cache[7] = function ($event) {
          return _ctx.save();
        }),
        saving: _ctx.loading
      }, null, 8, ["saving"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", PersonalSettingsvue_type_template_id_94ecac48_hoisted_6, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", PersonalSettingsvue_type_template_id_94ecac48_hoisted_7, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h2", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('UsersManager_ConfirmWithPassword')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
        uicontrol: "password",
        name: "currentPassword",
        autocomplete: false,
        modelValue: _ctx.passwordCurrent,
        "onUpdate:modelValue": _cache[8] || (_cache[8] = function ($event) {
          return _ctx.passwordCurrent = $event;
        }),
        "full-width": true,
        title: _ctx.translate('UsersManager_YourCurrentPassword')
      }, null, 8, ["modelValue", "title"])])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", PersonalSettingsvue_type_template_id_94ecac48_hoisted_8, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
        href: "",
        class: "modal-action btn",
        onClick: _cache[9] || (_cache[9] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
          return _ctx.save();
        }, ["prevent"])),
        style: {
          "margin-right": "3.5px"
        }
      }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Ok')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
        href: "",
        class: "modal-action modal-close modal-no",
        onClick: _cache[10] || (_cache[10] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function ($event) {
          return _ctx.passwordCurrent = '';
        }, ["prevent"]))
      }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Cancel')), 1)])], 512)], 512), [[_directive_form]])];
    }),
    _: 1
  }, 8, ["content-title"]);
}
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/PersonalSettings/PersonalSettings.vue?vue&type=template&id=94ecac48

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--14-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--0-1!./plugins/UsersManager/vue/src/PersonalSettings/PersonalSettings.vue?vue&type=script&lang=ts



var PersonalSettingsvue_type_script_lang_ts_window = window,
    PersonalSettingsvue_type_script_lang_ts_$ = PersonalSettingsvue_type_script_lang_ts_window.$;
/* harmony default export */ var PersonalSettingsvue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  props: {
    isUsersAdminEnabled: {
      type: Boolean,
      required: true
    },
    title: {
      type: String,
      required: true
    },
    userLogin: {
      type: String,
      required: true
    },
    userEmail: {
      type: String,
      required: true
    },
    currentLanguageCode: {
      type: String,
      required: true
    },
    languageOptions: {
      type: Object,
      required: true
    },
    currentTimeformat: {
      type: Number,
      required: true
    },
    timeFormats: {
      type: Object,
      required: true
    },
    defaultReport: {
      type: [String, Number],
      required: true
    },
    defaultReportOptions: {
      type: Object,
      required: true
    },
    defaultReportIdSite: {
      type: [String, Number],
      required: true
    },
    defaultReportSiteName: {
      type: String,
      required: true
    },
    defaultDate: {
      type: String,
      required: true
    },
    availableDefaultDates: {
      type: Object,
      required: true
    }
  },
  components: {
    ContentBlock: external_CoreHome_["ContentBlock"],
    SaveButton: external_CorePluginsAdmin_["SaveButton"],
    Field: external_CorePluginsAdmin_["Field"],
    SiteSelector: external_CoreHome_["SiteSelector"]
  },
  directives: {
    Form: external_CorePluginsAdmin_["Form"]
  },
  data: function data() {
    return {
      doesRequirePasswordConfirmation: false,
      username: this.userLogin,
      email: this.userEmail,
      language: this.currentLanguageCode,
      timeformat: this.currentTimeformat,
      theDefaultReport: this.defaultReport,
      site: {
        id: this.defaultReportIdSite,
        name: external_CoreHome_["Matomo"].helper.htmlDecode(this.defaultReportSiteName)
      },
      theDefaultDate: this.defaultDate,
      loading: false,
      passwordCurrent: ''
    };
  },
  methods: {
    save: function save() {
      var _this = this;

      if (this.doesRequirePasswordConfirmation && !this.passwordCurrent) {
        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
        // @ts-ignore
        PersonalSettingsvue_type_script_lang_ts_$(this.$refs.confirmChangesWithPasswordModal).modal({
          dismissible: false,
          ready: function ready() {
            PersonalSettingsvue_type_script_lang_ts_$('.modal.open #currentPassword').focus();
          }
        }).modal('open');
        return;
      }

      var modal = M.Modal.getInstance(this.$refs.confirmChangesWithPasswordModal);

      if (modal) {
        modal.close();
      }

      var postParams = {
        email: this.email,
        defaultReport: this.theDefaultReport === 'MultiSites' ? this.theDefaultReport : this.site.id,
        defaultDate: this.theDefaultDate,
        language: this.language,
        timeformat: this.timeformat
      };

      if (this.passwordCurrent) {
        postParams.passwordConfirmation = this.passwordCurrent;
      }

      this.loading = true;
      external_CoreHome_["AjaxHelper"].post({
        module: 'UsersManager',
        action: 'recordUserSettings',
        format: 'json'
      }, postParams, {
        withTokenInUrl: true
      }).then(function () {
        var id = external_CoreHome_["NotificationsStore"].show({
          message: Object(external_CoreHome_["translate"])('CoreAdminHome_SettingsSaveSuccess'),
          id: 'PersonalSettingsSuccess',
          context: 'success',
          type: 'transient'
        });
        external_CoreHome_["NotificationsStore"].scrollToNotification(id);
        _this.doesRequirePasswordConfirmation = false;
        _this.passwordCurrent = '';
        _this.loading = false;
      }).catch(function () {
        _this.loading = false;
        _this.passwordCurrent = '';
      });
    }
  }
}));
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/PersonalSettings/PersonalSettings.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/PersonalSettings/PersonalSettings.vue



PersonalSettingsvue_type_script_lang_ts.render = PersonalSettingsvue_type_template_id_94ecac48_render

/* harmony default export */ var PersonalSettings = (PersonalSettingsvue_type_script_lang_ts);
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/PersonalSettings/PersonalSettings.adapter.ts
/*!
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */


/* harmony default export */ var PersonalSettings_adapter = (Object(external_CoreHome_["createAngularJsAdapter"])({
  component: PersonalSettings,
  scope: {
    isUsersAdminEnabled: {
      angularJsBind: '<'
    },
    title: {
      angularJsBind: '<'
    },
    userLogin: {
      angularJsBind: '<'
    },
    userEmail: {
      angularJsBind: '<'
    },
    currentLanguageCode: {
      angularJsBind: '<'
    },
    languageOptions: {
      angularJsBind: '<'
    },
    currentTimeformat: {
      angularJsBind: '<'
    },
    timeFormats: {
      angularJsBind: '<'
    },
    defaultReport: {
      angularJsBind: '<'
    },
    defaultReportOptions: {
      angularJsBind: '<'
    },
    defaultReportIdSite: {
      angularJsBind: '<'
    },
    defaultReportSiteName: {
      angularJsBind: '<'
    },
    defaultDate: {
      angularJsBind: '<'
    },
    availableDefaultDates: {
      angularJsBind: '<'
    }
  },
  directiveName: 'matomoPersonalSettings'
}));
// CONCATENATED MODULE: ./plugins/UsersManager/vue/src/index.ts
/*!
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
















// CONCATENATED MODULE: ./node_modules/@vue/cli-service/lib/commands/build/entry-lib-no-default.js




/***/ })

/******/ });
});
//# sourceMappingURL=UsersManager.umd.js.map