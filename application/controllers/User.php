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
            array('user_email' => 'nkmwambs@gmail.com', 'user_password' => '@Compassion123')
        );

        if ($result->num_rows() > 0) {
            echo json_encode($result->row_array());
        } else {
            echo json_encode(['msg' => "Invalid Email or Password"]);
        }
    }

    function register()
    {
    }
}
