<?php
	//****************************************************************
	//환경 설정 파일
	//****************************************************************
	include dirname(__FILE__).'/utf8.php';

	define("LANGUAGE", "ko");
	define("FRONT_DB_ENGINE", "MyISAM");
	date_default_timezone_set("Asia/Seoul"); 

	//db 계정
	//MASTER,WEB,BOARD,BACK,LOG,SOCK 키는 수정불가
	//키별 (아이피,아이디,비밀번호,디비명,포트) 수정
	$__DB_INFO_ARR = array(
		"MASTER"     => array("localhost","hooeni","hooeni","rhkd927dla","3306")
		//"SMS_SERVER" => array("192.168.180.230","HTTP_ASP","HTTP_ASP1111","HTTP_SMS","3306")
	);
	//$__DOMAIN_SUFFIX_SINGLE = array(".com", ".net", ".info", ".jp", ".kr", ".cc", ".cn", ".eu", ".biz", ".name", ".mobi", ".tv", ".org", ".jp", ".in", ".us", ".asia");
	//$__DOMAIN_SUFFIX_DOUBLE = array(".or.kr", ".co.kr", ".pe.kr", ".go.kr", ".ac.kr", ".mil.kr", ".com.cn");

	$strConnect_Master			= $__DB_INFO_ARR["MASTER"];

	#관리자 IP
	$__IP_ADDR_ADMIN = array("");

	include dirname(__FILE__).'/func.php';
	include dirname(__FILE__).'/db_class.php';
?>