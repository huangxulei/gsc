<?php
namespace Admin\Controller;
use Admin\Controller\CommonController;

/**
 * 内容管理相关模块
 * @author wangdong
 * 
 * TODO
 * 带前缀的ACTION为控制权限用的，默认为view_
 * 后缀带_iframe的ACTION是在iframe中加载的，用于统一返回格式
 */
class ContentController extends CommonController {
	/**
	 * 对应权限如下：
	 * view 查看 | add 添加 | edit 编辑 | delete 删除 | order 排序
	 * 
	 * TODO 现在时间上来不及完善此功能，暂时先屏蔽
	 */
	public function _initialize(){
		parent::_initialize();
		
		//权限判断
		if(session('roleid') != 1 && ACTION_NAME != 'index' && strpos(ACTION_NAME, 'public_')===false) {
			
			$category_priv_db = M('category_priv');
			$tmp = explode('_', ACTION_NAME);
			$action = strtolower($tmp[0]);
			unset($tmp);
			if(!in_array($action, array('view', 'add', 'edit', 'delete', 'order', 'export', 'import'))) $action = 'view';
			
			$catid  = I('get.catid', 0, 'intVal');
			$roleid = session('roleid');
			
			$info = $category_priv_db->where(array('catid'=>$catid, 'roleid'=> $roleid, 'is_admin'=>1, 'action'=>$action))->count();
			if(!$info){
				//兼容iframe加载
				if(IS_GET && strpos(ACTION_NAME,'_iframe') !== false){
					exit('<style type="text/css">body{margin:0;padding:0}</style><div style="padding:6px;font-size:12px">您没有权限操作该项</div>');
				}
				//普通返回
				if(IS_AJAX && IS_GET){
					exit('<div style="padding:6px">您没有权限操作该项</div>');
				}else {
					$this->error('您没有权限操作该项');
				}
			}
		}
	}
	
	/**
	 * 内容管理首页
	 */
	public function index(){
		$menu_db = D('Menu');
		$currentpos = $menu_db->currentPos(I('get.menuid'));  //栏目位置
		$this->assign(currentpos, $currentpos);
		$this->display('index');
	}
	
	/**
	 * 介绍
	 */
	public function public_welcome(){
	    $this->display('welcome');
	}
	
	/**
	 * 右侧栏目
	 */
	public function public_right(){
	    if(IS_POST){
	        if(S('content_public_right')){
    			$data = S('content_public_right');
    		}else{
    	        $category_db = D('Category');
    			$data = $category_db->getCatTree();
    			S('content_public_right', $data);
    		}
			$this->ajaxReturn($data);
		}else{
	        $this->display('right');
		}
	}
	
	/**
	 * 文本编辑器
	 */
	public function editor_iframe($callback = ''){
		//转成js可以直接识别的代码
		if($callback) $callback = 'window.parent.'. $callback .'();';
		
		$this->assign('callback', $callback);
		$this->display('editor');
	}

	/**
	 * 单页面
	 */
	public function page($catid){
		$page_db = M('page');
		if(IS_POST){
			if(I('get.dosubmit')){
				$data = I('post.info');
				$data['updatetime'] = time();

				if($page_db->where(array('catid'=>$catid))->find()){
					$res = $page_db->where(array('catid'=>$catid))->save($data);
				}else{
					$data['catid'] = $catid;
					$res = $page_db->add($data);
				}
				$res ? $this->success('操作成功') : $this->error('操作失败');
			}else{
				$data = array();
				$info = $page_db->where(array('catid'=>$catid))->find();
				
				$fieldList = dict('page', 'Category'); //获取当前配置选项列表
				foreach ($fieldList as $key=>$fieldInfo){
					$fieldInfo['name']  = $fieldInfo['required'] ? "*{$fieldInfo['name']}" : $fieldInfo['name'];
					$fieldInfo['value'] = isset($info[$key]) ? $info[$key] : $fieldInfo['default'];
					$fieldInfo['key']   = $key;
					
					array_push($data, $fieldInfo);
				}
				$this->ajaxReturn($data);
			}
		}else{
			$info = $page_db->field(array('content'))->where(array('catid'=>$catid))->find();
			$info['content'] = htmlspecialchars_decode($info['content']);
			$this->assign('info', $info);
			$this->assign('catid', $catid);
			$this->display('page');
		}
	}
	
