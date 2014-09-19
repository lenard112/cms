<?php

defined ('_JEXEC') or die('Restricted access');
/**
 * abstract class for payment plugins
 *
 * @package    VirtueMart
 * @subpackage Plugins
 * @author Valérie Isaksen
 * @link http://www.virtuemart.net
 * @copyright Copyright (c) 2004 - 2011 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: vmplugin.php 4599 2011-11-02 18:29:04Z alatak $
 */

if (!class_exists( 'VmConfig' )) {
	if(file_exists(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_virtuemart'.DS.'helpers'.DS.'config.php')){
		require(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_virtuemart'.DS.'helpers'.DS.'config.php');
	} else {
		echo 'Install VirtueMart first'; return;
	}
}

// Load the helper functions that are needed by all plugins
if (!class_exists ('ShopFunctions')) {
	require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'shopfunctions.php');
}
// if (!class_exists('DbScheme'))
// require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'dbscheme.php');
// Get the plugin library
jimport ('joomla.plugin.plugin');

abstract class vmPlugin extends JPlugin {

	// var Must be overriden in every plugin file by adding this code to the constructor:
	// $this->_name = basename(__FILE, '.php');
	// just as note: protected can be accessed only within the class itself and by inherited and parent classes
	//This is normal name of the plugin family, custom, payment
	protected $_psType = 0;
	//Id of the joomla table where the plugins are registered
	protected $_jid = 0;
	protected $_vmpItable = 0;
	//the name of the table to store plugin internal data, like payment logs
	protected $_tablename = 0;
	protected $_tableId = 'id';
	//Name of the primary key of this table, for exampel virtuemart_calc_id or virtuemart_order_id
	protected $_tablepkey = 0;
	protected $_vmpCtable = 0;
	//the name of the table which holds the configuration like paymentmethods, shipmentmethods, customs
	protected $_configTable = 0;
	protected $_configTableFileName = 0;
	protected $_configTableClassName = 0;
	protected $_xParams = 0;
	protected $_varsToPushParam = array();
	//id field of the config table
	protected $_idName = 0;
	//Name of the field in the configtable, which holds the parameters of the pluginmethod
	protected $_configTableFieldName = 0;
	protected $_debug = FALSE;
	protected $_loggable = FALSE;
	protected $_cryptedFields = false;

	/**
	 * Constructor
	 *
	 * @param object $subject The object to observe
	 * @param array  $config  An array that holds the plugin configuration
	 * @since 1.5
	 */
	function __construct (& $subject, $config) {

		parent::__construct ($subject, $config);

		$this->_psType = substr ($this->_type, 2);

		$filename = 'plg_' . $this->_type . '_' . $this->_name;

		$this->loadJLang($filename);

		$this->_tablename = '#__virtuemart_' . $this->_psType . '_plg_' . $this->_name;
		$this->_tableChecked = FALSE;
		$this->_xmlFile	= JPath::clean( JPATH_ROOT .DS. 'plugins' .DS. $this->_type .DS.  $this->_name . DS. $this->_name . '.xml');

	}

	public function loadJLang($fname,$type=0,$name=0){

		$jlang =JFactory::getLanguage();
		$tag = $jlang->getTag();

		if(empty($type)) $type = $this->_type;
		if(empty($name)) $name = $this->_name;
		$path = $basePath = JPATH_ROOT .DS. 'plugins' .DS.$type.DS.$name;

		if(VmConfig::get('enableEnglish', true) and $tag!='en-GB'){
			$testpath = $basePath.DS.'language'.DS.'en-GB'.DS.'en-GB.'.$fname.'.ini';
			if(!file_exists($testpath)){
				$epath = JPATH_ADMINISTRATOR;
			} else {
				$epath = $path;
			}
			$jlang->load($fname, $epath, 'en-GB');
		}

		$testpath = $basePath.DS.'language'.DS.$tag.DS.$tag.'.'.$fname.'.ini';
		if(!file_exists($testpath)){
			$path = JPATH_ADMINISTRATOR;
		}

		$jlang->load($fname, $path,$tag,true);
	}

	function setPluginLoggable($set=TRUE){
		$this->_loggable = $set;
	}

	function setCryptedFields($fieldNames){
		$this->_cryptedFields = $fieldNames;
	}

	/**
	 * @return array
	 */
	function getTableSQLFields () {

		return false;
	}

	function getOwnUrl(){

		$url = '/plugins/'.$this->_type.'/'.$this->_name;
		return $url;
	}

	function display3rdInfo($intro,$developer,$contactlink,$manlink){
		$logolink = $this->getOwnUrl() ;
		return shopfunctions::display3rdInfo($this->_name,$intro,$developer,$logolink,$contactlink,$manlink);
	}

	/**
	 * This function gets the parameters of a plugin by an xml file.
	 * This is used for the configuration GUI in the BE.
	 * Attention: the xml Params must be always a subset of the varsToPushParams declared in the constructor
	 * @param $xmlFile
	 * @param $name
	 * @return array
	 */
	static public function getVarsToPushByXML ($xmlFile,$name){
		$data = array();

		if (is_file ( $xmlFile )) {

			//$xml = JFactory::getXML ('simple');
			//$result = $xml->loadFile ($xmlFile);
			$xml =  JFactory::getXML($xmlFile);
			if ($xml) {
				if (isset( $xml->document->params) ){

					$params = $xml->document->params;
					foreach ($params as $param) {
						if ($param->_name = "params") {
							if ($children = $param->_children) {
								foreach ($children as $child) {
									if (!empty($child->_attributes['name'])) {
										$fieldname = (string)$child->_attributes['name'];
										$private = false;
										if(strlen($fieldname)>1){
											if(substr($fieldname,0,2)=='__'){
												$private = true;
											}
										}

										if(!$private){
											$data[$fieldname] = array('', 'char');
										}
									}
								}
							}
						}
					}
				} else {

					$form = JForm::getInstance($name, $xmlFile, array(),false, '//vmconfig | //config[not(//vmconfig)]');
					$fieldSets = $form->getFieldsets();
					foreach ($fieldSets as $name => $fieldSet) {
						foreach ($form->getFieldset($name) as $field) {

							$fieldname = (string)$field->fieldname;
							$private = false;

							if(strlen($fieldname)>1){
								if(substr($fieldname,0,2)=='__'){
									$private = true;
								}
							}

							if(!$private){
								$type='char';
								$data[$fieldname] = array('',  $type);
							}

						}
					}
				}
			}
		}

		return $data;
	}

	/**
	 * Checks if this plugin should be active by the trigger
	 *
	 * @author Max Milbers
	 * @param string $psType shipment,payment,custom
	 * @param        string the name of the plugin for example textinput, paypal
	 * @param        int/array $jid the registered plugin id(s) of the joomla table
	 */
	protected function selectedThis ($psType, $name = 0, $jid = 0) {

		if ($psType !== 0) {
			if ($psType != $this->_psType) {
				vmdebug ('selectedThis $psType does not fit');
				return FALSE;
			}
		}

		if ($name !== 0) {
			if ($name != $this->_name) {
				//vmdebug ('selectedThis $name ' . $name . ' does not fit pluginname ' . $this->_name);
				return FALSE;
			}
		}

		if ($jid === 0) {
			return FALSE;
		}
		else {
			if ($this->_jid === 0) {
				$this->getJoomlaPluginId ();
			}
			if (is_array ($jid)) {
				if (!in_array ($this->_jid, $jid)) {
					vmdebug ('selectedThis id ' . $jid . ' not in array does not fit ' . $this->_jid);
					return FALSE;
				}
			}
			else {
				if ($jid != $this->_jid) {
					vmdebug ('selectedThis $jid ' . $jid . ' does not fit ' . $this->_jid);
					return FALSE;
				}
			}
		}

		return TRUE;
	}

	/**
	 * Checks if this plugin should be active by the trigger
	 *
	 * We should avoid this function, is expensive
	 *
	 * @author Max Milbers
	 * @author Valérie Isaksen
	 *
	 * @param int/array $id the registered plugin id(s) of the joomla table
	 */
	function selectedThisByMethodId ($id = 'type') {

		//if($psType!=$this->_psType) return false;

		if ($id === 'type') {
			return TRUE;
		}
		else {
			$db = JFactory::getDBO ();

			$q = 'SELECT vm.* FROM `' . $this->_configTable . '` AS vm,
						#__extensions AS j WHERE vm.`' . $this->_idName . '` = "' . $id . '"
						AND vm.' . $this->_psType . '_jplugin_id = j.extension_id
						AND j.element = "' . $this->_name . '"';

			$db->setQuery ($q);
			if (!$res = $db->loadObject ()) {
				// 				vmError('selectedThisByMethodId '.$db->getQuery());
				return FALSE;
			}
			else {
				return $res;
			}
		}
	}

	/**
	 * Checks if this plugin should be active by the trigger
	 *
	 * @author Max Milbers
	 * @author Valérie Isaksen
	 * @param int/array $jplugin_id the registered plugin id(s) of the joomla table
	 */
	protected function selectedThisByJPluginId ($jplugin_id = 'type') {

		if ($jplugin_id === 'type') {
			return TRUE;
		}
		else {
			$db = JFactory::getDBO ();

			$q = 'SELECT vm.* FROM `' . $this->_configTable . '` AS vm,
						#__extensions AS j WHERE vm.`' . $this->_psType . '_jplugin_id`  = "' . $jplugin_id . '"
						AND vm.`' . $this->_psType . '_jplugin_id` = j.extension_id
						AND j.`element` = "' . $this->_name . '"';

			$db->setQuery ($q);
			if (!$res = $db->loadObject ()) {
				// 				vmError('selectedThisByMethodId '.$db->getQuery());
				return FALSE;
			}
			else {
				return $res;
			}
		}
	}

	/**
	 * Gets the id of the joomla table where the plugin is registered
	 *
	 * @author Max Milbers
	 */
	final protected function getJoomlaPluginId () {

		if (!empty($this->_jid)) {
			return $this->_jid;
		}
		$db = JFactory::getDBO ();

		$q = 'SELECT j.`extension_id` AS c FROM #__extensions AS j
					WHERE j.element = "' . $this->_name . '" AND j.`folder` = "' . $this->_type . '"';

		$db->setQuery ($q);
		$this->_jid = $db->loadResult ();
		if (!$this->_jid) {
			vmError ('getJoomlaPluginId ' . $db->getErrorMsg ());
			return FALSE;
		}
		else {
			return $this->_jid;
		}
	}

	/**
	 * Create the table for this plugin if it does not yet exist.
	 * Or updates the table, if it exists. Please be aware that this function is slowing and is only called
	 * storing a method or installing/udpating a plugin.
	 *
	 * @param string $psType shipment,payment,custom
	 * @author Valérie Isaksen
	 * @author Max Milbers
	 */
	public function onStoreInstallPluginTable ($psType,$name=FALSE) {

		if(!empty($name) and $name!=$this->_name){
			return false;
		}

		//Todo the psType should be name of the plugin.
		if ($psType == $this->_psType) {

			$SQLfields = $this->getTableSQLFields();
			if(empty($SQLfields)) return false;

			$loggablefields = $this->getTableSQLLoggablefields();
			$tablesFields = array_merge($SQLfields, $loggablefields);

			$db = JFactory::getDBO();
			$query = 'SHOW TABLES LIKE "%' . str_replace('#__', $db->getPrefix(), $this->_tablename) . '"';
			$db->setQuery($query);
			$result = $db->loadResult();

			if ($result) {
				$update[$this->_tablename] = array($tablesFields, array(), array());
				$app = JFactory::getApplication();
				$app->enqueueMessage(get_class($this) . ':: VirtueMart2 update ' . $this->_tablename);
				if (!class_exists('GenericTableUpdater'))
					require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'tableupdater.php');
				$updater = new GenericTableUpdater();
				$updater->updateMyVmTables($update);
			} else {
				$query = $this->createTableSQL($name,$tablesFields);
				if(empty($query)){
					return false;
				} else {
					$db->setQuery ($query);
					if (!$db->execute ()) {
						vmWarn($this->_name . '::onStoreInstallPluginTable: ' . vmText::_ ('COM_VIRTUEMART_SQL_ERROR') . ' ' . $db->stderr (TRUE));
						echo $this->_name . '::onStoreInstallPluginTable: ' . vmText::_ ('COM_VIRTUEMART_SQL_ERROR') . ' ' . $db->stderr (TRUE);
					} else {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * adds loggable fields to the table
	 *
	 * @return array
	 */
	function getTableSQLLoggablefields () {
		return array(
			'created_on'  => 'datetime NOT NULL default \'0000-00-00 00:00:00\'',
			'created_by'  => "int(11) NOT NULL DEFAULT '0'",
			'modified_on' => 'datetime NOT NULL DEFAULT \'0000-00-00 00:00:00\'',
			'modified_by' => "int(11) NOT NULL DEFAULT '0'",
			'locked_on'   => 'datetime NOT NULL DEFAULT \'0000-00-00 00:00:00\'',
			'locked_by'   => 'int(11) NOT NULL DEFAULT \'0\''
		);
	}

	/**
	 * @param $tableComment
	 * @return string
	 */
	protected function createTableSQL ($tableComment,$tablesFields=0) {

		$query = "CREATE TABLE IF NOT EXISTS `" . $this->_tablename . "` (";
		if(!empty($tablesFields)){
			foreach ($tablesFields as $fieldname => $fieldtype) {
				$query .= '`' . $fieldname . '` ' . $fieldtype . " , ";
			}
		} else {
			$SQLfields = $this->getTableSQLFields ();
			$loggablefields = $this->getTableSQLLoggablefields ();
			foreach ($SQLfields as $fieldname => $fieldtype) {
				$query .= '`' . $fieldname . '` ' . $fieldtype . " , ";
			}
			foreach ($loggablefields as $fieldname => $fieldtype) {
				$query .= '`' . $fieldname . '` ' . $fieldtype . ", ";
			}
		}

		$query .= "	      PRIMARY KEY (`id`)
	    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='" . $tableComment . "' AUTO_INCREMENT=1 ;";
		return $query;
	}

	/**
	 * Set with this function the provided plugin parameters
	 *
	 * @param string $paramsFieldName
	 * @param array  $varsToPushParam
	 */
	function setConfigParameterable ($paramsFieldName, $varsToPushParam) {
		$this->_xParams = $paramsFieldName;
		$this->_varsToPushParam = $varsToPushParam;
	}

	/**
	 *
	 * @param $psType
	 * @param $name
	 * @param $id
	 * @param $xParams
	 * @param $varsToPush
	 * @return bool
	 */
	protected function getTablePluginParams ($psType,$name, $id, &$xParams,&$varsToPush) {
		//vmdebug('getTablePluginParams $this->_psType '.$this->_psType.' sets $psType '.$psType.' $name',$name);
		if (!empty($this->_psType) and !$this->selectedThis ($psType, $name, $id)) {
			return FALSE;
		}

		$varsToPush = $this->_varsToPushParam;
		$xParams = $this->_xParams;
		//vmdebug('getTablePluginParams '.$name.' sets xParams '.$xParams.' vars',$varsToPush);
	}

	/**
	 * @param $name
	 * @param $id
	 * @param $table
	 * @return bool
	 */
	protected function setOnTablePluginParams ($name, $id, &$table) {

		//Todo I think a test on this is wrong here
		//Adjusted it like already done in declarePluginParams
		if (!empty($this->_psType) and !$this->selectedThis ($this->_psType, $name, $id)) {
			return FALSE;
		}
		else {
			if($this->_cryptedFields){
				$table->setCryptedFields($this->_cryptedFields);
			}

			$table->setParameterable ($this->_xParams, $this->_varsToPushParam);
			return TRUE;
		}

	}

	/**
	 * Does VmTable::bindParameterable and setCryptedFields $name, $id, $data
	 * @param $psType
	 * @param $data
	 * @return bool
	 */
	protected function declarePluginParams ($psType, &$data, $blind=0, $blind2=0) {

		if(!empty($this->_psType)){
			$element = $this->_psType.'_element';
			$jplugin_id = $this->_psType.'_jplugin_id';
			//vmdebug('declarePluginParams ',$this->_psType,$data->$element,$data->$jplugin_id);
			if(!isset($data->$element) or !isset($data->$jplugin_id)) return FALSE;
			if(!$this->selectedThis($psType,$data->$element,$data->$jplugin_id)){
				return FALSE;
			}
		}

		if (!class_exists ('VmTable')) {
			require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'vmtable.php');
		}

		//New Vm3 way

		//Is only used for the config tables!
		//VmTable::bindParameterable ($data, $data->_xParams, $this->_varsToPushParam);
		if(isset($this->_varsToPushParam)){
			if(isset($data->_varsToPushParam)){
				$data->_varsToPushParam = array_merge((array)$data->_varsToPushParam, (array)$this->_varsToPushParam);
			} else {
				$data->_varsToPushParam = (array)$this->_varsToPushParam;
			}
		} else{
			vmdebug('no vars to push?',$this);
		}

		if($this->_cryptedFields){
			$data->setCryptedFields($this->_cryptedFields);
		}

		return TRUE;

	}

	/**
	 * @param $int
	 * @return mixed
	 */
	protected function getVmPluginMethod ($int, $cache = true) {

		if ($this->_vmpCtable === 0 || !$cache) {
			$db = JFactory::getDBO ();

			if (!class_exists ($this->_configTableClassName)) {
				require(JPATH_VM_ADMINISTRATOR . DS . 'tables' . DS . $this->_configTableFileName . '.php');
			}
			$this->_vmpCtable = new $this->_configTableClassName($db);
			if ($this->_xParams !== 0) {
				$this->_vmpCtable->setParameterable ($this->_configTableFieldName, $this->_varsToPushParam);
			}
			if($this->_cryptedFields){
				$this->_vmpCtable->setCryptedFields($this->_cryptedFields);
			}
		}

		return $this->_vmpCtable->load ($int);
	}

	/**
	 * This stores the data of the plugin, attention NOT the configuration of the pluginmethod,
	 * this function should never be triggered only called from triggered functions.
	 *
	 * @author Max Milbers
	 * @param array  $values array or object with the data to store
     * @param int|string $primaryKey
     * @param int|string $id
     * @param boolean $preload
     * @return array
	 */
	protected function storePluginInternalData (&$values, $primaryKey = 0, $id = 0, $preload = FALSE) {

		if ($primaryKey === 0) {
			$primaryKey = $this->_tablepkey;
		}
		if ($this->_vmpItable === 0 or $id==0) {
			$this->_vmpItable = $this->createPluginTableObject ($this->_tablename, $this->tableFields, $primaryKey, $this->_tableId, $this->_loggable);
		}

		$this->_vmpItable->bindChecknStore ($values, $preload);
		//vmdebug('storePluginInternalData',$values,$this->_vmpItable);
		$errors = $this->_vmpItable->getErrors ();
		if (!empty($errors)) {
			foreach ($errors as $error) {
				vmError ($error);
			}
		}
		return $values;
	}

	/**
	 * This loads the data stored by the plugin before, NOT the configuration of the method,
	 * this function should never be triggered only called from triggered functions.
	 *
	 * @param int    $id
	 * @param string $primaryKey
	 */
	protected function getPluginInternalData ($id, $primaryKey = 0) {

		if ($primaryKey === 0) {
			$primaryKey = $this->_tablepkey;
		}
		if ($this->_vmpItable === 0) {
			$this->_vmpItable = $this->createPluginTableObject ($this->_tablename, $this->tableFields, $primaryKey, $this->_tableId, $this->_loggable);
		}
		// 		vmdebug('getPluginInternalData $id '.$id.' and $primaryKey '.$primaryKey);
		return $this->_vmpItable->load ($id);
	}

	/**
	 * @param      $tableName
	 * @param      $tableFields
	 * @param      $primaryKey
	 * @param      $tableId
	 * @param bool $loggable
	 * @return VmTableData
	 */
	protected function createPluginTableObject ($tableName, $tableFields, $primaryKey, $tableId, $loggable = FALSE) {

		if (!class_exists ('VmTableData')) {
			require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'vmtabledata.php');
		}
		$db = JFactory::getDBO ();
		$table = new VmTableData($tableName, $tableId, $db);
		foreach ($tableFields as $field) {
			$table->$field = 0;
		}

		if ($primaryKey !== 0) {
			$table->setPrimaryKey ($primaryKey);
		}
		if ($loggable) {
			$table->setLoggable ();
		}

		if($this->_cryptedFields){
			$this->_vmpCtable->setCryptedFields($this->_cryptedFields);
		}

		/*if (!$this->_tableChecked) {
			$this->onStoreInstallPluginTable ($this->_psType);
			$this->_tableChecked = TRUE;
		}*/

		return $table;
	}

	/**
	 * @param     $id
	 * @param int $primaryKey
	 * @return mixed
	 */
	protected function removePluginInternalData ($id, $primaryKey = 0) {
		if ($primaryKey === 0) {
			$primaryKey = $this->_tablepkey;
		}
		if ($this->_vmpItable === 0) {
			$this->_vmpItable = $this->createPluginTableObject ($this->_tablename, $this->tableFields, $primaryKey, $this->_tableId, $this->_loggable);
		}
		vmdebug ('removePluginInternalData $id ' . $id . ' and $primaryKey ' . $primaryKey);
		return $this->_vmpItable->delete ($id);
	}

	/**
	 * Get the path to a layout for a type
	 *
	 * @param   string  $type  The name of the type
	 * @param   string  $layout  The name of the type layout. If alternative
	 *                           layout, in the form template:filename.
	 * @param   array   $viewData  The data you want to use in the layout
	 *                           can be an object/array/string... to reuse in the template
	 * @return  string  The path to the type layout
	 * original from libraries\joomla\application\module\helper.php
	 * @since   11.1
	 * @author Patrick Kohl, Valérie Isaksen, Max Milbers
	 */
	public function renderByLayout ($layout = 'default', $viewData = NULL, $name = NULL, $psType = NULL) {
		if ($name === NULL) {
			$name = $this->_name;
		}

		if ($psType === NULL) {
			$psType = 'vm'.$this->_psType;
		}

		$app = JFactory::getApplication ();
		$templatePath = JPATH_SITE . DS . 'templates' . DS . $app->getTemplate () . DS . 'html' . DS . $psType . DS . $name . DS . $layout . '.php';
		$defaultPath = JPATH_SITE . DS . 'plugins' . DS . $psType . DS . $name . DS . $name . DS . 'tmpl' . DS . $layout . '.php';

		$layout = false;
		if(!class_exists('JFile')) require(JPATH_VM_LIBRARIES.DS.'joomla'.DS.'filesystem'.DS.'file.php');
		if (JFile::exists ($templatePath)) {
			$layout = $templatePath;
		} else if (JFile::exists ($defaultPath)) {
			$layout = $defaultPath;
		}

		if($layout){
			ob_start ();
			include ($layout);
			return ob_get_clean ();
		} else {
			vmdebug('renderByLayout: layout '.$layout.'not found '.$psType. ' '.$name.' default path '.$defaultPath);
		}

	}


}
