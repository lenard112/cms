<?php

/**
 *
 * View for the shopping cart
 *
 * @package	VirtueMart
 * @subpackage
 * @author Max Milbers
 * @author Oscar van Eijk
 * @author RolandD
 * @link http://www.virtuemart.net
 * @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: view.html.php 8222 2014-08-21 00:05:19Z Milbo $
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

// Load the view framework
if(!class_exists('VmView'))require(JPATH_VM_SITE.DS.'helpers'.DS.'vmview.php');

/**
 * View for the shopping cart
 * @package VirtueMart
 * @author Max Milbers
 * @author Patrick Kohl
 */
class VirtueMartViewCart extends VmView {

	public function display($tpl = null) {

		$mainframe = JFactory::getApplication();

		$this->prepareContinueLink();
		if (VmConfig::get('use_as_catalog',0)) {
			vmInfo('This is a catalogue, you cannot acccess the cart');
			$mainframe->redirect($this->continue_link);
		}

		$pathway = $mainframe->getPathway();
		$document = JFactory::getDocument();
		$document->setMetaData('robots','NOINDEX, NOFOLLOW, NOARCHIVE, NOSNIPPET');

		$layoutName = $this->getLayout();

		if (!$layoutName) $layoutName = vRequest::getCmd('layout', 'default');
		$this->assignRef('layoutName', $layoutName);
		$format = vRequest::getCmd('format');

		if (!class_exists('VirtueMartCart'))
		require(JPATH_VM_SITE . DS . 'helpers' . DS . 'cart.php');
		$this->cart = VirtueMartCart::getCart();
		//$this->assignRef('cart', $cart);

		$this->cart->prepareVendor();

		//Why is this here, when we have view.raw.php
		if ($format == 'raw') {
			//$this->prepareCartViewData();
			vRequest::setVar('layout', 'mini_cart');
			$this->setLayout('mini_cart');
			$this->prepareContinueLink();
		}

		if ($layoutName == 'select_shipment') {

			$this->cart->prepareCartData();
			$this->lSelectShipment();

			$pathway->addItem(vmText::_('COM_VIRTUEMART_CART_OVERVIEW'), JRoute::_('index.php?option=com_virtuemart&view=cart', FALSE));
			$pathway->addItem(vmText::_('COM_VIRTUEMART_CART_SELECTSHIPMENT'));
			$document->setTitle(vmText::_('COM_VIRTUEMART_CART_SELECTSHIPMENT'));
		} else if ($layoutName == 'select_payment') {

			$this->cart->prepareCartData();

			$this->lSelectPayment();

			$pathway->addItem(vmText::_('COM_VIRTUEMART_CART_OVERVIEW'), JRoute::_('index.php?option=com_virtuemart&view=cart', FALSE));
			$pathway->addItem(vmText::_('COM_VIRTUEMART_CART_SELECTPAYMENT'));
			$document->setTitle(vmText::_('COM_VIRTUEMART_CART_SELECTPAYMENT'));
		} else if ($layoutName == 'order_done') {
			VmConfig::loadJLang('com_virtuemart_shoppers', true);
			$this->lOrderDone();

			$pathway->addItem(vmText::_('COM_VIRTUEMART_CART_THANKYOU'));
			$document->setTitle(vmText::_('COM_VIRTUEMART_CART_THANKYOU'));
		} else if ($layoutName == 'default') {
			VmConfig::loadJLang('com_virtuemart_shoppers', true);

			$this->renderCompleteAddressList();



			if (!class_exists ('VirtueMartModelUserfields')) {
				require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'userfields.php');
			}

			$userFieldsModel = VmModel::getModel ('userfields');

			$userFieldsCart = $userFieldsModel->getUserFields(
				'cart'
				, array('captcha' => true, 'delimiters' => true) // Ignore these types
				, array('delimiter_userinfo','user_is_vendor' ,'username','password', 'password2', 'agreed', 'address_type') // Skips
			);

			$this->userFieldsCart = $userFieldsModel->getUserFieldsFilled(
				$userFieldsCart
				,$this->cart->cartfields
			);



			if (!class_exists ('CurrencyDisplay'))
				require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'currencydisplay.php');

