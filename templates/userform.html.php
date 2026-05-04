<?php
$selected = true;
$domain = false;
?>
<h1><?= $pagehead; ?></h1>
<?php if (!empty($message)) { ?>
	<p><?= $message; ?></p>
<?php
}
if (($admin || $editor) && !empty($id)) {
	include TEMPLATE . '_call.html.php';
}
?>
<form action="<?= $action; ?>" method="post" name="usersform" class="<?= empty($class) ? 'details' : $class; ?>">
	<?php
	if (isset($class) && preg_match("/override/", $class)) { ?>
		<a href="/user/load/" class="cancel">X</a>
	<?php }
	if ($legend != '') { ?>
		<p><?= $legend ?? ''; ?></p>
	<?php	}
	?>
	<div>
		<label for="name">Name</label><input id="name" type="text" name="data[name]" value="<?= $name ?? ''; ?>" required autocomplete="off" />
		<label for="email">Email</label><input type="email" id="email" name="data[email]" value="<?= $email ?? ''; ?>" required autocomplete="off" />
		<label for="password">Password</label><input id="password" type="password" name="data[password]" autocomplete="new-password"/>
		<input type="hidden" name="employed" value="<?= $employer ?? ''; ?>" />
	</div>
	<?php include TEMPLATE . '_roles.html.php';
	include  TEMPLATE . '_clientlist.html.php'; ?>
	<input type="hidden" name="id" value="<?= $id ?? ''; ?>" />
	<input type="hidden" name="override" value="<?= $override ?? ''; ?>" />
	<input type="submit" value="<?= $button; ?>" />
</form>
<?php
include "adminfooter.html.php";
