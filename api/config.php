<?php
ini_set( "display_errors", true);
//ini_set( "display_errors", false);
ini_set('memory_limit', '1024M'); // or you could use 1G
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '125M');
ini_set('pcre.jit', false);
date_default_timezone_set( "Europe/London" );

define("NINJA", 'Ninja/');
define("CONTROLLERS", 'PoloAfrica/controllers/');
define("ENTITY", 'PoloAfrica/entity/');
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


define("LOGOUT", '/logger/logout');
define("LOGIN", '/logger/login');
define("REG", '/logger/reg/');


define("QUIT", '');
define("BBC", 'https://www.bbc.co.uk');
define("RELOAD", 'http://localhost/');
define("MARKDOWN_GUIDE", '../templates/markdown_guide.html');

include './includes/autoload.php';