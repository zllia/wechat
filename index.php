<?php 
require "./wechat.class.php";
$wechat = new Wechat;
//判断是来验证还是回复消息的
//get到echostr就是来验证的，否则就是发消息来的
if ($_GET['echostr']) {
	$wechat->valid();
}else{
	$wechat->responseMsg();
}




