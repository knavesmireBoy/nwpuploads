<?php

//include_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/api/includes/helpers.inc.php';
//include_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/api/config.php';

//include_once __DIR__ . '/../config.php';
//include_once FUNCTIONS;

foreach (get_defined_vars() as $k => $v) {
    $i = 0;
    $fail = false;
    $L = count($predicates);
    for ($i; $i < $L; $i++) {
      $fail = $predicates[$i]($k);
      if ($fail) {
        unset($$k);
        break;
      }
    }
  }
  unset($k);
  unset($v);
  unset($i);
  unset($L);
  unset($fail);


$klas = $pages > 1 ? 'paginate' : '';
$dateformat = 'l F j, Y';
$qlib = [
    'find',
    'user',
    'text',
    'suffix',
    'action',
    'flag',
    's',
    'u',
    'p',
    't',
    'sort',
    'ext',
    'loginerror',
    'upload'
];
$query = $_SERVER['QUERY_STRING'] ?? '';

parse_str($query, $output);
$result = array_keys($output);
$qpass = true;
$i = 0;
$failedsearch = 'There were no files that matched your criteria';

while (isset($result[$i])) {
    $qpass = $qpass && in_array($result[$i], $qlib);
    $i++;
}

if (!$qpass) {
    header("Location: .");
    exit();
}

$query = preg_match("/error/", $query) ? decode($query) : ($query ? $failedsearch : '');
//$d = 'j, n, Y';
?>
<h1>File Uploads</h1>
<h2><?= date($dateformat); ?></h2>

<?php
if (isset($template)) {
    ob_start();
}
echo $error; ?>

<p><a href="<?= $upload; ?>">Upload A File</a></p>

<?php
if (isset($template)) {
    ob_end_clean();
    include $template;
}

if (count($files) > 0): ?>
    </form>
    <h4>The following files are stored in the database</h4>
    <?php
    include '_tablehead.html.php'; ?>
    <tbody>
        <?php foreach ($files as $f): ?>
            <tr valign="top" class="<?php if ($f['origin'] == $myip) echo 'admin'; ?>">
                <?php
                $fsize = formatFileSize($f['size']);
                $client = $f['client'] ?? '';
                $description = (empty($f['description'])  ? 'No description provided' : $f['description']);
                $tel = $f['tel'];
                $tel = $client && $tel ? "$client | $tel" : $client;
                $id = $f['id'];
                $a = [$description];
                $b = array_map('html', $a);
                ?>
                <td><a title="<?= $fsize; ?>" href="/uploader/read/<?= $id; ?>">
                        <?= $f['filename']; ?></a></td>
                <?php if ($priv != 'Admin') : ?>
                    <td><?= $f['description']; ?></td>
                <?php endif;
                if ($priv == 'Admin') : ?>
                    <td title="<?= $description; ?>">
                        <?= $f['user']; ?></td>
                <?php endif;
                ?>
                <td title="<?= $tel; ?>">
                    <?php
                    $dateformat = "g:ia F j";
                    //$d = "j-n-Y";
                    echo date("j-n-Y", strtotime($f["time"])); ?></td>
                <td title="download">
                    <form action="/uploader/read/" method="post" name="downloads">
                        <div><input type="hidden" name="action" value="download" />
                            <input type="hidden" name="id" value="<?= $id; ?>" />
                            <input type="submit" value="Download" />
                        </div>
                    </form>
                </td>
                <?php
                if ($priv !== 'Browser') : ?>
                    <td title="delete">
                        <form action="/uploader/delete/" method="post" name="<?= $id; ?>">
                            <div><input type="hidden" name="action" value="delete" />
                                <input type="hidden" name="id" value="<?= $id; ?>" />
                                <input type="submit" value="Delete" title="delete or update"/>
                            </div>
                        </form>
                    </td>
                <?php endif; ?>
            </tr><?php endforeach; ?>
    </tbody>
    </table>
    </div>
<?php else :
    $greeting = $query ? $query : 'There are currently no files in the database' ?>
    <h2><a href="." title="Click to return"><?= $greeting; ?>
        </a></h2>
<?php
endif;
/*
$wither = seek();
$lnk = ($wither !== '.' ? 'Search files' : 'Clear search results');
if (!isset($_GET['find']) && count($files) > 0) { ?>
    <p><a href="<?= $wither; ?>"><?= $lnk; ?></a></p>
<?php
} ?>
*/
?>
<p><a href="/uploader/find/">Search Files</a></p>
<p><a href="/admin/load/">Admin Pages</a></p>
<?php
include "footer.html.php";
