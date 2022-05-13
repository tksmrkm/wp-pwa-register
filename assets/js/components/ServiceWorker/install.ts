import pkg from '../../../../package.json'

declare const caches: CacheStorage

const _offline = '/wp-content/plugins/wp-pwa-register/offline.html'

export const handleInstall = (event: ExtendableEvent) => {
    event.waitUntil(
        caches.open(pkg.version)
            .then(cache => {
                cache.add(_offline)
            })
    )
}

export const handleFetch = async (event: FetchEvent) => {
    const url = new URL(event.request.url)

    if (url.pathname === '/') {
        if (!navigator.onLine) {
            const matched = await caches.match(_offline)

            if (matched) {
                event.respondWith(
                    matched
                )
            }
        } else {
            const fallback = await fetch(event.request)
            .catch(() => {
                return caches.match(_offline)
            })
            if (fallback) {
                event.respondWith(
                    fallback
                )
            }
        }
    }
}
