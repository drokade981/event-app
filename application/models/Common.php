<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Common extends CI_Model {

    function __construct() {
        parent:: __construct();
    }

    //---------------- default functions -----------------//

    public function getData($table, $where = "", $options = array()) {
        if (isset($options['field'])) {
            $this->db->select($options['field']);
        }

        if ($where != "") {
            $this->db->where($where);
        }
        if (isset($options['where_in']) && !empty($options['where_in'])) {
            $this->db->where_in($options['colname'], $options['where_in']);
        }
        if (isset($options['where_in']) && empty($options['where_in'])) {
            return false;
        }
        if (isset($options['having']) && !empty($options['having'])) {
            $this->db->having($options['having']); // 'user_id = 45'
        }
        
        if (isset($options['order_by']) && isset($options['order_direction'])) {
            $this->db->order_by($options['order_by'], $options['order_direction']);
        } elseif (isset($options['order_by'])) {
            $this->db->order_by($options['order_by'], 'desc');
        } else{
            $this->db->order_by('id','desc');
        }

        if (isset($options['group_by'])) {
            $this->db->group_by($options['group_by']);
        }
        if (isset($options['limit']) && isset($options['offset'])) {
            $this->db->limit($options['limit'], $options['offset']);
        } elseif (isset($options['limit'])) {
            $this->db->limit($options['limit']);
        }

        $query = $this->db->get($table);
        $result = $query->result_array();

        if (!empty($options) && in_array("count", $options,true)) {            
            return count($result);
        }
        if ($result) {
            if (!empty($options) && in_array('single', $options,true)) {
                return $result[0];
            } else {
                return $result;
            }
        } else {
            if (!empty($options) && in_array('api', $options,true)) {
                return 'NA';
            }
            return false;
        }
    }

    public function getField($table, $data) {
        $post = array();
        $fields = $this->db->list_fields($table);
        
        foreach ($data as $key => $value) {
            if (in_array($key, $fields)) {
                $post[$key] = $value;
            }
        }
        return $post;
    }

    public function insertData($table, $data) {
        return $this->db->insert($table, $data);
    }

    public function updateData($table, $data, $where) {
        $this->db->update($table, $data, $where);
        if ($this->db->affected_rows() > 0) {
            return true;
        }else{
            return false;
        }
    }

    public function deleteData($table, $where) {
        return $this->db->delete($table, $where);
    }
    
    function pluck($table,$where=array(), $field){
        if($field != 'all') {
            $this->db->select($field);
        }        
        $this->db->where($where);
        $query = $this->db->get($table);
        $result = $query->result_array();
        $values = [];        
        if($result){            
            foreach ($result as $value){ 
               $values[] = $value[$field]; 
            }
        }
        return $values;
    }


    public function whereIn($table, $colname, $in, $where = array()) {
        $this->db->where($where);
        $search = "FIND_IN_SET('" . $in . "', $colname)";
        $this->db->where($search);
        $query = $this->db->get($table);
        $result = $query->result_array();
        if ($result) {
            return $result[0];
        } else {
            return false;
        }
    }

    public function getLike($table, $query, $column, $options = array()) {
        if (isset($options['field'])) {
            $this->db->select($options['field']);
        }
        if (is_array($column)) {
            foreach ($column as $key => $value) {
                if ($key == 0) {
                    $this->db->like($value,$query,'after');
                }else{
                    $this->db->or_like($value,$query,'after');
                }
            }
        }else{
            $this->db->like($column,$query,'both');
        }
        
        $this->db->from($table);
        $res = $this->db->get()->result_array();
        if (!empty($options) && in_array('count', $options,true)) {
            return count($res);
        }
        if ($res) {
            return $res;
        } else {
            if (isset($options) && in_array('api', $options,true)) {
                return array();
            }
            return false;
        }
    }

    public function arrayToName($table, $field, $array) {
        foreach ($array as $value) {
            $name[] = $this->getData($table, array('id' => $value), array('field' => $field, 'single'));
        }
        if (!empty($name)) {
            foreach ($name as $key => $value) {
                $name1[] = $value[$field];
            }
            return implode(', ', $name1);
        } else {
            return false;
        }
    }

    public function sendMail($to, $subject, $message, $options = array()) {
        $config = array(
            'mailtype' => 'html',
            'charset' => 'utf-8',
            'priority' => '1'
        );

//        $config = Array(
//            'protocol' => 'smtp',
//            'smtp_host' => 'ssl://smtp.googlemail.com',
//            'smtp_port' => 465,
//            'smtp_user' => 'nlivenindore@gmail.com',
//            'smtp_pass' => 'android@123',
//            'mailtype' => 'html',
//            'charset' => 'utf-8',
//            'charset' => 'iso-8859-1'
//        );
        //charset : iso-8859-1
        $this->load->library('email');
        $this->email->initialize($config);
        if (isset($options['fromEmail']) && isset($options['fromName'])) {
            $this->email->from($options['fromEmail'], $options['fromName']);
        } else {
            $this->email->from('info@upm.com', 'Used Plant & Machinery');
        }
        $this->email->to($to);
        if (isset($options['replyToEmail']) && isset($options['replyToName'])) {
            $this->email->reply_to($options['replyToEmail'], $options['replyToName']);
        }
        $this->email->subject('UPM 365 : '.$subject);
        $this->email->message($message);
        //return $this->email;
        if ($this->email->send()) {
            return true;
        } else {
            return false;
        }
    }

    public function do_upload($file, $path) {
        $config['upload_path'] = $path;
        $config['allowed_types'] = '*';
        $config['encrypt_name'] = true;
        // $config['max_size']             = 100;
        // $config['max_width']            = 1024;
        // $config['max_height']           = 768;

        $this->load->library('upload');
        $this->upload->initialize($config);

        if (!$this->upload->do_upload($file)) {
            $error = array('error' => $this->upload->display_errors());
            return $error;
        } else {
            $data = array('upload_data' => $this->upload->data());
            return $data;
        }
    }

    public function multi_upload($file,$path,$option = array()) {
        $config = array();
        $config['upload_path'] = $path; // upload path eg. - './resources/images/products/';
        $config['allowed_types'] = '*';
        $config['encrypt_name'] = true;
        //$config['max_size']      = '0';
        $config['overwrite'] = FALSE;
        if (!empty($option)) {
            foreach ($option as $key => $value) {
                $config[$key] = $value;
            }
        }
        
        $this->load->library('upload');
        $this->upload->initialize($config);
        $dataInfo = array();
        $files = $_FILES;


        foreach ($files[$file]['name'] as $key => $image) {

            $_FILES[$file]['name'] = $files[$file]['name'][$key];
            $_FILES[$file]['type'] = $files[$file]['type'][$key];
            $_FILES[$file]['tmp_name'] = $files[$file]['tmp_name'][$key];
            $_FILES[$file]['error'] = $files[$file]['error'][$key];
            $_FILES[$file]['size'] = $files[$file]['size'][$key];

            $this->upload->initialize($config);

            if ($this->upload->do_upload($file)) {
                $dataInfo[] = $this->upload->data();
            } else {
                $dataInfo['error'] = $this->upload->display_errors();
            }
        }
        if (!empty($dataInfo)) {
            return $dataInfo;
        } else {
            return false;
        }
    }

    //---------------- default functions close -----------------//	
    //---------------- custom functions -----------------//
    public function multi_m_upload($file, $path) {    // for array of array
        $config = array();
        $config['upload_path'] = $path; // upload path eg. - './resources/images/products/';
        $config['allowed_types'] = '*';
        $config['encrypt_name'] = true;
        //$config['max_size']      = '0';
        $config['overwrite'] = FALSE;
        $this->load->library('upload', $config);
        $dataInfo = array();
        $files = $_FILES[$file];      
        
        foreach ($files['name'] as $key => $value) {
            $m_file = array(); $dataInfo[$key] = array();
            foreach($value as $key1 => $value1){
                if(!empty($files['name'][$key][$key1])){
                    $_FILES[$file]['name'] = $files['name'][$key][$key1];
                    $_FILES[$file]['type'] = $files['type'][$key][$key1];
                    $_FILES[$file]['tmp_name'] = $files['tmp_name'][$key][$key1];
                    $_FILES[$file]['error'] = $files['error'][$key][$key1];
                    $_FILES[$file]['size'] = $files['size'][$key][$key1];
                
                    $this->upload->initialize($config);               

                    if ($this->upload->do_upload($file)) {
                        $dataInfo[$key][] = $this->upload->data();
                    } else {
                        return $this->upload->display_errors();
                    }
                }else{
                    $dataInfo[$key][] = "";
                }
            }
        }
        if (!empty($dataInfo)) {
            return $dataInfo;
        } else {
            return false;
        }
    }
    
    
    function multiJoinTable($table,$joins,$options=array()) {
        
        if (isset($options['field'])) {
            $this->db->select($options['field']);
        }
        
        $this->db->from($table);
        
        if(isset($joins)){
            foreach ($joins as $key => $value) {
                
                $html1 = $value['other_table'];
                $html2 = $value['other_table'].".".$value['other_field']." = ".$value['join_table'].".".$value['join_field'];
                $value['join_type'] = isset($value['join_type'])? $value['join_type']:'left';  

                $this->db->join($html1,$html2,$value['join_type']);                
            }
        }
        
        if (isset($options['where'])) {
            $this->db->where($options['where']);
        }
        if (isset($options['where_in']) && !empty($options['where_in'])) {
            $this->db->where_in($options['colname'], $options['where_in']);
        }
        if (isset($options['having']) && !empty($options['having'])) {
            $this->db->having($options['having']); // 'user_id = 45'
        }

        if (isset($options['order_by']) && isset($options['order_direction'])) {
            $this->db->order_by($options['order_by'], $options['order_direction']);
        } elseif (isset($options['order_by'])) {
            $this->db->order_by($options['order_by'], 'desc');
        }

        if (isset($options['limit']) && isset($options['offset'])) {
            $this->db->limit($options['limit'], $options['offset']);
        } elseif (isset($options['limit'])) {
            $this->db->limit($options['limit']);
        }

        if (isset($options['group_by'])) {
            $this->db->group_by($options['group_by']);
        }
        
        $query = $this->db->get();
        
        if (isset($options) && in_array('single', $options,true)) {            
            return $query->row_array();
        }

        $result = $query->result_array();
        if($result){
            return $result;
        }else{
            return false;
        }
    }
    //---------------- custom functions end -----------------//

    public function oneToManay($table1,$table2,$options)
    {
        // eg: $options = ['where1' => array('id'=>1),'where2'=> array('id'=>1),'field_match'=>'image','field1'=>*,'field2'=>'id,name.image'];
        $data = $this->getData($table1,$options['where1'],array('single','field'=>$options['field1']??'*'));

        $data1 = $this->getData($table2,$options['where2'],array('field'=>$options['field2']??'*'));
        $data[$options['field_match']] = $data1;
        return $data;
    }

    public function getMachines($where,$options=array())
    {   
        $filter = $limit = $sort = "";
        if (!empty($options)) {
            if($options['category'] == 'all'){
                $filter .= '';
            }else{
                $category = explode(',', $options['category']);
                $filter .= '(';
                foreach ($category as $key => $value) {

                    if($key != count($category)-1){
                        $filter .= "M.category_id = ".$value." or ";
                    }else{
                        $filter .= "M.category_id = ".$value."";
                    }
                }
                $filter .= ')';
            }
            
            if (array_key_exists("user_id", $options)) {
                $filter .= "M.user_id = ".$options['user_id'];
            }
            
            if (isset($options['limit']) && isset($options['offset'])) {
                $limit .= ' limit '.$options['limit'].' offset '.$options['offset'];
            }           

            if ($options['sort_by'] == 'all') {
                $sort = ' ORDER BY distance asc';
            }
            if($options['sort_by'] == 0){
                $sort = ' ORDER BY M.id desc';
            }
            if ($options['sort_by'] == 1) {
                $sort = ' ORDER BY M.id desc';
            }       
        }

        if ($filter != "") {
            $filter = ' and '.$filter.' ';
        }

        $query = "SELECT
                M.*,U.name,U.id as user_id,P.name as plan,P.allow_edit ";
        if (isset($where['latitude']) && $where['latitude'] != "" && $where['longitude'] != "") {
            // for mile 3959 and for kilometer 6371

            $query .= ",
                (
                    6371 * acos (
                      cos ( radians(".$where['latitude'].") )
                      * cos( radians( latitude ) )
                      * cos( radians( longitude ) - radians(".$where['longitude'].") )
                      + sin ( radians(".$where['latitude'].") )
                      * sin( radians( latitude ) )
                    )
                ) AS distance ";
        }

        $query .= "FROM machines as M
                left join plans as P on P.id = M.plan_id
                left join mst_country as C on M.country_id = C.id
                left join users as U on U.id = M.user_id where";

        if (isset($options) && array_key_exists('user_id', $options)) {
            // $query = preg_replace('/\W\w+\s*(\W*)$/', '$1', $query);

            // $query .= ' M.user_id = '.$options['user_id'];

        }else{
            $query .= " approve = 1 and M.status = 0 and sold = 0 and expire_date >= CURDATE() ";
        }
        // if (isset($where['M.user_id'])) {
        //     $query .= ' and M.user_id = '.$where['M.user_id'].' ';
        // }
        if (isset($options) && array_key_exists('machine_id', $options)) {
            $query .= " and M.id = ".$options['machine_id']." ";
        }
        if (isset($options) && array_key_exists('search', $options) && $options['search'] != "") {
            $query .= " and M.title like '%".$options['search']."%' ";
        }
        if (isset($options) && array_key_exists('city', $options) && $options['city'] != "" && $options['sort_by'] == 2) {
            $query .= " and M.city = '".$options['city']."' ";
        }
        if (isset($options) && array_key_exists('state', $options) && $options['state'] != "" && $options['sort_by'] == 3) {
            $query .= " and M.state = '".$options['state']."' ";
        }
        if (isset($options) && array_key_exists('country_id', $options) && $options['country_id'] != "" && $options['sort_by'] == 4) {
            $query .= " and M.country_id = '".$options['country_id']."' ";
        }

        $having = "";
        if (isset($options) && array_key_exists('having', $options)) {
            $having .= ' having '.$options['having'].' ';
        }
        // echo "query ".$query.'<br>';
        // echo "filer ".$filter.'<br>';
        // echo "sort ".$sort.'<br>';
        // echo "limit ".$limit.'<br>';

        $query .= $filter.$having.$sort.$limit;

        // print_r($options);
        //echo $query; die;
        $result = $this->db->query($query);        
            //  HAVING distance < ".$distance."

        if($result){
            $result1 = $result->result_array();
            foreach ($result1 as $key => $value) {
                $result1[$key]['image'] = $this->oneToManay('machines','machine_images',['where1' => array('id'=>$value['id']),'where2'=> array('machine_id'=>$value['id']),'field_match'=>'image','field1'=>'id','field2'=>'id,image']);
            }
            return $result1;
        }else{
            return false;
        }
    }

    public function my_machines($user_id = "",$where = [],$options= array())
    {
        $query = $this->db->select('M.*,U.name,U.id as user_id,P.name as plan,P.allow_edit,M.status as status')
        ->from('machines as M')
        ->join('plans as P','P.id = M.plan_id','left')
        ->join('users as U','U.id = M.user_id','left');
        
        if ($user_id != "") {
            $this->db->where(array('M.user_id'=> $user_id));            
        }else{
            $this->db->where(array('M.approve'=> 1,'M.status' => 0,'expire_date >= ' => CURDATE()));
        }
        if ($where != "") {
            $this->db->where($where);
        }

        if (isset($options['start_date']) && isset($options['end_date'])) {
            $this->db->where('M.created_at >= ',date('Y-m-d 00:00:00',strtotime($options['start_date'])));
            $this->db->where('M.created_at <= ',date('Y-m-d 23:59:59',strtotime($options['end_date'])));
        }

        if (isset($options['sold']) ) {
            $this->db->where('sold',$options['sold']);
        }
 
        if (isset($options['limit']) && isset($options['offset'])) {
            $query = $this->db->limit($options['limit'],$options['offset']);
        }
              
        $this->db->order_by('id','desc');
        $query = $this->db->get();
        $result = $query->result_array();
        
        if($result){            
            foreach ($result as $key => $value) {
                $result[$key]['image'] = $this->oneToManay('machines','machine_images',['where1' => array('id'=>$value['id']),'where2'=> array('machine_id'=>$value['id']),'field_match'=>'image','field1'=>'id','field2'=>'id,image']);
            }
            return $result;
        }else{
            return false;
        }
    }

    public function userDetail($user_id)
    {
        $query = $this->db->select('U.*,country_name')
        ->from('users as U')
        ->join('mst_country as C','U.country_id = C.id','left')
        ->where('U.id',$user_id)
        ->get();
        $result = $query->row_array();
        
        if ($result) {
            $result['ads_count'] = $this->getData('machines',array('user_id'=>$user_id,'status'=>0),array('count'));
            $category = $this->pluck('user_interest',array('user_id'=> $result['id']),'category_id');
            $result['category'] = $this->arrayToName('categories','category',$category);
            return $result;
        }else{
            return false;
        }
    }

    public function payment_filter($user_id,$where = array())
    {
        $this->db->select('PH.*,M.title,P.name')
        ->from('payment_history as PH')
        ->join('machines as M','PH.machine_id = M.id','left')
        ->join('plans as P','M.plan_id = P.id','left')
        ->where('PH.user_id',$user_id);

        if (isset($where['start_date'])) {
            $this->db->where('payment_date <= ',$where['start_date']);
        }
        if (isset($where['end_date'])) {
            $this->db->where('payment_date >= ',$where['end_date']);
        }
        if(isset($where['plan_id']) && $where['plan_id'] != "") {
            $this->db->where('plan_id',$where['paln_id']);
        }
        if (isset($where['title']) && $where['title'] != "") {
            // $this->db->like('')
        }
        $query = $this->db->get();
        $result = $query->result_array();
        if ($result) {
            return $result;
        }else{
            return false;
        }
    }

    public function machinesDetail($machine_id,$options=array())
    {   
        // for mile 3959 and for kilometer 6371

        $filter = $limit = $sort = "";

        $query = "SELECT
                M.*,U.name,U.id as user_id,P.name as plan,P.allow_edit,C1.category ";

        $query .= "FROM machines as M
                left join plans as P on P.id = M.plan_id
                left join mst_country as C on M.country_id = C.id
                left join users as U on U.id = M.user_id 
                left join categories as C1 on C1.id = M.category_id 
                where M.id = ".$machine_id." ";
        

        if (array_key_exists('user_id', $options) && array_key_exists('m_user_id', $options) && $options['user_id'] == $options['m_user_id']) {
            $query .= " and M.user_id = ".$options['user_id']." ";
        }
        elseif (in_array('admin', $options,true)) {
            
        }
        else{
            $query .= " and approve = 1 and M.status = 0 and expire_date >= CURDATE() ";
        }
        
        $query .= $filter.$sort.$limit;
        //echo $query; die;
        $result = $this->db->query($query);        
            //  HAVING distance < ".$distance."

        if($result){
            $result1 = $result->result_array();
            foreach ($result1 as $key => $value) {
                $result1[$key]['image'] = $this->oneToManay('machines','machine_images',['where1' => array('id'=>$value['id']),'where2'=> array('machine_id'=>$value['id']),'field_match'=>'image','field1'=>'id','field2'=>'id,image']);
            }
            return $result1;
        }else{
            return false;
        }
    }

}
