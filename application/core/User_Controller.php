<?php

/**
 * Description of Admin_Controller
 *
 * @author devendrarokade
 */
class User_Controller extends Base_Controller{
    
   public function __construct() {
        parent:: __construct();
        $this->checkAuth();
        $this->load->helper('common');
        //$this->data = json_decode(file_get_contents("php://input"));
    }
    
    function checkAuth() {
        if ($user = $this->session->userdata('user')) {
            return $user;
        } else {
            redirect(base_url('admin_login'));
        }
    }
    
    function checkUnique($table) {

        $isAvailable = true;
        $user = $this->common->getData($table,$_GET);
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
        $post = $_GET;// $this->security->xss_clean($_POST);
        $val = $this->common->getData($table,array('id'=> base64_decode($post['id'])),array('single','field'=>$field));
        $status = 1;
        if($val[$field] == 1){
            $status = 0;
        }
        $result = $this->common->updateData($table,array($field => $status),array('id'=> base64_decode($post['id'])));
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
}
