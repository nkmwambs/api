<?php 

class Plan_model extends CI_Model{
    function __construct()
    {
        parent::__construct();
        $this->load->database();

        $this->settings_library->set_settings();
    }

    function auto_create_plan($user_id){ 
        $fy = $this->settings_library->get_fy(date('Y-m-d'));
        $deactivate_user_active_plans = $this->deactivate_user_active_plans($user_id,$fy);

        $affected_rows = 0;

        if($deactivate_user_active_plans){
            
            $fy_dates = $this->settings_library->get_fy_start_end_date($fy);
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

    function user_active_plan($user_id)
    {

        $plan = [];

        $this->db->select(array(
            'plan_id', 'plan_name', 'plan_start_date',
            'plan_end_date', 'plan_status', 'user_first_name', 'user_last_name', 'plan_created_date','plan_year'
        ));

        $this->db->where(array('plan.user_id' => $user_id));
        
        $this->db->where(array('plan_status' =>  1));
     
        
        $this->db->join('user', 'user.user_id=plan.plan_created_by');
        $plan_obj = $this->db->get('plan');

        if($plan_obj->num_rows() > 0){
             $plan = $plan_obj->row_array();
        }

        return $plan;
    }

    private function deactivate_user_active_plans($user_id, $current_fy){

        $active_plan = $this->user_active_plan($user_id);
        $active_plan_fy = isset($active_plan['plan_year']) ? $active_plan['plan_year'] : 0;

        $deactivation_successful = false;

        if($current_fy > $active_plan_fy){
            $data['plan_status'] = 2;
            $this->db->where(array('plan_status'=>1,'user_id'=>$user_id));
            $this->db->update('plan',$data); 
            
            $deactivation_successful = true;
        }

        return $deactivation_successful;
          
    }
}