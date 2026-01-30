<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/helpers.inc.php'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<link href="../css/lofi.css" type="text/css" rel="stylesheet" media="all"/>
		<link href="../css/sap.css" type="text/css" rel="stylesheet" media="all"/>

		<title>Manage Users</title>
	</head>

	<body>
	<div>
		<h1><?php echo $manage; ?></h1>
		<?php if ($priv =='Admin') : ?>
		<p><a href="?add">Add New User</a></p>
		<?php endif; ?>
		

<?php if ($priv =='Admin' and !isset($_POST['act'])): ?>
<form action="" method="post" name="userform">
<ul><li><label for="user">User: </label><select id="user" name="user"><option value="">Select one</option>
<optgroup label="clients"><?php  foreach ($client as $x => $c): ?>
<option value="<?php htmlout($x); ?>"><?php htmlout($c); ?>
</option><?php endforeach; ?></optgroup>
<optgroup label="users">
<?php  foreach ($users as $ix => $u): ?>
<option value="<?php htmlout($ix); ?>"><?php htmlout($u); ?>
</option><?php endforeach; ?></optgroup></select>
<input type="submit" name="act" value="Choose"/></li>
</ul>
</form>


<?php  elseif($priv =='Client' or (isset($_POST['act']) and $_POST['act']=='Choose')):
foreach ($users as $k =>$user):?>

<!--
<form action="" method="post" name="edituserform">
<label><?php htmlout($user); ?></label>
<input type="hidden" name="id" value="<?php echo $k ;?>"/>
<input type="submit" name="action" value="Edit"/>&nbsp;<input type="submit" name="action" value="Delete"/>
</form>-->

<form action="" method="post" name="edituserform">
<ul><li><label><?php htmlout($user); ?></label></li>
<li><label>Edit<input type="radio" name="action" value="Edit"/></label>
<label>Delete<input type="radio" name="action" value="Delete"/></label></li>
<input type="hidden" name="id" value="<?php echo $k ;?>"/>
<li><input type="submit" value = "Submit"/></li>
</ul></form>


<?php
endforeach;
endif;
?>
<p><a href="..">Return to uploads</a></p>
<!--<p><a href="<?php //include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/index.php';?>">Return to uploads</a></p>-->
		
<?php
if (isset($prompt)) {
include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/templates/prompt.html.php';
}
include '../includes/logout.inc.html.php'; 
//include $_SERVER['DOCUMENT_ROOT'] . '/includes/logout.inc.html.php'; 
exit(); 
?>
</div>
</body>
</html>