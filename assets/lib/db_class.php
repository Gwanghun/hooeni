<?php
  if(!function_exists('debugLog')){
    function debugLog($name, $value, $mode="info"){
      global $__DEBUG_MODE;
      $debugMode = array(
          "info"		=> 1,
          "debug"		=> 2,
          "warning"	=> 3,
          "error"		=> 4,
          "none"		=> 5
        );
      if($debugMode[$mode] >= $debugMode[$__DEBUG_MODE]) {
        if($file = fopen($_SERVER['DOCUMENT_ROOT']."/debug.log", "a")) {
          fwrite($file, iconv("utf-8", "euc-kr", "[" . date("Y-m-d H:i:s") . " - ". $_SERVER['REMOTE_ADDR'] . "] [" . $mode . "] [".$_SERVER['SCRIPT_NAME']."] " . $name . " : " . $value . "\n"));
          fclose($file);
        }
      }
    }
  }

  class DB extends mysqli
  {
    var $g_proc_name = "";
    var $g_prmNmarrIndex = 0;
    var $g_prmNmarr = null;		//bind() param_name array
    var $g_prmVrarr = null;		//bind() var array
    var $g_prmTparr = null;		//bind() type array
    var $g_prmIoarr = null;		//bind() is_output array
    var $g_prmExarr = null;		//execut() 실행시 매개변수 배열
    var $g_isConnect = false;

    var $g_debugMode = false;	//procedure debugging시 사용

    var $tmpDBName = "";
    var $tmpPrevQuery = "";
    var $lastQuery = "";

    function __construct($dbinfo_arr, $persistent = true){
      $hostPrefix = "";
      $version = explode('.',PHP_VERSION);
      if(($version[0] * 10000 + $version[1] * 100 + $version[2]) < 50300 || $_SERVER["REMOTE_ADDR"] == "127.0.0.1") {
        $hostPrefix = "";
      } else {
          if($persistent) {
          $hostPrefix = "p:";
          } else {
          $hostPrefix = "";
          }
      }
      $host	= $hostPrefix . $dbinfo_arr[0];
      $user 	= $dbinfo_arr[1];
      $pass 	= $dbinfo_arr[2];
      $db		= $dbinfo_arr[3];	
      $port	= $dbinfo_arr[4];

      $this->tmpDBName = $db;

      @parent::__construct($host, $user, $pass, $db, $port);
      
        if(!$this->ping()) {
        debugLog("try reconnect", $db, "debug");
        @parent::close();
        @parent::__construct($dbinfo_arr[0], $user, $pass, $db, $port);
        }
        
      if ($this->connect_error){
            $msg = $this->connect_error; 
            debugLog("Database", $msg, "error");
            exit;
          } else {
            $this->set_charset("utf8");
            $this->g_isConnect = true;
          }
      }

      function query($sql){
        if($this->g_debugMode) {
          debugLog("Debugging Query", $sql, "debug");
        }

        if(!$this->ping()) {
          debugLog("disconnected", "\nDB Name : " . $this->tmpDBName . "\nPrev Query : " . $this->tmpPrevQuery . "\nCurr Query : " . $sql, "error");
        } else {
        $this->tmpPrevQuery = $sql;
      }

      $this->lastQuery = $sql;

        $result = @parent::query($sql);
        if(!$result) debugLog("query", $this->error . PHP_EOL . $sql, "error");
        
      return $result;
    }

    function queryf(){
      $args = func_get_args();

      if(is_array($args[1])) {
        $tmp = $args[1];
        array_unshift($tmp, $args[0]);
        $args = $tmp;
      }

      if(count($args) > 1) {
        for($i=1;$i<count($args);$i++) $args[$i] = $this->escape_string($args[$i]);
        $query = call_user_func_array('sprintf',$args);
      } else {
        if(strstr($args[0], '%%')) {
          $query = sprintf($args[0]);
        } else {
          $query = $args[0];
        }
      }
      
      return $this->query($query);
    }

    function getLastQuery() {
      return $this->lastQuery;
    }

      # procedure bin 처리 함수
      function sql_init($proc_name, $debugging = false){
        $this->g_debugMode = $debugging;
        $this->g_proc_name = $proc_name;
        //변수값 초기화
        $this->g_prmNmarr 		= null;
        $this->g_prmVrarr 		= null;
        $this->g_prmTparr 		= null;
        $this->g_prmIoarr 		= null;
        $this->g_prmExarr 		= null;
        $this->g_prmNmarrIndex 	= 0;
      }
      # bind 처리함수
      # sql_bind(string $param_name,mixed &$var,int $type [, bool $is_output= false [, bool $is_null= false [, int $maxlen= -1 ]]] )
      function sql_bind($param_name,&$var,$type,$is_output=false,$is_null=false,$maxlen=-1){
        $this->g_prmNmarr[$this->g_prmNmarrIndex] = $param_name;
        $this->g_prmVrarr[$this->g_prmNmarrIndex] = &$var;
        $this->g_prmTparr[$this->g_prmNmarrIndex] = $type;
        $this->g_prmIoarr[$this->g_prmNmarrIndex] = $is_output;
        
        $exeParm = $is_output ? $param_name : $var;	//is_output 이면 $param_name 값 세팅

      if($type==SQLVARCHAR && !$is_output){		//문자형이고 output이 아니면
          $exeParm = str_replace("\\'", "''''", $exeParm);
          $this->g_prmExarr[$this->g_prmNmarrIndex] = "'".str_replace("\\\\\\'\\\\\\'", "''", str_replace("'", "\\\\\\'", str_replace("\\", "\\\\\\\\\\\\\\\\", $exeParm)))."'";
        }else{
          $this->g_prmExarr[$this->g_prmNmarrIndex] = $exeParm;
        }

      $this->g_prmNmarrIndex++;
      }
      # bind 실행함수 
      function sql_execute(){
        $exe_param = "";	//프로시져 매개변수 문자열
        $output_index = 0;	//output 개수
        $output_arr = array();  //output 변수명
        
        if($this->g_prmNmarrIndex){
          $exe_param = implode(",",$this->g_prmExarr);
          //output 변수 분리
          for($i=0;$i<count($this->g_prmIoarr);$i++){
            if($this->g_prmIoarr[$i]){
              $output_arr[$output_index] = $this->g_prmNmarr[$i];
              $output_index++;
            }
          }
        }
      //프로시져 호출 
        $result = $this->query("call ".$this->g_proc_name."(". $exe_param .")");


      if($this->more_results()) {
          $this->next_result();
      }


        if($output_index){	//output 이 있으면
          $output_str = implode(", ",$output_arr );	//@aa,@bb 형식
          $temp_result = $this->query("select ".$output_str);
          $row = $temp_result->fetch_assoc();
          $keys = array_keys($row);		//key,value 분리
          $values = array_values($row);
          for($i=0;$i<count($keys);$i++){
            //전체 bind 배열에서 해당 output 의 key 값을 변수명으로 검색 한후 &$var 에 입력
            $this->g_prmVrarr[array_search($keys[$i],$this->g_prmNmarr)] = $values[$i];
          }
          $temp_result->close();
        }
        
        return $result;
      }
      
    #오류처리
    function error_print(){
      echo "db err";
      exit;
    }
    
    function isConnect() {
      return $this->g_isConnect;
    }
    
    #연결종료
    function close(){
          $this->g_isConnect = false;
      @parent::close();
    }
    
    #소멸자
    function __destruct(){
      @parent::close();
    }
  }

  /*
  * MSSQL library가 없으면 오류가 나므로 세팅해서 사용해야 함.
  */
  if(!defined(SQLVARCHAR)) {
    define("SQLINT4", 		1);
    define("SQLVARCHAR",	2);
  }
?>