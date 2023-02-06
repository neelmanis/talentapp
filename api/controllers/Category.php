<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH.'modules/generic/controllers/Generic.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST,GET,PUT,DELETE");
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header("Access-Control-Max-Age: 86000");

class Category extends Generic{

	function __construct() {
		parent::__construct();
		$this->load->model('Mdl_api');	
	}

	public function index(){
		$method = $_SERVER['REQUEST_METHOD'];

		if($method !== 'GET'){
			json_output(400,array('status' => 'fail','message' => 'Bad request.'));
		}else{
		
			$category_en_query = "SELECT category_id, slug, category_name_en, category_name_ar FROM category_master WHERE is_parent='yes' AND status='active' ORDER BY display_order, category_name_en ASC";
			$categoryEn = $this->Mdl_api->customQuery($category_en_query);
		
			$category_ar_query = "SELECT category_id, slug, category_name_en, category_name_ar FROM category_master WHERE is_parent='yes' AND status='active' ORDER BY display_order, category_name_ar ASC";
			$categoryAr = $this->Mdl_api->customQuery($category_ar_query);


			if($categoryEn !== "NA" && $categoryAr !== "NA"){
				$categoryEnArr = array();
				$class_index = 0;
				foreach( $categoryEn as $category ){
					$temp = array();
					$temp['category_id'] = $category->category_id;
					$temp['category_name_en'] = $category->category_name_en;
					$temp['category_name_ar'] = $category->category_name_ar;
					$temp['slug'] = $category->slug;
					$temp['class_index'] = $class_index;
					
					if($class_index == 5){
						$class_index = 0;
					}else{
						$class_index += 1;
					}

					$get_childrens_query = "SELECT category_id, slug, category_name_en, category_name_ar FROM category_master WHERE is_parent='no' AND parent_id='$category->category_id' AND status='active' ORDER BY category_name_en ASC";
					$childrens = $this->Mdl_api->customQuery($get_childrens_query);
					
					if($childrens !== "NA"){
						$temp['sub_cat_present'] = true;
						$temp['sub_categories'] = $childrens; 
					}else{
						$temp['sub_cat_present'] = false;
						$temp['sub_categories'] = array();
					}

					$categoryEnArr[] = $temp;
				}

				$categoryArArr = array();
				$class_index = 0;
				foreach( $categoryAr as $category ){
					$temp = array();
					$temp['category_id'] = $category->category_id;
					$temp['category_name_en'] = $category->category_name_en;
					$temp['category_name_ar'] = $category->category_name_ar;
					$temp['slug'] = $category->slug;
					$temp['class_index'] = $class_index;
					
					if($class_index == 5){
						$class_index = 0;
					}else{
						$class_index += 1;
					}

					$get_childrens_query = "SELECT category_id, slug, category_name_en, category_name_ar FROM category_master WHERE is_parent='no' AND parent_id='$category->category_id' AND status='active' ORDER BY category_name_ar ASC";
					$childrens = $this->Mdl_api->customQuery($get_childrens_query);
					
					if($childrens !== "NA"){
						$temp['sub_cat_present'] = true;
						$temp['sub_categories'] = $childrens; 
					}else{
						$temp['sub_cat_present'] = false;
						$temp['sub_categories'] = array();
					}

					$categoryArArr[] = $temp;
				}

				$response = array(
					"en" => $categoryEnArr,
					"ar" => $categoryArArr
				);

				json_output(200, array('status'=>'success','categories'=>$response));
			}else{
				json_output(400,array('status'=>'fail','message'=>'Connection failed.'));
			}
		}
	}

	// public function display(){
	// 	$method = $_SERVER['REQUEST_METHOD'];

	// 	if($method !== 'GET'){
	// 		json_output(400,array('status' => 'fail','message' => 'Bad request.'));
	// 	}else{
		
	// 		$category_en_query = "SELECT category_id, slug, category_name_en, category_name_ar FROM category_master WHERE is_parent='yes' AND status='active' AND category_id IN ( SELECT DISTINCT category_id from talent_category_master WHERE talent_id IN ( SELECT talent_id FROM talent_details t INNER JOIN registration r ON t.registration_id=r.registration_id WHERE r.profile_status='active' ) ) ORDER BY display_order, category_name_en ASC";
	// 		$categoryEn = $this->Mdl_api->customQuery($category_en_query);
		
	// 		$category_ar_query = "SELECT category_id, slug, category_name_en, category_name_ar FROM category_master WHERE is_parent='yes' AND status='active' AND category_id IN ( SELECT DISTINCT category_id from talent_category_master WHERE talent_id IN ( SELECT talent_id FROM talent_details t INNER JOIN registration r ON t.registration_id=r.registration_id WHERE r.profile_status='active' ) ) ORDER BY display_order, category_name_ar ASC";
	// 		$categoryAr = $this->Mdl_api->customQuery($category_ar_query);

	// 		if($categoryEn !== "NA" && $categoryAr !== "NA"){
	// 			$categoryEnArr = array();
	// 			$class_index = 0;
	// 			foreach( $categoryEn as $category ){
	// 				$temp = array();
	// 				$temp['category_id'] = $category->category_id;
	// 				$temp['category_name_en'] = $category->category_name_en;
	// 				$temp['category_name_ar'] = $category->category_name_ar;
	// 				$temp['slug'] = $category->slug;
	// 				$temp['class_index'] = $class_index;
					
	// 				if($class_index == 5){
	// 					$class_index = 0;
	// 				}else{
	// 					$class_index += 1;
	// 				}

	// 				$get_childrens_query = "SELECT category_id, slug, category_name_en, category_name_ar FROM category_master WHERE is_parent='no' AND parent_id='$category->category_id' AND status='active' ORDER BY category_name_en ASC";
	// 				$childrens = $this->Mdl_api->customQuery($get_childrens_query);
					
