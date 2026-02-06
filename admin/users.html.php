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
		<?php if ($priv == 'Admin') : ?>
			<p><a href="?add">Add New User</a></p>
		<?php endif;
		echo $error;
		if ($priv == 'Admin' and !isset($_POST['act'])): ?>
			<form action="" method="post" name="userform">
				<ul>
					<li><label for="user">User: </label><select id="user" name="user">
							<option value="">Select one</option>
							<optgroup label="clients"><?php foreach ($client as $x => $c): ?>
									<option value="<?php htmlout($x); ?>"><?php htmlout($c); ?>
									</option><?php endforeach; ?>
							</optgroup>
							<optgroup label="users">
								<?php foreach ($users as $ix => $u): ?>
									<option value="<?php htmlout($ix); ?>"><?php htmlout($u); ?>
									</option><?php endforeach; ?>
							</optgroup>
						</select>
						<input type="submit" name="act" value="Choose" />
					</li>
				</ul>
			</form>

			<?php elseif ($priv == 'Client' || (isset($_POST['act']) && $_POST['act'] == 'Choose')):
			foreach ($users as $k => $user): ?>
				<!--
<form action="" method="post" name="edituserform">
<label><?php htmlout($user); ?></label>
<input type="hidden" name="id" value="<?php echo $k; ?>"/>
<input type="submit" name="action" value="Edit"/>&nbsp;<input type="submit" name="action" value="Delete"/>
</form>-->
<form action="" method="post" name="edituserform">
					<ul>
						<li><label><?php htmlout($user); ?></label></li>
						<li><label>Edit<input type="radio" name="action" value="Edit" /></label>
							<label>Delete<input type="radio" name="action" value="Delete" /></label>
						</li>
						<input type="hidden" name="id" value="<?php echo $k; ?>" />
						<li><input type="submit" value="Submit" /></li>
					</ul>
				</form>
		<?php
			endforeach;
		endif;
		?>
		<p><a href="..">Return to uploads</a></p>
		<?php
		if (isset($prompt)) {
			include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/templates/prompt.html.php';
		}
		include '../includes/logout.inc.html.php';
		exit();
		 ?>
	</div>
</body>
</html>

