const common = event => clients => {
    let url = '/'

    if (event.notification.data.url) {
        url = event.notification.data.url
    }

    event.waitUntil(
        clients
        .matchAll({ type: 'window' })
        .then(() => {
            return clients.openWindow(url)
        })
    )
}

export const onClick = event => {
    event.notification.close()
    common(event)(clients)
}

export const onClose = event => {
    common(event)(clients)
}