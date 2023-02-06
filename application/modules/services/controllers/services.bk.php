<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH.'modules/generic/controllers/Generic.php';

class Services extends Generic{
	
	function __construct()
	{
		parent::__construct();
		$this->load->model('Mdl_services');
		
	}
	
/************************ Metal & Currency Status Start *****************************/
		function sendOtpAction()
		{ 
			$json = file_get_contents('php://input');
			$obj = json_decode($json,True);
			$mobile_number = trim($obj['mobile_number']);
            $datetime = date("Y-m-d H:i:s");
			if($mobile_number !=""){
				$digits = 4;	
	            //  $otp = rand(pow(10, $digits-1), pow(10, $digits)-1);
	           $otp = '1010';
	            $ip_address = $_SERVER['REMOTE_ADDR'];
	            /*
	            ** CHECK MOBILE NUMBER IS ALREADY EXIST IN TABLE OR NOT
	            */

	            $mobileExist = $this->Mdl_services->isExist("mobile_otp_vefification",array("mobile_no"=>$mobile_number));
	            if($mobileExist ===TRUE){
                    $data = array("otp"=>$otp,"status"=>"0","modified_at"=>$datetime,"ip_address"=>$ip_address);
                    $result=$this->Mdl_services->update("mobile_otp_vefification",array("mobile_no"=>$mobile_number),$data);
                    $response=array(
						"Result"=>$data,
						"Message"=>"OTP Sent to existing mobile number",
						"status"=>true
				    );	
	            }else{
	            	$data = array("mobile_no"=>$mobile_number,"otp"=>$otp,"status"=>"0","created_at"=>$datetime,"ip_address"=>$ip_address);
	                $result=$this->Mdl_services->insert("mobile_otp_vefification",$data);
	                $response=array(
						"Result"=>$data,
						"Message"=>"OTP Sent to new mobile number",
						"status"=>true
				    );	
	            }
			}else{
			    $response=array(
						"Result"=>"",
						"Message"=>"Enter Mobile Number",
						"status"=>false
				);	
			}
		
			header('Content-type: application/json');
			echo json_encode(array("Response"=>$response));
		} 
		
