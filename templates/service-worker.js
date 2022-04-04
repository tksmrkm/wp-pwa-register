/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./components/ServiceWorker/install.ts":
/*!*********************************************!*\
  !*** ./components/ServiceWorker/install.ts ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "handleFetch": () => (/* binding */ handleFetch),
/* harmony export */   "handleInstall": () => (/* binding */ handleInstall)
/* harmony export */ });
/* harmony import */ var _package_json__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../package.json */ "../../package.json");
var __awaiter = (undefined && undefined.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
var __generator = (undefined && undefined.__generator) || function (thisArg, body) {
    var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t, g;
    return g = { next: verb(0), "throw": verb(1), "return": verb(2) }, typeof Symbol === "function" && (g[Symbol.iterator] = function() { return this; }), g;
    function verb(n) { return function (v) { return step([n, v]); }; }
    function step(op) {
        if (f) throw new TypeError("Generator is already executing.");
        while (_) try {
            if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
            if (y = 0, t) op = [op[0] & 2, t.value];
            switch (op[0]) {
                case 0: case 1: t = op; break;
                case 4: _.label++; return { value: op[1], done: false };
                case 5: _.label++; y = op[1]; op = [0]; continue;
                case 7: op = _.ops.pop(); _.trys.pop(); continue;
                default:
                    if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                    if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                    if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                    if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                    if (t[2]) _.ops.pop();
                    _.trys.pop(); continue;
            }
            op = body.call(thisArg, _);
        } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
        if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
    }
};

var _offline = '/wp-content/plugins/wp-pwa-register/offline.html';
var handleInstall = function (event) {
    event.waitUntil(caches.open(_package_json__WEBPACK_IMPORTED_MODULE_0__.version)
        .then(function (cache) {
        cache.add(_offline);
    }));
};
var handleFetch = function (event) { return __awaiter(void 0, void 0, void 0, function () {
    var url, matched, fallback;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                url = new URL(event.request.url);
                if (!(url.pathname === '/')) return [3 /*break*/, 4];
                if (!!navigator.onLine) return [3 /*break*/, 2];
                return [4 /*yield*/, caches.match(_offline)];
            case 1:
                matched = _a.sent();
                if (matched) {
                    event.respondWith(matched);
                }
                return [3 /*break*/, 4];
            case 2: return [4 /*yield*/, fetch(event.request)
                    .catch(function () {
                    return caches.match(_offline);
                })];
            case 3:
                fallback = _a.sent();
                if (fallback) {
                    event.respondWith(fallback);
                }
                _a.label = 4;
            case 4: return [2 /*return*/];
        }
    });
}); };


/***/ }),

/***/ "./components/ServiceWorker/notification.ts":
/*!**************************************************!*\
  !*** ./components/ServiceWorker/notification.ts ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "onClick": () => (/* binding */ onClick),
/* harmony export */   "onClose": () => (/* binding */ onClose)
/* harmony export */ });
var common = function (event) { return function (clients) {
    var url = '/';
    if (event.notification.data.url) {
        url = event.notification.data.url;
    }
    event.waitUntil(clients
        .matchAll({ type: 'window' })
        .then(function () {
        return clients.openWindow(url);
    }));
}; };
var onClick = function (event) {
    event.notification.close();
    common(event)(clients);
};
var onClose = function (event) {
    common(event)(clients);
};


/***/ }),

/***/ "./components/ServiceWorker/push.ts":
/*!******************************************!*\
  !*** ./components/ServiceWorker/push.ts ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
var pushHandler = function (event) {
    var _a;
    var endpoint = ['/wp-json/wp/v2/pwa_notifications'];
    try {
        var data = (_a = event.data) === null || _a === void 0 ? void 0 : _a.json();
        if (data.data && data.data.post_id) {
            endpoint.push(data.data.post_id);
        }
        else if (data.post_id) {
            endpoint.push(data.post_id);
        }
    }
    catch (e) {
        console.warn(e);
    }
    event.waitUntil(fetch(endpoint.join('/'))
        .then(function (response) {
        if (response.ok) {
            return response.json();
        }
        throw new Error('notifications api response error');
    })
        .then(function (json) {
        var title = json.post_meta.headline ? json.post_meta.headline : '<?php echo $title ?>';
        var icon = json.post_meta.icon ? json.post_meta.icon : '<?php echo $icon ?>';
        var opts = {
            icon: icon,
            body: json.title.rendered,
            data: {
                url: json.post_meta.link
            },
            vibrate: [200, 100, 200, 100, 200, 100, 200]
        };
        return self.registration.showNotification(title, opts);
    })
        .catch(console.warn));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (pushHandler);


/***/ }),

