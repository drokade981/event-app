<?php

(defined('BASEPATH')) OR exit('No direct script access allowed');

class Base_Controller extends CI_Controller {

    public function __construct() {
        parent:: __construct();        
        $this->lang->load('user');
        $this->load->helper('common');
    }
   

    public function frontHtml($title = "", $page, $data = "") {
        $header['title'] = $title;
        $this->load->view('header', $header);
        $this->load->view('sidebar');
        $this->load->view($page, $data);
        $this->load->view('footer');
    }

    public function html($title = "UPM APP", $page, $data = array()) {
        $header['title'] = $title;
        $data['categories'] = $this->common->getData('categories',array('status'=>0,'is_delete'=>0),array('order_by'=>'category','order_direction'=>'asc'));
        $header['categories'] = $this->common->getData('categories',array('status'=>0,'is_delete'=>0),array('order_by'=>'category','order_direction'=>'asc'));        
        $this->load->view('front-header', $header);
        $this->load->view($page, $data);
        $this->load->view('front-footer');
    }

    public function flashMsg($class, $msg) {
        $msg1 = '<div class="alert-remove alert alert-' . $class . ' alert-dismissible" role="alert">' . $msg . '
		  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
		    <span aria-hidden="true">&times;</span>
		  </button>
		</div>
            <div class="clearfix"></div>';
        
        $this->session->set_flashdata('msg', $msg1);
        return true;
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

    public function pagination($url, $table, $segment) {
        $this->load->library('pagination');
        $config = [
            'base_url' => base_url($url),
            'per_page' => 10,
            'total_rows' => $this->common->getData($table, array(), array('count')),
            'full_tag_open' => "<ul class='pagination'>",
            'full_tag_close' => "</ul>",
            'first_tag_open' => '<li>',
            'first_tag_close' => '</li>',
            'last_tag_open' => '<li>',
            'last_tag_close' => '</li>',
            'next_tag_open' => '<li>',
            'next_tag_close' => '</li>',
            'prev_tag_open' => '<li>',
            'prev_tag_close' => '</li>',
            'num_tag_open' => '<li>',
            'num_tag_close' => '</li>',
            'cur_tag_open' => "<li class='active'><a>",
            'cur_tag_close' => '</a></li>',
        ];
        $this->pagination->initialize($config);
        $data = $this->common->getData($table, array(), array('limit' => $config['per_page'], 'offset' => $this->uri->segment($segment)));
        return $data;
    }

    public function imageLib($path, $option = array()) {
        $config['image_library'] = 'gd2';
        $config['source_image'] = $path;
        $config['create_thumb'] = false;
        $config['maintain_ratio'] = TRUE;
        $config['width'] = 65;
        $config['height'] = 45;
        if (!empty($option)) {
            foreach ($option as $key => $value) {
                $config[$key] = $value;
            }
        }
        $this->load->library('image_lib');
        $this->image_lib->initialize($config);
    }

    public function resizeImage($path, $config = array()) {
        $this->imageLib($path, $config);
        return $this->image_lib->resize();
    }

    public function watermark($path,$config = array())
    {
        $this->imageLib($path, $config);
        $this->image_lib->watermark();
    }
    
    public function generateToken($length=8)
    {
        $seed = str_split('abcdefghijklmnopqrstuvwxyz'
         .'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
         .'0123456789'); // and any other characters
        shuffle($seed); // probably optional since array_is randomized;
        $rand = '';
        foreach (array_rand($seed, $length) as $k){
                $rand .= $seed[$k];	
        } 

        return md5(microtime().$rand);
    }

    public function generateOTP($length=4)
    {
        $seed = str_split('0123456789'); // and any other characters
        shuffle($seed); // probably optional since array_is randomized;
        $rand = '';
        foreach (array_rand($seed, $length) as $k){
            $rand .= $seed[$k]; 
        } 
        return $rand;
    }
    
    public function generateCode($length=8)
    {
        if (function_exists("random_bytes")) {
            $bytes = random_bytes(ceil($length / 2));
        } elseif (function_exists("openssl_random_pseudo_bytes")) {
            $bytes = openssl_random_pseudo_bytes(ceil($length / 2));
        } else {
            throw new Exception("no cryptographically secure random function available");
        }
        echo substr(bin2hex($bytes), 0, $length); 
    }


    public  function callAPI($method, $url, $data){
       $curl = curl_init();

        switch ($method){
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);
                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            case "PUT":
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);                              
                break;
            default:
                if ($data)
                    $url = sprintf("%s?%s", $url, http_build_query($data));
       }

       // OPTIONS:
       curl_setopt($curl, CURLOPT_URL, $url);
       curl_setopt($curl, CURLOPT_HTTPHEADER, array(
              'Content-Type: application/json',
       ));
       curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
       curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

