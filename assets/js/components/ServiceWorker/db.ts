/**
 * WIP
 */

const dbName = 'db-store'
const version = 1
const storeName = 'store-uid'
const keyPath = 'uid'

export const createStore = (uid: string) => {
    const openReq = indexedDB.open(dbName, version)

    openReq.onupgradeneeded = (e) => {
        const target = e.target as IDBOpenDBRequest
        const db = target.result
        if (!db.objectStoreNames.contains(storeName)) {
            db.createObjectStore(storeName, {
                keyPath
            })
        }
    }

    openReq.onerror = () => {
        console.error('indexedDB failed to start')
    }

    openReq.onsuccess = e => {
        const target = e.target as IDBOpenDBRequest
        const db = target.result

        const transaction = db.transaction(storeName, 'readwrite')
        const store = transaction.objectStore(storeName)
        store.put({ uid }, keyPath)
    }
}

