<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/helpers.inc.php';?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
		<link href="../css/lofi.css" type="text/css" rel="stylesheet" media="all"/>
	</head>
	<body>
	<div>
		<h1><?php htmlout($pagetitle); ?></h1>
		<form action="?<?php htmlout($action); ?>" method="post" name="clientform">
			<div>
				<label for="the_name">Name: <input id="the_name" type="text" name="name" value="<?php htmlout($name);  ?>"/></label></div>
				<div>
				<label for="the_domain">Domain: <input id="the_domain" type="text" name="domain" value="<?php  htmlout($domain); ?>"/></label></div>
				
				<div>
				<label for="the_tel">Phone: <input id="the_tel" type="text" name="tel" value="<?php htmlout($tel); ?>"/></label></div>


<input type="hidden" name="id" value="<?php htmlout($id); ?>"/>
<input type="submit" value="<?php htmlout($button); ?>"/>
				
				</form>
		<p><a href="./">Return to Client List</a></p></div>
	</body>

</html>