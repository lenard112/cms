<?php
/**
 *
 * Paybox payment plugin
 *
 * @author Valérie Isaksen
 * @version $Id$
 * @package VirtueMart
 * @subpackage payment
 * Copyright (C) 2004-2014 Virtuemart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
 *
 * http://virtuemart.net
 */


defined('_JEXEC') or die('Restricted access');
/**
 * Copy of the vRequest File, for compatibility reqsons
 *
 *
 * @package    VirtueMart
 * @subpackage Helpers
 * @author Max Milbers
 * @copyright Copyright (c) 2004 - September 04 2014 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
 *
 * http://virtuemart.net
 */

/**
 * Class vRequest
 * Gets filtered request values.
 * @author Max Milbers
 */



class  pbxRequest {




	public static function getUword($field, $default='', $custom=''){
		$source = self::getVar($field,$default);
		return self::filterUword($source,$custom);
	}

	//static $filters = array( '' =>);
	public static function uword($field, $default='', $custom=''){
		$source = self::getVar($field,$default);
		return self::filterUword($source,$custom);
	}

	public static function filterUword($source, $custom,$replace=''){


		if(function_exists('mb_ereg_replace')){
			//$source is string that will be filtered, $custom is string that contains custom characters
			return mb_ereg_replace('[^\w'.preg_quote($custom).']', $replace, $source);
		} else {
			//return preg_replace('/[^\w'.preg_quote($custom).']/', '', $source);	//creates error Warning: preg_replace(): Unknown modifier ']'
			//return preg_replace('/([^\w'.preg_quote($custom).'])/', '', $source);	//Warning: preg_replace(): Unknown modifier ']'
			//return preg_replace("[^\w".preg_quote($custom)."]", '', $source);	//This seems to work even there is no seperator, the change is just the use of " instead '
			return preg_replace("~[^\w".preg_quote($custom,'~')."]~", $replace, $source);	//We use Tilde as separator, and give the preq_quote function the used separator
		}
	}



	public static function getBool($name, $default = 0){
		$tmp = self::get($name, $default, FILTER_SANITIZE_NUMBER_INT);
		if($tmp){
			$tmp = true;
		} else {
			$tmp = false;
		}
		return $tmp;
	}

	public static function getInt($name, $default = 0){
		return self::get($name, $default, FILTER_SANITIZE_NUMBER_INT);
	}

	public static function getFloat($name,$default=0.0){
		return self::get($name,$default,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_SCIENTIFIC|FILTER_FLAG_ALLOW_FRACTION);
	}

	/**
	 * - Strips all characters that has a numerical value <32.
	 * - Strips all html.
	 *
	 * @param $name
	 * @param null $default
	 * @return mixed|null
	 */
	public static function getVar($name, $default = null){

		return self::get($name, $default, FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW );
		//return VmConfig::$vmFilter->getVar($name, $default, $hash, $type, $mask);
	}

	/**
	 * - Strips all characters that has a numerical value <32.
	 * - encodes html
	 *
	 * @param $name
	 * @param string $default
	 * @return mixed|null
	 */
	public static function getString($name, $default = ''){
		return self::get($name, $default, FILTER_SANITIZE_SPECIAL_CHARS,FILTER_FLAG_STRIP_LOW);
	}

	public static function getHtml($name, $default = ''){
		$tmp = self::get($name, $default);
		return JComponentHelper::filterText($tmp);
	}
	/**
	 * Gets a filtered request value
	 * - Strips all characters that has a numerical value <32 and >127.
	 * - strips html
	 * @author Max Milbers
	 * @param $name
	 * @param string $default
	 * @return mixed|null
	 */

	public static function getCmd($name, $default = ''){
		return self::get($name, $default, FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH);
	}

