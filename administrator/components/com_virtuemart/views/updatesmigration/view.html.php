<?php
/**
 *
 * UpdatesMigration View
 *
 * @package	VirtueMart
 * @subpackage UpdatesMigration
 * @author Max Milbers
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
 * HTML View class for maintaining the Installation. Updating of the files and imports of the database should be done here
 *
 * @package	VirtueMart
 * @subpackage UpdatesMigration
 * @author Max Milbers
 */
class VirtuemartViewUpdatesMigration extends VmView {

	function display($tpl = null) {


		$latestVersion = vRequest::getVar('latestverison', '');

		JToolBarHelper::title(vmText::_('COM_VIRTUEMART_UPDATE_MIGRATION'), 'head vm_config_48');

		if (!class_exists('VmImage'))
			require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'image.php');
		if (!class_exists('VmHTML'))
			require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'html.php');

		$this->assignRef('checkbutton_style', $checkbutton_style);
		$this->assignRef('downloadbutton_style', $downloadbutton_style);
		$this->assignRef('latestVersion', $latestVersion);

		$freshInstall = vRequest::getInt('redirected',0);
		if($freshInstall){
			$this->setLayout('install');
		}

		parent::display($tpl);
	}

}
// pure php no closing tag
