<?php
/**
*
* Review controller
*
* @package	VirtueMart
* @subpackage
* @author Max Milberes
* @link http://www.virtuemart.net
* @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id: ratings.php 7821 2014-04-08 11:07:57Z Milbo $
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

// Load the controller framework
jimport('joomla.application.component.controller');

if (!class_exists ('VmController')){
	require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'vmcontroller.php');
}


/**
 * Review Controller
 *
 * @package    VirtueMart
 * @author Max Milbers
 */
class VirtuemartControllerRatings extends VmController {

	/**
	 * Method to display the view
	 *
	 * @access	public
	 */
	function __construct() {
		parent::__construct();

		$task = vRequest::getVar('task');

	}

	/**
	 * Generic edit task
	 *
	 * @author Max Milbers
	 */
	function edit_review(){

		vRequest::setVar('controller', $this->_cname);
		vRequest::setVar('view', $this->_cname);
		vRequest::setVar('layout', 'edit_review');
// 		vRequest::setVar('hidemenu', 1);

		if(empty($view)){
			$document = JFactory::getDocument();
			$viewType = $document->getType();
			$view = $this->getView($this->_cname, $viewType);
		}


		parent::display();
	}

	/**
	 * lits the reviews
	 * @author Max Milbers
	 */
	public function listreviews(){

		/* Create the view object */
		$view = $this->getView('ratings', 'html');

		$view->setLayout('list_reviews');

		$view->display();
	}

	/**
	 * we must overwrite it here, because the task publish can be meant for two different list layouts.
	 */
	function publish(){

		vRequest::vmCheckToken();
		$layout = vRequest::getString('layout','default');

		if($layout=='list_reviews'){

			$virtuemart_product_id = vRequest::getInt('virtuemart_product_id');
			if(is_array($virtuemart_product_id) && count($virtuemart_product_id) > 0){
				$virtuemart_product_id = (int)$virtuemart_product_id[0];
			} else {
				$virtuemart_product_id = (int)$virtuemart_product_id;
			}
			$redPath = '';
			if (!empty($virtuemart_product_id)) {
				$redPath = '&task=listreviews&virtuemart_product_id=' . $virtuemart_product_id;
			}

			parent::publish('virtuemart_rating_review_id','rating_reviews',$this->redirectPath.$redPath);
		} else {
			parent::publish();
		}

	}

	function unpublish(){

		vRequest::vmCheckToken();
		$layout = vRequest::getString('layout','default');

		if($layout=='list_reviews'){

			$virtuemart_product_id = vRequest::getInt('virtuemart_product_id');
			if(is_array($virtuemart_product_id) && count($virtuemart_product_id) > 0){
				$virtuemart_product_id = (int)$virtuemart_product_id[0];
			} else {
				$virtuemart_product_id = (int)$virtuemart_product_id;
			}
			$redPath = '';
			if (!empty($virtuemart_product_id)) {
				$redPath = '&task=listreviews&virtuemart_product_id=' . $virtuemart_product_id;
			}

			parent::unpublish('virtuemart_rating_review_id','rating_reviews',$this->redirectPath.$redPath);
		} else {
			parent::unpublish();
		}

	}

	/**
	 * Save task for review
	 *
	 * @author Max Milbers
	 */
	function saveReview(){

		$this->storeReview(FALSE);
	}

	/**
	 * Save task for review
	 *
	 * @author Max Milbers
	 */
	function applyReview(){

		$this->storeReview(TRUE);
	}


	function storeReview($apply){

		vRequest::vmCheckToken();

		if (empty($data)){
			$data = vRequest::get ('post');
		}

		$model = VmModel::getModel($this->_cname);
		$id = $model->saveRating($data);

		$errors = $model->getErrors();
		if (empty($errors)) {
			$msg = vmText::sprintf ('COM_VIRTUEMART_STRING_SAVED', $this->mainLangKey);
		}
		foreach($errors as $error){
			$msg = ($error).'<br />';
		}

		$redir = $this->redirectPath;
		if($apply){
			$redir = 'index.php?option=com_virtuemart&view=ratings&task=edit_review&virtuemart_rating_review_id='.$id;
		} else {
			$virtuemart_product_id = vRequest::getInt('virtuemart_product_id');
			if(is_array($virtuemart_product_id) && count($virtuemart_product_id) > 0){
				$virtuemart_product_id = (int)$virtuemart_product_id[0];
			} else {
				$virtuemart_product_id = (int)$virtuemart_product_id;
			}
			$redir = 'index.php?option=com_virtuemart&view=ratings&task=listreviews&virtuemart_product_id='.$virtuemart_product_id;
		}

		$this->setRedirect($redir, $msg);
	}
	/**
	 * Save task for review
	 *
	 * @author Max Milbers
	 */
	function cancelEditReview(){

		$virtuemart_product_id = vRequest::getInt('virtuemart_product_id');
		if(is_array($virtuemart_product_id) && count($virtuemart_product_id) > 0){
			$virtuemart_product_id = (int)$virtuemart_product_id[0];
		} else {
			$virtuemart_product_id = (int)$virtuemart_product_id;
		}
		$msg = vmText::sprintf('COM_VIRTUEMART_STRING_CANCELLED',$this->mainLangKey); //'COM_VIRTUEMART_OPERATION_CANCELED'
		$this->setRedirect('index.php?option=com_virtuemart&view=ratings&task=listreviews&virtuemart_product_id='.$virtuemart_product_id, $msg);
	}

}
// pure php no closing tag