       // EXECUTE:
       $result = curl_exec($curl);
       if(!$result){die("Connection Failure");}
       curl_close($curl);
       return $result;
    }
    
    
    public function curl($headers,$fields,$url){
        
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);  
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        $result = curl_exec($ch);           
        if ($result == FALSE) {
                return false;
            die('Curl failed: ' . curl_error($ch));
        }
        curl_close($ch); 
             // echo"<pre>";
             // print_r($result);
            // die();
        return $result;
    }

    function Apn($deviceToken,$message){  //ios

        $fields = array
        (
            'to'    => $deviceToken,
            'priority' => 'high',
            'notification' => array('body'=> $message['message'],'title'=> $message['title'],'sound' => 'chime.aiff'),
            'data'  => $message
        );

        $headers = array
        (
            'Authorization: key=API_ACCESS_KEY_ios',
            'Content-Type: application/json'
        );
        $url = 'https://fcm.googleapis.com/fcm/send';

        $this->curl($headers,$fields,$url);
    }

    public function send_notification($tokens, $message)  // fcm
    {           
        $fields = array(
                'registration_ids' => $tokens,
                'notification' => $message
            );  

        $headers = array(
            'Authorization:key = AAAAibOCUAY:APA91bG6TEZcH6FinqLE035dt21UOjUqPtDrKyElUbZ3OIVUiKLewolKnCm',            
            'Content-Type: application/json'
        );
        $url = 'https://fcm.googleapis.com/fcm/send';

        $this->curl($headers,$fields,$url);
    }
    
    function sendpushnotification($token,$message) // one signal
    {
        $content = array(
        "en" => strip_tags($message['message'])
        );

        $headings= array(
        "en" => $message['title']
        );

        $fields = array(
            'app_id' => $this->config->item('one_signal_app_id'),
            'include_player_ids' => $token,
            'data'       => array(
                    "alert" => strip_tags($message['message']),
                    "flag"  => 'app',
                    "title" => 'UPM 365',//$message['title'],
                    "corresponding_id" => $message['corresponding_id'],
                    "type"  => $message['type'],
                    "user_id"  => $message['user_id'],
                    'sender_user_id' => $message['sender_user_id']
                    
                    ),
            'contents'  => $content,
            'headings'  => $headings,
        );

        $headers = array(
                'Content-Type: application/json; charset=utf-8',
                'Authorization: Basic Yzk********************2Zm');
       
        $url = "https://onesignal.com/api/v1/notifications";

        $this->curl($headers,$fields,$url);
    }

    public function sendSMS($mobile,$message)
    {
        $apikey = $this->config->item('sms_apikey');
     
        //Approved sender id(6 characters string only).
        $senderid = $this->config->item('sms_senderid');
        
        //Message channel Promotional=1 or Transactional=2.
        $channel = $this->config->item('sms_channel');
        
         //Default is 0 for normal message, Set 8 for unicode sms.
        $DCS = "8";
        
         //Default is 0 for normal sms, Set 1 for immediate display.
        $flashsms = "0";
        
        //Recipient mobile number (pass with comma seprated if need to send on more then one number).

        if(is_array($mobile)){
            $mobile = preg_filter('/^/', '91', $mobile);
            $mobile = implode(',', $mobile);
        }else{
            $mobile = '91'.$mobile;
        }

        $number = $mobile;
    
        //Your message to send.
        $message = $message;
        
        //Define route 
        $route = 1;
        //Prepare you post parameters
        $postData = array(
            'APIKey' => $apikey,
            'senderid' => $senderid,
            'channel' => $channel,
            'DCS' => $DCS,
            'flashsms' => $flashsms,
            'number' => $number,
            'message' => rawurlencode($message),
            'route' => $route
        );
        
        //API URL
        $url="https://www.smsgatewayhub.com/api/mt/SendSMS?";
        
        // init the resource
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData
            //,CURLOPT_FOLLOWLOCATION => true
        ));
    
        $headers = array();

        $res = $this->curl($headers,$postData,$url);
        dd($res);
        //Ignore SSL certificate verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        //get response
        $output = curl_exec($ch);
        
        //Print error if any
        if(curl_errno($ch))
        {
            echo 'error:' . curl_error($ch);
        }
    
        curl_close($ch);
        
        return $output;
    }

    public function sms($mobile,$message)
    {
        $apikey = $this->config->item('sms_apikey');
        $apisender = $this->config->item('sms_senderid');
        
        if(is_array($mobile)){
            $mobile = preg_filter('/^/', '91', $mobile);
            $mobile = implode(',', $mobile);
        }else{
            $mobile = '91'.$mobile;
        }                
        $num = $mobile;    // MULTIPLE NUMBER VARIABLE PUT HERE...! 
        $ms = rawurlencode($message);   //This for encode your message content                       
         
        $url = 'https://www.smsgatewayhub.com/api/mt/SendSMS?APIKey='.$apikey.'&senderid='.$apisender.'&channel=2&DCS=0&flashsms=0&number='.$num.'&text='.$ms.'&route=1';
                             
        //echo $url;
        $ch=curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,"");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,2);
        $data = curl_exec($ch);
        if(curl_errno($ch))
        {
            echo 'error:' . curl_error($ch);
        }
    
        curl_close($ch);
        
        return $data;
        
    }
    
}
