<?php
/**
 * 字符串截取，支持中文和其他编码
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 * @return string
 */
function msubstr($str, $start=0, $length, $charset="utf-8", $suffix=false) {
	return Org\Util\String::msubstr($str, $start, $length, $charset, $suffix);
}

/**
 * 检测输入的验证码是否正确
 * @param string $code 为用户输入的验证码字符串
 * @return boolen
 */
function check_verify($code, $id = ''){
    $verify = new \Think\Verify();
    return $verify->check($code, $id);
}

/**
 * 对用户的密码进行加密
 * @param string $password
 * @param string $encrypt //传入加密串，在修改密码时做认证
 * @return array/password
 */
function password($password, $encrypt='') {
	$pwd = array();
	$pwd['encrypt'] =  $encrypt ? $encrypt : Org\Util\String::randString(6);
	$pwd['password'] = md5(md5(trim($password)).$pwd['encrypt']);
	return $encrypt ? $pwd['password'] : $pwd;
}

/**
 * 解析多行sql语句转换成数组
 * @param string $sql
 * @return array
 */
function sql_split($sql) {
	$sql = str_replace("\r", "\n", $sql);
	$ret = array();
	$num = 0;
	$queriesarray = explode(";\n", trim($sql));
	unset($sql);
	foreach($queriesarray as $query) {
		$ret[$num] = '';
		$queries = explode("\n", trim($query));
		$queries = array_filter($queries);
		foreach($queries as $query) {
			$str1 = substr($query, 0, 1);
			if($str1 != '#' && $str1 != '-') $ret[$num] .= $query;
		}
		$num++;
	}
	return($ret);
}

/**
 * 格式化字节大小
 * @param  number $size      字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string            格式化后的带单位的大小
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function format_bytes($size, $delimiter = '') {
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    for ($i = 0; $size >= 1024 && $i < 5; $i++) $size /= 1024;
    return round($size, 2) . $delimiter . $units[$i];
}

/**
 * 取得文件扩展
 * @param $filename 文件名
 * @return 扩展名
 */
function file_ext($filename) {
	return strtolower(trim(substr(strrchr($filename, '.'), 1, 10)));
}

/**
 * 文件是否存在
 * @param string $filename  文件名
 * @return boolean  
 */
function file_exist($filename ,$type=''){
	switch (STORAGE_TYPE){
		case 'Sae':
			$arr = explode('/', ltrim($filename, './'));
	        $domain = array_shift($arr);
	        $filePath = implode('/', $arr);
	        $s = new SaeStorage();
			return $s->fileExists($domain, $filePath);
			break;
		default:
			return \Think\Storage::has($filename ,$type);
	}
}

/**
 * 文件内容读取
 * @param string $filename  文件名
 * @return boolean         
 */
function file_read($filename, $type=''){
	switch (STORAGE_TYPE){
		case 'Sae':
			$arr = explode('/', ltrim($filename, './'));
	        $domain = array_shift($arr);
			$filePath = implode('/', $arr);
			$s=new SaeStorage();
			return $s->read($domain, $filePath);
			break;
		default:
			return \Think\Storage::read($filename, $type);
	}
}

/**
 * 文件写入
 * @param string $filename  文件名
 * @param string $content  文件内容
 * @return boolean         
 */
function file_write($filename, $content, $type=''){
	switch (STORAGE_TYPE){
		case 'Sae':
			$s=new SaeStorage();
			$arr = explode('/',ltrim($filename,'./'));
			$domain = array_shift($arr);
			$save_path = implode('/',$arr);
			return $s->write($domain, $save_path, $content);
			break;
		default:
			return \Think\Storage::put($filename, $content, $type);
	}
}

/**
 * 文件删除
 * @param string $filename  文件名
 * @return boolean     
 */
function file_delete($filename ,$type=''){
	switch (STORAGE_TYPE){
		case 'Sae':
			$arr = explode('/', ltrim($filename, './'));
	        $domain = array_shift($arr);
	        $filePath = implode('/', $arr);
	        $s = new SaeStorage();
			return $s->delete($domain, $filePath);
			break;
		default:
			return \Think\Storage::unlink($filename ,$type);
	}
}

/**
 * 获取文件URL
 * @param string $filename  文件名
 * @return string
 */
function file_path2url($filename){
	$search = array_keys(C('TMPL_PARSE_STRING'));
	$replace = array_values(C('TMPL_PARSE_STRING'));
	return str_ireplace($search, $replace, $filename);
}

/**************************************************************
 *
 *  将数组转换为JSON字符串（兼容中文）
 *  @param  array   $array      要转换的数组
 *  @return string      转换得到的json字符串
 *  @access public
 *
 *************************************************************/
function JSON($array) {
    arrayRecursive($array, 'urlencode', true);
    $json = json_encode($array);
    return urldecode($json);
}

/**************************************************************
 *
 *  使用特定function对数组中所有元素做处理
 *  @param  string  &$array     要处理的字符串
 *  @param  string  $function   要执行的函数
 *  @return boolean $apply_to_keys_also     是否也应用到key上
 *  @access public
 *
 *************************************************************/
function arrayRecursive(&$array, $function, $apply_to_keys_also = false)
{
    static $recursive_counter = 0;
    if (++$recursive_counter > 1000) {
        die('possible deep recursion attack');
    }
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            arrayRecursive($array[$key], $function, $apply_to_keys_also);
        } else {
            $array[$key] = $function($value);
        }

        if ($apply_to_keys_also && is_string($key)) {
            $new_key = $function($key);
            if ($new_key != $key) {
                $array[$new_key] = $array[$key];
                unset($array[$key]);
            }
        }
    }
    $recursive_counter--;
}