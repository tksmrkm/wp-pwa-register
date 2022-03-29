import Log from '~/components/Register/LogClass'
import Register from '~/components/Register/RegisterClass'

declare const WP_REGISTER_SERVICE_WORKER: {
    webroot: string;
}

declare const window: typeof globalThis

const logger = new Log(`${WP_REGISTER_SERVICE_WORKER.webroot}/api/log?method=post`);
const register = new Register(logger);

window.addEventListener('load', register.load.bind(register));
