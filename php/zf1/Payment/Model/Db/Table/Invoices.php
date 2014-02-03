<?php

require_once 'Zend/Db/Table/Abstract.php';
require_once 'Zend/Date.php';

class Xan_Payment_Model_DbTable_Invoices extends Zend_Db_Table_Abstract {
	
		protected $_name = 'invoices';
		protected $_rowClass = 'Xan_Payment_Model_DbTable_Row_Invoices';

		
		public function init(){
			
		}
		
		private function _saveLogic(array $data){
			
			return $data;			
		}
		
		public function insert(array $data){
			
			//$data['date_opened'] = Zend_Date::now()->getTime();
			
			parent::insert($this->_saveLogic($data));

			$lastInserID = $this->_db->lastInsertId();
			
			return $lastInserID;			
		}

	    public function update(array $data, $where)
	    {
	    	return parent::update($this->_saveLogic($data), $where);
	    }
	    
}

require_once 'Zend/Db/Table/Row/Abstract.php';

class Xan_Payment_Model_DbTable_Row_Invoices extends Zend_Db_Table_Row_Abstract {

}