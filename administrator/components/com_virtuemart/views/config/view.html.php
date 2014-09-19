<?php
/**
*
* Description
*
* @package	VirtueMart
* @subpackage Config
* @author RickG
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
if(!class_exists('VmView'))require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'vmview.php');
jimport('joomla.version');

/**
 * HTML View class for the configuration maintenance
 *
 * @package		VirtueMart
 * @subpackage 	Config
 * @author 		RickG
 */
class VirtuemartViewConfig extends VmView {

	function display($tpl = null) {

		if (!class_exists('VmImage'))
			require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'image.php');

		if (!class_exists('VmHTML'))
			require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'html.php');

		$model = VmModel::getModel();
		$usermodel = VmModel::getModel('user');

		JToolBarHelper::title( vmText::_('COM_VIRTUEMART_CONFIG') , 'head vm_config_48');

		$this->addStandardEditViewCommands();

		$this->config = VmConfig::loadConfig();
		if(!empty($this->config->_params)){
			unset ($this->config->_params['pdf_invoice']); // parameter remove and replaced by inv_os
		}

		$this->userparams = JComponentHelper::getParams('com_users');

		$this->jTemplateList = ShopFunctions::renderTemplateList(vmText::_('COM_VIRTUEMART_ADMIN_CFG_JOOMLA_TEMPLATE_DEFAULT'));

		$this->vmLayoutList = $model->getLayoutList('virtuemart');

		$this->categoryLayoutList = $model->getLayoutList('category');

		$this->productLayoutList = $model->getLayoutList('productdetails');

		$this->productsFieldList  = $model->getFieldList('products');

		$this->noimagelist = $model->getNoImageList();

		$orderStatusModel= VmModel::getModel('orderstatus');
		$this->inv_osList = $orderStatusModel->renderOSList(VmConfig::get('inv_os',array('C')),'inv_os',TRUE);
		$this->email_os_sList = $orderStatusModel->renderOSList(VmConfig::get('email_os_s',array('U','C','S','R','X')),'email_os_s',TRUE);
		$this->email_os_vList = $orderStatusModel->renderOSList(VmConfig::get('email_os_v',array('U','C','R','X')),'email_os_v',TRUE);
		$this->cp_rmList = $orderStatusModel->renderOSList(VmConfig::get('cp_rm',array('C')),'cp_rm',TRUE);
		$this->rr_osList = $orderStatusModel->renderOSList(VmConfig::get('rr_os',array('C')),'rr_os',TRUE);

		$this->currConverterList = $model->getCurrencyConverterList();
		//$moduleList = $model->getModuleList();

		$this->activeLanguages = $model->getActiveLanguages( VmConfig::get('active_languages') );

		$this->orderByFieldsProduct = $model->getProductFilterFields('browse_orderby_fields');

		VmModel::getModel('category');

		foreach (VirtueMartModelCategory::$_validOrderingFields as $key => $field ) {
			if($field=='c.category_shared') continue;
			$fieldWithoutPrefix = $field;
			$dotps = strrpos($fieldWithoutPrefix, '.');
			if($dotps!==false){
				$prefix = substr($field, 0,$dotps+1);
				$fieldWithoutPrefix = substr($field, $dotps+1);
			}

			$text = vmText::_('COM_VIRTUEMART_'.strtoupper($fieldWithoutPrefix)) ;
			$orderByFieldsCat[] =  JHtml::_('select.option', $field, $text) ;
		}

		$this->orderByFieldsCat = $orderByFieldsCat;

		$this->searchFields = $model->getProductFilterFields( 'browse_search_fields');

		$this->aclGroups = $usermodel->getAclGroupIndentedTree();

		if(!class_exists('shopFunctionsF'))require(JPATH_VM_SITE.DS.'helpers'.DS.'shopfunctionsf.php');
		$this->vmtemplate = shopFunctionsF::loadVmTemplateStyle();

		if(is_Dir(JPATH_ROOT.DS.'templates'.DS.$this->vmtemplate.DS.'images'.DS.'availability'.DS)){
			$this->imagePath = '/templates/'.$this->vmtemplate.'/images/availability/';
		} else {
			$this->imagePath = '/components/com_virtuemart/assets/images/availability/';
		}

		shopFunctions::checkSafePath();
		$this -> checkVmUserVendor();

		parent::display($tpl);
	}

	private function checkVmUserVendor(){

		$db = JFactory::getDBO();
		$multix = Vmconfig::get('multix','none');

		$q = 'select * from #__virtuemart_vmusers where user_is_vendor = 1';// and virtuemart_vendor_id '.$vendorWhere.' limit 1';
		$db->setQuery($q);
		$r = $db->loadAssocList();

		if (empty($r)){
			vmWarn('Your Virtuemart installation contains an error: No user as marked as vendor. Please fix this in your phpMyAdmin and set #__virtuemart_vmusers.user_is_vendor = 1 and #__virtuemart_vmusers.virtuemart_vendor_id = 1 to one of your administrator users. Please update all users to be associated with virtuemart_vendor_id 1.');
		} else {
			if($multix=='none' and count($r)!=1){
				vmWarn('You are using single vendor mode, but it seems more than one user is set as vendor');
			}
			foreach($r as $entry){
				if(empty($entry['virtuemart_vendor_id'])){
					vmWarn('The user with virtuemart_user_id = '.$entry['virtuemart_user_id'].' is set as vendor, but has no referencing vendorId.');
				}
			}
		}
	}

}
// pure php no closing tag
