<form action="<?php echo $action_url; ?>" method="post">
    <table class="form-table">
        <tbody>
            <tr>
                <th>From</th>
                <td>
                    <input type="datetime-local" name="from">
                </td>
            </tr>
            <tr>
                <th>To</th>
                <td>
                    <input type="datetime-local" name="to">
                </td>
            </tr>
            <tr>
                <th>件数</th>
                <td>
                    <input type="number" name="quantity" value="10000">
                </td>
            </tr>
            <tr>
                <td rowspan="2">
                    <input type="submit" value="抽出">
                </td>
            </tr>
        </tbody>
    </table>
</form>