			$currencyDisplay = CurrencyDisplay::getInstance($this->cart->pricesCurrency);
			$this->assignRef('currencyDisplay',$currencyDisplay);

			$customfieldsModel = VmModel::getModel ('Customfields');
			$this->assignRef('customfieldsModel',$customfieldsModel);

			$this->lSelectCoupon();

			$totalInPaymentCurrency = $this->getTotalInPaymentCurrency();

			$checkoutAdvertise =$this->getCheckoutAdvertise();

			if ($this->cart->getDataValidated()) {
				if($this->cart->_inConfirm){
					$pathway->addItem(vmText::_('COM_VIRTUEMART_CANCEL_CONFIRM_MNU'));
					$document->setTitle(vmText::_('COM_VIRTUEMART_CANCEL_CONFIRM_MNU'));
					$text = vmText::_('COM_VIRTUEMART_CANCEL_CONFIRM');
					$this->checkout_task = 'cancel';
				} else {
					$pathway->addItem(vmText::_('COM_VIRTUEMART_ORDER_CONFIRM_MNU'));
					$document->setTitle(vmText::_('COM_VIRTUEMART_ORDER_CONFIRM_MNU'));
					$text = vmText::_('COM_VIRTUEMART_ORDER_CONFIRM_MNU');
					$this->checkout_task = 'confirm';
				}


			} else {
				$pathway->addItem(vmText::_('COM_VIRTUEMART_CART_OVERVIEW'));
				$document->setTitle(vmText::_('COM_VIRTUEMART_CART_OVERVIEW'));
				$text = vmText::_('COM_VIRTUEMART_CHECKOUT_TITLE');
				$this->checkout_task = 'checkout';
			}
			$this->checkout_link_html = '<button type="submit"  id="checkoutFormSubmit" name="'.$this->checkout_task.'" value="1" class="vm-button-correct" ><span>' . $text . '</span> </button>';


			if (VmConfig::get('oncheckout_opc', 1)) {
				if (!class_exists('vmPSPlugin')) require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');
				JPluginHelper::importPlugin('vmshipment');
				JPluginHelper::importPlugin('vmpayment');
				$this->lSelectShipment();
				$this->lSelectPayment();
			} else {
				$this->checkPaymentMethodsConfigured();
				$this->checkShipmentMethodsConfigured();
			}

			if ($this->cart->virtuemart_shipmentmethod_id) {
				$shippingText =  vmText::_('COM_VIRTUEMART_CART_CHANGE_SHIPPING');
			} else {
				$shippingText = vmText::_('COM_VIRTUEMART_CART_EDIT_SHIPPING');
			}
			$this->assignRef('select_shipment_text', $shippingText);

			if ($this->cart->virtuemart_paymentmethod_id) {
				$paymentText = vmText::_('COM_VIRTUEMART_CART_CHANGE_PAYMENT');
			} else {
				$paymentText = vmText::_('COM_VIRTUEMART_CART_EDIT_PAYMENT');
			}
			$this->assignRef('select_payment_text', $paymentText);

			$this->cart->prepareAddressFieldsInCart();

