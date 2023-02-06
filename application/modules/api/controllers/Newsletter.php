<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH.'modules/generic/controllers/Generic.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST,GET,PUT,DELETE");
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header("Access-Control-Max-Age: 86000");

class Newsletter extends Generic{

  function __construct() {
    parent::__construct();
		$this->load->model('Mdl_api');	
	}

  /**
   * Callback : Email unique check
   */
	public function unique_email_check($email){
		if($email == ""){
			$this->form_validation->set_message('unique_email_check','newsletter-email-required');
			return false;
		}else{
      if($this->Mdl_api->isExist('newsletter_subscriber',array('email'=>$email))){
        $this->form_validation->set_message('unique_email_check','newsletter-email-exist');
			  return false;
      }else{
        return true;
      }
    }
  }
  
  /**
   * Newsletter subscribe
   */
	public function subscribe(){
    $method = $_SERVER['REQUEST_METHOD'];
    
		if($method != 'POST'){
			json_output(400,array('status' => 400,'message' => 'Bad request.'));
		}else{
      $content = json_decode(file_get_contents('php://input'), TRUE);
      $this->form_validation->set_data($content);
     
      $this->form_validation->set_rules("email","Email","trim|xss_clean|required|valid_email|callback_unique_email_check",
      array(
        'required' => "newsletter-email-required",
        'valid_email' => "newsletter-email-invalid"
      ));
    
      if($this->form_validation->run($this) == FALSE){
        $errors = $this->form_validation->error_array();
        json_output(200,array('status'=>'error','errorData'=>$errors));
      }else{
 
        $subscribe_data = array(
          "email" => strip_tags($content['email']),
          "created_date" => date("Y-m-d H:i:s")
        );
        
        $subscribe = $this->Mdl_api->insert("newsletter_subscriber",$subscribe_data);
        json_output(200,array('status'=>'success'));
      }
		}
  }
}
