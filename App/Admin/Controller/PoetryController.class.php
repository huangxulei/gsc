<?php
namespace Admin\Controller;
use Admin\Controller\CommonController;

/**
 * 古诗词模块
 * @package Admin\Controller
 */
class PoetryController extends CommonController{
    public  function  basedata(){
        if(IS_POST){
            $data = I('post.info', array(), 'trim');
            $basedataStr = "<?php \n return array(\n";

            $cates = explode('|',$data['catelist']);
            $basedataStr.="'POETRY_CATE' =>array(\n";
            foreach($cates as $key=>$v){
                $basedataStr.= "\t'".$key."'=>'".$v."',\n";
            }
            $basedataStr.="),";

            $kinds = explode('|',$data['kindlist']);
            $basedataStr.="'POETRY_KIND' =>array(\n";
            foreach($kinds as $key=>$v){
                $basedataStr.= "\t'".$key."'=>'".$v."',\n";
            }
            $basedataStr.="),";

            $basedataStr.="'POETRY_TYPE' =>array(\n";

            $types = explode('|',$data['typelist']);
            foreach($types as $key=>$v){
                $basedataStr.= "\t'".$key."'=>'".$v."',\n";
            }
            $basedataStr.="),";

            $basedataStr.="'POETRY_DYNASTY' =>array(\n";
            $dynastys = explode('|',$data['dynastylist']);
            foreach($dynastys as $key=>$v){
                $basedataStr.= "\t'".$key."'=>'".$v."',\n";
            }
            $basedataStr.=")";

            $basedataStr.=");\n?>\n";
            $basedataFile = CONF_PATH. DS .'setting.php';
            if(is_writable($basedataFile)){
                file_put_contents($basedataFile, $basedataStr);
                $this->success('修改成功');
            }else{
                $this->error('修改失败'.$basedataFile);
            }
        }
            $this->assign('catelist',    implode('|' , C('POETRY_CATE')));
            $this->assign('kindlist',    implode('|' , C('POETRY_KIND')));
            $this->assign('typelist',    implode('|' , C('POETRY_TYPE')));
            $this->assign('dynastylist', implode('|' , C('POETRY_DYNASTY')));
            $this->display('basedata');

    }

    public function writerList($page = 1, $rows = 10,$sort = 'writerid', $order = 'desc',$where="1=1" ){
        if(IS_POST){
            $writer_db = M('Writer');
            $info_db = M('info');
            $total = $writer_db->where($where)->count();
            $order = $sort.' '.$order;
            $limit = ($page - 1) * $rows . "," . $rows;
            $list = $writer_db->where($where)->order($order)->limit($limit)->select();
            if(!$list) $list = array();
            foreach ($list as &$val) {
                $val['dynastyname']=C("POETRY_DYNASTY")[$val['dynastyid']];
                $infos = $info_db->where(array('fid'=>$val['writerid'],'cateid'=>0))->field('infoid,title')->select();
                $val['infos'] = $infos;
            }

            $data = array('total'=>$total, 'rows'=>$list);
            $this->ajaxReturn($data);
        }else{
            $menu_db = D('Menu');
            $currentpos = $menu_db->currentPos(I('get.menuid'));  //栏目位置
            $datagrid = array(
                'options'     => array(
                    'title'   => $currentpos,
                    'url'     => U('Poetry/writerList', array('grid'=>'datagrid')),
                    'toolbar' => 'poetry_writerlist_datagrid_toolbar'
                ),
                'fields' => array(
                    '朝代' => array('field'=>'dynastyname','width'=>5,'align'=>'center','sortable'=>true),
                    '作者名称'  => array('field'=>'writername','width'=>5,'sortable'=>true),
                    '作者介绍' => array('field'=>'infos','width'=>10,'formatter'=>'infosFormatter'),
                    '管理操作'  => array('field'=>'writerid','width'=>10,'formatter'=>'poetrywriterListOperateFormatter'),
                )
            );
            $this->assign('datagrid', $datagrid);
            $this->display('writer_list');
        }
    }

    /**
     * 添加作者
     */
    public function writerAdd(){
        if(IS_POST){
            $writer_db = M('writer');
            $data = I('post.info', array(), 'trim');
            if($writer_db->where(array('writername'=>$data['writername']))->field('writername')->find()){
                $this->error('作者名称已存在');
            }
            $id = $writer_db->add($data);
            if($id){
                S('dynastyid', $data['dynastyid']);
                S('writerid', $id);
                $this->success('添加成功');
            }else {
                $this->error('添加失败');
            }
        }else{
            $this->assign('dynastylist', C('POETRY_DYNASTY'));
            $this->display('writer_add');
        }
    }