		function verifyOtpAction()
		{ 
			$json = file_get_contents('php://input');
			$obj = json_decode($json,True);
       // 	print_r($obj);exit;
			$otp_number = trim($obj['otp_number']);
			$mobile_number = trim($obj['mobile_number']);
            $datetime = date("Y-m-d H:i:s");
			if($mobile_number !="" && $otp_number !=""){
				
	            /*
	            ** CHECK MOBILE NUMBER IS ALREADY EXIST IN TABLE OR NOT
	            */

	            $mobileExist = $this->Mdl_services->isExist("mobile_otp_vefification",array("mobile_no"=>$mobile_number));
	            if($mobileExist ===TRUE){

                   $getOtpDetails =  $this->Mdl_services->retrieve("mobile_otp_vefification",array("mobile_no"=>$mobile_number,"status"=>"0"));
                   
                   if($getOtpDetails !="NA"){
                   	$db_otp = trim($getOtpDetails[0]->otp);

                   	if($db_otp == $otp_number){
                      
                      $result=$this->Mdl_services->update("mobile_otp_vefification",array("mobile_no"=>$mobile_number,"otp"=>$otp_number,"status"=>"0"),array("status"=>"1","modified_at"=> $datetime));
                      if($result){
                        $response=array(
							"Result"=>array("mobile_number"=>$mobile_number,"otp"=>$otp_number,"status"=>"1",",modified_at"=>$datetime),
							"Message"=>"OTP verified successfully ",
							"status"=>true
					    );	
                      }else{
                      	$response=array(
							"Result"=>"",
							"Message"=>"Database update error",
							"status"=>false
					    );	
                      }

                   	}else{
                   		$response=array(
							"Result"=>"",
							"Message"=>"OTP Not Matched",
							"status"=>false
					    );	
                   	}


                   }else{

	                   	$response=array(
							"Result"=>"",
							"Message"=>"Mobile Number otp is already verified",
							"status"=>false
					    );	
                   }

	            }else{
	            	$response=array(
						"Result"=>"",
						"Message"=>"Mobile Number Not Found",
						"status"=>false
				    );	
	            }
			}else{
			    $response=array(
						"Result"=>"",
						"Message"=>"Enter Mobile Number AND OTP properly",
						"status"=>false
				);	
			}
		
			header('Content-type: application/json');
			echo json_encode(array("Response"=>$response));
		} 

//-------
function verifyOtpFunction($mobile_number,$otp)
		{ 
		
			$otp_number = trim($otp);
			$mobile_number = trim($mobile_number);
            $datetime = date("Y-m-d H:i:s");
			if($mobile_number !="" && $otp_number !=""){
				
	            /*
	            ** CHECK MOBILE NUMBER IS ALREADY EXIST IN TABLE OR NOT
	            */

	            $mobileExist = $this->Mdl_services->isExist("mobile_otp_vefification",array("mobile_no"=>$mobile_number));
	            if($mobileExist ===TRUE){
					
					
                   $getOtpDetails =  $this->Mdl_services->retrieve("mobile_otp_vefification",array("mobile_no"=>$mobile_number));
                  
					if($getOtpDetails !="NA"){
						if($getOtpDetails[0]->status=='0')
						{
							$db_otp = trim($getOtpDetails[0]->otp);

							if($db_otp == $otp_number){

								$result=$this->Mdl_services->update("mobile_otp_vefification",array("mobile_no"=>$mobile_number,"otp"=>$otp_number,"status"=>"0"),array("status"=>"1","modified_at"=> $datetime));
								if($result){
								return "verified";
								}else{
								return "error";
								}
							
							}else{
								return "otp not matched";
							}

						}
						else return "already logged in";
					}					

	            }else{
	            	return "user not found";
	            }
			}
} 

function user_login()
{
	$method = $_SERVER['REQUEST_METHOD'];
    
		if($method !== 'POST'){
			//json_output(400,array('status' => 400,'message' => 'Bad request.'));
			$response=array(
                                "Result"=>'',
                                "Message"=>"Bad request. ",
                                "status"=>false
                                );
		}else{
    	$json = file_get_contents('php://input');
		
			$obj = json_decode($json,True);
			//print_r($obj);exit;
        	$mobile_number = trim($obj['mobile_number']);
			$otp = trim($obj['otp']);
			$datetime = date("Y-m-d H:i:s");
			if($mobile_number !="" && $otp !=""){
				
	            /*
	            ** CHECK MOBILE NUMBER IS ALREADY EXIST IN TABLE OR NOT
	            */

	            $userExist = $this->Mdl_services->isExist("user_registration",array("mobile"=>$mobile_number));
	            if($userExist ===TRUE){

                   $userCheck =  $this->Mdl_services->retrieve("user_registration",array("mobile"=>$mobile_number));
                   
                   if($userCheck !="NA"){
                       
                       
                       $verifyOtp=$this->verifyOtpFunction($mobile_number,$otp);
					   //echo "---------->".$verifyOtp;exit;
                       if($verifyOtp=='verified')
                       {
                            $name=$userCheck[0]->full_name;
							$profileImage=$userCheck[0]->profile_image;
                            
                            $result=$this->Mdl_services->update("user_registration",array("mobile"=>$mobile_number),array("last_login"=> $datetime));
							//----------------update token starts-----------------------------
							$token = Modules::run('security/generateAuthToken',$userCheck[0]->uid);
							$serach_param = array(
								"registration_id"=>$userCheck[0]->id, 								
								"uid"=>$userCheck[0]->uid
							);

							if($this->Mdl_services->isExist("authentication",$serach_param)){
							$authentication_data = array(
								"token" => $token,
								"expiry_time" => date("Y-m-d H:i:s",strtotime("+100 day")),
								"modified_date" => date("Y-m-d H:i:s")
							);
							$authentication_id = $this->Mdl_services->update("authentication",$serach_param,$authentication_data);
							}else{
							$authentication_data = array(
								"registration_id" => $userCheck[0]->id,
								"uid" => $userCheck[0]->uid,
								"token" => $token,
								"expiry_time" => date("Y-m-d H:i:s",strtotime("+100 day")),
								"created_date" => date("Y-m-d H:i:s"),
								"modified_date" => date("Y-m-d H:i:s")
							);
							$authentication_id = $this->Mdl_services->insert("authentication",$authentication_data);
							}							
							//--------------------update token ends-----------------------------

                            if($result)
                            {
                                $response=array(
                                "Result"=>array("mobile"=>$mobile_number,"name"=>$name,"profileImage"=>$profileImage),
                                "Message"=>"Login successfull ",
                                "status"=>true
                                );	
                            }
                       }
                       elseif($verifyOtp=='otp not matched')
                       {
                            $response=array(
							"Result"=>"",
							"Message"=>"OTP Not Matched",
							"status"=>false
					        );
                       }
                       elseif($verifyOtp=='user not found')
                       {
                            $response=array(
							"Result"=>"",
							"Message"=>"OTP not sent",
							"status"=>false
					        );
                       }
					   elseif($verifyOtp=='already logged in')
					   {
						   $response=array(
							"Result"=>"",
							"Message"=>"User already logged in",
							"status"=>false
					        );
						   
						   
						   
					   }
                   }
	            }else{
	            	$response=array(
						"Result"=>"",
						"Message"=>"User Not Found",
						"status"=>false
				    );	
	            }
			}else{
			    $response=array(
						"Result"=>"",
						"Message"=>"Enter mobile number AND otp",
						"status"=>false
				);	
			}
		
			header('Content-type: application/json');
			echo json_encode(array("Response"=>$response));
              
		}
}   

 function user_logout(){
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
    
       if($check_token_validity['status'] === "invalid")
	  {
        //json_output(200, array('status' => 'invalid token'));
		$response=array(
						"Result"=>'',
						"Message"=>"invalid token",
						"status"=>false
				);
      }
	  else if($check_token_validity['status'] === "expired")
	  {
       // json_output(200, array('status' => 'expired'));
	   $response=array(
						"Result"=>'',
						"Message"=>"invalid expired",
						"status"=>false
				);
      }else if($check_token_validity['status'] === "valid"){
      
        $registration_id = $check_token_validity['registration_id'];
        $uid = $check_token_validity['uid'];
       
        $authentication_details = $this->Mdl_services->retrieve('authentication',array('registration_id'=>$registration_id, 'uid'=>$uid));
      
        if($authentication_details !== "NA"){
          $updated_data = array(
            "expiry_time" => date("Y-m-d H:i:s"),
            "modified_date" => date("Y-m-d H:i:s")
          );
          $update = $this->Mdl_services->update('authentication', array('registration_id'=>$registration_id, 'uid'=>$uid), $updated_data);
          //json_output(200, array('status' => 'success'));
		  $response=array(
						"Result"=>'',
						"Message"=>"log out successfull",
						"status"=>true
				);
        }else{
          //json_output(200, array('status'=>'no data'));
		   $response=array(
						"Result"=>'',
						"Message"=>"wrong request",
						"status"=>false
				);
        }
      }
		}
		header('Content-type: application/json');
			echo json_encode(array("Response"=>$response));
  }
  
 
