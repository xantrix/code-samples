<?php
require_once 'Zend/Db/Table/Abstract.php';
require_once 'library/Xan/Payment/Model/Db/Table/Invoices.php';
require_once 'library/Xan/Payment/Model/Db/Table/InvoiceLineItems.php';
require_once 'library/Xan/Payment/Model/Db/Table/Orders.php';
require_once 'library/Xan/Payment/Model/Db/Table/OrderLineItems.php';
require_once 'library/Xan/Payment/Model/Db/Table/financialTransactions.php';
require_once 'library/Xan/Payment/Model/Db/Table/Accounts.php';
require_once 'library/Xan/Payment/Model/Db/Table/Clients.php';

require_once 'Zend/Date.php';

class Xan_Payment_Model
{
	protected static $_instance = null;

	protected $_invoices;
	protected $_invoiceLineItems;
	protected $_orders;
	protected $_orderLineItems;
	protected $_financialTransactions;
	protected $_accounts;
	protected $_clients;
	protected $_accountsClients;

	public function __construct()
	{
		$this->_invoices = new Xan_Payment_Model_DbTable_Invoices();
		$this->_invoiceLineItems = new Xan_Payment_Model_DbTable_InvoiceLineItems();
		$this->_orders = new Xan_Payment_Model_DbTable_Orders();
		$this->_orderLineItems = new Xan_Payment_Model_DbTable_OrderLineItems();
		$this->_financialTransactions = new Xan_Payment_Model_DbTable_FinancialTransactions();
		$this->_accounts = new Xan_Payment_Model_DbTable_Accounts();
		$this->_clients = new Xan_Payment_Model_DbTable_Clients();
		$this->_accountsClients = new Xan_Payment_Model_DbTable_AccountsClients();
	}

	public static function getInstance(){
		if(self::$_instance === null)
			self::$_instance = new self();

		return self::$_instance;;
	}

	public function createAccount($gatewayId)
	{
		$row = $this->_accounts->createRow();
		$row->gateway_id = $gatewayId;
		$row->date_opened = Zend_Date::now()->getTimestamp();

		if($row->save() > 0)
			return $row;

		return false;
	}

	public function getAccount($gatewayId){

		return $this->_accounts->fetchRow(
    		$this->_accounts->select()->where('gateway_id = ?', $gatewayId)
    	);
	}

	public function createClient($appId,$userId){
		$row = $this->_clients->createRow();
		$row->app_id = $appId;
		$row->user_id = $userId;

		if($row->save() > 0)
			return $row;

		return false;
	}

	/**
	 *
	 * @param unknown_type $appId
	 * @param unknown_type $userId
	 * @return row|null
	 */
	public function getClient($appId,$userId)
	{
    	return $this->_clients->fetchRow(
    		$this->_clients->select()->where('app_id = ?', (int) $appId)
    									->where('user_id = ?', (int) $userId)
    	);
	}

	public function createRelAccountClient($accountId,$clientId){
		$row = $this->_accountsClients->createRow();
		$row->account_id = $accountId;
		$row->client_id = $clientId;

		if($row->save() > 0)
			return $row;

		return false;
	}
	public function getRelAccountClient($accountId,$clientId){
    	return $this->_accountsClients->fetchRow(
    		$this->_accountsClients->select()->where('account_id = ?', (int) $accountId)
    									->where('client_id = ?', (int) $clientId)
    	);
	}



	public function createInvoice($data)
	{
		if(!isset($data['invoice_date']))
			$data['invoice_date'] = Zend_Date::now()->getTimestamp();

		$row = $this->_invoices->createRow($data);

		if($row->save() > 0)
			return $row;

		return false;
	}

	public function createOrder($data)
	{
		if(!isset($data['order_date']))
			$data['order_date'] = Zend_Date::now()->getTimestamp();

		$row = $this->_orders->createRow($data);

		if($row->save() > 0)
			return $row;

		return false;
	}

	public function updateInvoice($id,array $data)
	{
    	$row = $this->getInvoiceById($id);
    	if($row !== null){
    		$row->setFromArray($data);
    		$row->save();
    		return $row;
    	}
    	return false;
	}

	public function updateOrder($id,array $data)
	{
    	$row = $this->getOrderById($id);
    	if($row !== null){
    		$row->setFromArray($data);
    		$row->save();
    		return $row;
    	}
    	return false;
	}

