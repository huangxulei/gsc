<?php
namespace Admin\Controller;
use Admin\Controller\CommonController;

/**
 * 系统设置相关模块
 * @author wangdong
 */
class SettingController extends CommonController {
	/**
	 * 站点设置
	 */
	public function site(){
		if(IS_POST){
			$setting_db = D('Setting');
			if(I('get.dosubmit')){
				$state = $setting_db->dosave($_POST['data']);
				$state ? $this->success('操作成功') : $this->error('操作失败');
			}else{
				if(S('setting_site')){
					$data = S('setting_site');
				}else{
					$data = $setting_db->getSetting();
					S('setting_site', $data);
				}
				$this->ajaxReturn($data);
			}
		}else {
			$menu_db = D('Menu');
			$currentpos = $menu_db->currentPos(I('get.menuid'));  //栏目位置
			$propertygrid = array(
				'options'     => array(
	    			'title'   => $currentpos,
	    			'url'     => U('Setting/site', array('grid'=>'propertygrid')),
	    			'toolbar' => 'setting_site_propertygrid_toolbar',
	    		)
			);
			$this->assign('propertygrid', $propertygrid);
			$this->display();
		}
	}
	
	/**
	 * 恢复出厂设置
	 */
	public function siteDefault(){
		if(IS_POST){
			$setting_db = D('Setting');
			if($setting_db->where('1')->count()){
				$state = $setting_db->where('1')->delete();
				if($state){
					$setting_db->clearCatche();
					$this->success('操作成功');
				}else{
					$this->error('操作失败');
				}
			}
			$this->success('操作成功');
		}
	}
	
	/**
	 * 导出
	 */
	public function siteExport($filename = ''){
		if(IS_POST) {
			$setting_db = M('setting');
			$data       = array('type'=>'setting');
			$data['data']   = $setting_db->select();
			$data['verify'] = md5(var_export($data['data'], true) . $data['type']);
				
			//数据进行多次加密，防止数据泄露
			$data = base64_encode(gzdeflate(json_encode($data)));
				
			$uniqid = uniqid();
			$filename = UPLOAD_PATH . 'export/' . $uniqid . '.data';
			if(file_write($filename, $data)){
				$this->success('导出成功', U('Setting/siteExport', array('filename'=>$uniqid)));
			}
			$this->error('导出失败，请重试！');
		}else{
			//过滤特殊字符，防止非法下载文件
			$filename = str_replace(array('.', '/', '\\'), '', $filename);
			$filename = UPLOAD_PATH . 'export/' . $filename . '.data';
			if(!file_exist($filename)) $this->error('非法访问');
				
			header('Content-type: application/octet-stream');
			header('Content-Disposition: attachment; filename="站点设置.data"');
			echo file_read($filename);
				
			file_delete($filename);
		}
	}
	
	/**
	 * 导入
	 */
	public function siteImport($filename = ''){
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
			if(!is_array($data) || !isset($data['type']) || $data['type'] != 'setting' || !isset($data['verify']) || !isset($data['data'])){
				file_delete($filename);
				$this->error('非法数据');
			}
				
			if($data['verify'] != md5(var_export($data['data'], true) . $data['type'])){
				file_delete($filename);
				$this->error('非法数据');
			}
				
			$setting_db = D('Setting');
				
			//先清空数据再导入
			$setting_db->where("`key` <> ''")->delete();
			$setting_db->clearCatche();
				
			//开始导入
			asort($data['data']);
			foreach ($data['data'] as $add){
				$setting_db->add($add);
			}
				
			file_delete($filename);
			$this->success('导入成功');
		}else{
			$this->error('非法访问');
		}
	}
}