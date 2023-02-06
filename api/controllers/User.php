<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH.'modules/generic/controllers/Generic.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST,GET,PUT,DELETE");
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authtoken');
header("Access-Control-Max-Age: 86000");

class User extends Generic{

  function __construct() {
    parent::__construct();
    $this->load->model('Mdl_api');		
	}

  /**
   * Callback : Indian Mobile
   */
	public function mobile_ind_check($mobile){
		if($mobile == ""){
			$this->form_validation->set_message('mobile_ind_check','user-phoneNumber-required');
			return false;
		}else if(preg_match('/^[6-9][0-9]{9}$/', $mobile)) {
			return true;
		}else{
			$this->form_validation->set_message('mobile_ind_check','user-phoneNumber-invalid');
			return false;
		}
  }
  
  /**
   * Callback : International Mobile
   */
	public function mobile_intl_check($mobile){
		if($mobile == ""){
			$this->form_validation->set_message('mobile_intl_check','user-phoneNumber-required');
			return false;
		}else if(preg_match('/^[0-9]{7,15}$/', $mobile)) {
			return true;
		}else{
			$this->form_validation->set_message('mobile_intl_check','user-phoneNumber-invalid');
			return false;
		}
  }

  /**
   * Callback : Email unique check
   */
	public function unique_email_check($email){
		if($email == ""){
			$this->form_validation->set_message('unique_email_check','user-email-required');
			return false;
		}else{
      // if($this->Mdl_api->isExist('registration',array('email'=>$email))){
      //   $this->form_validation->set_message('unique_email_check','user-email-exist');
			//   return false;
      // }else{
      //   return true;
      // }

      $serchMail = strtolower($email);
      $query = "SELECT registration_id FROM registration WHERE LOWER(email)='$serchMail' ";
      $find = $this->Mdl_api->customQuery($query);

      if( $find !== "NA" ){
        $this->form_validation->set_message('unique_email_check','user-email-exist');
        return false;
      }else{
        return true;
      }

    }
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
			return 'user-password-required';
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
			return 'user-password-alphanumeric';
    }
    
		if (preg_match_all($regex_special, $password) < 1){
			return 'user-password-spl-char';
    }
    
		if (strlen($password) < 8){
			return 'user-password-min-len';
    }
    
