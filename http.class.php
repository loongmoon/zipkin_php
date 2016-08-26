<?php
/**
 * HTTP公用类
 * @author liu
 *
 */
class http{
	//是否为ajax
	public static function isAjax() {
		if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
				return true;
		}
		return false;
	}

	//访问者IP
	public static function clientIP(){
		 if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown")){
                        $ip = getenv("HTTP_CLIENT_IP");
                }else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown")){
                        $ip = getenv("HTTP_X_FORWARDED_FOR");
                }else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown")){
                        $ip = getenv("REMOTE_ADDR");
                }else if (isset($_SERVER['HTTP_CLIENT_IP']) && strcasecmp($_SERVER['HTTP_CLIENT_IP'], "unknown")){
                        $ip = $_SERVER['HTTP_CLIENT_IP'];
                }else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], "unknown")){
                        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
                }else if (isset($_SERVER['REMOTE_ADDR']) && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown")){
                        $ip = $_SERVER['REMOTE_ADDR'];
                }else{
                        $ip = "unknown";
                }
                return $ip;
	}

 /**
     * Do a 302
     *
     * @param string $url
     * @param integer $seconds
     */
	public static function go($url, $seconds=0, $target='') {
		if($target) {
			$target = 'target="'.$target.'"';
		}
		$str = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">';
		$str .= '<HTML><HEAD><TITLE> redirecting...... </TITLE>';
		$str .= '<meta http-equiv="refresh" content="'.$seconds.';url='.$url.'" '.$target.' >';
		$str .= '</HEAD><BODY></BODY></HTML>';
		echo $str;
		die();
	}

	///////////////////////////////////////////////////////COOKIE SESSION///////////////////////////
	//可能根据不同的客户端写cookie,
	public static function setCookie($key,$value,$expire=0,$path="/",$domain=""){
		if(empty($domain)){
			$domain = DOMAIN;
		}
		return @setcookie($key,$value,$expire,$path,$domain);
	}

	public static function delCookie($key, $path="/",$domain=""){
		if(empty($domain)){
			$domain = DOMAIN;
		}
		@setcookie($key,null,time()-3600,$path,$domain);
		if (array_key_exists($key,$_COOKIE)) {
			$_COOKIE[$key] = null;
			unset($_COOKIE[$key]);
		}
	}

	public static function COOKIE($key, $defaultValue=""){
		return is_array($_COOKIE)&&array_key_exists($key,$_COOKIE) ? $_COOKIE[$key] : $defaultValue;
	}


	public static function SESSION($key, $defaultValue=""){
		return is_array($_SESSION)&&array_key_exists($key,$_SESSION)?$_SESSION[$key]:$defaultValue;
	}

	public static function setSession($key,$value){
		$_SESSION[$key]=$value;
	}

	public static function delSession($key){
		if (array_key_exists($key,$_SESSION)) {
			$_SESSION[$key]=null;
			unset($_SESSION[$key]);
		}
	}

	public static function sessionDestroy(){
		return @session_destroy();
	}

	public static function GET($url,$data = array() , $cookie = array() ,$header = array(), $timeout = 5){
		return self::PHPGet($url,$data , $cookie , $header, $timeout);
	}

	public static function POST($url,$data = array() , $cookie = array() ,$header = array(), $timeout = 5){
		return self::PHPPost($url,$data , $cookie , $header, $timeout);
	}

	//http get
	public static function PHPGet($url,$data = array() , $cookie = array() ,$header = array() ,$timeout = 5){
	    $query = self::make_query($data);
        $curl = curl_init(); // 启动一个CURL会话
		if (empty($header)) {
			$header = array();
		}
		if (!empty($GLOBALS['HEADER_TRACEID'])) {
			array_push($header, $GLOBALS['HEADER_TRACEID']);
		}
		if (!empty($GLOBALS['HEADER_SPANID'])) {
			array_push($header, $GLOBALS['HEADER_SPANID']);
		}
		if (!empty($GLOBALS['HEADER_PARENTSPANID'])) {
			array_push($header, $GLOBALS['HEADER_PARENTSPANID']);
		}
        curl_setopt($curl, CURLOPT_URL, $url.'?'.$query); // 要访问的地址
   		if(!empty($header)) curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        if(!empty($cookie)){
            $i = 0;
            $str = '';
            foreach($cookie as $key => $val){
                if($i > 0) $str .= ';';
                $str .= $key.'='.urlencode($val);
                $i = 1;
            }
            curl_setopt($curl, CURLOPT_COOKIE, $str);
        }

	    curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
	    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
	    curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
		curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);   //只需要设置一个秒的数量就可以
	    curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
	    $totalline = curl_exec($curl); // 执行操作
	    if (curl_errno($curl)) {
	       echo 'Errno:'.curl_error($curl);//捕抓异常
	    }

        curl_close($curl); // 关闭CURL会话

        return $totalline;
	}


	//https get
	public static function PHPGetSSL($url,$data = array() , $cookie = array() ,$header = array() ,$timeout = 5){
		$query = self::make_query($data);
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url.'?'.$query); // 要访问的地址
		if(!empty($header)) curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        if(!empty($cookie)){
            $i = 0;
            $str = '';
            foreach($cookie as $key => $val){
                if($i > 0) $str .= ';';
                $str .= $key.'='.urlencode($val);
                $i = 1;
            }
            curl_setopt($curl, CURLOPT_COOKIE, $str);
        }

	    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
	    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); // 从证书中检查SSL加密算法是否存在
	    curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
	    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
	    curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
		curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);   //只需要设置一个秒的数量就可以
	    curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
	    $totalline = curl_exec($curl); // 执行操作
	    if (curl_errno($curl)) {
	       echo 'Errno'.curl_error($curl);//捕抓异常
	    }

        curl_close($curl); // 关闭CURL会话

        return $totalline;
	}

	//https post
	public static function PHPPostSSL($url,$data = array() , $cookie = array() , $header = array() ,$timeout = 5){
        $query = self::make_query($data);
		$curl = curl_init(); // 启动一个CURL会话
	    curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
	    if(!empty($header)) curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        if(!empty($cookie)){
            $i = 0;
            $str = '';
            foreach($cookie as $key => $val){
                if($i > 0) $str .= ';';
                $str .= $key.'='.urlencode($val);
                $i = 1;
            }
            curl_setopt($curl, CURLOPT_COOKIE, $str);
        }

	    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
	    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); // 从证书中检查SSL加密算法是否存在
	    curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
	    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
	    curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
	    curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
	    curl_setopt($curl, CURLOPT_POSTFIELDS, $query); // Post提交的数据包
		curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);   //只需要设置一个秒的数量就可以
	    curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
	    $totalline = curl_exec($curl); // 执行操作
	    if (curl_errno($curl)) {
	       echo 'Errno'.curl_error($curl);//捕抓异常
	    }

        curl_close($curl); // 关闭CURL会话

        return $totalline;
	}

	//
    public static function PHPPost($url,$data = array() , $cookie = array() , $header = array() ,$timeout = 5){
        $query = self::make_query($data);
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        if(!empty($header)) curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        if(!empty($cookie)){
            $i = 0;
            $str = '';
            foreach($cookie as $key => $val){
                if($i > 0) $str .= ';';
                $str .= $key.'='.urlencode($val);
                $i = 1;
            }
            curl_setopt($curl, CURLOPT_COOKIE, $str);
        }

        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $query); // Post提交的数据包
		curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);   //只需要设置一个秒的数量就可以
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $totalline = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
           echo 'Errno'.curl_error($curl);//捕抓异常
        }

        curl_close($curl); // 关闭CURL会话

        return $totalline;
    }

    //
    private static function make_query($data = array()){
        $str = '';
		if(!empty($data) && is_array($data)){
			if(function_exists("http_build_query")) {
				$str = http_build_query($data);
			}else{
				foreach($data as $k => $v){
					if($str !== ''){
						$str .= '&';
					}
					$str .= $k.'='.urlencode($v);
				}
			}
		}

        if(!empty($data) && is_string($data)){
        	$str = $data;
        }

        return $str;
    }


	public static function uploadByCURL($post_data,$post_url){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_URL, $post_url);
		curl_setopt($curl, CURLOPT_POST, 1 );
		curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($curl);
		$error = curl_error($curl);
		return $error ? $error : $result;
	}

	public static function request_format($keys = array(),$default = array() ){
		if(empty($keys)) return array();
        if(!is_array($keys)) $keys = array($keys);
        if(!is_array($default)) $default = array($default);
        $request = $_REQUEST;
		$result = array();
		foreach($keys as $k => $v){
			if(isset($request[$v])){
				if(is_numeric($request[$v])){
					$result[$v] = intval($request[$v]);
				}else if(is_string($request[$v])){
					$result[$v] = htmlspecialchars(trim($request[$v]));
				}else{
					$result[$v] = $request[$v];
				}
			}else{
				if(isset($default[$k])){
					$result[$v] = $default[$k];
				}else{
					$result[$v] = '';
				}
			}
		}

		return $result;
	}
	
	public function isPrivateIP($ip) {
        return (
            ($ip & 0xFF000000) == 0x00000000 || # 0.0.0.0/8
            ($ip & 0xFF000000) == 0x0A000000 || # 10.0.0.0/8
            ($ip & 0xFF000000) == 0x7F000000 || # 127.0.0.0/8
            ($ip & 0xFFF00000) == 0xAC100000 || # 172.16.0.0/12
            ($ip & 0xFFFF0000) == 0xA9FE0000 || # 169.254.0.0/16
            ($ip & 0xFFFF0000) == 0xC0A80000);  # 192.168.0.0/16
    }
}
?>
