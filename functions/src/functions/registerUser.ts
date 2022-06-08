import { https, Response } from 'firebase-functions'
import { admin } from '../firebase'
import { getFirestore } from 'firebase-admin/firestore'
import { getMessaging } from 'firebase-admin/messaging'

type handler = (req: https.Request, res: Response<any>) => void | Promise<void>

const registerUser: handler = async (req, res) => {
    if (req.method !== 'POST') {
        res.status(405).send(`Method not allowed: ${req.method}`)
        return
    }

    const params = JSON.parse(req.body)
    const firestore = getFirestore(admin)
    const messaging = getMessaging(admin)

    firestore.collection('users').doc(params.title).get().then(snapshot => {
        const dat = {
            token: params.token,
            created: new Date(),
            modified: new Date()
        }
        if (snapshot.exists) {
            dat.created = snapshot.data()?.created
        }
        snapshot.ref.set(dat)
    })

    const subscribed = await messaging.subscribeToTopic(params.token, 'all')

    res
    .setHeader('Access-Control-Allow-Origin', '*')
    .send({
        subscribed,
        params
    })
}

export default registerUser
