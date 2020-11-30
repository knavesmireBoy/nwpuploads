<h1>Log In</h1>
		<p>Please log in to upload or download files</p>
		<?php if (isset($loginError)): ?>
		<p><?php htmlout($loginError); ?></p>
		<?php endif; ?>
		<form action="." method="post" name="loginform">
			<div>
				<label for="email">Email: <input id="email" type="email" name="email"/></label></div>
			<div>
				<label for="password">Password: <input id="password" type="password" name="password"/></label></div>
			<div>
				<input type="hidden" name="action" value="login"/><input type="submit" value="Log in"/></div>
		</form><?php echo '</div></body></html>';