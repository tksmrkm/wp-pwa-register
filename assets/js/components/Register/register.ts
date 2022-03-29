export const handleRegisterSuccess = (registration: ServiceWorkerRegistration) => {
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

export const handleRegisterError = (error: unknown) => {
    console.warn(error)
}