	/**
	 * Main filter function, called by the others with set Parameters
	 * The standard filter is non restrictiv.
	 *
	 * @author Max Milbers
	 * @param $name
	 * @param null $default
	 * @param int $filter
	 * @param int $flags
	 * @return mixed|null
	 */
	public static function get($name, $default = null, $filter = FILTER_UNSAFE_RAW, $flags = FILTER_FLAG_STRIP_LOW){
		//vmSetStartTime();
		if(!empty($name)){

			if(!isset($_REQUEST[$name])) return $default;

			//if(strpos($name,'[]'!==FALSE)){
			if(is_array($_REQUEST[$name])){
				return filter_var_array($_REQUEST[$name], $filter );
			}
			else {
				return filter_var($_REQUEST[$name], $filter, $flags);
			}

		} else {
			vmTrace('empty name in pbxRequest::get');
			return $default;
		}

	}

	/**
	 * Gets the request and filters it directly. It uses the standard php function filter_var_array,
	 * The standard filter allows all chars, also the special ones. But removes dangerous html tags.
	 *
	 * @author Max Milbers
	 * @param array $filter
	 * @return mixed cleaned $_REQUEST
	 */
	public static function getRequest( ){
		return  filter_var_array($_REQUEST, FILTER_SANITIZE_STRING);
	}
	public static function getPost( ){
		return  filter_var_array($_POST, FILTER_SANITIZE_STRING);
	}
	public static function getGet( ){
		return  filter_var_array($_GET, FILTER_SANITIZE_STRING);
	}
	public static function getFiles($name){
		return  filter_var_array($_FILES[$name], FILTER_SANITIZE_STRING);
	}

	public static function setVar($name, $value = null){
		if(isset($_REQUEST[$name])){
			$tmp = $_REQUEST[$name];
			$_REQUEST[$name] = $value;
			return $tmp;
		} else {
			$_REQUEST[$name] = $value;
			return null;
		}
	}

	/**
	 * Checks for a form token in the request.
	 * Use in conjunction with JHtml::_('form.token') or JSession::getFormToken.
	 *
	 * @copyright  Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
	 * @param   string  $method  The request method in which to look for the token key.
	 *
	 * @return  boolean  True if found and valid, false otherwise.
	 *
	 * @since   12.1
	 */
	public static function vmCheckToken($redirectMsg=0){

		$token = self::getFormToken();

		if (!self::uword($token, FALSE)){

			if ($rToken = self::uword('token', FALSE)){
				if($rToken == $token){
					return true;
				}
			}

			$session = JFactory::getSession();

			if ($session->isNew()){
				// Redirect to login screen.
				$app = JFactory::getApplication();
				$app->redirect(JRoute::_('index.php'), vmText::_('JLIB_ENVIRONMENT_SESSION_EXPIRED'));
				$app->close();
			}
			else {
				if($redirectMsg===0){
					$redirectMsg = 'Invalid Token, in ' . pbxRequest::getCmd('options') .' view='.pbxRequest::getCmd('view'). ' task='.pbxRequest::getCmd('task');
					//jexit('Invalid Token, in ' . pbxRequest::getCmd('options') .' view='.pbxRequest::getCmd('view'). ' task='.pbxRequest::getCmd('task'));
				} else {
					$redirectMsg =  vmText::_($redirectMsg);
				}
				// Redirect to login screen.
				$app = JFactory::getApplication();
				$session->close();
				$app->redirect(JRoute::_('index.php'), vmText::_($redirectMsg));
				$app->close();
				return false;
			}
		}
		else {
			return false;
		}
	}

	/**
	 * Method to determine a hash for anti-spoofing variable names
	 *
	 * @copyright  Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
	 * @param   boolean  $forceNew  If true, force a new token to be created
	 *
	 * @return  string  Hashed var name
	 *
	 * @since   11.1
	 */
	public static function getFormToken($forceNew = false){

		$user = JFactory::getUser();
		$session = JFactory::getSession();
		$hash = JApplication::getHash($user->get('id', 0) . $session->getToken($forceNew));

		return $hash;
	}

}

