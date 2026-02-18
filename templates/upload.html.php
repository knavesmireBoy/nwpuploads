<form action="<?php $_SERVER['PHP_SELF'] ?>" method="post" name="uploadform" enctype="multipart/form-data">
    <div><label for="uploadfiles">Upload File</label>
            <input id="uploadfiles" type="file" name="upload" />
    </div>
           <div><label for="desc">Description</label>
            <input id="desc" type="text" name="desc" maxlength="255" />
</div>
        <?php if ($priv == 'Admin') : ?>
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
                    </select></div>
        <?php endif; ?>
        <input type="hidden" name="action" value="upload" />
        <div><input type="submit" value="Upload" /></div>
</form>