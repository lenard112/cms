<?php
/**
 *
 * Data module for shop configuration
 *
 * @package	VirtueMart
 * @subpackage Config
 * @author Max Milbers
 * @author RickG
 * @link http://www.virtuemart.net
 * @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: config.php 8124 2014-07-09 13:17:21Z Milbo $
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

/**
 * Model class for shop configuration
 *
 * @package	VirtueMart
 * @subpackage Config
 * @author Max Milbers
 * @author RickG
 */
class VirtueMartModelConfig extends VmModel {

	function __construct() {
		parent::__construct();
		//$this->setMainTable('configs');
		$this->_cidName = 'id';

	}

	function getFieldList($fieldname){

		$dirs[] = JPATH_ROOT.DS.'components'.DS.'com_virtuemart'.DS.'sublayouts';

		$q = 'SELECT `template` FROM `#__template_styles` WHERE `client_id` ="0" AND `home`="1" ';

		$db = JFactory::getDBO();
		$db->setQuery($q);

		$tplnames = $db->loadResult();
		if($tplnames){
			if(is_dir(JPATH_ROOT.DS.'templates'.DS.$tplnames.DS.'html'.DS.'com_virtuemart'.DS.'sublayouts')){
				$dirs[] = JPATH_ROOT.DS.'templates'.DS.$tplnames.DS.'html'.DS.'com_virtuemart'.DS.'sublayouts';
			}
		}
		return self::getLayouts($dirs,$fieldname.'_');
	}

	/**
	 * Retrieve a list of layouts from the default and chosen templates directory.
	 *
	 * @author Max Milbers
	 * @param name of the view
	 * @return object List of flypage objects
	 */
	static function getLayoutList($view) {

		$dirs = array();
		$dirs[] = JPATH_ROOT.DS.'components'.DS.'com_virtuemart'.DS.'views'.DS.$view.DS.'tmpl';

		$q = 'SELECT `template` FROM `#__template_styles` WHERE `client_id` ="0" AND `home`="1" ';

		$db = JFactory::getDBO();
		$db->setQuery($q);

		$tplnames = $db->loadResult();
		if($tplnames){
			if(is_dir(JPATH_ROOT.DS.'templates'.DS.$tplnames.DS.'html'.DS.'com_virtuemart'.DS.$view)){
				$dirs[] = JPATH_ROOT.DS.'templates'.DS.$tplnames.DS.'html'.DS.'com_virtuemart'.DS.$view;
			}
		}
		return self::getLayouts($dirs);
	}

	static function getLayouts($dirs,$type=0){

		$result = array();
		$emptyOption = JHtml::_('select.option', '0', vmText::_('COM_VIRTUEMART_ADMIN_CFG_NO_OVERRIDE'));
		$result[] = $emptyOption;

		$alreadyAddedFile = array();
		foreach($dirs as $dir){

			if ($handle = opendir($dir)) {
				while (false !== ($file = readdir($handle))) {
					if(!empty($file) and strpos($file,'.')!==0 and $file != 'index.html' and !is_Dir($file)){

						if( (!empty($type) and strpos($file,$type)===0) or (empty($type) and strpos($file,'_')==0) ){
							//Handling directly for extension is much cleaner
							$path_info = pathinfo($file);
							if(empty($path_info['extension'])){
								vmError('Attention file '.$file.' has no extension and directory '.$dir);
								$path_info['extension'] = '';
							}
							if ($path_info['extension'] == 'php' && !in_array($file,$alreadyAddedFile)) {
								$alreadyAddedFile[] = $file;
								$result[] = JHtml::_('select.option', $path_info['filename'], $path_info['filename']);
							}
						}

					}
				}
			}
		}
		return $result;
	}


