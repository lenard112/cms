<?php
/**
 *
 * Realex payment plugin
 *
 * @author Valerie Isaksen
 * @version $Id: getrealex.php 8244 2014-08-27 10:53:31Z alatak $
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
defined('JPATH_BASE') or die();

/**
 * Renders a label element
 */
jimport('joomla.form.formfield');
class JFormFieldGetRealex extends JFormField {
	/**
	 * Element name
	 *
	 * @access    protected
	 * @var        string
	 */
	public $type = 'getrealex';

	protected function getInput() {
		$doc = JFactory::getDocument();
		$doc->addScript(JURI::root(true) . '/plugins/vmpayment/realex_hpp_api/realex_hpp_api/assets/js/admin.js');
		$doc->addStyleSheet(JURI::root(true) . '/plugins/vmpayment/realex_hpp_api/realex_hpp_api/assets/css/admin.css');

		$url = "http://www.realexpayments.com/partner-referral?id=virtuemart";
		$logo = '<img src="http://www.realexpayments.com/images/logo_realex_large.png" width="150"/>';
		$html = '<a target="_blank" href="' . $url . '" id="getrealex_link" ">' . $logo . '</a><br />';
		$html .= '<a target="_blank" href="' . $url . '" id="getrealex_link" ">' . vmText::_('VMPAYMENT_REALEX_HPP_API_REGISTER') . '</a>';

		return $html;
	}
}