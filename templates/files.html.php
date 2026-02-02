<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/helpers.inc.php';
//ob_start('ob_postprocess');
//ob_start('ob_gzhandler');
?>
<h1><a href="<?php $_SERVER['PHP_SELF'] ?>">North Wolds | File Uploads</a></h1>
<h2><?php echo date('l F j, Y'); ?></h2>
<form action="<?php $_SERVER['PHP_SELF'] ?>" method="post" name="uploadform" enctype="multipart/form-data">
    <table class="up">
        <tr>
            <td><label for="uploadfiles">Upload File:</label></td>
            <td><input id="uploadfiles" type="file" name="upload" /></td>
        </tr>
        <tr>
            <td><label for="desc">File Description: </label></td>
            <td><input id="desc" type="text" name="desc" maxlength="255" /></td>
        </tr>
        <?php if ($priv == 'Admin') : ?>
            <tr>
                <td><label for="user">User:</label></td>
                <td><select id="user" name="user">
                        <option value="">Select one</option>
                        <optgroup label="clients"><?php foreach ($client as $x => $c): ?>
                                <option value="<?php htmlout($x); ?>"><?php htmlout($c); ?>
                                </option><?php endforeach; ?>
                        </optgroup>
                        <optgroup label="users">
                            <?php foreach ($users as $ix => $u): ?>
                                <option value="<?php htmlout($ix); ?>"><?php htmlout($u); ?>
                                </option><?php endforeach; ?>
                        </optgroup>
                    </select>
                </td>
            </tr>
        <?php endif; ?>
        <input type="hidden" name="action" value="upload" />
        <tr>
            <td><input type="submit" value="Upload" /></td>
            <td>&nbsp;</td>
        </tr>
    </table>
</form>
<?php if (count($files) > 0): ?>
    <p>The following files are stored in the database:</p>

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
                <td>
                    <form action="<?php $_SERVER['PHP_SELF'] ?>" method="get" name="downloads">
                        <div><input type="hidden" name="action" value="download" />
                            <input type="hidden" name="id" value="<?php htmlout($f['id']); ?>" />
                            <input type="submit" value="Download" />
                        </div>
                    </form>
                </td>
                <?php if ($priv != 'Browser') : ?>
                    <td>
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

include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/logout.inc.html.php';
if ($priv == 'Admin' or $priv == 'Client') : ?>
    <p><a href="admin/">Admin Pages</a></p>

<?php
endif;
//$wither = ($suffix || $user_id || $text || $ext || $useroo || $textme ? '.' : '?find'); 
$wither = seek();
$link = ($wither == '.'  ? 'Clear search results' : 'Search files');
?>
<p><a href="<?php echo $wither; ?>"><?php echo $link; ?></a></p>
<?php include "_footer.html.php";

    if (isset($prompt)) {
        include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/templates/prompt.html.php';
        if (!isset($filename)) {
            echo '</div></body></html>';
            exit();
        }
    } //prompt
    if (isset($filename)) {
        include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/templates/update.html.php';
        echo '</div></body></html>';
    }
