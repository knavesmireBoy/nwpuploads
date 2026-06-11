<?php

//$uhead = $listuser ? $uhead : '/uploader/load/';
?>
<div id="upload">
<table>
    <thead>
        <tr>
            <th><a href="/uploader/sort/<?= $fhead; ?>">File name</a></th>
            <th><a href="/uploader/sort/<?= $uhead; ?>"><?= $listuser ? 'User' : 'Description'; ?></a></th>
            <th><a href="/uploader/sort/<?= $thead; ?>">Time</a></th>
            <th colspan="<?= $_colspan; ?>" class="control">Control<?php ?></th>
        </tr>
    </thead>