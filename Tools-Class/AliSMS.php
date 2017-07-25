<?

/**
 * Class Ali
 * 阿里大于接口
 *
 * 注意事项：
 * curl_do为CURL函数自行补充.
 * log_error 日志函数自行补充
 */


class Ali
{
	private $api_url 		= 'http://gw.api.taobao.com/router/rest';		//接口url
	private $appKey			= '';		//API接口key
	private $appSecret		= '';		//API接口Secret


	/************************ 公用部分开始 *************************/

	/**
	 * 生成签名
	 * @param $params			参数数组，值不可为数组
	 * @param $secret			密钥
	 * @param string $type		加密方式	notify - 通知加密   密钥 + 参数			api - api加密		密钥 + 参数 + 密钥
	 * @return string			返回签名
	 */
	public function makeSign($params,$secret,$type = 'notify'){

		$sign_data = '';
		//升序键值
		ksort($params);
		//重组参数，拼接字符串
		foreach ($params as $k => $v){
			if('sign' !== $k && '' !== $v){
				$sign_data .= $k.$v;
			}
		}

		switch ($type){
			case 'notify':
				$sign_data = strtoupper(md5($secret.$sign_data));
				break;
			case 'api':
				$sign_data = strtoupper(md5($secret.$sign_data.$secret));
				break;
			default:
				$sign_data = strtoupper(md5($sign_data));
		}

		return $sign_data;

	}

	/**
	 * 生成公用数组
	 * @param $method					方法名
	 * @return mixed					返回公用数组
	 */
	public function makePublicData($method){

		$data['v'] = '2.0';
		$data['method'] = $method;
		$data['app_key'] = $this->appKey;
		$data['timestamp'] = date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']);
		$data['format'] = 'json';
		$data['sign_method'] = 'md5';
		return $data;
	}

	/************************ 公用部分结束 *************************/





	/**************** 阿里大鱼部分 *****************/
	/**
	 * @param $sms_template_code	短信模板代码
	 * @param $rec_num				手机号
	 * @param $sms_param			短信模板参数数组
	 * @return bool				是否处理成功
     *
	 */
	public function aliqinFcSmsNumSend($sms_template_code, $rec_num, $sms_param, $sms_free_sign_name = '签名'){

		$post_data = $this->makePublicData('alibaba.aliqin.fc.sms.num.send');
		$post_data['sms_type'] = 'normal';
		$post_data['sms_free_sign_name'] = $sms_free_sign_name != "" ? $sms_free_sign_name : '签名';
		$post_data['sms_param'] = json_encode($sms_param);
		$post_data['rec_num'] = $rec_num;
		$post_data['sms_template_code'] = $sms_template_code;

		$post_data['sign'] = $this->makeSign($post_data,$this->appSecret,'api');

		$post_data = http_build_query($post_data);

        $rt = curl_do($this->api_url,$post_data,'POST');

        if($rt[0] === 200){
            return $this->aliqinFcSmsNumSendReurn($rt[1],'');
        }else{	// 请求对方服务器失败
            return false;
        }

	}

	/**
	 * @param $result			返回结果(待处理)
	 * @param string $task		任务信息
	 * @return bool			返回处理结果
	 */

	//短信处理结果
	public function aliqinFcSmsNumSendReurn($result,$task = ''){

		$info = json_decode($result,true);
		if(isset($info['error_response']['code'])){
			$task_info = '';
			if (!empty($task)){
				$task_info = 'Task_info:';
				$task_info.= isset($task['url']) ? 'URL:'.$task['url'] : '';
				$task_info.= isset($task['data']) ? 'DATA:'.json_encode($task['data']) : '';
			}
			log_error(509, 'Ali API error - ' .$result. $task_info);
			return false;
		}else{
			return true;
		}
	}

}
