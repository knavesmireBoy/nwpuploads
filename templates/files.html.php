<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/uploads/includes/helpers.inc.php';
//ob_start('ob_postprocess');
//ob_start('ob_gzhandler');
?>
		<h1><a href="<?php $_SERVER['PHP_SELF']?>">North Wolds | File Uploads</a></h1>
		<h2><?php echo date('l F j, Y'); ?></h2>
		<form action="<?php $_SERVER['PHP_SELF']?>" method="post" name="uploadform" enctype="multipart/form-data">
		<table class="up"><tr><td><label for="uploadfiles">Upload File:</label></td><td><input id="uploadfiles" type="file" name="upload"/></td></tr>
		<tr><td><label for="desc">File Description: </label></td><td><input id="desc" type="text" name="desc" maxlength="255"/></td></tr>
			<?php if ($priv =='Admin') : ?>
<tr><td><label for="user">User:</label></td><td><select id="user" name="user"><option value="">Select one</option>
<optgroup label="clients"><?php  foreach ($client as $x => $c): ?>
<option value="<?php htmlout($x); ?>"><?php htmlout($c); ?>
</option><?php endforeach; ?></optgroup>
<optgroup label="users">
<?php  foreach ($users as $ix => $u): ?>
<option value="<?php htmlout($ix); ?>"><?php htmlout($u); ?>
</option><?php endforeach; ?></optgroup></select>
</td></tr>
<?php endif; ?>
<input type="hidden" name="action" value="upload"/>
<tr><td><input type="submit" value="Upload"/></td><td>&nbsp;</td></tr></table>
</form>
	<?php if (count($files) > 0): ?>
		<p>The following files are stored in the database:</p>
		<table>
			<thead>
				<tr>
<?php 
$tel = '';
$sort = '';
$toggle = ['f', 'u', 't'];
                    
// TABLE ORDERING...
$q = $_SERVER['QUERY_STRING'];
$q = preg_replace('/(\?[a-z0-9=&]*)(&sort|&flag)(=?[a-z]*)/','$1','?'.$q);
                
if($q=='?') {//first run
    $sort='sort='; 
}
elseif (substr($q,-3, 1)=='=') {//double
   if(substr($q,-2, 1) != substr($q,-1, 1)){
       $q = substr($q,-2, 1);
       $sort = '&sort=' . substr($q,-1, 1); 
   }
    else {
        $q='?sort='; 
    }
       
}
//elseif (substr($q,1,4)=='sort') {//single
elseif (substr($q,-2, 1)=='='){
    $sort = substr($q,-1);
    $q='?sort='; 
}

else {
    $sort='&sort='; 
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

<th><a href="<?php echo $q . $sort . $toggle[0]; ?>">File name</a></th>
<?php $choice = ($priv =='Admin')  ? 'User' : 'Description'  ?>
<th><a href="<?php echo $q . $sort . $toggle[1]; ?>"><?php echo $choice; ?></a></th>
<th><a href="<?php echo $q . $sort . $toggle[2]; ?>">Time</a></th>

<?php $num = ($priv !='Browser'  ? '2' : '1')  ?>
<th colspan="<?php echo($num) ?>" class="control">Control<?php ?></th>
</tr>
</thead>
<tbody>
			
<?php foreach($files as $f): ?>
<tr valign="top" class="<?php if($f['origin'] == $myip) echo 'admin';?>">
<?php 
$fsize = formatFileSize($f['size']);
?>
<td><a title="<?php htmlout($fsize); ?>" href="<?php htmlout($f['file']);?>">
<?php htmlout($f['filename']); ?></a></td>
<?php if ($priv =='Client') : ?>
<td><?php htmlout($f['description']); ?></td>
<?php endif;
if ($priv =='Admin') : 
$des = (empty($f['description'])  ? 'No description provided' : html($f['description'])); ?>
<td title="<?php echo $des; ?>" >
<?php htmlout($f['user']); ?></td>
<?php endif; 
?>

<td title="<?php echo $tel ?>">
    <?php 
$stamp = html($f["time"]);
echo date("g:i a F j ", strtotime($stamp)) ;?></td>


<td><form action="<?php $_SERVER['PHP_SELF']?>" method="get" name="downloads">
<div><input type="hidden" name="action" value="download"/>
<input type="hidden" name="id" value="<?php htmlout($f['id']); ?>"/>
<input type="submit" value="Download"/></div></form></td>
<?php if ($priv !='Browser') : ?>
<td><form action="<?php $_SERVER['PHP_SELF']?>" method="post" name="<?php htmlout($f['id']); ?>">
<div><input type="hidden" name="action" value="delete"/>
<input type="hidden" name="id" value="<?php htmlout($f['id']); ?>"/>
<input type="submit" value="Delete"/></div>
</form>
</td>
<?php endif; ?></tr><?php endforeach; ?>
</tbody>
</table>


<?php else :
 $greeting=($_SERVER['QUERY_STRING']) ? 'There were no files that matched your criteria' : 'There are currently no files in the database' ?>

<h2><a href="<?php $_SERVER['PHP_SELF']?>" title="Click to return"><?php echo $greeting; ?>

</a></h2>
<?php 
endif; 

include $_SERVER['DOCUMENT_ROOT'] . '/uploads/includes/logout.inc.html.php'; 
if ($priv =='Admin' or $priv =='Client') : ?>
<p><a href="admin/">Admin Pages</a></p>

<?php 
endif;
//$wither = ($suffix || $user_id || $text || $ext || $useroo || $textme ? '.' : '?find'); 
$wither = seek();
$link = ($wither=='.'  ? 'Clear search results' : 'Search files');
?>
<p><a href="<?php echo $wither;?>"><?php echo $link; ?></a></p>

<p class="footer">

<?php
if(isset($_GET['ext'])) $suffix=$ext;
if(isset($_GET['u'])) $user_id=$useroo;
if(isset($_GET['u'])) $text= $textme;
if ($pages > 1) {
    
$current_page = ($start/$display) + 1;
if ($current_page != 1) { ?>
<a href="?s=<?php echo $start-$display; ?>&p=<?php echo ($pages); ?>&u=<?php echo $user_id; ?>&t=<?php echo $text; ?>&ext=<?php echo $suffix; ?>&sort=<?php echo $sort; ?>">Previous</a>
<?php 
}
for ($i=1; $i<=$pages; $i++){
if ($i != $current_page) { ?>
<a href="?s=<?php echo ($display * ($i-1)); ?>&p=<?php echo ($pages);?>&u=<?php echo $user_id; ?>&t=<?php echo $text; ?>&ext=<?php echo $suffix; ?>&sort=<?php echo $sort; ?>"><?php echo $i ?></a>
<?php
}
else {  ?>
    
<span class="current"><?php echo($i); ?></span>
<?php
}
}
if ($current_page <> $pages) { ?>
<a href="?s=<?php echo $start+$display; ?>&p=<?php echo ($pages); ?>&u=<?php echo $user_id; ?>&t=<?php echo $text; ?>&ext=<?php echo $suffix;?>&sort=<?php echo $sort; ?>">Next</a></p>
<?php
}
}//If Pages > 1

if (isset($prompt)) {
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/templates/prompt.html.php';
if (!isset($filename)) { 
echo '</div></body></html>';
exit();
}
}//prompt
if (isset($filename)) {
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/templates/update.html.php';
echo '</div></body></html>';
}
