<?php
$single = isset($_SESSION['extent'])  && $_SESSION['extent'] <= 1;
$k = $single && ($priv === 'Client Admin') ? 'Client' : $priv;
//could be failed attempt to edit form from a TEAM member
$k = !$single ? 'Client Admin' : $k;
$routes = ['Client' => '_return2uploads.html.php', 'Client Admin' => '_return2list.html.php', 'Admin' => '_return2list.html.php'];

if (isset($editor)) {
	$k = $admin ? 'Admin' : $k;
}
else {
	$k = 'Client';
}
$route = $routes[$k];

if ($admin) { ?>
	<p class="call"><a href="/client/load/">Edit Clients</a></p>
<?php
}
include $route;
?>

</main>
<footer>
	<?php
	include TEMPLATE . '_logout.html.php'; ?>
</footer>