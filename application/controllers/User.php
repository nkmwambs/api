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

        $result = $this->db->get_where(
            'user',
            array('user_email' => $post['userEmail'], 'user_password' => $post['userPassword'])
        );

        if ($result->num_rows() > 0) {
            $rst['data'] = $result->row_array();
            $rst['status'] = 'success';
            echo json_encode($rst);
        } else {
            $rst['msg'] = "Invalid Email or Password";
            echo json_encode($rst);
        }
    }

    function register()
    {
    }
}