			$layoutName = $this->cart->layout;
			//set order language
			$lang = JFactory::getLanguage();
			$order_language = $lang->getTag();
			$this->assignRef('order_language',$order_language);
		}

		//dump ($this->cart,'cart');
		$useSSL = VmConfig::get('useSSL', 0);
		$useXHTML = false;
		$this->assignRef('useSSL', $useSSL);
		$this->assignRef('useXHTML', $useXHTML);
		$this->assignRef('totalInPaymentCurrency', $totalInPaymentCurrency);
		$this->assignRef('checkoutAdvertise', $checkoutAdvertise);

		shopFunctionsF::setVmTemplate($this, 0, 0, $layoutName);

		//We never want that the cart is indexed
		$document->setMetaData('robots','NOINDEX, NOFOLLOW, NOARCHIVE, NOSNIPPET');

		if($this->cart->_inConfirm) vmInfo('COM_VIRTUEMART_IN_CONFIRM');
		parent::display($tpl);
	}


	/*
 * Prepare the datas for cart/mail views
* set product, price, user, adress and vendor as Object
* @author Patrick Kohl
* @author Valerie Isaksen
*/
/*	function prepareCartViewData(){

		// Get the products for the cart
		//$this->cart->prepareCartData();

		//$this->cart->prepareAddressFieldsInCart();

		$vendorModel = VmModel::getModel('vendor');
		$this->cart->vendor = $vendorModel->getVendor(1);
		$vendorModel->addImages($this->cart->vendor,1);

	}*/



	private function lSelectCoupon() {

		$this->couponCode = (!empty($this->cart->couponCode) ? $this->cart->couponCode : '');
		$this->coupon_text = $this->cart->couponCode ? vmText::_('COM_VIRTUEMART_COUPON_CODE_CHANGE') : vmText::_('COM_VIRTUEMART_COUPON_CODE_ENTER');
	}

	/*
	 * lSelectShipment
	* find al shipment rates available for this cart
	*
	* @author Valerie Isaksen
	*/

	private function lSelectShipment() {
		$found_shipment_method=false;
		$shipment_not_found_text = vmText::_('COM_VIRTUEMART_CART_NO_SHIPPING_METHOD_PUBLIC');
		$this->assignRef('shipment_not_found_text', $shipment_not_found_text);
		$this->assignRef('found_shipment_method', $found_shipment_method);

		$shipments_shipment_rates=array();
		if (!$this->checkShipmentMethodsConfigured()) {
			$this->assignRef('shipments_shipment_rates',$shipments_shipment_rates);
			return;
		}

		$selectedShipment = (empty($this->cart->virtuemart_shipmentmethod_id) ? 0 : $this->cart->virtuemart_shipmentmethod_id);

		$shipments_shipment_rates = array();
		if (!class_exists('vmPSPlugin')) require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');
		JPluginHelper::importPlugin('vmshipment');
		$dispatcher = JDispatcher::getInstance();

		$returnValues = $dispatcher->trigger('plgVmDisplayListFEShipment', array( $this->cart, $selectedShipment, &$shipments_shipment_rates));
		// if no shipment rate defined
		$found_shipment_method =count($shipments_shipment_rates);

		if ($found_shipment_method== 0 AND empty($this->cart->BT))  {
			$redirectMsg = vmText::_('COM_VIRTUEMART_CART_ENTER_ADDRESS_FIRST');
			if (VmConfig::get('oncheckout_opc', 1)) {
				vmInfo($redirectMsg);
			} else {
				$mainframe = JFactory::getApplication();
				$mainframe->redirect(JRoute::_('index.php?option=com_virtuemart&view=user&task=editaddresscart&addrtype=BT'), $redirectMsg);
			}
		}
		$shipment_not_found_text = vmText::_('COM_VIRTUEMART_CART_NO_SHIPPING_METHOD_PUBLIC');
		$this->assignRef('shipment_not_found_text', $shipment_not_found_text);
		$this->assignRef('shipments_shipment_rates', $shipments_shipment_rates);
		$this->assignRef('found_shipment_method', $found_shipment_method);


		return;
	}

	/*
	 * lSelectPayment
	* find al payment available for this cart
	*
	* @author Valerie Isaksen
	*/

	private function lSelectPayment() {

		$this->payment_not_found_text='';
		$this->payments_payment_rates=array();

		$this->found_payment_method = 0;
		$selectedPayment = empty($this->cart->virtuemart_paymentmethod_id) ? 0 : $this->cart->virtuemart_paymentmethod_id;

		$this->paymentplugins_payments = array();
		if (!$this->checkPaymentMethodsConfigured()) {
			return;
		}

		if(!class_exists('vmPSPlugin')) require(JPATH_VM_PLUGINS.DS.'vmpsplugin.php');
		JPluginHelper::importPlugin('vmpayment');
		$dispatcher = JDispatcher::getInstance();

		$returnValues = $dispatcher->trigger('plgVmDisplayListFEPayment', array($this->cart, $selectedPayment, &$this->paymentplugins_payments));

		$this->found_payment_method =count($this->paymentplugins_payments);
		if (!$this->found_payment_method) {
			$link=''; // todo
			$this->payment_not_found_text = vmText::sprintf('COM_VIRTUEMART_CART_NO_PAYMENT_METHOD_PUBLIC', '<a href="'.$link.'" rel="nofollow">'.$link.'</a>');
		}
		if ($this->found_payment_method == 0 AND empty($this->cart->BT))  {
			$redirectMsg = vmText::_('COM_VIRTUEMART_CART_ENTER_ADDRESS_FIRST');
			if (VmConfig::get('oncheckout_opc', 1)) {
				vmInfo($redirectMsg);
			} else {
				$mainframe = JFactory::getApplication();
				$mainframe->redirect(JRoute::_('index.php?option=com_virtuemart&view=user&task=editaddresscart&addrtype=BT'), $redirectMsg);
			}
		}

	}

	private function getTotalInPaymentCurrency() {

		if (empty($this->cart->virtuemart_paymentmethod_id)) {
			return null;
		}

		if (!$this->cart->paymentCurrency or ($this->cart->paymentCurrency==$this->cart->pricesCurrency)) {
			return null;
		}

		$paymentCurrency = CurrencyDisplay::getInstance($this->cart->paymentCurrency);

		$totalInPaymentCurrency = $paymentCurrency->priceDisplay( $this->cart->cartPrices['billTotal'],$this->cart->paymentCurrency) ;

		$currencyDisplay = CurrencyDisplay::getInstance($this->cart->pricesCurrency);
// 		$this->assignRef('currencyDisplay',$currencyDisplay);

		return $totalInPaymentCurrency;
	}
	/*
	 * Trigger to place Coupon, payment, shipment advertisement on the cart
	 */
	private function getCheckoutAdvertise() {
		$checkoutAdvertise=array();
		JPluginHelper::importPlugin('vmcoupon');
		JPluginHelper::importPlugin('vmshipment');
		JPluginHelper::importPlugin('vmpayment');
		$dispatcher = JDispatcher::getInstance();
		$returnValues = $dispatcher->trigger('plgVmOnCheckoutAdvertise', array( $this->cart, &$checkoutAdvertise));
		return $checkoutAdvertise;
}

	private function lOrderDone() {
		$display_title = vRequest::getBool('display_title',true);
		$this->assignRef('display_title', $display_title);
		// // Do not change this. It contains the payment form
		$this->html = vRequest::get('html', vmText::_('COM_VIRTUEMART_ORDER_PROCESSED') );
		//Show Thank you page or error due payment plugins like paypal express
	}

	private function checkPaymentMethodsConfigured() {

		//For the selection of the payment method we need the total amount to pay.
		$paymentModel = VmModel::getModel('Paymentmethod');
		$payments = $paymentModel->getPayments(true, false);
		if (empty($payments)) {

			$text = '';
			$user = JFactory::getUser();
			if($user->authorise('core.admin','com_virtuemart') or $user->authorise('core.manage','com_virtuemart') or VmConfig::isSuperVendor()) {
				$uri = JFactory::getURI();
				$link = $uri->root() . 'administrator/index.php?option=com_virtuemart&view=paymentmethod';
				$text = vmText::sprintf('COM_VIRTUEMART_NO_PAYMENT_METHODS_CONFIGURED_LINK', '<a href="' . $link . '" rel="nofollow">' . $link . '</a>');
			}

			vmInfo('COM_VIRTUEMART_NO_PAYMENT_METHODS_CONFIGURED', $text);

			$tmp = 0;
			$this->assignRef('found_payment_method', $tmp);
			$this->cart->virtuemart_paymentmethod_id = 0;
			return false;
		}
		return true;
	}

	private function checkShipmentMethodsConfigured() {

		//For the selection of the shipment method we need the total amount to pay.
		$shipmentModel = VmModel::getModel('Shipmentmethod');
		$shipments = $shipmentModel->getShipments();
		if (empty($shipments)) {

			$text = '';
			$user = JFactory::getUser();
			if($user->authorise('core.admin','com_virtuemart') or $user->authorise('core.manage','com_virtuemart') or VmConfig::isSuperVendor()) {
				$uri = JFactory::getURI();
				$link = $uri->root() . 'administrator/index.php?option=com_virtuemart&view=shipmentmethod';
				$text = vmText::sprintf('COM_VIRTUEMART_NO_SHIPPING_METHODS_CONFIGURED_LINK', '<a href="' . $link . '" rel="nofollow">' . $link . '</a>');
			}

			vmInfo('COM_VIRTUEMART_NO_SHIPPING_METHODS_CONFIGURED', $text);

			$tmp = 0;
			$this->assignRef('found_shipment_method', $tmp);
			$this->cart->virtuemart_shipmentmethod_id = 0;
			return false;
		}
		return true;
	}

