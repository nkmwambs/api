<?php defined('BASEPATH') or exit('No direct script access allowed');

class Settings_library {
    public $fy_year_digits = 2;
    public $fy_start_month = 7;
    public $fy_year_reference = 'next';

    private $CI = null;

    function __construct()
    {
        $this->CI =& get_instance();

        $this->CI->load->database();
    }

    function set_settings(){
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

        $this->CI->db->insert('setting',$data);

        return $setting_value;
    }

    private function settings($selected_setting_names = []){

        $this->CI->db->select(array('setting_name','setting_value'));
        $settings_data = $this->CI->db->get('setting')->result_array();

        if(!empty($selected_setting_names)){
            $this->CI->db->where_in('setting_name', $selected_setting_names);
        }

        $setting_names = array_column($settings_data,'setting_name');
        $setting_values = array_column($settings_data,'setting_value');

        $settings = array_combine($setting_names,$setting_values);

        return $settings;
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
  
    function get_fy_start_end_date($fy){
        
        $months = $this->month_order($this->fy_start_month); // List of months in a year in a custom order

        $start_end_dates_of_year = $this->period_date_limits($fy, $months);
 
        return $start_end_dates_of_year;
    }

    function get_quarters(){

        $q1 = $this->quarter_month_limits(1, true);
        $q2 = $this->quarter_month_limits(2, true);
        $q3 = $this->quarter_month_limits(3, true);
        $q4 = $this->quarter_month_limits(4, true);

        $quarters = [
            1 => ['quarter_number' => 1, 'quarter_name' => 'First Quarter ['. $q1['period_start_month'].' to '. $q1['period_end_month'].']'],
            2 => ['quarter_number' => 2, 'quarter_name' => 'Second Quarter ['.$q2['period_start_month'].' to '.$q2['period_end_month'].']'],
            3 => ['quarter_number' => 3, 'quarter_name' => 'Third Quarter ['.$q3['period_start_month'].' to '.$q3['period_end_month'].']'],
            4 => ['quarter_number' => 4, 'quarter_name' => 'Fourth Quarter ['.$q4['period_start_month'].' to '.$q4['period_end_month'].']']
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

    function quarter_date_limits($fy, $quarter_number){

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
}