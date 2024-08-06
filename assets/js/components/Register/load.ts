import { isSupported } from 'firebase/messaging';
import handleShow from './show';
import handleRequest from './request';
import { handleRegisterSuccess } from './register';
import { getAuth, signInAnonymously } from 'firebase/auth';
import app from '~/utils/firebase';

export const expiredKey = 'tokenSubscribedDatetime'

const handleLoad = async () => {
    const supported = await isSupported()

    if (!supported) {
        // not support this browser
        return false
    }

    const { permission } = Notification

    if (permission === 'default') {
        localStorage.removeItem(expiredKey)
    }

    const expired = localStorage.getItem(expiredKey)

    if (expired) {
        const now = new Date()
        const diff = 30 * 24 * 60 * 60 * 1000 // 30days

        if (Number(expired) + diff > now.getTime()) {
            /**
             * トークンの更新は30日に一回
             * 
             * expiredがlocalStorageに記録されており
             * 30日経過していない場合はgetTokenまで進まない
             */
            return false
        }
    }

    const auth = getAuth(app)
    const user = await signInAnonymously(auth)
    .catch(console.warn)

    if (!user) {
        throw new Error('Failed to sign in anonymously')
    }

    if (!WP_REGISTER_SERVICE_WORKER.register.useDialog) {
        /**
         * Legacy method
         * all users start registration onLoad
         */
        handleRequest(user.user.uid)()
        return false
    }

    if (permission === 'granted') {
        // granted browser renew tokens
        const registration = await navigator.serviceWorker.getRegistration()
        if (registration) {
            handleRegisterSuccess(user.user.uid)(registration)
        }
        return false
    }

    if (permission === 'denied') {
        // denied browser do nothing
        // we can show banners for re-subscribing
        return false
    }

    handleShow(user.user.uid)
}

export default handleLoad
