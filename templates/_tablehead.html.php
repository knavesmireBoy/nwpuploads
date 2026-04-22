<?php
$tel = '';
$sort = '';
$toggle = ['f', 'u', 't'];
$uhead = ($priv === 'Admin') ? $uhead : '.';
?>
<div id="upload">
<table>
    <thead>
        <tr>
            <th><a href="/uploader/sort/<?= $fhead; ?>">File name</a></th>
            <?php $choice = ($priv == 'Admin')  ? 'User' : 'Description' ?>
            <th><a href="/uploader/sort/<?= $uhead; ?>"><?= $choice; ?></a></th>
            <th><a href="/uploader/sort/<?= $thead; ?>">Time</a></th>
            <?php $num = ($priv !== 'Browser'  ? '2' : '1')  ?>
            <th colspan="<?= $num; ?>" class="control">Control<?php ?></th>
        </tr>
    </thead>