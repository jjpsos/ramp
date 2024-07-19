<?php

$to      = 'jjpsos@videotron.ca';
$subject = 'Subject';
$message = 'Hello-Again-3';

$header = "From: jjpsos@videotron.ca\r\n";
$header.= "MIME-Version: 1.0\r\n";
$header.= "Content-Type: text/html; charset=ISO-8859-1\r\n";
$header.= "X-Priority: 1\r\n";

$mail = mail($to, $subject, $message, $header);
echo $mail ? "<h1>Email Sent Successfully!</h1>" : "<h1>Email sending failed.</h1>";

