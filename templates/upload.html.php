<form action="/uploader/upload/" method="post" name="uploadform" enctype="multipart/form-data" class="details">
        <a href="/uploader/load/" class="cancel">X</a>
        <label for="uploadfiles">Upload File</label><input id="uploadfiles" type="file" name="upload" <?= $disabled; ?> />
        <label for="desc">Description</label><input id="desc" type="text" name="desc" />
        <?php
        if ($priv == 'Admin') : ?>
               <label for="user">User</label><select id="user" name="user">
                                <option value="">Select One</option>
                                <?php
                                $optgroup = 'clients';
                                $group = $clients;
                                include '_optgroup.html.php';
                                $optgroup = 'users';
                                $group = $users;
                                include '_optgroup.html.php'; ?>
                        </select>
                
        <?php endif; ?>
        <input type="hidden" name="key" value="<?= $key; ?>" />
        <input type="submit" value="Upload" />
</form>