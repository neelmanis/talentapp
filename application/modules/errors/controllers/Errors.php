<?php if (!defined('BASEPATH')) exit('No direct script access allowed');


class Errors extends Generic{
	
	function __construct() {
		parent::__construct();
		$this->load->model('Mdl_errors');
	}

	/*
	**	404 Error Page 
	*/
	function index(){
		
		
		$data['viewFile'] = '404';
		$template = 'errors';
		$data['module'] = "errors";
		echo Modules::run('template/'.$template, $data); 
	}

}

