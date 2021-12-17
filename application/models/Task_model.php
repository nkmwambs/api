<?php 

class Task_model extends CI_Model{

    function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    function count_goal_due_tasks($goal_id)
    {
        
        $this->db->where(array(
            'goal.goal_id' => $goal_id
        ));
        $this->db->join('goal', 'goal.goal_id=task.goal_id');
        $this->db->where("task_end_date <=  DATE_SUB(DATE(NOW()), INTERVAL -7 DAY) AND task_end_date >= DATE(NOW())");
        $result = $this->db->get('task')->num_rows();

        return $result;

    }

    function count_goal_complete_tasks($goal_id){
        $this->db->where(array('goal_id'=>$goal_id,'task_status' => 2));
        $count_all_goal_tasks = $this->db->get('task')->num_rows();

        return $count_all_goal_tasks;
    }

    function count_all_goal_tasks($goal_id){

        $this->db->where(array('goal_id'=>$goal_id));
        $count_all_goal_tasks = $this->db->get('task')->num_rows();

        return $count_all_goal_tasks;
    }

    function count_plan_due_tasks($plan_id)
    {
        
        $this->db->where(array(
            'plan.plan_id' => $plan_id
        ));
        $this->db->join('goal', 'goal.goal_id=task.goal_id');
        $this->db->join('plan', 'plan.plan_id=goal.plan_id');
        $this->db->where(array('plan.deleted_at' => NULL));
        $this->db->where(array('goal.deleted_at' => NULL));
        $this->db->where(array('task.deleted_at' => NULL));
        $this->db->where("task_end_date <=  DATE_SUB(DATE(NOW()), INTERVAL -7 DAY) AND task_end_date >= DATE(NOW())");
        $result = $this->db->get('task')->num_rows();

        return $result;

    }

    function count_overdue_plan_tasks($date, $plan_id)
    {
       
        $this->db->where(array('task_end_date < ' => $date, 'goal.plan_id' => $plan_id));
        $this->db->join('goal', 'goal.goal_id=task.goal_id');
        $this->db->join('plan', 'plan.plan_id=goal.plan_id');
        $result = $this->db->get('task')->num_rows();

        return $result;
    }


    function count_goal_overdue_tasks($goal_id, $date)
    {
       
        $this->db->where(array('task_end_date < ' => $date, 'goal.goal_id' => $goal_id));
        $this->db->join('goal', 'goal.goal_id=task.goal_id');
        $result = $this->db->get('task')->num_rows();

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

    function count_plan_tasks($plan_id)
    {
        $this->db->select(array('task_id'));
        $this->db->where(array('plan.plan_id' => $plan_id));
        $this->db->join('goal','goal.goal_id=task.goal_id');
        $this->db->join('plan','plan.plan_id=goal.plan_id');
        $count_tasks = $this->db->get('task')->num_rows();

        return $count_tasks;
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
}