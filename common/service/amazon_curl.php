<?php

class AmazonSellerCentralSession {
	private $mCurl;
	private $mBaseUrl;
	private $mCookieJar;
	private $mReturnInstructions;

	const USERAGENT='Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.90 Safari/537.36';

	public function __construct(){
		if (!function_exists('curl_init')){
			trigger_error('CURL module is required.', E_USER_ERROR);
		}

		$this->initializeCurl();
	}

	public function __destruct(){
		if ($this->mCookieJar){
			unlink($this->mCookieJar);
		}
	}

	public function setReturnInstructions($s){
		$this->mReturnInstructions = $s;
	}

	public function login($user, $pass, $base){
		$this->mBaseUrl = $base;

		//First request the home page to setup cookies and such
		$body = $this->performRequest('GET', '/');
		if (!$body){
			trigger_error('Failed to retrieve login page.', E_USER_NOTICE);
			return false;
		}

		//Attempt to extract the form data from the HTML document
		$formData = $this->extractFormData($body);
		if (!$formData){
			trigger_error('Failed to parse form data.', E_USER_NOTICE);
			return false;
		}

		$loginForm = null;
		$usernameField = '';
		foreach ($formData as $form){
			if (isset($form['elements']['username']) && isset($form['elements']['password'])){
				$loginForm = $form;
				$usernameField = 'username';
				break;
			}
			else if (isset($form['elements']['email']) && isset($form['elements']['password'])){
				$loginForm = $form;
				$usernameField = 'email';
			}
		}

		//Check for the sign in form data
		if (!$loginForm){
			trigger_error('Failed to locate login form.', E_USER_NOTICE);
			return false;
		}

		$postData = array(
			$usernameField => $user
			, 'password' => $pass
		) + $loginForm['elements'];

		$parts = parse_url($loginForm['action']);
		$parts['query'] = isset($parts['query'])?$parts['query']:null;

		$action = $parts['path'].($parts['query']?'?'.$parts['query']:'');
		$body = $this->performRequest('POST', $action, $postData);
		if (!$body){
			trigger_error('Failed to process login request.', E_USER_NOTICE);
			return false;
		}

		//Check if the login was successful or not
		//If the login was successful we should be able to find a 'Your Orders' widget.
		try {
			$doc = new DOMDocument();
			if (!@$doc->loadHTML($body)){
				trigger_error('Failed to process login response.', E_USER_NOTICE);
				return false;
			}

			$xpath = new DOMXPath($doc);
			$query = $xpath->query('//h4/text()');
			if (!$query || $query->length == 0){
				return false;
			}
			else foreach ($query as $node){
				if (strtolower($node->nodeValue)=='your orders'){
					return true;
				}
			}
		}
		catch (Exception $e){
			trigger_error('Login Failed: '.((string)$e), E_USER_NOTICE);
			return false;
		}
		return false;
	}

	public function authorizeReturn($orderId, $rmaNumber=null){
		$doc = $this->loadOrderDetails($orderId);

		// # Need to request the authorization form in order to get certain fields.
		$authFormUrl = $this->getRequestAuthorizationUrl($doc);
		if (!$authFormUrl){
			trigger_error('Failed to get URL for authorization.', E_USER_NOTICE);
			echo 'Failed to find an active Return Amazon Authorization to authorize. Please ensure customer has requested authorization before attempting to authorize one. Instructions for returns can be found at: http://www.amazon.com/gp/orc/returns/homepage.html';
			return false;
		}

		$parts = parse_url($authFormUrl);
		$authFormUrl = $parts['path'].(isset($parts['query'])?'?'.$parts['query']:'');
		$authParams = $this->getAuthorizationParameters($authFormUrl);
		if (!$authParams){
			trigger_error('Failed to find required fields for return authorization', E_USER_NOTICE);
			return false;
		}


		//Call the Ajax Api to authorize the return
		$postData = array(
			'action' => 'authorize-return-request'
			, 'returnRequestID' => $authParams['returnRequestID']
			, 'merchantPartyID' => $authParams['merchantPartyID']
			, 'returnAddressID' => $authParams['returnAddressID']
			, 'applicationPath' => $authParams['applicationPath']
			, 'labelType' => $authParams['labelType']
			, 'returnInstructions' => $authParams['returnInstructions']
		);
		if ($rmaNumber !== null){
			$postData['merchantRmaID'] = (string)$rmaNumber;
		}

		if (!$this->performAuthorizationRequest($authParams['postbackUrl'], $postData)){
			return false;
		}

		// # If no RMA number was assigned, extract the amazon generated one.
		if ($rmaNumber === null){
			$doc = $this->loadOrderDetails($orderId, true);
			$rmaNumber = $this->getAmazonRMANumber($doc);
		}

		return $rmaNumber;
	}

