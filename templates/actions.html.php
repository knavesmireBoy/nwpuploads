<?php
$para = '';
$_logged = "<h6>$username</h6>";
$_logged = "";
if ($loggedIn) {
  if (isset($username)) {
    echo $_logged;
  }
?>
  <ul class="adminlist">
    <?php
    if ($loggedIn->canEdit()) { ?>
      <li><a href="<?= ARTICLES_LIST . 1 ?>">Edit Articles</a></li>
      <li><a href="<?= PAGES_LIST ?>">Edit Pages</a></li>
      <li><a href="<?= GAL_REVIEW ?>">Edit Gallery</a></li>
      <li><a href="<?= USER_LIST ?>">List Users</a></li>
    <?php } else {
      /*
      for users that have no editing rights (1 in permissions table)
      we must provide a link to edit their own details so we take advantage of the logic flow to start buffer here
      */
      ob_start(); ?>
      <p style="text-align: center;">You may edit your own <a href="<?= USER_EDIT ?><?= $loggedIn->id ?>">details</a>.</p>
    <?php
      $para = ob_get_clean();
    } ?>
    <li><a href="<?= LOGOUT ?>" class="noajax">Log Out</a></li>
    <li><a href="/">Public Website</a></li>
  </ul>
<?php }

echo $para;