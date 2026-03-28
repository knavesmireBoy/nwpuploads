<?php 

if (!empty($clientlist)) : ?>
    <fieldset>
            <legend>Assign to Client</legend>
            <select name="employer" id="employer">
                <option value="">Select One</option>
                <?php foreach ($clientlist as $k => $v): ?>
                    <option value="<?= $k; ?>" <?= isset($employer) && $employer == $k ? 'selected' : ''; ?>><?= $v; ?></option>
                <?php endforeach; ?>
            </select>
        </fieldset>

<?php endif;
