<?php include_once $_SERVER['DOCUMENT_ROOT'] .    '/nwp_uploads/includes/helpers.inc.php'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html lang="en" xml:lang="en" xmlns="http://www.w3.org/1999/xhtml">

	<head>
		<title>Access Denied</title>
		<meta http-equiv="content-type" content="text/html;charset=utf-8"/>
	</head>

	<body>
		<h1>Access Denied</h1>
		<p><?php echo htmlout($error); ?></p>
		<?php include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/logout.inc.html.php'; 
?>
	</body>

</html>