<?php
/*
$selected = true;
$domain = false;
$setcookie = doSetCookie(false);
*/
$load = '/user/load/';
?>
<h1><?= $pagehead; ?></h1>
<?php if (isset($message)) { ?>
	<p><?= $message; ?></p>
<?php
}
if (isset($callroute)) {
	include TEMPLATE . '_call.html.php';
}
?>
<form action="<?= $action; ?>" method="post" name="usersform" class="<?= $class ?? 'details'; ?>">
	<?php
	if (isset($class) && preg_match("/override/", $class)) {
	?>
		<a href="<?= $load; ?>" class="cancel">X</a>
	<?php }
	if (isset($legend)) { ?>
		<p><?= $legend; ?></p>
	<?php	}
	?>
	<div>
		<label for="name">Name</label><input id="name" type="text" name="data[name]" value="<?= $name ?? ''; ?>" required autocomplete="off" />
		<label for="email">Email</label><input type="email" id="email" name="data[email]" value="<?= $email ?? ''; ?>" required autocomplete="off" />
		<label for="password">Password</label><input id="password" type="password" name="data[password]" autocomplete="new-password" />
	</div>
	<?php include TEMPLATE . '_roles.html.php';
	include  TEMPLATE . '_clientlist.html.php'; ?>
	<input type="hidden" name="id" value="<?= $id ?? ''; ?>" />
	<input type="hidden" name="override" value="<?= $override ?? ''; ?>" />
	<input type="submit" value="<?= $button; ?>" />
</form>
<?php
include "adminfooter.html.php";
