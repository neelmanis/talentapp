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
      $video_key = $this->getHalaGramVideoKey();

      $updatedRequest = array(
        "request_status" => 'confirmed',
        "payment_status" => 'hold',
        "key" => $video_key,
        "request_date" => date("Y-m-d H:i:s"),
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
      // $telrMail = "amit@kwebmaker.com";
      // $telrName = "Amit kashte";
      $telrMail = "laxmi@gmail.com";
      $telrName = "Laxmi Test";

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
        $talentName = '';
        if($talent_details !== "NA"){
          // $talentName = $lang == 'en' ? $talent_details[0]->fullname_en : $talent_details[0]->fullname_ar;
          $talentName = $lang == 'en' ? $talent_details[0]->stage_name_en : $talent_details[0]->stage_name_ar;
        }

        $occasionName = '';
        if($occasion !== "NA"){
          $occasionName = $lang == 'en' ? $occasion[0]->occasion_name_en : $occasion[0]->occasion_name_ar;
        }

        $serviceName = '';
        if($request[0]->service_type == "shout-out"){
          $serviceName = $lang == 'en' ? "Shout Out" : "اهداءات";
        }else{
          $serviceName = $lang == 'en' ? "Nugget of Wisdom" : "حكمة";
        }

        $shareHalagram = '';
        if($request[0]->share_halagram == "yes"){
          $shareHalagram = $lang == 'en' ? "Yes" : "نعم";
        }else{
          $shareHalagram = $lang == 'en' ? "No" : "لا";
        }

        if($email == $telrMail){
          // $subject = $lang == 'en' ? 'HalaGram – Order Confirmation' : 'هلاجرام - تأكيد طلبك';
          // $mail_view_file = 'request-payment-success-'.$lang;

          $template = '';
          if($request[0]->service_type == "shout-out"){
            $template = "user order confirmation if mail ids are same ( shout out )";
          }else{
            $template = "user order confirmation if mail ids are same ( nugget of wisdom )";
          }

          $user_mail_data = array(
            // 'view_file' => 'user/'.$mail_view_file,
            // 'subject' => $subject,
            'template' => $template,
            'lang' => $lang,
            'to' => $email,
            'cc' => '',
            'isAttachment' => false,
            'halagram_logo' => base_url()."assets/images/HalaLogo.png",
            'name' => $fullname,
            'order_id' => $request[0]->order_id,
            'talent_name' => $talentName,
            'service_type' => $request[0]->service_type,
            'service_name' => $serviceName,
            'occasion' => $occasionName,
            'request_to' => $request[0]->request_to,
            'request_from' => $request[0]->request_from,
            'details' => nl2br($request[0]->details),
            'reference_link' => $request[0]->reference_link,
            'share_halagram' => $shareHalagram,
            'halagram_price' => $request[0]->halagram_price,
            "transaction_ref" => '123456',
            "transaction_date" => date("d-m-Y H:i:s"),
            "transaction_type" => 'auth',
            "transaction_code" => '123',
            "amount" => $request[0]->halagram_price, 
            "currency" => 'AED', 
            "description" => $serviceName,
            "card_type" => 'credit card',
            "card_last4" => '0002'
          );
            
          // Modules::run('email/mailer',$user_mail_data);
          Modules::run('email/template',$user_mail_data);
        }else{
          // $subject_1 = $lang == 'en' ? "HalaGram – Order Confirmation" : "هلاجرام - تأكيد طلبك";
          // $viewfile_1 = 'request-success-'.$lang;

          $template_1 = '';
          if($request[0]->service_type == "shout-out"){
            $template_1 = "user order confirmation ( shout out )";
          }else{
            $template_1 = "user order confirmation ( nugget of wisdom )";
          }

          $mail_data_1 = array(
            // 'view_file' => 'user/'.$viewfile_1,
            // 'subject' => $subject_1,
            'template' => $template_1,
            'lang' => $lang,
            'to' => $email,
            'cc' => '',
            'isAttachment' => false,
            'halagram_logo' => base_url()."assets/images/HalaLogo.png",
            'name' => $fullname,
            'order_id' => $request[0]->order_id,
            'talent_name' => $talentName,
            'service_type' => $request[0]->service_type,
            'service_name' => $serviceName,
            'occasion' => $occasionName,
            'request_to' => $request[0]->request_to,
            'request_from' => $request[0]->request_from,
            'details' => nl2br($request[0]->details),
            'reference_link' => $request[0]->reference_link,
            'share_halagram' => $shareHalagram,
            'halagram_price' => $request[0]->halagram_price,
            "transaction_ref" => '123456',
            "transaction_date" => date("d-m-Y H:i:s"),
            "transaction_type" => 'auth',
            "transaction_code" => '123',
            "amount" => $request[0]->halagram_price, 
            "currency" => 'AED', 
            "description" => $serviceName,
            "card_type" => 'credit card',
            "card_last4" => '0002'
          );
            
          // Modules::run('email/mailer',$mail_data_1);
          Modules::run('email/template',$mail_data_1);

          // $subject_2 = $lang == 'en' ? "HalaGram – Transaction Confirmation" : "هلاجرام - تأكيد عملية تفويض الدفع"; 
          // $viewfile_2 = 'transaction-success-'.$lang;

          $template_2 = '';
          if($request[0]->service_type == "shout-out"){
            $template_2 = "user transaction confirmation ( shout out )";
          }else{
            $template_2 = "user transaction confirmation ( nugget of wisdom )";
          }

          $mail_data_2 = array(
            // 'view_file' => 'user/'.$viewfile_2,
            // 'subject' => $subject_2,
            'template' => $template_2,
            'lang' => $lang,
            'to' => $telrMail,
            'cc' => '',
            'isAttachment' => false,
            'halagram_logo' => base_url()."assets/images/HalaLogo.png",
            'name' => $telrName,
            'order_id' => $request[0]->order_id,
            'talent_name' => $talentName,
            'service_type' => $request[0]->service_type,
            'service_name' => $serviceName,
            'occasion' => $occasionName,
            'request_to' => $request[0]->request_to,
            'request_from' => $request[0]->request_from,
            'details' => nl2br($request[0]->details),
            'reference_link' => $request[0]->reference_link,
            'share_halagram' => $shareHalagram,
            'halagram_price' => $request[0]->halagram_price,
            "transaction_ref" => '123456',
            "transaction_date" => date("d-m-Y H:i:s"),
            "transaction_type" => 'auth',
            "transaction_code" => '123',
            "amount" => $request[0]->halagram_price, 
            "currency" => 'AED', 
            "description" => $serviceName,
            "card_type" => 'credit card',
            "card_last4" => '0002'
          );
            
          // Modules::run('email/mailer',$mail_data_2);
          Modules::run('email/template',$mail_data_2);
        }
      }

      if($talent_details !== "NA"){
        // $subject = $talent_details[0]->lang == 'en' ? 'HalaGram – New Request' : 'هلاجرام - طلب جديد';
        // $mail_view_file = 'order-confirmation-'.$talent_details[0]->lang;

        $occasionName = '';
        if($occasion !== "NA"){
          $occasionName = $talent_details[0]->lang == 'en' ? $occasion[0]->occasion_name_en : $occasion[0]->occasion_name_ar;
        }

        $serviceName = '';
        $template = '';

        if($request[0]->service_type == "shout-out"){
          $template = "talent order confirmation ( shout out )";
          $serviceName = $talent_details[0]->lang == 'en' ? "Shout Out" : "اهداءات";
        }else{
          $template = "talent order confirmation ( nugget of wisdom )";
          $serviceName = $talent_details[0]->lang == 'en' ? "Nugget of Wisdom" : "حكمة";
        }

        $shareHalagram = '';
        if($request[0]->share_halagram == "yes"){
          $shareHalagram = $talent_details[0]->lang == 'en' ? "Yes" : "نعم";
        }else{
          $shareHalagram = $talent_details[0]->lang == 'en' ? "No" : "لا";
        }

        $talent_mail_data = array(
          // 'view_file' => 'talent/'.$mail_view_file,
          // 'subject' => $subject,
          'template' => $template,
          'lang' => $talent_details[0]->lang,
          'to' => $talent_details[0]->email,
          'cc' => '',
          'halagram_logo' => base_url()."assets/images/HalaLogo.png",
          'isAttachment' => false,
          'name' => $talent_details[0]->lang == 'en' ? $talent_details[0]->fullname_en : $talent_details[0]->fullname_ar,
          'order_id' => $request[0]->order_id,
          'talent_name' => '',
          'service_type' => $request[0]->service_type,
          'service_name' => $serviceName,
          'occasion' => $occasionName,
          'request_to' => $request[0]->request_to,
          'request_from' => $request[0]->request_from,
          'details' => nl2br($request[0]->details),
          'reference_link' => $request[0]->reference_link,
          'share_halagram' => $shareHalagram,
          'halagram_price' => $request[0]->halagram_price,
          'redirect' => $this->global_variables['front_end_url'].'new-request'
        );
          
        Modules::run('email/template',$talent_mail_data);
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