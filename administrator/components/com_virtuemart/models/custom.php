<?php
/**
*
* Description
*
* @package	VirtueMart
* @subpackage
* @author Max Milbers
* @link http://www.virtuemart.net
* @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved by the author.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id: custom.php 3057 2011-04-19 12:59:22Z Electrocity $
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

if(!class_exists('VmModel'))require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'vmmodel.php');

/**
 * Model for VirtueMart Customs Fields
 *
 * @package		VirtueMart
 */
class VirtueMartModelCustom extends VmModel {


	/**
	 * constructs a VmModel
	 * setMainTable defines the maintable of the model
	 * @author Max Milbers
	 */
	function __construct() {
		parent::__construct('virtuemart_custom_id');
		$this->setMainTable('customs');
		$this->setToggleName('admin_only');
		$this->setToggleName('is_hidden');
	}

    /**
     * Gets a single custom by virtuemart_custom_id
     * .
     * @param string $type
     * @param string $mime mime type of custom, use for exampel image
     * @return customobject
     */
    function getCustom($id = 0){

		if(!empty($id)) $this->_id = (int)$id;

    	if(empty($this->_cache[$this->_id])){

    		$this->_cache[$this->_id] = $this->getTable('customs');

		    $customfields = VmModel::getModel('Customfields');
		    $this->_cache[$this->_id]->field_types = $customfields->getField_types() ;

		    $this->_cache[$this->_id]->_varsToPushParam = self::getVarsToPush($this->_cache[$this->_id]->field_type);

			$this->_cache[$this->_id]->customfield_params = '';
		    if ($this->_cache[$this->_id]->field_type == 'E') {
			    JPluginHelper::importPlugin ('vmcustom');
			    $dispatcher = JDispatcher::getInstance ();
			    $retValue = $dispatcher->trigger ('plgVmDeclarePluginParamsCustomVM3', array(&$this->_cache[$this->_id]));
		    }
			//exaample 	vm2 withParent="0"|parentOrderable="0"|
			//			vm3 withParent="1"|parentOrderable="1"|
			$this->_cache[$this->_id]->_xParams = 'custom_params';
			$this->_cache[$this->_id]->load($this->_id);

    	}

  		return $this->_cache[$this->_id];

    }


    /**
	 * Retrieve a list of customs from the database. This is meant only for backend use
	 *
	 * @author Kohl Patrick
	 * @author Max Milbers
	 * @return object List of custom objects
	 */
    function getCustoms($custom_parent_id,$search = false){

	    $query='* FROM `#__virtuemart_customs` ';

		$where = array();
		if($custom_parent_id){
			$where[] = ' `custom_parent_id` ='.(int)$custom_parent_id;
		}

		if($search){
			$db = JFactory::getDBO();
			$search = '"%' . $db->escape( $search, true ) . '%"' ;
			$where[] = ' `custom_title` LIKE '.$search;
		}

		if (count ($where) > 0) {
			$whereString = ' WHERE (' . implode (' AND ', $where) . ') ';
		}
		else {
			$whereString = '';
		}
	    $datas = new stdClass();
		$datas->items = $this->exeSortSearchListQuery(0, $query, '',$whereString,$this->_getOrdering());

		$customfields = VmModel::getModel('Customfields');

		if (!class_exists('VmHTML')) require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'html.php');
		$datas->field_types = $customfields->getField_types() ;

		foreach ($datas->items as $key => & $data) {
	  		if (!empty($data->custom_parent_id)) $data->custom_parent_title = $this->getCustomParentTitle($data->custom_parent_id);
			else {
				$data->custom_parent_title =  '-' ;
			}
			if(!empty($datas->field_types[$data->field_type ])){
				$data->field_type_display = vmText::_( $datas->field_types[$data->field_type ] );
			} else {
				$data->field_type_display = 'not valid, delete this line';
				vmError('The field with id '.$data->virtuemart_custom_id.' and title '.$data->custom_title.' is not longer valid, please delete it from the list');
			}

		}
		$datas->customsSelect= $this->displayCustomSelection();

