const dbname = 'wp-pwa-register'
const dbVersion = 1
const storeName = 'users'
const uidKeyName = 'uid'

const getIndexedDb = () => {
    return indexedDB.open(dbname, dbVersion)
}

export const saveUid = (uid: string) => {
    const indexed = getIndexedDb()

    indexed.addEventListener('error', e => {
        console.warn('indexedDB connection error on wp-pwa-register')
    })

    indexed.addEventListener('upgradeneeded', e => {
        const db = (e.target as IDBOpenDBRequest).result
        db.createObjectStore(storeName)
    })

    indexed.addEventListener('success', e => {
        const db = (e.target as IDBOpenDBRequest).result
        const transaction = db.transaction(storeName, 'readwrite')
        const store = transaction.objectStore(storeName)
        store.put(uid, uidKeyName)
    })
}

type GetUidType = (callback: (payload: unknown) => void) => void

export const getUid: GetUidType = callback => {
    const indexed = getIndexedDb()

    indexed.addEventListener('success', e => {
        const db = (e.target as IDBOpenDBRequest).result
        const transaction = db.transaction(storeName, 'readonly')
        const store = transaction.objectStore(storeName)
        const result = store.get(uidKeyName)

        result.addEventListener('success', e => {
            callback((e.target as IDBOpenDBRequest).result)
        })
    })

    indexed.addEventListener('error', console.warn)
}
