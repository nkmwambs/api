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
}