	public function getBuyerComments($orderId){
		$doc = $this->loadOrderDetails($orderId);
		$xpath = new DOMXPath($doc);
		$buyerCommentList = $xpath->query('//span[text()="Buyer Comment:"]');
		if (!$buyerCommentList || $buyerCommentList->length == 0){
			trigger_error("Failed to locate buyer comments.", E_USER_NOTICE);
			return false;
		}

		$commentList=array();
		foreach ($buyerCommentList as $bc){
			$commentText = $bc->nextSibling->nodeValue;
			$itemUrl = $bc->parentNode->parentNode->getElementsByTagName('li')->item(0)->getElementsByTagName('a')->item(0)->getAttribute('href');

			$commentList[$itemUrl]=$commentText;
		}

		return $commentList;
	}

	public function assignRmaNumber($orderId, $rmaNumber=null){
		$doc = $this->loadOrderDetails($orderId);
		$returnRequestId = $this->getReturnRequestId($doc);
		if (!$returnRequestId){
			return false;
		}

		if ($rmaNumber === null){
			$rmaNumber = $this->getAmazonRMANumber($doc);
			if (!$rmaNumber){
				trigger_error('Could not locate amazon RMA number', E_USER_NOTICE);
				return false;
			}
		}

		//Save the RMA number
		$postData = array(
			'action' => 'update-merchant-rma-id'
			, 'returnRequestId' => $returnRequestId
			, 'merchantRmaId' => $rmaNumber
			, 'applicationPath' => '/gp/returns'
		);

		$body = $this->performRequest('POST', '/gp/returns/remote-actions/update-merchant-rma-id.html', $postData);
		if (!$body){
			trigger_error('Failed to save RMA # for return '.$returnRequestId, E_USER_NOTICE);
			return false;
		}

		return $this->checkAjaxRequestSuccess($body);
	}

	public function setCurlOptions($options){
		curl_setopt_array($this->mCurl, $options);
	}

	protected function performRequest($method, $url, $postData=null){
		$options = array();
		$is55 = version_compare(PHP_VERSION, '5.5.0', '>=');

		switch (strtolower($method)){
			case 'get':
				$options[CURLOPT_HTTPGET] = true;
			break;
			case 'post':
				if (is_array($postData)){
					//Check for upload files
					$hasUpload = false;
					foreach ($postData as $key=>$value){
						if ($key[0] == '@' || ($is55 && $value instanceof CURLFile)){
							$hasUpload = true;
							if ($is55 && !is_object($value)){
								if (strpos($value, ';type=') !== false){
									list($file, $type) = explode(';type=', $value, 2);
								}
								else {
									$file = $value;
									$type = null;
								}

								$obj = new CURLFile($file);
								if ($type) $obj->setMimetype($type);

								$postData[substr($key, 1)] = $obj;
							}
						}
					}

					if ($hasUpload){
						$options[CURLOPT_UPLOAD] = true;
					}
				}

				$options[CURLOPT_POST] = true;
				if ($postData){
					if (!array_key_exists(CURLOPT_UPLOAD, $options)) $postData = http_build_query($postData);
					$options[CURLOPT_POSTFIELDS] = $postData;
				}
			break;
		}

		$options[CURLOPT_URL] = $this->mBaseUrl.$url;
		$this->setCurlOptions($options);
		$result = curl_exec($this->mCurl);
		//file_put_contents(md5($options[CURLOPT_URL]), var_export($options,true).PHP_EOL.PHP_EOL.$result);
		if ($result === false){
			trigger_error('Curl Error: '.curl_error($this->mCurl), E_USER_NOTICE);
			return false;
		}

		// # If code is not a 2xx or 3xx response, consider the request failed.
		$code = curl_getinfo($this->mCurl, CURLINFO_HTTP_CODE);
		if (!(($code >= 300 && $code <= 399) || ($code >= 200 && $code <= 299))){
			trigger_error('Unexpected HTTP Response code: '.$code, E_USER_NOTICE);
			return false;
		}

		return $result;
	}

