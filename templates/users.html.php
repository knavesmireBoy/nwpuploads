<h1><?= $pagehead; ?></h1>
<p class='error'><?= $error; ?></p>
<?php

if ($admin || isset($editor)) {
	ob_start();
	$obstart = true;
	include TEMPLATE . '_call.html.php';
}
?>
<?php
if (empty($selected)):
?>
	<form action="/user/select/" method="post" name="userform" class="choose">
		<label for="user"></label><select id="user" name="user">
			<option value="">Select one</option>
			<?php if ($optgroup) {
				$group = $clients;
				include TEMPLATE . '_optgroup.html.php';
				$optgroup = 'users';
				$group = $users;
				include TEMPLATE . '_optgroup.html.php';
			}
			?>
		</select>
		<input type="submit" name="action" value="Choose" />
	</form>
<?php elseif (preg_match("/client/i", $priv) || (isset($selected))):
?>
	<div class="clientgroup">
		<?php
		foreach ($users as $k => $user):
			include '_users.html.php';
		endforeach;
		?>
	</div>
<?php
endif;
if (isset($prompt) && isset($obstart)) {
	ob_end_clean();
	include TEMPLATE . 'prompt.html.php';
}
?>
<?php
include "adminfooter.html.php";
