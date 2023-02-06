<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH.'modules/generic/controllers/Generic.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST,GET,PUT,DELETE");
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authtoken');
header("Access-Control-Max-Age: 86000");

class Visitor extends Generic{

  function __construct() {
    parent::__construct();
    $this->load->model('Mdl_api');		
  }

  /**
   * Add to visitor
   */
	public function add(){
		$method = $_SERVER['REQUEST_METHOD'];
    $headers = $this->input->request_headers();
    
		if($method !== 'POST'){
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
          $type = $check_token_validity['type'];
        }
      }

      $content = json_decode(file_get_contents('php://input'), TRUE);
      $this->form_validation->set_data($content);
      
      $GET_TALENT_ID = 'SELECT t.talent_id FROM talent_details t INNER JOIN registration r ON r.registration_id = t.registration_id WHERE r.uid=\''.$content['talent_uid'].'\' AND r.type=\'talent\'';
      $talent_details = $this->Mdl_api->customQuery($GET_TALENT_ID);
      
      if($talent_details !== "NA"){
        if($is_auth){
          $age = null;
          $country = null;
          $gender = null;

          if($type == 'talent'){
            $talent_data = $this->Mdl_api->retrieveByCol('gender,country','talent_details',array('registration_id' => $registration_id));
          
            if($talent_data !== "NA"){
              $country = $talent_data[0]->country;
              $gender = $talent_data[0]->gender;
            }
          }else if($type == 'user'){
            $user_data = $this->Mdl_api->retrieveByCol('birth_date, country, gender','user_details',array('registration_id' => $registration_id));
          
            if($user_data !== "NA"){
              $age = $this->calculateAge($user_data[0]->birth_date);
              $country = $user_data[0]->country;
              $gender = $user_data[0]->gender;
            }
          }

          $visit_entry = array(
            'registration_id' => $registration_id,
            'type' => $type, 
            'uid' => $uid,  
            'talent_id' => $talent_details[0]->talent_id,
            'age' => $age,
            'country' => $country,
            'gender' => $gender,
            'created_date' => date('Y-m-d H:i:s')
          );
          $visitor_insert = $this->Mdl_api->insert('talent_profile_visits',$visit_entry);
        }else{
          // $visit_entry = array(
          //   'registration_id' => null,
          //   'type' => 'anonymous', 
          //   'uid' => null,  
          //   'talent_id' => $talent_details[0]->talent_id,
          //   'created_date' => date('Y-m-d H:i:s')
          // );
        }
        
        json_output(200, array('status'=>'success'));
      }else{
        json_output(200, array('status'=>'no data'));
      }
		}
  }

  public function updateData(){
    $visitors = $this->Mdl_api->retrieve('talent_profile_visits',array('1'=>'1'));
    // print_r($visitors);

    // if($visitors !== 'NA'){
    //   foreach($visitors as $val){
    //     $age = null;
    //     $country = null;
    //     $gender = null;

    //     if($val->type == 'talent'){
    //       $talent_data = $this->Mdl_api->retrieveByCol('gender,country','talent_details',array('registration_id' => $val->registration_id));
          
    //       if($talent_data !== "NA"){
    //         $country = $talent_data[0]->country;
    //         $gender = $talent_data[0]->gender;
    //       }

    //       $visitorData = array(
    //         'country' => $country,
    //         'gender' => $gender
    //       );
    //       $udpate = $this->Mdl_api->update('talent_profile_visits',array('visitor_id' => $val->visitor_id),$visitorData);
    //     }else if($val->type == 'user'){
    //       $user_data = $this->Mdl_api->retrieveByCol('birth_date, country, gender','user_details',array('registration_id' => $val->registration_id));
          
    //       if($user_data !== "NA"){
    //         $age = $this->calculateAge($user_data[0]->birth_date);
    //         $country = $user_data[0]->country;
    //         $gender = $user_data[0]->gender;
    //       }

    //       $visitorData = array(
    //         'age' => $age,
    //         'country' => $country,
    //         'gender' => $gender
    //       );
    //       $udpate = $this->Mdl_api->update('talent_profile_visits',array('visitor_id' => $val->visitor_id),$visitorData);
    //     }
    //   }
    // }
  }

  public function calculateAge($dob){
    $from = new DateTime($dob);
    $to   = new DateTime('today');
    return $from->diff($to)->y;
  }
}