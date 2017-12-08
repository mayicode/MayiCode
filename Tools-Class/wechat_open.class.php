<?
/**
* 微信相关api的调用封装
**/

class Wechat_open_core_model extends Model
{

	public $appid = '';
	private $appsecret = '';
	public $encrypt = '';

	private $api_token = 'token';
	private $EncodingAESKey = '';

	private $token = false;
	private $auth_token = false;

	public $auth_appid = '';

	// 设置一个要使用的微信服务号
	public function wechat($appid){
		if ($appid == ''){
			throw new Exception("Wechat api error<-> auth appid 无效");
		}
		$this->auth_appid = $appid;
	}


	public function token($need_expire = false){

		if($this->token === false){
			$token_info = $this->red('system')->get('wechat_open_token_'.$this->appid);
			
			if($token_info === false){	// 无token
				return $this->refreshToken($need_expire);
			}else{
				$this->token = explode('@',$token_info);
			}
		}
		if(!isset($this->token[1]) || time() > $this->token[1]){	// 已过期或无准确token
			return $this->refreshToken($need_expire);
		}else{
			//检测是否过期
			$url = "https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token".$this->token[0]; 
			$cs = curl_do($url);
			if($cs[0]=== 200){
				$res = json_decode($cs[1],true);
				if(isset($res['errcode']) && $res['errcode']===40001){
					return $this->refreshToken($need_expire);
				}else{
					if($need_expire === true){
						return $this->token;
					}else{
						return $this->token[0];
					}
				}
			}
		}
	}

	private function refreshToken($need_expire){	// 刷新token,即获取一个新的token

		$verify_ticket = $this->red('cache')->get('component_verify_ticket_'.$this->appid);
		if ($verify_ticket == false){
			throw new Exception('Wechat api error<-> verify ticket 无效');
		}

		$data = ['component_appid' => $this->appid, 'component_appsecret' => $this->appsecret, 'component_verify_ticket' => $verify_ticket];

		$rt = curl_do('https://api.weixin.qq.com/cgi-bin/component/api_component_token',json_encode($data),'POST');

		if($rt[0] === 200){
			$token = json_decode($rt[1],true);
			if(isset($token['component_access_token'])){
				$token['expires_in'] = $token['expires_in'] - 1000;
				$expires_in = time() + $token['expires_in'];
				$this->red('system')->setex('wechat_open_token_'.$this->appid,$token['expires_in'],$token['component_access_token'].'@'.$expires_in);
				$this->token = [$token['component_access_token'],$expires_in];
				if($need_expire === true){
					return $this->token;
				}else{
					return $this->token[0];
				}
			}else{
				throw new Exception('Wechat api error<-> 微信服务器返回异常 - 请求数据:'.$rt[2].'|'.$rt[3].'|'.$rt[4].' - 返回状态:'.$rt[0].' 返回数据:'.$rt[1].' - '.debug(),507);
			}
		}else{
			throw new Exception('Wechat api error<-> 微信服务器请求失败 - 请求数据:'.$rt[2].'|'.$rt[3].'|'.$rt[4].' - 返回状态:'.$rt[0].' 返回数据:'.$rt[1].' - '.debug(),507);
		}
	}

	public function auth(){

		$data['component_appid'] = $this->appid;
		$rt = curl_do('https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token='.$this->token(),json_encode($data),'POST');

		if($rt[0] === 200){
			$data = json_decode($rt[1],true);
			$pre_auth_code = $data['pre_auth_code'];
			echo '<a href="https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid='.$this->appid.'&pre_auth_code='.$pre_auth_code.'&redirect_uri=http://open.l23.pw/auth/back" target="_blank">发起授权</a>';

		}else{
			throw new Exception('Wechat api error<-> 微信服务器请求失败 - 请求数据:'.$rt[2].'|'.$rt[3].'|'.$rt[4].' - 返回状态:'.$rt[0].' 返回数据:'.$rt[1].' - '.debug(),507);
		}

	}

