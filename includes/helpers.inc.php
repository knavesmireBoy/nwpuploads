<?php

function doQuery($pdo, $sql, $msg)
{
    try {
        return $pdo->query($sql);
    } catch (PDOException $e) {
        $error = $msg . ' ' . $e->getMessage();
        $root =  $_SERVER['DOCUMENT_ROOT'] . '/api/';
        $root =  $_SERVER['DOCUMENT_ROOT'];
        include __DIR__ . '/../templates/error.html.php';
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
        include __DIR__ . '/../templates/output.html.php';
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
