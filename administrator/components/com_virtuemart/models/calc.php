<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');
/**
*
* Data module for shop calculation rules
*
* @package	VirtueMart
* @subpackage  Calculation tool
* @author Max Milbers
* @author mediaDESIGN> St.Kraft 2013-02-24 manufacturer relation added
* @link http://www.virtuemart.net
* @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id: calc.php 8130 2014-07-14 16:47:09Z Milbo $
*/


if(!class_exists('VmModel'))require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'vmmodel.php');

class VirtueMartModelCalc extends VmModel {


    /**
     * Constructor for the calc model.
     *
     * The calc id is read and detmimined if it is an array of ids or just one single id.
     *
     * @author RickG
     */
    public function __construct(){

    	parent::__construct();

			$this->setMainTable('calcs');
			$this->setToggleName('calc_shopper_published');
			$this->setToggleName('calc_vendor_published');
	  		$this->setToggleName('shared');
			$this->addvalidOrderingFieldName(array('virtuemart_category_id','virtuemart_country_id','virtuemart_state_id','virtuemart_shoppergroup_id'
				,'virtuemart_manufacturer_id'
			)); 
    }


    /**
     * Retrieve the detail record for the current $id if the data has not already been loaded.
     *
     * @author Max Milbers
     */
	public function getCalc($id = 0){

		if(!empty($id)) $this->_id = (int)$id;

		if (empty($this->_cache[$this->_id])) {

			$this->_cache[$this->_id] = $this->getTable('calcs');
			$this->_cache[$this->_id]->load((int)$this->_id);

			$xrefTable = $this->getTable('calc_categories');
			$this->_cache[$this->_id]->calc_categories = $xrefTable->load($this->_id);
			if ( $xrefTable->getError() ) {
				vmError(get_class( $this ).' calc_categories '.$xrefTable->getError());
			}

			$xrefTable = $this->getTable('calc_shoppergroups');
			$this->_cache[$this->_id]->virtuemart_shoppergroup_ids = $xrefTable->load($this->_id);
			if ( $xrefTable->getError() ) {
				vmError(get_class( $this ).' calc_shoppergroups '.$xrefTable->getError());
			}

			$xrefTable = $this->getTable('calc_countries');
			$this->_cache[$this->_id]->calc_countries = $xrefTable->load($this->_id);
			if ( $xrefTable->getError() ) {
				vmError(get_class( $this ).' calc_countries '.$xrefTable->getError());
			}

			$xrefTable = $this->getTable('calc_states');
			$this->_cache[$this->_id]->virtuemart_state_ids = $xrefTable->load($this->_id);
			if ( $xrefTable->getError() ) {
				vmError(get_class( $this ).' virtuemart_state_ids '.$xrefTable->getError());
			}

			$xrefTable = $this->getTable('calc_manufacturers');
			$this->_cache[$this->_id]->virtuemart_manufacturers = $xrefTable->load($this->_id);
			if ( $xrefTable->getError() ) {
				vmError(get_class( $this ).' calc_manufacturers '.$xrefTable->getError());
			}

			JPluginHelper::importPlugin('vmcalculation');
			$dispatcher = JDispatcher::getInstance();
			$dispatcher->trigger('plgVmGetPluginInternalDataCalc',array(&$this->_cache[$this->_id]));

  		}

  		return $this->_cache[$this->_id];
	}

	/**
	 * Retrieve a list of calculation rules from the database.
	 *
     * @author Max Milbers
     * @param string $onlyPuiblished True to only retreive the published Calculation rules, false otherwise
     * @param string $noLimit True if no record count limit is used, false otherwise
	 * @return object List of calculation rule objects
	 */
	public function getCalcs($onlyPublished=false, $noLimit=false, $search=false){

		$where = array();
		$this->_noLimit = $noLimit;

		// add filters
		if ($onlyPublished) $where[] = '`published` = 1';

		$db = JFactory::getDBO();
		if($search){
			$search = '"%' . $db->escape( $search, true ) . '%"' ;
			$where[] = ' `calc_name` LIKE '.$search.' OR `calc_descr` LIKE '.$search.' OR `calc_value` LIKE '.$search.' ';
		}

		$whereString= '';
		if (count($where) > 0) $whereString = ' WHERE '.implode(' AND ', $where) ;

		$datas = $this->exeSortSearchListQuery(0,'*',' FROM `#__virtuemart_calcs`',$whereString,'',$this->_getOrdering());

		if(!class_exists('ShopFunctions')) require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'shopfunctions.php');
		foreach ($datas as &$data){

			$data->currencyName = ShopFunctions::getCurrencyByID($data->calc_currency);

			JPluginHelper::importPlugin('vmcalculation');
			$dispatcher = JDispatcher::getInstance();
			$error = $dispatcher->trigger('plgVmGetPluginInternalDataCalcList',array(&$data));
		}

