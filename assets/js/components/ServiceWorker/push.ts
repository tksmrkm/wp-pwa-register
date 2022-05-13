declare const self: ServiceWorkerGlobalScope

const pushHandler = (event: PushEvent) => {
    const endpoint = ['/wp-json/wp/v2/pwa_notifications']

    try {
        const data = event.data?.json()

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
            const title = json.post_meta.headline ? json.post_meta.headline: '<?php echo $title ?>'
            const icon = json.post_meta.icon ? json.post_meta.icon: '<?php echo $icon ?>'
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