	public function getSelectInvoices($accountId = '', $clientId = '')
	{
		$select = $this->_invoices->select();

		if($clientId)
			$select->where('client_id = ?', $clientId);

		if($accountId)
			$select->where('account_id = ?', $accountId);

		return $select;
	}

	public function getSelectOrders($accountId = '', $clientId = '')
	{
		$select = $this->_orders->select();

		if($clientId)
			$select->where('client_id = ?', $clientId);

		if($accountId)
			$select->where('account_id = ?', $accountId);

		return $select;
	}

	public function getInvoiceLinesByInvoiceId($id){
		return $this->_invoiceLineItems->fetchAll(
    		$this->_invoiceLineItems->select()->where('invoice_id = ?', $id)
    	);
	}

	public function getOrderLinesByOrderId($id){
		return $this->_orderLineItems->fetchAll(
    		$this->_orderLineItems->select()->where('order_id = ?', $id)
    	);
	}

	public function getInvoiceById($id){
		return $this->_invoices->fetchRow(
    		$this->_invoices->select()->where('invoice_id = ?', $id)
    	);
	}
	public function getOrderById($id){
		return $this->_orders->fetchRow(
    		$this->_orders->select()->where('order_id = ?', $id)
    	);
	}
	public function getInvoiceByNumber($invNum){
		return $this->_invoices->fetchRow(
    		$this->_invoices->select()->where('invoice_number = ?', $invNum)
    	);
	}
	public function getOrderByNumber($ordNum){
		return $this->_orders->fetchRow(
    		$this->_orders->select()->where('order_number = ?', $ordNum)
    	);
	}

	public function getInvoiceByProfileRpId($profileRpId){
		return $this->_invoices->fetchRow(
    		$this->_invoices->select()->where('profile_rp_id = ?', $profileRpId)
    	);
	}

	public function getOrderByProfileRpId($profileRpId){
		return $this->_orders->fetchRow(
    		$this->_orders->select()->where('profile_rp_id = ?', $profileRpId)
    	);
	}

	public function getInvoiceByTrCode($code){
		return $this->_invoices->fetchRow(
    		$this->_invoices->select()->where('transaction_code = ?', $code)
    	);
	}

	public function getOrderByTrCode($code){
		return $this->_orders->fetchRow(
    		$this->_orders->select()->where('transaction_code = ?', $code)
    	);
	}


	public function createInvoiceLine($data)
	{
		$row = $this->_invoiceLineItems->createRow($data);

		if($row->save() > 0)
			return $row;

		return false;
	}

	public function createOrderLine($data)
	{
		$row = $this->_orderLineItems->createRow($data);

		if($row->save() > 0)
			return $row;

		return false;
	}

	public function getSelectInvoiceLines($invoiceId)
	{
		$select = $this->_invoiceLineItems->select();

		if($invoiceId)
			$select->where('invoice_id = ?', $invoiceId);

		return $select;
	}
	public function getSelectOrderLines($orderId)
	{
		$select = $this->_orderLineItems->select();

		if($orderId)
			$select->where('order_id = ?', $orderId);

		return $select;
	}


	public function createFinancialTransaction($data)
	{
		if(!isset($data['transaction_date']))
			$data['transaction_date'] = Zend_Date::now()->getTimestamp();

		$row = $this->_financialTransactions->createRow($data);

		if($row->save() > 0)
			return $row;

		return false;
	}

	public function getSelectFinancialTransactions($invoiceId)
	{
		$select = $this->_financialTransactions->select();

		if($invoiceId)
			$select->where('invoice_id = ?', $invoiceId);

		return $select;
	}

	public function getFinancialTransactionByCode($code)
	{
		return $this->_financialTransactions->fetchRow(
    		$this->_financialTransactions->select()->where('transaction_code = ?', $code)
    	);
	}

	public function getFinancialTransactionByInvoiceId($invoiceId)
	{
		return $this->_financialTransactions->fetchRow(
    		$this->_financialTransactions->select()->where('invoice_id = ?', $invoiceId)
    	);
	}

	public function getFinancialTransactionById($id)
	{
		return $this->_financialTransactions->fetchRow(
    		$this->_financialTransactions->select()->where('transaction_id = ?', $id)
    	);
	}

}