<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once("PHPMailer/src/Exception.php");
require_once("PHPMailer/src/PHPMailer.php");
require_once("PHPMailer/src/SMTP.php");

if (3 != count($argv)) {
	echo 'Parameter error need two: device serial and error msg';
	return 1;//error
}

//const
$secure = 'tls';
$subject = '设备故障';


//get from argvs
//$body = '<h1>Hello World</h1>';
$serial = $argv[1];
date_default_timezone_set("PRC");
if ($argv[2] == 1)
{
	$event = '市电消失';
}
else if ($argv[2] == 2)
{
	$event = '市电恢复';
}
else if ($argv[2] == 3)
{
	$event = '相机连接断开';
}
else if ($argv[2] == 4)
{
	$event = '相机连接恢复';
}
else if ($argv[2] == 5)
{
	$event = '服务器空间使用量已超过90%，请及时处理。';
}
else if ($argv[2] == 6)
{
	$event = '服务器空间使用量警告解除。';
}
else if ($argv[2] == 7)
{
	$event = '服务器空间已满，照片已停止上传。';
}
else if ($argv[2] == 8)
{
	$event = '照片上传恢复。';
}
else if ($argv[2] == 9)
{
	$event = '每日上传总量超过最大限制，照片已停止上传。';
}
else
{
	return -1;
}

$body="时间: ".date('Y-m-d H:i:s')."，装置序列号:".$argv[1]."，事件:".$event;


//get from mysql
$con = mysql_connect("localhost","root","123456");
if (!$con){
	echo 'Could not connect: ', mysql_error();
	return 1;//error
}

mysql_query("SET NAMES 'utf8'",$con); 

mysql_select_db("ysf", $con);

$result_conf = mysql_query("SELECT user_id FROM wrt_ysf_device WHERE serial='" . $serial . "'");
$row = mysql_fetch_array($result_conf);

$user_id = $row['user_id'];

mysql_query("INSERT INTO `wrt_ysf_event_log` (`serial`,`user_id`,`event`) VALUES ('".$argv[1]."','".$user_id."','".$event."')");

$result_conf = mysql_query("SELECT * FROM wrt_ysf_email");
  
while($row = mysql_fetch_array($result_conf)){
	$host = $row['email_server_address'];
	$port = $row['email_server_port'];
	$from_name = $row['email_sender_show'];
	$username = $row['email_sender'];
	$password = $row['email_auth_passwd'];
	$from = $row['email_sender'];
	if ($row['email_subject'] != "")
		$subject = $row['email_subject'];
	break;
}

$result_to_address = mysql_query("SELECT wrt_ysf_user.email FROM wrt_ysf_user INNER JOIN wrt_ysf_device ON wrt_ysf_user.id=wrt_ysf_device.user_id where wrt_ysf_device.serial=$serial");
  
while($row = mysql_fetch_array($result_to_address)){
	$to_address = $row['email'];
	break;
}

mysql_close($con);

if ($host == ""){
    echo 'Email config not ready';
	return 1;//error
}

if ($to_address == ""){
    echo 'Device $serial email config not ready';
	return 1;//error
}

$mail = new PHPMailer();
try {
	$mail->SMTPDebug = 0;
	$mail->isSMTP();
	$mail->SMTPAuth = true;
	$mail->Host = $host;
	$mail->SMTPAuth = true; 
	$mail->SMTPSecure = $secure;
	$mail->Port = $port;
	$mail->CharSet = 'UTF-8';
	$mail->FromName = $from_name;
	$mail->Username = $username;
	$mail->Password = $password;
	$mail->From = $from;
	$mail->isHTML(true);
	$mail->addAddress($to_address);
	$mail->Subject = $subject;
	$mail->Body = $body;
	$status = $mail->send();
	
	if ($status == 1){
		//echo " 1status " . $status . " ";
		return 0;//ok
	}
		
	else{
		//echo " 2status " . $status . " ";
		return 1;//error
	}
	
	//echo 'Message has been sent';
} catch (Exception $e) {
    echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
	return 1;//error
}
?>
