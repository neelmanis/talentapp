<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Mdl_generic extends CI_Model{
	function __construct() {
		parent::__construct();
		$this->load->model('Mdl_generic');
	}
	
	/**********************************************************************/
  /*                         GENERIC QUERIES  		                      */
	/**********************************************************************/
	
	/*
	**	INSERT QUERY
	*/
	function insert($table, $data){
		$this->db->insert($table, $data);
		$id = $this->db->insert_id();
		return $id;
	}

  /*
  **  INSERT MULTIPLE
  */
  function insert_batch($table, $data){
    $this->db->insert_batch($table, $data);
    return 1;
  }
	
	/*
	**	UPDATE QUERY
	*/
	function update($table, $param, $data){
		foreach($param as $key => $value){
      $this->db->where($key, $value);
    }
    return $this->db->update($table, $data);
	}

	/*
	**	DELETE QUERY
	*/
	function delete($table, $param){
		foreach($param as $key => $value){
			$this->db->where($key, $value);
		}
		return $this->db->delete($table);
	}
	
	/*
	**	RETRIEVE RECORDS
	*/
	function retrieve($table, $param){
		foreach($param as $key => $value){
			$this->db->where($key, $value);
		}

		$query=$this->db->get($table);
		
		if($query->num_rows() > 0){
			return $query->result(); 
		}else{
			return "NA";
		} 
	}
	/*
	**	RETRIEVE RECORDS By Order
	*/
	function retrieveByOrder($table, $param,$column,$order){
		foreach($param as $key => $value){
			$this->db->where($key, $value);
		}
		$this->db->order_by($column,$order);

		$query=$this->db->get($table);
		
		if($query->num_rows() > 0){
			return $query->result(); 
		}else{
			return "No Data";
		} 
	}

	/*
	**	FIND RECORD
	*/
	function isExist($table, $param){
		foreach($param as $key => $value){
			$this->db->where($key, $value);
		}
		$query=$this->db->get($table);
		if($query->num_rows() > 0){
			return TRUE; 
		}else{
			return FALSE;
		} 
	}
	/*
	**	isExist Query BY CUSTOM
	*/
	function isExistByCustom($mysql_query){
		$query = $this->db->query($mysql_query);
		if($query->num_rows() > 0){
			return TRUE; 
		}else{
			return FALSE;
		} 
	}
	/*
	**	GET RECORD  COUNT
	*/
	function getCount($table, $param){
			foreach($param as $key => $value){
			$this->db->where($key, $value);
		    }
			$query=$this->db->get($table);
			$count=$query->num_rows();
            return $count; 
	}
	/*
	**	GET RECORD  COUNT WITH CUSTOM QUERY
	*/
	function getCountByCustom($mysql_query){
			$query = $this->db->query($mysql_query);
			$count=$query->num_rows();
            return $count; 
	}
	
	/*
	**	CUSTOM QUERY
	*/
	function customQuery($query){
		$query = $this->db->query($query);
		if($query->num_rows() > 0){
	      return $query->result(); 
	    }else{
	      return "NA";
	    } 
	}
	//-------verify email
	function everify($cipher){

		if(!empty($cipher)) {
   
			//$email = $this->_mc_decrypt($cipher, ENCRYPTION_KEY);
                        $ids = base64_decode($cipher);
		$result = $this->retrieve('user_registration',array('id'=>$ids));
		
		if(!empty($result))
		{
			if(!empty($result[0]->email_id))
                   { 
                      $email = $result[0]->email_id;         
                   }     
                          
			if(!preg_match('/^(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){255,})(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){65,}@)(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22))(?:\.(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\]))$/iD', $email))
				return FALSE;
			$result = $this->retrieve('user_registration',array('email_id'=>$email));
			if(!$result || !sizeof($result))
				return FALSE;
			else {

				if($result[0]->is_verified == 'Y')
					return 'Your Email has already been Verified';

				$regId = $result[0]->id;
				if($this->update('user_registration',array('id'=>$regId), array('is_verified'=>'Y')))
					return 'Your Email verification is successful';

				return FALSE;
			}
			
			
			
		}
		//else echo "No record found";
                
		}
		else
			return FALSE;
	}



}