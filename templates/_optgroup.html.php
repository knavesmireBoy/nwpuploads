<?php
dump(9);
if (isset($optgroup)) { ?>
    <optgroup label="<?= $optgroup; ?>">
        <?php foreach ($group as $k => $v): ?>
            <option value="<?= $k; ?>"><?= $v; ?>
            </option><?php endforeach; ?>
    </optgroup>
    <?php } else {
    foreach ($group as $k => $v): ?>
        <option value="<?= $k; ?>"><?= $v; ?>
        </option><?php endforeach; ?>

<?php }
