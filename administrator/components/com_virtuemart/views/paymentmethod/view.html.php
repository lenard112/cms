<?php
/**
 *
 * Calc View
 *
 * @package	VirtueMart
 * @subpackage Payment Method
 * @author Max Milbers
 * @author valérie isaksen
 * @link http://www.virtuemart.net
 * @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: view.html.php 8228 2014-08-21 21:07:12Z Milbo $
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

// Load the view framework
if(!class_exists('VmView'))require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'vmview.php');

/**
 * Description
 *
 * @package		VirtueMart
 * @author valérie isaksen
 */
if (!class_exists('VirtueMartModelCurrency'))
require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'currency.php');

class VirtuemartViewPaymentMethod extends VmView {

	function display($tpl = null) {

		// Load the helper(s)
		$this->addHelperPath(JPATH_VM_ADMINISTRATOR.DS.'helpers');

		if (!class_exists('VmHTML'))
			require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'html.php');

		if (!class_exists ('vmPSPlugin')) {
			require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');
		}

		$this->user = JFactory::getUser();
		$model = VmModel::getModel('paymentmethod');

		// TODO logo
		$this->SetViewTitle();

		$layoutName = vRequest::getCmd('layout', 'default');

		$vendorModel = VmModel::getModel('vendor');

		$vendorModel->setId(1);
		$vendor = $vendorModel->getVendor();
		$currencyModel = VmModel::getModel('currency');
		$currencyModel = $currencyModel->getCurrency($vendor->vendor_currency);
		$this->assignRef('vendor_currency', $currencyModel->currency_symbol);

		if ($layoutName == 'edit') {

			// Load the helper(s)
			if (!class_exists('VmImage'))
				require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'image.php');

			VmConfig::loadJLang('plg_vmpsplugin', false);

			JForm::addFieldPath(JPATH_VM_ADMINISTRATOR . DS . 'fields');

			$payment = $model->getPayment();

			// Get the payment XML.
			$formFile	= JPath::clean( JPATH_ROOT .DS. 'plugins'. DS. 'vmpayment' .DS. $payment->payment_element .DS. $payment->payment_element . '.xml');
			if (file_exists($formFile)){

				$payment->form = JForm::getInstance($payment->payment_element, $formFile, array(),false, '//vmconfig | //config[not(//vmconfig)]');
				$payment->params = new stdClass();
				$varsToPush = vmPlugin::getVarsToPushByXML($formFile,'paymentForm');
				$payment->params->payment_params = $payment->payment_params;
				VmTable::bindParameterable($payment->params,'payment_params',$varsToPush);
				$payment->form->bind($payment);

			} else {
				$payment->form = null;
			}

			$this->assignRef('payment',	$payment);
			$this->assignRef('vmPPaymentList', self::renderInstalledPaymentPlugins($payment->payment_jplugin_id));
			$this->assignRef('shopperGroupList', ShopFunctions::renderShopperGroupList($payment->virtuemart_shoppergroup_ids, true));

			if(Vmconfig::get('multix','none')!=='none'){
				$vendorList= ShopFunctions::renderVendorList($payment->virtuemart_vendor_id);
				$this->assignRef('vendorList', $vendorList);
			}

			$this->addStandardEditViewCommands( $payment->virtuemart_paymentmethod_id);
		} else {
			JToolBarHelper::custom('clonepayment', 'copy', 'copy', vmText::_('COM_VIRTUEMART_PAYMENT_CLONE'), true);

			$this->addStandardDefaultViewCommands();
			$this->addStandardDefaultViewLists($model);

			$this->payments = $model->getPayments();
			foreach ($this->payments as &$data){
				// Write the first 5 shoppergroups in the list
				$data->paymShoppersList = shopfunctions::renderGuiList($data->virtuemart_shoppergroup_ids,'shoppergroups','shopper_group_name','payment' );
			}

			$pagination = $model->getPagination();
			$this->assignRef('pagination', $pagination);

		}

		parent::display($tpl);
	}

	function renderInstalledPaymentPlugins($selected){

		$db = JFactory::getDBO();

		$q = 'SELECT * FROM `#__extensions` WHERE `folder` = "vmpayment" and `state`="0"  ORDER BY `ordering`,`name` ASC';
		$db->setQuery($q);
		$result = $db->loadAssocList('extension_id');
		if(empty($result)){
			$app = JFactory::getApplication();
			$app -> enqueueMessage(vmText::_('COM_VIRTUEMART_NO_PAYMENT_PLUGINS_INSTALLED'));
		}

		$listHTML='<select id="payment_jplugin_id" name="payment_jplugin_id" style= "width: 300px;">';

		foreach($result as $paym){
			if($paym['extension_id']==$selected) $checked='selected="selected"'; else $checked='';
			// Get plugin info
			$listHTML .= '<option '.$checked.' value="'.$paym['extension_id'].'">'.vmText::_($paym['name']).'</option>';

		}
		$listHTML .= '</select>';

		return $listHTML;
	}

}
// pure php not tag
