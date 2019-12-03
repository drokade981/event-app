<?php //echo phpinfo(); ?>
<section class="section-bg bg-cover d-flex align-items-center justify-content-center">
  <div class="container">
    <div class="row">
      <div class="col-md-6 offset-md-3 wow fadeInUp">
        <?php echo $this->session->flashdata('msg'); ?>
        <h3 id='msg' style="display: none">Please wait...</h3>
        <!--  -->

        <div id="filelist1">.</div>
        <form class="login-form position-relative" 
        id="add-video" enctype="multipart/form-data" action="#" >

          <header class="section-header wow fadeInDown">
            <h4 class="text-white mb-5">Upload New Video</h4>
          </header>
            <?php
              $csrf = array(
                  'name' => $this->security->get_csrf_token_name(),
                  'hash' => $this->security->get_csrf_hash()
              ); ?>
                
            <input type="hidden" name="<?= $csrf['name'];?>" value="<?= $csrf['hash'];?>" />
          <div class="form-group mt-3">
            <label class="control-label">Video title</label>
            <input type="text" class="form-control" name="title" id="title">
          </div>
          <div class="form-group mt-4">
            <label class="control-label">Select category</label>
            <select class="form-control" name="category_id" id="category_id">
              <option selected disabled>  </option>
              <?php if (!empty($categories)) {
                foreach ($categories as $key => $value) { ?>
                  <option value="<?= $value['id']; ?>"> <?= $value['category'] ?> </option>                  
                <?php }
              } ?>
            </select>
          </div>
          <label class="form-control-label mt-3">Upload video</label>
          <div class="form-group custom-file mb-4">
            <input type="file" class="custom-file-input form-control" id="customFile" name="video" accept="video/*">
            <label class="custom-file-label" id="=filelist" for="customFile">Your browser doesn't have Flashs, Silverlight or HTML5 support.</label>
            
          </div>
          <div class="form-group mt-3">
            <label class="form-control-label">Video description</label>
            <textarea class="form-control" id="description" name="description" rows="3" placeholder=""></textarea>
          </div>
          <div class="col-md-12 text-right wow fadeInLeft">
            <div class="form-group">              
              <input type="hidden" id="file_ext" name="file_ext" value="<?=substr( md5( rand(10,100) ) , 0 ,10 )?>">
              <button type="submit" id="submit" class="btn btn-primary mt-4 submitBtn" > Upload <i class="fa fa-chevron-right"></i></button>
            </div>
          </div>
          <div class="form-icon position-absolute d-flex align-items-center justify-content-center rounded-circle text-white wow bounce"> <i class="fa fa-camera"></i> </div>
          <div class="reel-icon position-absolute"> <img src="<?php echo base_url('assets/front/img/reel.png'); ?>"> </div>
        </form>

      </div>
    </div>
  </div>
</section>
<div class="loader" style="display: none;">
  <span id="progress">0%</span>
  <!-- <i class="fa fa-circle-o-notch fa-spin" style="font-size:240px"></i> -->
</div>
<!-- form -->

<script type="text/javascript">
  BASE_URL = "<?php echo base_url();?>"
</script>

<script src="<?=base_url();?>assets/front/js/plupload/plupload.full.min.js"></script>

