<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH.'modules/generic/controllers/Generic.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST,GET,PUT,DELETE");
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authtoken');
header("Access-Control-Max-Age: 86000");

class Orders extends Generic{

  function __construct() {
    parent::__construct();
    $this->load->model('Mdl_api');		
  }

  public function index(){
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
        
        $GET_REQUEST_QUERY = "SELECT t.fullname_en, t.fullname_ar, r.order_id, r.service_type, r.occasion, r.details, r.halagram_price, r.request_status, r.payment_status, r.created_date FROM request r INNER JOIN talent_details t ON t.talent_id = r.talent_id WHERE r.type='$type' AND r.registration_id='$registration_id' AND r.uid='$uid' ORDER BY r.created_date desc";
        $request = $this->Mdl_api->customQuery($GET_REQUEST_QUERY);

        if($request !== "NA"){
          $response = array();
          foreach($request as $val){
            $occasionEn = $occasionAr = '';
            
            if($val->occasion !== '' && $val->service_type == 'shout-out'){
              $GET_OCCASION = "SELECT occasion_name_en, occasion_name_ar FROM occasion_master WHERE occasion_id='$val->occasion' ";
					    $occasion = $this->Mdl_api->customQuery($GET_OCCASION);
              if($occasion !== "NA"){
                $occasionEn = $occasion[0]->occasion_name_en;
                $occasionAr = $occasion[0]->occasion_name_ar;
              }
            }

            if($val->service_type == 'shout-out'){
              $serviceType = 'Shout Out';
            }else{
              $serviceType = 'Nugget of Wisdom';
            }

            $temp = array();
            $temp['orderId'] = $val->order_id;
            $temp['talentNameEn'] = $val->fullname_en;
            $temp['talentNameAr'] = $val->fullname_ar;
            $temp['serviceType'] = $serviceType;
            $temp['occasionEn'] = $occasionEn;
            $temp['occasionAr'] = $occasionAr;
            $temp['details'] = $val->details;
            $temp['halagramPrice'] = $val->halagram_price;
            $temp['requestStatus'] = $val->request_status;
            $temp['paymentStatus'] = $val->payment_status;
            $temp['requestDate'] = date("d/m/Y",strtotime($val->created_date));
            $response[] = $temp;
          }
          
          json_output(200, array("status"=>"success","requests"=>$response ));
        }else{
          json_output(200, array("status"=>"no data"));
        }
      }
    }
  }

  public function byFilter(){
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
        $dateFrom = $content['dateFrom'];
        $dateTo = $content['dateTo'];
        $occasion = $content['occasion'];
    
        if(isset($_GET['service'])){
          $service = $content['service'];
        }else{
          $service = '';
        }

        $GET_REQUEST_QUERY = "SELECT t.fullname_en, t.fullname_ar, r.order_id, r.service_type, r.occasion, r.details, r.halagram_price, r.request_status, r.payment_status, r.created_date FROM request r INNER JOIN talent_details t ON t.talent_id = r.talent_id WHERE r.type='$type' AND r.registration_id='$registration_id' AND r.uid='$uid' ";

        if($occasion !== ''){
          $GET_REQUEST_QUERY .= " AND r.occasion='$occasion' ";
        }

        if($dateFrom !== ''){
          $date_from = date('Y-m-d H:i:s',strtotime($dateFrom.' 00:00:00'));
          $GET_REQUEST_QUERY .= " AND r.created_date >= '$date_from' ";
        }

        if($dateTo !== ''){
          $date_to = date('Y-m-d H:i:s',strtotime($dateTo.' 23:59:59'));
          $GET_REQUEST_QUERY .= " AND r.created_date <= '$date_to' ";
        }

        if($service !== ''){
          $GET_REQUEST_QUERY .= " AND r.service_type = '$service' ";
        }

        $GET_REQUEST_QUERY .= " ORDER BY created_date desc ";
        
        $request = $this->Mdl_api->customQuery($GET_REQUEST_QUERY);
        
        if($request !== "NA"){
          $response = array();
          foreach($request as $val){
            $occasionEn = $occasionAr = '';
            
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
            $temp['talentNameEn'] = $val->fullname_en;
            $temp['talentNameAr'] = $val->fullname_ar;
            $temp['serviceType'] = $serviceType;
            $temp['occasionEn'] = $occasionEn;
            $temp['occasionAr'] = $occasionAr;
            $temp['details'] = $val->details;
            $temp['halagramPrice'] = $val->halagram_price;
            $temp['requestStatus'] = $val->request_status;
            $temp['paymentStatus'] = $val->payment_status;
            $temp['requestDate'] = date("d/m/Y",strtotime($val->created_date));
            $response[] = $temp;
          }

          json_output(200, array("status"=>"success","requests"=>$response));
        }else{
          json_output(200, array("status"=>"no data"));
        }
      }
    }
  }
}