<?php

class Xan_Payment_Data_Paypal_AuthInfo
{
    
    protected $_username;
    
    protected $_password;
    
    protected $_signature;
    
    protected $_subject;
    
    protected $_authToken;
    
    protected $_authSignature;
    
    protected $_authTimestamp;
    
    public function __construct( $username = null, $password = null, $signature = null, $subject = null )
    {
        $this->setAuth($username,$password,$signature,$subject); 
    }
    
	public function setAuth($username = null, $password = null, $signature = null, $subject = null){
        $this->_username  = $username;
        $this->_password  = $password;
        $this->_signature = $signature;
        $this->_subject = $subject; 		
	}
    
    public function getUsername()
    {
        return $this->_username;
    }
    
    public function getPassword()
    {
        return $this->_password;
    }
    
    public function getSignature()
    {
        return $this->_signature;
    }
    
    public function getSubject()
    {
    	return $this->_subject;
    }
    
    public function setPermission($token,$signature,$timestamp)
    {
    	$this->_authToken = $token;
    	$this->_authSignature = $signature;
    	$this->_authTimestamp = $timestamp;
    }
    
    public function getAuthToken()
    {
    	return $this->_authToken;
    }

    public function getAuthSignature()
    {
    	return $this->_authSignature;
    }
    
    public function getAuthTimestamp()
    {
    	return $this->_authTimestamp;
    }
    
    
}