<?php
// domain pattern...
// Source - https://stackoverflow.com/a/14878953
// Posted by Aleks G, modified by community. See post 'Timeline' for change history
// Retrieved 2026-04-29, License - CC BY-SA 3.0

if (isset($id)) {
	include TEMPLATE . '_call.html.php';
} ?>
<form action="<?= $action; ?>" method="post" name="clientform" class="details">
	<label for="name">Name</label><input id="name" type="text" name="data[name]" value="<?= $name ?? ''; ?>" required autocomplete="off" />
	<label for="domain">Domain</label><input id="domain" type="text" name="data[domain]" value="<?= $domain ?? ''; ?>" required autocomplete="off"/>
	<label for="tel">Phone</label><input id="tel" type="text" name="data[tel]" value="<?= $tel ?? ''; ?>" autocomplete="off" />
	<input type="hidden" name="id" value="<?= $id ?? ''; ?>" />
	<input type="submit" value="<?= $button; ?>" />
</form>
<p><a href="/client/load/">Return to Client List</a></p>