function register(){
    $method = $_SERVER['REQUEST_METHOD'];
    
		if($method !== 'POST'){
			$response=array(
						"Result"=>"",
						"Message"=>"Bad request.",
						"status"=>false
				);	
			//json_output(400,array('status' => 400,'message' => 'Bad request.'));
		}else{
    
      $content = json_decode(file_get_contents('php://input'), TRUE);
	 // print_r($content);exit;
      $this->form_validation->set_data($content);
      $custom_errors = array();
    
      $this->form_validation->set_rules("full_name","Full Name","trim|xss_clean|required",
      array(
        'required' => "full_name-required"
      ));
    
           
      $this->form_validation->set_rules("email_id","Email","trim|xss_clean|required|valid_email|is_unique[user_registration.email_id]",
      array(
        'required' => "email_id-required",
        'valid_email' => "email_id-invalid"
      ));      
      
      $this->form_validation->set_rules("country_code","Phone Code","trim|xss_clean|required",
      array(
        'required' => "country_code-required"
      ));
      
      $this->form_validation->set_rules("mobile","Mobile Number","trim|xss_clean|required|is_unique[user_registration.mobile]|callback_validate_number",array(

      'validate_number' => 'Mobile number not in'

    ));
	 // $this->form_validation->set_message('validate_number', 'Group id is not acceptable.');
	/*  $this->form_validation->set_rules("mobile_number","Mobile number","trim|required|numeric|callback_validate_number",array(

      'validate_number' => 'Mobile number not in'

    ));*/
       
        if($this->form_validation->run($this) == FALSE){
			
        $errors = $this->form_validation->error_array();
		//print_r($errors);exit;
		
        $final_array = array_merge($errors,$custom_errors);
        //json_output(200,array('status'=>'error','errorData'=>$final_array));
		$response=array(
						"Result"=>$final_array,
						"Message"=>"Validation error.",
						"status"=>false
				);	
      }else{
      
        if(! empty($custom_errors)){
          //json_output(200,array('status'=>'error','errorData'=>$custom_errors));
		  $response=array(
						"Result"=>$custom_errors,
						"Message"=>"Custom Validation error.",
						"status"=>false
				);
        }
		else{
		$uid = $this->getUID();        
        $data = array(
          "uid" => $uid,
		  "full_name" => strip_tags($content['full_name']),   
          "email_id" => strip_tags($content['email_id']),         
          "country_code" => strip_tags($content['country_code']),
          "mobile" => strip_tags($content['mobile']),
          "is_verified"=>'N',
		  "platform"=>'web',
          "is_profile_updated" => 'N',
		  "account_status" => '0',
          "added_date" => date("Y-m-d H:i:s"),
          "modified_date" => date("Y-m-d H:i:s")
        );
        $reg_id = $this->Mdl_services->insert("user_registration",$data);		
        $verification_link = base_url().'services/everify/'.($uid);   
        $subject = "Flik - Verify your account";      
        $email_view_file = 'email-verify';
        
        /**
         * Verification Mail
         */
        $mail_data = array(
          'view_file' => $email_view_file,
          'to' => strip_tags($content['email_id']),
          'cc' => '',
          'subject' => $subject,
          'isAttachment' => false,
          'verification_link' => $verification_link,
          "name" => strip_tags($content['full_name'])
        );
        
        if(Modules::run('email/mailer',$mail_data)){
          //json_output(200,array('status'=>'success'));
		  $response=array(
						"Result"=>'',
						"Message"=>"success",
						"status"=>true
				);
        }else{
          //json_output(200,array('status'=>'fail','message'=>'mail-failed'));
		   $response=array(
						"Result"=>'',
						"Message"=>"Mail failed",
						"status"=>false
				);
        }		
			
		}  
       
      }
		}
		header('Content-type: application/json');
			echo json_encode(array("Response"=>$response));
  }
  
  
