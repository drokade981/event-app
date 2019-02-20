<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


function dateDiff($date)
{
   $mydate= date("Y-m-d H:i:s");
   $theDiff="";
   //echo $mydate;//2014-06-06 21:35:55
   $datetime1 = date_create($date);
   $datetime2 = date_create($mydate);
   $interval = date_diff($datetime1, $datetime2);
   //echo $interval->format('%s Seconds %i Minutes %h Hours %d days %m Months %y Year    Ago')."<br>";
   $min=$interval->format('%i');
   $sec=$interval->format('%s');
   $hour=$interval->format('%h');
   $mon=$interval->format('%m');
   $day=$interval->format('%d');
   $year=$interval->format('%y');
   if($interval->format('%i%h%d%m%y')=="00000")
   {
    //echo $interval->format('%i%h%d%m%y')."<br>";
      return $sec." Sec ago";
   } 

   else if($interval->format('%h%d%m%y')=="0000"){
      return $min." Min ago";
   }
   else if($interval->format('%d%m%y')=="000"){
      return $hour." Hours ago";
   }
   else if($interval->format('%m%y')=="00"){
      return $day." Days ago";
   }
   else if($interval->format('%y')=="0"){
      return $mon." Months ago";
   }
   else{
      return $year." Years ago";
   }
}

if(!function_exists('multid'))
{
   function multid_sort($arr, $index) {
      $b = array();
      $c = array();
      foreach ($arr as $key => $value) {
         $b[$key] = $value[$index];
      }
      asort($b);
      foreach ($b as $key => $value) {
         $c[] = $arr[$key];
      }

      return $c;
   }
}

function distance($lat1, $lon1, $lat2, $lon2 ) {

   $theta = $lon1 - $lon2;
   $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
   $dist = acos($dist);
   $dist = rad2deg($dist);
   $miles = $dist * 60 * 1.1515 * 1.609344;
   return $miles;  
}

function langMsg($msg){
    $CI = & get_instance();
    $CI->lang->load('user');
    return $CI->lang->line($msg);
}

function apiMsg($msg){
    $CI = & get_instance();
    $CI->lang->load('api');
    return $CI->lang->line($msg);
}

function removeKey($validation_name,$values = array()) {
    $CI = &get_instance();
    $CI->config->load('form_validation', TRUE);
    $validation = $CI->config->item('form_validation');
    $array = $validation[$validation_name];
    
    if(is_array($values)){
        foreach($values as $value){
            foreach($array as $subKey => $subArray){
                if($subArray['field'] == $value){
                     unset($array[$subKey]);
                }
            }   
        }        
    }else{
        foreach($array as $subKey => $subArray){
            if($subArray['field'] == $values){
                 unset($array[$subKey]);
            }
       }       
    } 
    return $array;
}

function addKey($validation_name,$array) {
    $CI = &get_instance();
    $CI->config->load('form_validation', TRUE);
    $validation = $CI->config->item('form_validation');
    if(is_array($validation_name)){
        return array_merge($validation_name,$array);
    }else{
        $array1 = $validation[$validation_name];        
        return array_merge($array1,$array);
    }
}

function generateRandomString($length = '8') {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$&*()_';
    return substr(str_shuffle(str_repeat($characters, ceil($length/strlen($characters)) )),1,$length);   
}

    function generateToken($length=8)
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
function base64Img($path){
    $type = pathinfo($path, PATHINFO_EXTENSION);
    $data = file_get_contents($path);
    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
    // Display the output 
    return $base64; 
}


function removeDir($path) {
    $files = glob($path . '/*');
    foreach ($files as $file) {
            is_dir($file) ? removeDir($file) : unlink($file);
    }   
    if(rmdir($path))
    {
      return true;
    } 
    return false;            
}

function social_name($id) {
    switch ($id) {
        case 1:
            return 'Facebook';
            break;
        case 1:
            return 'Instagram';
            break;
        case 1:
            return 'Twitter';
            break;
        case 1:
            return 'LinkedIn';
            break;
    }
}
