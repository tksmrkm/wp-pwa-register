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
            this.saveUser();
        }
    }

    saveUser() {
        const root = ajaxurl.split('/wp-admin/')[0];
        const data = new FormData();
        const content = {
            token: this.token
        };
        data.set('title', this.uid);
        data.set('content', JSON.stringify(content));
        const headers = new Headers({
            Authorization: 'Basic ' + WP_REGISTER_SERVICE_WORKER.base64
        });

        fetch(`${root}/wp-json/wp/v2/pwa_users`, {
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
