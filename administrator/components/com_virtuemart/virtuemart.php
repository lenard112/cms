<?php
defined( '_JEXEC' ) or die( 'Restricted access' );
/**
*
* @version $Id: admin.virtuemart.php 7256 2013-09-29 18:42:44Z Milbo $
* @package VirtueMart
* @subpackage core
* @copyright Copyright (C) VirtueMart Team - All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
*
* http://virtuemart.net
*/
defined('DS') or define('DS', DIRECTORY_SEPARATOR);
if (!class_exists( 'VmConfig' )) require(JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_virtuemart'.DS.'helpers'.DS.'config.php');
VmConfig::loadConfig();

if (!class_exists( 'VmController' )) require(JPATH_COMPONENT_ADMINISTRATOR.DS.'helpers'.DS.'vmcontroller.php');
if (!class_exists( 'VmModel' )) require(JPATH_COMPONENT_ADMINISTRATOR.DS.'helpers'.DS.'vmmodel.php');


vmRam('Start');
vmSetStartTime('Start');

/*$test = array(	'test'=>"!1'2§3$4%5&6/7(8)9=0öüä und ß?\x80 bzw new &#10 line \x12 new line?\x80",
				'test2' => 'und mal ein script<script>Mein scriptinhalt</script>',
				'test3' =>'Ne runde Russisch рождения',
				'test4' => '<a>harmloses</a> <b>html</b>'
);

$testString = $test['test'].$test['test2'].$test['test3'].$test['test4'];
vRequest::setVar('test',$testString);

$tmp = vRequest::getVar('test');
vmdebug('my getVar ',$tmp);

$tmp = vRequest::getString('test');
vmdebug('my getString ',$tmp);
//$output = filter_var($testString,FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
/*vmdebug('Filter test UNFILTERED ',$test);
$filter = array( 'filter' =>FILTER_SANITIZE_STRING,
				 'flags' => FILTER_FLAG_ENCODE_LOW,
	);
$output = filter_var_array($test,FILTER_SANITIZE_STRING);



$output = filter_var($testString,FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
vmdebug('Filter test FILTER_UNSAFE_RAW ',$output);

//vmdebug('Filter filter_var_array FILTER_SANITIZE_STRING FILTER_FLAG_STRIP_LOW',$output);
//Ich brauche 3 Filter, RAW mit strip low,
//Normal => erlaube alles, bis auf komisches => FILTER_FLAG_STRIP_LOW IMMER


//$output = filter_var($testString,FILTER_SANITIZE_ENCODED,FILTER_FLAG_STRIP_LOW);
//vmdebug('Filter test FILTER_SANITIZE_ENCODED ',$output);

$output = filter_var($testString,FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW );
vmdebug('Filter test FILTER_SANITIZE_STRING ',$output);

$output = filter_var($testString,FILTER_SANITIZE_SPECIAL_CHARS);
vmdebug('Filter test FILTER_SANITIZE_SPECIAL_CHARS ',$output);
*/

VmConfig::loadJLang('com_virtuemart');

vmJsApi::jQuery(0);
vmJsApi::jSite();

// check for permission Only vendor and Admin can use VM2 BE
// this makes trouble somehow, we need to check if the perm object works not too strict maybe

if(!VmConfig::isSuperVendor()){
	$app = JFactory::getApplication();
	vmError( 'Access restricted to Vendor and Administrator only (you are admin and should not see this messsage?)','Access restricted to Vendors and Administrator only' );
	$app->redirect('index.php');
}

// Require specific controller if requested
if($_controller = vRequest::getCmd('view', vRequest::getCmd('controller', 'virtuemart'))) {
	if (file_exists(JPATH_VM_ADMINISTRATOR.DS.'controllers'.DS.$_controller.'.php')) {
		// Only if the file exists, since it might be a Joomla view we're requesting...
		require (JPATH_VM_ADMINISTRATOR.DS.'controllers'.DS.$_controller.'.php');
	} else {
		// try plugins
		JPluginHelper::importPlugin('vmextended');
		$dispatcher = JDispatcher::getInstance();
		$results = $dispatcher->trigger('onVmAdminController', array($_controller));
		if (empty($results)) {
			$app = JFactory::getApplication();
			$app->enqueueMessage('Fatal Error in maincontroller admin.virtuemart.php: Couldnt find file '.$_controller);
			$app->redirect('index.php?option=com_virtuemart');
		}
	}
}

// Create the controller
$_class = 'VirtueMartController'.ucfirst($_controller);
$controller = new $_class();

// Perform the Request task
$controller->execute(vRequest::getCmd('task', $_controller));

vmTime($_class.' Finished task '.$_controller,'Start');
vmRam('End');
vmRamPeak('Peak');
$controller->redirect();

// pure php no closing tag