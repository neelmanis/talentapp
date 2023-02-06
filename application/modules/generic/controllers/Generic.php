<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
use Aws\S3\S3Client;
require ('aws/vendor/autoload.php');
require ('vendor/autoload.php');

class Generic extends MX_Controller{
  public $global_variables;
  
	function __construct() {
    parent::__construct();  

		$this->global_variables = array(
      'web_assets' => base_url().'assets/web/',
      'script_version' => '_v_2.1',
    
      'front_end_url' => 'http://localhost:3000/',

      'mail_env'       => 'local',
      'mail_host'      => 'ssl://smtp.googlemail.com', 
      'mail_username'  => 'amitkashte1593@gmail.com',                    
      'mail_password'  => 'Amit@2020#',
      'mail_port'      => 465,
  
      'root'           => $_SERVER['DOCUMENT_ROOT'].'/halagram/',
      'ffmpeg'         => $_SERVER['DOCUMENT_ROOT'].'/halagram/ffmpeg/bin/ffmpeg.exe', 
      'ffprobe'        => $_SERVER['DOCUMENT_ROOT'].'/halagram/ffmpeg/bin/ffprobe.exe',      

      // 'front_end_url' => 'https://halagram.me/frontend/',

      // 'mail_env'       => 'live',
      // 'mail_host'      => 'email-smtp.us-east-1.amazonaws.com', 
      // 'mail_username'  => 'AKIARS54EEVD2DCUXWFO',                    
      // 'mail_password'  => 'BDluexWikLudnD2NeNIrSIs8bPI1E552lMqAjeecemxy',
      // 'mail_port'      => 587,

      // 'root'           => $_SERVER['DOCUMENT_ROOT'],
      // 'ffmpeg'         => '/usr/bin/ffmpeg', 
      // 'ffprobe'        => '/usr/bin/ffprobe'

      // 'aws_key'        => 'AKIARS54EEVD23VVP43R',
      // 'aws_secrete'    => 'UnkaMlpR+OoYHywEWQ2qs4YuJocoe7WeBs/10zi2',
      // 'aws_region'     => 'me-south-1',
      // 'aws_bucket'     => 'halagram',
      // 'aws_bucket_url' => 'https://halagram.s3.me-south-1.amazonaws.com/',

      'aws_key'        => 'AKIAIV3ZJIQH6OIS3BZQ',
      'aws_secrete'    => 'X07lAqTfMmxHYKjonLDIOOv9PJGfjk8Cy6fLRtYn',
      'aws_region'     => 'ap-south-1',
      'aws_bucket'     => 'talentapp2',
     // 'aws_bucket_url' => 'https://s3.ap-south-1.amazonaws.com/amit.halagram/',
	  'aws_bucket_url' => 'http://s3.ap-south-1.amazonaws.com/talentapp2/',


      'store_id'        => '24506',
      'auth_key'        => '7DxWH^GN7F~dr6LC',
      'remote_auth_key' => 'FMdDH~MXNZ^vvWgd',
      'test'            => '1',
       
      'jwt_key'        => 'kwebmaker@halagram@2020',
      'start_date'     => '01-09-2020'
    );
	}

public function index()
  {

    echo "test";
  }
/**
   * Function to generate UID
   */
  private function generateUID(){
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; 
    $uid = '';
    for ($i = 0; $i < 20; $i++) { 
      $index = rand(0, strlen($characters) - 1); 
      $uid .= $characters[$index]; 
    } 
    return $uid;
  }
  public function getUID(){
    $run_loop = true;
    while($run_loop){
      $uid = $this->generateUID();
      $check_uid = $this->Mdl_generic->isExist('user_registration',array('uid'=>$uid));
      if(!$check_uid){
        $run_loop = false;
      }
    }
    return $uid;
  }
    /**
   * AWS S3 functionality
   */
  function initialize(){
    $this->s3=S3Client::factory([
			'key'=> $this->global_variables['aws_key'],
			'secret'=> $this->global_variables['aws_secrete'],
      'region'=> $this->global_variables['aws_region'],
      'signature' => 'v4'
		]);	
  }

  function addObject($key, $file){
    $this->initialize();

    try{
      $result = $this->s3->putObject([
        'Bucket'=> $this->global_variables['aws_bucket'],
        'Key'=> $key,
        'SourceFile' => $_FILES[$file]['tmp_name'],
        'ContentType' => $_FILES[$file]['type'],
        'StorageClass' => 'STANDARD',
        'ACL' => 'public-read'
      ]);

      $response = array(
        'upload' => true,
        'path' => $result['ObjectURL']
      );
    }catch(S3Exception $e){
      $response = array(
        'upload' => false
      );
    }

    return $response;
  }

  function removeObject($path){
    $this->initialize();
    
    $aws_url_array = explode($this->global_variables['aws_bucket_url'], $path);
                
    if(sizeof($aws_url_array) > 1){
      $keyname = $aws_url_array[1];
      $result = $this->s3->deleteObject(array(
        'Bucket' => $this->global_variables['aws_bucket'],
        'Key'    => $keyname
      ));
    }
  }

 


}

