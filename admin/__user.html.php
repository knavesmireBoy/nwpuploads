<form action="" method="post" name="edituserform" class="prompt">
	<input type="hidden" name="id" value="<?= $k; ?>" />
	<p><?= $user; ?></p>
	<label for="edit">Edit</label><input id="edit" type="radio" name="action" value="Edit" />
	<?php
	if (empty($denied)) { ?>
		<label for="delete">Delete</label><input id="delete" type="radio" name="action" value="Delete" />
	<?php } ?>
	<input type="submit" value="Submit" />
</form>