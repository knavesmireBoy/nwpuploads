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
    <?php elseif (!isset($clientlist) and !isset($del)):
    ?>
        <form action="<?= $action; ?>" method="post" name="choice" class="prompt" id="yesno">
            <input type="hidden" name="id" value="<?= $id; ?>" />
            <p><?= $prompt; ?></p>
            <input id="yes" type="radio" name="<?= $call; ?>" value="<?= $pos; ?>" />
            <label for="yes">Yes</label>
            <input id="no" type="radio" name="<?= $call; ?>" value="<?= $neg; ?>" />
            <label for="no">No</label>
            <input type="submit" value="Submit" />
        </form>
    <?php endif;  ?>

    <?php if (isset($del)):
        $u = $users[$id] ??  'this user';
        $c = $client[$id] ?? 'this client';
    ?>
        <form action="." method="post" name="deletions" class="block prompt">
            <input type="hidden" name="id" value="<?= $id; ?>" />
            <p><label for="ext_nwf">Delete this file only</label>&nbsp;<input type="radio" id="ext_nwf" name="extent" value="f" /></p>
            <p><label for="ext_nwu">Delete all files for <?= $u; ?></label>&nbsp;<input type="radio" id="ext_nwu" name="extent" value="u" /></p>
            <?php if (preg_match("/admin/i", $priv)): ?>
                <p><label for="ext_nwc">Delete all files for <?= $c; ?></label>&nbsp;<input type="radio" id="ext_nwc" name="extent" value="c" /></p>
            <?php endif; ?>
            <p><label for="cancel">Cancel deletion</label>&nbsp;<input type="radio" id="cancel" name="extent" /></p>
            <input type="hidden" name="<?= $del; ?>" value="remove" />
            <input type="submit" value="Remove Files" />
        </form>
    <?php endif; ?>
</section>