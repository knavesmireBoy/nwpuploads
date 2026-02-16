<?php include 'base.html.php';
$root = $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/';

?>
	<h1>Access Denied</h1>
	<p><?= $error; ?></p>
	<?php  include '_logout.html.php';
	?>
	<p><a href="..">Back</a></p>
</body>
</html>