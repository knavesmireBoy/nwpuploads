<?php include_once $_SERVER['DOCUMENT_ROOT'].'/nwp_uploads/includes/helpers.inc.php'; ?>

		<h1>Search Files</h1>
<form action="?" method="get" name="searchFiles">
<p>View files satisfying the following criteria:</p>
<?php if(!isset($zero)) :?>
<div><label for="user">By user: </label><select id="user" name="user">
<option value="">Any User</option>
<?php if($priv=="Admin"): ?><optgroup label="clients">
<?php endif; ?>
<?php  foreach ($client as $x => $c): ?>
<option value="<?php htmlout($x); ?>"><?php htmlout($c); ?>
</option><?php endforeach; ?></optgroup>
<?php if($priv=="Admin"): ?><optgroup label="users">
<?php endif; ?>
<?php  foreach ($users as $ix => $u): ?>
<option value="<?php htmlout($ix); ?>"><?php htmlout($u); ?>
</option><?php endforeach; ?></optgroup></select>
</div>
<?php endif; ?>
<div>
<label for="text">Containing text:</label> <input id="text" type="search" name="text"/></div>
<div>
<div>
<label for="suffix">Suffix: </label>
<select id="suffix" name="suffix">
<option value="">Search files</option>
<option value="pdf">pdf</option>
<option value="zip">zip</option>
<option value="owt">other</option>
</select></div>
<input type="hidden" name="action" value="search"/>
<input type="hidden" name="flag" />
<input type="submit" value="Search"/></div>
		</form>
		<p><a href=".">Return</a></p>
		<!--</div>	-->	
	</body>
</html>