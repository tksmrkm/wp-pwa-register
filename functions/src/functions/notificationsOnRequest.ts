import { https, Response } from 'firebase-functions'
import { admin } from '../firebase'
import { getFirestore } from 'firebase-admin/firestore'

type handler = (req: https.Request, res: Response<any>) => void | Promise<void>

const notificationsOnRequest: handler = async (req, res) => {
    if (req.method === 'POST') {
        throw new Error(`Method not allowed: ${req.method}`)
    }

    getFirestore(admin).collection('notifications').add({
        ...req.params
    })
}

export default notificationsOnRequest
