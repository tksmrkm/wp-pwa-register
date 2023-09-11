import { isSupported } from 'firebase/messaging';
import handleShow from './show';
import handleRequest from './request';
import { handleRegisterSuccess } from './register';

const handleLoad = async () => {
    const supported = await isSupported()

    if (!supported) {
        // not support this browser
        return false
    }

    if (!WP_REGISTER_SERVICE_WORKER.register.useDialog) {
        /**
         * Legacy method
         * all users start registration onLoad
         */
        handleRequest()
        return false
    }

    if (Notification.permission === 'granted') {
        // granted browser renew tokens
        const registration = await navigator.serviceWorker.getRegistration()
        if (registration) {
            handleRegisterSuccess(registration)
        }
        return false
    }

    if (Notification.permission === 'denied') {
        // denied browser do nothing
        // we can show banners for re-subscribing
        return false
    }

    handleShow()
}

export default handleLoad
