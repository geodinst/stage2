<?php
namespace Drupal\stage2_admin;

/**
*
* Establish connection with GEONETWORK
* Convert an object to an array
*
* @param    String  $username Geonetwork admin username
* @param    String  $password Geonetwork admin password
* @param    String  $srvURL Geonetwork url
* @return   array
*/
class add_gnXML {

	public function gnGET_RECORD($var_val_id,$srvURL){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "$srvURL/geonetwork/srv/api/0.1/records/".$var_val_id);
		// return "$srvURL/geonetwork/srv/api/0.1/records/".$var_val_id;
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/xml'));
		$result = curl_exec($ch);
		curl_close($ch);
			 return $result;//"'Accept: application/xml' 'http://192.168.33.10:8080/geonetwork/srv/api/0.1/records/54'";
	}

	public function gnPUT_SHARE_RECORD($username,$password,$srvURL,$uuid){
		$data_json = '{"clear":false,"privileges":[{"group":1,"operations":{"view":true,"download":true,"dynamic":true}}]}';
		$logfh = fopen("sites/default/files/geonetwork_PHP_add.log", 'w') or die("can't open log file");

		$cookie_url = "$srvURL/geonetwork/srv/eng/info?type=me";
		$cookie_file_path = "/tmp/cookie.txt";

		if (file_exists($cookie_file_path)) {
			unlink($cookie_file_path);
		}

		// get cookie
		$ch = curl_init();
		$headers[] = "Connection: Keep-Alive";
		curl_setopt($ch, CURLOPT_HTTPHEADER,  $headers);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file_path);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file_path);
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_STDERR, $logfh); // logs curl messages
		curl_setopt($ch, CURLOPT_POST, 1);
		// get headers too with this line
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_URL, $cookie_url);
		// $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$result1 = curl_exec($ch);

		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result1, $matches);
		$cookies = array();
		foreach($matches[1] as $item) {
				parse_str($item, $cookie);
				$cookies = array_merge($cookies, $cookie);
		}

		curl_setopt($ch, CURLOPT_USERPWD, $username.':'.$password);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json',"X-XSRF-TOKEN:".$cookies["XSRF-TOKEN"],'Content-Length: ' . strlen($data_json)
																							 ));
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($ch, CURLOPT_URL, "$srvURL/geonetwork/srv/api/0.1/records/".$uuid.'/sharing');
		curl_setopt($ch, CURLOPT_POSTFIELDS,$data_json);
		$result2 = curl_exec($ch);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($result2, 0, $header_size);
		$body = substr($result2, $header_size);
		curl_close($ch);
		if (file_exists($cookie_file_path)) {
			unlink($cookie_file_path);
		}
		return($body);

	}

	 public function gnPUT_RECORD_XML($username,$password,$srvURL,$xml){
		 $logfh = fopen("sites/default/files/geonetwork_PHP_add.log", 'w') or die("can't open log file");
				// drupal_set_message($logfh);
		 $cookie_url = "$srvURL/geonetwork/srv/eng/info?type=me";
		 $cookie_file_path = "/tmp/cookiesss.txt";

		 if (file_exists($cookie_file_path)) {
			 unlink($cookie_file_path);
		 }

		 // get cookie
		 $ch = curl_init();
		 $headers[] = "Connection: Keep-Alive";
		 curl_setopt($ch, CURLOPT_HTTPHEADER,  $headers);
		 curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		 curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		 curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file_path);
		 curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file_path);

		 curl_setopt($ch, CURLOPT_VERBOSE, true);
		 curl_setopt($ch, CURLOPT_STDERR, $logfh); // logs curl messages
		 curl_setopt($ch, CURLOPT_POST, 1);
		 // get headers too with this line
		 curl_setopt($ch, CURLOPT_HEADER, 1);
		 curl_setopt($ch, CURLOPT_URL, $cookie_url);
		 // $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		 $result1 = curl_exec($ch);

		 preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result1, $matches);
		 $cookies = array();
		 foreach($matches[1] as $item) {
		     parse_str($item, $cookie);
		     $cookies = array_merge($cookies, $cookie);
		 }
		 curl_setopt($ch, CURLOPT_USERPWD, $username.':'.$password);
		 curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml','Accept: application/json',"X-XSRF-TOKEN:".$cookies["XSRF-TOKEN"]
		 																						));
		 curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
		 curl_setopt($ch, CURLOPT_URL, "$srvURL/geonetwork/srv/api/0.1/records?metadataType=METADATA&recursiveSearch=false&assignToCatalog=false&uuidProcessing=OVERWRITE&rejectIfInvalid=false&transformWith=_none_");

		 curl_setopt( $ch, CURLOPT_POSTFIELDS, $xml );
		 $result2 = curl_exec($ch);
		 $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		 $header = substr($result2, 0, $header_size);
		 $body = substr($result2, $header_size);
		 curl_close($ch);

		 return($body);
	 }



}


 ?>
