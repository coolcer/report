#!/xdfapp/server/php/bin/php
<?php
include 'config.php';
include 'report_monitor.php';
//include 'message.php';
date_default_timezone_set('PRC');
$mail_to = "op@okjiaoyu.cn";
$date = date("Y-m-d H:i:s");
$check_date = strtotime (date ("H:i:s"));

//选择数据库连接
$db_link = mysql_connect($db_host, $db_user, $db_pass) or die(message(mysql_error()));

//选择数据库
mysql_select_db($db_name, $db_link) or die(message(mysql_error()));

//获取异常服务器信息
$sql_report =  "select * FROM `m_report` where REPORT_STATUS = 'DOWN' and CONTROL != 'STOP'";
$result = mysql_query($sql_report, $db_link) or die(message(mysql_error()));
while ($row = mysql_fetch_array($result)){
	$report_host = $row['HOSTNAME']."";

	//获取正常服务器信息
	$sql_use = "select * FROM `m_use` where HOSTNAME = '$report_host'";
	$result_use = mysql_query($sql_use,$db_link) or die (message(mysql_error()));
	$row_use = mysql_fetch_array($result_use);

	//use的服务器更新时间
	$use_up_time = $row_use['UP_TIME']."";
	$use_up_time_new = strtotime($use_up_time);

	//判断是否有更新
	$static = ceil($check_date-$use_up_time_new);
	if ($static > 100){
		print "$date $report_host is down,But dont report\n";
		
	} elseif ($static > 70){
		$mail_head = "Monitor Server is Dwon";
		$mail = "$date The $report_host is Down Down Down";
		mails($mail_to,$mail_head,$mail);
		print "$date $report_host is down\n";
	} else{
		$updata_report_sql = "insert into m_report (HOSTNAME,REPORT_STATUS) values ('$report_host','OK') on duplicate key update HOSTNAME='$report_host',REPORT_STATUS='OK'";
		$result_use = mysql_query($updata_report_sql,$db_link)  or die (message(mysql_error()));
		print "$date $report_host is OK\n";
                $mail_head = "Monitor Server is OK";
                $mail = "$date The $report_host is OK";
                mails($mail_to,$mail_head,$mail);
	}
}
//获取使用率异常
$monitor_sql_report_use =  "select * FROM `m_report` where REPORT_STATUS != 'DOWN' and CONTROL != 'STOP' and U_CONTROL != 'STOP'";
$monitor_result_use_report = mysql_query($monitor_sql_report_use, $db_link) or die(message(mysql_error()));
while ($row_use_monitor = mysql_fetch_array($monitor_result_use_report)){

	$report_WARN_host = $row_use_monitor['HOSTNAME']."";
	$report_WARN_time = $row_use_monitor['UP_TIME']."";
	$WARN_TIME = strtotime($report_WARN_time);

	$monitor = array("R_CPU","R_MEM","R_LOAD","R_DISK");
	for ($x=0;$x<$arraylong;$x++){
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
function mails($mail_to,$mail_head,$mail)
{
	if (isset($mail_head)){
		exec("/xdfapp/scripts/sendEmail -t $mail_to -u $mail_head -m $mail");
	}else{
                echo "$date not report\n";
        }
}
?>
