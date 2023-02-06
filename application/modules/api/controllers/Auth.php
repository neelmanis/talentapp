<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH.'modules/generic/controllers/Generic.php';
date_default_timezone_set('Asia/Riyadh');

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST,GET,PUT,DELETE");
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authtoken');
header("Access-Control-Max-Age: 86000");

class Auth extends Generic{

  function __construct() {
    parent::__construct();
    $this->load->model('Mdl_api');	
	}

  /**
   * Verify Email and Password and Generate Token
   */
	public function verify(){
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

      $this->form_validation->set_rules("password","Password","trim|xss_clean|required",
      array(
        'required' => "login-password-required"
      ));
      
      if($this->form_validation->run($this) == FALSE){
        $errors = $this->form_validation->error_array();
        json_output(200,array('status'=>'error','errorData'=>$errors));
      }else{
        
        $password = $content['password'];
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

          $is_valid_password = Modules::run('security/verifyPassoword',$password,$registration[0]->password_enc);
          if($is_valid_password){

            $token = Modules::run('security/generateAuthToken',$registration[0]->uid);
            $serach_param = array(
              "registration_id"=>$registration[0]->registration_id, 
              "type"=>$registration[0]->type, 
              "uid"=>$registration[0]->uid
            );
            
            if($this->Mdl_api->isExist("authentication",$serach_param)){
              $authentication_data = array(
                "token" => $token,
                "expiry_time" => date("Y-m-d H:i:s",strtotime("+1 day")),
                "modified_date" => date("Y-m-d H:i:s")
              );
              $authentication_id = $this->Mdl_api->update("authentication",$serach_param,$authentication_data);
            }else{
              $authentication_data = array(
                "registration_id" => $registration[0]->registration_id,
                "type" => $registration[0]->type,
                "uid" => $registration[0]->uid,
                "token" => $token,
                "expiry_time" => date("Y-m-d H:i:s",strtotime("+1 day")),
                "created_date" => date("Y-m-d H:i:s"),
                "modified_date" => date("Y-m-d H:i:s")
              );
              $authentication_id = $this->Mdl_api->insert("authentication",$authentication_data);
            }

            $name_en = $name_ar = $profile_image = '';
            $registrationId = $registration[0]->registration_id;
            if($registration[0]->type == 'talent'){
              $get_details = "SELECT fullname_en, fullname_ar, profile_image FROM talent_details WHERE registration_id=$registrationId";
              $user_info = $this->Mdl_api->customQuery($get_details);
            }else{
              $get_details = "SELECT fullname, profile_image FROM user_details WHERE registration_id=$registrationId";
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
              $profile_image = $user_info[0]->profile_image;
            }

            $user = array(
              "token" => $token,
              "type" => $registration[0]->type,
              "nameEn" => $name_en,
              "nameAr" => $name_ar,
              "image" => $profile_image
            );
            json_output(200,array('status'=>'success','user'=>$user));
          }else{
            json_output(200,array('status'=>'error','errorData'=>array('password'=>'password-wrong')));
          }
        }
      }
		}
  }

  /**
   * Change expiry date of token
   */
  public function logout(){
    $method = $_SERVER['REQUEST_METHOD'];
    $headers = $this->input->request_headers();
    
    if($method !== 'GET'){
			json_output(400, array('status' => 400,'message' => 'Bad request.'));
		}else{
    
      if(isset($headers['Authtoken'])){
        $token = $headers['Authtoken'];
      }else{
        $token = $headers['authtoken'];
      }
      $check_token_validity = Modules::run('security/validateAuthToken',$token);
    
      if($check_token_validity['status'] === "invalid"){
        json_output(200, array('status' => 'invalid token'));
      }else if($check_token_validity['status'] === "expired"){
        json_output(200, array('status' => 'expired'));
      }else if($check_token_validity['status'] === "valid"){
      
        $registration_id = $check_token_validity['registration_id'];
        $uid = $check_token_validity['uid'];
        $type = $check_token_validity['type'];
        $authentication_details = $this->Mdl_api->retrieve('authentication',array('registration_id'=>$registration_id, 'uid'=>$uid, 'type'=>$type));
      
        if($authentication_details !== "NA"){
          $updated_data = array(
            "expiry_time" => date("Y-m-d H:i:s"),
            "modified_date" => date("Y-m-d H:i:s")
          );
          $update = $this->Mdl_api->update('authentication', array('registration_id'=>$registration_id, 'uid'=>$uid, 'type'=>$type), $updated_data);
          json_output(200, array('status' => 'success'));
        }else{
          json_output(200, array('status'=>'no data'));
        }
      }
		}
  }
  
  /**
   * Signin by social media ( Google, Facebook )
   */
  public function provider(){
    $method = $this->input->method(TRUE);

		if($method !== 'POST'){
			json_output(400,array('status' => 400,'message' => 'Bad request.'));
		}else{
    
      $content = json_decode(file_get_contents('php://input'), TRUE);
      $this->form_validation->set_data($content);
      
      $this->form_validation->set_rules("email","Email","trim|xss_clean|required|valid_email");
      $this->form_validation->set_rules("name","Name","trim|xss_clean|required");
      $this->form_validation->set_rules("iamge","Profile Image","trim|xss_clean");
      $this->form_validation->set_rules("providerId","Provider Userid","trim|xss_clean|required");
      $this->form_validation->set_rules("provider","Provider","trim|xss_clean|in_list[google,facebook]|required");
      $this->form_validation->set_rules("lang","Selected Language","trim|xss_clean|in_list[en,ar]|required");
    
      if($this->form_validation->run($this) == FALSE){
        $errors = $this->form_validation->error_array();
        json_output(200,array('status'=>'error','errorData'=>$errors));
      }else{
  
        $registration = $this->Mdl_api->retrieve("registration", array("email" => $content['email']));
        if($registration == "NA"){
    
          $uid = $this->getUID();
          $registration_data = array(
            "type" => 'user',
            "provider" => strip_tags($content['provider']),
            "provider_id" => strip_tags($content['providerId']),
            "uid" => $uid,
            "email" => strip_tags($content['email']),
            "password_text" => null,
            "password_enc" => null,
            "is_mail_verified" => "yes",
            "application_status" => "approved",
            "account_status" => "active",
            "admin_id" => 0,
            "created_date" => date("Y-m-d H:i:s"),
            "modified_date" => date("Y-m-d H:i:s")
          );
          $registration_id = $this->Mdl_api->insert("registration",$registration_data);
          
          $user_data = array(
            "registration_id" => $registration_id,
            "lang" => strip_tags($content['lang']),
            "fullname" => strip_tags($content['name']),
            "email" => strip_tags($content['email']),
            "country" => null,
            "phone_code" => null,
            "phone" => null,
            "birth_date" => null,
            "contact_preference" => null,
            "gender" => null,
            "profile_image" => strip_tags($content['image']),
            "created_date" => date("Y-m-d H:i:s"),
            "modified_date" => date("Y-m-d H:i:s")
          );
          $user_id = $this->Mdl_api->insert("user_details",$user_data);
          
          $token = Modules::run('security/generateAuthToken',$uid);
          $serach_param = array(
            "registration_id"=>$registration_id, 
            "type"=>'user', 
            "uid"=>$uid
          );
          
          if($this->Mdl_api->isExist("authentication",$serach_param)){
            $authentication_data = array(
              "token" => $token,
              "expiry_time" => date("Y-m-d H:i:s",strtotime("+120 minutes")),
              "modified_date" => date("Y-m-d H:i:s")
            );
            $authentication_id = $this->Mdl_api->update("authentication",$serach_param,$authentication_data);
          }else{
            $authentication_data = array(
              "registration_id" => $registration_id,
              "type" => 'user',
              "uid" => $uid,
              "token" => $token,
              "expiry_time" => date("Y-m-d H:i:s",strtotime("+120 minutes")),
              "created_date" => date("Y-m-d H:i:s"),
              "modified_date" => date("Y-m-d H:i:s")
            );
            $authentication_id = $this->Mdl_api->insert("authentication",$authentication_data);
          }
          
          $name_en = $name_ar = strip_tags($content['name']);
          $profile_image = strip_tags($content['image']);
          $user = array(
            "token" => $token,
            "type" => 'user',
            "nameEn" => $name_en,
            "nameAr" => $name_ar,
            "image" => $profile_image
          );

          if($content['lang'] === "en"){
            $subject = "Welcome to Halagram";
          }else{
            $subject = "Welcome to Halagram";
          }
        
          $email_view_file = 'registration-success-'.$content['lang'];
          $mail_data = array(
            'view_file' => 'user/'.$email_view_file,
            'to' => strip_tags($content['email']),
            'cc' => '',
            'bcc' => '',
            'subject' => $subject,
            'isAttachment' => false,
            "name" => strip_tags($content['fullName'])
          );
          Modules::run('email/mailer',$mail_data);
          json_output(200,array('status'=>'success','user'=>$user));
        
        }else if($registration[0]->account_status == "deactive"){
          json_output(200,array('status'=>'fail','message'=>'account-deactive'));
        }else if($registration[0]->type == "talent"){
          json_output(200,array('status'=>'fail','message'=>'invalid-account'));
        }else if($registration[0]->type == "user" && $registration[0]->provider == $content['provider']){

          $token = Modules::run('security/generateAuthToken',$registration[0]->uid);
          $serach_param = array(
            "registration_id"=>$registration[0]->registration_id, 
            "type"=>$registration[0]->type, 
            "uid"=>$registration[0]->uid
          );
          
          if($this->Mdl_api->isExist("authentication",$serach_param)){
            $authentication_data = array(
              "token" => $token,
              "expiry_time" => date("Y-m-d H:i:s",strtotime("+120 minutes")),
              "modified_date" => date("Y-m-d H:i:s")
            );
            $authentication_id = $this->Mdl_api->update("authentication",$serach_param,$authentication_data);
          }else{
            $authentication_data = array(
              "registration_id" => $registration[0]->registration_id,
              "type" => $registration[0]->type,
              "uid" => $registration[0]->uid,
              "token" => $token,
              "expiry_time" => date("Y-m-d H:i:s",strtotime("+120 minutes")),
              "created_date" => date("Y-m-d H:i:s"),
              "modified_date" => date("Y-m-d H:i:s")
            );
            $authentication_id = $this->Mdl_api->insert("authentication",$authentication_data);
          }

          $name_en = $name_ar = $profile_image = '';
          $registrationId = $registration[0]->registration_id;
          $get_details = "SELECT fullname, profile_image FROM user_details WHERE registration_id=$registrationId ";
          $user_info = $this->Mdl_api->customQuery($get_details);
          
          if($user_info !== "NA"){
            $name_en = $name_ar = $user_info[0]->fullname;
            $profile_image = $user_info[0]->profile_image;
          }
          
          $user = array(
            "token" => $token,
            "type" => $registration[0]->type,
            "nameEn" => $name_en,
            "nameAr" => $name_ar,
            "image" => $profile_image
          );
          json_output(200,array('status'=>'success','user'=>$user));
        }else{
          json_output(200,array('status'=>'fail','message'=>'email-in-use'));
        } 
      }
		}
  }

  /**
   *  Validate Token
   */
	public function validateToken(){
    $method = $_SERVER['REQUEST_METHOD'];
    $headers = $this->input->request_headers();

		if($method !== 'GET'){
			json_output(400, array('status' => 400,'message' => 'Bad request.'));
		}else{
    
      if(isset($headers['Authtoken'])){
        $token = $headers['Authtoken'];
      }else{
        $token = $headers['authtoken'];
      }
      $check_token_validity = Modules::run('security/validateAuthToken',$token);
    
      if($check_token_validity['status'] === "valid"){
        json_output(200, array('status' => 'success'));
      }else{
        json_output(200, array('status' => 'fail'));
      } 
    }
  }
}
