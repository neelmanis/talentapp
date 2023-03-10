<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH.'modules/generic/controllers/Generic.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST,GET,PUT,DELETE");
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authtoken');
header("Access-Control-Max-Age: 86000");

class Payment extends Generic{

  function __construct() {
    parent::__construct();
    $this->load->model('Mdl_api');		
  }

  /**
   *  New Request
   */
	public function request(){
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
        $orderId = $content['orderId'];

        $get_request_query = "SELECT request_status, payment_status, halagram_price, service_type  FROM request WHERE type='$type' AND registration_id='$registration_id' AND uid='$uid' AND order_id='$orderId' ";
        $request = $this->Mdl_api->customQuery($get_request_query);

        if($request == "NA"){
          json_output(200,array('status'=>'fail','message'=>'order-not-exist'));
        }else if($request[0]->request_status == 'confirmed' || $request[0]->request_status == 'complete'){
          json_output(200,array('status'=>'fail','message'=>'payment-done'));
        }else if($request[0]->request_status == 'failed' || $request[0]->request_status == 'expired' || $request[0]->request_status == 'canceled'){
          json_output(200,array('status'=>'fail','message'=>'order-canceled'));
        }else if($request[0]->request_status == 'created' && $request[0]->payment_status == 'pending'){
          $url = base_url().'payment/'.$orderId;
          json_output(200,array('status'=>'success', 'paymentUrl' => $url));
        }
      }
    }
  }

  /**
   *  Payment Success
   */
  public function success($orderId){
    $request = $this->Mdl_api->retrieve("request",array("order_id" =>$orderId));
    if($request !== "NA"){
      $updatedRequest = array(
        "request_status" => 'confirmed',
        "payment_status" => 'hold',
        // "request_date" => date("Y-m-d H:i:s"),
        "expiry_date" => date("Y-m-d H:i:s",strtotime("+5 days")),
        "modified_date" => date("Y-m-d H:i:s")
      );
      $update = $this->Mdl_api->update("request",array('order_id'=>$orderId), $updatedRequest);

      $talentId = $request[0]->talent_id; 
      $registrationId = $request[0]->registration_id;
      $occasionId = $request[0]->occasion !== '' ? $request[0]->occasion : 0;

      $get_talent_details = "SELECT lang, fullname_en, fullname_ar, stage_name_en, stage_name_ar, email FROM talent_details WHERE talent_id='$talentId' ";
      $talent_details = $this->Mdl_api->customQuery($get_talent_details);

      $GET_OCCASION = "SELECT occasion_name_en, occasion_name_ar FROM occasion_master WHERE occasion_id=$occasionId ";
      $occasion = $this->Mdl_api->customQuery($GET_OCCASION);
              
      $lang = $fullname = $email = '';

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
        $subject = $lang == 'en' ? 'HalaGram ??? Order Confirmation' : '?????????????? - ?????????? ????????';
        $mail_view_file = 'request-payment-success-'.$lang;

        $talentName = '';
        if($talent_details !== "NA"){
          $talentName = $lang == 'en' ? $talent_details[0]->fullname_en : $talent_details[0]->fullname_ar;
        }

        $occasionName = '';
        if($occasion !== "NA"){
          $occasionName = $lang == 'en' ? $occasion[0]->occasion_name_en : $occasion[0]->occasion_name_ar;
        }

        $user_mail_data = array(
          'view_file' => 'user/'.$mail_view_file,
          'to' => $email,
          'cc' => '',
          'subject' => $subject,
          'isAttachment' => false,
          'name' => $fullname,
          'order_id' => $request[0]->order_id,
          'talent_name' => $talentName,
          'service_type' => $request[0]->service_type,
          'occasion' => $occasionName,
          'request_to' => $request[0]->request_to,
          'request_from' => $request[0]->request_from,
          'details' => $request[0]->details,
          'reference_link' => $request[0]->reference_link,
          'share_halagram' => $request[0]->share_halagram,
          'halagram_price' => $request[0]->halagram_price
        );
          
        Modules::run('email/mailer',$user_mail_data);
      }

      if($talent_details !== "NA"){
        $subject = $talent_details[0]->lang == 'en' ? 'HalaGram ??? New Request' : '?????????????? - ?????? ????????';
        $mail_view_file = 'order-confirmation-'.$lang;

        $occasionName = '';
        if($occasion !== "NA"){
          $occasionName = $talent_details[0]->lang == 'en' ? $occasion[0]->occasion_name_en : $occasion[0]->occasion_name_ar;
        }

        $talent_mail_data = array(
          'view_file' => 'talent/'.$mail_view_file,
          'to' => $talent_details[0]->email,
          'cc' => '',
          'subject' => $subject,
          'isAttachment' => false,
          'name' => $talent_details[0]->lang == 'en' ? $talent_details[0]->fullname_en : $talent_details[0]->fullname_ar,
          'order_id' => $request[0]->order_id,
          'talent_name' => '',
          'service_type' => $request[0]->service_type,
          'occasion' => $occasionName,
          'request_to' => $request[0]->request_to,
          'request_from' => $request[0]->request_from,
          'details' => $request[0]->details,
          'reference_link' => $request[0]->reference_link,
          'share_halagram' => $request[0]->share_halagram,
          'halagram_price' => $request[0]->halagram_price,
          'redirect' => $this->global_variables['front_end_url'].'new-request'
        );
          
        Modules::run('email/mailer',$talent_mail_data);
      }
    }
    
    $redirect = $this->global_variables['front_end_url'].'payment/success';
    redirect($redirect,'refresh');
  }
  
  public function failed($orderId){
    $redirect = $this->global_variables['front_end_url'].'payment/failure';
    redirect($redirect,'refresh');
  }
}