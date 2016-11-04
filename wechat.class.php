<?php 
/********编写一个wechat类用来存放相关调用微信公众平台的接口方法*****/
//引入配置文件
require "./wechat.inc.php";
class Wechat{
	//封装属性
	private $appid;
	private $appsecret;
	//使用构造方法赋值（在对象实例化的时候会自动调用构造方法）
	public function __construct(){
		//给属性赋值
		$this->appid = APPID;
		$this->appsecret = APPSECRET;
		//文本模板
		$this->textTpl = "<xml>
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[%s]]></MsgType>
        <Content><![CDATA[%s]]></Content>
        <FuncFlag>0</FuncFlag>
        </xml>";
        //每一条新闻就是一个item，将item拼接出来新闻
        $this->itemTpl = "<item>
                    <Title><![CDATA[%s]]></Title>
                    <Description><![CDATA[%s]]></Description>
                    <PicUrl><![CDATA[%s]]></PicUrl>
                    <Url><![CDATA[%s]]></Url>
                    </item>";
        //将拼接的新闻放到新闻的模板中
    	$this->newsTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[news]]></MsgType>
                    <ArticleCount>%s</ArticleCount>
                    <Articles>%s</Articles>
                    </xml>";
	}
	//封装请求方法
	public function request($url,$https=true,$method='get',$data=null){
		//http,https,get,post等请求种类
		// 1.初始化curl
		$ch = curl_init($url);
		//2.设置相关配置文件
		//返回值return设置
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		//判断是否是HTTPS请求
		if ($https === true) {
			curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
			curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
		}
		//判断请求方法get，post
		if ($method === 'post') {
			curl_setopt($ch,CURLOPT_POST,true);
			curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
		}
		//3.发送请求
		$str = curl_exec($ch);
		//4.关闭连接
		curl_close($ch);
		//返回请求的数据
		return $str;
	}
	//测试request方法
	public function testRequest(){
		$url = "https://www.baidu.com";
		$content = $this->request($url);
		var_dump($contnent);
	}
	//获取access_token
	public function getAccessToken(){
		//1.$url
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appid."&secret=".$this->appsecret;
		//判断请求方式get
		//发送请求//返回json
		$content = $this->request($url);
		//处理返回值
		//var_dump($content);
		$content = json_decode($content);
		$access_token = $content->access_token;
		//echo $access_token;
		//8bCKT-4qaw5h78nqAKRDgX8nPaD-aYy9MBaQVAQhVN_gYa9BD_aRWuvazVZhCWiU847NBZ3Tk2ZxV0Rlmc1-THHoXUl5H9nKkWOKj8rsBOc4AupIhISDUktWWucUobuvRHGiAIAFVU
		return $access_token;
	}
	//获取ticket
	public function getTicket($scene_id=66,$tmp=true,$expire_seconds=604800){//给tmp默认true
		//1.url
		$url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=".$this->getAccessToken();
		//2.get,post//post方式，需要生成data
		//判断生成临时的还是永久的，来给不同的data
		if($tmp === true){//临时的，给data
			$data = '{"expire_seconds": 604800, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": 123}}}';//json字符串
		}else{//永久的data
			$data = '{"action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": 123}}}';//json字符串
		}
		//发送请求//返回json
		$content = $this->request($url,true,'post',$data);
		//处理数据//转为对象
		$content = json_decode($content);
		$ticket = $content->ticket;
		//echo $ticket;
		//gQEm8ToAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL05EajU2dmZtNGR5WUpSSlQxUmFNAAIEK4QYWAMEgDoJAA==
		return $ticket;
	}
	//通过ticket换取二维码
	public function getQRCode(){
		//url
		$url = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=".$this->getTicket();
		//请求方式get
		//发送请求//返回图片
		$content = $this->request($url);
		//返回数据处理//保存图片
		$rs = file_put_contents('./qrcode.jpg',$content);
		//file_put_contents('./qrcode.jpg',$content);
		var_dump($rs);
	}
	//删除菜单
	public function delMenu(){
		//url//第三方接口url到对应的文档中找
		$url = "https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=".$this->getAccessToken();
		//get,post//url传值，get
		//发送请求
		$content = $this->request($url);
		//处理返回值
		$content = json_decode($content);
		if ($content->errmsg == 'ok') {
			echo "删除菜单成功";
		}else{
			echo "删除菜单失败！".'<br>';
			echo "错误代码：".$content->errcode;
		}
	}
	//创建菜单
	public function createMenu(){
		//1.url
		$url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$this->getAccessToken();
		//2.get,post//post
		$data = '
			{
			     "button":[
			     {	
			          "type":"click",
			          "name":"今日新闻",
			          "key":"news"
			      },
			      {
			           "name":"菜单",
			           "sub_button":[
			           {	
			               "type":"view",
			               "name":"搜索",
			               "url":"http://www.baidu.com/"
			            },
			            {
			               "type":"view",
			               "name":"视频",
			               "url":"http://v.qq.com/"
			            },
			            {
			               "type":"location_select",
			               "name":"地图",
			               "key":"rselfmenu_2_0"
			            }]
			       }]
			 }
		';
		//3.发送请求//返回json
		$content = $this->request($url,true,'post',$data);
		//处理返回数据
		$content = json_decode($content);
		if ($content->errmsg == 'ok') {
			echo "创建菜单成功";
		}else{
			echo "创建菜单失败！".'<br>';
			echo "错误代码：".$content->errcode;
		}
	}
	//显示菜单
	public function showMenu(){
		//url
		$url = "https://api.weixin.qq.com/cgi-bin/menu/get?access_token=".$this->getAccessToken();
		//get,post//get
		//发送请求
		$content = $this->request($url);
		//处理返回数据
		var_dump($content);
	}
	//获取用户openid
	public function getUserList(){
		//url,openid不给默认从第一个开始
		$url = 'https://api.weixin.qq.com/cgi-bin/user/get?access_token='.$this->getAccessToken().'&next_openid=';
		//get,post
		//发送请求//返回json
		$content = $this->request($url);
		//处理返回数据
		//var_dump($content);
		$content = json_decode($content);
		//输出对象里面的信息//其中的用户列表为数组
		echo "关注用户数为:".$content->total.'<br>';
		echo "本次拉取数为:".$content->count.'<br>';
		echo "用户列表为为:<br>";
		//遍历输出用户列表数组中的内容
		foreach ($content->data->openid as $key => $value) {
			echo $key.'####<a href="http://www.zend.com/wechat/getUserInfo.php?openid='.$value.'"</a>'.$value.'<br>';
		}
	}
	//通过openid获取用户基本信息
	public function getUserInfo(){
		//要给一个openid
		$openid = $_GET['openid'];
		//url
		$url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$this->getAccessToken()."&openid=".$openid."&lang=zh_CN";
		//get，post//get
		//发送请求//json
		$content = $this->request($url);
		//处理返回数据//转成对象
		$content = json_decode($content);
		//var_dump($content);
		switch ($content->sex) {
			case '0':
				$sex = "未知";
				break;
			case '1':
				$sex = "男";
				break;
			case '2':
				$sex = "女";
				break;
		}
		echo "昵称：".$content->nickname."<br />";
		echo "性别：".$sex.'<br>';
		echo "省份：".$content->province."<br />";
		echo "头像<img src='".$content->headimgurl."' style='width:200'/><br />";
	}

	//验证调用的方法
  //在管理平台输入的url地址之后
  //微信公众平台，会根据填写url地址，进行访问操作
  //并且会传输一些字符串过来（微信公众平台根据填的url传输回来的）
  //微信公众平台的操作
  public function valid(){
          $echoStr = $_GET["echostr"];
          //valid signature , option
          //如果调用的checkSignature方法返回真，
          //就继续下面操作
          if($this->checkSignature()){
            echo $echoStr;
            //结束脚本
            exit;
          }
  }
  //第三方服务器，接收和发送消息的方法
  public function responseMsg(){
      //get post data, May be due to the different environments
     //接收用户发过来的xml数据，通过微信平台转发过来
      $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

      //extract post data
      //判断用户数据是否为空
      if (!empty($postStr)){
          /* libxml_disable_entity_loader is to prevent XML eXternal Entity Injection,
             the best way is to check the validity of xml by yourself */
          //微信服务建议开启的xml安全操作
          libxml_disable_entity_loader(true);
          //xml转为对象
          $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
          
          //判断消息类型
          switch ($postObj->MsgType) {
          	case 'text':
          		$this->_doText($postObj);//文本信息的处理方法
          		break;
          	case 'image':
          		$this->_doImage($postObj);//图片信息的处理方法
          		break;
          	case 'location':
          		$this->_doLocation($postObj);//地理位置消息的处理方法
          		break;
          	case 'event':
          		$this->_doEvent($postObj);//地理位置消息的处理方法
          		break;
          	default:
          		# code...
          		break;
          }
 
      }
  }

  public function _doText($postObj){
      $keyword = trim($postObj->Content);//去掉空格
      $time = time();
      //返回数据的xml模板 
      if(!empty( $keyword )){
        $msgType = "text";
        //$contentStr = "Welcome to wechat world!";
        //接入自动回复API（智能机器人）
        $url = "http://api.qingyunke.com/api.php?key=free&appid=0&msg=".$keyword;
        //post,get//get
        //发送请求
        $content = $this->request($url,false);
        //处理数据返回值
        $content = json_decode($content);
        
        $contentStr = $content->content;
        //拼接要回复的消息的xml字符串，，第一个参数是那个信息模板，后面的是模板里面缺的数据，第一个是发给谁，第二个是谁发的，时间，信息类型，信息内容
        $resultStr = sprintf($this->textTpl, $postObj->FromUserName, $postObj->ToUserName, $time, $msgType, $contentStr);
        //file_put_contents('./data.xml',$resultStr);//为什么不行呢
        echo $resultStr;
      }
  }

  public function _doImage($postObj){
  	$msgType = "text";
    $time = time();
  	$contentStr = $postObj->PicUrl;
  	$resultStr = sprintf($this->textTpl, $postObj->FromUserName, $postObj->ToUserName, $time, $msgType, $contentStr);
    file_put_contents('./data.xml',$resultStr);
    echo $resultStr;
  	/*$content = $this->request($PicUrl,)
  	file_put_contents()*/
  	$content = $this->request($postObj->PicUrl,false);
  	file_put_contents(time().'.jpg',$content);
  }

  public function _doLocation($postObj){
  	$locationX = $postObj->Location_X;
  	$locationY = $postObj->Location_Y;
  	$contentStr = "经度:".$locationX.'纬度:'.$locationY;
  	//file_put_contents('./a.txt',$contentStr);
  	$resultStr = sprintf($this->textTpl, $postObj->FromUserName, $postObj->ToUserName, time(), 'text', $contentStr);
    //file_put_contents('./data.xml',$resultStr);
    echo $resultStr;
  }

  public function _doEvent($postObj){
  	//区分是关注还是取消关注，还是点击事件
  	switch ($postObj->Event) {
  		case 'subscribe':
  			$this->_doSubscribe($postObj);
  			break;
  		case 'unsubscribe':
  			$this->_doUnSubscribe($postObj);
  			break;
  		case 'CLICK':
  			$this->_doClick($postObj);
  			break;
  		default:
  			# code...
  			break;
  	}
  }

  public function _doSubscribe($postObj){
  	//关注之后输出内容
  	$contentStr = "终于等到你，还好我没放弃";
  	$resultStr = sprintf($this->textTpl, $postObj->FromUserName, $postObj->ToUserName, time(), 'text', $contentStr);
  	file_put_contents('./a.xml',$resultStr);
    echo $resultStr;
  }

  public function _doUnSubscribe($postObj){
  	//取消关注事件，一般是删除用户的相关信息
  }
  public function _doClick($postObj){
  	//点击事件
  	//判断点击事件的key值，
  	switch ($postObj->EventKey) {
  		case 'news':
  			$this->_sendTuwen($postObj);
  			break;
  		default:
  			# code...
  			break;
  	}

  }

  public function _sendTuwen($postObj){
  	$items = array(//items一共是3条新闻的数组
  		array(
  			'Title' => '习近平总书记会见中国国民党主席洪秀柱',
            'Description' => '11月1日，中共中央总书记习近平在北京人民大会堂会见洪秀柱主席率领的中国国民党大陆访问团。',
            'PicUrl' => 'http://b.hiphotos.baidu.com/news/q%3D100/sign=b23c8a47c1ea15ce47eee40986013a25/8435e5dde71190efc7c1761dc61b9d16fdfa603e.jpg',
            'Url' => 'http://taiwan.huanqiu.com/photo/2016-11/2850170.html?from=bdtp#p=1',
  		),//一条新闻一个数组，一个item
  		array(
          'Title' => '新疆武警:去年有暴徒杀害多名民众并设伏杀警',
          'Description' => '新华社乌鲁木齐11月2日电题：一切为了人民安居乐业——记武警新疆总队某支队支队长、反恐尖兵王刚',
          'PicUrl' => 'http://cms-bucket.nosdn.127.net/catchpic/a/a1/a1ec7f45eee4859b26c0e8ef9f96fc25.jpg?imageView&thumbnail=550x0',
          'Url' => 'http://news.163.com/16/1102/16/C4SLB6SA000187V5.html',
          ),
        array(
          'Title' => '就算你颜值爆表，自恋的样子也很掉价',
          'Description' => '若一个人时刻告诉自己“我很美”，那是自信；若时刻告诉全世界“我很美”，那是自恋。',
          'PicUrl' => 'http://cms-bucket.nosdn.127.net/3b971f72cf5d4f63886389caaf80614b20161102110010.gif',
          'Url' => 'http://caozhi.news.163.com/16/1102/13/C4SB2JPF000181TI.html',
          ),
  		);
  	//遍历items数组，将每一条新闻放到item的xml模板中，并拼接
  	$itemStr = '';
  	foreach ($items as $key => $value) {
  		$itemStr .= sprintf($this->itemTpl,$value['Title'],$value['Description'],$value['PicUrl'],$value['Url']);
  	}
  	//file_put_contents('./a.xml',$itemStr);
  	//将itemStr放到模板中
  	$newsStr = sprintf($this->newsTpl,$postObj->FromUserName,$postObj->ToUserName,time(),count($items),$itemStr);
  	file_put_contents('./b.xml',$newsStr);
  	echo $newsStr;

  }

  private function checkSignature(){
          // you must define TOKEN by yourself
    //如果未定义TOKEN常量，就抛出一个异常
    if (!defined("TOKEN")) {
      throw new Exception('TOKEN is not defined!');
    }
      //接收到微信服务器，传输过来的字符串
      $signature = $_GET["signature"];
      $timestamp = $_GET["timestamp"];
      $nonce = $_GET["nonce"];

      $token = TOKEN;
      $tmpArr = array($token, $timestamp, $nonce);
          // use SORT_STRING rule
      //数组排序操作
      sort($tmpArr, SORT_STRING);
      $tmpStr = implode( $tmpArr );
      //sha1加密操作
      $tmpStr = sha1( $tmpStr );
      //进行了一系列的排序和加密操作后
      //和传输过来的signature进行比对
        if( $tmpStr == $signature ){
          return true;
        }else{
          return false;
        }
  }
	
}