	public function authOk(){
		$data['component_appid'] = $this->appid;
		$data['authorization_code'] = $_POST['auth_code'];
		$rt = curl_do('https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token='.$this->token(),json_encode($data),'POST');

		if($rt[0] === 200){
			$data = json_decode($rt[1],true);
			$data = $data['authorization_info'];
			$this->auth_appid = $data['authorizer_appid'];
			$auth_access_token = $data['authorizer_access_token'];
			$auth_refresh_token = $data['authorizer_refresh_token'];
			$data['expires_in'] = $data['expires_in'] - 1000;
			$expires_in = $data['expires_in'] + time();
			$this->red('system')->setex('wechat_open_auth_token_'.$this->auth_appid ,$data['expires_in'],$auth_access_token.'@'.$expires_in);
			$this->red('system')->setex('wechat_open_auth_refresh_token_'.$this->auth_appid ,2592000,$auth_refresh_token);
			echo "授权完成！";
		}else{
			throw new Exception('Wechat api error<-> 微信服务器请求失败 - 请求数据:'.$rt[2].'|'.$rt[3].'|'.$rt[4].' - 返回状态:'.$rt[0].' 返回数据:'.$rt[1].' - '.debug(),507);
		}
	}

	private function authorizerToken($need_expire = false){

		if($this->auth_token === false){
			$token_info = $this->red('system')->get('wechat_open_auth_token_'.$this->auth_appid);
			if($token_info === false){	// 无token
				return $this->refreshAuthToken($need_expire);
			}else{
				$this->auth_token = explode('@',$token_info);
			}
		}
		if(!isset($this->auth_token[1]) || time() > $this->auth_token[1]){	// 已过期或无准确token
			return $this->refreshAuthToken($need_expire);
		}else{
			if($need_expire === true){
				return $this->auth_token;
			}else{
				return $this->auth_token[0];
			}
		}

	}

	private function refreshAuthToken($need_expire){

		$refresh_token = $this->red('system')->get('wechat_open_auth_refresh_token_'.$this->auth_appid);
		if ($refresh_token == false){
			echo "授权过期，请重新授权！";
			exit;
		}

		$data['component_appid'] = $this->appid;
		$data['authorizer_appid'] = $this->auth_appid;
		$data['authorizer_refresh_token'] = $refresh_token; //todo

		$rt = curl_do('https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token?component_access_token='.$this->token(),json_encode($data),'POST');
		if($rt[0] === 200){
			$token = json_decode($rt[1],true);
			if(isset($token['authorizer_access_token'])){
				$token['expires_in'] = $token['expires_in'] - 1000;
				$expires_in = time() + $token['expires_in'];
				$auth_access_token = $token['authorizer_access_token'];
				$auth_refresh_token = $token['authorizer_refresh_token'];
				$this->red('system')->setex('wechat_open_auth_token_'.$this->auth_appid ,$token['expires_in'],$auth_access_token.'@'.$expires_in);
				$this->red('system')->setex('wechat_open_auth_refresh_token_'.$this->auth_appid ,2592000,$auth_refresh_token);
				$this->auth_token = [$token['authorizer_access_token'],$expires_in];

				file_put_contents('/change.log', date('Y-m-d H:i:s').' Refresh auth token ok. Data:'.json_encode($token)."\n\n",FILE_APPEND);

				if($need_expire === true){
					return $this->token;
				}else{
					return $this->token[0];
				}
			}else{
				throw new Exception('Wechat api error<-> 微信服务器返回异常 - 请求数据:'.$rt[2].'|'.$rt[3].'|'.$rt[4].' - 返回状态:'.$rt[0].' 返回数据:'.$rt[1].' - '.debug(),507);
			}
		}else{
			throw new Exception('Wechat api error<-> 微信服务器请求失败 - 请求数据:'.$rt[2].'|'.$rt[3].'|'.$rt[4].' - 返回状态:'.$rt[0].' 返回数据:'.$rt[1].' - '.debug(),507);
		}
		// //todo
		// echo "aa";exit;

	}
	

