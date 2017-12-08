import fetchPush from './components/ServiceWorker/fetchPush';
import notificationClick from './components/ServiceWorker/notificationClick';

self.addEventListener('push', fetchPush);

self.addEventListener('notificationclick', notificationClick);