    public function writerEdit($writerid){

        $writer_db = M('writer');
        if(IS_POST){
            $data = I('post.info', array(), 'trim');
            $result =$writer_db->where(array('writerid'=>$writerid))->save($data);
            if($result){
                $this->success('修改成功');
            }else {
                $this->error('修改失败');
            }
        }else{
            $info = $writer_db->where(array('writerid'=>$writerid))->find();
            $this->assign('dynastylist', C('POETRY_DYNASTY'));
            $this->assign('info', $info);
            $this->display('writer_edit');
       }
    }

    /**
     * 验证类型名
     */
    public function public_checkWriterName($writername){
        if (I('post.default') == $writername) {
            $this->error('作者名相同');
        }
        $writer_db = M('writer');
        $exists = $writer_db->where(array('writername'=>$writername))->field('writername')->find();
        if ($exists) {
            $this->success('作者名存在');
        }else{
            $this->error('作者名不存在');
        }
    }

    public function poetryList($page = 1, $rows = 10,$sort = 'poetryid', $order = 'desc',$where="1=1" ){
        if(IS_POST){
            $poetry_db = M('Poetry');
            $writer_db = M('Writer');
            $info_db = M("info");
            $total = $poetry_db->where($where)->count();
            $order = $sort.' '.$order;
            $limit = ($page - 1) * $rows . "," . $rows;
            $list = $poetry_db->where($where)->order($order)->limit($limit)->select();
            //echo $poetry_db->getLastSql();
            if(!$list) $list = array();
            foreach ($list as &$val) {
                $writer = $writer_db->find($val['writerid']);
                $val['writername'] = $writer['writername'];
                $val['kindname'] = C('POETRY_KIND')[$val['kindid']];
                $infos = $info_db->where(array('fid'=>$val['poetryid'],'cateid'=>1))->field('infoid,title')->select();
                $typenames="";
                $idarr = explode(",",$val['typeid']);
                if(count($idarr) > 1){
                    foreach($idarr as $vv){
                        $typenames = $typenames.C('POETRY_TYPE')[$vv].',';
                    }
                }else{
                    $typenames = C('POETRY_TYPE')[$idarr[0]];
                }
                $val['typenames'] =$typenames;
                $val['infos'] = $infos;
            }
            $data = array('total'=>$total, 'rows'=>$list);
            $this->ajaxReturn($data);
        }else{
            $menu_db = D('Menu');
            $currentpos = $menu_db->currentPos(I('get.menuid'));  //栏目位置
            $datagrid = array(
                'options'     => array(
                    'title'   => $currentpos,
                    'url'     => U('Poetry/poetryList', array('grid'=>'datagrid')),
                    'toolbar' => 'poetry_poetrylist_datagrid_toolbar'
                ),
                'fields' => array(
                    '作品名' => array('field'=>'title','width'=>5,'align'=>'center','sortable'=>true),
                    '形式' => array('field'=>'kindname','width'=>2,'align'=>'center','sortable'=>true),
                    '作者' => array('field'=>'writername','width'=>5,'align'=>'center','sortable'=>true),
                    '类型'=>array('field'=>'typenames','width'=>15),
                    '参考翻译'=> array('field'=>'infos','width'=>15,'formatter'=>'infosFormatter'),
                    '管理操作'  => array('field'=>'poetryid','width'=>5,'formatter'=>'poetrypoetryListOperateFormatter'),
                )
            );
            $writer_db = M('writer');
            $writerlist = $writer_db->order('writerid desc')->select();
            $this->assign('writerlist', $writerlist);
            $this->assign('datagrid', $datagrid);
            $this->display('poetry_list');
        }
    }

    /**
     * @param $infoid 信息主键
     * @param $cateid 信息种类
     */
    public function addInfo($fid,$cateid){
        if(IS_POST){
            $info_db = M('info');
            $data = I('post.info', array(), 'trim');
            $data['fid'] = $fid;
            $data['cateid']= $cateid;
            $id = $info_db->add($data);
            if($id){
                $this->success('添加成功');
            }else {
                $this->error('添加失败');
            }
        }else{
            if($cateid == 0){
                $writer_db = M('writer');
                $data = $writer_db->find($fid);
                $fname =$data['writername'];
            }else{
                $poetry_db =M('poetry');
                $data = $poetry_db->find($fid);
                $fname =$data['title'];
            }
            $this->assign('fname', $fname);
            $this->assign('fid', $fid);
            $this->assign('cateid', $cateid);
            $this->display('info_add');
        }
    }

