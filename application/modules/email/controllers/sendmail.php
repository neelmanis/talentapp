<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
require_once 'PHPMailer.php';
require_once 'SMTP.php';

function sesmail($to,$subject,$msg){

	try {
    $mail = new PHPMailer(true);
    // $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      // Enable verbose debug output
    $mail->isSMTP();                                            // Send using SMTP
    $mail->Host       = 'email-smtp.ap-south-1.amazonaws.com';                    // Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
    $mail->Username   = 'AKIARP2M4TU5GY5ZMV5P';                     // SMTP username
    $mail->Password   = 'BDFZH7L7bwDg7Yr/FGE6XLeNXCxJsewvoobm43Wz6Jtk';                               // SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
		$mail->Port       = 587;                                    // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above
		
    //Recipients
    $mail->setFrom('no-reply@setmycoach.com', 'setmycoach.com');
    $mail->addAddress($to);     // Add a recipient

    // Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = $subject;
    $mail->Body    = $msg;

		$mail->send();
		return true;
    	// echo 'Message has been sent';
	} catch (Exception $e) {
		return false;
		// echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
	}
}

?>
