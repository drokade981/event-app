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
function langMsgs($msg){
    $CI = & get_instance();
    $CI->lang->load('users');
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


function status($id) {
    switch ($id) {
        case 0:
            return 'Publish';
            break;
        case 1:
            return 'Draft';
            break;        
    }
}

function is_working($id) {
    switch ($id) {
        case 0:
            return 'No';
            break;
        case 1:
            return 'Yes';
            break;        
    }
}

function plan_type($id) {
    switch ($id) {
        case 0:
            return 'used';
            break;
        case 1:
            return 'new';
            break;        
    }
}

function type($id) {
    switch ($id) {
        case 0:
            return 'Percentage';
            break;
        case 1:
            return 'Fixed';
            break;        
    }
}

function allow_edit($id) {
    switch ($id) {
        case 0:
            return 'Not Allowed';
            break;
        case 1:
            return 'Allowed';
            break;        
    }
}

function userStatus($id) {
    switch ($id) {
        case 0:
            return 'Active';
            break;
        case 1:
            return 'Blocked';
            break;        
    }
}

function machine_type($id)
{
  switch ($id) {
      case 0:
          return 'new';
          break;
      case 1:
          return 'used';
          break;        
  }
}

function position($id)
{
  switch ($id) {
      case 1:
          return 'top';
          break;
      case 2:
          return 'after 10 ads';
          break;  
      case 3:
          return 'after 20 ads';
          break;       
  }
}

function ownership($id)
{
 switch ($id) {
      case 0:
          return 'New';
          break;
      case 1:
          return 'First Hand';
          break;
      case 2:
          return 'Second Hand';
          break;        
  }
}

function machine_status($id)
{
 switch ($id) {
      case 0:
          return 'draft';
          break;
      case 1:
          return 'approve';
          break;
      case 2:
          return 'rejected';
          break;
      case 3:
          return 'expired';
          break;
      case 4:
          return 'pending';
          break; 
      case 5:
          return 'expiring soon';
          break;         
  }
}

function getDateRange($date1,$date2,$format = 'Y-m-d') {
    $period = new DatePeriod(
        new DateTime(date($format, strtotime($date1))),
        new DateInterval('P1D'),
        new DateTime(date($format, strtotime($date2)))
   );
    
    $dates = array(); 
      
    // Variable that store the date interval 
    // of period 1 day 
//    $interval = new DateInterval('P1D'); 
//  
//    $realEnd = new DateTime($date2); 
//    $realEnd->add($interval); 
//  
//    $period = new DatePeriod(new DateTime($date1), $interval, $realEnd);
    
    foreach($period as $date) {                  
        $dates[] = $date->format($format);  
    }
    $dates[] = date($format, strtotime($date2)); // for adding given date
  
    return $dates; 
}

function dd($data)
{
   echo "<pre>"; print_r($data); die;
}

function last_query()
{
  $CI = & get_instance();
  echo $CI->db->last_query();
}

function show_validation_error()
{
  if(validation_errors()){
    $error = validation_errors();
    $error=str_ireplace('<p>','',$error);
    $error=str_ireplace('</p>','<br>',$error); 
    $msg = '<div class="alert-remove alert alert-danger alert-dismissible" role="alert"><div class="pull-left">' . $error . '</div>
      <div>
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
      </div>
      <div class="clearfix"></div>
    </div>
        <div class="clearfix"></div>';
      return $msg;
  }else{
    return false;
  }
}

function quotePriceMsg($name,$amount,$title="machine ad")
{
  $msg = '<strong>'.$name.'</strong> has quoted <strong>₹ '.$amount.'</strong> for your '.$title.'.';  
  return $msg;
}


function numberTowords($number) {
    $hyphen      = '-';
    $conjunction = ' and ';
    $separator   = ', ';
    $negative    = 'negative ';
    $decimal     = ' point ';
    $dictionary  = array(
        0                   => 'zero',
        1                   => 'one',
        2                   => 'two',
        3                   => 'three',
        4                   => 'four',
        5                   => 'five',
        6                   => 'six',
        7                   => 'seven',
        8                   => 'eight',
        9                   => 'nine',
        10                  => 'ten',
        11                  => 'eleven',
        12                  => 'twelve',
        13                  => 'thirteen',
        14                  => 'fourteen',
        15                  => 'fifteen',
        16                  => 'sixteen',
        17                  => 'seventeen',
        18                  => 'eighteen',
        19                  => 'nineteen',
        20                  => 'twenty',
        30                  => 'thirty',
        40                  => 'fourty',
        50                  => 'fifty',
        60                  => 'sixty',
        70                  => 'seventy',
        80                  => 'eighty',
        90                  => 'ninety',
        100                 => 'hundred',
        1000                => 'thousand',
        1000000             => 'million',
        1000000000          => 'billion',
        1000000000000       => 'trillion',
        1000000000000000    => 'quadrillion',
        1000000000000000000 => 'quintillion'
    );
    if (!is_numeric($number)) {
        return false;
    }
    if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
        // overflow
        trigger_error(
            'convert_number_to_words only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX,
            E_USER_WARNING
        );
        return false;
    }
    if ($number < 0) {
        return $negative . numberTowords(abs($number));
    }
    $string = $fraction = null;
    if (strpos($number, '.') !== false) {
        list($number, $fraction) = explode('.', $number);
    }
    switch (true) {
        case $number < 21:
            $string = $dictionary[$number];
            break;
        case $number < 100:
            $tens   = ((int) ($number / 10)) * 10;
            $units  = $number % 10;
            $string = $dictionary[$tens];
            if ($units) {
                $string .= $hyphen . $dictionary[$units];
            }
            break;
        case $number < 1000:
            $hundreds  = $number / 100;
            $remainder = $number % 100;
            $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
            if ($remainder) {
                $string .= $conjunction . numberTowords($remainder);
            }
            break;
        default:
            $baseUnit = pow(1000, floor(log($number, 1000)));
            $numBaseUnits = (int) ($number / $baseUnit);
            $remainder = $number % $baseUnit;
            $string = numberTowords($numBaseUnits) . ' ' . $dictionary[$baseUnit];
            if ($remainder) {
                $string .= $remainder < 100 ? $conjunction : $separator;
                $string .= numberTowords($remainder);
            }
            break;
    }
    if (null !== $fraction && is_numeric($fraction)) {
        $string .= $decimal;
        $words = array();
        foreach (str_split((string) $fraction) as $number) {
            $words[] = $dictionary[$number];
        }
        $string .= implode(' ', $words);
    }
    return $string;
}
