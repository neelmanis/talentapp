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
          
          $params = array(
            'ivp_method'  => 'create',
            'ivp_trantype' => 'auth',
            'ivp_store'   => $this->global_variables['store_id'],
            'ivp_authkey' => $this->global_variables['auth_key'],
            'ivp_cart'    => $orderId.strtotime("now"), 
            'ivp_test'    => $this->global_variables['test'],
            'ivp_amount'  => $request[0]->halagram_price,
            'ivp_currency'=> 'AED',
            'ivp_desc'    => $request[0]->service_type,
            'return_auth' => base_url().'api/payment/success/'.$orderId,
            'return_can'  => base_url().'api/payment/failed/'.$orderId,
            'return_decl' => base_url().'api/payment/failed/'.$orderId
          );
        
          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, "https://secure.telr.com/gateway/order.json");
          curl_setopt($ch, CURLOPT_POST, count($params));
          curl_setopt($ch, CURLOPT_POSTFIELDS,$params);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
          $results = curl_exec($ch);
          curl_close($ch);
          $results = json_decode($results,true);

          if( isset($results['order'])) {
            $ref= trim($results['order']['ref']);
            $url= trim($results['order']['url']);
          }else{
            json_output(200,array('status'=>'fail','message'=>'payment-failed'));
          }
          
          if (empty($ref) || empty($url)) {
            json_output(200,array('status'=>'fail','message'=>'payment-failed'));
          }else{
            $updatedRequest = array(
              "payment_ref" => $ref,
              "modified_date" => date("Y-m-d H:i:s")
            );
            $update = $this->Mdl_api->update("request",array('order_id'=>$orderId,'type'=>$type,'registration_id'=>$registration_id,"uid"=>$uid), $updatedRequest);

            json_output(200,array('status'=>'success', 'paymentUrl' => $url));
          }
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
      if($request[0]->payment_status == "pending"){

        $params = array(
          'ivp_method'  => 'check',
          'ivp_store'   => $this->global_variables['store_id'],
          'ivp_authkey' => $this->global_variables['auth_key'],
          'order_ref'   => $request[0]->payment_ref  
        );
  
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://secure.telr.com/gateway/order.json");
        curl_setopt($ch, CURLOPT_POST, count($params));
        curl_setopt($ch, CURLOPT_POSTFIELDS,$params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
        $results = curl_exec($ch);
        curl_close($ch);
        $results = json_decode($results,true);
        
        if ( !empty($results['order']) ) {
          if($results['order']['status']['code'] == 2){

            $payment_details = array(
              "order_id" => $orderId,
              "trace" => $results['trace'],
              "ref" => $results['order']['ref'],
              "cartid" => $results['order']['cartid'],
              "amount" => $results['order']['amount'], 
              "currency" => $results['order']['currency'], 
              "description" => $results['order']['description'], 
              "order_status_code" => $results['order']['status']['code'],
              "order_status_text" => $results['order']['status']['text'],
              "transaction_ref" => $results['order']['transaction']['ref'],
              "transaction_date" => $results['order']['transaction']['date'],
              "transaction_type" => $results['order']['transaction']['type'],
              "transaction_class" => $results['order']['transaction']['class'],
              "transaction_status" => $results['order']['transaction']['status'],
              "transaction_code" => $results['order']['transaction']['code'],
              "transaction_message" => $results['order']['transaction']['message'],
              "payment_method" => $results['order']['paymethod'],
              "card_type" => $results['order']['card']['type'],
              "card_last4" => $results['order']['card']['last4'],
              "card_first6" => $results['order']['card']['first6'],
              "card_expiry_month" => $results['order']['card']['expiry']['month'],
              "card_expiry_year" => $results['order']['card']['expiry']['year'],
              "card_country" => $results['order']['card']['country'],
              "customer_email" => $results['order']['customer']['email'],
              "customer_name" => $results['order']['customer']['name']['forenames'].' '.$results['order']['customer']['name']['surname'],
              "customer_address" => $results['order']['customer']['address']['line1'],
              "customer_city" => $results['order']['customer']['address']['city'],
              "customer_country" => $results['order']['customer']['address']['country'],
              "customer_mobile" => $results['order']['customer']['address']['mobile'],
              "created_date" => date("Y-m-d H:i:s")
            );
            $insert = $this->Mdl_api->insert("payment_details", $payment_details);
        
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
            $telrMail = $results['order']['customer']['email'];
            $telrName = $results['order']['customer']['name']['forenames'].' '.$results['order']['customer']['name']['surname'];

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
                  "transaction_ref" => $results['order']['transaction']['ref'],
                  "transaction_date" => $results['order']['transaction']['date'],
                  "transaction_type" => $results['order']['transaction']['type'],
                  "transaction_code" => $results['order']['transaction']['code'],
                  "amount" => $results['order']['amount'], 
                  "currency" => $results['order']['currency'], 
                  "description" => $results['order']['description'],
                  "card_type" => $results['order']['card']['type'],
                  "card_last4" => $results['order']['card']['last4']
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
                  "transaction_ref" => $results['order']['transaction']['ref'],
                  "transaction_date" => $results['order']['transaction']['date'],
                  "transaction_type" => $results['order']['transaction']['type'],
                  "transaction_code" => $results['order']['transaction']['code'],
                  "amount" => $results['order']['amount'], 
                  "currency" => $results['order']['currency'], 
                  "description" => $results['order']['description'],
                  "card_type" => $results['order']['card']['type'],
                  "card_last4" => $results['order']['card']['last4']
                );
                  
                // Modules::run('email/mailer',$mail_data_1);
                Modules::run('email/template',$mail_data_1);

                // $subject_2 = $lang == 'en' ? "HalaGram – Transaction Confirmation" : "هلاجرام - تأكيد عملية تفويض الدفع"; 
                // $viewfile_2 = 'transaction-success-'.$lang;

                $template_2 = '';
                if($request[0]->service_type == "shout-out"){
                  $template_2 = "user order confirmation ( shout out )";
                }else{
                  $template_2 = "user order confirmation ( nugget of wisdom )";
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
                  "transaction_ref" => $results['order']['transaction']['ref'],
                  "transaction_date" => $results['order']['transaction']['date'],
                  "transaction_type" => $results['order']['transaction']['type'],
                  "transaction_code" => $results['order']['transaction']['code'],
                  "amount" => $results['order']['amount'], 
                  "currency" => $results['order']['currency'], 
                  "description" => $results['order']['description'],
                  "card_type" => $results['order']['card']['type'],
                  "card_last4" => $results['order']['card']['last4']
                );
                  
                // Modules::run('email/mailer',$mail_data_2);
                Modules::run('email/template',$mail_data_2);
              }
            }

            if($talent_details !== "NA"){
              $subject = $talent_details[0]->lang == 'en' ? 'HalaGram – New Request' : 'هلاجرام - طلب جديد';
              $mail_view_file = 'order-confirmation-'.$talent_details[0]->lang;

              $occasionName = '';
              if($occasion !== "NA"){
                $occasionName = $talent_details[0]->lang == 'en' ? $occasion[0]->occasion_name_en : $occasion[0]->occasion_name_ar;
              }

              $serviceName = '';
              if($request[0]->service_type == "shout-out"){
                $serviceName = $talent_details[0]->lang == 'en' ? "Shout Out" : "اهداءات";
              }else{
                $serviceName = $talent_details[0]->lang == 'en' ? "Nugget of Wisdom" : "حكمة";
              }

              $shareHalagram = '';
              if($request[0]->share_halagram == "yes"){
                $shareHalagram = $talent_details[0]->lang == 'en' ? "Yes" : "نعم";
              }else{
                $shareHalagram = $talent_details[0]->lang == 'en' ? "No" : "لا";
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
                'service_name' => $serviceName,
                'occasion' => $occasionName,
                'request_to' => $request[0]->request_to,
                'request_from' => $request[0]->request_from,
                'details' => $request[0]->details,
                'reference_link' => $request[0]->reference_link,
                'share_halagram' => $shareHalagram,
                'halagram_price' => $request[0]->halagram_price,
                'redirect' => $this->global_variables['front_end_url'].'new-request'
              );
                
              Modules::run('email/mailer',$talent_mail_data);
            }
          }
        }
      }
    }
    
    $data['redirect'] = $this->global_variables['front_end_url'].'payment/success';
    // redirect($redirect,'refresh');
    $this->load->view('payment-response',$data);
  }
  
  /**
   *  Payment Failure
   */
  public function failed($orderId){
    $data['redirect'] = $this->global_variables['front_end_url'].'payment/failure';
    // redirect($redirect,'refresh');
    $this->load->view('payment-response',$data);
  }
}