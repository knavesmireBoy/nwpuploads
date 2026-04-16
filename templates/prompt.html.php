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
        $client_name = $owner['clientname'] ?? '';
        $multi = $owner['multi'] ?? null;


        $lib = [ '1' => 'u', '10' => 'c', '11' => 'uc'];
        

        $n = $owner['name'] ?? null;
        $c = $client[$owner['id']] ?? null;
        $k = 'prompt';

        dump([$multi & 1, $multi % 2]);
        if ($c || $multi) {
            $k .= ' span';
        }
        $c = $c ? $c : 'this client';
        $n = $n ?? 'this user';
        $dl = "delete file";
        $dlu = "delete all files for <span>$n</span>";
        if ($multi) {
            $dl = "delete this file only";
            $dlu = $editor ? "delete all your files" : $dlu;
        } else {
            $dl = $editor ? "delete file" : "delete file for <span>$n</span>";
        }
    ?>
        <form action="<?= $action; ?>" method="post" name="deletions" class="<?= $k; ?>">
            <input type="radio" id="ext_nwf" name="extent" value="f" /><label for="ext_nwf"><?= $dl; ?></label>
            <?php if ($multi) { ?>
                <input type="radio" id="ext_nwu" name="extent" value="u" /><label for="ext_nwu"><?= $dlu; ?></label>
            <?php }
            if (($c !== 'this client') && $multi): ?>
                <input type="radio" id="ext_nwc" name="extent" value="c" /><label for="ext_nwc">delete all files for <span><?= $c; ?></span></label>
            <?php endif; ?>
            <input type="radio" id="cancel" name="extent" /><label for="cancel">cancel</label>
            <input type="hidden" name="id" value="<?= $id; ?>" />
            <input type="hidden" name="ownerid" value="<?= $owner['id'] ?? ''; ?>" />
            <input type="hidden" name="<?= $delete; ?>" value="destroy" />
            <input type="submit" />
        </form>
    <?php endif; ?>
</section>