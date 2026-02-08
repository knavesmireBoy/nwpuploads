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
		<form action="?<?= $action; ?>" method="post" name="usersform">
			<ul>
				<li><label for="name">Name:</label><input id="name" type="text" name="name" value="<?= $name; ?>" size="32" required /></li>
				<li><label for="email">Email:</label><input type="email" id="email" name="email" value="<?= $email; ?>" size="32" required/></li>
				<li><label for="password">Set password:</label><input id="password" type="password" name="password"/><input type="hidden" name="employer"
				value="<?= $job ? $job : ''; ?>" size="32" /></li>
			</ul>
			<?php if ($priv == 'Admin') : ?>
				<fieldset>
					<legend>Roles</legend> <?php for ($i = 0; $i < count($roles); $i++): ?>
						<div>
							<label for="role<?php echo $i; ?>"><input id="role<?php echo $i; ?>" type="checkbox" name="roles[]" value="<?= $roles[$i]['id']; ?>"
									<?= $roles[$i]['selected'] ? 'checked' : ''; ?> />
								<?= $roles[$i]['id']; ?></label>: <?= $roles[$i]['description']; ?>
						</div>
					<?php endfor; ?>
				</fieldset>
				<div><label for="employer">Company: </label>
					<select name="employer" id="employer">
						<option value="">Assign to Client?</option>
						<?php foreach ($clientlist as  $i => $client): ?>
							<option value="<?= $i; ?>" <?= isset($job) && $job == $i ? 'selected' : ''; ?>>
								<?= $client; ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			<?php endif; ?>
			<div><input type="hidden" name="id" value="<?= $id; ?>" />
			<input type="hidden" name="override" value="<?= $override; ?>" />
			<input type="submit" value="<?= $button; ?>" /></div>
		</form>
		<p><a href=".">Return to User List</a></p>
		<?php if ($priv == 'Admin') : ?>
			<p><a href="../clients/">Edit Clients</a></p>
		<?php endif;  ?>
	</div>
</body>

</html>