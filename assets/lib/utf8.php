<?php
//한글
if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"]=="on"){	
	session_cache_limiter('public');
}else{
	session_cache_limiter('nocache');
}
session_start();

//W3C P3P 규약설정
Header("P3P: CP=ALL CURa ADMa DEVa TAIa OUR BUS IND PHY ONL UNI PUR FIN COM NAV INT DEM CNT STA POL HEA PRE LOC OTC");
Header("Content-type: text/html; charset=UTF-8");
if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"]=="on"){
	
}else{
	Header("Cache-Control: Private, no-cache, no-store, must-revalidate");
	Header("Pragma: No-Cache");
	Header("Expires: -1000");
}
//-------------------------------------------------------------------------
// register_globals == "off" 문제 처리 
// $_GET['id'] === $id
//-------------------------------------------------------------------------

if(!empty($HTTP_GET_VARS)) $_GET = $HTTP_GET_VARS;
if(!empty($HTTP_POST_VARS)) $_POST = $HTTP_POST_VARS;
if(!empty($HTTP_COOKIE_VARS)) $_COOKIE = $HTTP_COOKIE_VARS;
if(!empty($HTTP_SESSION_VARS)) $_SESSION= $HTTP_SESSION_VARS;
if(!empty($HTTP_POST_FILES)) $_FILES = $HTTP_POST_FILES;
if(!empty($HTTP_SERVER_VARS)) $_SERVER = $HTTP_SERVER_VARS;
if(!empty($HTTP_ENV_VARS)) $_ENV = $HTTP_ENV_VARS;

if(count($_GET)) extract($_GET);
if(count($_POST)) extract($_POST);
if(count($_SERVER)) extract($_SERVER);

while(list($key,$value)=each($_COOKIE)){
	$temp_arr = explode("&",$_COOKIE[$key]);
	if(count($temp_arr) > 1 ){
		$_COOKIE[$key] = array();
		for($i=0;$i<count($temp_arr);$i++){
			$temp_arr2 = explode("=",$temp_arr[$i]);
			$_COOKIE[$key][$temp_arr2[0]] = $temp_arr2[1];
		}
	}
}
?>