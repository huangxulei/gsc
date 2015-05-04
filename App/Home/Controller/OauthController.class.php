<?php
namespace Home\Controller;
use Home\Controller\CommonController;
use Home\Plugin\ThinkSDK\ThinkOauth;
use Think\Log;

class OauthController extends CommonController {
	/**
	 * 第三方登陆访问入口，测试用
	 */
	public function login($type = null){
		if(!$type) $this->error('参数错误');

		try {
			$sns  = ThinkOauth::getInstance($type);
		}catch (\Exception $e){
			$this->_empty();
		};

		//跳转到授权页面
		redirect($sns->getRequestCodeURL());
	}

	/**
	 * 授权回调地址
	 */
	public function callback($type = null, $code = null){
		if(!($type) || !($code)) $this->error('参数错误');
		$type = strtolower($type);

		try {
			$sns  = ThinkOauth::getInstance($type);
		}catch (\Exception $e){
			$this->error('登录失败');
		};

		$extend = null;
		//腾讯微博需传递的额外参数
		if($type == 'tencent'){
			$extend = array('openid' => I('get.openid'), 'openkey' => I('get.openkey'));
		}

		try {
			$token = $sns->getAccessToken($code , $extend);
		}catch (\Exception $e){
			$this->error('登录失败');
		};

		//调用不同的登录方式
		$data = self::$type($sns);
		if(!$data){
			$this->error('登录失败');
		}

		$member_db       = M('member');
		$member_oauth_db = M('member_oauth');

		//如果用户没有注册则先进行注册
		$oauthInfo       = $member_oauth_db->where(array('type'=>$data['type'], 'openid'=>$data['openid']))->find();
		if(!$oauthInfo){
			$memberInfo  = $member_db->where(array('username'=>$data['username']))->find();
			if(!$memberInfo){
				//添加一个随机密码，防止出现用户名密码都为空的情况
				$passwordinfo = password(rand(000000, 999999));
				$add = array(
					'username'      => $data['username'],
					'password'      => $passwordinfo['password'],
					'encrypt'       => $passwordinfo['encrypt'],
					'typeid'        => 2,
					'regtime'       => time(),
					'lastloginip'   => get_client_ip(0, true),
					'lastlogintime' => time(),
				);
				$memberid = $member_db->add($add);
			}else{
				$memberid = $memberInfo['memberid'];
			}

			if(!$memberid) $this->error('登录失败');

			unset($data['username']);
			$data = array_merge($data, array(
				'memberid' => $memberid,
				'addtime'  => time(),
			));
			$id = $member_oauth_db->add($data);
			if(!$id){
				$this->error('登录失败');
			}

			$oauthInfo = $data;
		}

		//修改登陆时间
		$member_db->where(array('memberid'=>$oauthInfo['memberid']))->save(array('lastloginip'=>get_client_ip(0, true), 'lastlogintime'=>time()));

		cookie('member_id',   $oauthInfo['memberid']);
		cookie('member_name', $oauthInfo['nick']);
		cookie('member_head', $oauthInfo['head']);
		cookie('member_link', $oauthInfo['link']);

		$this->success('登录成功', U('Home/Index/index'));
	}


	/**
	 * 退出登陆
	 */
	public function logout(){
		cookie('member_id',   null);
		cookie('member_name', null);
		cookie('member_head', null);
		cookie('member_link', null);
		$this->success('已退出登陆', U('Home/Index/index'));
	}
	
	
	private function uuid(){
		$res = M()->query("select UUID() as uuid");
		return $res[0]['uuid'];
	}

	/**
	 * 获取新浪微博用户信息
	 */
	private function sina($sns){
		$data = $sns->call('users/show', "uid={$sns->openid()}");
		if($data['error_code'] == 0){
			return array(
				'username'  => $this->uuid(),
				'type'      => 'sina',
				'openid'    => $sns->openid(),
				'nick'      => $data['screen_name'],
				'head'      => $data['avatar_large'],
				'gender'    => $data['gender'],
				'link'      => '',
			);
		}
		return null;
	}

	/**
	 * 获取百度用户信息
	 */
	private function baidu($sns){
		$data  = $sns->call('passport/users/getLoggedInUser');
		if(!empty($data['uid'])){
			return array(
				'username'  => $this->uuid(),
				'type'      => 'baidu',
				'openid'    => $sns->openid(),
				'nick'      => $data['uname'],
				'head'      => "http://tb.himg.baidu.com/sys/portrait/item/{$data['portrait']}",
				'gender'    => '',
				'link'      => '',
			);
		}
		return null;
	}

	/**
	 * 获取腾讯QQ用户信息
	 */
	private function qq($sns){
		$data = $sns->call('user/get_user_info');
		if($data['ret'] == 0){
			return array(
				'username'  => $this->uuid(),
				'type'      => 'qq',
				'openid'    => $sns->openid(),
				'nick'      => $data['nickname'],
				'head'      => $data['figureurl_2'],
				'gender'    => $data['gender'],
				'link'      => '',
			);
		}
		return null;
	}

}