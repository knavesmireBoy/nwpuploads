<?php

function autoloader($className) {
    $fileName = str_replace('\\', '/', $className) . '.php';
    $file = __DIR__ . '/../' . $fileName;
    include $file;
}


ini_set( "display_errors", true);
//ini_set( "display_errors", false);
ini_set('memory_limit', '1024M'); // or you could use 1G
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '125M');
ini_set('pcre.jit', false);
date_default_timezone_set( "Europe/London" );

define("NINJA", 'Ninja/');
define("CONTROLLERS", 'NorthWolds/controllers/');
define("ENTITY", 'NorthWolds/entity/');
define("INCLUDES", 'includes/');
define("MARKDOWN", 'Ninja/Markdown');
define("MICHELF", '../Michelf/MarkdownExtra.php');
define("FUNCTIONS", './includes/helpers.inc.php');
define("HELPERS", './includes/helpers.inc.php');
define('CONNECT', __DIR__  . '/includes/db.inc.php');
define('DBSYSTEM', 'mysql');
define('SUPERUSER', 'files@northwolds.co.uk');
define("ROOT", $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/');


define("TEMPLATE", '../templates/');
//define("CSS", '../public/css/');
define("CSS",  '../public/css/');
define("JS", '../public/js/');
define("ASSETS", '../public/assets/');
define("PDF_FILE", '/public/images/dev/pdf_sq.png');


define("FILENOTFOUND", '/public/images/dev/lost.png');

define ("HTTPREG", '/https?:\/\/(?:www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b(?:[-a-zA-Z0-9()@:%_\+.~#?&\/=]*)/');

define("LOGOUT", '/logger/logout');
define("LOGIN", '/logger/login');
define("REG", '/logger/reg/');


define("QUIT", '');
define("BBC", 'https://www.bbc.co.uk');
define("RELOAD", 'http://localhost/');
define("MARKDOWN_GUIDE", '../templates/markdown_guide.html');

spl_autoload_register('autoloader');
session_start();