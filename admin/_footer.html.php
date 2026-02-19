<?php if ((isset($_SESSION['extent']) && $_SESSION['extent'] > 1) || $priv === 'Admin') { ?>
	<p><a href=".">Return to User List</a></p>
<?php

}
if ((isset($_SESSION['extent']) && $_SESSION['extent'] == 1) && $priv !== 'Admin') { ?>
	<p><a href="..">Return to Uploads</a></p>
<?php
}
if ($priv == 'Admin') { ?>
	<p><a href="../clients/">Edit Clients</a></p>
<?php }
include TEMPLATE . '_logout.html.php';
exit();

/*
A standalone client does not require a user list and will need a "Return to Uploads" link below the form
a multi people client can have that on the user list, cute
*/