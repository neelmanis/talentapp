<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH.'modules/generic/controllers/Generic.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST,GET,PUT,DELETE");
header("Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authtoken");
header("Access-Control-Max-Age: 86000");

class Video extends Generic{

  function __construct() {
    parent::__construct();
    $this->load->model('Mdl_api');		
  }

  /**
   *  Case I
   */
  public function upload(){
    $method = $_SERVER['REQUEST_METHOD'];
    $headers = $this->input->request_headers();

    if($method !== 'POST'){
      // json_output(400, array('status' => 400,'message' => 'Bad request.'));
      $response = array(
        "Result"=>'',
        "Message"=>"bad request",
        "status"=>false
      );
    }else{

			// if(isset($headers['Authtoken'])){
      //   $token = $headers['Authtoken'];
      // }else{
      //   $token = $headers['authtoken'];
      // }
      // $check_token_validity = Modules::run('security/validateAuthToken',$token);

      $check_token_validity['status'] = "valid";

      if($check_token_validity['status'] === "invalid"){
        //json_output(200, array('status' => 'invalid token'));
        $response = array(
          "Result"=>'',
        	"Message"=>"invalid token",
          "status"=>false
        );
      }else if($check_token_validity['status'] === "expired"){
        // json_output(200, array('status' => 'expired'));
        $response = array(
          "Result"=>'',
        	"Message"=>"token expired",
          "status"=>false
        );
      }else if($check_token_validity['status'] === "valid"){
        // $registration_id = $check_token_validity['registration_id'];
        // $uid = $check_token_validity['uid'];
        // $type = $check_token_validity['type'];

        // $talent_details = $this->Mdl_services->retrieve('user_registration',array('id'=>$registration_id));

        $content = json_decode(file_get_contents('php://input'), TRUE);
        if($content['device'] !== "android" || $content['device'] !== "ios"){
          $device = null;
        }else{
          $device = $content['device'];
        }
        
        $custom_errors = array();        
        $allowed_type = array('mp4','MP4');

        if($_FILES['halagram']['name'] !== "" ){
          $filename = $_FILES['halagram']['name'];
          $ext = pathinfo($filename, PATHINFO_EXTENSION);
          
          if(! in_array($ext, $allowed_type)){
            $custom_errors['halagram'] = 'invalid video type';
          }else if($_FILES['halagram']['size'] > 100000000){
            $custom_errors['halagram'] = 'max file size allowed is 100 mb';
          }
        }else{
          $custom_errors['halagram'] = 'record and upload video';
        }

        if(! empty($custom_errors)){
          //json_output(200,array('status'=>'error','errorData'=>$custom_errors));
          $response=array(
						"Result"=>$custom_errors,
						"Message"=>"error",
						"status"=>false
  				);
        }else{
					if(! empty($_FILES['halagram']['name'])){

            $video_key = $this->getVideoKey();

            $insert_video_data = array(
              "key" => $video_key,
              "device" => $device,
              "is_uploaded" => "no",
              "thumbnail" => null,
              "raw_file" => null,
              "final_file" => null,
              "created_date" => date("Y-m-d H:i:s"),
              "modified_date" => date("Y-m-d H:i:s")
            );
            $insert_video = $this->Mdl_api->insert("video_test_app",$insert_video_data);

            $video_raw_file = 'raw-'.$video_key.'.mp4';
            $video_raw_upload_path = './assets/app/';
            $upload = $this->uploadFile($video_raw_file,$video_raw_upload_path,'0','halagram');

            $raw_video_path = base_url().'assets/app/'.$video_raw_file;
            $thumbnail_path = base_url().'assets/thumbnail/'.$video_key.'.jpg';

            if($upload !== 1){
              $response = array(
                "Result"=>"",
                "Message"=>"video upload failed",
                "status"=>false
              );
            }else{
              $raw_video = 'assets/app/'.$video_raw_file;
              $final_video = 'assets/app/'.$video_key.'.mp4';
              $thumb_path = 'assets/thumbnail/'.$video_key.'.jpg';
              $key = $video_key.'.mp4';
            
              $this->getVideoThumbnail($raw_video, $thumb_path);
              $add_video_watermark = $this->addVideoWatermark($raw_video, $final_video, $key);

              if($add_video_watermark['upload']){

                $updated_video_data = array(
                  "is_uploaded" => "yes",
                  "thumbnail" => $thumbnail_path,
                  "raw_file" => $raw_video_path,
                  "final_file" => $add_video_watermark['path'],
                  "modified_date" => date("Y-m-d H:i:s")
                );
                $update_video = $this->Mdl_api->update("video_test_app",array('key' => $video_key),$updated_video_data);
    
                // json_output(200, array('status'=>'success', 'welcomeVideo'=>$upload_response['path'] ));
                $response=array(
                  "Result"=>"",
                  "Message"=>"video uploaded",
                  "status"=>true
                );
              }else{
                // json_output(200, array('status'=>'error', 'errorData'=> array('welcomeVideo'=>"upload-failed") ));
                $response=array(
                  "Result"=>'',
                  "Message"=>"video upload failed",
                  "status"=>false
                );
              }
            }
          }else{
            //json_output(200, array('status'=>'fail', 'profileImage'=>"image-required" ));
            $custom_errors['halagram'] = 'record and upload video';
            $response=array(
              "Result"=>$custom_errors,
              "Message"=>"error",
              "status"=>false
            );
          }
        }
      }
    }

    header('Content-type: application/json');
    echo json_encode(array("Response"=>$response));
  }

