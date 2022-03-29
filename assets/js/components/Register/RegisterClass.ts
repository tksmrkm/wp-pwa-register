export default class Register
{
/**
 * Constructor
 * this.messaging firebaseのmessaging()オブジェクト
 * this.auth      firebaseのauth()オブジェクト
 */
    constructor(logger) {
        // console.count('constructor')
        this.logger = logger
        this.messaging = firebase.messaging()
        this.auth = firebase.auth()
        this.refresh = false

        // bind functions
        this.refreshToken = this.refreshToken.bind(this)
        this.onAuthStateChanged = this.onAuthStateChanged.bind(this)
        this.register = this.register.bind(this)
        this.error = this.error.bind(this)
        this.getToken = this.getToken.bind(this)
        this.setToken = this.setToken.bind(this)
    }

/**
 * window onLoad にバインドされたイベント
 * /pwa-service-worker.jsを登録
 */
    load() {
        // console.count('onload')
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/pwa-service-worker.js')
            .then(this.register)
            .catch(this.error)
        }
    }

/**
 * service-worker登録後の処理
 * @param  {[type]} registration
 */
    register(registration) {
        // console.count('register')
        this.messaging.useServiceWorker(registration)

        const permission = this.messaging.getNotificationPermission_()
        this.permission = permission

        if (permission === 'default') {
            this.requestPermission()
        } else if (permission === 'granted') {
            this.messaging.onTokenRefresh(this.refreshToken)
        }
    }

/**
 * 通知のパーミッションがデフォルトのとき
 * @return {[type]} [description]
 */
    requestPermission() {
        // console.count('requestPermission')
        this.messaging.requestPermission()
        .then(this.getToken)
        .catch(this.error)
    }

    refreshToken() {
        // console.count('RefreshToken')
        this.refresh = true
        this.logger.logging({msg: 'will refresh token'})
        return this.getToken()
    }

    getToken() {
        // console.count('getToken')
        return this.messaging.getToken()
        .then(this.setToken)
        .catch(this.error)
    }

    setToken(token) {
        // console.count('setToken')
        // console.log(token)
        this.token = token
        this.auth.onAuthStateChanged(this.onAuthStateChanged)
        this.auth.signInAnonymously()
        .catch(this.error)
    }

/**
 * this.auth.onAuthStateChangedのコールバック関数
 * firebaseで認証されたらthis.findUserに移行
 * @param  {[type]} user [description]
 */
    onAuthStateChanged(user) {
        // console.count('onAuthStateChanged')
        if (user) {
            // console.log(user)
            this.uid = user.uid
            this.findUser()
        }
    }

/**
 * firebase.authのUIDをキーとしてpwa_usersから検索
 * 新規か既存か判定し、既存ユーザーであればsaveUserにpwa_usersの記事IDを渡す
 * @return {[type]} [description]
 */
    findUser() {
        // console.count('findUser')
        const headers = new Headers({
            Authorization: 'Basic ' + WP_REGISTER_SERVICE_WORKER.base64
        })

        fetch(`${WP_REGISTER_SERVICE_WORKER.root}wp/v2/pwa_users?search=${this.uid}&status=draft`, {
            headers: headers,
            credentials: 'omit'
        })
        .then(response => {
            if (response.ok) {
                return response.json()
            }

            throw `${response.status}: ${response.statusText}`
        })
        .then(json => {
            let id = null
            if (json.length) {
                id = json.pop().id
            }
            this.saveUser(id)
        })
        .catch(this.error)
    }

/**
 * pwa_user_idがnullなら新規作成
 * pwa_usersの記事IDが渡されていればアップデート
 * @param  {[type]} pwa_user_id [description]
 * @return {[type]}             [description]
 */
    saveUser(pwa_user_id) {
        if (WP_REGISTER_SERVICE_WORKER.debug && this.permission === 'granted') {
            const fetchBody = new FormData()
            fetchBody.append('permission', 'granted')
            fetchBody.append('uid', this.uid)
            fetchBody.append('pwa_user_id', pwa_user_id)
            fetchBody.append('token', this.token)
            this.logger.logging(fetchBody)
        }

        if (this.refresh) {
            const ref = new FormData()
            ref.append('msg', 'RefreshData')
            ref.append('uid', this.uid)
            ref.append('pwa_user_id', pwa_user_id)
            ref.append('token', this.token)
            this.logger.logging(ref)
        }
        // console.count('saveUser')
        // console.log(pwa_user_id)
        const headers = new Headers({
            "Authorization": 'Basic ' + WP_REGISTER_SERVICE_WORKER.base64,
            "Content-Type": "application/json"
        })
        const data = {
            title: this.uid,
            token: this.token
        }
        let entrypoint = `${WP_REGISTER_SERVICE_WORKER.root}wp/v2/pwa_users`
        if (pwa_user_id) {
            entrypoint += '/' + pwa_user_id
        }

        fetch(entrypoint, {
            method: 'POST',
            headers: headers,
            body: JSON.stringify(data),
            credentials: 'omit'
        })
        .catch(this.error)
    }

    error(err) {
        console.warn(err)
    }
}