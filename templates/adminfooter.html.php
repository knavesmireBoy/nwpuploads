<?php

$admin = isApproved($_SESSION['role'], 'ADMIN');
if ($admin) { ?>
	<p class="call"><a href="/client/load/">Edit Clients</a></p>
<?php
}
include $retour;
?>

</main>
<footer>
	<?php
	include TEMPLATE . '_logout.html.php'; ?>
</footer>