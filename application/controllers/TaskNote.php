<?php defined('BASEPATH') or exit('No direct script access allowed');

class TaskNote extends CI_Controller{

    function __construct()
    {
        parent::__construct();
        $this->load->database();

        $this->settings_library->set_settings();
    }

    function get_task_notes($task_id)
    {
        $this->db->where(array('task_id' => $task_id));
        $result = $this->db->get('task_note')->result_array();

        $task_notes["data"] = $result;
        $task_notes["status"] = "success";

        //echo json_encode($task_notes, JSON_PRETTY_PRINT);
        return $task_notes;
    }

    function add_task_note()
    {
        $post = $this->input->post();

        $data['task_note'] = $post['task_note'];
        $data['task_id'] = $post['task_id'];
        $data['task_note_created_by'] = $post['task_note_created_by'];
        $data['task_note_created_date'] = $post['task_note_created_date'];
        $data['task_note_last_modified_by'] = $post['task_note_last_modified_by'];

        $this->db->insert('task_note', $data);

        $rst = [];

        if ($this->db->affected_rows()) {
            $rst['data']['task_note_id'] = $this->db->insert_id();
            $rst['status'] = 'success';
        } else {
            $rst['msg'] = "Insert Failed";
        }

        //$out = json_encode($rst, JSON_PRETTY_PRINT);

        echo $rst;
    }
}