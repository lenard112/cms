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
* @version $Id: view.html.php 7821 2014-04-08 11:07:57Z Milbo $
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
class VirtuemartViewMedia extends VmView {

	function display($tpl = null) {

		if (!class_exists('VmHTML'))
			require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'html.php');

		$vendorId=VmConfig::isSuperVendor();
		$this->assignRef('vendorId', $vendorId);

		// TODO add icon for media view
		$this->SetViewTitle();

		$model = VmModel::getModel('media');

		$layoutName = vRequest::getCmd('layout', 'default');
		if ($layoutName == 'edit') {

			$media = $model->getFile();
			$this->assignRef('media',	$media);

			$isNew = ($media->virtuemart_media_id < 1);

			$this->addStandardEditViewCommands();

        }
        else {
			$virtuemart_product_id = vRequest::getInt('virtuemart_product_id');
			if(is_array($virtuemart_product_id) && count($virtuemart_product_id) > 0){
				$virtuemart_product_id = (int)$virtuemart_product_id[0];
			} else {
				$virtuemart_product_id = (int)$virtuemart_product_id;
			}
        	$cat_id = vRequest::getInt('virtuemart_category_id',0);

			JToolBarHelper::custom('synchronizeMedia', 'new', 'new', vmText::_('COM_VIRTUEMART_TOOLS_SYNC_MEDIA_FILES'),false);
			$this->addStandardDefaultViewCommands();
			$this->addStandardDefaultViewLists($model,null,null,'searchMedia');
			$options = array( '' => vmText::_('COM_VIRTUEMART_LIST_ALL_TYPES'),
				'product' => vmText::_('COM_VIRTUEMART_PRODUCT'),
				'category' => vmText::_('COM_VIRTUEMART_CATEGORY'),
				'manufacturer' => vmText::_('COM_VIRTUEMART_MANUFACTURER'),
				'vendor' => vmText::_('COM_VIRTUEMART_VENDOR')
				);
			$this->lists['search_type'] = VmHTML::selectList('search_type', vRequest::getVar('search_type'),$options,1,'','onchange="this.form.submit();"');

			$options = array( '' => vmText::_('COM_VIRTUEMART_LIST_ALL_ROLES'),
				'file_is_displayable' => vmText::_('COM_VIRTUEMART_FORM_MEDIA_DISPLAYABLE'),
				'file_is_downloadable' => vmText::_('COM_VIRTUEMART_FORM_MEDIA_DOWNLOADABLE'),
				'file_is_forSale' => vmText::_('COM_VIRTUEMART_FORM_MEDIA_SET_FORSALE'),
				);
			$this->lists['search_role'] = VmHTML::selectList('search_role', vRequest::getVar('search_role'),$options,1,'','onchange="this.form.submit();"');

			$files = $model->getFiles(false,false,$virtuemart_product_id,$cat_id);
			$this->assignRef('files',	$files);

			$pagination = $model->getPagination();
			$this->assignRef('pagination', $pagination);

		}

		parent::display($tpl);
	}

}
// pure php no closing tag