<?php include 'base.html.php';
?>
	<h1>Access Denied</h1>
	<p><?= $error; ?></p>
	<?php include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/logout.inc.html.php';
	?>
</body>
</html>