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
define("FUNCTIONS", './includes/helpers.inc.php');
define("ENTITY", 'PoloAfrica/entity/');
define("INCLUDES", 'includes/');
define("MARKDOWN", 'Ninja/Markdown');
define("MICHELF", '../Michelf/MarkdownExtra.php');

define("TEMPLATE", '../templates/');
define("PAGES", 'pages/');
define("WEBSITES", '../../');
define("FILESTORE_DIR", '../../filestore/');

define("CSS", '/resources/css/');
define("JS", '/resources/js/');
define("ASSETS", '/resources/assets/');
define("RESOURCES", '/resources/');
define("PDF_FILE", '/resources/images/dev/pdf_sq.png');

define("IMAGES", '/resources/images/articles/');
define("DEV", '/resources/images/dev/');

define("FILENOTFOUND", '/resources/images/dev/lost.png');
define("VIDEO_MEDLEY", '/resources/video/medley/');
define("VIDEOS", '/resources/video/');
define ("HTTPREG", '/https?:\/\/(?:www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b(?:[-a-zA-Z0-9()@:%_\+.~#?&\/=]*)/');

//define("FILENOTFOUND", '/resources/images/dev/beatles-for-sale.jpg');
//define("THUMBS", '/resources/images/articles/thumb/');
//define("IMG", '/resources/images/');

define("GALLERY", '/resources/images/gallery/');
define("GALLERY_IMG", '/resources/images/gallery/fullsize/');
define("GALLERY_THUMBS", '/resources/images/gallery/thumbs/');
define("ARTICLE_IMG", '/resources/images/articles/fullsize/');
define("ARTICLE_THUMB", '/resources/images/articles/thumb/');
define("TRANSIT", 'resources/transit/');

define("VIDEO_EXT", ['.mp4', '.ogv', '.webm', '.avi', '.mov']);
define("VIDEO_CODECS", [
    'video/mp4; codecs="avc1.42E01E, mp4a.40.2"',
    'video/webm; codecs="vp8, vorbis"',
    'video/ogg; codecs="theora, vorbis"'
]);

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

define("ARTICLES_LIST", '/article/list/');
define("ARTICLES_ARCHIVED", '/article/archived');
define("ARTICLES_EDIT", '/article/edit/');
define("ARTICLES_ADD", '/article/add');
define("ARTICLES_DEL", '/article/delete/');
define("ARTICLES_DESTROY", '/article/destroy/');
define("ARTICLES_RETIRE", '/article/retire/');
define("ARTICLES_RESTORE", '/article/restore/');
define("ARTICLES_UNARCHIVE", '/article/unarchive/');
define("ARTICLES_CONFIRM", '/article/confirm/');
define("ARTICLES_MOVE", '/article/move/');
define("ASSETS_EDIT", '/article/assets/');

define("ASSET_LIST", '/asset/list/');
define("ASSET_DEL", '/asset/delete/');
define("ASSET_ADD",  '/asset/add/');
define("ASSET_ASSIGN",  '/asset/assign/');
define("ASSET_EDIT",  '/asset/edit/');
define("ASSET_REPLACE",  '/asset/replace/');
define("ASSET_UPLOAD",  '/asset/upload/');
define("ASSET_RELOAD",  '/asset/reload/');
define("ASSET_LOAD",  '/asset/loadpic/');
define("ASSET_LOOP",  '/asset/loop/');
define("ASSET_MANAGE",  '/asset/manage/');
define("ASSET_ON_UPLOAD",  '/asset/onupload/');
define("ASSET_DESTROY",  '/asset/destroy/');
define("ASSET_CONFIRM",  '/asset/confirm/');
define("ASSET_REMOVE",  '/asset/remove/');
define("ASSET_RETIRE",  '/asset/retire/');
define("ASSET_RETRIEVER",  '/asset/retriever/');
define("ASSET_UNTRACKED",  '/asset/getuntracked/');
define("ASSET_META",  '/asset/metadata/');

define("GAL_EDIT",  '/gallery/edit/');
define("GAL_NEXT",  '/gallery/next/');
define("GAL_PREV",  '/gallery/prev/');
define("GAL_NEXT_PP",  '/gallery/nextpage/');
define("GAL_PREV_PP",  '/gallery/prevpage/');
define("GAL_ON_UPLOAD",  '/gallery/onupload/');
define("GAL_UP",  '/gallery/upload/');
define("GAL_RELOAD",  '/gallery/reload/');
define("GAL_ASSIGN",  '/gallery/assign/');
define("GAL_LOAD",  '/gallery/loadpic/');
define("GAL_MANAGE",  '/gallery/manage/');
define("GAL_LIST",  '/gallery/display');
define("GAL_REVIEW",  '/gallery/review');
define("GAL_TRANSIT",  '/gallery/transit/');
define("GAL_UNTRACKED",  '/gallery/getuntracked/');

define("LOGOUT", '/logger/logout');
define("LOGIN", '/logger/login');
define("REG", '/logger/reg/');

define("PAGES_LIST",  '/pages/list');
define("PAGES_EDIT",  '/pages/edit/');
define("PAGES_ADD",  '/pages/add');
define("PAGES_APPROVE",  '/pages/approve/');
define("PAGES_DEL",  '/pages/delete/');

define("QUIT", '');
define("BBC", 'https://www.bbc.co.uk');
define("RELOAD", 'http://localhost/');
define("MARKDOWN_GUIDE", '../templates/markdown_guide.html');

define("HOME", '/home/');
define("TRUST", '/trust/');
define("SCHOLARS", '/scholars/');
define("PLACE", '/place/');
define("STAY", '/stay/');
define("POLO", '/polo/');
define("MEDLEY", '/medley/');
define("ENQUIRIES", '/enquiries/');
define("PHOTOS",  '/gallery/display');


include './includes/autoload.php';