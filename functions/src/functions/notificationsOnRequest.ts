import { https, Response } from 'firebase-functions'
import firebase from '../firebase'

type handler = (req: https.Request, res: Response<any>) => void | Promise<void>

const notificationsOnRequest: handler = async (req, res) => {
    if (req.method === 'POST') {
        throw new Error(`Method not allowed: ${req.method}`)
    }

    firebase.firestore().collection('notifications').add({
        ...req.params
    })
}

export default notificationsOnRequest
