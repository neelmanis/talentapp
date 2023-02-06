<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Template extends MX_Controller {

	
	 function __construct()
	  {
        parent::__construct();
      }
	
   
	 function home($data)
    {
		$this->load->view('home', $data);
	}

}