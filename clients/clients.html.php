<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/helpers.inc.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/config.php';
?>
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
		<p><a href="./?add">Add New Client</a></p>


		<?php
		ob_start();
		if (preg_match("/admin/i", $priv) && !isset($_POST['act'])) { ?>
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
		<?php }

		if (isset($_POST['act']) && $_POST['act'] == 'Choose' && $_POST['client'] != '') {
			include 'form.html.php';
		}

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