	protected function initializeCurl(){
		if ($this->mCurl && is_resource($this->mCurl)){
			curl_close($this->mCurl);
		}

		//Cookie jar file is required for cURL so lets just make a temporary file.
		$this->mCookieJar = tempnam(sys_get_temp_dir(), 'cookiejar');
		if ($this->mCookieJar === false){
			throw new RuntimeException('Could not create cookie jar file.');
		}

		$this->mCurl = curl_init();
		$this->setCurlOptions(array(
			CURLOPT_AUTOREFERER => true
			, CURLOPT_RETURNTRANSFER => true
			, CURLOPT_SSL_VERIFYPEER => true
			, CURLOPT_SSL_VERIFYHOST => 2
			, CURLOPT_FOLLOWLOCATION => true
			, CURLOPT_CONNECTTIMEOUT => 5
			, CURLOPT_MAXREDIRS => 10
			, CURLOPT_USERAGENT => self::USERAGENT
			, CURLOPT_COOKIEJAR => $this->mCookieJar
			, CURLOPT_COOKIEFILE => $this->mCookieJar
		));
	}

	private function checkAjaxRequestSuccess($body){
		$doc = new DOMDocument();
		if (!@$doc->loadXML($body)){
			trigger_error("Failed to parse ajax response.", E_USER_NOTICE);
			return false;
		}

		$xpath = new DOMXPath($doc);
		$status = $xpath->query('//status/text()');
		if (!$status || $status->length == 0){
			trigger_error("Failed to locate ajax response status.", E_USER_NOTICE);
			return false;
		}
		else if ($status->item(0)->nodeValue != 'success'){
			$message = $xpath->query('//message/text()');
			if (!$message || $message->length == 0) $message= '';
			else $message = $message->item(0)->nodeValue;
			trigger_error('Ajax request failed: '.$message, E_USER_NOTICE);
			return false;
		}

		return true;
	}

	private function performAuthorizationRequest($url, $postData){
		var_dump($url, $postData);
		$body = $this->performRequest('POST', $url, $postData);
		if (!$body){
			trigger_error("Failed to authorize return.", E_USER_NOTICE);
			return false;
		}

		return $this->checkAjaxRequestSuccess($body);
	}

	private function getAuthorizationParameters($authFormUrl){
		$body = $this->performRequest('GET', $authFormUrl);
		if (!$body){
			trigger_error('Failed to request authorization form.', E_USER_NOTICE);
			return false;
		}

		$doc = new DOMDocument();
		if (!@$doc->loadHTML($body)){
			trigger_error('Failed to parse authorization form.', E_USER_NOTICE);
			return false;
		}

		$xpath = new DOMXPath($doc);
		$scripts = $xpath->query('//script/text()');
		if (!$scripts || $scripts->length==0){
			trigger_error('Failed to extract script tags from authorization form.', E_USER_NOTICE);
			return false;
		}
		else {
			$regex = '/^\s*(applicationPath|returnRequestID|merchantPartyID|returnAddressID):\s*(.*)$/m';
			$merchantPartyID = $returnAddressID = $returnRequestID = $applicationPath = null;
			foreach ($scripts as $s){
				$s = $s->nodeValue;
				if (preg_match_all($regex, $s, $match)){
					for ($i=0; $i<count($match[1]); $i++){
						${$match[1][$i]} = trim(trim($match[2][$i]), '"\',');
					}
				}
			}
		}

		if (!$this->mReturnInstructions){
			$textareas = $xpath->query('//textarea[@id="_myrAR_rmi_custom_instructions_textarea"]/text()');
			if ($textareas && $textareas->length == 1){
				$this->mReturnInstructions = $textareas->item(0)->nodeValue;
			}
		}

		$postbackUrl = $xpath->query('//div[@id="_myr_ex_ra"]/text()');
		if ($postbackUrl && $postbackUrl->length == 1){
			$postbackUrl = $postbackUrl->item(0)->nodeValue;
			if (strncmp($postbackUrl, $this->mBaseUrl, strlen($this->mBaseUrl)) == 0){
				$postbackUrl = substr($postbackUrl, strlen($this->mBaseUrl));
			}
		} else {
			trigger_error('Failed to determine postback URL', E_USER_NOTICE);
			return false;
		}

		if ($merchantPartyID && $returnAddressID && $returnRequestID && $applicationPath){
			return array(
				'merchantPartyID' => $merchantPartyID
				, 'returnAddressID' => $returnAddressID
				, 'returnRequestID' => $returnRequestID
				, 'applicationPath' => $applicationPath
				, 'labelType' => 'AmazonUnPaidLabel'
				, 'returnInstructions' => $this->mReturnInstructions
				, 'postbackUrl' => $postbackUrl
			);
		}
		else {
			return false;
		}
	}

