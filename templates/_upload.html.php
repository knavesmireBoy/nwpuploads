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
                                <option value="<?= $x; ?>"><?= $c; ?>
                                </option><?php endforeach; ?>
                        </optgroup>
                        <optgroup label="users">
                            <?php foreach ($users as $ix => $u): ?>
                                <option value="<?= $ix; ?>"><?= $u; ?>
                                </option><?php endforeach; ?>
                        </optgroup>
                    </select>
                </td>
            </tr>
        <?php endif; ?>
        <input type="hidden" name="action" value="upload" />
        <tr>
            <td colspan=2><input type="submit" value="Upload" /></td>
        </tr>
    </table>
</form>