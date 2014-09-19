<?php
/**
*
* Calc controller
*
* @package	VirtueMart
* @subpackage Calc
* @author Max Milbers, jseros
* @link http://www.virtuemart.net
* @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id: paymentmethod.php 8026 2014-06-12 17:01:23Z Milbo $
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

// Load the controller framework
jimport('joomla.application.component.controller');

if(!class_exists('VmController'))require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'vmcontroller.php');


/**
 * Calculator Controller
 *
 * @package    VirtueMart
 * @subpackage Calculation tool
 * @author Max Milbers
 */
class VirtuemartControllerPaymentmethod extends VmController {

	/**
	 * Method to display the view
	 *
	 * @access	public
	 */
	public function __construct() {
		VmConfig::loadJLang('com_virtuemart_orders',TRUE);
		parent::__construct();

	}


	function save($data = 0){
		$data = vRequest::getRequest();
		// TODO disallow html in paym_name ?
		$data['payment_name'] = vRequest::getHtml('payment_name','');
		$data['payment_desc'] = vRequest::getHtml('payment_desc','');

		parent::save($data);
	}

	/**
	 * Clone a payment
	 *
	 * @author Valérie Isaksen
	 */
	public function ClonePayment() {
		$mainframe = Jfactory::getApplication();

		/* Load the view object */
		$view = $this->getView('paymentmethod', 'html');

		$model = VmModel::getModel('paymentmethod');
		$msgtype = '';
		//$cids = vRequest::getInt('virtuemart_product_id',0);
		$cids = vRequest::getVar($this->_cidName, vRequest::getVar('virtuemart_payment_id'));
		//jimport( 'joomla.utilities.arrayhelper' );
		JArrayHelper::toInteger($cids);

		foreach($cids as $cid){
			if ($model->createClone($cid)) $msg = vmText::_('COM_VIRTUEMART_PAYMENT_CLONED_SUCCESSFULLY');
			else {
				$msg = vmText::_('COM_VIRTUEMART_PAYMENT_NOT_CLONED_SUCCESSFULLY');
				$msgtype = 'error';
			}
		}

		$mainframe->redirect('index.php?option=com_virtuemart&view=paymentmethod', $msg, $msgtype);
	}

}
// pure php no closing tag
