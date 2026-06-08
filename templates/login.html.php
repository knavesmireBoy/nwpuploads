<?php
$pagetitle = isset($pagetitle) ? $pagetitle :  "Log In";
$e = $loginerror ?? $_GET['loginerror'] ?? '';
?>
<h1>Log In</h1>
<?php
if (!empty($e)) { ?>
	<h3><?= $e; ?></h3>
<?php } else { ?>
	<h2>Please log in to upload or download files</h2>
<?php } ?>
<form action="<?= $action; ?>" method="post" name="loginform" class="details">
	<label for="email">Email</label>
	<input id="email" type="email" name="email" autocomplete="off" required />
	<label for="password">Password</label>
	<input id="password" type="password" name="password" autocomplete="off" required />
	<input type="hidden" name="action" value="login" /><input type="submit" value="Log in" />
</form>

<?php
if (isset($ret)) { ?>
	<p><a href="<?= $ret; ?>">Return to uploads</a></p>
<?php }   ?>
</body>

</html>