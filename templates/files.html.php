<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/helpers.inc.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/config.php';
?>
<h1><a href="<?php $_SERVER['PHP_SELF'] ?>">North Wolds | File Uploads</a></h1>
<h2><?= date('l F j, Y'); ?></h2>

<?php
include TEMPLATE . $template;
echo $error;
if (count($files) > 0): ?>
    </form>
    <p><?= "The following files are stored in the database:" ?></p>
    <?php
    include '_tablehead.html.php'; ?>
    <tbody>
        <?php foreach ($files as $f): ?>
            <tr valign="top" class="<?php if ($f['origin'] == $myip) echo 'admin'; ?>">
                <?php
                $fsize = formatFileSize($f['size']);
                $client = $f['client'] ?? '';
                $des = (empty($f['description'])  ? 'No description provided' : $f['description']);
                $tel = $f['tel'];
                $tel = $client && $tel ? "$client | $tel" : $client;
                $id = $f['id'];
                ?>
                <td><a title="<?= $fsize; ?>" href="<?= '?action=get&id=' . $id; ?>">
                        <?= $f['filename']; ?></a></td>
                <?php if ($priv != 'Admin') : ?>
                    <td><?= $f['description']; ?></td>
                <?php endif;
                if ($priv == 'Admin') : ?>
                    <td title="<?= $des; ?>">
                        <?= $f['user']; ?></td>
                <?php endif;
                ?>
                <td title="<?= $tel; ?>">
                    <?php
                    $stamp = $f["time"];
                    echo date("g:ia F j", strtotime($stamp)); ?></td>
                <td title="download">
                    <form action="." method="get" name="downloads">
                        <div><input type="hidden" name="action" value="download" />
                            <input type="hidden" name="id" value="<?= $id; ?>" />
                            <input type="submit" value="Download" />
                        </div>
                    </form>
                </td>
                <?php if ($priv != 'Browser') : ?>
                    <td title="delete">
                        <form action="." method="post" name="<?= $f['id']; ?>">
                            <div><input type="hidden" name="action" value="delete" />
                                <input type="hidden" name="id" value="<?= $id; ?>" />
                                <input type="submit" value="Delete" />
                            </div>
                        </form>
                    </td>
                <?php endif; ?>
            </tr><?php endforeach; ?>
    </tbody>
    </table>
    </div>
<?php else :
    $greeting = ($_SERVER['QUERY_STRING']) ? 'There were no files that matched your criteria' : 'There are currently no files in the database' ?>
    <h2><a href="<?php $_SERVER['PHP_SELF'] ?>" title="Click to return"><?= $greeting; ?>
        </a></h2>
<?php
endif;
$wither = seek();
$lnk = ($wither !== '.' ? 'Search files' : 'Clear search results');

if (!isset($_GET['find']) /* && !isset($greeting)*/) { ?>
    <p><a href="<?= $wither; ?>"><?= $lnk; ?></a></p>
<?php
} ?>

<p><a href="admin/">Admin Pages</a></p>
<?php
include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/logout.inc.html.php';
include "_footer.html.php";
