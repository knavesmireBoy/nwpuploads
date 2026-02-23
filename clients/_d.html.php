<form action="?<?= $action; ?>" method="post" name="<?= $formname; ?>">
    <input type="hidden" name="id" value="<?= $id; ?>" />
    <p><?= $prompt; ?></p>
    <input id="yes" type="radio" name="<?= $call; ?>" value="<?= $pos; ?>" />
    <label for="yes">Yes</label>
    <input id="no" type="radio" name="<?= $call; ?>" value="<?= $neg; ?>" />
    <label for="no">No</label>
    <input type="submit" />
</form>