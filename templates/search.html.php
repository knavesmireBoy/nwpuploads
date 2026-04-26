<form action="?" method="get" name="search" class="details">
<a href="." class="cancel">X</a>
	<p>View files satisfying the following criteria</p>
	<?php if (!isset($zero)) : ?>
		<label for="user">By user</label><select id="user" name="user">
			<option value="">Any User</option>
			<?php if ($priv === "Admin") { ?>
				<optgroup label="clients">
				<?php }
			foreach ($clients as $k => $v): ?>
					<option value="<?= $k; ?>"><?= $v; ?>
					</option><?php endforeach; ?>
				</optgroup>
				<?php if (!empty($users)) { ?>
					<optgroup label="users">
					<?php }
				foreach ($users as $k => $v): ?>
						<option value="<?= $v; ?>"><?= $v; ?>
						</option><?php endforeach; ?>
					</optgroup>
		</select>

	<?php endif; ?>
	<label for="text">Containing text</label><input id="text" type="search" name="text" />
	<label for="ext">Suffix</label><select id="ext" name="ext">
		<option value="">Search files</option>
		<option value="pdf">pdf</option>
		<option value="zip">zip</option>
		<option value="owt">other</option>
	</select>
	<input type="hidden" name="action" value="search" />
	<input type="hidden" name="flag" />
	<input type="submit" value="Search" />
</form>
