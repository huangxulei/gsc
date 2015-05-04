<?php
/**
 * 获取数据字典
 * @param $key      //键值，方便查找数据
 * @param $fileName //字典文件名 目录Common/Dict/
 * @return mixed
 */
function dict($key = '', $fileName = 'Setting') {
    static $_dictFileCache  =   array();
    $file = MODULE_PATH . 'Common' . DS . 'Dict' . DS . $fileName . '.php';
    if (!file_exists($file)){
    	unset($_dictFileCache);
    	return null;
    }
    if(!$key && !empty($_dictFileCache)) return $_dictFileCache;
    if ($key && isset($_dictFileCache[$key])) return $_dictFileCache[$key];
    $data = require_once $file;
    $_dictFileCache = $data;
	return $key ? $data[$key] : $data;
}

/**
 * 扫描目录所有文件，并生成treegrid数据
 * @param string $path     目录
 * @param string $filter   过滤文件名
 * @param number $i        辅助用，这个不用传参
 * @return array
 */
function scandir_tree($path, $filter = SITE_DIR, &$i = 1){
	$result = array();
	$path   = realpath($path);

	$path   = str_replace(array('/', '\\'), DS, $path);
	$filter = str_replace(array('/', '\\'), DS, $filter);

	$list = glob($path . DS . '*');

	foreach ($list as $key => $filename){
		$result[$key]['id']    = $i;
		$result[$key]['name']  = str_replace($filter, '', $filename);
		$i++;
		if(is_dir($filename)){
			$result[$key]['type'] = 'dir';
			$result[$key]['size']  = '-';
			$result[$key]['mtime'] = '-';
			
			$result[$key]['state'] = 'closed';
			$result[$key]['children'] = scandir_tree($filename, $filter, $i);
			
			//easyui当children为空时会出现问题，因此在这里过滤
			if(empty($result[$key]['children'])){
				$result[$key]['iconCls'] = 'tree-folder';
				unset($result[$key]['state']);
				unset($result[$key]['children']);
			}
			
		}else{
			$result[$key]['type'] = 'file';
			$result[$key]['size']  = format_bytes(filesize($filename), ' ');
			$result[$key]['mtime'] = date('Y-m-d H:i:s', filemtime($filename));
		}
	}
	return $result;
}

function utf8_array_asort(&$array) {
    if(!isset($array) || !is_array($array)) {
        return false;
    }
    foreach($array as $k=>$v) {
        $array[$k] = iconv('UTF-8', 'GBK//IGNORE',$v);
    }
    asort($array);
    foreach($array as $k=>$v) {
        $array[$k] = iconv('GBK', 'UTF-8//IGNORE', $v);
    }
    return true;
}