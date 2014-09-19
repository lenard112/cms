<?php
defined ('_JEXEC') or die('Direct Access to ' . basename (__FILE__) . ' is not allowed.');

/**
 * General helper class
 *
 * This class provides some shop functions that are used throughout the VirtueMart shop.
 *
 * @package	VirtueMart
 * @subpackage Helpers
 * @author Max Milbers
 * @author Patrick Kohl
 * @copyright Copyright (c) 2004-2008 Soeren Eberhardt-Biermann, 2009 VirtueMart Team. All rights reserved.
 * @version $Id: shopfunctions.php 8171 2014-08-12 12:17:30Z Milbo $
 */
class ShopFunctions {

	/**
	 * Contructor
	 */
	public function __construct () {

	}

	/**
	 * Builds an enlist for information (not chooseable)
	 *
	 * @author Max Milbers
	 *
	 * @param array $idList ids
	 * @param string $table vmTable to use
	 * @param string $name fieldname for the name
	 * @param string $view view for the links
	 * @param bool $tableXref the xref table
	 * @param bool $tableSecondaryKey the fieldname of the xref table
	 * @param int $quantity
	 * @param bool $translate
	 * @return string
	 */
	static public function renderGuiList ($idList, $table, $name, $view, $tableXref = false, $tableSecondaryKey = false, $quantity = 3, $translate = true ) {

		$list = '';
		$ttip = '';
		$link = '';

		if ($view != 'user') {
			$cid = 'cid';
		} else {
			$cid = 'virtuemart_user_id';
		}

		$model = new VmModel();
		$table = $model->getTable($table);

		if(!is_array($idList)){
			$db = JFactory::getDBO ();
			$q = 'SELECT `' . $table->getPKey() . '` FROM `#__virtuemart_' . $db->escape ($tableXref) . '` WHERE ' . $db->escape ($tableSecondaryKey) . ' = "' . (int)$idList . '"';
			$db->setQuery ($q);
			$idList = $db->loadColumn ();
			//vmdebug('renderGuiList',$q,$list);
		}

		$i = 0;

		foreach($idList as $id ){

			$item = $table->load ((int)$id);
			if($translate) $item->$name = vmText::_($item->$name);
			$link = ', '.JHtml::_('link', JRoute::_('index.php?option=com_virtuemart&view='.$view.'&task=edit&'.$cid.'[]='.$id,false), $item->$name);
			if($i<$quantity and $i<=count($idList)){
				$list .= $link;
			} else if ($i==$quantity and $i<count($idList)){
				$list .= ',...';
			}
			$ttip .= ', '.$item->$name;
			if($i>($quantity + 6)) {
				$ttip .= ',...';
				break;
			}
			$i++;
		}

		$list = substr ($list, 2);
		$ttip = substr ($ttip, 2);

		return '<span class="hasTip" title="'.$ttip.'" >' . $list . '</span>';
	}

	/**
	 * Creates a Drop Down list of available Vendors
	 *
	 * @author Max Milbers
	 * @access public
	 * @param int $virtuemart_shoppergroup_id the shopper group to pre-select
	 * @param bool $multiple if the select list should allow multiple selections
	 * @return string HTML select option list
	 */
	static public function renderVendorList ($vendorId, $multiple = FALSE) {

		$db = JFactory::getDBO ();

		if (Vmconfig::get ('multix', 'none') == 'none') {

			$vendorId = 1;

			$q = 'SELECT `vendor_name` FROM `#__virtuemart_vendors` WHERE `virtuemart_vendor_id` = "' . (int)$vendorId . '" ';
			$db->setQuery ($q);
			$vendor = $db->loadResult ();
			$html = '<input type="text" size="14" name="vendor_name" class="inputbox" value="' . $vendor . '" readonly="">';
		} else {

			if (!JFactory::getUser()->authorise('core.admin', 'com_virtuemart')) {
				if (empty($vendorId)) {
					$vendorId = 1;
					//Dont delete this message, we need it later for multivendor
					//vmWarn('renderVendorList $vendorId is empty, please correct your used model to automatically set the virtuemart_vendor_id to the logged Vendor');
				}
				$q = 'SELECT `vendor_name` FROM `#__virtuemart_vendors` WHERE `virtuemart_vendor_id` = "' . (int)$vendorId . '" ';
				$db->setQuery ($q);
				$vendor = $db->loadResult ();
				$html = '<input type="text" size="14" name="vendor_name" class="inputbox" value="' . $vendor . '" readonly="">';
				//			$html .='<input type="hidden" value="'.$vendorId.'" name="virtuemart_vendor_id">';
				return $html;
			} else {

				return self::renderVendorFullVendorList($vendorId);
			}
		}

	}

	static public function renderVendorFullVendorList($vendorId,$multiple=false){

		$db = JFactory::getDBO ();

		$q = 'SELECT `virtuemart_vendor_id`,`vendor_name` FROM #__virtuemart_vendors';
		$db->setQuery ($q);
		$vendors = $db->loadAssocList ();

		$attrs = array();
		$name = 'vendor_name';
		$idA = $id = 'virtuemart_vendor_id';
		$attrs['class'] = 'vm-chzn-select';
		if ($multiple) {
			$attrs['multiple'] = 'multiple';
			$idA .= '[]';
		} else {
			$emptyOption = JHtml::_ ('select.option', '', vmText::_ ('COM_VIRTUEMART_LIST_EMPTY_OPTION'), $id, $name);
			array_unshift ($vendors, $emptyOption);
		}
		$listHTML = JHtml::_ ('select.genericlist', $vendors, $idA, $attrs, $id, $name, $vendorId);
		return $listHTML;
	}

