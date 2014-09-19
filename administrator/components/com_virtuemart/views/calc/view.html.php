<?php
/**
*
* Calc View
*
* @package	VirtueMart
* @subpackage Calculation tool
* @author Max Milbers
* @link http://www.virtuemart.net
* @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id: view.html.php 7854 2014-04-24 09:04:15Z Milbo $
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

// Load the view framework
if(!class_exists('VmView'))require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'vmview.php');

/**
 * Description
 *
 * @package		VirtueMart
 * @author
 */

class VirtuemartViewCalc extends VmView {

	function display($tpl = null) {

		if (!class_exists('VmHTML'))
			require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'html.php');

		$model = VmModel::getModel('calc');

		//@todo should be depended by loggedVendor
		$vendorId=1;
		$this->assignRef('vendorId', $vendorId);

		$db = JFactory::getDBO();

		$this->SetViewTitle();


		$layoutName = vRequest::getCmd('layout', 'default');
		if ($layoutName == 'edit') {

			$calc = $model->getCalc();
			
			$this->assignRef('calc',	$calc);

			$isNew = ($calc->virtuemart_calc_id < 1);
			if ($isNew) {

				$db = JFactory::getDBO();
				//get default currency of the vendor, if not set get default of the shop
				$q = 'SELECT `vendor_currency` FROM `#__virtuemart_vendors` WHERE `virtuemart_vendor_id` = "'.$vendorId.'"';
				$db->setQuery($q);
				$currency= $db->loadResult();
				if(empty($currency)){
					$q = 'SELECT `vendor_currency` FROM `#__virtuemart_vendors` WHERE `virtuemart_vendor_id` = "1" ';
					$db->setQuery($q);
					$currency= $db->loadResult();
					$calc->calc_currency = $currency;
				} else {
					$calc->calc_currency = $currency;
				}

			}
			$entryPointsList = self::renderEntryPointsList($calc->calc_kind);
			$this->assignRef('entryPointsList',$entryPointsList);

			$mathOpList = self::renderMathOpList($calc->calc_value_mathop);
			$this->assignRef('mathOpList',$mathOpList);


			/* Get the category tree */
			$categoryTree= null;
			if (isset($calc->calc_categories)){
				$calc_categories = $calc->calc_categories;
				$categoryTree = ShopFunctions::categoryListTree($calc_categories);
			}else{
				 $categoryTree = ShopFunctions::categoryListTree();
			}
			$this->assignRef('categoryTree', $categoryTree);


			$currencyModel = VmModel::getModel('currency');
			$_currencies = $currencyModel->getCurrencies();
			$this->assignRef('currencies', $_currencies);

			/* Get the shoppergroup tree */
			$shopperGroupList= ShopFunctions::renderShopperGroupList($calc->virtuemart_shoppergroup_ids,True);
			$this->assignRef('shopperGroupList', $shopperGroupList);

			if (!class_exists ('ShopFunctionsF')) {
				require(JPATH_VM_SITE . DS . 'helpers' . DS . 'shopfunctionsf.php');
			}
			
			$countriesList = ShopFunctionsF::renderCountryList($calc->calc_countries,True);
			$this->assignRef('countriesList', $countriesList);

			$statesList = ShopFunctionsF::renderStateList($calc->virtuemart_state_ids,'', True);
			$this->assignRef('statesList', $statesList);

			$manufacturerList= ShopFunctions::renderManufacturerList($calc->virtuemart_manufacturers,true);
			$this->assignRef('manufacturerList', $manufacturerList);

			if($this->showVendors()){
				$vendorList= ShopFunctions::renderVendorList($calc->virtuemart_vendor_id,false);
				$this->assignRef('vendorList', $vendorList);
			}

			$this->addStandardEditViewCommands();

        } else {
			if($this->showVendors()){
				JToolBarHelper::custom('toggle.shared.1', 'publish', 'yes', vmText::_('COM_VIRTUEMART_SHARED_TOGGLE_ON'), true);
				JToolBarHelper::custom('toggle.shared.0', 'unpublish', 'no', vmText::_('COM_VIRTUEMART_SHARED_TOGGLE_OFF'), true);
			}

			$this->addStandardDefaultViewCommands();
			$this->addStandardDefaultViewLists($model);

			$search = vRequest::getCmd('search', false);
			$calcs = $model->getCalcs(false, false, $search);

			foreach ($calcs as &$data){
				$data->calcCategoriesList = shopfunctions::renderGuiList($data->virtuemart_calc_id,'categories','category_name','category','calc_categories','virtuemart_calc_id');

				$data->calcShoppersList = shopfunctions::renderGuiList($data->virtuemart_calc_id,'shoppergroups','shopper_group_name','shopper','calc_shoppergroups','virtuemart_calc_id');

				$data->calcCountriesList = shopfunctions::renderGuiList($data->virtuemart_calc_id,'countries','country_name','country','calc_countries','virtuemart_calc_id');

				$data->calcStatesList = shopfunctions::renderGuiList($data->virtuemart_calc_id,'states','states_name','states','calc_states','virtuemart_calc_id');

				$data->calcManufacturersList = shopfunctions::renderGuiList($data->virtuemart_calc_id,'manufacturers','manufacturer_name','manufacturer','calc_manufacturers','virtuemart_calc_id');
			}

			$this->assignRef('calcs',	$calcs);

			$pagination = $model->getPagination();
			$this->assignRef('pagination', $pagination);

		}

