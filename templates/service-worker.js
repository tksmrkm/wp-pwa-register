(()=>{"use strict";var n=function(n){return function(t){var e="/";n.notification.data.url&&(e=n.notification.data.url),n.waitUntil(t.matchAll({type:"window"}).then((function(){return t.openWindow(e)})))}};var t="/wp-content/plugins/wp-pwa-register/offline.html";self.addEventListener("push",(function(n){var t,e,i,o=null===(t=n.data)||void 0===t?void 0:t.json(),a="<?php echo $title ?>",r="<?php echo $icon ?>";if(console.log(o),"version"in o.data&&"v2"===o.data.version)n.waitUntil(self.registration.showNotification(null!==(e=o.notification.title)&&void 0!==e?e:a,{icon:null!==(i=o.data.icon)&&void 0!==i?i:r,body:o.notification.body,data:{url:o.data.link}}));else{var c=["/wp-json/wp/v2/pwa_notifications",o.data.post_id];n.waitUntil(fetch(c.join("/")).then((function(n){if(n.ok)return n.json();throw new Error("notifications api response error")})).then((function(n){var t,e,i=null!==(t=n.post_meta.headline)&&void 0!==t?t:a,o={icon:null!==(e=n.post_meta.icon)&&void 0!==e?e:r,body:n.title.rendered,data:{url:n.post_meta.link}};return self.registration.showNotification(i,o)})).catch(console.warn))}})),self.addEventListener("notificationclick",(function(t){t.notification.close(),n(t)(clients)})),self.addEventListener("notificationclose",(function(t){n(t)(clients)})),self.addEventListener("install",(function(n){n.waitUntil(caches.open("1.3.11").then((function(n){n.add(t)})))})),self.addEventListener("fetch",(function(n){return e=void 0,i=void 0,a=function(){var e,i;return function(n,t){var e,i,o,a,r={label:0,sent:function(){if(1&o[0])throw o[1];return o[1]},trys:[],ops:[]};return a={next:c(0),throw:c(1),return:c(2)},"function"==typeof Symbol&&(a[Symbol.iterator]=function(){return this}),a;function c(c){return function(l){return function(c){if(e)throw new TypeError("Generator is already executing.");for(;a&&(a=0,c[0]&&(r=0)),r;)try{if(e=1,i&&(o=2&c[0]?i.return:c[0]?i.throw||((o=i.return)&&o.call(i),0):i.next)&&!(o=o.call(i,c[1])).done)return o;switch(i=0,o&&(c=[2&c[0],o.value]),c[0]){case 0:case 1:o=c;break;case 4:return r.label++,{value:c[1],done:!1};case 5:r.label++,i=c[1],c=[0];continue;case 7:c=r.ops.pop(),r.trys.pop();continue;default:if(!((o=(o=r.trys).length>0&&o[o.length-1])||6!==c[0]&&2!==c[0])){r=0;continue}if(3===c[0]&&(!o||c[1]>o[0]&&c[1]<o[3])){r.label=c[1];break}if(6===c[0]&&r.label<o[1]){r.label=o[1],o=c;break}if(o&&r.label<o[2]){r.label=o[2],r.ops.push(c);break}o[2]&&r.ops.pop(),r.trys.pop();continue}c=t.call(n,r)}catch(n){c=[6,n],i=0}finally{e=o=0}if(5&c[0])throw c[1];return{value:c[0]?c[1]:void 0,done:!0}}([c,l])}}}(this,(function(o){switch(o.label){case 0:return"/"!==new URL(n.request.url).pathname?[3,4]:navigator.onLine?[3,2]:[4,caches.match(t)];case 1:return(e=o.sent())&&n.respondWith(e),[3,4];case 2:return[4,fetch(n.request).catch((function(){return caches.match(t)}))];case 3:(i=o.sent())&&n.respondWith(i),o.label=4;case 4:return[2]}}))},new((o=void 0)||(o=Promise))((function(n,t){function r(n){try{l(a.next(n))}catch(n){t(n)}}function c(n){try{l(a.throw(n))}catch(n){t(n)}}function l(t){var e;t.done?n(t.value):(e=t.value,e instanceof o?e:new o((function(n){n(e)}))).then(r,c)}l((a=a.apply(e,i||[])).next())}));var e,i,o,a}))})();