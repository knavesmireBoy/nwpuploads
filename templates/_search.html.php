<form action="<?= $action; ?>?" method="get" name="search" class="details">
    <a href="/uploader/load/" class="cancel">X</a>
    <p>View files satisfying the following criteria</p>
    <?php if (!isset($zero)) : ?>
        <label for="user">By user</label><select id="user" name="user">
            <option value="">Any User</option>
            <?php if ($priv === "Admin") {
                $optgroup = 'clients';
                $group = $clients;
                include '_optgroup.html.php';
                $optgroup = 'users';
                $group = $users;
                include '_optgroup.html.php';
            } ?>
        </select>

    <?php endif; ?>
    <label for="text">Containing text</label><input id="text" type="search" name="text" />
    <label for="suffix">Suffix</label><select id="suffix" name="suffix">
        <option value="">Search files</option>
        <option value="pdf">pdf</option>
        <option value="zip">zip</option>
        <option value="owt">other</option>
    </select>
    <input type="submit" value="Search" />
</form>