		return 'valid';
  }

  /**
   * User Registration
   */

  /*
	public function register(){
    $method = $_SERVER['REQUEST_METHOD'];
    
		if($method !== 'POST'){
			json_output(400,array('status' => 400,'message' => 'Bad request.'));
		}else{
    
      $content = json_decode(file_get_contents('php://input'), TRUE);
      $this->form_validation->set_data($content);
      $custom_errors = array();
    
      $this->form_validation->set_rules("fullName","Full Name","trim|xss_clean|required",
      array(
        'required' => "user-fullName-required"
      ));

      $this->form_validation->set_rules("country","Country of Residence","trim|xss_clean|required",
      array(
        'required' => "user-country-required"
      ));

      $this->form_validation->set_rules("email","Email","trim|xss_clean|required|valid_email|callback_unique_email_check",
      array(
        'required' => "user-email-required",
        'valid_email' => "user-email-invalid"
      ));

      $this->form_validation->set_rules("password","Password","trim|xss_clean|required",
      array(
        'required' => "user-password-required"
      ));
      
      if(isset($content['password'] ) && $content['password'] !== ""){
        $valid_password = $this->validPassword($content['password']);
        if($valid_password !== "valid"){
          $custom_errors['password'] = $valid_password;
        }
      }

      $this->form_validation->set_rules("confirmPassword","Confirm Password","trim|xss_clean|required|matches[password]",
      array(
        'required' => "user-password-retype",
        'matches' => 'user-password-match'
      ));

      $this->form_validation->set_rules("phoneCode","Phone Code","trim|xss_clean|required",
      array(
        'required' => "user-phonecode-required"
      ));

      $this->form_validation->set_rules("phoneNumber","Phone Number","trim|xss_clean|callback_mobile_intl_check");
      
      $this->form_validation->set_rules("contactPreference","Contact Preference","trim|xss_clean|required|in_list[email,phone]",
      array(
        'required' => "user-contactPreference-required",
        'in_list' => 'user-invalid-option'
      ));

      $this->form_validation->set_rules("gender","Gender","trim|xss_clean|required|in_list[male,female]",
      array(
        'required' => "user-gender-required",
        'in_list' => 'user-invalid-option'
      ));

      $this->form_validation->set_rules("dob","Date of Birth","trim|xss_clean|required",
      array(
        'required' => "user-select-dob"
      ));

      if($this->form_validation->run($this) == FALSE){
        $errors = $this->form_validation->error_array();
        $final_array = array_merge($errors,$custom_errors);
        json_output(200,array('status'=>'error','errorData'=>$final_array));
      }else{

        if(! empty($custom_errors)){
          json_output(200,array('status'=>'error','errorData'=>$custom_errors));
        }
        
        $password_text = strip_tags($content['password']);
        $password_enc = Modules::run('security/makeHash',$password_text);
        $uid = $this->getUID();
        
        $registration_data = array(
          "type" => 'user',
          "provider" => 'platform',
          "uid" => $uid,
          "email" => strip_tags($content['email']),
          "password_text" => $password_text,
          "password_enc" => $password_enc,
          "is_mail_verified" => "yes",
          "application_status" => "approved",
          "profile_status" => "active",
          "account_status" => "active",
          "is_talent" => "no",
          "admin_id" => 0,
          "created_date" => date("Y-m-d H:i:s"),
          "modified_date" => date("Y-m-d H:i:s")
        );
        $registration_id = $this->Mdl_api->insert("registration",$registration_data);
        
        $user_data = array(
          "registration_id" => $registration_id,
          "lang" => strip_tags($content['lang']),
          "fullname" => strip_tags($content['fullName']),
          "email" => strip_tags($content['email']),
          "country" => strip_tags($content['country']),
          "phone_code" => strip_tags($content['phoneCode']),
          "phone" => strip_tags($content['phoneNumber']),
          "birth_date" => date("Y-m-d",strtotime(strip_tags($content['dob']))),
          "contact_preference" => strip_tags($content['contactPreference']),
          "gender" => strip_tags($content['gender']),
          "profile_image" => '',
          "created_date" => date("Y-m-d H:i:s"),
          "modified_date" => date("Y-m-d H:i:s")
        );
        $user_id = $this->Mdl_api->insert("user_details",$user_data);

        // if($content['lang'] === "en"){
        //   $subject = "Welcome to HalaGram";
        // }else{
        //   $subject = "هلاجرام ترحب بكم";
        // }

        // $email_view_file = 'registration-success-'.$content['lang'];
        $mail_data = array(
          // 'view_file' => 'user/'.$email_view_file,
          // 'subject' => $subject,
           // "front_end" => $this->global_variables['front_end_url'],
          'halagram_logo' => base_url().'assets/images/HalaLogo.png',
          'template' => 'user registration',
          'lang' => $content['lang'],
          'to' => strip_tags($content['email']),
          'isAttachment' => false,
          "name" => strip_tags($content['fullName']),
          "website_url" => $this->global_variables['front_end_url']
        );
        // Modules::run('email/mailer',$mail_data);
        Modules::run('email/template',$mail_data);
        
        json_output(200,array('status'=>'success'));
      }
		}
  }
  */

  /**
   *  User Registration with mail verification
   */
  public function register(){
    $method = $_SERVER['REQUEST_METHOD'];
    
		if($method !== 'POST'){
			json_output(400,array('status' => 400,'message' => 'Bad request.'));
		}else{
    
      $content = json_decode(file_get_contents('php://input'), TRUE);
      $this->form_validation->set_data($content);
      $custom_errors = array();
    
      $this->form_validation->set_rules("fullName","Full Name","trim|xss_clean|required",
      array(
        'required' => "user-fullName-required"
      ));

      $this->form_validation->set_rules("country","Country of Residence","trim|xss_clean|required",
      array(
        'required' => "user-country-required"
      ));

      $this->form_validation->set_rules("email","Email","trim|xss_clean|required|valid_email|callback_unique_email_check",
      array(
        'required' => "user-email-required",
        'valid_email' => "user-email-invalid"
      ));

      $this->form_validation->set_rules("password","Password","trim|xss_clean|required",
      array(
        'required' => "user-password-required"
      ));
      
      if(isset($content['password'] ) && $content['password'] !== ""){
        $valid_password = $this->validPassword($content['password']);
        if($valid_password !== "valid"){
          $custom_errors['password'] = $valid_password;
        }
      }

      $this->form_validation->set_rules("confirmPassword","Confirm Password","trim|xss_clean|required|matches[password]",
      array(
        'required' => "user-password-retype",
        'matches' => 'user-password-match'
      ));

      $this->form_validation->set_rules("phoneCode","Phone Code","trim|xss_clean|required",
      array(
        'required' => "user-phonecode-required"
      ));

      $this->form_validation->set_rules("phoneNumber","Phone Number","trim|xss_clean|callback_mobile_intl_check");
      
      $this->form_validation->set_rules("contactPreference","Contact Preference","trim|xss_clean|required|in_list[email,phone]",
      array(
        'required' => "user-contactPreference-required",
        'in_list' => 'user-invalid-option'
      ));

      $this->form_validation->set_rules("gender","Gender","trim|xss_clean|required|in_list[male,female]",
      array(
        'required' => "user-gender-required",
        'in_list' => 'user-invalid-option'
      ));

      $this->form_validation->set_rules("dob","Date of Birth","trim|xss_clean|required",
      array(
        'required' => "user-select-dob"
      ));

      if($this->form_validation->run($this) == FALSE){
        $errors = $this->form_validation->error_array();
        $final_array = array_merge($errors,$custom_errors);
        json_output(200,array('status'=>'error','errorData'=>$final_array));
      }else{

        if(! empty($custom_errors)){
          json_output(200,array('status'=>'error','errorData'=>$custom_errors));
        }
        
        $password_text = strip_tags($content['password']);
        $password_enc = Modules::run('security/makeHash',$password_text);
        $uid = $this->getUID();
        
        $registration_data = array(
          "type" => 'user',
          "provider" => 'platform',
          "uid" => $uid,
          "email" => strip_tags($content['email']),
          "password_text" => $password_text,
          "password_enc" => $password_enc,
          "is_mail_verified" => "no",
          "application_status" => "pending",
          "profile_status" => "pending",
          "account_status" => "deactive",
          "is_talent" => "no",
          "admin_id" => 0,
          "created_date" => date("Y-m-d H:i:s"),
          "modified_date" => date("Y-m-d H:i:s")
        );
        $registration_id = $this->Mdl_api->insert("registration",$registration_data);
        
        $user_data = array(
          "registration_id" => $registration_id,
          "lang" => strip_tags($content['lang']),
          "fullname" => strip_tags($content['fullName']),
          "email" => strip_tags($content['email']),
          "country" => strip_tags($content['country']),
          "phone_code" => strip_tags($content['phoneCode']),
          "phone" => strip_tags($content['phoneNumber']),
          "birth_date" => date("Y-m-d",strtotime(strip_tags($content['dob']))),
          "contact_preference" => strip_tags($content['contactPreference']),
          "gender" => strip_tags($content['gender']),
          "profile_image" => '',
          "created_date" => date("Y-m-d H:i:s"),
          "modified_date" => date("Y-m-d H:i:s")
        );
        $user_id = $this->Mdl_api->insert("user_details",$user_data);

        // if($content['lang'] === "en"){
        //   $subject = "Welcome to HalaGram";
        // }else{
        //   $subject = "هلاجرام ترحب بكم";
        // }

        // $email_view_file = 'registration-success-'.$content['lang'];

        $verification_link = $this->global_variables['front_end_url'].'user/verify/'.$uid;

        $mail_data = array(
          // 'view_file' => 'user/'.$email_view_file,
          // 'subject' => $subject,
           // "front_end" => $this->global_variables['front_end_url'],
          'halagram_logo' => base_url().'assets/images/HalaLogo.png',
          'template' => 'user registration',
          'lang' => $content['lang'],
          'to' => strip_tags($content['email']),
          'isAttachment' => false,
          "name" => strip_tags($content['fullName']),
          "website_url" => $this->global_variables['front_end_url'],
          'verification_link' => $verification_link
        );
        // Modules::run('email/mailer',$mail_data);
        Modules::run('email/template',$mail_data);
        
        json_output(200,array('status'=>'success'));
      }
		}
  }

  /**
   *  User Verification
   */
	public function verification(){
		$method = $_SERVER['REQUEST_METHOD'];
    
    if($method !== 'POST'){
			json_output(400,array('status' => 400,'message' => 'Bad request.'));
		}else{
    
      $content = json_decode(file_get_contents('php://input'), TRUE);
      $uid = $content['uid'];
    
      $search_data = array(
        'uid' => $uid,
        'type' => 'user'
      );
      $registration = $this->Mdl_api->retrieve("registration",$search_data);
      
      $registrationId = $registration[0]->registration_id; 
      $get_user_details = "SELECT fullname, profile_image FROM user_details WHERE registration_id='$registrationId' ";
      $user_details = $this->Mdl_api->customQuery($get_user_details);

      if($registration == "NA" || $user_details == "NA" || $registration[0]->type == 'talent'){
        json_output(200,array('status'=>'danger','message'=>'something-went-wrong'));
      }else if($registration[0]->is_mail_verified == "yes" && $registration[0]->account_status == "deactive"){
        json_output(200,array('status'=>'danger','message'=>'account-deactive'));
      }else{        
        
        if($registration[0]->is_mail_verified == "no"){
          $updated_registration_data = array(
            "is_mail_verified" => "yes",
            "application_status" => "approved",
            "profile_status" => "active",
            "account_status" => "active",
            'modified_date' => date("Y-m-d H:i:s")
          );
          $updated = $this->Mdl_api->update("registration",$search_data, $updated_registration_data);
        }
        
        $token = Modules::run('security/generateAuthToken',$registration[0]->uid);
        $serach_param = array(
          "registration_id"=>$registrationId, 
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
        if($user_details !== "NA"){
          if($registration[0]->type == 'user'){
            $name_en = $user_details[0]->fullname;
            $name_ar = $user_details[0]->fullname;
          }
          $profile_image = $user_details[0]->profile_image;
        }

        $user = array(
          "token" => $token,
          "type" => $registration[0]->type,
          "nameEn" => $name_en,
          "nameAr" => $name_ar,
          "image" => $profile_image
        );

        json_output(200,array('status'=>'success','message'=>'user-email-verified','user'=>$user));
      } 
		}
  }
  
  /**
   * Get User Details
   */
	public function getDetails(){
		$method = $_SERVER['REQUEST_METHOD'];
    $headers = $this->input->request_headers();
    
		if($method !== 'GET'){
			json_output(400, array('status' => 400,'message' => 'Bad request.'));
		}else{
    
      $token = isset($headers['Authtoken']) ? $headers['Authtoken'] : $headers['authtoken'];
      $check_token_validity = Modules::run('security/validateAuthToken',$token);
    
      if($check_token_validity['status'] === "invalid"){
        json_output(200, array('status' => 'invalid token'));
      }else if($check_token_validity['status'] === "expired"){
        json_output(200, array('status' => 'expired'));
      }else if($check_token_validity['status'] === "valid"){

        $registration_id = $check_token_validity['registration_id'];
        $uid = $check_token_validity['uid'];
        $type = $check_token_validity['type'];
        
        $user_registration = $this->Mdl_api->retrieve('registration',array('registration_id'=>$registration_id, 'uid'=>$uid));
        $user_details = $this->Mdl_api->retrieve('user_details',array('registration_id'=>$registration_id));
        
        if($user_registration !== "NA" && $user_details !== "NA"){
          $response = array(
            "fullName" => $user_details[0]->fullname,
            "email" => $user_details[0]->email,
            "provider" => $user_registration[0]->provider,
            "password" => $user_registration[0]->password_text,
            "country" => $user_details[0]->country,
            "phoneCode" => $user_details[0]->phone_code,
            "phoneNumber" => $user_details[0]->phone,
            "contactPreference" => $user_details[0]->contact_preference,
            "gender" => $user_details[0]->gender,
            "dob" => $user_details[0]->birth_date,
            "profileImage" => $user_details[0]->profile_image
          );
          json_output(200, array('status' => 'success','details' => $response));
        }else{
          json_output(200, array('status'=>'no data'));
        }
      }
		}
  }

  /**
   * User Upload Profile Image
   */
  public function uploadImage(){
    $method = $_SERVER['REQUEST_METHOD'];
    $headers = $this->input->request_headers();

		if($method !== 'POST'){
			json_output(400, array('status' => 400,'message' => 'Bad request.'));
		}else{
    
      $token = isset($headers['Authtoken']) ? $headers['Authtoken'] : $headers['authtoken'];
      $check_token_validity = Modules::run('security/validateAuthToken',$token);
    
      if($check_token_validity['status'] === "invalid"){
        json_output(200, array('status' => 'invalid token'));
      }else if($check_token_validity['status'] === "expired"){
        json_output(200, array('status' => 'expired'));
      }else if($check_token_validity['status'] === "valid"){
    
        $registration_id = $check_token_validity['registration_id'];
        $uid = $check_token_validity['uid'];
    
        $user_details = $this->Mdl_api->retrieve('user_details',array('registration_id'=>$registration_id));
        $custom_errors = array();
    
        $image_allowed_type = array('png','jpeg','jpg','PNG','JPEG','JPG');
        if($_FILES['profileImage']['name'] !== "" ){
          $filename = $_FILES['profileImage']['name'];
          $ext = pathinfo($filename, PATHINFO_EXTENSION);
          if(! in_array($ext, $image_allowed_type)){
            $custom_errors['profileImage'] = 'invalid-image-type';
          }else if($_FILES['profileImage']['size'] > 100000000){
            $custom_errors['profileImage'] = 'file-size';
          }
        }else{
          $custom_errors['profileImage'] = 'image-required';
        }

        if(! empty($custom_errors)){
          json_output(200,array('status'=>'error','errorData'=>$custom_errors));
        }else{
        
          if(! empty($_FILES['profileImage']['name'])){
            $filename = $_FILES['profileImage']['name'];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $imagename = 'profile-'.$uid.strtotime('now').'.'.$ext;
						$key = $imagename;
            $upload_response = $this->addObject($key,'profileImage');

            if($upload_response['upload']){
              $old_profile_image = $user_details[0]->profile_image;
              
              if($old_profile_image !== ''){
                $this->removeObject($old_profile_image);
              }

              $updated_user_details = array(
                'profile_image' => $upload_response['path'],
                "modified_date" => date("Y-m-d H:i:s")
              );
              $update_profile_image = $this->Mdl_api->update("user_details",array('registration_id' => $registration_id),$updated_user_details);
             
              json_output(200, array('status'=>'success', 'profileImage'=>$upload_response['path'] ));
            }else{
              json_output(200, array('status'=>'fail', 'profileImage'=>"upload-failed" ));
            }
          }else{
            json_output(200, array('status'=>'fail', 'profileImage'=>"image-required" ));
          }
        }
      }
    }
  }
  
  /**
   * User Update Profile
   */
	public function update(){
		$method = $_SERVER['REQUEST_METHOD'];
    $headers = $this->input->request_headers();

		if($method !== 'POST'){
			json_output(400, array('status' => 400,'message' => 'Bad request.'));
		}else{
    
      $token = isset($headers['Authtoken']) ? $headers['Authtoken'] : $headers['authtoken'];
      $check_token_validity = Modules::run('security/validateAuthToken',$token);
    
      if($check_token_validity['status'] === "invalid"){
        json_output(200, array('status' => 'invalid token'));
      }else if($check_token_validity['status'] === "expired"){
        json_output(200, array('status' => 'expired'));
      }else if($check_token_validity['status'] === "valid"){
      
        $registration_id = $check_token_validity['registration_id'];
        $uid = $check_token_validity['uid'];
        $type = $check_token_validity['type'];
      
        $user_registration = $this->Mdl_api->retrieve('registration',array('registration_id'=>$registration_id, 'uid'=>$uid));
      
        $content = json_decode(file_get_contents('php://input'), TRUE);
        $this->form_validation->set_data($content);
        $custom_errors = array();
        
        $this->form_validation->set_rules("fullName","Full Name","trim|xss_clean|required",
        array(
          'required' => "user-fullName-required"
        ));
        
        $this->form_validation->set_rules("country","Country of Residence","trim|xss_clean|required",
        array(
          'required' => "user-country-required"
        ));

        $this->form_validation->set_rules("email","Email","trim|xss_clean|required|valid_email",
        array(
          'required' => "user-email-required",
          'valid_email' => "user-email-invalid"
        ));

        if($user_registration[0]->provider == 'platform'){
          $this->form_validation->set_rules("password","Password","trim|xss_clean|required",
          array(
            'required' => "user-password-required"
          ));

          if(isset($content['password'] ) && $content['password'] !== ""){
            $valid_password = $this->validPassword($content['password']);
            if($valid_password !== "valid"){
              $custom_errors['password'] = $valid_password;
            }
          }
        }

        $this->form_validation->set_rules("phoneCode","Phone Code","trim|xss_clean|required",
        array(
          'required' => "user-phonecode-required"
        ));

        $this->form_validation->set_rules("phoneNumber","Phone Number","trim|xss_clean|callback_mobile_intl_check");

        $this->form_validation->set_rules("contactPreference","Contact Preference","trim|xss_clean|required|in_list[email,phone]",
        array(
          'required' => "user-contactPreference-required",
          'in_list' => 'user-invalid-option'
        ));
        
        $this->form_validation->set_rules("gender","Gender","trim|xss_clean|required|in_list[male,female]",
        array(
          'required' => "user-gender-required",
          'in_list' => 'user-invalid-option'
        ));

        $this->form_validation->set_rules("dob","Date of Birth","trim|xss_clean|required",
        array(
          'required' => "user-select-dob"
        ));

        if($this->form_validation->run($this) == FALSE){
          $errors = $this->form_validation->error_array();
          $final_array = array_merge($errors,$custom_errors);
          json_output(200,array('status'=>'error','errorData'=>$final_array));
        }else{
        
          if(! empty($custom_errors)){
            json_output(200,array('status'=>'error','errorData'=>$custom_errors));
          }
        
          if($user_registration[0]->provider == 'platform' && $content['password'] !== $user_registration[0]->password_text){
            $password_text = strip_tags($content['password']);
            $password_enc = Modules::run('security/makeHash',$password_text);
          }else{
            $password_text = $user_registration[0]->password_text;
            $password_enc = $user_registration[0]->password_enc;
          }

          $updated_registration_data = array(
            "password_text" => $password_text,
            "password_enc" => $password_enc,
            "modified_date" => date("Y-m-d H:i:s")
          );
          $update_registration = $this->Mdl_api->update("registration",array('registration_id'=> $registration_id, 'uid'=>$uid), $updated_registration_data);
          
          $updated_user_data = array(
            "registration_id" => $registration_id,
            "fullname" => strip_tags($content['fullName']),
            "email" => strip_tags($content['email']),
            "country" => strip_tags($content['country']),
            "phone_code" => strip_tags($content['phoneCode']),
            "phone" => strip_tags($content['phoneNumber']),
            "birth_date" => date("Y-m-d",strtotime(strip_tags($content['dob']))),
            "contact_preference" => strip_tags($content['contactPreference']),
            "gender" => strip_tags($content['gender']),
            "profile_image" => strip_tags($content['profileImage']),
            "modified_date" => date("Y-m-d H:i:s")
          );
          $user_details_update = $this->Mdl_api->update("user_details",array('registration_id'=>$registration_id),$updated_user_data);
          
          json_output(200,array('status'=>'success'));
        }
      }
    }
  }
}
