<form action="<?= $action; ?>" method="post" name="updatefileinfo" class="prompt">
	<?php
	$swap = $answer ?? $_POST['swap'] ?? NULL;
	if ($swap === 'No') {
	?>
		<div>
			<label for="filename">Name</label><input id="filename" type="text" name="filename" value="<?= $filename; ?>" />
		</div>
		<div>
			<label for="description">Description</label><input id="description" type="text" name="description" value="<?= $description; ?>" />
		</div>
	<?php } ?>
	<?php if (!isset($colleagues) && isset($all_users)) { ?>
		<div>
			<label for="user">User</label><select id="user" name="user">
				<option value="">Select one</option><?php foreach ($all_users as $i => $a): ?>
					<option value="<?= $i; ?>"><?= $a; ?></option><?php endforeach; ?>
			</select>
		</div>
	<?php }
	if (isset($colleagues)) { ?>
		<div>
			<label for="colleagues">Colleagues:&nbsp;</label> <select id="colleagues" name="colleagues">
				<option value="">Select one</option><?php foreach ($colleagues as $i => $c): ?>
					<option value="<?= $i; ?>"><?= $c; ?></option><?php endforeach; ?>
			</select>
		</div>
	<?php } ?>
	<div>
		<input type="hidden" name="fileid" value="<?= $id; ?>" />
		<input type="hidden" name="answer" value="<?= $answer; ?>" />
		<input type="hidden" name="original" value="<?= $userid; ?>" />
		<input type="submit" value="<?= $button; ?>" />
	</div>
</form>