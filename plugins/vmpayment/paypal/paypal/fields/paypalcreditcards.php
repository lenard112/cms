<?php
/**
 *
 * Paypal  payment plugin
 *
 * @author Jeremy Magne
 * @version $Id: paypal.php 7217 2013-09-18 13:42:54Z alatak $
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

if (!class_exists('ShopFunctions')) {
	require(JPATH_VM_ADMINISTRATOR . '/helpers/shopfunctions.php');
}
if (!class_exists('PaypalHelperPaypal')) {
	require(JPATH_ROOT . '/plugins/vmpayment/paypal/paypal/helpers/paypal.php');
}

jimport('joomla.form.formfield');
class JFormFieldPaypalCreditcards extends JFormField {

	protected $type = 'Paypalcreditcards';

	protected function getInput() {
		JFactory::getLanguage()->load('plg_vmpayment_paypal', JPATH_ADMINISTRATOR);

		$creditcards = PaypalHelperPaypal::getPaypalCreditCards();

		$prefix = 'VMPAYMENT_PAYPAL_CC_';

		$fields = array();
		foreach ($creditcards as $creditcard) {
			$fields[$creditcard]['value'] = $creditcard;
			$fields[$creditcard]['text'] = vmText::_($prefix . strtoupper($fields[$creditcard]['value']));
		}

		return JHTML::_('select.genericlist', $creditcards, $this->name, 'size="1"', 'value', 'title', $this->value);

	}

}