<?php
class Xan_Payment_Data_Paypal_Address
{
	public $name;
    /**
     * Street
     *
     * @var string
     */
    public $street;

    /**
     * TODO: description.
     * 
     * @var string
     */
    public $street2;
    
    /**
     * City
     *
     * @var string
     */
    public $city;
    
    /**
     * State (2 character code)
     *
     * @var string
     */
    public $state = 'ZZ';
    
    /**
     * Country (2 character code)
     *
     * @var string
     */
    public $countryCode;
    
    /**
     * Zip code
     *
     * @var integer
     */
    public $zip;

    /**
     * TODO: description.
     * 
     * @var mixed
     */
    public $phoneNum;

    protected $_prefix;
    
    public function setShipToPrefix()
    {
    	$this->_prefix = 'SHIPTO';
    }
    public function setPaymentRequestPrefix($n)
    {
    	$this->_prefix = 'PAYMENTREQUEST_'.$n.'_SHIPTO';
    }
    
    /**
     * TODO: short description.
     * 
     * @return TODO
     */
    public function toNvp()
    {
        $data = array();
        $data[$this->_prefix.'NAME']     = $this->name;
        $data[$this->_prefix.'STREET']     = $this->street;
        $data[$this->_prefix.'STREET2']    = $this->street2;
        $data[$this->_prefix.'STATE']      = $this->state;
        $data[$this->_prefix.'COUNTRYCODE']= $this->countryCode;
        $data[$this->_prefix.'ZIP']        = $this->zip;
        $data[$this->_prefix.'PHONENUM']   = $this->phoneNum;

        return array_filter( $data );
    }
} 
