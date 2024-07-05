declare const self: ServiceWorkerGlobalScope

type LegacyData = {
    legacy: boolean;
    post_id: string;
}

type HttpV1Data = {
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

    // detect version
    try {
        if ('legacy' in json.data) {
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
    } catch (e) {
        console.warn(e)
    }

    if ('icon' in json.data) {
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
    }
}

export default pushHandler