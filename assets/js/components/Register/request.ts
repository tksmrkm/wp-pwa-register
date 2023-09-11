import { handleRegisterSuccess } from "./register"

interface ExtendedWorkerNavigator extends WorkerNavigator{
    serviceWorker?: ServiceWorkerContainer
}

declare const navigator: ExtendedWorkerNavigator

const handleRequest = () => {
    navigator
        .serviceWorker
        ?.register('/pwa-service-worker.js')
        .then(handleRegisterSuccess)
        .catch(console.warn)
}

export default handleRequest
