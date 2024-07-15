import { getToken, getMessaging } from 'firebase/messaging'
import { signInAnonymously, getAuth } from 'firebase/auth'
import app from '~/utils/firebase'
import { expiredKey } from './load'

export const handleRegisterSuccess = async (serviceWorkerRegistration: ServiceWorkerRegistration) => {
    const messaging = getMessaging(app)
    const token = await getToken(messaging, {
        serviceWorkerRegistration
    })
    .catch(console.warn)

    if (!token) {
        throw new Error('Token is not found')
    }

    const auth = getAuth(app)
    const user = await signInAnonymously(auth)
    .catch(console.warn)

    if (!user) {
        throw new Error('can not find user')
    }

    // user.user.uid
    // token
    const subscribeBody = new FormData()
    subscribeBody.append('uid', user.user.uid)
    subscribeBody.append('token', token)

    await fetch('/pwa-subscribe', {
        method: 'POST',
        body: subscribeBody
    })
    .then(res => {
        if (res.ok) {
            return res.json()
        }

        throw new Error(res.statusText)
    })

    localStorage.setItem(expiredKey, (new Date()).getTime().toString())
}
