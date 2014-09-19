<?php
/**
 *
 * Layout for the shopping cart
 *
 * @package    VirtueMart
 * @subpackage Cart
 * @author Max Milbers
 *
 * @link http://www.virtuemart.net
 * @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: cart.php 2551 2010-09-30 18:52:40Z milbo $
 */

// Check to ensure this file is included in Joomla!
defined ('_JEXEC') or die('Restricted access');

JHtml::_ ('behavior.formvalidation');
$document = JFactory::getDocument ();
$document->addScriptDeclaration ("
//<![CDATA[
	jQuery(document).ready(function($) {
		if ( $('#STsameAsBTjs').is(':checked') ) {
			$('#output-shipto-display').hide();
		} else {
			$('#output-shipto-display').show();
		}
		$('#STsameAsBTjs').click(function(event) {
			if($(this).is(':checked')){
				$('#STsameAsBT').val('1') ;
				$('#output-shipto-display').hide();
			} else {
				$('#STsameAsBT').val('0') ;
				$('#output-shipto-display').show();
			}
			location.reload();
		});
	});
//]]>
");
$document->addScriptDeclaration ('
//<![CDATA[
	jQuery(document).ready(function($) {
		jQuery("#checkoutFormSubmit").bind("click dblclick", function(e){
			e.preventDefault();
			jQuery(this).attr("disabled", "true");
			jQuery(this).removeClass( "vm-button-correct" );
			jQuery(this).addClass( "vm-button" );
			jQuery(this).fadeIn( 400 );
			var name = jQuery(this).attr("name");
			$("#checkoutForm").append("<input name=\""+name+"\" value=\"1\" type=\"hidden\">");
			$("#checkoutForm").submit();
		});
	});
//]]>
');
 ?>

<div class="cart-view">
	<div>
		<div class="width50 floatleft">
			<h1><?php echo vmText::_ ('COM_VIRTUEMART_CART_TITLE'); ?></h1>
		</div>
		<?php if (VmConfig::get ('oncheckout_show_steps', 1) && $this->checkout_task === 'confirm') {
		echo '<div class="checkoutStep" id="checkoutStep4">' . vmText::_ ('COM_VIRTUEMART_USER_FORM_CART_STEP4') . '</div>';
	} ?>
		<div class="width50 floatleft right">
			<?php // Continue Shopping Button
			if (!empty($this->continue_link_html)) {
				echo $this->continue_link_html;
			} ?>
		</div>
		<div class="clear"></div>
	</div>

	<?php echo shopFunctionsF::getLoginForm ($this->cart, FALSE);

	// This displays the form to change the current shopper
	$adminID = JFactory::getSession()->get('vmAdminID');
	if ((JFactory::getUser()->authorise('core.admin', 'com_virtuemart') || JFactory::getUser($adminID)->authorise('core.admin', 'com_virtuemart')) && (VmConfig::get ('oncheckout_change_shopper', 0))) { 
		echo $this->loadTemplate ('shopperform');
	}

	$taskRoute = '';
	?><form method="post" id="checkoutForm" name="checkoutForm" action="<?php echo JRoute::_ ('index.php?option=com_virtuemart&view=cart' . $taskRoute, $this->useXHTML, $this->useSSL); ?>">
		<?php
		if(VmConfig::get('multixcart')=='byselection'){
			echo shopFunctions::renderVendorFullVendorList($this->cart->vendorId);
		}
		echo $this->loadTemplate ('address');
		// This displays the pricelist MUST be done with tables, because it is also used for the emails
		echo $this->loadTemplate ('pricelist');

		if (!empty($this->checkoutAdvertise)) {
			?> <div id="checkout-advertise-box"> <?php
			foreach ($this->checkoutAdvertise as $checkoutAdvertise) {
				?>
				<div class="checkout-advertise">
					<?php echo $checkoutAdvertise; ?>
				</div>
				<?php
			}
			?></div><?php
		}

		echo $this->loadTemplate ('cartfields');

		?> <div class="checkout-button-top"> <?php
			echo $this->checkout_link_html;
		?></div>

		<?php // Continue and Checkout Button END ?>
		<input type='hidden' name='order_language' value='<?php echo $this->order_language; ?>'/>
		<input type='hidden' name='task' value='updatecart'/>
		<input type='hidden' name='option' value='com_virtuemart'/>
		<input type='hidden' name='view' value='cart'/>
	</form>
</div>

