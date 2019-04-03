import push from './components/ServiceWorker/push';
import { onClick, onClose } from './components/ServiceWorker/notification'

self.addEventListener('push', push);

self.addEventListener('notificationclick', onClick);
self.addEventListener('notificationclose', onClose);
