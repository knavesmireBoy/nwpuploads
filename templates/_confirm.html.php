
<p><?= $prompt; ?></p>

<?php if(isset($neg)) : ?>

<input id="yes" type="radio" name="<?= $call; ?>" value="<?= $pos; ?>" />
<label for="yes">Yes</label>
<input id="no" type="radio" name="<?= $call; ?>" value="<?= $neg; ?>" />
<label for="no">No</label>

<?php else : ?>
<input id="no" type="radio" name="<?= $call; ?>" value="<?= $pos; ?>" />
<label for="no">OK</label>

<?php endif; ?>


<input type="hidden" name="id" value="<?= $id; ?>" />
<input type="submit" />