function update_profile(){
	
  $method = $_SERVER['REQUEST_METHOD'];
    $headers = $this->input->request_headers();
    
		if($method !== 'POST'){
			$response=array(
						"Result"=>"",
						"Message"=>"Bad request.",
						"status"=>false
				);	
			//json_output(400,array('status' => 400,'message' => 'Bad request.'));
		}else{
			
	  if(isset($headers['Authtoken'])){
        $token = $headers['Authtoken'];
      }else{
        $token = $headers['authtoken'];
      }
      $check_token_validity = Modules::run('security/validateAuthToken',$token);
	   if($check_token_validity['status'] === "invalid")
	  {
        //json_output(200, array('status' => 'invalid token'));
		$response=array(
						"Result"=>'',
						"Message"=>"invalid token",
						"status"=>false
				);
      }
	  else if($check_token_validity['status'] === "expired")
	  {
       // json_output(200, array('status' => 'expired'));
	   $response=array(
						"Result"=>'',
						"Message"=>"invalid expired",
						"status"=>false
				);
      }
	  else if($check_token_validity['status'] === "valid")
	  {
		  
		$registration_id = $check_token_validity['registration_id'];
        $uid = $check_token_validity['uid'];
        $type = $check_token_validity['type'];
		  
		 $user_info = $this->Mdl_services->retrieve('user_registration',array('id'=>$registration_id, 'uid'=>$uid));
		 $user_category = $this->Mdl_services->retrieve('category_user',array('user_id'=>$registration_id));
		 $user_language = $this->Mdl_services->retrieve('language_user',array('user_id'=>$registration_id));
		 $content = json_decode(file_get_contents('php://input'), TRUE);
	  //print_r($content);exit;
      $this->form_validation->set_data($content);
      $custom_errors = array();
    $this->form_validation->set_rules("full_name","Full Name","trim|xss_clean|required",
      array(
        'required' => "full_name-required"
      ));
      $this->form_validation->set_rules("username","User Name","trim|xss_clean|required|is_unique[user_registration.username]",
      array(
        'required' => "username-required"
      ));
    
           
      $this->form_validation->set_rules("bio","Bio","trim|xss_clean|required",
      array(
        'required' => "bio-required",
       
      )); 
		if($content['bio'] !== ""){
          $bioLen =  mb_strlen($content['bio'],'UTF-8'); 
          if($bioLen > 350){ 
            $custom_errors['bio'] = 'bio-maxlength';
          }
        }
	
		
	 $this->form_validation->set_rules("gender","Gender","trim|xss_clean|required|in_list[M,F]",
      array(
        'required' => "gender-required",
        'in_list' => 'gender-invalid-option'
      ));
	  
	  $this->form_validation->set_rules("url","Url","trim|xss_clean|required",
      array(
        'required' => "url-required"      
      ));
      
      if(isset($content['language']) && empty($content['language'])){
          $custom_errors['language'] = 'select-language';
        }
      
     if(isset($content['categories']) && empty($content['categories'])){
          $custom_errors['categories'] = 'select-category';
        }
	
      	
        if($this->form_validation->run($this) == FALSE){
			
        $errors = $this->form_validation->error_array();
		
		
        $final_array = array_merge($errors,$custom_errors);
		//print_r($final_array);exit;
        //json_output(200,array('status'=>'error','errorData'=>$final_array));
		$response=array(
						"Result"=>$final_array,
						"Message"=>"Validation error.",
						"status"=>false
				);	
      }else
	  {
       //print_r($custom_errors);exit;
        if(!empty($custom_errors)){
          //json_output(200,array('status'=>'error','errorData'=>$custom_errors));
		  $response=array(
						"Result"=>$custom_errors,
						"Message"=>"Custom Validation error.",
						"status"=>false
				);
        }
		else
		{
			if($user_category !== "NA"){
              foreach($user_category as $exhCategory){
                $found = false;
                foreach($content['categories'] as $newCategory){
                  if($newCategory['value'] == $exhCategory->cat_id){
                    $found = true;
                    break;
                  }
                }
                if(! $found){
                  $this->Mdl_services->delete("category_user",array('user_id'=>$registration_id, 'cat_id'=>$exhCategory->cat_id));
                }
              }
            }
  
            foreach($content['categories'] as $category){
              if( ! $this->Mdl_services->isExist("category_user", array('user_id'=>$registration_id, 'cat_id'=>$category['value'])) ){
                $category_array = array(
                  "user_id" => $registration_id,                 
                  "cat_id" => strip_tags($category['value']),
                  "created_date" => date("Y-m-d H:i:s")
                );
                $insert_category = $this->Mdl_services->insert("category_user",$category_array);
              }
            }
			//---------------languages--------------------
			 if($user_language !== "NA"){
              foreach($user_language as $exhLang){
                $found = false;
                foreach($content['language'] as $newLang){
                  if($newLang['value'] == $exhLang->lang_id){
                    $found = true;
                    break;
                  }
                }
                if(! $found){
                  $this->Mdl_services->delete("language_user",array('user_id'=>$registration_id, 'lang_id'=>$exhLang->lang_id));
                }
              }
            }
  
            foreach($content['language'] as $languages){
              if( ! $this->Mdl_services->isExist("language_user", array('user_id'=>$registration_id, 'lang_id'=>$languages['value'])) ){
                $category_array = array(
                  "user_id" => $registration_id,                 
                  "lang_id" => strip_tags($languages['value']),
                  "created_date" => date("Y-m-d H:i:s")
                );
                $insert_language = $this->Mdl_services->insert("language_user",$category_array);
              }
            }
        
        $data = array(		
				"full_name" => strip_tags($content['full_name']),   
				"bio" => strip_tags($content['bio']),
				"gender" => strip_tags($content['gender']),
				"url" => strip_tags($content['url']),           
				"is_profile_updated" => 'Y',			
				"modified_date" => date("Y-m-d H:i:s")
        );
        $reg_id = $this->Mdl_services->update("user_registration",array('id'=>$registration_id),$data);
			$response=array(
						"Result"=>'',
						"Message"=>"Profile updated.",
						"status"=>true
				);
			
		}      
      }
		} 
		  
		  
	  }
    
      
		header('Content-type: application/json');
			echo json_encode(array("Response"=>$response));
  }
  
  

  /**
   * Verify email id
   */

	public function everify(){
		$method = $_SERVER['REQUEST_METHOD'];
    
    if($method !== 'POST'){
			//json_output(400,array('status' => 400,'message' => 'Bad request.'));
			$response=array(
						"Result"=>"",
						"Message"=>"Bad request.",
						"status"=>false
				);	
		}else{
    
      $content = json_decode(file_get_contents('php://input'), TRUE);
      $uid = $content['uid'];
    
      $search_data = array(
        'uid' => $uid       
      );
      $registration = $this->Mdl_services->retrieve("user_registration",$search_data);
       if($registration != "NA" )
	   {
      $registrationId = $registration[0]->id; 
      $get_user_details = "SELECT is_profile_updated FROM user_registration WHERE id='$registrationId' ";
      $user_details = $this->Mdl_services->customQuery($get_user_details);
	   }
      if($registration == "NA" ){
       // json_output(200,array('status'=>'danger','message'=>'something-went-wrong'));
		$response=array(
						"Result"=>"",
						"Message"=>"something went wrong",
						"status"=>false
				);
      }else if( ($registration[0]->is_verified == "N" || $registration[0]->is_verified == "Y") && $registration[0]->is_profile_updated == "N")
	  {

        $updated_registration_data = array(
          'is_verified' => 'Y',
          'account_status' => '1',
          'modified_date' => date("Y-m-d H:i:s")
        );
        $updated = $this->Mdl_services->update("user_registration",$search_data, $updated_registration_data);

        $token = Modules::run('security/generateAuthToken',$registration[0]->uid);
        $serach_param = array(
          "registration_id"=>$registrationId,          
          "uid"=>$registration[0]->uid
        );
            
        if($this->Mdl_services->isExist("authentication",$serach_param)){
          $authentication_data = array(
            "token" => $token,
            "expiry_time" => date("Y-m-d H:i:s",strtotime("+1 day")),
            "modified_date" => date("Y-m-d H:i:s")
          );
          $authentication_id = $this->Mdl_services->update("authentication",$serach_param,$authentication_data);
        }else{
          $authentication_data = array(
            "registration_id" => $registration[0]->id,           
            "uid" => $registration[0]->uid,
            "token" => $token,
            "expiry_time" => date("Y-m-d H:i:s",strtotime("+1 day")),
            "created_date" => date("Y-m-d H:i:s"),
            "modified_date" => date("Y-m-d H:i:s")
          );
          $authentication_id = $this->Mdl_services->insert("authentication",$authentication_data);
        }

        $profile_image = $registration[0]->profile_image;
		  $full_name = $registration[0]->full_name;
     

        $user = array(
          "token" => $token,
         "name" => $full_name,
          "image" => $profile_image
        );

        //json_output(200,array('status'=>'success','message'=>'talent-email-verified','user'=>$user));
		$response=array(
						"Result"=>$user,
						"Message"=>"email verified",
						"status"=>true
				);
      }
	  else if($registration[0]->account_status == "0")
	  { 
       // json_output(200,array('status'=>'danger','message'=>'account-deactive'));
	   $response=array(
						"Result"=>'',
						"Message"=>"Account deactive",
						"status"=>false
				);
      }
	  else
	  { 
        //json_output(200,array('status'=>'verified','message'=>'talent-email-verified'));
		$response=array(
						"Result"=>'',
						"Message"=>"email verified",
						"status"=>true
				);
      }
		}
		header('Content-type: application/json');
			echo json_encode(array("Response"=>$response));
  }

 