	/**
	 * 文章列表
	 */
	public function newsList($catid, $page=1, $rows=10, $search = array(), $sort = 'listorder', $order = 'desc'){
		if(IS_POST){
    		$news_db = M('news');
			
			//搜索
			$where = array("`catid` = {$catid}");
			foreach ($search as $k=>$v){
				if(!$v) continue;
				switch ($k){
					case 'title':
						$where[] = "`{$k}` like '%{$v}%'";
						break;
					case 'begin':
						if(!preg_match("/^\d{4}(-\d{2}){2}$/", $v)){
							unset($search[$k]);
							continue;
						}
						if($search['end'] && $search['end'] < $v) $v = $search['end'];
						$v = strtotime($v);
						$where[] = "`inputtime` >= '{$v}' or `updatetime` >= '{$v}'";
						break;
					case 'end':
						if(!preg_match("/^\d{4}(-\d{2}){2}$/", $v)){
							unset($search[$k]);
							continue;
						}
						if($search['begin'] && $search['begin'] > $v) $v = $search['begin'];
						$v = strtotime($v);
						$where[] = "`inputtime` <= '{$v}' or `updatetime` <= '{$v}'";
						break;
				}
			}
			$where = implode(' and ', $where);
			
			$limit=($page - 1) * $rows . "," . $rows;
			$total = $news_db->where($where)->count();
			$order = $sort.' '.$order;
			$field = array('id', 'listorder', 'title', 'username', 'FROM_UNIXTIME(updatetime, "%Y-%m-%d %H:%i:%s") as updatetime', 'FROM_UNIXTIME(inputtime, "%Y-%m-%d %H:%i:%s") as inputtime', 'status', 'id as operateid');
			$list = $total ? $news_db->field($field)->where($where)->order($order)->limit($limit)->select() : array();
    		$data = array('total'=>$total, 'rows'=>$list);
    		$this->ajaxReturn($data);
		}else{
			$datagrid = array(
		        'options'     => array(
    				'url'          => U('Content/newsList', array('grid'=>'datagrid', 'catid'=>$catid)),
    				'toolbar'      => '#content_newslist_datagrid_toolbar',
					'singleSelect' => false,
    			),
		        'fields' => array(
    				'选中'    => array('field'=>'ck', 'checkbox'=>true),
		        	'排序'     => array('field'=>'listorder','width'=>5,'formatter'=>'contentNewsListOrderFormatter'),
		        	'ID'      => array('field'=>'id','width'=>10,'sortable'=>true),
		        	'标题'     => array('field'=>'title','width'=>50,'sortable'=>true),
		        	'发布人'   => array('field'=>'username','width'=>20,'sortable'=>true),
			        '添加时间' => array('field'=>'inputtime','width'=>20,'sortable'=>true,'formatter'=>'contentNewsListTimeFormatter'),
		        	'更新时间' => array('field'=>'updatetime','width'=>20,'sortable'=>true,'formatter'=>'contentNewsListTimeFormatter'),
		        	'状态'    => array('field'=>'status','width'=>10,'sortable'=>true,'formatter'=>'contentNewsListStatusFormatter'),
		        	'管理操作' => array('field'=>'operateid','width'=>15,'formatter'=>'contentNewsListOperateFormatter')
    			)
		    );
		    $this->assign('datagrid', $datagrid);
			$this->assign('catid', $catid);
			$this->display('news_list');
		}
	}
	
