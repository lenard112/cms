<?php
/**
*
* Model paymentmethod
*
* @package	VirtueMart
* @subpackage  Payment
* @author Max Milbers
* @link http://www.virtuemart.net
* @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id: paymentmethod.php 8139 2014-07-22 14:16:45Z Milbo $
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

if(!class_exists('VmModel'))require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'vmmodel.php');

class VirtueMartModelPaymentmethod extends VmModel{

	/**
	 * constructs a VmModel
	 * setMainTable defines the maintable of the model
	 * @author Max Milbers
	 */
	function __construct() {
		parent::__construct();
		$this->setMainTable('paymentmethods');
		$this->_selectedOrdering = 'ordering';
		$this->setToggleName('shared');
	}

	/**
	 * Gets the virtuemart_paymentmethod_id with a plugin and vendorId
	 *
	 * @author Max Milbers
	 */
	public function getIdbyCodeAndVendorId($jpluginId,$vendorId=1){
	 	if(!$jpluginId) return 0;
	 	$q = 'SELECT `virtuemart_paymentmethod_id` FROM #__virtuemart_paymentmethods WHERE `payment_jplugin_id` = "'.$jpluginId.'" AND `virtuemart_vendor_id` = "'.$vendorId.'" ';
		$db = JFactory::getDBO();
		$db->setQuery($q);
		return $db->loadResult();
	}

    /**
     * Retrieve the detail record for the current $id if the data has not already been loaded.
     *
     * @author Max Milbers
     */
	public function getPayment($id = 0){

		if(!empty($id)) $this->_id = (int)$id;

		if (empty($this->_cache[$this->_id])) {
			$this->_cache[$this->_id] = $this->getTable('paymentmethods');
			$this->_cache[$this->_id]->load((int)$this->_id);

			if(empty($this->_cache->virtuemart_vendor_id)){
				if(!class_exists('VirtueMartModelVendor')) require(JPATH_VM_ADMINISTRATOR.DS.'models'.DS.'vendor.php');
				$this->_cache[$this->_id]->virtuemart_vendor_id = VirtueMartModelVendor::getLoggedVendor();
			}

			if($this->_cache[$this->_id]->payment_jplugin_id){
				JPluginHelper::importPlugin('vmpayment');
				$dispatcher = JDispatcher::getInstance();
				$retValue = $dispatcher->trigger ('plgVmDeclarePluginParamsPaymentVM3', array(&$this->_cache[$this->_id]));
				//$retValue = $dispatcher->trigger('plgVmDeclarePluginParamsPayment',array($this->_cache[$this->_id]->payment_element,$this->_cache[$this->_id]->payment_jplugin_id,&$this->_cache));
			}

			if(!empty($this->_cache[$this->_id]->_varsToPushParam)){
				VmTable::bindParameterable($this->_cache[$this->_id],'payment_params',$this->_cache[$this->_id]->_varsToPushParam);
			}

			//todo check if we still need this
			/*if($this->_data[$this->_id]->getCryptedFields()){
				if(!class_exists('vmCrypt')){
					require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'vmcrypt.php');
				}

				if(isset($this->_data[$this->_id]->modified_on)){
					$date = JFactory::getDate($this->_data[$this->_id]->modified_on);
					$date = $date->toUnix();
				} else {
					$date = 0;
				}

				foreach($this->_data[$this->_id]->getCryptedFields() as $field){
					if(isset($this->_data[$this->_id]->$field)){
						$this->_data[$this->_id]->$field = vmCrypt::decrypt($this->_data[$this->_id]->$field,$date);
					}
				}
			}*/

			$q = 'SELECT `virtuemart_shoppergroup_id` FROM #__virtuemart_paymentmethod_shoppergroups WHERE `virtuemart_paymentmethod_id` = "'.$this->_id.'"';
			$this->_db->setQuery($q);
			$this->_cache[$this->_id]->virtuemart_shoppergroup_ids = $this->_db->loadResultArray();
			if(empty($this->_cache[$this->_id]->virtuemart_shoppergroup_ids)) $this->_cache[$this->_id]->virtuemart_shoppergroup_ids = 0;

		}

		return $this->_cache[$this->_id];
	}

	/**
	 * Retireve a list of calculation rules from the database.
	 *
     * @author Max Milbers
     * @param string $onlyPuiblished True to only retreive the publish Calculation rules, false otherwise
     * @param string $noLimit True if no record count limit is used, false otherwise
	 * @return object List of calculation rule objects
	 */
	public function getPayments($onlyPublished=false, $noLimit=false) {
		$where = array();
		if ($onlyPublished) {
			$where[] = ' `#__virtuemart_paymentmethods`.`published` = 1';
		}

		$whereString = '';
		if (count($where) > 0) $whereString = ' WHERE '.implode(' AND ', $where) ;

		$select = ' * FROM `#__virtuemart_paymentmethods_'.VmConfig::$vmlang.'` as l ';
		$joinedTables = ' JOIN `#__virtuemart_paymentmethods`   USING (`virtuemart_paymentmethod_id`) ';
		$datas =$this->exeSortSearchListQuery(0,$select,$joinedTables,$whereString,' ',$this->_getOrdering() );

			//$this->exeSortSearchListQuery(0,'*',' FROM `#__virtuemart_paymentmethods`',$whereString,'',$this->_getOrdering('ordering'));

		if(isset($datas)){

			if(!class_exists('shopfunctions')) require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'shopfunctions.php');
			foreach ($datas as &$data){
				/* Add the paymentmethod shoppergroups */
				$q = 'SELECT `virtuemart_shoppergroup_id` FROM #__virtuemart_paymentmethod_shoppergroups WHERE `virtuemart_paymentmethod_id` = "'.$data->virtuemart_paymentmethod_id.'"';
				$db = JFactory::getDBO();
				$db->setQuery($q);
				$data->virtuemart_shoppergroup_ids = $db->loadColumn();

			}

		}
		return $datas;
	}


	/**
	 * Bind the post data to the paymentmethod tables and save it
     *
     * @author Max Milbers
     * @return boolean True is the save was successful, false otherwise.
	 */
    public function store(&$data){

		if ($data) {
			$data = (array)$data;
		}

		if(!empty($data['params'])){
			foreach($data['params'] as $k=>$v){
				$data[$k] = $v;
			}
		}

	  	if(empty($data['virtuemart_vendor_id'])){
	  	   	if(!class_exists('VirtueMartModelVendor')) require(JPATH_VM_ADMINISTRATOR.DS.'models'.DS.'vendor.php');
	   		$data['virtuemart_vendor_id'] = VirtueMartModelVendor::getLoggedVendor();
	  	}


		$table = $this->getTable('paymentmethods');

		if(isset($data['payment_jplugin_id'])){

			$q = 'SELECT `element` FROM `#__extensions` WHERE `extension_id` = "'.$data['payment_jplugin_id'].'"';
			$db = JFactory::getDBO();
			$db->setQuery($q);
			$data['payment_element'] = $db->loadResult();

			$q = 'UPDATE `#__extensions` SET `enabled`= 1 WHERE `extension_id` = "'.$data['payment_jplugin_id'].'"';
			$db->setQuery($q);
			$db->execute();


			JPluginHelper::importPlugin('vmpayment');
			$dispatcher = JDispatcher::getInstance();
			$retValue = $dispatcher->trigger('plgVmSetOnTablePluginParamsPayment',array( $data['payment_element'],$data['payment_jplugin_id'],&$table));

		}

		$table->bindChecknStore($data);
		$errors = $table->getErrors();
		foreach($errors as $error){
				vmError($error);
			}

		$xrefTable = $this->getTable('paymentmethod_shoppergroups');
		$xrefTable->bindChecknStore($data);
		$errors = $xrefTable->getErrors();
		foreach($errors as $error){
			vmError($error);
		}

		if (!class_exists('vmPSPlugin')) require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');
			JPluginHelper::importPlugin('vmpayment');
			//Add a hook here for other shipment methods, checking the data of the choosed plugin
			$dispatcher = JDispatcher::getInstance();
			$retValues = $dispatcher->trigger('plgVmOnStoreInstallPaymentPluginTable', array(  $data['payment_jplugin_id']));

		return $table->virtuemart_paymentmethod_id;
	}



	/**
	 * Due the new plugin system this should be obsolete
	 * function to render the payment plugin list
	 *
	 * @author Max Milbers
	 *
	 * @param radio list of creditcards
	 * @return html
	 */
	public function renderPaymentList($selectedPaym=0){

		$payms = self::getPayments(false,true);
		$listHTML='';
		foreach($payms as $item){
			$checked='';
			if($item->virtuemart_paymentmethod_id==$selectedPaym){
				$checked='"checked"';
			}
			$listHTML .= '<input type="radio" name="virtuemart_paymentmethod_id" value="'.$item->virtuemart_paymentmethod_id.'" '.$checked.'>'.$item->payment_name.' <br />';
			$listHTML .= ' <br />';
		}

		return $listHTML;

	}

	public function createClone ($id) {

		$this->setId ($id);
		$payment = $this->getPayment();
		$payment->virtuemart_paymentmethod_id = 0;
		$payment->payment_name = $payment->payment_name.' Copy';
		if (!$clone = $this->store($payment)) {
			JError::raiseError(500, 'createClone '. $payment->getError() );
		}
		return $clone;
	}

}
