<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH.'modules/generic/controllers/Generic.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST,GET,PUT,DELETE");
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header("Access-Control-Max-Age: 86000");

class Banner extends Generic{

	function __construct() {
		parent::__construct();
		$this->load->model('Mdl_api');	
	}

	public function index(){
		$method = $_SERVER['REQUEST_METHOD'];

		if($method !== 'GET'){
			json_output(400,array('status' => 'fail','message' => 'Bad request.'));
		}else{
		
			$banner_text_query = "SELECT * FROM banner_text WHERE status='active' ORDER BY position ASC";
			$banner_text = $this->Mdl_api->customQuery($banner_text_query);

			$banner_image_query = "SELECT * FROM banner_image WHERE status='active' ORDER BY position ASC";
			$banner_image = $this->Mdl_api->customQuery($banner_image_query);

			$banner_video_query = "SELECT * FROM banner_video";
			$banner_video = $this->Mdl_api->customQuery($banner_video_query);
			
			$bannerText = array();
			$bannerImage = array();
			$bannerVideo = array(
				'image_one' => "",
				'image_two' => "",
				'video_one' => "",
				'video_two' => ""
			);

			if($banner_text !== "NA"){
				$bannerText = $banner_text;
			}

			if($banner_image !== "NA"){
				$bannerImage = $banner_image;
			}

			if($banner_video !== "NA"){
				$bannerVideo = array(
					'image_one' => $banner_video[0]->image_one,
					'image_two' => $banner_video[0]->image_two,
					'video_one' => $banner_video[0]->video_one,
					'video_two' => $banner_video[0]->video_two
				);
			}
			
			json_output(200,array('status'=>'success','bannerText'=>$bannerText,'bannerImage'=>$bannerImage,'bannerVideo'=>$bannerVideo));
		}
	}
}
