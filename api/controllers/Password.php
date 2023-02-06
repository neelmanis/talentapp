<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH.'modules/generic/controllers/Generic.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST,GET,PUT,DELETE");
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authtoken');
header("Access-Control-Max-Age: 86000");

class Password extends Generic{

  function __construct() {
    parent::__construct();
    $this->load->model('Mdl_api');	
	}

  /**
	 * Password validation
	 */
	private function validPassword($password){
		$password = trim($password);
		$regex_lowercase = '/[a-z]/';
		$regex_uppercase = '/[A-Z]/';
		$regex_letters = '/[a-zA-Z]/';
		$regex_number = '/[0-9]/';
    $regex_special = '/[!@#$%^&*()\-_=+{};:,<.>�~]/';
    
		if (empty($password)){
			return 'login-password-required';
    }
    
		// if (preg_match_all($regex_lowercase, $password) < 1){
		// 	return 'The password field must have at least one lowercase letter.';
		// }
		// if (preg_match_all($regex_uppercase, $password) < 1){
		// 	return 'The password field must have at least one uppercase letter.';
		// }
		// if (preg_match_all($regex_number, $password) < 1){
		// 	return 'The password field must have at least one number.';
		// }
		// echo 'char : '.preg_match_all($regex_letters, $password);
    // echo 'num : '.preg_match_all($regex_number, $password);
    
		$count_letters = preg_match_all($regex_letters, $password);
    $count_numbers = preg_match_all($regex_number, $password);
    
		if (!($count_letters > 0  && $count_numbers > 0)){
			return 'login-password-alphanumeric';
    }
    
		if (preg_match_all($regex_special, $password) < 1){
			return 'login-password-spl-char';
    }
    
		if (strlen($password) < 8){
			return 'login-password-min-len';
    }
    
		return 'valid';
  }

  /**
   * Password change request
   */
  public function resetLink(){
    $method = $this->input->method(TRUE);
    
		if($method !== 'POST'){
			json_output(400,array('status' => 400,'message' => 'Bad request.'));
		}else{
    
      $content = json_decode(file_get_contents('php://input'), TRUE);
      $this->form_validation->set_data($content);
     
      $this->form_validation->set_rules("email","Email","trim|xss_clean|required|valid_email",
      array(
        'required' => "login-email-required",
        'valid_email' => "login-email-invalid"
      ));
      
      if($this->form_validation->run($this) == FALSE){
        $errors = $this->form_validation->error_array();
        json_output(200,array('status'=>'error','errorData'=>$errors));
      }else{
        $email = $content['email'];
        $registration = $this->Mdl_api->retrieve("registration", array("email" => $email));
        
        if($registration == "NA"){
          json_output(200,array('status'=>'fail','message'=>'user-not-exist'));
        }else if($registration[0]->provider !== "platform"){

          if($registration[0]->provider == "google"){
            $message = "registered-via-google";
          }else{
            $message = "registered-via-facebook";
          }
          json_output(200,array('status'=>'fail','message'=>$message));

        }else if($registration[0]->account_status == "deactive"){
          json_output(200,array('status'=>'fail','message'=>'account-deactivate'));
        }else{

          $password_token = $this->getPassToken();

          $update_data = array(
            'password_token' => $password_token,
            'modified_date' => date('Y-m-d H:i:s')
          );
          $update_registration = $this->Mdl_api->update("registration",array('registration_id'=> $registration[0]->registration_id), $update_data);

          $name_en = $name_ar = $lang = '';
          $registrationId = $registration[0]->registration_id;

          if($registration[0]->type == 'talent'){
            $get_details = "SELECT lang, fullname_en, fullname_ar FROM talent_details WHERE registration_id=$registrationId ";
            $user_info = $this->Mdl_api->customQuery($get_details);
          }else{
            $get_details = "SELECT lang, fullname FROM user_details WHERE registration_id=$registrationId ";
            $user_info = $this->Mdl_api->customQuery($get_details);
          } 

          if($user_info !== "NA"){
            if($registration[0]->type == 'talent'){
              $name_en = $user_info[0]->fullname_en;
              $name_ar = $user_info[0]->fullname_ar;
            }else{
              $name_en = $user_info[0]->fullname;
              $name_ar = $user_info[0]->fullname;
            }
            $lang = $user_info[0]->lang;
          }

          $recovery_link = $this->global_variables['front_end_url'].'reset-password/'.$password_token;
          // if($lang === "en"){
          //   $subject = "HalaGram - Reset Password";
          // }else{
          //   $subject = "هلاجرام - إعادة تعيين كلمة المرور";
          // }
          // $email_view_file = 'password-reset-'.$lang;
        
          $mail_data = array(
            // 'view_file' => $email_view_file,
            // 'subject' => $subject,
            'template' => "reset password",
            'lang' => $lang,
            'to' => strip_tags($content['email']),
            'cc' => '',
            'bcc' => '',
            'halagram_logo' => base_url()."assets/images/HalaLogo.png",
            'isAttachment' => false,
            'recovery_link' => $recovery_link,
            "name" => ${'name_'.$lang}
          );
          
          if(Modules::run('email/template',$mail_data)){
            json_output(200,array('status'=>'success'));
          }else{
            json_output(200,array('status'=>'fail','message'=>'mail-failed'));
          }
        }
      }
		}
  }

