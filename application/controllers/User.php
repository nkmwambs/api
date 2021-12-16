<?php
defined('BASEPATH') or exit('No direct script access allowed');

class User extends CI_Controller
{

    private $fy_year_digits = 2;
    private $fy_start_month = 7;
    private $fy_year_reference = 'next';

    function __construct()
    {
        parent::__construct();
        $this->load->database();

        $this->set_settings();

    }

    public function index()
    {
    }

    private function set_settings(){
        $settings = $this->settings();

        extract($settings);

        $this->fy_year_digits = isset($fy_year_digits) ? $fy_year_digits : $this->create_setting('fy_year_digits',$this->fy_year_digits,'Possible values are 2 or 4');
        $this->fy_start_month = isset($fy_start_month) ? $fy_start_month : $this->create_setting('fy_start_month', $this->fy_start_month, 'Possible values are 1 to 12');
        $this->fy_year_reference = isset($fy_year_reference) ? $fy_year_reference : $this->create_setting('fy_year_reference', $this->fy_year_reference, 'Possible values are next or previous');
    }

    private function create_setting($setting_name, $setting_value, $setting_description){
        $data['setting_name'] = $setting_name;
        $data['setting_value'] = $setting_value;
        $data['setting_description'] = $setting_description;
        $data['setting_created_by'] = 1;
        $data['setting_created_date'] = date('Y-m-d');
        $data['setting_last_modified_by'] = 1;

        $this->db->insert('setting',$data);

        return $setting_value;
    }

    private function settings($selected_setting_names = []){

        $this->db->select(array('setting_name','setting_value'));
        $settings_data = $this->db->get('setting')->result_array();

        if(!empty($selected_setting_names)){
            $this->db->where_in('setting_name', $selected_setting_names);
        }

        $setting_names = array_column($settings_data,'setting_name');
        $setting_values = array_column($settings_data,'setting_value');

        $settings = array_combine($setting_names,$setting_values);

        return $settings;
    }

    function themes()
    {
        $this->db->select(array('theme_id', 'theme_name'));
        $this->db->where(array("theme_status" => 1));
        $themes["data"] = $this->db->get("theme")->result_array();
        $themes["status"] = "success";

        //echo json_encode($themes, JSON_PRETTY_PRINT);
        return $themes;
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

        //$out = json_encode($result, JSON_PRETTY_PRINT);

        return $result;
    }

    function count_goal_tasks($goal_id, $task_status = '')
    {
        $this->db->select(array('task_id'));
        if($task_status != ''){
            $this->db->where(array('task_status'=>$task_status));
        }
        $this->db->where(array('goal_id' => $goal_id));
        $count = $this->db->get('task')->num_rows();

        return $count;
    }

    private function count_plan_tasks($plan_id)
    {
        $this->db->select(array('task_id'));
        $this->db->where(array('plan.plan_id' => $plan_id));
        $this->db->join('goal','goal.goal_id=task.goal_id');
        $this->db->join('plan','plan.plan_id=goal.plan_id');
        $count_tasks = $this->db->get('task')->num_rows();

        // $count["data"] = $count_tasks;
        // $count["status"] = "success";

        // echo json_encode($count, JSON_PRETTY_PRINT);

        return $count_tasks;
    }

    private function count_plan_due_tasks($plan_id)
    {
        
        $this->db->where(array(
            'plan.plan_id' => $plan_id
        ));
        $this->db->join('goal', 'goal.goal_id=task.goal_id');
        $this->db->join('plan', 'plan.plan_id=goal.plan_id');
        $this->db->where("task_end_date <=  DATE_SUB(DATE(NOW()), INTERVAL -7 DAY) AND task_end_date >= DATE(NOW())");
        $result = $this->db->get('task')->num_rows();

        // $tasks["data"] = $result;
        // $tasks["status"] = "success";

        // echo json_encode($tasks, JSON_PRETTY_PRINT);

        return $result;

    }