    public function editInfo($infoid){
        $info_db = M('info');
        if(IS_POST){
            $data = I('post.info', array(), 'trim');
            $result = $info_db->where(array('infoid'=>$infoid))->save($data);
            if($result){
                $this->success('修改成功');
            }else {
                $this->error('修改失败');
            }
        }else{
            $info=$info_db->find($infoid);
            if($info['cateid'] ==0){
                $writer_db = M("writer");
                $writer = $writer_db->find($info['fid']);
                $fname = $writer['writername'];
            }else{
                $poetry_db =M("poetry");
                $poetry = $poetry_db->find($info['fid']);
                $fname = $poetry['title'];
            }
            $this->assign('cateid',$info['cateid']);
            $this->assign('fname',$fname);
            $this->assign('info',$info);
            $this->display('info_edit');
        }
    }

    /**
     * 添加作品
     */
    public function poetryAdd(){
        if(IS_POST){
            $poetry_db = M('poetry');
            $data = I('post.info', array(), 'trim');
            if($poetry_db->where(array('title'=>$data['title'],'writerid'=>$data['writerid']))->find()){
                $this->error('作品名称已存在');
            }
            $id = $poetry_db->add($data);
            if($id){
                S('writerid',$data['writerid']);
                S('kindid',$data['kindid']);
                S('typeid',$data['typeid']);
                $this->success('添加成功');
            }else {
                $this->error('添加失败');
            }
        }else{
            $writer_db = M('writer');
            $writerlist = $writer_db->order('writerid desc')->select();
            $this->assign('writerlist', $writerlist);
            $this->assign('kindlist', C('POETRY_KIND'));
            $this->assign('typelist', C('POETRY_TYPE'));

            $this->display('poetry_add');
        }
    }


    public function poetryEdit($poetryid){

        $poetry_db = M('poetry');
        if(IS_POST){
            $data = I('post.info', array(), 'trim');
            $result =$poetry_db->where(array('poetryid'=>$poetryid))->save($data);
            if($result){
                $this->success('修改成功');
            }else {
                $this->error('修改失败');
            }

        }else{
            $info = $poetry_db->where(array('poetryid'=>$poetryid))->find();
            $info['content'] = htmlspecialchars_decode($info['content']);
            $info['translate'] = htmlspecialchars_decode($info['translate']);
            $info['appreciate'] = htmlspecialchars_decode($info['appreciate']);
            $writer_db = M('writer');
            $writerlist = $writer_db->order('writerid asc')->select();
            $this->assign('writerlist', $writerlist);
            $this->assign('kindlist', C('POETRY_KIND'));
            $this->assign('typelist', C('POETRY_TYPE'));
            $this->assign('info', $info);
            $this->display('poetry_edit');
        }
    }


    /**
     * 验证类型名
     */
    public function public_checkPoetryTitle($title){
        if (I('post.default') == $title) {
            $this->error('作品名相同');
        }
        $poetry_db = M('poetry');
        $exists = $poetry_db->where(array('title'=>$title))->field('title')->find();
        if ($exists) {
            $this->success('作品名存在');
        }else{
            $this->error('作品名不存在');
        }
    }


    public function getTypeJson(){

        $typelist = C('POETRY_TYPE');
        foreach($typelist as $key=>$val){
            $data[$key]['id'] = $key;
            $data[$key]['text'] = $val;
        }
        header('Content-Type:text/html;Charset=UTF-8');
        echo JSON($data);
    }

    public function getDynastyJson(){

        $dynastylist = C('POETRY_DYNASTY');
        foreach($dynastylist as $key=>$val){
            $data[$key]['id'] = $key;
            $data[$key]['text'] = $val;
        }
        header('Content-Type:text/html;Charset=UTF-8');
        echo JSON($data);
    }

    public function getWriterJson(){

        $writer_db = M('Writer');
        $writerlist = $writer_db->field('writerid,writername')->order("convert(writername using gb2312) ASC")->select();
        foreach($writerlist as $key=>$val){
            $data[$key]['id'] = $val['writerid'];
            $data[$key]['text'] = $val['writername'];
        }
        header('Content-Type:text/html;Charset=UTF-8');
        echo JSON($data);
    }

    public function getTypeString(){

        $typelist = C('POETRY_TYPE');
        $data ="";
        foreach($typelist as $key=>$val){
            $data = $data.',"'.$key.'|'.$val.'"';
        }
        header('Content-Type:text/html;Charset=UTF-8');
        echo $data;
    }

    public  function  getTitleJson(){
        $poetry_db = M('Poetry');
        $poetrylist = $poetry_db->field('poetryid,title')->order("convert(title using gb2312) ASC")->select();
        foreach($poetrylist as $key=>$val){
            $data[$key]['id'] = $val['poetryid'];
            $data[$key]['text'] = $val['title'];
        }
        header('Content-Type:text/html;Charset=UTF-8');
        echo JSON($data);
    }


}