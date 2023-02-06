<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH.'modules/generic/controllers/Generic.php';
class Security extends Generic{
	function __construct() {
		parent::__construct();
		$this->load->model('Mdl_security');	
	}
	/**
	 * Create Hash value
	 */
	function makeHash($password){
		$options = array(
			'cost' => 12
		);
		$hash = password_hash($password, PASSWORD_BCRYPT, $options);
		echo $hash;
	}
	/**
	 * Verify Password
	 */
	function verifyPassoword($password, $hash){
		if(password_verify($password, $hash)){
			return TRUE;
		}else{
			return FALSE;
		}
	}

	/**
	 * Generate JWT Auth Token
	*/
	function generateAuthToken($uid){
		$token['uid'] = $uid;
    $key = $this->global_variables['jwt_key'];
    $token =  JWT::encode($token, $key);
    return $token;
	}
	/**
	 * Validate JWT Auth Token
	 */
	function validateAuthToken($token){
		$auth_details = $this->Mdl_security->retrieve('authentication',array('token'=>$token));
		if($auth_details == "NA"){
			return array("status"=>"invalid");
		} else {
			if(strtotime($auth_details[0]->expiry_time) < strtotime("now")){
				return array("status"=>"expired");
			} else {
				$key = $this->global_variables['jwt_key'];
				$token_value = JWT::decode($token, $key);
				if($token_value->uid === $auth_details[0]->uid){
					return array("status"=>"valid","registration_id"=>$auth_details[0]->registration_id,"type"=>$auth_details[0]->type,"uid"=>$auth_details[0]->uid);
				}else{
					return array("status"=>"invalid");
				}
			}
		}
	}
	/**
	 * Verify Admin 
	 */
	function isAdmin(){
		if($this->session->userdata('admin')){
			$user = $this->session->userdata('admin');
			if(is_numeric($user['id']) && $user['type']=="admin"){
				return TRUE;
			}else{
				return FALSE;
			}
		}else{
			return FALSE;
		}
	}

	/**
	 * Test Password Hash
	 */
	function test(){
		$options = array(
			'cost' => 12
		);

		$password = 'admin@halagram';
		echo $hash = password_hash($password, PASSWORD_BCRYPT, $options);
		echo '<br>';
		
		if(password_verify($password, $hash)){
			echo 'TRUE';
		} else {
			echo 'FALSE';
		}
	}
}