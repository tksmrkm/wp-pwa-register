<div class="wrap">
    <h2>Manage DB</h2>

    <form action="<?php echo $action_url ?>" method="post">
        <select name="action">
            <option>-</option>
            <option value="migrate">Migrate</option>
            <option value="delete">Delete</option>
        </select>

        <input type="submit" value="Exec">
    </form>
</div>