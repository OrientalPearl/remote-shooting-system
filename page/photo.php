<?php

$filename = $_REQUEST['filename'];
$serial = $_REQUEST['serial'];

$pathname = "/mnt/photos/$serial/jpg/$filename";


//file_put_contents("/tmp/phpdebug", $pathname);

$img = file_get_contents($pathname, true);
//使用图片头输出浏览器
header("Cache-Control: private, max-age=10800, pre-check=10800");
header("Pragma: private");
header("Expires: Mon, 26 Jul 2997 05:00:00 GMT");
header("Content-Type: image/jpeg;text/html; charset=utf-8");
echo $img;
exit;

?>