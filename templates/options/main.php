<div class="wrap">
    <h2>Choose WebPushTarget</h2>
    <button id="button">Get DATA</button>
    <textarea id="filtered" style="width: 100%;"></textarea>
    <div id="loader" style="position: fixed; left: 0; top: 0; width: 100%; height: 100%; z-index: 100; background: rgba(0, 0, 0, 0.5); display: none;"></div>
</div>

<script>

const textarea = document.getElementById('filtered'),
loader = document.getElementById('loader'),
button = document.getElementById('button'),
getIds = (page = 1, limit = 1000, data = []) => {
    return fetch(`/wp-json/wp_pwa_register/v1/notification_ids?page=${page}&limit=${limit}`)
    .then(response => response.json())
    .then(json => {
        if (json.data.length) {
            return getIds(json.meta.page + 1, json.meta.limit, [
                ...data,
                ...json.data
            ])
        }
        loader.style.display = "none"
        textarea.textContent = JSON.stringify(data)
        button.disabled = true
        textarea.focus()
        textarea.select()
    })
}

button.addEventListener('click', () => {
    loader.style.display = "block"
    getIds()
})
</script>