import handleCancel, { canceledId } from './cancel';
import handleCloseModal from './closeModal';
import handleRequest from './request';

export const wrapperId = 'wp-pwa-register-wrapper'

const getWrapper = () => {
    const node = document.createElement('div')
    node.setAttribute('id', wrapperId)
    return node
}

const getContainer = () => {
    const node = document.createElement('div')
    node.setAttribute('id', 'wp-pwa-register-container')
    return node
}

const getContent = () => {
    const node = document.createElement('div')
    node.setAttribute('id', 'wp-pwa-register-content')
    return node
}

const getMain = () => {
    const node = document.createElement('div')
    node.setAttribute('id', 'wp-pwa-register-main')
    return node
}

const getFooter = () => {
    const node = document.createElement('div')
    node.setAttribute('id', 'wp-pwa-register-footer')
    return node
}

const getBanner = () => {
    if (!WP_REGISTER_SERVICE_WORKER.register.icon) {
        return null
    }

    const node = document.createElement('img')
    node.setAttribute('id', 'wp-pwa-register-banner')
    node.setAttribute('src', WP_REGISTER_SERVICE_WORKER.register.icon)
    return node
}

const getMessage = () => {
    const node = document.createElement('p')
    node.setAttribute('id', 'wp-pwa-register-message')
    node.textContent = WP_REGISTER_SERVICE_WORKER.register.message ?? 'プッシュ通知で記事更新をお知らせします。'
    return node
}

const getAcceptButton = () => {
    const node = document.createElement('button')
    node.setAttribute('id', 'wp-pwa-register-accept-button')
    node.textContent = '通知を受け取る'
    node.addEventListener('click', handleRequest)
    node.addEventListener('click', handleCloseModal)
    return node
}

const getCancelButton = () => {
    const node = document.createElement('button')
    node.setAttribute('id', 'wp-pwa-register-cancel-button')
    node.textContent = '受け取らない'
    node.addEventListener('click', handleCancel)
    node.addEventListener('click', handleCloseModal)
    return node
}

const handleShow = () => {
    // 通知バナーで受け取らないをクリックした日時
    const canceled = localStorage.getItem(canceledId)

    if (canceled) {
        // 1 week
        const expireDuration = 7 * 24 * 60 * 60 * 1000
        const expired = Date.now() > (Number(canceled) + expireDuration)

        // 規定日数が過ぎていない場合、早期リターン
        if (!expired) {
            return false
        }
    }

    const wrapper = getWrapper()
    const container = getContainer()
    const content = getContent()
    const main = getMain()
    const footer = getFooter()
    const banner = getBanner()
    const message = getMessage()
    const acceptButton = getAcceptButton()
    const cancelButton = getCancelButton()

    if (banner) {
        main.appendChild(banner)
    }
    main.appendChild(message)
    footer.appendChild(acceptButton)
    footer.appendChild(cancelButton)
    content.appendChild(main)
    content.appendChild(footer)
    container.appendChild(content)
    wrapper.appendChild(container)
    document.body.appendChild(wrapper)
}

export default handleShow
