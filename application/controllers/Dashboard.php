<?php defined('BASEPATH') or exit('No direct script access allowed');

class Dashboard extends CI_Controller{

    function __construct()
    {
        parent::__construct();
        $this->load->database();

        $this->settings_library->set_settings();
    }

    function get_dashboard_statistics($date, $user_id)
    {
        $overdue_goals = count($this->overdue_goals($date, $user_id));
        $due_tasks = $this->due_tasks($user_id)->num_rows();
        $overdue_tasks = $this->overdue_tasks($date, $user_id)->num_rows();


        $data['data']['count_overdue_goals'] = $overdue_goals;
        $data['data']['count_due_tasks'] = $due_tasks;
        $data['data']['count_overdue_tasks'] = $overdue_tasks;
        $data['status'] = 'success';

        //echo json_encode($data, JSON_PRETTY_PRINT);

        return $data;
    }

    function api_result($method_call, ...$args){
        $method_call_result = call_user_func_array(array($this, $method_call),$args);
        echo json_encode($method_call_result, JSON_PRETTY_PRINT);
    }
    
}