<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends Admin_Controller {

    public function __construct() {
        parent:: __construct();
        $this->checkAuth();
        $this->load->helper('common');
        //$this->data = json_decode(file_get_contents("php://input"));
    }

    function dashboard() {
        $this->frontHtml('Dashboard','dashboard');
    }
    
    function myProfile() {
        if ($this->form_validation->run('add-speaker') == false) {
            $data['user'] = $this->common->getData('users',array('id'=>1),array('single'));
            $this->frontHtml('My Profile', 'my-profile',$data);
        } else {
            $_POST['image'] = "";
            if (!empty($_FILES)) {
                $image = $this->common->do_upload('image', 'assets/uploads/users');
                if (!empty($image['upload_data'])) {
                    $_POST['image'] = 'assets/uploads/users/'.$image['upload_data']['file_name'];
                    if(file_exists($_POST['old_image'])){
                        unlink($_POST['old_image']);
                    }
                }
                else{
                    $_POST['image'] = $_POST['old_image'];
                }
            }else{
                $_POST['image'] = $_POST['old_image'];
            }
            $post = $this->common->getField('users', $_POST);
            $result = $this->common->updateData('users', $post,array('id'=>1));
            if ($result) {
                $this->flashMsg('success', langMsg('profile_update_success'));
                redirect(base_url('admin/myProfile'));
            } else {
                $this->flashMsg('danger', langMsg('error'));
                redirect(base_url('admin/myProfile'));
            }
        }
    }
    
    function changePassword() {
        if ($this->form_validation->run('change-password') == false) {
            $this->frontHtml('Change Password', 'change-password');
        } else {
            $result = false;
            $user = $this->common->getData('users',array('id'=>1),array('single'));
            if($user['password'] != md5($_POST['old_password'])){
                $this->flashMsg('danger', langMsg('old_password_wrong'));
                redirect(base_url('admin/changePassword'));
            }else{
                $post = $this->common->getField('users', $_POST);
                $result = $this->common->updateData('users', array('password'=>md5($_POST['new_password'])),array('id'=>1));                
            }
            if ($result) {
                $this->common->sendMail($user['email'],'Password Change','Hello '.$user['fname'].'<br> Your Password has been change successfully');
                $this->flashMsg('success', langMsg('password_update_success'));
                redirect(base_url('admin/myProfile'));
            } else {
                $this->flashMsg('success', langMsg('error'));
                redirect(base_url('admin/changePassword'));
            }
        }
    }

    function addOrganizers() {
        if ($this->form_validation->run('add-organizers') == false) {
            $this->frontHtml('Organizer', 'organisers/add-organizers');
        } else {
            $_POST['logo'] = "";
            if (!empty($_FILES)) {
                $logo = $this->common->do_upload('logo', 'assets/uploads/organizers');
                if (!empty($logo['upload_data'])) {
                    $_POST['logo'] = 'assets/uploads/organizers/' . $logo['upload_data']['file_name'];
                }
            }
            $post = $this->common->getField('organisers', $_POST);
            $result = $this->common->insertData('organisers', $post);
            if ($result) {
                $this->flashMsg('success', langMsg('add_organiser_succ'));
                redirect(base_url('admin/organisers'));
            } else {
                $this->flashMsg('success', langMsg('error'));
                redirect(base_url('admin/addOrganizers'));
            }
        }
    }
    
    function editOrganiser($id="") {    
        if($id == ""){
            redirect(base_url('admin/addOrganizers'));
        }
        $rules = removeKey('add-organizers','email'); 
        $this->form_validation->set_rules($rules);
        if($this->form_validation->run() == false){
            $data['organiser'] = $this->common->getData('organisers',array('id'=>  base64_decode($id)),array('single'));
            $this->frontHtml('Organizer','organisers/add-organizers',$data);            
        }else{
            
            if (!empty($_FILES)) {
                $logo = $this->common->do_upload('logo', 'assets/uploads/organizers');
                if (!empty($logo['upload_data'])) {
                    $_POST['logo'] = 'assets/uploads/organizers/' . $logo['upload_data']['file_name'];
                    if(file_exists($_POST['old_logo'])){
                        unlink($_POST['old_logo']);
                    }
                }else{
                    $_POST['logo'] = $_POST['old_logo'];
                }
            }
            else{
                $_POST['logo'] = $_POST['old_logo'];
            }
            $post = $this->common->getField('organisers', $_POST);
            $result = $this->common->updateData('organisers', $post,array('id'=> base64_decode($id)));
            if ($result) {
                $this->flashMsg('success', langMsg('update_organiser_succ'));
                redirect(base_url('admin/organisers'));
            } else {
                $this->flashMsg('success', langMsg('error'));
                redirect(base_url('admin/addOrganizers'));
            }
        }
    }
    
    function organisers(){
        $data['organisers'] = $this->common->getData('organisers',array(),array('order_by'=>'id'));
        $this->frontHtml('Organizer List','organisers/organiser-list',$data);
    }
    
    function addEvent() {
        if ($this->form_validation->run('add-event') == false) {
            $data['organisers'] = $this->common->getData('organisers',array('status'=>0));
            $this->frontHtml('Add Event', 'events/add-event',$data);            
        } else {
           
            $_POST['logo'] = $_POST['floor_plan'] = "";
            if (!empty($_FILES)) {
                $logo = $this->common->do_upload('logo', 'assets/uploads/events');
                if (!empty($logo['upload_data'])) {
                    $_POST['logo'] = 'assets/uploads/events/' . $logo['upload_data']['file_name'];
                }
                $floor_plan = $this->common->do_upload('floor_plan', 'assets/uploads/events');
                if (!empty($logo['upload_data'])) {
                    $_POST['floor_plan'] = 'assets/uploads/events/' . $floor_plan['upload_data']['file_name'];
                }                
            }
            
            $post = $this->common->getField('events', $_POST);
            $event_organiser = $this->common->getField('event_organizers', $_POST);
            
            $result = $this->common->insertData('events', $post);
            $event_ins_id = $this->db->insert_id();
            if ($result) {
                foreach ($_POST['organiser_id'] as $organiser_id) {
                    $this->common->insertData('event_organizers',array('event_id'=>$event_ins_id,'organiser_id'=> $organiser_id));
                }
                $this->flashMsg('success', langMsg('add_event_succ'));
                redirect(base_url('admin/events'));
            } else {
                $this->flashMsg('success', langMsg('error'));
                redirect(base_url('admin/addevent'));
            }
        }
    }
    
    function editEvent($id="") {
        $this->blank_id($id);
        $this->config->load('form_validation', TRUE);
        $validation = $this->config->item('form_validation');        
        if($this->form_validation->run('add-event') == false){
            $data['event'] = $this->common->getData('events',array('id'=>  base64_decode($id)),array('single'));
            $data['organisers'] = $this->common->getData('organisers',array('status'=>0));
            $data['organiser_id'] = $this->common->pluck('event_organizers',array('event_id'=> base64_decode($id)),'organiser_id');
            $data['active'] = 'event';
            $this->frontHtml('General Detail','events/edit-event',$data);            
        }else{        
            if (!empty($_FILES)) {
                if($_FILES['logo']['name'] != ""){
                    $logo = $this->common->do_upload('logo', 'assets/uploads/events');
                    if (!empty($logo['upload_data'])) {
                        $_POST['logo'] = 'assets/uploads/events/' . $logo['upload_data']['file_name'];
                        if(file_exists($_POST['old_logo'])){
                            unlink($_POST['old_logo']);
                        }
                    }else{
                        $_POST['logo'] = $_POST['old_logo'];
                    }                    
                }
                else{
                    $_POST['logo'] = $_POST['old_logo'];
                }  
                if($_FILES['floor_plan']['name'] != ""){
                    $floor_plan = $this->common->do_upload('floor_plan', 'assets/uploads/events');
                    if (!empty($floor_plan['upload_data'])) {
                        $_POST['floor_plan'] = 'assets/uploads/events/' . $floor_plan['upload_data']['file_name'];
                        if(file_exists($_POST['old_floor_plan'])){
                            unlink($_POST['old_floor_plan']);
                        }
                    }else{
                        $_POST['floor_plan'] = $_POST['old_floor_plan'];
                    }                    
                }
                else{
                    $_POST['floor_plan'] = $_POST['old_floor_plan'];
                }  
            }
            else{
                $_POST['logo'] = $_POST['old_logo'];
                $_POST['floor_plan'] = $_POST['old_floor_plan'];
            }
           
            $post = $this->common->getField('events', $_POST);
            $result = $this->common->updateData('events', $post,array('id'=> base64_decode($id)));
            if ($result) {
                $this->common->deleteData('event_organizers',array('event_id'=> base64_decode($id)));
                foreach ($_POST['organiser_id'] as $organiser_id) {
                    $this->common->insertData('event_organizers',array('event_id'=> base64_decode($id),'organiser_id'=> $organiser_id));
                }
                $this->flashMsg('success', langMsg('update_general_succ'));
                redirect(base_url('admin/editEvent/'.$id));
            } else {
                $this->flashMsg('success', langMsg('error'));
                redirect(base_url('admin/editEvent/'.$id));
            }
        }
    }

    function events(){
        $data['events'] = $this->common->getData('events',array(),array('order_by'=>'id'));        
        $this->frontHtml('Event List','events/event-list',$data);
    }
    
    function venue($id="") {  
        $this->blank_id($id);
        if($this->form_validation->run('add-venue') == false){
            $data['event'] = $this->common->getData('events',array('id'=> base64_decode($id)),array('single'));
            $data['venue'] = $this->common->getData('event_venue',array('event_id'=> base64_decode($id)),array('single'));
            $data['active'] = 'venue';
            $this->frontHtml('Venue','events/add-venue',$data);
            //$this->load->view('events/map-venue',$data);
        }else{
            $_POST['event_id'] = base64_decode($id);
            $post = $this->common->getField('event_venue',$_POST);
            
            $venue = $this->common->getData('event_venue',array('event_id'=>$post['event_id']),array('single'));
            if($venue){
                unset($post['event_id']);
                $result = $this->common->updateData('event_venue',$post,array('event_id'=>$_POST['event_id']));
            }else{
                $result = $this->common->insertData('event_venue',$post);
            }
            if($result){
                $this->flashMsg('success', langMsg('event_venue_succ'));
                redirect(base_url('moderator/addModerator/'.$id));
            }else{
                 $this->flashMsg('success', langMsg('error'));
                 redirect(base_url('admin/venue/'.$id));
            }
        }
    }
    
    
    
    function addAgenda($event_id="") {
        $this->blank_id($event_id);
        if($this->form_validation->run('add-agenda') == false){
            $data['event'] = $this->common->getData('events',array('id'=> base64_decode($event_id)),array('single'));
            $data['agendas'] = $this->common->getData('agenda',array('event_id'=> base64_decode($event_id)));
            $data['active'] = 'agenda';
            $this->frontHtml('Agenda','events/add-agenda',$data);
        }else{
            $_POST['event_id'] = base64_decode($event_id);                
            $post = $this->common->getField('agenda',$_POST);
            $result = $this->common->insertData('agenda',$post); 
                
            if($result){
                $this->flashMsg('success', langMsg('add_agenda_succ'));
            }else{
                 $this->flashMsg('danger', langMsg('error'));
            }
            redirect(base_url('admin/addAgenda/'.$event_id));
        }          

    }
    
    function editAgenda($event_id="",$agenda_id) {   
        $this->blank_id($event_id);
        if($this->form_validation->run('add-agenda') == false){
            $data['event'] = $this->common->getData('events',array('id'=> base64_decode($event_id)),array('single'));
            $data['agendas'] = $this->common->getData('agenda',array('event_id'=> base64_decode($event_id)));
            $data['agenda'] = $this->common->getData('agenda',array('id'=> base64_decode($agenda_id)),array('single'));
            $data['active'] = 'agenda';
            $this->frontHtml('Agenda','events/add-agenda',$data);
        }else{                       
            $result = $this->common->updateData('agenda',array('name'=>$_POST['name']),array('id'=> base64_decode($agenda_id),'event_id'=> base64_decode($event_id))); 
            if ($result) {                
                $this->flashMsg('success', langMsg('update_agenda_succ'));
            } else {
                $this->flashMsg('danger', langMsg('error'));              
            }
            redirect(base_url('admin/editAgenda/'.$event_id.'/'.$agenda_id));
        }
    }
}
