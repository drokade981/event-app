
<section class="section-bg bg-cover d-flex align-items-center justify-content-center">
  <div class="container">
    <div class="row">
      <div class="col-md-6 offset-md-3 wow fadeInUp">
        <?php echo $this->session->flashdata('msg'); ?>
        <form class="login-form position-relative" method="post" id="add-video" enctype="multipart/form-data" action="<?= base_url('users'); ?>">

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
            <input type="text" class="form-control" name="title">
          </div>
          <div class="form-group mt-4">
            <label class="control-label">Select category</label>
            <select class="form-control" name="category_id">
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
            <input type="file" class="custom-file-input" id="customFile" name="video" accept="video/*">
            <label class="custom-file-label" for="customFile"></label>
          </div>
          <div class="form-group mt-3">
            <label class="form-control-label">Video description</label>
            <textarea class="form-control" name="description" rows="3" placeholder=""></textarea>
          </div>
          <div class="col-md-12 text-right wow fadeInLeft">
            <div class="form-group">
              <button type="submit" class="btn btn-primary mt-4 submitBtn" > Upload <i class="fa fa-chevron-right"></i></button>
            </div>
          </div>
          <div class="form-icon position-absolute d-flex align-items-center justify-content-center rounded-circle text-white wow bounce"> <i class="fa fa-camera"></i> </div>
          <div class="reel-icon position-absolute"> <img src="<?php echo base_url('assets/front/img/reel.png'); ?>"> </div>
        </form>
      </div>
    </div>
  </div>
</section>
<!-- form -->

<script type="text/javascript">
  $(document).ready(function () {
    //  $("#post-video").on('submit', function(e){ 
    //     e.preventDefault();
    //     $.ajax({
    //         type: 'POST',
    //         url: base_url+'users/uploadVideo',
    //         data: new FormData(this),
    //         contentType: false,
    //         cache: false,
    //         processData:false,
    //         beforeSend: function(){
    //             $('.submitBtn').attr("disabled","disabled");
    //             $('#post-video').css("opacity",".5");
    //         },
    //         success: function(msg){
    //             $('.statusMsg').html('');
    //             if(msg == 'ok'){
    //                 $('#post-video')[0].reset();
    //                 $('.statusMsg').html('<span style="font-size:18px;color:#34A853">Form data submitted successfully.</span>');
    //             }else{
    //                 $('.statusMsg').html('<span style="font-size:18px;color:#EA4335">Some problem occurred, please try again.</span>');
    //             }
    //             $('#post-video').css("opacity","");
    //             $(".submitBtn").removeAttr("disabled");
    //         }
    //     });
    // });
    
    //file type validation
    $("#customFile").change(function() {
        var file = this.files[0];
        var imagefile = file.type;
        var match= ["video/mp4","video/3gpp","video/x-flv","video/x-msvideo"];
        if(!((imagefile==match[0]) || (imagefile==match[1]) || (imagefile==match[2]) || (imagefile==match[3]))){
            alert('Please select a valid video file (mp4/flv/3gp/avi).');
            $("#customFile").val('');
            return false;
        }
    });
  });
</script>
