export const canceledId = 'wp-pwa-register-canceled'

const handleCancel = () => {
    localStorage.setItem(canceledId, Date.now().toString())
}

export default handleCancel