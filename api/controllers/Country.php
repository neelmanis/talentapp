<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH.'modules/generic/controllers/Generic.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST,GET,PUT,DELETE");
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header("Access-Control-Max-Age: 86000");

class Country extends Generic{

	function __construct() {
		parent::__construct();
		$this->load->model('Mdl_api');	
	}

	public function index(){
		$method = $_SERVER['REQUEST_METHOD'];

		if($method !== 'GET'){
			json_output(400,array('status' => 400,'message' => 'Bad request.'));
		}else{
		
			$country_en_query = "SELECT country_id, country_name_en, country_name_ar, phonecode, shortname FROM country_master WHERE status='active' ORDER BY country_name_en ASC";		
			$countryEn = $this->Mdl_api->customQuery($country_en_query);
		
			$country_ar_query = "SELECT country_id, country_name_en, country_name_ar, phonecode, shortname FROM country_master WHERE status='active' ORDER BY country_name_ar ASC";		
			$countryAr = $this->Mdl_api->customQuery($country_ar_query);

			$response = array(
				"en" => $countryEn,
				"ar" => $countryAr
			);

			if($response !== "NA"){
				json_output(200, array('status'=>'success','countries'=>$response));
			}else{
				json_output(400,array('status'=>'fail','message'=>'Connection failed.'));
			}
		}
	}
}