	// 				if($childrens !== "NA"){
	// 					$temp['sub_cat_present'] = true;
	// 					$temp['sub_categories'] = $childrens; 
	// 				}else{
	// 					$temp['sub_cat_present'] = false;
	// 					$temp['sub_categories'] = array();
	// 				}

	// 				$categoryEnArr[] = $temp;
	// 			}

	// 			$categoryArArr = array();
	// 			$class_index = 0;
	// 			foreach( $categoryAr as $category ){
	// 				$temp = array();
	// 				$temp['category_id'] = $category->category_id;
	// 				$temp['category_name_en'] = $category->category_name_en;
	// 				$temp['category_name_ar'] = $category->category_name_ar;
	// 				$temp['slug'] = $category->slug;
	// 				$temp['class_index'] = $class_index;
					
	// 				if($class_index == 5){
	// 					$class_index = 0;
	// 				}else{
	// 					$class_index += 1;
	// 				}

	// 				$get_childrens_query = "SELECT category_id, slug, category_name_en, category_name_ar FROM category_master WHERE is_parent='no' AND parent_id='$category->category_id' AND status='active' ORDER BY category_name_ar ASC";
	// 				$childrens = $this->Mdl_api->customQuery($get_childrens_query);
					
	// 				if($childrens !== "NA"){
	// 					$temp['sub_cat_present'] = true;
	// 					$temp['sub_categories'] = $childrens; 
	// 				}else{
	// 					$temp['sub_cat_present'] = false;
	// 					$temp['sub_categories'] = array();
	// 				}

	// 				$categoryArArr[] = $temp;
	// 			}

	// 			$response = array(
	// 				"en" => $categoryEnArr,
	// 				"ar" => $categoryArArr
	// 			);

	// 			json_output(200, array('status'=>'success','categories'=>$response));
	// 		}else{
	// 			json_output(400,array('status'=>'fail','message'=>'Connection failed.'));
	// 		}
	// 	}
	// }

	public function display(){
		$method = $_SERVER['REQUEST_METHOD'];

		if($method !== 'GET'){
			json_output(400,array('status' => 'fail','message' => 'Bad request.'));
		}else{
		
			$category_en_query = "SELECT category_id, slug, category_name_en, category_name_ar FROM category_master WHERE is_parent='yes' AND status='active' AND category_id IN ( SELECT DISTINCT category_id from talent_category_master WHERE talent_id IN ( SELECT talent_id FROM talent_details t INNER JOIN registration r ON t.registration_id=r.registration_id WHERE r.profile_status='active' ) ) ORDER BY display_order, category_name_en ASC";
			$categoryEn = $this->Mdl_api->customQuery($category_en_query);
		
			$category_ar_query = "SELECT category_id, slug, category_name_en, category_name_ar FROM category_master WHERE is_parent='yes' AND status='active' AND category_id IN ( SELECT DISTINCT category_id from talent_category_master WHERE talent_id IN ( SELECT talent_id FROM talent_details t INNER JOIN registration r ON t.registration_id=r.registration_id WHERE r.profile_status='active' ) ) ORDER BY display_order, category_name_ar ASC";
			$categoryAr = $this->Mdl_api->customQuery($category_ar_query);

			if($categoryEn !== "NA" && $categoryAr !== "NA"){
				$categoryEnArr = array();
				$class_index = 0;
				foreach( $categoryEn as $category ){
					$temp = array();
					$temp['category_id'] = $category->category_id;
					$temp['category_name_en'] = $category->category_name_en;
					$temp['category_name_ar'] = $category->category_name_ar;
					$temp['slug'] = $category->slug;
					$temp['class_index'] = $class_index;
					
					if($class_index == 5){
						$class_index = 0;
					}else{
						$class_index += 1;
					}

					$get_childrens_query = "SELECT category_id, slug, category_name_en, category_name_ar FROM category_master WHERE is_parent='no' AND parent_id='$category->category_id' AND status='active' ORDER BY category_name_en ASC";
					$childrens = $this->Mdl_api->customQuery($get_childrens_query);
					
					if($childrens !== "NA"){
						$temp['sub_cat_present'] = true;
						$temp['sub_categories'] = $childrens; 
					}else{
						$temp['sub_cat_present'] = false;
						$temp['sub_categories'] = array();
					}

					$categoryEnArr[] = $temp;
				}

				$categoryArArr = array();
				$class_index = 0;
				foreach( $categoryAr as $category ){
					$temp = array();
					$temp['category_id'] = $category->category_id;
					$temp['category_name_en'] = $category->category_name_en;
					$temp['category_name_ar'] = $category->category_name_ar;
					$temp['slug'] = $category->slug;
					$temp['class_index'] = $class_index;
					
					if($class_index == 5){
						$class_index = 0;
					}else{
						$class_index += 1;
					}

					$get_childrens_query = "SELECT category_id, slug, category_name_en, category_name_ar FROM category_master WHERE is_parent='no' AND parent_id='$category->category_id' AND status='active' ORDER BY category_name_ar ASC";
					$childrens = $this->Mdl_api->customQuery($get_childrens_query);
					
					if($childrens !== "NA"){
						$temp['sub_cat_present'] = true;
						$temp['sub_categories'] = $childrens; 
					}else{
						$temp['sub_cat_present'] = false;
						$temp['sub_categories'] = array();
					}

					$categoryArArr[] = $temp;
				}

				$response = array(
					"en" => $categoryEnArr,
					"ar" => $categoryArArr
				);

				json_output(200, array('status'=>'success','categories'=>$response));
			}else{
				$response = array(
					"en" => array(),
					"ar" => array()
				);

				json_output(400,array('status'=>'success','categories'=>$response));
			}
		}
	}
}
