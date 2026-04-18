<form action="<?= $action; ?>" method="post" name="updatefileinfo" class="details">
	<a href="/uploader/load/" class="cancel">X</a>
	<?php
	//if no allow filename and description to be updated, otherwise assume we are only interested in changing file ownership
	if ($answer === 'No') {
	?>
		<label for="filename">Name</label><input id="filename" type="text" name="filename" value="<?= $filename; ?>" />
		<label for="description">Description</label><input id="description" type="text" name="description" value="<?= $description; ?>" />
	<?php } ?>
	<?php if (empty($colleagues) && !empty($all_users)) { ?>
		<label for="user">User</label><select id="user" name="user">
			<option value="">Select One</option>
			<?php
			$group = $all_users;
			include '_optgroup.html.php'; ?>
		</select>
	<?php }
	if (!empty($colleagues)) { ?>
		<label for="colleagues">Colleagues:&nbsp;</label> <select id="colleagues" name="colleagues">
			<option value="">Select one</option>
			<?php
			$group = $colleagues;
			include '_opgtroup.html.php'; ?>
		</select>
	<?php } ?>
	<input type="hidden" name="id" value="<?= $id; ?>" />
	<input type="hidden" name="answer" value="<?= $answer; ?>" />
	<input type="hidden" name="original" value="<?= $owner['id']; ?>" />
	<input type="submit" value="<?= $button; ?>" />
</form>