		return $datas;
	}

	/**
	 * Bind the post data to the calculation table and save it
     *
     * @author Max Milbers
     * @return boolean True is the save was successful, false otherwise.
	 */
    public function store(&$data) {

		vRequest::vmCheckToken();

		$table = $this->getTable('calcs');

		$db = JFactory::getDBO();
		// Convert selected dates to MySQL format for storing.
		$startDate = JFactory::getDate($data['publish_up']);
		$data['publish_up'] = $startDate->toSQL();
//		if ($data['publish_down'] == '' or $data['publish_down']==0){
		if (empty($data['publish_down']) || trim($data['publish_down']) == vmText::_('COM_VIRTUEMART_NEVER')){
			$data['publish_down']	= $db->getNullDate();
		} else {
			$expireDate = JFactory::getDate($data['publish_down']);
			$data['publish_down']	= $expireDate->toSQL();
		}

		$table->bindChecknStore($data);
		if($table->getError()){
			vmError('Calculation store '.$table->getError());
			return false;
		}

    	$xrefTable = $this->getTable('calc_categories');
    	$xrefTable->bindChecknStore($data);
    	if($xrefTable->getError()){
			vmError('Calculation store '.$xrefTable->getError());
		}

		$xrefTable = $this->getTable('calc_shoppergroups');
    	$xrefTable->bindChecknStore($data);
    	if($xrefTable->getError()){
			vmError('Calculation store '.$xrefTable->getError());
		}

		$xrefTable = $this->getTable('calc_countries');
    	$xrefTable->bindChecknStore($data);
    	if($xrefTable->getError()){
			vmError('Calculation store '.$xrefTable->getError());
		}

		$xrefTable = $this->getTable('calc_states');
    	$xrefTable->bindChecknStore($data);
    	if($xrefTable->getError()){
			vmError('Calculation store '.$xrefTable->getError());
		}

		$xrefTable = $this->getTable('calc_manufacturers');
    	$xrefTable->bindChecknStore($data);
    	if($xrefTable->getError()){
			vmError('Calculation store '.$xrefTable->getError());
		}

		if (!class_exists('vmCalculationPlugin')) require(JPATH_VM_PLUGINS . DS . 'vmcalculationplugin.php');
		JPluginHelper::importPlugin('vmcalculation');
		$dispatcher = JDispatcher::getInstance();
		$error = $dispatcher->trigger('plgVmStorePluginInternalDataCalc',array(&$data));

    	$errMsg = $db->getErrorMsg();
		$errs = $db->getErrors();

		if(!empty($errMsg)){

			$errNum = $db->getErrorNum();
			vmError('SQL-Error: '.$errNum.' '.$errMsg.' <br /> used query '.$db->getQuery());
		}

		if(!empty($errs)){
			foreach($errs as $err){
				if(!empty($err)) vmError('Calculation store '.$err);
			}
		}

		return $table->virtuemart_calc_id;
	}

	static function getRule($kind){

		if (!is_array($kind)) $kind = array($kind);
		$db = JFactory::getDBO();

		$nullDate		= $db->getNullDate();
		$now			= JFactory::getDate()->toSQL();

		$q = 'SELECT * FROM `#__virtuemart_calcs` WHERE ';
		foreach ($kind as $field){
			$q .= '`calc_kind`='.$db->Quote($field).' OR ';
		}
		$q=substr($q,0,-3);

		$q .= 'AND ( publish_up = "' . $db->escape($nullDate) . '" OR publish_up <= "' . $db->escape($now) . '" )
				AND ( publish_down = "' . $db->escape($nullDate) . '" OR publish_down >= "' . $db->escape($now) . '" ) ';


		$db->setQuery($q);
		$data = $db->loadObjectList();

		if (!$data) {
   			$data = new stdClass();
  		}
  		return $data;
	}

	/**
	* Delete all calcs selected
	*
	* @author Max Milbers
	* @param  array $cids categories to remove
	* @return boolean if the item remove was successful
	*/
	public function remove($cids) {

		vRequest::vmCheckToken();

		$table = $this->getTable($this->_maintablename);
		$cat = $this->getTable('calc_categories');
		$sgrp = $this->getTable('calc_shoppergroups');
		$countries = $this->getTable('calc_countries');
		$states = $this->getTable('calc_states');
		$manufacturers = $this->getTable('calc_manufacturers');

		$ok = true;

		foreach($cids as $id) {
			$id = (int)$id;
			vmdebug('remove '.$id);
			if (!$table->delete($id)) {
				vmError(get_class( $this ).'::remove '.$id.' '.$table->getError());
				$ok = false;
			}

			if (!$cat->delete($id)) {
				vmError(get_class( $this ).'::remove '.$id.' '.$cat->getError());
				$ok = false;
			}

			if (!$sgrp->delete($id)) {
				vmError(get_class( $this ).'::remove '.$id.' '.$sgrp->getError());
				$ok = false;
			}

			if (!$countries->delete($id)) {
				vmError(get_class( $this ).'::remove '.$id.' '.$countries->getError());
				$ok = false;
			}

			if (!$states->delete($id)) {
				vmError(get_class( $this ).'::remove '.$id.' '.$states->getError());
				$ok = false;
			}

			// Mod. <mediaDESIGN> St.Kraft 2013-02-24
			if (!$manufacturers->delete($id)) {
				vmError(get_class( $this ).'::remove '.$id.' '.$manufacturers->getError());
				$ok = false;
			}

// 			if(!class_exists('vmPSPlugin')) require(JPATH_VM_PLUGINS.DS.'vmpsplugin.php');
			JPluginHelper::importPlugin('vmcalculation');
			$dispatcher = JDispatcher::getInstance();
			$returnValues = $dispatcher->trigger('plgVmDeleteCalculationRow', array( $id));

		}

		return $ok;
	}

	static function getTaxes() {

		return self::getRule(array('TAX','VatTax','TaxBill'));
	}

	static function getDiscounts(){
		return  self::getRule(array('DATax','DATaxBill','DBTax','DBTaxBill'));
	}

	static function getDBDiscounts() {

		return self::getRule(array('DBTax','DBTaxBill'));
	}

	static function getDADiscounts() {

		return self::getRule(array('DATax','DATaxBill'));
	}
}