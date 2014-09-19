<?php
/**
*
* Category controller
*
* @package	VirtueMart
* @subpackage Category
* @author RickG, jseros
* @link http://www.virtuemart.net
* @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id: category.php 7821 2014-04-08 11:07:57Z Milbo $
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

// Load the controller framework
jimport('joomla.application.component.controller');

if(!class_exists('VmController'))require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'vmcontroller.php');

/**
 * Category Controller
 *
 * @package    VirtueMart
 * @subpackage Category
 * @author jseros, Max Milbers
 */
class VirtuemartControllerCategory extends VmController {

	public function __construct() {
		parent::__construct();
	}

	/**
	 * We want to allow html so we need to overwrite some request data
	 *
	 * @author Max Milbers
	 */
	function save($data = 0){

		//ACL
		if (!JFactory::getUser()->authorise('vm.category.edit', 'com_virtuemart')) {
			JFactory::getApplication()->redirect( 'index.php?option=com_virtuemart', vmText::_('JERROR_ALERTNOAUTHOR'), 'error');
		}
		
		$data = vRequest::getRequest();

		$data['category_name'] = vRequest::getHtml('category_name','');
		$data['category_description'] = vRequest::getHtml('category_description','');

		parent::save($data);
	}


	/**
	* Save the category order
	*
	* @author jseros
	*/
	public function orderUp()
	{
		//ACL
		if (!JFactory::getUser()->authorise('vm.category.edit', 'com_virtuemart')) {
			JFactory::getApplication()->redirect( 'index.php?option=com_virtuemart', vmText::_('JERROR_ALERTNOAUTHOR'), 'error');
		}

		// Check token
		vRequest::vmCheckToken();

		//capturing virtuemart_category_id
		$id = 0;
		$cid	= vRequest::getInt( 'cid', array() );

		if (isset($cid[0]) && $cid[0]) {
			$id = $cid[0];
		} else {
			$this->setRedirect( 'index.php?option=com_virtuemart&view=category', vmText::_('COM_VIRTUEMART_NO_ITEMS_SELECTED') );
			return false;
		}

		//getting the model
		$model = VmModel::getModel('category');

		if ($model->orderCategory($id, -1)) {
			$msg = vmText::_('COM_VIRTUEMART_ITEM_MOVED_UP');
		} else {
			$msg = $model->getError();
		}

		$this->setRedirect( 'index.php?option=com_virtuemart&view=category', $msg );
	}


	/**
	* Save the category order
	*
	* @author jseros
	*/
	public function orderDown()
	{
		//ACL
		if (!JFactory::getUser()->authorise('vm.category.edit', 'com_virtuemart')) {
			JFactory::getApplication()->redirect( 'index.php?option=com_virtuemart', vmText::_('JERROR_ALERTNOAUTHOR'), 'error');
		}
		
		// Check token
		vRequest::vmCheckToken();

		//capturing virtuemart_category_id
		$id = 0;
		$cid	= vRequest::getInt( 'cid', array() );

		if (isset($cid[0]) && $cid[0]) {
			$id = $cid[0];
		} else {
			$this->setRedirect( 'index.php?option=com_virtuemart&view=category', vmText::_('COM_VIRTUEMART_NO_ITEMS_SELECTED') );
			return false;
		}

		//getting the model
		$model = VmModel::getModel('category');

		if ($model->orderCategory($id, 1)) {
			$msg = vmText::_('COM_VIRTUEMART_ITEM_MOVED_DOWN');
		} else {
			$msg = $model->getError();
		}

		$this->setRedirect( 'index.php?option=com_virtuemart&view=category', $msg );
	}


	/**
	* Save the categories order
	*/
	public function saveOrder()
	{
		//ACL
		if (!JFactory::getUser()->authorise('vm.category.edit', 'com_virtuemart')) {
			JFactory::getApplication()->redirect( 'index.php?option=com_virtuemart', vmText::_('JERROR_ALERTNOAUTHOR'), 'error');
		}
		
		// Check for request forgeries
		vRequest::vmCheckToken();

		$cid	= vRequest::getInt( 'cid', array() );	//is sanitized

		$model = VmModel::getModel('category');

		$order	= vRequest::getInt('order', array() );

		if ($model->setOrder($cid,$order)) {
			$msg = vmText::_('COM_VIRTUEMART_NEW_ORDERING_SAVED');
		} else {
			$msg = $model->getError();
		}
		$this->setRedirect('index.php?option=com_virtuemart&view=category', $msg );
	}

}
