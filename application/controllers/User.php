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

    function login()
    {
        $post = $this->input->post();

        //return json_encode($post);

        $query = $this->db->get_where(
            'user',
            array('user_email' => $post['email'], 'user_password' => $post['password'])
        );

        $result = ["msg" => "User logged successfully"];

        if ($query->num_rows() > 0) {
            $result["data"] = $query->row_array();
            $data['status'] = "success";
        } else {
            $result["msg"] = "Invalid Email or Password";
        }

        echo json_encode($result, JSON_PRETTY_PRINT);
        //echo trim(preg_replace('/\s+/', '', json_encode($result)));
    }

    function register()
    {
        $post = $this->input->post();

        $data['user_name'] = $post['userName'];
        $data['user_email'] = $post['userEmail'];
        $data['user_age'] = $post['userAge'];
        $data['user_address'] = $post['userAddress'];
        $data['user_password'] = $post['userPassword'];

        $this->db->trans_start();
        $this->db->insert('user', $data);
        $this->db->trans_commit();

        $out = '';

        if ($this->db->trans_status() == false) {
            $rst['msg'] = 'Insert failed';
            $out = json_encode($rst, JSON_PRETTY_PRINT);
        } else {
            $rst['status'] = 'success';
            $out = json_encode($rst, JSON_PRETTY_PRINT);
        }

        echo $out;
    }
}
