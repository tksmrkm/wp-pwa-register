const isSupported = async () => {
    if (!('Notification' in window)) {
        console.warn('no Notification in global')
        return false
    }

    if (!('serviceWorker' in navigator)) {
        console.warn('no service worker in navigator')
        return false
    }

    try {
        const sw = await navigator.serviceWorker.ready

        if (!('pushManager' in sw)) {
            console.warn('no pushManager in serviceworker')
            return false
        }

        return true
    } catch (error) {
        console.warn(error)
        return false
    }
}

export default isSupported