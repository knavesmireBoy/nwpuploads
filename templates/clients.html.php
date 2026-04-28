<h1>Manage Clients</h1>
<?php
if (isset($template)) {
	ob_start();
}
include TEMPLATE . '_call.html.php';
?>
<?php
if (preg_match("/admin/i", $priv)) { ?>
	<form action="" method="post" name="clientsform" class="choose">
		<label for="the_client"></label>
		<select name="client" id="the_client">
			<option value="">Select one</option>
			<?php foreach ($clients as $client): ?>
				<option value="<?= $client['id']; ?>">
					<?= $client['name']; ?></option>
			<?php endforeach; ?>
		</select>
		<input type="submit" name="action" value="Choose" />
	</form>
<?php }
if (isset($selected) && $_POST['client'] !== '') {
	include 'clientform.html.php';
}
if (isset($template)) {
	ob_end_clean();
	include TEMPLATE . "$template";
}
if (isset($clientid)) { ?>
	<p><a href="/client/load">Return to clients</a></p>
<?php } ?>
<p><a href="user/load/">Return to users</a></p>

</main>
<footer>
	<?php
	include TEMPLATE . '_logout.html.php'; ?>
</footer>