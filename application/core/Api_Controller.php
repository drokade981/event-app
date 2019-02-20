<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Api_Controller
 *
 * @author devendrarokade
 */
class Api_Controller extends Base_Controller{
    
    public function __construct()
    {
        parent:: __construct();
        $this->authkey = 'dfs#!df154$';
        $this->lang->load('api');
    }
    
    public function checkAuth()
    {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With");
        
        foreach($_SERVER as $key => $value) {
            if (substr($key, 0, 5) <> 'HTTP_') {
                continue;
            }
            $header = str_replace(' ','-', ucwords(str_replace('_',' ', strtolower(substr($key,5)))));
            $headers[$header] = $value;
        }	    
        
        if($headers['Authorization'] == ""){	
            $message = "Auth key required";
            $this->response(false,$message); exit;
        } 

        if($headers['Authorization'] != $this->authkey){	
            $message = "wrong Authentication key";
            $this->response(false,$message); exit;
        }
       
        if($headers['Is-Update']==0){
        // Login,Registration and Singup API
        }elseif($headers['Is-Update']==1){

            $user = $this->common->getData('users',array('token'=> $headers['Token']),array('single'));
            if($user && $user['status'] == 0){
                return $user['id'];
            }elseif ($user && $user['status'] == 1) {
                $this->response(false, apiMsg('user_blocked')); exit;
            }
            else {            
                $this->response(false, apiMsg('unauthorize_access')); exit;
            }
        }elseif($headers['Is-Update']==2){
            // bypass authentication
        }		   
    }
    
    function AuthCheck2() {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With");
        
//        foreach($_SERVER as $key => $value) {
//            if (substr($key, 0, 5) <> 'HTTP_') {
//                continue;
//            }
//            $header = str_replace(' ','-', ucwords(str_replace('_',' ', strtolower(substr($key,5)))));
//            $headers[$header] = $value;
//        }
        
        $param = ['Authorization','Is-Update'];
        $this->requireParameter($param,$_POST);
        
        if($_REQUEST['Authorization'] == ""){	
            $message = "Auth key required";
            $this->response(false,$message); exit;
        } 

        if($_REQUEST['Authorization'] != $this->authkey){	
            $message = "wrong Authentication key";
            $this->response(false,$message); exit;
        }
       
        if($_REQUEST['Is-Update']==0){
        // Login,Registration and Singup API
        }elseif($_REQUEST['Is-Update']==1){
            $this->requireParameter(['Token'],$_POST);
            $user = $this->common->getData('users',array('token'=> $_REQUEST['Token']),array('single'));
            
            if($user && $user['status'] == 0){
                return $user['id'];
            }elseif ($user && $user['status'] == 1) {
                $this->response(false, apiMsg('user_blocked')); exit;
            }
            else {            
                $this->response(false, apiMsg('unauthorize_access')); exit;
            }
        }elseif($_REQUEST['Is-Update']==2){
            // bypass authentication
        } 
    }
    
    function requireParameter($keys,$a2) {
        //print_r($a1); print_r($a2); die;
        
        $post2 = $post = array();
        foreach ($keys as $key => $value) {
            if (array_key_exists($value, $a2)) {
                $post[$value] = $a2[$value];
            }else{
                $post2[] = $value;
            }
        }   
        
        if(!empty($post2)){
            $res = implode(',', $post2);
            $this->response(false, apiMsg('key_required').$res); exit;
        }else{
            //$result=array_intersect($a1,$a2);
            return $post;
        }
    }
    
    
    public function response($status=true,$message,$other_option= array())
    {
        $response = array(
                        "success" => $status,	
                        "msg" => $message
                );	
        if(!empty($other_option)){
            foreach ($other_option as $key => $value) {
                    $response[$key] = $value;
            }
        }
        echo json_encode($response);
    }

    public function curl($headers,$fields){
        $url = 'https://fcm.googleapis.com/fcm/send';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);  
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);           
        if ($result == FALSE) {
                return false;
            die('Curl failed: ' . curl_error($ch));
        }
             curl_close($ch); 
             echo"<pre>";
             print_r($result);
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

        $this->curl($headers,$fields);
    }

    public function send_notification($tokens, $message)
    {			
        $fields = array(
                'registration_ids' => $tokens,
                'notification' => $message
            );	

        $headers = array(
            'Authorization:key = AAAAibOCUAY:APA91bG6TEZcH6FinqLE035dt21UOjUmTQuRXFg3pA9CFWe1B07g4PMHFuO0qVV-wPjGFx0aTdmBqPtDrKyElUbZ3OIVUiK3qUmcROKBhHLu3EU6zahpWfw2UjT9YlPgwKuLewolKnCm',
            'Content-Type: application/json'
        );

        $this->curl($headers,$fields);
    }
    
    function oneSignalPush($token,$message){
                
        $headers =  array(
                'Content-Type: application/json; charset=utf-8',
                'Authorization: Basic  YWUzMDI5NWEtNThkNi00ODQ5LTkyZTgtM2ViOGU1MTA1ZTI0e1b7fbf7-ba58-4a63-bc6f-cd97599bd8ec'
            );

//        $fields = array(
//              'app_id' => "e1b7fbf7-ba58-4a63-bc6f-cd97599bd8ec",
//              'included_segments' => array($token),
//              'data' => array("foo" => "bar"),
//              'large_icon' =>"ic_launcher_round.png",
//              'contents' => $message
//        );
        
        $fields = array(
                      'app_id' => "e1b7fbf7-ba58-4a63-bc6f-cd97599bd8ec",
                      'filters' => array(array("field" => "tag", "key" => "user_id", "relation" => "=", "value" => $token)),
                      'contents' => $message
              );

        $fields = json_encode($fields);
        print("\nJSON sent:\n");
        print($fields);
        

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications&quot");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);       

        $response = curl_exec($ch);
        curl_close($ch);

        //return $response;
        
        $return["allresponses"] = $response;
        $return = json_encode( $return);
        print("\n\nJSON received:\n");
        print($return);
        print("\n");
    }
    
    

    
    
    
}