/**
   * Callback : International Mobile
   */
	function validate_number($mobile)
{
 
	
		if(preg_match('/^[0-9]{7,15}$/', $mobile)) {
			return true;
		}else{
			//$this->form_validation->set_message('mobile_intl_check','talent-phoneNumber-invalid');
			return false;
		}
   

}
  /**
   * Callback : Mobile unique check
   */
	public function unique_mobile_check($mobile_number){
		
      if($this->Mdl_services->isExist('user_registration',array('mobile'=>$mobile_number))){
       return false;
      }else{
        return true;
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
    
       if($check_token_validity['status'] === "invalid")
	  {
        //json_output(200, array('status' => 'invalid token'));
		$response=array(
						"Result"=>'',
						"Message"=>"invalid token",
						"status"=>false
				);
      }
	  else if($check_token_validity['status'] === "expired")
	  {
       // json_output(200, array('status' => 'expired'));
	   $response=array(
						"Result"=>'',
						"Message"=>"invalid expired",
						"status"=>false
				);
      }else if($check_token_validity['status'] === "valid"){
    
        $registration_id = $check_token_validity['registration_id'];
        $uid = $check_token_validity['uid'];
       // $type = $check_token_validity['type'];
       
        $talent_details = $this->Mdl_services->retrieve('user_registration',array('id'=>$registration_id));
       
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
          //json_output(200,array('status'=>'error','errorData'=>$custom_errors));
		   $response=array(
						"Result"=>$custom_errors,
						"Message"=>"Custom Validation error.",
						"status"=>false
				);
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
       
              $update_profile_image = $this->Mdl_services->update("user_registration",array('id' => $registration_id),$updated_talent_details);
              //json_output(200, array('status'=>'success', 'profileImage'=>$upload_response['path'] ));
			  $response=array(
						"Result"=>$upload_response['path'],
						"Message"=>"Image uploaded",
						"status"=>true
				);
            }else{
              //json_output(200, array('status'=>'fail', 'profileImage'=>"upload-failed" ));
			  $response=array(
						"Result"=>'',
						"Message"=>"Image upload fail",
						"status"=>false
				);
            }
          }else{
            //json_output(200, array('status'=>'fail', 'profileImage'=>"image-required" ));
			 $response=array(
						"Result"=>'',
						"Message"=>"Image required",
						"status"=>false
				);
          }
        }
      }
			
	
      
    }
	header('Content-type: application/json');
			echo json_encode(array("Response"=>$response));
  }
  
  /**
	* Show Profile
 */ 
 function show_profile(){
	
  $method = $_SERVER['REQUEST_METHOD'];
   	if($method !== 'POST'){
			$response=array(
						"Result"=>"",
						"Message"=>"Bad request.",
						"status"=>false
				);	
			//json_output(400,array('status' => 400,'message' => 'Bad request.'));
		}else{
			
		  $content = json_decode(file_get_contents('php://input'), TRUE);
		$user_info = $this->Mdl_services->retrieve('user_registration',array('id'=>$content['registration_id'], 'uid'=>$content['uid']));		
		
	//	echo $this->db->last_query();exit;
		if($user_info!='NA')
		{
			$uid=$content['uid'];
			$registration_id=$content['registration_id'];
			$get_talent_categories = "SELECT c.id, c.category_name FROM categories c INNER JOIN category_user t ON t.cat_id=c.id WHERE t.user_id='$registration_id'";
			$talent_category_master = $this->Mdl_services->customQuery($get_talent_categories);
			 if($talent_category_master !== "NA"){ 
				$categories = [];
				foreach($talent_category_master as $category){
				  $name = Modules::run('services/getNameByCatId', $category->id);
				  $categories[] = (object) array('label' => $name, 'value' => $category->id);
				}
				
				$data['categories'] = $categories;
				// $response['categories'] = $talent_category_master;
			  }else{
				$data['categories'] = [];
			  }
			  
			
			 $get_talent_language = "SELECT c.id, c.language FROM language_master c INNER JOIN language_user t ON t.lang_id=c.id WHERE t.user_id='$registration_id'";
			 $talent_language_master = $this->Mdl_services->customQuery($get_talent_language);
			 if($talent_language_master !== "NA"){
				$language = [];
				foreach($talent_language_master as $language){
				  $name = Modules::run('services/getNameByLangId', $language->id);
				  $language[] = (object) array('label' => $name, 'value' => $language->id);
				}
				$data['language'] = $language;
				// $response['categories'] = $talent_category_master;
			  }else{
				$data['language'] = [];
			  } 
			   //---------------get followers of user
			  $follower_info = $this->Mdl_services->retrieve('follower_master',array('user_id'=>$user_info[0]->id));
			  if($follower_info!='NA')
			  {
				  $followerCount=sizeof($follower_info);
				  
			  }
			  else $followerCount=0;
			  //---------------get following of user
			  $following_info = $this->Mdl_services->retrieve('following_master',array('following_id'=>$user_info[0]->id));
			  if($following_info!='NA')
			  {
				  $followingCount=sizeof($following_info);
				  
			  }
			  else $followingCount=0;
			  
			    //---------------get views of user
			  $view_info = $this->Mdl_services->retrieve('user_story_views',array('user_id'=>$user_info[0]->id));
			  if($view_info!='NA')
			  {
				  $viewsCount=sizeof($view_info);
				  
			  }
			  else $viewsCount=0;
			  
			$data[] = array(		
					"full_name" => strip_tags($user_info[0]->full_name),   
					"bio" => strip_tags($user_info[0]->bio),
					"email_id" => strip_tags($user_info[0]->email_id),
					"mobile" => strip_tags($user_info[0]->mobile),
					"username" => strip_tags($user_info[0]->username),
					"profile_image" => strip_tags($user_info[0]->profile_image),
					"gender" => ($user_info[0]->gender=='M')?"Male":"Female",
					"url" => strip_tags($user_info[0]->url), 
					"platform" => strip_tags($user_info[0]->platform),
					"followerCount" => $followerCount,
					"followingCount" => $followingCount,
					"viewsCount" => $viewsCount,
					"is_profile_updated" => ($user_info[0]->is_profile_updated=='Y')?"Yes":"No",
					"is_verified" => ($user_info[0]->is_verified=='Y')?"Yes":"No",					
				"added_date" => date('Y-m-d',strtotime($user_info[0]->added_date)),
			);
		
				$response=array(
							"Result"=>$data,
							"Message"=>"",
							"status"=>true
					);
				
			
		}
		else{
			
			$response=array(
							"Result"=>'',
							"Message"=>"Profile not found.",
							"status"=>false
					);
			
			
			
		}
			
		
		  
		  
	  }
    
      
		header('Content-type: application/json');
		echo json_encode(array("Response"=>$response));
  }
  
  /**
   * Get name of the category
   */
  function getNameByCatId($category_id){
    $category = $this->Mdl_services->retrieve('categories', array('id'=>$category_id));
    if($category !== "NA"){
      return $category[0]->category_name;
    }else{
      return "No Data";
    }
  } 
  /**
   * Get name of the language
   */
  function getNameByLangId($language_id){
    $language = $this->Mdl_services->retrieve('language_master', array('id'=>$language_id));
    if($language !== "NA"){
      return $language[0]->language;
    }else{
      return "No Data";
    }
  } 

