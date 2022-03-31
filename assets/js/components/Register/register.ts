import { getToken, getMessaging, } from 'firebase/messaging'
import { signInAnonymously, getAuth } from 'firebase/auth'
import app from '~/utils/firebase'

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

    if (WP_REGISTER_SERVICE_WORKER.debug) {
        throw new Error('test')
    }

    // find user
    const headers = new Headers({
        Authorization: `Basic ${WP_REGISTER_SERVICE_WORKER.base64}`
    })

    fetch(`${WP_REGISTER_SERVICE_WORKER.root}wp/v2/pwa_users?search=${user.user.uid}&status=draft`, {
        headers
    })
    .then(response => {
        if (response.ok) {
            return response.json()
        }

        throw `${response.status}: ${response.statusText}`
    })
    .then((json: {id: string}[]) => {
        const id = json.length ? json.pop()?.id: null
        return id
    })
    .then(id => {
        // save user
        const headers = new Headers({
            Authorization: `Basic ${WP_REGISTER_SERVICE_WORKER.base64}`,
            "Content-Type": "application/json"
        })

        const body = JSON.stringify({
            title: id,
            token
        })

        const entry = [`${WP_REGISTER_SERVICE_WORKER.root}wp/v2/pwa_users`]

        if (id) {
            entry.push(id)
        }

        return fetch(entry.join('/'), {
            headers,
            body,
            credentials: 'omit'
        })
    })
    .catch(console.warn)
}
