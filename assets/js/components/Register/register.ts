import { getToken, getMessaging } from 'firebase/messaging'
import { signInAnonymously, getAuth } from 'firebase/auth'
import app from '~/utils/firebase'

type UserProps = {
    id: string;
}

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

    // find user
    const headers = new Headers({
        "Authorization": `Basic ${WP_REGISTER_SERVICE_WORKER.base64}`
    })

    const query = new URLSearchParams({
        search: user.user.uid,
        status: 'draft'
    })

    const fetchedUser = await fetch(`${WP_REGISTER_SERVICE_WORKER.root}wp/v2/pwa_users?${query}`, {
        headers,
        credentials: 'omit'
    })
    .then(response => {
        if (response.ok) {
            return response.json()
        }

        throw `${response.status}: ${response.statusText}`
    })
    .then((json: UserProps[]) => {
        const user = json.length ? json.pop(): null
        return user
    })
    .catch(console.warn)

    // save user
    const saveHeaders = new Headers({
        Authorization: `Basic ${WP_REGISTER_SERVICE_WORKER.base64}`,
        "Content-Type": "application/json"
    })

    const body = JSON.stringify({
        title: user.user.uid,
        token
    })

    const entry = [`${WP_REGISTER_SERVICE_WORKER.root}wp/v2/pwa_users`]

    if (fetchedUser) {
        entry.push(fetchedUser.id)
    }

    return fetch(entry.join('/'), {
        method: 'post',
        headers: saveHeaders,
        body,
        credentials: 'omit'
    })
    .then(response => {
        if (response.ok) {
            return response.json()
        }
    })
    .then(json => {
        return json
    })
}
