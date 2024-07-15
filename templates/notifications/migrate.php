<div class="wrap">
    <h2>Migrate to Http v1</h2>

    <table>
        <thead>
            <tr>
                <th>Legacy</th>
                <th>Migrated</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?php echo $legacy_user_count; ?></td>
                <td><?php echo $migrated_user_count; ?></td>
            </tr>
        </tbody>
    </table>

    <form action="<?php echo $action_url ?>" method="post">
        件数
        <input type="number" name="exec_count" value="<?php echo $exec_count ?>" max="<?php echo $max_count ?>">
        <input type="submit" value="実行">
    </form>
</div>