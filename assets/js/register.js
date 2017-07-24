class Register
{
    constructor() {
        this.messaging = firebase.messaging();
        this.auth = firebase.auth();
        window.addEventListener('load', this.onload.bind(this));
    }

    onload() {
        navigator.serviceWorker.register('/pwa-service-worker.js')
        .then(this.register.bind(this));
    }

    register(registration) {
        this.messaging.useServiceWorker(registration);

        const permission = this.messaging.getNotificationPermission_();

        if (permission === 'default') {
            this.requestPermission();
        }
    }

    requestPermission() {
        this.messaging.requestPermission()
        .then(this.getToken.bind(this))
        .catch(this.error.bind(this));
    }

    getToken() {
        const result = this.messaging.getToken()
        .then(this.setToken.bind(this))
        .catch(this.error.bind(this));
    }

    setToken(token) {
        this.token = token;
        this.auth.onAuthStateChanged(this.onAuthStateChanged.bind(this));
        this.auth.signInAnonymously()
        .catch(this.error.bind(this));
    }

    onAuthStateChanged(user) {
        if (user) {
            this.uid = user.uid;
            this.findUser();
        }
    }

    findUser() {

        const root = ajaxurl.split('/wp-admin/')[0];
        const headers = new Headers({
            Authorization: 'Basic ' + WP_REGISTER_SERVICE_WORKER.base64
        });

        fetch(`${root}/wp-json/wp/v2/pwa_users?status=draft&search=${this.uid}`, {
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

    saveUser(pwa_user_id) {
        const root = ajaxurl.split('/wp-admin/')[0];
        const headers = new Headers({
            Authorization: 'Basic ' + WP_REGISTER_SERVICE_WORKER.base64
        });
        const data = new FormData();
        let entrypoint = `${root}/wp-json/wp/v2/pwa_users`;
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
