<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/config.php';

function safeFilter($array, $cb)
{
    return array_values(array_filter($array, $cb));
}

function reAssignClient()
{
  $sql = "SELECT user.id, RIGHT(user.email, LENGTH(user.email) - LOCATE('@', user.email)) AS dom FROM user LEFT JOIN userrole ON userid = user.id WHERE roleid = 'Client Admin' ORDER by dom";
  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
  $update = "UPDATE userrole SET roleid = 'Client' WHERE userid=:id";
  $st = doQuery($pdo, $sql, 'fail');
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  $l = count($rows);
  for ($i = 0; $i < $l; $i++) {

    if ($i && $rows[$i - 1]['dom'] === $rows[$i]['dom']) {
      $st = $pdo->prepare($update);
      $st->bindValue(":id", $rows[$i]['id']);
      doPreparedQuery($st, 'failure');
    }
  }
}

function reOrderTable() {

/*
$st = doQuery($pdo, "SELECT id FROM user ORDER by id", "");
$count = 1;
$sql = "UPDATE user SET id=:count WHERE id=:current";
$all = $st->fetchAll(PDO::FETCH_NUM);
$all = array_merge(...$all);
$l = count($all);

for ($i = 0; $i < $l; $i++) {
  $cur = $all[$i];
  if ($cur !== $count) {
    $st = $pdo->prepare($sql);
    $st->bindValue(":count", $count);
    $st->bindValue(":current", $cur);
    doPreparedQuery($st, "Error updating table");
  }
  $count++;
}
  $sql = ALTER table X AUTO_INCREMENT = $count;...
*/
}

function checkVars($arr, $pagevars = [])
{
    $arr = array_keys($arr);
    foreach ($arr as $key => $value) {
        if (preg_match('/_[A-Z]+/', $key)) unset($arr[$key]);
    }
    return empty(array_diff($pagevars, $arr));
}

function sanitizeInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
function formatDate($date)
{
    return date('F j, Y', strtotime($date));
}
function generateRandomString($length = 10)
{
    return substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / strlen($x)))), 1, $length);
}

function identity($arg)
{
    return $arg;
}

function add($a, $b)
{
    return $a + $b;
}

function multiply($a, $b)
{
    return $a * $b;
}

//https://eddmann.com/posts/using-partial-application-in-php/
function partial($func, ...$args)
{
    return function (...$newargs) use ($func, $args) {
        return $func(...$args, ...$newargs);
    };
}

function curry2($fun)
{
    return function ($arg2) use ($fun) {
        return function ($arg1) use ($fun, $arg2) {
            return $fun($arg1, $arg2);
        };
    };
}

function composer(...$fns)
{
    return array_reduce($fns, function ($f, $g) {
        return function (...$vals) use ($f, $g) {
            $f($g(...$vals));
        };
    }, 'identity');
}


function lastInsert($pdo, $db = 'mysql')
{
    if ($db = 'postgres') {
    }
    return $pdo->lastInsertId();
}

function fromStrPos($db = 'mysql')
{
    if ($db === 'postgres') {
        return "substring(email FROM POSITION('@' IN email) + 1)";
    } else {
        return "RIGHT(email, LENGTH(email) - LOCATE('@', email))";
    }
}

function replaceStrPos($new, $db = 'mysql')
{
    if ($db === 'postgres') {
        return "CONCAT(LEFT(email, substring(email FROM POSITION('@' IN email) + 1)), '$new')";
    } else {

        return "CONCAT(LEFT(email, INSTR(email, '@')), '$new')";
    }
}

function getRemoteAddr()
{
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
        $ipAddress = array_pop(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
    }
    return $ipAddress;
}


function qsort($q)
{
    $res = explode($q, $_SERVER['QUERY_STRING']);
    $rest = isset($res[0]) ? $res[0] : '';
    $sort = isset($res[1]) ? $res[1] : '';
    $sort = preg_replace("/[^ftu]/", '', $sort);
    return [$rest, $sort];
}
//queries the current $_SERVER['QUERY_STRING'] and determines what the next "route" would be
//user can sublist by TIME or FILENAME (points; goal diff)
function qUserHead($char)
{
    return function ($str) use ($char) {
        $l = strlen($str);
        $ret = '';
        if (!$l) {
            return $char;
        }
        $match = isset($str[0]) && $str[0] === $char;
        $nomatch = isset($str[0]) && $str[0] !== $char;
        if ($match) {
            $next = isset($str[1]) && $str[0] === $str[1];
            $ret = $next ? substr($str, 1) : $char . $str;
        } else if ($nomatch) {
            $ret = $char . $str;
        }
        return $ret;
    };
}
//MAY be subservient to user but otherwise examines the query string and removes anything that fails to match
function qHead($char, $permitted = '')
{
    return function ($str) use ($char, $permitted) {
        $l = strlen($str);
        $ret = '';
        if (!$l) {
            return $char;
        }
        $match = isset($str[0]) && $str[0] === $char;
        $nomatch = isset($str[0]) && $str[0] !== $char;
        if ($match) {
            $next = isset($str[1]) && $str[0] === $str[1];
            $ret = $next ? substr($str, 1) : $char . $str;
        } else if ($nomatch) {
            $sanitize = preg_replace("/$char/", '', $str);
            $sanitize = preg_replace("/$permitted/", '', $sanitize);
            if (isset($sanitize[0])) {
                $str = preg_replace("/$sanitize[0]/", '', $str);
            }
            if (isset($str[0]) && $str[0] === $permitted) {
                $single = preg_match("/$char/", $str);
                $double = preg_match("/$char$char/", $str);
                $repl = preg_replace("/$char/", '', $str);
                $next = $double ? $char : ($single ? "$char$char" : $char);
                $ret =  $repl . $next;
            } else {
                return $char;
            }
        }
        return $ret;
    };
}