		parent::display($tpl);
	}


	/**
	 * Builds a list to choose the Entrypoints
	 * When you want to add extra Entrypoints, look in helpers/calculationh.php for mor information
	 *
	 *
	 * @copyright Copyright (c) 2009 VirtueMart Team. All rights reserved.
	 * @author Max Milbers
	 * @param 	$selected 	the selected values, may be single data or array
	 * @return 	$list 		list of the Entrypoints
	 */

	function renderEntryPointsList($selected){

		//MathOp array
		$entryPoints = array(
		'0' => array('calc_kind' => 'Marge', 'calc_kind_name' => vmText::_('COM_VIRTUEMART_CALC_EPOINT_PMARGIN')),
		'1' => array('calc_kind' => 'DBTax', 'calc_kind_name' => vmText::_('COM_VIRTUEMART_CALC_EPOINT_DBTAX')),
		'2' => array('calc_kind' => 'Tax', 'calc_kind_name' => vmText::_('COM_VIRTUEMART_CALC_EPOINT_TAX')),
		'3' => array('calc_kind' => 'VatTax', 'calc_kind_name' => vmText::_('COM_VIRTUEMART_CALC_EPOINT_VATTAX')),
		'4' => array('calc_kind' => 'DATax', 'calc_kind_name' => vmText::_('COM_VIRTUEMART_CALC_EPOINT_DATAX')),
		'5' => array('calc_kind' => 'DBTaxBill', 'calc_kind_name' => vmText::_('COM_VIRTUEMART_CALC_EPOINT_DBTAXBILL')),
		'6' => array('calc_kind' => 'TaxBill', 'calc_kind_name' => vmText::_('COM_VIRTUEMART_CALC_EPOINT_TAXBILL')),
		'7' => array('calc_kind' => 'DATaxBill', 'calc_kind_name' => vmText::_('COM_VIRTUEMART_CALC_EPOINT_DATAXBILL')),
		);

		$listHTML = JHtml::_('Select.genericlist', $entryPoints, 'calc_kind', '', 'calc_kind', 'calc_kind_name', $selected );
		return $listHTML;

	}

	/**
	 * Builds a list to choose the mathematical operations
	 * When you want to add extra operations, look in helpers/calculationh.php for more information
	 *
	 * @copyright 	Copyright (c) 2009 VirtueMart Team. All rights reserved.
	 * @author 		Max Milbers
	 * @param 	$selected 	the selected values, may be single data or array
	 * @return 	$list 		list of the Entrypoints
	 */

	function renderMathOpList($selected){

		//MathOp array
		$mathOps = array(
		'0' => array('calc_value_mathop' => '+', 'calc_value_mathop_name' => '+'),
		'1' => array('calc_value_mathop' => '-', 'calc_value_mathop_name' => '-'),
		'2' => array('calc_value_mathop' => '+%', 'calc_value_mathop_name' => '+%'),
		'3' => array('calc_value_mathop' => '-%', 'calc_value_mathop_name' => '-%')
		);

		if (!class_exists('vmCalculationPlugin')) require(JPATH_VM_PLUGINS . DS . 'vmcalculationplugin.php');
		JPluginHelper::importPlugin('vmcalculation');
		$dispatcher = JDispatcher::getInstance();

		$answer = $dispatcher->trigger('plgVmAddMathOp', array(&$mathOps));

		$listHTML = JHtml::_('Select.genericlist', $mathOps, 'calc_value_mathop', '', 'calc_value_mathop', 'calc_value_mathop_name', $selected );
		return $listHTML;
	}



}
// pure php no closing tag