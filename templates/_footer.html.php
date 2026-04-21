<?php
$klas = $pages > 1 ? 'paginate' : '';
?>
</main>
<footer class="<?= $klas; ?>">
    <?php
    include '_logout.html.php';
/*
    $suffix = isset($_GET['ext']) ? $_GET['ext'] : '';
    $user_id = isset($_GET['usr']) ? $_GET['usr'] : '';
    $text = isset($_GET['txt']) ? $_GET['txt'] : '';
    */
    $sort = '';
    var_dump([$_GET, $_SERVER]);

    if (isset($_SERVER["QUERY_STRING"])) {
        $sort = explode('sort=', $_SERVER["QUERY_STRING"] ?? '');
        $sort = isset($sort[1]) ? $sort[1] : '';
        $sort = $sort ? "&sort=$sort" : '';
        $sort = preg_replace("/&&/", "&", $sort);
    }

    if ($pages > 1) {
        $current_page = ($start / $display) + 1;
        if ($current_page != 1) { ?>
            <a href="/uploader/nav/<?= $start - $display; ?>/<?= $pages; ?>/?usr=<?= $user_id; ?>&txt=<?= $text; ?>&ext=<?= $suffix; ?><?= $sort; ?>">Previous</a>
            <?php
        }
        for ($i = 1; $i <= $pages; $i++) {
            if ($i != $current_page) { ?>
                <a href="/uploader/nav/<?= $display * ($i - 1); ?>/<?= $pages; ?>/?usr=<?= $user_id; ?>&txt=<?= $text; ?>&ext=<?= $suffix; ?><?= $sort; ?>"><?= $i ?></a>
            <?php
            } else {  ?>
                <span class="current"><?= $i; ?></span>
            <?php
            }
        }
        if ($current_page <> $pages) { ?>
            <a href="/uploader/nav/<?= $start + $display; ?>/<?= $pages; ?>/?usr=<?= $user_id; ?>&txt=<?= $text; ?>&ext=<?= $suffix; ?><?= $sort; ?>">Next</a>
    <?php
        }
    }
    ?>
</footer>
</body>

</html>