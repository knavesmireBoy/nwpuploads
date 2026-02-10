<!DOCTYPE html>
<html class="no-js" lang="en">
<head>
	<meta charset="utf-8">
	<meta content="IE=edge" http-equiv="X-UA-Compatible">
	<meta content="width=device-width, initial-scale=1" name="viewport">
	<title>Manage Users</title>
	<link href="../css/main.css" type="text/css" rel="stylesheet" media="all" />
	<script>
		document.cookie = 'resolution=' + Math.max(screen.width, screen.height) + '; path=/';
	</script>
<body>
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

		if (($priv == 'Admin') && !isset($_POST['act'])): ?>
			<form action="" method="post" name="userform" class="choose">
				<ul>
					<li><label for="user">User: </label><select id="user" name="user">
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
						<input type="submit" name="act" value="Choose" />
					</li>
				</ul>
			</form>
		<?php elseif (preg_match("/client/i", $priv) || (isset($_POST['act']) && $_POST['act'] == 'Choose')):
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
			include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/templates/prompt.html.php';
		}
		?>
		<p><a href="..">Return to uploads</a></p>
		<?php
		include '../includes/logout.inc.html.php';
		exit();
		?>
	</div>
</body>

</html>