<script>

  $(document).ready(function () {
    $("html, body").animate({ scrollTop: 0 }, "slow");
  });
  var security_csrf = '<?=$this->security->get_csrf_hash()?>';
  var datafile = new plupload.Uploader({
  runtimes : 'html5,flash,silverlight,html4',
  browse_button : 'customFile', // you can pass in id...
  container: document.getElementById('add-video'), // ... or DOM Element itself
  chunk_size: '3mb', 
  url : BASE_URL + 'users/upload3',
  max_retries: 3,
  max_file_count: 1,

  multipart_params: {
      <?= $this->security->get_csrf_token_name() ?> : security_csrf
  },
        
  //ADD FILE FILTERS HERE
  filters : {
      max_file_size : '5000mb',
      mime_types: [
      // {title : "Image files", extensions : "jpg,gif,png"},
      // {title : "Zip files", extensions : "zip"},
      {title : "Movie files", extensions : "mp4,flv,avi,mov,mkv"}
      ]
  }, 

  // Flash settings
  flash_swf_url : BASE_URL+'assets/front/js/plupload/Moxie.swf',

  // Silverlight settings
  silverlight_xap_url : BASE_URL+'assets/front/js/plupload/Moxie.xap',
   
  init: {
    PostInit: function() {
      // document.getElementById('filelist').innerHTML = ''; 
      $("label[for='customFile']").html(''); 
      // $('#submit').click(function (e) {
      //   e.preventDefault();
      //   datafile.start();
      //   return false;
      // });
      document.getElementById('submit').onclick = function() {
        // if($('#title').val() == "" || $('#category_id').val()== ""){
          //alert('Title and category is required');
        // }else{
        datafile.start();
        return false;          
        // }
      };
    },

    Browse: function(up) {
        // Called when file picker is clicked
        console.log('[Browse]'+up);
    },

    Refresh: function(up) {
        // Called when the position or dimensions of the picker change
        console.log('[Refresh]'+up);
    },

    StateChanged: function(up) {
        // Called when the state of the queue is changed
        console.log('[StateChanged]', up.state == plupload.STARTED ? "STARTED" : "STOPPED");
    },

    QueueChanged: function(up) {
        // Called when queue is changed by adding or removing files
        console.log('[QueueChanged]'+up);
    },

    BeforeUpload: function(up, file) {
        // Called right before the upload for a given file starts, can be used to cancel it if required
        console.log('[BeforeUpload]', 'File: ', file);
    },

    UploadProgress: function(up, file) {
        // Called while file is being uploaded
        console.log('[UploadProgress]', 'File:', file, "Total:", up.total);
    },

    FilesAdded: function(up, files) {
      plupload.each(files, function(file) {
        //document.getElementById('filelist').innerHTML += '<div id="' + file.id + '">' + file.name + ' (' + plupload.formatSize(file.size) + ') <b></b></div>';
        $("label[for='customFile']").html(file.name +'('+plupload.formatSize(file.size)+')' ); 
      });
    },

    UploadProgress: function(up, file) {
      $('.loader').show();
      // document.getElementById(file.id).getElementsByTagName('b')[0].innerHTML = '<span>' + file.percent + "%</span>";
      $('#progress').html(file.percent+'%');

    },
    FileUploaded: function(up, file, info) {
        // Called when file has finished uploading
        console.log('[FileUploaded] File:', file, "Info:", info);
    },
    ChunkUploaded: function(up, file, result) {
        response_data = JSON.parse(result.response);
        security_csrf = response_data.next_csrf;
        datafile.settings.multipart_params.<?=$this->security->get_csrf_token_name()?>=security_csrf;
        if(response_data.ok == 2){
          upload(response_data);
        }
    },
    // UploadComplete: function(up, files) {
    //     // Called when all files are either uploaded or failed
    //     console.log('[UploadComplete]'+files);
    // },
 
    Error: function(up, err) {
      //document.getElementById('console').innerHTML += "\nError #" + err.code + ": " + err.message;
      console.log('err code '+err.code+' message '+err.message);
      if(err.code == '-601'){
        alert('Please upload only mp4,flv,avi,mkv,mov file format');
      }
      if(err.code == '-200'){
        alert('Connection Error. Please upload again');
      }
    }
  }
});

datafile.init();

function upload(response) {
  console.log(response);
  var csrf = '<?= $this->security->get_csrf_token_name() ?>',
  title = $('#title').val(),
  description = $('#description').val(),
  category_id = $('#category_id').val();

  $.ajax({
    url: BASE_URL+"users/add_video",
    type: "POST",
    data: {[csrf] : response.next_csrf, video : response.file, title: title, description: description, category_id : category_id },
    success: function(result){
      console.log(result);
        $(".loader").fadeOut("slow");
        location.reload();
    }
  });
}
</script>

<style>
  .loader {
    position: fixed;
    left: 0px;
    top: 0px;
    width: 100%;
    height: 100%;
    z-index: 9999;
    /*left: 45%;
    top: 45%;*/
    opacity: 0.3;
    background: url('<?= base_url('assets/front/img/6.gif'); ?>') 50% 50% no-repeat rgb(249,249,249);
    color: #fff;
}
#progress {
    position: relative;
    left: auto;right: auto;
    color: #000;
    font-weight: bold;
    font-size: larger;
    display: flex;
    align-items: center;justify-content: center;
    width: 100%;
    height: 100%;

}
</style>
