<?php
$tel = '';
$sort = '';
$toggle = ['f', 'u', 't'];

// TABLE ORDERING...
$q = $_SERVER['QUERY_STRING'];
$q = preg_replace('/(\?[a-z0-9=&]*)(&sort|&flag)(=?[a-z]*)/', '$1', '?' . $q);

if ($q == '?') { //first run
    $sort = 'sort=';
} elseif (substr($q, -3, 1) == '=') { //double
    if (substr($q, -2, 1) != substr($q, -1, 1)) {
        $q = substr($q, -2, 1);
        $sort = '&sort=' . substr($q, -1, 1);

        //   $sort = '?sort=' . substr($q, -1, 1);
        //  $q = '';

    } else {
        $q = '?sort=';
    }
}
//elseif (substr($q,1,4)=='sort') {//single
elseif (substr($q, -2, 1) == '=') {
    $sort = substr($q, -1);
    $q = '?sort=';
} else {
    $sort = '&sort=';
}

/*
if ((substr($sort,0,2)=='uu' and strlen($sort)<=3)) {
    $toggle=array($sort.'f',  'u', $sort. 't' );
}
elseif ((substr($sort, 0,1)=='u' and strlen($sort)<=2)) {
    $toggle=array($sort.'f', $sort. 'u', $sort. 't' );
}
elseif (!$sort or strlen($sort)>1 ){
    $toggle=array('f','u','t');
}
else {
    $toggle = array($sort .'f', $sort . 'u', $sort . 't' );//append to existing sort
}
*/

?>
<div id="upload">
<table>
    <thead>
        <tr>
            <th><a href="<?php echo $q . $sort . $toggle[0]; ?>">File name</a></th>
            <?php $choice = ($priv == 'Admin')  ? 'User' : 'Description'  ?>
            <th><a href="<?php echo $q . $sort . $toggle[1]; ?>"><?php echo $choice; ?></a></th>
            <th><a href="<?php echo $q . $sort . $toggle[2]; ?>">Time</a></th>
            <?php $num = ($priv != 'Browser'  ? '2' : '1')  ?>
            <th colspan="<?php echo ($num) ?>" class="control">Control<?php ?></th>
        </tr>
    </thead>