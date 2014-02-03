<?php
require_once 'library/Xan/Payment/Api/Abstract.php';

class Xan_Payment_Api_Paypal extends Xan_Payment_Api_Abstract
{

    const PAYMENT_ACTION_SALE          = 'Sale';
    const PAYMENT_ACTION_AUTHORIZATION = 'Authorization';

    const LANDING_PAGE_BILLING = 'Billing';

    const REFUND_TYPE_OTHER            = 'Other';
    const REFUND_TYPE_FULL             = 'Full';
    const REFUND_TYPE_PARTIAL          = 'Partial';

    const CURRENCY_EUR = 'EUR';
    const CURRENCY_USD = 'USD';

    protected $_authInfo;

    protected $_log;
    protected $_logActive;
    protected $_testMode;


    protected $_config = array(
        'api_endpoint' => 'https://api-3t.sandbox.paypal.com/nvp',
    	'ec_url' => 'https://www.paypal.com/webscr&cmd=_express-checkout&token=',
        'version'  => 65.1,
    	'use_proxy' => false,
    	'proxy_host' => '127.0.0.1',
    	'proxy_port' => '808'
    );

    protected $_session;

	protected $_nvpstr;

	protected $_params;

	protected $_items;

	protected $_shippingOptions;

	protected $_extraParams;//session



	public function __construct(Xan_Payment_Data_Paypal_AuthInfo $authInfo = null,array $options = array())
	{

        $this->_session = new Zend_Session_Namespace('Xan_Payment_Api_Paypal');

        $this->_authInfo    = $authInfo;
		$this->_config = array_merge($this->_config, $options);

		$this->_testMode = FALSE;
		$this->_logActive = TRUE;
	}

    protected function _setDefaultLog()
    {
        if( ! $this->_log instanceOf Zend_Log ) {
            require_once 'Zend/Log.php';
            require_once 'Zend/Log/Writer/Stream.php';
            $mode = ($this->_testMode) ? "test" : "live";
            $logFileName = APPLICATION_PATH ."/../logs/paypal.api.".$mode.".log";

            $writer = new Zend_Log_Writer_Stream($logFileName);

            $this->setLog( new Zend_Log( $writer ) );
        }
    }

    public function setLog($log)
    {
    	$this->_log = $log;
    }

    public function disableLog()
    {
    	$this->_logActive = FALSE;
    }

	public function setAuthInfo(Xan_Payment_Data_Paypal_AuthInfo $authInfo){
		$this->_authInfo    = $authInfo;
	}


    public function enableTestMode()
    {
    	$this->_testMode = TRUE;
	    $configSandBox = array(
	    'api_endpoint' => 'https://api-3t.sandbox.paypal.com/nvp',
	    'ec_url' => 'https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token='
	    );

	    $this->_config = array_merge($this->_config, $configSandBox);

    }

	public function getEcUrl()
	{
		return $this->_config['ec_url'];
	}

	public function getLastApiRes()
	{
		return $this->_session->lastApiRes;
	}

	public function getLastApiCall()
	{
		return $this->_session->lastApiCall;
	}

	public function getPaymentType()
	{
		return $this->_session->paymentType;
	}

	public function getBillingAgdesc()
	{
		return $this->_session->billingAgdesc;
	}

