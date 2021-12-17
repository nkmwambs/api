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
    

    function goal_statistics($goal_id, $date){
        $stats['data']['count_goal_due_tasks'] = $this->task_model->count_goal_due_tasks($goal_id);
        $stats['data']['count_goal_complete_tasks']  = $this->task_model->count_goal_complete_tasks($goal_id);
        $stats['data']['count_goal_overdue_tasks']  = $this->task_model->count_goal_overdue_tasks($goal_id, $date);
        $stats['data']['count_goal_all_tasks']  = $this->task_model->count_all_goal_tasks($goal_id);

        $stats["status"] = "success";

        return $stats;
    }

    function goals()
    {

        $plan_id = isset($_GET['plan_id']) ? $_GET['plan_id'] : 0;

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

        $user_id = $this->db->get_where('plan',array('plan_id' => $post['plan_id']))->row()->user_id;

        $plan = $this->plan_model->user_active_plan($user_id);
        $year = $this->settings_library->get_fy($plan['plan_start_date']);

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

    function api_result($method_call, ...$args){
        $method_call_result = call_user_func_array(array($this, $method_call),$args);
        echo json_encode($method_call_result, JSON_PRETTY_PRINT);
    }
}