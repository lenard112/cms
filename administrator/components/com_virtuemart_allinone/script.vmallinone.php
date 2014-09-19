<?php
defined ('_JEXEC') or die('Restricted access');

/**
 *
 * VirtueMart script file
 *
 * This file is executed during install/upgrade and uninstall
 *
 * @author Patrick Kohl, Max Milbers, Valérie Isaksen
 * @package VirtueMart
 */

defined ('DS') or define('DS', DIRECTORY_SEPARATOR);
//Update Tables
if (!class_exists ('VmConfig')) {
	if(file_exists(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_virtuemart' . DS . 'helpers' . DS . 'config.php')){
		require(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_virtuemart' . DS . 'helpers' . DS . 'config.php');
	} else {
		jExit('Install the virtuemart Core first ');
	}

}

$max_execution_time = ini_get ('max_execution_time');
if ((int)$max_execution_time < 120) {
	@ini_set ('max_execution_time', '120');
}
$memory_limit = (int)substr (ini_get ('memory_limit'), 0, -1);
if ($memory_limit < 128) {
	@ini_set ('memory_limit', '128M');
}

// hack to prevent defining these twice in 1.6 installation
if (!defined ('_VM_AIO_SCRIPT_INCLUDED')) {

	define('_VM_AIO_SCRIPT_INCLUDED', TRUE);

	class com_virtuemart_allinoneInstallerScript {

		public function preflight () {
			//$this->vmInstall();
		}

		public function install () {
			//$this->vmInstall();
		}

		public function discover_install () {
			//$this->vmInstall();
		}

		public function postflight () {

			$this->vmInstall ();
		}

		public function vmInstall ($dontMove=0) {

			jimport ('joomla.filesystem.file');
			jimport ('joomla.installer.installer');

			$this->createIndexFolder (JPATH_ROOT . DS . 'plugins' . DS . 'vmcalculation');
			$this->createIndexFolder (JPATH_ROOT . DS . 'plugins' . DS . 'vmcustom');
			$this->createIndexFolder (JPATH_ROOT . DS . 'plugins' . DS . 'vmpayment');
			$this->createIndexFolder (JPATH_ROOT . DS . 'plugins' . DS . 'vmshipment');
			$this->createIndexFolder (JPATH_ROOT . DS . 'plugins' . DS . 'vmuserfield');

			if(empty($dontMove)){
				$this->path = JInstaller::getInstance ()->getPath ('extension_administrator');
			} else {
				$this->path = JPATH_ROOT;
			}
			$this->dontMove = $dontMove;

			$this->updateShipperToShipment ();
			$this->installPlugin ('VM Payment - Standard', 'plugin', 'standard', 'vmpayment',1);
			$this->installPlugin ('VM Payment - Klarna', 'plugin', 'klarna', 'vmpayment');
			$this->installPlugin ('VM Payment - KlarnaCheckout', 'plugin', 'klarnacheckout', 'vmpayment');
			$this->installPlugin ('VM Payment - Sofort Banking/Überweisung', 'plugin', 'sofort', 'vmpayment');
			$this->installPlugin ('VM Payment - PayPal', 'plugin', 'paypal', 'vmpayment');
			$this->installPlugin ('VM Payment - Heidelpay', 'plugin', 'heidelpay', 'vmpayment');
			$this->installPlugin ('VM Payment - Paybox', 'plugin', 'paybox', 'vmpayment');
			$this->installPlugin ('VM Payment - Realex HPP & API', 'plugin', 'realex_hpp_api', 'vmpayment');
			$this->installPlugin ('VM UserField - Realex HPP & API', 'plugin', 'realex_hpp_api', 'vmuserfield');

			$this->installPlugin ('VM Payment - Skrill', 'plugin', 'skrill', 'vmpayment');

			$this->installPlugin ('VM Payment - Authorize.net', 'plugin', 'authorizenet', 'vmpayment');

			$this->installPlugin ('VM Payment - Sofort iDeal', 'plugin', 'sofort_ideal', 'vmpayment');

			$this->installPlugin ('VM Shipment - By weight, ZIP and countries', 'plugin', 'weight_countries', 'vmshipment', 1);

			$this->installPlugin ('VM Custom - Customer text input', 'plugin', 'textinput', 'vmcustom', 1);
			$this->installPlugin ('VM Custom - Product specification', 'plugin', 'specification', 'vmcustom', 1);
			//$this->installPlugin ('VM Custom - Stockable variants', 'plugin', 'stockable', 'vmcustom', 1);
			$this->installPlugin ('VM Calculation - Avalara Tax', 'plugin', 'avalara', 'vmcalculation' );
			//$this->installPlugin ('VM Userfield - Realex', 'plugin', 'realex', 'vmuserfield' );

			// 			$table = '#__virtuemart_customs';
			// 			$fieldname = 'field_type';
			// 			$fieldvalue = 'G';
			// 			$this->addToRequired($table,$fieldname,$fieldvalue,"INSERT INTO `#__virtuemart_customs`
			// 					(`custom_parent_id`, `admin_only`, `custom_title`, `custom_tip`, `custom_value`, `custom_field_desc`,
			// 					 `field_type`, `is_list`, `is_hidden`, `is_cart_attribute`, `published`) VALUES
			// 						(0, 0, 'COM_VIRTUEMART_STOCKABLE_PRODUCT', 'COM_VIRTUEMART_STOCKABLE_PRODUCT_TIP', NULL,
			// 					'COM_VIRTUEMART_STOCKABLE_PRODUCT_DESC', 'G', 0, 0, 0, 1 );");

			$this->installPlugin ('VirtueMart Product', 'plugin', 'virtuemart', 'search');
			$this->updateMoneyBookersToSkrill();


			$task = vRequest::getCmd ('task');
			if ($task != 'updateDatabase') {

				// modules auto move
				$src = $this->path . DS . "modulesBE";
				$dst = JPATH_ROOT . DS."administrator". DS . "modules";
				$this->recurse_copy ($src, $dst);

				//echo "Checking VirtueMart modules...";
				if (!$this->VmAdminModulesAlreadyInstalled ()) {
					echo "Installing VirtueMart Administrator modules<br/ >";
						$defaultParams = '{"show_vmmenu":"1"}';
						$this->installModule ('VM - Administrator Module', 'mod_vmmenu', 5, $defaultParams, $dst,1,'menu',3);
						$this->updateJoomlaUpdateServer( 'module', 'mod_vmmenu', $dst   );
				}


				// modules auto move
				$src = $this->path . DS . "modules";
				$dst = JPATH_ROOT . DS . "modules";
				$this->recurse_copy ($src, $dst);

				if (!$this->VmModulesAlreadyInstalled ()) {
					echo "Installing VirtueMart3 modules<br/ >";
					if (version_compare (JVERSION, '1.6.0', 'ge')) {
						$defaultParams = '{"text_before":"","product_currency":"","cache":"1","moduleclass_sfx":"","class_sfx":""}';
					} else {
						$defaultParams = "text_before=\nproduct_currency=\ncache=1\nmoduleclass_sfx=\nclass_sfx=\n";
					}
					$this->installModule ('VM - Currencies Selector', 'mod_virtuemart_currencies', 5, $defaultParams, $dst);

					if (version_compare (JVERSION, '1.6.0', 'ge')) {
						$defaultParams = '{"product_group":"featured","max_items":"1","products_per_row":"1","display_style":"list","show_price":"1","show_addtocart":"1","headerText":"Best products","footerText":"","filter_category":"0","virtuemart_category_id":"0","cache":"0","moduleclass_sfx":"","class_sfx":""}';

					} else {
						$defaultParams = "product_group=featured\nmax_items=1\nproducts_per_row=1\ndisplay_style=list\nshow_price=1\nshow_addtocart=1\nheaderText=Best products\nfooterText=\nfilter_category=0\ncategory_id=1\ncache=0\nmoduleclass_sfx=\nclass_sfx=\n";
					}
					$this->installModule ('VM - Featured products', 'mod_virtuemart_product', 3, $defaultParams, $dst);

					if (version_compare (JVERSION, '1.6.0', 'ge')) {
						$defaultParams = '{"product_group":"topten","max_items":"1","products_per_row":"1","display_style":"list","show_price":"1","show_addtocart":"1","headerText":"","footerText":"","filter_category":"0","virtuemart_category_id":"0","cache":"0","moduleclass_sfx":"","class_sfx":""}';
					} else {
						$defaultParams = "product_group=topten\nmax_items=1\nproducts_per_row=1\ndisplay_style=list\nshow_price=1\nshow_addtocart=1\nheaderText=\nfooterText=\nfilter_category=0\ncategory_id=1\ncache=0\nmoduleclass_sfx=\nclass_sfx=\n";
					}
					$this->installModule ('VM - Best Sales', 'mod_virtuemart_product', 1, $defaultParams, $dst);

					if (version_compare (JVERSION, '1.6.0', 'ge')) {

						$defaultParams = '{"width":"20","text":"","button":"","button_pos":"right","imagebutton":"","button_text":""}';
					} else {
						$defaultParams = "width=20\ntext=\nbutton=\nbutton_pos=right\nimagebutton=\nbutton_text=\nmoduleclass_sfx=\ncache=1\ncache_time=900\n";
					}
					$this->installModule ('VM - Search in Shop', 'mod_virtuemart_search', 2, $defaultParams, $dst);

					if (version_compare (JVERSION, '1.6.0', 'ge')) {
						$defaultParams = '{"show":"all","display_style":"list","manufacturers_per_row":"1","headerText":"","footerText":""}';
					} else {
						$defaultParams = "show=all\ndisplay_style=div\nmanufacturers_per_row=1\nheaderText=\nfooterText=\ncache=0\nmoduleclass_sfx=\nclass_sfx=";
					}
					$this->installModule ('VM - Manufacturer', 'mod_virtuemart_manufacturer', 8, $defaultParams, $dst);

					if (version_compare (JVERSION, '1.6.0', 'ge')) {
						$defaultParams = '{"moduleclass_sfx":"","show_price":"1","show_product_list":"1"}';
					} else {
						$defaultParams = "moduleclass_sfx=\nshow_price=1\nshow_product_list=1\n";
					}
					$this->installModule ('VM - Shopping cart', 'mod_virtuemart_cart', 0, $defaultParams, $dst);

					if (version_compare (JVERSION, '1.6.0', 'ge')) {
						$defaultParams = '{"Parent_Category_id":"0","layout":"default","cache":"0","moduleclass_sfx":"","class_sfx":""}';
					} else {
						$defaultParams = "moduleclass_sfx=\nclass_sfx=\ncategory_name=default\ncache=no\n";
					}
					$this->installModule ('VM - Category', 'mod_virtuemart_category', 4, $defaultParams, $dst);
				} else {
					echo "VirtueMart3 modules already installed<br/ >";
				}
				$modules = array(
					'mod_virtuemart_currencies',
					'mod_virtuemart_product',
					'mod_virtuemart_search',
					'mod_virtuemart_manufacturer',
					'mod_virtuemart_cart',
					'mod_virtuemart_category'
				);
				foreach ($modules as $module) {
					$this->updateJoomlaUpdateServer( 'module', $module, $dst   );
				}

				// libraries auto move
				$src = $this->path . DS . "libraries";
				$dst = JPATH_ROOT . DS . "libraries";
				$this->recurse_copy ($src, $dst);
				echo "VirtueMart3 pdf moved to the joomla libraries folder<br/ >";

				//update plugins, make em loggable
				/*			$loggables = array(	'created_on' => 'DATETIME NOT NULL DEFAULT "0000-00-00 00:00:00"',
				'created_by' => 'INT(11) NOT NULL DEFAULT "0"',
				'modified_on'=> 'DATETIME NOT NULL DEFAULT "0000-00-00 00:00:00"',
				'modified_by'=> 'INT(11) NOT NULL DEFAULT "0"',
				'locked_on' =>'DATETIME NOT NULL DEFAULT "0000-00-00 00:00:00"',
				'locked_by' =>'INT(11) NOT NULL DEFAULT "0"'
				);
				foreach($loggables as $key => $value){
				$this->checkAddFieldToTable('#__virtuemart_payment_paypal',$key,$value);
				$this->checkAddFieldToTable('#__virtuemart_payment_standard',$key,$value);
				$this->checkAddFieldToTable('#__virtuemart_shipment_weight_countries',$key,$value);
				}*/

				echo "<H3>Installing VirtueMart Plugins and modules Success.</h3>";
				echo "<H3>Keep the AIO component for automatic updates of ALL VirtueMart Plugins and modules</h3>";

			} else {
				echo "<H3>Updated VirtueMart Plugin tables</h3>";
			}
			$this->updateOrderingExtensions();

			$this->checkFixJoomlaBEMenuEntries();

			$this->replaceStockableByDynamicChilds();
			return TRUE;

		}

		/**
		 * Replaces the old stockable plugin by the native method of vm
		 */
		public function replaceStockableByDynamicChilds(){
			$db = JFactory::getDbo();
			$db->setQuery('SELECT `extension_id` FROM `#__extensions` WHERE `type` = "plugin" AND `folder` = "vmcustom" AND `element`="stockable"');
			$jId = $db->loadResult();

			if($jId){
				$db->setQuery('SELECT `virtuemart_custom_id` FROM #__virtuemart_customs WHERE `custom_jplugin_id` = "'.$jId.'" ');
				$cId = $db->loadResult();

				$db->setQuery('SELECT `virtuemart_custom_id` FROM #__virtuemart_customs WHERE `field_type` = "A" ');
				$acId = $db->loadResult();

				if($cId){
					$db->setQuery('UPDATE #__virtuemart_product_customfields SET `virtuemart_custom_id` = "'.$acId.'" WHERE `virtuemart_custom_id` = "'.$cId.'" ');
					$db->execute();
				}
			}
			$db->setQuery('UPDATE #__extensions SET `published` = "0" WHERE `extension_id` = "'.$jId.'" ');

		}

		/**
		 *
		 */
		public function checkFixJoomlaBEMenuEntries(){

			$db = JFactory::getDbo();
			$db->setQuery('SELECT `extension_id` FROM `#__extensions` WHERE `type` = "component" AND `element`="com_virtuemart"');
			$jId = $db->loadResult();

			//The extension entry does not exist, lets insert one.
			/*if(!$jId){
				$q = 'INSERT INTO `#__extensions` (`extension_id`, `name`, `type`, `element`, `folder`, `client_id`, `enabled`, `access`, `protected`, `manifest_cache`)
VALUES (null, \'VIRTUEMART\', \'component\', \'com_virtuemart\', \'\', 1, 1, 1, 0, \'{"legacy":true,"name":"VIRTUEMART","type":"component","creationDate":"September 04 2014","author":"The VirtueMart Development Team","copyright":"Copyright (C) 2004-2013 Virtuemart Team. All rights reserved.","authorEmail":"max|at|virtuemart.net","authorUrl":"http:\\/\\/www.virtuemart.net","version":"2.9.9b","description":"","group":""}\'); ';
				$db->setQuery($q);
				if($db->execute($q)){
					$jId = $db->insertid();
					vmInfo('VirtueMart extension entry was missing, added with id '.$jId);
				} else {
					vmError('Serious Error, could not create entry for com_virtuemart in table extensions');
				}
			}*/

			if($jId){
				//now lets check if there are menue entries
				$db->setQuery('SELECT `id` FROM `#__menu` WHERE `menutype` = "main" AND `path`="com-virtuemart"');

				if($id = $db->loadResult()){
					$db->setQuery('UPDATE `#__menu` SET `component_id`="'.$jId.'", `language`="*" WHERE `id` = "'.$id.'" ');
					$db->execute();

					$db->setQuery('SELECT `id` FROM `#__menu` WHERE `component_id` = "'.$jId.'" ');
					$mId = $db->loadResult();

					$db->setQuery('UPDATE `#__menu` SET `component_id`="'.$jId.'", `language`="*" WHERE `parent_id` = "'.$mId.'" ');
					$db->execute();
				} else {
					vmError('Could not find VirtueMart submenues, please install VirtueMart again');
				}

			}
		}

		private function updateMoneyBookersToSkrill() {
			$db = JFactory::getDBO ();
			$q="SELECT `extension_id` FROM `#__extensions` WHERE `#__extensions`.`folder` =  'vmpayment' AND `#__extensions`.`element` LIKE  'skrill'";
			$db->setQuery ($q);
			$skrill_jplugin_id = $db->loadResult()  ;
			$app = JFactory::getApplication ();

			$q="SELECT *
				FROM `#__virtuemart_paymentmethods`
				JOIN `#__extensions` ON `#__extensions`.`extension_id` = `#__virtuemart_paymentmethods`.`payment_jplugin_id`
				WHERE `#__extensions`.`folder` =  'vmpayment'
				AND `#__extensions`.`element` LIKE  'moneybookers_%'";
			$db->setQuery ($q);
			$moneybookers = $db->loadObjectList()  ;
			if ($moneybookers) {
				foreach ($moneybookers as $moneybooker) {
					$payment_params=$moneybooker->payment_params;
					$mb_element=str_replace('moneybookers_', '',$moneybooker->element);
					$payment_params='product='.$mb_element.'|'.$payment_params;
					$q = 'UPDATE `#__virtuemart_paymentmethods`
									SET `payment_params`= "'.$payment_params.'" , `payment_jplugin_id` = '.$skrill_jplugin_id.' , `payment_element`= "skrill"
									 WHERE `virtuemart_paymentmethod_id` ='.$moneybooker->virtuemart_paymentmethod_id;
					$db->setQuery($q);
					$db->query();
					$app->enqueueMessage ("Updated payment method: ".$moneybooker->payment_element.". Uses skrill now");
					}
			}
			$q="DELETE FROM  `#__extensions` WHERE  `#__extensions`.`folder` =  'vmpayment'
				AND `#__extensions`.`element` LIKE  'moneybookers%'";
			$db->setQuery($q);
			$db->query();

			$path =JPATH_ROOT . DS . 'plugins' . DS . 'vmpayment';
			$moneybookers_variants=array('', '_acc', '_did','_gir','_idl','_obt', '_pwy','_sft', '_wlt');
			foreach ($moneybookers_variants as $moneybookers_variant) {
				$folder=$path.DS.'moneybookers'.$moneybookers_variant;
				if (JFolder::exists($folder) ) {
					if (!JFolder::delete($folder)) {
						$app->enqueueMessage ("Failed to delete ". $folder." folder");
					}
				}
				$lang_file=	  JPATH_ROOT . DS."administrator". DS . "language". DS. 'en-GB'. DS.'en-GB.plg_vmpayment_moneybookers'.$moneybookers_variant."ini";
					if (JFile::exists ($lang_file) ){
						if (!JFile::delete ($lang_file)) {
							$app->enqueueMessage ('Couldnt delete ' . $lang_file);
							return false;
						}
					}
			}


		}



		private function updateOrderingExtensions(){


			$db = JFactory::getDBO ();

			$q = 'UPDATE `#__extensions` SET `ordering`= 5 WHERE `folder` ="vmpayment"';
			$db->setQuery($q);
			$db->query();

			$q = 'UPDATE `#__extensions` SET `ordering`= 1 WHERE `element` ="klarna"';
			$db->setQuery($q);
			$db->query();

			$q = 'UPDATE `#__extensions` SET `ordering`= 2 WHERE `element` ="sofort"';
			$db->setQuery($q);
			$db->query();

			$q = 'UPDATE `#__extensions` SET `ordering`= 2 WHERE `element` ="sofort_ideal"';
			$db->setQuery($q);
			$db->query();

			$q = 'UPDATE `#__extensions` SET `ordering`= 3 WHERE `element` ="paypal"';
			$db->setQuery($q);
			$db->query();

			$q = 'UPDATE `#__extensions` SET `ordering`= 100 WHERE `element` ="payzen"';
			$db->setQuery($q);
			$db->query();

			$q = 'UPDATE `#__extensions` SET `ordering`= 100 WHERE `element` ="systempay"';
			$db->setQuery($q);
			$db->query();
		}

		/**
		 * Installs a vm plugin into the database
		 *
		 */
		private function installPlugin ($name, $type, $element, $group, $published = 0, $createJPluginTable = 1) {

			$task = vRequest::getCmd ('task');

			if ($task != 'updateDatabase') {
				$data = array();

				$src = $this->path . DS . 'plugins' . DS . $group . DS . $element;

				if ($createJPluginTable) {
					if (version_compare (JVERSION, '1.7.0', 'ge')) {

						// Joomla! 1.7 code here
						$table = JTable::getInstance ('extension');
						$data['enabled'] = $published;
						$data['access'] = 1;
						$tableName = '#__extensions';
						$idfield = 'extension_id';
					} else {

						// Joomla! 1.5 code here
						$table = JTable::getInstance ('plugin');
						$data['published'] = $published;
						$data['access'] = 0;
						$tableName = '#__plugins';
						$idfield = 'id';
					}

					$data['name'] = $name;
					$data['type'] = $type;
					$data['element'] = $element;
					$data['folder'] = $group;

					$data['client_id'] = 0;

					$db = JFactory::getDBO ();
					$q = 'SELECT COUNT(*) FROM `' . $tableName . '` WHERE `element` = "' . $element . '" and folder = "' . $group . '" ';
					$db->setQuery ($q);
					$count = $db->loadResult ();

					//We write only in the table, when it is not installed already
					if ($count == 0) {
						// 				$table->load($count);
						if (version_compare (JVERSION, '1.6.0', 'ge')) {
							$data['manifest_cache'] = json_encode (JApplicationHelper::parseXMLInstallFile ($src . DS . $element . '.xml'));
						}

						if (!$table->bind ($data)) {
							$app = JFactory::getApplication ();
							$app->enqueueMessage ('VMInstaller table->bind throws error for ' . $name . ' ' . $type . ' ' . $element . ' ' . $group);
						}

						if (!$table->check ($data)) {
							$app = JFactory::getApplication ();
							$app->enqueueMessage ('VMInstaller table->check throws error for ' . $name . ' ' . $type . ' ' . $element . ' ' . $group);

						}

						if (!$table->store ($data)) {
							$app = JFactory::getApplication ();
							$app->enqueueMessage ('VMInstaller table->store throws error for ' . $name . ' ' . $type . ' ' . $element . ' ' . $group);
						}

						$errors = $table->getErrors ();
						foreach ($errors as $error) {
							$app = JFactory::getApplication ();
							$app->enqueueMessage (get_class ($this) . '::store ' . $error);
						}
						// remove duplicated
					} elseif ($count == 2) {
						$q = 'SELECT ' . $idfield . ' FROM `' . $tableName . '` WHERE `element` = "' . $element . '" ORDER BY  `' . $idfield . '` DESC  LIMIT 0,1';
						$db->setQuery ($q);
						$duplicatedPlugin = $db->loadResult ();
						$q = 'DELETE FROM `' . $tableName . '` WHERE ' . $idfield . ' = ' . $duplicatedPlugin;
						$db->setQuery ($q);
						$db->query ();
					}
				}

			}
			if (version_compare (JVERSION, '1.7.0', 'ge')) {
				// Joomla! 1.7 code here
				$dst = JPATH_ROOT . DS . 'plugins' . DS . $group . DS . $element;

			} elseif (version_compare (JVERSION, '1.6.0', 'ge')) {
				// Joomla! 1.6 code here
				$dst = JPATH_ROOT . DS . 'plugins' . DS . $group . DS . $element;
			} else {
				// Joomla! 1.5 code here
				$dst = JPATH_ROOT . DS . 'plugins' . DS . $group;
			}
			$success = true;
			if ($task != 'updateDatabase') {
				$success =$this->recurse_copy ($src, $dst);
			}
			if ($success) {
				if ($group != 'search') {
					$this->updatePluginTable ($name, $type, $element, $group, $dst);
				} else {
					if (version_compare (JVERSION, '1.6.0', 'ge')) {
						$this->updatePluginTable ($name, $type, $element, $group, $dst);
					}
				}
			}
			$this->updateJoomlaUpdateServer( $type, $element, $dst   );


		}


		public function updatePluginTable ($name, $type, $element, $group, $dst) {

			$app = JFactory::getApplication ();

			//Update Tables
			if (!class_exists ('VmConfig')) {
				require(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_virtuemart' . DS . 'helpers' . DS . 'config.php');
			}

			if (class_exists ('VmConfig')) {
				$pluginfilename = $dst . DS . $element . '.php';
				if(file_exists($pluginfilename)){
					require_once ($pluginfilename);	//require_once cause is more failproof and is just for install
				} else {
					$app = JFactory::getApplication ();
					$app->enqueueMessage (get_class ($this) . ':: VirtueMart3 could not find file '.$pluginfilename);
					return false;
				}


				//plgVmpaymentPaypal
				$pluginClassname = 'plg' . ucfirst ($group) . ucfirst ($element);

				//Let's get the global dispatcher
				$dispatcher = JDispatcher::getInstance ();
				$config = array('type' => $group, 'name' => $group, 'params' => '');
				$plugin = new $pluginClassname($dispatcher, $config);
				;
				// 				$updateString = $plugin->getVmPluginCreateTableSQL();
				//if(function_exists($plugin->getTableSQLFields)){
				$_psType = substr ($group, 2);

				$tablename = '#__virtuemart_' . $_psType . '_plg_' . $element;
				$db = JFactory::getDBO ();
				$prefix = $db->getPrefix ();
				$query = 'SHOW TABLES LIKE "' . str_replace ('#__', $prefix, $tablename) . '"';
				$db->setQuery ($query);
				$result = $db->loadResult ();
				//$app -> enqueueMessage( get_class( $this ).'::  '.$query.' '.$result);
				if ($result) {
					$SQLfields = $plugin->getTableSQLFields ();
					$loggablefields = $plugin->getTableSQLLoggablefields ();
					$tablesFields = array_merge ($SQLfields, $loggablefields);
					$update[$tablename] = array($tablesFields, array(), array());
					vmdebug ('install plugin', $update);
					$app->enqueueMessage (get_class ($this) . ':: VirtueMart2 update ' . $tablename);

					if (!class_exists ('GenericTableUpdater')) {
						require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'tableupdater.php');
					}
					$updater = new GenericTableUpdater();

					$updater->updateMyVmTables ($update);
				}
				//}
				// 				} else {

				// 					$app = JFactory::getApplication();
				// 					$app -> enqueueMessage( get_class( $plugin ).':: VirtueMart2 function getTableSQLFields not found');

				// 				}

			} else {
				$app = JFactory::getApplication ();
				$app->enqueueMessage (get_class ($this) . ':: VirtueMart2 must be installed, or the tables cant be updated ');

			}

		}

		public function installModule ($title, $module, $ordering, $params, $src, $client_id=0, $position='position-4', $access=1) {

			$params = '';

			$table = JTable::getInstance ('module');

			$db = $table->getDBO ();
			$q = 'SELECT id FROM `#__modules` WHERE `module` = "' . $module . '" ';
			$db->setQuery ($q);
			$id = $db->loadResult ();

			$src .= DS . $module;
			if (!empty($id)) {
				return;
			}
			$table->load ();
			/*
			if (version_compare (JVERSION, '1.7.0', 'ge')) {
				// Joomla! 1.7 code here
				$position = 'position-4';
				$access = 1;
			} else {
				if (version_compare (JVERSION, '1.6.0', 'ge')) {
					// Joomla! 1.6 code here
					$access = 1;
				} else {
					// Joomla! 1.5 code here
					$position = 'left';
					$access = 0;
				}
			}
*/
			if (empty($table->title)) {
				$table->title = $title;
			}
			if (empty($table->ordering)) {
				$table->ordering = $ordering;
			}
			if (empty($table->published)) {
				$table->published = 1;
			}
			if (empty($table->module)) {
				$table->module = $module;
			}
			if (empty($table->params)) {
				$table->params = $params;
			}
			// table is loaded with access=1
				$table->access = $access;
			if (empty($table->position)) {
				$table->position = $position;
			}
			if (empty($table->client_id)) {
				$table->client_id  = $client_id;
			}

			$table->language = '*';
			// 			$data['manifest_cache'] ='';
			// 			if(!empty($id)){
			// 				unset($data['manifest_cache']);
			// 				$table->load($id);
			// 				if(empty($table->manifest_cache)){
			// 					if(version_compare(JVERSION,'1.6.0','ge')) {
			// 						$data['manifest_cache'] = json_encode(JApplicationHelper::parseXMLInstallFile($src.DS.$module.'.xml'));
			// 					}
			// 				}
			// 			}

			// 			if(empty($count)){
			// 			if(!$table->bind($data)){
			// 				$app = JFactory::getApplication();
			// 				$app -> enqueueMessage('VMInstaller table->bind throws error for '.$title.' '.$module.' '.$params);
			// 			}

			if (!$table->check ()) {
				$app = JFactory::getApplication ();
				$app->enqueueMessage ('VMInstaller table->check throws error for ' . $title . ' ' . $module . ' ' . $params);
			}

			if (!$table->store ()) {
				$app = JFactory::getApplication ();
				$app->enqueueMessage ('VMInstaller table->store throws error for for ' . $title . ' ' . $module . ' ' . $params);
			}

			$errors = $table->getErrors ();
			foreach ($errors as $error) {
				$app = JFactory::getApplication ();
				$app->enqueueMessage (get_class ($this) . '::store ' . $error);
			}
			// 			}

			$lastUsedId = $table->id;

			$q = 'SELECT moduleid FROM `#__modules_menu` WHERE `moduleid` = "' . $lastUsedId . '" ';
			$db->setQuery ($q);
			$moduleid = $db->loadResult ();

			$action = '';
			if (empty($moduleid)) {
				$q = 'INSERT INTO `#__modules_menu` (`moduleid`, `menuid`) VALUES( "' . $lastUsedId . '" , "0");';
			} else {
				//$q = 'UPDATE `#__modules_menu` SET `menuid`= "0" WHERE `moduleid`= "'.$moduleid.'" ';
			}
			$db->setQuery ($q);
			$db->query ();

			if (version_compare (JVERSION, '1.6.0', 'ge')) {

				$q = 'SELECT extension_id FROM `#__extensions` WHERE `element` = "' . $module . '" ';
				$db->setQuery ($q);
				$ext_id = $db->loadResult ();

				//				$manifestCache = str_replace('"', '\'', $data["manifest_cache"]);
				$action = '';
				if (empty($ext_id)) {
					if (version_compare (JVERSION, '1.6.0', 'ge')) {
						$manifest_cache = json_encode (JApplicationHelper::parseXMLInstallFile ($src . DS . $module . '.xml'));
					}
					$q = 'INSERT INTO `#__extensions` 	(`name`, `type`, `element`, `folder`, `client_id`, `enabled`, `access`, `protected`, `manifest_cache`, `params`, `ordering`) VALUES
																	( "' . $module . '" , "module", "' . $module . '", "", "'.$client_id.'", "1","' . $access . '", "0", "' . $db->escape ($manifest_cache) . '", "' . $params . '","' . $ordering . '");';
				} else {

					/*					$q = 'UPDATE `#__extensions` SET 	`name`= "'.$module.'",
																						`type`= "module",
																						`element`= "'.$module.'",
																						`folder`= "",
																						`client_id`= "'.$client_id.'",
																						`enabled`= "1",
																						`access`= "'.$access.'",
																						`protected`= "0",
																						`ordering`= "'.$ordering.'"

										WHERE `extension_id`= "'.$ext_id.'" ';*/
				}
				$db->setQuery ($q);
				if (!$db->query ()) {
					$app = JFactory::getApplication ();
					$app->enqueueMessage (get_class ($this) . '::  ' . $db->getErrorMsg ());
				}

			}
		}

		public function VmModulesAlreadyInstalled () {

			// when the modules are already installed publish=-2
			$table = JTable::getInstance ('module');
			$db = $table->getDBO ();
			$q = 'SELECT count(*) FROM `#__modules` WHERE `module` LIKE "mod_virtuemart_%"';
			$db->setQuery ($q);
			$count = $db->loadResult ();
			return $count;
		}

		public function VmAdminModulesAlreadyInstalled () {

			// when the modules are already installed publish=-2
			$table = JTable::getInstance ('module');
			$db = $table->getDBO ();
			$q = 'SELECT count(*) FROM `#__modules` WHERE `module` LIKE "mod_vmmenu"';
			$db->setQuery ($q);
			$count = $db->loadResult ();
			return $count;
		}

 		/**
		 * @param $type= 'plugin'
		 * @param $element= 'textinput'
		 * @param $src = path . DS . 'plugins' . DS . $group . DS . $element;
		 * @author Valerie Isaksen
		 */
		function updateJoomlaUpdateServer( $type, $element, $dst  ){

			$db = JFactory::getDBO();
			$extensionXmlFileName=$this->getExtensionXmlFileName($type, $element, $dst );
			$xml=simplexml_load_file($extensionXmlFileName);

			// get extension id
			$query="SELECT extension_id FROM #__extensions WHERE type=".$db->quote($type)." AND element=".$db->quote($element);
			$db->setQuery($query);
			$extension_id=$db->loadResult();
			if(!$extension_id) {
				vmdebug('updateJoomlaUpdateServer no extension id ',$query);
				return;
			}
			// Is the extension already in the update table ?
			$query="SELECT * FROM `#__update_sites_extensions` WHERE extension_id=".$extension_id;
			$db->setQuery($query);
			$update_sites_extensions=$db->loadObject();
			//VmConfig::$echoDebug=true;


			// Update the version number for all
			if(isset($xml->version)) {
				$query="UPDATE `#__updates` SET `version`=".$db->quote((string)$xml->version)."
					         WHERE extension_id=".$extension_id;
				$db->setQuery($query);
				$db->query();
			}


			if(isset($xml->updateservers->server)) {
				if (!$update_sites_extensions) {

					$query="INSERT INTO `#__update_sites` SET `name`=".$db->quote((string)$xml->updateservers->server['name']).",
				        `type`=".$db->quote((string)$xml->updateservers->server['type']).",
				        `location`=".$db->quote((string)$xml->updateservers->server).", enabled=1 ";
					$db->setQuery($query);
					$db->query();

					$update_site_id=$db->insertId();

					$query="INSERT INTO #__update_sites_extensions SET update_site_id=".$update_site_id." , extension_id=".$extension_id;
					$db->setQuery($query);
					$db->query();
				} else {
					if(empty($update_sites_extensions->update_site_id)){
						vmWarn('Update site id not found for '.$element);
						vmdebug('Update site id not found for '.$element,$update_sites_extensions);
						return false;
					}
					$query="SELECT * FROM `#__update_sites` WHERE `update_site_id`=".$update_sites_extensions->update_site_id;
					$db->setQuery($query);
					$update_sites= $db->loadAssocList();
					vmdebug('updateJoomlaUpdateServer',$update_sites);
					if(empty($update_sites)){
						vmdebug('No update sites found, they should be inserted');
						return false;
					}
					//Todo this is written with an array, but actually it is only tested to run with one server
					foreach($update_sites as $upSite){
						if (strcmp($upSite['location'], (string)$xml->updateservers->server) != 0) {
							// the extension was already there: we just update the server if different
							$query="UPDATE `#__update_sites` SET `location`=".$db->quote((string)$xml->updateservers->server['name'])."
					         WHERE update_site_id=".$update_sites_extensions->update_site_id;
							$db->setQuery($query);
							$db->query();
						}
					}

				}

			} else {
				echo ('<br />UPDATE SERVER NOT FOUND IN XML FILE:'.$extensionXmlFileName);
			}
		}

		/**
		 * @param $type= 'plugin'
		 * @param $element= 'textinput'
		 * @param $src = path . DS . 'plugins' . DS . $group . DS . $element;
		 */
		function getExtensionXmlFileName($type, $element, $dst ){
			if ($type=='plugin') {
				$extensionXmlFileName=  $dst. DS . $element.  '.xml';
			} else if ($type=='module'){
				$extensionXmlFileName = $dst. DS . $element.DS . $element. '.xml';
			}
			return $extensionXmlFileName;
		}

		/**
		 * @author Max Milbers
		 * @param string $tablename
		 * @param string $fields
		 * @param string $command
		 */
		private function alterTable ($tablename, $fields, $command = 'CHANGE') {

			if (empty($this->db)) {
				$this->db = JFactory::getDBO ();
			}

			$query = 'SHOW COLUMNS FROM `' . $tablename . '` ';
			$this->db->setQuery ($query);
			$columns = $this->db->loadResultArray (0);

			foreach ($fields as $fieldname => $alterCommand) {
				if (in_array ($fieldname, $columns)) {
					$query = 'ALTER TABLE `' . $tablename . '` ' . $command . ' COLUMN `' . $fieldname . '` ' . $alterCommand;

					$this->db->setQuery ($query);
					$this->db->query ();
				}
			}

		}

		/**
		 *
		 * @author Max Milbers
		 * @param string $table
		 * @param string $field
		 * @param string $fieldType
		 * @return boolean This gives true back, WHEN it altered the table, you may use this information to decide for extra post actions
		 */
		private function checkAddFieldToTable ($table, $field, $fieldType) {

			$query = 'SHOW COLUMNS FROM `' . $table . '` ';
			$this->db->setQuery ($query);
			$columns = $this->db->loadResultArray (0);

			if (!in_array ($field, $columns)) {

				$query = 'ALTER TABLE `' . $table . '` ADD ' . $field . ' ' . $fieldType;
				$this->db->setQuery ($query);
				if (!$this->db->query ()) {
					$app = JFactory::getApplication ();
					$app->enqueueMessage ('Install checkAddFieldToTable ' . $this->db->getErrorMsg ());
					return FALSE;
				} else {
					return TRUE;
				}
			}
			return FALSE;
		}

		private function updateShipperToShipment () {

			if (empty($this->db)) {
				$this->db = JFactory::getDBO ();
			}
			if (version_compare (JVERSION, '1.6.0', 'ge')) {
				// Joomla! 1.6 code here
				$table = JTable::getInstance ('extension');
				$tableName = '#__extensions';
				$idfield = 'extension_id';
			} else {

				// Joomla! 1.5 code here
				$table = JTable::getInstance ('plugin');
				$tableName = '#__plugins';
				$idfield = 'id';
			}

			$q = 'SELECT ' . $idfield . ' FROM ' . $tableName . ' WHERE `folder` = "vmshipper" ';
			$this->db->setQuery ($q);
			$result = $this->db->loadResult ();
			if ($result) {
				$q = 'UPDATE `' . $tableName . '` SET `folder`="vmshipment" WHERE `extension_id`= ' . $result;
				$this->db->setQuery ($q);
				$this->db->query ();
			}
		}

		/**
		 * copy all $src to $dst folder and remove it
		 *
		 * @author Max Milbers
		 * @param String $src path
		 * @param String $dst path
		 * @param String $type modulesBE, modules, plugins, languageBE, languageFE
		 */
		private function recurse_copy ($src, $dst) {

			if($this->dontMove) return true;
			$dir = opendir ($src);
			$this->createIndexFolder ($dst);

			if (is_resource ($dir)) {
				while (FALSE !== ($file = readdir ($dir))) {
					if (($file != '.') && ($file != '..')) {
						if (is_dir ($src . DS . $file)) {
							$this->recurse_copy ($src . DS . $file, $dst . DS . $file);
						} else {
							if (JFile::exists ($dst . DS . $file)) {
								if (!JFile::delete ($dst . DS . $file)) {
									$app = JFactory::getApplication ();
									$app->enqueueMessage ('Couldnt delete ' . $dst . DS . $file);
									return false;
								}
							}
							if (!JFile::move ($src . DS . $file, $dst . DS . $file)) {
								$app = JFactory::getApplication ();
								$app->enqueueMessage ('Couldnt move ' . $src . DS . $file . ' to ' . $dst . DS . $file);
								return false;
							}
						}
					}
				}
				closedir ($dir);
				if (is_dir ($src)) {
					JFolder::delete ($src);
				}
			} else {
				$app = JFactory::getApplication ();
				$app->enqueueMessage ('Couldnt read dir ' . $dir . ' source ' . $src);
				return false;
			}
			return true;
		}


		public function uninstall () {

			return TRUE;
		}

		/**
		 * creates a folder with empty html file
		 *
		 * @author Max Milbers
		 *
		 */
		public function createIndexFolder ($path) {

			if (JFolder::create ($path)) {
				if (!JFile::exists ($path . DS . 'index.html')) {
					JFile::copy (JPATH_ROOT . DS . 'components' . DS . 'index.html', $path . DS . 'index.html');
				}
				return TRUE;
			}
			return FALSE;
		}

	}

	if (!defined ('_VM_SCRIPT_INCLUDED')) {
		// PLZ look in #vminstall.php# to add your plugin and module
		function com_install () {

			if (!version_compare (JVERSION, '1.6.0', 'ge')) {
				$vmInstall = new com_virtuemart_allinoneInstallerScript();
				$vmInstall->vmInstall ();
			}
			return TRUE;
		}

		function com_uninstall () {

			return TRUE;
		}
	}
} //if defined
// pure php no tag
