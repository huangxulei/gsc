<?php
namespace Admin\Controller;
use Admin\Controller\CommonController;
use Think\Upload;

/**
 * 上传相关模块
 * @author wangdong
 */
class UploadController extends CommonController {
	/**
	 * 文件导入，不支持多文件上传
	 */
	public function import(){
		if(count($_FILES) != 1){
			$data = array('info'=>'只能导入一个文件', 'status'=>0);
			$this->ajaxReturn(json_encode($data), 'eval');
		}
		
		$uniqid = uniqid();
		$upload = new Upload(array(
			'autoSub'    => false,       //自动子目录保存文件
			'rootPath'   => UPLOAD_PATH, //保存根路径
			'savePath'   => 'import/',   //保存路径
			'saveName'   => $uniqid,     //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
			'replace'    => true,        //存在同名是否覆盖
		));
		$res = $upload->upload();
		
		if($res){
			$data = array('status'=>1, 'info'=>'上传成功', 'filename'=>$uniqid);
		}else{
			$data = array('status'=>0, 'info'=>$upload->getError());
		}
		
		//由于是通过iframe上传，部分浏览器使用json类型会直接下载文件，因此这里使用eval类型
		$this->ajaxReturn(json_encode($data), 'eval');
	}
	
	/**
	 * 附件上传
	 */
	public function link(){
		if(!IS_POST) return false;
		$config = C('FILE_UPLOAD_LINK_CONFIG');
		return self::_xhUpload($_FILES, $config, 'link');
	}
	
	/**
	 * 图片上传
	 */
	public function img(){
		if(!IS_POST) return false;
		$config = C('FILE_UPLOAD_IMG_CONFIG');
		return self::_xhUpload($_FILES, $config, 'img');
	}
	
	/**
	 * 缩略图上传
	 * TODO 为了兼容不同平台，裁剪功能将在后续开发
	 */
	public function thumb(){
		if(!IS_POST) return false;
		$config = C('FILE_UPLOAD_IMG_CONFIG');
		return self::_xhUpload($_FILES, $config, 'thumb');
	}
	
	/**
	 * flash上传
	 */
	public function flash(){
		if(!IS_POST) return false;
		$config = C('FILE_UPLOAD_FLASH_CONFIG');
		return self::_xhUpload($_FILES, $config, 'flash');
	}
	
	/**
	 * media上传
	 */
	public function media(){
		if(!IS_POST) return false;
		$config = C('FILE_UPLOAD_MEDIA_CONFIG');
		return self::_xhUpload($_FILES, $config, 'media');
	}
	
	/**
	 * xheditor专用上传
	 * @param string|array $files
	 * @param array $config  上传配置
	 * @param string $type  上传文件类型
	 */
	private function _xhUpload($files, $config = array(), $type = ''){
		$msg = null;
		$error = null;
		
		//上传配置
		$config = array_merge(C('FILE_UPLOAD_CONFIG'), $config);
		
		//HTML5上传
		if(isset($_SERVER['HTTP_CONTENT_DISPOSITION'])&&preg_match('/attachment;\s+name="(.+?)";\s+filename="(.+?)"/i',$_SERVER['HTTP_CONTENT_DISPOSITION'],$info)){
			$localName = urldecode($info[2]);
			$ext = file_ext($localName);
			if(!in_array($ext, $config['exts'])){
				$error = '上传文件后缀不允许';
			}else{
				$filename = UPLOAD_PATH . date('Y/m/d/') . uniqid() . '.' . $ext;
				$res = file_write($filename, file_get_contents("php://input"));
				if($res){
					$msg = array(
						'url'       => file_path2url($filename),
						'localname' => $localName
					);
				}else{
					$error = '上传失败';
				}
			}
		}else{
			$upload = new Upload($config);
			$res = $upload->upload($files);
			if($res){
				$localName = $res['filedata']['name'];
				$filename = UPLOAD_PATH .$res['filedata']['savepath'].$res['filedata']['savename'];
				$msg = array(
					'url'       => file_path2url($filename),
					'localname' => $res['filedata']['name']
				);
			}else{
				$error = $upload->getError();
			}
		}

		//针对xheditor不同上传类型返回不同结果
		if($msg){
			switch ($type){
				case 'link': //附件类型，在URL中直接显示文件名称
					$msg['url'] .= '||' . $localName;
					break;
			}
		}

		//返回上传结果
		$this->ajaxReturn(json_encode(array('err'=>$error, 'msg'=>$msg)), 'eval');
	}
}