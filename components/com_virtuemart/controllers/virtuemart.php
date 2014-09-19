<?php
/**
*
* Base controller Frontend
*
* @package		VirtueMart
* @subpackage
* @author Max Milbers
* @link http://www.virtuemart.net
* @copyright Copyright (c) 2011 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id: virtuemart.php 7821 2014-04-08 11:07:57Z Milbo $
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

// Load the controller framework
jimport('joomla.application.component.controller');

/**
 * VirtueMart Component Controller
 *
 * @package		VirtueMart
 */
class VirtueMartControllerVirtuemart extends JControllerLegacy
{

	function __construct() {
		parent::__construct();
		if (VmConfig::get('shop_is_offline') == '1') {
		    vRequest::setVar( 'layout', 'off_line' );
	    }
	    else {
		    vRequest::setVar( 'layout', 'default' );
	    }
	}

	/**
	 * Override of display to prevent caching
	 *
	 * @return  JController  A JController object to support chaining.
	 */
	public function display($cachable = false, $urlparams = false){

		$document = JFactory::getDocument();
		$viewType = $document->getType();
		$viewName = vRequest::getCmd('view', 'virtuemart');
		$viewLayout = vRequest::getCmd('layout', 'default');

		//vmdebug('basePath is NOT JPATH_VM_SITE',$this->basePath,JPATH_VM_SITE);
		$view = $this->getView($viewName, $viewType);
		$view->assignRef('document', $document);

		$view->display();

		return $this;
	}



}
 //pure php no closing tag
