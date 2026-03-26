<form action="." method="post" name="uploadform" enctype="multipart/form-data">
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
                        <optgroup label="clients"><?php foreach ($client as $k => $v): ?>
                                <option value="<?= $k; ?>"><?= $v; ?>
                                </option><?php endforeach; ?>
                        </optgroup>
                        <optgroup label="users">
                            <?php foreach ($users as $k => $v): ?>
                                <option value="<?= $k; ?>"><?= $v; ?>
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