	/**
	 * Retrieve a list of available fonts to be used with PDF Invoice generation & PDF Product view on FE
	 *
	 * @author Nikos Zagas
	 * @return object List of available fonts
	 */
	function getTCPDFFontsList() {

		$dir = JPATH_ROOT.DS.'libraries'.DS.'tcpdf'.DS.'fonts';
		$result = array();
		if(function_exists('glob')){
			$specfiles = glob($dir.DS."*_specs.xml");
			foreach ($specfiles as $file) {
				$fontxml = @simpleXML_load_file($file);
				if ($fontxml) {
					if (file_exists($dir . DS . $fontxml->filename . '.php')) {
						$result[] = JHtml::_('select.option', $fontxml->filename, vmText::_($fontxml->fontname.' ('.$fontxml->fonttype.')'));
					} else {
						vmError ('A font master file is missing: ' . $dir . DS . 	$fontxml->filename . '.php');
					}
				} else {
					vmError ('Wrong structure in font XML file: '. $dir . DS . $file);
				}
			}
		}

		return $result;
	}


	/**
	 * Retrieve a list of possible images to be used for the 'no image' image.
	 *
	 * @author RickG
	 * @author Max Milbers
	 * @return object List of image objects
	 */
	function getNoImageList() {

		//TODO set config value here
		$dirs[] = JPATH_ROOT.DS.'components'.DS.'com_virtuemart'.DS.'assets'.DS.'images'.DS.'vmgeneral';

		$tplpath = VmConfig::get('vmtemplate',0);
		if(!empty($tplpath) and is_numeric($tplpath)){
			$db = JFactory::getDbo();
			$query = 'SELECT `template`,`params` FROM `#__template_styles` WHERE `id`="'.$tplpath.'" ';
			$db->setQuery($query);
			$res = $db->loadAssoc();
			if($res){
				$registry = new JRegistry;
				$registry->loadString($res['params']);
				$tplpath = $res['template'];
			}
		}
		if($tplpath){
			if(is_dir(JPATH_ROOT.DS.'templates'.DS.$tplpath.DS.'images'.DS.'vmgeneral')){
				$dirs[] = JPATH_ROOT.DS.'templates'.DS.$tplpath.DS.'images'.DS.'vmgeneral';
			}
		}

		$result = '';

		foreach($dirs as $dir){
			if ($handle = opendir($dir)) {
				while (false !== ($file = readdir($handle))) {
					if ($file != "." && $file != ".." && $file != '.svn' && $file != 'index.html') {
						if (filetype($dir.DS.$file) != 'dir') {
							$result[] = JHtml::_('select.option', $file, vmText::_(str_replace('.php', '', $file)));
						}
					}
				}
			}
		}
		return $result;
	}


