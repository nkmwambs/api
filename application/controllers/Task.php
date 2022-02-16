<?php defined('BASEPATH') or exit('No direct script access allowed');

class Task extends CI_Controller{
    
    function __construct()
    {
        parent::__construct();
        $this->load->database();

        $this->settings_library->set_settings();
    }

    function task()
    {

        $goal_id = isset($_GET['goal_id']) ? $_GET['goal_id'] : 0;
        $task_id = isset($_GET['task_id']) ? $_GET['task_id'] : 0;
        $plan_id = isset($_GET['plan_id']) ? $_GET['plan_id'] : 0;

        $this->db->select(array(
            'task_id', 'goal_name', 'goal_start_date', 'goal_end_date', 'theme_name',
            'goal_description', 'task_name', 'task_start_date', 'task_end_date', 'task_status',
            'task_type.task_type_id as task_type_id','task_name', 'task_type_name'
        ));

        $this->db->join('goal', 'goal.goal_id=task.goal_id');
        $this->db->join('plan', 'plan.plan_id=goal.plan_id');
        $this->db->join('theme', 'theme.theme_id=goal.theme_id');
        $this->db->join('task_type', 'task_type.task_type_id=task.task_type_id');

        $this->db->where(array('goal.deleted_at' => NULL));
        $this->db->where(array('plan.deleted_at' => NULL));
        $this->db->where(array('theme.deleted_at' => NULL));
        $this->db->where(array('task_type.deleted_at' => NULL));

        if($goal_id > 0){
            $this->db->where(array('task.goal_id' => $goal_id));
        }

        if($task_id > 0){
            $this->db->where(array('task_id' => $task_id));
        }

        if($plan_id > 0){
            $this->db->where(array('goal.plan_id' => $plan_id));
        }

        $tasks["data"] = $this->db->get("task")->result_array();
        $tasks["status"] = "success";

        return $tasks;
    }

    function add_task()
    {

        $post = $this->input->post();

        $result['status'] = 'success';

        $goal_id = $post['goal_id'];//isset($_GET['goal_id']) ? $_GET['goal_id'] : 0;
        $task_title = $post['task_title'];
        $task_description = $post['task_description'];
        $task_start_date = $post['task_start_date'];
        $task_end_date = $post['task_end_date'];
        $task_status = $post['task_status'];
        $user_id = $post['user_id'];

        $data['task_name'] = $task_title;
        $data['task_description'] = $task_description;
        $data['goal_id'] = $goal_id;
        $data['task_start_date'] = $task_start_date;
        $data['task_end_date'] =  $task_end_date;
        $data['task_status'] = $task_status;
        $data['task_created_by'] = $user_id;
        $data['task_created_date'] = date('Y-m-d');
        $data['task_last_modified_by'] = $user_id;

        $this->db->insert('task', $data);

        $result = [];

        if ($this->db->affected_rows() > 0) {
            $result['data']['task_id'] = $this->db->insert_id();
        } 

        return $result;
    }


    function update_task_status(){
        $post = $this->input->post();

        $data['task_status'] = $post['task_status'];

        $this->db->where(array('task_id'=>$post['task_id']));
        $this->db->update('task',$data);

        $result['data']['task_id'] = 0;
        $result['status'] = "failed";

        if($this->db->affected_rows() > 0){
            $result['data']['task_id'] = $post['task_id'];
            $result['status'] = "success";
        }

        return  $result;
    }

    function edit_task(){

    }

    function delete_task(){
        $task_id = isset($_GET['task_id']) ? $_GET['task_id'] : 0;

        $this->db->where(array('task_id' => $task_id,'deleted_at' => NULL));
        $this->db->update('task',['deleted_at' => date('Y-m-d h:i:s')]);

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