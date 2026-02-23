<?php
if (!empty($roles)): ?>
    <fieldset>
        <legend>Roles</legend>
        <?php for ($i = 0; $i < count($roles); $i++): ?>
            <div>
            <input id="role<?= $i; ?>" type="radio" name="roles[]" value="<?= $roles[$i]['id']; ?>"
            <?= $roles[$i]['selected'] ? 'checked' : ''; ?> />
                <label for="role<?= $i; ?>"><?= $roles[$i]['id']; ?></label><span><?= $roles[$i]['description']; ?></span>
            </div>
        <?php endfor; ?>
    </fieldset>
<?php endif; ?>