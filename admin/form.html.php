<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/uploads/includes/helpers.inc.php';?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
		<link href="../css/lofi.css" type="text/css" rel="stylesheet" media="all"/>
	</head>
	<body><div>
		<h1><?php htmlout($pagetitle); ?></h1>
		<form action="?<?php htmlout($action); ?>" method="post" name="usersform">
			<ul><li><label for="name">Name:</label><input id="name" type="text" name="name" value="<?php htmlout($name); ?>" size="32"/></li>
			<li><label for="email">Email:</label><input id="email"  name="email" value="<?php htmlout($email); ?>" size="32"/></li><li><label for="password">Set password:</label><input id="password" type="password" name="password"/><input type="hidden" name="employer" value="<?php if(isset($cid)) {htmlout($cid);} ?>" size="32"/></li></ul>
				
<?php if ($priv =='Admin') : ?>
			<fieldset>
				<legend>Roles:</legend> <?php for ($i = 0; $i < count($roles); $i++): ?>
				<div>
					<label for="role<?php echo $i; ?>"><input id="role<?php echo $i; ?>" type="checkbox" name="roles[]" value="<?php htmlout($roles[$i]['id']); ?>" <?php if ($roles[$i]['selected']) { echo 'checked="checked"'; } ?>/>
                       <?php htmlout($roles[$i]['id']); ?></label>: <?php htmlout($roles[$i]['description']); ?></div>
				<?php endfor; ?>
			</fieldset>
<div><label for="employer">Company: </label>
<select name="employer" id="employer">
<option value="">Assign to Client?</option>
<?php foreach ($clientlist as  $i => $client): ?>
<option value="<?php echo $i; ?>" <?php if(isset($job) && $job==$i) echo 'selected="selected"' ?>>
<?php htmlout($client);?></option>
<?php endforeach; ?>
</select></div>
<?php endif; ?>
<div><input type="hidden" name="id" value="<?php htmlout($id); ?>"/><input type="submit" value="<?php htmlout($button); ?>"/></div></form>
		<p><a href=".">Return to User List</a></p>
          <!--<p><a href="<?php //$_SERVER['DOCUMENT_ROOT'] . '/admin/index.php';?>">Return to User List</a></p>-->
		<?php if ($priv =='Admin') : ?>
		<p><a href="../clients/">Edit Clients</a></p>
      <!--<p><a href="<?php //$_SERVER['DOCUMENT_ROOT'] . '/clients/';?>">Edit Clients</a></p>-->

<?php endif;  ?>

</div></body>

</html>