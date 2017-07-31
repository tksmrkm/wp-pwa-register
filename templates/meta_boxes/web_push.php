<?php if ($already): ?>
<input type="hidden" name="<?php echo $key; ?>[already]" value="<?php echo $already; ?>">
<?php endif; ?>
<p>
    <input id="<?php echo $key ?>[flag]" name="<?php echo $key ?>[flag]" type="checkbox">
    <label for="<?php echo $key ?>[flag]">プッシュ情報も更新する</label>
</p>
<p>
    <input id="<?php echo $key ?>[icon]" name="<?php echo $key ?>[icon]" type="checkbox"<?php echo $icon ?>>
    <label for="<?php echo $key ?>[icon]">アイコンにアイキャッチを使用する</label>
</p>
<p>
    <label for="<?php echo $key ?>[headline]">見出し</label>
    <input type="text" id="<?php echo $key ?>[headline]" name="<?php echo $key ?>[headline]" value="<?php echo $headline; ?>" placeholder="<?php echo get_bloginfo('name') ?>">
</p>
<p>
    <label for="<?php echo $key ?>[title]">タイトル</label>
    <input type="text" id="<?php echo $key ?>[title]" name="<?php echo $key ?>[title]" value="<?php echo $title; ?>">
</p>
<p>
    <label for="<?php echo $key ?>[datetime]">配信時間</label>
    <input type="datetime-local" id="<?php echo $key ?>[datetime]" name="<?php echo $key ?>[datetime]" value="<?php echo $datetime; ?>">
</p>