<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Api2 extends Api_Controller {

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
   
    public function signup() {
        $existEmail = $existMobile = false;
        $parameter = ['email','password','country_id','platform'];
        //print_r($_POST); die;
        $this->requireParameter($parameter,$_POST);
        
        if ($_POST['email'] != "") {
            $existEmail = $this->common->getData('users', array('email' => $_POST['email']), array('single'));
        }
        $existMobile = $this->common->getData('users', array('mobile' => $_POST['mobile']), array('single'));
        
        if ($existEmail) {
            $this->response(false,apiMsg("email_exist"));
        } elseif ($existMobile) {
            $this->response(false, apiMsg("mobile_exist"));
        } else {
            
            $_POST['type'] = 2;           
            $_POST['password'] = md5($_POST['password']);
            $_POST['token'] = generateToken();
            $country = $this->common->getData('countries',array('country_code' => $_POST['country_id']),array('single','field'=>'id'));

            $_POST['country_id'] = $country['id']; 

            $old_device = false;
            if (isset($_POST['device_token'])) {
                $old_device = $this->common->getData('users', array('device_token' => $_POST['device_token']), array('single', 'field' => 'id'));
            }
           
            if ($old_device) {
                $this->common->updateData('users', array('device_token' => "", "platform" => ""), array('id' => $old_device['id']));
            }
            $post = $this->common->getField('users', $_POST);
            $result = $this->common->insertData('users', $post);
            if ($result) {
                $userid = $this->db->insert_id();
                $info = $this->common->getData('users', array('id' => $userid), array('single'));               
                $this->response(true,apiMsg('registration_succ'), array("response" => $info));
            } else {
                $this->response(false,apiMsg('error'));
            }
        }
    }

    public function login() {  
        
        $parameter = ['email','password'];
        $post = $this->requireParameter($parameter,$_POST);
        $platform = ['platform','device_token'];
        $post3 = $this->requireParameter($platform,$_POST);
        $post['password'] = md5($post['password']); 
        $result = $this->common->getData('users',$post, array('single'));
        $token = generateToken();
        if ($result && $result['status'] == 0) {
            $old_device = false;
            if(!empty($_POST['device_token']) && $_POST['device_token'] != null && $_POST['device_token'] != 'undefined'){
                $old_device = $this->common->getData('users', array('device_token' => $_POST['device_token']), array('single', 'field' => 'id'));
            
                if (!empty($old_device)) {
                    $this->common->updateData('users', array('device_token' => "",'platform' =>""), array('id' => $old_device['id']));
                }
                $this->common->updateData('users', array('device_token' => $_POST['device_token'],'platform'=>$_POST['platform'],'last_login'=>date('Y-m-d H:i:s')), array('id' => $result['id']));
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

    public function loginWithPhone()
    {   
        $parameter = ['mobile'];
        $post = $this->requireParameter($parameter,$_POST);
        $user = $this->common->getData('users',array('mobile'=>$post['mobile']),array('single','field'=>'email,mobile,id,country_id'));
        if ($user) {
            $otp = $this->generateOTP();
            $this->common->updateData('users',array('otp'=>$otp),array('id'=>$user['id']));
            $country = $this->common->getData('countries',array('id'=>$user['country_id']),array('single','field'=>'phonecode'));
            $user['phonecode'] = $country['phonecode'];
            $user['otp'] = $otp;
            // $template = $this->common->getData('contents',array('id'=>1),array('single'));
            // if (strpos($template['description'], 'USER') !== false) {
            //     $template['description'] = str_replace('USER', $user['name'], $template['description']);
            // }

            // if (strpos($template['description'], 'OTP') !== false) {
            //     $template['description'] = str_replace('OTP', $otp, $template['description']);
            // }

            // $template = $template['description'];

            // $email_temp = $this->load->view('template/template',compact('template'),true);
            // $this->common->sendMail($user['email'],"Forget Password",$email_temp);

            // send otp
            $this->response(true,apiMsg('otp_sent_to_mobile'),array('response'=>$user));
        } else {
            $this->response(false,apiMsg('wrong_mobile'));
        }
    }

    public function matchLoginOtp()
    {
        $parameter = ['mobile','otp'];
        $post = $this->requireParameter($parameter,$_POST);
        $platform = ['platform','device_token'];
        $this->requireParameter($platform,$_POST);
        $user = $this->common->getData('users',$post,array('single'));
        if($user){
            $this->common->updateData('users',array('otp'=>'','last_login'=>date('Y-m-d H:i:s'),'phone_verify'=>1),array('id'=>$user['id']));
            $this->response(true,apiMsg('user_fetched'),array('response'=>$user));
        }else{
            $this->response(false,apiMsg('wront_otp'));
        }

    }

    public function social_login()
    {      
        $parameter = ['email','image','country_id'];
        $post = $this->requireParameter($parameter,$_POST);
        
        // $path = $_SERVER['DOCUMENT_ROOT'].'/dev/upm_app/test.txt';
        // echo $path;
        // $file = fopen($path,"w");
        // echo fwrite($file,implode(', ----', $_POST));
        // fclose($file);
        // dd($_POST);

        $user = $this->common->getData('users',array('email' => $_POST['email']),array('single'));
        $url = $this->input->post('image');
        $uimg = "";
        if($url != ""){
            $uimg = 'assets/uploads/users/'.rand().time().'.png';
            file_put_contents($uimg, file_get_contents($url));
        }
        $country = $this->common->getData('countries',array('country_code' => $post['country_id']),array('single','field'=>'id'));
        $_POST['country_id'] = $country['id'];

        if($user){ 

            $old_device = $this->common->getData('users',array('device_token' => $_POST['device_token']),array('single','field'=>'id'));
            if($old_device){
                $this->common->updateData('users',array('device_token' => "", "platform" => ""),array('id' => $old_device['id']));
            }
            $update = $this->common->updateData('users',array('image' => $uimg, 'device_token' =>$_POST['device_token'], 'platform' => $_POST['platform'],'last_login'=>date('Y-m-d H:i:s')),array('id' => $user['id']));
            if($update){                
                if($user['image'] != "" && file_exists($user['image'])){
                    unlink($user['image']);
                }
                $user['image'] = $uimg;
                $this->response(true,"Login Successfully.",array("response" => $user));
            }else{
                $this->response(false,apiMsg('error'));
            }
        }else{    
            $post1 = $this->common->getField('users',$_POST);
            $post1['image'] = $uimg;
            $post1['fb_verify'] = 1;
            $insert = $this->common->insertData('users',$post1);
            $uid  = $this->db->insert_id();
            if($insert){
                $user = $this->common->getData('users',array('id'=> $uid),array('single'));
                $this->response(true,apiMsg('registration_succ'),array("response" => $user));
            }else {
                $this->response(false,apiMsg('error'));
            }
        }
    }
    
    function forgetPassword() {       
        
        $parameter = ['param'];
        $post = $this->requireParameter($parameter,$_POST); 
        $where = 'email = "'.$post['param'].'" or mobile = "'.$post['param'].'"';
        
        $user = $this->common->getData('users',$where,array('single','field'=>'name,email,mobile,status,id,country_id'));
       
        if($user && $user['status'] == 0){
            $country = $this->common->getData('countries',array('id'=>$user['country_id']),array('single','field'=>'phonecode'));
            $user['phonecode'] = $country['phonecode'];

            $otp = $this->generateOTP();
            $this->common->updateData('users',array('otp'=>$otp),array('id'=>$user['id']));
            $user['otp'] = $otp;
            
            $template = $this->common->getData('contents',array('id'=>4),array('single'));
            if (strpos($template['description'], 'USER') !== false) {
                $template['description'] = str_replace('USER', $user['name'], $template['description']);
            }

            if (strpos($template['description'], 'OTP') !== false) {
                $template['description'] = str_replace('OTP', $otp, $template['description']);
            }

            $template = $template['description'];

            $email_temp = $this->load->view('template/template',compact('template'),true);

            $this->common->sendMail($user['email'],"Forgot Password",$email_temp);

            // send mobile otp
            if($email_temp){
                $this->response(true, apiMsg('otp_sent_to_mobile_email'),array('response'=>$user));

            }else{
                $this->response(false, apiMsg('error'));
            }
        }elseif ($user && $user['status'] == 1) {
            $this->response(false, apiMsg('user_blocked'));
        }
        else{
            $this->response(false, apiMsg('wrong_email_mobile'));
        }
    }

    public function matchOTP()
    {
        $parameter = ['mobile','otp'];
        $post = $this->requireParameter($parameter,$_POST);
        $user = $this->common->getData('users',$post,array('single','field'=>'email,mobile'));
        if ($user) {
            $this->response(true,apiMsg('user_fetched'),$user);
        } else {
            $this->response(false,apiMsg('wront_otp'));
        }        
    }

    public function resetPassword()
    {
        $parameter = ['password','email','mobile'];
        $post = $this->requireParameter($parameter,$_POST);
        $result = $this->common->updateData('users',array('password'=> md5($post['password']),'otp'=>''),array('email'=>$post['email'],'mobile'=>$post['mobile']));
        if ($result) {
            $this->response(true,apiMsg('password_change_success'));
        } else {
            $this->response(false,apiMsg('error'));
        }
    }
    
    public function changePassword()
    {        
        $parameter = ['Token','Is-Update','password','old_password'];
        $post = $this->requireParameter($parameter,$_POST);
        $user = $this->common->getData('users',array('id' => $this->user_id),array('single'));
        if($user['password'] != md5($_POST['old_password'])){
            $this->response(false, apiMsg('old_pass_not_match'));
        }
        else{
            $res = $this->common->updateData('users',array('password'=> md5($_POST['password'])),array('id' => $this->user_id));
            $user = $this->common->getData('users',array('id'=> $this->user_id),array('single','field'=>'fname,lname,email'));
            $template = $this->common->getData('contents',array('id'=>3),array('single'));
            if (strpos($template['description'], 'USER') !== false) {
                $template = str_replace('USER', $user['fname'], $template['description']);
            }else{
                $template = $template['description'];
            }

            $email_temp = $this->load->view('template/template',compact('template'),true);
            
            $this->common->sendMail($user['email'],"Password Change",$email_temp);

            $this->response($res, apiMsg('password_change_success'));
        }
    }

    public function home()
    {
        $parameter = ['Token','latitude','longitude'];
        $post = $this->requireParameter($parameter,$_POST);
        $filter = ['category','sort_by','limit','offset','search','city','latitude','longitude','state','country_code','country_id'];
        $country = $this->common->getData('countries',array('country_code'=>$_POST['country_code']),array('single'));
        $_POST['country_id'] = $country['id'];
        $options = $this->requireParameter($filter,$_POST);
        $user = $this->common->getData('users',array('id'=> $this->user_id),array('single'));
        //$distance =  $this->config->item('distance');
        $post['sold'] = 0;
        $machines = $this->common->getMachines($post,$options);
        
        if($machines){
            foreach ($machines as $key => $value) {
                $machines[$key]['image'] = $this->common->oneToManay('machines','machine_images',['where1' => array('id'=>$value['id']),'where2'=> array('machine_id'=>$value['id']),'field_match'=>'image','field1'=>'id','field2'=>'id,image']);
            }
        }else{
           $machines = 'NA';
        }

        $coupon = $this->common->getData('coupons',array('plan_id'=> 0,'status' => 0,'start_date <= '=> date('Y-m-d'),'expiry_date >= '=> date('Y-m-d')),array('single','api'));

        $this->response(true, apiMsg('machine_fetched'),array('response'=>$machines,'coupon'=>$coupon)); 
    }

    public function applyCoupon()
    {
        $parameter = ['Token','Is-Update','coupon_code','plan_id'];
        $post = $this->requireParameter($parameter,$_POST);
        $plan = $this->common->getData('plans',array('id'=>$post['plan_id']),array('single','field'=>'cost'));
        $coupon = $this->common->getData('coupons',array('coupon_code'=>$post['coupon_code'],'status'=>0,'is_delete'=>0),array('single'));

        if($coupon && ($post['plan_id'] == $coupon['plan_id'] || $coupon['plan_id'] == 0)){
                if ($coupon['type'] == 0) {
                    $discount = $plan['cost']*($coupon['value']/100);
                }else{
                    $discount = $coupon['value'];
                }
                $price = $plan['cost'] - $discount;

                $this->response(true,apiMsg('coupon_applied'),array('response'=>['price'=>$price,'discount'=>$discount]));
            
        }else{
            $this->response(false,apiMsg('wrong_coupon'));
        }

    }

    public function addMachine()
    {        
        $parameter = ['Token','title','price','machine_type','make','country_code','category_id','capacity','make_year','description','city','state','ownership','latitude','longitude','plan_id','show_phone','status','approx_cost','accessories','address_title','formatted_address','country_code']; //is_working
        $post = $this->requireParameter($parameter,$_POST);
        $token = $post['Token'];
        $post['user_id'] = $this->user_id;

        $country = $this->common->getData('countries',array('country_code' => $post['country_code']),array('single','field'=>'id'));
        if ($country) {
            $post['country_id'] = $country['id'];            
        }else{
            $post['country_id'] = 0;
        }
        unset($post['Token'],$post['country_code']);
        $plan = $this->common->getData('plans',array('id'=>$post['plan_id']),array('single','field'=>'name,cost'));
        if (isset($_POST['coupon_code'])) {
            $post['coupon_code'] = $_POST['coupon_code'];
            $coupon = $this->common->getData('coupons',array('coupon_code'=> $_POST['coupon_code']),array('single'));
            if ($coupon) {
                if ($coupon['type'] == 0) {
                    $discount = $plan['cost']*($coupon['value']/100);
                }elseif($coupon['type'] == 1){
                    $discount = $coupon['value'];
                }else{
                    $discount = 0;
                }
                    $price = $plan['cost'] - $discount;
            }else{
                $this->response(false,apiMsg('wrong_coupon')); exit();
            }
        }    
        
        $result = false;
        if($post['status'] == 1){
            $result = $this->common->insertData('machines',$post);
            $machine_id = $this->db->insert_id();
        }else{
            $result = $this->common->insertData('machines',$post);
            $machine_id = $this->db->insert_id();
            $post['machine_id'] = $this->db->insert_id();
            $post['payment'] = $price;
            $post['transaction_id'] = $_POST['transaction_id'];
            $getPay = $this->common->getField('payment_history',$post);
            $result1 = $this->common->insertData('payment_history',$getPay);
            
        }
        if ($result && !empty($_FILES)) {  
            
            $path = 'assets/uploads/machines/'.$machine_id; 
            if (!file_exists($path) && !is_dir($path)) {
                mkdir($path,0777,true);
            }          
            $image = $this->common->multi_upload('image', $path);
            
            if (!empty($image)) {
                foreach ($image as $key => $value) {
                    $ins_img = $this->common->insertData('machine_images',array('machine_id'=>$machine_id,'image'=>$path.'/'.$value['file_name']));
                    if($ins_img){
                        $post['image'][] = $path.'/'.$value['file_name'];
                    }
                }
            }   
            
        }else{
            $post['image'] = 'NA';
        }
        if($result){

            $msg = 'machine_add_succ';
            if ($post['status'] == 1) {
                $msg = 'machine_add_draft_succ';
            }
            $post['Token'] = $token;
            $this->response(true,apiMsg($msg),array('response'=>$post));
        }else{
            $this->response(false,apiMsg('error'));
        }
    }

    public function editMachine()
    {     
        $parameter = ['Token','title','price','machine_type','make','category_id','country_code','capacity','make_year','description','city','state','ownership','latitude','longitude','plan_id','show_phone','status','approx_cost','accessories','address_title','formatted_address']; // is_working

        $post = $this->requireParameter($parameter,$_POST);
        $token = $post['Token'];
        $country = $this->common->getData('countries',array('country_code' => $post['country_code']),array('single','field'=>'id'));
        $post['country_id'] = $country['id'];
        $post['approve'] = 0;
        unset($post['Token'],$post['country_code']);
        $machine = $this->common->getData('machines',array('id'=>$_POST['machine_id']),array('single','field'=>'status,id'));
        $result = $this->common->updateData('machines',$post,array('id'=>$_POST['machine_id'],'user_id'=>$this->user_id));

        if ($machine['status'] == 1 && $post['status'] == 0) {
            if (isset($_POST['coupon_code'])) {
                $plan = $this->common->getData('plans',array('id'=>$post['plan_id']),array('single','field'=>'name,cost'));
                $coupon = $this->common->getData('coupons',array('coupon_code'=> $_POST['coupon_code']),array('single'));
                if ($coupon) {
                    if ($coupon['type'] == 0) {
                        $discount = $plan['cost']*($coupon['value']/100);
                    }elseif($coupon['type'] == 1){
                        $discount = $coupon['value'];
                    }else{
                        $discount = 0;
                    }
                    $price = $plan['cost'] - $discount;
                }else{
                    $this->response(false,apiMsg('wrong_coupon')); exit();
                }
            }else{
                $price = $plan['cost'];
            }
            $post['payment'] = $price;
            $post['coupon_code'] = $_POST['coupon_code'];
            $post['transaction_id'] = $_POST['transaction_id'];
            $post['machine_id'] = $machine['id'];
            $post['user_id'] = $this->user_id;
            $getPay = $this->common->getField('payment_history',$post);
            $result1 = $this->common->insertData('payment_history',$getPay);            
        }

        if ($result && !empty($_FILES)) {  
            $machine_id = $_POST['machine_id'];
            $path = 'assets/uploads/machines/'.$machine_id; 
            if (!file_exists($path) && !is_dir($path)) {
                mkdir($path,0777,true);
            }          
            $image = $this->common->multi_upload('image', $path);
            
            if (!empty($image)) {
                foreach ($image as $key => $value) {
                    $ins_img = $this->common->insertData('machine_images',array('machine_id'=>$machine_id,'image'=>$path.'/'.$value['file_name']));
                    if($ins_img){
                        $post['image'][] = $path.'/'.$value['file_name'];
                    }
                }
            }else{
                $post['image'] = 'NA';
            }   
        }else{
            $post['image'] = 'NA';
        }
        if (!empty($_POST['delete_img'])) {
            foreach ($_POST['delete_img'] as $key1 => $value1) {
                $img = $this->common->getData('machine_images',array('id'=>$value1),array('single'));
                if($img && file_exists($img['image'])){
                    unlink($img['image']);
                    $this->common->deleteData('machine_images',array('id'=>$value1));
                }
            }
        }
        
        $post['Token'] = $token;
        if($result){
            $this->response(true,apiMsg('machine_update_succ'),array('response'=>$post));
        }else{
            $this->response(false,apiMsg('machine_update_succ'),array('response'=>$post));
        }
    }

    public function machineDetail()
    {
        $parameter = ['Token','machine_id'];
        $post = $this->requireParameter($parameter,$_POST);
        //$machine = $this->common->getData('machines',array('id'=>$post['machine_id']),array('single','field'=>'*,DATE_ADD(approve_date, INTERVAL P.validity DAY)) as valid'));

        $joins = [
                    [
                        'join_table' => 'M',
                        'join_field' => 'user_id',
                        'other_table'=> 'users as U',
                        'other_field'=> 'id'
                    ],
                    [
                        'join_table' => 'M',
                        'join_field' => 'plan_id',
                        'other_table'=> 'plans as P',
                        'other_field'=> 'id'
                    ]
                ];
        $options = [
                        'where' => array('M.id'=>$post['machine_id']),
                        'field' => 'M.*,U.name,P.name as plan,P.cost,P.validity,IF(approve_date != null,"Pending",DATE_ADD(approve_date, INTERVAL P.validity DAY)) as valid'
                    ];
                    //,IF(M.show_phone == 1,U.mobile,null)
        $machine = $this->common->multiJoinTable('machines as M',$joins,$options);

        if($machine){
            $machine = $machine[0];
            $machine['user'] = $this->common->getData('users',array('id'=>$machine['user_id']),array('single'));
            $machine['ownership'] = ownership($machine['ownership']);
            $machine['status'] = status($machine['status']);
            $machine['is_working'] = is_working($machine['is_working']);
            $images = $this->common->getData('machine_images',array('machine_id'=>$post['machine_id']),array('api','field'=>'id,image'));
            $plan = $this->common->getData('plans',array('id'=>$machine['plan_id']),array('single'));         
            $machine['expiry_date'] =  date('Y-m-d', strtotime('+'.$plan['validity'].' day', strtotime($machine['approve_date'])));
            $favourite = $this->common->getData('price_n_favourite',array('user_id'=>$this->user_id,'machine_id'=>$machine['id'],'favourite'=>1),array('single'));
            if ($favourite) {
                $machine['favourite'] = 1; // favourite
            }else{
                $machine['favourite'] = 0;
            }
            if ($machine['expiry_date'] < date('Y-m-d')) {
                $machine['expire'] = 1;  // expired
            }else{
                $machine['expire'] = 0;
            }

            if ($machine['status'] == 1 && $machine['approve'] == 0) {
                    $machine['machine_status'] = machine_status(0);
            }elseif ($machine['status'] == 0 && $machine['approve'] == 1 && $machine['valid'] >= date('Y-m-d')) {
                $machine['machine_status'] = machine_status(1);
            }elseif ($machine['status'] == 0 && $machine['approve'] == 2) {
                $machine['machine_status'] = machine_status(2);
            }elseif ($machine['status'] == 0 && $machine['approve'] == 1 && $machine['valid'] < date('Y-m-d')) {
                $machine['machine_status'] = machine_status(3);
            }else{
                $machine['machine_status'] = machine_status(4);
            }

            $report = $this->common->getData('report_ads',array('user_id'=>$this->user_id,'machine_id'=>$machine['id']),array('single'));
            if ($report) {
                $machine['report'] = 1;  // reported
            }else{
                $machine['report'] = 0;
            }
            $machine['images'] = $images;
            $this->response(true,apiMsg('machine_fetched'),array('response'=>$machine));
        }else{
            $this->response(true,apiMsg('no_data'),array('response'=>'NA'));
        }
    }

    public function machineEdit()
    {
        $parameter = ['Token','machine_id'];
        $post = $this->requireParameter($parameter,$_POST);
        $machine = $this->common->getData('machines',array('id'=>$post['machine_id']),array('single'));
        if($machine){
            $machine['user'] = $this->common->getData('users',array('id'=>$machine['user_id']),array('single'));
            $images = $this->common->getData('machine_images',array('machine_id'=>$post['machine_id']),array('api','field'=>'id,image'));
            
            $machine['images'] = $images;
            $country = $this->common->getData('countries',array());
            $this->response(true,apiMsg('machine_fetched'),array('response'=>$machine));
        }else{
            $this->response(true,apiMsg('no_data'),array('response'=>'NA'));
        }
    }

    public function getAllContent() {
        // $terms = $this->common->getData('pages',array('id'=>1),array('single','api','field'=>'description'));
        // $data['terms'] = isset($terms['description'])? $terms['description']:"NA";
        // $about = $this->common->getData('pages',array('id'=>2),array('single','api','field'=>'description'));        
        // $data['about'] = isset($about['description'])? $about['description']:"NA";
        // $privacy = $this->common->getData('pages',array('id'=>3),array('single','api','field'=>'description'));
        // $data['privacy'] = isset($privacy['description'])? $privacy['description'] :"NA";
        $data['faq'] = $this->common->getData('faq',array(),array('api'));
        $data['country'] = $this->common->getData('countries',array(),array('order_by'=>'country_name','order_direction'=>'asc'));
        $data['rate_url'] = [
                    'android' => 'https://play.google.com/store/apps',
                    'ios' => 'https://play.google.com/store/apps'
                ];
        $data['share_app'] = [
                    'android' => 'https://play.google.com/store/apps',
                    'ios' => 'https://play.google.com/store/apps',
                    'title' =>'Event App',
                    'description' => 'Details',
                    'logo' => 'assets/images/logo_1.png'
                ];
        $data['plan'] = $this->common->getData('plans',array('status'=>0,'is_delete'=>0),array('api','order_by'=>'validity','order_direction'=>'asc'));
        $data['reason'] = $this->common->getData('reject_reasons',array(),array('api'));
        $data['coupons'] = $this->common->getData('coupons',array('status'=>0,'is_delete'=>0),array('api'));
        if (!empty($data['plan'])) {
            foreach ($data['plan'] as $key => $value) {
                $data['plan'][$key]['plan_type'] = plan_type($value['plan_type']);
            }
        }
        $data['machine_type'] = $this->config->item('machine_type');
        $data['machine_status'] = $this->config->item('machine_status');
        $data['sort_by'] = $this->config->item('sort_by');
        $data['ownership'] = $this->config->item('ownership');
        $data['category'] = $this->common->getData('categories',array('status'=>0,'is_delete'=>0));
        $data['limit'] = 10;
        
        //$data['stripe_publishable_key'] = $this->config->item('stripe_publishable_key');
        
        $this->response(true,'data fetched successfully',array('response'=>$data));
    }
    
    public function myProfile()
    {       
        $param = ['Token','Is-Update'];
        $post = $this->requireParameter($param,$_POST);
        $result = $this->common->getData('users',array('id'=> $this->user_id),array('single'));
        if($result){
            $country = $this->common->arrayToName('countries','country_name',array($result['country_id']));
            $result['country_name'] = $country;
            $category_interest = $this->common->pluck('user_interest',array('user_id'=>$this->user_id),'category_id');
            $category = $this->common->arrayToName('categories','category',$category_interest);
            $result['category_interest_id'] = $category_interest;
            $result['category_interest'] = $category;
            $result['complitness'] = 70;
            $this->response(true,apiMsg('user_fetched'),array('response'=>$result));
        }else{
            $this->response(true,apiMsg('no_data'),array('response'=>'NA'));
        }    
    }
    
    function updateProfile() {
        $param = ['Token','category','category_id'];
        $this->requireParameter($param,$_POST);
        $post = $this->common->getField('users',$_POST);
        $post['category_id'] = $_POST['category'];
        $result = $this->common->updateData('users',$post,array('id'=>$this->user_id));
        if($result){
            if (!empty($_POST['category_id'])) {
                $this->common->deleteData('user_interest',array('user_id'=>$this->user_id));
                foreach ($_POST['category_id'] as $key => $value) {
                    $result = $this->common->insertData('user_interest',array('user_id'=>$this->user_id,'category_id'=>$value));
                }
            }
            $message = apiMsg('profile_update_success');                
        }else{            
            $message = apiMsg('error');
        }
        
        $user = $this->common->getData('users',array('id'=>$this->user_id),array('single'));
        $this->response($result,$message,array('response'=>$user));         
    }

    public function categoryInterest()
    {
        $param = ['Token','Is-Update','category_id'];
        $this->requireParameter($param,$_POST);
        $result = false;
        if (!empty($_POST['category_id'])) {
            $this->common->deleteData('user_interest',array('user_id'=>$this->user_id));
            foreach ($_POST['category_id'] as $key => $value) {
                $result = $this->common->insertData('user_interest',array('user_id'=>$this->user_id,'category_id'=>$value));
            }
        }
        if ($result) {
            $this->response($result,apiMsg('category_interest_added'));        
        }else{
            $this->response($result,apiMsg('error'));
        }

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
    
   
    function notificationList() {
        $not_ids = $this->common->pluck('notification_user',array('user_id'=>$this->user_id,'(push_status or mail_status) = '=>1),'notification_id');
        if(!empty($not_ids)){
            $notification = $this->common->getData('notifications',array('status'=>0,'msg_status'=>1),array('where_in'=>$not_ids,'colname'=>'id','api','order_by'=>'send_date'));
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


    public function addBank()
    {   
        require_once APPPATH."third_party/stripe/init.php";
        //set api key in above file
                
        \Stripe\Stripe::setApiKey($stripe['secret_key']);  
        
        try {

            $dob_year =   date('Y', strtotime($_REQUEST['dob']));
            $dob_month =   date('m', strtotime($_REQUEST['dob']));
            $dob_day =    date('d', strtotime($_REQUEST['dob']));
            
            $acct = \Stripe\Account::create(array(
                //"managed" => true,
                "type" => "custom",                        
                "external_account" => $_REQUEST['token'],
                "legal_entity[type]" => "individual",
                "legal_entity[first_name]" => $_REQUEST['first_name'],
                "legal_entity[last_name]" => $_REQUEST['last_name'],
                //"legal_entity[address][city]" => trim($this->input->post('city')),
                //"legal_entity[address][state]" => trim($this->input->post('state')),
                //"legal_entity[address][line1]" => trim($this->input->post('address_line1')),
                //"legal_entity[address][line2]" =>  $this->input->post('address_line2') ,
                //"legal_entity[address][postal_code]" => trim($this->input->post('post_code')),
                "legal_entity[dob][day]" => $dob_day,
                "legal_entity[dob][month]" => $dob_month,
                "legal_entity[dob][year]" => $dob_year,                         
                "tos_acceptance" => array(
                    "date" => time(),
                    "ip" => $_SERVER['REMOTE_ADDR']
                )
            ));         
                        
            $stripe_bank_token = array('user_id'=>$_REQUEST['user_id'],'stripe_account' => $acct->id,'dob'=> $_REQUEST['dob']);

            $result = $this->common->insertData('account_detail',$stripe_bank_token);
            if($result){
                $this->response(true,"Your account detail added successfully.");
            }else{
                $this->response(false,"Record not found.");
            }
              
        } catch (Exception $e) {                
            $errormsg = "Bank details wrong ". $e->getMessage();
            $this->session->set_flashdata('error_msg', $errormsg); 
            $this->response(false,$e->getMessage());      
        } 
    }

    public function registerForActivityOnline()
    {          
        $param = ['activity_id','payment_mode','token'];
        if($_POST['payment_mode']== 0){
            $_POST['payment_date'] = date('Y-m-d H:i:s');
        }
        $post = $this->requireParameter($param,$_POST);
        //include Stripe PHP library
        require_once APPPATH."third_party/stripe/init.php"; 
                  
        \Stripe\Stripe::setApiKey($this->config->item('stripe_secret_key'));
        try{   
      
            $itemPrice = 1; //$_REQUEST['amount']; 
            //$fee = $itemPrice*20;// admin fee + remaining amount from cover charges after stripe fee 
            $user = $this->common->getData('users',array('id'=>$this->user_id),array('single','field'=>'email'));
                            
            $charge = \Stripe\Charge::create(array(
                "amount" => $itemPrice*100,
                "receipt_email" => $user['email'],
                "currency" => 'USD',
                "source" => $_REQUEST['token'], // $client_charge_token,
                //"application_fee" =>  round($fee),// client fee + remaining amount from cover charges after stripe fee
            ) 
            //array("stripe_account" => $this->config->item('stripe_account'))
            ); 
            $date = date('Y-m-d H:i:s');

            $this->common->insertData('activity_attendee',array('payment_mode'=>$post['payment_mode'],'payment_status'=>'1','transaction_id'=>$charge->id,'payment_date'=>$date,'user_id'=>$this->user_id,'activity_id'=>$post['activity_id'])); 
            
            $message = array('title' => 'Payment Received','body'=>'Your payment for service is received');

                // if($provider[0]['device_type'] == 'android'){
                //     $this->send_notification(array($provider[0]['device_token']), $message); 
                // }else{
                //     $this->Apn($provider[0]['device_token'], $message); 
                // }   

            
            $this->response(true, 'Payment Successfull. You are successfully registerd for the activity.',array('result'=>'Your payment for activity is received'));           
        }

        catch (Exception $e) {            
            $errormsg = "Some error occured ". $e->getMessage();
            $this->response(false, $e->getMessage());  
        }    
    }

    public function reportAds() {
        $param = ['Token','Is-Update','machine_id','reason_id'];
        $post = $this->requireParameter($param,$_POST);
        $reported = $this->common->getData('report_ads', array('machine_id' => $post['machine_id']), array('single'));
        $result = false;
        if($reported){
            $this->response(false,apiMsg('machine_already_reported'));
        }else{
            $result = $this->common->insertData('report_ads',array('machine_id'=>$post['machine_id'],'user_id'=>$this->user_id,'reason_id'=>$post['reason_id']));            
        }
                
        if ($result) {
            $machine = $this->common->getData('machines',array('id'=>$post['machine_id']),array('single','field'=>'title'));
            $user = $this->common->getData('users',array('id'=>$this->user_id),array('single'));
            $template = $this->common->getData('contents',array('id'=>6),array('single'));
            if (strpos($template['description'], 'USER') !== false) {
                $template['description'] = str_replace('USER', $user['name'], $template['description']);
            }

            $link = '<a href="'.base_url('ads/adsDetail/'.$_POST['machine_id']).'">'.$machine['title'].'</a>';
            if (strpos($template['description'], 'LINK') !== false) {
                $template['description'] = str_replace('LINK', $link, $template['description']);
            }
            $template = $template['description'];

            $email_temp = $this->load->view('template/template',compact('template'),true);
            $this->common->sendMail($user['email'],$machine['title'].' Ad is reported',$email_temp);
            
            $response = $this->response(true, "Reported Successfully");
        }else{
            $this->response(false,apiMsg('error'));
        }
    }

    public function myMachines()
    {
        $param = ['Token','Is-Update','limit','offset','plan_id'];
        $post = $this->requireParameter($param,$_POST);
        
        $user_id = "";
        if($_POST['my_machine'] == 1){  // 1 for only my machine
            $user_id = $this->user_id;
        }
        
        $options1['limit'] = $post['limit'];
        $options1['offset'] = $post['offset'];

        if (isset($_POST['start_date'] )) {
            $options1['start_date <= '] = $_POST['start_date'];
        }
        if (isset($_POST['end_date'])) {
            $options1['end_date >= '] = $_POST['end_date'];
        }
        if ($_POST['filter'] != "all") {
          
            if ($_POST['filter'] == 0) {
                $options1['status'] = 1;
            }
            if ($_POST['filter'] == 1) {
                $options1['status'] = 0;
                $options1['approve'] = 1;
            }
            if ($_POST['filter'] == 2) {
                $options1['status'] = 0;
                $options1['approve'] = 2;
            }
            if ($_POST['filter'] == 3) {
                $options1['status'] = 0;
                $options1['approve'] = 2;
                $options1['valid <= '] = date('Y-m-d');
            }
            if ($_POST['filter'] == 4) {
                $options1['status'] = 0;
                $options1['approve'] = 0;
            }
            
            // $options1['status'] = 
        }
        if ($_POST['plan_id'] != 'all') {
            $options1['plan_id'] = $_POST['plan_id'];
        }

        // $data['ads'] = $this->common->multiJoinTable('machines as M',$joins,$options);
        $data['ads'] = $this->common->my_machines($this->user_id,$options1);

        if($data['ads']){
            foreach ($data['ads'] as $key => $value) {
                $data['ads'][$key]['ownership'] = ownership($value['ownership']);
                $data['ads'][$key]['renew'] = 0;
                
                if ($value['status'] == 1 || $value['allow_edit'] == 1) {

                    if($value['approve'] == 0 && $value['allow_edit'] == 1 && $value['status'] != 1){
                    
                        $data['ads'][$key]['allow_edit'] = 0;
                    
                    }else{
                          $data['ads'][$key]['allow_edit'] = 1;
                    }
 
                    // if ($value['valid'] != '0000-00-00' && $value['allow_edit'] == 1 ) {
                    //     $data['ads'][$key]['allow_edit'] == 0;
                    //     if ( date('Y-m-d', strtotime('-3 day', strtotime($value['valid']))) < date('Y-m-d') && date('Y-m-d') <= $value['valid']) {
                    //         $data['ads'][$key]['renew'] = 1;
                    //     }
                    // }else{
                    //     $data['ads'][$key]['allow_edit'] == 1;
                    // }                    
                }
                else{
                    $data['ads'][$key]['allow_edit'] = 0;
                }
                
                if ($value['status'] == 1 && $value['approve'] == 0) {
                    $data['ads'][$key]['machine_status'] = machine_status(0);
                }
                if ($value['status'] == 0 && $value['approve'] == 1 ) {
                    $data['ads'][$key]['machine_status'] = machine_status(1);
                }
                if ($value['status'] == 0 && $value['approve'] == 2) {
                    $data['ads'][$key]['machine_status'] = machine_status(2);
                }
                if ($value['status'] == 0 && $value['approve'] == 1 && $value['valid'] < date('Y-m-d')) {
                    $data['ads'][$key]['machine_status'] = machine_status(3);
                }
                if ($value['status'] == 0 && $value['approve'] == 1 && date('Y-m-d', strtotime('-3 day', strtotime($value['valid']))) <= date('Y-m-d') && date('Y-m-d') <= $value['valid']) {
                    $data['ads'][$key]['machine_status'] = machine_status(5);
                }
                if($value['status'] == 0 && $value['approve'] == 0){
                    $data['ads'][$key]['machine_status'] = machine_status(4);
                }


            }
            $this->response(true,apiMsg('machine_fetched'),array('response'=>$data));
        }else{
            $this->response(true,apiMsg('no_data'),array('response'=>'NA'));
        }
    }

    public function viewAds()
    {
        $joins = [
                    [
                        'join_table' => 'M',
                        'join_field' => 'user_id',
                        'other_table'=> 'users as U',
                        'other_field'=> 'id'
                    ],
                    [
                        'join_table' => 'M',
                        'join_field' => 'plan_id',
                        'other_table'=> 'plans as P',
                        'other_field'=> 'id'
                    ]
                ];
        $options = [
                        'field' => 'M.*,U.name,P.name as plan,P.cost,P.validity,IF(approve_date != null,"Pending",DATE_ADD(approve_date, INTERVAL P.validity DAY)) as valid,IF(show_phone == 1,U.mobile,null)'
                    ];
        $data['ads'] = $this->common->multiJoinTable('machines as M',$joins,$options);
    }

    public function sellerProfile()
    {
        $param = ['Token','Is-Update','uid'];
        $post = $this->requireParameter($param,$_POST);
        $result = $this->common->getData('users',array('id'=> $post['uid']),array('single'));
        if($result){
            $this->response(true,apiMsg('user_fetched'),array('response'=>$result));
        }else{
            $this->response(true,apiMsg('no_data'),array('response'=>'NA'));
        }
    }

    public function markAsFavourite()
    {
        $param = ['Token','Is-Update','machine_id'];
        $post = $this->requireParameter($param,$_POST);
        $check = $this->common->getData('price_n_favourite',array('user_id'=>$this->user_id,'machine_id'=>$post['machine_id']));
        if($check){
            $result = $this->common->updateData('price_n_favourite',array('favourite'=>1),array('user_id'=>$this->user_id,'machine_id'=>$post['machine_id']));
        }else{
            $result = $this->common->insertData('price_n_favourite',array('user_id'=>$this->user_id,'machine_id'=>$post['machine_id'],'favourite'=>1));
        }
        if($result){
            $this->response(true,apiMsg('favourite_added'),array('response'=>$post));
        }else{
            $this->response(false,apiMsg('error'));
        }
    }

    public function quotePrice()
    {
        $param = ['Token','Is-Update','machine_id','price'];
        $post = $this->requireParameter($param,$_POST);
        $check = $this->common->getData('price_n_favourite',array('user_id'=>$this->user_id,'machine_id'=>$post['machine_id']));
        if($check){
            $result = $this->common->updateData('price_n_favourite',array('price'=>$post['price']),array('user_id'=>$this->user_id,'machine_id'=>$post['machine_id']));
        }else{
            $result = $this->common->insertData('price_n_favourite',array('user_id'=>$this->user_id,'machine_id'=>$post['machine_id'],'price'=>$post['price']));
        }
        if($result){
            $machine = $this->common->getData('machines',array('id'=>$post['machine_id']),array('single','field'=>'user_id,title'));
            $user = $this->common->getData('users',array('id'=>$machine['user_id']),array('single'));
            $message = [
                'title' => 'New Price Quote',
                'message' => 'New price quoted on you ad '.$machine['title']
            ];
            $token = $user['device_token'];
            $this->sendpushnotification($token,$message); 
            $this->common->sendMail($user['email'],'New Price Quote','New price quoted on you ad '.$machine['title'].' for '.$post['price']);

            $this->response(true,apiMsg('price_quoted'),array('response'=>$post));
        }else{
            $this->response(false,apiMsg('error'));
        }
    }

    public function renewAds()
    {
        
    }

    public function myFavourite()
    {
        $param = ['Token','Is-Update','limit','offset'];
        $post = $this->requireParameter($param,$_POST);
        $favourite = $this->common->getData('price_n_favourite',array('user_id'=>$this->user_id,'favourite' => 1),array('limit'=>$post['limit'],'offset'=>$post['offset'],'field'=>'price,machine_id'));

        $machine_ids = [];
                        
        if (!empty($favourite)) {
            foreach ($favourite as $key => $value) {
            //     $options1['user_id'] = $this->user_id;                
                $machine_ids[] = $value['machine_id'];

            //     $machine[] = $this->common->getMachines(array(),$options1);
            }
        }
        
        $machine = $this->common->getData('machines',array(),array('where_in'=>$machine_ids,'colname'=>'id','limit'=>$post['limit'],'offset'=>$post['offset'],'api'));
        
        if (!empty($machine) && $machine != 'NA') {
            foreach ($machine as $key1 => $value1) {
                $machine[$key1]['image'] = $this->common->oneToManay('machines','machine_images',['where1' => array('id'=>$value1['id']),'where2'=> array('machine_id'=>$value1['id']),'field_match'=>'image','field1'=>'id','field2'=>'id,image']);
            }
        }
              
        $this->response(true,apiMsg('machine_fetched'),array('response'=>$machine));
        
    }
 
    public function removeFavourite()
    {
        $param = ['Token','Is-Update','machine_id'];
        $post = $this->requireParameter($param,$_POST);
        $result = $this->common->updateData('price_n_favourite',array('favourite'=>0),array('machine_id'=>$post['machine_id']));
        if ($result) {
            $this->response(true,apiMsg('removed_favourite'));
        }else{
            $this->response(false,apiMsg('error'));
        }
    } 

    public function markAsSold()
    {
        $param = ['Token','Is-Update','machine_id'];
        $post = $this->requireParameter($param,$_POST);
        $result = $this->common->updateData('machines',array('sold'=>1),array('id'=>$post['machine_id'],'user_id'=>$this->user_id));
        if ($result) {
            $this->response(true,apiMsg('marked_as_sold'));
        }else{
            $this->response(false,apiMsg('error'));
        }
    }  

    public function edituniqueData()
    {
        $param = ['Token','Is-Update','mobile','email','type']; // type 1 for email, 0 for mobile , 2 for both
        $post = $this->requireParameter($param,$_POST);
        $result = false;
        if ($post['type'] == 1) { 
            $check = $this->common->getData('users',array('email'=> $post['email'])); 
            if ($check) { 
                $this->response(false,apiMsg('email_exist')); exit();
            }else{
                $result = $this->common->updateData('users',array('email'=>$post['email']),array('id'=>$this->user_id));
            }      
        }
        if ($post['type'] == 0) {
            $check = $this->common->getData('users',array('mobile'=> $post['mobile']));
            if ($check) {
                $this->response(false,apiMsg('mobile_exist')); exit();
            }else{
                $result = $this->common->updateData('users',array('mobile'=>$post['mobile']),array('id'=>$this->user_id));
            } 
        }
        if ($result) {
            $this->response(true,apiMsg('profile_update_success'));
        }else{
            $this->response(false,apiMsg('error'));
        }
    }

    public function myPayments()
    {
        $param = ['Token','Is-Update'];
        $post = $this->requireParameter($param,$_POST);
        $joins = [
            [
                'join_table'  => 'PH',
                'join_field'  => 'machine_id',
                'other_table' => 'machines as M',
                'other_field' => 'id'
            ],
            [
                'join_table'  => 'M',
                'join_field'  => 'plan_id',
                'other_table' => 'plans as P',
                'other_field' => 'id'
            ]
        ];
        $where['PH.user_id'] = $this->user_id;
        if (isset($_POST['start_date'])) {
            $where['PH.payment_date <= '] = $_POST['start_date'];
        }
        if (isset($_POST['end_date'])) {
            $where['PH.payment_date >= '] = $_POST['end_date'];
        }
        if ($_POST['plan_id'] != "all") {
            $where['M.id'] = $_POST['paln_id'];
        }

        $options = [
                    'where' => $where,
                    'field' => 'PH.*,M.title,P.name,M.id as machine_id'
            ];
        $result = $this->common->multiJoinTable('payment_history as PH',$joins,$options);
        if ($result) {
            $this->response(true,apiMsg('payment_detail_fetched'),array('response'=>$result));
        }else{
            $this->response(true,apiMsg('no_data'),array('response'=>'NA'));
        }
    }
}
