<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH.'modules/generic/models/Mdl_generic.php';

/**
 * Author : Amit k
 * Dated  : 30/06/2020
 */
class Mdl_api extends Mdl_generic {

	function __construct(){
		parent::__construct();
	}

	function getTalentByFilter($lang, $filter, $categories, $start, $limit){
		$this->db->distinct();
		$this->db->select('r.uid, t.fullname_en, t.fullname_ar, t.profile_image, t.slug, t.halagram_price')
						->from('talent_details t')
						->join('registration r', 'r.registration_id = t.registration_id');

		if(! empty($categories)){
			$this->db->join('talent_category_master c', 'c.registration_id = t.registration_id');
			$this->db->where_in('c.category_id', $categories);
		}

		$this->db->where('r.application_status','approved');
		$this->db->where('r.account_status','active');

		if(! empty($filter)){
			if($lang == 'ar'){
				$this->db->where_in("LEFT(t.fullname_ar, 1)",$filter);
			}else{
				$this->db->where_in("LEFT(t.fullname_en, 1)",$filter);
			}
		}

		$this->db->limit($limit, $start);
		$query = $this->db->get();

		// echo $this->db->last_query();

		if($query->num_rows() > 0){
			return $query->result(); 
		}else{
			return "NA";
		} 
	}

	function totalTalentRecords($lang, $filter,$categories){
		$this->db->select('t.talent_id')
						->from('talent_details t')
						->join('registration r', 'r.registration_id = t.registration_id');
		
		if(! empty($categories)){
			$this->db->join('talent_category_master c', 'c.registration_id = t.registration_id');
			$this->db->where_in('c.category_id', $categories);
		}

		if(! empty($filter)){
			if($lang == 'ar'){
				$this->db->where_in("LEFT(t.fullname_ar, 1)",$filter);
			}else{
				$this->db->where_in("LEFT(t.fullname_en, 1)",$filter);
			}
		}
		
		$this->db->where('r.application_status','approved');
		$this->db->where('r.account_status','active');

		$query=$this->db->get();
		
		if($query->num_rows() > 0){
			return $query->num_rows(); 
		}else{
			return "NA";
		} 
	}

	function getWishlist($filter, $registration_id, $uid, $type, $start, $limit){
		$this->db->select('r.uid, t.fullname_en, t.fullname_ar, t.profile_image, t.slug, t.halagram_price')
						->from('talent_details t')
						->join('registration r', 'r.registration_id = t.registration_id')
						->join('wishlist w', 'w.talent_id = t.talent_id');

		$this->db->where('r.application_status','approved');
		$this->db->where('r.account_status','active');
		$this->db->where('w.registration_id',$registration_id);
		$this->db->where('w.type',$type);
		$this->db->where('w.uid',$uid);

		if(! empty($filter)){
			$this->db->where_in('LEFT(t.fullname_en, 1)', $filter);
		}

		$this->db->limit($limit, $start);
		$query = $this->db->get();

		// echo $this->db->last_query();

		if($query->num_rows() > 0){
			return $query->result(); 
		}else{
			return "NA";
		} 
	}

	function totalWishlistRecords($filter, $registration_id, $uid, $type){
		$this->db->select('t.talent_id')
						->from('talent_details t')
						->join('registration r', 'r.registration_id = t.registration_id')
						->join('wishlist w', 'w.talent_id = t.talent_id');

		if(! empty($filter)){
			$this->db->where_in("LEFT(t.fullname_en, 1)",$filter);
		}
		
		$this->db->where('r.application_status','approved');
		$this->db->where('r.account_status','active');
		$this->db->where('w.registration_id',$registration_id);
		$this->db->where('w.type',$type);
		$this->db->where('w.uid',$uid);

		$query=$this->db->get();
		
		if($query->num_rows() > 0){
			return $query->num_rows(); 
		}else{
			return "NA";
		} 
	}
}