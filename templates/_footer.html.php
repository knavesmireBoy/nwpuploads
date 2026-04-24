<?php
$klas = $pages > 1 ? 'paginate' : '';
?>
</main>
<footer class="<?= $klas; ?>">
    <?php
    include '_logout.html.php';

    if ($pages > 1) {
        $current_page = ($start / $display) + 1;
        if ($current_page != 1) { ?>
            <a href="/uploader/nav/<?= $start - $display; ?>/<?= $pages; ?>/<?= $user_id; ?>/<?= $text; ?>/<?= $ext; ?>/<?= $sort; ?>">Previous</a>
            <?php
        }
        for ($i = 1; $i <= $pages; $i++) {
            if ($i != $current_page) { ?>
                <a href="/uploader/nav/<?= $display * ($i - 1); ?>/<?= $pages; ?>/<?= $user_id; ?>/<?= $text; ?>/<?= $ext; ?>/<?= $sort; ?>"><?= $i ?></a>
            <?php
            } else {  ?>
                <span class="current"><?= $i; ?></span>
            <?php
            }
        }
        if ($current_page <> $pages) { ?>
            <a href="/uploader/nav/<?= $start + $display; ?>/<?= $pages; ?>/<?= $user_id; ?>/<?= $text; ?>/<?= $ext; ?>/<?= $sort; ?>">Next</a>
    <?php
        }
    }
    ?>
</footer>
</body>

</html>