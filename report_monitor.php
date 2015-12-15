<?php
include 'config.php';
date_default_timezone_set('PRC');
$date = date("Y-m-d H:i:s");
//选择数据库连接
$db_link = mysql_connect($db_host, $db_user, $db_pass);// or die(message(mysql_error()));
$session = get_session(6);
//选择数据库
mysql_select_db($db_name, $db_link);// or die(message(mysql_error()));

//转换本地时间
$check_date = strtotime (date ("H:i:s"));

//检查报警信息
$sql_report_use =  "select * FROM `m_use` where STATUS = 'OK'";
$result_use = mysql_query($sql_report_use, $db_link);
while ($row_use = mysql_fetch_array($result_use)){

	$use_report_host = $row_use['HOSTNAME']."";
	$use_report_time = $row_use['UP_TIME']."";
	$use_report_cpu = $row_use['CPU_USE']."";
	$use_report_mem = $row_use['MEM_USE']."";
	$use_report_disk = $row_use['DISK_USE']."";
	$use_report_load = $row_use['LOAD']."";

//转换更新时间
$last_up_time = strtotime($use_report_time);
$static = ceil($check_date-$last_up_time);
//print "$use_report_host $static static\n";

//检查客户端是否中断更新时间
if($static > 75){
	$report_monitor = "select * FROM m_report where HOSTNAME = '$use_report_host'";
	$result_monitor = mysql_query($report_monitor,$db_link);
	$row_monitor_status = mysql_fetch_array($result_monitor);

	$report_monitor_status = $row_monitor_status['REPORT_STATUS']."";
	
	//判定更新状态是否是down
	if ($report_monitor_status != DOWN){
		//更新down的状态到report的数据库
		$sql_report_monitor = "insert into m_report (HOSTNAME,REPORT_STATUS,UP_TIME,CONTROL,SESSION) values ('$use_report_host','DOWN','$date','OK','$session') on duplicate key update HOSTNAME='$use_report_host',REPORT_STATUS='DOWN',UP_TIME='$date',SESSION='$session'";
		$result_report_monitor = mysql_query($sql_report_monitor,$db_link);
		}	
	}else { 	
        $report_monitor_use = "select * FROM m_report where HOSTNAME = '$use_report_host' and CONTROL != 'STOP'";
        $result_monitor_use = mysql_query($report_monitor_use,$db_link);
        $row_monitor_status_use = mysql_fetch_array($result_monitor_use);

        $report_monitor_status_host = $row_monitor_status_use['HOSTNAME']."";
        $report_monitor_status_cpu = $row_monitor_status_use['R_CPU']."";
        $report_monitor_status_disk = $row_monitor_status_use['R_DISK']."";
        $report_monitor_status_load = $row_monitor_status_use['R_LOAD']."";
	$report_monitor_status_mem = $row_monitor_status_use['R_MEM']."";
	$report_monitor_status_cont = $row_monitor_status_use['CONTROL']."";
	//若状态正常,则判断使用率是否超过阀值
	if ($report_monitor_status_cpu != OK){
	}else{
		if ($use_report_cpu > 80){
			$sql_report_use_cpu = "insert into m_report (HOSTNAME,R_CPU,UP_TIME) values ('$report_monitor_status_host','WARN-$use_report_cpu','$date') on duplicate key update HOSTNAME='$report_monitor_status_host',R_CPU='WARN-$use_report_cpu',UP_TIME='$date'";
			$result_report_monitor_use_cpu = mysql_query($sql_report_use_cpu,$db_link);
			//print "$date -  $report_monitor_status_host - use_report_cpu is $use_report_cpu\n";
		}
	}
	if ($report_monitor_status_mem != OK){
	}else{
		if ($use_report_mem > 60){
			//将结果交给report处理
			$sql_report_use_mem = "insert into m_report (HOSTNAME,R_MEM,UP_TIME) values ('$report_monitor_status_host','WARN-$use_report_mem','$date') on duplicate key update HOSTNAME='$report_monitor_status_host',R_MEM='WARN-$use_report_mem',UP_TIME='$date'";
			$result_report_monitor_use_mem = mysql_query($sql_report_use_mem,$db_link);
			//print "$date -  $report_monitor_status_host -  $report_monitor_status_mem\n";
		}
	}
	
	if ($report_monitor_status_disk != OK){
	}else{
		if ($use_report_disk > 80){
			$sql_report_use_disk = "insert into m_report (HOSTNAME,R_DISK,UP_TIME) values ('$use_report_host','WARN-$use_report_disk','$date') on duplicate key update HOSTNAME='$use_report_host',R_DISK='WARN-$use_report_disk',UP_TIME='$date'";
			$result_report_monitor_use_disk = mysql_query($sql_report_use_disk,$db_link);
			//将结果交给report处理
			//print "use_report_disk is $use_report_disk\n";
		}
	}
	if ($report_monitor_status_load = OK){
	}else{
		if ($use_report_load > 20){
			$sql_report_use_load = "insert into m_report (HOSTNAME,R_LOAD,UP_TIME) values ('$use_report_host','WARN-$use_report_load','$date') on duplicate key update HOSTNAME='$use_report_host',R_LOAD='WARN-$use_report_load',UP_TIME='$date'";
			$result_report_monitor_use_load = mysql_query($sql_report_use_load,$db_link);
			//将结果交给report处理
			//print "use_report_load is $use_report_load\n";
		}
	}
   }
//关闭数据库
}
function get_session( $length = 8 ) {
    // 密码字符集
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    $p_session = '';
    for ( $i = 0; $i < $length; $i++ )
    {
        $p_session .= $chars[ mt_rand(0, strlen($chars) - 1) ];
    }

    return $p_session;
}
mysql_close($db_link);
?>
