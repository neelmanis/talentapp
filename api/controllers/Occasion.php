<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH.'modules/generic/controllers/Generic.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST,GET,PUT,DELETE");
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header("Access-Control-Max-Age: 86000");

class Occasion extends Generic{

	function __construct() {
		parent::__construct();
		$this->load->model('Mdl_api');	
	}

	public function index(){
		$method = $_SERVER['REQUEST_METHOD'];

		if($method !== 'GET'){
			json_output(400,array('status' => 'fail','message' => 'Bad request.'));
		}else{
			
			$occasion_en_query = "SELECT occasion_id, occasion_name_en, occasion_name_ar FROM occasion_master WHERE status='active' ORDER BY occasion_name_en ASC";
			$occasionEn = $this->Mdl_api->customQuery($occasion_en_query);
		
			$occasion_ar_query = "SELECT occasion_id, occasion_name_en, occasion_name_ar FROM occasion_master WHERE status='active' ORDER BY occasion_name_ar ASC";
			$occasionAr = $this->Mdl_api->customQuery($occasion_ar_query);

			if($occasionEn !== "NA" && $occasionAr !== "NA"){
				json_output(200, array('status'=>'success','occasions'=>array("en"=>$occasionEn, "ar"=>$occasionAr)));
			}else{
				json_output(400,array('status'=>'fail','message'=>'Connection failed.'));
			}
		}
	}
}
