<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH.'modules/generic/controllers/Generic.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST,GET,PUT,DELETE");
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authtoken');
header("Access-Control-Max-Age: 86000");

class Talent extends Generic{

  function __construct() {
    parent::__construct();
    $this->load->model('Mdl_api');		
	}

  /**
   * Callback : Indian Mobile
   */
	public function mobile_ind_check($mobile){
		if($mobile == ""){
			$this->form_validation->set_message('mobile_ind_check','talent-phoneNumber-invalid');
			return false;
		}else if(preg_match('/^[6-9][0-9]{9}$/', $mobile)) {
			return true;
		}else{
			$this->form_validation->set_message('mobile_ind_check','talent-phoneNumber-invalid');
			return false;
		}
  }
  
  /**
   * Callback : International Mobile
   */
	public function mobile_intl_check($mobile){
		if($mobile == ""){
			$this->form_validation->set_message('mobile_intl_check','talent-phoneNumber-invalid');
			return false;
		}else if(preg_match('/^[0-9]{7,15}$/', $mobile)) {
			return true;
		}else{
			$this->form_validation->set_message('mobile_intl_check','talent-phoneNumber-invalid');
			return false;
		}
  }

  /**
   * Callback : Email unique check
   */
	public function unique_email_check($email){
		if($email == ""){
			$this->form_validation->set_message('unique_email_check','talent-email-required');
			return false;
		}else{
      if($this->Mdl_api->isExist('registration',array('email'=>$email))){
        $this->form_validation->set_message('unique_email_check','talent-email-exist');
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
			return 'talent-password-required';
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
			return 'talent-password-alphanumeric';
		}
    
    if (preg_match_all($regex_special, $password) < 1){
			return 'talent-password-spl-char';
		}
    
    if (strlen($password) < 8){
			return 'talent-password-min-len';
		}
    
    return 'valid';
  }

  /**
   * Check Talent Slug
   */
  private function checkTalentSlug($slug){
    $final_str = '';
    if($slug == ""){
			return $final_str;
		}else{
      $records = $this->Mdl_api->countRecords('talent_details',array('slug'=>$slug));
      if($records !== "NA"){
        $final_str = $slug.'-'.($records+1);
        return $final_str;
      }else{
        return $slug;
      }
    }
  }

  /**
   * Talent Registration
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
        'required' => "talent-fullname-required"
      ));
    
      $this->form_validation->set_rules("stageName","Stage Name","trim|xss_clean|required",
      array(
        'required' => "talent-stageName-required"
      ));

      $this->form_validation->set_rules("country","Country of Residence","trim|xss_clean|required",
      array(
        'required' => "talent-country-required"
      ));
      
      $this->form_validation->set_rules("email","Email","trim|xss_clean|required|valid_email|callback_unique_email_check",
      array(
        'required' => "talent-email-required",
        'valid_email' => "talent-email-invalid"
      ));
      
      $this->form_validation->set_rules("password","Password","trim|xss_clean|required",
      array(
        'required' => "talent-password-required"
      ));
      
      if(isset($content['password'] ) && $content['password'] !== ""){
        $valid_password = $this->validPassword($content['password']);
        if($valid_password !== "valid"){
          $custom_errors['password'] = $valid_password;
        }
      }
      
      $this->form_validation->set_rules("confirmPassword","Confirm Password","trim|xss_clean|required|matches[password]",
      array(
        'required' => "talent-password-retype",
        'matches' => 'talent-password-match'
      ));
      
      $this->form_validation->set_rules("phoneCode","Phone Code","trim|xss_clean|required",
      array(
        'required' => "talent-phonecode-required"
      ));
      
      $this->form_validation->set_rules("phoneNumber","Phone Number","trim|xss_clean|callback_mobile_intl_check");
      
      $this->form_validation->set_rules("contactPreference","Contact Preference","trim|xss_clean|required|in_list[email,phone]",
      array(
        'required' => "talent-contactPreference-required",
        'in_list' => 'talent-invalid-option'
      ));
      
      $this->form_validation->set_rules("gender","Gender","trim|xss_clean|required|in_list[male,female]",
      array(
        'required' => "talent-gender-required",
        'in_list' => 'talent-invalid-option'
      ));
      
      if(isset($content['platforms_counter']) && $content['platforms_counter'] > 0){
        for($i=1; $i <= $content['platforms_counter']; $i++){
          $platform = 'platform_'.$i;
          $screen_name = 'screenName_'.$i;
          $followers = 'followers_'.$i;
          
          $this->form_validation->set_rules($platform,"Platform","trim|xss_clean|required",
          array(
            "required" => "talent-platform-required"
          ));

          $this->form_validation->set_rules($screen_name,"Screen Name","trim|xss_clean|required",
          array(
            "required" => "talent-screenName-required"
          ));

          $this->form_validation->set_rules($followers,"Followers","trim|xss_clean|required",
          array(
            "required" => "talent-followers-required"
          ));
        }
      }
      
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
      
        // $replace = array("");
        // $slug = str_replace(" ","-",trim($new_string));
        $mail_array = explode('@',$content['email']);
        $slug_str = strip_tags($mail_array['0']);
        $find = array("_",".");
        $replace = "-";
        $new_slug = str_replace($find,$replace,strtolower($slug_str));
        // $new_slug = preg_replace('/^\s+|\s+$|\s+(?=\s)/','',$new_slug);
        $slug = str_replace(" ","-",$new_slug);
        // $find = array("/","|","_","?","(",")","-",":","!","'",".",",","\r","\n","\r\n");
        // $replace = array(' ');
        // $new_slug = str_replace($find,$replace,strtolower($slug_str));
        // $new_slug = preg_replace('/^\s+|\s+$|\s+(?=\s)/','',$new_slug);
        // $slug = str_replace(" ","-",trim($new_slug));
        $check_slug = $this->checkTalentSlug($slug);
      
        $uid = $this->getUID();
      
        $registration_data = array(
          "type" => 'talent',
          "provider" => 'platform',
          "uid" => $uid,
          "email" => strip_tags($content['email']),
          "password_text" => $password_text,
          "password_enc" => $password_enc,
          "is_mail_verified" => "no",
          "application_status" => "pending",
          "account_status" => "deactive",
          "admin_id" => 0,
          "created_date" => date("Y-m-d H:i:s"),
          "modified_date" => date("Y-m-d H:i:s")
        );
        $registration_id = $this->Mdl_api->insert("registration",$registration_data);
        
        $talent_name_en = $talent_name_ar = $stage_name_en = $stage_name_ar = '';
        if($content['lang'] === "en"){
          $talent_name_en = strip_tags($content['fullName']);
          $stage_name_en =  strip_tags($content['stageName']);
        }else{
          $talent_name_ar = strip_tags($content['fullName']);
          $stage_name_ar =  strip_tags($content['stageName']);
        }
        
        $talent_data = array(
          "registration_id" => $registration_id,
          "lang" => strip_tags($content['lang']),
          "fullname_en" => $talent_name_en,
          "fullname_ar" => $talent_name_ar,
          "stage_name_en" => $stage_name_en,
          "stage_name_ar" => $stage_name_ar,
          "slug" => $check_slug,
          "email" => strip_tags($content['email']),
          "country" => strip_tags($content['country']),
          "phone_code" => strip_tags($content['phoneCode']),
          "phone" => strip_tags($content['phoneNumber']),
          "contact_preference" => strip_tags($content['contactPreference']),
          "gender" => strip_tags($content['gender']),
          "about_talent_en" => '',
          "about_talent_ar" => '',
          "profile_image" => '',
          "intro_video" => '',
          "halagram_price" => '',
          "platform_commision" => '',
          "talent_share" => '',
          "is_profile_updated" => 'no',
          "created_date" => date("Y-m-d H:i:s"),
          "modified_date" => date("Y-m-d H:i:s")
        );
        $talent_id = $this->Mdl_api->insert("talent_details",$talent_data);
        
        $social_media_array = array();
        if(isset($content['platforms_counter']) && $content['platforms_counter'] > 0){
          for($i=1; $i <= $content['platforms_counter']; $i++){
            $platform = 'platform_'.$i;
            $screen_name = 'screenName_'.$i;
            $followers = 'followers_'.$i;
            $temp = array(
              "registration_id" => $registration_id,
              "talent_id" => $talent_id,
              "platform_name" => strip_tags($content[$platform]),
              "screen_name" => strip_tags($content[$screen_name]),
              "followers" => strip_tags($content[$followers]),
              "created_date" => date("Y-m-d H:i:s"),
              "modified_date" => date("Y-m-d H:i:s")
            );
            array_push($social_media_array,$temp);
          }
          $insert_talent_social_media = $this->Mdl_api->insert_batch("talent_social_media_master",$social_media_array);
        }

        $verification_link = $this->global_variables['front_end_url'].'talent/verify/'.$uid;
        if($content['lang'] === "en"){
          $subject = "HalaGram - Verify your account";
        }else{
          $subject = "هلاجرام - عملية تحقق حسابك";
        }
        $email_view_file = 'email-verification-'.$content['lang'];
        
        /**
         * Verification Mail
         */
        $mail_data = array(
          'view_file' => 'talent/'.$email_view_file,
          'to' => strip_tags($content['email']),
          'cc' => '',
          'subject' => $subject,
          'isAttachment' => false,
          'verification_link' => $verification_link,
          "name" => strip_tags($content['fullName'])
        );
        
        if(Modules::run('email/mailer',$mail_data)){
          json_output(200,array('status'=>'success'));
        }else{
          json_output(200,array('status'=>'fail','message'=>'mail-failed'));
        }
      }
		}
  }

  /**
   * Talent Verification
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
        'type' => 'talent'
      );
      $registration = $this->Mdl_api->retrieve("registration",$search_data);
      
      $registrationId = $registration[0]->registration_id; 
      $get_talent_details = "SELECT is_profile_updated FROM talent_details WHERE registration_id='$registrationId' ";
      $talent_details = $this->Mdl_api->customQuery($get_talent_details);

      if($registration == "NA" || $talent_details == "NA" || $registration[0]->type == 'user'){
        json_output(200,array('status'=>'danger','message'=>'something-went-wrong'));
      }else if( ($registration[0]->is_mail_verified == "no" || $registration[0]->is_mail_verified == "yes") && $talent_details[0]->is_profile_updated == "no" ){

        $updated_registration_data = array(
          'is_mail_verified' => 'yes',
          'account_status' => 'active',
          'modified_date' => date("Y-m-d H:i:s")
        );
        $updated = $this->Mdl_api->update("registration",$search_data, $updated_registration_data);

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
        if($registration[0]->type == 'talent'){
          $get_details = 'SELECT fullname_en, fullname_ar, profile_image FROM talent_details WHERE registration_id='.$registration[0]->registration_id;
          $user_info = $this->Mdl_api->customQuery($get_details);
        } 

        if($user_info !== "NA"){
          if($registration[0]->type == 'talent'){
            $name_en = $user_info[0]->fullname_en;
            $name_ar = $user_info[0]->fullname_ar;
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

        json_output(200,array('status'=>'success','message'=>'talent-email-verified','user'=>$user));
      }else if($registration[0]->account_status == "deactive"){
        json_output(200,array('status'=>'danger','message'=>'account-deactive'));
      }else{
        json_output(200,array('status'=>'verified','message'=>'talent-email-verified'));
      }
		}
  }

  /**
   * Get Talent Details
   */
	public function getDetails(){
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
     
        $talent_registration = $this->Mdl_api->retrieve('registration',array('registration_id'=>$registration_id, 'uid'=>$uid));
     
        $talent_details = $this->Mdl_api->retrieve('talent_details',array('registration_id'=>$registration_id));
        
        $get_talent_categories = "SELECT c.category_id, c.category_name_en, c.category_name_ar FROM category_master c INNER JOIN talent_category_master t ON t.category_id=c.category_id WHERE t.registration_id='$registration_id'";
        $talent_category_master = $this->Mdl_api->customQuery($get_talent_categories);

        // $talent_category_master = $this->Mdl_api->retrieve('talent_category_master',array
        // ('registration_id'=>$registration_id));
        
        $talent_bank_details = $this->Mdl_api->retrieve('talent_bank_details',array('registration_id'=>$registration_id));
        
        if($talent_registration !== "NA" && $talent_details !== "NA"){
          $response = array(
            "isApproved" => $talent_registration[0]->application_status === "approved" ? true : false,
            "fullNameEn" => $talent_details[0]->fullname_en,
            "fullNameAr" => $talent_details[0]->fullname_ar,
            "stageNameEn" => $talent_details[0]->stage_name_en,
            "stageNameAr" => $talent_details[0]->stage_name_ar,
            "email" => $talent_details[0]->email,
            "password" => $talent_registration[0]->password_text,
            "country" => $talent_details[0]->country,
            "phoneCode" => $talent_details[0]->phone_code,
            "phoneNumber" => $talent_details[0]->phone,
            "contactPreference" => $talent_details[0]->contact_preference,
            "gender" => $talent_details[0]->gender,
            "aboutTalentEn" => $talent_details[0]->about_talent_en,
            "aboutTalentAr" => $talent_details[0]->about_talent_ar,
            "profileImage" => $talent_details[0]->profile_image,
            "welcomeVideo" => $talent_details[0]->intro_video,
            "halagramPrice" => $talent_details[0]->halagram_price,
            "charityCheck" => $talent_details[0]->charity_check
          );
         
          if($talent_category_master !== "NA"){
            $categories = [];
            foreach($talent_category_master as $category){
              $name = Modules::run('master/category/getNameById', $category->category_id);
              $categories[] = (object) array('label' => $name, 'value' => $category->category_id);
            }
            $response['categories'] = $categories;
            // $response['categories'] = $talent_category_master;
          }else{
            $response['categories'] = [];
          }
         
          if($talent_bank_details !== "NA"){
            $response['accountName'] = $talent_bank_details[0]->account_name;
            $response['accountNumber'] = $talent_bank_details[0]->account_number;
            $response['bankName'] = $talent_bank_details[0]->bank_name;
            $response['branchName'] = $talent_bank_details[0]->branch_name;
            $response['swiftCode'] = $talent_bank_details[0]->swift_code;
            $response['bankAddress'] = $talent_bank_details[0]->bank_address;
          }else{
            $response['accountName'] = '';
            $response['accountNumber'] = '';
            $response['bankName'] = '';
            $response['branchName'] = '';
            $response['swiftCode'] = '';
            $response['bankAddress'] = '';
          }
          
          json_output(200, array('status' => 'success','details' => $response));
        }else{
          json_output(200, array('status'=>'no data'));
        }
      }
		}
  }

  /**
   * Talent Upload Profile Image
   */
  public function uploadImage(){
    $method = $_SERVER['REQUEST_METHOD'];
    $headers = $this->input->request_headers();
    
    if($method !== 'POST'){
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
       
        $talent_details = $this->Mdl_api->retrieve('talent_details',array('registration_id'=>$registration_id));
       
        $custom_errors = array();
        $image_allowed_type = array('png','jpeg','jpg','PNG','JPEG','JPG');
       
        if($_FILES['profileImage']['name'] !== "" ){
          $filename = $_FILES['profileImage']['name'];
          $ext = pathinfo($filename, PATHINFO_EXTENSION);
          if(! in_array($ext, $image_allowed_type)){
            $custom_errors['profileImage'] = 'invalid-image-type';
          }else if($_FILES['profileImage']['size'] > 10000000){
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
            $imagename = 'image-'.strtotime('now').'.'.$ext;
						$key = $uid.'/'.$imagename;
            $upload_response = $this->addObject($key,'profileImage');
       
            if($upload_response['upload']){
              $old_profile_image = $talent_details[0]->profile_image;
              if($old_profile_image !== ''){
                $this->removeObject($old_profile_image);
              }
       
              $updated_talent_details = array(
                'profile_image' => $upload_response['path'],
                "modified_date" => date("Y-m-d H:i:s")
              );
       
              $update_profile_image = $this->Mdl_api->update("talent_details",array('registration_id' => $registration_id),$updated_talent_details);
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
   * Talent Upload Introduction Video
   */

  /*
  public function uploadVideo(){
    $method = $_SERVER['REQUEST_METHOD'];
    $headers = $this->input->request_headers();
    
    if($method !== 'POST'){
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
        
        $talent_details = $this->Mdl_api->retrieve('talent_details',array('registration_id'=>$registration_id));
        
        $custom_errors = array();
        $video_allowed_type = array('mp4','MP4');
        
        if($_FILES['welcomeVideo']['name'] !== "" ){
          $filename = $_FILES['welcomeVideo']['name'];
          $ext = pathinfo($filename, PATHINFO_EXTENSION);
          if(! in_array($ext, $video_allowed_type)){
            $custom_errors['welcomeVideo'] = 'Upload video of type mp4';
          }else if($_FILES['welcomeVideo']['size'] > 100000000){
            $custom_errors['welcomeVideo'] = 'File size should be less than 100 mb';
          }
        }else{
          $custom_errors['welcomeVideo'] = 'Welcome video is required';
        }
        
        if(! empty($custom_errors)){
          json_output(200,array('status'=>'error','errorData'=>$custom_errors));
        }else{
					if(! empty($_FILES['welcomeVideo']['name'])){
            $filename = $_FILES['welcomeVideo']['name'];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $videoname = 'video-'.strtotime('now').'.'.$ext;
            $key = $uid.'/'.$videoname;
            $upload_response = $this->addObject($key,'welcomeVideo');
            
            if($upload_response['upload']){
              $old_welcome_video = $talent_details[0]->intro_video;
              if($old_welcome_video !== ''){
                $this->removeObject($old_welcome_video);
              }

              $updated_talent_details = array(
                'intro_video' => $upload_response['path'],
                "modified_date" => date("Y-m-d H:i:s")
              );
              $update_profile_image = $this->Mdl_api->update("talent_details",array('registration_id' => $registration_id),$updated_talent_details);
              json_output(200, array('status'=>'success', 'welcomeVideo'=>$upload_response['path'] ));
            }else{
              json_output(200, array('status'=>'fail', 'welcomeVideo'=>"Upload failed" ));
            }
          }else{
            json_output(200, array('status'=>'fail', 'welcomeVideo'=>"Upload file" ));
          }
        }
      }
    }
  }
  */

  public function uploadVideo(){
    $method = $_SERVER['REQUEST_METHOD'];
    $headers = $this->input->request_headers();
    
    if($method !== 'POST'){
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
        
        $talent_details = $this->Mdl_api->retrieve('talent_details',array('registration_id'=>$registration_id));
        
        $custom_errors = array();
        $video_allowed_type = array('mp4','MP4');

        if($_FILES['welcomeVideo']['name'] !== "" ){
          $filename = $_FILES['welcomeVideo']['name'];
          $ext = pathinfo($filename, PATHINFO_EXTENSION);
          if(! in_array($ext, $video_allowed_type)){
            $custom_errors['welcomeVideo'] = 'video-type';
          }else if($_FILES['welcomeVideo']['size'] > 100000000){
            $custom_errors['welcomeVideo'] = 'video-file-size';
          }
        }else{
          $custom_errors['welcomeVideo'] = 'intro-video-required';
        }

        if(! empty($custom_errors)){
          json_output(200,array('status'=>'error','errorData'=>$custom_errors));
        }else{
					if(! empty($_FILES['welcomeVideo']['name'])){
            $filename = $_FILES['welcomeVideo']['name'];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $videoname = 'raw-'.$uid.'.'.$ext;
            $uploadPath = './assets/video/';
            $upload = $this->uploadFile($videoname,$uploadPath,'250','welcomeVideo');

            if($upload !== 1){
              json_output(200, array('status'=>'error', 'errorData'=> array('welcomeVideo'=>'upload-failed') ));
            }else{
              $raw_video = 'assets/video/'.$videoname;
              $final_video = 'assets/video/hala-'.$uid.'.mp4';
              $new_name = 'video-'.strtotime('now').'.'.$ext;
              $key = $uid.'/'.$new_name;
            
              $upload_response = $this->addWatermark($raw_video, $final_video, $key);
            
              if($upload_response['upload']){
                $old_welcome_video = $talent_details[0]->intro_video;
                if($old_welcome_video !== ''){
                  $this->removeObject($old_welcome_video);
                }
  
                $updated_talent_details = array(
                  'intro_video' => $upload_response['path'],
                  "modified_date" => date("Y-m-d H:i:s")
                );
                $update_profile_image = $this->Mdl_api->update("talent_details",array('registration_id' => $registration_id),$updated_talent_details);

                json_output(200, array('status'=>'success', 'welcomeVideo'=>$upload_response['path'] ));
              }else{
                json_output(200, array('status'=>'error', 'errorData'=> array('welcomeVideo'=>"upload-failed") ));
              }
            }
          }else{
            json_output(200, array('status'=>'error', 'errorData'=> array('welcomeVideo'=>"intro-video-required") ));
          }
        }
      }
    }
  }

  /**
   * Talent Update Profile
   */
	public function update(){
		$method = $_SERVER['REQUEST_METHOD'];
    $headers = $this->input->request_headers();

		if($method !== 'POST'){
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
    
        $talent_registration = $this->Mdl_api->retrieve('registration',array('registration_id'=>$registration_id, 'uid'=>$uid));
        
        $talent_details_query = "SELECT talent_id, is_profile_updated, platform_commision FROM talent_details WHERE registration_id='$registration_id' ";
        $talent_details = $this->Mdl_api->customQuery($talent_details_query);
        
        $talent_category_master = $this->Mdl_api->retrieve('talent_category_master',array('registration_id'=>$registration_id));
        
        $content = json_decode(file_get_contents('php://input'), TRUE);
        $this->form_validation->set_data($content);
        $custom_errors = array();

        $this->form_validation->set_rules("fullNameEn","Full Name (En)","trim|xss_clean|required",
        array(
          'required' => "talent-fullNameEn-required"
        ));
        
        $this->form_validation->set_rules("fullNameAr","Full Name (Ar)","trim|xss_clean|required",
        array(
          'required' => "talent-fullNameAr-required"
        ));
        
        $this->form_validation->set_rules("stageNameEn","Stage Name (En)","trim|xss_clean|required",
        array(
          'required' => "talent-stageNameEn-required"
        ));
        
        $this->form_validation->set_rules("stageNameAr","Stage Name (Ar)","trim|xss_clean|required",
        array(
          'required' => "talent-stageNameAr-required"
        ));
        
        $this->form_validation->set_rules("country","Country of Residence","trim|xss_clean|required",
        array(
          'required' => "talent-country-required"
        ));
        
        $this->form_validation->set_rules("password","Password","trim|xss_clean|required",
        array(
          'required' => "talent-password-required"
        ));
        
        $this->form_validation->set_rules("phoneCode","Phone Code","trim|xss_clean|required",
        array(
          'required' => "talent-phonecode-required"
        ));
        
        $this->form_validation->set_rules("phoneNumber","Phone Number","trim|xss_clean|callback_mobile_intl_check");
        
        $this->form_validation->set_rules("contactPreference","Contact Preference","trim|xss_clean|required|in_list[email,phone]",
        array(
          'required' => "talent-contactPreference-required",
          'in_list' => 'talent-invalid-option'
        ));
        
        $this->form_validation->set_rules("gender","Gender","trim|xss_clean|required|in_list[male,female]",
        array(
          'required' => "talent-gender-required",
          'in_list' => 'talent-invalid-option'
        ));
        
        $this->form_validation->set_rules("aboutTalentEn","Profile Description (En)","trim|xss_clean|required",
        array(
          'required' => "talent-aboutMeEn-required"
        ));
        
        if($content['aboutTalentEn'] !== ""){
          $aboutTalentEnLen =  strlen($content['aboutTalentEn']); 
          if($aboutTalentEnLen > 350){
            $custom_errors['aboutTalentEn'] = 'talent-about-maxlength';
          }
        }

        $this->form_validation->set_rules("aboutTalentAr","Profile Description (Ar)","trim|xss_clean|required",
        array(
          'required' => "talent-aboutMeAr-required"
        ));
        
        if($content['aboutTalentAr'] !== ""){
          $aboutTalentArLen =  mb_strlen($content['aboutTalentAr'],'UTF-8'); 
          if($aboutTalentArLen > 350){
            $custom_errors['aboutTalentAr'] = 'talent-about-maxlength';
          }
        }

        $this->form_validation->set_rules("halagramPrice","Halagram Price","trim|xss_clean|required",
        array(
          'required' => "talent-price-required"
        ));
        
        $this->form_validation->set_rules("charityCheck","Charity Check","trim|xss_clean");
        
        $this->form_validation->set_rules("accountName","Account Name","trim|xss_clean|required",
        array(
          'required' => "talent-accountName-required"
        ));
        
        $this->form_validation->set_rules("accountNumber","Account Number","trim|xss_clean|required",
        array(
          'required' => "talent-accountNumber-required"
        ));
        
        $this->form_validation->set_rules("bankName","Bank Name","trim|xss_clean|required",
        array(
          'required' => "talent-bankName-required"
        ));
        
        $this->form_validation->set_rules("branchName","Branch Name","trim|xss_clean|required",
        array(
          'required' => "talent-branchName-required"
        ));
        
        $this->form_validation->set_rules("swiftCode","Swift code","trim|xss_clean|required",
        array(
          'required' => "talent-swift-code-required"
        ));
        
        $this->form_validation->set_rules("bankAddress","Bank Address","trim|xss_clean|required",
        array(
          'required' => "talent-bankAddress-required"
        ));
        
        if(isset($content['categories']) && empty($content['categories'])){
          $custom_errors['categories'] = 'select-category';
        }
        
        if($this->form_validation->run($this) == FALSE){
          $errors = $this->form_validation->error_array();
          $final_array = array_merge($errors,$custom_errors);
          json_output(200,array('status'=>'error','errorData'=>$final_array));
        }else{
          
          if(!empty($custom_errors)){
            json_output(200,array('status'=>'error','errorData'=>$custom_errors));
          }else{

            if($content['password'] !== $talent_registration[0]->password_text){
              $password_text = strip_tags($content['password']);
              $password_enc = Modules::run('security/makeHash',$password_text);
            }else{
              $password_text = $talent_registration[0]->password_text;
              $password_enc = $talent_registration[0]->password_enc;
            }
          
            $registration_data = array(
              "password_text" => $password_text,
              "password_enc" => $password_enc,
              "modified_date" => date("Y-m-d H:i:s")
            );
            $update_registration = $this->Mdl_api->update("registration",array('registration_id'=> $registration_id, 'uid'=>$uid), $registration_data);
          
            $platformCommision = $talentShare = '';
            if($talent_details[0]->platform_commision !== ''){
              $platformCommision = (float)$talent_details[0]->platform_commision;
              $halagramPrice = (float)$content['halagramPrice'];
              $plaformShare = round( ($halagramPrice * $platformCommision) /100 );
              $talentShare = round( $halagramPrice - $plaformShare );
            }

            $talent_data = array(
              "fullname_en" => strip_tags($content['fullNameEn']),
              "fullname_ar" => strip_tags($content['fullNameAr']),
              "stage_name_en" => strip_tags($content['stageNameEn']),
              "stage_name_ar" => strip_tags($content['stageNameAr']),
              "country" => strip_tags($content['country']),
              "phone_code" => strip_tags($content['phoneCode']),
              "phone" => strip_tags($content['phoneNumber']),
              "contact_preference" => strip_tags($content['contactPreference']),
              "gender" => strip_tags($content['gender']),
              "about_talent_en" => $content['aboutTalentEn'],
              "about_talent_ar" => $content['aboutTalentAr'],
              "profile_image" => strip_tags($content['profileImage']),
              "intro_video" => strip_tags($content['welcomeVideo']),
              "halagram_price" => strip_tags($content['halagramPrice']),
              "charity_check" => strip_tags($content['charityCheck']),
              "platform_commision" => $platformCommision,
              "talent_share" => $talentShare,
              "is_profile_updated" => 'yes',
              "modified_date" => date("Y-m-d H:i:s")
            );
            $talent_details_update = $this->Mdl_api->update("talent_details",array('registration_id'=>$registration_id),$talent_data);
           
            if($talent_category_master !== "NA"){
              foreach($talent_category_master as $exhCategory){
                $found = false;
                foreach($content['categories'] as $newCategory){
                  if($newCategory['value'] == $exhCategory->category_id){
                    $found = true;
                    break;
                  }
                }
                if(! $found){
                  $this->Mdl_api->delete("talent_category_master",array('registration_id'=>$registration_id, 'category_id'=>$exhCategory->category_id));
                }
              }
            }
  
            foreach($content['categories'] as $category){
              if( ! $this->Mdl_api->isExist("talent_category_master", array('registration_id'=>$registration_id, 'category_id'=>$category['value'])) ){
                $category_array = array(
                  "registration_id" => $registration_id,
                  "talent_id" => $talent_details[0]->talent_id,
                  "category_id" => strip_tags($category['value']),
                  "created_date" => date("Y-m-d H:i:s")
                );
                $insert_talent_category = $this->Mdl_api->insert("talent_category_master",$category_array);
              }
            }
  
            $talent_bank_details = array(
              "account_name" => strip_tags($content['accountName']),
              "account_number" => strip_tags($content['accountNumber']),
              "bank_name" => strip_tags($content['bankName']),
              "branch_name" => strip_tags($content['branchName']),
              "swift_code" => strip_tags($content['swiftCode']),
              "bank_address" => strip_tags($content['bankAddress']),
              "modified_date" => date("Y-m-d H:i:s")
            );
  
            if( $this->Mdl_api->isExist("talent_bank_details", array('registration_id'=>$registration_id)) ){
              $talent_bank_update = $this->Mdl_api->update("talent_bank_details",array('registration_id'=>$registration_id),$talent_bank_details);
            }else{
              $talent_bank_details['registration_id'] = $registration_id;
              $talent_bank_details['created_date'] = date("Y-m-d H:i:s");
              $talent_bank_insert = $this->Mdl_api->insert("talent_bank_details",$talent_bank_details);
            }
            
            $message = 'profile-updated';
            $text = '';
            if($talent_details[0]->is_profile_updated == 'no'){
              $message = 'talent-profile-success';
              $text = 'profile-verification';

              $mail_data = array(
                'view_file' => 'admin/talent-profile-update',
                'to' => 'support@halagram.me',
                'subject' => 'Talent Profile Completion',
                'isAttachment' => false,
                "name" => strip_tags($content['fullNameEn'])
              );
              Modules::run('email/mailer',$mail_data);  
            }

            json_output(200,array('status'=>'success', 'message'=>$message, 'text'=>$text)); 
          }
        }
      }
    }
  }

  /**
   * Get Talent Search Results
   */
  public function search(){
    $method = $_SERVER['REQUEST_METHOD'];
    $headers = $this->input->request_headers();

		if($method !== 'GET'){
			json_output(400, array('status' => 400,'message' => 'Bad request.'));
		}else{
      $content = $_GET;
      $serach = $content['serach'];
      
      $GET_TALENTS = "SELECT t.talent_id, t.registration_id, t.fullname_en, t.fullname_ar, t.slug, t.profile_image, t.halagram_price FROM talent_details t INNER JOIN registration r ON r.registration_id = t.registration_id WHERE r.application_status='approved' AND r.account_status='active' AND LOWER(t.fullname_en) LIKE LOWER('%$serach%') OR LOWER(t.fullname_ar) LIKE LOWER('%$serach%')";
      $talents = $this->Mdl_api->customQuery($GET_TALENTS);
      
      if($talents !== "NA"){
        $response = array();
        foreach($talents as $val){
          $temp = array();
          $registratonId = $val->registration_id;
          $get_category_list = "SELECT category_name_en, category_name_ar FROM category_master WHERE category_id IN ( SELECT category_id FROM talent_category_master WHERE registration_id=$registratonId )";
          $category_list = $this->Mdl_api->customQuery($get_category_list);

          $temp['nameEn'] = $val->fullname_en;
          $temp['nameAr'] = $val->fullname_ar;
          $temp['image'] = $val->profile_image;
          $temp['slug'] = $val->slug;
          $temp['price'] = $val->halagram_price;
          $temp['cateogries'] = $category_list;
          $response[] = $temp;
        }

        json_output(200, array("status"=>"success","records"=>$response));
      }else{
        json_output(200, array("status"=>"no data"));
      }
    }
  }

  /**
   * Get Talent Listing
   */
  public function page(){
    $method = $_SERVER['REQUEST_METHOD'];
    $headers = $this->input->request_headers();

		if($method !== 'GET'){
			json_output(400, array('status' => 400,'message' => 'Bad request.'));
		}else{
    
      $content = json_decode(file_get_contents('php://input'), TRUE);
      $content = $_GET;
    
      $records_per_page = 10;
      $from = $content['lastRecord'];
      $category_slug = $content['category'];
      $lang = $content['lang'];
    
      if($content['filter'] !== ''){
        if($lang == 'ar'){
          switch($content['filter']){
            case 1 : $filter = array("أ","ب","ت","ث","ج","ح","خ"); break;
            case 2 : $filter = array("د","ذ","ر","ز","س","ش",); break;
            case 3 : $filter = array("ص","ض","ط","ظ","ع","غ"); break;
            case 4 : $filter = array("ف","ق","ك","ل","م"); break;
            case 5 : $filter = array("ن","ه","و","ي"); break;
            default : $filter = array();
          }
        }else{
          switch($content['filter']){
            case 1 : $filter = array("A","B","C","D","E","F","a","b","c","d","e","f"); break;
            case 2 : $filter = array("G","H","I","J","K","g","h","i","j","k"); break;
            case 3 : $filter = array("L","M","N","O","P","l","m","n","o","p"); break;
            case 4 : $filter = array("Q","R","S","T","U","q","r","s","t","u"); break;
            case 5 : $filter = array("V","W","X","Y","Z","v","w","x","y","z"); break;
            default : $filter = array();
          }
        }
      }else{
        $filter = array();  
      }

      $get_category_ids = $this->getCategoryIds($category_slug);
      if($get_category_ids == ''){
        $categories = array();
      }else{
        $categories = explode(",",$get_category_ids);
      }
      $talent = $this->Mdl_api->getTalentByFilter($lang, $filter, $categories, $from, $records_per_page);
      $total_records = $this->Mdl_api->totalTalentRecords($lang, $filter, $categories);

      if($talent !== "NA"){
        json_output(200, array("status"=>"success","talent"=>$talent, "total_records"=>$total_records));
      }else{
        json_output(200, array("status"=>"no data"));
      }
    }
  }

  public function getCategoryIds($category){
    $ids = '';
    $category = $this->Mdl_api->retrieve('category_master',array('slug'=>$category,'status'=>'active'));

    if($category !== "NA"){
      $ids .= $category[0]->category_id;
      if($category[0]->is_parent == "yes"){
        $subcategories = $this->Mdl_api->retrieve('category_master',array('parent_id'=>$category[0]->category_id,'status'=>'active'));
        if($subcategories !== "NA"){
          foreach($subcategories as $subcat){
            $ids .= ', '.$subcat->category_id;
          }
        }
      }
    }
    return $ids;
  }

  /**
   * User wishlist 
   */
  public function wishlist(){
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
      
        $content = json_decode(file_get_contents('php://input'), TRUE);
        $content = $_GET;
      
        $records_per_page = 10;
        $from = $content['lastRecord'];
        $user_details = $this->Mdl_api->retrieve('user_details',array('registration_id'=>$registration_id));
      
        if($content['filter'] !== ''){
          switch($content['filter']){
            case 1 : $filter = array("A","B","C","D","E","F","a","b","c","d","e","f"); break;
            case 2 : $filter = array("G","H","I","J","K","g","h","i","j","k"); break;
            case 3 : $filter = array("L","M","N","O","P","l","m","n","o","p"); break;
            case 4 : $filter = array("Q","R","S","T","U","q","r","s","t","u"); break;
            case 5 : $filter = array("V","W","X","Y","Z","v","w","x","y","z"); break;
            default : $filter = array();
          }
        }else{
          $filter = array();  
        }
        
        $talent = $this->Mdl_api->getWishlist($filter, $registration_id, $uid, $type, $from, $records_per_page);
        $total_records = $this->Mdl_api->totalWishlistRecords($filter, $registration_id, $uid, $type);
        
        if($talent !== "NA"){
          json_output(200, array("status"=>"success","talent"=>$talent, "total_records"=>$total_records));
        }else{
          json_output(200, array("status"=>"no data"));
        }
      }
    }
  }

  /**
   * Get Talent Listing by category ( for home page slider )
   */
  public function filterByCategory(){
    $method = $_SERVER['REQUEST_METHOD'];
    $headers = $this->input->request_headers();
    
    if($method !== 'GET'){
			json_output(400, array('status' => 400,'message' => 'Bad request.'));
		}else{
    
      // if(isset($headers['Authtoken'])){
      //   $token = $headers['Authtoken'];
      // }else{
      //   $token = $headers['authtoken'];
      // }
      // $check_token_validity = Modules::run('security/validateAuthToken',$token);
      
      $content = json_decode(file_get_contents('php://input'), TRUE);
      $content = $_GET;
      
      $category_slug = $content['category'];
      $get_category_ids = $this->getCategoryIds($category_slug);
      
      if($get_category_ids == ''){
        json_output(200, array("status"=>"no data"));
      }else{
        $categories = explode(",",$get_category_ids);
        $talents = $this->Mdl_api->getTalentByFilter('', array(), $categories, 0, 15);
        if($talents !== "NA"){
          json_output(200, array("status"=>"success","talent"=>$talents));
        }else{
          json_output(200, array("status"=>"no data"));
        }
      }
    }
  }

  /**
   * Get Talent Profile Details
   */
	public function getProfileDetails(){
		$method = $_SERVER['REQUEST_METHOD'];
    $headers = $this->input->request_headers();
    
		if($method !== 'GET'){
			json_output(400, array('status' => 400,'message' => 'Bad request.'));
		}else{

      $is_auth = false;
      if(isset($headers['Authtoken'])){
        $token = $headers['Authtoken'];
        $is_auth = true;
      }else if(isset($headers['authtoken'])){
        $token = $headers['authtoken'];
        $is_auth = true;
      }

      if($is_auth){
        $registration_id = $uid = '';
        $check_token_validity = Modules::run('security/validateAuthToken',$token);
        if($check_token_validity['status'] === "valid"){
          $registration_id = $check_token_validity['registration_id'];
          $uid = $check_token_validity['uid'];
        }
      }
      
      $content = json_decode(file_get_contents('php://input'), TRUE);
      $content = $_GET;
      $talent_slug = $content['slug'];
      
      $talent_details_query = "SELECT r.uid, t.talent_id, t.registration_id, t.fullname_en, t.fullname_ar, t.about_talent_en, t.about_talent_ar, t.profile_image, t.intro_video, t.halagram_price FROM talent_details t INNER JOIN registration r ON r.registration_id = t.registration_id WHERE t.slug='$talent_slug' ";
      $talent_details = $this->Mdl_api->customQuery($talent_details_query);
      
      if($talent_details !== "NA"){
        $registrationId = $talent_details[0]->registration_id;
        $get_category_list = "SELECT category_name_en, category_name_ar FROM category_master WHERE category_id IN ( SELECT category_id FROM talent_category_master WHERE registration_id=$registrationId )";
        $category_list = $this->Mdl_api->customQuery($get_category_list);
        
        $my_profile = false;
        if($is_auth){
          $my_profile = $talent_details[0]->uid == $uid && $talent_details[0]->registration_id == $registration_id ? true : false; 
        } 
        
        // $get_rating_average = 'SELECT AVG(rating), COUNT(review_id) AS total FROM talent_review WHERE talent_id=\''.$talent_details[0]->talent_id.'\'';
        // $rating = $this->Mdl_api->customQuery($get_rating_average);
        
        // $average = $rating[0]->averageRating !== null ? (float)$rating[0]->averageRating : 0;
        // $total = $rating[0]->total;

        $talentId = $talent_details[0]->talent_id;
        $get_rating = "SELECT rating FROM talent_review WHERE talent_id='$talentId' ";
        $rating = $this->Mdl_api->customQuery($get_rating);
        
        if($rating !== "NA"){
          $total_stars = 0;
          foreach($rating as $val){
            $total_stars += $val->rating;
          }
          $average = $total_stars / sizeof($rating);
          $total = sizeof($rating);
        }else{
          $average = 0;
          $total = 0;
        }
        
        $get_halagram = "SELECT service_type, occasion, halagram_link FROM request WHERE talent_id='$talentId' AND share_halagram='yes' AND share_halagram_talent='yes' AND request_status='complete' ORDER BY created_date DESC LIMIT 8";
        $halagramVideos = $this->Mdl_api->customQuery($get_halagram);

        if($halagramVideos !== "NA"){
          $response = array();
          foreach($halagramVideos as $val){
  
            $occasionEn = $occasionAr = '';
            if($val->occasion !== '' && $val->service_type == 'shout-out'){
              $GET_OCCASION = "SELECT occasion_name_en, occasion_name_ar FROM occasion_master WHERE occasion_id='$val->occasion' ";
              $occasion = $this->Mdl_api->customQuery($GET_OCCASION);
              if($occasion !== "NA"){
                $occasionEn = $occasion[0]->occasion_name_en;
                $occasionAr = $occasion[0]->occasion_name_ar;
              }
            }
  
            $temp = array();
            $temp['occasionEn'] = $occasionEn;
            $temp['occasionAr'] = $occasionAr;
            $temp['halagramLink'] = $val->halagram_link;
            $response[] = $temp;
          }

          $halagram = $response;
        }else{
          $halagram = 'No Records';
        }

        $get_reactions = "SELECT service_type, occasion, reaction_link FROM request WHERE talent_id='$talentId' AND request_status='complete' AND reaction_link != '' AND share_reaction='yes' ORDER BY created_date DESC LIMIT 8";
        $reactionVideos = $this->Mdl_api->customQuery($get_reactions);

        if($reactionVideos !== "NA"){
          $response = array();
          foreach($reactionVideos as $val){

            $occasionEn = $occasionAr = '';
            if($val->occasion !== '' && $val->service_type == 'shout-out'){
              $GET_OCCASION = "SELECT occasion_name_en, occasion_name_ar FROM occasion_master WHERE occasion_id='$val->occasion'";
              $occasion = $this->Mdl_api->customQuery($GET_OCCASION);
              if($occasion !== "NA"){
                $occasionEn = $occasion[0]->occasion_name_en;
                $occasionAr = $occasion[0]->occasion_name_ar;
              }
            }

            $temp = array();
            $temp['occasionEn'] = $occasionEn;
            $temp['occasionAr'] = $occasionAr;
            $temp['reactionLink'] = $val->reaction_link;
            $response[] = $temp;
          }

          $reaction = $response;
        }else{
          $reaction = 'No Records';
        }

        $profile = array(
          'uid' =>  $talent_details[0]->uid,
          'talent_id' =>  $talent_details[0]->talent_id,
          'average_rating' => $average,
          'total_review' => $total,
          'fullname_en' =>  $talent_details[0]->fullname_en,
          'fullname_ar' =>  $talent_details[0]->fullname_ar,
          'about_talent_en' =>  $talent_details[0]->about_talent_en,
          'about_talent_ar' =>  $talent_details[0]->about_talent_ar,
          'profile_image' =>  $talent_details[0]->profile_image,
          'intro_video' =>  $talent_details[0]->intro_video,
          'halagram_price' =>  $talent_details[0]->halagram_price,
          'categories' => $category_list,
          'my_profile' => $my_profile,
          'slug' => $talent_slug,
          'halagrams' => $halagram,
          'reactions' => $reaction
        );
        json_output(200, array('status' => 'success', 'profile' => $profile));

      }else{
        json_output(200, array('status'=>'no data'));
      }
		}
  }

  /**
  **  Get Talent Halagram
  */
  public function halagram(){
    $method = $_SERVER['REQUEST_METHOD'];
    $headers = $this->input->request_headers();

		if($method !== 'GET'){
			json_output(400, array('status' => 400,'message' => 'Bad request.'));
		}else{
      
      $content = $_GET;
      $talent_slug = $content['slug'];

      $talent_details_query = "SELECT t.talent_id, t.fullname_en, t.fullname_ar FROM talent_details t INNER JOIN registration r ON r.registration_id = t.registration_id WHERE t.slug='$talent_slug' ";
      $talent_details = $this->Mdl_api->customQuery($talent_details_query);

      if($talent_details !== "NA"){
        $talentId = $talent_details[0]->talent_id;
        $get_halagram = "SELECT service_type, occasion, halagram_link FROM request WHERE talent_id='$talentId' AND share_halagram='yes' AND share_halagram_talent='yes' AND request_status='complete' ORDER BY created_date DESC";
        $halagramVideos = $this->Mdl_api->customQuery($get_halagram);

        $halagrams = 'No Records';
        if($halagramVideos !== "NA"){
          $response = array();
          foreach($halagramVideos as $val){
  
            $occasionEn = $occasionAr = '';
            if($val->occasion !== '' && $val->service_type == 'shout-out'){
              $GET_OCCASION = 'SELECT occasion_name_en, occasion_name_ar FROM occasion_master WHERE occasion_id=\''.$val->occasion.'\'';
              $occasion = $this->Mdl_api->customQuery($GET_OCCASION);
              if($occasion !== "NA"){
                $occasionEn = $occasion[0]->occasion_name_en;
                $occasionAr = $occasion[0]->occasion_name_ar;
              }
            }
  
            $temp = array();
            $temp['occasionEn'] = $occasionEn;
            $temp['occasionAr'] = $occasionAr;
            $temp['halagramLink'] = $val->halagram_link;
            $response[] = $temp;
          }
          $halagrams = $response;
        }

        $profile = array(
          'fullname_en' =>  $talent_details[0]->fullname_en,
          'fullname_ar' =>  $talent_details[0]->fullname_ar,
          'halagrams' => $halagrams
        );
        json_output(200, array('status' => 'success', 'records' => $profile));
      }else{
        json_output(200, array('status'=>'no data'));
      }
    }
  }

  /**
  **  Get Talent Reaction
  */
  public function reaction(){
    $method = $_SERVER['REQUEST_METHOD'];
    $headers = $this->input->request_headers();

		if($method !== 'GET'){
			json_output(400, array('status' => 400,'message' => 'Bad request.'));
		}else{
      $content = $_GET;
      $talent_slug = $content['slug'];

      $talent_details_query = "SELECT t.talent_id, t.fullname_en, t.fullname_ar FROM talent_details t INNER JOIN registration r ON r.registration_id = t.registration_id WHERE t.slug='$talent_slug' ";
      $talent_details = $this->Mdl_api->customQuery($talent_details_query);
      
      if($talent_details !== "NA"){
        $talentId = $talent_details[0]->talent_id;

        $get_reactions = "SELECT service_type, occasion, reaction_link FROM request WHERE talent_id='$talentId' AND request_status='complete' AND reaction_link != '' AND share_reaction='yes' ORDER BY created_date DESC";
        $reactionVideos = $this->Mdl_api->customQuery($get_reactions);

        $reactions = 'No Records';
        if($reactionVideos !== "NA"){
          $response = array();
          foreach($reactionVideos as $val){

            $occasionEn = $occasionAr = '';
            if($val->occasion !== '' && $val->service_type == 'shout-out'){
              $GET_OCCASION = "SELECT occasion_name_en, occasion_name_ar FROM occasion_master WHERE occasion_id='$val->occasion' ";
              $occasion = $this->Mdl_api->customQuery($GET_OCCASION);
              if($occasion !== "NA"){
                $occasionEn = $occasion[0]->occasion_name_en;
                $occasionAr = $occasion[0]->occasion_name_ar;
              }
            }
  
            $temp = array();
            $temp['occasionEn'] = $occasionEn;
            $temp['occasionAr'] = $occasionAr;
            $temp['reactionLink'] = $val->reaction_link;
            $response[] = $temp;
          }
          $reactions = $response;
        }

        $profile = array(
          'fullname_en' =>  $talent_details[0]->fullname_en,
          'fullname_ar' =>  $talent_details[0]->fullname_ar,
          'reactions' => $reactions
        );
        json_output(200, array('status' => 'success', 'records' => $profile));
      }else{
        json_output(200, array('status'=>'no data'));
      }
    }
  }

  public function counts(){
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
    
        $registration = $this->Mdl_api->retrieve('registration',array('registration_id'=>$registration_id));
        $isApproved = false;
        $requestCount = 0;

        if($registration !== "NA"){
          $isApproved = $registration[0]->application_status == 'approved' ? true : false;
          $talentId = $this->getTalentId($registration_id);

          $currTime = date("Y-m-d H:i:s");
          $GET_REQUESTS = "SELECT request_id FROM request WHERE talent_id='$talentId' AND request_status='confirmed' AND expiry_date > '$currTime' ";
          $request = $this->Mdl_api->customQuery($GET_REQUESTS);
 
          if($request !== "NA"){
            $requestCount = sizeof($request);
          }
        }
        
        json_output(200, array("status"=>"success", "requestCount"=>$requestCount, "isApproved"=>$isApproved, 'result'=>$request ));       
      }
    }
  }

  /***************************************************************/
  /*                       DASHBOARD API                         */
  /***************************************************************/

  /**
  **  Get Talent Counters 
  */
  public function counter(){
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
        // $uid = $check_token_validity['uid'];
        // $type = $check_token_validity['type'];
    
        $talentId = $this->getTalentId($registration_id);

        $content = $_GET;
        $dateFrom = $content['dateFrom'];
        $dateTo = $content['dateTo'];
        $occasion = $content['occasion'];
        $serviceType = $content['serviceType'];
    
        $GET_VISITOR_COUNTER = "SELECT COUNT(visitor_id) AS total_visits FROM talent_profile_visits WHERE type IN ('user','talent') AND talent_id='$talentId' ";

        $GET_REQUESTS = "SELECT talent_share, request_status FROM request WHERE talent_id='$talentId' AND request_status NOT IN ('created','failed','expired','canceled') ";

        $newRequest = 0;
        $currTime = date("Y-m-d H:i:s");
        $GET_NEW_REQUESTS = "SELECT request_id FROM request WHERE talent_id='$talentId' AND request_status='confirmed' AND expiry_date > '$currTime' ";
        $new_request = $this->Mdl_api->customQuery($GET_NEW_REQUESTS);
 
        if($new_request !== "NA"){
          $newRequest = sizeof($new_request);
        }

        if($occasion !== ''){
          $GET_REQUESTS .= " AND occasion='$occasion' ";
        }

        if($serviceType !== ''){
          $GET_REQUESTS .= " AND service_type='$serviceType' ";
        }

        if($dateFrom !== ''){
          $date_from = date('Y-m-d H:i:s',strtotime($dateFrom.' 00:00:00'));
          $GET_REQUESTS .= " AND created_date >= '$date_from' ";
          $GET_VISITOR_COUNTER .= " AND created_date >= '$date_from' ";
        }

        if($dateTo !== ''){
          $date_to = date('Y-m-d H:i:s',strtotime($dateTo.' 23:59:59'));
          $GET_REQUESTS .= " AND created_date <= '$date_to' ";
          $GET_VISITOR_COUNTER .= " AND created_date <= '$date_to' ";
        }
   
        $request = $this->Mdl_api->customQuery($GET_REQUESTS);
        $visitors = $this->Mdl_api->customQuery($GET_VISITOR_COUNTER);

        // $newRequest = 0;
        $totalVisitors = 0;
        $totalRequest = 0;
        $totalEarning = 0;
        $completedRequest = 0;
        $conversionRate = 0;
        $completionRate = 0;
        
        if($request !== "NA"){
          foreach($request as $val){
            if($val->request_status == 'confirmed'){
              // $newRequest += 1;
              $totalRequest += 1;  
            }else if($val->request_status == 'complete'){
              $totalRequest += 1;
              $completedRequest += 1;
              $totalEarning += $val->talent_share;
            }
          }
        }

        if($visitors !== "NA"){
          $totalVisitors = $visitors[0]->total_visits;
        }

        if($totalRequest > 0 && $totalVisitors > 0){
          $conversionRate = ($totalRequest / $totalVisitors) * 100;
        }
        
        if($completedRequest > 0 && $totalRequest > 0){
          $completionRate = ($completedRequest / $totalRequest) * 100;
        }
        
        json_output(200, array("status"=>"success", "newRequest"=>$newRequest, "totalVisitors"=>$totalVisitors,"totalRequest"=>$totalRequest, "totalEarning"=>$totalEarning, "conversionRate"=>$conversionRate, "completionRate"=>$completionRate ));       
      }
    }
  }

  /**
  **  Get Visitor Doughnut Data 
  */
  public function visitorData(){
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
        $talentId = $this->getTalentId($registration_id);

        $content = $_GET;
        $dateFrom = $content['dateFrom'];
        $dateTo = $content['dateTo'];
        
        $GET_MALE_COUNTER = "SELECT COUNT(visitor_id) AS total_male FROM talent_profile_visits WHERE type IN ('user','talent') AND talent_id='$talentId' AND gender='male' ";
        
        $GET_FEMALE_COUNTER = "SELECT COUNT(visitor_id) AS total_female FROM talent_profile_visits WHERE type IN ('user','talent') AND talent_id='$talentId' AND gender='female' ";

        $GET_AGE_GROUP_A = "SELECT COUNT(visitor_id) AS group_a FROM talent_profile_visits WHERE type IN ('user','talent') AND talent_id='$talentId' AND  age >= '0' AND age <= '30' ";
        $GET_AGE_GROUP_B = "SELECT COUNT(visitor_id) AS group_b FROM talent_profile_visits WHERE type IN ('user','talent') AND talent_id='$talentId' AND  age >= '31' AND age <= '60' ";
        $GET_AGE_GROUP_C = "SELECT COUNT(visitor_id) AS group_c FROM talent_profile_visits WHERE type IN ('user','talent') AND talent_id='$talentId' AND  age >= '61' ";

        $GET_COUNTRY_DATA = "SELECT country, COUNT(visitor_id) AS total FROM talent_profile_visits WHERE type IN ('user','talent') AND talent_id='$talentId' ";

        if($dateFrom !== ''){
          $date_from = date('Y-m-d H:i:s',strtotime($dateFrom.' 00:00:00'));
          $GET_MALE_COUNTER .= " AND created_date >= '$date_from' ";
          $GET_FEMALE_COUNTER .= " AND created_date >= '$date_from' ";
          $GET_AGE_GROUP_A .= " AND created_date >= '$date_from' ";
          $GET_AGE_GROUP_B .= " AND created_date >= '$date_from' ";
          $GET_AGE_GROUP_C .= " AND created_date >= '$date_from' ";
          $GET_COUNTRY_DATA .= " AND created_date >= '$date_from' ";
        }

        if($dateTo !== ''){
          $date_to = date('Y-m-d H:i:s',strtotime($dateTo.' 23:59:59'));
          $GET_MALE_COUNTER .= " AND created_date <= '$date_to' ";
          $GET_FEMALE_COUNTER .= " AND created_date <= '$date_to' ";
          $GET_AGE_GROUP_A .= " AND created_date <= '$date_to' ";
          $GET_AGE_GROUP_B .= " AND created_date <= '$date_to' ";
          $GET_AGE_GROUP_C .= " AND created_date <= '$date_to' ";
          $GET_COUNTRY_DATA .= " AND created_date <= '$date_to' ";
        }
   
        $GET_COUNTRY_DATA .= " GROUP BY country ORDER BY total desc limit 4";

        $visitorAsMale = $this->Mdl_api->customQuery($GET_MALE_COUNTER);
        $visitorAsFemale = $this->Mdl_api->customQuery($GET_FEMALE_COUNTER);
        $ageA = $this->Mdl_api->customQuery($GET_AGE_GROUP_A);
        $ageB = $this->Mdl_api->customQuery($GET_AGE_GROUP_B);
        $ageC = $this->Mdl_api->customQuery($GET_AGE_GROUP_C);
        $countryData = $this->Mdl_api->customQuery($GET_COUNTRY_DATA);
        
        $male = $visitorAsMale !== "NA" ? $visitorAsMale[0]->total_male : 0;
        $female = $visitorAsFemale !== "NA" ? $visitorAsFemale[0]->total_female : 0;
        $total = $male + $female;

        $groupA = $ageA !== "NA" ? $ageA[0]->group_a : 0;
        $groupB = $ageB !== "NA" ? $ageB[0]->group_b : 0;
        $groupC = $ageC !== "NA" ? $ageC[0]->group_c : 0;
        $totalGroup = $groupA + $groupB + $groupC;

        $malePer = $femalePer = 0;
        if($male > 0){
          $malePer = round(($male/$total)*100,2);
        }

        if($female > 0){
          $femalePer = round(($female/$total)*100,2);
        }
        
        $groupAper = $groupBper = $groupCper = 0;
        if($groupA > 0){
          $groupAper = round(($groupA/$totalGroup)*100,2);
        }

        if($groupB > 0){
          $groupBper = round(($groupB/$totalGroup)*100,2);
        }

        if($groupC > 0){
          $groupCper = round(($groupC/$totalGroup)*100,2);
        }

        $countryTotal = 0;
        if($countryData !== "NA"){
          $countryTotal = $total;
        }

        json_output(200, array("status"=>"success", "malePer"=>$malePer, "femalePer"=>$femalePer, "groupAper" => $groupAper, "groupBper" => $groupBper, "groupCper" => $groupCper, "countryTotal" => $countryTotal, "countryData" => $countryData));       
      }
    }
  }

  /**
  **  Get Visitor Chart 
  */
  public function visitorChart(){
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
        $talentId = $this->getTalentId($registration_id);

        $content = $_GET;
        $dateFrom = $content['dateFrom'];
        $dateTo = $content['dateTo'];
        
        if( $dateFrom == "" && $dateTo !== "" ){
          $start = strtotime($this->global_variables['start_date']);
          $end = strtotime($dateTo); 
          if( $start > $end ){
            json_output(200, array("status"=>"error")); exit; 
          }
        }
       
        $dateRange = $this->generateDateRange($dateFrom, $dateTo);
        $labelArr = [];
        $dataArr = [];
        $maxVal = 0;
        
        for($i=0; $i<6; $i++){
          $count = 0;
          $next = $i + 1;
          $GET_VISITOR_COUNT = "SELECT COUNT(visitor_id) AS total FROM talent_profile_visits WHERE type IN ('user','talent') AND talent_id='$talentId' AND created_date >= '$dateRange[$i]' AND created_date <= '$dateRange[$next]' ";
          $visitor_count = $this->Mdl_api->customQuery($GET_VISITOR_COUNT);

          $count = $visitor_count !== "NA" ? $visitor_count[0]->total : 0;
          $maxVal = $count > $maxVal ? $count : $maxVal;
          $dataArr[] = $count;
        }

        $labelArr = $this->generateLabel($dateFrom, $dateTo, $dateRange);

        $startYear = date('Y',strtotime($dateRange[0]));
        $endYear = date('Y',strtotime($dateRange[6]));
        $duration = '';
        if($startYear == $endYear){
          $duration = $startYear;
        }else{
          $duration = $startYear.' - '.$endYear;
        }

        json_output(200, array("status"=>"success", "label"=>$labelArr, "data"=>$dataArr, "maxValue" => $maxVal, 'duration'=>$duration));       
      }
    }
  }

  /**
  **  Get Earning Chart 
  */
  public function earningChart(){
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
        $talentId = $this->getTalentId($registration_id);

        $content = $_GET;
        $dateFrom = $content['dateFrom'];
        $dateTo = $content['dateTo'];
        $occasion = $content['occasion'];
        $serviceType = $content['serviceType'];
        
        if( $dateFrom == "" && $dateTo !== "" ){
          $start = strtotime($this->global_variables['start_date']);
          $end = strtotime($dateTo); 
          if( $start > $end ){
            json_output(200, array("status"=>"error")); exit; 
          }
        }
       
        $dateRange = $this->generateDateRange($dateFrom, $dateTo);
        $labelArr = [];
        $dataArr = [];
        $maxVal = 0;

        for($i=0; $i<6; $i++){
          $next = $i + 1;
          $GET_EARNING_DATA = "SELECT talent_share FROM request WHERE talent_id='$talentId' AND created_date >= '$dateRange[$i]' AND created_date <= '$dateRange[$next]' AND request_status='complete' ";

          if($serviceType !== ''){
            $GET_EARNING_DATA .= " AND service_type='$serviceType' ";
          }

          if($occasion !== ''){
            $GET_EARNING_DATA .= " AND occasion='$occasion' ";
          }

          $total_earning = 0;
          $earning = $this->Mdl_api->customQuery($GET_EARNING_DATA);

          if($earning !== "NA"){
            foreach($earning as $val){
              $total_earning += $val->talent_share;
            }
          }

          $maxVal = $total_earning > $maxVal ? $total_earning : $maxVal;
          $dataArr[] = $total_earning;
        }

        $labelArr = $this->generateLabel($dateFrom, $dateTo, $dateRange);
        
        $startYear = date('Y',strtotime($dateRange[0]));
        $endYear = date('Y',strtotime($dateRange[6]));
        $duration = '';
        if($startYear == $endYear){
          $duration = $startYear;
        }else{
          $duration = $startYear.' - '.$endYear;
        }

        json_output(200, array("status"=>"success", "label"=>$labelArr, "data"=>$dataArr, "maxValue" => $maxVal, 'duration'=>$duration));       
      }
    }
  }

  /**
  **  Get Conversion Chart 
  */
  public function conversionChart(){
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
        $talentId = $this->getTalentId($registration_id);

        $content = $_GET;
        $dateFrom = $content['dateFrom'];
        $dateTo = $content['dateTo'];
        $occasion = $content['occasion'];
        $serviceType = $content['serviceType'];
        
        if( $dateFrom == "" && $dateTo !== "" ){
          $start = strtotime($this->global_variables['start_date']);
          $end = strtotime($dateTo); 
          if( $start > $end ){
            json_output(200, array("status"=>"error")); exit; 
          }
        }
       
        $dateRange = $this->generateDateRange($dateFrom, $dateTo);
        $labelArr = [];
        $dataArrA = [];
        $dataArrB = [];
        
        for($i=0; $i<6; $i++){
          $next = $i + 1;
          $GET_VISITOR_COUNT = "SELECT COUNT(visitor_id) AS total FROM talent_profile_visits WHERE type IN ('user','talent') AND talent_id='$talentId' AND created_date >= '$dateRange[$i]' AND created_date <= '$dateRange[$next]' ";
          $visitors = $this->Mdl_api->customQuery($GET_VISITOR_COUNT);

          $visitorsCount = $visitors !== "NA" ? $visitors[0]->total : 0;
          
          $GET_REQUESTS = "SELECT request_status FROM request WHERE talent_id='$talentId' AND request_status NOT IN ('created','failed','expired','canceled') AND created_date >= '$dateRange[$i]' AND created_date <= '$dateRange[$next]' ";

          if($serviceType !== ''){
            $GET_REQUESTS .= " AND service_type='$serviceType' ";
          }

          if($occasion !== ''){
            $GET_REQUESTS .= " AND occasion='$occasion' ";
          }

          $requests = $this->Mdl_api->customQuery($GET_REQUESTS);

          $totalRequest = 0;
          $completedRequest = 0;
          $conversionRate = 0;
          $completionRate = 0;
        
          if($requests !== "NA"){
            foreach($requests as $val){
              if($val->request_status == 'confirmed'){
                $totalRequest += 1;  
              }else if($val->request_status == 'complete'){
                $totalRequest += 1;
                $completedRequest += 1;
              }
            }
          }

          if($totalRequest > 0 && $visitorsCount > 0){
            $conversionRate = ($totalRequest / $visitorsCount) * 100;
          }
          
          if($completedRequest > 0 && $totalRequest > 0){
            $completionRate = ($completedRequest / $totalRequest) * 100;
          }

          $dataArrA[] = round($conversionRate,2);
          $dataArrB[] = round($completionRate,2);
        }

        $labelArr = $this->generateLabel($dateFrom, $dateTo, $dateRange);
        
        $startYear = date('Y',strtotime($dateRange[0]));
        $endYear = date('Y',strtotime($dateRange[6]));
        $duration = '';
        if($startYear == $endYear){
          $duration = $startYear;
        }else{
          $duration = $startYear.' - '.$endYear;
        }

        json_output(200, array("status"=>"success", "label"=>$labelArr, "dataSetA"=>$dataArrA, "dataSetB"=>$dataArrB, 'duration'=>$duration));       
      }
    }
  }

  /**
  **  Get Rating Chart 
  */
  public function ratingChart(){
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
        $talentId = $this->getTalentId($registration_id);

        $content = $_GET;
        $dateFrom = $content['dateFrom'];
        $dateTo = $content['dateTo'];
        
        if( $dateFrom == "" && $dateTo !== "" ){
          $start = strtotime($this->global_variables['start_date']);
          $end = strtotime($dateTo); 
          if( $start > $end ){
            json_output(200, array("status"=>"error")); exit; 
          }
        }
       
        $dateRange = $this->generateDateRange($dateFrom, $dateTo);
        $labelArr = [];
        $dataArr = [];
        $maxVal = 0;

        for($i=0; $i<6; $i++){
          $next = $i + 1;
          $QUERY_RATING = "SELECT rating FROM talent_review WHERE talent_id='$talentId' AND created_date >= '$dateRange[$i]' AND created_date <= '$dateRange[$next]' ";
          $ratings = $this->Mdl_api->customQuery($QUERY_RATING);

          $average = 0;
          $total_stars = 0;
          if($ratings !== "NA"){
            foreach($ratings as $val){
              $total_stars += $val->rating;
            }
            $average = $total_stars / sizeof($ratings);
          }

          $dataArr[] = $average;
        }

        $labelArr = $this->generateLabel($dateFrom, $dateTo, $dateRange);
        
        $startYear = date('Y',strtotime($dateRange[0]));
        $endYear = date('Y',strtotime($dateRange[6]));
        $duration = '';
        if($startYear == $endYear){
          $duration = $startYear;
        }else{
          $duration = $startYear.' - '.$endYear;
        }

        json_output(200, array("status"=>"success", "label"=>$labelArr, "data"=>$dataArr, 'duration'=>$duration ));       
      }
    }
  }
  
  /**
   * Generate Label Text
   */
  function generateLabel($dateFrom, $dateTo, $dateRange){
    $labelArr = [];

    if( $dateFrom == "" && $dateTo == "" ){
      for($i=0; $i<6; $i++){          
        $labelArr[] = date('M',strtotime($dateRange[$i]));
      }
    }else if( $dateFrom !== "" && $dateTo == "" ){
      $start_date = strtotime($dateFrom);
      $end_date = strtotime("now");
      $datediff = $end_date - $start_date;
      $days = round($datediff / (60 * 60 * 24));
      
      if($days < 7){
        for($i=0; $i<6; $i++){          
          $labelArr[] = date('d-M-y',strtotime($dateRange[$i]));
        }
      }else{
        for($i=0; $i<6; $i++){
          $next = $i+1;          
          $labelArr[] = date('d/m/y',strtotime($dateRange[$i])).' - '.date('d/m/y',strtotime($dateRange[$next]));
        }
      }
    }else if( $dateFrom == "" && $dateTo !== "" ){
      for($i=0; $i<6; $i++){
        $next = $i+1;          
        $labelArr[] = date('d/m/y',strtotime($dateRange[$i])).' - '.date('d/m/y',strtotime($dateRange[$next]));
      }
    }else{
      $start_date = strtotime($dateFrom);
      $end_date = strtotime($dateTo);
      $datediff = $end_date - $start_date;
      $days = round($datediff / (60 * 60 * 24));

      if($days < 7){
        for($i=0; $i<6; $i++){          
          $labelArr[] = date('d-M-y',strtotime($dateRange[$i]));
        }
      }else{
        for($i=0; $i<6; $i++){
          $next = $i+1;          
          $labelArr[] = date('d/m/y',strtotime($dateRange[$i])).' - '.date('d/m/y',strtotime($dateRange[$next]));
        }
      }
    }

    return $labelArr;
  }

  /**
   *  Generate Date Range
   */
  public function generateDateRange($start, $end){
    $endDates = [31,29,31,30,31,30,31,31,30,31,30,31];

    if( $start == "" && $end == "" ){
      $output = "Y-m-d H:i:s";
      $startMonth = date('m',strtotime("-5 months"));
      $startYear = date('Y',strtotime("-5 months"));
      $currMonth = date('m');
      $currYear = date('Y');

      $month = $startMonth;
      $year = $startYear;

      $startDate = '01-'.$month.'-'.$year;
      $dataCollection[] = date($output, strtotime($startDate));
      $month++;
      for($i=0; $i<6; $i++){
        if($month == 12){
          $month = 1;
          $year = $currYear;
        }
        $startDate = '01-'.$month.'-'.$year;
        $dataCollection[] = date($output, strtotime($startDate));
        // $endDate = $endDates[(int)$month-1].'-'.$month.'-'.$year;        
        // $dataCollection[] = date($output, strtotime($endDate));
        $month += 1;
      }

    }else if( $start !== "" && $end == "" ){
      $end_date = strtotime("now");
      $start_date = strtotime($start);
      $datediff = $end_date - $start_date;

      $days = round($datediff / (60 * 60 * 24));
      if($days == 0){
        $end = date('Y-m-d',strtotime('+6 days'));
      }else if($days < 6){
        $end = date('Y-m-d',strtotime('+5 days'));
      }else{
        $end = date('Y-m-d');
      }
      
      $dataCollection = $this->splitDates($start, $end);
    }else if( $start == "" && $end !== "" ){
      $start = date('Y-m-d',strtotime($this->global_variables['start_date']));
      $dataCollection = $this->splitDates($start, $end);
    }else{
      $start_date = strtotime($start);
      $end_date = strtotime($end);
      
      $datediff = $end_date - $start_date;
      $days = round($datediff / (60 * 60 * 24));
      
      $start = date('Y-m-d',$start_date);

      // if($days == 0){
      //   $end = date('Y-m-d',strtotime($start.' +6 days'));
      // }else 
      if($days < 6){
        $end = date('Y-m-d',strtotime($start.' +6 days'));
      }else{
        $end = date('Y-m-d',$end_date);
      }

      $dataCollection = $this->splitDates($start, $end);
    }

    return $dataCollection;
  }

  /**
   *  Date Range Calculator
   */
  // function splitDates($min, $max, $parts = 6, $output = "Y-m-d H:i:s"){
  //   $dataCollection[] = date($output, strtotime($min));
  //   $diff = (strtotime($max) - strtotime($min)) / $parts;
  //   $convert = strtotime($min) + $diff;

  //   for ($i = 1; $i < $parts; $i++) {
  //       $dataCollection[] = date($output, $convert);
  //       $convert += $diff;
  //   }
  //   $dataCollection[] = date($output, strtotime($max));
  //   return $dataCollection;
  // }

  function splitDates($min, $max, $parts = 6, $output = "Y-m-d"){
    $dateOne = date($output, strtotime($min));
    $dataCollection[] = date('Y-m-d H:i:s', strtotime($dateOne.' 00:00:00'));
    $diff = (strtotime($max) - strtotime($min)) / $parts;
    $convert = strtotime($min) + $diff;

    for ($i = 1; $i < $parts; $i++) {
        // $dataCollection[] = date($output, $convert);
        $dateTwo = date($output,$convert);
        $dataCollection[] = date('Y-m-d H:i:s', strtotime($dateTwo.' 00:00:00'));
        $convert += $diff;
    }

    $dateThree = date($output, strtotime($max));
    $dataCollection[] = date('Y-m-d H:i:s', strtotime($dateThree.' 23:59:59'));
    // $dataCollection[] = date($output, strtotime($max));
    return $dataCollection;
  }

  /**
   *  Get Talent ID
   */
  function getTalentId($registrationId){
    $query = "SELECT talent_id FROM talent_details WHERE registration_id=$registrationId";
    $talent = $this->Mdl_api->customQuery($query);
    if($talent !== "NA"){
      return $talent[0]->talent_id;
    }else{
      return 0;
    }
  }

  public function test(){
    // echo $_SERVER['DOCUMENT_ROOT'];

    // date_default_timezone_set('Asia/Riyadh');
    // echo date('Y-m-d H:i:s',strtotime('+2 hours'));
    $response = $this->generateDateRange('9-10-2020','15-10-2020');
    print_r($response);
  }
}