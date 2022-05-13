(()=>{"use strict";var t=function(t){return function(n){var e="/";t.notification.data.url&&(e=t.notification.data.url),t.waitUntil(n.matchAll({type:"window"}).then((function(){return n.openWindow(e)})))}};var n="/wp-content/plugins/wp-pwa-register/offline.html";self.addEventListener("push",(function(t){var n,e=["/wp-json/wp/v2/pwa_notifications"];try{var i=null===(n=t.data)||void 0===n?void 0:n.json();i.data&&i.data.post_id?e.push(i.data.post_id):i.post_id&&e.push(i.post_id)}catch(t){console.warn(t)}t.waitUntil(fetch(e.join("/")).then((function(t){if(t.ok)return t.json();throw new Error("notifications api response error")})).then((function(t){var n=t.post_meta.headline?t.post_meta.headline:"<?php echo $title ?>",e={icon:t.post_meta.icon?t.post_meta.icon:"<?php echo $icon ?>",body:t.title.rendered,data:{url:t.post_meta.link},vibrate:[200,100,200,100,200,100,200]};return self.registration.showNotification(n,e)})).catch(console.warn))})),self.addEventListener("notificationclick",(function(n){n.notification.close(),t(n)(clients)})),self.addEventListener("notificationclose",(function(n){t(n)(clients)})),self.addEventListener("install",(function(t){t.waitUntil(caches.open("1.3.7").then((function(t){t.add(n)})))})),self.addEventListener("fetch",(function(t){return e=void 0,i=void 0,r=function(){var e,i;return function(t,n){var e,i,o,r,a={label:0,sent:function(){if(1&o[0])throw o[1];return o[1]},trys:[],ops:[]};return r={next:c(0),throw:c(1),return:c(2)},"function"==typeof Symbol&&(r[Symbol.iterator]=function(){return this}),r;function c(r){return function(c){return function(r){if(e)throw new TypeError("Generator is already executing.");for(;a;)try{if(e=1,i&&(o=2&r[0]?i.return:r[0]?i.throw||((o=i.return)&&o.call(i),0):i.next)&&!(o=o.call(i,r[1])).done)return o;switch(i=0,o&&(r=[2&r[0],o.value]),r[0]){case 0:case 1:o=r;break;case 4:return a.label++,{value:r[1],done:!1};case 5:a.label++,i=r[1],r=[0];continue;case 7:r=a.ops.pop(),a.trys.pop();continue;default:if(!((o=(o=a.trys).length>0&&o[o.length-1])||6!==r[0]&&2!==r[0])){a=0;continue}if(3===r[0]&&(!o||r[1]>o[0]&&r[1]<o[3])){a.label=r[1];break}if(6===r[0]&&a.label<o[1]){a.label=o[1],o=r;break}if(o&&a.label<o[2]){a.label=o[2],a.ops.push(r);break}o[2]&&a.ops.pop(),a.trys.pop();continue}r=n.call(t,a)}catch(t){r=[6,t],i=0}finally{e=o=0}if(5&r[0])throw r[1];return{value:r[0]?r[1]:void 0,done:!0}}([r,c])}}}(this,(function(o){switch(o.label){case 0:return"/"!==new URL(t.request.url).pathname?[3,4]:navigator.onLine?[3,2]:[4,caches.match(n)];case 1:return(e=o.sent())&&t.respondWith(e),[3,4];case 2:return[4,fetch(t.request).catch((function(){return caches.match(n)}))];case 3:(i=o.sent())&&t.respondWith(i),o.label=4;case 4:return[2]}}))},new((o=void 0)||(o=Promise))((function(t,n){function a(t){try{s(r.next(t))}catch(t){n(t)}}function c(t){try{s(r.throw(t))}catch(t){n(t)}}function s(n){var e;n.done?t(n.value):(e=n.value,e instanceof o?e:new o((function(t){t(e)}))).then(a,c)}s((r=r.apply(e,i||[])).next())}));var e,i,o,r}))})();