		return $datas;
    }

	public function getCustomParentTitle ($custom_parent_id) {

		$q = 'SELECT custom_title FROM `#__virtuemart_customs` WHERE virtuemart_custom_id =' . (int)$custom_parent_id;
		$db = JFactory::getDBO();
		$db->setQuery ($q);
		return $db->loadResult ();
	}

	/**
	 * Displays a possibility to select created custom
	 *
	 * @author Max Milbers
	 * @author Patrick Kohl
	 */
	public function displayCustomSelection () {

		$customslist = $this->getParentList ();
		if (isset($this->virtuemart_custom_id)) {
			$value = $this->virtuemart_custom_id;
		}
		else {
			$value = vRequest::getInt ('custom_parent_id', 0);
		}
		return VmHTML::row ('select', 'COM_VIRTUEMART_CUSTOM_GROUP', 'custom_parent_id', $customslist, $value);
	}

	/**
	 * Retrieve a list of layouts from the default and chosen templates directory.
	 *
	 * We may use here the getCustoms function of the custom model or write something simular
	 *
	 * @author Max Milbers
	 * @param name of the view
	 * @return object List of flypage objects
	 */
	function getCustomsList ($publishedOnly = FALSE) {

		$vendorId = 1;
		// get custom parents
		$q = 'SELECT `virtuemart_custom_id` AS value ,custom_title AS text FROM `#__virtuemart_customs` WHERE custom_parent_id="0" AND field_type <> "R" AND field_type <> "Z" ';
		if ($publishedOnly) {
			$q .= 'AND `published`=1';
		}
		if ($ID = vRequest::getInt ('virtuemart_custom_id', 0)) {
			$q .= ' AND `virtuemart_custom_id`!=' . (int)$ID;
		}
		$db = JFactory::getDBO();
		$db->setQuery ($q);

		$result = $db->loadObjectList ();

		$errMsg = $db->getErrorMsg ();
		$errs = $db->getErrors ();

		if (!empty($errMsg)) {
			$app = JFactory::getApplication ();
			$errNum = $db->getErrorNum ();
			$app->enqueueMessage ('SQL-Error: ' . $errNum . ' ' . $errMsg);
		}

		if ($errs) {
			$app = JFactory::getApplication ();
			foreach ($errs as $err) {
				$app->enqueueMessage ($err);
			}
		}

		return $result;
	}

	/**
	 *
	 * Enter description here ...
	 *
	 * @param unknown_type $excludedId
	 * @return unknown|multitype:
	 */
	function getParentList ($excludedId = 0) {

		$db = JFactory::getDBO();
		$db->setQuery (' SELECT virtuemart_custom_id as value,custom_title as text FROM `#__virtuemart_customs` WHERE `field_type` ="G" and virtuemart_custom_id!=' . $excludedId);
		if ($results = $db->loadObjectList ()) {
			return $results;
		}
		else {
			return array();
		}
	}


	/**
	 * Creates a clone of a given custom id
	 *
	 * @author Max Milbers
	 * @param int $virtuemart_product_id
	 */

	public function createClone($id){
		$this->virtuemart_custom_id = $id;
		$row = $this->getTable('customs');
		$row->load( $id );
		$row->virtuemart_custom_id = 0;
		$row->custom_title = $row->custom_title.' Copy';

		if (!$clone = $row->store()) {
			JError::raiseError(500, 'createClone '. $row->getError() );
		}
		return $clone;
	}


	/* Save and delete from database
	 *  all Child product custom_fields relation
	 * 	@ var   $table	: the xref table(eg. product,category ...)
	 * 	@array $data	: array of customfields
	 * 	@int     $id		: The concerned id (eg. product_id)
	 **/
	public function saveChildCustomRelation($table,$datas) {

		vRequest::vmCheckToken('Invalid token in saveChildCustomRelation');
		//Table whitelist
		$tableWhiteList = array('product','category','manufacturer');
		if(!in_array($table,$tableWhiteList)) return false;

		$db = JFactory::getDBO();
		// delete existings from modelXref and table customfields
		foreach ($datas as $child_id =>$fields) {
			$fields['virtuemart_'.$table.'_id']=$child_id;
			$db->setQuery( 'DELETE PC FROM `#__virtuemart_'.$table.'_customfields` as `PC`, `#__virtuemart_customs` as `C` WHERE `PC`.`virtuemart_custom_id` = `C`.`virtuemart_custom_id` AND field_type="C" and virtuemart_'.$table.'_id ='.$child_id );
			if(!$db->execute()){
				vmError('Error in deleting child relation '); //.$db->getQuery()); Dont give hackers too much info
			}

			$tableCustomfields = $this->getTable($table.'_customfields');
			$tableCustomfields->bindChecknStore($fields);
    		$errors = $tableCustomfields->getErrors();
			foreach($errors as $error){
				vmError($error);
			}
		}

	}


	public function store(&$data){

		if(!empty($data['params'])){
			foreach($data['params'] as $k=>$v){
				$data[$k] = $v;
			}
		}

		//I think this is obsolete, note by Max
		if(empty($data['virtuemart_vendor_id'])){
			if(!class_exists('VirtueMartModelVendor')) require(JPATH_VM_ADMINISTRATOR.DS.'models'.DS.'vendor.php');
			$data['virtuemart_vendor_id'] = VirtueMartModelVendor::getLoggedVendor();
		} else {
			$data['virtuemart_vendor_id'] = (int) $data['virtuemart_vendor_id'];
		}

		if(isset($data['custom_jplugin_id'])){

			$tb = '#__extensions';
			$ext_id = 'extension_id';

			$q = 'SELECT `element` FROM `' . $tb . '` WHERE `' . $ext_id . '` = "'.$data['custom_jplugin_id'].'"';
			$db = JFactory::getDBO();
			$db->setQuery($q);
			$data['custom_element'] = $db->loadResult();

			$q = 'UPDATE `#__extensions` SET `enabled`= 1 WHERE `extension_id` = "'.$data['custom_jplugin_id'].'"';
			$db->setQuery($q);
			$db->execute();

		}

		$table = $this->getTable('customs');
		$table->field_type = $data['field_type'];
		$table->custom_element = $data['custom_element'];
		$table->custom_jplugin_id = $data['custom_jplugin_id'];
		$table->_xParams = 'custom_params';

		if(isset($data['custom_title'])){
			$data['custom_title'] = htmlentities($data['custom_title'], ENT_QUOTES, "UTF-8");
		}
		if(!empty($data['is_input'])){
			if(empty($data['layout_pos'])) $data['layout_pos'] = 'addtocart';
		}

		//We are in the custom and so the table contains the field_type, else not!!
		self::setParameterableByFieldType($table,$table->field_type);

		$table->bindChecknStore($data);

		$errors = $table->getErrors();

		foreach($errors as $error){
			vmError($error);
		}

		JPluginHelper::importPlugin('vmcustom');
		$dispatcher = JDispatcher::getInstance();
		$error = $dispatcher->trigger('plgVmOnStoreInstallPluginTable', array('custom' , $data, $table));

		return $table->virtuemart_custom_id ;

	}

	/**
	 * Sets the xParams field and $varsToPush to the used table object,
	 * called in the store functions Customfields::storeProductCustomfields and custom store
	 * @param $table
	 * @param $type
	 * @param int $custom_element
	 * @param int $custom_jplugin_id
	 */
	static function setParameterableByFieldType(&$table, $type, $custom_element=0,$custom_jplugin_id=0){

		//$type = $table->field_type;
		if($custom_element===0){
			$custom_element = $table->custom_element;
		}

		if($custom_jplugin_id===0){
			$custom_jplugin_id = $table->custom_jplugin_id;
		}

		$varsToPush = self::getVarsToPush($type);
		$xParams = $table->_xParams;

		if ($type == 'E') {
			JPluginHelper::importPlugin ('vmcustom');
			$dispatcher = JDispatcher::getInstance ();
			//We call here vmplugin->getTablePluginParams which sets the xParam and the varsToPush of the Plugin
			vmdebug('setParameterableByFieldType before trigger plgVmGetTablePluginParams ',$xParams,$varsToPush);
			$retValue = $dispatcher->trigger ('plgVmGetTablePluginParams', array('custom',$custom_element, $custom_jplugin_id, &$xParams, &$varsToPush));
		}
		$xParams = $table->_xParams;
		if(!empty($varsToPush)){
			$table->setParameterable($xParams,$varsToPush,TRUE);
		}

	}

	static function getVarsToPush($type){

		$varsToPush = 0;
		if($type=='A'){
			$varsToPush = array(
				'withParent'        => array(0, 'int'),
				'parentOrderable'   => array(0, 'int')
			);
		} else if($type=='C'){
			$varsToPush = array(
				'withParent'        => array(0, 'int'),
				'parentOrderable'   => array(0, 'int'),
				'selectoptions'		=> array(array(), 'int'),
				'clabels'   => array(array(), 'int'),
				'options'	=> array(array(), 'int')
			);
		}
		return $varsToPush;
	}


	/**
	 * Delete all record ids selected
	 *
	 * @author Max Milbers
	 * @return boolean True is the delete was successful, false otherwise.
	 */
	public function remove($ids) {

		$table = $this->getTable($this->_maintablename);

		$customfields = $this->getTable ('product_customfields');

		foreach($ids as $id) {
			if (!$table->delete((int)$id)) {
				vmError(get_class( $this ).'::remove '.$id.' '.$table->getError());
				return false;
			} else {
				//Delete this customfield also in all product_customfield tables
				if (!$customfields->delete ($id, 'virtuemart_custom_id')) {
					vmError ('Custom delete Productcustomfield delete ' . $customfields->getError ());
					$ok = FALSE;
				}

			}
		}

		return true;
	}

}
// pure php no closing tag
