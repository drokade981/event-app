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
        if (!empty($options) && in_array('count', $options)) {
            return count($result);
        }
        if ($result) {
            if (isset($options) && in_array('single', $options)) {
                return $result[0];
            } else {
                return $result;
            }
        } else {
            if (isset($options) && in_array('api', $options)) {
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
        return $this->db->update($table, $data, $where);
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
        $this->db->from($table);
        $res = $this->db->get()->result_array();
        if (!empty($options) && in_array('count', $options)) {
            return count($res);
        }
        if ($res) {
            return $res;
        } else {
            if (isset($options) && in_array('api', $options)) {
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
            return implode(',', $name1);
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
            $this->email->from('support@complianceaid.com', 'Compliance Aid');
        }
        $this->email->to($to);
        if (isset($options['replyToEmail']) && isset($options['replyToName'])) {
            $this->email->reply_to($options['replyToEmail'], $options['replyToName']);
        }
        $this->email->subject($subject);
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
                $value['join_type'] = isset($value['join_type'])?:'left';                
                $this->db->join($html1,$html2,$value['join_type']);                
            }
        }
        
        if (isset($options['where'])) {
            $this->db->where($options['where']);
        }
        if (isset($options['where_in']) && !empty($options['where_in'])) {
            $this->db->where_in($options['colname'], $options['where_in']);
        }

        if (isset($options['order_by']) && isset($options['order_direction'])) {
            $this->db->order_by($options['order_by'], $options['order_direction']);
        } elseif (isset($options['order_by'])) {
            $this->db->order_by($options['order_by'], 'desc');
        }

        if (isset($options['group_by'])) {
            $this->db->group_by($options['group_by']);
        }
        
        $query = $this->db->get();
        $result = $query->result_array();
        if($result){
            return $result;
        }else{
            return false;
        }
    }
    //---------------- custom functions end -----------------//
}
