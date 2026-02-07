<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/helpers.inc.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/config.php';
?>
<h1><a href="<?php $_SERVER['PHP_SELF'] ?>">North Wolds | File Uploads</a></h1>
<h2><?php echo date('l F j, Y'); ?></h2>

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
                ?>
                <td><a title="<?php htmlout($fsize); ?>" href="<?= '?action=get&id=' . $f['id']; ?>">
                        <?php htmlout($f['filename']); ?></a></td>
                <?php if ($priv == 'Client') : ?>
                    <td><?php htmlout($f['description']); ?></td>
                <?php endif;
                if ($priv == 'Admin') :
                    $des = (empty($f['description'])  ? 'No description provided' : html($f['description'])); ?>
                    <td title="<?php echo $des; ?>">
                        <?php htmlout($f['user']); ?></td>
                <?php endif;
                ?>
                <td title="<?php echo $tel ?>">
                    <?php
                    $stamp = html($f["time"]);
                    echo date("g:i a F j ", strtotime($stamp)); ?></td>
                <td title="download">
                    <form action="<?php $_SERVER['PHP_SELF'] ?>" method="get" name="downloads">
                        <div><input type="hidden" name="action" value="download" />
                            <input type="hidden" name="id" value="<?php htmlout($f['id']); ?>" />
                            <input type="submit" value="Download" />
                        </div>
                    </form>
                </td>
                <?php if ($priv != 'Browser') : ?>
                    <td title="delete">
                        <form action="<?php $_SERVER['PHP_SELF'] ?>" method="post" name="<?php htmlout($f['id']); ?>">
                            <div><input type="hidden" name="action" value="delete" />
                                <input type="hidden" name="id" value="<?php htmlout($f['id']); ?>" />
                                <input type="submit" value="Delete" />
                            </div>
                        </form>
                    </td>
                <?php endif; ?>
            </tr><?php endforeach; ?>
    </tbody>
    </table>

<?php else :
    $greeting = ($_SERVER['QUERY_STRING']) ? 'There were no files that matched your criteria' : 'There are currently no files in the database' ?>
    <h2><a href="<?php $_SERVER['PHP_SELF'] ?>" title="Click to return"><?php echo $greeting; ?>
        </a></h2>
<?php
endif;
$wither = seek();
$lnk = ($wither !== '.' ? 'Search files' : 'Clear search results');

if (!isset($_GET['find'])) { ?>
    <p><a href="<?= $wither; ?>"><?= $lnk; ?></a></p>
<?php
}
include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/logout.inc.html.php';
include "_footer.html.php";
