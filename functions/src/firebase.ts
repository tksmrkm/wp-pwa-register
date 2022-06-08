import { initializeApp } from 'firebase-admin/app'
import { credential } from 'firebase-admin'
import * as serviceAccount from './service-account.json'

export const admin = initializeApp({
    // credential: applicationDefault(),
    credential: credential.cert({
        clientEmail: serviceAccount.client_email,
        privateKey: serviceAccount.private_key,
        projectId: serviceAccount.project_id
    }),
    databaseURL: `https://${serviceAccount.project_id}.firebaseio.com`
})
