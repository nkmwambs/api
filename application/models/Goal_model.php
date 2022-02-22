<?php 

class Goal_model extends CI_Model{

    function __construct()
    {
        parent::__construct();
        $this->load->database();
        
    }

    function count_plan_goals($plan_id){

        $this->db->join('plan','plan.plan_id=goal.plan_id');
        $this->db->where(array('goal.plan_id'=>$plan_id));
        $this->db->where(array('plan.deleted_at' => NULL));
        $this->db->where(array('goal.deleted_at' => NULL));
        $count_goals = $this->db->get('goal')->num_rows();

        return $count_goals;
    }


    function overdue_goals($date, $user_id)
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
        $this->db->where(array('goal.deleted_at' => NULL));
        $this->db->join('theme', 'theme.theme_id=goal.theme_id');
        $this->db->join('task', 'task.goal_id=goal.goal_id');
        $result = $this->db->get('goal')->result_array();

        $result = array_unique($result, SORT_REGULAR);

        return $result;
    }

    // function get_overdue_goals($date, $user_id)
    // {

    //     $goals["data"] = $this->overdue_goals($date, $user_id);
    //     $goals["status"] = "success";

    //     echo json_encode($goals, JSON_PRETTY_PRINT);
    // }
    
}