function doQuery($pdo, $sql, $msg)
{
    try {
        return $pdo->query($sql);
    } catch (PDOException $e) {
        $error = $msg . ' ' . $e->getMessage();
        $root =  $_SERVER['DOCUMENT_ROOT'] . '/api/';
        $root =  $_SERVER['DOCUMENT_ROOT'];
        include '../templates/error.html.php';
        exit();
    }
}

function doPreparedQuery($st, $msg, $flag = false)
{
    try {
        if ($flag) {
            $st->execute();
            $count = $st->rowCount();
            if ($count) {
                return $st->fetchAll();
            } else {
                return false;
            }
        }
        return $st->execute();
    } catch (PDOException $e) {
        $error = $msg . ' ' . $e->getMessage();
        $root =  $_SERVER['DOCUMENT_ROOT'] . '/api/';
        $root =  $_SERVER['DOCUMENT_ROOT'];
        include '../templates/output.html.php';
        exit();
    }
}

function dump($arg)
{
    var_dump($arg);
    exit;
}
function seek()
{
    $arr = array(
        'suffix',
        'user_id',
        'text',
        'ext',
        'useroo',
        'textme'
    );
    $i = count($arr);
    while ($i--) {
        if (!empty($GLOBALS[$arr[$i]])) {
            return '.';
        }
    }
    return '?find';
}


function bbcode2html($text)
{
    $text = html($text); // [B]old
    $text = preg_replace('/\[B](.+?)\[\/B]/i', '<strong>$1</strong>', $text);
    // [I]talic
    $text = preg_replace('/\[I](.+?)\[\/I]/i', '<em>$1</em>', $text);
    // Convert Windows (\r\n) to Unix (\n)
    $text = str_replace("\r\n", "\n", $text);
    // Convert Macintosh (\r) to Unix (\n)
    $text = str_replace("\r", "\n", $text);
    // Paragraphs
    $text = '<p>' . str_replace("\n\n", '</p><p>', $text) . '</p>';
    // Line breaks
    $text = str_replace("\n", '<br/>', $text);
    // [URL]link[/URL]
    $text = preg_replace('/\[URL]([-a-z0-9._~:\/?#@!$&\'()*+,;=%]+)\[\/URL]/i', '<a href="$1">$1</a>', $text);
    // [URL=url]link[/URL]
    $text = preg_replace('/\[URL=([-a-z0-9._~:\/?#@!$&\'()*+,;=%]+)](.+?)\[\/URL]/i', '<a href="$1">$2</a>', $text);
    return $text;
}
function bbcodeout($text)
{
    echo bbcode2html($text);
}
function html($text)
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
function htmlout($text)
{
    echo html($text);
}
function add_querystring_var($url, $key, $value)
{
    $url = preg_replace('/(.*)(\?|&)' . $key . '=[^&]+?(&)(.*)/i', '$1$2$4', $url . '&');
    $url = substr($url, 0, -1);
    if (strpos($url, '?') === false) {
        return ($url . '?' . $key . '=' . $value);
    } else {
        return ($url . '&' . $key . '=' . $value);
    }
}

function remove_querystring_var($url, $key)
{
    $url = preg_replace('/(.*)(\?|&)' . $key . '=[^&]+?(&)(.*)/i', '$1$2$4', $url . '&');
    $url = substr($url, 0, -1);
    return ($url);
}
function doSanitize($lnk, $arg)
{
    return mysqli_real_escape_string($lnk, $arg);
}
function doSafeFetch($lnk, $sql)
{
    //assumes query works!!
    return mysqli_fetch_array(mysqli_query($lnk, $sql));
}
function doFetch($lnk, $sql, $msg)
{
    $result = mysqli_query($lnk, $sql);
    if (!$result) {
        $error = $msg;
        include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
        exit();
    }
    return $result;
}

function formatFileSize($size)
{
    if ($size > 1024) {
        return number_format($size / 1024, 2, '.', '') . 'mb';
    }
    return ceil($size) . 'kb';
}
