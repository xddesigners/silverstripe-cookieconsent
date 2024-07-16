/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./client/src/js/cookieconsent.js":
/*!****************************************!*\
  !*** ./client/src/js/cookieconsent.js ***!
  \****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ CookieConsent)
/* harmony export */ });
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
var CookieConsent = /*#__PURE__*/function () {
  function CookieConsent() {
    _classCallCheck(this, CookieConsent);
    // console.log('init cookie consent');
    this.cookieName = 'CookieConsent';
    this.cookieJar = {}; // Changed from [] to {}

    // Define dataLayer and the gtag function.
    window.dataLayer = window.dataLayer || [];
    function gtag() {
      dataLayer.push(arguments);
    }

    // Set default consent to 'denied' as a placeholder
    var data = {
      'ad_storage': 'denied',
      // Marketing
      'ad_user_data': 'denied',
      // Marketing
      'ad_personalization': 'denied',
      // Marketing
      'analytics_storage': 'denied' // Analytics
    };
    gtag('consent', 'default', data);
    this.updateConsent();
    this.pushToDataLayer();
    this.enableXHRMode();
  }
  return _createClass(CookieConsent, [{
    key: "isSet",
    value: function isSet() {
      return this.cookieJar[this.cookieName] !== undefined;
    }
  }, {
    key: "check",
    value: function check(group) {
      return this.consent.indexOf(group) !== -1;
    }
  }, {
    key: "updateConsent",
    value: function updateConsent() {
      var cookies = document.cookie ? document.cookie.split('; ') : [];
      for (var i = 0; i < cookies.length; i++) {
        var parts = cookies[i].split('=');
        var key = parts[0];
        this.cookieJar[key] = parts.slice(1).join('=');
      }
      this.consent = this.isSet() ? decodeURIComponent(this.cookieJar[this.cookieName]).split(',') : [];
    }
  }, {
    key: "pushToDataLayer",
    value: function pushToDataLayer() {
      console.log('consent', this.consent);

      // Add

      // Simplified direct reference to window.dataLayer
      if (typeof window.dataLayer !== 'undefined') {
        if (this.check('Necessary')) {
          console.log('grant: functionality_storage');
          gtag('consent', 'update', {
            'functionality_storage': 'granted'
          });
          window.dataLayer.push({
            'event': 'cookieconsent_preferences'
          });
        }
        if (this.check('Analytics')) {
          console.log('grant: analytics_storage');
          gtag('consent', 'update', {
            'analytics_storage': 'granted'
          });
          window.dataLayer.push({
            'event': 'cookieconsent_analytics'
          });
        }
        if (this.check('Marketing')) {
          console.log('grant: ad_storage');
          console.log('grant: personalization_storage');
          gtag('consent', 'update', {
            'ad_storage': 'granted',
            'personalization_storage': 'granted'
          });
          window.dataLayer.push({
            'event': 'cookieconsent_marketing'
          });
        }
      } else {
        console.log('dataLayer undefined');
      }
    }
  }, {
    key: "enableXHRMode",
    value: function enableXHRMode() {
      var _this2 = this;
      var acceptAllLink = document.getElementById('accept-all-cookies');
      var acceptNecessaryLink = document.getElementById('accept-necessary-cookies');
      var cookiePopup = document.getElementById('cookie-consent-popup');
      if (cookiePopup) {
        if (this.isSet()) {
          cookiePopup.remove();
          return;
        }

        // show popup
        cookiePopup.classList.remove('cookie-consent-background--hidden');
        if (acceptAllLink) {
          acceptAllLink.addEventListener('click', function (e) {
            e.preventDefault();
            _this2.sendXHRRequest(acceptAllLink.href);
          });
        }
        if (acceptNecessaryLink) {
          acceptNecessaryLink.addEventListener('click', function (e) {
            e.preventDefault();
            _this2.sendXHRRequest(acceptNecessaryLink.href);
          });
        }
      }
    }
  }, {
    key: "sendXHRRequest",
    value: function sendXHRRequest(url) {
      var _this = this;
      var cookiePopup = document.getElementById('cookie-consent-popup');
      var xhr = new XMLHttpRequest();
      xhr.open('GET', url);
      xhr.addEventListener('load', function () {
        if (xhr.status >= 200 && xhr.status < 300) {
          // console.log('XHRRequest success');
          _this.updateConsent();
          _this.pushToDataLayer();
        } else {
          console.log('XHRRequest completed but was not successful. Status:', xhr.status);
        }
      });
      xhr.addEventListener('error', function () {
        console.error('XHRRequest failed.');
      });
      xhr.send();
      cookiePopup.remove();
    }
  }]);
}();

window.CookieConsent = CookieConsent;
var consent = new CookieConsent();

/***/ }),

/***/ "./client/src/styles/cookieconsent.scss":
/*!**********************************************!*\
  !*** ./client/src/styles/cookieconsent.scss ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/chunk loaded */
/******/ 	(() => {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = (result, chunkIds, fn, priority) => {
/******/ 			if(chunkIds) {
/******/ 				priority = priority || 0;
/******/ 				for(var i = deferred.length; i > 0 && deferred[i - 1][2] > priority; i--) deferred[i] = deferred[i - 1];
/******/ 				deferred[i] = [chunkIds, fn, priority];
/******/ 				return;
/******/ 			}
/******/ 			var notFulfilled = Infinity;
/******/ 			for (var i = 0; i < deferred.length; i++) {
/******/ 				var [chunkIds, fn, priority] = deferred[i];
/******/ 				var fulfilled = true;
/******/ 				for (var j = 0; j < chunkIds.length; j++) {
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every((key) => (__webpack_require__.O[key](chunkIds[j])))) {
/******/ 						chunkIds.splice(j--, 1);
/******/ 					} else {
/******/ 						fulfilled = false;
/******/ 						if(priority < notFulfilled) notFulfilled = priority;
/******/ 					}
/******/ 				}
/******/ 				if(fulfilled) {
/******/ 					deferred.splice(i--, 1)
/******/ 					var r = fn();
/******/ 					if (r !== undefined) result = r;
/******/ 				}
/******/ 			}
/******/ 			return result;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	(() => {
/******/ 		// no baseURI
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"/client/dist/js/cookieconsent": 0,
/******/ 			"client/dist/styles/cookieconsent": 0
/******/ 		};
/******/ 		
/******/ 		// no chunk on demand loading
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		__webpack_require__.O.j = (chunkId) => (installedChunks[chunkId] === 0);
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = (parentChunkLoadingFunction, data) => {
/******/ 			var [chunkIds, moreModules, runtime] = data;
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some((id) => (installedChunks[id] !== 0))) {
/******/ 				for(moduleId in moreModules) {
/******/ 					if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 						__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 					}
/******/ 				}
/******/ 				if(runtime) var result = runtime(__webpack_require__);
/******/ 			}
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					installedChunks[chunkId][0]();
/******/ 				}
/******/ 				installedChunks[chunkId] = 0;
/******/ 			}
/******/ 			return __webpack_require__.O(result);
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = self["webpackChunksilverstripe_cookie_consent"] = self["webpackChunksilverstripe_cookie_consent"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module depends on other loaded chunks and execution need to be delayed
/******/ 	__webpack_require__.O(undefined, ["client/dist/styles/cookieconsent"], () => (__webpack_require__("./client/src/js/cookieconsent.js")))
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["client/dist/styles/cookieconsent"], () => (__webpack_require__("./client/src/styles/cookieconsent.scss")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;