import { wrapperId } from "./show"

const handleCloseModal = () => {
    document.getElementById(wrapperId)?.classList.add('hide')
}

export default handleCloseModal