/***/ "../../package.json":
/*!**************************!*\
  !*** ../../package.json ***!
  \**************************/
/***/ ((module) => {

module.exports = JSON.parse('{"name":"wp-pwa-register","version":"1.3.7","main":"index.js","repository":"C:/xampp/htdocs/temp.apptimes.net/wordpress/wp-content/plugins/../../../../wordpress/wp-content/plugins/wp-pwa-register","author":"tksmrkm <nis.murakami@gmail.com>","license":"MIT","devDependencies":{"ts-loader":"^9.2.8","typescript":"^4.6.3","webpack":"^5.70.0","webpack-cli":"^4.9.2"},"scripts":{"js:build":"webpack --mode production","js:watch":"webpack -w --mode development -d inline-source-map"},"dependencies":{"firebase":"^9.6.10"}}');

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
/************************************************************************/
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
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
(() => {
/*!***************************!*\
  !*** ./service-worker.ts ***!
  \***************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _components_ServiceWorker_push__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./components/ServiceWorker/push */ "./components/ServiceWorker/push.ts");
/* harmony import */ var _components_ServiceWorker_notification__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./components/ServiceWorker/notification */ "./components/ServiceWorker/notification.ts");
/* harmony import */ var _components_ServiceWorker_install__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./components/ServiceWorker/install */ "./components/ServiceWorker/install.ts");



self.addEventListener('push', _components_ServiceWorker_push__WEBPACK_IMPORTED_MODULE_0__["default"]);
self.addEventListener('notificationclick', _components_ServiceWorker_notification__WEBPACK_IMPORTED_MODULE_1__.onClick);
self.addEventListener('notificationclose', _components_ServiceWorker_notification__WEBPACK_IMPORTED_MODULE_1__.onClose);
self.addEventListener('install', _components_ServiceWorker_install__WEBPACK_IMPORTED_MODULE_2__.handleInstall);
self.addEventListener('fetch', _components_ServiceWorker_install__WEBPACK_IMPORTED_MODULE_2__.handleFetch);

})();

