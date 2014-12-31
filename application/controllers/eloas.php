<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ELOAs extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('eloa_model');
    }
    
    /**
     * PRE-FLIGHT
     */
    public function index_options() { $this->response(array('status' => true)); }
    public function view_options() { $this->response(array('status' => true)); }
    
    /**
     * INDEX
     */
    public function index_get($member_id = FALSE) {
		// Must have permission to view this type of record for this member or for any member
		if( ! $this->user->permission('eloas_view_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        }
		// View records
		else {
			$skip = $this->input->get('skip') ? $this->input->get('skip') : 0;
			$model = $this->eloa_model;
			if($member_id) {
			    $model->by_member($member_id);
			}
            if($this->input->get('active')) {
                $model->active();
            }
			$eloas = nest($model->paginate('', $skip)->result_array());
			$count = $this->eloa_model->total_rows;
			$this->response(array('status' => true, 'count' => $count, 'skip' => $skip, 'eloas' => $eloas));
		}
    }
    
    /**
     * VIEW
     */
    //public function view_get($loa_id) {}
    
    /**
     * CREATE
     */
    public function index_post() {
        // Must have permission to create this member's eloas or any member's eloas
        if( ! $this->user->permission('eloas_add', $this->post('member_id')) && ! $this->user->permission('eloas_add_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        }
        // Form validation
        else if($this->eloa_model->run_validation('validation_rules_add') === FALSE) {
            $this->response(array('status' => false, 'error' => $this->eloa_model->validation_errors), 400);
        }
        // Ensure start date is not in the past
        else if(strtotime($this->post('start_date')) < strtotime('midnight')) {
            $this->response(array('status' => false, 'error' => 'Start date cannot be in the past'), 400);
        }
        // Ensure start date is not after end date
        else if(strtotime($this->post('start_date')) > strtotime($this->post('end_date'))) {
            $this->response(array('status' => false, 'error' => 'Start date cannot be after end date'), 400);
        }
        // Update record
        else {
            $this->usertracking->track_this();
            $data = whitelist($this->post(), array('member_id', 'start_date', 'end_date', 'reason', 'availability'));

            // Set datetime of posting
            $data['posting_date'] = format_date('now', 'mysqldatetime');

            // Clean dates
            $data['start_date'] = format_date($data['start_date'], 'mysqldate');
            $data['end_date'] = format_date($data['end_date'], 'mysqldate');
            
            $insert_id = $this->eloa_model->save(NULL, $data);
            $new_record = $insert_id ? nest($this->eloa_model->get_by_id($insert_id)) : null;
            $this->response(array('status' => $insert_id ? true : false, 'eloa' => $new_record));
        }
    }
    
    /**
     * UPDATE
     */
    public function view_post($eloa_id) {
        // Must have permission to edit this member's eloas or any member's eloas
        $eloa = nest($this->eloa_model->get_by_id($eloa_id));
        if( ! $this->user->permission('eloas_add', $eloa['member']['id']) && ! $this->user->permission('eloas_add_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        }
        // Form validation
        /*else if($this->eloa_model->run_validation('validation_rules_edit') === FALSE) {
            $this->response(array('status' => false, 'error' => $this->eloa_model->validation_errors), 400);
        }*/
        // Ensure start date is not in the past
        else if(strtotime($this->post('start_date')) < strtotime('midnight')) {
            $this->response(array('status' => false, 'error' => 'Start date cannot be in the past'), 400);
        }
        // Ensure start date is not after end date
        else if(strtotime($this->post('start_date')) > strtotime($this->post('end_date'))) {
            $this->response(array('status' => false, 'error' => 'Start date cannot be after end date'), 400);
        }
        // Update record
        else {
            $this->usertracking->track_this();
            $data = whitelist($this->post(), array('start_date', 'end_date', 'reason', 'availability'));
                
            $result = $this->eloa_model->save($eloa_id, $data);
            
            $this->response(array('status' => $result ? true : false, 'eloa' => nest($this->eloa_model->get_by_id($eloa_id))));
        }
    }
}