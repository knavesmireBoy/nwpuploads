<form action="<?= $action; ?>" method="post" name="choice" class="prompt" id="yesno">
    <input type="hidden" name="dom" value="<?= $clientdom; ?>" />
    <input type="hidden" name="id" value="<?= $clientid; ?>" />
    <p><?= "Associate existing users with $clientname?"; ?></p>
    <input type="radio" name="<?= $call; ?>" value="<?= $pos; ?>" id="<?= $pos; ?>" /><label for="<?= $pos; ?>">Yes</label>
    <input type="radio" name="<?= $call; ?>" value="<?= $neg; ?>" value="<?= $neg; ?>" /><label for="<?= $neg; ?>">No</label>
    <input type="submit" value="Submit" />
</form>