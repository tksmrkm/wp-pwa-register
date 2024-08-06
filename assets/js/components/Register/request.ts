import { handleRegisterSuccess } from "./register"

interface ExtendedWorkerNavigator extends WorkerNavigator{
    serviceWorker?: ServiceWorkerContainer
}

declare const navigator: ExtendedWorkerNavigator

const handleRequest = (uid: string) => () => {
    navigator
        .serviceWorker
        ?.register(`/pwa-service-worker.js?uid=${uid}`)
        .then(handleRegisterSuccess(uid))
        .catch(console.warn)
}

export default handleRequest
