declare const clients: Clients

const common = (event: NotificationEvent) => (clients: Clients) => {
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

export const onClick = (event: NotificationEvent) => {
    event.notification.close()
    common(event)(clients)
}

export const onClose = (event: NotificationEvent) => {
    common(event)(clients)
}