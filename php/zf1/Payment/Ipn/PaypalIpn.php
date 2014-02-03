<?php
require_once 'library/Xan/Payment/Model/Model.php';

class Xan_Payment_Ipn_Paypal
{
    /**
     * Holds the last error encountered
     *
     * @var string
     */
   protected $_lastError;

    /**
     * Do we need to log IPN results ?
     *
     * @var boolean
     */
    protected $_logIpn;

    /**
     * File to log IPN results
     *
     * @var string
     */
    protected $_ipnLog;

    /**
     * Payment gateway IPN response
     *
     * @var string
     */
    protected $_ipnResponse;

    /**
     * Are we in test mode ?
     *
     * @var boolean
     */
    protected $_testMode;

    /**
     * Field array to submit to gateway
     *
     * @var array
     */
    protected $_fields = array();

    /**
     * IPN post values as array
     *
     * @var array
     */
    protected $_ipnData = array();

    /**
     * Payment gateway URL
     *
     * @var string
     */
    protected $_gatewayUrl;

    /**
     * Initialization constructor
     *
     * @param none
     * @return void
     */

    const TXN_TYPE_EC = 'express_checkout';
    const TXN_TYPE_RP = 'recurring_payment';
    const TXN_TYPE_RP_EXPIRED = 'recurring_payment_expired';

    const TXN_TYPE_RPP_CREATED = 'recurring_payment_profile_created';
    const TXN_TYPE_RPP_CANCEL = 'recurring_payment_profile_cancel';


    public function __construct()
    {
        // Some default values of the class
        $this->_lastError = '';
        $this->_logIpn = TRUE;
        $this->_ipnResponse = '';
        $this->_testMode = FALSE;

        // Some default values of the class
		$this->_gatewayUrl = 'https://www.paypal.com/cgi-bin/webscr';

    }

    public function enableTestMode()
    {
        $this->_testMode = TRUE;
        $this->_gatewayUrl = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
    }

    public function disableLog()
    {
    	$this->_logIpn = FALSE;
    }

	public function validateIpn()
	{
		// parse the paypal URL
		$urlParsed = parse_url($this->_gatewayUrl);

		// generate the post string from the _POST vars
		$postString = '';

		foreach ($_POST as $field=>$value)
		{
			$this->_ipnData["$field"] = $value;
			$postString .= $field .'=' . urlencode(stripslashes($value)) . '&';
		}

		//ipn model logic
		if(!$this->_ipnManager()){
			$this->_lastError = "Error Model";
			$this->_logResults(false);
			return false;
		}

		// Post the data back to paypal
		$postString .="cmd=_notify-validate"; // append ipn command

		// open the connection to paypal
		$fp = fsockopen($urlParsed['host'], "80", $errNum, $errStr, 30);

		if(!$fp)
		{
			// Could not open the connection, log error if enabled
			$this->_lastError = "fsockopen error no. $errNum: $errStr";
			$this->_logResults(false);
			return false;
		}
		else
		{
			// Post the data back to paypal
			fputs($fp, "POST $urlParsed[path] HTTP/1.1\r\n");
			fputs($fp, "Host: $urlParsed[host]\r\n");
			fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
			fputs($fp, "Content-length: " . strlen($postString) . "\r\n");
			fputs($fp, "Connection: close\r\n\r\n");
			fputs($fp, $postString . "\r\n\r\n");

			// loop through the response from the server and append to variable
			while(!feof($fp))
			{
				$this->_ipnResponse .= fgets($fp, 1024);
			}

		 	fclose($fp); // close connection
		}

		if (eregi("VERIFIED", $this->_ipnResponse))
		{
		 	// Valid IPN transaction.
		 	$this->_logResults(true);
		 	return true;
		}
		else
		{
		 	// Invalid IPN transaction.  Check the log for details.
			$this->_lastError = "IPN Validation Failed . $urlParsed[path] : $urlParsed[host]";
			$this->_logResults(false);
			return false;
		}
	}


    protected function _logResults($success)
    {

        if (!$this->_logIpn) return;

        $this->_setDefaultLog();

        // Timestamp
        $text = '[' . date('m/d/Y g:i A').'] - ';

        // Success or failure being logged?
        $text .= ($success) ? "SUCCESS!\n" : 'FAIL: ' . $this->_lastError . "\n";

        // Log the POST variables
        $text .= "IPN POST Vars from gateway:\n";
        foreach ($this->_ipnData as $key=>$value)
        {
            $text .= "$key=$value, ";
        }

        // Log the response from the paypal server
        $text .= "\nIPN Response from gateway Server:\n " . $this->_ipnResponse;

        // Write to log
//        $fp = fopen($this->_ipnLog,'a');
//        fwrite($fp, $text . "\n\n");
//        fclose($fp);

        $this->_ipnLog->debug( "\n\n".$text."\n\n");


    }

    protected function _setDefaultLog()
    {
        if( ! $this->_ipnLog instanceOf Zend_Log ) {
            require_once 'Zend/Log.php';
            require_once 'Zend/Log/Writer/Stream.php';
            $mode = ($this->_testMode) ? "test" : "live";
            $logFileName = APPLICATION_PATH ."/../logs/paypal.ipn.".$mode.".log";

            $writer = new Zend_Log_Writer_Stream($logFileName);

            $this->setLog( new Zend_Log( $writer ) );
        }
    }

    public function setLog($log)
    {
    	$this->_ipnLog = $log;
    }

