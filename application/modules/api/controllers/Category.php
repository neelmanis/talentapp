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
		
			$category_query = "SELECT category_id, slug, category_name_en, category_name_ar FROM category_master WHERE is_parent='yes' AND status='active' ORDER BY display_order, category_name_en ";
			$category_master = $this->Mdl_api->customQuery($category_query);
		
			if($category_master !== "NA"){
				$response = array();
				$class_index = 0;

				foreach( $category_master as $category ){
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

					// $ids = Modules::run('api/talent/getCategoryIds',$category->slug);
					// $categories = explode(",",$ids);
        	// $talents = $this->Mdl_api->getTalentByFilter(array(), $categories, 0, 1);
					// if($talents !== 'NA'){
					// 	$temp['records'] = true;
					// }else{
					// 	$temp['records'] = false;
					// }

					$get_childrens_query = "SELECT category_id, slug, category_name_en, category_name_ar FROM category_master WHERE is_parent='no' AND parent_id='$category->category_id' AND status='active' ORDER BY category_name_en ASC";
					$childrens = $this->Mdl_api->customQuery($get_childrens_query);
					
					if($childrens !== "NA"){
						$temp['sub_cat_present'] = true;
						$temp['sub_categories'] = $childrens; 
					}else{
						$temp['sub_cat_present'] = false;
						$temp['sub_categories'] = array();
					}

					$response[] = $temp;
				}
				json_output(200, array('status'=>'success','categories'=>$response));
			}else{
				json_output(400,array('status'=>'fail','message'=>'Connection failed.'));
			}
		}
	}
}
