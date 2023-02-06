<?php defined('BASEPATH') OR exit('No direct script access allowed');

function json_output($statusHeader,$response){
	$ci =& get_instance();
	$ci->output->set_header('HTTP/1.0 200 OK');
	$ci->output->set_header('HTTP/1.1 200 OK');
	$ci->output->set_header('Cache-Control: no-store, no-cache, must-revalidate');
	// $ci->output->set_header('Cache-Control: post-check=0, pre-check=0');
	// $ci->output->set_header('Access-Control-Allow-Origin: *');
	$ci->output->set_header('Access-Control-Max-Age: 86000');
	$ci->output->set_status_header($statusHeader);
	$ci->output->set_output(json_encode($response));
}

