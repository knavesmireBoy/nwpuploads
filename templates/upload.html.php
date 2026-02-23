<form action="." method="post" name="uploadform" enctype="multipart/form-data">
        <label for="uploadfiles">Upload File</label>
        <input id="uploadfiles" type="file" name="upload" <?= $disabled; ?> />
        <label for="desc">Description</label>
        <input id="desc" type="text" name="desc" />
        <?php
        if ($priv == 'Admin') : ?>
                <div><label for="user">User</label>
                        <select id="user" name="user">
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
                </div>
        <?php endif; ?>
        <input type="hidden" name="action" value="upload" />
        <input type="submit" value="Upload" />
</form>