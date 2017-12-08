import push from './components/ServiceWorker/push';
import notificationclick from './components/ServiceWorker/notificationclick';

self.addEventListener('push', push);

self.addEventListener('notificationclick', notificationclick);
