<?php
$css =  is_dir('../css') ? '../css/main.css' : 'css/main.css';
?>
<!DOCTYPE html>
<html class="no-js" lang="en">
<head>
	<meta charset="utf-8">
	<meta content="IE=edge" http-equiv="X-UA-Compatible">
	<meta content="width=device-width, initial-scale=1" name="viewport">
    <title><?= $pagetitle; ?></title>
    <link href="<?= $css; ?>" type="text/css" rel="stylesheet" media="all" />
	<link rel="shortcut icon" type="image/jpg" href="assets/favicon.ico">
	<script>
		document.cookie = 'resolution=' + Math.max(screen.width, screen.height) + '; path=/';
	</script>
</head>
<body id="<?= $pageid ?? ''; ?>">
	<main>