	/**
	 * 添加文章
	 */
	public function add_news($catid){
		if(IS_POST){
			if(I('get.dosubmit')){
				$data = I('post.info', array(), 'trim');
				if(!$data['title'] || !$data['content']) $this->error('请填写必填字段');
				$data['catid'] = $catid;
				//添加时间
				$data['inputtime'] = time();
				//转向链接判断
				if(isset($data['islink']) && $data['islink'] == '开启'){
					$data['islink'] = '1';
				}else{
					$data['islink'] = '0';
					unset($data['url']);
				}
				//状态
				$data['status'] = (isset($data['status']) && $data['status'] == '发布') ? '1' : '0';

				//自动提取描述
				$data['description'] = $data['description'] ? strip_tags($data['description']) : strip_tags($data['content']);
				if($data['description']) $data['description'] = msubstr(str_replace(array("\n", "\r\n", ' ', '　'), '', strip_tags($data['description'])), 0, 100);

				//自动提取缩略图
				if(!$data['thumb']) {
					if(preg_match_all("/(src)=([\"|']?)([^ \"'>]+\.(gif|jpg|jpeg|bmp|png))\\2/i", $data['content'], $matches)) {
						$data['thumb'] = $matches[3][0];
					}
				}

				$news_db = M('news');
				$res = $news_db->add($data);

				$res ? $this->success('操作成功') : $this->error('操作失败');
			}else{
				$data = array();
				
				$fieldList = dict('news', 'Category'); //获取当前配置选项列表
				foreach ($fieldList as $key=>$fieldInfo){
					$fieldInfo['name']  = $fieldInfo['required'] ? "*{$fieldInfo['name']}" : $fieldInfo['name'];
					$fieldInfo['value'] = $fieldInfo['default'];
					$fieldInfo['key']   = $key;
					
					//缩略图需要单独处理
					if($key == 'thumb'){
						$fieldInfo['value'] = '<input id="content_add_news_thumb_value" style="width:112px;height:88px" type="image" src="' . $fieldInfo['value'] . '" title="点击上传缩略图" onclick="return contentAddNewsThumbClick(this)" />';
					}
						
					array_push($data, $fieldInfo);
				}
				$this->ajaxReturn($data);
			}
		}else{
			$this->assign('catid', $catid);
			$this->display('news_add');
		}
	}
	
	/**
	 * 编辑文章
	 */
	public function edit_news($catid, $id){
		$news_db = M('news');
		if(IS_POST){
			if(I('get.dosubmit')){
				$data = I('post.info', array(), 'trim');
				if(!$data['title'] || !$data['content']) $this->error('请填写必填字段');
				//编辑时间
				$data['updatetime'] = time();
				
				//转向链接判断
				if(isset($data['islink']) && $data['islink'] == '开启'){
					$data['islink'] = '1';
				}else{
					$data['islink'] = '0';
					unset($data['url']);
				}
				//状态
				$data['status'] = (isset($data['status']) && $data['status'] == '发布') ? '1' : '0';
				
				//自动提取描述
				$data['description'] = $data['description'] ? strip_tags($data['description']) : strip_tags($data['content']);
				if($data['description']) $data['description'] = msubstr(str_replace(array("\n", "\r\n", ' ', '　'), '', strip_tags($data['description'])), 0, 100);
				
				//自动提取缩略图
				if(!$data['thumb']) {
					if(preg_match_all("/(src)=([\"|']?)([^ \"'>]+\.(gif|jpg|jpeg|bmp|png))\\2/i", $data['content'], $matches)) {
						$data['thumb'] = $matches[3][0];
					}
				}
				
				$news_db = M('news');
				$res = $news_db->where(array('catid'=>$catid, 'id'=>$id))->save($data);
				
				$res ? $this->success('操作成功') : $this->error('操作失败');
				
			}else{
				$data = array();
				$info = $news_db->where(array('catid'=>$catid, 'id'=>$id))->find();
				
				$fieldList = dict('news', 'Category'); //获取当前配置选项列表
				foreach ($fieldList as $key=>$fieldInfo){
					$fieldInfo['name']  = $fieldInfo['required'] ? "*{$fieldInfo['name']}" : $fieldInfo['name'];
					$fieldInfo['value'] = isset($info[$key]) ? $info[$key] : $fieldInfo['default'];
					$fieldInfo['key']   = $key;
					
					switch($key){
						//缩略图需要单独处理
						case 'thumb':
							if(!$fieldInfo['value']) $fieldInfo['value'] = $fieldInfo['default'];
							$fieldInfo['value'] = '<input id="content_edit_news_thumb_value" style="width:112px;height:88px" type="image" src="' . $fieldInfo['value'] . '" title="点击上传缩略图" onclick="return contentEditNewsThumbClick(this)" />';
							break;
							
						case 'islink':
							$fieldInfo['value'] = $fieldInfo['value'] ? '开启' : '关闭';
							break;
							
						case 'status':
							$fieldInfo['value'] = $fieldInfo['value'] ? '发布' : '不发布';
							break;
					}
			
					array_push($data, $fieldInfo);
				}
				$this->ajaxReturn($data);
			}
		}else{
			$info = $news_db->field(array('id', 'content'))->where(array('catid'=>$catid, 'id'=>$id))->find();
			$info['content'] = htmlspecialchars_decode($info['content']);
			$this->assign('info', $info);
			$this->assign('catid', $catid);
			$this->display('news_edit');
		}
	}
	
