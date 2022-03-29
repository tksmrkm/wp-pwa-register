import { handleRegisterError, handleRegisterSuccess } from "./register"

interface ExtendedWorkerNavigator extends WorkerNavigator{
    serviceWorker?: ServiceWorkerContainer
}

declare const navigator: ExtendedWorkerNavigator

const handleLoad = () => {
    if (navigator.serviceWorker) {
        navigator
            .serviceWorker
            .register('/pwa-service-worker.js')
            .then(handleRegisterSuccess)
            .catch(handleRegisterError)
    }
}

export default handleLoad