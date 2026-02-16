<?php
if (!empty($roles)): ?>
    <fieldset>
        <legend>Roles</legend>
        <?php for ($i = 0; $i < count($roles); $i++): ?>
            <div>
                <label for="role<?= $i; ?>"><input id="role<?= $i; ?>" type="checkbox" name="roles[]" value="<?= $roles[$i]['id']; ?>"
                        <?= $roles[$i]['selected'] ? 'checked' : ''; ?> />
                    <?= $roles[$i]['id']; ?></label>: <?= $roles[$i]['description']; ?>
            </div>
        <?php endfor; ?>
    </fieldset>
<?php endif; ?>