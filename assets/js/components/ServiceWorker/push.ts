declare const self: ServiceWorkerGlobalScope

const pushHandler = (event: PushEvent) => {
    const endpoint = ['/wp-json/wp/v2/pwa_notifications']
    const _title = '<?php  echo $title?>'
    const _icon = '<?php echo $icon ?>'

    try {
        const data = event.data?.json()

        /**
         * push version2
         */
        if (data.v2) {
            // fetchさせず直接ペイロードを送信
            return event.waitUntil(
                self.registration.showNotification(data.v2.headline ?? _title, {
                    icon: data.v2.icon ?? _icon,
                    body: data.v2.title,
                    data: {
                        url: data.v2.link
                    },
                    vibrate: [200, 100, 200, 100, 200, 100, 200]
                })
            )
        }

        if (data.data && data.data.post_id) {
            endpoint.push(data.data.post_id)
        } else if (data.post_id) {
            endpoint.push(data.post_id)
        }
    } catch (e) {
        console.warn(e)
    }

    event.waitUntil(
        fetch(endpoint.join('/'))
        .then(response => {
            if (response.ok) {
                return response.json()
            }

            throw new Error('notifications api response error')
        })
        .then(json => {
            const title = json.post_meta.headline ? json.post_meta.headline: _title
            const icon = json.post_meta.icon ? json.post_meta.icon: _icon
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

export default pushHandler