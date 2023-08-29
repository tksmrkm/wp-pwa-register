import { handleRegisterSuccess } from "./register"

interface ExtendedWorkerNavigator extends WorkerNavigator{
    serviceWorker?: ServiceWorkerContainer
}

declare const navigator: ExtendedWorkerNavigator

const handleLoad = () => {
    console.log('onLoad')
    if (navigator.serviceWorker) {
        navigator
            .serviceWorker
            .register('/pwa-service-worker.js')
            .then(handleRegisterSuccess)
            .catch(console.warn)
    }
}

export default handleLoad