<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/config.php';
function dump($arg)
{
    var_dump($arg);
    exit;
}


function likeDomain($change, $domain)
{
    if ($change) {
        include CONNECT;
        $st = $pdo->prepare("SELECT client.id FROM client WHERE client.domain LIKE :dom");
        $st->bindValue(":dom", "$domain%");
        doPreparedQuery($st, 'Error finding the domain');
        return $st->fetch();
    }
    return false;
}

function doBest($pred, $actions)
{
    return function (...$args) use ($pred, $actions) {
        return array_reduce($actions, function ($a, $b) use ($pred, $args) {
            return $a && $pred(...$args) ? $a : $b;
        });
    };
}

function checkIsset($o, $props, $flag = false)
{
    if ($flag) {
        return array_filter($props, function ($p) {
            return isset($o[$p]);
        });
    } else {
        return array_reduce($props, function ($agg, $curr) use ($o) {
            return $agg || isset($o[$curr]);
        }, null);
    }
}

function decode($qs)
{
    $res = explode('=', $qs);
    return isset($res[1]) ? urldecode($res[1]) : '';
}

function parseEmail($email)
{

    $i = strpos($email, '@');
    if ($i) {
        $edom = substr($email, $i + 1);
        $i = strrpos($edom, '.');
        $top = substr($edom, $i + 1);
        $second = substr($edom, 0, strlen($edom) - strlen($top) - 1);
        $i = strrpos($second, '.');
    } else { //$email may just be a domain
        $i = strrpos($email, '.');
        $top = substr($email, $i + 1);
        $second = substr($email, 0, $i);
        $i = null;
        $i = strrpos($second, '.');
    }
    if ($i) {
        $aux = substr($second, $i + 1);
        $top = "$aux.$top";
        $second = substr($second,  0, strlen($top) - $i);
    }
    return [$second, $top];
}

function unsetCookie($str)
{
    unset($_COOKIE[$str]);
    setcookie($str, '', -1, '/');
}

function doSetCookie($flag)
{
    return function ($k, $v = '', $time = -1) use ($flag) {

        //need if undefined here
        if (!is_string($v)) {
            if (!is_int($v)) {
                $v = $k;
            }
        }

        if (!is_int($v)) {
            if (!is_string($v)) {
                $v = $k;
            }
        }

        if (!isset($_COOKIE[$k]) && $flag) {
            setcookie($k, $v, $time, '/');
            $_COOKIE[$k] = $v;
        } elseif (isset($_COOKIE[$k]) && !$flag) {
            unset($_COOKIE[$k]);
            setcookie($k, '', -1, '/');
        }
    };
}

function safeFilter($array, $cb)
{
    return array_values(array_filter($array, $cb));
}

function getDefinedVars()
{
    $arr = get_defined_vars();
    foreach ($arr as $key => $value) {
        if (preg_match('/_[A-Z]+/', $key)) unset($arr[$key]);
    }
    return $arr;
}

function isQualified($role, $flag = false)
{
    $a = preg_match("/^admin/i", $role);
    $ca = preg_match("/admin/i", $role);

    return $flag ? $a : $ca;
}


function isApproved($role, $str = 'admin')
{
    $m = '';
    $flag = false;
    if (is_numeric(strpos($str, '!'))) {
        $str = preg_replace('/\W?(\w+)\W?/', '$1', $str);
        $flag = true;
    }
    if (strtoupper($str) === $str) {
        $str = strtolower($str);
        $m = "/^$str/i";
    } else {
        $m = "/$str/i";
    }
    $ret = preg_match($m, $role);
    return $flag ? !$ret : $ret;
}

function reAssignClient($pdo)
{
    $sql = "SELECT user.id, RIGHT(user.email, LENGTH(user.email) - LOCATE('@', user.email)) AS dom FROM user LEFT JOIN userrole ON userid = user.id WHERE roleid = 'Client Admin' ORDER by dom";
    $update = "UPDATE userrole SET roleid = 'Client' WHERE userid=:id";
    $st = doQuery($pdo, $sql, 'Failed to Update Role');
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

function reOrderTable($pdo)
{

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
    $sql = "ALTER table user AUTO_INCREMENT = $count";
    doQuery($pdo, $sql, "Error on Auto Increment");
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
        include TEMPLATE . 'output.html.php';
        //include '../templates/error.html.php';
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
        if ($msg) {
            $error = $msg . ' ' . $e->getMessage();
            $root =  $_SERVER['DOCUMENT_ROOT'] . '/api/';
            $root =  $_SERVER['DOCUMENT_ROOT'];
            include TEMPLATE . 'output.html.php';
            exit();
        }
    }
}

function seek()
{
    $arr = array(
        'suffix',
        'user_id',
        'text',
        'ext',
        'byuser',
        'bytext'
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

function formatFileSize($size)
{
    if ($size > 1024) {
        return number_format($size / 1024, 2, '.', '') . 'mb';
    }
    return ceil($size) . 'kb';
}


function payment1($total, $rate, $pay, $fixed = 0)
{
    $count = 0;
    while ($total > 0) {
        //interest
        $total *= $rate;
        //monthly payment
        $total -= $pay;
        //fixed charges (before or after rate applied?)
        $total += $fixed;
        //duration
        $count++;
    }
    return [$total, $count];
}

function paymentZero($total, $rate, $min = 100)
{
    $count = 0;
    $x = ($total * $rate);
    $y = $total * .01;
    $pay = $x + $y;

    $pay = max($pay, $min);

    while ($total > 0) {
        //monthly payment
        $total -= $pay;
        //interest
        //$total *= $rate;
        //fixed charges (before or after rate applied?)
        //duration
        $count++;
    }
    return [$total, $count];
}


function interest($total, $rate, $dur, $min = 100)
{
    $count = 0;

    $x = ($total * $rate);
    $y = $total * .01;
    $pay = $x + $y;



    $pay = max($pay, $min);
    while ($count < $dur) {
        //monthly payment
        $total -= $pay;
        //interest
        // $total *= $rate;
        //fixed charges (before or after rate applied?)
        //duration
        $count++;
    }
    return [$total, $count];
}


function reAssoc($roles, $keys, $k, $v, $ret, $i, $j)
{
    if (isset($roles[$i]) && isset($keys[$j])) {
        $tgt = $keys[$j];
        //iterate until you find KEY to title
        if ($roles[$i]['id'] === $tgt) {
            // $ret[] = [$roles[$i]['id'] => $roles[$i]['description']];
            //$ret[] = ['id' => $roles[$i]['id'], 'description' => $roles[$i]['description']];
            $ret[] = [$k => $roles[$i][$k], $v => $roles[$i][$v]];
            $j += 1; //advance
            $i = 0; //reset
            return reAssoc($roles, $keys, $k, $v, $ret, $i, $j);
        } else {
            //increment $roles
            return reAssoc($roles, $keys, $k, $v, $ret, $i += 1, $j);
            //return $ret;
        }
    } else {
        return $ret;
    }
}