	protected function _nvpHeader()
	{
    	$apiUserName = $this->_authInfo->getUsername();
    	$apiPassword = $this->_authInfo->getPassword();
    	$apiSignature = $this->_authInfo->getSignature();
    	$subject = $this->_authInfo->getSubject();

    	$authToken = $this->_authInfo->getAuthToken();//isset
    	$authSignature = $this->_authInfo->getAuthSignature();
    	$authTimestamp = $this->_authInfo->getAuthTimestamp();

    	$nvpHeaderStr = "";

    	//$AuthMode = "3TOKEN"; //Merchant's API 3-TOKEN Credential is required to make API Call.
    	//$AuthMode = "FIRSTPARTY"; //Only merchant Email is required to make EC Calls.
    	//$AuthMode = "THIRDPARTY";Partner's API Credential and Merchant Email as Subject are required.

    	if((!empty($apiUserName)) && (!empty($apiPassword)) && (!empty($apiSignature)) && (!empty($subject))) {
    		$authMode = "THIRDPARTY";
    	}

    	else if((!empty($apiUserName)) && (!empty($apiPassword)) && (!empty($apiSignature))) {
    		$authMode = "3TOKEN";
    	}

    	elseif (!empty($authToken) && !empty($authSignature) && !empty($authTimestamp)) {
    		$authMode = "PERMISSION";
    	}
        elseif(!empty($subject)) {
    		$authMode = "FIRSTPARTY";
    	}

    	switch($authMode) {

    		case "3TOKEN" :
    				$nvpHeaderStr = "&PWD=".urlencode($apiPassword)."&USER=".urlencode($apiUserName)."&SIGNATURE=".urlencode($apiSignature);
    				break;
    		case "FIRSTPARTY" :
    				$nvpHeaderStr = "&SUBJECT=".urlencode($subject);
    				break;
    		case "THIRDPARTY" :
    				$nvpHeaderStr = "&PWD=".urlencode($apiPassword)."&USER=".urlencode($apiUserName)."&SIGNATURE=".urlencode($apiSignature)."&SUBJECT=".urlencode($subject);
    				break;
    		case "PERMISSION" :
    			    $nvpHeaderStr = formAutorization($authToken,$authSignature,$authTimestamp);
    			    break;
    	}

    	return $nvpHeaderStr;
	}

	/**
	  * _hashCall: Function to perform the API call to PayPal using API signature
	  * @methodName is name of API  method.
	  * @nvpStr is nvp string.
	  * returns an associtive array containing the response from the server.
	*/
	protected function _hashCall($methodName,$nvpStr)
	{

		$authToken = $this->_authInfo->getAuthToken();
		$authSignature = $this->_authInfo->getAuthSignature();
		$authTimestamp = $this->_authInfo->getAuthTimestamp();

		$apiEndpoint = $this->_config['api_endpoint'];
		$version = $this->_config['version'];

		// form header string
		$nvpheader=$this->_nvpHeader();

		//setting the curl parameters.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$apiEndpoint);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);

		//turning off the server and peer verification(TrustManager Concept).
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_POST, 1);

