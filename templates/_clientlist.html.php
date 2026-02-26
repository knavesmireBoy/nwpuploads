<?php if (!empty($clientlist)) : ?>
    <fieldset>
            <legend>Assign to Client</legend>
            <select name="employer" id="employer">
                <option value="">Select One</option>
                <?php foreach ($clientlist as $i => $client): ?>
                    <option value="<?= $i; ?>" <?= isset($job) && $job == $i ? 'selected' : ''; ?>><?= $client; ?></option>
                <?php endforeach; ?>
            </select>
        </fieldset>

<?php endif;
