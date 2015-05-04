<?php
namespace Home\Controller;
use Think\Controller;

/**
 * 公共控制器
 */
class CommonController extends Controller {
	/**
	 * 相当于构造函数，初始化一些公共内容
	 * TODO 这里面建议使用self调用，防止子类中出现重名函数
	 */
	public function _initialize(){
		self::initNavbar();  //初始化导航，这里使用S缓存
	}

	/**
	 * 栏目管理初始化
	 */
	private function initNavbar(){
		//导航列表数据
		$category_db = D('Category');
		if(S('home_category_list')){
			$category_list = S('home_category_list');
		}else{
			$category_list = $category_db->getNavigation();
			S('home_category_list', $category_list);
		}
		$category_list = $category_db->getNavigation();
		$this->assign('g_category_list', $category_list);
	}

	/**
	 * 空操作，用于输出404页面
	 */
	public function _empty(){
		header("HTTP/1.0 404 Not Found");
		$this->show('<b>404 Not Found</b>');
	}
	
}