    function count_overdue_plan_tasks($date, $plan_id)
    {
       
        $this->db->where(array('task_end_date < ' => $date, 'goal.plan_id' => $plan_id));
        $this->db->join('goal', 'goal.goal_id=task.goal_id');
        $this->db->join('plan', 'plan.plan_id=goal.plan_id');
        $result = $this->db->get('task')->num_rows();

        // $tasks["data"] = $result;
        // $tasks["status"] = "success";

        // echo json_encode($tasks, JSON_PRETTY_PRINT);

        return $result;
    }

    function goal_statistics($goal_id, $date){
        $stats['data']['count_goal_due_tasks'] = $this->count_goal_due_tasks($goal_id);
        $stats['data']['count_goal_complete_tasks']  = $this->count_goal_complete_tasks($goal_id);
        $stats['data']['count_goal_overdue_tasks']  = $this->count_goal_overdue_tasks($goal_id, $date);
        $stats['data']['count_goal_all_tasks']  = $this->count_all_goal_tasks($goal_id);

        $stats["status"] = "success";

        //echo json_encode($stats, JSON_PRETTY_PRINT);

        return $stats;
    }

    private function count_goal_overdue_tasks($goal_id, $date)
    {
       
        $this->db->where(array('task_end_date < ' => $date, 'goal.goal_id' => $goal_id));
        $this->db->join('goal', 'goal.goal_id=task.goal_id');
        $result = $this->db->get('task')->num_rows();

        return $result;
    }

    private function count_goal_due_tasks($goal_id)
    {
        
        $this->db->where(array(
            'goal.goal_id' => $goal_id
        ));
        $this->db->join('goal', 'goal.goal_id=task.goal_id');
        $this->db->where("task_end_date <=  DATE_SUB(DATE(NOW()), INTERVAL -7 DAY) AND task_end_date >= DATE(NOW())");
        $result = $this->db->get('task')->num_rows();

        return $result;

    }

    private function count_goal_complete_tasks($goal_id){
        $this->db->where(array('goal_id'=>$goal_id,'task_status' => 2));
        $count_all_goal_tasks = $this->db->get('task')->num_rows();

        return $count_all_goal_tasks;
    }

    private function count_all_goal_tasks($goal_id){

        $this->db->where(array('goal_id'=>$goal_id));
        $count_all_goal_tasks = $this->db->get('task')->num_rows();

        return $count_all_goal_tasks;
    }

    function plan_statistics($plan_id, $date){
        $stats['data']['count_plan_goals'] = $this->count_plan_goals($plan_id);
        $stats['data']['count_plan_due_tasks']  = $this->count_plan_due_tasks($plan_id);
        $stats['data']['count_plan_tasks']  = $this->count_plan_tasks($plan_id);
        $stats['data']['count_overdue_plan_tasks']  = $this->count_overdue_plan_tasks($date, $plan_id);

        $stats["status"] = "success";

        //echo json_encode($stats, JSON_PRETTY_PRINT);

        return $stats;
    }

    private function due_tasks($user_id)
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

        // $tasks["data"] = $result;
        // $tasks["status"] = "success";

        // echo json_encode($tasks, JSON_PRETTY_PRINT);

