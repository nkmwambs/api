<?php
defined('BASEPATH') or exit('No direct script access allowed');

class User extends CI_Controller
{

    function __construct()
    {
        parent::__construct();
        $this->load->database();
    }
    public function index()
    {
    }

    function themes()
    {
        $this->db->select(array('theme_id', 'theme_name'));
        $this->db->where(array("theme_status" => 1));
        $themes["data"] = $this->db->get("theme")->result_array();
        $themes["status"] = "success";

        echo json_encode($themes, JSON_PRETTY_PRINT);
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

        $out = json_encode($result, JSON_PRETTY_PRINT);

        echo $out;
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

        echo json_encode($stats, JSON_PRETTY_PRINT);
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

        echo json_encode($stats, JSON_PRETTY_PRINT);
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

        echo json_encode($data, JSON_PRETTY_PRINT);
    }

    private function deactivate_user_active_plans($user_id){

        $active_plan = $this->_active_plan($user_id);
        $active_plan_fy = isset($active_plan['plan_year']) ? $active_plan['plan_year'] : 0;
        $current_fy = $this->get_fy(date('Y-m-01'));

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

        $deactivate_user_active_plans = $this->deactivate_user_active_plans($post['user_id']);

        $rst = [];

        $rst['msg'] = "Insert Failed";

        if($deactivate_user_active_plans){

            $data['plan_name'] = $post['plan_name'];
            $data['plan_start_date'] = $post['plan_start_date'];
            $data['plan_end_date'] = $post['plan_end_date'];
            $data['plan_year'] = $this->get_fy($post['plan_start_date']);
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

        $out = json_encode($rst, JSON_PRETTY_PRINT);

        echo $out;

    }

    function add_goal()
    {
        $post = $this->input->post();

        $data["goal_name"] = $post['goal_name'];
        $data["theme_id"] = $post['theme_id'];
        $data['plan_id'] = $post['plan_id'];
        $data["goal_description"] = $post['goal_description'];
        $data["goal_start_date"] = $post['goal_start_date'];
        $data["goal_end_date"] = $post['goal_end_date'];
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

        $out = json_encode($rst, JSON_PRETTY_PRINT);

        echo $out;
    }

    function plan($plan_id = "")
    {
        $this->db->select(array(
            'plan_id', 'plan_name', 'plan_start_date',
            'plan_end_date', 'plan_status', 'user_first_name', 'user_last_name', 'plan_created_date'
        ));

        $this->db->where(array('plan_id' => $plan_id));
        $this->db->where(array('plan_status' => 1));
        $this->db->join('user', 'user.user_id=plan.plan_created_by');
        $plans["data"] = $this->db->get('plan')->row_array();

        $plans["status"] = "success";

        echo json_encode($plans, JSON_PRETTY_PRINT);
    }

    private function _active_plan($user_id = "")
    {
        $this->db->select(array(
            'plan_id', 'plan_name', 'plan_start_date',
            'plan_end_date', 'plan_year', 'plan_status', 'user_first_name', 'user_last_name', 'plan_created_date'
        ));

        if ($user_id != "") {
            $this->db->where(array('plan.user_id' => $user_id));
        }

        $this->db->where(array('plan_status' => 1));
        $this->db->join('user', 'user.user_id=plan.plan_created_by');
        $plan_obj = $this->db->get('plan');

        $plan = [];

        if($plan_obj->num_rows() > 0){
             $plan = $plan_obj->row_array();
        }

        return $plan;
    }


    function active_plan($user_id = "")
    {
        
        $plans["data"] = $this->_active_plan($user_id);

        $plans["status"] = "success";

        echo json_encode($plans, JSON_PRETTY_PRINT);
    }

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

        echo json_encode($goal, JSON_PRETTY_PRINT);
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

    function get_plan($plan_id){
        $this->db->select(array(
            'plan_id', 'plan_name', 'plan_start_date',
            'plan_end_date', 'plan_status', 'user_first_name', 'user_last_name', 'plan_created_date'
        ));

        //if ($plan_id != "") {
            $this->db->where(array('plan.plan_id' => $plan_id));
        //}

        //$this->db->where(array('plan_status' => 1));
        $this->db->join('user', 'user.user_id=plan.plan_created_by');
        $plans["data"] = $this->db->get('plan')->row_array();

        $plans["status"] = "success";

        echo json_encode($plans, JSON_PRETTY_PRINT);
    }

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

        echo json_encode($plans, JSON_PRETTY_PRINT);
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

        echo json_encode($goals, JSON_PRETTY_PRINT);
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

        echo json_encode($task, JSON_PRETTY_PRINT);
    }

    function get_task_notes($task_id)
    {
        $this->db->where(array('task_id' => $task_id));
        $result = $this->db->get('task_note')->result_array();

        $task_notes["data"] = $result;
        $task_notes["status"] = "success";

        echo json_encode($task_notes, JSON_PRETTY_PRINT);
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

        $out = json_encode($rst, JSON_PRETTY_PRINT);

        echo $out;
    }

 

