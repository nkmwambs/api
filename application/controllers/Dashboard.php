<?php defined('BASEPATH') or exit('No direct script access allowed');

class Dashboard extends CI_Controller{

    function __construct()
    {
        parent::__construct();
        $this->load->database();

        $this->load->model('goal_model');
        $this->load->model('task_model');

        $this->settings_library->set_settings();
    }

    function dashboard_statistics()
    {
        $target_date = isset($_GET['target_date']) ? $_GET['target_date'] : 0;
        $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : 0;

        $overdue_goals = count($this->goal_model->overdue_goals($target_date, $user_id));
        $due_tasks = $this->task_model->due_tasks($user_id)->num_rows();
        $overdue_tasks = $this->task_model->overdue_tasks($target_date, $user_id)->num_rows();


        $data['data']['count_overdue_goals'] = $overdue_goals;
        $data['data']['count_due_tasks'] = $due_tasks;
        $data['data']['count_overdue_tasks'] = $overdue_tasks;
        $data['status'] = 'success';

        return $data;
    }

    function api_result($method_call, ...$args){
        $method_call_result = call_user_func_array(array($this, $method_call),$args);
        echo json_encode($method_call_result, JSON_PRETTY_PRINT);
    }
    
}