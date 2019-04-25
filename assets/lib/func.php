<?php
function isAdminIp() {
	global $__IP_ADDR_ADMIN;
	return in_array($_SERVER['REMOTE_ADDR'],$__IP_ADDR_ADMIN);
}


function fsock_post($host, $target, $posts, $port = 80){
	if(is_array($posts)){
		foreach( $posts as $name => $value ){
			$postValues .= ($postValues ? '&' : '').urlencode($name).'='.urlencode($value);
		}
	}else{
		$postValues = $posts;
	}

	$postLength = strlen($postValues);

	$request  = "POST $target HTTP/1.0\r\n";
	$request .= "User-Agent: ShoplogFSocket!@0909#$\r\n";
	$request .= "Host: $host\r\n";
	$request .= "Content-Type: application/x-www-form-urlencoded\r\n";
	$request .= "Content-Length: " . $postLength . "\r\n";
	$request .= "Connection: close\r\n";
	$request .= "\r\n";
	$request .= $postValues;

	$socket = fsockopen($host, $port, $errno, $errstr, 100);
	fwrite($socket, $request);

	$ret = "";
	while(!feof($socket))	$ret .= fgets($socket, 4096);
	fclose($socket);
	return $ret;
}

function getUserDBConnection($mst, $svr){
	global $strConnect_Master;

	$ConnMst = new DB($strConnect_Master);

	if($mst == "loga"){
		$dbname = "HTTP_MASTER";
	}else{
		$dbname = "HTTP_MASTER_FULL";
	}

	$sql = "SELECT db_ip, db_port, db_id, db_pass, db_name FROM ".$dbname.".DB_INFO WHERE asp_server_id='%s'";
	$rs = $ConnMst->queryf($sql, $svr)->fetch_assoc();
	$ConnMst->close();
	if($rs){
		$dbinfo = array($rs["db_ip"], $rs["db_name"], $rs["db_pass"], $rs["db_name"], $rs["db_port"]);
		return new DB($dbinfo);
	}else{
		return null;
	}
}


function toString($text){
	return iconv('UTF-16LE', 'UTF-8', chr(hexdec(substr($text[1], 2, 2))).chr(hexdec(substr($text[1], 0, 2))));
}
function toUnicode($word) {
//	$word = iconv('UHC', 'UTF-16LE', $word); // UTF-8 문서이므로 아래 처럼 변경
	$word = iconv('UTF-8', 'UTF-16LE', $word);
	$f = dechex(ord(substr($word,1,1)));
	$b = dechex(ord(substr($word,0,1)));
	if(strlen($f) < 2) {
		$f = "0".$f;
	}
	if(strlen($b) < 2) {
		$b = "0".$b;
	}
	return strtoupper($f.$b);
}


function unescape($text){
	return urldecode(preg_replace_callback('/%u([[:alnum:]]{4})/', 'toString', $text));
}
function escape($str) {
	$len = strlen($str);
	for($i=0,$s='';$i<$len;$i++) {
		$ck = substr($str,$i,1);
		$ascii = ord($ck);
		if($ascii > 127) {
			$s .= '%u'.toUnicode(substr($str, $i, 3)); // UTF-8이므로 3byte처리
			$i = $i + 2;// UTF-8이므로 3byte처리
		} else if($ascii >= 65 && $ascii <= 122 && $ascii != 92) { // 92 : "\"
			$s .= $ck;
		} else {
			$s .= (in_array($ascii, array(42, 43, 45, 47, 58, 64, 92, 95))) ? '%'.strtoupper(dechex($ascii)) : $ck;
		}
	}
	return $s;
}
function CutByte($str, $strlen,$bdotview=true) {
	$nLength = 0.0;
	$rValue = "";

	for($i = 0; $i < strlen($str); $i++) {
		$tmpStr = substr($str, $i, 1);
		$tmpAsc = ord($tmpStr);
		
		if($tmpAsc > 127) { 
			$tmpStr = substr($str, $i, 3); // UTF8 한글일때 3byte 가져와야 함.
			$nLength = $nLength + 1.4; // 한글일때 길이값 설정
			$i+=2; // 3byte가져 왔으므로 $i를 3 증가 시키기 위해
		} else if($tmpAsc >= 97 && $tmpAsc <= 122) {
			$nLength = $nLength + 0.75; // 영문소문자 길이값 설정
		} else if($tmpAsc >= 65 && $tmpAsc <= 90) {
			$nLength = $nLength + 1.0; // 영문대문자 길이값 설정
		} else {
			$nLength = $nLength + 0.6; // 그외 문자 일때
		}
		$rValue = $rValue . $tmpStr;
		if($nLength >= $strlen) {
			if($bdotview){
				$rValue = $rValue . "...";
			}else{
				$rValue = $rValue;
			}
			break;
		}
	}
	return $rValue;
}