	/**
	 * 删除文章
	 */
	public function delete_news($catid){
		if(IS_POST){
			$news_db = M('news');
			$ids = I('post.ids', array());
			foreach($ids as $id) {
				$news_db->where(array('id'=>$id))->delete();
			}
			$this->success('操作成功');
		} else {
			$this->error('操作失败');
		}
	}
	
	/**
	 * 文章排序
	 */
	public function order_news($catid){
		if(IS_POST) {
			$news_db = M('news');
			foreach(I('post.order') as $id => $listorder) {
				$news_db->where(array('id'=>$id))->save(array('listorder'=>$listorder));
			}
			$this->success('操作成功');
		} else {
			$this->error('操作失败');
		}
	}
	
	/**
	 * 文章导出
	 */
	public function export_news($catid, $filename = ''){
		if(IS_POST) {
			$news_db = M('news');
			$ids     = I('post.ids', array());
			if(is_array($ids) && !empty($ids)){
				$data           = array('type'=>'news');
				$data['data']   = $news_db->where('`id` in (' . implode(',', $ids) . ') and catid = ' . $catid)->order('id asc')->getField('id,title,style,thumb,keywords,description,content,url,listorder,status,islink,username,inputtime,updatetime', true);
				$data['verify'] = md5(var_export($data['data'], true) . $data['type']);
					
				//数据进行多次加密，防止数据泄露
				$data = base64_encode(gzdeflate(json_encode($data)));
					
				$uniqid = uniqid();
				$filename = UPLOAD_PATH . 'export/' . $uniqid . '.data';
				if(file_write($filename, $data)){
					$this->success('导出成功', U('Content/export_news', array('catid'=>$catid, 'filename'=>$uniqid)));
				}
			}
			$this->error('导出失败，请重试！');
		}else{
			//过滤特殊字符，防止非法下载文件
			$filename = str_replace(array('.', '/', '\\'), '', $filename);
			$filename = UPLOAD_PATH . 'export/' . $filename . '.data';
			if(!file_exist($filename)) $this->error('非法访问');
				
			header('Content-type: application/octet-stream');
			header('Content-Disposition: attachment; filename="内容管理.data"');
			echo file_read($filename);
				
			file_delete($filename);
		}
	}
	
	/**
	 * 文章导入
	 */
	public function import_news($catid, $filename = ''){
		if(IS_POST) {
			//过滤特殊字符，防止非法下载文件
			$filename = str_replace(array('.', '/', '\\'), '', $filename);
			$filename = UPLOAD_PATH . 'import/' . $filename . '.data';
			if(!file_exist($filename)) $this->error('导入失败');
				
			$content = file_read($filename);
				
			//解密
			try {
				$data  = gzinflate(base64_decode($content));
			}catch (\Exception $e){};
			if(!isset($data)){
				file_delete($filename);
				$this->error('非法数据');
			}
				
			//防止非法数据
			try {
				$data = json_decode($data, true);
			}catch (\Exception $e){};
			if(!is_array($data) || !isset($data['type']) || $data['type'] != 'news' || !isset($data['verify']) || !isset($data['data'])){
				file_delete($filename);
				$this->error('非法数据');
			}
				
			if($data['verify'] != md5(var_export($data['data'], true) . $data['type'])){
				file_delete($filename);
				$this->error('非法数据');
			}
				
			$news_db = M('news');
				
			//开始导入
			asort($data['data']);
			foreach ($data['data'] as $add){
				unset($add['id']);
				$add['catid'] = $catid;
				$news_db->add($add);
			}
				
			file_delete($filename);
			$this->success('导入成功');
		}else{
			$this->error('非法访问');
		}
	}
}