/**
	 * Todo, works only for small stores, we need a new solution there with a bit filtering
	 * For example by time, if already shopper, and a simple search
	 * @return object list of users
	 */
	function getUserList() {
		$db = JFactory::getDbo();
		$q = 'SELECT `id`,`name`,`username` FROM `#__users` ORDER BY `name` LIMIT 0,10000';
		$db->setQuery($q);
		$result = $db->loadObjectList();
		foreach($result as $user) {
			$user->displayedName = $user->name .'&nbsp;&nbsp;( '. $user->username .' )';
		}
		return $result;
	}

	function renderCompleteAddressList(){

		$addressList = false;

		if($this->cart->user->virtuemart_user_id){
			$addressList = array();
			$newBT = '<a href="index.php'
				.'?option=com_virtuemart'
				.'&view=user'
				.'&task=editaddresscart'
				.'&addrtype=BT'
				. '">'.vmText::_('COM_VIRTUEMART_ACC_BILL_DEF').'</a></br>';
			foreach($this->cart->user->userInfo as $userInfo){
				$address = $userInfo->loadFieldValues(false);
				if($address->address_type=='BT'){
					$address->virtuemart_userinfo_id = 0;
					$address->address_type_name = $newBT;
					array_unshift($addressList,$address);
				} else {
					$address->address_type_name = '<a href="index.php'
					.'?option=com_virtuemart'
					.'&view=user'
					.'&task=editaddresscart'
					.'&addrtype=ST'
					.'&virtuemart_userinfo_id='.$address->virtuemart_userinfo_id
					. '" rel="nofollow">'.$address->address_type_name.'</a></br>';
					$addressList[] = $address;
				}
			}
			if(count($addressList)==0){
				$addressList[0] = new stdClass();
				$addressList[0]->virtuemart_userinfo_id = 0;
				$addressList[0]->address_type_name = $newBT;
			}

			$_selectedAddress = (
			empty($this->cart->selected_shipto)
				? $addressList[0]->virtuemart_userinfo_id // Defaults to 1st BillTo
				: $this->cart->selected_shipto
			);

			$this->cart->lists['shipTo'] = JHtml::_('select.radiolist', $addressList, 'shipto', null, 'virtuemart_userinfo_id', 'address_type_name', $_selectedAddress);
			$this->cart->lists['billTo'] = empty($addressList[0]->virtuemart_userinfo_id)? 0 : $addressList[0]->virtuemart_userinfo_id;
		} else {
			$this->cart->lists['shipTo'] = false;
			$this->cart->lists['billTo'] = false;
		}


	}

}

//no closing tag
