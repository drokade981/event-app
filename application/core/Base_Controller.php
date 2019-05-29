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
            $bytes = random_bytes(ceil($lenght / 2));
        } elseif (function_exists("openssl_random_pseudo_bytes")) {
            $bytes = openssl_random_pseudo_bytes(ceil($length / 2));
        } else {
            throw new Exception("no cryptographically secure random function available");
        }
        echo substr(bin2hex($bytes), 0, $length);
    }
    
    function createThumb() {
        
        require APPPATH.'libraries/ffmpeg/src/FFMpeg/FFMpeg.php';
        
        $sec = 1;
        $movie = base_url('assests/uploads/gallery/08f81b6fcd0c37b4418e8b553da76869.mp4');
        $thumbnail = 'assests/uploads/thumbnail.png';

        $ffmpeg = FFMpeg\FFMpeg::create();
        $video = $ffmpeg->open($movie);
        $frame = $video->frame(FFMpeg\Coordinate\TimeCode::fromSeconds($sec));
        $frame->save($thumbnail);
        echo '<img src="'.$thumbnail.'">';
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
            //  echo"<pre>";
            //  print_r($result);
            // die();
        return $result;
    }

    function Apn($deviceToken,$message){  

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
            'Authorization:key = AAAAibOCUAY:APA91bG6TEZcH6FinqLE035dt21UOjUmTQuRXFg3pA9CFWe1B07g4PMHFuO0qVV-wPjGFx0aTdmBqPtDrKyElUbZ3OIVUiK3qUmcROKBhHLu3EU6zahpWfw2UjT9YlPgwKuLewolKnCm',            
            'Content-Type: application/json'
        );
        $url = 'https://fcm.googleapis.com/fcm/send';

        $this->curl($headers,$fields,$url);
    }
    

    function sendpushnotification($token,$message) // one signal
    {
        $content = array(
        "en" => $message['message']
        );

        $headings= array(
        "en" => $message['title']
        );

        $fields = array(
        'app_id' => "8b253122-3b51-434d-87de-9164baf6eccb",
        'include_player_ids' => $token,
        'data' => array("alert" =>$message['message'],"flag" =>'app',"title" =>$message['title']),
        'contents' => $content,
        'headings' => $headings,
        );

        $headers = array(
                'Content-Type: application/json; charset=utf-8',
                'Authorization: Basic MWFiN2Y4ZWItMzY2Ny00MGQ5LTg1OTQtMmE1ZDc4YzhkMjdh');
       
        $url = "https://onesignal.com/api/v1/notifications";

        $this->curl($headers,$fields,$url);
    }

}
