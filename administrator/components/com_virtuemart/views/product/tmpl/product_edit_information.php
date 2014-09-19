<?php
/**
 *
 * Main product information
 *
 * @package	VirtueMart
 * @subpackage Product
 * @author RolandD
 * @todo Price update calculations
 * @link http://www.virtuemart.net
 * @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: product_edit_information.php 8219 2014-08-20 10:45:50Z Milbo $
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

// set row counter
$i=0;
?>
<div class="vm-lang-list-container">
	<?php echo $this->langList; ?>
</div>

<fieldset>
	<legend><?php echo vmText::_('COM_VIRTUEMART_PRODUCT_INFORMATION'); echo ' id: '.$this->product->virtuemart_product_id ?></legend>
    <table class="adminform" width="100%">
		<tr class="row<?php echo $i?>">
			<td style="min-width:75px;max-width:200px;width:130px;">
				<?php echo vmText::_('COM_VIRTUEMART_PRODUCT_FORM_NAME') ?>
			</td>
			<td>
				<input type="text" class="inputbox"  name="product_name" id="product_name" value="<?php echo htmlspecialchars($this->product->product_name); ?>" size="32" maxlength="255" />
			</td>
			<td colspan="2">
				<label><?php echo VmHTML::checkbox('published', $this->product->published); ?><?php echo vmText::_('COM_VIRTUEMART_PUBLISHED') ?></label>
				<label><?php echo VmHTML::checkbox('product_special', $this->product->product_special); ?> <?php echo vmText::_('COM_VIRTUEMART_PRODUCT_FORM_SPECIAL') ?></label>
			</td>
			<td>
				<span class="hastip" title="<?php echo vmText::_('COM_VIRTUEMART_PRODUCT_FORM_ALIAS_TIP');?>"><?php echo vmText::_('COM_VIRTUEMART_PRODUCT_FORM_ALIAS') ?></span>
			</td>
			<td height="18">
				<input type="text" class="inputbox"  name="slug" id="slug" value="<?php echo $this->product->slug; ?>" size="32" maxlength="255" />
			</td>
		</tr>

		<?php $i = 1 - $i; ?>
		<tr class="row<?php echo $i?>">
			<td>
				<span  class="hastip" title="<?php echo vmText::_('COM_VIRTUEMART_PRODUCT_SKU_TIP') ?>"><?php echo vmText::_('COM_VIRTUEMART_PRODUCT_SKU') ?></span>
			</td>
			<td>
				<input type="text" class="inputbox" name="product_sku" id="product_sku" value="<?php echo $this->product->product_sku; ?>" size="32" maxlength="64" />
			</td>
			<td width="130px">
				<span class="hastip" title="<?php echo vmText::_('COM_VIRTUEMART_PRODUCT_GTIN_TIP') ?>"><?php echo vmText::_('COM_VIRTUEMART_PRODUCT_GTIN') ?></span>
			</td>
			<td>
				<input type="text" class="inputbox" name="product_gtin" id="product_gtin" value="<?php echo $this->product->product_gtin; ?>" size="32" maxlength="64" />
			</td>
			<td>
				<span class="hastip" title="<?php echo vmText::_('COM_VIRTUEMART_PRODUCT_MPN_TIP') ?>"><?php echo vmText::_('COM_VIRTUEMART_PRODUCT_MPN') ?></span>
			</td>
			<td>
				<input type="text" class="inputbox" name="product_mpn" id="product_mpn" value="<?php echo $this->product->product_mpn; ?>" size="32" maxlength="64" />
			</td>
		</tr>

		<?php $i = 1 - $i; ?>
		<tr class="row<?php echo $i?>">
			<?php if(isset($this->lists['manufacturers'])) { ?>
				<td>
					<?php echo vmText::_('COM_VIRTUEMART_MANUFACTURER') ?>
				</td>
				<td>
					<?php echo $this->lists['manufacturers'];?>
				</td>
			<?php } else {
				echo '<td></td><td></td>';
			}?>
			<td>
				<?php echo vmText::_('COM_VIRTUEMART_PRODUCT_DETAILS_PAGE') ?>
			</td>
			<td>
				<?php echo JHTML::_('Select.genericlist', $this->productLayouts, 'layout', 'size=1', 'value', 'text', $this->product->layout); ?>
			</td>
			<td>
				<?php echo vmText::_('COM_VIRTUEMART_PRODUCT_FORM_URL') ?>
			</td>
			<td>
				<input type="text" class="inputbox" name="product_url" value="<?php echo $this->product->product_url; ?>" size="32" maxlength="255" />
			</td>
		</tr>
		<?php $i = 1 - $i; ?>
		<tr class="row<?php echo $i?>">
			<td>
				<?php echo vmText::_('COM_VIRTUEMART_CATEGORY_S') ?>
			</td>
			<td>
				<select class="inputbox" id="categories" name="categories[]" multiple="multiple"  data-placeholder="<?php echo vmText::_('COM_VIRTUEMART_DRDOWN_SELECT_SOME_OPTIONS')  ?>" size="10">
					<option value=""><?php echo vmText::_('COM_VIRTUEMART_UNCATEGORIZED') ?></option>
					<?php echo $this->category_tree; ?>
				</select>
			</td>
			<?php
			// It is important to have all product information in the form, since we do not preload the parent
			// I place the ordering here, maybe we make it editable later.
			if(!isset($this->product->ordering)) {
				$this->product->ordering = 0;
				?><input type="hidden" value="<?php echo $this->product->ordering ?>" name="ordering"> <?php
			} ?>
			<td>
				<span class="hasTip" title="<?php echo vmText::_ ('COM_VIRTUEMART_SHOPPER_FORM_GROUP_PRODUCT_TIP'); ?>">
				<?php echo vmText::_('COM_VIRTUEMART_SHOPPER_FORM_GROUP') ?></span>
			</td>
			<td>
				<?php echo $this->shoppergroupList; ?>
			</td>
			<?php if(Vmconfig::get('multix','none')!=='none') { ?>
			<td>
				<?php echo vmText::_('COM_VIRTUEMART_VENDOR') ?>
			</td>
			<td>
				<?php echo $this->lists['vendors'];?>
			</td>
		<?php } else {
				echo '<td><td>';
			}?>
		</tr>
	</table>
</fieldset>

			<!-- Product pricing -->
			<fieldset>
			    <legend><?php
					echo vmText::sprintf('COM_VIRTUEMART_PRODUCT_FORM_PRICES',$this->activeShoppergroups); ?></legend>
			
				<?php
				//$product = $this->product;
			
				if (empty($this->product->prices)) {
					$this->product->prices[] = array();
				}
	$this->i = 0;
	$rowColor = 0;
	if (!class_exists ('calculationHelper')) {
		require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'calculationh.php');
	}
	$calculator = calculationHelper::getInstance ();
	$currency_model = VmModel::getModel ('currency');
	$currencies = $currency_model->getCurrencies ();
	$nbPrice = count ($this->product->allPrices);
	$this->priceCounter = 0;
	$this->product->allPrices[$nbPrice] = VmModel::getModel()->fillVoidPrice();

	if (!class_exists ('calculationHelper')) {
		require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'calculationh.php');
	}
	$calculator = calculationHelper::getInstance ();
	?>
    <table border="0" width="100%" cellpadding="2" cellspacing="3" id="mainPriceTable" class="adminform  ">
        <tbody id="productPriceBody">
		<?php

		foreach ($this->product->allPrices as $k => $sPrices) {


			if(empty($this->product->allPrices[$k]['product_currency'])){
				$this->product->allPrices[$k]['product_currency'] = $this->vendor->vendor_currency;
			}

			$this->product->selectedPrice = $k;
			$this->calculatedPrices = $calculator->getProductPrices ($this->product);
			$this->product->allPrices[$k] = array_merge($this->product->allPrices[$k],$this->calculatedPrices);

			$currency_model = VmModel::getModel ('currency');
			$this->lists['currencies'] = JHtml::_ ('select.genericlist', $currencies, 'mprices[product_currency][' . $this->priceCounter . ']', '', 'virtuemart_currency_id', 'currency_name', $this->product->allPrices[$k]['product_currency']);

			$DBTax = ''; //vmText::_('COM_VIRTUEMART_RULES_EFFECTING') ;
			foreach ($calculator->rules['DBTax'] as $rule) {
				$DBTax .= $rule['calc_name'] . '<br />';
			}
			$this->DBTaxRules = $DBTax;

			$tax = ''; //vmText::_('COM_VIRTUEMART_TAX_EFFECTING').'<br />';
			foreach ($calculator->rules['Tax'] as $rule) {
				$tax .= $rule['calc_name'] . '<br />';
			}
			foreach ($calculator->rules['VatTax'] as $rule) {
				$tax .= $rule['calc_name'] . '<br />';
			}
			$this->taxRules = $tax;

			$DATax = ''; //vmText::_('COM_VIRTUEMART_RULES_EFFECTING');
			foreach ($calculator->rules['DATax'] as $rule) {
				$DATax .= $rule['calc_name'] . '<br />';
			}
			$this->DATaxRules = $DATax;

			if (!isset($this->product->product_tax_id)) {
				$this->product->product_tax_id = 0;
			}
			$this->lists['taxrates'] = ShopFunctions::renderTaxList ($this->product->allPrices[$k]['product_tax_id'], 'mprices[product_tax_id][' . $this->priceCounter . ']');
			if (!isset($this->product->allPrices[$k]['product_discount_id'])) {
				$this->product->allPrices[$k]['product_discount_id'] = 0;
			}
			$this->lists['discounts'] = $this->renderDiscountList ($this->product->allPrices[$k]['product_discount_id'], 'mprices[product_discount_id][' . $this->priceCounter . ']');

			$this->lists['shoppergroups'] = ShopFunctions::renderShopperGroupList ($this->product->allPrices[$k]['virtuemart_shoppergroup_id'], false, 'mprices[virtuemart_shoppergroup_id][' . $this->priceCounter . ']');

			if ($this->priceCounter == $nbPrice) {
				$tmpl = "productPriceRowTmpl";
				$this->product->allPrices[$k]['virtuemart_product_price_id'] = '';
			} else {
				$tmpl = "productPriceRowTmpl_" . $this->priceCounter;
			}

			?>
        <tr id="<?php echo $tmpl ?>" class="removable row<?php echo $rowColor?>">
	            <td width="100%">
		        <span class="vmicon vmicon-16-move price_ordering"></span>
		        <?php /* <span class="vmicon vmicon-16-new price-clone" ></span> */ ?>
                <span class="vmicon vmicon-16-remove price-remove"></span>
				<?php //echo vmText::_ ('COM_VIRTUEMART_PRODUCT_PRICE_ORDER');
				echo $this->loadTemplate ('price'); ?>
			 </td>
        </tr>
			<?php
			$this->priceCounter++;
		}
		?>
        </tbody>
    </table>
    <div class="button2-left btn-wrapper">
        <div class="blank">
            <a  class="btn btn-small" href="#" id="add_new_price"><?php echo vmText::_ ('COM_VIRTUEMART_PRODUCT_ADD_PRICE') ?> </a>
        </div>
    </div>

