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

    
}
