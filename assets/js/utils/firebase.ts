import { initializeApp } from 'firebase/app'

declare const WP_PWA_REGISTER_FIREBASE_CONFIG: {
    appId: string;
    apiKey: string;
    projectId: string;
    senderId: string;
}

const app = initializeApp({
    appId: WP_PWA_REGISTER_FIREBASE_CONFIG.appId,
    apiKey: WP_PWA_REGISTER_FIREBASE_CONFIG.apiKey,
    authDomain: `${WP_PWA_REGISTER_FIREBASE_CONFIG.projectId}.firebaseapp.com`,
    databaseURL: `${WP_PWA_REGISTER_FIREBASE_CONFIG.projectId}.firebaseio.com`,
    projectId: WP_PWA_REGISTER_FIREBASE_CONFIG.projectId,
    storageBucket: `${WP_PWA_REGISTER_FIREBASE_CONFIG.projectId}.appspot.com`,
    messagingSenderId: WP_PWA_REGISTER_FIREBASE_CONFIG.senderId
})

export default app