</fieldset>
</tr>
		</td>
	</tr>
	<tr>
	<td colspan="2" >
	<fieldset>
		<legend>
		<?php echo vmText::_('COM_VIRTUEMART_PRODUCT_FORM_CHILD_PARENT'); ?></legend>
		<table class="adminform">
			<tr class="row<?php echo $i?>">
				<td width="50%">
				<?php
					if ($this->product->virtuemart_product_id) {
						$link=JROUTE::_('index.php?option=com_virtuemart&view=product&task=createChild&virtuemart_product_id='.$this->product->virtuemart_product_id.'&'.JSession::getFormToken().'=1' );
						$add_child_button="";
					} else {
						$link="";
						$add_child_button=" not-active";
					}
?>
					<div class="button2-left <?php echo $add_child_button ?> btn-wrapper">
						<div class="blank">
							<?php if ($link) { ?>
								<a href="<?php echo $link ?>" class="btn btn-small">
							<?php } else { ?>
								<span class="hasTip" title="<?php echo vmText::_ ('COM_VIRTUEMART_PRODUCT_ADD_CHILD_TIP'); ?>">
							<?php } ?>
								<?php echo vmText::_('COM_VIRTUEMART_PRODUCT_ADD_CHILD');?>
							<?php if ($link) { ?>
								</a>
							<?php } else{ ?>
								</span>
							<?php } ?>
						</div>
					</div>
				</td>
				<th>
					<?php echo vmText::_('COM_VIRTUEMART_PRODUCT_FORM_PARENT') ?>
				</th>
				<td width="71%"> 
					<?php if ($this->product->product_parent_id) {
							$result = vmText::_('COM_VIRTUEMART_EDIT').' ' . $this->product_parent->product_name;
					echo ' | '.JHtml::_('link', JRoute::_('index.php?option=com_virtuemart&view=product&task=edit&virtuemart_product_id='.$this->product->product_parent_id),
								$this->product_parent->product_name, array('title' => $result)).' | '.$this->parentRelation;
						}
					?>
				</td>
			</tr>
			<?php $i = 1 - $i; ?>
			<tr class="row<?php echo $i?>" >
				<td width="79%" colspan = "3">
					<?php if (false ){//count($this->product_childs)>0 ) {
					
		                	$customs = array();
		                	if(!empty($this->product->customfields)){
		                		foreach($this->product->customfields as $custom){
		                			//vmdebug('my custom',$custom);
		                			if($custom->field_type=='A'){
		                				$customs[] = $custom;
		                			}
		                		}
		                	}
							// vmdebug('ma $customs',$customs);
							?>

						<table class="adminform">
							<tr>
								<th style="text-align: left !important;"><?php echo vmText::_('COM_VIRTUEMART_PRODUCT_CHILD') ?></th>
								<th style="text-align: left !important;"><?php echo vmText::_('COM_VIRTUEMART_PRODUCT_CHILD_NAME')?></th>
								<th style="text-align: left !important;" width="5%"><?php echo vmText::_('COM_VIRTUEMART_PRODUCT_FORM_PRICE_COST')?></th>
								<th style="text-align: left !important;"><?php echo vmText::_('COM_VIRTUEMART_PRODUCT_FORM_IN_STOCK')?></th>
								<th style="text-align: left !important;" width="5%"><?php echo vmText::_('COM_VIRTUEMART_PRODUCT_FORM_ORDERED_STOCK')?></th>
								<?php foreach($customs as $custom){ ?>
									<th style="text-align: left !important;">
										<?php echo vmText::sprintf('COM_VIRTUEMART_PRODUCT_CUSTOM_FIELD_N',vmText::_('COM_VIRTUEMART_'.strtoupper($custom->customfield_value)))?>
									</th>
								<?php } ?>
								<th style="text-align: left !important;" width="5%"><?php echo vmText::_('COM_VIRTUEMART_ORDERING')?></th>
								<th style="text-align: left !important;" width="5%"><?php echo vmText::_('COM_VIRTUEMART_PUBLISHED')?></th>
							</tr>
							<?php foreach ($this->product_childs as $child  ) {
								$i = 1 - $i; ?>
								<tr class="row<?php echo $i ?>">
									<td>
										<?php echo JHTML::_('link', JRoute::_('index.php?option=com_virtuemart&view=product&task=edit&virtuemart_product_id='.$child->virtuemart_product_id), $child->slug, array('title' => vmText::_('COM_VIRTUEMART_EDIT').' '.$child->product_name)) ?>
										<input type="hidden" name="childs[<?php echo $child->virtuemart_product_id ?>][slug]" id="child<?php echo $child->virtuemart_product_id ?>slug" value="<?php echo $child->slug ?>" />
									</td>
									<td><input type="text" class="inputbox" name="childs[<?php echo $child->virtuemart_product_id ?>][product_name]" id="child<?php echo $child->virtuemart_product_id ?>product_name" size="32" value="<?php echo htmlspecialchars($child->product_name) ?>" /></td>
									<td><input type="text" class="inputbox" name="childs[<?php echo $child->virtuemart_product_id ?>][mprices][product_price][]" size="10" value="<?php echo $child->allPrices[$child->selectedPrice]['product_price'] ?>" /><input type="hidden" name="childs[<?php echo $child->virtuemart_product_id ?>][mprices][virtuemart_product_price_id][]" value="<?php echo $child->allPrices[$child->selectedPrice]['virtuemart_product_price_id'] ?>"  ></td>
									<td><?php echo $child->product_in_stock ?></td>
									<td><?php echo $child->product_ordered ?></td>
									<?php foreach($customs as $custom){
										$attrib = $custom->customfield_value;
										if(isset($child->$attrib)){
											$childAttrib = $child->$attrib;
										} else {
											//vmdebug('unset? use Fallback product_name instead $attrib '.$attrib,$child);
											$childAttrib = $child->product_name;
										}
										//vmdebug(' $attrib '.$attrib,$child,$childAttrib);
										?>
										<td><input type="text" class="inputbox" name="childs[<?php echo $child->virtuemart_product_id ?>][<?php echo $attrib ?>]" size="20" value="<?php echo $childAttrib ?>" /></td>
										<?php
									}
									?>
									<td>
	                                    <input type="text" class="inputbox" name="childs[<?php echo $child->virtuemart_product_id ?>][pordering]" size="2" value="<?php echo $child->pordering ?>" /></td>
									</td>
									<td>
										<?php echo VmHTML::checkbox('childs['.$child->virtuemart_product_id.'][published]', $child->published) ?>
									</td>
								</tr>
							<?php } ?>
						</table>
					<?php } ?>
				</td>
			</tr>
		</table>
	</fieldset>
