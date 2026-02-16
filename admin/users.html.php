<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/helpers.inc.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/config.php';
include TEMPLATE . 'base.html.php'; ?>
	<div>
		<h1><?php echo $manage; ?></h1>
		<?php
		if (preg_match("/admin/i", $priv)) {
		?>
			<p><a href="?add">Add New User</a></p>
		<?php }
		ob_start();
		?>
		<h3 class='error'><?= $error; ?></h3>
		<?php

		if (($priv == 'Admin') && !isset($selected)): ?>
			<form action="" method="post" name="userform" class="choose">
				<ul>
					<li><label for="user"></label><select id="user" name="user">
							<option value="">Select one</option>
							<?php if ($priv === 'Admin') {
								$optgroup = 'clients';
							}
							$group = $client;
							include '../templates/_optgroup.html.php';
							if ($priv === 'Admin') {
								$optgroup = 'users';
							}
							$group = $users;
							include '../templates/_optgroup.html.php'; ?>
						</select>
						<input type="submit" name="action" value="Choose" />
					</li>
				</ul>
			</form>
		<?php elseif (preg_match("/client/i", $priv) || (isset($selected))):
		?>
			<div class="clientgroup">
				<?php
				foreach ($users as $k => $user):
					include '_users.html.php';
				endforeach;
				?>
			</div>
			<?php
			if ($priv === 'Admin') { ?>
				<p><a href=".">Return to user list</a></p>
		<?php }
		endif;
		if (isset($prompt)) {
			ob_end_clean();
			include TEMPLATE . 'prompt.html.php';
		}
		?>
		<p><a href="..">Return to uploads</a></p>
		<?php
		include TEMPLATE . '_logout.html.php';
		exit();
		?>
	</div>
</body>
</html>