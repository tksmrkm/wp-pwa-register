export default event => {
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
}
