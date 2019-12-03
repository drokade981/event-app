<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends User_Controller {

	public function __construct()
	{
		parent:: __construct();
		$this->user_id = $this->checkAuth();		
		$this->load->helper('common');
		//$this->data = json_decode(file_get_contents("php://input"));
	}		

	public function index()
	{  
		$data['categories'] = $this->common->getData('categories',[],['order_by'=>'category','order_direction'=>'asc']);
		$this->Html('User', 'index2',$data);		
	}

	public function userDetail($user_id)
	{
		echo phpinfo(); die;
		$data['user'] = $this->common->getData('users',array('id'=> base64_decode($user_id),'type'=> 0),array('single'));
		if ($data['user']) {
			$joins = [
                  [
                      'join_table' => 'UF',
                      'join_field' => 'follower_id',
                      'other_table'=> 'users as U',
                      'other_field'=> 'id'
                  ]
              ];
      $options =  [
                  'where' => ['UF.user_id' => base64_decode($user_id)],
                  'field' => 'U.id,U.name,U.image,U.username'
              ];
       
      $data['followers'] =  $this->common->multiJoinTable('user_follow as UF',$joins,$options);
      $joins1 = [
                  [
                      'join_table' => 'UF',
                      'join_field' => 'user_id',
                      'other_table'=> 'users as U',
                      'other_field'=> 'id'
                  ]
              ];
      $options1 =  [
                  'where' => ['UF.follower_id' => base64_decode($user_id)],
                  'field' => 'U.id,U.name,U.image,U.username'
              ];
    	$data['following'] =  $this->common->multiJoinTable('user_follow as UF',$joins,$options);
    }
      $this->adminHtml('User List', 'user-detail',$data);
	}

  public function index3()
  {
    if (empty($_POST) && empty($_FILES)) {
      $this->load->view('index3');
    }else{
      $this->load->library("PluploadHandler");
      $this->pluploadhandler->no_cache_headers();
      $this->pluploadhandler->cors_headers();
      if (!$this->pluploadhandler->handle(array(
        'target_dir' => '/tmp/',
            'allow_extensions' => 'mp4,flv,mov'
            ))) {
                die(json_encode(array(
                'OK' => 0,
                'error' => array(
                'code' => $this->pluploadhandler->get_error_code(),
                'message' => $this->pluploadhandler->get_error_message()
              )
            )));
        } else {
          die(json_encode(array('OK' => 1, 'next_csrf'=>$this->security->get_csrf_hash())));
      }
    }
  }

  public function upload3()
    {
    // 5 minutes execution time
    @set_time_limit(5 * 60);
    // Uncomment this one to fake upload time
    // usleep(5000);
    // https://www.plupload.com/punbb/viewtopic.php?id=14422
    // Settings

    $targetDir =  "assets/uploads/videos/".date('Y-m-d');
    //$targetDir = 'uploads';
    $cleanupTargetDir = true; // Remove old files
    $maxFileAge = 5 * 3600; // Temp file age in seconds


    // Create target dir
    if (!file_exists($targetDir)) {
      @mkdir($targetDir);
    }

    // Get a file name
    if (isset($_REQUEST["name"])) {
      $fileName = $_REQUEST["name"];
    } elseif (!empty($_FILES)) {
      $fileName = $_FILES["file"]["name"];
    } else {
      $fileName = uniqid("file_");
    }

    $filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

    // Chunking might be enabled
    $chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
    $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;

    // Remove old temp files  
    if ($cleanupTargetDir) {
      if (!is_dir($targetDir) || !$dir = opendir($targetDir)) {
        die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');
      }

      while (($file = readdir($dir)) !== false) {
        $tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;

        // If temp file is current file proceed to the next
        if ($tmpfilePath == "{$filePath}.part") {
          continue;
        }

        // Remove temp file if it is older than the max age and is not the current file
        if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - $maxFileAge)) {
          @unlink($tmpfilePath);
        }
      }
      closedir($dir);
    } 


    // Open temp file
    if (!$out = @fopen("{$filePath}.part", $chunks ? "ab" : "wb")) {
      die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
    }

    if (!empty($_FILES)) {
      if ($_FILES["file"]["error"] || !is_uploaded_file($_FILES["file"]["tmp_name"])) {
        die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
      }

      // Read binary input stream and append it to temp file
      if (!$in = @fopen($_FILES["file"]["tmp_name"], "rb")) {
        die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
      }
    } else {  
      if (!$in = @fopen("php://input", "rb")) {
        die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
      }
    }

    while ($buff = fread($in, 4096)) {
      fwrite($out, $buff);
    }

    @fclose($out);
    @fclose($in);

    // Check if file has been uploaded
    if (!$chunks || $chunk == $chunks - 1) {
      // Strip the temp .part suffix off 
      rename("{$filePath}.part", $filePath);
      // rename("{$filePath}.part", generateToken());
      die(json_encode(array('ok'=> '2', 'file'=> $filePath, 'next_csrf'=>$this->security->get_csrf_hash())));
    }

    // Return Success JSON-RPC response
    die(json_encode(array('OK' => 1, 'next_csrf'=>$this->security->get_csrf_hash())));
  }

  public function add_video()
  {
    $channel = $this->common->getData('channels',['user_id'=> $this->user_id],['field'=>'id','single']);
    $_POST['channel_id'] = $channel['id'];
    
    date_default_timezone_set('Asia/Kolkata');
    if($_POST['video'] != ""){
      $ext = pathinfo($_POST['video'], PATHINFO_EXTENSION);
      $path = 'assets/uploads/videos/'.$channel['id'].'/';
      if(!file_exists($path)){
          mkdir($path,0777,true);
        }
      $video = $path.generateToken().'.'.$ext;

      rename($_POST['video'], $video);
      $_POST['video'] = $video;
      $_POST['ext'] = $ext;
      $_POST['created_at'] = date('Y-m-d H:i:s');
    }
    
    $post = $this->common->getField('channel_video',$_POST);
    $result = $this->common->insertData('channel_video',$post);
    if ($result) {
      $days_ago = date('Y-m-d', strtotime('-5 days', strtotime(date('Y-m-d'))));
      removeDir('assets/uploads/videos/'.$days_ago);
      $video_id = $this->db->insert_id();
      $subscriber = $this->common->pluck('subscribers',array('channel_id'=>$post['channel_id']),'user_id');      
      $user = $this->common->getData('users',['id'=> $this->user_id],['single']);
     
      $notification = [];
      if (!empty($subscriber)) {
        $device_tokens = $this->common->pluck('users',[],'device_token',['where_in'=>$subscriber,'colname'=>'id','field'=>'device_token']);
        foreach ($subscriber as $key => $value) {
          $notification[] = $notification1 = 
            [
              'title'     => 'New video posted',
              'user_id'   => $value,
              'corresponding_id' => $video_id,
              'message'   => $user['name'].' added new video',
              'type'      => 3
            ];
          if($device_tokens[$key] != "")   
            $this->send_notification([$device_tokens[$key]],$notification1);
        }
        $this->db->insert_batch('notification',$notification);
      }
          
      $this->flashMsg('success','Video'.langMsg('uploaded_succ'));
      echo 1; exit();
    } else {
      $this->flashMsg('danger', langMsg('error'));
      echo 0; exit();
    }
  }
}
