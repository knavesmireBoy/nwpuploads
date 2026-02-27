<form action="?" method="get" name="search" class="details">
	<p>View files satisfying the following criteria</p>
	<?php if (!isset($zero)) : ?>
		<label for="user">By user</label><select id="user" name="user">
			<option value="">Any User</option>
			<?php if ($priv === "Admin") { ?>
				<optgroup label="clients">
				<?php }
			foreach ($client as $x => $c): ?>
					<option value="<?= $x; ?>"><?= $c; ?>
					</option><?php endforeach; ?>
				</optgroup>
				<?php if ($priv == "Admin") { ?>
					<optgroup label="users">
					<?php }
				foreach ($users as $ix => $u): ?>
						<option value="<?= $ix; ?>"><?= $u; ?>
						</option><?php endforeach; ?>
					</optgroup>
		</select>

	<?php endif; ?>
	<label for="text">Containing text</label><input id="text" type="search" name="text" />
	<label for="suffix">Suffix</label><select id="suffix" name="suffix">
		<option value="">Search files</option>
		<option value="pdf">pdf</option>
		<option value="zip">zip</option>
		<option value="owt">other</option>
	</select>
	<input type="hidden" name="action" value="search" />
	<input type="hidden" name="flag" />
	<input type="submit" value="Search" />
</form>
<p><a href=".">Clear Search</a></p>