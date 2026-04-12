<?php
//include_once 'head.html.php';
$pagetitle = isset($pagetitle) ? $pagetitle :  "Log In";
$e = $loginerror ?? $_GET['loginerror'] ?? '';
?>
<h1>Log In</h1>
<p>Please log in to upload or download files</p>
<h4><?= $e; ?></h4>
<form action="./bolt/fart" method="post" name="loginform" class="details">
	<label for="email">Email</label>
	<input id="email" type="email" name="email" autocomplete="off" />
	<label for="password">Password</label>
	<input id="password" type="password" name="password" autocomplete="off" />
	<input type="hidden" name="action" value="login" /><input type="submit" value="Log in" />
</form>

<?php
if (isset($ret)) { ?>
	<p><a href="<?= $ret; ?>">Return to uploads</a></p>
<?php }   ?>
</body>

</html>