	/**
	 * Creates a Drop Down list of available Shopper Groups
	 *
	 * @author Max Milbers
	 * @access public
	 * @param int $shopperGroupId the shopper group to pre-select
	 * @param bool $multiple if the select list should allow multiple selections
	 * @return string HTML select option list
	 */
	static public function renderShopperGroupList ($shopperGroupId = 0, $multiple = TRUE,$name='virtuemart_shoppergroup_id', $select_attribute='COM_VIRTUEMART_DRDOWN_AVA2ALL' ) {

		$shopperModel = VmModel::getModel ('shoppergroup');
		$shoppergrps = $shopperModel->getShopperGroups (FALSE, TRUE);
		$attrs = '';

		$attrs['class'] = 'vm-chzn-select';
		if ($multiple) {
			$attrs['multiple'] = 'multiple';
			$attrs['data-placeholder'] = vmText::_($select_attribute);
			if($name=='virtuemart_shoppergroup_id'){
				$name.= '[]';
			}
		} else {
			$emptyOption = JHTML::_ ('select.option', '', vmText::_ ($select_attribute), 'virtuemart_shoppergroup_id', 'shopper_group_name');
			array_unshift ($shoppergrps, $emptyOption);
		}

		$listHTML = JHTML::_ ('select.genericlist', $shoppergrps, $name, $attrs, 'virtuemart_shoppergroup_id', 'shopper_group_name', $shopperGroupId,false,true);
		return $listHTML;
	}

	/**
	 * Renders the list of Manufacturers
	 *
	 * @author St. Kraft
	 * Mod. <mediaDESIGN> St.Kraft 2013-02-24 Herstellerrabatt
	 */
	static public function renderManufacturerList ($manufacturerId = 0, $multiple = FALSE, $name = 'virtuemart_manufacturer_id') {

		$manufacturerModel = VmModel::getModel ('manufacturer');
		$manufacturers = $manufacturerModel->getManufacturers (FALSE, TRUE);
		$attrs = array('style'=>"width: 210px");
		//$attrs = 'style="width:210px"';

		if ($multiple) {
			//$attrs .= 'multiple="multiple"';
			$attrs['multiple'] = 'multiple';
			if($name=='virtuemart_manufacturer_id')	$name.= '[]';
		} else {
			$emptyOption = JHtml::_ ('select.option', '', vmText::_ ('COM_VIRTUEMART_LIST_EMPTY_OPTION'), 'virtuemart_manufacturer_id', 'mf_name');
			array_unshift ($manufacturers, $emptyOption);
		}
		//vmdebug('renderManufacturerList',$name,$manufacturers);
		$listHTML = JHtml::_ ('select.genericlist', $manufacturers, $name, $attrs, 'virtuemart_manufacturer_id', 'mf_name', $manufacturerId);
		return $listHTML;
	}


	/**
	 * Renders the list for the tax rules
	 *
	 * @author Max Milbers
	 */
	static function renderTaxList ($selected, $name = 'product_tax_id', $class = '') {

		if (!class_exists ('VirtueMartModelCalc')) {
					require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'calc.php');
				}
		$taxes = VirtueMartModelCalc::getTaxes ();

