<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/helpers.inc.php'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head><meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<link href="../css/lofi.css" type="text/css" rel="stylesheet" media="all"/>
		<title>Manage Clients</title>
	</head>
<body><div>
		<h1>Manage Clients</h1>
		<p><a href="./?add">Add New Client</a></p>

<?php if ($priv =='Admin' and !isset($_POST['act'])): ?>

<form action="" method="post" name="clientsform">
<label for="the_client">Client: </label>
<select name="client" id="the_client"><option value="">Select one</option>
<?php foreach ($clients as $client): ?>
<option value="<?php echo $client['id']; ?>">
<?php htmlout($client['name']) ?></option>
<?php endforeach; ?>
</select>
<input type="submit" name="act" value="Choose"/>
</form>


<?php  elseif(isset($_POST['act']) and $_POST['act'] == 'Choose'):

foreach ($clients as $client):?>
<form action="" method="post" name="editclientform">
<ul><li><label><?php htmlout($client['name']); ?></label></li>
<li><label>Edit<input type="radio" name="action" value="Edit"/></label>
<label>Delete<input type="radio" name="action" value="Delete"/></label></li>
<li>
<input type="hidden" name="id" value="<?php echo $client['id']; ?>"/>
<input type="submit" value = "Submit"/>
</li>
</ul></form>
<?php
endforeach;
endif;
?>

<p><a href="../admin/">Return to users</a></p>
<p><a href="..">Return to uploads</a></p>

<?php 
if (isset($prompt)) {
include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/templates/prompt.html.php';
}
include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/logout.inc.html.php';
exit(); 
?>
hello</div>
</body>
</html>