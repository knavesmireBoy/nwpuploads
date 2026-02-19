<section id=prompt>
    <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/helpers.inc.php';

    if (isset($clientlist)): ?>
        <form action="." method="post" name="clientform" class="prompt">
            <div><label for="employer">If existing client:</label>
                <select name="employer" id="employer">
                    <option value="">Set email domain</option>
                    <?php foreach ($clientlist as $i => $client): ?>
                        <option value="<?= $i; ?>">
                            <?= $client; ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" name="action" value="continue" />
            </div>
        </form>
    <?php elseif (!isset($clientlist) && !isset($del)):

    ?>
        <form action="<?= $action; ?>" method="post" name="choice" class="prompt" id="yesno">
            <input type="hidden" name="id" value="<?= $id; ?>" />
            <input type="hidden" name="userid" value="<?= $userid; ?>" />
            <input type="hidden" name="username" value="<?= $name; ?>" />
            <input type="hidden" name="multi" value="<?= !!$multi; ?>" />
            <input type="hidden" name="domain" value="<?= $domain ?? ''; ?>" />
            <p><?= $prompt; ?></p>
            <input id="yes" type="radio" name="<?= $call; ?>" value="<?= $pos; ?>" />
            <label for="yes">Yes</label>
            <input id="no" type="radio" name="<?= $call; ?>" value="<?= $neg; ?>" />
            <label for="no">No</label>
            <input type="submit" value="Submit" />
        </form>
    <?php endif;  ?>

    <?php if (isset($del)):

        $n = $name ?? $users[$userid] ?? null;
        $c = $client[$domain] ?? null;

        $k = 'prompt';
        if ($c || $multi) {
            $k .= ' span';
        }
        $c = $c ?? 'this client';
        $n = $n ?? 'this user';
        $dl = $multi ? 'delete this file only' : 'delete';

    ?>
        <form action="." method="post" name="deletions" class="<?= $k;?>">
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
    <?php endif; ?>
</section>