/**
	* Check if profile is updated
*/

function checkProfileIsUpdated()
{
	$method = $_SERVER['REQUEST_METHOD'];
    $headers = $this->input->request_headers();
    
		if($method !== 'POST'){
			$response=array(
						"Result"=>"",
						"Message"=>"Bad request.",
						"status"=>false
				);	
			//json_output(400,array('status' => 400,'message' => 'Bad request.'));
		}else{
			
	  if(isset($headers['Authtoken'])){
        $token = $headers['Authtoken'];
      }else{
        $token = $headers['authtoken'];
      }
      $check_token_validity = Modules::run('security/validateAuthToken',$token);
	   if($check_token_validity['status'] === "invalid")
	  {
        //json_output(200, array('status' => 'invalid token'));
		$response=array(
						"Result"=>'',
						"Message"=>"invalid token",
						"status"=>false
				);
      }
	  else if($check_token_validity['status'] === "expired")
	  {
       // json_output(200, array('status' => 'expired'));
	   $response=array(
						"Result"=>'',
						"Message"=>"token expired",
						"status"=>false
				);
      }
	  else if($check_token_validity['status'] === "valid")
	  {
		 $registration_id = $check_token_validity['registration_id'];
		 $uid = $check_token_validity['uid']; 
		  
		$user_info = $this->Mdl_services->retrieve('user_registration',array('id'=>$registration_id, 'uid'=>$uid));
		if($user_info!='NA')
		{
			$isProfileUpdated=$user_info[0]->is_profile_updated;
			if($user_info[0]->is_profile_updated=='Y')
			{
				$response=array(
						"Result"=>'',
						"Message"=>"Profile updated",
						"status"=>true
				);
				
				
			}
			else
			{
				
				$response=array(
						"Result"=>'',
						"Message"=>"Profile not updated",
						"status"=>false
				);
				
			}
			
		}
		else
			{
				
				$response=array(
						"Result"=>'',
						"Message"=>"Profile not found",
						"status"=>false
				);
				
			}
		  
	  }
}

header('Content-type: application/json');
echo json_encode(array("Response"=>$response));
}

