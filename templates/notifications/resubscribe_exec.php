<form action="<?php echo $action_url; ?>" method="post">
    <?php foreach($_POST as $key => $value): ?>
        <input type="hidden" name="<?php echo $key; ?>" value="<?php echo $value; ?>">
    <?php endforeach; ?>
        <input type="hidden" name="exec" value="1">
    <table class="form-table">
        <tbody>
            <tr>
                <td>
                    <p>
                        <?php echo $count; ?>件が見つかりました。
                    </p>
                    <p>
                        <?php echo $unit_count; ?>件を上限として、<?php echo $chunk_count; ?>回の処理を実行します。
                    </p>
                </td>
            </tr>
            <tr>
                <td>

                    <input type="submit" value="実行">
                </td>
            </tr>
        </tbody>
    </table>
</form>