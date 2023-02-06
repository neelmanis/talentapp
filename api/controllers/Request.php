<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH.'modules/generic/controllers/Generic.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST,GET,PUT,DELETE");
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authtoken');
header("Access-Control-Max-Age: 86000");

class Request extends Generic{

  function __construct() {
    parent::__construct();
    $this->load->model('Mdl_api');		
  }

  // public function test(){
    
  //   $details = "ما هو لوريم إيبسوم لوريم إيبسوم هو ببساطة نص وهمي لصناعة الطباعة والتنضيد. كان لوريم إيبسوم هو النص الوهمي القياسي في الصناعة منذ القرن الخامس عشر↵الميلادي عندما أخذت طابعة غير معروفة لوحًا من النوع وتدافعت عليه لعمل عينة كتابما هو لوريم إيبسوم لوريم إيبسوم هو ببساطة نص وهمم";
    
  //   echo mb_strlen($details,'UTF-8');
  // }

  /**
   * New Request
   */
	public function add(){
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
    
        $content = json_decode(file_get_contents('php://input'), TRUE);
        $this->form_validation->set_data($content);
        $talent_uid = $content['talent_uid'];
        $custom_errors = array();
    
        $talent_details_query = "SELECT t.talent_id, t.halagram_price, t.platform_commision, t.talent_share FROM talent_details t INNER JOIN registration r ON r.registration_id = t.registration_id WHERE r.uid='$talent_uid' ";
        $talent_details = $this->Mdl_api->customQuery($talent_details_query);
    
        if($talent_details !== "NA"){
          $this->form_validation->set_rules("serviceType","Service Type","trim|xss_clean|required|in_list[nugget-of-wisdom,shout-out]",
          array(
            'required' => "request-service-required"
          ));

          if($content['serviceType'] !== 'nugget-of-wisdom'){
          
            $this->form_validation->set_rules("requestTo","Request To","trim|xss_clean|required",
            array(
              'required' => "field-required"
            ));
          
            $this->form_validation->set_rules("requestFrom","Request From","trim|xss_clean|required",
            array(
              'required' => "field-required"
            ));
          
            $this->form_validation->set_rules("occasion","Occasion","trim|xss_clean|required",
            array(
              'required' => "request-occasion-required"
            ));
          }
          
          $this->form_validation->set_rules("details","Details","trim|xss_clean|required",
          array(
            'required' => "request-details-required"
          ));
          
          if($content['details'] !== ""){
            $detailsLen =  $content['lang'] == "en" ? strlen($content['details']) : mb_strlen($content['details'],'UTF-8'); 
            if($detailsLen > 275){
              $custom_errors['details'] = 'request-details-maxlength';
            }
          }

          $this->form_validation->set_rules("reference","Reference Link","trim|xss_clean");
          $this->form_validation->set_rules("shareHalagram","Share Halagram","trim|xss_clean|required|in_list[yes,no]",
          array(
            'required' => "select-option"
          ));
          
          if($this->form_validation->run($this) == FALSE){
            $errors = $this->form_validation->error_array();
            $final_array = array_merge($errors,$custom_errors);
            json_output(200,array('status'=>'error','errorData'=>$final_array));
          }else{
            if(! empty($custom_errors)){
              json_output(200,array('status'=>'error','errorData'=>$custom_errors));
            }
          
            $orderId = $this->getOrderId();
            $total_price = (float)$talent_details[0]->halagram_price;
            $platform_share = round((float)$total_price * ( (float)$talent_details[0]->platform_commision/100));
            $talent_share = $total_price - $platform_share;

            $request_data = array(
              "order_id" => $orderId,
              "type" => $type,
              "registration_id" => $registration_id,
              "uid" => $uid,
              "talent_id" => $talent_details[0]->talent_id,
              "service_type" => strip_tags($content['serviceType']),
              "occasion" => strip_tags($content['occasion']),
              "request_to" => strip_tags($content['requestTo']),
              "request_from" => strip_tags($content['requestFrom']),
              "details" => strip_tags($content['details']),
              "reference_link" => strip_tags($content['reference']),
              "share_halagram" => strip_tags($content['shareHalagram']),
              "share_halagram_talent" => strip_tags($content['shareHalagram']),
              "halagram_link" => '',
              "share_reaction" => '',
              "reaction_link" => '',
              "halagram_price" => $total_price,
              "platform_share" => $platform_share,
              "talent_share" => $talent_share,
              "request_status" => 'created',
              "payment_status" => 'pending',
              "talent_payment_status" => 'pending',
              'payment_ref' => null,
              "request_date" => null,
              "expiry_date" => null,
              "completion_date" => null,
              "complition_duration" => null,
              "key" => null,
              "halagram_thumbnail" => null,
              "reaction_thumbnail" => null,
              "created_date" => date("Y-m-d H:i:s"),
              "modified_date" => date("Y-m-d H:i:s")
            );
            $insert = $this->Mdl_api->insert("request", $request_data);

            json_output(200,array('status'=>'success', 'orderId'=>$orderId));
          }
        }else{
          json_output(200, array('status' => 'fail'));
        }
      }
		}
  }

  /**
   * Talent requests 
   */
  public function all(){
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
        $curr_date = date("Y-m-d H:i:s");
     
        $GET_REQUEST_QUERY = "SELECT r.order_id, r.type, r.registration_id, r.uid, r.service_type, r.request_from, r.occasion, r.created_date, r.request_date, r.expiry_date FROM request r INNER JOIN talent_details t ON t.talent_id = r.talent_id WHERE t.registration_id=$registration_id AND r.request_status='confirmed' AND r.payment_status='hold' AND r.expiry_date >= '$curr_date' ORDER BY request_date asc ";
        $request = $this->Mdl_api->customQuery($GET_REQUEST_QUERY);
        
        if($request !== "NA"){
          $response = array();
          foreach($request as $val){
            $occasionEn = $occasionAr = '';
            $nameEn = $nameAr = '';

            if($val->type == "talent"){
              $GET_USER_DETAILS = "SELECT t.fullname_en, t.fullname_ar FROM talent_details t INNER JOIN registration r ON r.registration_id = t.registration_id WHERE r.uid='$val->uid' ";
              $user_details = $this->Mdl_api->customQuery($GET_USER_DETAILS);
              
              if($user_details !== "NA"){
                $nameEn = $user_details[0]->fullname_en;
                $nameAr = $user_details[0]->fullname_ar;
              }
            }else{
              $GET_USER_DETAILS = "SELECT u.fullname FROM user_details u INNER JOIN registration r ON r.registration_id = u.registration_id WHERE r.uid='$val->uid' ";
              $user_details = $this->Mdl_api->customQuery($GET_USER_DETAILS);
              
              if($user_details !== "NA"){
                $nameEn = $user_details[0]->fullname;
                $nameAr = $user_details[0]->fullname;
              }
            }
            
            if($val->occasion !== '' && $val->service_type == 'shout-out'){
              $GET_OCCASION = "SELECT occasion_name_en, occasion_name_ar FROM occasion_master WHERE occasion_id='$val->occasion' ";
					    $occasion = $this->Mdl_api->customQuery($GET_OCCASION);
              if($occasion !== "NA"){
                $occasionEn = $occasion[0]->occasion_name_en;
                $occasionAr = $occasion[0]->occasion_name_ar;
              }
            }
            
            $temp = array();
            $temp['orderId'] = $val->order_id;
            $temp['nameEn'] = $nameEn;
            $temp['nameAr'] = $nameAr;
            $temp['serviceType'] = $val->service_type;
            $temp['requestFrom'] = $val->request_from;
            $temp['occasionEn'] = $occasionEn;
            $temp['occasionAr'] = $occasionAr;
            $temp['requestDate'] = date("d/m/Y",strtotime($val->request_date));
            $req_time = strtotime("now");
            $exp_time = strtotime($val->expiry_date);
            $remaining = $exp_time - $req_time;
            $days = round($remaining / 86400);
            $temp['daysRemain'] = $days;
            $response[] = $temp;
          }
          json_output(200, array("status"=>"success","requests"=>$response));
        }else{
          json_output(200, array("status"=>"no data"));
        }
      }
    }
  }

  /**
   * Request Details 
   */
  public function details(){
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
        
        $content = $_GET;
        $orderId = $content['orderId'];
        
        $GET_REQUEST_QUERY = "SELECT r.order_id, r.service_type, r.request_to, r.request_from, r.occasion, r.details, r.reference_link, r.share_halagram, r.created_date, r.request_date FROM request r INNER JOIN talent_details t ON t.talent_id = r.talent_id WHERE t.registration_id='$registration_id' AND r.order_id='$orderId' ";
        $request = $this->Mdl_api->customQuery($GET_REQUEST_QUERY);
        
        if($request !== "NA"){
          $response = array();
          $occasionEn = $occasionAr = '';
          $service_type = 'Nugget of Wisdom';
          $occasionId = $request[0]->occasion !== '' ? $request[0]->occasion : 0;
          
          if($request[0]->occasion !== '' && $request[0]->service_type == 'shout-out'){
            $GET_OCCASION = "SELECT occasion_name_en, occasion_name_ar FROM occasion_master WHERE occasion_id='$occasionId' ";
            $occasion = $this->Mdl_api->customQuery($GET_OCCASION);
            if($occasion !== "NA"){
              $occasionEn = $occasion[0]->occasion_name_en;
              $occasionAr = $occasion[0]->occasion_name_ar;
            }
          }
          
          if($request[0]->service_type == 'shout-out'){
            $service_type = 'Shout Out';
          }
          
          $response['orderId'] = $request[0]->order_id;
          $response['serviceType'] = $service_type;
          $response['requestTo'] = $request[0]->request_to;
          $response['requestFrom'] = $request[0]->request_from;
          $response['details'] = $request[0]->details;
          $response['referenceLink'] = $request[0]->reference_link;
          $response['shareHalagram'] = $request[0]->share_halagram;
          $response['occasionEn'] = $occasionEn;
          $response['occasionAr'] = $occasionAr;
          $response['requestDate'] = date("d/m/Y",strtotime($request[0]->request_date));
          json_output(200, array("status"=>"success","details"=>$response));

        }else{
          json_output(200, array("status"=>"no data"));
        }
      }
    }
  }

  /**
   *   Upload Halagram video 
   */
  public function uploadHalagram(){
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
      
        $get_talent_details = "SELECT t.talent_id FROM talent_details t INNER JOIN registration r ON r.registration_id = t.registration_id WHERE r.uid = '$uid' ";
        $talent_details = $this->Mdl_api->customQuery($get_talent_details);
        
        if($talent_details == "NA"){
          json_output(200, array('status' => 'invalid token'));
        }
        
        $content = $this->input->post();
        $orderId = $content['orderId'];
        $isMobile = $content['isMobile'];
        $curr_date = date("Y-m-d H:i:s");
        
        $request = $this->Mdl_api->retrieve('request', array('order_id'=>$orderId, 'talent_id'=>$talent_details[0]->talent_id, "request_status"=>"confirmed", "expiry_date >="=>$curr_date));
        
        if($request !== "NA"){
          $video_allowed_type = array('webm','WEBM','video/webm');
          if($_FILES['halagramVideo']['name'] !== "" ){
            $filename = $_FILES['halagramVideo']['name'];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);

            if(! in_array($ext, $video_allowed_type)){
              json_output(200, array('status'=>'error', 'message'=>'invalid-video-type'));
            }else if($_FILES['halagramVideo']['size'] > 100000000){
              json_output(200, array('status'=>'error', 'message'=>'video-file-size'));
            }
          }else{
            json_output(200, array('status'=>'error', 'message'=>'record-video'));
          }  

          // $videoname = 'raw-'.$orderId.'.webm';
          $videoname = 'raw-'.strtotime('now').$orderId.'.webm';
          $uploadPath = './assets/video/';
          $upload = $this->uploadFile($videoname,$uploadPath,'0','halagramVideo');

          if($upload !== 1){
            json_output(200, array('status'=>'error', 'message'=> 'upload-failed' ));
          }else{
             
            $raw_video = 'assets/video/'.$videoname;
            $final_video = 'assets/video/hala-'.$orderId.'.mp4';
            $new_name = 'video-'.strtotime('now').'.mp4';
            $key = $uid.'/'.$new_name;
            
            $upload_response = $this->addWatermark($raw_video, $final_video, $key, $isMobile);

            if($upload_response['upload']){
              $duration = strtotime("now") - strtotime($request[0]->request_date);
              $updated_request = array(
                'halagram_link' => $upload_response['path'],
                'request_status' => 'complete',
                'payment_status' => 'captured',
                'is_release' => 'no',
                'completion_date' => date("Y-m-d H:i:s"),
                'complition_duration' => $duration,
                'modified_date' => date("Y-m-d H:i:s")
              );
              $update_request = $this->Mdl_api->update("request",array('request_id' => $request[0]->request_id),$updated_request);
              
              $lang = $fullname = $email = '';
              $registrationId = $request[0]->registration_id;

              if($request[0]->type == 'user'){
                $get_user_details = "SELECT lang, fullname, email FROM user_details WHERE registration_id='$registrationId' ";
                $user_details = $this->Mdl_api->customQuery($get_user_details);
    
                if($user_details !== "NA"){
                  $lang = $user_details[0]->lang;
                  $fullname = $user_details[0]->fullname;
                  $email = $user_details[0]->email;
                }
              }else{
                $get_user_details = "SELECT lang, fullname_en, fullname_ar, email FROM talent_details WHERE registration_id='$registrationId' ";
                $user_details = $this->Mdl_api->customQuery($get_user_details);
    
                if($user_details !== "NA"){
                  $lang = $user_details[0]->lang;
                  $fullname = $lang == 'en' ? $user_details[0]->fullname_en : $user_details[0]->fullname_ar;
                  $email = $user_details[0]->email;
                }
              }

              if($user_details !== "NA"){
                // $subject = $lang == 'en' ? 'Download Your HalaGram' : 'حمل هلاجرامك';
                // $mail_view_file = 'download-'.$lang;

                $download_link = $this->global_variables['front_end_url'].'download/'.$orderId;
                $mail_data = array(
                  // 'view_file' => $mail_view_file,
                  // 'subject' => $subject,
                  'template' => "download halagram",
                  'lang' => $lang,
                  'to' => $email,
                  'cc' => '',
                  'bcc' => '',
                  'halagram_logo' => base_url()."assets/images/HalaLogo.png",
                  'isAttachment' => false,
                  'name' => $fullname,
                  'download_link' => $download_link
                );
                Modules::run('email/template',$mail_data);
              }

              json_output(200, array('status'=>'success'));
            }else{
              json_output(200, array('status'=>'error', 'message'=>'upload-failed'));
            }
          }
        }else{
          json_output(200, array('status'=>'fail'));
        }
      }
    }
  }

  /**
   *  Upload Reaction video
   */
  public function uploadReaction(){
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
      
        $content = $this->input->post();
        $orderId = $content['orderId'];
        $isMobile = $content['isMobile'];

        $request = $this->Mdl_api->retrieve('request', array('order_id'=>$orderId,  "request_status"=>"complete"));
      
        if($request !== "NA"){
          $video_allowed_type = array('webm','WEBM','video/webm');
      
          if($_FILES['reactionVideo']['name'] !== "" ){
            $filename = $_FILES['reactionVideo']['name'];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            if(! in_array($ext, $video_allowed_type)){
              json_output(200, array('status'=>'error', 'message'=>'invalid-video-type'));
            }else if($_FILES['reactionVideo']['size'] > 100000000){
              json_output(200, array('status'=>'error', 'message'=>'video-file-size'));
            }
          }else{
            json_output(200, array('status'=>'error', 'message'=>'record-video'));
          }  
      
          // $videoname = 'rct-raw-'.$orderId.'.webm';
          $videoname = 'rct-raw-'.strtotime('now').$orderId.'.webm';
          $uploadPath = './assets/video/';
          $upload = $this->uploadFile($videoname,$uploadPath,'0','reactionVideo');

          if($upload !== 1){
            // 'upload-failed'
            json_output(200, array('status'=>'error', 'message'=> 'upload-failed' ));
          }else{
          
            $raw_video = 'assets/video/'.$videoname;
            $final_video = 'assets/video/reaction-'.$orderId.'.mp4';
            $new_name = 'video-'.strtotime('now').'.mp4';
            $key = $uid.'/'.$new_name;
            
            $upload_response = $this->addWatermark($raw_video, $final_video, $key, $isMobile);

            if($upload_response['upload']){
              $updated_request = array(
                'reaction_link' => $upload_response['path'],
                'reaction_read' => 'no',
                'modified_date' => date("Y-m-d H:i:s")
              );
              $update_request = $this->Mdl_api->update("request",array('request_id' => $request[0]->request_id),$updated_request);

              json_output(200, array('status'=>'success', 'reactionLink'=>$upload_response['path'] ));
            }else{
              json_output(200, array('status'=>'error', 'message'=>'upload-failed'));
            }
          }
        }else{
          json_output(200, array('status'=>'fail'));
        }
      }
    }
  }
  
  /**
   * Filter request 
   */
  public function download(){
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
    
        $content = $_GET;
        $orderId = $content['orderId'];
    
        $GET_REQUEST_QUERY = "SELECT halagram_link, reaction_link, halagram_thumbnail FROM request WHERE registration_id='$registration_id' AND type='$type' AND uid='$uid' AND order_id='$orderId' AND  request_status='complete' ";
        $request = $this->Mdl_api->customQuery($GET_REQUEST_QUERY);

        if($request !== "NA"){
          $thumbImage = base_url().$request[0]->halagram_thumbnail;
          json_output(200, array("status"=>"success","halagramLink"=>$request[0]->halagram_link, "thumbnail"=>$thumbImage, "reactionLink"=>$request[0]->reaction_link ));
        }else{
          json_output(200, array("status"=>"no data"));
        }
      }
    }
  }

  /**
   *  Change Halagram / Reaction status
   */
  public function changeStatus(){
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
      
        $content = json_decode(file_get_contents('php://input'), TRUE);

        $orderId = $content['orderId'];
        $videoType = $content['type'];
        $status = $content['status'];
      
        $request = $this->Mdl_api->retrieve('request', array('order_id'=>$orderId,  "request_status"=>"complete"));
      
        if($request !== "NA"){
          if($videoType == 'reaction'){
            $updatedReaction = array(
              'share_reaction' => $status,
              'modified_date' => date('Y-m-d H:i:s')
            );
          }else{
            $updatedReaction = array(
              'share_halagram_talent' => $status,
              'modified_date' => date('Y-m-d H:i:s')
            );
          }
          $update = $this->Mdl_api->update('request', array('order_id'=>$orderId), $updatedReaction);
          json_output(200, array('status'=>'success'));
        }else{
          json_output(200, array('status'=>'fail'));
        }
      }
    } 
  }

  public function halagram(){
    $method = $_SERVER['REQUEST_METHOD'];
    $headers = $this->input->request_headers();

		if($method !== 'GET'){
			json_output(400, array('status' => 400,'message' => 'Bad request.'));
		}else{
    
      $content = $_GET;
      $orderId = $content['orderId'];
    
      $GET_REQUEST_QUERY = "SELECT halagram_link, halagram_thumbnail FROM request WHERE order_id='$orderId' AND  request_status='complete' ";
      $request = $this->Mdl_api->customQuery($GET_REQUEST_QUERY);

      if($request !== "NA"){
        $thumbImage = base_url().$request[0]->halagram_thumbnail;
        json_output(200, array("status"=>"success","halagramLink"=>$request[0]->halagram_link, "thumbnail"=>$thumbImage ));
      }else{
        json_output(200, array("status"=>"no data"));
      }
    }
  }

  /**
   * Completed request 
   */
  // public function completed(){
  //   $method = $_SERVER['REQUEST_METHOD'];
  //   $headers = $this->input->request_headers();
    
  //   if($method !== 'GET'){
	// 		json_output(400, array('status' => 400,'message' => 'Bad request.'));
	// 	}else{
    
  //     if(isset($headers['Authtoken'])){
  //       $token = $headers['Authtoken'];
  //     }else{
  //       $token = $headers['authtoken'];
  //     }
  //     $check_token_validity = Modules::run('security/validateAuthToken',$token);
    
  //     if($check_token_validity['status'] === "invalid"){
  //       json_output(200, array('status' => 'invalid token'));
  //     }else if($check_token_validity['status'] === "expired"){
  //       json_output(200, array('status' => 'expired'));
  //     }else if($check_token_validity['status'] === "valid"){
    
  //       $registration_id = $check_token_validity['registration_id'];
  //       $uid = $check_token_validity['uid'];
  //       $type = $check_token_validity['type'];
  //       $curr_date = date("Y-m-d H:i:s");
    
  //       $get_talent_details = "SELECT talent_id FROM talent_details WHERE registration_id='$registration_id' ";
  //       $talent = $this->Mdl_api->customQuery($get_talent_details);

  //       if($talent !== "NA"){
  //         $request_data = array(
  //           "reaction_read" => "yes"
  //         );
  //         $update = $this->Mdl_api->update("request", array("talent_id"=>$talent[0]->talent_id, "reaction_read" => "no"), $request_data);
  //       }

  //       $GET_REQUEST_QUERY = "SELECT r.order_id, r.type, r.service_type, r.registration_id, r.uid, r.occasion, r.halagram_link, r.reaction_link, r.share_halagram, r.share_reaction, r.share_halagram_talent, r.halagram_price, r.platform_share, r.talent_share, r.talent_payment_status, r.created_date, r.request_date FROM request r INNER JOIN talent_details t ON t.talent_id = r.talent_id WHERE t.registration_id='$registration_id' AND r.request_status='complete' AND r.payment_status='captured' ORDER BY created_date desc";
  //       $request = $this->Mdl_api->customQuery($GET_REQUEST_QUERY);
    
  //       $requestCount = 0;
  //       $reactionCount = 0;
  //       $totalEarning = 0;
  //       $totalPaid = 0;
  //       $totalPending = 0;

  //       if($request !== "NA"){
  //         $response = array();
  //         foreach($request as $val){
  //           $occasionEn = $occasionAr = '';
  //           $nameEn = $nameAr = '';

  //           if($val->type == "talent"){
  //             $GET_USER_DETAILS = "SELECT t.fullname_en, t.fullname_ar FROM talent_details t INNER JOIN registration r ON r.registration_id = t.registration_id WHERE r.uid='$val->uid' ";
  //             $user_details = $this->Mdl_api->customQuery($GET_USER_DETAILS);
              
  //             if($user_details !== "NA"){
  //               $nameEn = $user_details[0]->fullname_en;
  //               $nameAr = $user_details[0]->fullname_ar;
  //             }
  //           }else{
  //             $GET_USER_DETAILS = "SELECT u.fullname FROM user_details u INNER JOIN registration r ON r.registration_id = u.registration_id WHERE r.uid='$val->uid' ";
  //             $user_details = $this->Mdl_api->customQuery($GET_USER_DETAILS);
              
  //             if($user_details !== "NA"){
  //               $nameEn = $user_details[0]->fullname;
  //               $nameAr = $user_details[0]->fullname;
  //             }
  //           }

  //           if($val->occasion !== '' && $val->service_type == 'shout-out'){
  //             $GET_OCCASION = "SELECT occasion_name_en, occasion_name_ar FROM occasion_master WHERE occasion_id='$val->occasion' ";
	// 				    $occasion = $this->Mdl_api->customQuery($GET_OCCASION);
  //             if($occasion !== "NA"){
  //               $occasionEn = $occasion[0]->occasion_name_en;
  //               $occasionAr = $occasion[0]->occasion_name_ar;
  //             }
  //           }

  //           if($val->service_type == 'shout-out'){
  //             $serviceType = 'Shout Out';
  //           }else{
  //             $serviceType = 'Nugget of Wisdom';
  //           }

  //           $temp = array();
  //           $temp['orderId'] = $val->order_id;
  //           $temp['nameEn'] = $nameEn;
  //           $temp['nameAr'] = $nameAr;
  //           $temp['serviceType'] = $serviceType;
  //           $temp['occasionEn'] = $occasionEn;
  //           $temp['occasionAr'] = $occasionAr;
  //           $temp['halagramLink'] = $val->halagram_link;
  //           $temp['reactionLink'] = $val->reaction_link;
  //           $temp['shareHalagram'] = $val->share_halagram;
  //           $temp['shareHalagramTalent'] = $val->share_halagram_talent;
  //           $temp['shareReaction'] = $val->share_reaction;
  //           $temp['halagramPrice'] = $val->halagram_price;
  //           $temp['platformShare'] = $val->platform_share;
  //           $temp['talentShare'] = $val->talent_share;
  //           $temp['paymentStatus'] = $val->talent_payment_status;
  //           $temp['requestDate'] = date("d/m/Y",strtotime($val->request_date));
  //           $response[] = $temp;
  //           $requestCount += 1;

  //           if($val->reaction_link !== ''){
  //             $reactionCount += 1;
  //           }
  //           $totalEarning += $val->talent_share;
            
  //           if($val->talent_payment_status == 'paid'){
  //             $totalPaid += $val->talent_share;
  //           }else{
  //             $totalPending += $val->talent_share;
  //           }
  //         }
          
  //         json_output(200, array("status"=>"success","requests"=>$response,"requestCount"=>$requestCount, "reactionCount"=>$reactionCount, "totalEarning"=>$totalEarning, "totalPaid"=>$totalPaid, "totalPending"=>$totalPending ));
  //       }else{
  //         json_output(200, array("status"=>"no data"));
  //       }
  //     }
  //   }
  // }

  /**
   * Filter request 
   */
  // public function byFilter(){
  //   $method = $_SERVER['REQUEST_METHOD'];
  //   $headers = $this->input->request_headers();

	// 	if($method !== 'GET'){
	// 		json_output(400, array('status' => 400,'message' => 'Bad request.'));
	// 	}else{
    
  //     if(isset($headers['Authtoken'])){
  //       $token = $headers['Authtoken'];
  //     }else{
  //       $token = $headers['authtoken'];
  //     }
  //     $check_token_validity = Modules::run('security/validateAuthToken',$token);
    
  //     if($check_token_validity['status'] === "invalid"){
  //       json_output(200, array('status' => 'invalid token'));
  //     }else if($check_token_validity['status'] === "expired"){
  //       json_output(200, array('status' => 'expired'));
  //     }else if($check_token_validity['status'] === "valid"){
    
  //       $registration_id = $check_token_validity['registration_id'];
  //       $uid = $check_token_validity['uid'];
  //       $type = $check_token_validity['type'];
    
  //       $content = $_GET;
  //       $dateFrom = $content['dateFrom'];
  //       $dateTo = $content['dateTo'];
  //       $occasion = $content['occasion'];
    
  //       if(isset($_GET['service'])){
  //         $service = $content['service'];
  //       }else{
  //         $service = '';
  //       }

  //       $GET_REQUEST_QUERY = "SELECT r.order_id, r.type, r.service_type, r.registration_id, r.uid, r.occasion, r.halagram_link, r.reaction_link, r.share_halagram, r.share_halagram_talent, r.share_reaction, r.halagram_price, r.platform_share, r.talent_share, r.talent_payment_status, r.created_date, r.request_date FROM request r INNER JOIN talent_details t ON t.talent_id = r.talent_id WHERE t.registration_id='$registration_id' AND r.request_status='complete' AND r.payment_status='captured' ";

  //       if($occasion !== ''){
  //         $GET_REQUEST_QUERY .= ' AND r.occasion=\''.$occasion.'\'';
  //       }

  //       if($dateFrom !== ''){
  //         $date_from = date('Y-m-d H:i:s',strtotime($dateFrom.' 00:00:00'));
  //         $GET_REQUEST_QUERY .= ' AND r.created_date >= \''.$date_from.'\'';
  //       }

  //       if($dateTo !== ''){
  //         $date_to = date('Y-m-d H:i:s',strtotime($dateTo.' 23:59:59'));
  //         $GET_REQUEST_QUERY .= ' AND r.created_date <= \''.$date_to.'\'';
  //       }

  //       if($service !== ''){
  //         $GET_REQUEST_QUERY .= ' AND r.service_type = \''.$service.'\'';
  //       }

  //       $GET_REQUEST_QUERY .= ' ORDER BY created_date desc';
        
  //       $request = $this->Mdl_api->customQuery($GET_REQUEST_QUERY);
  //       $requestCount = 0;
  //       $reactionCount = 0;
  //       $totalEarning = 0;
  //       $totalPaid = 0;
  //       $totalPending = 0;

  //       if($request !== "NA"){
  //         $response = array();
  //         foreach($request as $val){
  //           $occasionEn = $occasionAr = '';
  //           $nameEn = $nameAr = '';

  //           if($val->type == "talent"){
  //             $GET_USER_DETAILS = "SELECT t.fullname_en, t.fullname_ar FROM talent_details t INNER JOIN registration r ON r.registration_id = t.registration_id WHERE r.uid='$val->uid' ";
  //             $user_details = $this->Mdl_api->customQuery($GET_USER_DETAILS);
              
  //             if($user_details !== "NA"){
  //               $nameEn = $user_details[0]->fullname_en;
  //               $nameAr = $user_details[0]->fullname_ar;
  //             }
  //           }else{
  //             $GET_USER_DETAILS = "SELECT u.fullname FROM user_details u INNER JOIN registration r ON r.registration_id = u.registration_id WHERE r.uid='$val->uid' ";
  //             $user_details = $this->Mdl_api->customQuery($GET_USER_DETAILS);
              
  //             if($user_details !== "NA"){
  //               $nameEn = $user_details[0]->fullname;
  //               $nameAr = $user_details[0]->fullname;
  //             }
  //           }

  //           if($val->occasion !== '' && $val->service_type == 'shout-out'){
  //             $GET_OCCASION = "SELECT occasion_name_en, occasion_name_ar FROM occasion_master WHERE occasion_id='$val->occasion' ";
	// 				    $occasion = $this->Mdl_api->customQuery($GET_OCCASION);
              
  //             if($occasion !== "NA"){
  //               $occasionEn = $occasion[0]->occasion_name_en;
  //               $occasionAr = $occasion[0]->occasion_name_ar;
  //             }
  //           }

  //           if($val->service_type == 'shout-out'){
  //             $serviceType = 'Shout out';
  //           }else{
  //             $serviceType = 'Nugget of Wisdom';
  //           }
            
  //           $temp = array();
  //           $temp['orderId'] = $val->order_id;
  //           $temp['nameEn'] = $nameEn;
  //           $temp['nameAr'] = $nameAr;
  //           $temp['serviceType'] = $serviceType;
  //           $temp['occasionEn'] = $occasionEn;
  //           $temp['occasionAr'] = $occasionAr;
  //           $temp['halagramLink'] = $val->halagram_link;
  //           $temp['reactionLink'] = $val->reaction_link;
  //           $temp['shareHalagram'] = $val->share_halagram;
  //           $temp['shareHalagramTalent'] = $val->share_halagram_talent;
  //           $temp['shareReaction'] = $val->share_reaction;
  //           $temp['halagramPrice'] = $val->halagram_price;
  //           $temp['platformShare'] = $val->platform_share;
  //           $temp['talentShare'] = $val->talent_share;
  //           $temp['paymentStatus'] = $val->talent_payment_status;
  //           $temp['requestDate'] = date("d/m/Y",strtotime($val->request_date));
  //           $response[] = $temp;
            
  //           $requestCount += 1;
  //           if($val->reaction_link !== ''){
  //             $reactionCount += 1;
  //           }
  //           $totalEarning += $val->talent_share;
            
  //           if($val->talent_payment_status == 'paid'){
  //             $totalPaid += $val->talent_share;
  //           }else{
  //             $totalPending += $val->talent_share;
  //           }
  //         }

  //         json_output(200, array("status"=>"success","requests"=>$response,"requestCount"=>$requestCount, "reactionCount"=>$reactionCount, "totalEarning"=>$totalEarning, "totalPaid"=>$totalPaid, "totalPending"=>$totalPending ));
  //       }else{
  //         json_output(200, array("status"=>"no data"));
  //       }
  //     }
  //   }
  // }

  public function counter(){
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
    
        $content = $_GET;
        
        $dateFrom = isset($content['dateFrom']) && $content['dateFrom'] !== "" ? $content['dateFrom'] : "";
        $dateTo = isset($content['dateTo']) && $content['dateTo'] !== "" ? $content['dateTo'] : "";
        $occasion = isset($content['occasion']) && $content['occasion'] !== "" ? $content['occasion'] : "";
        $service = isset($content['service']) && $content['service'] !== "" ? $content['service'] : "";

        $query_to_retrieve_completed_requests = "SELECT r.order_id, r.reaction_link FROM request r INNER JOIN talent_details t ON t.talent_id = r.talent_id WHERE t.registration_id='$registration_id' AND r.request_status='complete' AND r.payment_status='captured' ";

        if($occasion !== ''){
          $query_to_retrieve_completed_requests .= " AND r.occasion='$occasion' ";
        }

        if($dateFrom !== ''){
          $date_from = date('Y-m-d H:i:s',strtotime($dateFrom.' 00:00:00'));
          $query_to_retrieve_completed_requests .= " AND r.created_date >= '$date_from' ";
        }

        if($dateTo !== ''){
          $date_to = date('Y-m-d H:i:s',strtotime($dateTo.' 23:59:59'));
          $query_to_retrieve_completed_requests .= " AND r.created_date <= '$date_to' ";
        }

        if($service !== ''){
          $query_to_retrieve_completed_requests .= " AND r.service_type = '$service' ";
        }
        
        $completedRequests = $this->Mdl_api->customQuery($query_to_retrieve_completed_requests);

        $requestCount = 0;
        $reactionCount = 0;

        if($completedRequests !== "NA"){
          foreach($completedRequests as $val){            
            $requestCount += 1;
            if($val->reaction_link !== ''){
              $reactionCount += 1;
            }
          }

          json_output(200, array("status"=>"success","requestCount"=>$requestCount, "reactionCount"=>$reactionCount ));
        }else{
          json_output(200, array("status"=>"no data"));
        }
      }
    }
  }

  public function page(){
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
    
        $content = $_GET;
        $page = isset($content['page']) && $content['page'] !== "" ? $content['page'] : 1;
        $recordsToShow = 5;
        $start = ( $page - 1 ) * $recordsToShow;
        
        $dateFrom = isset($content['dateFrom']) && $content['dateFrom'] !== "" ? $content['dateFrom'] : "";
        $dateTo = isset($content['dateTo']) && $content['dateTo'] !== "" ? $content['dateTo'] : "";
        $occasion = isset($content['occasion']) && $content['occasion'] !== "" ? $content['occasion'] : "";
        $service = isset($content['service']) && $content['service'] !== "" ? $content['service'] : "";

        $query_to_retrieve_total_requests = "SELECT COUNT( r.order_id ) AS total_counts FROM request r INNER JOIN talent_details t ON t.talent_id = r.talent_id WHERE t.registration_id='$registration_id' AND r.request_status='complete' AND r.payment_status='captured' ";

        $query_to_retrieve_completed_requests = "SELECT r.order_id, r.type, r.service_type, r.registration_id, r.uid, r.occasion, r.halagram_link, r.reaction_link, r.share_halagram, r.share_halagram_talent, r.share_reaction, r.halagram_price, r.platform_share, r.talent_share, r.talent_payment_status, r.created_date, r.request_date FROM request r INNER JOIN talent_details t ON t.talent_id = r.talent_id WHERE t.registration_id='$registration_id' AND r.request_status='complete' AND r.payment_status='captured' ";

        if($occasion !== ''){
          $query_to_retrieve_completed_requests .= " AND r.occasion='$occasion' ";
          $query_to_retrieve_total_requests .= " AND r.occasion='$occasion' ";
        }

        if($dateFrom !== ''){
          $date_from = date('Y-m-d H:i:s',strtotime($dateFrom.' 00:00:00'));
          $query_to_retrieve_completed_requests .= " AND r.created_date >= '$date_from' ";
          $query_to_retrieve_total_requests .= " AND r.created_date >= '$date_from' ";
        }

        if($dateTo !== ''){
          $date_to = date('Y-m-d H:i:s',strtotime($dateTo.' 23:59:59'));
          $query_to_retrieve_completed_requests .= " AND r.created_date <= '$date_to' ";
          $query_to_retrieve_total_requests .= " AND r.created_date <= '$date_to' ";
        }

        if($service !== ''){
          $query_to_retrieve_completed_requests .= " AND r.service_type = '$service' ";
          $query_to_retrieve_total_requests .= " AND r.service_type = '$service' ";
        }

        $query_to_retrieve_completed_requests .= "ORDER BY created_date desc LIMIT $recordsToShow OFFSET $start ";
        
        $completedRequests = $this->Mdl_api->customQuery($query_to_retrieve_completed_requests);
        $totalRequests = $this->Mdl_api->customQuery($query_to_retrieve_total_requests);

        if($totalRequests !== "NA"){
          $totalPages = ceil($totalRequests[0]->total_counts / $recordsToShow);
          $nextPage = ($page + 1) <= $totalPages ? ($page + 1) : "NA";
        }

        if($completedRequests !== "NA"){
          $response = array();

          foreach($completedRequests as $val){
            $occasionEn = $occasionAr = '';
            $nameEn = $nameAr = '';

            if($val->type == "talent"){
              $GET_USER_DETAILS = "SELECT t.fullname_en, t.fullname_ar FROM talent_details t INNER JOIN registration r ON r.registration_id = t.registration_id WHERE r.uid='$val->uid' ";
              $user_details = $this->Mdl_api->customQuery($GET_USER_DETAILS);
              
              if($user_details !== "NA"){
                $nameEn = $user_details[0]->fullname_en;
                $nameAr = $user_details[0]->fullname_ar;
              }
            }else{
              $GET_USER_DETAILS = "SELECT u.fullname FROM user_details u INNER JOIN registration r ON r.registration_id = u.registration_id WHERE r.uid='$val->uid' ";
              $user_details = $this->Mdl_api->customQuery($GET_USER_DETAILS);
              
              if($user_details !== "NA"){
                $nameEn = $user_details[0]->fullname;
                $nameAr = $user_details[0]->fullname;
              }
            }

            if($val->occasion !== '' && $val->service_type == 'shout-out'){
              $GET_OCCASION = "SELECT occasion_name_en, occasion_name_ar FROM occasion_master WHERE occasion_id='$val->occasion' ";
					    $occasion = $this->Mdl_api->customQuery($GET_OCCASION);
              
              if($occasion !== "NA"){
                $occasionEn = $occasion[0]->occasion_name_en;
                $occasionAr = $occasion[0]->occasion_name_ar;
              }
            }

            if($val->service_type == 'shout-out'){
              $serviceType = 'Shout out';
            }else{
              $serviceType = 'Nugget of Wisdom';
            }
            
            $temp = array();
            $temp['orderId'] = $val->order_id;
            $temp['nameEn'] = $nameEn;
            $temp['nameAr'] = $nameAr;
            $temp['serviceType'] = $serviceType;
            $temp['occasionEn'] = $occasionEn;
            $temp['occasionAr'] = $occasionAr;
            $temp['halagramLink'] = $val->halagram_link;
            $temp['reactionLink'] = $val->reaction_link;
            $temp['shareHalagram'] = $val->share_halagram;
            $temp['shareHalagramTalent'] = $val->share_halagram_talent;
            $temp['shareReaction'] = $val->share_reaction;
            $temp['halagramPrice'] = $val->halagram_price;
            $temp['platformShare'] = $val->platform_share;
            $temp['talentShare'] = $val->talent_share;
            $temp['paymentStatus'] = $val->talent_payment_status;
            $temp['requestDate'] = date("d/m/Y",strtotime($val->request_date));
            $response[] = $temp;
          }

          json_output(200, array("status"=>"success","requests"=>$response, "nextPage" => $nextPage ));
        }else{
          json_output(200, array("status"=>"no data", "nextPage" => "NA"));
        }
      }
    }
  }
}