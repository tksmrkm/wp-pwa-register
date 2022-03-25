import { version } from '../../../../package.json'

const _offline = '/wp-content/plugins/wp-pwa-register/offline.html'

export const handleInstall = event => {
    event.waitUntil(
        caches.open(version)
            .then(cache => {
                cache.add(_offline)
            })
    )
}

export const handleFetch = event => {
    const url = new URL(event.request.url)

    if (url.pathname === '/') {
        if (!navigator.onLine) {
            event.respondWith(caches.match(_offline))
        } else {

            event.respondWith(
                fetch(event.request)
                .catch(e => {
                    return caches.match(_offline)
                })
            )
        }
    }
}
