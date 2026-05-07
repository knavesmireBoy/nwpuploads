
<?php
include_once 'pagehead.html.php';
?>
<body id="<?= $pageid ?? ''; ?>">
<main role="main" class="<?= $mainclass ?? ''; ?>">
<?= $output; ?> 
</main>
</body>