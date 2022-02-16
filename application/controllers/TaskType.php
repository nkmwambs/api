<?php defined('BASEPATH') or exit('No direct script access allowed');

class TaskType extends CI_Controller{

    function __construct()
    {
        parent::__construct();
        $this->load->database();

        $this->settings_library->set_settings();
    }

    function tasktype()
    {

        $this->db->select(array('task_type_id', 'task_type_name'));
        $this->db->where(array('task_type_is_active' => 1));

        $task_types["data"] =  $this->db->get('task_type')->result_array();
        $task_types["status"] = "success";

        //echo json_encode($task_types, JSON_PRETTY_PRINT);

        return $task_types;
    }

    function api_result($method_call, ...$args){
        $method_call_result = call_user_func_array(array($this, $method_call),$args);
        echo json_encode($method_call_result, JSON_PRETTY_PRINT);
    }
}