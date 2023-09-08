import { isSupported } from 'firebase/messaging';
import handleShow from './show';
import handleRequest from './request';

const handleLoad = async () => {
    const supported = await isSupported()

    if (!supported) {
        // not support this browser
        return false
    }

    if (Notification.permission === 'granted') {
        // granted browser renew tokens
        handleRequest()
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
