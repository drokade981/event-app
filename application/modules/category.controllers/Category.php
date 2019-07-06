<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Category extends Admin_Controller {

    public function __construct() {       
        parent:: __construct();
        $this->checkAuth();
        $this->load->helper('common');
    }

    public function index()
    {    
        $post = $this->security->xss_clean($_POST);

        $data['category'] = $this->common->getData('categories'); 
        if($post){
            $post2 = $this->common->getField('categories',$post);  
           
            $data['categories'] = $this->common->getData('categories',$post2);
          
        } else{
            $data['categories'] = $this->common->getData('categories'); 
        }
         $links = '<li><a href="'.base_url("category").'">Machine Category</a> <span class="bread-slash">/</span></li>
            <li><a href="javascript:void(0)">Machine Category List</a></li>';
        $this->frontHtml($links,'category-list',$data);
    }
   
    function add() {      

        if($this->form_validation->run('add-category') == false){           
            $data['active'] = 'category';
           
          $links = '<li><a href="'.base_url("category").'">Machine Category</a> <span class="bread-slash">/</span></li>
                <li><a href="javascript:void(0)">Add New Machine Category</a></li>';
            $this->frontHtml($links,'add-category',$data);
        }else{
            $post = $this->security->xss_clean($_POST); 
            $post['image'] = '';
            if (!empty($_FILES)) {
                $image = $this->common->do_upload('image', 'assets/uploads/category');
                if (!empty($image['upload_data'])) {
                    $post['image'] = 'assets/uploads/category/' . $image['upload_data']['file_name'];
                    $this->resizeImage($post['image']);
                }
            } 
            $post2 = $this->common->getField('categories',$post);

            $result = $this->common->insertData('categories',$post2);
           
            if($result){
               $this->flashMsg('success',langMsgs('add_category_succ'));
               redirect(base_url('category'));
            }
            else{                
                $this->flashMsg('danger',langMsgs('error'));
                redirect(base_url('category/add'));
            }
        }            
    }
    
    function edit($category_id="") { 
        if($category_id == ""){
            redirect(base_url('category'));
        } 
        $rules = removeKey('add-category',array('category')); 
                 
        $this->form_validation->set_rules($rules);

        if($this->form_validation->run('add-category') == false){
            try{ 
                $data['category'] = $this->common->getData('categories',array('id'=>  base64_decode($category_id)),array('single'));            
                $data['active'] = 'activity';
              
               $links = '<li><a href="'.base_url("category").'">Machine Category</a> <span class="bread-slash">/</span></li>
                <li><a href="javascript:void(0)">Edit Machine Category</a></li>';

                $this->frontHtml($links,'add-category',$data);                
            } catch (Exception $exc) {
                echo $exc->getTraceAsString(); die;
            }

        }else{   

            $post = $this->security->xss_clean($_POST);
            
            $post['modified_at']=date('Y-m-d H:i:s');

            if($_FILES['image']['name'] != "")
            {
                $image = $this->common->do_upload('image', 'assets/uploads/category');
             
                if (!empty($image['upload_data'])) {
                    $post['image'] = 'assets/uploads/category/' . $image['upload_data']['file_name'];
                    $this->resizeImage($post['image']);
                    if($post['old_image'] != "" && file_exists($post['old_image'])){
                        unlink($post['old_image']);
                    }                         
                }
            }    
            $post2 = $this->common->getField('categories',$post);

            $result = $this->common->updateData('categories',$post2,array('id'=>base64_decode($category_id)));
           
            if($result){
               $this->flashMsg('success',langMsgs('update_category_succ'));
               redirect(base_url('category'));
            }
            else{                
                $this->flashMsg('danger',langMsgs('error'));
                redirect(base_url('category/edit/'.$category_id));
            }      
           
        }
    }

    public function delete() { 
        $check = $this->common->getData('machines',array('category_id'=> base64_decode($_GET['id'])));
         $check1 = $this->common->getData('user_interest',array('category_id'=> base64_decode($_GET['id'])));
        $status = 0;
        if($check||$check1){
            $status = 1;
        }else{
            $category = $this->common->getData('categories',array('id'=> base64_decode(($_GET['id']))),array('single'));
            if($category){
                if ($category['image'] != "" && file_exists($category['image'])) {
                    unlink($category['image']);
                }
            }
            $result = $this->common->deleteData('categories',array('id'=>base64_decode($_GET['id'])));            
            if($result){
                $status = 2;
            }else{
                $status = 3;
            }
        }  
        echo $status;  die;
    }
  }
