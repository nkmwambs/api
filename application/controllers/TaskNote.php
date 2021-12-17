<?php defined('BASEPATH') or exit('No direct script access allowed');

class TaskNote extends CI_Controller{

    function __construct()
    {
        parent::__construct();
        $this->load->database();

        $this->settings_library->set_settings();
    }

    function task_note()
    {

        $task_id = isset($_GET['task_id']) ? $_GET['task_id'] : 0;
        $goal_id = isset($_GET['goal_id']) ? $_GET['goal_id'] : 0;
        $plan_id = isset($_GET['plan_id']) ? $_GET['plan_id'] : 0;

        $this->db->join('task','task.task_id=task_note.task_id');
        $this->db->join('goal','goal.goal_id=task.goal_id');
        $this->db->join('plan','plan.plan_id=goal.plan_id');

        if($task_id > 0){
            $this->db->where(array('task_note_id.task_id' => $task_id));
        }

        if($goal_id > 0){
            $this->db->where(array('goal.goal_id' => $task_id));
        }

        if($plan_id > 0){
            $this->db->where(array('plan.plan_id' => $task_id));
        }
        
        $this->db->where(array('task.deleted_at' => NULL));
        $this->db->where(array('goal.deleted_at' => NULL));
        $this->db->where(array('plan.deleted_at' => NULL));
        $this->db->where(array('task_note.deleted_at' => NULL));

        $result = $this->db->get('task_note')->result_array();

        $task_notes["data"] = $result;
        $task_notes["status"] = "success";

        return $task_notes;
    }

    function add_task_note()
    {
        $post = $this->input->post();

        $task_note = $post['task_note'];
        $task_id = $post['task_id'];
        $task_note_created_by = $post['task_note_created_by'];
        $task_note_last_modified_by = isset($post['task_note_last_modified_by']) ? $post['task_note_last_modified_by'] : $task_note_created_by;
        $task_note_created_date = isset($post['task_note_created_date']) ? $post['task_note_created_date'] : date('Y-m-d');

        $task = $this->db->get('task',array('task_id' => $task_id))->row();

        $rst['status'] = 'success';
        $rst['data'] = [];

        if($task->deleted_at == NULL){

            $data['task_note'] = $task_note;
            $data['task_id'] = $task_id;
            $data['task_note_created_by'] = $task_note_created_by;
            $data['task_note_created_date'] = $task_note_created_date;
            $data['task_note_last_modified_by'] = $task_note_last_modified_by;
    
            $this->db->insert('task_note', $data);
    
            if ($this->db->affected_rows()) {
                $rst['data']['task_note_id'] = $this->db->insert_id();
            }
        } 

        return $rst;
    }

    function update_task_note(){

    }

    function delete_task_note(){

    }

    function api_result($method_call, ...$args){
        $method_call_result = call_user_func_array(array($this, $method_call),$args);
        echo json_encode($method_call_result, JSON_PRETTY_PRINT);
    }
}