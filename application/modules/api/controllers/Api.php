<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends Api_Controller {

    public function __construct() {
        parent:: __construct();
        //$this->user_id = $this->checkAuth();
        $this->user_id = $this->AuthCheck2();
        $this->load->helper('common');
        $this->data = (array)json_decode(file_get_contents("php://input"));
        
    }

    public function getKey() {
        $result = $this->common->getFieldKey($_POST['table']);
        echo json_encode($result);
    }
    
   

    public function login() {  
        
        $parameter = ['email','password'];
        $post = $this->requireParameter($parameter,$_POST);
//        $platform = ['platform'];
//        $post3 = $this->requireParameter($platform,$_POST);
        $post['password'] = md5($post['password']); 
        $result = $this->common->getData('users',$post, array('single'));
       // $result = $this->common->getData('users', array('email' => $this->data->email, 'password' => $this->data->password), array('single'));

        if ($result && $result['status'] == 0) {
            $old_device = false;
            if(!empty($_POST['device_token'])){
                $old_device = $this->common->getData('users', array('device_token' => $_POST['device_token']), array('single', 'field' => 'id'));
            
                $this->common->updateData('users', array('device_token' => $_POST['device_token'],'platform'=>$_POST['platform']), array('id' => $result['id']));
            }
            if (!empty($old_device)) {
                $this->common->updateData('users', array('device_token' => "",'platform' =>""), array('id' => $old_device['id']));
            }
            
            $result['device_token'] = $_POST['device_token'];
            $this->response(true, 'Successfully Login', array("response" => $result));
        }
        elseif ($result && $result['status'] == 1) {
            $this->response(false, apiMsg('user_blocked'));
        }
        else {            
            $this->response(false, apiMsg('login_error'));
        }
    }

    public function signup() {
        $existEmail = false;
        if ($_POST['email'] != "") {
            $existEmail = $this->common->getData('user', array('email' => $_POST['email']), array('single'));
        }
        $existMobile = $this->common->getData('user', array('mobile' => $_POST['mobile']), array('single'));
        $_POST['otp'] = '1234';

        if ($existEmail) {
            $this->response('false', "This email address already exists.", array("userinfo" => ""));
        } elseif ($existMobile) {
            $this->response('false', "This mobile number already exists.", array("userinfo" => ""));
        } else {
            $iname = '';
            if (isset($_FILES['image'])) {
                $image = $this->common->do_upload('image', './assets/userfile/profile/');
                if (isset($image['upload_data'])) {
                    $iname = $image['upload_data']['file_name'];
                }
            }
            $_POST['image'] = $iname;
            $_POST['password'] = md5($_POST['password']);
            $_POST['created_at'] = date('Y-m-d H:i:s');

            $old_device = $old_ios = false;
            if (isset($_POST['android_token'])) {
                $old_device = $this->common->getData('user', array('android_token' => $_POST['android_token']), array('single', 'field' => 'id'));
            }
            if (isset($_POST['ios_token'])) {
                $old_ios = $this->common->getData('user', array('ios_token' => $_POST['ios_token']), array('single', 'field' => 'id'));
            }
            if ($old_device || $old_ios) {
                $this->common->updateData('user', array('android_token' => "", "ios_token" => ""), array('id' => $old_device['id']));
            }
            $post = $this->common->getField('user', $_POST);

            $result = $this->common->insertData('user', $post);
            if ($result) {
                $userid = $this->db->insert_id();
                $info = $this->common->getData('user', array('id' => $userid), array('single'));
                if ($_POST['email'] != "") {
                    $template = $this->load->view('template/verify-email', array('email' => $_POST['email'], 'otp' => $_POST['otp'], 'name' => $_POST['name']), true);
                    $this->common->sendMail($_POST['email'], "Verify Email", $template);
                }

                $this->response(true, "Your registration successfully completed.", array("userinfo" => $info));
            } else {
                $this->response(false, "There is a problem, please try again.", array("userinfo" => ""));
            }
        }
    }

    public function social_login() {
        $user = $this->common->getData('user', array('email' => $_POST['email']), array('single'));
        $url = $this->input->post('image');
        $uimg = "";
        if ($url != "") {
            $uimg = rand() . time() . '.png';
            file_put_contents('assets/userfile/profile/' . $uimg, file_get_contents($url));
        }
        if ($user) {

            $old_device = $this->common->getData('user', array('android_token' => $_POST['android_token']), array('single', 'field' => 'id'));
            if ($old_device) {
                $this->common->updateData('user', array('android_token' => "", "ios_token" => ""), array('id' => $old_device['id']));
            }
            $update = $this->common->updateData('user', array('image' => $uimg, 'ios_token' => $_POST['ios_token'], 'android_token' => $_POST['android_token']), array('id' => $user['id']));
            if ($update) {
                if ($user['image'] != "" && file_exists('assets/userfile/profile/' . $user['image'])) {
                    unlink('assets/userfile/profile/' . $user['image']);
                }
                $user['image'] = $uimg;
                $this->response(true, "Login Successfully.", array("userinfo" => $user));
            } else {
                $this->response(false, "There is a problem, please try again.", array("userinfo" => ""));
            }
        } else {
            $insert = $this->common->insertData('user', array('email' => $_POST['email'], 'image' => $uimg, 'name' => $_POST['name'], 'ios_token' => $_POST['ios_token'], 'android_token' => $_POST['android_token'], 'created_at' => Date('Y-m-d H:i:s')));
            $uid = $this->db->insert_id();
            if ($insert) {
                $user = $this->common->getData('user', array('id' => $uid), array('single'));
                $this->response(true, "Your Registration Successfully Completed.", array("userinfo" => $user));
            } else {
                $this->response(false, "There is a problem, please try again.", array("userinfo" => ""));
            }
        }
    }

    public function report() {
        $_POST['created_at'] = date('Y-m-d H:i:s');
        $post = $this->common->getData('post', array('id' => $_POST['post_id']), array('single'));
        $user = $this->common->getData('user', array('id' => $post['uid']), array('single', 'field' => 'email,name'));
        $post1 = $this->common->getField('report', $_POST);
        $report = $this->common->insertData('report', $post1);
        $mail = false;
        if ($report) {
            //$this->checkMail();
            $message = "Hello Administrator <br> One post <a href='" . base_url('api/postDetail/' . $post['id']) . "'>" . $post['title'] . "</a> is reported. We will delete your post if found inappropriate. <br>" . $_POST['comment'];

            $mail = $this->common->sendMail("info@positiy.au", 'Report on your post', $message);
        }
        $response = $this->response($mail, "Reported Successfully");
    }
    
    function forgetPassword() {       
        
        $parameter = ['email'];
        $post = $this->requireParameter($parameter,$_POST);
        $user = $this->common->getData('users',array('email'=>$_POST['email']),array('single'));
        if($user && $user['status'] == 0){
            $token = $this->generateToken();
            $this->common->updateData('users',array('token'=>$token),$post);
            $url = base_url('attendee/resetPassword/'.$token);
            $mail = $this->common->sendMail($user['email'],'Reset Password',"Hello ".$user['fname']." Please click on the link to reset your password <a href=".$url.">Click Here</a>");
            if($mail){
                $this->response(true, apiMsg('email_password_reset'));
            }else{
                $this->response(false, apiMsg('error'));
            }
        }elseif ($user && $user['status'] == 1) {
            $this->response(false, apiMsg('user_blocked'));
        }
        else{
            $this->response(false, apiMsg('auth_error'));
        }
    }
    
    public function resetPassword($token)
    {
        $this->form_validation->set_rules('password','Password','required');
        $this->form_validation->set_rules('confPassword','Confirm Password','required');
        if($this->form_validation->run() == false){
            $this->frontHtml('Reset Password','reset-password');
        }
        
        $user = $this->common->getData('users',array('token' => $token),array('single'));
        if($user){
            $this->common->updateData('users',array('token'=> $_POST['password']),array('otp'=>$_POST['otp'],'mobile' => $_POST['mobile']));	

            $message = "Password reset successfully";            
            
        }else{
            $res = false;
            $message = "Wrong otp entered";
        }		  

        $this->response($res,$message);
    }
    
    function eventList() {        
        $parameter = ['Token'];
        $post = $this->requireParameter($parameter,$_POST);
        $event_ids = $this->common->pluck('event_moderator',array('moderator_id'=>$this->user_id),'event_id');
        if(!empty($event_ids)){
            $event = $this->common->getData('events',array('status'=>0),array('where_in'=>$event_ids,'colname'=>'id','api'));
        }else{
            $event = 'NA';
        }
        if(is_array($event)){
            foreach ($event as $key => $value) {
                $event[$key]['event_type'] = "upcoming";                
                $event[$key]['venue'] = $this->common->getData('event_venue',array('event_id'=>$value['id']),array('single','api'));
                $organiser = $this->common->multiJoinTable('event_organizers as EO',array(['join_table'=>'EO','join_field'=>'organiser_id','other_table'=>'organisers as O','other_field'=>'id']),array('where'=> ['EO.event_id'=>$value['id']],'field'=>'O.fname,O.lname'));
                if(!empty($organiser)){
                    $event_organiser = [];
                    foreach($organiser as $key1 => $value1){
                        $event_organiser[] = $value1['fname'].' '.$value1['lname'];
                    }
                    $event[$key]['organiser'] = implode(',', $event_organiser);
                }else{
                    $event[$key]['organiser'] = "";
                }
            }
        }
        $this->response(true, "Event fetched Successfully",array('response'=>$event));
    }
    
    function eventDetail() {
        $parameter = ['event_id','Token'];
        $post = $this->requireParameter($parameter,$_POST);
        
        $event_id = $post['event_id'];
        $event = [];
        $data = $this->common->getData('events',array('id'=>$event_id),array('single'));
        $event['event'] = $data;
        $organiser = $this->common->multiJoinTable('event_organizers as EO',array(['join_table'=>'EO','join_field'=>'organiser_id','other_table'=>'organisers as O','other_field'=>'id']),array('where'=> ['EO.event_id'=>$event_id],'field'=>'O.*'));
        if(!empty($organiser)){
            foreach($organiser as $key1 => $value1){
                $event_organiser[] = $value1['fname'].' '.$value1['lname'];
            }
            $event['event']['organiser'] = implode(',', $event_organiser);
            $event['organiser'] = $organiser;
        }else{
            $event['event']['organiser'] = "";
            $event['organiser'] = "";
        }        
        
        $event['venue'] = $this->common->getData('event_venue',array('event_id'=>$event_id),array('api','single'));
        $event['event']['venue'] = $this->common->getData('event_venue',array('event_id'=>$event_id),array('api','single','field'=>'address'));
               
        $moderator_ids = $this->common->pluck('event_moderator',array('event_id'=>$event_id),'moderator_id');
        $event['moderator'] = $this->common->getData('users',array('type'=>2,'status'=>0),array('where_in'=>$moderator_ids,'colname'=>'id','api'));
        
        $attendee_feeds = $this->common->multiJoinTable('attendee_feeds as AF',
                array(['join_table'=>'AF','join_field'=>'user_id','other_table'=> 'users as U','other_field'=>'id','join_type'=>'left']),
                array('where'=>['AF.event_id'=>$event_id,'AF.status'=>1],'field'=>'AF.*,U.fname,U.lname'));
        // status 1 for approved and 0 for disaapprove
        
        $event['attendee_feeds'] = $attendee_feeds?:'NA';       
        
        $sponsor = $this->common->getData('event_sponsor',array('event_id'=>$event_id));
        if(!empty($sponsor)){
            foreach ($sponsor as $key2 => $value2) {
                $event['sponsors'][] = $this->common->getData('sponsors',array('id'=>$value2['sponsor_id']));
            }
        }else{
            $event['sponsors'] = 'NA';
        }
        
        $partner = $this->common->getData('event_partner',array('event_id'=>$event_id));
        if(!empty($partner)){
            foreach ($partner as $key3 => $value3) {
                $event['partners'][] = $this->common->getData('partners',array('id'=>$value3['partner_id']));
            }
        }else{
            $event['partners'] = 'NA';
        }
        
        $event['speaker'] = $this->common->multiJoinTable('event_speaker as ES',array(['join_table' => 'ES','join_field'=>'speaker_id','other_table'=>'speakers as S','other_field'=>'id']),['where'=>['ES.event_id'=>$event_id],'field'=>'S.*']);
        
        $event['session'] = $this->common->getData('sessions',array('event_id'=>$event_id));
        if($event['session']){
            foreach ($event['session'] as $key5 => $value5) {
               $spker_name = [];
               $spkr = $this->common->multiJoinTable('session_speakers as SS',array(['join_table' => 'SS','join_field'=>'speaker_id','other_table'=>'speakers as S','other_field'=>'id']),['where'=>['SS.session_id'=> $value5['id']],'field'=>'S.fname,S.lname']);
               if (!empty($spkr)) {
                   foreach($spkr as $key4 => $value4){
                       $spker_name[] = $value4['fname'].' '.$value4['lname'];
                   }
                   $event['session'][$key5]['speaker'] = implode(',', $spker_name);
               }
            }
        }
        
        $agenda = $this->common->getData('agenda',array('event_id'=>$event_id));
        if(!empty($agenda)){
            $event['agenda'] = $agenda;
            foreach ($agenda as $key4 => $value4) {
                $session = $this->common->getData('sessions',array('event_id'=>$event_id,'status'=>0,'agenda_id'=>$value4['id']),array('api','order_by'=>'start_time','sort_direction'=>'desc'));
                
                $event['agenda'][$key4]['sessions'] = $session;
                if($session != 'NA'){
                    foreach ($session as $key5 => $value5) {
                        $session_speaker = $this->common->multiJoinTable('session_speakers as SS',array(['join_table'=>'SS','join_field'=>'speaker_id','other_table'=>'speakers as SP','other_field'=>'id','join_type'=>'left']),array('where'=>['session_id'=>$value5['id'],'event_id'=>$event_id],'field'=>'SP.*'));
                        $session_spkr_name = [];
                        if(!empty($session_speaker)){
                            $event['agenda'][$key4]['sessions'][$key5]['speaker'] = $session_speaker;
                            foreach ($session_speaker as $key => $value) {
                                $session_spkr_name[] = $value['fname'].' '.$value['lname'];
                            }
                        }else{
                            $event['agenda'][$key4]['sessions'][$key5]['speaker'] = 'NA';
                        }
                        $event['agenda'][$key4]['sessions'][$key5]['speaker_name'] = implode(',', $session_spkr_name);
                    }
                }
            }
            
        }else{
            $event['agenda'] = $agenda ?:'NA';            
        }
        $event['gallery'] = $this->common->getData('gallery',array('event_id'=> $post['event_id']),array('api'));
        $event['activity'] = $this->common->getData('conference_activities',array('event_id'=> $event_id),array('api'));
        $today = date('Y-m-d');  $curr_time = date('g:i A');
        
        //$survey_cond = [];
        
        if($event['event']['start_date'] == $today && $event['event']['start_time'] < $curr_time || $event['event']['end_date'] == $today && $event['event']['end_time'] > $curr_time  ){     
            $survey_cond = array('event_id'=>$event_id,'survey_type' => 2,'validity >='=>$today);
        }
        
        if($event['event']['start_date'] > $today){     
            $survey_cond = array('event_id'=>$event_id,'survey_type' => 1,'validity >='=>$today);
        }
        
        if($event['event']['start_date'] <= $today  && $event['event']['end_date'] >= $today  ){  
            $survey_cond = array('event_id'=>$event_id,'survey_type' => 2,'validity >='=>$today);
        }
        
        if($event['event']['end_date'] < $today){      
            $survey_cond = array('event_id'=>$event_id,'survey_type' => 3,'validity >='=>$today);
        }
       
        $event['event_survey'] = $this->common->getData('surveys',$survey_cond);
        
        if(!empty($event['event_survey'])){
            foreach ($event['event_survey'] as $key7 => $value7) {
                $answer = $this->common->getData('survey_feedback',array('user_id'=>$this->user_id,'survey_id'=>$value7['id']),array('single'));
                if($answer){
                    $event['event_survey'][$key7]['answer'] = $answer['answer'];
                }else{
                    $event['event_survey'][$key7]['answer'] = 'NA';
                }
            }
        }
        
        $event['session_survey'] = $this->common->getData('surveys',array('event_id'=>$event_id,'survey_type' => 0));
        
        if(!empty($event['session_survey'])){
            foreach ($event['session_survey'] as $key8 => $value8) {
                $answer = $this->common->getData('survey_feedback',array('user_id'=>$this->user_id,'survey_id'=>$value8['id']),array('single'));
                if($answer){
                    $event['session_survey'][$key8]['answer'] = $answer['answer'];
                }else{
                    $event['session_survey'][$key8]['answer'] = 'NA';
                }
            }
        }
        
        $event['hashtag'] = $this->common->getData('social_hashtag',array('event_id'=>$event_id),array('api'));
       
        $event['chairman_msg'] = $this->common->getData('messages',array('event_id'=>$event_id),array('single','api'));
        
        $not_ids = $this->common->pluck('notification_user',array('user_id'=>  $this->user_id),'notification_id');                
        $event['notification'] = $this->common->getData('notifications',arraY('status'=>0,'event_id'=>$event_id),array('where_in'=>$not_ids,'colname'=>'id','api'));
               
        $this->response(true,  apiMsg('event_fetch_success'),array('response'=>$event));
        
    }
    
    function addFeeds() {
        $param = ['Token'];
        $this->requireParameter($param,$_POST);        
        $parameter = ['Token','title','event_id'];
        $post = $this->requireParameter($parameter,$_POST);
        $post['user_id'] = $this->user_id;  
        $post['image'] = "";
        
        if (isset($_FILES['image'])) {
            $image = $this->common->do_upload('image', 'assets/uploads/feeds');
            if (isset($image['upload_data'])) {
                $post['image'] = 'assets/uploads/feeds/'.$image['upload_data']['file_name'];
            }
        }
        $post2 = $this->common->getField('attendee_feeds',$post);
        $result = $this->common->insertData('attendee_feeds',$post2);
        
        if($result){
            $message = apiMsg('feed_success');                
        }else{            
            $message = apiMsg('error');
        }		  
        $this->response($result,$message);
        
    }
    
    function getAllContent() {
        $terms = $this->common->getData('pages',array('name'=>'terms'),array('single','api','field'=>'description'));
        $data['terms'] = isset($terms['description'])? $terms['description']:"NA";
        $about = $this->common->getData('pages',array('name'=>'about'),array('single','api','field'=>'description'));        
        $data['about'] = isset($about['description'])? $about['description']:"NA";
        $privacy = $this->common->getData('pages',array('name'=>'privacy'),array('single','api','field'=>'description'));
        $data['privacy'] = isset($privacy['description'])? $privacy['description'] :"NA";
        $data['rate_url'] = [
                    'android' => 'https://play.google.com/store/apps/details?id=com.nliven.trackvise&hl=en&hl=en',
                    'ios' => 'https://play.google.com/store/apps/details?id=com.nliven.trackvise&hl=en&hl=en'
                ];
        $data['share_app'] = [
                    'android' => 'https://play.google.com/store/apps/details?id=com.nliven.trackvise&hl=en&hl=en',
                    'ios' => 'https://play.google.com/store/apps/details?id=com.nliven.trackvise&hl=en&hl=en',
                    'title' =>'Event App',
                    'description' => 'Details'
                ];
        $data['chairman_msg'] = $this->common->getData('messages',array('event_id'=> 0),array('single','api'));
        
        $this->response(true,'data fetched successfully',array('response'=>$data));
    }
    
    function survey(){ 
        $param = ['Token'];
        $this->requireParameter($param,$_POST);
        $parameter = ['event_id'];
        $post = $this->requireParameter($parameter,$_POST);
        $survey = $this->common->getData('surveys',$post,array('api'));
        $this->response(true,'data fetched successfully',array('response'=>$survey));
    }
    
    function sessionList() {
        $param = ['Token'];
        $this->requireParameter($param,$_POST);
        $parameter = ['event_id'];
        $post = $this->requireParameter($parameter,$_POST);
        $session = $this->common->getData('surveys',array('event_id'=> $post['event_id'],'session_id > '=> 0),array('api'));
        $this->response(true,'data fetched successfully',array('response'=>$session));
    }
    
    function surveyFeedback() {
//        $_POST = [
//            'Token' => 'erfesr',
//            'event_id'=> 3,
//            'Authorization' => 'dfs#!df154$',
//            'Is-Update' => 1,
//            'user_id' => 3,
//            'question'=> [1,2,4,6],
//            'answer' => [2,3,4,0],
//            'answer_type' => [1,3,3,2]
//        ];
//        
        $param = ['Token'];
        $this->requireParameter($param,$_POST);
        
        $result = false;
        if(!empty($_POST['question'])){
            foreach ($_POST['question'] as $key => $value) {            
                $feedback = [
                                'survey_id'=>$value,
                                'user_id' => $this->user_id,
                                'answer_type' => $_POST['answer_type'][$key],
                                'answer' => $_POST['answer'][$key]
                            ];
                $result = $this->common->insertData('survey_feedback',$feedback);
            }
        }
        
        if($result){
            $message = apiMsg('feed_success');                
        }else{            
            $message = apiMsg('error');
        }		  
        $this->response($result,$message);         
    }
    
    function updateSurveyFeedback() {
//        $_POST = [
//            'Token' => 'erfesr',
//            'event_id'=> 3,
//            'Authorization' => 'dfs#!df154$',
//            'Is-Update' => 1,
//            'user_id' => 3,
//            'question'=> [1,2,4,6],
//            'answer' => [2,3,4,0],
//            'answer_type' => [1,3,3,2]
//        ];
//        
        $param = ['Token'];
        $this->requireParameter($param,$_POST);
        
        $result = false;
        if(!empty($_POST['question'])){
            foreach ($_POST['question'] as $key => $value) {            
                $feedback = [
                                'survey_id'=>$value,
                                'user_id' => $this->user_id,
                                'answer_type' => $_POST['answer_type'][$key],
                                'answer' => $_POST['answer'][$key]
                            ];
                $result = $this->common->updateData('survey_feedback',$feedback,array('survey_id'=>$value,'user_id' => $this->user_id,'answer_type' => $_POST['answer_type'][$key]));
            }
        }
        
        if($result){
            $message = apiMsg('feed_success');                
        }else{            
            $message = apiMsg('error');
        }		  
        $this->response($result,$message);         
    }
    
    function updateProfile() {
        $param = ['Token'];
        $this->requireParameter($param,$_POST);
        $post = $this->common->getField('users',$_POST);
        $result = $this->common->updateData('users',$post,array('id'=>$this->user_id));
        if($result){
            $message = apiMsg('profile_update_success');                
        }else{            
            $message = apiMsg('error');
        }
        
        $user = $this->common->getData('users',array('id'=>$this->user_id),array('single'));
        $this->response($result,$message,array('response'=>$user));         
    }
    
    function updateProfileImage() {
        $param = ['Token'];
        $this->requireParameter($param,$_POST);
        $userimage = $this->common->getData('users',array('id'=>$this->user_id),array('single','field'=>'image'));
         if (!empty($_FILES)) {
            $image = $this->common->do_upload('image', 'assets/uploads/users');
            if (!empty($image['upload_data'])) {
                $_POST['image'] = 'assets/uploads/users/' . $image['upload_data']['file_name'];
                if(file_exists($userimage['image'])){
                    unlink($userimage['image']);
                }
            }                      
        }else{
            $_POST['image'] = $userimage['image'];
        }
        $post = $this->common->getField('users',$_POST);
        $result = $this->common->updateData('users',$post,array('id'=>$this->user_id));
        if($result){
            $message = apiMsg('profile_update_success');                
        }else{            
            $message = apiMsg('error');
        }
        
        $user = $this->common->getData('users',array('id'=>$this->user_id),array('single'));
        $this->response($result,$message,array('response'=>$user));    
    }
    
    function gallery() {
        $param = ['Token','event_id'];
        $post = $this->requireParameter($param,$_POST);
        $result = false;
        $gallery = $this->common->getData('gallery',array('event_id'=> $post['event_id']),array('api'));
        if($gallery != 'NA'){ $result = true; }
        if($result){
            $message = apiMsg('gallery_success');                
        }else{            
            $message = apiMsg('no_gallery');
        }
        $this->response($result,$message,array('response'=>$gallery));  
    }
    
    function hashtag() {
        $param = ['Token'];
        $post = $this->requireParameter($param,$_POST);
        $result = false;
        $hashtag = $this->common->getData('social_hashtag',array(),array('api'));
        if($hashtag != 'NA'){ $result = true; }
        if($result){
            $message = apiMsg('hashtag_success');                
        }else{            
            $message = apiMsg('no_hashtag');
        }
        $this->response($result,$message,array('response'=>$hashtag));  
    }
    
    function notificationList() {
        $not_ids = $this->common->pluck('notification_user',array('user_id'=>$this->user_id),'notification_id');
        if(!empty($not_ids)){
            $notification = $this->common->getData('notifications',arraY('status'=>0),array('where_in'=>$not_ids,'colname'=>'id','api'));
        }else{
            $notification = 'NA';
        }
        $result = false;
        if($notification != 'NA'){ $result = true; }
        if($result){
            $message = apiMsg('notification_success');                
        }else{            
            $message = apiMsg('no_notification');
        }
        $this->response($result,$message,array('response'=>$notification)); 
    }
    
    
    

}
