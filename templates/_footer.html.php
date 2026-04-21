<?php
$klas = $pages > 1 ? 'paginate' : '';
?>
</main>
<footer class="<?= $klas; ?>">
    <?php
    include '_logout.html.php';

    $suffix = isset($_GET['ext']) ? $_GET['ext'] : '';
    $user_id = isset($_GET['usr']) ? $_GET['usr'] : '';
    $text = isset($_GET['txt']) ? $_GET['txt'] : '';


    $sort = explode('sort=', $_SERVER["QUERY_STRING"] ?? '');
    $sort = isset($sort[1]) ? $sort[1] : '';
    $sort = $sort ? "&sort=$sort" : '';
    $sort = preg_replace("/&&/", "&", $sort);

    if ($pages > 1) {
        $current_page = ($start / $display) + 1;
        if ($current_page != 1) { ?>
            <a href="?s=<?= $start - $display; ?>&p=<?= $pages; ?>$usr=<?= $user_id; ?>$txt=<?= $text; ?>&ext=<?= $suffix; ?><?= $sort; ?>">Previous</a>
            <?php
        }
        for ($i = 1; $i <= $pages; $i++) {
            if ($i != $current_page) { ?>
                <a href="?s=<?= $display * ($i - 1); ?>&p=<?= $pages; ?>$usr=<?= $user_id; ?>$txt=<?= $text; ?>&ext=<?= $suffix; ?><?= $sort; ?>"><?= $i ?></a>
            <?php
            } else {  ?>
                <span class="current"><?= $i; ?></span>
            <?php
            }
        }
        if ($current_page <> $pages) { ?>
            <a href="?s=<?= $start + $display; ?>&p=<?= $pages; ?>$usr=<?= $user_id; ?>$txt=<?= $text; ?>&ext=<?= $suffix; ?><?= $sort; ?>">Next</a>
<?php
 }
}
?></footer>
    </body></html>