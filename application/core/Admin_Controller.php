<?php

/**
 * Description of Admin_Controller
 *
 * @author devendrarokade
 */
class Admin_Controller extends Base_Controller{
    
   public function __construct() {
        parent:: __construct();
        $this->checkAuth();
        $this->load->helper('common');
        //$this->data = json_decode(file_get_contents("php://input"));
    }
    
    function checkAuth() {
        if ($user = $this->session->userdata('user')) {

        } else {
            redirect(base_url('admin_login'));
        }
    }
    
    function checkEmail($table) {
        $isAvailable = true;
        $user = $this->common->getData($table,array('email'=>$_REQUEST['email']));
        if($user){
            $isAvailable = false;
        }
        echo json_encode(array(
            'valid' => $isAvailable,
        ));
    }
    
    function blank_id($id) {
        if($id == ""){
            redirect(base_url('admin/addEvent'));
        }
    }
    
    function changeStatus($table,$field) {
        $val = $this->common->getData($table,array('id'=> base64_decode($_POST['id'])),array('single','field'=>$field));
        $status = 1;
        if($val[$field] == 1){
            $status = 0;
        }
        $result = $this->common->updateData($table,array($field => $status),array('id'=> base64_decode($_POST['id'])));
        if($result){
            echo $status; die;
        }else{
            echo '2'; die;
        }
    }
    
    function multiChangeStatus() {
        $id =$_POST['id'];
        $result = false;
        if(!empty($id)){
            foreach ($_POST['id'] as $key => $value) {
                $result = $this->common->updateData($_POST['table'],array($_POST['field'] => $_POST['status']),array('id' => base64_decode($value)));
            }
        }
        $status = 'Disapproved';
        if($_POST['status'] == 1){ $status = 'Approved'; }
        if($result){
            echo $status; die;
        }else{
            echo '0'; die;
        }
    }
    
    function addToEvent() {
        if($_POST['event_id'] == ""){
            echo '0'; die;
        }
        $this->common->deleteData($_POST['table'],array('event_id'=> base64_decode($_POST['event_id'])));
        $result = false;
        $name = explode('_', $_POST['table']);
        if(!empty($_POST['id'])){
            foreach ($_POST['id'] as $key => $value) {
                $result = $this->common->insertData($_POST['table'],array('event_id'=> base64_decode($_POST['event_id']),$name[1].'_id'=> $value));
            }
        }
        if($result){
            echo '1'; die;
        }else{
            echo '0'; die;
        }
    }
    
    function getAttendee($event_id,$field) {
        return $this->common->pluck('event_moderator',array('event_id'=> base64_decode($event_id)),$field);
    }
    
    
}
