import Log from './components/Log';
import Register from './components/Register';

const logger = new Log(`${WP_REGISTER_SERVICE_WORKER.webroot}/api/log?method=post`);
const register = new Register(logger);

window.addEventListener('load', register.load.bind(register));