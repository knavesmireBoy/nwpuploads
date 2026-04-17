<?php
$klas = $pages > 1 ? 'paginate' : '';
?>
</main>
<footer class="<?= $klas; ?>">
    <?php
    include '_logout.html.php';
    $srch = 0;
    if (isset($_GET['u'])) {
        $user_id = $byuser;
        $srch += 1;
    }
    if (isset($_GET['t'])) {
        $text = $bytext;
        $srch += 2;
    }
    if (isset($_GET['ext'])) {
        $suffix = $ext;
        $srch += 4;
    }
    //0 1 2 3 4 5 6 7

    $sort = explode('sort=', $_SERVER["QUERY_STRING"] ?? '');
    $sort = isset($sort[1]) ? $sort[1] : '';
    $sort = $sort ? $sort : '';
    $sort = preg_replace("/&&/", "&", $sort);

    if ($pages > 1) {
        $current_page = ($start / $display) + 1;
        if ($current_page != 1) { ?>
            <a href="/uploader/nav/<?= $start - $display; ?>/<?= $pages; ?>/<?= $srch; ?>/<?= $sort; ?>">Previous</a>
            <?php
        }
        for ($i = 1; $i <= $pages; $i++) {
            if ($i != $current_page) { ?>
                <a href="/uploader/nav/<?= $display * ($i - 1); ?>/<?= $pages; ?>/<?= $srch; ?>/<?= $sort; ?>"><?= $i ?></a>
            <?php
            } else {  ?>
                <span class="current"><?= $i; ?></span>
            <?php
            }
        }
        if ($current_page <> $pages) { ?>
            <a href="/uploader/nav/<?= $start + $display; ?>/<?= $pages; ?>/<?= $srch; ?>/<?= $sort; ?>">Next</a>
    <?php
        }
    }
    ?>
</footer>
</body>

</html>