  /**
   *  Case II
   */
  /*
  public function upload(){
    $method = $_SERVER['REQUEST_METHOD'];
    $headers = $this->input->request_headers();

    if($method !== 'POST'){
      // json_output(400, array('status' => 400,'message' => 'Bad request.'));
      $response = array(
        "Result"=>'',
        "Message"=>"bad request",
        "status"=>false
      );
    }else{
      
			// if(isset($headers['Authtoken'])){
      //   $token = $headers['Authtoken'];
      // }else{
      //   $token = $headers['authtoken'];
      // }
      // $check_token_validity = Modules::run('security/validateAuthToken',$token);

      $check_token_validity['status'] = "valid";

      if($check_token_validity['status'] === "invalid"){
        //json_output(200, array('status' => 'invalid token'));
        $response = array(
          "Result"=>'',
        	"Message"=>"invalid token",
          "status"=>false
        );
      }else if($check_token_validity['status'] === "expired"){
        // json_output(200, array('status' => 'expired'));
        $response = array(
          "Result"=>'',
        	"Message"=>"token expired",
          "status"=>false
        );
      }else if($check_token_validity['status'] === "valid"){
        // $registration_id = $check_token_validity['registration_id'];
        // $uid = $check_token_validity['uid'];
        // $type = $check_token_validity['type'];

        // $talent_details = $this->Mdl_services->retrieve('user_registration',array('id'=>$registration_id));

        $content = json_decode(file_get_contents('php://input'), TRUE);
        if($content['device'] !== "android" || $content['device'] !== "ios"){
          $device = null;
        }else{
          $device = $content['device'];
        }

        $custom_errors = array();        
        $allowed_type = array('mp4','MP4');

        if($_FILES['halagram']['name'] !== "" ){
          $filename = $_FILES['halagram']['name'];
          $ext = pathinfo($filename, PATHINFO_EXTENSION);
          
          if(! in_array($ext, $allowed_type)){
            $custom_errors['halagram'] = 'invalid video type';
          }else if($_FILES['halagram']['size'] > 100000000){
            $custom_errors['halagram'] = 'max file size allowed is 100 mb';
          }
        }else{
          $custom_errors['halagram'] = 'record and upload video';
        }

        if(! empty($custom_errors)){
          //json_output(200,array('status'=>'error','errorData'=>$custom_errors));
          $response=array(
						"Result"=>$custom_errors,
						"Message"=>"error",
						"status"=>false
  				);
        }else{
					if(! empty($_FILES['halagram']['name'])){

            $video_key = $this->getVideoKey();

            $insert_video_data = array(
              "key" => $video_key,
              "device" => $device,
              "is_uploaded" => "no",
              "thumbnail" => null,
              "raw_file" => null,
              "final_file" => null,
              "created_date" => date("Y-m-d H:i:s"),
              "modified_date" => date("Y-m-d H:i:s")
            );
            $insert_video = $this->Mdl_api->insert("video_test_app",$insert_video_data);

            $video_file = $video_key.'.mp4';
            $video_upload_path = './assets/app/';
            $upload = $this->uploadFile($video_file,$video_upload_path,'0','halagram');

            $thumbnail_path = base_url().'assets/thumbnail/'.$video_key.'.jpg';

            if($upload !== 1){
              $response = array(
                "Result"=>"",
                "Message"=>"video upload failed",
                "status"=>false
              );
            }else{
              $final_video = 'assets/app/'.$video_key.'.mp4';
              $thumb_path = 'assets/thumbnail/'.$video_key.'.jpg';
              $key = $video_key.'.mp4';
            
              $this->getVideoThumbnail($final_video, $thumb_path);
              $upload_to_s3 = $this->uploadToS3($final_video, $key);

              if($upload_to_s3['upload']){

                $updated_video_data = array(
                  "is_uploaded" => "yes",
                  "thumbnail" => $thumbnail_path,
                  "raw_file" => null,
                  "final_file" => $upload_to_s3['path'],
                  "modified_date" => date("Y-m-d H:i:s")
                );
                $update_video = $this->Mdl_api->update("video_test_app",array('key' => $video_key),$updated_video_data);
    
                // json_output(200, array('status'=>'success', 'welcomeVideo'=>$upload_response['path'] ));
                $response=array(
                  "Result"=>"",
                  "Message"=>"video uploaded",
                  "status"=>true
                );
              }else{
                // json_output(200, array('status'=>'error', 'errorData'=> array('welcomeVideo'=>"upload-failed") ));
                $response=array(
                  "Result"=>'',
                  "Message"=>"video upload failed",
                  "status"=>false
                );
              }
            }
          }else{
            //json_output(200, array('status'=>'fail', 'profileImage'=>"image-required" ));
            $custom_errors['halagram'] = 'record and upload video';
            $response=array(
              "Result"=>$custom_errors,
              "Message"=>"error",
              "status"=>false
            );
          }
        }
      }
    }

    header('Content-type: application/json');
    echo json_encode(array("Response"=>$response));
  }
  */

  public function records(){
    $method = $_SERVER['REQUEST_METHOD'];
    $headers = $this->input->request_headers();

		if($method !== 'GET'){
			json_output(400, array('status' => 400,'message' => 'Bad request.'));
		}else{
      
      $get_videos = "SELECT * FROM  video_test_app WHERE is_uploaded='yes' ";
      $videos = $this->Mdl_api->customQuery($get_videos);

      if($videos !== "NA"){
        $halagrams = 'No Records';
        $response = array();
        foreach($videos as $val){
          $temp = array();
          $temp['halagramLink'] = $val->final_file;
          $temp['poster'] = $val->thumbnail;
          $response[] = $temp;
        }
        $halagrams = $response;
      }

      json_output(200, array('status' => 'success', 'records' => array( 'halagrams' => $halagrams ) ));
    }
  }
}