        return $result;
    }

    // function count_due_tasks($user_id)
    // {

    //     $count["data"] = $this->due_tasks($user_id)->num_rows();
    //     $count["status"] = "success";

    //     echo json_encode($count, JSON_PRETTY_PRINT);
    // }

    function get_due_tasks($user_id)
    {

        $tasks["data"] = $this->due_tasks($user_id)->result_array();
        $tasks["status"] = "success";

        echo json_encode($tasks, JSON_PRETTY_PRINT);
    }

    private function overdue_tasks($date, $user_id)
    {
        $this->db->select(array(
            "theme.theme_id as theme_id", "theme_name", "goal.goal_id as goal_id",
            "goal_start_date", "goal_end_date",
            "goal_name", "task_id", "task_name", "task_start_date", "task_end_date", "task_status"
        ));
        $this->db->where(array('task_end_date < ' => $date, 'user_id' => $user_id));
        $this->db->join('goal', 'goal.goal_id=task.goal_id');
        $this->db->join('theme', 'theme.theme_id=goal.theme_id');
        $result = $this->db->get('task');

        return $result;
    }

    function count_overdue_tasks($date, $user_id)
    {

        $count["data"] = $this->overdue_tasks($date, $user_id)->num_rows();
        $count["status"] = "success";

        echo json_encode($count, JSON_PRETTY_PRINT);
    }

    function get_overdue_tasks($date, $user_id)
    {
        $count["data"] = $this->overdue_tasks($date, $user_id)->result_array();
        $count["status"] = "success";

        echo json_encode($count, JSON_PRETTY_PRINT);
    }

    private function overdue_goals($date, $user_id)
    {
        $this->db->where(
            array(
                'goal_end_date < ' => $date,
                'task_end_date < ' => $date,
                'task_status < ' => 2,
                'user_id' => $user_id
            )
        );

        $this->db->select(
            array(
                'goal.goal_id as goal_id',
                'goal_name',
                'theme_name',
                'goal_start_date',
                'goal_end_date',
                'user_id'
            )
        );
        $this->db->join('theme', 'theme.theme_id=goal.theme_id');
        $this->db->join('task', 'task.goal_id=goal.goal_id');
        $result = $this->db->get('goal')->result_array();

        $result = array_unique($result, SORT_REGULAR);

        return $result;
    }

    function get_overdue_goals($date, $user_id)
    {

        $goals["data"] = $this->overdue_goals($date, $user_id);
        $goals["status"] = "success";

        echo json_encode($goals, JSON_PRETTY_PRINT);
    }

    // private function count_overdue_goals($date, $user_id)
    // {

    //     $result = $this->overdue_goals($date, $user_id);

    //     //$data['data'] = count($result);
    //     //$data['status'] = 'success';

    //     //echo json_encode($data, JSON_PRETTY_PRINT);

    //     return count($result);
    // }

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

    private function deactivate_user_active_plans($user_id, $current_fy){

        $active_plan = $this->plan();
        $active_plan_fy = isset($active_plan['plan_year']) ? $active_plan['plan_year'] : 0;
        //$current_fy = 22;//$this->get_fy(date('Y-m-01'));

        $deactivation_successful = false;

        if($current_fy > $active_plan_fy){
            $data['plan_status'] = 2;
            $this->db->where(array('plan_status'=>1,'user_id'=>$user_id));
            $this->db->update('plan',$data); 
            
            $deactivation_successful = true;
        }

        return $deactivation_successful;
          
    }

    function add_plan(){
        $post = $this->input->post();
        $fy = $this->get_fy($post['plan_start_date']);

        $deactivate_user_active_plans = $this->deactivate_user_active_plans($post['user_id'], $fy);

        $rst = [];

        $rst['msg'] = "Insert Failed";

        if($deactivate_user_active_plans){

            $data['plan_name'] = $post['plan_name'];
            $data['plan_start_date'] = $post['plan_start_date'];
            $data['plan_end_date'] = $post['plan_end_date'];
            $data['plan_year'] = $fy;
            $data['plan_status'] = 1;
            $data['user_id'] = $post['user_id'];
            $data['plan_created_by'] = $post['user_id'];
            $data['plan_created_date'] = date('Y-m-d');
            $data['plan_last_modified_by'] = $post['user_id'];
    
            $this->db->insert('plan', $data);
    
            if ($this->db->affected_rows()) {
                $rst['data']['plan_id'] = $this->db->insert_id();
                $rst['status'] = 'success';
            } else {
                $rst['msg'] = "Insert Failed";
            }

        }

        //$out = json_encode($rst, JSON_PRETTY_PRINT);

        return $rst;

    }

    function add_goal()
    {
        $post = $this->input->post();
        $plan_id = $post['plan_id'];
        $goal_period = $post['goal_period'];

        $plan = $this->plan();
        $year = $this->get_fy($plan['plan_start_date']);

        $data["goal_name"] = $post['goal_name'];
        $data["theme_id"] = $post['theme_id'];
        $data['plan_id'] = $plan_id;
        $data["goal_description"] = $post['goal_description'];
        $data["goal_start_date"] = $this->quarter_date_limits($year, $goal_period)['period_start_date'];//'2021-07-01';//$post['goal_start_date'];
        $data["goal_end_date"] = $this->quarter_date_limits($year, $goal_period)['period_end_date'];//$post['goal_end_date'];
        $data["goal_period"] = $post['goal_period'];
        $data["user_id"] = $post['user_id'];

        $data['goal_created_by'] = $post['user_id'];
        $data['goal_created_date'] = date('Y-m-d');
        $data['goal_last_modified_by'] = $post['user_id'];

        $this->db->insert('goal', $data);

        $rst = [];

        if ($this->db->affected_rows()) {
            $rst['data']['goal_id'] = $this->db->insert_id();
            $rst['status'] = 'success';
        } else {
            $rst['msg'] = "Insert Failed";
        }

        //$out = json_encode($rst, JSON_PRETTY_PRINT);

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
        
        $this->db->join('user', 'user.user_id=plan.plan_created_by');
        $plan_obj = $this->db->get('plan');

        if($plan_obj->num_rows() > 0){
             $plans['data'] = $plan_obj->row_array();
        }

        $plans["status"] = "success";

        echo json_encode($plans, JSON_PRETTY_PRINT);
    }

    // private function _active_plan($user_id = "")
    // {
    //     $this->db->select(array(
    //         'plan_id', 'plan_name', 'plan_start_date',
    //         'plan_end_date', 'plan_year', 'plan_status', 'user_first_name', 'user_last_name', 'plan_created_date'
    //     ));

    //     if ($user_id != "") {
    //         $this->db->where(array('plan.user_id' => $user_id));
    //     }

    //     $this->db->where(array('plan_status' => 1));
    //     $this->db->join('user', 'user.user_id=plan.plan_created_by');
    //     $plan_obj = $this->db->get('plan');

    //     $plan = [];

    //     if($plan_obj->num_rows() > 0){
    //          $plan = $plan_obj->row_array();
    //     }

    //     return $plan;
    // }


    // function active_plan($user_id = "")
    // {
        
    //     $plans["data"] = $this->_active_plan($user_id);

    //     $plans["status"] = "success";

    //     //echo json_encode($plans, JSON_PRETTY_PRINT);
    //     return $plans;
    // }

    function goal($goal_id)
    {

        $this->db->select(array(
            'goal.goal_id', 'theme.theme_id as theme_id', 'goal_name', 'theme_name',
            'plan.user_id as user_id', 'goal_start_date', 'goal_end_date', 'goal_created_date',
            'user_first_name', 'user_last_name', 'goal_created_date'
        ));

        $this->db->where(array('goal.goal_id' => $goal_id));
        $this->db->join('theme', 'theme.theme_id=goal.theme_id');
        $this->db->join('plan','plan.plan_id=goal.plan_id');
        $this->db->join('user', 'user.user_id=plan.user_id');
        $result = $this->db->get('goal')->row_array();

        $goal["data"] = $result;
        $goal["status"] = "success";

        //echo json_encode($goal, JSON_PRETTY_PRINT);

        return $goal;
    }

    // function get_goal($goal_id){
    //     $this->db->select(array(
    //         'goal_id', 'goal_name', 'goal_start_date',
    //         'goal_end_date', 'user_first_name', 
    //         'user_last_name', 'goal_created_date'
    //     ));

    //     $this->db->where(array('goal.goal_id' => $goal_id));
    //     $this->db->join('plan','plan.plan_id=goal.plan_id');
    //     $this->db->join('user', 'user.user_id=plan.user_id');
    //     $goal["data"] = $this->db->get('goal')->row_array();

    //     $goal["status"] = "success";

    //     echo json_encode($goal, JSON_PRETTY_PRINT);
    // }

    // private function _get_plan(){

    //     $plan_id = isset($_GET['plan_id']) ? $_GET['plan_id'] : 0;

    //     $this->db->select(array(
    //         'plan_id', 'plan_name', 'plan_start_date',
    //         'plan_end_date', 'plan_status', 'user_first_name', 'user_last_name', 'plan_created_date'
    //     ));

    //     //if ($plan_id != "") {
    //         $this->db->where(array('plan.plan_id' => $plan_id));
    //     //}

    //     //$this->db->where(array('plan_status' => 1));
    //     $this->db->join('user', 'user.user_id=plan.plan_created_by');
    //     $plan = $this->db->get('plan')->row_array();

    //     $plans["data"] = "success";
    //     $plans["status"] = "success";

    //     return $plan;
    // }

    // function get_plan($plan_id){
       
    //     $plans["data"] = $this->_get_plan();

    //     $plans["status"] = "success";

    //     return $plans;
    // }

    function plans($user_id = "")
    {

        $this->db->select(array(
            'plan_id', 'plan_name', 'plan_start_date',
            'plan_end_date', 'plan_status','plan_created_date'
        ));

        if ($user_id != "") {
            $this->db->where(array('user_id' => $user_id));
        }

        $this->db->order_by('plan_status','plan_start_date', 'asc');
        $plans["data"] = $this->db->get('plan')->result_array();

        $plans["status"] = "success";

        //echo json_encode($plans, JSON_PRETTY_PRINT);

        return $plans;
    }

    private function count_plan_goals($plan_id){

        $this->db->join('plan','plan.plan_id=goal.plan_id');
        $this->db->where(array('goal.plan_id'=>$plan_id));
        $count_goals = $this->db->get('goal')->num_rows();

        // $count['data'] = $count_goals;
        // $count['status'] = 'success';

        // echo json_encode($count, JSON_PRETTY_PRINT);

        return $count_goals;
    }

    function goals($plan_id = "")
    {
        $this->db->select(array(
            'goal_id', 'goal_name', 'plan_name', 'theme_name', 'goal_start_date',
            'goal_end_date', 'goal.user_id as user_id','plan.plan_id', 'goal_created_date'
        ));

        $this->db->join('plan', 'plan.plan_id=goal.plan_id');
        $this->db->join('theme', 'theme.theme_id=goal.theme_id');
        $this->db->order_by('theme.theme_id', 'goal_id');

        if ($plan_id != "") {
            $this->db->where(array('goal.plan_id' => $plan_id));
        }

        $goals_with_task_count = [];

        $result = $this->db->get("goal")->result_array();

        foreach ($result as $goal) {
            $goal['count_of_tasks'] = $this->count_goal_tasks($goal['goal_id']);
            $goal['count_new_tasks'] = $this->count_goal_tasks($goal['goal_id'],0);
            $goal['count_inprogress_tasks'] = $this->count_goal_tasks($goal['goal_id'],1);
            $goal['count_completed_tasks'] = $this->count_goal_tasks($goal['goal_id'],2);

            $goals_with_task_count[] = $goal;
        }

        $goals["data"] = $goals_with_task_count;

        $goals["status"] = "success";

        //echo json_encode($goals, JSON_PRETTY_PRINT);

        return $goals;
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

        //echo json_encode($task, JSON_PRETTY_PRINT);

        return $task;
    }

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

    function login()
    {
        $post = $this->input->post();

        
        $this->db->select(array('user_id','user_name','user_first_name','user_last_name','user_email','user_age',
        'user_address','profile_name','country_name','language_name','language_code'));

        $this->db->join('profile','profile.profile_id=user.profile_id');
        $this->db->join('country','country.country_id=user.country_id');
        $this->db->join('language','language.language_id=user.language_id');
        $this->db->where(array('user_email' => $post['email'], 'user_password' => $post['password'], 'user_active' => 1));
        $query = $this->db->get('user');

        $result = ["msg" => "User logged successfully"];

        $user = [];

        if ($query->num_rows() > 0) {
            $user = $query->row_array();
            $user['language_phrases'] = $this->language_phrases($user['user_id']);
            $result["data"] = $user;
            $result["status"] = "success";

            // Create a plan when missing one
            $this->auto_create_plan($user['user_id']);

        } else {
            $result["msg"] = "Invalid Email or Password";
        }

        echo json_encode($result, JSON_PRETTY_PRINT);

    }

    function register()
    {
        $post = $this->input->post();

        $data['user_name'] = $post['name'];
        $data['user_email'] = $post['email'];
        $data['user_age'] = $post['age'];
        $data['user_address'] = $post['address'];
        $data['user_password'] = $post['password'];

        //$this->db->trans_start();
        //$this->db->insert('user', $data);
        //$this->db->trans_complete();

        $rst = [];

        $query = $this->db->get_where('user', ['user_email' => $post['email']]);

        if ($query->num_rows() > 0) {
            $rst['msg'] = 'User registration failed. Email already exists';
            //$out = json_encode($rst, JSON_PRETTY_PRINT);
        } else {
            $this->db->insert('user', $data);
            $rst['status'] = 'success';
            //$out = json_encode($rst, JSON_PRETTY_PRINT);
        }

        return $rst;
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

    function get_year_start_month(){
        $this->db->where(array('setting_name' => 'year_start_month'));
        $year_start_month = $this->db->get('setting')->row()->setting_value;

        return $year_start_month;
    }

    
    function get_fy($date_string, $override_fy_year_digits_config = false)
	{

        $date_month_number = date('n', strtotime($date_string));
		$fy = ($this->fy_year_digits == 4 && !$override_fy_year_digits_config) ? date('Y', strtotime($date_string)) : date('y', strtotime($date_string));
        
        $months = $this->month_order($this->fy_start_month);

		$first_month = current($months);
		$last_month = end($months);

		$half_year_months = array_chunk($months, 6);

		if ($first_month != 1 && $last_month != 12) {

			if (in_array($date_month_number, $half_year_months[0]) && $this->fy_year_reference == 'next') {
				$fy++;
			}
		}

		return $fy;
	}
  
    private function get_fy_start_end_date($fy){
        
        $months = $this->month_order($this->fy_start_month); // List of months in a year in a custom order

        $start_end_dates_of_year = $this->period_date_limits($fy, $months);
 
        return $start_end_dates_of_year;
    }

    private function auto_create_plan($user_id){ 
        $fy = $this->get_fy(date('Y-m-d'));
        $deactivate_user_active_plans = 0;//$this->deactivate_user_active_plans($user_id,$fy);

        // $rst['data'] = 0;
        // $rst['status'] = "success";
        $affected_rows = 0;

        if($deactivate_user_active_plans){
            
            $fy_dates = $this->get_fy_start_end_date($fy);
            $data['plan_name'] = "My FY".$fy." Plan";
            $data['plan_start_date'] = $fy_dates['period_start_date'];
            $data['plan_end_date'] = $fy_dates['period_end_date'];
            $data['plan_year'] = $fy;
            $data['plan_status'] = 1;
            $data['user_id'] = $user_id;
            $data['plan_created_by'] = $user_id;
            $data['plan_created_date'] = date('Y-m-d');
            $data['plan_last_modified_by'] = $user_id;

            $this->db->insert('plan', $data);

            $affected_rows = $this->db->affected_rows();
        }

        return $affected_rows;
    }

    function get_quarters(){

        $q1 = $this->quarter_month_limits(1, true);
        $q2 = $this->quarter_month_limits(2, true);
        $q3 = $this->quarter_month_limits(3, true);
        $q4 = $this->quarter_month_limits(4, true);

        $quarters = [
            ['quarter_number' => 1, 'quarter_name' => 'First Quarter ['. $q1['period_start_month'].' to '. $q1['period_end_month'].']'],
            ['quarter_number' => 2, 'quarter_name' => 'Second Quarter ['.$q2['period_start_month'].' to '.$q2['period_end_month'].']'],
            ['quarter_number' => 3, 'quarter_name' => 'Third Quarter ['.$q3['period_start_month'].' to '.$q3['period_end_month'].']'],
            ['quarter_number' => 4, 'quarter_name' => 'Fourth Quarter ['.$q4['period_start_month'].' to '.$q4['period_end_month'].']']
          ];

        $qtr["data"] = $quarters;
        $qtr["status"] = "success";

        return  $qtr;
    }

    private function period_date_limits($fy, $period_months){

        $first_month = current($period_months); // Get first month of the custom year
		$last_month = end($period_months); // Get last month of the custom year

        $last_date_of_first_month = 0;
        $last_date_of_last_month = 0;

        // Get the start/ end year of the custom year in four digit year
        if($this->fy_year_reference == 'next'){
            $start_year = $fy - 1;
            $end_year = $fy;
        }else{
            $start_year = $fy;
            $end_year = $fy + 1;
        }

        $four_digit_current_year = date('Y');

        if($this->fy_year_digits == 2){
            $century = substr($four_digit_current_year,0,2);
            $start_year =  $century.''.$start_year;
            $end_year =  $century.''.$end_year;
        }

        // Make the start/ end month two digit

        if($first_month < 10){
            $first_month = "0".$first_month;
        }

        if($last_month < 10){
            $last_month = "0".$last_month;
        }

        // Make a complete start/end date of the custom year

        $last_date_of_first_month = $start_year.'-'. $first_month.'-01';
        $last_date_of_last_month = date("Y-m-t",strtotime($end_year.'-'. $last_month.'-01'));

        $start_end_dates_of_year = ['period_start_date' => $last_date_of_first_month,  'period_end_date' => $last_date_of_last_month];

        return $start_end_dates_of_year;
    }

    private function quarter_date_limits($fy, $quarter_number){

        $selected_quarter_months = $this->quarter_months($quarter_number);

        $start_end_dates_of_period = $this->period_date_limits($fy, $selected_quarter_months);

        return $start_end_dates_of_period;
    }

    private function quarter_month_limits($quarter_number, $show_full_month_names = false){

        $selected_quarter_months = $this->quarter_months($quarter_number);

        $period_start_month = current($selected_quarter_months);
        $period_end_month = end($selected_quarter_months);

        $month_names = array_combine(range(1,12),['January','February','March','April','May','June','July','August','September','October','November','December']);

        $quarter_month_limits = ['period_start_month' => $period_start_month, 'period_end_month' => $period_end_month];
        
        if($show_full_month_names){
            $quarter_month_limits = ['period_start_month' => $month_names[$period_start_month], 'period_end_month' => $month_names[$period_end_month]];
        }
        
        return $quarter_month_limits;
    }

    private function quarter_months($quarter_number){
        $month_order = $this->month_order($this->fy_start_month);
        
        $quarter_months = array_combine([1,2,3,4],array_chunk($month_order,3));

        $selected_quarter_months = $quarter_months[$quarter_number];

        return $selected_quarter_months;
    }


    private function month_order($start_month = 1){
              
        $default_month_order = range(1, 12);

        $first_phase = [];
        $second_phase = [];

        foreach($default_month_order as $month){
            if($month >= $start_month){
                $first_phase[] = $month;
            }else{
                $second_phase[] = $month;
            }
        }

        $customized_month_order = array_merge($first_phase, $second_phase);

        return $customized_month_order;
    }

    function language_phrases($user_id){

        $language = 'english';

        ob_start();
            include APPPATH.'language'.DIRECTORY_SEPARATOR.$language.DIRECTORY_SEPARATOR.'phrases.php';
            ob_get_contents();
        ob_end_clean();
        
        $result['data'] = $lang;
        $result['status'] = 'success';

        return  $result;
    }

    function api_result($method_call, ...$args){
        $method_call_result = call_user_func_array(array($this, $method_call),$args);
        echo json_encode($method_call_result, JSON_PRETTY_PRINT);
    }
}
