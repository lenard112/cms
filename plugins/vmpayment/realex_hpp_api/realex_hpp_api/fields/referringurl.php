<?php
/**
 *
 * Realex payment plugin
 *
 * @author Valerie Isaksen
 * @version $Id: referringurl.php 8200 2014-08-14 11:09:44Z alatak $
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

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

/**
 * Renders a label element
 */
jimport('joomla.form.formfield');
class JFormFieldReferringurl extends JFormField {
	/**
	 * Element name
	 *
	 * @access    protected
	 * @var        string
	 */
	public $type = 'referringurl';

	protected function getInput() {

		$this->value = JURI::root() . 'plugins/vmpayment/realex/jump.php';

		$class = !empty($this->class)? 'class="' . $this->class . '"' : 'class="text_area"';
		if ($this->editable == 'true') {
			$size = ($this->size) ? 'size="' . $this->size . '"' : '';

			return '<input type="text" name="' . $this->name . '" id="' . $this->name . '" value="' . $this->value . '" ' . $class . ' ' . $size . ' />';
		} else {
			return '<label for="' . $this->name . '"' . $class . '>' . $this->value . '</label>';
		}
	}
}