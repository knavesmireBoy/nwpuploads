<form action="<?php htmlout($action); ?>" method="post" name="updateFileInfo">
	<?php if ($_POST['swap'] == 'No'): ?>
		<div>
			<label for="filename">Name: <input id="filename" type="text" name="filename" value="<?php htmlout($filename); ?>" /></label>
		</div>
		<div>
			<label for="description">Description: <input id="description" type="text" name="description" value="<?php htmlout($diz); ?>" /></label>
		</div>
		<div>
		<?php endif; ?>
		<?php if (count($colleagues) == 0) : ?>
			<label for="user">User:&nbsp;</label><select id="user" name="user">
				<option value="">Select one</option><?php foreach ($all_users as $i => $a): ?>
					<option value="<?php htmlout($i); ?>"><?php htmlout($a) ?></option><?php endforeach; ?>
			</select>
		</div>
	<?php else : ?>
		<div>
			<label for="colleagues">Colleagues:&nbsp;</label> <select id="colleagues" name="colleagues">
				<option value="">Select one</option><?php foreach ($colleagues as $i => $c): ?>
					<option value="<?php htmlout($i); ?>"><?php htmlout($c); ?></option><?php endforeach; ?>
			</select>
		</div>
	<?php endif; ?>
	<div>
		<input type="hidden" name="fileid" value="<?php htmlout($id); ?>" />
		<input type="hidden" name="answer" value="<?php htmlout($answer); ?>" />
		<input type="hidden" name="original" value="<?php htmlout($userid); ?>" />
		<input type="submit" value="<?php htmlout($button); ?>" />
	</div>
</form>