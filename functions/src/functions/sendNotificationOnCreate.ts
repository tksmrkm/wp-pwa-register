import { EventContext } from 'firebase-functions'
import { firestore } from 'firebase-functions'

type handler = (snapshot: firestore.QueryDocumentSnapshot, context: EventContext) => unknown

const sendNotificationOnCreate: handler = (snapshot, context) => {

}

export default sendNotificationOnCreate
