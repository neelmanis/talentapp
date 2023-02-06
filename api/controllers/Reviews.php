<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH.'modules/generic/controllers/Generic.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST,GET,PUT,DELETE");
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authtoken');
header("Access-Control-Max-Age: 86000");

class Reviews extends Generic{

  function __construct() {
    parent::__construct();
    $this->load->model('Mdl_api');		
  }

  /**
   * Get Review by OrderId
   */
  public function byOrderId(){
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
        
        $GET_REVIEW = "SELECT review, rating FROM talent_review WHERE order_id='$orderId' AND type='$type' AND registration_id='$registration_id' AND uid='$uid' ";
        $review = $this->Mdl_api->customQuery($GET_REVIEW);
        
        if($review !== "NA"){
          $response = [];
          $response['review'] = $review[0]->review;
          $response['rating'] = $review[0]->rating;
          json_output(200, array("status"=>"success","details"=>$response));
        }else{
          json_output(200, array("status"=>"no data"));
        }
      }
    }
  }

	public function updateStatus(){
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
        
        $get_request = "SELECT talent_id FROM talent_details WHERE registration_id='$registration_id' ";
        $talent = $this->Mdl_api->customQuery($get_request);

        if($talent !== "NA"){
          $review_data = array(
            "is_read" => "yes"
          );
          $update = $this->Mdl_api->update("talent_review", array("talent_id"=>$talent[0]->talent_id), $review_data);
          json_output(200,array('status'=>'success'));
        }else{
          json_output(200, array('status' => 'fail'));
        }
      }
		}
  }

  /**
   * New Review
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
        
        $orderId = $content['orderId'];
        $get_request = "SELECT talent_id FROM request WHERE registration_id='$registration_id' AND uid='$uid' AND type='$type' AND order_id='$orderId' ";
        $request = $this->Mdl_api->customQuery($get_request);

        if($request !== "NA"){
         
          $this->form_validation->set_rules("rating","Rating","trim|xss_clean|required|in_list[1,2,3,4,5]",
          array(
            'required' => "Rating is required",
            'in_list' => 'Invalid value'
          ));

          $this->form_validation->set_rules("review","Feedback","trim|xss_clean|required",
          array(
            'required' => "Feedback required"
          ));

          if($this->form_validation->run($this) == FALSE){
            $errors = $this->form_validation->error_array();
            json_output(200,array('status'=>'error','errorData'=>$errors));
          }else{

            $review_data = array(
              "order_id" => $content['orderId'],
              "type" => $type,
              "registration_id" => $registration_id,
              "uid" => $uid,
              "talent_id" => $request[0]->talent_id,
              "rating" => strip_tags($content['rating']),
              "review" => $content['review'],
              "is_read" => "no",
              "created_date" => date("Y-m-d H:i:s")
            );
            $insert = $this->Mdl_api->insert("talent_review", $review_data);
            json_output(200,array('status'=>'success'));
          }
        }else{
          json_output(200, array('status' => 'fail'));
        }
      }
		}
  }

  /**
   * Talent Reviews
   */
  public function byTalent(){
    $method = $_SERVER['REQUEST_METHOD'];
    $headers = $this->input->request_headers();
    
    if($method !== 'GET'){
			json_output(400, array('status' => 400,'message' => 'Bad request.'));
		}else{
      
      $slug = $dateFrom = $dateTo = $rating = '';
      $content = $_GET;
      $slug = $content['slug'];

      if(isset($content['dateFrom'])){
        $dateFrom = $content['dateFrom'];
      }

      if(isset($content['dateTo'])){
        $dateTo = $content['dateTo'];
      }

      if(isset($content['rating'])){
        $rating = $content['rating'];
      }
      
      if($slug == ''){
        json_output(200, array("status"=>"no data"));
      }else{
        $GET_REVIEWS = "SELECT r.type, r.registration_id, r.uid, r.review, r.rating, r.created_date FROM talent_review r INNER JOIN talent_details t ON t.talent_id = r.talent_id WHERE t.slug='$slug' ";

        if($rating !== ''){
          $GET_REVIEWS .= ' AND r.rating=\''.$rating.'\'';
        }

        if($dateFrom !== ''){
          $date_from = date('Y-m-d H:i:s',strtotime($dateFrom.' 00:00:00'));
          $GET_REVIEWS .= ' AND r.created_date >= \''.$date_from.'\'';
        }

        if($dateTo !== ''){
          $date_to = date('Y-m-d H:i:s',strtotime($dateTo.' 23:59:59'));
          $GET_REVIEWS .= ' AND r.created_date <= \''.$date_to.'\'';
        }

        $GET_REVIEWS .= ' ORDER BY created_date DESC';
        $reviews = $this->Mdl_api->customQuery($GET_REVIEWS);

        $total_rating = 0;
        $total_review = 0;
        $latest_review = 0;
        $margin_date = strtotime("-1 days");
        
        if($reviews !== "NA"){
          $response = array();
          foreach($reviews as $val){
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
            
            $temp = array();
            $temp['nameEn'] = $nameEn;
            $temp['nameAr'] = $nameAr;
            $temp['review'] = $val->review;
            $temp['rating'] = $val->rating;
            $temp['date'] = date("d/m/Y",strtotime($val->created_date));
            $response[] = $temp;
            $total_rating += $val->rating;
            $total_review += 1;
            if(strtotime($val->created_date) > $margin_date){
              $latest_review += 1;
            }
          }

          $average = $total_rating / $total_review;
          json_output(200, array("status"=>"success", "records"=>$response, "avgRating"=>$average, "total"=>$total_review, "new"=>$latest_review ));
        }else{
          json_output(200, array("status"=>"no data"));
        }
      }
    }
  }

  /**
   * Get All Reviews 
   */
  // public function all(){
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
  //       $curr_date = date("Y-m-d H:i:s");
       
  //       $get_talent_details = "SELECT talent_id FROM talent_details WHERE registration_id='$registration_id' ";
  //       $talent = $this->Mdl_api->customQuery($get_talent_details);

  //       if($talent !== "NA"){
  //         $review_data = array(
  //           "is_read" => "yes"
  //         );
  //         $update = $this->Mdl_api->update("talent_review", array("talent_id"=>$talent[0]->talent_id, "is_read" => "no"), $review_data);
  //       }

  //       $GET_REVIEWS = "SELECT r.type, r.registration_id, r.uid, r.review, r.rating, r.created_date FROM talent_review r INNER JOIN talent_details t ON t.talent_id = r.talent_id WHERE t.registration_id='$registration_id' ORDER BY created_date DESC";
  //       $reviews = $this->Mdl_api->customQuery($GET_REVIEWS);

  //       $total_rating = 0;
  //       $total_review = 0;
  //       $latest_review = 0;
  //       $margin_date = strtotime("-1 days");
        
  //       if($reviews !== "NA"){
  //         $response = array();
  //         foreach($reviews as $val){
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

  //           $temp = array();
  //           $temp['nameEn'] = $nameEn;
  //           $temp['nameAr'] = $nameAr;
  //           $temp['review'] = $val->review;
  //           $temp['rating'] = $val->rating;
  //           $temp['date'] = date("d/m/Y",strtotime($val->created_date));
  //           $response[] = $temp;
  //           $total_rating += $val->rating;
  //           $total_review += 1;
  //           if(strtotime($val->created_date) > $margin_date){
  //             $latest_review += 1;
  //           }
  //         }

  //         $average = $total_rating / $total_review;
  //         json_output(200, array("status"=>"success", "records"=>$response, "avgRating"=>$average, "total"=>$total_review, "new"=>$latest_review ));
  //       }else{
  //         json_output(200, array("status"=>"no data"));
  //       }
  //     }
  //   }
  // }

  /**
   * Filter Reviews
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
  //       $rating = $content['rating'];
        
  //       $GET_REVIEWS = "SELECT r.type, r.registration_id, r.uid, r.review, r.rating, r.created_date FROM talent_review r INNER JOIN talent_details t ON t.talent_id = r.talent_id WHERE t.registration_id='$registration_id' ";
        
  //       if($rating !== ''){
  //         $GET_REVIEWS .= ' AND r.rating=\''.$rating.'\'';
  //       }
        
  //       if($dateFrom !== ''){
  //         $date_from = date('Y-m-d H:i:s',strtotime($dateFrom.' 00:00:00'));
  //         $GET_REVIEWS .= ' AND r.created_date >= \''.$date_from.'\'';
  //       }
        
  //       if($dateTo !== ''){
  //         $date_to = date('Y-m-d H:i:s',strtotime($dateTo.' 23:59:59'));
  //         $GET_REVIEWS .= ' AND r.created_date <= \''.$date_to.'\'';
  //       }

  //       $GET_REVIEWS .= ' ORDER BY created_date DESC';
        
  //       $reviews = $this->Mdl_api->customQuery($GET_REVIEWS);
  //       $total_rating = 0;
  //       $total_review = 0;
  //       $latest_review = 0;
  //       $margin_date = strtotime("-1 days");
        
  //       if($reviews !== "NA"){
  //         $response = array();
  //         foreach($reviews as $val){
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

  //           $temp = array();
  //           $temp['nameEn'] = $nameEn;
  //           $temp['nameAr'] = $nameAr;
  //           $temp['review'] = $val->review;
  //           $temp['rating'] = $val->rating;
  //           $temp['date'] = date("d/m/Y",strtotime($val->created_date));
  //           $response[] = $temp;
  //           $total_rating += $val->rating;
  //           $total_review += 1;
  //           if(strtotime($val->created_date) > $margin_date){
  //             $latest_review += 1;
  //           }
  //         }

  //         $average = $total_rating / $total_review;
  //         json_output(200, array("status"=>"success", "records"=>$response, "avgRating"=>$average, "total"=>$total_review, "new"=>$latest_review ));
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
        $rating = isset($content['rating']) && $content['rating'] !== "" ? $content['rating'] : "";
        
        $query_to_get_counter = "SELECT r.rating, r.created_date FROM talent_review r INNER JOIN talent_details t ON t.talent_id = r.talent_id WHERE t.registration_id='$registration_id' ";
        
        if($rating !== ''){
          $query_to_get_counter .= " AND r.rating='$rating' ";
        }
        
        if($dateFrom !== ''){
          $date_from = date('Y-m-d H:i:s',strtotime($dateFrom.' 00:00:00'));
          $query_to_get_counter .= " AND r.created_date >= '$date_from' ";
        }
        
        if($dateTo !== ''){
          $date_to = date('Y-m-d H:i:s',strtotime($dateTo.' 23:59:59'));
          $query_to_get_counter .= " AND r.created_date <= '$date_to' ";
        }

        $query_to_get_counter .= " ORDER BY created_date DESC ";
        $reviews = $this->Mdl_api->customQuery($query_to_get_counter);

        $total_rating = 0;
        $total_review = 0;
        $latest_review = 0;
        $margin_date = strtotime("-1 days");
        
        if($reviews !== "NA"){

          foreach($reviews as $val){
            $total_rating += $val->rating;
            $total_review += 1;
            if(strtotime($val->created_date) > $margin_date){
              $latest_review += 1;
            }
          }

          $average = $total_rating / $total_review;

          json_output(200, array("status"=>"success", "averageRating"=>$average, "totalRatings"=>$total_review, "latestRatings"=>$latest_review ));
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
        $rating = isset($content['rating']) && $content['rating'] !== "" ? $content['rating'] : "";
        
        $query_to_get_total_requests = "SELECT COUNT( r.rating ) AS total_counts FROM talent_review r INNER JOIN talent_details t ON t.talent_id = r.talent_id WHERE t.registration_id='$registration_id' ";

        $query_to_get_requests = "SELECT r.type, r.registration_id, r.uid, r.review, r.rating, r.created_date FROM talent_review r INNER JOIN talent_details t ON t.talent_id = r.talent_id WHERE t.registration_id='$registration_id' ";
        
        if($rating !== ''){
          $query_to_get_requests .= " AND r.rating='$rating' ";
          $query_to_get_total_requests .= " AND r.rating='$rating' ";
        }
        
        if($dateFrom !== ''){
          $date_from = date('Y-m-d H:i:s',strtotime($dateFrom.' 00:00:00'));
          $query_to_get_requests .= " AND r.created_date >= '$date_from' ";
          $query_to_get_total_requests .= " AND r.created_date >= '$date_from' ";
        }
        
        if($dateTo !== ''){
          $date_to = date('Y-m-d H:i:s',strtotime($dateTo.' 23:59:59'));
          $query_to_get_requests .= " AND r.created_date <= '$date_to' ";
          $query_to_get_total_requests .= " AND r.created_date <= '$date_to' ";
        }

        $query_to_get_requests .= " ORDER BY created_date DESC LIMIT $recordsToShow OFFSET $start ";
        
        $reviews = $this->Mdl_api->customQuery($query_to_get_requests);
        $totalReviews = $this->Mdl_api->customQuery($query_to_get_total_requests);

        if($totalReviews !== "NA"){
          $totalPages = ceil($totalReviews[0]->total_counts / $recordsToShow);
          $nextPage = ($page + 1) <= $totalPages ? ($page + 1) : "NA";
        }
        
        if($reviews !== "NA"){
          $response = array();
          foreach($reviews as $val){
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

            $temp = array();
            $temp['nameEn'] = $nameEn;
            $temp['nameAr'] = $nameAr;
            $temp['review'] = $val->review;
            $temp['rating'] = $val->rating;
            $temp['date'] = date("d/m/Y",strtotime($val->created_date));
            $response[] = $temp;
          }

          json_output(200, array("status"=>"success", "reviews"=>$response, "nextPage" => $nextPage ));
        }else{
          json_output(200, array("status"=>"no data", "nextPage" => "NA" ));
        }
      }
    }
  }
}