	private function getReturnRequestId($doc){
		$node = $this->locateOrderNode($doc);
		if (!$node){
			trigger_error('Failed to locate order details node', E_USER_NOTICE);
			return false;
		}

		$xpath = new DOMXpath($doc);
		$hrefs = $xpath->query('descendant::a[@href]/@href', $node);
		foreach ($hrefs as $href){
			$parts = parse_url($href->value);

			if ($parts && isset($parts['query']) && isset($parts['path'])){
				parse_str($parts['query'], $qs);
				if (isset($qs['returnRequestId'])){
					return $qs['returnRequestId'];
				}
			}
		}

		return false;
	}

	private function getRequestAuthorizationUrl($doc){
		$xpath = new DOMXpath($doc);
		$hrefs = $xpath->query('//ul[@class="returnInfoContent"]//a[@href]/@href');
		if (!$hrefs || $hrefs->length == 0){
			return false;
		}

		foreach ($hrefs as $href){
			$parts = parse_url($href->value);
			if ($parts && isset($parts['query']) && isset($parts['path'])){
				parse_str($parts['query'], $qs);
				if (strpos($href->value, 'authorize') && isset($qs['returnRequestId'])){
					return $href->value;
				}
			}
		}
		return false;
	}

	private function getAmazonRMANumber($doc){
		$xpath = new DOMXPath($doc);
		$info = $xpath->query('//ul[@class="orderOverviewInfo"]/li/text()');
		if (!$info || $info->length==0){
			trigger_error('Unable to locate order info.', E_USER_NOTICE);
			return false;
		}

		$rmaNumber = null;

		foreach ($info as $text){
			$text = $text->nodeValue;
			if (substr($text, 0, 4)=='RMA:'){
				$rmaNumber = trim(substr($text, 5));
			}
		}

		if ($rmaNumber === null){
			trigger_error('Unable to locate RMA number.', E_USER_NOTICE);
			return false;
		}

		return $rmaNumber;
	}

	private function loadOrderDetails($orderId,$auth=false){
		//$url = '/gp/returns/list?stateIds=PendingApproval&searchType=orderId&keywordSearch='.urlencode($orderId);
		$url='/gp/returns/list?searchType=orderId&keywordSearch='.urlencode($orderId).'&preSelectedRange=30&exactFromDate=&exactToDate=&stateIds=PendingApproval';
		if ($auth) $url .= '&auth=true';
		$body = $this->performRequest('GET', $url);
		if (!$body){
			return false;
		}

		$doc = new DOMDocument();
		if (!@$doc->loadHTML($body)){
			trigger_error('Failed to load pending authroization list', E_USER_NOTICE);
			return false;
		}

		return $doc;
	}

	private function extractFormData($html){
		$doc = new DOMDocument();
		if (!@$doc->loadHTML($html)){
			return false;
		}

		$xpath = new DOMXPath($doc);
		$formNodes = $xpath->query('//form');
		$forms=array();
		foreach ($formNodes as $idx=>$node){
			$formInfo = array(
				'action' => $node->getAttribute('action')
				, 'method' => $node->getAttribute('method')
				, 'elements' => $this->getFormElements($node)
			);

			$forms[] = $formInfo;
		}

		return $forms;
	}

	private function getFormElements($node){
		$elements = array();
		$elementCount = 0;

		$xpath = new DOMXpath($node->ownerDocument);
		$inputs = $xpath->query('.//input', $node);
		foreach ($inputs as $inputNode){
			$name = $inputNode->getAttribute('name');
			if (!$name) continue;

			$value = $inputNode->getAttribute('value');
			$elements[$name] = $value;
		}

		return $elements;
	}
}


/*
if (PHP_SAPI=='cli' && basename($_SERVER['argv'][0]) == basename(__FILE__)){
	$obj = new AmazonSellerCentralSession();
	$obj->setCurlOptions(array(
		CURLOPT_CAINFO => 'C:/php/curl-ca-bundle.crt'
		, CURLOPT_VERBOSE => true
	));
	$order = '112-2711641-6842658';
	if ($obj->login('keithm@aoeex.com', 'xxx', 'https://sellercentral.amazon.com')){
		$rma = $obj->authorizeReturn($order);
		if ($rma){
			echo 'Authorized: '.$rma.PHP_EOL;
			foreach ($obj->getBuyerComments($order) as $item=>$comment){
				echo "{$item}: {$comment}".PHP_EOL;
			}
		}
		else {
			echo 'Failed to authorize'.PHP_EOL;
		}
	}
	else {
		echo 'Login Failed'.PHP_EOL;
	}
}
*/