 	protected function _ipnManager()
 	{
 		$txnType = $this->_ipnData['txn_type'];
 		switch ($txnType)
 		{
 			case self::TXN_TYPE_EC :
 				return $this->_ipnExpressCheckout();
 				break;

 			case self::TXN_TYPE_RPP_CREATED :
 				return $this->_ipnRecurringPaymentProfileCreated();
 				break;

 			case self::TXN_TYPE_RP :
 				return $this->_ipnRecurringPayment();
 				break;

 			case self::TXN_TYPE_RP_EXPIRED :
 				return $this->_ipnRecurringPaymentExpired();
 				break;

 			case self::TXN_TYPE_RPP_CANCEL :
 				return $this->_ipnRecurringPaymentProfileCancel();
 				break;

 			default:
 				return true;
 		}
 	}

 	protected function _createInvoiceFromOrder($orderRow)
 	{
 		$model = Xan_Payment_Model::getInstance();

 		if(!$orderRow){
 			return null;
 		}

 		$invoiceData = array();
		$invoiceData['invoice_number'] = '0001';
		$invoiceData['account_id'] = $orderRow->account_id;
		$invoiceData['client_id'] = $orderRow->client_id;
		$invoiceData['company_id'] = $orderRow->company_id;
		$invoiceData['profile_rp_id'] = $orderRow->profile_rp_id;
		$invoiceData['transaction_code'] = $orderRow->transaction_code;

		$invoiceRow = $model->createInvoice($invoiceData);
 		if(!$invoiceRow){
 			return null;
 		}
		$invoiceId = $invoiceRow->invoice_id;

 		$orderLines = $model->getOrderLinesByOrderId($orderRow->order_id);

 		foreach($orderLines as $ordLine){
			$invoiceLineData = array();
			$invoiceLineData['invoice_id'] = $invoiceId;
			$invoiceLineData['product_id'] = $ordLine->product_id;
			$invoiceLineData['product_title'] = $ordLine->product_title;
			$invoiceLineData['product_quantity'] = $ordLine->product_quantity;
			$invoiceLineData['product_price'] = $ordLine->product_price;
			$invoiceLineData['vat'] = $ordLine->vat;
			$invoiceLineData['total_cost'] = $ordLine->total_cost;

 			$model->createInvoiceLine($invoiceLineData);
 		}

 		return $invoiceRow;
 	}

 	protected function _ipnExpressCheckout()
 	{
 		$model = Xan_Payment_Model::getInstance();
 		$code = $this->_ipnData['txn_id'];
 		$orderRow = $model->getOrderByTrCode($code);//recupero order

 	 	if(!$orderRow){
 			return false;
 		}
 		$invoiceRow = $this->_createInvoiceFromOrder($orderRow);
 	 	if(!$invoiceRow){
 			return false;
 		}

 		$data = array();
 		$data['invoice_id'] = $invoiceRow->invoice_id;
 		$data['transaction_type'] = $this->_ipnData['txn_type'];
 		$data['transaction_code'] = $this->_ipnData['txn_id'];
 		$data['transaction_amount'] = $this->_ipnData['mc_gross'];
 		$data['payment_status'] = $this->_ipnData['payment_status'];

 		return $model->createFinancialTransaction($data);

 	}

 	protected function _ipnRecurringPaymentProfileCreated()
 	{
 		$model = Xan_Payment_Model::getInstance();
 		$profileRpId = $this->_ipnData['recurring_payment_id'];
 		$orderRow = $model->getOrderByProfileRpId($profileRpId);//recupero order

 	 	 if(!$orderRow){
 			return false;
 		}
 		$invoiceRow = $this->_createInvoiceFromOrder($orderRow);
 	 	if(!$invoiceRow){
 			return false;
 		}

 		$data = array();
 		$data['invoice_id'] = $invoiceRow->invoice_id;
 		$data['transaction_type'] = $this->_ipnData['txn_type'];
 		$data['transaction_code'] = $this->_ipnData['initial_payment_txn_id'];
 		$data['transaction_amount'] = $this->_ipnData['initial_payment_amount'];
 		$data['payment_status'] = $this->_ipnData['initial_payment_status'];

 		return $model->createFinancialTransaction($data);
 	}

 	protected function _ipnRecurringPayment()
 	{
 		$model = Xan_Payment_Model::getInstance();
 		$profileRpId = $this->_ipnData['recurring_payment_id'];
 		$orderRow = $model->getOrderByProfileRpId($profileRpId);//recupero fattura collegata

 	 	 if(!$orderRow){
 			return false;
 		}
 		$invoiceRow = $this->_createInvoiceFromOrder($orderRow);
 	 	if(!$invoiceRow){
 			return false;
 		}

 		$data = array();
 		$data['invoice_id'] = $invoiceRow->invoice_id;
 		$data['transaction_type'] = $this->_ipnData['txn_type'];
 		$data['transaction_code'] = $this->_ipnData['txn_id'];
 		$data['transaction_amount'] = $this->_ipnData['mc_gross'];
 		$data['payment_status'] = $this->_ipnData['payment_status'];

 		return $model->createFinancialTransaction($data);
 	}

 	protected function _ipnRecurringPaymentExpired()
 	{
 		//disable service
 		return true;
 	}

 	protected function _ipnRecurringPaymentProfileCancel()
 	{
 		//disable service
 		return true;
 	}
}