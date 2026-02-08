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
		ob_start();
		if ($priv == 'Admin') { ?>
			<p><a href="?add">Add New User</a></p>
		<?php }
		echo "<h2 class='error'>$error</h2>";
		if ($priv == 'Admin' && !isset($_POST['act'])): ?>
			<form action="" method="post" name="userform">
				<ul>
					<li><label for="user">User: </label><select id="user" name="user">
							<option value="">Select one</option>
							<optgroup label="clients"><?php foreach ($client as $x => $c): ?>
									<option value="<?= $x; ?>"><?= $c; ?>
									</option><?php endforeach; ?>
							</optgroup>
							<optgroup label="users">
								<?php foreach ($users as $ix => $u): ?>
									<option value="<?= $ix; ?>"><?= $u; ?>
									</option><?php endforeach; ?>
							</optgroup>
						</select>
						<input type="submit" name="act" value="Choose" />
					</li>
				</ul>
			</form>
			<?php elseif ($priv == 'Client' || (isset($_POST['act']) && $_POST['act'] == 'Choose')):
			foreach ($users as $k => $user): ?>
				<form action="" method="post" name="edituserform" class="prompt">
					<input type="hidden" name="id" value="<?php echo $k; ?>" />
					<p><?= $user; ?></p>
					<label for="edit">Edit</label><input id="edit" type="radio" name="action" value="Edit" />
					<label for="delete">Delete</label><input id="delete" type="radio" name="action" value="Delete" />
					<input type="submit" value="Submit" />
				</form>
			<?php
			endforeach;
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