<!DOCTYPE html>
<html class="no-js" lang="en">

<head>
	<meta charset="utf-8">
	<meta content="IE=edge" http-equiv="X-UA-Compatible">
	<meta content="width=device-width, initial-scale=1" name="viewport">
	<title>Admin | Users</title>
	<link href="../css/main.css" type="text/css" rel="stylesheet" media="all" />
	<script>
		document.cookie = 'resolution=' + Math.max(screen.width, screen.height) + '; path=/';
	</script>
</head>

<body>
	<div>
		<h1><?= $pagetitle; ?></h1>
		<p><?= $message; ?></p>
		<form action="?" method="post" name="usersform">
			<ul>
				<li><label for="name">Name:</label><input id="name" type="text" name="name" value="<?= $name; ?>" size="32" required /></li>
				<li><label for="email">Email:</label><input type="email" id="email" name="email" value="<?= $email; ?>" size="32" required /></li>
				<li><label for="password">Set password:</label><input id="password" type="password" name="password" /><input type="hidden" name="employer"
						value="<?= $job ? $job : ''; ?>" size="32" /><label for="delete">Delete</label><input type="checkbox" id="delete" name="delete"></li>
			</ul>
			<?php if (preg_match("/admin/i", $priv)) : ?>
				<fieldset>
					<legend>Roles</legend>
					<?php for ($i = 0; $i < count($roles); $i++): ?>
						<div>
							<label for="role<?= $i; ?>"><input id="role<?= $i; ?>" type="checkbox" name="roles[]" value="<?= $roles[$i]['id']; ?>"
									<?= $roles[$i]['selected'] ? 'checked' : ''; ?> />
								<?= $roles[$i]['id']; ?></label>: <?= $roles[$i]['description']; ?>
						</div>
					<?php endfor; ?>
				</fieldset>

			<?php if (!empty($clientlist)) {
					include '../templates/_clientlist.html.php';
				}

			endif; ?>
			<div><input type="hidden" name="id" value="<?= $id; ?>" />
				<input type="hidden" name="action" value="<?= $route; ?>" />
				<input type="hidden" name="override" value="<?= $override; ?>" />
				<input type="submit" value="<?= $button; ?>" />
			</div>
		</form>
		<?php if (isset($_SESSION['extent']) && $_SESSION['extent'] > 1) { ?>
			<p><a href=".">Return to User List</a></p>
		<?php } ?>
		<p><a href="..">Return to Uploads</a></p>
		<?php
		if ($priv == 'Admin') { ?>
			<p><a href="../clients/">Edit Clients</a></p>
		<?php }
		include '../includes/logout.inc.html.php';
		exit();

		?>
	</div>
</body>

</html>