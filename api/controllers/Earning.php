<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH.'modules/generic/controllers/Generic.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST,GET,PUT,DELETE");
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authtoken');
header("Access-Control-Max-Age: 86000");

class Earning extends Generic{

  function __construct() {
    parent::__construct();
    $this->load->model('Mdl_api');		
  }

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

        $query_to_retrieve_completed_requests = "SELECT r.order_id, r.halagram_price, r.platform_share, r.talent_share, r.reaction_link, r.talent_payment_status FROM request r INNER JOIN talent_details t ON t.talent_id = r.talent_id WHERE t.registration_id='$registration_id' AND r.request_status='complete' AND r.payment_status='captured' ";

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
        $totalEarning = 0;
        $totalPaid = 0;
        $totalPending = 0;

        if($completedRequests !== "NA"){
          foreach($completedRequests as $val){            
            $requestCount += 1;
            if($val->reaction_link !== ''){
              $reactionCount += 1;
            }
            $totalEarning += $val->talent_share;
            
            if($val->talent_payment_status == 'paid'){
              $totalPaid += $val->talent_share;
            }else{
              $totalPending += $val->talent_share;
            }
          }

          json_output(200, array("status"=>"success","requestCount"=>$requestCount, "reactionCount"=>$reactionCount, "totalEarning"=>$totalEarning, "totalPaid"=>$totalPaid, "totalPending"=>$totalPending ));
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
        $recordsToShow = 30;
        $start = ( $page - 1 ) * $recordsToShow;
        
        $dateFrom = isset($content['dateFrom']) && $content['dateFrom'] !== "" ? $content['dateFrom'] : "";
        $dateTo = isset($content['dateTo']) && $content['dateTo'] !== "" ? $content['dateTo'] : "";
        $occasion = isset($content['occasion']) && $content['occasion'] !== "" ? $content['occasion'] : "";
        $service = isset($content['service']) && $content['service'] !== "" ? $content['service'] : "";

        $query_to_retrieve_total_requests = "SELECT COUNT( r.order_id ) AS total_counts FROM request r INNER JOIN talent_details t ON t.talent_id = r.talent_id WHERE t.registration_id='$registration_id' AND r.request_status='complete' AND r.payment_status='captured' ";

        $query_to_retrieve_completed_requests = "SELECT r.order_id, r.type, r.service_type, r.registration_id, r.uid, r.occasion, r.halagram_price, r.platform_share, r.talent_share, r.talent_payment_status, r.created_date, r.request_date FROM request r INNER JOIN talent_details t ON t.talent_id = r.talent_id WHERE t.registration_id='$registration_id' AND r.request_status='complete' AND r.payment_status='captured' ";

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
            $temp['halagramPrice'] = $val->halagram_price;
            $temp['platformShare'] = $val->platform_share;
            $temp['talentShare'] = $val->talent_share;
            $temp['paymentStatus'] = $val->talent_payment_status;
            $temp['requestDate'] = date("d/m/Y",strtotime($val->request_date));
            $response[] = $temp;
          }

          json_output(200, array("status"=>"success","requests"=>$response, "nextPage" => $nextPage ));
        }else{
          json_output(200, array("status"=>"no data", "nextPage" => "NA" ));
        }
      }
    }
  }
}