<?php
function autoloader($className) {
    $fileName = str_replace('\\', '/', $className) . '.php';
    $file = __DIR__  . "/$fileName";
    include $file;
}

function autoloader1($className) {
    $fileName = str_replace('\\', '/', $className) . '.php';
    include $fileName;
}

//ini_set( "display_errors", true);
//ini_set( "display_errors", false);
ini_set('memory_limit', '1024M'); // or you could use 1G
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '125M');
ini_set('pcre.jit', false);
date_default_timezone_set( "Europe/London" );
//define("FILESTORE", __DIR__ . '/filestore/');
define("FILESTORE", '/tmp/');

define("TEMPLATE", __DIR__ . '../../templates/');
define("BASE", __DIR__ . '../templates/base.html');

define("INCLUDES", __DIR__ . '/includes/');
define("HELPERS", __DIR__ . '/includes/helpers.inc.php');
define("FUNCTIONS", __DIR__ . '/includes/helpers.inc.php');
define("ACCESS", __DIR__ . '/includes/access.inc.php');
define("WEBSITE", '/nwp_uploads/api/');
define('BASE_PATH', __DIR__);
define('CONNECT', __DIR__  . '/includes/db.inc.php');
define('DBSYSTEM', 'postgres');
define('SUPERUSER', 'files@northwolds.co.uk');
//define('DBSYSTEM', 'mysql');
//define('SUPERUSER', 'files@northwolds.co.uk');
define('MYIP', '86.160.57.166');
define('PAGINATE', 5);

define("LOGOUT", '/logger/logout');
define("LOGIN", '/logger/login');
define("REG", '/logger/reg/');
define("BBC", 'https://www.bbc.co.uk');

define("BADMINTON", '/user/admin');
define("USER_EDIT", '/user/edit/');
define("USER_LIST", '/user/list');
define("USER_REG", '/user/register/');
define("USER_RECOVER", '/user/contact/');
define("USER_DENIED", '/user/access');
define("USER_PERMIT", '/user/permissions/');
define("USER_D1", '/user/delete/');
define("USER_D2", '/user/confirm/');
define("USER_PWD", '/user/changepassword/');
define("USER_MAIL", '/user/changeemail/');
define("USER_OK", '/user/success');
define("USER_RESET_PWD", '/user/resetpassword/');
define("USER_RESET_EMAIL", '/user/resetemail/');

spl_autoload_register('autoloader');
session_start();