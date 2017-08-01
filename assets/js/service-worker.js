self.addEventListener('push', event => {
    const endpoint = ['/wp-json/wp/v2/pwa_notifications'];

    try {
        const data = event.data.json();

        if (data.data && data.data.post_id) {
            endpoint.push(data.data.post_id);
        }
    } catch (e) {
        console.warn(e)
    }

    event.waitUntil(
        fetch(endpoint.join('/'))
        .then(function(response) {
            if (response.status === 200) {
                return response.json();
            }
            throw new Error('notifications api response error');
        })
        .then(function(json) {
            if (typeof json.length === 'undefined') {
                const dat = json;
            }
            const dat = typeof json.length === 'undefined' ? json: json.shift();
            const title = dat.post_meta.headline ? dat.post_meta.headline: '<?php echo $title; ?>';
            const icon = dat.post_meta.icon ? dat.post_meta.icon: '<?php echo $icon ?>';
            const opts = {
                icon: icon,
                body: dat.title.rendered,
                data: {
                    url: dat.post_meta.link
                },
                vibrate: [200, 100, 200, 100, 200, 100, 200]
            };

            // send push
            return self.registration.showNotification(title, opts);
        })
    );
});

self.addEventListener('notificationclick', event => {
    event.notification.close();

    let url = '/';

    if (event.notification.data.url) {
        url = event.notification.data.url;
    }

    event.waitUntil(
        clients
        .matchAll({type: 'window'})
        .then(function() {
            if (clients.openWindow) {
                return clients.openWindow(url);
            }

            return;
        })
    );
});
