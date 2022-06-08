import * as functions from "firebase-functions";
import notificationsOnRequest from "./functions/notificationsOnRequest";
import registerUser from "./functions/registerUser";
import sendNotificationOnCreate from "./functions/sendNotificationOnCreate";

// // Start writing Firebase Functions
// // https://firebase.google.com/docs/functions/typescript
//
// export const helloWorld = functions.https.onRequest((request, response) => {
//   functions.logger.info("Hello logs!", {structuredData: true});
//   response.send("Hello from Firebase!");
// });

export const sendNotification = functions.firestore.document('notifications/{id}').onCreate(sendNotificationOnCreate)

export const notifications = functions.https.onRequest(notificationsOnRequest)

export const users = functions.https.onRequest(registerUser)
