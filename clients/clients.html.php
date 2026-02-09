<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/helpers.inc.php'; ?>
<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/config.php'; ?>

<!DOCTYPE html>
<html class="no-js" lang="en">

<head>
	<meta charset="utf-8">
	<meta content="IE=edge" http-equiv="X-UA-Compatible">
	<meta content="width=device-width, initial-scale=1" name="viewport">
	<title>Manage Clients</title>
	<link href="../css/main.css" type="text/css" rel="stylesheet" media="all" />
	<script>
		document.cookie = 'resolution=' + Math.max(screen.width, screen.height) + '; path=/';
	</script>
</head>

<body>
	<div>
		<h1>Manage Clients</h1>
		<?php if (isset($template)) {
			ob_start();
			$obstart = true;
		}
		?>
		<p><a href="./?add">Add New Client</a></p>
		<?php

		ob_start();
		if (preg_match("/admin/i", $priv) && !isset($_POST['act'])): ?>
			<form action="" method="post" name="clientsform">
				<label for="the_client">Client: </label>
				<select name="client" id="the_client">
					<option value="">Select one</option>
					<?php foreach ($clients as $client): ?>
						<option value="<?= $client['id']; ?>">
							<?= $client['name']; ?></option>
					<?php endforeach; ?>
				</select>
				<input type="submit" name="act" value="Choose" />
			</form>

			<?php elseif (isset($_POST['act']) && $_POST['act'] == 'Choose'):
			foreach ($clients as $client): ?>
				<form action="" method="post" name="editclientform">
					<ul>
						<li><h1><?= $client['name']; ?></h1></li>
						<li><label for="edit">Edit</label><input id="edit" type="radio" name="action" value="Edit" />
							<label for="delete">Delete</label><input id="delete" type="radio" name="action" value="Delete" />
						</li>
						<li>
							<input type="hidden" name="id" value="<?php echo $client['id']; ?>" />
							<input type="submit" value="Submit" />
						</li>
					</ul>
				</form>
			<?php
			endforeach;
		endif;

		if (isset($template)) {
			ob_end_clean();
			include TEMPLATE . "$template";
		}
		if (isset($clientid)) { ?>
			<p><a href=".">Return to clients</a></p>
		<?php } ?>
		<p><a href="../admin/">Return to users</a></p>
		<?php
		include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/logout.inc.html.php';
		exit();
		?>
	</div>
</body>

</html>