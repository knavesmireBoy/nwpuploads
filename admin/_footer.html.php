<?php
/*
A standalone client does not require a user list and will need a "Return to Uploads" link below the form
a multi people client can have that on the user list, cute
//(isset($_SESSION['extent']) && $_SESSION['extent'] > 1) || $priv === 'Admin'
*/

if ($priv == 'Admin') { ?>
	<p class="call"><a href="../clients/">Edit Clients</a></p>
<?php }

if ($selected) { ?>
	<p><a href=".">Return to User List</a></p>
<?php
}
else { ?>
	<p><a href="..">Return to Uploads</a></p>
<?php
} ?>
</main>
<footer>
	<?php
	include TEMPLATE . '_logout.html.php'; ?>
</footer>