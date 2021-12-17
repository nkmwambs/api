<?php defined('BASEPATH') or exit('No direct script access allowed');

class Goal extends CI_Controller{

    function __construct()
    {
        parent::__construct();
        $this->load->database();

        $this->load->model('task_model');
        $this->load->model('plan_model');

        $this->settings_library->set_settings();
    }
    

    function goal_statistics(){

        $goal_id = isset($_GET['goal_id']) ? $_GET['goal_id'] : 0;
        $target_date = isset($_GET['target_date']) ? $_GET['target_date'] : 0;

        $stats['data']['count_goal_due_tasks'] = $this->task_model->count_goal_due_tasks($goal_id);
        $stats['data']['count_goal_complete_tasks']  = $this->task_model->count_goal_complete_tasks($goal_id);
        $stats['data']['count_goal_overdue_tasks']  = $this->task_model->count_goal_overdue_tasks($goal_id, $target_date);
        $stats['data']['count_goal_all_tasks']  = $this->task_model->count_all_goal_tasks($goal_id);

        $stats["status"] = "success";

        return $stats;
    }

    function goal()
    {

        $plan_id = isset($_GET['plan_id']) ? $_GET['plan_id'] : 0;
        $goal_id = isset($_GET['goal_id']) ? $_GET['goal_id'] : 0;

        $this->db->select(array(
            'goal_id', 'goal_name', 'plan_name', 'theme_name', 'goal_start_date',
            'goal_end_date', 'goal.user_id as user_id','plan.plan_id', 'goal_created_date'
        ));

        $this->db->join('plan', 'plan.plan_id=goal.plan_id');
        $this->db->join('theme', 'theme.theme_id=goal.theme_id');
        $this->db->order_by('theme.theme_id', 'goal_id');

        if ($plan_id > 0) {
            $this->db->where(array('goal.plan_id' => $plan_id));
        }

        if ($goal_id > 0) {
            $this->db->where(array('goal.goal_id' => $goal_id));
        }

        $this->db->where(array('plan.deleted_at' => NULL));
        $this->db->where(array('theme.deleted_at' => NULL));
        $this->db->where(array('goal.deleted_at' => NULL));

        $goals_with_task_count = [];

        $result = $this->db->get("goal")->result_array();

        foreach ($result as $goal) {
            $goal['count_of_tasks'] = $this->task_model->count_goal_tasks($goal['goal_id']);
            $goal['count_new_tasks'] = $this->task_model->count_goal_tasks($goal['goal_id'],0);
            $goal['count_inprogress_tasks'] = $this->task_model->count_goal_tasks($goal['goal_id'],1);
            $goal['count_completed_tasks'] = $this->task_model->count_goal_tasks($goal['goal_id'],2);

            $goals_with_task_count[] = $goal;
        }

        $goals["data"] = $goals_with_task_count;

        $goals["status"] = "success";

        return $goals;
    }


    function add_goal()
    {
        $post = $this->input->post();

        $plan_id = $post['plan_id'];
        $goal_period = $post['goal_period'];
        $goal_name = $post['goal_name'];
        $theme_id = $post['theme_id'];
        $goal_description = $post['goal_description'];
        $creating_user_id = $post['user_id'];

        $rst['status'] = 'success';

        $plan_record = $this->db->get_where('plan',array('plan_id' => $plan_id))->row();

        $year = $this->settings_library->get_fy($plan_record->plan_start_date);

        if($plan_record->deleted_at != NULL){
            $data["goal_name"] =  $goal_name;
            $data["theme_id"] =  $theme_id;
            $data['plan_id'] = $plan_id;
            $data["goal_description"] = $goal_description;
            $data["goal_start_date"] = $this->settings_library->quarter_date_limits($year, $goal_period)['period_start_date'];
            $data["goal_end_date"] = $this->settings_library->quarter_date_limits($year, $goal_period)['period_end_date'];
            $data["goal_period"] = $goal_period;
            $data["user_id"] = $plan_record->user_id;
    
            $data['goal_created_by'] = $creating_user_id;
            $data['goal_created_date'] = date('Y-m-d');
            $data['goal_last_modified_by'] = $creating_user_id;
    
            $this->db->insert('goal', $data);

            if ($this->db->affected_rows()) {
                $rst['data']['goal_id'] = $this->db->insert_id();
            } 
        }


        return $rst;
    }

    function edit_goal(){

    }

    function delete_goal(){
        $goal_id = isset($_GET['goal_id']) ? $_GET['goal_id'] : 0;

        $this->db->where(array('goal_id' => $goal_id, 'deleted_at' => NULL));
        $this->db->update('goal',['deleted_at' => date('Y-m-d h:i:s')]);

        $rst['status'] = 'success';
        $rst['data'] = 0;

        if($this->db->affected_rows() > 0){
            $rst['data'] = $this->db->affected_rows();
        }

        return $rst;
    }

    
    function api_result($method_call, ...$args){
        $method_call_result = call_user_func_array(array($this, $method_call),$args);
        echo json_encode($method_call_result, JSON_PRETTY_PRINT);
    }
}