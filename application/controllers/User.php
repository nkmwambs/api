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
        $post = $this->input->post();

        $data['user_name'] = $post['userName'];
        $data['user_email'] = $post['userEmail'];
        $data['user_age'] = $post['userAge'];
        $data['user_address'] = $post['userAddress'];
        $data['user_password'] = $post['userPassword'];

        $this->db->trans_start();
        $this->db->insert('user', $data);
        $this->db->trans_commit();

        if ($this->db->trans_status() == false) {
            $rst['msg'] = 'Insert failed';
            echo json_encode($rst);
        } else {
            $rst['status'] = 'success';
            echo json_encode($rst);
        }
    }
}