	/**
	 * Retrieve a list of currency converter modules from the plugins directory.
	 *
	 * @author RickG
	 * @return object List of theme objects
	 */
	function getCurrencyConverterList() {
		$dir = JPATH_VM_ADMINISTRATOR.DS.'plugins'.DS.'currency_converter';
		$result = '';

		if ($handle = opendir($dir)) {
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != ".." && $file != '.svn') {
					$info = pathinfo($file);
					if ((filetype($dir.DS.$file) == 'file') && ($info['extension'] == 'php')) {
						$result[] = JHtml::_('select.option', $file, vmText::_($file));
					}
				}
			}
		}

		return $result;
	}


	/**
	 * Retrieve a list of Joomla content items.
	 *
	 * @author RickG
	 * @return object List of content objects
	 */
	function getContentLinks() {
		$db = JFactory::getDBO();

		$query = 'SELECT `id`, CONCAT(`title`, " (", `title_alias`, ")") AS text FROM `#__content` ';
		$query .= 'ORDER BY `id`';
		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/*
	 * Get the joomla list of languages
	 */
	function getActiveLanguages($active_languages) {

		$activeLangs = array() ;
		$language =JFactory::getLanguage();
		$jLangs = $language->getKnownLanguages(JPATH_BASE);

		foreach ($jLangs as $jLang) {
			$jlangTag = strtolower(strtr($jLang['tag'],'-','_'));
			$activeLangs[] = JHtml::_('select.option', $jLang['tag'] , $jLang['name']) ;
		}

		return JHtml::_('select.genericlist', $activeLangs, 'active_languages[]', 'size=10 multiple="multiple" data-placeholder="'.vmText::_('COM_VIRTUEMART_DRDOWN_NOTMULTILINGUAL').'"', 'value', 'text', $active_languages );// $activeLangs;
	}


	/**
	 * Retrieve a list of preselected and existing search or order By Fields
	 * $type = 'browse_search_fields' or 'browse_orderby_fields'
	 * @author Kohl Patrick
	 * @return array of order list
	 */
	function getProductFilterFields( $type ) {

		$searchChecked = VmConfig::get($type) ;

		if (!is_array($searchChecked)) {
			$searchChecked = (array)$searchChecked;
		}
		if($type!='browse_cat_orderby_field'){
			$searchFieldsArray = ShopFunctions::getValidProductFilterArray ();
			if($type=='browse_search_fields'){
				if($key = array_search('pc.ordering',$searchFieldsArray)){
					unset($searchFieldsArray[$key]);
				}
			}
		} else {
			$searchFieldsArray = array('category_name','category_description','cx.ordering','c.published');
		}

		$searchFields= new stdClass();
		$searchFields->checkbox ='<div class="threecols"><ul>';
		foreach ($searchFieldsArray as $key => $field ) {
			if (in_array($field, $searchChecked) ) {
				$checked = 'checked="checked"';
			}
			else {
				$checked = '';
			}

			$fieldWithoutPrefix = $field;
			$dotps = strrpos($fieldWithoutPrefix, '.');
			if($dotps!==false){
				$prefix = substr($field, 0,$dotps+1);
				$fieldWithoutPrefix = substr($field, $dotps+1);
			}

			$text = vmText::_('COM_VIRTUEMART_'.strtoupper($fieldWithoutPrefix)) ;

			if ($type == 'browse_orderby_fields' or $type == 'browse_cat_orderby_field'){
				$searchFields->select[] =  JHtml::_('select.option', $field, $text) ;
			}
			$searchFields->checkbox .= '<li><input type="checkbox" id="' .$type.$fieldWithoutPrefix.$key. '" name="'.$type.'[]" value="' .$field. '" ' .$checked. ' /><label for="' .$type.$fieldWithoutPrefix.$key. '">' .$text. '</label></li>';
		}
		$searchFields->checkbox .='</ul></div>';
		return $searchFields;
	}

	/**
	 * Save the configuration record
	 *
	 * @author Max Milbers
	 * @return boolean True is successful, false otherwise
	 */
	function store(&$data) {

		vRequest::vmCheckToken();
		//We create a fresh config
		$config = VmConfig::loadConfig(false,true);

		//We load the config file
		$_raw = self::readConfigFile(FALSE);
		$_value = join('|', $_raw);
		//We set the config file values as parameters into the config
		$config->setParams($_value);

		//We merge the array from the file with the array from the form
		//in case it the form has the same key as the file, the value is taken from the form
		$config->_params = array_merge($config->_params,$data);

		//We need this to know if we should delete the cache
		$browse_cat_orderby_field = $config->get('browse_cat_orderby_field');
		$cat_brws_orderby_dir = $config->get('cat_brws_orderby_dir');

		$urls = array('assets_general_path','media_category_path','media_product_path','media_manufacturer_path','media_vendor_path');
		foreach($urls as $urlkey){
			$url = trim($config->get($urlkey));
			$length = strlen($url);

			if($length<=1){
				vmdebug('Urlkey was TOO SHORT '.$urlkey.' = '.$url.' and length '.$length,$_raw[$urlkey]);
				unset($config->_params[$urlkey]);
				continue;
			}
			if(strrpos($url,'/')!=($length-1)){
				$config->set($urlkey,$url.'/');
				vmInfo('Corrected media url '.$urlkey.' added missing /');
			}
		}

		//If empty it is not sent by the form, other forms do it by using a table to store,
		//the config is like a big xparams and so we check some values for this form manually
		/*$toSetEmpty = array('active_languages','inv_os','email_os_v','email_os_s');
		foreach($toSetEmpty as $item){
			if(!isset($data[$item])) {
				$config->set($item,array());
			}
		}*/

		$checkCSVInput = array('pagseq','pagseq_1','pagseq_2','pagseq_3','pagseq_4','pagseq_5');
		foreach($checkCSVInput as $csValueKey){
			$csValue = $config->get($csValueKey);
			if(!empty($csValue)){
				$sequenceArray = explode(',', $csValue);
				foreach($sequenceArray as &$csV){
					$csV = (int)trim($csV);
				}
				$csValue = implode(',',$sequenceArray);
				$config->set($csValueKey,$csValue);
			}
		}

		if(!class_exists('JFolder')) require(JPATH_VM_LIBRARIES.DS.'joomla'.DS.'filesystem'.DS.'folder.php');

		$safePath = trim($config->get('forSale_path'));
		if(!empty($safePath)){
			if(DS!='/' and strpos($safePath,'/')!==false){
				$safePath=str_replace('/',DS,$safePath);
				vmInfo('Corrected safe path, replaced / by '.DS);
			}
			$length = strlen($safePath);
			if(strrpos($safePath,DS)!=($length-1)){
				$safePath = $safePath.DS;
				vmInfo('Corrected safe path, added missing '.DS);
			}
			$config->set('forSale_path',$safePath);
		} else {
			VmWarn('COM_VIRTUEMART_WARN_SAFE_PATH_NO_INVOICE',vmText::_('COM_VIRTUEMART_ADMIN_CFG_MEDIA_FORSALE_PATH'));
		/*	$safePath = JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_virtuemart'.DS.'vmfiles';

			$exists = JFolder::exists($safePath);
			if(!$exists){
				$created = JFolder::create($safePath);
				$safePath = $safePath.DS;
				if($created){
					vmInfo('COM_VIRTUEMART_SAFE_PATH_DEFAULT_CREATED',$safePath);
					// create htaccess file
					$fileData = "order deny, allow\ndeny from all\nallow from none";
					JLoader::import('joomla.filesystem.file');
					$fileName = $safePath.DS.'.htaccess';
					$result = JFile::write($fileName, $fileData);
					if (!$result) {
						VmWarn('COM_VIRTUEMART_HTACCESS_DEFAULT_NOT_CREATED',$safePath,$fileData);
					}
					$config->set('forSale_path',$safePath);
				} else {
					VmWarn('COM_VIRTUEMART_WARN_SAFE_PATH_NO_INVOICE',vmText::_('COM_VIRTUEMART_ADMIN_CFG_MEDIA_FORSALE_PATH'));
				}
			}*/
		}

		if(!class_exists('shopfunctions')) require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'shopfunctions.php');
		$safePath = shopFunctions::checkSafePath($safePath);

		if(!empty($safePath)){

			$exists = JFolder::exists($safePath.'invoices');
			if(!$exists){
				$created = JFolder::create($safePath.'invoices');
				if($created){
					vmInfo('COM_VIRTUEMART_SAFE_PATH_INVOICE_CREATED');
				} else {
					VmWarn('COM_VIRTUEMART_WARN_SAFE_PATH_NO_INVOICE',vmText::_('COM_VIRTUEMART_ADMIN_CFG_MEDIA_FORSALE_PATH'));
				}
			}
		}

		$active_langs = $config->get('active_languages');
		if(empty($active_langs)){
			$config->set('active_languages',array(VmConfig::$vmlangTag));
		}

		//ATM we want to ensure that only one config is used
		$confData = array();
		$confData['virtuemart_config_id'] = 1;
		$confData['config'] = $config->toString();

		$confTable = $this->getTable('configs');
		if (!$confTable->bindChecknStore($confData)) {
			vmError($confTable->getError());
		}

		VmConfig::loadConfig(true);

		if(!class_exists('GenericTableUpdater')) require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'tableupdater.php');
		$updater = new GenericTableUpdater();
		$result = $updater->createLanguageTables();

		/* This conditions is not enough, if the language changes we need to recall the cache.
		$newbrowse_cat_orderby_field = $config->get('browse_cat_orderby_field');
		$newcat_brws_orderby_dir = $config->get('cat_brws_orderby_dir');
		if($browse_cat_orderby_field!=$newbrowse_cat_orderby_field or $newcat_brws_orderby_dir!=$cat_brws_orderby_dir){
			$cache = JFactory::getCache('com_virtuemart_cats','callback');
			$cache->clean();
		}*/

		$cache = JFactory::getCache('com_virtuemart_cats','callback');
		$cache->clean();
		$cache = JFactory::getCache('com_virtuemart_rss','callback');
		$cache->clean();
		$cache = JFactory::getCache('convertECB','callback');
		$cache->clean();
		$cache = JFactory::getCache('_virtuemart');
		$cache->clean();
		$cache = JFactory::getCache('com_plugins');
		$cache->clean();
		$cache = JFactory::getCache('_system');
		$cache->clean();
		$cache = JFactory::getCache('page');
		$cache->clean();

		return true;
	}

	static public function checkConfigTableExists(){

		$db = JFactory::getDBO();
		$query = 'SHOW TABLES LIKE "'.$db->getPrefix().'virtuemart_configs"';
		$db->setQuery($query);
		$configTable = $db->loadResult();

		if(!$configTable){
			return false;
		} else {
			return true;
		}
	}

	static public function checkVirtuemartInstalled(){
		//return false;
		$db = JFactory::getDBO();
		$query = 'SHOW TABLES LIKE "'.$db->getPrefix().'virtuemart%"';
		$db->setQuery($query);
		$vmTables = $db->loadColumn();
		$err = $db->getError();
		if(!empty($err) or !$vmTables or count($vmTables)<50){	//52 tables for a normal installation
			//vmdebug('checkVirtuemartInstalled, return false ');
			return false;
		} else {
			//vmdebug('checkVirtuemartInstalled, return true found tables '.count($vmTables));
			return true;
		}

	}

	/**
	 * Creates the config table, if it does not exist
	 *
	 * @param $_section Section from the virtuemart_defaults.cfg file to be parsed. Currently, only 'config' is implemented
	 * @return Boolean; true on success, false otherwise
	 * @author Oscar van Eijk
	 */
	static public function installVMconfigTable(){
		vmdebug('installVMconfigTable');
		$qry = self::getCreateConfigTableQuery();
		$_db = JFactory::getDBO();
		$_db->setQuery($qry);
		return $_db->execute();
	}

	static public function getCreateConfigTableQuery(){

		return "CREATE TABLE IF NOT EXISTS `#__virtuemart_configs` (
  `virtuemart_config_id` tinyint(1) unsigned NOT NULL AUTO_INCREMENT,
  `config` text,
  `created_on` datetime NOT NULL default '0000-00-00 00:00:00',
  `created_by` int(11) NOT NULL DEFAULT 0,
  `modified_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` int(11) NOT NULL DEFAULT 0,
  `locked_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `locked_by` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`virtuemart_config_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 COMMENT='Holds configuration settings' AUTO_INCREMENT=1 ;";
	}

	/**
	 * We should this move out of this file, because it is usually only used one time in a shop life
	 * @author Oscar van Eijk
	 * @author Max Milbers
	 */
	static function readConfigFile(){

		$_datafile = JPATH_VM_ADMINISTRATOR.DS.'virtuemart.cfg';
		if (!file_exists($_datafile)) {
			if (file_exists(JPATH_VM_ADMINISTRATOR.DS.'virtuemart_defaults.cfg-dist')) {
				if(!class_exists('JFile')) require(JPATH_VM_LIBRARIES.DS.'joomla'.DS.'filesystem'.DS.'file.php');
				JFile::copy('virtuemart_defaults.cfg-dist','virtuemart.cfg',JPATH_VM_ADMINISTRATOR);
			} else {
				vmWarn('The data file with the default configuration could not be found. You must configure the shop manually.');
				return FALSE;
			}

		} else {
			vmInfo('Taking config from file');
			//vmTrace('read config file',TRUE);
		}

		$_section = '[CONFIG]';
		$_data = fopen($_datafile, 'r');
		$_configData = array();
		$_switch = FALSE;
		while ($_line = fgets ($_data)) {
			$_line = trim($_line);

			if (strpos($_line, '#') === 0) {
				continue; // Commentline
			}
			if ($_line == '') {
				continue; // Empty line
			}
			if (strpos($_line, '[') === 0) {
				// New section, check if it's what we want
				if (strtoupper($_line) == $_section) {
					$_switch = TRUE; // Ok, right section
				} else {
					$_switch = FALSE;
				}
				continue;
			}
			if (!$_switch) {
				continue; // Outside a section or inside the wrong one.
			}

			if (strpos($_line, '=') !== FALSE) {

				$pair = explode('=',$_line);
				if(isset($pair[1])){
					if(strpos($pair[1], 'array:') !== FALSE){
						$pair[1] = substr($pair[1],6);
						$pair[1] = explode('|',$pair[1]);
					}
					// if($pair[0]!=='offline_message' && $pair[0]!=='dateformat'){
					if($pair[0]!=='offline_message'){
						$_line = $pair[0].'='.serialize($pair[1]);
					} else {
						$_line = $pair[0].'='.base64_encode(serialize($pair[1]));
					}

					/*if(($freshInstall or $returnDangerousTools) && $pair[0] == 'dangeroustools' ){

						if($returnDangerousTools){
							if ($pair[1] == "0") {
								return FALSE;
							}
							else {
								return TRUE;
							}
						}
						if($freshInstall){
							//vmdebug('$freshInstall');
							$pair[1]="1";
							$_line = $pair[0].'='.serialize($pair[1]);
						}
						vmdebug('dangeroustools '.$pair[1]);
					}/*/

				} else {
					$_line = $pair[0].'=';
				}
				$_configData[] = $_line;

			}

		}

		fclose ($_data);

		if (!$_configData) {
			return FALSE; // Nothing to do
		} else {
			return $_configData;
		}
	}

	/**
	 * Dangerous tools get disabled after execution an operation which needed that rights.
	 * This is the function actually doing it.
	 *
	 * @author Max Milbers
	 */
	function setDangerousToolsOff(){

		$dangerousTools = VirtueMartModelConfig::readConfigFile(true);

		if( $dangerousTools){
			$uri = JFactory::getURI();
			$link = $uri->root() . 'administrator/index.php?option=com_virtuemart&view=config';
			$lang = vmText::sprintf('COM_VIRTUEMART_SYSTEM_DANGEROUS_TOOL_STILL_ENABLED',vmText::_('COM_VIRTUEMART_ADMIN_CFG_DANGEROUS_TOOLS'),$link);
			VmInfo($lang);
		} else {
			if(self::checkConfigTableExists()){
				$data['dangeroustools'] = 0;
				$data['virtuemart_config_id'] = 1;
				$this->store($data);
			}
		}
	}

	public function remove($id) {

		$table = $this->getTable('configs');
		$id = 1;
		if (!$table->delete($id)) {
			vmError(get_class( $this ).'::remove '.$id.' '.$table->getError(),'Cannot delete config');
			return false;
		}

		return true;
	}

	/**
	 * This function deletes a config stored in the database
	 *
	 * @author Max Milbers
	 */
	function deleteConfig(){

		if($this->remove(1)){
			return VmConfig::loadConfig(true,true);
		} else {
			return false;
		}

	}

}

//pure php no closing tag