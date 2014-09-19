<?php
if( !defined( '_JEXEC' ) ) die( 'Direct Access to '.basename(__FILE__).' is not allowed.' );
/**
*
* @version $Id: virtuemart.php 8263 2014-09-02 06:49:26Z Milbo $
* @package VirtueMart
* @subpackage core
* @author Max Milbers
* @copyright Copyright (C) 2009-11 by the authors of the VirtueMart Team listed at /administrator/com_virtuemart/copyright.php - All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
*
* http://virtuemart.net
*/

/* Require the config */
defined('DS') or define('DS', DIRECTORY_SEPARATOR);
if (!class_exists( 'VmConfig' )) require(JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_virtuemart'.DS.'helpers'.DS.'config.php');

VmConfig::loadConfig();

vmRam('Start');
vmSetStartTime('Start');

VmConfig::loadJLang('com_virtuemart', true);


if(VmConfig::get('shop_is_offline',0)){
	//$cache->setCaching (1);
	$_controller = 'virtuemart';
	require (JPATH_VM_SITE.DS.'controllers'.DS.'virtuemart.php');
	vRequest::setVar('view', 'virtuemart');
	$task='';
	$basePath = JPATH_VM_SITE;
} else {

	//$cache->setCaching (0);

	/* Front-end helpers */
	if(!class_exists('VmImage')) require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'image.php'); //dont remove that file it is actually in every view except the state view
	if(!class_exists('shopFunctionsF'))require(JPATH_VM_SITE.DS.'helpers'.DS.'shopfunctionsf.php'); //dont remove that file it is actually in every view

	$_controller = vRequest::getCmd('view', vRequest::getCmd('controller', 'virtuemart')) ;
	$trigger = 'onVmSiteController';
// 	$task = vRequest::getCmd('task',vRequest::getCmd('layout',$_controller) );		$this makes trouble!
	$task = vRequest::getCmd('task','') ;
	vmdebug('Our request ',$_REQUEST);
	if($manage = vRequest::getCmd('manage',false) or ( ($_controller == 'product' or $_controller == 'category') and ($task == 'save' || $task == 'edit' or $task == 'apply' ) ) or ($_controller == 'translate' && $task='paste') ){
	//if ((($_controller == 'product' || $_controller == 'category') && ($task == 'save' || $task == 'edit')) || ($_controller == 'translate' && $task='paste') ) {
		$app = JFactory::getApplication();

		$user = JFactory::getUser();
		$vendorIdUser = VmConfig::isSuperVendor();

		if	($vendorIdUser) {
			VmConfig::loadJLang('com_virtuemart');
			$jlang = JFactory::getLanguage();
			$tag = $jlang->getTag();
			$jlang->load('', JPATH_ADMINISTRATOR,$tag,true);
			VmConfig::loadJLang('com_virtuemart');
			$basePath = JPATH_VM_ADMINISTRATOR;
			$trigger = 'onVmAdminController';
			vmdebug('$vendorIdUser use FE managing '.$vendorIdUser);
			vRequest::setVar('manage','1');

			vmJsApi::jQuery(false);
			vmJsApi::loadBECSS();

			$app = JFactory::getApplication();
			$router = $app->getRouter();
			$router->setMode(0);
			//vmdebug('my conf',$router->getMode());
			//JFactory::getConfig()->set('sef',0);
		} else {
			$app->redirect('index.php?option=com_virtuemart', vmText::_('COM_VIRTUEMART_RESTRICTED_ACCESS') );
		}
		vRequest::setVar('tmpl','component') ;
	} elseif($_controller) {
			vmJsApi::jQuery();
			vmJsApi::jSite();
			vmJsApi::cssSite();
			$basePath = JPATH_VM_SITE;
	}
}


/* Create the controller name */
$_class = 'VirtuemartController'.ucfirst($_controller);

if (file_exists($basePath.DS.'controllers'.DS.$_controller.'.php')) {
	if (!class_exists($_class)) {
		require ($basePath.DS.'controllers'.DS.$_controller.'.php');
	}
}
else {
	// try plugins
	JPluginHelper::importPlugin('vmextended');
	$dispatcher = JDispatcher::getInstance();
	$dispatcher->trigger($trigger, array($_controller));
}


if (class_exists($_class)) {
    $controller = new $_class();

    /* Perform the Request task */
    $controller->execute($task);

    //vmTime($_class.' Finished task '.$task,'Start');
    vmRam('End');
    vmRamPeak('Peak');

    /* Redirect if set by the controller */
    $controller->redirect();
} else {
    vmDebug('VirtueMart controller not found: '. $_class);
    if (VmConfig::get('handle_404',1)) {
    	$mainframe = Jfactory::getApplication();
    	$mainframe->redirect(JRoute::_ ('index.php?option=com_virtuemart&view=virtuemart', FALSE));
    } else {
    	JError::raise(E_ERROR,'404','Not found');
    }
}
