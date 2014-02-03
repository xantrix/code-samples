<?php
class Xan_Payment_Data_Paypal_Item
{
	public $index;
	
    /**
     * Name
     *
     * @var string
     */
    public $name;

    /**
     * amt.
     * 
     * @var string
     */
    public $amt;
    
    /**
     * qta
     *
     * @var string
     */
    public $qta;
    
    /**
     * Number
     *
     * @var string
     */
    public $number;
    
    /**
     * desc 
     *
     * @var string
     */
    public $desc;
    
    public function getTotalAmt()
    {
    	return $this->amt * $this->qta;
    }
    
    
    public function toNvp()
    {
        $data = array();
        $data['L_NAME'.$this->index]    = $this->name;
        $data['L_AMT'.$this->index]     = $this->amt;
        $data['L_QTY'.$this->index]     = $this->qta;
        $data['L_NUMBER'.$this->index]	= $this->number;
        $data['L_DESC'.$this->index]    = $this->desc;

        return array_filter( $data );
    }
} 