		$taxrates = array();
		$taxrates[] = JHtml::_ ('select.option', '-1', vmText::_ ('COM_VIRTUEMART_PRODUCT_TAX_NONE'), $name);
		$taxrates[] = JHtml::_ ('select.option', '0', vmText::_ ('COM_VIRTUEMART_PRODUCT_TAX_NO_SPECIAL'), $name);
		foreach ($taxes as $tax) {
			$taxrates[] = JHtml::_ ('select.option', $tax->virtuemart_calc_id, $tax->calc_name, $name);
		}
		$listHTML = JHtml::_ ('Select.genericlist', $taxrates, $name, $class, $name, 'text', $selected);
		return $listHTML;
	}

	/**
	 * Creates the chooseable template list
	 *
	 * @author Max Milbers, impleri
	 *
	 * @param string defaultText Text for the empty option
	 * @param boolean defaultOption you can supress the empty otion setting this to false
	 * return array of Template objects
	 */
	static public function renderTemplateList ($defaultText = 0, $defaultOption = TRUE) {

		if (empty($defaultText)) {
			$defaultText = vmText::_ ('COM_VIRTUEMART_TEMPLATE_DEFAULT');
		}

		$defaulttemplate = array();
		if ($defaultOption) {
			$defaulttemplate[0] = new stdClass;
			$defaulttemplate[0]->name = $defaultText;
			$defaulttemplate[0]->directory = 0;
			$defaulttemplate[0]->value = 'default';
		}

		$q = 'SELECT * FROM `#__template_styles` WHERE `client_id`="0"';
		$db = JFactory::getDbo();
		$db->setQuery($q);

		$jtemplates = $db->loadObjectList();

		foreach ($jtemplates as $key => $template) {
			$template->name = $template->title;
			$template->value = $template->id;
			$template->directory = $template->template;
		}

		return array_merge ($defaulttemplate, $jtemplates);
	}

	static function renderOrderingList($table,$fieldname,$selected,$orderingField = 'ordering'){
		//'order_status_name','orderstates',$orderStatus->virtuemart_orderstate_id
// Ordering dropdown
		$qry = 'SELECT ordering AS value, '.$fieldname.' AS text'
			. ' FROM #__virtuemart_'.$table
			. ' ORDER BY ordering';
		$db = JFactory::getDbo();
		$db->setQuery($qry);
		$orderStatusList = $db -> loadAssocList();
		foreach($orderStatusList as &$text){
			$text = $text['value'].' '.vmText::_($text['text']);
		}
		return JHtml::_('select.genericlist',$orderStatusList,'ordering','','value','text',$selected);
	}

	/**
	 * Returns all the weight unit
	 *
	 * @author Valérie Isaksen
	 */
	static function getWeightUnit () {

		static $weigth_unit;
		if ($weigth_unit) {
			return $weigth_unit;
		}
		return $weigth_unit = array(
			'KG' => vmText::_ ('COM_VIRTUEMART_UNIT_NAME_KG')
		, 'G'   => vmText::_ ('COM_VIRTUEMART_UNIT_NAME_G')
		, 'MG'   => vmText::_ ('COM_VIRTUEMART_UNIT_NAME_MG')
		, 'LB'   => vmText::_ ('COM_VIRTUEMART_UNIT_NAME_LB')
		, 'OZ'   => vmText::_ ('COM_VIRTUEMART_UNIT_NAME_ONCE')
		);
	}

	/**
	 * Renders the string for the
	 *
	 * @author Valérie Isaksen
	 */
	static function renderWeightUnit ($name) {

		$weigth_unit = self::getWeightUnit ();
		if (isset($weigth_unit[$name])) {
					return $weigth_unit[$name];
		} else {
			return '';
		}
	}

	/**
	 * Renders the list for the Weight Unit
	 *
	 * @author Valérie Isaksen
	 */
	static function renderWeightUnitList ($name, $selected) {

		$weight_unit_default = self::getWeightUnit ();
		foreach ($weight_unit_default as  $key => $value) {
			$wu_list[] = JHtml::_ ('select.option', $key, $value, $name);
		}
		$listHTML = JHtml::_ ('Select.genericlist', $wu_list, $name, '', $name, 'text', $selected);
		return $listHTML;

	}

	static function renderUnitIsoList($name, $selected){

		$weight_unit_default = array(
			'KG' => vmText::_ ('COM_VIRTUEMART_UNIT_SYMBOL_KG')
		, '100G' => vmText::_ ('COM_VIRTUEMART_UNIT_SYMBOL_100G')
		, 'M'   => vmText::_ ('COM_VIRTUEMART_UNIT_SYMBOL_M')
		, 'SM'   => vmText::_ ('COM_VIRTUEMART_UNIT_SYMBOL_SM')
		, 'CUBM'   => vmText::_ ('COM_VIRTUEMART_UNIT_SYMBOL_CUBM')
		, 'L'   => vmText::_ ('COM_VIRTUEMART_UNIT_SYMBOL_L')
		, '100ML'   => vmText::_ ('COM_VIRTUEMART_UNIT_SYMBOL_100ML')
		);
		foreach ($weight_unit_default as  $key => $value) {
			$wu_list[] = JHtml::_ ('select.option', $key, $value, $name);
		}
		$listHTML = JHtml::_ ('Select.genericlist', $wu_list, $name, '', $name, 'text', $selected);
		return $listHTML;
	}

	/**
	 * typo problem with the function name. We must keep the other one for compatibility purposes
	 * @param $value
	 * @param $from
	 * @param $to
	 */
	static function convertWeigthUnit ($value, $from, $to) {
		return self::convertWeightUnit ($value, $from, $to);
	}
	/**
	 * Convert Weight Unit
	 *
	 * @author Valérie Isaksen
	 */
	static function convertWeightUnit ($value, $from, $to) {

		$from = strtoupper($from);
		$to = strtoupper($to);
		$value = str_replace (',', '.', $value);
		if ($from === $to) {
			return $value;
		}

		$g = (float)$value;

		switch ($from) {
			case 'KG':
				$g = (float)(1000 * $value);
			break;
			case 'MG':
				$g = (float)($value / 1000);
			break;
			case 'LB':
				$g = (float)(453.59237 * $value);
			break;
			case 'OZ':
				$g = (float)(28.3495 * $value);
			break;
		}
		switch ($to) {
			case 'KG' :
				$value = (float)($g / 1000);
				break;
			case 'G' :
				$value = $g;
				break;
			case 'MG' :
				$value = (float)(1000 * $g);
				break;
			case 'LB' :
				$value = (float)($g / 453.59237);
				break;
			case 'OZ' :
				$value = (float)($g / 28.3495);
				break;
		}
		return $value;
	}

	/**
	 * Convert Metric Unit
	 *
	 * @author Florian Voutzinos
	 */
	static function convertDimensionUnit ($value, $from, $to) {

		$from = strtoupper($from);
		$to = strtoupper($to);
		$value = (float)str_replace (',', '.', $value);
		if ($from === $to) {
			return $value;
		}
		$meter = (float)$value;

		// transform $value in meters
		switch ($from) {
			case 'CM':
				$meter = (float)(0.01 * $value);
				break;
			case 'MM':
				$meter = (float)(0.001 * $value);
				break;
			case 'YD' :
				$meter =(float) (0.9144 * $value);
				break;
			case 'FT' :
				$meter = (float)(0.3048 * $value);
				break;
			case 'IN' :
				$meter = (float)(0.0254 * $value);
				break;
		}
		switch ($to) {
			case 'M' :
				$value = $meter;
				break;
			case 'CM':
				$value = (float)($meter / 0.01);
				break;
			case 'MM':
				$value = (float)($meter / 0.001);
				break;
			case 'YD' :
				$value =(float) ($meter / 0.9144);
				break;
			case 'FT' :
				$value = (float)($meter / 0.3048);
				break;
			case 'IN' :
				$value = (float)($meter / 0.0254);
				break;
		}
		return $value;
	}

	/**
	 * Renders the list for the Length, Width, Height Unit
	 *
	 * @author Valérie Isaksen
	 */
	static function renderLWHUnitList ($name, $selected) {

		if (!class_exists ('VmHTML')) {
			require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'html.php');
		}

		$lwh_unit_default = array('M' => vmText::_ ('COM_VIRTUEMART_UNIT_NAME_M')
		, 'CM'                        => vmText::_ ('COM_VIRTUEMART_UNIT_NAME_CM')
		, 'MM'                        => vmText::_ ('COM_VIRTUEMART_UNIT_NAME_MM')
		, 'YD'                        => vmText::_ ('COM_VIRTUEMART_UNIT_NAME_YARD')
		, 'FT'                        => vmText::_ ('COM_VIRTUEMART_UNIT_NAME_FOOT')
		, 'IN'                        => vmText::_ ('COM_VIRTUEMART_UNIT_NAME_INCH')
		);
		foreach ($lwh_unit_default as  $key => $value) {
			$lu_list[] = JHtml::_ ('select.option', $key, $value, $name);
		}
		$listHTML = JHtml::_ ('Select.genericlist', $lu_list, $name, '', $name, 'text', $selected);
		return $listHTML;

	}


	/**
	 * Writes a line  for the price configuration
	 *
	 * @author Max Milberes
	 * @param string $name
	 * @param string $langkey
	 */
	static function writePriceConfigLine ($array, $name, $langkey) {

		if (!class_exists ('VmHTML')) {
			require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'html.php');
		}
		if(is_object($array)) $array = get_object_vars($array);
		$html =
			'<tr>
				<td class="key">
					<span class="editlinktip hasTip" title="' . vmText::_ ($langkey . '_EXPLAIN') . '">
						<label>' . vmText::_ ($langkey) .
						'</label>
					</span>
				</td>

				<td>' .
				VmHTML::checkbox ($name, $array[$name]) . '
				</td>
				<td align="center">' .
				VmHTML::checkbox ($name . 'Text', $array[$name . 'Text']) . '
				</td>
				<td align="center">
				<input type="text" value="' . $array[$name . 'Rounding'] . '" class="inputbox" size="4" name="' . $name . 'Rounding">
				</td>
			</tr>';
		return $html;
	}

	/**
	 * This generates the list when the user have different ST addresses saved
	 *
	 * @author Oscar van Eijk
	 */
	static function generateStAddressList ($view, $userModel, $task) {

		// Shipment address(es)
		$_addressList = $userModel->getUserAddressList ($userModel->getId (), 'ST');
		if (count ($_addressList) == 1 && empty($_addressList[0]->address_type_name)) {
			return vmText::_ ('COM_VIRTUEMART_USER_NOSHIPPINGADDR');
		} else {
			$_shipTo = array();
			$useXHTTML = empty($view->useXHTML) ? false : $view->useXHTML;
			$useSSL = empty($view->useSSL) ? FALSE : $view->useSSL;

			for ($_i = 0; $_i < count ($_addressList); $_i++) {
				if (empty($_addressList[$_i]->virtuemart_user_id)) {
					$_addressList[$_i]->virtuemart_user_id = JFactory::getUser ()->id;
				}
				if (empty($_addressList[$_i]->virtuemart_userinfo_id)) {
					$_addressList[$_i]->virtuemart_userinfo_id = 0;
				}
				if (empty($_addressList[$_i]->address_type_name)) {
					$_addressList[$_i]->address_type_name = 0;
				}

				$_shipTo[] = '<li>' . '<a href="index.php'
					. '?option=com_virtuemart'
					. '&view=user'
					. '&task=' . $task
					. '&addrtype=ST'
					. '&virtuemart_user_id[]=' . $_addressList[$_i]->virtuemart_user_id
					. '&virtuemart_userinfo_id=' . $_addressList[$_i]->virtuemart_userinfo_id
					. '">' . $_addressList[$_i]->address_type_name . '</a> ' ;

				$_shipTo[] = '&nbsp;&nbsp;<a href="'.JRoute::_ ('index.php?option=com_virtuemart&view=user&task=removeAddressST&virtuemart_user_id[]=' . $_addressList[$_i]->virtuemart_user_id . '&virtuemart_userinfo_id=' . $_addressList[$_i]->virtuemart_userinfo_id, $useXHTTML, $useSSL ). '" class="icon_delete">'.vmText::_('COM_VIRTUEMART_USER_DELETE_ST').'</a></li>';

			}


			$addLink = '<a href="' . JRoute::_ ('index.php?option=com_virtuemart&view=user&task=' . $task . '&new=1&addrtype=ST&virtuemart_user_id[]=' . $userModel->getId (), $useXHTTML, $useSSL) . '"><span class="vmicon vmicon-16-editadd"></span> ';
			$addLink .= vmText::_ ('COM_VIRTUEMART_USER_FORM_ADD_SHIPTO_LBL') . ' </a>';

			return $addLink . '<ul>' . join ('', $_shipTo) . '</ul>';
		}
	}

	/**
	 * used mostly in the email, to display the vendor address
	 * Attention, this function will be removed from any view.html.php
	 *
	 * @static
	 * @param        $vendorId
	 * @param string $lineSeparator
	 * @param array  $skips
	 * @return string
	 */
	static public function renderVendorAddress ($vendorId,$lineSeparator="<br />", $skips = array('name','username','email','agreed')) {

		$vendorModel = VmModel::getModel('vendor');
		$vendorFields = $vendorModel->getVendorAddressFields($vendorId);

		$vendorAddress = '';
		foreach ($vendorFields['fields'] as $field) {
			if(in_array($field['name'],$skips)) continue;
			if (!empty($field['value'])) {
				$vendorAddress .= $field['value'];
				if ($field['name'] != 'title' and $field['name'] != 'first_name' and $field['name'] != 'middle_name' and $field['name'] != 'zip') {
					$vendorAddress .= $lineSeparator;
				} else {
					$vendorAddress .= ' ';
				}
			}
		}
		return $vendorAddress;
	}



	public static $counter = 0;
	public static $categoryTree = 0;

	static public function categoryListTree ($selectedCategories = array(), $cid = 0, $level = 0, $disabledFields = array()) {

		if (empty(self::$categoryTree)) {
// 			vmTime('Start with categoryListTree');
			$cache = JFactory::getCache ('com_virtuemart_cats');
			$cached = $cache->getCaching();
			$cache->setCaching (1);
			self::$categoryTree = $cache->call (array('ShopFunctions', 'categoryListTreeLoop'), $selectedCategories, $cid, $level, $disabledFields);
			$cache->setCaching ($cached);
			// self::$categoryTree = self::categoryListTreeLoop($selectedCategories, $cid, $level, $disabledFields);
// 			vmTime('end loop categoryListTree '.self::$counter);
		}

		return self::$categoryTree;
	}

	/**
	 * Get feed
	 * @author valerie isaksen
	 * @param $rssUrl
	 * @param $max
	 * @return mixed
	 */
	static public function getCPsRssFeed($rssUrl,$max) {

		$cache_time=86400*2; // 2days
		$cache = JFactory::getCache ('com_virtuemart_rss');
		$cached = $cache->getCaching();
		$cache->setLifeTime($cache_time);
		$cache->setCaching (1);
		$feeds = $cache->call (array('ShopFunctions', 'getRssFeed'), $rssUrl, $max);
		$cache->setCaching ($cached);
		return $feeds;
	}

	/**
	 * @author Valerie Isaksen
	 * Returns the RSS feed from Extensions.virtuemart.net
	 * @return mixed
	 */
	public static $extFeeds = 0;
	static public function getExtensionsRssFeed() {
		if (empty(self::$extFeeds)) {
			self::$extFeeds =  ShopFunctions::getCPsRssFeed("http://extensions.virtuemart.net/?format=feed&type=rss", 15);
		}
		return self::$extFeeds;
	}
	/**
	 * @author Valerie Isaksen
	 * Returns the RSS feed from virtuemart.net
	 * @return mixed
	 */
	public static $vmFeeds = 0;
	static public function getVirtueMartRssFeed() {
 		if (empty(self::$vmFeeds)) {
			self::$vmFeeds =  ShopFunctions::getCPsRssFeed("http://virtuemart.net/news/list-all-news?format=feed&type=rss", 5);
		}
		return self::$vmFeeds;
	}
	static public function getRssFeed ($rssURL,$max) {
		// prevent Strict Standards errors in simplepie
		error_reporting(E_ALL ^ E_STRICT);

		$rssFeed = JFactory::getFeedParser($rssURL);
		if(empty($rssFeed) or !is_object($rssFeed)) return false;
		$count = $rssFeed->get_item_quantity();
		$limit=min($max,$count);
			for ($i = 0; $i < $limit; $i++) {
				$feed = new StdClass();
				$item = $rssFeed->get_item($i);
				$feed->link = $item->get_link();
				$feed->title = $item->get_title();
				$feed->description = $item->get_description();
				$feeds[] = $feed;
			}

		return $feeds;
	}
	/**
	 * Creates structured option fields for all categories
	 *
	 * @todo: Connect to vendor data
	 * @author Max Milbers, jseros
	 * @param array 	$selectedCategories All category IDs that will be pre-selected
	 * @param int 		$cid 		Internally used for recursion
	 * @param int 		$level 		Internally used for recursion
	 * @return string 	$category_tree HTML: Category tree list
	 */
	static public function categoryListTreeLoop ($selectedCategories = array(), $cid = 0, $level = 0, $disabledFields = array()) {

		self::$counter++;

		static $categoryTree = '';

		$virtuemart_vendor_id = 1;

// 		vmSetStartTime('getCategories');
		$categoryModel = VmModel::getModel ('category');
		$level++;

		$categoryModel->_noLimit = TRUE;
		$app = JFactory::getApplication ();
		$records = $categoryModel->getCategories ($app->isSite (), $cid);
// 		vmTime('getCategories','getCategories');
		$selected = "";
		if (!empty($records)) {
			foreach ($records as $key => $category) {

				$childId = $category->category_child_id;

				if ($childId != $cid) {
					if (in_array ($childId, $selectedCategories)) {
						$selected = 'selected=\"selected\"';
					} else {
						$selected = '';
					}

					$disabled = '';
					if (in_array ($childId, $disabledFields)) {
						$disabled = 'disabled="disabled"';
					}

					if ($disabled != '' && stristr ($_SERVER['HTTP_USER_AGENT'], 'msie')) {
						//IE7 suffers from a bug, which makes disabled option fields selectable
					} else {
						$categoryTree .= '<option ' . $selected . ' ' . $disabled . ' value="' . $childId . '">';
						$categoryTree .= str_repeat (' - ', ($level - 1));

						$categoryTree .= $category->category_name . '</option>';
					}
				}

				if ($categoryModel->hasChildren ($childId)) {
					self::categoryListTreeLoop ($selectedCategories, $childId, $level, $disabledFields);
				}

			}
		}

		return $categoryTree;
	}


	/**
	 * Return the countryname or code of a given countryID
	 *
	 * @author Oscar van Eijk
	 * @access public
	 * @param int $id Country ID
	 * @param char $fld Field to return: country_name (default), country_2_code or country_3_code.
	 * @return string Country name or code
	 */
	static public function getCountryByID ($id, $fld = 'country_name') {

		if (empty($id)) {
			return '';
		}

		$id = (int)$id;
		$db = JFactory::getDBO ();

		$q = 'SELECT `' . $db->escape ($fld) . '` AS fld FROM `#__virtuemart_countries` WHERE virtuemart_country_id = ' . (int)$id;
		$db->setQuery ($q);
		return $db->loadResult ();
	}

	/**
	 * Return the virtuemart_country_id of a given country name
	 *
	 * @author Oscar van Eijk
	 * @author Max Milbers
	 * @access public
	 * @param string $name Country name (can be country_name or country_3_code  or country_2_code )
	 * @return int virtuemart_country_id
	 */
	static public function getCountryIDByName ($name) {

		if (empty($name)) {
			return 0;
		}
		$db = JFactory::getDBO ();

		if (strlen ($name) === 2) {
			$fieldname = 'country_2_code';
		} else {
			if (strlen ($name) === 3) {
				$fieldname = 'country_3_code';
			} else {
				$fieldname = 'country_name';
			}
		}
		$q = 'SELECT `virtuemart_country_id` FROM `#__virtuemart_countries` WHERE `' . $fieldname . '` = "' . $db->escape ($name) . '"';
		$db->setQuery ($q);
		$r = $db->loadResult ();
		return $r;
	}

	/**
	 * Return the statename or code of a given virtuemart_state_id
	 *
	 * @author Oscar van Eijk
	 * @access public
	 * @param int $id State ID
	 * @param char $fld Field to return: state_name (default), state_2_code or state_3_code.
	 * @return string state name or code
	 */
	static public function getStateByID ($id, $fld = 'state_name') {

		if (empty($id)) {
			return '';
		}
		$db = JFactory::getDBO ();
		$q = 'SELECT ' . $db->escape ($fld) . ' AS fld FROM `#__virtuemart_states` WHERE virtuemart_state_id = "' . (int)$id . '"';
		$db->setQuery ($q);
		$r = $db->loadObject ();
		return $r->fld;
	}

	/**
	 * Return the stateID of a given state name
	 *
	 * @author Max Milbers
	 * @access public
	 * @param string $name Country name
	 * @return int virtuemart_state_id
	 */
	static public function getStateIDByName ($name) {

		if (empty($name)) {
			return 0;
		}
		$db = JFactory::getDBO ();
		if (strlen ($name) === 2) {
			$fieldname = 'state_2_code';
		} else {
			if (strlen ($name) === 3) {
				$fieldname = 'state_3_code';
			} else {
				$fieldname = 'state_name';
			}
		}
		$q = 'SELECT `virtuemart_state_id` FROM `#__virtuemart_states` WHERE `' . $fieldname . '` = "' . $db->escape ($name) . '"';
		$db->setQuery ($q);
		$r = $db->loadResult ();
		return $r;
	}

	/*
	 * Returns the associative array for a given virtuemart_calc_id
	*
	* @author Valérie Isaksen
	* @access public
	* @param int $id virtuemart_calc_id
	* @return array Result row
	*/
	static public function getTaxByID ($id) {

		if (empty($id)) {
			return '';
		}

		$id = (int)$id;
		$db = JFactory::getDBO ();
		$q = 'SELECT  *   FROM `#__virtuemart_calcs` WHERE virtuemart_calc_id = ' . (int)$id;
		$db->setQuery ($q);
		return $db->loadAssoc ();

	}

	/**
	 * Return any field  from table '#__virtuemart_currencies'
	 *
	 * @author Valérie Isaksen
	 * @access public
	 * @param int $id Currency ID
	 * @param char $fld Field from table '#__virtuemart_currencies' to return: currency_name (default), currency_code_2, currency_code_3 etc.
	 * @return string Currency name or code
	 */
	static public function getCurrencyByID ($id, $fld = 'currency_name') {

		if (empty($id)) {
			return '';
		}
		static $currencyNameById = array();
		if(!isset($currencyNameById[$id][$fld])){
			$id = (int)$id;
			$db = JFactory::getDBO ();

			$q = 'SELECT ' . $db->escape ($fld) . ' AS fld FROM `#__virtuemart_currencies` WHERE virtuemart_currency_id = ' . (int)$id;
			$db->setQuery ($q);
			$currencyNameById[$id][$fld] = $db->loadResult ();
		}

		return $currencyNameById[$id][$fld];
	}

	/**
	 * Return the currencyID of a given Currency name
	 * This function becomes dangerous if there is a currency name with 3 letters
	 * @author Valerie Isaksen, Max Milbers
	 * @access public
	 * @param string $name Currency name
	 * @return int virtuemart_currency_id
	 */
	static public function getCurrencyIDByName ($name) {

		if (empty($name)) {
			return 0;
		}
		static $currencyIdByName = array();
		if(!isset($currencyIdByName[$name])){
			$db = JFactory::getDBO ();
			if (strlen ($name) === 2) {
				$fieldname = 'currency_code_2';
			} else {
				if (strlen ($name) === 3) {
					$fieldname = 'currency_code_3';
				} else {
					$fieldname = 'currency_name';
				}
			}
			$q = 'SELECT `virtuemart_currency_id` FROM `#__virtuemart_currencies` WHERE `' . $fieldname . '` = "' . ($name) . '"';
			$db->setQuery ($q);
			$currencyIdByName[$name] = $db->loadResult ();
		}

		return $currencyIdByName[$name];
	}

	/**
	 * Print a select-list with enumerated categories
	 *
	 * @author jseros
	 *
	 * @param boolean $onlyPublished Show only published categories?
	 * @param boolean $withParentId Keep in mind $parentId param?
	 * @param integer $parentId Show only its childs
	 * @param string $attribs HTML attributes for the list
	 * @return string <Select /> HTML
	 */
	static public function getEnumeratedCategories ($onlyPublished = TRUE, $withParentId = FALSE, $parentId = 0, $name = '', $attribs = '', $key = '', $text = '', $selected = NULL) {

		$categoryModel = VmModel::getModel ('category');

		$categories = $categoryModel->getCategories ($onlyPublished, $parentId);

		foreach ($categories as $index => $cat) {
			$cat->category_name = $cat->ordering . '. ' . $cat->category_name;
			$categories[$index] = $cat;
		}
		return JHtml::_ ('Select.genericlist', $categories, $name, $attribs, $key, $text, $selected, $name);
	}

	/**
	 * Return the order status name for a given code
	 *
	 * @author Oscar van Eijk
	 * @access public
	 *
	 * @param char $_code Order status code
	 * @return string The name of the order status
	 */
	static public function getOrderStatusName ($_code) {

		$db = JFactory::getDBO ();

		$_q = 'SELECT `order_status_name` FROM `#__virtuemart_orderstates` WHERE `order_status_code` = "' . $db->escape ($_code) . '"';
		$db->setQuery ($_q);
		$_r = $db->loadObject ();
		if (empty($_r->order_status_name)) {
			vmError ('getOrderStatusName: couldnt find order_status_name for ' . $_code);
			return 'current order status broken';
		} else {
			return vmText::_($_r->order_status_name);
		}

	}

	/*
	 * @author Valerie
	 */
	static function InvoiceNumberReserved ($invoice_number) {
		if(!class_exists('ShopFunctionsF')) require(JPATH_VM_SITE.DS.'helpers'.DS.'shopfunctionsf.php');
		return shopFunctionsF::InvoiceNumberReserved($invoice_number);
	}

	/**
	 * Creates an drop-down list with numbers from 1 to 31 or of the selected range,
	 * dont use within virtuemart. It is just meant for paymentmethods
	 *
	 * @param string $list_name The name of the select element
	 * @param string $selected_item The pre-selected value
	 */
	static function listDays ($list_name, $selected = FALSE, $start = NULL, $end = NULL) {

		$options = array();
		if (!$selected) {
			$selected = date ('d');
		}
		$start = $start ? $start : 1;
		$end = $end ? $end : $start + 30;
		$options[] = JHtml::_ ('select.option', 0, vmText::_ ('DAY'));
		for ($i = $start; $i <= $end; $i++) {
			$options[] = JHtml::_ ('select.option', $i, $i);
		}
		return JHtml::_ ('select.genericlist', $options, $list_name, '', 'value', 'text', $selected);
	}


	/**
	 * Creates a Drop-Down List for the 12 months in a year
	 *
	 * @param string $list_name The name for the select element
	 * @param string $selected_item The pre-selected value
	 *
	 */
	static function listMonths ($list_name, $selected = FALSE, $attr = '', $format='F') {

		$options = array();
		if (!$selected) {
			$selected = date ('m');
		}
		$months=array(
			"01"=>vmText::_ ('JANUARY'),
			"02"=>vmText::_ ('FEBRUARY'),
			"03"=>vmText::_ ('MARCH'),
			"04"=>vmText::_ ('APRIL'),
			"05"=>vmText::_ ('MAY'),
			"06"=>vmText::_ ('JUNE'),
			"07"=>vmText::_ ('JULY'),
			"08"=>vmText::_ ('AUGUST'),
			"09"=>vmText::_ ('SEPTEMBER'),
			"10"=>vmText::_ ('OCTOBER'),
			"11"=>vmText::_ ('NOVEMBER'),
			"12"=>vmText::_ ('DECEMBER')
		);

		$options[] = JHTML::_ ('select.option', 0, vmText::_ ('MONTH'));
		foreach($months as  $key => $value) {
			if ($format=='F') {
				$text=$value;
			} else {
				$text=$key;
			}
			$options[] = JHTML::_ ('select.option',$key, $text);
		}

		return JHTML::_ ('select.genericlist', $options, $list_name, $attr, 'value', 'text', $selected);

	}

	/**
	 * Creates an drop-down list with years of the selected range or of the next 7 years
	 *
	 * @param string $list_name The name of the select element
	 * @param string $selected_item The pre-selected value
	 */
	static function listYears ($list_name, $selected = FALSE, $start = NULL, $end = NULL, $attr = '', $format='Y') {

		$options = array();
		if (!$selected) {
			$selected = date ($format);
		}
		$start = $start ? $start : date ($format);
		$end = $end ? $end : $start + 11;
		$options[] = JHtml::_ ('select.option', 0, vmText::_ ('YEAR'));
		for ($i = $start; $i <= $end; $i++) {
			$options[] = JHtml::_ ('select.option', $i, $i);
		}
		return JHtml::_ ('select.genericlist', $options, $list_name, $attr, 'value', 'text', $selected);
	}


	/**
	 * Return $str with all but $display_length at the end as asterisks.
	 *
	 * @author gday
	 *
	 * @access public
	 * @param string $str The string to mask
	 * @param int $display_length The length at the end of the string that is NOT masked
	 * @param boolean $reversed When true, masks the end. Masks from the beginning at default
	 * @return string The string masked by asteriks
	 */
	public function asteriskPad ($str, $display_length, $reversed = FALSE) {

		$total_length = strlen ($str);

		if ($total_length > $display_length) {
			if (!$reversed) {
				for ($i = 0; $i < $total_length - $display_length; $i++) {
					$str[$i] = "*";
				}
			} else {
				for ($i = $total_length - 1; $i >= $total_length - $display_length; $i--) {
					$str[$i] = "*";
				}
			}
		}

		return ($str);
	}

	static function getValidProductFilterArray () {

		static $filterArray;

		if (!isset($filterArray)) {

			$filterArray = array('product_name', '`p`.created_on', '`p`.product_sku',
			'product_s_desc', 'product_desc','`l`.slug',
			'category_name', 'category_description', 'mf_name',
			'product_price', '`p`.product_special', '`p`.product_sales', '`p`.product_availability', '`p`.product_available_date',
			'`p`.product_height', '`p`.product_width', '`p`.product_length', '`p`.product_lwh_uom',
			'`p`.product_weight', '`p`.product_weight_uom', '`p`.product_in_stock', '`p`.low_stock_notification',
			'`p`.modified_on',
			'`p`.product_unit', '`p`.product_packaging', '`p`.virtuemart_product_id', 'pc.ordering');

			//other possible fields
			//'p.intnotes',		this is maybe interesting, but then only for admins or special shoppergroups

			// this fields leads to trouble, because we have this fields in product, category and manufacturer,
			// they are anyway making not a lot sense for orderby or search.
			//'l.metadesc', 'l.metakey', 'l.metarobot', 'l.metaauthor'
		}

		return $filterArray;
	}

	/**
	 * Returns developer information for a plugin
	 * Returns a 2 link with background image, should look like a button to open contact page or manual
	 *
	 * @static
	 * @param $title string Title of the plugin
	 * @param $intro string Intro text
	 * @param $logolink url Url to logo images, use here the path and then as image names contact.png and manual.png
	 * @param $developer string Name of the developer/company
	 * @param $contactlink url Url to the contact form of the developer for support
	 * @param $manlink url URL to the manual for this specific plugin
	 * @return string
	 */
	static function display3rdInfo($title,$intro,$developer,$logolink,$contactlink,$manlink,$width='96px',$height='66px',$linesHeight='33px'){

		$html = $intro;

		$html .= self::displayLinkButton(vmText::sprintf('COM_VIRTUEMART_THRD_PARTY_CONTACT',$developer),$contactlink, $logolink.'/contact.png',$width,$height,$linesHeight);
		$html .='<br />';
		$html .= self::displayLinkButton(vmText::sprintf('COM_VIRTUEMART_THRD_PARTY_MANUAL',$title),$manlink, $logolink.'/manual.png',$width,$height,$linesHeight);

		return $html;
	}


	static function displayLinkButton($title, $link, $bgrndImage,$width,$height,$linesHeight,$additionalStyles=''){

		//$lineHeight = ((int)$height)/$lines;
		//vmdebug('displayLinkButton '.$height.' '.$lineHeight);
		$html = '<div style="line-height:'.$linesHeight.';background-image:url('.$bgrndImage.');width:'.$width.';height:'.$height.';'.$additionalStyles.'">'
				.'<a  title="'.$title.'" href="'.$link.'" target="_blank" >'.$title .'</a></div>';

		return $html;
	}

	static $tested = False;
	static function checkSafePath($safePath=0){


		if($safePath==0) {
			$safePath = VmConfig::get('forSale_path',0);
			if(self::$tested) return $safePath;
		}

		$warn = FALSE;
		$uri = JFactory::getURI();
		$configlink = $uri->root() . 'administrator/index.php?option=com_virtuemart&view=config';
		VmConfig::loadJLang('com_virtuemart');
		if(empty($safePath)){
			$warn = 'COM_VIRTUEMART_WARN_NO_SAFE_PATH_SET';
		} else {
			//jimport('joomla.filesystem.folder');
			if(!class_exists('JFolder')) require_once(JPATH_VM_LIBRARIES.DS.'joomla'.DS.'filesystem'.DS.'folder.php');
			$exists = JFolder::exists($safePath);
			if(!$exists){
				$warn = 'COM_VIRTUEMART_WARN_SAFE_PATH_WRONG';
			} else{
				if(!is_writable( $safePath )){
					VmConfig::loadJLang('com_virtuemart_config');
					VmError('COM_VIRTUEMART_WARN_SAFE_PATH_NOT_WRITEABLE',vmText::_('COM_VIRTUEMART_ADMIN_CFG_MEDIA_FORSALE_PATH'),$safePath,$configlink);
				} else {
					if(!is_writable(self::getInvoicePath($safePath) )){
						VmConfig::loadJLang('com_virtuemart_config');
						VmError('COM_VIRTUEMART_WARN_SAFE_PATH_INV_NOT_WRITEABLE',vmText::_('COM_VIRTUEMART_ADMIN_CFG_MEDIA_FORSALE_PATH'),$safePath,$configlink);
					}
				}
			}
		}

		if($warn){
			$suggestedPath=shopFunctions::getSuggestedSafePath();
			VmConfig::loadJLang('com_virtuemart_config');
			VmWarn($warn,vmText::_('COM_VIRTUEMART_ADMIN_CFG_MEDIA_FORSALE_PATH'),$suggestedPath,$configlink);
			return FALSE;
		}

		return $safePath;
	}
	/*
	 * get The invoice Folder Name
	 * @return the invoice folder name
	 */
	static function getInvoiceFolderName() {
		if(!class_exists('ShopFunctionsF')) require(JPATH_VM_SITE.DS.'helpers'.DS.'shopfunctionsf.php');
		return ShopFunctionsF::getInvoiceFolderName();
	}
	/*
	 * get The invoice path
	 * @param $safePath the safepath from the config
	 * @return the path where the invoice are stored
	 */
	static function getInvoicePath($safePath) {
		return  $safePath.self::getInvoiceFolderName() ;
	}

	/*
	 * Returns the suggested safe Path, used to store the invoices
	 * @static
	 * @return string: suggested safe path
	 */
	static public function getSuggestedSafePath() {
		$lastIndex= strrpos(JPATH_ROOT,DS);
		return substr(JPATH_ROOT,0,$lastIndex).DS.'vmfiles';
	}
	/*
	 * @author Valerie Isaksen
	 */
	static public function renderProductShopperList ($productShoppers) {

		$html = '';
		$i=0;
		if(empty($productShoppers)) return '';
		foreach ($productShoppers as $email => $productShopper) {
			$html .= '<tr  class="customer row'.$i.'" data-cid="' . $productShopper['email'] . '">
			<td rowspan ="'.$productShopper['nb_orders'] .'">' . $productShopper['name'] . '</td>
			<td rowspan ="'.$productShopper['nb_orders'] .'><a class="mailto" href="' . $productShopper['mail_to'] . '"><span class="mail">' . $productShopper['email'] . '</span></a></td>
			<td rowspan ="'.$productShopper['nb_orders'] .'class="shopper_phone">' . $productShopper['phone'] . '</td>';
            $first=TRUE;
			foreach ($productShopper['order_info'] as $order_info) {
				if (!$first)
				$html .= '<tr class="row'.$i.'">';
			$html .= '<td class="quantity">';
			$html .= $order_info['quantity'];
			$html .= '</td>';
			$html .= '<td class="order_status">';
			$html .= vmText::_($order_info['order_item_status_name']);
			$html .= '</td>
			<td class="order_number">';
				$uri = JFactory::getURI();
				$link = $uri->root() . 'administrator/index.php?option=com_virtuemart&view=orders&task=edit&virtuemart_order_id=' . $order_info['order_id'];
				$html .= JHtml::_ ('link', $link, $order_info['order_number'], array('title' => vmText::_ ('COM_VIRTUEMART_ORDER_EDIT_ORDER_NUMBER') . ' ' . $order_info['order_number']));
			$first=FALSE;
			$html .= '
					</td>
				</tr>
				';
			}
			$i = 1 - $i;
		}
		if (empty($html)) {
			$html = '
				<tr class="customer">
					<td colspan="4">
						' . vmText::_ ('COM_VIRTUEMART_NO_SEARCH_RESULT') . '
					</td>
				</tr>
				';
		}

		return $html;
	}

	static public function renderMetaEdit($obj){

		$options = array(
			''	=>	vmText::_('JGLOBAL_INDEX_FOLLOW'),
			'noindex, follow'	=>	vmText::_('JGLOBAL_NOINDEX_FOLLOW'),
			'index, nofollow'	=>	vmText::_('JGLOBAL_INDEX_NOFOLLOW'),
			'noindex, nofollow'	=>	vmText::_('JGLOBAL_NOINDEX_NOFOLLOW'),
			'noodp, noydir'	=>	vmText::_('COM_VIRTUEMART_NOODP_NOYDIR'),
			'noodp, noydir, nofollow'	=>	vmText::_('COM_VIRTUEMART_NOODP_NOYDIR_NOFOLLOW'),
		);
		$html = '<table>
					'.VmHTML::row('input','COM_VIRTUEMART_CUSTOM_PAGE_TITLE','customtitle',$obj->customtitle).'
					'.VmHTML::row('textarea','COM_VIRTUEMART_METAKEY','metakey',$obj->metakey,'class="inputbox"',80).'
					'.VmHTML::row('textarea','COM_VIRTUEMART_METADESC','metadesc',$obj->metadesc,'class="inputbox"',80).'
					'.VmHtml::row('selectList','COM_VIRTUEMART_METAROBOTS','metarobot',$obj->metarobot,$options).'
					'.VmHTML::row('input','COM_VIRTUEMART_METAAUTHOR','metaauthor',$obj->metaauthor).'
				</table>';
		return $html;
	}
}

//pure php no tag
