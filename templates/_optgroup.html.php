<?php
if (isset($optgroup)) { ?>
    <optgroup label="<?= $optgroup; ?>">
        <?php foreach ($group as $k => $v): ?>
            <option value="<?= $k; ?>"><?= $v; ?>
            </option><?php endforeach; ?>
    </optgroup>
    <?php
    dump([44, $group]);

} else {
    foreach ($group as $k => $v): ?>
        <option value="<?= $k; ?>"><?= $v; ?>
        </option><?php endforeach; ?>

<?php }
