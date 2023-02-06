<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH.'modules/generic/controllers/Generic.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST,GET,PUT,DELETE");
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authtoken');
header("Access-Control-Max-Age: 86000");

class Wishlist extends Generic{

  function __construct() {
    parent::__construct();
    $this->load->model('Mdl_api');		
  }

  /**
   *  Add to wishlist
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
        $GET_TALENT_ID = "SELECT t.talent_id FROM talent_details t INNER JOIN registration r ON r.registration_id = t.registration_id WHERE r.uid='$talent_uid' AND r.type='talent' ";
        $talent_details = $this->Mdl_api->customQuery($GET_TALENT_ID);
        
        if($talent_details !== "NA"){
          $WHISHLIST_EXIST = $this->Mdl_api->isExist('wishlist', array('type' => $type, 'uid' => $uid, 'registration_id' => $registration_id, 'talent_id' => $talent_details[0]->talent_id));
          
          if($WHISHLIST_EXIST){
            json_output(200, array('status'=>'success','message'=>'wishlist-exist'));
          }else{
            $wishlist_data = array(
              'registration_id' => $registration_id,
              'type' => $type, 
              'uid' => $uid,  
              'talent_id' => $talent_details[0]->talent_id,
              'created_date' => date('Y-m-d H:i:s')
            );
            $whishlist_insert = $this->Mdl_api->insert('wishlist',$wishlist_data);
            json_output(200, array('status'=>'success','message'=>'wishlist-success'));
          }
        }else{
          json_output(200, array('status'=>'fail','message'=>'something-went-wrong'));
        }
      }
		}
  }

  /**
   *  Delete wishlist
   */
	public function delete(){
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
        $GET_TALENT_ID = "SELECT t.talent_id FROM talent_details t INNER JOIN registration r ON r.registration_id = t.registration_id WHERE r.uid='$talent_uid' AND r.type='talent' ";
        $talent_details = $this->Mdl_api->customQuery($GET_TALENT_ID);
        
        if($talent_details !== "NA"){
          $WHISHLIST_EXIST = $this->Mdl_api->isExist('wishlist', array('type' => $type, 'uid' => $uid, 'registration_id' => $registration_id, 'talent_id' => $talent_details[0]->talent_id));
          
          if($WHISHLIST_EXIST){
            $param = array(
              'registration_id' => $registration_id,
              'type' => $type, 
              'uid' => $uid,  
              'talent_id' => $talent_details[0]->talent_id
            );

            $this->Mdl_api->delete('wishlist', $param);
          }

          json_output(200, array('status'=>'success','message'=>'wishlist-removed'));
        }else{
          json_output(200, array('status'=>'fail','message'=>'something-went-wrong'));
        }
      }
		}
  }
}