	private function refreshTicket($need_expire){	// 刷新ticket,即获取一个新的ticket

		$rt = curl_do('https://api.weixin.qq.com/cgi-bin/ticket/getticket','type=jsapi&access_token='.$this->authorizerToken());

		if($rt[0] === 200){
			$ticket = json_decode($rt[1],true);
			if(isset($ticket['ticket'])){

				$ticket['expires_in'] = $ticket['expires_in'] - 1000;
				$expires_in = time() + $ticket['expires_in'];
				$this->red('system')->setex('wechat_ticket_open_'.$this->appid,$ticket['expires_in'] - 100,$ticket['ticket'].'@'.$expires_in);

				$this->ticket = [$ticket['ticket'],$expires_in];
				if($need_expire === true){
					return $this->ticket;
				}else{
					return $this->ticket[0];
				}
			}else{
				throw new Exception('Wechat api error<-> 微信服务器返回异常 - 请求数据:'.$rt[2].'|'.$rt[3].'|'.$rt[4].' - 返回状态:'.$rt[0].' 返回数据:'.$rt[1].' - '.debug(),507);
			}
		}else{
			throw new Exception('Wechat api error<-> 微信服务器请求失败 - 请求数据:'.$rt[2].'|'.$rt[3].'|'.$rt[4].' - 返回状态:'.$rt[0].' 返回数据:'.$rt[1].' - '.debug(),507);
		}
	}

	private $ticket = false;
	public function ticket($need_expire = false){

		if($this->ticket === false){
			$ticket_info = $this->red('system')->get('wechat_open_ticket_'.$this->appid);
			if($ticket_info === false){	// 无ticket
				return $this->refreshTicket($need_expire);
			}else{
				$this->ticket = explode('@',$ticket_info);
			}
		}
		if(!isset($this->ticket[1]) || time() > $this->ticket[1]){	// 已过期或无准确ticket
			return $this->refreshTicket($need_expire);
		}else{
			if($need_expire === true){
				return $this->ticket;
			}else{
				return $this->ticket[0];
			}
		}
	}

	// 获取jsapi签名信息
	public function getJsApi($url = false){

		$noncestr = rand_str(16);
		if($url === false){ $url = now_url(false); }
		$signature = sha1('jsapi_ticket='.$this->ticket().'&noncestr='.$noncestr.'&timestamp='.$_SERVER['REQUEST_TIME'].'&url='.$url);
		$sign_package = [
			'appId' => $this->auth_appid,
			'nonceStr' => $noncestr,
			'timestamp' => $_SERVER['REQUEST_TIME'],
			'url' => $url,
			'signature' => $signature
		];
		return $sign_package;
	}




	//校验签名
	public function checkSign($sign_data){

		$arr = [
			$this->api_token,
			$sign_data['timestamp'],
			$sign_data['nonce'],
			$this->encrypt
		];

		sort($arr,SORT_STRING);
		$str = implode($arr);
		$sign = sha1($str);

		if ($sign == $sign_data['msg_signature']){
			return true;
		}else{
			return false;
		}

	}

	//解密数据
	public function decrypt(){

		$key = base64_decode($this->EncodingAESKey."=");

		$iv = substr($key, 0, 16);          
        $decrypted = openssl_decrypt($this->encrypt,'AES-256-CBC',substr($key, 0, 32),OPENSSL_ZERO_PADDING,$iv);

	    //去除补位字符
	    $result = $this->decode($decrypted);
	    //去除16位随机字符串,网络字节序和AppId
	    if (strlen($result) < 16)
	        return "";
	    $content = substr($result, 16, strlen($result));
	    $len_list = unpack("N", substr($content, 0, 4));
	    $xml_len = $len_list[1];
	    $xml_content = substr($content, 4, $xml_len);
		
		return $xml_content;
	}

	public function decode($text){

		$pad = ord(substr($text, -1));
		if ($pad < 1 || $pad > 32) {
			$pad = 0;
		}
		return substr($text, 0, (strlen($text) - $pad));
	}
	
}

