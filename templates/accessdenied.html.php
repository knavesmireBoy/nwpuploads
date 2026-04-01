<?php include 'head.html.php';
//$root = $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/api/';
$route = preg_match("/administrators/i", $error) ? '..' : '.';
$route = preg_match("/administrators/i", $error) ? "../?action=logout" : "./?action=logout";
?>
	<h1>Access Denied</h1>
	<p><?= $error; ?></p>
	<?php
	//header("Location: ./?action=logout&error=$error");
	//include '_logout.html.php';
	?>
	<p><a href="<?= $route; ?>">Back</a></p>
</body>
</html>