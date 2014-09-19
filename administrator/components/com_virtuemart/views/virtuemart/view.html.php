<?php
/**
*
* Description
*
* @package	VirtueMart
* @subpackage
* @author
* @link http://www.virtuemart.net
* @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id: view.html.php 8024 2014-06-12 15:08:59Z Milbo $
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

// Load the view framework
if(!class_exists('VmView'))require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'vmview.php');


/**
 * HTML View class for the VirtueMart Component
 *
 * @package		VirtueMart
 * @author
 */
class VirtuemartViewVirtuemart extends VmView {

	function display($tpl = null) {

		if (!class_exists('VmImage'))
			require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'image.php');
		VmConfig::loadJLang('com_virtuemart_orders',TRUE);

		$model = VmModel::getModel('virtuemart');

		$nbrCustomers = $model->getTotalCustomers();
		$this->assignRef('nbrCustomers', $nbrCustomers);

		$nbrActiveProducts = $model->getTotalActiveProducts();
		$this->assignRef('nbrActiveProducts', $nbrActiveProducts);
		$nbrInActiveProducts = $model->getTotalInActiveProducts();
		$this->assignRef('nbrInActiveProducts', $nbrInActiveProducts);
		$nbrFeaturedProducts = $model->getTotalFeaturedProducts();
		$this->assignRef('nbrFeaturedProducts', $nbrFeaturedProducts);

		$ordersByStatus = $model->getTotalOrdersByStatus();
		$this->assignRef('ordersByStatus', $ordersByStatus);

		$recentOrders = $model->getRecentOrders();
			if(!class_exists('CurrencyDisplay'))require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'currencydisplay.php');

			/* Apply currency This must be done per order since it's vendor specific */
			$_currencies = array(); // Save the currency data during this loop for performance reasons
			foreach ($recentOrders as $virtuemart_order_id => $order) {

				//This is really interesting for multi-X, but I avoid to support it now already, lets stay it in the code
				if (!array_key_exists('v'.$order->virtuemart_vendor_id, $_currencies)) {
					$_currencies['v'.$order->virtuemart_vendor_id] = CurrencyDisplay::getInstance('',$order->virtuemart_vendor_id);
				}
				$order->order_total = $_currencies['v'.$order->virtuemart_vendor_id]->priceDisplay($order->order_total);
			}
		$this->assignRef('recentOrders', $recentOrders);
		$recentCustomers = $model->getRecentCustomers();
		$this->assignRef('recentCustomers', $recentCustomers);

		if (!class_exists('ShopFunctions')) require(JPATH_VM_ADMINISTRATOR.'/helpers/shopfunctions.php');

		$this->extensionsFeed = ShopFunctions::getExtensionsRssFeed();

		$virtuemartFeed = ShopFunctions::getVirtueMartRssFeed();
		$this->assignRef('virtuemartFeed', $virtuemartFeed);

		parent::display($tpl);
	}
}

//pure php no tag