</tr>
<tr>
	<td width="100%" valign="top" colspan="2">
		<fieldset>
			<legend>
				<?php echo vmText::_('COM_VIRTUEMART_PRODUCT_PRINT_INTNOTES'); ?>
			</legend>
			<textarea style="width: 100%;" class="inputbox" name="intnotes" id="intnotes" cols="35" rows="6">
					<?php echo $this->product->intnotes; ?></textarea>
		</fieldset>
	</td>
</tr>



<script type="text/javascript">
    jQuery(document).ready(function () {
        jQuery("#mainPriceTable").dynoTable({
            removeClass:'.price-remove', //remove class name in  table
            cloneClass:'.price-clone', //Custom cloner class name in  table
            addRowTemplateId:'#productPriceRowTmpl', //Custom id for  row template
            addRowButtonId:'#add_new_price', //Click this to add a price
            lastRowRemovable:true, //let the table be empty.
            orderable:true, //prices can be rearranged
            dragHandleClass:".price_ordering", //class for the click and draggable drag handle
            onRowRemove:function () {
            },
            onRowClone:function () {
                //var myLastRow = jQuery('input [name="mprices[virtuemart_product_price_id][]"]',jQuery('#mainPriceTable tr:last') );
                //var myLastRow = jQuery('#mainPriceTable tr:last' );
	            /*jQuery.each(myLastRow,function(key, element1) {
                           jQuery.each(element1,function(key, element) {
                                alert('key: ' + key + '\n' + 'value: ' + element);
                           })
                });*/
            },
            onRowAdd:function () {
            },
            onTableEmpty:function () {
            },
            onRowReorder:function () {
            }
        });
    });

</script>

<script type="text/javascript">
	var tax_rates = new Array();
	<?php
		if( property_exists($this, 'taxrates') && is_array( $this->taxrates )) {
			foreach( $this->taxrates as $key => $tax_rate ) {
				echo 'tax_rates["'.$tax_rate->tax_rate_id.'"] = '.$tax_rate->tax_rate."\n";
			}
		}
	?>
</script>