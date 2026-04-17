<section id=prompt>
    <?php
    //$clientlist from admin not upload
    if (isset($clientlist)): ?>
        <form action="." method="post" name="clientform" class="prompt">
            <div><label for="employer">If existing client:</label>
                <select name="employer" id="employer">
                    <option value="">Set email domain</option>
                    <?php foreach ($clientlist as $i => $theclient): ?>
                        <option value="<?= $i; ?>">
                            <?= $theclient; ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" name="action" value="continue" />
            </div>
        </form>
    <?php elseif (!isset($clientlist) && !isset($delete)):
        //confirm used by several controllers
    ?>
        <form action="<?= $action; ?>" method="post" name="choice" class="prompt" id="yesno">
            <input type="hidden" name="ownerid" value="<?= $owner['id'] ?? $id; ?>" />
            <input type="hidden" name="ownername" value="<?= $owner['name'] ?? ''; ?>" />
            <input type="hidden" name="multi" value="<?= $owner['multi'] ?? '' ?>" />
            <input type="hidden" name="domain" value="<?= $owner['domain'] ?? ''; ?>" />
            <input type="hidden" name="clientname" value="<?= $owner['clientname'] ?? ''; ?>" />
            <input type="hidden" name="editor" value="<?= $owner['editor'] ?? ''; ?>" />
            <?php
            include '_confirm.html.php';
            ?>
        </form>
    <?php endif;  ?>

    <?php if (isset($delete)):
        //We need to determine the logic of which messages to display


        $domain = $owner['domain'] ?? '';
        $clientname = $owner['clientname'] && ($multi & 2) ? $owner['clientname'] : '';
        $multi = $owner['multi'] ?? null;
        $n = $owner['name'] ?? null;
        //  $c = isset($client[$owner['id']]) ? $client_name : '';
        $klas = 'prompt';

        if ($clientname || $multi) {
            $klas .= ' span';
        }
        $n = $n ?? 'this user';

        $dlf = "delete this file";
        $dlu = "delete all files for <span>$n</span>";
        $dlc = "delete all files for <span>$c</span>";

        if ($multi) {
            $dlf .= " only";
            $dlu = $editor ? "delete all your files" : $dlu;
        } else {
            $dl = $editor ? "delete this file" : "delete file for <span>$n</span>";
        }
    ?>
        <form action="<?= $action; ?>" method="post" name="deletions" class="<?= $klas; ?>">
            <input type="radio" id="ext_nwf" name="extent" value="f" /><label for="ext_nwf"><?= $dlf; ?></label>
            <?php if ($multi & 1) { ?>
                <input type="radio" id="ext_nwu" name="extent" value="u" /><label for="ext_nwu"><?= $dlu; ?></label>
            <?php }
            if ($clientname): ?>
                <input type="radio" id="ext_nwc" name="extent" value="c" /><label for="ext_nwc"><?= $dlc; ?></label>
            <?php endif; ?>
            <input type="radio" id="cancel" name="extent" /><label for="cancel">cancel</label>
            <input type="hidden" name="id" value="<?= $id; ?>" />
            <input type="hidden" name="ownerid" value="<?= $owner['id'] ?? ''; ?>" />
            <input type="hidden" name="<?= $delete; ?>" value="destroy" />
            <input type="submit" />
        </form>
    <?php endif; ?>
</section>