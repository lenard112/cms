<?php
/**
 *
 * List/add/edit/remove Order Status Types
 *
 * @package	VirtueMart
 * @subpackage OrderStatus
 * @author Oscar van Eijk
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
 * HTML View class for maintaining the list of order types
 *
 * @package	VirtueMart
 * @subpackage OrderStatus
 * @author Oscar van Eijk
 */
class VirtuemartViewOrderstatus extends VmView {

	function display($tpl = null) {

		// Load the helper(s)


		if (!class_exists('VmHTML'))
			require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'html.php');

		$model = VmModel::getModel();



		$layoutName = vRequest::getCmd('layout', 'default');

// 'A' : sotck Available
		// 'O' : stock Out
		// 'R' : stock reserved
			$stockHandelList = array(
				'A' => 'COM_VIRTUEMART_ORDER_STATUS_STOCK_AVAILABLE',
				'R' => 'COM_VIRTUEMART_ORDER_STATUS_STOCK_RESERVED',
				'O' => 'COM_VIRTUEMART_ORDER_STATUS_STOCK_OUT'
			);

		if ($layoutName == 'edit') {
			$orderStatus = $model->getData();
			$this->SetViewTitle('',vmText::_($orderStatus->order_status_name) );
			if ($orderStatus->virtuemart_orderstate_id < 1) {

				$this->assignRef('ordering', vmText::_('COM_VIRTUEMART_NEW_ITEMS_PLACE'));
			} else {

				if (!class_exists('ShopFunctions'))
					require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'shopfunctions.php');
				$this->ordering = ShopFunctions::renderOrderingList('orderstates','order_status_name',$orderStatus->virtuemart_orderstate_id);

			}
			$lists['vmCoreStatusCode'] = $model->getVMCoreStatusCode();

			$this->assignRef('stockHandelList', $stockHandelList);
			// Vendor selection
			$vendor_model = VmModel::getModel('vendor');
			$vendor_list = $vendor_model->getVendors();
			$lists['vendors'] = JHtml::_('select.genericlist', $vendor_list, 'virtuemart_vendor_id', '', 'virtuemart_vendor_id', 'vendor_name', $orderStatus->virtuemart_vendor_id);


			$this->assignRef('orderStatus', $orderStatus);
			$this->assignRef('lists', $lists);

			$this->addStandardEditViewCommands();
		} else {
			$this->SetViewTitle('');
			$this->addStandardDefaultViewCommands();
			$this->addStandardDefaultViewLists($model);
			$this->lists['vmCoreStatusCode'] = $model->getVMCoreStatusCode();

			$orderStatusList = $model->getOrderStatusList();
			$this->assignRef('orderStatusList', $orderStatusList);

			$this->assignRef('stockHandelList', $stockHandelList);

			$pagination = $model->getPagination();
			$this->assignRef('pagination', $pagination);
		}

		parent::display($tpl);
	}
}

//No Closing Tag