function get_stories()
{
	$method = $_SERVER['REQUEST_METHOD'];
    $headers = $this->input->request_headers();
    
		if($method !== 'POST'){
			$response=array(
						"Result"=>"",
						"Message"=>"Bad request.",
						"status"=>false
				);	
			//json_output(400,array('status' => 400,'message' => 'Bad request.'));
		}else{
			
	  if(isset($headers['Authtoken'])){
        $token = $headers['Authtoken'];
      }else{
        $token = $headers['authtoken'];
      }
      $check_token_validity = Modules::run('security/validateAuthToken',$token);
	   if($check_token_validity['status'] === "invalid")
	  {
        //json_output(200, array('status' => 'invalid token'));
		$response=array(
						"Result"=>'',
						"Message"=>"invalid token",
						"status"=>false
				);
      }
	  else if($check_token_validity['status'] === "expired")
	  {
       // json_output(200, array('status' => 'expired'));
	   $response=array(
						"Result"=>'',
						"Message"=>"token expired",
						"status"=>false
				);
      }
	  else if($check_token_validity['status'] === "valid")
	  {
		$content = json_decode(file_get_contents('php://input'), TRUE);
		$limit_per_page = $content['limit'];
		$page = $content['page'];
		$start_index = ($page - 1) * $limit_per_page;
		  
		$user_info = $this->Mdl_services->retrieve('user_registration',array('id'=>$content['user_id']));
	//	echo $this->db->last_query();exit;
		if($user_info!='NA')
		{
			
			 $story_infoQ="Select * from user_story  where user_id='".$content['user_id']."' and status='Active' and content_warning='' order by added_date DESC";
			$story_infoQ = $this->Mdl_services->customQuery($story_infoQ);
			
			
			//echo $this->db->last_query();exit;
			if($story_infoQ!='NA')
			{
				$story_count=sizeof($story_infoQ);
				$stories=array();
				$story_info="Select * from user_story  where user_id='".$content['user_id']."' and status='Active' and content_warning='' order by added_date DESC limit $start_index,$limit_per_page";
				$story_info = $this->Mdl_services->customQuery($story_info);
					if($story_info!='NA')
					{
					foreach($story_info as $key=>$str)
					{ 
						$likeCount="Select * from user_story_likes  where story_id=".$str->story_id." and is_liked='1'";
						$likes = $this->Mdl_services->customQuery($likeCount);						
						if($likes!='NA') $likes=sizeof($likes);
						else $likes=0;
						
						$commentsCount="Select * from user_story_comments  where story_id=".$str->story_id." and is_spam='0' and status='Active'";
						$comments = $this->Mdl_services->customQuery($commentsCount);						
						if($comments!='NA') $comments=sizeof($comments);
						else $comments=0;
						
						$stories[$key]['story_id']=$str->story_id;
						$stories[$key]['story_url']=$str->story_url;
						$stories[$key]['story_thumb_url']=$str->story_thumb_url;
						$stories[$key]['story_description']=$str->story_description;
						$stories[$key]['music_cover_title']=$str->music_cover_title;
						$stories[$key]['music_cover_image_link']=$str->music_cover_image_link;
						$stories[$key]['status']=$str->status;
						$stories[$key]['added_date']=$str->added_date;
						$stories[$key]['user_id']=$str->user_id;
						$stories[$key]['likes']=$likes;
						$stories[$key]['comments']=$comments;
						$stories[$key]['userProfilePicUrl']=$user_info[0]->profile_image;
						$stories[$key]['userName']=$user_info[0]->username;
											
					}
					$response=array(
						"Result"=>$stories,
						"Message"=>"Data fetched succesfully",
						"status"=>true,
						"total_records"=>$story_count
				);
					}
					else{

							$response=array(
						"Result"=>'',
						"Message"=>"No Data fetched",
						"status"=>false,
						"total_records"=>$story_count
				);

					}	
			
				

			}
			else
			{
				$response=array(
						"Result"=>'',
						"Message"=>"Stories not found",
						"status"=>false
				);
			}
		}
		else
			{
				
				$response=array(
						"Result"=>'',
						"Message"=>"Profile not found",
						"status"=>false
				);
				
			}
		  
	  }
}

header('Content-type: application/json');
echo json_encode(array("Response"=>$response));
}


