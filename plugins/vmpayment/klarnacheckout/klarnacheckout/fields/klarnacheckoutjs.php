<?php
/**
 *
 * Paypal payment plugin
 *
 * @author Jeremy Magne
 * @version $Id: customjs.php 7301 2013-10-29 17:45:07Z alatak $
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


defined('_JEXEC') or die();


jimport('joomla.form.formfield');
class JFormFieldklarnacheckoutjs extends JFormField {

	protected $type = 'klarnacheckoutjs';

	function getInput() {


		$doc = JFactory::getDocument();
		$doc->addScript(JURI::root(true) . '/plugins/vmpayment/klarnacheckout/klarnacheckout/assets/js/admin.js');
		$doc->addStyleSheet(JURI::root(true) . '/plugins/vmpayment/klarnacheckout/klarnacheckout/assets/css/klarnacheckout.css');


		return '';
	}

}
