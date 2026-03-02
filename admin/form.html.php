<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/helpers.inc.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/config.php';
include TEMPLATE . 'base.html.php';
$selected = true;
$domain = false;

?>
<h1><?= $pagehead; ?></h1>
<?php if (!empty($message)) { ?>
	<p><?= $message; ?></p>
<?php
}
if ((isApproved($priv, 'admin') || $editor) && !empty($id)) {
	include TEMPLATE . '_call.html.php';
}
?>
<form action="?<?= $action; ?>" method="post" name="usersform" class="<?= empty($class) ? 'details' : $class; ?>">
	<div>
		<label for="name">Name</label><input id="name" type="text" name="name" value="<?= $name ?? ''; ?>" required autocomplete="off" />
		<label for="email">Email</label><input type="email" id="email" name="email" value="<?= $email ?? ''; ?>" required autocomplete="off" />
		<label for="password">Password</label><input id="password" type="password" name="password" /><input type="hidden" name="employer"
			value="<?= $job ?? ''; ?>" />
	</div>
	<?php include TEMPLATE . '_roles.html.php'; ?>
	<?php include  TEMPLATE . '_clientlist.html.php'; ?>
	<input type="hidden" name="id" value="<?= $id ?? ''; ?>" />
	<input type="hidden" name="action" value="<?= $route; ?>" />
	<input type="hidden" name="override" value="<?= $override ?? ''; ?>" />
	<input type="submit" value="<?= $button; ?>" />
</form>
<?php
include "_footer.html.php";
