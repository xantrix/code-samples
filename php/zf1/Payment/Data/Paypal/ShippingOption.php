<?php
class Xan_Payment_Data_Paypal_ShippingOption
{
	public $index;
	
	public $name;
	
	public $label;
	
	public $amt;
	
	public $default = false;
	
    public function toNvp()
    {
        $data = array();
        $data['L_SHIPPINGOPTIONNAME'.$this->index]    = $this->name;
        $data['L_SHIPPINGOPTIONlABEL'.$this->index]     = $this->label;
        $data['L_SHIPPINGOPTIONAMOUNT'.$this->index]     = $this->amt;
        $data['L_SHIPPINGOPTIONISDEFAULT'.$this->index]	= ($this->default) ? 'true' : 'false';

        return array_filter( $data );
    }	
	
}