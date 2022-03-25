import push from './components/ServiceWorker/push';
import { onClick, onClose } from './components/ServiceWorker/notification'
import { handleFetch, handleInstall } from './components/ServiceWorker/install'

self.addEventListener('push', push);

self.addEventListener('notificationclick', onClick);
self.addEventListener('notificationclose', onClose);

self.addEventListener('install', handleInstall)
self.addEventListener('fetch', handleFetch)
