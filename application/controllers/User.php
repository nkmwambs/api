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

    function add_goal()
    {
        $post = $this->input->post();

        $data["goal_name"] = $post['goal_name'];
        $data["theme_id"] = $post['theme_id'];
        $data["goal_description"] = $post['goal_description'];
        $data["goal_start_date"] = $post['goal_start_date'];
        $data["goal_end_date"] = $post['goal_end_date'];
        $data["user_id"] = $post['user_id'];

        $this->db->insert('goal', $data);

        $rst = [];

        if ($this->db->affected_rows()) {
            $rst['data']['goal_id'] = $this->db->insert_id();
            $rst['status'] = 'success';
        } else {
            $rst['msg'] = "";
        }

        $out = json_encode($rst, JSON_PRETTY_PRINT);

        echo $out;
    }

    function goals($user_id = "")
    {
        $this->db->select(array('goal_id', 'goal_name', 'theme_name', 'goal_start_date', 'goal_end_date', 'user_id'));
        //$this->db->where(array("theme_status" => 1));
        $this->db->join('theme', 'theme.theme_id=goal.theme_id');
        $this->db->order_by('theme.theme_id', 'goal_id');

        if ($user_id != "") {
            $this->db->where(array('user_id' => $user_id));
        }

        $goals["data"] = $this->db->get("goal")->result_array();
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

        if ($query->num_rows() > 0) {
            $result["data"] = $query->row_array();
            $result["status"] = "success";
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
}
