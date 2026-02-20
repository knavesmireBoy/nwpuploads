<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/helpers.inc.php';
include_once TEMPLATE . 'base.html.php';
?>
<div>
	<h1><?= $pagehead; ?></h1>
	<form action="?<?= $action; ?>" method="post" name="clientform">
		<div>
			<label for="the_name">Name</label>
			<input id="the_name" type="text" name="name" value="<?= $name; ?>" />
		</div>
		<div>
			<label for="the_domain">Domain</label>
			<input id="the_domain" type="text" name="domain" value="<?= $domain; ?>" />
		</div>
		<div>
			<label for="the_tel">Phone</label>
			<input id="the_tel" type="text" name="tel" value="<?= $tel; ?>" />
			<?php if (preg_match('/edit/', $action)) { ?>
				<input id="delete" type="checkbox" name="delete" />
				<label for="delete">Delete</label>
			<?php } ?>
		</div>
		<input type="hidden" name="id" value="<?= $id; ?>" />
		<input type="hidden" name="action" value="<?= $route; ?>" />
		<input type="submit" value="<?= $button; ?>" />
	</form>
	<p><a href=".">Return to Client List</a></p>
</div>
</body>