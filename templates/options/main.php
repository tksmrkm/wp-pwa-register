<div class="wrap">
    <h2>Choose WebPushTarget</h2>
    <textarea id="filtered" style="width: 100%;"><?php echo json_encode($filtered); ?></textarea>
</div>

<script>
const filtered = document.getElementById('filtered')
filtered.addEventListener('focus', e => {
    e.target.select()
})
filtered.focus()
</script>