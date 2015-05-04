<?php
namespace Admin\Controller;
use Think\Controller;

class ApiController  extends Controller {

    public function writer_list(){

        $writer_db = M('writer');
        $data = $writer_db->select();
        header('Content-Type:text/html;Charset=UTF-8');
        echo JSON($data);
    }

    public function  poetry_list(){

        $poetry_db = M('Poetry');
        $data = $poetry_db->select();
        header('Content-Type:text/html;Charset=UTF-8');
        echo JSON($data);
    }


    public function  info_list(){
        $info_db = M('info');
        $data = $info_db->select();
        header('Content-Type:text/html;Charset=UTF-8');
        echo JSON($data);
    }

    public function  infopart_list($start,$end){
        $info_db = M('info');
        $map['infoid'] = array(array('gt',$start),array('lt',$end));
        $data = $info_db->where($map)->select();
        header('Content-Type:text/html;Charset=UTF-8');
        echo JSON($data);
    }
} 