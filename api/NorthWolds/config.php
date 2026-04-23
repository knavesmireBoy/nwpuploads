<?php


//ini_set( "display_errors", true);
ini_set( "display_errors", false);
ini_set('memory_limit', '1024M'); // or you could use 1G
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '125M');
ini_set('pcre.jit', false);
date_default_timezone_set( "Europe/London" );
//define("FILESTORE", __DIR__ . '/filestore/');
define("FILESTORE", '/tmp/');

//define("TEMPLATE", '../../../../templates/');
define("TEMPLATE", __DIR__ . '../../templates/');
define("BASE", __DIR__ . '../templates/base.html');

define("INCLUDES", __DIR__ . '/includes/');
define("HELPERS", __DIR__ . '/includes/helpers.inc.php');
define("ACCESS", __DIR__ . '/includes/access.inc.php');
define("WEBSITE", '/nwp_uploads/api/');
define('BASE_PATH', __DIR__);
define('CONNECT', __DIR__  . '/includes/db.inc.php');
define('DBSYSTEM', 'postgres');
define('SUPERUSER', 'files@northwolds.co.uk');
define('PAGINATE', 10);

define("LOGOUT", '/logger/logout');
define("LOGIN", '/logger/login');
define("REG", '/logger/reg/');

include __DIR__ . '/includes/autoload.php';
//spl_autoload_register('autoloader');
//session_start();