		//in case of permission APIs send headers as HTTPheders
		if(!empty($authToken) && !empty($authSignature) && !empty($authTimestamp))
		 {
			$headers_array[] = "X-PP-AUTHORIZATION: ".$nvpheader;

	    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers_array);
	    curl_setopt($ch, CURLOPT_HEADER, false);
		}
		else
		{
			$nvpStr=$nvpheader.$nvpStr;
		}

		if($this->_config['use_proxy'])
		curl_setopt ($ch, CURLOPT_PROXY, $this->_config['proxy_host'].":".$this->_config['proxy_port']);

		//check if version is included in $nvpStr else include the version.
		if(strlen(str_replace('VERSION=', '', strtoupper($nvpStr))) == strlen($nvpStr)) {
			$nvpStr = "&VERSION=" . urlencode($version) . $nvpStr;
		}

		$nvpreq="METHOD=".urlencode($methodName).$nvpStr;

		//setting the nvpreq as POST FIELD to curl
		curl_setopt($ch,CURLOPT_POSTFIELDS,$nvpreq);

		//getting response from server
		$response = curl_exec($ch);

		//convrting NVPResponse to an Associative Array
		$nvpResArray=$this->_deformatNvp($response);
		$nvpReqArray=$this->_deformatNvp($nvpreq);

		if (curl_errno($ch)) {
			// moving to display page to display curl errors
			  //$_SESSION['curl_error_no']=curl_errno($ch) ;
			  //$_SESSION['curl_error_msg']=curl_error($ch);
			  $curl_error_no=curl_errno($ch) ;
			  $curl_error_msg=curl_error($ch);

			  throw new Exception($curl_error_msg);

		 } else {
			 //closing the curl
				curl_close($ch);
		  }

	return $nvpResArray;
	}

	/** This function will take NVPString and convert it to an Associative Array and it will decode the response.
	  * It is usefull to search for a particular key and displaying arrays.
	  * @nvpstr is NVPString.
	  * @nvpArray is Associative Array.
	  */
	protected function _deformatNvp($nvpstr)
	{

		$intial=0;
	 	$nvpArray = array();


		while(strlen($nvpstr)){
			//postion of Key
			$keypos= strpos($nvpstr,'=');
			//position of value
			$valuepos = strpos($nvpstr,'&') ? strpos($nvpstr,'&'): strlen($nvpstr);

			/*getting the Key and Value values and storing in a Associative Array*/
			$keyval=substr($nvpstr,$intial,$keypos);
			$valval=substr($nvpstr,$keypos+1,$valuepos-$keypos-1);
			//decoding the respose
			$nvpArray[urldecode($keyval)] =urldecode( $valval);
			$nvpstr=substr($nvpstr,$valuepos+1,strlen($nvpstr));
	     }
		return $nvpArray;
	}


	protected function _formAutorization($auth_token,$auth_signature,$auth_timestamp)
	{
		$authString="token=".$auth_token.",signature=".$auth_signature.",timestamp=".$auth_timestamp ;
		return $authString;
	}

	public function getExtraParams(){
		return $this->_session->extraParams;
	}

	public function setAppId($appId){
		$this->_extraParams['app_id'] = $appId;
		$this->_session->extraParams = $this->_extraParams;
	}

	public function setUserId($userId){
		$this->_extraParams['user_id'] = $userId;
		$this->_session->extraParams = $this->_extraParams;
	}

	public function setCompany($companyId){
		$this->_extraParams['company_id'] = $companyId;
		$this->_session->extraParams = $this->_extraParams;
	}

	public function setCurrency($currencyCodeType)
	{
		//$this->_currency = $currencyCodeType;
		$this->_params['CURRENCYCODE'] = $currencyCodeType;
	}

	public function setPaymentType($paymentType)
	{
		$this->_params['PAYMENTACTION'] = $paymentType;

		$this->_session->paymentType = $paymentType;
	}


	public function setUrls($returnUrl,$cancelUrl)
	{
		$currency = $this->_params['CURRENCYCODE'];
		$paymentType = $this->_params['PAYMENTACTION'];

		$returnURL =($returnUrl.'?currencyCodeType='.$currency.'&paymentType='.$paymentType);
		$cancelURL =($cancelUrl.'?paymentType='.$paymentType );

		   $this->_params['RETURNURL'] = $returnURL;
		   $this->_params['CANCELURL'] = $cancelURL;
	}

	public function addItem(Xan_Payment_Data_Paypal_Item $item)
	{
		$this->_items[] = $item;
	}

	public function setItem($idx,Xan_Payment_Data_Paypal_Item $item)
	{
		$this->_items[$idx] = $item;
	}

	public function setShipping(Xan_Payment_Data_Paypal_Address $address)
	{
		$address->setShipToPrefix();
		$this->_params += $address->toNvp();
	}

	public function setShippingDiscountAmt($amt)
	{
		$this->_params['SHIPDISCAMT'] = $amt;
	}

	public function setShippingAmt($amt)
	{
		$this->_params['SHIPPINGAMT'] = $amt;
	}

	public function setTaxAmt($amt)
	{
		$this->_params['TAXAMT'] = $amt;
	}

	public function setInsuranceAmt($amt,$optOffered=true)
	{
		$opt = ($optOffered) ? 'true' : 'false';

		$this->_params['INSURANCEAMT'] = $amt;
		$this->_params['INSURANCEOPTIONOFFERED'] = $opt;
	}

	public function addShippingOpt(Xan_Payment_Data_Paypal_ShippingOption $shipOpt)
	{
		   $this->_shippingOptions[] = $shipOpt;
	}

	public function setShippingOpt($idx,Xan_Payment_Data_Paypal_ShippingOption $shipOpt)
	{
			$this->_shippingOptions[$idx] = $shipOpt;
	}

	protected function _getParam($param)
	{
		if( isset($this->_params[$param]) )
			return $this->_params[$param];

		return null;
	}

	/**
	 * before setExpressCk
	 */
	protected function _calculateAmtFields()
	{
		//amt finale= tax + insurance + (shippingAmt - shippingDiscount) + itemsAmt

		$shipAmt = $this->_params['SHIPPINGAMT'] + $this->_params['SHIPDISCAMT'];//discount � negativo

		   $itemsAmt = 0.00;

		   foreach($this->_items as $item){
		   	$itemsAmt += $item->getTotalAmt();
		   }

		   $amt = $this->_params['TAXAMT'] + $this->_params['INSURANCEAMT'] + $shipAmt + $itemsAmt;
		   $maxamt= $amt+25.00;

		   $this->_params['MAXAMT'] = (string)$maxamt;
		   $this->_params['AMT'] = (string)$amt;
		   $this->_params['ITEMAMT'] = (string)$itemsAmt;

		  //setto params items e shippingOptions
		  foreach($this->_items as $item){
			$this->_params += $item->toNvp();
		  }

		  foreach($this->_shippingOptions as $shipOpt){
		  	$this->_params += $shipOpt->toNvp();
		  }

	}

	public function setLandingPage($type)
	{
		$this->_params['LANDINGPAGE'] = $type;
	}

	public function setBillingAgreementFields($desc)
	{
		$this->_params['L_BILLINGTYPE0'] = 'RecurringPayments';
		$this->_params['L_BILLINGAGREEMENTDESCRIPTION0'] = $desc;

		$this->_session->billingAgdesc = $desc;

	}

	protected function _addNvpField($name,$value)
	{
		$this->_nvpstr .= '&'.$name.'='.urlencode($value);
	}

	protected function _writeLog($text)
	{
		if(!$this->_logActive) return;
		$this->_setDefaultLog();
		$this->_log->debug("\n\n".$text."\n\n");
	}

	protected function _apiCall($methodName)
	{
		//costruisco nvpstr da array prima di ogni chiamata
		foreach($this->_params as $name => $value){
			$this->_addNvpField($name,$value);
		}

		$text = "API Call: ";
		$text .= $methodName."\n";
		$text .= print_r($this->_params,true);
		$this->_writeLog($text);

		$resArray=$this->_hashCall($methodName,$this->_nvpstr);

		$text = "API Response: ";
		$text .= $methodName."\n";
		$text .= print_r($resArray,true);
		$this->_writeLog($text);

		//svuoto dopo la chiamata
		$this->_nvpstr = '';

		$this->_session->lastApiRes = $resArray;
		$this->_session->lastApiCall = $methodName;

		return $resArray;
	}

	/**
	 * ritorna token|null
	 */
	public function ecFirstStep()
	{
		$this->_calculateAmtFields();

		if(!$this->_params['AMT'] || !$this->_params['RETURNURL'] || !$this->_params['CANCELURL'])
			throw new Exception('impossibile completare la chiamata:parametri mancanti');

		$resArray = $this->_apiCall('SetExpressCheckout');
		$ack = strtoupper($resArray["ACK"]);
		if($ack=="SUCCESS"){
			$token = ($resArray["TOKEN"]);
			return $token;
		}

		return null;
	}


	/**
	 * ritorna true|false
	 */
	public function ecSecondStep()
	{
		$resArray = $this->getLastApiRes();
		if($resArray!=null && $resArray["TOKEN"])
			$token = $resArray["TOKEN"];

		if($this->getLastApiCall()!='SetExpressCheckout')
			throw new Exception('chiamare prima EcFirstStep');

		if(!$token)
			throw new Exception('token obbligatorio');


		$this->_params['TOKEN'] = $token;
		$resArray = $this->_apiCall('GetExpressCheckoutDetails');
		$ack = strtoupper($resArray["ACK"]);

		if($ack=="SUCCESS" || $ack == 'SUCCESSWITHWARNING'){
			return true;
		}

		return false;

	}

	/**
	 * ritorna true|false
	 */
	public function ecThirdStep($paymentType=null)
	{
		$resArray = $this->getLastApiRes();

		$paymentType = ($paymentType) ? $paymentType : $this->getPaymentType();

		if( !$resArray || !$paymentType)
			throw new Exception('impossibile completare la chiamata:parametri mancanti');

		if($this->getLastApiCall()!='GetExpressCheckoutDetails')
			throw new Exception('chiamare prima EcSecondStep');

		//saveOrderData
		$orderId = $this->_saveOrderData($resArray);

        $token = $resArray['TOKEN'];
        //amt totale(item+ship+tax+insurance)+(-discountAmt)
        $TotalAmount = $resArray['AMT'] + $resArray['SHIPDISCAMT'];
		$paymentAmount = $TotalAmount;

		$currCodeType = $resArray['CURRENCYCODE'];
		$payerID = $resArray['PAYERID'];
		//$serverName = urlencode($_SERVER['SERVER_NAME']);

		$this->_params['TOKEN'] = $token;
		$this->_params['PAYERID'] = $payerID;
		$this->_params['PAYMENTACTION'] = $paymentType;
		$this->_params['AMT'] = $paymentAmount;
		$this->_params['CURRENCYCODE'] = $currCodeType;
		//$this->_params['IPADDRESS'] = $serverName;

		$resArray = $this->_apiCall('DoExpressCheckoutPayment');

		$ack = strtoupper($resArray["ACK"]);

		if($ack == 'SUCCESS' || $ack == 'SUCCESSWITHWARNING'){
			$data = array();
			$data['transaction_code'] = $resArray['TRANSACTIONID'];
			$this->_setOrderFtReference($orderId,$data);

			$this->_clearEcPending();
			return true;
		}

		return false;
	}

	public function ecThirdStepRp($billingPeriod,$billingFrequency,$billingCycles,
            					  $trialbillingPeriod,$trialbillingFrequency,$trialbillingCycles,$trialAmt)
	{
		$resArray = $this->getLastApiRes();
		$paymentType = $this->getPaymentType();
		$billingAgdesc = $this->getBillingAgdesc();

		if( !$resArray || !$paymentType || !$billingAgdesc)
			throw new Exception('impossibile completare la chiamata:parametri mancanti');

		if($this->getLastApiCall()!='GetExpressCheckoutDetails')
			throw new Exception('chiamare prima EcSecondStep');

        //amt totale(item+ship+tax+insurance)+(-discountAmt)
        $TotalAmount = (string) $resArray['AMT'] + $resArray['SHIPDISCAMT'];

		$currCodeType = $resArray['CURRENCYCODE'];

        return $this->createRpProfile(time(),$currCodeType,$billingAgdesc,
        						   $billingPeriod,$billingFrequency,$billingCycles,
        						   $TotalAmount,$TotalAmount,'0.00','0.00',
        						   $trialbillingPeriod,$trialbillingFrequency,$trialbillingCycles,$trialAmt);




	}

	/**
	 * necessita di token attivo di tipo EC
	 * @param unknown_type $profileStartDate
	 * @param unknown_type $currency
	 * @param unknown_type $desc
	 * @param unknown_type $billingPeriod
	 * @param unknown_type $billingFrequency
	 * @param unknown_type $billingCycles
	 * @param unknown_type $amt
	 * @param unknown_type $initAmt
	 * @param unknown_type $shipAmt
	 * @param unknown_type $taxAmt
	 * @param unknown_type $trialbillingPeriod
	 * @param unknown_type $trialbillingFrequency
	 * @param unknown_type $trialbillingCycles
	 * @param unknown_type $trialAmt
	 *
	 * NOTA sul token
	 * A timestamped token, the value of which was returned in the response to the first
	 * call to SetExpressCheckout.
	 * You can also use the token returned in the SetCustomerBillingAgreement response.
		Either this token or a credit card number is required.
		If you include both token and credit card number,
		the token is used and credit card number is ignored.
	 *
	 */
	public function createRpProfile($profileStartDate,$currency,$desc,
									$billingPeriod,$billingFrequency,$billingCycles,
									$amt,$initAmt,$shipAmt,$taxAmt,
									$trialbillingPeriod,$trialbillingFrequency,$trialbillingCycles,$trialAmt
									)
	{
		$resArray = $this->getLastApiRes();

		if( !$resArray)
			throw new Exception('impossibile completare la chiamata:parametri mancanti');

		if($this->getLastApiCall()=='GetExpressCheckoutDetails'){
			//saveOrderData
			$orderId = $this->_saveOrderData($resArray);
		}

		//TODO gestione con cc invece del tokenEc
        $token = $resArray['TOKEN'];
		$profileStartDate = date("Y-m-d",$profileStartDate).'T00:00:00Z';

		$this->_params['TOKEN'] = $token;
		$this->_params['CURRENCYCODE'] = $currency;
		$this->_params['DESC'] = $desc;

		$this->_params['PROFILESTARTDATE'] = $profileStartDate;

		$this->_params['BILLINGPERIOD'] = $billingPeriod;
		$this->_params['BILLINGFREQUENCY'] = $billingFrequency;
		($billingCycles) ? $this->_params['TOTALBILLINGCYCLES'] = $billingFrequency : null;

		$this->_params['AMT'] = $amt;

		if($initAmt!=null){
			$this->_params['INITAMT'] = $initAmt;
		}

		($shipAmt) ? $this->_params['SHIPPINGAMT'] = $shipAmt : null;

		($taxAmt) ? $this->_params['TAXAMT'] = $taxAmt : null;

		($trialbillingPeriod) ? $this->_params['TRIALBILLINGPERIOD'] = $trialbillingPeriod : null;
		($trialbillingFrequency) ? $this->_params['TRIALBILLINGFREQUENCY'] = $trialbillingFrequency : null;
		($trialbillingCycles) ? $this->_params['TRIALTOTALBILLINGCYCLES'] = $trialbillingCycles : null;
		($trialAmt) ? $this->_params['TRIALAMT'] = $trialAmt : null;

		$resArray = $this->_apiCall('CreateRecurringPaymentsProfile');

		$ack = strtoupper($resArray["ACK"]);

		if($ack == 'SUCCESS' || $ack == 'SUCCESSWITHWARNING'){
			$data = array();
			$data['profile_rp_id'] = $resArray['PROFILEID'];
			$this->_setOrderFtReference($orderId,$data);

			$this->_clearEcPending();
			return true;
		}

		return false;
	}

	/**
	 *
	 * @param array $data GetExpressCheckoutDetails array response
	 */
	protected function _saveOrderData($data)
	{
		require_once 'library/Xan/Payment/Model/Model.php';
		$model = Xan_Payment_Model::getInstance();

		//email del buyer
		$gatewayId = $data['EMAIL'];

		$extraParams = $this->getExtraParams();
		$appId = $extraParams['app_id'];
		$userId = $extraParams['user_id'];
		$companyId = $extraParams['company_id'];

		//crea o recupera account
		$rowAccount = $model->getAccount($gatewayId);
		if(!$rowAccount){
			$rowAccount = $model->createAccount($gatewayId);
		}

		//crea o recupera client
		$rowClient = $model->getClient($appId,$userId);
		if(!$rowClient){
			$rowClient = $model->createClient($appId,$userId);
		}

		//crea o recupera relazione
		$rowRel = $model->getRelAccountClient($rowAccount->account_id,$rowClient->client_id);
		if(!$rowRel){
			$rowRel = $model->createRelAccountClient($rowAccount->account_id,$rowClient->client_id);
		}

		$orderData = array();
		$orderData['order_number'] = '0001';
		$orderData['account_id'] = $rowAccount->account_id;
		$orderData['client_id'] = $rowClient->client_id;
		$orderData['company_id'] = $companyId;
		$rowOrder = $model->createOrder($orderData);
		$orderId = $rowOrder->order_id;

		//GetExpressCheckoutDetails $data
		$i=0;
		while(isset($data['L_NAME'.$i])){
			$orderLineData = array();
			$orderLineData['order_id'] = $orderId;
			$orderLineData['product_id'] = null;
			$orderLineData['product_title'] = $data['L_NAME'.$i];
			$orderLineData['product_quantity'] = $data['L_QTY'.$i];
			$orderLineData['product_price'] = $data['L_AMT'.$i];
			$orderLineData['vat'] = 20;
			$orderLineData['total_cost'] = $orderLineData['product_quantity'] * $orderLineData['product_price'];

			$model->createOrderLine($orderLineData);
			$i++;
		}

		return $orderId;
	}

	protected function _setOrderFtReference($id,$data){
		require_once 'library/Xan/Payment/Model/Model.php';
		$model = Xan_Payment_Model::getInstance();
		$model->updateOrder($id,$data);
	}



	protected function _clearEcPending()
	{
		$this->_session->paymentType = null;
		$this->_session->billingAgdesc = null;
		$this->_session->extraParams = null;

	}

	public function isEcPending()
	{
		return ($this->getPaymentType()!=null) ? true : false;
	}


	public function getBalance()
	{
		$this->_params['RETURNALLCURRENCIES'] = '1';
		$resArray = $this->_apiCall('GetBalance');

		$ack = strtoupper($resArray["ACK"]);

		if($ack == 'SUCCESS' || $ack == 'SUCCESSWITHWARNING'){
			return true;
		}

		return false;
	}

}
