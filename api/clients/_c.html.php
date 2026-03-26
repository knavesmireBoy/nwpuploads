<?php
/*
			foreach ($clients as $client): ?><?php
			endforeach;
            */
?>
<form action="" method="post" name="editclientform" class="choose">
    <ul>
        <li>
            <h1><?= $client['name']; ?></h1>
        </li>
        <li><label for="edit">Edit</label><input id="edit" type="radio" name="action" value="Edit" />
            <label for="delete">Delete</label><input id="delete" type="radio" name="action" value="Delete" />
        </li>
    </ul>
    <input type="hidden" name="id" value="<?php echo $client['id']; ?>" />
    <input type="submit" value="Submit" />
</form>