<?php
include_once 'base.html.php';
?>
<h1>Log In</h1>
<p>Please log in to upload or download files</p>
<?php if (isset($loginError)): ?>
	<p><?= $loginError; ?></p>
<?php endif; ?>
<form action="." method="post" name="loginform">
	<label for="email">Email</label>
	<input id="email" type="email" name="email"/>
	<label for="password">Password</label>
	<input id="password" type="password" name="password"/>
	<input type="hidden" name="action" value="login" /><input type="submit" value="Log in"/>
</form>
</body>
</html>