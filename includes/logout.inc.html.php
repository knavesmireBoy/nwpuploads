<form action="<?php $_SERVER['PHP_SELF'] ?>" method="post" name="logoutform">
    <div>
        <input type="hidden" name="action" value="logout" /><input type="hidden" name="goto" value="<?php $_SERVER['DOCUMENT_ROOT'] ?>/nwp_uploads/" /><input type="submit" value="Log out" />
    </div>
</form>