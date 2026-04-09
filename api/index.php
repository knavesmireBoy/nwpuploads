<?php
require_once 'config.php';
include FUNCTIONS;
//phpinfo();
$layout = 'pagelayout.html.php';
$route = fixUri();
$uri = empty($route) ? '' : implode('/', $route);
$home = 'home';
//array of "actions" which need submit adding to string for processing forms; eg assignSubmit
$posts = ['assign', 'create', 'contact', 'edit', 'login', 'manage', 'permissions', 'register', 'retrieve', 'retire', 'unarchive', 'relocate', 'swap'];
$pp = $pages[$route[0]] ?? '';
$website = new \PoloAfrica\PoloAfricaWebsite($pp);
$entryPoint = new \Ninja\EntryPoint($website, $posts);
$layoutVariables = $entryPoint->run($uri, $_SERVER['REQUEST_METHOD'], 'public', $home);
echo $entryPoint->loadTemplate($layout, $layoutVariables);