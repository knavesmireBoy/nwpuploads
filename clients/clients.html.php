<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/helpers.inc.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/config.php';
include_once TEMPLATE . 'base.html.php';
foreach (get_defined_vars() as $k => $v) {
    $i = 0;
    $fail = false;
    $L = count($predicates);
    for ($i; $i < $L; $i++) {
      $fail = $predicates[$i]($k);
      if ($fail) {
        unset($$k);
        break;
      }
    }
  }
  unset($k);
  unset($v);
  unset($i);
  unset($L);
  unset($fail);

?>

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
	include 'form.html.php';
}
if (isset($template)) {
	ob_end_clean();
	include TEMPLATE . "$template";
}
if (isset($clientid)) { ?>
	<p><a href=".">Return to clients</a></p>
<?php } ?>
<p><a href="../admin/">Return to users</a></p>

</main>
<footer>
	<?php
	include TEMPLATE . '_logout.html.php'; ?>
</footer>