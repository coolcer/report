#!/xdfapp/server/php/bin/php
<?php
include 'config.php';
include 'report_monitor.php';
//include 'message.php';
date_default_timezone_set('PRC');
$date = date("Y-m-d H:i:s");
$check_date = strtotime (date ("H:i:s"));
$get_session_u = get_session(6);
//选择数据库连接
$db_link = mysql_connect($db_host, $db_user, $db_pass) or die(message(mysql_error()));

//选择数据库
mysql_select_db($db_name, $db_link) or die(message(mysql_error()));

//获取异常服务器信息
//$sql_report =  "select * FROM `m_report` where REPORT_STATUS = 'DOWN' and CONTROL != 'STOP'";
$sql_report =  "select * FROM `m_report` where REPORT_STATUS = 'DOWN'";

$result = mysql_query($sql_report, $db_link) or die(message(mysql_error()));
while ($row = mysql_fetch_array($result)){
	$report_host = $row['HOSTNAME']."";
	$use_session = $row['SESSION']."";
	$r_control = $row['CONTROL']."";
	//print "$report_host -----\n";
	//获取正常服务器信息
	$sql_use = "select * FROM `m_use` where HOSTNAME = '$report_host'";
	$result_use = mysql_query($sql_use,$db_link) or die (message(mysql_error()));
	$row_use = mysql_fetch_array($result_use);

	//use的服务器更新时间
	$use_up_time = $row_use['UP_TIME']."";
	$use_up_time_new = strtotime($use_up_time);

	//判断是否有更新
	$static = ceil($check_date-$use_up_time_new);
	//if ($static > 300 && $static < 309){
	if ($static > 100){
		if ($r_control != STOP){
			print "$date $report_host is down,But dont report\n";
		}
		
	} elseif ($static > 80){
	     if ($r_control != STOP){
		$mail_head = "Monitor Server Dwon";
		$long=array("changqingshuai@okjiaoyu.cn","coolcer@163.com");
		$count = count($long);
		for ($n=0;$n<$count;$n++){
			$mail_to = $long[$n];
			$off_report="关闭报警请戳:http://monitor.xk12.cn/offreport.php?ssid=ce187194a62013ff2'&'from=$mail_to'&'session=$use_session'&'hostname=$report_host'&'action=stop";
			$up_report="打开报警请戳: http://monitor.xk12.cn/offreport.php?ssid=ce187194a62013ff2'&'from=$mail_to'&'session=$use_session'&'hostname=$report_host'&'action=start";
			$mail = "$use_up_time $report_host 宕机... '\r'$off_report'\r'$up_report";
			mails($mail_to,$mail_head,$mail);
		}
		print "$date $report_host is down\n";
	
	   }else{
		print "sss\n";
		}
	}else{
		$updata_report_sql = "insert into m_report (HOSTNAME,REPORT_STATUS,SESSION,CONTROL) values ('$report_host','OK','$get_session_u','OK') on duplicate key update HOSTNAME='$report_host',REPORT_STATUS='OK',SESSION='$get_session_u',CONTROL='OK'";
		$result_use = mysql_query($updata_report_sql,$db_link)  or die (message(mysql_error()));
		print "$date $report_host  OK\n";
                $mail_head = "Monitor Server OK";
                $mail = "$date The $report_host is OK";
		$mail_to = "coolcer@163.com";
                mails($mail_to,$mail_head,$mail);
	}
}
///*
//获取使用率异常
$monitor_sql_report_use =  "select * FROM `m_report` where REPORT_STATUS != 'DOWN' and CONTROL != 'STOP' and U_CONTROL != 'STOP'";
$monitor_result_use_report = mysql_query($monitor_sql_report_use, $db_link) or die(message(mysql_error()));
while ($row_use_monitor = mysql_fetch_array($monitor_result_use_report)){

	$report_WARN_host = $row_use_monitor['HOSTNAME']."";
	$report_WARN_time = $row_use_monitor['UP_TIME']."";
	$WARN_TIME = strtotime($report_WARN_time);

	$monitor = array("R_CPU","R_MEM","R_LOAD","R_DISK");
	$count_m = count($monitor);
	for ($x=0;$x<$count_m;$x++){
		$cc = $monitor[$x];
		$report_WARN = $row_use_monitor[$cc]."";
		$WARN_IF = "/^((?!WARN).)*$/is";
		if (preg_match($WARN_IF,$report_WARN)){
		}else{
			//判断已故障时长
			$static_warn = ceil($check_date-$WARN_TIME);
			if ($static_warn > 100){
				print "$date $report_WARN_host $cc is $report_WARN,But dont report -- $static_warn\n";
				$updata_warn_sql = "insert into m_report (HOSTNAME,U_CONTROL) values ('$report_WARN_host','STOP') on duplicate key update HOSTNAME='$report_WARN_host',U_CONTROL='STOP'";
				$result_use_warn = mysql_query($updata_warn_sql,$db_link) or die(message(mysql_error()));
			} elseif ($static_warn > 70){ 
				print "$date $report_WARN_host -- $cc -- $report_WARN\n";
			} else { 
				$updata_warn_sql = "insert into m_report (HOSTNAME,U_CONTROL) values ('$report_WARN_host','STOP') on duplicate key update HOSTNAME='$report_WARN_host',U_CONTROL='STOP'";
				$result_use_warn = mysql_query($updata_warn_sql,$db_link) or die(message(mysql_error()));
				print "xxxxxxxxxxxxxxxxxx\n";
			}	
		}
	}
}
mysql_close($db_link);
//*/
function message($variable, $TYPE = 'ERR')
{
	$arr_type = array(
		'ERR' => "<pre style='color: red;'>Error: {$variable}</pre>\n",
		'WARN' => "<pre style='background-color: yellow;'>Warning: {$variable}</pre>\n",
		'INFO' => "<pre style='color: blue;'>Info: {$variable}</pre>\n",
		);
	//打印信息
	echo $arr_type[$TYPE];
}
function mails($mail_to,$mail_head,$mail) {
	if (isset($mail_head)){
		exec("/xdfapp/scripts/sendEmail -t $mail_to -u $mail_head -m $mail");
	}else{
                echo "$date not report\n";
        }
}
?>