function like_story()
{
	
  $method = $_SERVER['REQUEST_METHOD'];
    $headers = $this->input->request_headers();
    
		if($method !== 'POST'){
			$response=array(
						"Result"=>"",
						"Message"=>"Bad request.",
						"status"=>false
				);	
			//json_output(400,array('status' => 400,'message' => 'Bad request.'));
		}else{
			
	  if(isset($headers['Authtoken'])){
        $token = $headers['Authtoken'];
      }else{
        $token = $headers['authtoken'];
      }
      $check_token_validity = Modules::run('security/validateAuthToken',$token);
	   if($check_token_validity['status'] === "invalid")
	  {
        //json_output(200, array('status' => 'invalid token'));
		$response=array(
						"Result"=>'',
						"Message"=>"invalid token",
						"status"=>false
				);
      }
	  else if($check_token_validity['status'] === "expired")
	  {
       // json_output(200, array('status' => 'expired'));
	   $response=array(
						"Result"=>'',
						"Message"=>"invalid expired",
						"status"=>false
				);
      }
	  else if($check_token_validity['status'] === "valid")
	  {
		  $content = json_decode(file_get_contents('php://input'), TRUE);
	  //print_r($content);exit;
		$user_id=$content['user_id'];
		$story_id=$content['story_id'];
		$like=$content['like'];
   
		//---------------languages--------------------
		$ifLikeExist=$this->Mdl_services->isExist("user_story_likes", array('user_id'=>$user_id,'story_id'=>$story_id));
		if($ifLikeExist==false)
		{
                $likes_array = array(
                  "user_id" => $user_id,                 
                  "story_id" => $story_id,
				  "is_liked" => $like,
				  "ip_address" =>$_SERVER['REMOTE_ADDR'] ,
                  "added_date" => date("Y-m-d H:i:s"),
				  "modified_date" => date("Y-m-d H:i:s")
                );
                $insert_like = $this->Mdl_services->insert("user_story_likes",$likes_array);
				$response=array(
						"Result"=>'',
						"Message"=>"Like inserted.",
						"status"=>true
				);
        }
		else{
			
				$likes_array = array(
                  "is_liked" => $like,
				  "ip_address" =>$_SERVER['REMOTE_ADDR'] ,				  
				  "modified_date" => date("Y-m-d H:i:s")
                );
                $insert_like = $this->Mdl_services->update("user_story_likes",array('user_id'=>$user_id,'story_id'=>$story_id),$likes_array);
				$response=array(
						"Result"=>'',
						"Message"=>"Like updated.",
						"status"=>true
				);	
			
		}    
		} 
		  
		  
	  }
    
      
		header('Content-type: application/json');
			echo json_encode(array("Response"=>$response));
  }
  
function comment_story()
{
	
  $method = $_SERVER['REQUEST_METHOD'];
    $headers = $this->input->request_headers();
    
		if($method !== 'POST'){
			$response=array(
						"Result"=>"",
						"Message"=>"Bad request.",
						"status"=>false
				);	
			//json_output(400,array('status' => 400,'message' => 'Bad request.'));
		}else{
			
	  if(isset($headers['Authtoken'])){
        $token = $headers['Authtoken'];
      }else{
        $token = $headers['authtoken'];
      }
      $check_token_validity = Modules::run('security/validateAuthToken',$token);
	   if($check_token_validity['status'] === "invalid")
	  {
        //json_output(200, array('status' => 'invalid token'));
		$response=array(
						"Result"=>'',
						"Message"=>"invalid token",
						"status"=>false
				);
      }
	  else if($check_token_validity['status'] === "expired")
	  {
       // json_output(200, array('status' => 'expired'));
	   $response=array(
						"Result"=>'',
						"Message"=>"invalid expired",
						"status"=>false
				);
      }
	  else if($check_token_validity['status'] === "valid")
	  {
		  $content = json_decode(file_get_contents('php://input'), TRUE);
	  //print_r($content);exit;
		$user_id=$content['user_id'];
		$story_id=$content['story_id'];
		$comment=$content['comment'];
		$comment_id=$content['comment_id'];//check if parent comment is there-threaded comment
		
		if($comment=='')
		{
			
			$response=array(
			"Result"=>'',
			"Message"=>"Please enter comment.",
			"status"=>false
		);
			
		}
		else
		{
			
				$comment_array = array(
				"user_id" => $user_id,                 
				"story_id" => $story_id,
				"comment" => (trim($comment)),
				"parent_id" => $comment_id,
				"is_spam"=>'0',
				"status"=>'Active',
				"ip_address" =>$_SERVER['REMOTE_ADDR'] ,
				"added_date" => date("Y-m-d H:i:s"),
				"modified_date" => date("Y-m-d H:i:s")
			);
			$insert_like = $this->Mdl_services->insert("user_story_comments",$comment_array);
			$response=array(
				"Result"=>'',
				"Message"=>"Comment inserted.",
				"status"=>true
			);
			
			
		}
   
		
		
      
		} 
		  
		  
	  }
    
      
		header('Content-type: application/json');
			echo json_encode(array("Response"=>$response));
  }
  
  
}
?>
