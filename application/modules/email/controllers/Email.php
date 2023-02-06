<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH.'modules/generic/controllers/Generic.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
require_once 'PHPMailer.php';
require_once 'SMTP.php';

class Email extends Generic{
  function __construct() {
    parent::__construct();
  }

  // function mailer($data){
  //   $this->email->clear(TRUE);
  //   $message =  $this->load->view($data['viewFile'], $data, TRUE);

  //   $this->email->set_newline("\r\n");
  //   $this->email->set_mailtype("html");
  //   $this->email->from('noreply@smc.com', 'SET MY COACH');
    
  //   if($this->mail_env == "local"){ 
  //     // $this->email->to('sheetal.godase263@gmail.com');
  //     $this->email->to($data['receiverEmail']);
  //   }else if($this->mail_env == "live" || $this->mail_env == "test"){
  //     $this->email->to($data['receiverEmail']);
  //     $this->email->bcc("viren@kwebmaker.com");
  //   }
  
  //   $this->email->subject($data['subject']);
  //   $this->email->message($message);
  //   if (!$this->email->send()) {
  //     return FALSE;
  //   }else {
  //     return TRUE;
  //   }
  // }

  // function test(){
  //   $this->email->clear(TRUE);

  //   $this->email->set_newline("\r\n");
  //   $this->email->set_mailtype("html");
  //   $this->email->from('no-reply@setmycoach.com','SET MY COACH');
  //   $this->email->to('amit@kwebmaker.com');
    
  //   $this->email->subject('subject');
  //   $this->email->message('Test Message');
    
  //   if ($this->email->send()) {
  //     echo 'sent';
  //   }else {
  //     echo 'not sent';
  //     echo $this->email->printDebugger();
  //   }
  // }

  /**
   * Functions with ses configuration
   */
  // function mailer($data){
  //   $message =  $this->load->view($data['viewFile'], $data, TRUE);
  //   $to = $data['receiverEmail'];
  //   $subject = $data['subject'];

  //   if ( sesmail($to, $subject, $message) ) {
  //     return FALSE;
  //   }else {
  //     return TRUE;
  //   }
  // }

  function mailer($data){
    $message =  $this->load->view($data['view_file'], $data, TRUE);
    $to = $data['to'];
    $subject = $data['subject'];

    try {
      $mail = new PHPMailer(true);
      
      $mail->isSMTP();                                            
      $mail->Host       = 'email-smtp.ap-south-1.amazonaws.com';                    
      $mail->SMTPAuth   = true;                                   
      $mail->Username   = 'AKIARP2M4TU5GY5ZMV5P';                     
      $mail->Password   = 'BDFZH7L7bwDg7Yr/FGE6XLeNXCxJsewvoobm43Wz6Jtk';                               
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         
      $mail->Port       = 587;                                    
      
      $mail->setFrom('no-reply@setmycoach.com', 'Setmycoach.com');
      $mail->addAddress($to);     
  
      // Content                          
      $mail->Subject = $subject;
      $mail->Body    = $message;
      $mail->isHTML(true); 

      $mail->send();
      return true;
        // echo 'Message has been sent';
    } catch (Exception $e) {
      return false;
      // echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
  }
}