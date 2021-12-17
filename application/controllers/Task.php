<?php defined('BASEPATH') or exit('No direct script access allowed');

class Task extends CI_Controller{
    
    function __construct()
    {
        parent::__construct();
        $this->load->database();

        $this->settings_library->set_settings();
    }

    function add_task($goal_id)
    {

        $post = $this->input->post();

        $data['task_name'] = $post['task_title'];
        $data['task_description'] = $post['task_description'];
        $data['goal_id'] = $goal_id;
        $data['task_start_date'] = $post['task_start_date'];
        $data['task_end_date'] = $post['task_end_date'];
        $data['task_status'] = $post['task_status'];
        $data['task_created_by'] = $post['user_id'];
        $data['task_created_date'] = date('Y-m-d');
        $data['task_last_modified_by'] = $post['task_last_modified_by'];

        $this->db->insert('task', $data);

        $result = [];

        if ($this->db->affected_rows() > 0) {
            $result['data']['task_id'] = $this->db->insert_id();
            $result['status'] = 'success';
        } else {
            $result['msg'] = "Insert Failed";
        }

        return $result;
    }


    function task($task_id)
    {
        $this->db->select(array(
            'task_id', 'task_type.task_type_id as task_type_id', 'task_name',
            'task_type_name', 'task_description', 'task_start_date',
            'task_end_date', 'task_status'
        ));
        $this->db->where(array('task_id' => $task_id));
        $this->db->join('task_type', 'task_type.task_type_id=task.task_type_id');
        $result = $this->db->get('task')->row_array();

        $task["data"] = $result;
        $task["status"] = "success";

        return $task;
    }

    function due_tasks($user_id)
    {
        $this->db->select(array(
            "theme.theme_id as theme_id", "theme_name", "goal.goal_id as goal_id",
            "goal_start_date", "goal_end_date",
            "goal_name", "task_id", "task_name", "task_start_date", "task_end_date", "task_status"
        ));
        $this->db->where(array(
            'user_id' => $user_id
        ));
        $this->db->join('goal', 'goal.goal_id=task.goal_id');
        $this->db->join('theme', 'theme.theme_id=goal.theme_id');
        $this->db->where("task_end_date <=  DATE_SUB(DATE(NOW()), INTERVAL -7 DAY) AND task_end_date >= DATE(NOW())");
        $result = $this->db->get('task');

        return $result;
    }

    // function get_due_tasks($user_id)
    // {

    //     $tasks["data"] = $this->due_tasks($user_id)->result_array();
    //     $tasks["status"] = "success";

    //     echo json_encode($tasks, JSON_PRETTY_PRINT);
    // }


    function get_task_notes($task_id)
    {
        $this->db->where(array('task_id' => $task_id));
        $result = $this->db->get('task_note')->result_array();

        $task_notes["data"] = $result;
        $task_notes["status"] = "success";

        //echo json_encode($task_notes, JSON_PRETTY_PRINT);
        return $task_notes;
    }

    function add_task_note()
    {
        $post = $this->input->post();

        $data['task_note'] = $post['task_note'];
        $data['task_id'] = $post['task_id'];
        $data['task_note_created_by'] = $post['task_note_created_by'];
        $data['task_note_created_date'] = $post['task_note_created_date'];
        $data['task_note_last_modified_by'] = $post['task_note_last_modified_by'];

        $this->db->insert('task_note', $data);

        $rst = [];

        if ($this->db->affected_rows()) {
            $rst['data']['task_note_id'] = $this->db->insert_id();
            $rst['status'] = 'success';
        } else {
            $rst['msg'] = "Insert Failed";
        }

        //$out = json_encode($rst, JSON_PRETTY_PRINT);

        echo $rst;
    }

 

    function get_task_types()
    {

        $this->db->select(array('task_type_id', 'task_type_name'));
        $this->db->where(array('task_type_is_active' => 1));

        $task_types["data"] =  $this->db->get('task_type')->result_array();
        $task_types["status"] = "success";

        //echo json_encode($task_types, JSON_PRETTY_PRINT);

        return $task_types;
    }

    function tasks($goal_id)
    {

        $this->db->select(array(
            'task_id', 'goal_name', 'goal_start_date', 'goal_end_date', 'theme_name',
            'goal_description', 'task_name', 'task_start_date', 'task_end_date', 'task_status'
        ));
        $this->db->join('goal', 'goal.goal_id=task.goal_id');
        $this->db->join('theme', 'theme.theme_id=goal.theme_id');
        $this->db->where(array('task.goal_id' => $goal_id));
        $tasks["data"] = $this->db->get("task")->result_array();
        $tasks["status"] = "success";

        //echo json_encode($goals, JSON_PRETTY_PRINT);

        return $tasks;
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

        //echo json_encode($result, JSON_PRETTY_PRINT);

        return  $result;
    }

    function api_result($method_call, ...$args){
        $method_call_result = call_user_func_array(array($this, $method_call),$args);
        echo json_encode($method_call_result, JSON_PRETTY_PRINT);
    }
   
}