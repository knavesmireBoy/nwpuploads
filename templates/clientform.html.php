<?php
// domain pattern...
// Source - https://stackoverflow.com/a/14878953
// Posted by Aleks G, modified by community. See post 'Timeline' for change history
// Retrieved 2026-04-29, License - CC BY-SA 3.0

if (isset($id)) {
	include TEMPLATE . '_call.html.php';
} ?>
<form action="<?= $action; ?>" method="post" name="clientform" class="details">
	<label for="the_name">Name</label><input id="the_name" type="text" name="name" value="<?= $name ?? ''; ?>" required pattern="([a-zA-Z0-9]+\s)+" autocomplete="off" />
	<label for="the_domain">Domain</label><input id="the_domain" type="text" name="domain" value="<?= $domain ?? ''; ?>" required pattern="\w+\.(\w\.?){1,2}" autocomplete="off"/>
	<label for="the_tel">Phone</label><input id="the_tel" type="text" name="tel" value="<?= $tel ?? ''; ?>" autocomplete="off" pattern="\d\d{5,6}\s?\d{4,6}"/>
	<input type="hidden" name="id" value="<?= $id ?? ''; ?>" />
	<input type="submit" value="<?= $button; ?>" />
</form>
<p><a href=".">Return to Client List</a></p>