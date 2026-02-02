<p class="footer">
    <?php
    if (isset($_GET['ext'])) $suffix = $ext;
    if (isset($_GET['u'])) $user_id = $useroo;
    if (isset($_GET['u'])) $text = $textme;

    $sort = explode('sort=', $_SERVER["QUERY_STRING"]);
    $sort = isset($sort[1]) ? $sort[1] : '';
    $sort = $sort ? "&sort=$sort" : '';
    $sort = preg_replace("/&&/", "&", $sort);


    if ($pages > 1) {
        $current_page = ($start / $display) + 1;
        if ($current_page != 1) { ?>
            <a href="?s=<?= $start - $display; ?>&p=<?= $pages; ?>&u=<?= $user_id; ?>&t=<?= $text; ?>&ext=<?= $suffix; ?><?= $sort; ?>">Previous</a>
            <?php
        }
        for ($i = 1; $i <= $pages; $i++) {
            if ($i != $current_page) { ?>
                <a href="?s=<?= $display * ($i - 1); ?>&p=<?= $pages; ?>&u=<?= $user_id; ?>&t=<?= $text; ?>&ext=<?= $suffix; ?><?= $sort; ?>"><?= $i ?></a>
            <?php
            } else {  ?>
                <span class="current"><?= $i; ?></span>
            <?php
            }
        }
        if ($current_page <> $pages) { ?>
            <a href="?s=<?= $start + $display; ?>&p=<?= $pages; ?>&u=<?= $user_id; ?>&t=<?= $text; ?>&ext=<?= $suffix; ?><?= $sort; ?>">Next</a>
</p>
<?php
        }
    } //If Pages > 1