function getContents($url, $method="GET", $timeout=3, $includeHeaders=false, $agent="") {
	$result = "";
	$url = parse_url($url);

	if (!isset($url['port'])) {
		if ($url['scheme'] == 'http') { $url['port']=80; }
		elseif ($url['scheme'] == 'https') { $url['port']=443; }
	}
	$url['query']=isset($url['query'])?$url['query']:'';

	$url['protocol']=$url['scheme'].'://';
	$eol="\r\n";
	
	if($agent){
		$agent_str = "User-Agent: ".$agent.$eol;
	}else{
		$agent_str = "";
	}
	if($method == "POST") {
		$headers =  "POST ".$url['protocol'].$url['host'].$url['path']." HTTP/1.0".$eol. 
					"Host: ".$url['host'].$eol. 
					"Referer: ".$url['protocol'].$url['host'].$url['path'].$eol. 
					"Content-Type: application/x-www-form-urlencoded".$eol. 
					"Content-Length: ".strlen($url['query']).$eol.$agent_str.
					$eol.$url['query'];
	} else {
		$headers =  "GET ".$url['path'].(isset($url['query'])?"?".$url['query']:"")." HTTP/1.0".$eol. 
					"Host: ".$url['host'].$eol.$agent_str.
					"Connection: Close".$eol.
					$eol;
	}

	$fp = fsockopen($url['host'], $url['port'], $errno, $errstr, $timeout); 

	if($fp) {
		fwrite($fp, $headers);

		stream_set_blocking($fp, TRUE); 
		stream_set_timeout($fp, $timeout); 
		$info = stream_get_meta_data($fp); 

		$result = '';

		while ((!feof($fp)) && (!$info['timed_out'])) { 
			$result .= fgets($fp, 128); 
			$info = stream_get_meta_data($fp); 
		} 
		fclose($fp);

		if ($info['timed_out']) { 
			return "Connection Timed Out!"; 
		} 

		if (!$includeHeaders) {
			//removes headers
			/*
			$pattern="/^.*\r\n\r\n/s";
			$result=preg_replace($pattern,'',$result);
			*/
			$result = str_replace(substr($result, 0, strpos($result, "\r\n\r\n")), "", $result);
		}

	} else {
		//return "$errstr ($errno)";
	}
	return $result;
}

function cutString($string, $n, $add='...'){
	$len=strlen($string);   //string length
	$newstring = "";
	$total=0;
	for($i=0;$i<$len;$i++){
		$asc=ord(substr($string,$i,1));
		if($asc>128){
			$newstring .= substr($string,$i,3);
			$i = $i+2;
		}else{
			$newstring .=  substr($string,$i,1);
		}
		$total++;
		if($total >= $n){
			break;
		}
	}
	if($i <$len){
		$newstring.=$add;
	}
	return $newstring;
}

function getPagingHTML($total, $scale, $link_cnt, $param='', $page=0){ //전체갯수, 목록갯수, 페이징버튼갯수, 파라미터
	if(empty($param)) $param = $_SERVER["QUERY_STRING"];
	$param = preg_replace('/[\?\&]*page=[0-9]+[\&]*/i', '', $param);
	if(!empty($param)) $param = '&amp;'.$param;
	if($page == 0) {
		$page = (isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1);
	}
	$total_page = ceil($total/$scale);
	$page_list  = ceil($page/$link_cnt)-1;

	$page_html = "";
	if($page_list>0){
		$prev_page  = ($page_list-1)*$link_cnt+1;
		$page_html .= '<a href="?page=1'.$param.'" class="btn_first"></a>'.chr(13);
		$page_html .= '<a class="btn_prev" href="?page='.$prev_page.$param.'"></a>'.chr(13);
	}
	
	$page_end = ($page_list+1)*$link_cnt;
	if($page_end > $total_page)	$page_end = $total_page;
	
	if($page_end == 1){
		$page_html .= '<a href="javascript:void(0);" class="loc_num on">1</a>'.chr(13);
	}else{
		for($setpage=($page_list*$link_cnt)+1; $setpage<=$page_end; $setpage++){
			if($setpage == $page){
				$page_html .= '<a href="javascript:void(0);" class="loc_num on">'.$setpage.'</a>'.chr(13);
			}else{
				$page_html .= '<a href="?page='.$setpage.$param.'" class="loc_num">'.$setpage.'</a>'.chr(13);
			}
		}
	}
	
	if($page_end < $total_page){
		$next_page  = ($page_list+1)*$link_cnt+1;
		$page_html .= '<a class="btn_next" href="?page='.$next_page.$param.'"></a>'.chr(13);
		$page_html .= '<a href="?page='.$total_page.$param.'" class="btn_last"></a>'.chr(13);
	}
	return $page_html.chr(13);
}


function avoidXSS($string, $type = "") {
	$string = trim($string);

	if($string == "") {
		return $string;
	}

	if($type == "") {
		$ret = str_replace("<", "&lt;", $string);
		$ret = str_replace(">", "&gt;", $ret);
		$ret = str_replace('"', "&quot;", $ret);
		$ret = preg_replace("/javascript/i", "", $ret);
		return $ret;
	} else if($type == "alpha") {
		if(ctype_alpha(str_replace("_", "", $string))) {
			return $string;
		} else {
			exit;
		}
	} else if($type == "num") {
		if(ctype_digit($string)) {
			return $string;
		} else {
			exit;
		}
	} else if($type == "alnum") {
		if(ctype_alnum(str_replace("_", "", $string))) {
			return $string;
		} else {
			exit;
		}
	}
}


function getQueryString($except=""){
	$arr = array();
	parse_str($_SERVER["QUERY_STRING"], $get);
	foreach($get as $n => $v){
		if($v == "" || $n == $except) continue;
		$arr[] = $n."=".$v;
	}
	return join("&", $arr);
}

function write_result_json($result, $data=array()){
	$data["result"] = $result;
	exit(json_encode($data));
}
?>