    function get_task_types()
    {

        $this->db->select(array('task_type_id', 'task_type_name'));
        $this->db->where(array('task_type_is_active' => 1));

        $task_types["data"] =  $this->db->get('task_type')->result_array();
        $task_types["status"] = "success";

        echo json_encode($task_types, JSON_PRETTY_PRINT);
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
        $goals["data"] = $this->db->get("task")->result_array();
        $goals["status"] = "success";

        echo json_encode($goals, JSON_PRETTY_PRINT);
    }

    function login()
    {
        $post = $this->input->post();

        //return json_encode($post);

        $query = $this->db->get_where(
            'user',
            array('user_email' => $post['email'], 'user_password' => $post['password'], 'user_active' => 1)
        );

        $result = ["msg" => "User logged successfully"];

        $user = [];

        if ($query->num_rows() > 0) {
            $user = $query->row_array();
            $result["data"] = $user;
            $result["status"] = "success";

            // Create a plan when missing one
            $this->auto_create_plan($user['user_id']);

        } else {
            $result["msg"] = "Invalid Email or Password";
        }

        echo json_encode($result, JSON_PRETTY_PRINT);
        //echo trim(preg_replace('/\s+/', '', json_encode($result)));
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

        $out = '';

        $query = $this->db->get_where('user', ['user_email' => $post['email']]);

        if ($query->num_rows() > 0) {
            $rst['msg'] = 'User registration failed. Email already exists';
            $out = json_encode($rst, JSON_PRETTY_PRINT);
        } else {
            $this->db->insert('user', $data);
            $rst['status'] = 'success';
            $out = json_encode($rst, JSON_PRETTY_PRINT);
        }

        echo $out;
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

        echo json_encode($result, JSON_PRETTY_PRINT);
    }

    function get_year_start_month(){
        $this->db->where(array('setting_name' => 'year_start_month'));
        $year_start_month = $this->db->get('setting')->row()->setting_value;

        return $year_start_month;
    }

    private function get_fy($date_string, $override_fy_year_digits_config = false)
	{

		$CI = &get_instance();
		$fy_year_digits = 2;//$CI->config->item('fy_year_digits');

		$date_month_number = date('n', strtotime($date_string));
		$fy = ($fy_year_digits == 4 && !$override_fy_year_digits_config) ? date('Y', strtotime($date_string)) : date('y', strtotime($date_string));

		// $CI->read_db->select(array('month_number'));
		// $CI->read_db->order_by('month_order', 'ASC');
		// $months_array = $CI->read_db->get('month')->result_array();
        
        $months = [7,8,9,10,11,12,1,2,3,4,5,6];

		//$months = array_column($months_array, 'month_number');

        //echo json_encode($months);

		$first_month = current($months);
		$last_month = end($months);

		$fy_year_reference = 'next';//$CI->config->item('fy_year_reference');

		$half_year_months = array_chunk($months, 6);

		if ($first_month != 1 && $last_month != 12) {

			if (in_array($date_month_number, $half_year_months[0]) && $fy_year_reference == 'next') {
				$fy++;
			}
		}

		return $fy;
	}

    /**
     * @todo This method is yet to me implemented. It has been hard coded.
     */
    function get_fy_start_end_date($fy){
        return ['fy_start_date' => '2021-07-01', 'fy_end_date' => '2022-06-30'];
    }

    function auto_create_plan($user_id){ 
   
        $deactivate_user_active_plans = $this->deactivate_user_active_plans($user_id);

        if($deactivate_user_active_plans){
            $fy = $this->get_fy(date('Y-m-d'));
            $fy_dates = $this->get_fy_start_end_date($fy);

            $data['plan_name'] = "My FY".$fy." Plan";
            $data['plan_start_date'] = $fy_dates['fy_start_date'];
            $data['plan_end_date'] = $fy_dates['fy_end_date'];
            $data['plan_year'] = $fy;
            $data['plan_status'] = 1;
            $data['user_id'] = $user_id;
            $data['plan_created_by'] = $user_id;
            $data['plan_created_date'] = date('Y-m-d');
            $data['plan_last_modified_by'] = $user_id;

            $this->db->insert('plan', $data);
        }

    }

    function get_quarters(){

        $quarters = [
            ['quarter_number' => 1, 'quarter_name' => 'First Quarter [July to September]'],
            ['quarter_number' => 2, 'quarter_name' => 'Second Quarter [October to December]'],
            ['quarter_number' => 3, 'quarter_name' => 'Third Quarter [January to March]'],
            ['quarter_number' => 4, 'quarter_name' => 'Fourth Quarter [April to June]']
          ];

        $qtr["data"] = $quarters;
        $qtr["status"] = "success";
        
        echo json_encode($qtr, JSON_PRETTY_PRINT);
    }
}
