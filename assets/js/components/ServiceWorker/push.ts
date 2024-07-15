declare const self: ServiceWorkerGlobalScope

type LegacyData = {
    version: 1;
    post_id: string;
}

type HttpV1Data = {
    version: 2;
    icon?: string;
    link: string;
}

// expect data type
type DataFormat = {
    data: LegacyData | HttpV1Data;
    fcmMessageId: string;
    from: string;
    notification: {
        title: string;
        body: string;
    };
    priority: string;
}

const pushHandler = (event: PushEvent) => {
    const json = event.data?.json() as DataFormat
    const _title = '<?php echo $title ?>'
    const _icon = '<?php echo $icon ?>'

    if (json.data.version === 2) {
        // HTTP v1 API
        event.waitUntil(
            self.registration.showNotification(json.notification.title ?? _title, {
                icon: json.data.icon ?? _icon,
                body: json.notification.body,
                data: {
                    url: json.data.link
                },
            })
        )
    } else {
        const endpoint = ['/wp-json/wp/v2/pwa_notifications', json.data.post_id]

        event.waitUntil(
            fetch(endpoint.join('/'))
            .then(response => {
                if (response.ok) {
                    return response.json()
                }

                throw new Error('notifications api response error')
            })
            .then(json => {
                const title = json.post_meta.headline ?? _title
                const icon = json.post_meta.icon ?? _icon
                const opts = {
                    icon,
                    body: json.title.rendered,
                    data: {
                        url: json.post_meta.link
                    },
                    vibrate: [200, 100, 200, 100, 200, 100, 200]
                }

                return self.registration.showNotification(title, opts)
            })
            .catch(console.warn)
        )
    }
}

export default pushHandler