<?php defined('BASEPATH') or exit('No direct script access allowed');

class Plan extends CI_Controller{

    function __construct()
    {
        parent::__construct();
        $this->load->database();

        $this->load->model('task_model');
        $this->load->model('plan_model');
        $this->load->model('goal_model');

        $this->settings_library->set_settings();
    }
    

    function plan_statistics(){

        $plan_id = isset($_GET['plan_id']) ? $_GET['plan_id'] : 0;
        $target_date = isset($_GET['plan_end_date']) ? $_GET['plan_end_date'] : date('Y-m-d');

        $stats['data']['count_plan_goals'] = $this->goal_model->count_plan_goals($plan_id);
        $stats['data']['count_plan_due_tasks']  = $this->task_model->count_plan_due_tasks($plan_id);
        $stats['data']['count_plan_tasks']  = $this->task_model->count_plan_tasks($plan_id);
        $stats['data']['count_overdue_plan_tasks']  = $this->task_model->count_overdue_plan_tasks($target_date, $plan_id);

        $stats["status"] = "success";

        return $stats;
    }
    

    function add_plan(){
        $post = $this->input->post();

        $plan_start_date = $post['plan_start_date'];
        $user_id = $post['user_id'];
        $plan_name = $post['plan_name'];
        $plan_end_date = $post['plan_end_date'];

        $fy = $this->settings_library->get_fy($plan_start_date);

        $deactivate_user_active_plans = $this->plan_model->deactivate_user_active_plans($user_id, $fy);
        
        $rst = [];

        $rst['status'] = "failed";

        if($deactivate_user_active_plans){

            $data['plan_name'] = $plan_name;
            $data['plan_start_date'] = $plan_start_date;
            $data['plan_end_date'] = $plan_end_date;
            $data['plan_year'] = $fy;
            $data['plan_status'] = 1;
            $data['user_id'] = $user_id;
            $data['plan_created_by'] = $user_id;
            $data['plan_created_date'] = date('Y-m-d');
            $data['plan_last_modified_by'] = $user_id;
    
            $this->db->insert('plan', $data);
    
            if ($this->db->affected_rows()) {
                $rst['data']['plan_id'] = $this->db->insert_id();
                $rst['status'] = 'success';
            } else {
                $rst['status'] = "failed";
            }

        }

        return $rst;

    }

    
    function plan()
    {

        $plan_id = isset($_GET['plan_id']) ? $_GET['plan_id'] : 0;
        $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : 0;
        $plan_status = isset($_GET['plan_status']) ? $_GET['plan_status'] : 0;
        $inactive_plans_only = isset($_GET['inactive_plans_only']) ? $_GET['inactive_plans_only'] : 0;

        $plans['data'] = [];

        $this->db->select(array(
            'plan_id', 'plan_name', 'plan_start_date',
            'plan_end_date', 'plan_status', 'user_first_name', 'user_last_name', 'plan_created_date'
        ));

        if($plan_id > 0){
            $this->db->where(array('plan_id' => $plan_id));
        }

        if($user_id > 0){
            $this->db->where(array('plan.user_id' => $user_id));
        }

        if($plan_status > 0){
            $this->db->where(array('plan_status' =>  $plan_status));
        }

        if($inactive_plans_only == 1 && $plan_status == 0){
            $this->db->where(array('plan_status' =>  $plan_status));
        }
        
        $this->db->where(array('plan.deleted_at' => NULL));
        $this->db->where(array('user.deleted_at' => NULL));

        $this->db->join('user', 'user.user_id=plan.plan_created_by');
        $plan_obj = $this->db->get('plan');

        if($plan_obj->num_rows() > 0){
            $plans['data'] = $plan_obj->result_array();
        }

        $plans["status"] = "success";

        return $plans;
    }

    function edit_plan(){

        $post = $this->input->post();
        // $plan_id = isset($_GET['plan_id']) ? $_GET['plan_id'] : 0;

        // $plan = [];

        // $this->db->where(array('plan_id' => $plan_id));
        // $this->db->update('plan',$post);

        // if($this->db->affected_rows() > 0){
        //     $plan = $this->plan();
        // }

        return $_SERVER;
    }

    function delete_plan(){
        $plan_id = isset($_GET['plan_id']) ? $_GET['plan_id'] : 0;

        $this->db->where(array('plan_id' => $plan_id));
        $this->db->delete('plan');

        if($this->db->affected_rows() > 0){
            $plan = ['plan_id' =>$plan_id ];
        }

        return $plan;
    }


    function api_result($method_call, ...$args){
        $method_call_result = call_user_func_array(array($this, $method_call),$args);
        echo json_encode($method_call_result, JSON_PRETTY_PRINT);
    }
    
}

