<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/helpers.inc.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/config.php';
include TEMPLATE . 'base.html.php'; ?>
<div>
	<h1><?= $pagehead; ?></h1>
	<p><?= $message; ?></p>
	<form action="?<?= $action; ?>" method="post" name="usersform" class="<?= $class ?? ''; ?>">
		<ul>
			<li><label for="name">Name:</label><input id="name" type="text" name="name" value="<?= $name; ?>" required /></li>
			<li><label for="email">Email:</label><input type="email" id="email" name="email" value="<?= $email; ?>" required /></li>
			<li><label for="password">Password:</label><input id="password" type="password" name="password" /><input type="hidden" name="employer"
					value="<?= $job ?? ''; ?>" />
				<?php if (preg_match('/edit/', $action)) { ?>
					<input type="checkbox" id="delete" name="delete"><label for="delete">Delete</label>
			</li>
		<?php } ?>
		</ul>

		<?php include TEMPLATE . '_roles.html.php'; ?>
		<?php include '../templates/_clientlist.html.php'; ?>

		<div><input type="hidden" name="id" value="<?= $id; ?>" />
			<input type="hidden" name="action" value="<?= $route; ?>" />
			<input type="hidden" name="override" value="<?= $override; ?>" />
			<input type="submit" value="<?= $button; ?>" />
		</div>
	</form>
	<?php include '_footer.html.php';
	?>
</div>
</body>

</html>