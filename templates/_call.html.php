<?php
if (isset($callroute) && isset($calltext)) { ?>
    <p class="call"><a href="./?<?= $callroute; ?>"><?= $calltext; ?></a></p>
<?php }
