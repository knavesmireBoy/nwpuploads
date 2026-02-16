<?php if ((isset($_SESSION['extent']) && $_SESSION['extent'] > 1) || $priv === 'Admin') { ?>
			<p><a href=".">Return to User List</a></p>
		<?php } ?>
		<p><a href="..">Return to Uploads</a></p>
		<?php
		if ($priv == 'Admin') { ?>
			<p><a href="../clients/">Edit Clients</a></p>
		<?php }
		include '_logout.html.php';
		exit();