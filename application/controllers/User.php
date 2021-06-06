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

        $out = '';

        $rst = new stdClass();

        if ($result->num_rows() > 0) {
            $rst->data = $result->row();
            $rst->status = 'success';
            $out = json_encode($rst);
        } else {
            $rst->msg = "Invalid Email or Password";
            $out = json_encode($rst);
        }

        echo trim(preg_replace('/\s+/', '<br/>', $out));
        //echo $out;
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
            $out = json_encode($rst);
        } else {
            $rst['status'] = 'success';
            $out = json_encode($rst);
        }

        echo trim(preg_replace('/\s+/', '<br/>', $out));
    }
}
