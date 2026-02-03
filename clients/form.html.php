<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/helpers.inc.php'; ?>

<!DOCTYPE html>
<html class="no-js" lang="en">
<head>
	<meta charset="utf-8">
	<meta content="IE=edge" http-equiv="X-UA-Compatible">
	<meta content="width=device-width, initial-scale=1" name="viewport">
	<title>Admin | Client</title>
	<link href="../css/lofi.css" type="text/css" rel="stylesheet" media="all" />
	<script>
		document.cookie = 'resolution=' + Math.max(screen.width, screen.height) + '; path=/';
	</script>
</head>
<body>
	<div>
		<h1><?php htmlout($pagetitle); ?></h1>
		<form action="?<?php htmlout($action); ?>" method="post" name="clientform">
			<div>
				<label for="the_name">Name</label>
				<input id="the_name" type="text" name="name" value="<?php htmlout($name);  ?>" />
			</div>
			<div>
				<label for="the_domain">Domain</label>
				<input id="the_domain" type="text" name="domain" value="<?php htmlout($domain); ?>" />
			</div>

			<div>
				<label for="the_tel">Phone</label>
				<input id="the_tel" type="text" name="tel" value="<?php htmlout($tel); ?>" />
			</div>
			<input type="hidden" name="id" value="<?php htmlout($id); ?>" />
			<input type="submit" value="<?php htmlout($button); ?>" />
		</form>
		<p><a href="./">Return to Client List</a></p>
	</div>
</body>

</html>