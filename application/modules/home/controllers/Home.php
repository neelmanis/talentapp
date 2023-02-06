<?php if (!defined('BASEPATH')) exit('No direct script access allowed');


class Home extends MX_Controller {

	
	 function __construct()
	  {
        parent::__construct();
        $this->load->model('Mdl_home');
      }
	public function index()
	{
		$template = 'home';
		$data['viewFile'] = "index";
		$data['scriptFile'] = "home";
		$data['module'] = "home";
		echo Modules::run('template/'.$template, $data);
	}




	
}