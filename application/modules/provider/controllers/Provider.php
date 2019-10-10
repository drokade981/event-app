<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Provider extends Base_Controller {

	public function __construct()
	{
		parent:: __construct();
		$this->checkAuth();		
		$this->load->helper('common');
		$this->data = json_decode(file_get_contents("php://input"));
	}	

	public function getKey()
	{
		$result = $this->common->getFieldKey($_POST['table']);
		echo json_encode($result);
	}

	public function login()
	{				
		$_POST['password'] = md5($_POST['password']);
		
		$result = $this->common->getData('user',array('email or mobile = ' => $_POST['email'], 'password' => $_POST['password']),array('single'));
		
		if($result){
			if(isset($_POST['android_token'])){
				$old_device = $this->common->getData('user',array('android_token' => $_POST['android_token']),array('single','field'=>'id'));	
			}		
			if (isset($_POST['ios_token'])) {
				$old_device = $this->common->getData('user',array('ios_token' => $_POST['ios_token']),array('single','field'=>'id'));	
			}
			if($old_device){
				$this->common->updateData('user',array('android_token' => "", "ios_token" => ""),array('id' => $old_device['id']));
			}
			$this->common->updateData('user',array('ios_token' =>$_POST['ios_token'], 'android_token' => $_POST['android_token']), array('id' => $result['id']));
			$result['android_token'] = $_POST['android_token'];
			$this->response(true,'Successfully Login',array("userinfo" => $result));					
		}else{
			$message = "Wrong email or password";			
			$this->response(false,$message,array("userinfo" => ""));
		}
	}

	public function signup()
	{			
		$existEmail = false;
		if($_REQUEST['email'] != ""){
			$existEmail = $this->common->getData('user',array('email' => $_REQUEST['email']),array('single'));
		}
		$existMobile = $this->common->getData('user',array('mobile' => $_REQUEST['mobile']),array('single'));
		$_POST['otp'] = '1234';			
		
		if($existEmail){
			$this->response(false,"This email address already exists.");	die;		
		}elseif($existMobile){
			$this->response(false,"This mobile number already exists.");	die;
		}
		else{

			$_POST['user_img'] = '';
			if(isset($_FILES['user_img'])){
				$image = $this->common->do_upload('user_img','./assets/userfile/profile/');
				if(isset($image['upload_data'])){
					$_POST['user_img'] = $image['upload_data']['file_name'];
				}
			}			
			
			$_POST['password'] = md5($_POST['password']);
			$_POST['created_at'] = date('Y-m-d H:i:s');
						
			$old_device = $old_ios = false;
			if(isset($_POST['android_token'])){
				$old_device = $this->common->getData('user',array('android_token' => $_POST['android_token']),array('single','field'=>'id'));
			}
			if(isset($_POST['ios_token'])){
				$old_ios =  $this->common->getData('user',array('ios_token' => $_POST['ios_token']),array('single','field'=>'id'));
			}
			if($old_device || $old_ios){
				$this->common->updateData('user',array('android_token' => "", "ios_token" => ""),array('id' => $old_device['id']));
			}
			$post = $this->common->getField('user',$_POST); 
			
			$result = $this->common->insertData('user',$post);
			if($result){
				$userid = $this->db->insert_id();					
				$info = $this->common->getData('user',array('id' => $userid),array('single'));
				if($_POST['email'] != ""){
					$template = $this->load->view('template/verify-email',array('email' => $_POST['email'],'otp' => $_POST['otp'],'name' => $_POST['name']),true);
					$this->common->sendMail($_POST['email'],"Verify Email",$template);
				}
				
				$this->response(true,"Your registration successfully completed.",array("userinfo" => $info));					
			}else{
				$this->response(false,"There is a problem, please try again.",array("userinfo" => ""));
			}
		}
	}

	public function mailcheck()
	{
		$template = $this->load->view('template/verify-email',array('email' => 'devendra@mailinator.com','otp' => '1258','name' => 'Devendra'),true);
		$r = $this->common->sendMail('anshulsoni.rit@gmail.com','verify mail',$template);
		if($r){
			echo "send";
		}else{
			echo "not send";
		}
	}

	public function verification()
	{		
		if($_POST['type'] =='mobile'){
			$userinfo = $this->common->getData('user',array('mobile'=>$_POST['mobile']),array('single'));
			if($_POST['otp'] != $userinfo['otp']){
				$this->response(false,"Wrong OTP entered. please try again.",array("userinfo" => $userinfo)); exit();
			}
			$this->common->updateData('user',array('verified'=> '1','otp' => null),array('mobile'=> $_POST['mobile']));
			$message = "OTP verified successfully.";
		}

		if($_POST['type'] == 'email'){
			$userinfo = $this->common->getData('user',array('email'=>$_POST['email']),array('single'));
			if($_POST['otp'] != $userinfo['otp']){
				$this->response(false,"Wrong OTP entered. please try again.",array("userinfo" => $userinfo)); exit();
			}
			$this->common->updateData('user',array('verified' => '1','otp' => null),array('email' => $_POST['email']));
			$message = "Email verified successfully.";	
		}
		
		$this->response(true,$message,array("userinfo" => $userinfo));
	}

	public function social_login()
	{		
		$user = $this->common->getData('user',array('email' => $_POST['email']),array('single'));
		$url = $this->input->post('image');
		$uimg = "";
		if($url != ""){
			$uimg = rand().time().'.png';
			file_put_contents('assets/userfile/profile/'.$uimg, file_get_contents($url));
		}
		if($user){
			
			$old_device = $this->common->getData('user',array('android_token' => $_POST['android_token']),array('single','field'=>'id'));
			if($old_device){
				$this->common->updateData('user',array('android_token' => "", "ios_token" => ""),array('id' => $old_device['id']));
			}
			$update = $this->common->updateData('user',array('image' => $uimg, 'ios_token' =>$_POST['ios_token'], 'android_token' => $_POST['android_token']),array('id' => $user['id']));
			if($update){				
				if($user['image'] != "" && file_exists('assets/userfile/profile/'.$user['image'])){
					unlink('assets/userfile/profile/'.$user['image']);
				}
				$user['image'] = $uimg;
				$this->response(true,"Login Successfully.",array("userinfo" => $user));
			}else{
			 	$this->response(false,"There is a problem, please try again.",array("userinfo" => ""));
  			}
		}else{			
			$insert = $this->common->insertData('user',array('email' => $_POST['email'],'image' => $uimg,'name' => $_POST['name'],'ios_token' =>$_POST['ios_token'], 'android_token' => $_POST['android_token'],'created_at' => Date('Y-m-d H:i:s')));
			$uid  = $this->db->insert_id();
			if($insert){
		    $user = $this->common->getData('user',array('id'=> $uid),array('single'));
				$this->response(true,"Your Registration Successfully Completed.",array("userinfo" => $user));
			}else {
		     	$this->response(false,"There is a problem, please try again.",array("userinfo" => ""));
		    }
		}
	}

	public function updateProfile(){

		$id = $_POST['id']; unset($_POST['id']);		
		if(!empty($_FILES['image'])){
			$image = $this->common->do_upload('image','./assets/userfile/profile/');
			$_POST['image'] = $image['upload_data']['file_name'];
			$old_image = $this->common->getData('user',array('id'=>$id),array('single','field'=>'image'));
			if(file_exists('./assets/userfile/profile/'.$old_image['image'])){ 
				unlink('./assets/userfile/profile/'.$old_image['image']);
			}
		}	
		$post = $this->common->getField('user',$_POST);		
		$result = $this->common->updateData('user',$post,array('id' => $id)); 

		if($result){
			$user = $this->common->getData('user',array('id' => $id),array('single'));
			$this->response(true,"Profile Update Successfully.",array("userinfo" => $user));
		}else{
			$this->response(false,"There is a problem, please try again.",array("userinfo" => ""));
		}
	}

	public function getProfile()
	{
		$user = $this->common->getData('user',array('id' => $_POST['id']),array('single'));
		if($user){
			$this->response(true,"Profile fetch Successfully.",array("userinfo" => $user));			
		}else{
			$this->response(false,"There is a problem, please try again.",array("userinfo" => ""));
		}			
	}

	public function contactUs()
	{
		$message = '<h4>'.$_POST['name'].'</h4><p>'.$_POST['message'].'</p>';
		$mail = $this->common->sendMail('devendra@mailinator.com','Contact Us',$message,array('fromEmail'=>$_POST['email']));
		$mail_msg = $mail ? 'Email send successfully' : 'Email not send. Please send again';
		$this->response($mail,$mail_msg);	
	}
	
	public function report()
	{		
		$_POST['created_at'] = date('Y-m-d H:i:s');
		$post = $this->common->getData('post',array('id' => $_POST['post_id']),array('single'));
		$user = $this->common->getData('user',array('id'=> $post['uid']),array('single','field'=>'email,name'));
		$post1 = $this->common->getField('report',$_POST);
		$report = $this->common->insertData('report',$post1);
		$mail = false;
		if($report){
			//$this->checkMail();
			$message = "Hello Administrator <br> One post <a href='".base_url('api/postDetail/'.$post['id'])."'>".$post['title']."</a> is reported. We will delete your post if found inappropriate. <br>".$_POST['comment'];

			$mail = $this->common->sendMail("info@positivenetwork.com.au",'Report on your post',$message);
		}
		$response = $this->response($mail,"Reported Successfully");		
	}

	public function deleteService()
	{		
		$service = $this->common->getData('my_services',array('my_service_id'=>$_REQUEST['my_service_id'],'provider_id'=>$_REQUEST['provider_id']),array('single'));

		$result = false;
		if($service){
			$user_request = $this->common->getData('user_send_request',array('provider_id'=>$_REQUEST['provider_id'],'service_offer_category_id'=>$service['service_offer_category_id'],'service_offer_subcategory_id'=>$service['service_offer_subcategory_id']),array('single','field'=>'status'));
			
			if($user_request){
				if($user_request['status'] == 4){
					$result = $this->common->deleteData('my_services',array('my_service_id'=>$_REQUEST['my_service_id']));
					$services = $this->common->myServices($_REQUEST['provider_id']);
					if($services == false){
						$this->response(false,"No service found."); die;
					}
				}else{
					$services = $this->common->myServices($_REQUEST['provider_id']);
					$this->response(false,"you can not delete this service. It is already ongoing",array('result'=>$services)); die;
				}
			}else{
				$result = $this->common->deleteData('my_services',array('my_service_id'=>$_REQUEST['my_service_id']));
				$services = $this->common->myServices($_REQUEST['provider_id']);
				if($services == false){
					$this->response(false,"No service found."); die;
				}
			}
		}		
		$services = $this->common->myServices($_REQUEST['provider_id']);
		
		if($services){
			foreach ($services as $key => $value) {
				$ongoing = $this->common->getData('user_send_request',array('provider_id'=>$_REQUEST['provider_id'],'status'=>1),array('count'));
				if($ongoing){
					$services[$key]['ongoing_projects'] = $ongoing;
				}

				$complete = $this->common->getData('user_send_request',array('provider_id'=>$_REQUEST['provider_id'],'status'=>3),array('count'));
				if($complete){
					$services[$key]['completed_projects'] = $complete;
				}
			}
		}		
		
		if($result){
			$this->response(true,"Service deleted Successfully.",array('result'=>$services));			
		}else{
			$this->response(false,"There is a problem, please try again.",array('result'=>$services));
		}	
	}

	public function history()
	{
		$datatype = array('ser.user_id'=>$_REQUEST['user_id']);
		if(isset($_REQUEST['request_id']) && $_REQUEST['request_id']  != ''){
			$datatype = array(
				'ser.user_send_request_id'=> $_REQUEST['request_id'],
				'ser.user_id'=>$_REQUEST['user_id']
			);
		}

		$result = $this->common->getUserHistory($datatype);

		if($result){
			foreach ($result as $key => $value) {

				$diff = strtotime($value['end_working_hours']) - strtotime($value['start_working_hours']);
				$datetime1 = new DateTime($value['start_working_hours']);
				$datetime2 = new DateTime($value['end_working_hours']);
				$interval = $datetime1->diff($datetime2);
				$minutes = $interval->days * 24 * 60;
				$minutes += $interval->h * 60;
				$minutes += $interval->i;

				$amount = floor($minutes / 60)*$value['service_rate'];
				$amount += ($minutes % 60)*$value['service_rate']/60;

				$result[$key]['amount'] = $amount;
				$result[$key]['rating'] = 3;
				$result[$key]['working_hours'] = convertToHoursMins($minutes);
			}
		}
		if($result){
			$this->response(true,"History fetched Successfully.",array('result'=>$result));			
		}else{
			$this->response(false,"There is a problem, please try again.",array('result'=>$result));
		}
	}

	public function deleteRequest()
	{
		$result = $this->common->deleteData('user_send_request',array('user_send_request_id'=>$_REQUEST['request_id']));
		if($result){
			$this->response(true,"Request deleted Successfully.");			
		}else{
			$this->response(false,"There is a problem, please try again.");
		}
	}

	public function unreadNotification()
	{
		if($_REQUEST['type'] == 0){  // user
			$where = array('user_id' => $_REQUEST['user_id'],'user_view'=>0);
		}else{	// 1 provider
			$where = array('provider_id' => $_REQUEST['user_id'],'provider_view'=>0);
		}
		$result = $this->common->getData('user_send_request',$where ,array('count'));
		if($result){
			$this->response(true,"Notification fetched Successfully.",array('result'=>$result));
		}else{
			$this->response(false,"There is a problem, please try again.");
		}
	}

	public function requestedServices()
	{
		$result = $this->common->getUserHistory(array('ser.user_id'=>$_REQUEST['user_id']));
		if($result){
			
			foreach ($result as $key => $value) {

				$diff = strtotime($value['end_working_hours']) - strtotime($value['start_working_hours']);
				$datetime1 = new DateTime($value['start_working_hours']);
				$datetime2 = new DateTime($value['end_working_hours']);
				$interval = $datetime1->diff($datetime2);
				$minutes = $interval->days * 24 * 60;
				$minutes += $interval->h * 60;
				$minutes += $interval->i;

				$amount = floor($minutes / 60)*$value['service_rate'];
				$amount += ($minutes % 60)*$value['service_rate']/60;

				$result[$key]['amount'] = $amount;
				$result[$key]['rating'] = 3;
				$result[$key]['working_hours'] = convertToHoursMins($minutes);
			}
		
			$this->response(true,"success",array('result'=>$result));
		}else{
			$this->response(false,"Record not found");
		}
	}

	public function canceRequest()
	{
		$update = $this->common->updateData('user_send_request',array('service_status_message' =>'cancelled', 'status' => $_REQUEST['status_code']),array('user_send_request_id' => $_REQUEST['user_send_request_id']));
		$this->user_send_request_list();
	}

	public function user_send_request_list()
	{
		$services = $this->common->getUserHistory(array('ser.status' => 0,'ser.user_id'=>$_REQUEST['user_id']));
		if($services){
			$this->response(true,"success",array('result'=>$services));
		}else{
			$this->response(false,"Record not found.");
		}
	}

	public function spareParts()
	{
		$result = $this->common->getData('spare_parts',array('user_send_request_id'=>$_REQUEST['request_id']));
		if($result){
			$this->response(true,"success",array('result'=>$result));
		}else{
			$this->response(false,"Record not found.");
		}
	}

	public function paymentHistory()
	{
		if($_REQUEST['type'] == 0){  // user
			$where['user_from'] = $_REQUEST['user_id'];
		}else{	// 1 provider
			$where['user_to'] = $_REQUEST['user_id'];
		}
		$result = $this->common->payHistory($where);
		if($result){
			$this->response(true,"success",array('result'=>$result));
		}else{
			$this->response(false,"Record not found.");
		}
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

    public function pay()
    {  
        $tobank = $this->common->getData('account_detail',array('user_id' => $_REQUEST['provider_id']),array('single'));       

        //include Stripe PHP library
        require_once APPPATH."third_party/stripe/init.php";
        
        //set api key in above file   
                  
        \Stripe\Stripe::setApiKey($stripe['secret_key']);
                       
        try{   
      
        	$itemPrice = $_REQUEST['amount']; 
	        $fee = $itemPrice*20;// fancess fee + remaining amount from cover charges after stripe fee 
	                        
	        $charge = \Stripe\Charge::create(array(
	            "amount" => $itemPrice*100,
	            "receipt_email" => $_REQUEST['email'],
	            "currency" => 'USD',
	            "source" => $_REQUEST['token'], // $client_charge_token,
	            "application_fee" =>  round($fee),// fancess fee + remaining amount from cover charges after stripe fee
	        ), array("stripe_account" => $tobank['stripe_account'])); //$bank['stripe_account']
	        $date = date('Y-m-d H:i:s');

	        $update = array("user_send_request_id"=>$_REQUEST['user_send_request_id'],"user_from" =>$_REQUEST['user_id'],"user_to"=>$_REQUEST['provider_id'],"stripe_receipt"=>$charge->id,'created_at' => $date,'amount'=> $itemPrice,'paid_amount' => $itemPrice*0.8);
        	$paid = $this->common->insertData('payment_history',$update); 
        	$last_id = $this->db->insert_id();        	
        	$this->common->updateData('user_send_request',array('service_status_message'=>'Paid','status'=>5),array('user_send_request_id'=>$_REQUEST['user_send_request_id'])); 

        	$provider = $this->common->getData('users',array('provider_id'=>$_REQUEST['provider_id']));
        	if($provider){
        		$message = array('title' => 'Payment Received','body'=>'Your payment for service is received');

        		if($provider[0]['device_type'] == 'android'){
					$this->send_notification(array($provider[0]['device_token']), $message); 
				}else{
					$this->Apn($provider[0]['device_token'], $message); 
				}	

        		foreach ($provider as $key => $value) {
        			$provider[$key]['id'] = $last_id;        			
        		}
        	}
        	$this->response(true, 'Payment Successfull',array('result'=>$provider));           
        }

        catch (Exception $e) {            
            $errormsg = "Some error occured ". $e->getMessage();
            $this->response(false, $e->getMessage());  
        }    
    }

    public function ratingReview()
    {
    	$result = $this->common->updateData('payment_history',array('rating'=>$_REQUEST['rating'],'review'=>$_REQUEST['review'],'rate_date'=>$_REQUEST['rate_date']),array('id'=>$_REQUEST['id']));
    	if($result){
			$this->response(true,"Rated successfully");
		}else{
			$this->response(false,"Some error occured.Please try again.");
		}
    }

    public function checkBank()
    {
    	$bank = $this->common->getData('account_detail',array('user_id'=>$_REQUEST['provider_id']),array('count'));
    	if($bank > 0){
			$this->response(true,"Bank already successfully");
		}else{
			$this->response(false,"Bank not added");
		}
    }

    public function getReview()
    {
    	$result = $this->common->getData('payment_history',array('user_to'=>$_REQUEST['provider_id']),array('field'=>'rating,review,rate_date,user_from'));
    	if($result){
    		foreach ($result as $key => $value) {
    			$user = $this->common->getData('users',array('provider_id'=>$value['user_from']),array('single','field'=>'first_name,last_name,user_img'));
    			$result[$key]['first_name'] = $user['first_name'];
    			$result[$key]['last_name'] = $user['last_name'];
    			$result[$key]['user_img'] = $user['user_img'];
    		}
    	}
    	if($result){
			$this->response(true,"Review fetched successfully",array('result'=>$result));
		}else{
			$this->response(false,"No record Found");
		}
    }

    public function ongoing_service()
    {
    	$task = explode(',', $_REQUEST['add_task_list']); 
    	$provider = $this->common->getData('users',array('provider_id'=>$_REQUEST['provider_id']),array('single'));
    	$category_id = $this->common->getData('service_offer_category',array('service_offer_category_id'=>$_REQUEST['provider_id']),array('single','field'=>'service_offer_category_id'));
    	if($_REQUEST['status_code'] == '1'){

    	}
    	if($_REQUEST['status_code'] == '2'){    		
    		$update = $this->common->updateData('user_send_request',array('status' => '2', 'service_status_message' => 'started','start_working_hours' => $_REQUEST['working_hours'],'provider_view' => '1'),array('user_send_request_id' => $_REQUEST['user_send_request_id']));
			$message = array('type' => 'Service Start','title'=>'Today your apointment with <b>'.$provider1['first_name'].' '.$provider1['last_name'].'</b> provider for <b>'.$category1['service_offer_category_name'].'</b>');
			if($provider['device_type'] == 'android'){
				$this->send_notification(array($provider['device_token']), $messages); 
			}else{
				$this->Apn($provider['device_token'], $messages); 
			}	
    	}
    	if($_REQUEST['status_code'] == '3'){
    		
    		$update = $this->common->updateData('user_send_request',array('status' => '3', 'service_status_message' => 'completed','end_working_hours' => $_REQUEST['working_hours'],'provider_view' => '1'),array('user_send_request_id' => $_REQUEST['user_send_request_id']));
			if(!empty($task)){
				foreach ($task as $key => $value) {					
					mysqli_query($con,"INSERT INTO spare_parts (user_send_request_id,spare) VALUES ('$user_send_request_id','$value')");		
				}
			}
			$message = array('type' => 'Service Completed','title'=>'Your service apointment with <b>'.$provider1['first_name'].' '.$provider1['last_name'].'</b> provider for <b>'.$category1['service_offer_category_name'].'</b> is completed');
			if($provider['device_type'] == 'android'){
				$this->send_notification(array($provider['device_token']), $messages); 
			}else{
				$this->Apn($provider['device_token'], $messages); 
			}	

    	}
    	if($_REQUEST['status_code'] == '4'){
    		$update = mysqli_query($con,"UPDATE user_send_request SET status = '4',service_status_message = 'cancelled',provider_view = '1' WHERE user_send_request_id = '$user_send_request_id'");
    		$update = $this->common->updateData('user_send_request',array('status' => '4', 'service_status_message' => 'cancelled','provider_view' => '1'),array('user_send_request_id' => $_REQUEST['user_send_request_id']));
    	}
    	$result = $this->common->getUserHistory('ser.provider_id='.$_REQUEST['provider_id'].' AND (ser.status =1 OR ser.status =2');
    }

    public function myReview()
    {
    	$result = $this->common->myReview($_REQUEST['provider_id']);
    	if($result){
			$this->response(true,"Review fetched successfully",array('result'=>$result));
		}else{
			$this->response(false,"No record Found");
		}
    }

    public function getSupport()
    {
    	$result = $this->common->getData('admin',array('id'=>1),array('single'));
    	if($result){
			$this->response(true,"Review fetched successfully",array('result'=>$result));
		}else{
			$this->response(false,"No record Found");
		}
    }
	
	  public function upcomingSection()
  {  
    $data["universities"] = $this->common->getData("university_tbl",[],["field"=>"uname,uid"]);
    $this->adminHtml('Upcoming Course','upcoming-section',$data);
  }


  public function upcoming_section_data()
  {
    $where = array('S.start_at >= ' => date("Y-m-d"));
    $arr = array(1 => "U.uname",0 => "C.cname",2 => "S.name",3 => "S.start_at",4 => "S.end_at",5 => "teacher",6 => "number_of_registered_student");

             
    $start_date = $_GET["start_date"];
    $end_date = $_GET["end_date"];
    $course = $_GET["course"];
    $university = $_GET["university"];

    if(!empty($start_date))
    {
      $where['DATE(S.start_at) > '] = date("Y-m-d",strtotime($start_date));
    } 

    if(!empty($end_date))
    {
      $where['DATE(S.end_at) < '] = date("Y-m-d",strtotime($end_date));
    }

    if(!empty($course))
    {
      $where['course_id'] = $course;
    } 
    if(!empty($university))
    {
      $where['U.uid']=$university;
    }

    if(isset($_GET["search"]) && !empty($_GET["search"]["value"]))
    {
      $search_value=$_GET["search"]["value"];
      foreach ($arr as $key => $value) {
       if($key==0)
        $this->db->like($value,$search_value,'both');
       else
        $this->db->or_like($value,$search_value,'both');
      }
    }

    $options = array(
      "where" => $where,
      "field" =>"U.uname, C.cname,S.*,SUM(CASE WHEN (SU1.role_id = 3) THEN 1 ELSE 0 END) AS studentcount,SUM(CASE WHEN (SU1.role_id = 4) THEN 1 ELSE 0 END) AS teachercount",
      "order_by"=>  "U.uname",
      "limit"   =>  $_GET["length"],
      "offset"  =>  $_GET["start"], 
      "group_by" => "S.section_id"     
    );
   

    if(isset($_GET["order"][0]["column"]))
    {
      $sort_col=$_GET["order"][0]["column"];
       
      if(isset($arr[$sort_col]))
      {
        $sort_order=$_GET["order"][0]["dir"];
        $this->db->order_by($arr[$sort_col], $sort_order);
      }
    }else{
      $this->db->order_by($options['order_by']);
    }
           
    if (isset($options['limit']) && isset($options['offset'])) {
        $this->db->limit($options['limit'], $options['offset']);
    } elseif (isset($options['limit'])) {
        $this->db->limit($options['limit']);
    }
       
            
    $joins = [
          [
            "join_table"=>'S',
            "join_field"=>'course_id',
            "other_table"=>'course_tbl as C',
            "other_field"=>'course_code',
          ],
          [
            "join_table"=> 'C',
            "join_field"=> 'uid',
            "other_table"=> 'university_tbl as U',
            "other_field"=> 'uid',            
          ],
          [
            "join_table"=> 'S',
            "join_field"=> 'section_id',
            "other_table"=> 'section_users as SU1',
            "other_field"=> 'section_id',            
          ],   

        ];
    $sections=$this->common->multiJoinTable("section as S",$joins,$options);
   
    $rows = array();
    if(!empty($sections)){
      foreach($sections as $section){
        $start_date = "";
        if($section["start_at"] != '0000-00-00 00:00:00'){ 
          $start_date = date("Y-m-d",strtotime($section["start_at"]));
        }
        $end_date = "";
        if($section["end_at"] != '0000-00-00 00:00:00'){ 
          $end_date=date("Y-m-d",strtotime($section["end_at"])); 
        } 
             
        // if($section["number_of_registered_student"]==""){ 
        //   $number_of_registered_student=0;
        // }
        // else{ 
        //   $number_of_registered_student=$section["number_of_registered_student"];
        // }

        // $number_of_registered_student = '<a href="'.base_url('sections/sectionDetail/'.$section['section_id']).'"><button type="button" class="btn btn-primary btn-sm">View</button></a>';

          $rows[] = array(
                  $section["cname"],
                  $section["uname"],
                  $section["name"],
                  $start_date,
                  $end_date,
                  $section["studentcount"],
                  $section["teachercount"],
                  '<a href="'.base_url('sections/sectionDetail/'.$section['section_id']).'" target="_blank"><i class="fa fa-arrow-circle-right font-20"></i></a>'
              );
            }
        }

        $data["data"]=$rows;
        if(isset($_GET["search"]) && !empty($_GET["search"]["value"]))
        {
          $search_value=$_GET["search"]["value"];
          foreach ($arr as $key => $value) {
            if($key==0)
              $this->db->like($value,$search_value,'both');
            else
              $this->db->or_like($value,$search_value,'both');

        }
      }
      $options = array("where" => $where,"field" =>"U.uname, C.cname,S.*,SUM(CASE WHEN (SU1.role_id = 3) THEN 1 ELSE 0 END) AS studentcount,SUM(CASE WHEN (SU1.role_id = 4) THEN 1 ELSE 0 END) AS teachercount","group_by" => "S.section_id","count");                 
      $sectioncount = $this->common->multiJoinTable("section as S",$joins,$options);
      $data["recordsTotal"] = $sectioncount;
      $data["recordsFiltered"] = $sectioncount;

      echo json_encode($data);

  }

}
