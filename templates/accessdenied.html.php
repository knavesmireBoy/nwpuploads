<?php include 'base.html.php';
$root = $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/';
$admin = preg_match("/administrators/i", $error);//?find a better way
$route = $admin ? '..' : '.';
?>
	<h1>Access Denied</h1>
	<p><?= $error; ?></p>
	<?php
	header("Location: ./?action=logout&error=$error");
	exit();
	include '_logout.html.php';
	?>
	<p><a href="<?= $route; ?>">Back</a></p>
</body>
</html>