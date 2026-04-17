<form action="<?= $action; ?>" method="post" name="updatefileinfo" class="details">
<a href="/uploader/load/" class="cancel">X</a>
	<?php
	if ($swap === 'No') {
	?>
		<label for="filename">Name</label><input id="filename" type="text" name="filename" value="<?= $filename; ?>" />
		<label for="description">Description</label><input id="description" type="text" name="description" value="<?= $description; ?>" />
	<?php } ?>
	<?php if (!isset($colleagues) && isset($all_users)) { ?>
		<label for="user">User</label><select id="user" name="user">
			<option value="">Select One</option><?php foreach ($all_users as $k => $v): ?>
				<option value="<?= $k; ?>"><?= $v; ?></option><?php endforeach; ?>
		</select>
	<?php }
	if (isset($colleagues)) { ?>
		<label for="colleagues">Colleagues:&nbsp;</label> <select id="colleagues" name="colleagues">
			<option value="">Select one</option><?php foreach ($colleagues as $k => $v): ?>
				<option value="<?= $k; ?>"><?= $v; ?></option><?php endforeach; ?>
		</select>
	<?php } ?>
	<input type="hidden" name="fileid" value="<?= $id; ?>" />
	<input type="hidden" name="answer" value="<?= $answer; ?>" />
	<input type="hidden" name="original" value="<?= $owner['id']; ?>" />
	<input type="submit" value="<?= $button; ?>" />
</form>