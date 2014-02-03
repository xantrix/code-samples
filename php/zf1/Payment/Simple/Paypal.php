<?php
class Xan_Payment_Simple_Paypal extends Xan_Payment_Simple_Abstract
{
	const GATEWAY_URL = 'https://www.paypal.com/cgi-bin/webscr';
	 
	
	public function __construct()
	{
        // Some default values of the class
		$this->gatewayUrl = GATEWAY_URL;
		$this->ipnLogFile = IPNLOGFILE;

		// Populate $fields array with a few default
		$this->addHtmlField('rm', '2');           // Return method = POST
		$this->addHtmlField('cmd', '_xclick');  //Buy Now button
		/* htmlPayment */			
	}
	
	public function enableTestMode()
    {
    	
    }	
	
    /**
     * valida la notifica pagamento 
     */
	public function validateIpn()
	{
		// parse the paypal URL
		$urlParsed = parse_url($this->gatewayUrl);

		// generate the post string from the _POST vars
		$postString = '';

		foreach ($_POST as $field=>$value)
		{
			$this->ipnData["$field"] = $value;
			$postString .= $field .'=' . urlencode(stripslashes($value)) . '&';
		}

		$postString .="cmd=_notify-validate"; // append ipn command

		// open the connection to paypal
		$fp = fsockopen($urlParsed[host], "80", $errNum, $errStr, 30);

		if(!$fp)
		{
			// Could not open the connection, log error if enabled
			$this->lastError = "fsockopen error no. $errNum: $errStr";
			$this->logResults(false);

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
				$this->ipnResponse .= fgets($fp, 1024);
			}

		 	fclose($fp); // close connection
		}

		if (eregi("VERIFIED", $this->ipnResponse))
		{
		 	// Valid IPN transaction.
		 	$this->logResults(true);
		 	return true;
		}
		else
		{
		 	// Invalid IPN transaction.  Check the log for details.
			$this->lastError = "IPN Validation Failed . $urlParsed[path] : $urlParsed[host]";
			$this->logResults(false);
			return false;
		}
	}   	
	
}