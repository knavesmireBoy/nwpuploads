<?php
$body =  '<html><body><p>We have just uploaded the file <a href='.
'"http://northwolds.serveftp.net/nwp_uploads/api/" /><strong>' . $file . '</strong></a> for printing.</p></body></html>'; 
if (!@mail('north.wolds@btinternet.com', 'Files to North Wolds | ' . $file,  
   $body,  
    "From: $name <{$_SESSION['email']}>\n" . 
     "cc:  $name <files@northwolds.co.uk>\n" .
    "MIME-Version: 1.0\n" .  
    "Content-type: text/html; charset=iso-8859-1"))
{
 exit('<p>The file uploaded but an email could not be sent.</p>');  
}
