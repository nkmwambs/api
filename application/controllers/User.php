<?php
defined('BASEPATH') or exit('No direct script access allowed');

class User extends CI_Controller
{

    function __construct()
    {
        parent::__construct();
        $this->load->database();

        $this->load->model('plan_model');

        $this->settings_library->set_settings();

    }

    public function index()
    {
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
            $user['language_phrases'] = $this->settings_library->language_phrases($user['user_id']);
            $result["data"] = $user;
            $result["status"] = "success";

            // Create a plan when missing one
            $this->plan_model->auto_create_plan($user['user_id']);

        } else {
            $result["msg"] = "Invalid Email or Password";
        }

        //echo json_encode($result, JSON_PRETTY_PRINT);
        return  $result;
    }

    function register()
    {
        $post = $this->input->post();

        $data['user_name'] = $post['name'];
        $data['user_email'] = $post['email'];
        $data['user_age'] = $post['age'];
        $data['user_address'] = $post['address'];
        $data['user_password'] = $post['password'];

        $rst = [];

        $query = $this->db->get_where('user', ['user_email' => $post['email']]);

        if ($query->num_rows() > 0) {
            $rst['msg'] = 'User registration failed. Email already exists';
        } else {
            $this->db->insert('user', $data);
            $rst['status'] = 'success';
        }

        return $rst;
    }


    function api_result($method_call, ...$args){
        $method_call_result = call_user_func_array(array($this, $method_call),$args);
        echo json_encode($method_call_result, JSON_PRETTY_PRINT);
    }
}
