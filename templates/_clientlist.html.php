<div><label for="employer">Company: </label>
    <select name="employer" id="employer">
        <option value="">Assign to Client?</option>
        <?php foreach ($clientlist as  $i => $client): ?>
            <option value="<?= $i; ?>" <?= isset($job) && $job == $i ? 'selected' : ''; ?>>
                <?= $client; ?></option>
        <?php endforeach; ?>
    </select>
</div>