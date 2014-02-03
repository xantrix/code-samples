<?php
require_once 'application/modules/general/controllers/AuthController.php';
require_once 'library/Xan/Payment/Data/Paypal/Address.php';
require_once 'library/Xan/Payment/Data/Paypal/AuthInfo.php';
require_once 'library/Xan/Payment/Data/Paypal/Item.php';
require_once 'library/Xan/Payment/Data/Paypal/ShippingOption.php';

class IndexController extends General_AuthController
{

    public function init()
    {
        parent::init();
    }

    public function indexAction()
    {

    }

    protected function _getXanPaymentService()
    {

        require_once 'library/Xan/Payment/Api/Paypal.php';
        $vpPaypal = new Xan_Payment_Api_Paypal();

        //sandbox
        $authInfo = new Xan_Payment_Data_Paypal_AuthInfo();
        $authInfo->setAuth('seller_gfgfgf.gmail.com',
        					'gfgdfgdfgdf',
        					'Asfdsgfdgd'
        );


        $vpPaypal->setAuthInfo($authInfo);

        //live


        $vpPaypal->enableTestMode();

        return $vpPaypal;

    }

    public function successAction()
    {
 		$vpPaypal = $this->_getXanPaymentService();
        $this->view->resArray = $vpPaypal->getLastApiRes();
    }

    public function failureAction()
    {
        // action body
    }

    public function ipnAction()
    {
    	require_once 'library/Xan/Payment/Ipn/PaypalIpn.php';
    	$vpPaypalIpn = new Xan_Payment_Ipn_Paypal();

    	$vpPaypalIpn->enableTestMode();
    	$vpPaypalIpn->validateIpn();
    }


    public function startPayAction()
    {

        $vpPaypal = $this->_getXanPaymentService();

        $token = $this->getRequest()->getParam('token');

        $itemName0 = $this->getRequest()->getParam('L_NAME0');
        $itemAMT0 = $this->getRequest()->getParam('L_AMT0');
        $itemQTY0 = $this->getRequest()->getParam('L_QTY0');

        $itemName1 = $this->getRequest()->getParam('L_NAME1');
        $itemAMT1 = $this->getRequest()->getParam('L_AMT1');
        $itemQTY1 = $this->getRequest()->getParam('L_QTY1');

        $currency = $this->getRequest()->getParam('currencyCodeType');

        if(!isset($token)){

        	$vpPaypal->setAppId(3);
        	$vpPaypal->setUserId(300);
        	$vpPaypal->setCompany(2);

            $vpPaypal->setCurrency($currency);
            $vpPaypal->setPaymentType(Xan_Payment_Api_Paypal::PAYMENT_ACTION_SALE);

		    $returnURL = $this->view->serverUrl().$this->_helper->url('start-pay');
            $cancelURL = $this->view->serverUrl().$this->_helper->url('start-pay');

            $vpPaypal->setUrls($returnURL,$cancelURL);

            $item0 = new Xan_Payment_Data_Paypal_Item();
            $item0->index = 0;
            $item0->name = $itemName0;
            $item0->amt = $itemAMT0;
            $item0->qta = $itemQTY0;
            $item0->number = 000;
            $item0->desc = 'desc0';

            $vpPaypal->addItem($item0);

            $item1 = new Xan_Payment_Data_Paypal_Item();
            $item1->index = 1;
            $item1->name = $itemName1;
            $item1->amt = $itemAMT1;
            $item1->qta = $itemQTY1;
            $item1->number = 001;
            $item1->desc = 'desc0';

            $vpPaypal->addItem($item1);

            $address = new Xan_Payment_Data_Paypal_Address();
            $address->name = 'mario brega';
            $address->street = 'via strada1';
            $address->city = 'Roma';
            $address->countryCode = 'RM';
            $address->state = 'IT';
            $address->zip = '00010';
            $address->setShipToPrefix();

            $vpPaypal->setShipping($address);
            $vpPaypal->setShippingAmt('8.00');

            $shipOpt0 = new Xan_Payment_Data_Paypal_ShippingOption();
            $shipOpt0->index = 0;
            $shipOpt0->name = 'UPS Air';
            $shipOpt0->label = 'UPS Next Day Air';
            $shipOpt0->amt = '8.00';
            $shipOpt0->default = true;

            $shipOpt1 = new Xan_Payment_Data_Paypal_ShippingOption();
            $shipOpt1->index = 1;
            $shipOpt1->name = 'Ground';
            $shipOpt1->label = 'UPS Ground 7 Days';
            $shipOpt1->amt = '2.00';
            $shipOpt1->default = false;

            $vpPaypal->addShippingOpt($shipOpt0);
            $vpPaypal->addShippingOpt($shipOpt1);

            $vpPaypal->setShippingDiscountAmt('-5.00');
            $vpPaypal->setTaxAmt('2.00');
            $vpPaypal->setInsuranceAmt('1.00');

            $vpPaypal->setLandingPage(Xan_Payment_Api_Paypal::LANDING_PAGE_BILLING);//landing page con cc

			$vpPaypal->setBillingAgreementFields('iscrizione nuovo test');//Rp mode on

            $token = $vpPaypal->ecFirstStep();
            if($token)
              	return $this->_redirect($vpPaypal->getEcUrl().$token);
             else
              	return $this->_helper->redirector->gotoSimpleAndExit('api-error');
        }else{
           	//FROM PAYPAL
           	if($vpPaypal->ecSecondStep())
            	return $this->_helper->redirector->gotoSimpleAndExit('pay');
           	else
           		return $this->_helper->redirector->gotoSimpleAndExit('api-error');
        }
    }

    public function payAction()
    {
        //review data before complete payment
        if($this->getRequest()->isGet()){
            $vpPaypal = $this->_getXanPaymentService();
             $this->view->resArray = $vpPaypal->getLastApiRes();
       	}

        //ends payment process
        if($this->getRequest()->isPost())
        {
			$vpPaypal = $this->_getXanPaymentService();

        	if( //$vpPaypal->ecThirdStep()
				$vpPaypal->ecThirdStepRp('Day','01','07',
										 'Day','01','07','1.6')
        	)
        	{

        		return $this->_helper->redirector->gotoSimpleAndExit('success');
        	}
        	else{
        		return $this->_helper->redirector->gotoSimpleAndExit('api-error');
        	}

        }
    }

	public function userAccountAction()
	{

	}

    public function testAction()
    {
		$vpPaypal = $this->_getXanPaymentService();
    }

    public function apiErrorAction()
    {
		$vpPaypal = $this->_getXanPaymentService();
        $this->view->resArray = $vpPaypal->getLastApiRes();
    }

    public function balanceAction()
    {
		$vpPaypal = $this->_getXanPaymentService();
        $vpPaypal->getBalance();
        $this->view->resArray = $vpPaypal->getLastApiRes();

    }


}
