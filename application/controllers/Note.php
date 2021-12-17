<?php defined('BASEPATH') or exit('No direct script access allowed');

class Note extends CI_Controller{
    
    function __construct()
    {
        parent::__construct();
        $this->load->database(); 
    }

    function api_result($method_call, ...$args){
        $method_call_result = call_user_func_array(array($this, $method_call),$args);
        echo json_encode($method_call_result, JSON_PRETTY_PRINT);
    }
}