  /**
   *  Verify Password token
   */
  public function verify(){
    $method = $this->input->method(TRUE);
    
		if($method !== 'POST'){
			json_output(400,array('status' => 400,'message' => 'Bad request.'));
		}else{
    
      $content = json_decode(file_get_contents('php://input'), TRUE);
      $this->form_validation->set_data($content);
      
      $password_token = $content['passToken'];
      $registration = $this->Mdl_api->retrieve("registration", array("password_token" => $password_token));
        
      if($registration == "NA"){
        json_output(200,array('status'=>'fail','message'=>'invalid-password-token'));
      }else if($registration[0]->provider !== "platform"){
        if($registration[0]->provider == "google"){
          $message = "registered-via-google";
        }else{
          $message = "registered-via-facebook";
        }
        json_output(200,array('status'=>'fail','message'=>$message));

      }else if($registration[0]->account_status == "deactive"){
        json_output(200,array('status'=>'fail','message'=>'account-deactivate'));
      }else{
        json_output(200,array('status'=>'success'));
      }
		}
  }

  /**
   * Password reset
   */
  public function reset(){
    $method = $this->input->method(TRUE);
    
		if($method !== 'POST'){
			json_output(400,array('status' => 400,'message' => 'Bad request.'));
		}else{
    
      $content = json_decode(file_get_contents('php://input'), TRUE);
      $this->form_validation->set_data($content);
     
      $this->form_validation->set_rules("password","Password","trim|xss_clean|required",
      array(
        'required' => "login-password-required"
      ));
      
      if(isset($content['password'] ) && $content['password'] !== ""){
        $valid_password = $this->validPassword($content['password']);
        if($valid_password !== "valid"){
          $custom_errors['password'] = $valid_password;
        }
      }

      $this->form_validation->set_rules("confirmPassword","Confirm Password","trim|xss_clean|required|matches[password]",
      array(
        'required' => "login-password-retype",
        'matches' => 'login-password-match'
      ));
      
      if($this->form_validation->run($this) == FALSE){
        $errors = $this->form_validation->error_array();
        json_output(200,array('status'=>'error','errorData'=>$errors));
      }else{

        $password_token = $content['passToken'];
        $registration = $this->Mdl_api->retrieve("registration", array("password_token" => $password_token));
        
        if($registration == "NA"){
          json_output(200,array('status'=>'fail','message'=>'user-not-exist'));
        }else if($registration[0]->provider !== "platform"){

          if($registration[0]->provider == "google"){
            $message = "registered-via-google";
          }else{
            $message = "registered-via-facebook";
          }
          json_output(200,array('status'=>'fail','message'=>$message));

        }else if($registration[0]->account_status == "deactive"){
          json_output(200,array('status'=>'fail','message'=>'account-deactivate'));
        }else{

          $password_text = strip_tags($content['password']);
          $password_enc = Modules::run('security/makeHash',$password_text);
        
          $update_data = array(
            "password_text" => $password_text,
            "password_enc" => $password_enc,
            "password_token" => null,
            "modified_date" => date('Y-m-d H:i:s')
          );
          $update_registration = $this->Mdl_api->update("registration",array('registration_id'=> $registration[0]->registration_id), $update_data);

          $name_en = $name_ar = $lang = '';
          $registrationId = $registration[0]->registration_id;

          if($registration[0]->type == 'talent'){
            $get_details = "SELECT lang, fullname_en, fullname_ar FROM talent_details WHERE registration_id=$registrationId ";
            $user_info = $this->Mdl_api->customQuery($get_details);
          }else{
            $get_details = "SELECT lang, fullname FROM user_details WHERE registration_id=$registrationId ";
            $user_info = $this->Mdl_api->customQuery($get_details);
          } 

          if($user_info !== "NA"){
            if($registration[0]->type == 'talent'){
              $name_en = $user_info[0]->fullname_en;
              $name_ar = $user_info[0]->fullname_ar;
            }else{
              $name_en = $user_info[0]->fullname;
              $name_ar = $user_info[0]->fullname;
            }
            $lang = $user_info[0]->lang;
          }

          // if($lang === "en"){
          //   $subject = "HalaGram - Reset Password";
          // }else{
          //   $subject = "هلاجرام - إعادة تعيين كلمة المرور";
          // }
          // $email_view_file = 'password-updated-'.$lang;
        
          $mail_data = array(
            // 'view_file' => $email_view_file,
            // 'subject' => $subject,
            'template' => "password change confirmation",
            'lang' => $lang,
            'to' => $registration[0]->email,
            'cc' => '',
            'bcc' => '',
            'halagram_logo' => base_url()."assets/images/HalaLogo.png",
            'isAttachment' => false,
            "name" => ${'name_'.$lang},
            "front_end" => $this->global_variables['front_end_url']
          );
          
          Modules::run('email/template',$mail_data);
          json_output(200,array('status'=>'success'));
        }
      }
		}
  }
}
