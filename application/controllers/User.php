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
