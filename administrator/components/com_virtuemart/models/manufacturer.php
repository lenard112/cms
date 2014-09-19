<?php
/**
*
* Manufacturer Model
*
* @package	VirtueMart
* @subpackage Manufacturer
* @author Patrick Kohl, Max Milbers
* @link http://www.virtuemart.net
* @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id: manufacturer.php 8036 2014-06-16 07:42:24Z Milbo $
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

if(!class_exists('VmModel'))require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'vmmodel.php');

/**
 * Model class for VirtueMart Manufacturers
 *
 * @package VirtueMart
 * @subpackage Manufacturer
 * @author Max Milbers
 * @todo Replace getOrderUp and getOrderDown with JTable move function. This requires the virtuemart_product_category_xref table to replace the ordering with the ordering column
 */
class VirtueMartModelManufacturer extends VmModel {

	/**
	 * constructs a VmModel
	 * setMainTable defines the maintable of the model
	 * @author Max Milbers
	 */
	function __construct() {
		parent::__construct('virtuemart_manufacturer_id');
		$this->setMainTable('manufacturers');
		$this->addvalidOrderingFieldName(array('m.virtuemart_manufacturer_id','mf_name','mf_desc','mf_category_name','mf_url'));
		$this->removevalidOrderingFieldName('virtuemart_manufacturer_id');
		$this->_selectedOrdering = 'mf_name';
		$this->_selectedOrderingDir = 'ASC';
	}


	/**
	* Load a single manufacturer
	*/
	public function getManufacturer($id = 0) {

		if(!empty($id)) $this->_id = (int)$id;

		if(empty($this->_cache[$this->_id])){
			$this->_cache[$this->_id] = $this->getTable('manufacturers');
			$this->_cache[$this->_id]->load($this->_id);

			$xrefTable = $this->getTable('manufacturer_medias');
			$this->_cache[$this->_id]->virtuemart_media_id = $xrefTable->load($this->_id);
		}

		return $this->_cache[$this->_id];
	}

     /**
	 * Bind the post data to the manufacturer table and save it
     *
     * @author Roland
     * @author Max Milbers
     * @return boolean True is the save was successful, false otherwise.
	 */
	public function store(&$data) {

		// Setup some place holders
		$table = $this->getTable('manufacturers');

		$table->bindChecknStore($data);
		$errors = $table->getErrors();
		foreach($errors as $error){
			vmError($error);
		}

		// Process the images
		$mediaModel = VmModel::getModel('Media');
		$mediaModel->storeMedia($data,'manufacturer');
		$errors = $mediaModel->getErrors();
		foreach($errors as $error){
			vmError($error);
		}
		return $table->virtuemart_manufacturer_id;
	}

    /**
     * Returns a dropdown menu with manufacturers
     * @author Max Milbers
	 * @return object List of manufacturer to build filter select box
	 */
	function getManufacturerDropdown() {
		$db = JFactory::getDBO();
		$query = "SELECT `virtuemart_manufacturer_id` AS `value`, `mf_name` AS text, '' AS disable
						FROM `#__virtuemart_manufacturers_".VmConfig::$vmlang."` ORDER BY `mf_name` ASC";
		$db->setQuery($query);
		$options = $db->loadObjectList();
		array_unshift($options, JHtml::_('select.option',  '0', '- '. vmText::_('COM_VIRTUEMART_SELECT_MANUFACTURER') .' -' ));
		return $options;
	}


    /**
	 * Retireve a list of countries from the database.
	 *
     * @param string $onlyPuiblished True to only retreive the publish countries, false otherwise
     * @param string $noLimit True if no record count limit is used, false otherwise
	 * @return object List of manufacturer objects
	 */
	public function getManufacturers($onlyPublished=false, $noLimit=false, $getMedia=false) {

		$this->_noLimit = $noLimit;
		$mainframe = JFactory::getApplication();
		$option	= 'com_virtuemart';

		$virtuemart_manufacturercategories_id	= $mainframe->getUserStateFromRequest( $option.'virtuemart_manufacturercategories_id', 'virtuemart_manufacturercategories_id', 0, 'int' );
		$search = $mainframe->getUserStateFromRequest( $option.'search', 'search', '', 'string' );

		static $_manufacturers = array();

		$hash = $search.serialize($virtuemart_manufacturercategories_id).VmConfig::$vmlang.(int)$onlyPublished.(int)$this->_noLimit.(int)$getMedia;

		if (array_key_exists ($hash, $_manufacturers)) {
			vmdebug('Return cached getManufacturers');
			return $_manufacturers[$hash];
		}

		$where = array();
		if ($virtuemart_manufacturercategories_id > 0) {
			$where[] .= ' `m`.`virtuemart_manufacturercategories_id` = '. $virtuemart_manufacturercategories_id;
		}

		$joinedTables = ' FROM `#__virtuemart_manufacturers` as m';
		$select = ' `m`.*';
		if ( $search && $search != 'true') {
			$db = JFactory::getDBO();
			$search = '"%' . $db->escape( $search, true ) . '%"' ;
			//$search = $db->Quote($search, false);
			$where[] .= ' LOWER( `mf_name` ) LIKE '.$search;
		}

		$ordering = $this->_getOrdering();
		//if ( $search && $search != 'true' or strpos($ordering,'mf_')!==FALSE or $ordering == 'm.virtuemart_manufacturer_id' ) {
			$select .= ',`#__virtuemart_manufacturers_'.VmConfig::$vmlang.'`.*, mc.`mf_category_name` ';
			$joinedTables .= ' LEFT JOIN `#__virtuemart_manufacturers_'.VmConfig::$vmlang.'` USING (`virtuemart_manufacturer_id`) ';
			$joinedTables .= ' LEFT JOIN `#__virtuemart_manufacturercategories_'.VmConfig::$vmlang.'` AS mc on  mc.`virtuemart_manufacturercategories_id`= `m`.`virtuemart_manufacturercategories_id` ';
		//}

		if ($onlyPublished) {
			$where[] .= ' `m`.`published` = 1';
		}

		$groupBy=' ';
		if($getMedia){
			$select .= ',mmex.virtuemart_media_id ';
			$joinedTables .= 'LEFT JOIN `#__virtuemart_manufacturer_medias` as mmex ON `m`.`virtuemart_manufacturer_id`= mmex.`virtuemart_manufacturer_id` ';
			$groupBy=' GROUP BY `m`.`virtuemart_manufacturer_id` ';

		}
		$whereString = ' ';
		if (count($where) > 0) $whereString = ' WHERE '.implode(' AND ', $where).' ' ;

		$_manufacturers[$hash] = $this->_data = $this->exeSortSearchListQuery(0,$select,$joinedTables,$whereString,$groupBy,$ordering );

		return $_manufacturers[$hash];
	}

}
// pure php no closing tag