/******/ })()
;
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoic2VydmljZS13b3JrZXIuanMiLCJtYXBwaW5ncyI6Ijs7Ozs7Ozs7Ozs7Ozs7OztBQUFBLGlCQUFpQixTQUFJLElBQUksU0FBSTtBQUM3Qiw0QkFBNEIsK0RBQStELGlCQUFpQjtBQUM1RztBQUNBLG9DQUFvQyxNQUFNLCtCQUErQixZQUFZO0FBQ3JGLG1DQUFtQyxNQUFNLG1DQUFtQyxZQUFZO0FBQ3hGLGdDQUFnQztBQUNoQztBQUNBLEtBQUs7QUFDTDtBQUNBLG1CQUFtQixTQUFJLElBQUksU0FBSTtBQUMvQixjQUFjLDZCQUE2QiwwQkFBMEIsY0FBYyxxQkFBcUI7QUFDeEcsaUJBQWlCLG9EQUFvRCxxRUFBcUUsY0FBYztBQUN4Six1QkFBdUIsc0JBQXNCO0FBQzdDO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLHdDQUF3QztBQUN4QyxtQ0FBbUMsU0FBUztBQUM1QyxtQ0FBbUMsV0FBVyxVQUFVO0FBQ3hELDBDQUEwQyxjQUFjO0FBQ3hEO0FBQ0EsOEdBQThHLE9BQU87QUFDckgsaUZBQWlGLGlCQUFpQjtBQUNsRyx5REFBeUQsZ0JBQWdCLFFBQVE7QUFDakYsK0NBQStDLGdCQUFnQixnQkFBZ0I7QUFDL0U7QUFDQSxrQ0FBa0M7QUFDbEM7QUFDQTtBQUNBLFVBQVUsWUFBWSxhQUFhLFNBQVMsVUFBVTtBQUN0RCxvQ0FBb0MsU0FBUztBQUM3QztBQUNBO0FBQ21EO0FBQ25EO0FBQ087QUFDUCxnQ0FBZ0Msa0RBQU87QUFDdkM7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNPLHFDQUFxQztBQUM1QztBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsaUJBQWlCO0FBQ2pCO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxLQUFLO0FBQ0wsQ0FBQzs7Ozs7Ozs7Ozs7Ozs7OztBQ3hFRCxnQ0FBZ0M7QUFDaEM7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLG9CQUFvQixnQkFBZ0I7QUFDcEM7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNPO0FBQ1A7QUFDQTtBQUNBO0FBQ087QUFDUDtBQUNBOzs7Ozs7Ozs7Ozs7Ozs7QUNqQkE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxhQUFhO0FBQ2I7QUFDQTtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQSxpRUFBZSxXQUFXLEVBQUM7Ozs7Ozs7Ozs7Ozs7Ozs7O1VDckMzQjtVQUNBOztVQUVBO1VBQ0E7VUFDQTtVQUNBO1VBQ0E7VUFDQTtVQUNBO1VBQ0E7VUFDQTtVQUNBO1VBQ0E7VUFDQTtVQUNBOztVQUVBO1VBQ0E7O1VBRUE7VUFDQTtVQUNBOzs7OztXQ3RCQTtXQUNBO1dBQ0E7V0FDQTtXQUNBLHlDQUF5Qyx3Q0FBd0M7V0FDakY7V0FDQTtXQUNBOzs7OztXQ1BBOzs7OztXQ0FBO1dBQ0E7V0FDQTtXQUNBLHVEQUF1RCxpQkFBaUI7V0FDeEU7V0FDQSxnREFBZ0QsYUFBYTtXQUM3RDs7Ozs7Ozs7Ozs7Ozs7QUNObUQ7QUFDd0I7QUFDSztBQUNoRiw4QkFBOEIsc0VBQUk7QUFDbEMsMkNBQTJDLDJFQUFPO0FBQ2xELDJDQUEyQywyRUFBTztBQUNsRCxpQ0FBaUMsNEVBQWE7QUFDOUMsK0JBQStCLDBFQUFXIiwic291cmNlcyI6WyJ3ZWJwYWNrOi8vLy4vY29tcG9uZW50cy9TZXJ2aWNlV29ya2VyL2luc3RhbGwudHMiLCJ3ZWJwYWNrOi8vLy4vY29tcG9uZW50cy9TZXJ2aWNlV29ya2VyL25vdGlmaWNhdGlvbi50cyIsIndlYnBhY2s6Ly8vLi9jb21wb25lbnRzL1NlcnZpY2VXb3JrZXIvcHVzaC50cyIsIndlYnBhY2s6Ly8vd2VicGFjay9ib290c3RyYXAiLCJ3ZWJwYWNrOi8vL3dlYnBhY2svcnVudGltZS9kZWZpbmUgcHJvcGVydHkgZ2V0dGVycyIsIndlYnBhY2s6Ly8vd2VicGFjay9ydW50aW1lL2hhc093blByb3BlcnR5IHNob3J0aGFuZCIsIndlYnBhY2s6Ly8vd2VicGFjay9ydW50aW1lL21ha2UgbmFtZXNwYWNlIG9iamVjdCIsIndlYnBhY2s6Ly8vLi9zZXJ2aWNlLXdvcmtlci50cyJdLCJzb3VyY2VzQ29udGVudCI6WyJ2YXIgX19hd2FpdGVyID0gKHRoaXMgJiYgdGhpcy5fX2F3YWl0ZXIpIHx8IGZ1bmN0aW9uICh0aGlzQXJnLCBfYXJndW1lbnRzLCBQLCBnZW5lcmF0b3IpIHtcbiAgICBmdW5jdGlvbiBhZG9wdCh2YWx1ZSkgeyByZXR1cm4gdmFsdWUgaW5zdGFuY2VvZiBQID8gdmFsdWUgOiBuZXcgUChmdW5jdGlvbiAocmVzb2x2ZSkgeyByZXNvbHZlKHZhbHVlKTsgfSk7IH1cbiAgICByZXR1cm4gbmV3IChQIHx8IChQID0gUHJvbWlzZSkpKGZ1bmN0aW9uIChyZXNvbHZlLCByZWplY3QpIHtcbiAgICAgICAgZnVuY3Rpb24gZnVsZmlsbGVkKHZhbHVlKSB7IHRyeSB7IHN0ZXAoZ2VuZXJhdG9yLm5leHQodmFsdWUpKTsgfSBjYXRjaCAoZSkgeyByZWplY3QoZSk7IH0gfVxuICAgICAgICBmdW5jdGlvbiByZWplY3RlZCh2YWx1ZSkgeyB0cnkgeyBzdGVwKGdlbmVyYXRvcltcInRocm93XCJdKHZhbHVlKSk7IH0gY2F0Y2ggKGUpIHsgcmVqZWN0KGUpOyB9IH1cbiAgICAgICAgZnVuY3Rpb24gc3RlcChyZXN1bHQpIHsgcmVzdWx0LmRvbmUgPyByZXNvbHZlKHJlc3VsdC52YWx1ZSkgOiBhZG9wdChyZXN1bHQudmFsdWUpLnRoZW4oZnVsZmlsbGVkLCByZWplY3RlZCk7IH1cbiAgICAgICAgc3RlcCgoZ2VuZXJhdG9yID0gZ2VuZXJhdG9yLmFwcGx5KHRoaXNBcmcsIF9hcmd1bWVudHMgfHwgW10pKS5uZXh0KCkpO1xuICAgIH0pO1xufTtcbnZhciBfX2dlbmVyYXRvciA9ICh0aGlzICYmIHRoaXMuX19nZW5lcmF0b3IpIHx8IGZ1bmN0aW9uICh0aGlzQXJnLCBib2R5KSB7XG4gICAgdmFyIF8gPSB7IGxhYmVsOiAwLCBzZW50OiBmdW5jdGlvbigpIHsgaWYgKHRbMF0gJiAxKSB0aHJvdyB0WzFdOyByZXR1cm4gdFsxXTsgfSwgdHJ5czogW10sIG9wczogW10gfSwgZiwgeSwgdCwgZztcbiAgICByZXR1cm4gZyA9IHsgbmV4dDogdmVyYigwKSwgXCJ0aHJvd1wiOiB2ZXJiKDEpLCBcInJldHVyblwiOiB2ZXJiKDIpIH0sIHR5cGVvZiBTeW1ib2wgPT09IFwiZnVuY3Rpb25cIiAmJiAoZ1tTeW1ib2wuaXRlcmF0b3JdID0gZnVuY3Rpb24oKSB7IHJldHVybiB0aGlzOyB9KSwgZztcbiAgICBmdW5jdGlvbiB2ZXJiKG4pIHsgcmV0dXJuIGZ1bmN0aW9uICh2KSB7IHJldHVybiBzdGVwKFtuLCB2XSk7IH07IH1cbiAgICBmdW5jdGlvbiBzdGVwKG9wKSB7XG4gICAgICAgIGlmIChmKSB0aHJvdyBuZXcgVHlwZUVycm9yKFwiR2VuZXJhdG9yIGlzIGFscmVhZHkgZXhlY3V0aW5nLlwiKTtcbiAgICAgICAgd2hpbGUgKF8pIHRyeSB7XG4gICAgICAgICAgICBpZiAoZiA9IDEsIHkgJiYgKHQgPSBvcFswXSAmIDIgPyB5W1wicmV0dXJuXCJdIDogb3BbMF0gPyB5W1widGhyb3dcIl0gfHwgKCh0ID0geVtcInJldHVyblwiXSkgJiYgdC5jYWxsKHkpLCAwKSA6IHkubmV4dCkgJiYgISh0ID0gdC5jYWxsKHksIG9wWzFdKSkuZG9uZSkgcmV0dXJuIHQ7XG4gICAgICAgICAgICBpZiAoeSA9IDAsIHQpIG9wID0gW29wWzBdICYgMiwgdC52YWx1ZV07XG4gICAgICAgICAgICBzd2l0Y2ggKG9wWzBdKSB7XG4gICAgICAgICAgICAgICAgY2FzZSAwOiBjYXNlIDE6IHQgPSBvcDsgYnJlYWs7XG4gICAgICAgICAgICAgICAgY2FzZSA0OiBfLmxhYmVsKys7IHJldHVybiB7IHZhbHVlOiBvcFsxXSwgZG9uZTogZmFsc2UgfTtcbiAgICAgICAgICAgICAgICBjYXNlIDU6IF8ubGFiZWwrKzsgeSA9IG9wWzFdOyBvcCA9IFswXTsgY29udGludWU7XG4gICAgICAgICAgICAgICAgY2FzZSA3OiBvcCA9IF8ub3BzLnBvcCgpOyBfLnRyeXMucG9wKCk7IGNvbnRpbnVlO1xuICAgICAgICAgICAgICAgIGRlZmF1bHQ6XG4gICAgICAgICAgICAgICAgICAgIGlmICghKHQgPSBfLnRyeXMsIHQgPSB0Lmxlbmd0aCA+IDAgJiYgdFt0Lmxlbmd0aCAtIDFdKSAmJiAob3BbMF0gPT09IDYgfHwgb3BbMF0gPT09IDIpKSB7IF8gPSAwOyBjb250aW51ZTsgfVxuICAgICAgICAgICAgICAgICAgICBpZiAob3BbMF0gPT09IDMgJiYgKCF0IHx8IChvcFsxXSA+IHRbMF0gJiYgb3BbMV0gPCB0WzNdKSkpIHsgXy5sYWJlbCA9IG9wWzFdOyBicmVhazsgfVxuICAgICAgICAgICAgICAgICAgICBpZiAob3BbMF0gPT09IDYgJiYgXy5sYWJlbCA8IHRbMV0pIHsgXy5sYWJlbCA9IHRbMV07IHQgPSBvcDsgYnJlYWs7IH1cbiAgICAgICAgICAgICAgICAgICAgaWYgKHQgJiYgXy5sYWJlbCA8IHRbMl0pIHsgXy5sYWJlbCA9IHRbMl07IF8ub3BzLnB1c2gob3ApOyBicmVhazsgfVxuICAgICAgICAgICAgICAgICAgICBpZiAodFsyXSkgXy5vcHMucG9wKCk7XG4gICAgICAgICAgICAgICAgICAgIF8udHJ5cy5wb3AoKTsgY29udGludWU7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBvcCA9IGJvZHkuY2FsbCh0aGlzQXJnLCBfKTtcbiAgICAgICAgfSBjYXRjaCAoZSkgeyBvcCA9IFs2LCBlXTsgeSA9IDA7IH0gZmluYWxseSB7IGYgPSB0ID0gMDsgfVxuICAgICAgICBpZiAob3BbMF0gJiA1KSB0aHJvdyBvcFsxXTsgcmV0dXJuIHsgdmFsdWU6IG9wWzBdID8gb3BbMV0gOiB2b2lkIDAsIGRvbmU6IHRydWUgfTtcbiAgICB9XG59O1xuaW1wb3J0IHsgdmVyc2lvbiB9IGZyb20gJy4uLy4uLy4uLy4uL3BhY2thZ2UuanNvbic7XG52YXIgX29mZmxpbmUgPSAnL3dwLWNvbnRlbnQvcGx1Z2lucy93cC1wd2EtcmVnaXN0ZXIvb2ZmbGluZS5odG1sJztcbmV4cG9ydCB2YXIgaGFuZGxlSW5zdGFsbCA9IGZ1bmN0aW9uIChldmVudCkge1xuICAgIGV2ZW50LndhaXRVbnRpbChjYWNoZXMub3Blbih2ZXJzaW9uKVxuICAgICAgICAudGhlbihmdW5jdGlvbiAoY2FjaGUpIHtcbiAgICAgICAgY2FjaGUuYWRkKF9vZmZsaW5lKTtcbiAgICB9KSk7XG59O1xuZXhwb3J0IHZhciBoYW5kbGVGZXRjaCA9IGZ1bmN0aW9uIChldmVudCkgeyByZXR1cm4gX19hd2FpdGVyKHZvaWQgMCwgdm9pZCAwLCB2b2lkIDAsIGZ1bmN0aW9uICgpIHtcbiAgICB2YXIgdXJsLCBtYXRjaGVkLCBmYWxsYmFjaztcbiAgICByZXR1cm4gX19nZW5lcmF0b3IodGhpcywgZnVuY3Rpb24gKF9hKSB7XG4gICAgICAgIHN3aXRjaCAoX2EubGFiZWwpIHtcbiAgICAgICAgICAgIGNhc2UgMDpcbiAgICAgICAgICAgICAgICB1cmwgPSBuZXcgVVJMKGV2ZW50LnJlcXVlc3QudXJsKTtcbiAgICAgICAgICAgICAgICBpZiAoISh1cmwucGF0aG5hbWUgPT09ICcvJykpIHJldHVybiBbMyAvKmJyZWFrKi8sIDRdO1xuICAgICAgICAgICAgICAgIGlmICghIW5hdmlnYXRvci5vbkxpbmUpIHJldHVybiBbMyAvKmJyZWFrKi8sIDJdO1xuICAgICAgICAgICAgICAgIHJldHVybiBbNCAvKnlpZWxkKi8sIGNhY2hlcy5tYXRjaChfb2ZmbGluZSldO1xuICAgICAgICAgICAgY2FzZSAxOlxuICAgICAgICAgICAgICAgIG1hdGNoZWQgPSBfYS5zZW50KCk7XG4gICAgICAgICAgICAgICAgaWYgKG1hdGNoZWQpIHtcbiAgICAgICAgICAgICAgICAgICAgZXZlbnQucmVzcG9uZFdpdGgobWF0Y2hlZCk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIHJldHVybiBbMyAvKmJyZWFrKi8sIDRdO1xuICAgICAgICAgICAgY2FzZSAyOiByZXR1cm4gWzQgLyp5aWVsZCovLCBmZXRjaChldmVudC5yZXF1ZXN0KVxuICAgICAgICAgICAgICAgICAgICAuY2F0Y2goZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICByZXR1cm4gY2FjaGVzLm1hdGNoKF9vZmZsaW5lKTtcbiAgICAgICAgICAgICAgICB9KV07XG4gICAgICAgICAgICBjYXNlIDM6XG4gICAgICAgICAgICAgICAgZmFsbGJhY2sgPSBfYS5zZW50KCk7XG4gICAgICAgICAgICAgICAgaWYgKGZhbGxiYWNrKSB7XG4gICAgICAgICAgICAgICAgICAgIGV2ZW50LnJlc3BvbmRXaXRoKGZhbGxiYWNrKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgX2EubGFiZWwgPSA0O1xuICAgICAgICAgICAgY2FzZSA0OiByZXR1cm4gWzIgLypyZXR1cm4qL107XG4gICAgICAgIH1cbiAgICB9KTtcbn0pOyB9O1xuIiwidmFyIGNvbW1vbiA9IGZ1bmN0aW9uIChldmVudCkgeyByZXR1cm4gZnVuY3Rpb24gKGNsaWVudHMpIHtcbiAgICB2YXIgdXJsID0gJy8nO1xuICAgIGlmIChldmVudC5ub3RpZmljYXRpb24uZGF0YS51cmwpIHtcbiAgICAgICAgdXJsID0gZXZlbnQubm90aWZpY2F0aW9uLmRhdGEudXJsO1xuICAgIH1cbiAgICBldmVudC53YWl0VW50aWwoY2xpZW50c1xuICAgICAgICAubWF0Y2hBbGwoeyB0eXBlOiAnd2luZG93JyB9KVxuICAgICAgICAudGhlbihmdW5jdGlvbiAoKSB7XG4gICAgICAgIHJldHVybiBjbGllbnRzLm9wZW5XaW5kb3codXJsKTtcbiAgICB9KSk7XG59OyB9O1xuZXhwb3J0IHZhciBvbkNsaWNrID0gZnVuY3Rpb24gKGV2ZW50KSB7XG4gICAgZXZlbnQubm90aWZpY2F0aW9uLmNsb3NlKCk7XG4gICAgY29tbW9uKGV2ZW50KShjbGllbnRzKTtcbn07XG5leHBvcnQgdmFyIG9uQ2xvc2UgPSBmdW5jdGlvbiAoZXZlbnQpIHtcbiAgICBjb21tb24oZXZlbnQpKGNsaWVudHMpO1xufTtcbiIsInZhciBwdXNoSGFuZGxlciA9IGZ1bmN0aW9uIChldmVudCkge1xuICAgIHZhciBfYTtcbiAgICB2YXIgZW5kcG9pbnQgPSBbJy93cC1qc29uL3dwL3YyL3B3YV9ub3RpZmljYXRpb25zJ107XG4gICAgdHJ5IHtcbiAgICAgICAgdmFyIGRhdGEgPSAoX2EgPSBldmVudC5kYXRhKSA9PT0gbnVsbCB8fCBfYSA9PT0gdm9pZCAwID8gdm9pZCAwIDogX2EuanNvbigpO1xuICAgICAgICBpZiAoZGF0YS5kYXRhICYmIGRhdGEuZGF0YS5wb3N0X2lkKSB7XG4gICAgICAgICAgICBlbmRwb2ludC5wdXNoKGRhdGEuZGF0YS5wb3N0X2lkKTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIGlmIChkYXRhLnBvc3RfaWQpIHtcbiAgICAgICAgICAgIGVuZHBvaW50LnB1c2goZGF0YS5wb3N0X2lkKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBjYXRjaCAoZSkge1xuICAgICAgICBjb25zb2xlLndhcm4oZSk7XG4gICAgfVxuICAgIGV2ZW50LndhaXRVbnRpbChmZXRjaChlbmRwb2ludC5qb2luKCcvJykpXG4gICAgICAgIC50aGVuKGZ1bmN0aW9uIChyZXNwb25zZSkge1xuICAgICAgICBpZiAocmVzcG9uc2Uub2spIHtcbiAgICAgICAgICAgIHJldHVybiByZXNwb25zZS5qc29uKCk7XG4gICAgICAgIH1cbiAgICAgICAgdGhyb3cgbmV3IEVycm9yKCdub3RpZmljYXRpb25zIGFwaSByZXNwb25zZSBlcnJvcicpO1xuICAgIH0pXG4gICAgICAgIC50aGVuKGZ1bmN0aW9uIChqc29uKSB7XG4gICAgICAgIHZhciB0aXRsZSA9IGpzb24ucG9zdF9tZXRhLmhlYWRsaW5lID8ganNvbi5wb3N0X21ldGEuaGVhZGxpbmUgOiAnPD9waHAgZWNobyAkdGl0bGUgPz4nO1xuICAgICAgICB2YXIgaWNvbiA9IGpzb24ucG9zdF9tZXRhLmljb24gPyBqc29uLnBvc3RfbWV0YS5pY29uIDogJzw/cGhwIGVjaG8gJGljb24gPz4nO1xuICAgICAgICB2YXIgb3B0cyA9IHtcbiAgICAgICAgICAgIGljb246IGljb24sXG4gICAgICAgICAgICBib2R5OiBqc29uLnRpdGxlLnJlbmRlcmVkLFxuICAgICAgICAgICAgZGF0YToge1xuICAgICAgICAgICAgICAgIHVybDoganNvbi5wb3N0X21ldGEubGlua1xuICAgICAgICAgICAgfSxcbiAgICAgICAgICAgIHZpYnJhdGU6IFsyMDAsIDEwMCwgMjAwLCAxMDAsIDIwMCwgMTAwLCAyMDBdXG4gICAgICAgIH07XG4gICAgICAgIHJldHVybiBzZWxmLnJlZ2lzdHJhdGlvbi5zaG93Tm90aWZpY2F0aW9uKHRpdGxlLCBvcHRzKTtcbiAgICB9KVxuICAgICAgICAuY2F0Y2goY29uc29sZS53YXJuKSk7XG59O1xuZXhwb3J0IGRlZmF1bHQgcHVzaEhhbmRsZXI7XG4iLCIvLyBUaGUgbW9kdWxlIGNhY2hlXG52YXIgX193ZWJwYWNrX21vZHVsZV9jYWNoZV9fID0ge307XG5cbi8vIFRoZSByZXF1aXJlIGZ1bmN0aW9uXG5mdW5jdGlvbiBfX3dlYnBhY2tfcmVxdWlyZV9fKG1vZHVsZUlkKSB7XG5cdC8vIENoZWNrIGlmIG1vZHVsZSBpcyBpbiBjYWNoZVxuXHR2YXIgY2FjaGVkTW9kdWxlID0gX193ZWJwYWNrX21vZHVsZV9jYWNoZV9fW21vZHVsZUlkXTtcblx0aWYgKGNhY2hlZE1vZHVsZSAhPT0gdW5kZWZpbmVkKSB7XG5cdFx0cmV0dXJuIGNhY2hlZE1vZHVsZS5leHBvcnRzO1xuXHR9XG5cdC8vIENyZWF0ZSBhIG5ldyBtb2R1bGUgKGFuZCBwdXQgaXQgaW50byB0aGUgY2FjaGUpXG5cdHZhciBtb2R1bGUgPSBfX3dlYnBhY2tfbW9kdWxlX2NhY2hlX19bbW9kdWxlSWRdID0ge1xuXHRcdC8vIG5vIG1vZHVsZS5pZCBuZWVkZWRcblx0XHQvLyBubyBtb2R1bGUubG9hZGVkIG5lZWRlZFxuXHRcdGV4cG9ydHM6IHt9XG5cdH07XG5cblx0Ly8gRXhlY3V0ZSB0aGUgbW9kdWxlIGZ1bmN0aW9uXG5cdF9fd2VicGFja19tb2R1bGVzX19bbW9kdWxlSWRdKG1vZHVsZSwgbW9kdWxlLmV4cG9ydHMsIF9fd2VicGFja19yZXF1aXJlX18pO1xuXG5cdC8vIFJldHVybiB0aGUgZXhwb3J0cyBvZiB0aGUgbW9kdWxlXG5cdHJldHVybiBtb2R1bGUuZXhwb3J0cztcbn1cblxuIiwiLy8gZGVmaW5lIGdldHRlciBmdW5jdGlvbnMgZm9yIGhhcm1vbnkgZXhwb3J0c1xuX193ZWJwYWNrX3JlcXVpcmVfXy5kID0gKGV4cG9ydHMsIGRlZmluaXRpb24pID0+IHtcblx0Zm9yKHZhciBrZXkgaW4gZGVmaW5pdGlvbikge1xuXHRcdGlmKF9fd2VicGFja19yZXF1aXJlX18ubyhkZWZpbml0aW9uLCBrZXkpICYmICFfX3dlYnBhY2tfcmVxdWlyZV9fLm8oZXhwb3J0cywga2V5KSkge1xuXHRcdFx0T2JqZWN0LmRlZmluZVByb3BlcnR5KGV4cG9ydHMsIGtleSwgeyBlbnVtZXJhYmxlOiB0cnVlLCBnZXQ6IGRlZmluaXRpb25ba2V5XSB9KTtcblx0XHR9XG5cdH1cbn07IiwiX193ZWJwYWNrX3JlcXVpcmVfXy5vID0gKG9iaiwgcHJvcCkgPT4gKE9iamVjdC5wcm90b3R5cGUuaGFzT3duUHJvcGVydHkuY2FsbChvYmosIHByb3ApKSIsIi8vIGRlZmluZSBfX2VzTW9kdWxlIG9uIGV4cG9ydHNcbl9fd2VicGFja19yZXF1aXJlX18uciA9IChleHBvcnRzKSA9PiB7XG5cdGlmKHR5cGVvZiBTeW1ib2wgIT09ICd1bmRlZmluZWQnICYmIFN5bWJvbC50b1N0cmluZ1RhZykge1xuXHRcdE9iamVjdC5kZWZpbmVQcm9wZXJ0eShleHBvcnRzLCBTeW1ib2wudG9TdHJpbmdUYWcsIHsgdmFsdWU6ICdNb2R1bGUnIH0pO1xuXHR9XG5cdE9iamVjdC5kZWZpbmVQcm9wZXJ0eShleHBvcnRzLCAnX19lc01vZHVsZScsIHsgdmFsdWU6IHRydWUgfSk7XG59OyIsImltcG9ydCBwdXNoIGZyb20gJy4vY29tcG9uZW50cy9TZXJ2aWNlV29ya2VyL3B1c2gnO1xuaW1wb3J0IHsgb25DbGljaywgb25DbG9zZSB9IGZyb20gJy4vY29tcG9uZW50cy9TZXJ2aWNlV29ya2VyL25vdGlmaWNhdGlvbic7XG5pbXBvcnQgeyBoYW5kbGVGZXRjaCwgaGFuZGxlSW5zdGFsbCB9IGZyb20gJy4vY29tcG9uZW50cy9TZXJ2aWNlV29ya2VyL2luc3RhbGwnO1xuc2VsZi5hZGRFdmVudExpc3RlbmVyKCdwdXNoJywgcHVzaCk7XG5zZWxmLmFkZEV2ZW50TGlzdGVuZXIoJ25vdGlmaWNhdGlvbmNsaWNrJywgb25DbGljayk7XG5zZWxmLmFkZEV2ZW50TGlzdGVuZXIoJ25vdGlmaWNhdGlvbmNsb3NlJywgb25DbG9zZSk7XG5zZWxmLmFkZEV2ZW50TGlzdGVuZXIoJ2luc3RhbGwnLCBoYW5kbGVJbnN0YWxsKTtcbnNlbGYuYWRkRXZlbnRMaXN0ZW5lcignZmV0Y2gnLCBoYW5kbGVGZXRjaCk7XG4iXSwibmFtZXMiOltdLCJzb3VyY2VSb290IjoiIn0=