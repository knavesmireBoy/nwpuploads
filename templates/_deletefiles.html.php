<form action="." method="post" name="deletions" class="<?= $k; ?>">
            <input type="hidden" name="id" value="<?= $id; ?>" />
            <p><input type="radio" id="ext_nwf" name="extent" value="f" /><label for="ext_nwf"><?= $dl; ?></label></p>
            <?php if ($multi) { ?>
                <p><input type="radio" id="ext_nwu" name="extent" value="u" /><label for="ext_nwu">delete all files for <span><?= $n; ?></span></label></p>
            <?php }
            if ($c != 'this client'): ?>
                <p><input type="radio" id="ext_nwc" name="extent" value="c" /><label for="ext_nwc">delete all files for <span><?= $c; ?></span></label></p>
            <?php endif; ?>
            <p><input type="radio" id="cancel" name="extent" /><label for="cancel">cancel</label></p>
            <input type="hidden" name="<?= $del; ?>" value="destroy" />
            <input type="submit" />
        </form>