<?php
defined('_JEXEC') or die('Restricted access');

/**
 *
 * Realex payment plugin
 *
 * @author Valerie Isaksen
 * @version $Id: creditcards.php 8242 2014-08-27 09:47:57Z alatak $
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

if (!class_exists('VmConfig')) {
	require(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_virtuemart' . DS . 'helpers' . DS . 'config.php');
}
if (!class_exists('ShopFunctions')) {
	require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'shopfunctions.php');
}

/**
 * @copyright    Copyright (C) 2009 Open Source Matters. All rights reserved.
 * @license    GNU/GPL
 */
// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

/**
 * Renders a multiple item select element
 *
 */
jimport('joomla.form.formfield');

class JFormFieldCreditCards extends JFormField {

	/**
	 * Element name
	 *
	 * @access    protected
	 * @var        string
	 */

	public $type = 'creditcards';

	protected function getInput() {
		JFactory::getLanguage()->load('plg_vmpayment_realex_hpp_api', JPATH_ADMINISTRATOR);

		$creditcards = RealexHelperRealex::getRealexCreditCards();

		$prefix = 'VMPAYMENT_REALEX_CC_';

		$fields = array();
		foreach ($creditcards as $creditcard) {
			$fields[$creditcard]['value'] = $creditcard;
			$fields[$creditcard]['text'] = vmText::_($prefix . strtoupper($fields[$creditcard]['value']));
		}

		return JHTML::_('select.genericlist', $creditcards, $this->name, 'size="1"', 'value', 'title', $this->value);

	}

}

