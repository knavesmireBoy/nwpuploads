<!DOCTYPE html>
<html class="no-js" lang="en">
<head>
	<meta charset="utf-8">
	<meta content="IE=edge" http-equiv="X-UA-Compatible">
	<meta content="width=device-width, initial-scale=1" name="viewport">
    <title>Contact Me</title>
    <link href="css/lofi.css" type="text/css" rel="stylesheet" media="all" />
	<script>
		document.cookie = 'resolution=' + Math.max(screen.width, screen.height) + '; path=/';
	</script>
</head>
<body>
<h1>Contact Me</h1>
<?php # Script 10.1 - email.php

// Check for form submission:
if (isset($_POST['submitted'])) {

	// Minimal form validation:
	if (!empty($_POST['name']) && !empty($_POST['email']) && !empty($_POST['comments']) ) {
	
		// Create the body:
		$body = "Name: {$_POST['name']}\n\nComments: {$_POST['comments']}";
		
		// Make it no longer than 70 characters long:
		$body = wordwrap($body, 70);
	
		// Send the email:
		mail('north.wolds@btinternet.com', 'Contact Form Submission', $body, "From: {$_POST['email']}");
		
		// Print a message:
		echo '<p><em>Thank you for contacting me at ' . date('g:i a (T) '). ' on ' . date('l F j, Y') .'. I will reply some day.</em></p>';
		
		//How long did it take?
	echo '<p><strong>It took ' . (time() - $_POST['start']) . ' seconds for you to complete and submit the form.</strong></p>';

		
		// Clear $_POST (so that the form's not sticky):
		$_POST = array();
	
	} else {
		echo '<p style="font-weight: bold; color: #C00">Please fill out the form completely.</p>';
	}
	
} // End of main isset() IF.

// Create the HTML form:
?>
<p>Please fill out this form to contact me.</p>
<form action="" method="post">
	<p>Name: <input type="text" name="name" size="30" maxlength="60" value="<?php if (isset($_POST['name'])) echo $_POST['name']; ?>" /></p>
	<p>Email Address: <input type="text" name="email" size="30" maxlength="80" value="<?php if (isset($_POST['email'])) echo $_POST['email']; ?>" /></p>
	<p>Comments: <textarea name="comments" rows="5" cols="30"><?php if (isset($_POST['comments'])) echo $_POST['comments']; ?></textarea></p>
	<p><input type="submit" name="submit" value="Send!" /></p>
	<input type="hidden" name="submitted" value="TRUE" /><input type="hidden" name="start" value="<?php echo time(); ?>" />
</form>
</body>
</html>
