class Register
{
/**
 * Constructor
 * this.messaging firebaseのmessaging()オブジェクト
 * this.auth      firebaseのauth()オブジェクト
 */
    constructor() {
        // console.count('constructor');
        this.messaging = firebase.messaging();
        this.auth = firebase.auth();
        window.addEventListener('load', this.onload.bind(this));
    }

/**
 * window onLoad にバインドされたイベント
 * /pwa-service-worker.jsを登録
 */
    onload() {
        // console.count('onload');
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/pwa-service-worker.js')
            .then(this.register.bind(this));
        }
    }

/**
 * service-worker登録後の処理
 * @param  {[type]} registration
 */
    register(registration) {
        // console.count('register');
        this.messaging.useServiceWorker(registration);

        const permission = this.messaging.getNotificationPermission_();
        this.permission = permission;

        if (permission === 'default') {
            this.requestPermission();
        } else if (permission === 'granted') {
            this.messaging.onTokenRefresh(this.getToken.bind(this));
        }
    }

/**
 * 通知のパーミッションがデフォルトのとき
 * @return {[type]} [description]
 */
    requestPermission() {
        // console.count('requestPermission');
        this.messaging.requestPermission()
        .then(this.getToken.bind(this))
        .catch(this.error.bind(this));
    }

    getToken() {
        // console.count('getToken')
        return this.messaging.getToken()
        .then(this.setToken.bind(this))
        .catch(this.error.bind(this));
    }

    setToken(token) {
        // console.count('setToken');
        // console.log(token);
        this.token = token;
        this.auth.onAuthStateChanged(this.onAuthStateChanged.bind(this));
        this.auth.signInAnonymously()
        .catch(this.error.bind(this));
    }

/**
 * this.auth.onAuthStateChangedのコールバック関数
 * firebaseで認証されたらthis.findUserに移行
 * @param  {[type]} user [description]
 */
    onAuthStateChanged(user) {
        // console.count('onAuthStateChanged');
        if (user) {
            // console.log(user);
            this.uid = user.uid;
            this.findUser();
        }
    }

/**
 * firebase.authのUIDをキーとしてpwa_usersから検索
 * 新規か既存か判定し、既存ユーザーであればsaveUserにpwa_usersの記事IDを渡す
 * @return {[type]} [description]
 */
    findUser() {
        // console.count('findUser');
        const headers = new Headers({
            Authorization: 'Basic ' + WP_REGISTER_SERVICE_WORKER.base64
        });

        fetch(`${WP_REGISTER_SERVICE_WORKER.root}wp/v2/pwa_users?status=draft&search=${this.uid}`, {
            headers: headers
        })
        .then(response => {
            return response.json()
        })
        .then(json => {
            let id = null;
            if (json.length) {
                id = json.pop().id;
            }
            this.saveUser(id);
        })
    }

/**
 * pwa_user_idがnullなら新規作成
 * pwa_usersの記事IDが渡されていればアップデート
 * @param  {[type]} pwa_user_id [description]
 * @return {[type]}             [description]
 */
    saveUser(pwa_user_id) {
        if (WP_REGISTER_SERVICE_WORKER.debug && this.permission === 'granted') {
            const fetchBody = new FormData();
            fetchBody.append('permission', 'granted');
            fetchBody.append('uid', this.uid);
            fetchBody.append('pwa_user_id', pwa_user_id);
            fetchBody.append('token', this.token);
            fetch(`${WP_REGISTER_SERVICE_WORKER.webroot}/api/log?method=post`, {
                method: 'POST',
                body: fetchBody
            });
        }
        // console.count('saveUser');
        // console.log(pwa_user_id);
        const headers = new Headers({
            Authorization: 'Basic ' + WP_REGISTER_SERVICE_WORKER.base64
        });
        const data = new FormData();
        let entrypoint = `${WP_REGISTER_SERVICE_WORKER.root}wp/v2/pwa_users`;
        if (pwa_user_id) {
            entrypoint += '/' + pwa_user_id;
        }
        data.set('title', this.uid);
        data.set('token', this.token);

        fetch(entrypoint, {
            method: 'POST',
            headers: headers,
            body: data
        });
    }

    error(err) {
        console.warn(err);
    }
}

new Register();
