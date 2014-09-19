<?php
/**
 *
 * Description
 *
 * @package    VirtueMart
 * @subpackage
 * @author Max Milbers
 * @link http://www.virtuemart.net
 * @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved by the author.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id:$
 */

// Check to ensure this file is included in Joomla!
defined ('_JEXEC') or die('Restricted access');

if (!class_exists ('VmModel')) {
	require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'vmmodel.php');
}

/**
 * Model for VirtueMart Customs Fields
 *
 * @package        VirtueMart
 */
class VirtueMartModelCustomfields extends VmModel {

	/**
	 * constructs a VmModel
	 * setMainTable defines the maintable of the model
	 *
	 * @author Max Milbers
	 */
	function __construct () {

		parent::__construct ('virtuemart_customfield_id');
		$this->setMainTable ('product_customfields');
	}

	/** @return autorized Types of data **/
	function getField_types () {

		return array('S' => 'COM_VIRTUEMART_CUSTOM_STRING',
					 'C' => 'COM_VIRTUEMART_CHILDVARIANT',
		             'D' => 'COM_VIRTUEMART_DATE',
		             'T' => 'COM_VIRTUEMART_TIME',
		             'M' => 'COM_VIRTUEMART_IMAGE',
		             'B' => 'COM_VIRTUEMART_CUSTOM_BOOLEAN',
		             'G' => 'COM_VIRTUEMART_CUSTOM_GROUP',
		             'A' => 'COM_VIRTUEMART_CHILD_GENERIC_VARIANT',
		             'X' => 'COM_VIRTUEMART_CUSTOM_EDITOR',
		             'Y' => 'COM_VIRTUEMART_CUSTOM_TEXTAREA',
		             'E' => 'COM_VIRTUEMART_CUSTOM_EXTENSION',
		             'R'=>'COM_VIRTUEMART_RELATED_PRODUCTS',
					'Z'=>'COM_VIRTUEMART_RELATED_CATEGORIES'
		);

		// 'U'=>'COM_VIRTUEMART_CUSTOM_CART_USER_VARIANT',
		// 'C'=>'COM_VIRTUEMART_CUSTOM_PRODUCT_CHILD',
		// 'G'=>'COM_VIRTUEMART_CUSTOM_PRODUCT_CHILD_GROUP',
		//
	}


	/**
	 * Gets a single custom by virtuemart_customfield_id
	 *
	 * @param string $type
	 * @param string $mime mime type of custom, use for exampel image
	 * @return customobject
	 */
	function getCustomfield ($id = 0) {

		return $this->getData($id);

	}

	public static function getProductCustomSelectFieldList(){

		$q = 'SELECT c.`virtuemart_custom_id`, c.`custom_parent_id`, c.`virtuemart_vendor_id`, c.`custom_jplugin_id`, c.`custom_element`, c.`admin_only`, c.`custom_title`, c.`show_title` , c.`custom_tip`,
		c.`custom_value`, c.`custom_desc`, c.`field_type`, c.`is_list`, c.`is_hidden`, c.`is_cart_attribute`, c.`is_input`, c.`layout_pos`, c.`custom_params`, c.`shared`, c.`published`, c.`ordering`, ';
		$q .= 'field.`virtuemart_customfield_id`, field.`virtuemart_product_id`, field.`customfield_value`, field.`customfield_price`,
		field.`customfield_params`, field.`published` as fpublished, field.`override`, field.`disabler`, field.`ordering`
		FROM `#__virtuemart_customs` AS c LEFT JOIN `#__virtuemart_product_customfields` AS field ON c.`virtuemart_custom_id` = field.`virtuemart_custom_id` ';
		return $q;
	}


	function getCustomEmbeddedProductCustomField($virtuemart_customfield_id){

		$db= JFactory::getDBO ();
		$q = VirtueMartModelCustomfields::getProductCustomSelectFieldList();
		if($virtuemart_customfield_id){
			$q .= ' WHERE `virtuemart_customfield_id` ="' . (int)$virtuemart_customfield_id . '"';
		}
		$db->setQuery ($q);
		$field = $db->loadObject ();
		if($field){
			VirtueMartModelCustomfields::bindCustomEmbeddedFieldParams($field,$field->field_type);
		}

		return $field;
	}

	function getCustomEmbeddedProductCustomFields($productIds,$virtuemart_custom_id=0,$cartattribute=-1,$forcefront=FALSE){

		$app = JFactory::getApplication();
		$db= JFactory::getDBO ();
		$q = VirtueMartModelCustomfields::getProductCustomSelectFieldList();

		static $_customFieldByProductId = array();

		$hashCwAttribute = $cartattribute;
		if($hashCwAttribute==-1) $hashCwAttribute = 2;
		$productCustomsCached = array();
		foreach($productIds as $k=>$productId){
			$hkey = (int)$productId.$hashCwAttribute;
			if (array_key_exists ($hkey, $_customFieldByProductId)) {
				//$productCustomsCached = $_customFieldByProductId[$hkey];
				$productCustomsCached = array_merge($productCustomsCached,$_customFieldByProductId[$hkey]);
				unset($productIds[$k]);
			}
		}

		if(is_array($productIds) and count($productIds)>0){
			$q .= 'WHERE `virtuemart_product_id` IN ('.implode(',', $productIds).')';
		} else if(!empty($productIds)){
			$q .= 'WHERE `virtuemart_product_id` = "'.$productIds.'" ';
		} else {
			return $productCustomsCached;
		}
		if(!empty($virtuemart_custom_id)){
			if(is_numeric($virtuemart_custom_id)){
				$q .= ' AND c.`virtuemart_custom_id`= "' . (int)$virtuemart_custom_id.'" ';
			} else {
				$virtuemart_custom_id = substr($virtuemart_custom_id,0,1); //just in case
				$q .= ' AND c.`field_type`= "' .$virtuemart_custom_id.'" ';
			}
		}
		if(!empty($cartattribute) and $cartattribute!=-1){
			$q .= ' AND ( `is_cart_attribute` = 1 OR `is_input` = 1) ';
		}
		if($forcefront or $app->isSite()){
			$q .= ' AND c.`published` = "1" ';
			$forcefront = true;
		}

		if(!empty($virtuemart_custom_id) and $virtuemart_custom_id!==0){
			$q .= ' ORDER BY field.`ordering` ASC';
		} else {
			if($forcefront or $app->isSite()){
				//$q .= ' GROUP BY c.`virtuemart_custom_id`';
			}

			$q .= ' ORDER BY field.`ordering`,`virtuemart_custom_id` ASC';
		}

		$db->setQuery ($q);
		$productCustoms = $db->loadObjectList ();
		$err=$db->getErrorMsg();
		if($err){
			vmError('getCustomEmbeddedProductCustomFields error in query '.$err);
		}

		foreach($productCustoms as $customfield){
			$hkey = (int)$customfield->virtuemart_product_id.$hashCwAttribute;
			$_customFieldByProductId[$hkey][] = $customfield;
		}
		$productCustoms = array_merge($productCustomsCached,$productCustoms);
		if($productCustoms){

			$customfield_ids = array();
			$customfield_override_ids = array();
			foreach($productCustoms as $field){

				if($field->override!=0){
					$customfield_override_ids[] = $field->override;
				} else if ($field->disabler!=0) {
					$customfield_override_ids[] = $field->disabler;
				}

				$customfield_ids[] = $field->virtuemart_customfield_id;
			}
			$virtuemart_customfield_ids = array_unique( array_diff($customfield_ids,$customfield_override_ids));

			foreach ($productCustoms as $k =>$field) {
				if(in_array($field->virtuemart_customfield_id,$virtuemart_customfield_ids)){

					if($forcefront and $field->disabler){
						unset($productCustoms[$k]);
					} else {
						VirtueMartModelCustomfields::bindCustomEmbeddedFieldParams($field,$field->field_type);
					}

				} else{
					unset($productCustoms[$k]);
				}
			}
			return $productCustoms;
		} else {
			return array();
		}
	}


	static function bindCustomEmbeddedFieldParams(&$obj,$fieldtype){

		//vmdebug('bindCustomEmbeddedFieldParams begin',$obj);
		if(!class_exists('VirtueMartModelCustom')) require(JPATH_VM_ADMINISTRATOR.DS.'models'.DS.'custom.php');

		if ($obj->field_type == 'E') {
			JPluginHelper::importPlugin ('vmcustom');
			$dispatcher = JDispatcher::getInstance ();
			$retValue = $dispatcher->trigger ('plgVmDeclarePluginParamsCustomVM3', array(&$obj));
			if(!empty($obj->_varsToPushParam)){
				if(empty($obj->_varsToPushParamCustom)) $obj->_varsToPushParamCustom = $obj->_varsToPushParam;
				if(empty($obj->_varsToPushParamCustomField)) $obj->_varsToPushParamCustomField = $obj->_varsToPushParam;
			}
		} else {
			$obj->_varsToPushParamCustom = VirtueMartModelCustom::getVarsToPush($fieldtype);
			$obj->_varsToPushParam = $obj->_varsToPushParamCustomField = $obj->_varsToPushParamCustom;
			//vmdebug('my $obj->_varsToPushParamCustom',$obj->_varsToPushParamCustomField);
		}

		if(!empty($obj->_varsToPushParam)){
			//$obj ->_xParams = 'custom_params';
			VmTable::bindParameterable($obj,'custom_params',$obj->_varsToPushParamCustom);

			$obj ->_xParams = 'customfield_params';
			VmTable::bindParameterable($obj,$obj->_xParams,$obj->_varsToPushParamCustomField);
		}

	}


	/**
	 *
	 * Enter description here ...
	 *
	 * @param unknown_type $product_id
	 * @return string|Ambigous <string, mixed, multitype:>
	 */
	function getProductParentRelation ($product_id) {
		$db = JFactory::getDBO();
		$db->setQuery (' SELECT `customfield_value` FROM `#__virtuemart_product_customfields` WHERE  `virtuemart_product_id` =' . (int)$product_id);
		if ($childcustom = $db->loadResult ()) {
			return '(' . $childcustom . ')';
		}
		else {
			return vmText::_ ('COM_VIRTUEMART_CUSTOM_NO_PARENT_RELATION');
		}
	}

	private function sortChildIds ($product_id, $childIds, $sorted=array()){

		//vmdebug('sortChildIds',$product_id, $childIds);
		foreach($childIds as $childIdKey => $childs){
			if(!is_array($childs)){
				$sorted[] = array('parent_id'=>$product_id,'vm_product_id'=>$childs);
				if(isset($childIds[$childs]) and is_array($childIds[$childs])){
					$sorted = self::sortChildIds($childs, $childIds[$childs], $sorted);
					//unset($childIds[$childs]);
				}
			} else {
				//$sorted = self::sortChildIds($childIdKey, $childs, $sorted);
			}
		}
		return $sorted;
	}


	private function renderProductChildLine($i,$line,$field,$productModel,$row){

		$child = $productModel->getProductSingle($line['vm_product_id'],false);
		//if(!empty($parentId)) $parentId = $parentId.'->';
		$linkLabel = $line['parent_id'] .'->'. $line['vm_product_id'];
		$html = '<tr class="row'.(($i+1)%2).'">';
		$html .= '<td>
	'.JHTML::_('link', JRoute::_('index.php?option=com_virtuemart&view=product&task=edit&virtuemart_product_id='.$child->virtuemart_product_id), $linkLabel, array('title' => vmText::_('COM_VIRTUEMART_EDIT').' '.$child->slug)) ;
		$html .= '</td><td>';
		$html .= 	'<input type="hidden" name="childs['.$child->virtuemart_product_id .'][slug]" id="child'.$child->virtuemart_product_id .'slug" value="'.$child->slug .'" />
					<input type="hidden" name="childs['.$child->virtuemart_product_id .'][product_parent_id]" id="child'.$child->virtuemart_product_id .'slug" value="'.$child->product_parent_id .'" />';
		$html .= 	'<input type="text" class="inputbox" name="childs['.$child->virtuemart_product_id .'][product_name]" id="child'.$child->virtuemart_product_id .'product_name" size="32" value="'.htmlspecialchars($child->product_name) .'" /></td>
<td><input type="text" class="inputbox" name="childs['.$child->virtuemart_product_id .'][mprices][product_price][]" size="10" value="'.$child->allPrices[$child->selectedPrice]['product_price'] .'" /><input type="hidden" name="childs['.$child->virtuemart_product_id .'][mprices][virtuemart_product_price_id][]" value="'.$child->allPrices[$child->selectedPrice]['virtuemart_product_price_id'] .'"  ></td>
<td>'.$child->product_in_stock .'</td>
<td>'.$child->product_ordered .'</td>';

		$product_id = $line['vm_product_id'];
		foreach($field->selectoptions as $k=>$selectoption){
			//vmdebug('my $field->options',$field->options);
			//if(!isset($field->options)) continue;

			$idTag = $product_id.'so'.$k;
			if($selectoption->voption=='clabels'){
				$name = 'field[' . $row . '][options]['.$product_id.']['.$k.']';
				$myoption = false;
				if(isset($field->options->$product_id)){
					$myoption = $field->options->$product_id;
				}

				if(!isset($myoption[$k])){
					$value = 0;
				} else {
					$value = trim($myoption[$k]);
				}
				vmdebug('my value',$value);
			} else {
				$name = 'childs['.$product_id .']['.$selectoption->voption.']';
				$value = trim($child->{$selectoption->voption});

			}
			//vmdebug('renderProductChildLine',$selectoption);
			if(count($selectoption->comboptions)>0){
				$html .= '<td>'.JHtml::_ ('select.genericlist', $selectoption->comboptions,$name , '', 'value', 'text',
						$value ,$idTag) .'</td>';
				if($selectoption->voption!='clabels'){
					$html .= '<input type="hidden" name="field[' . $row . '][options]['.$product_id.']['.$k.']" value="'.$value .'" />';
				}
			}


		}
		$html .= '</tr>';
		return $html;
	}

	/**
	 * @author Max Milbers
	 * @param $field
	 * @param $product_id
	 * @param $row
	 */
	public function displayProductCustomfieldBE ($field, $product, $row) {

		//This is a kind of fallback, setting default of custom if there is no value of the productcustom
		$field->customfield_value = empty($field->customfield_value) ? $field->custom_value : $field->customfield_value;
		$field->customfield_price = empty($field->customfield_price) ? 0 : $field->customfield_price;

		if(is_object($product)){
			$product_id = $product->virtuemart_product_id;
		} else {
			$product_id = $product;
		}
		//the option "is_cart_attribute" gives the possibility to set a price, there is no sense to set a price,
		//if the custom is not stored in the order.
		if ($field->is_input) {
			if(!class_exists('VirtueMartModelVendor')) require(JPATH_VM_ADMINISTRATOR.DS.'models'.DS.'vendor.php');
			if(!class_exists('VirtueMartModelCurrency')) require(JPATH_VM_ADMINISTRATOR.DS.'models'.DS.'currency.php');
			$vendor_model = VmModel::getModel('vendor');
			$virtuemart_vendor_id = 1;
			$vendor = $vendor_model->getVendor($virtuemart_vendor_id);
			$currency_model = VmModel::getModel('currency');
			$vendor_currency = $currency_model->getCurrency($vendor->vendor_currency);

			$priceInput = '<span style="white-space: nowrap;"><input type="text" size="12" style="text-align:right;" value="' . $field->customfield_price . '" name="field[' . $row . '][customfield_price]" /> '.$vendor_currency->currency_symbol."</span>";
		}
		else {
			$priceInput = ' ';
		}

		switch ($field->field_type) {

			case 'C':
				//vmdebug('displayProductCustomfieldBE $field',$field);
				if(!isset($field->withParent)) $field->withParent = 0;
				if(!isset($field->parentOrderable)) $field->parentOrderable = 0;
				//vmdebug('displayProductCustomfieldBE',$field);
				if (!class_exists('VmHTML')) require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'html.php');
				$html = vmText::_('COM_VIRTUEMART_CUSTOM_WP').VmHTML::checkbox('field[' . $row . '][withParent]',$field->withParent,1,0,'');
				$html .= vmText::_('COM_VIRTUEMART_CUSTOM_PO').VmHTML::checkbox('field[' . $row . '][parentOrderable]',$field->parentOrderable,1,0,'').'<br />';

				if(empty($field->selectoptions) or count($field->selectoptions)==0){
					$selectOption = new stdClass();	//The json conversts it anyway in an object, so suitable to use an object here
					$selectOption->voption = 'product_name';
					$selectOption->slabel = '';
					$selectOption->clabel = '';
					$selectOption->canonical = 0;
					$selectOption->values = '';
					$c = 0;
					$field->selectoptions = new stdClass();
					$field->selectoptions->$c = $selectOption;
					$field->options = new stdClass();

				} else if(is_array($field->selectoptions)){
					$field->selectoptions = (object)$field->selectoptions;
				}
				$field->options = (object)$field->options;

				$optAttr = array();

				$optAttr[] = array('value' => '0' ,'text' =>vmText::_('COM_VIRTUEMART_LIST_EMPTY_OPTION'));
				$optAttr[] = array('value' => 'product_name' ,'text' =>vmText::_('COM_VIRTUEMART_PRODUCT_FORM_NAME'));
				$optAttr[] = array('value' => 'product_sku', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_SKU'));
				$optAttr[] = array('value' => 'slug', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_ALIAS'));
				$optAttr[] = array('value' => 'product_length', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_LENGTH'));
				$optAttr[] = array('value' => 'product_width', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_WIDTH'));
				$optAttr[] = array('value' => 'product_height', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_HEIGHT'));
				$optAttr[] = array('value' => 'product_weight', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_WEIGHT'));
				$optAttr[] = array('value' => 'clabels', 'text' => vmText::_ ('COM_VIRTUEMART_CLABELS'));


				$productModel = VmModel::getModel('product');

				$childIds = array();
				$sorted = array();
				//vmSetStartTime();
				$productModel->getAllProductChildIds($product_id,$childIds);
				if(isset($childIds[$product_id])){
					$sorted = self::sortChildIds($product_id,$childIds[$product_id]);
				}
				array_unshift($sorted,  array('parent_id' => $product_id, 'vm_product_id' => $product_id));

				//VmConfig::$echoDebug = true;
				//vmdebug('my sorted ids ',$sorted);
				//return;
				$k = 0;
				foreach($field->selectoptions as $k=>&$soption){
					$options = array();
					$options[] = array('value' => '0' ,'text' =>vmText::_('COM_VIRTUEMART_LIST_EMPTY_OPTION'));

					$added = array();

					if($soption->voption!='clabels'){

						foreach($sorted as $vmProductId){
							if(empty($vmProductId)) continue;
							$product = $productModel->getProductSingle($vmProductId['vm_product_id'],false);
							$voption = trim($product->{$soption->voption});

							if(!empty($voption)) {
								$found = false;
								//Guys, dont tell me about in_array or array_search, it does not work here
								foreach($added as $add){
									if($add == $voption){
										$found = true;
										vmdebug('Found true due child product',$add);
									}
								}
								if(!$found){
									$added[] = $voption;
								}
							}
						}

						//$soption->values = implode("\n",$values);
					}

					if(!empty($soption->values)){
						$values = explode("\n",$soption->values);
						foreach($values as $value){
							$found = false;
							$value = trim($value);
							foreach($added as $add){
								if($add == $value){
									$found = true;
									vmdebug('Found true due $soption->values');
								}
							}
							if(!$found){
								$added[] = $value;
							}
						}
					}

					$soption->values = implode("\n",$added);

					foreach($added as &$value){
						$options[] = array('value' => $value ,'text' =>$value);
					}

					$soption->comboptions = $options;
					if(!isset($soption->clabel)) $soption->clabel = '';
					$soption->slabel = empty($soption->clabel)? vmText::_('COM_VIRTUEMART_'.strtoupper($soption->voption)): vmText::_($soption->clabel);
					$html .='<div>';
					$idTag = 'selectoptions'.$k;
					$html .= JHtml::_ ('select.genericlist', $optAttr, 'field[' . $row . '][selectoptions]['.$k.'][voption]', '', 'value', 'text', $soption->voption,$idTag) ;
					$html .= '<input type="text" value="' . $soption->clabel . '" name="field[' . $row . '][selectoptions]['.$k.'][clabel]" style="line-height:2em;margin:5px 5px 0;" />';
					$html .= '<textarea name="field[' . $row . '][selectoptions]['.$k.'][values]" rows="5" cols="35" style="float:none;margin:5px 5px 0;" >'.$soption->values.'</textarea><br/>';
					$html .='</div>';
				}

				$idTag = 'selectoptions'.++$k;
				$html .= '<fieldset style="background-color:#F9F9F9;">
					<legend>'. JText::_('COM_VIRTUEMART_CUSTOM_RAMB_NEW').'</legend>
					<div id="new_ramification">';
				//$html .= JHtml::_ ('select.genericlist', $options, 'field[' . $row . '][selectoptions]['.$k.'][voption]', '', 'value', 'text', 'product_name',$idTag) ;
				//$html .= '<input type="text" value="" name="field[' . $row . '][selectoptions]['.$k.'][slabel]" />';

				$html .= JHtml::_ ('select.genericlist', $optAttr, 'voption', '', 'value', 'text', 'product_name','voption') ;
				$html .= '<input type="text" value="" id="vlabel" />';

				$html .= '<span id="new_ramification_bt"><span class="icon-nofloat vmicon vmicon-16-new"></span>'. JText::_('COM_VIRTUEMART_ADD').'</span>
					</div>
				</fieldset>';

				$script = "
	jQuery( function($) {
		$('#new_ramification_bt').click(function() {
			var Prod = $('.new_ramification');

			var voption = jQuery('#voption').val();
			var label = jQuery('#vlabel').val();
				console.log ('my label '+label);
			form = document.getElementById('adminForm');
			var newdiv = document.createElement('div');
			newdiv.innerHTML = '<input type=\"text\" value=\"'+voption+'\" name=\"field[" . $row . "][selectoptions][".$k."][voption]\" /><input type=\"text\" value=\"'+label+'\" name=\"field[" . $row . "][selectoptions][".$k."][label]\" />';
			form.appendChild(newdiv);

			form.task.value = 'apply';
			form.submit();
		});
	});
	";
				$html .= '<script type="text/javascript">'.$script.'</script>';

				if ($product_id) {
					$link=JROUTE::_('index.php?option=com_virtuemart&view=product&task=createChild&virtuemart_product_id='.$product_id.'&'.JSession::getFormToken().'=1' );
					$add_child_button="";
				} else {
					$link="";
					$add_child_button=" not-active";
				}

				$html .= '<div class="button2-left '.$add_child_button.' btn-wrapper">
						<div class="blank">';
				if ($link) {
					$html .= '<a href="'. $link .'" class="btn btn-small">';
				} else {
					$html .= '<span class="hasTip" title="'.vmText::_ ('COM_VIRTUEMART_PRODUCT_ADD_CHILD_TIP').'">';
				}
				$html .= vmText::_('COM_VIRTUEMART_PRODUCT_ADD_CHILD');
				if ($link) {
					$html .= '</a>';
				} else{
					$html .= '</span>';
				}
				$html .= '</div>
					</div><div class="clear"></div>';
				//vmdebug('my $field->selectoptions',$field->selectoptions,$field->options);
				$html .= '<table>';
				$html .= '<tr>
<th style="text-align: left !important;">#</th>
<th style="text-align: left !important;">'.vmText::_('COM_VIRTUEMART_PRODUCT_CHILD_NAME').'</th>
<th style="text-align: left !important;" width="5%">'.vmText::_('COM_VIRTUEMART_PRODUCT_FORM_PRICE_COST').'</th>
<th style="text-align: left !important;">'.vmText::_('COM_VIRTUEMART_PRODUCT_FORM_IN_STOCK').'</th>
<th style="text-align: left !important;" width="5%">'.vmText::_('COM_VIRTUEMART_PRODUCT_FORM_ORDERED_STOCK').'</th>';
				foreach($field->selectoptions as $k=>$option){
					$html .= '<th>'.vmText::_('COM_VIRTUEMART_'.strtoupper($option->voption)).'</th>';
				}
				$html .= '</tr>';



				if(isset($childIds[$product_id])){
					//$sorted = self::sortChildIds($product_id,$childIds[$product_id]);

					foreach($sorted as $i=>$line){

						$html .= self::renderProductChildLine($i,$line,$field,$productModel,$row);
					}
				}


				$html .= '</table>';
				//$html .=  '</td><td>' . $priceInput;
				$html .=  '</td><td>' ;
				return $html;
				// 					return 'Automatic Childvariant creation (later you can choose here attributes to show, now product name) </td><td>';
				break;
			case 'A':
				//vmdebug('displayProductCustomfieldBE $field',$field);
				if(!isset($field->withParent)) $field->withParent = 0;
				if(!isset($field->parentOrderable)) $field->parentOrderable = 0;
				//vmdebug('displayProductCustomfieldBE',$field);
				if (!class_exists('VmHTML')) require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'html.php');
				$html = vmText::_('COM_VIRTUEMART_CUSTOM_WP').VmHTML::checkbox('field[' . $row . '][withParent]',$field->withParent,1,0,'').'<br />';
				$html .= vmText::_('COM_VIRTUEMART_CUSTOM_PO').VmHTML::checkbox('field[' . $row . '][parentOrderable]',$field->parentOrderable,1,0,'');

				$options = array();
				$options[] = array('value' => 'product_name' ,'text' =>vmText::_('COM_VIRTUEMART_PRODUCT_FORM_NAME'));
				$options[] = array('value' => 'product_sku', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_SKU'));
				$options[] = array('value' => 'slug', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_ALIAS'));
				$options[] = array('value' => 'product_length', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_LENGTH'));
				$options[] = array('value' => 'product_width', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_WIDTH'));
				$options[] = array('value' => 'product_height', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_HEIGHT'));
				$options[] = array('value' => 'product_weight', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_WEIGHT'));

				$html .= JHtml::_ ('select.genericlist', $options, 'field[' . $row . '][customfield_value]', '', 'value', 'text', $field->customfield_value) . '</td><td>' . $priceInput;
				return $html;
				// 					return 'Automatic Childvariant creation (later you can choose here attributes to show, now product name) </td><td>';
				break;
			/* string or integer */
			case 'B':
			case 'S':

				if($field->is_list){
					$options = array();
					$values = explode (';', $field->custom_value);

					foreach ($values as $key => $val) {
						$options[] = array('value' => $val, 'text' => $val);
					}

					$currentValue = $field->customfield_value;
					return JHtml::_ ('select.genericlist', $options, 'field[' . $row . '][customfield_value]', NULL, 'value', 'text', $currentValue) . '</td><td>' . $priceInput;
				} else{
					return '<input type="text" value="' . $field->customfield_value . '" name="field[' . $row . '][customfield_value]" /></td><td>' . $priceInput;
					break;
				}

				break;
			/* parent hint, this is a GROUP and should be G not P*/
			case 'G':
				return $field->customfield_value . '<input type="hidden" value="' . $field->customfield_value . '" name="field[' . $row . '][customfield_value]" /></td><td>';
				break;
			/* image */
			case 'M':
				if (empty($product)) {
					$vendorId = 1;
				}
				else {
					$vendorId = $product->virtuemart_vendor_id;
				}
				$q = 'SELECT `virtuemart_media_id` as value,`file_title` as text FROM `#__virtuemart_medias` WHERE `published`=1
					AND (`virtuemart_vendor_id`= "' . $vendorId . '" OR `shared` = "1")';
				$db = JFactory::getDBO();
				$db->setQuery ($q);
				$options = $db->loadObjectList ();
				return JHtml::_ ('select.genericlist', $options, 'field[' . $row . '][customfield_value]', '', 'value', 'text', $field->customfield_value) . '</td><td>' . $priceInput;
				break;

			case 'D':
				return vmJsApi::jDate ($field->customfield_value, 'field[' . $row . '][customfield_value]', 'field_' . $row . '_customvalue') .'</td><td>'. $priceInput;
				break;

			//'X'=>'COM_VIRTUEMART_CUSTOM_EDITOR',
			case 'X':
        // Not sure why this block is needed to get it to work when editing the customfield (the subsequent block works fine when creating it, ie. in JS)
				$document=& JFactory::getDocument();
				if (get_class($document) == 'JDocumentHTML') {
					$editor =& JFactory::getEditor();
					return $editor->display('field['.$row.'][customfield_value]',$field->custom_value, '550', '400', '60', '20', false).'</td><td>';
				}
				return '<textarea class="mceInsertContentNew" name="field[' . $row . '][customfield_value]" id="field-' . $row . '-customfield_value">' . $field->customfield_value . '</textarea>
						<script type="text/javascript">// Creates a new editor instance
							tinymce.execCommand("mceAddControl",true,"field-' . $row . '-customfield_value")
						</script></td><td>' . $priceInput;
				//return '<input type="text" value="'.$field->customfield_value.'" name="field['.$row.'][customfield_value]" /></td><td>'.$priceInput;
				break;
			//'Y'=>'COM_VIRTUEMART_CUSTOM_TEXTAREA'
			case 'Y':
				return '<textarea id="field[' . $row . '][customfield_value]" name="field[' . $row . '][customfield_value]" class="inputbox" cols=80 rows=6 >' . $field->customfield_value . '</textarea></td><td>' . $priceInput;
				//return '<input type="text" value="'.$field->customfield_value.'" name="field['.$row.'][customfield_value]" /></td><td>'.$priceInput;
				break;
			/*Extended by plugin*/
			case 'E':

				$html = '<input type="hidden" value="' . $field->customfield_value . '" name="field[' . $row . '][customfield_value]" />';
				if (!class_exists ('vmCustomPlugin')) {
					require(JPATH_VM_PLUGINS . DS . 'vmcustomplugin.php');
				}
				//vmdebug('displayProductCustomfieldBE $field',$field);
				JPluginHelper::importPlugin ('vmcustom', $field->custom_element);
				$dispatcher = JDispatcher::getInstance ();
				$retValue = '';
				$dispatcher->trigger ('plgVmOnProductEdit', array($field, $product_id, &$row, &$retValue));

				return $html . $retValue  . '</td><td>'. $priceInput;
				break;

			/* related category*/
			case 'Z':
				if (!$product_id) {
					return '';
				} // special case it's category ID !

				$q = 'SELECT * FROM `#__virtuemart_categories_' . VmConfig::$vmlang . '` JOIN `#__virtuemart_categories` AS p using (`virtuemart_category_id`) WHERE `virtuemart_category_id`= "' . (int)$field->customfield_value . '" ';
				$db = JFactory::getDBO();
				$db->setQuery ($q);
				//echo $db->_sql;
				if ($category = $db->loadObject ()) {
					$q = 'SELECT `virtuemart_media_id` FROM `#__virtuemart_category_medias` WHERE `virtuemart_category_id`= "' . (int)$field->customfield_value . '" ';
					$db->setQuery ($q);
					$thumb = '';
					if ($media_id = $db->loadResult ()) {
						$thumb = $this->displayCustomMedia ($media_id,'category');
					}

					$display = '<input type="hidden" value="' . $field->customfield_value . '" name="field[' . $row . '][customfield_value]" />';
					$display .= '<span class="custom_related_image">'.$thumb.'</span><span class="custom_related_title">';
					$display .= JHtml::link ('index.php?option=com_virtuemart&view=category&task=edit&virtuemart_category_id=' . (int)$field->customfield_value, $category->category_name, array('title' => $category->category_name,'target'=>'blank')).'</span>';
					return $display;
				}
				else {
					return 'no result $product_id = '.$product_id.' and '.$field->customfield_value;
				}
			/* related product*/
			case 'R':
				if (!$product_id) {
					return '';
				}

				$pModel = VmModel::getModel('product');
				$related = $pModel->getProduct((int)$field->customfield_value,TRUE,FALSE,FALSE,1);
				if (!empty($related->virtuemart_media_id[0])) {
					$thumb = $this->displayCustomMedia ($related->virtuemart_media_id[0]).' ';
				} else {
					$thumb = $this->displayCustomMedia (0).' ';
				}
				$display = '<input type="hidden" value="' . $field->customfield_value . '" name="field[' . $row . '][customfield_value]" />';
				$display .= '<span class="custom_related_image">'.$thumb.'</span><span class="custom_related_title">';
				$display .= JHtml::link ('index.php?option=com_virtuemart&view=product&task=edit&virtuemart_product_id=' . $related->virtuemart_product_id , $related->product_name, array('title' => $related->product_name,'target'=>'blank')).'</span>';
				return $display;

		}
	}

	/**
	 * @author Max Milbers
	 * @param $product
	 * @param $customfield
	 */
	public function displayProductCustomfieldFE (&$product, &$customfields) {

		static $idUnique = array();
		if (!class_exists ('calculationHelper')) {
			require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'calculationh.php');
		}
		$calculator = calculationHelper::getInstance ();

		$selectList = array();

		$dynChilds = 1; //= array();
		$session = JFactory::getSession ();
		$virtuemart_category_id = $session->get ('vmlastvisitedcategoryid', 0, 'vm');

		foreach($customfields as $k => &$customfield){

			if(!isset($customfield->display))$customfield->display = '';

			$calculator ->_product = $product;
			if (!class_exists ('vmCustomPlugin')) {
				require(JPATH_VM_PLUGINS . DS . 'vmcustomplugin.php');
			}

			if ($customfield->field_type == "E") {

				JPluginHelper::importPlugin ('vmcustom');
				$dispatcher = JDispatcher::getInstance ();
				$ret = $dispatcher->trigger ('plgVmOnDisplayProductFEVM3', array(&$product, &$customfield));
				continue;
			}

			$fieldname = 'field['.$product->virtuemart_product_id.'][' . $customfield->virtuemart_customfield_id . '][customfield_value]';
			$customProductDataName = 'customProductData['.$product->virtuemart_product_id.']['.$customfield->virtuemart_custom_id.']';

			//This is a kind of fallback, setting default of custom if there is no value of the productcustom
			$customfield->customfield_value = empty($customfield->customfield_value) ? $customfield->custom_value : $customfield->customfield_value;

			$type = $customfield->field_type;

			$idTag = (int)$product->virtuemart_product_id.'-'.$customfield->virtuemart_customfield_id;

			if(!isset($idUnique[$idTag])){
				$idUnique[$idTag] = 0;
			}  else {
				$counter = $idUnique[$idTag]++;
				$idTag = $idTag.'-'.$counter;
			}
			$idTag = $idTag . 'customProductData';
			if (!class_exists ('CurrencyDisplay'))
				require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'currencydisplay.php');
			$currency = CurrencyDisplay::getInstance ();

			switch ($type) {

				case 'C':

					$html = '';

					$dropdowns = array();

					if(isset($customfield->options->{$product->virtuemart_product_id})){
						$productSelection = $customfield->options->{$product->virtuemart_product_id};
					} else {
						$productSelection = false;
					}


					$ignore = array();
					foreach($customfield->options as $product_id=>$variants){
						if(in_array($product_id,$ignore)) continue;
						foreach($variants as $k => $variant){
							if(!isset($dropdowns[$k]) or !is_array($dropdowns[$k])) $dropdowns[$k] = array();

							if(!in_array($variant,$dropdowns[$k])  ){
								if($k==0 or !$productSelection){
									$dropdowns[$k][] = $variant;
								} else if($k>0 and $productSelection[$k-1] == $variants[$k-1]){
									vmdebug('my ',$variant,$productSelection[$k-1]);
									$dropdowns[$k][] = $variant;
								} else {
									$ignore[] = $product_id;
								}

							}
						}
					}

					foreach($customfield->selectoptions as $k => $soption){
						$options = array();
						$selected = 0;
						foreach($dropdowns[$k] as $i=> $elem){

							$elem = trim((string)$elem);
							$options[] = array('value'=>$elem,'text'=>$elem);

							if($productSelection and $productSelection[$k] == $elem){
								$selected = $elem;
							}

						}
						$idTag .= 'cvard'.$k;
						$soption->slabel = empty($soption->clabel)? vmText::_('COM_VIRTUEMART_'.strtoupper($soption->voption)): vmText::_($soption->clabel);

						$html .= JHtml::_ ('select.genericlist', $options, $fieldname, 'class="vm-chzn-select cvselection" ', "value", "text", $selected,$idTag);

					}
					//vmdebug('displayProductCustomfieldFE my C', $customfield->options,$dropdowns);
					//'http://vm3j2.stuprecht/en/?option=com_virtuemart&view=productdetails&virtuemart_product_id='+variants[index][0]+'&virtuemart_category_id=10&Itemid=127'
					$Itemid = vRequest::getInt('Itemid',''); // '&Itemid=127';
					if(!empty($Itemid)){
						$Itemid = '&Itemid='.$Itemid;
					}
					$url = 'index.php?option=com_virtuemart&view=productdetails&virtuemart_category_id=' . $virtuemart_category_id . '&virtuemart_product_id=';
					//create array for js
					$jsArray = array();

					foreach($customfield->options as $product_id=>$variants){
						$jsArray[] = '["'.$product_id.'","'.implode('","',$variants).'"]';
					}
					$jsVariants = implode(',',$jsArray);
					$script = "
	jQuery( function($) {
		$('.cvselection').change(function() {
				var variants = [".$jsVariants."];
				var selection = [];
				$('.cvselection').each(function() {
					selection[selection.length] = $(this).val();
					console.log('My selection '+selection[selection.length-1]);
				});
				var Itemid = '".$Itemid."';
				var url = '".$url."';
				var index ;
				var i2 ;
				var hitcount;
				var runs;
				for	(runs = 0; runs < selection.length; index++) {
					for	(index = 0; index < variants.length; index++) {
						hitcount = 0;
						for	(i2 = 0; i2 < selection.length; i2++) {
							//console.log('hmm '+variants[index][i2+1]+' selection '+selection[i2]);
							if(selection[i2]==variants[index][i2+1]){
								hitcount++;
								console.log('Attribute hit selection '+i2+' '+selection[i2]+' '+variants[index][i2+1] );
								if(hitcount == (selection.length-runs)){
									console.log('Product found '+variants[index][0]+' '+variants[index][1]+' '+variants[index][2]);
									console.log('Would redirect to '+url+variants[index][0])
									//break;
									window.top.location.href=url+variants[index][0]+Itemid;
									i2 = 200;
									index = 200;
									runs = 200;
								}
							} else {
								break;
							}
						}
					}
					runs++;
					console.log('Could not find product for selection ');
				}

			});
		});
	";
					$html .= '<script type="text/javascript">'.$script.'</script>';

					//Now we need just the JS to reload the correct product
					$customfield->display = $html;
					break;

				case 'A':

					$html = '';
					//if($selectedFound) continue;
					$options = array();
					$productModel = VmModel::getModel ('product');

					//Note by Jeremy Magne (Daycounts) 2013-08-31
					//Previously the the product model is loaded but we need to ensure the correct product id is set because the getUncategorizedChildren does not get the product id as parameter.
					//In case the product model was previously loaded, by a related product for example, this would generate wrong uncategorized children list
					$productModel->setId($customfield->virtuemart_product_id);

					$uncatChildren = $productModel->getUncategorizedChildren ($customfield->withParent);

					if(!$customfield->withParent or ($customfield->withParent and $customfield->parentOrderable)){
						$options[0] = array('value' => JRoute::_ ('index.php?option=com_virtuemart&view=productdetails&virtuemart_category_id=' . $virtuemart_category_id . '&virtuemart_product_id=' . $customfield->virtuemart_product_id,FALSE), 'text' => vmText::_ ('COM_VIRTUEMART_ADDTOCART_CHOOSE_VARIANT'));
					}

					$selected = vRequest::getInt ('virtuemart_product_id',0);
					$selectedFound = false;
					$customfield->withPrices = false;
					if (empty($calculator) and $customfield->withPrices) {
						if (!class_exists ('calculationHelper'))
							require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'calculationh.php');
						$calculator = calculationHelper::getInstance ();
					}

					$parentStock = 0;
					foreach ($uncatChildren as $k => $child) {
						if(!isset($child[$customfield->customfield_value])){
							vmdebug('The child has no value at index '.$customfield->customfield_value,$customfield,$child);
						} else {

							$productChild = $productModel->getProduct((int)$child['virtuemart_product_id'],false);
							if(!$productChild) continue;
							$available = $productChild->product_in_stock - $productChild->product_ordered;
							if(VmConfig::get('stockhandle','none')=='disableit_children' and $available <= 0){
								continue;
							}
							$parentStock += $available;
							$priceStr = '';
							if($customfield->withPrices){
								//$product = $productModel->getProductSingle((int)$child['virtuemart_product_id'],false);
								$productPrices = $calculator->getProductPrices ($productChild);
								$priceStr =  ' (' . $currency->priceDisplay ($productPrices['salesPrice']) . ')';
							}
							$options[] = array('value' => JRoute::_ ('index.php?option=com_virtuemart&view=productdetails&virtuemart_category_id=' . $virtuemart_category_id . '&virtuemart_product_id=' . $child['virtuemart_product_id']), 'text' => $child[$customfield->customfield_value].$priceStr);
							if($selected==$child['virtuemart_product_id']){
								$selectedFound = true;
								vmdebug($customfield->virtuemart_product_id.' $selectedFound by vRequest '.$selected);
							}
							//vmdebug('$child productId ',$child['virtuemart_product_id'],$customfield->customfield_value,$child);
						}
					}
					if(!$selectedFound){
						$pos = array_search($customfield->virtuemart_product_id, $product->allIds);
						if(isset($product->allIds[$pos-1])){
							$selected = $product->allIds[$pos-1];
							//vmdebug($customfield->virtuemart_product_id.' Set selected to - 1 allIds['.($pos-1).'] = '.$selected.' and count '.$dynChilds);
							//break;
						} elseif(isset($product->allIds[$pos])){
							$selected = $product->allIds[$pos];
							//vmdebug($customfield->virtuemart_product_id.' Set selected to allIds['.$pos.'] = '.$selected.' and count '.$dynChilds);
						} else {
							$selected = $customfield->virtuemart_product_id;
							//vmdebug($customfield->virtuemart_product_id.' Set selected to $customfield->virtuemart_product_id ',$selected,$product->allIds);
						}
					}

					$url = 'index.php?option=com_virtuemart&view=productdetails&virtuemart_category_id='.
						$virtuemart_category_id .'&virtuemart_product_id='. $selected;
					$html .= JHtml::_ ('select.genericlist', $options, $fieldname, 'onchange="window.top.location.href=this.options[this.selectedIndex].value" size="1" class="vm-chzn-select" data-dynamic-update="1" ', "value", "text",
						JRoute::_ ($url,false),$idTag);

					if($customfield->parentOrderable==0){
						if($product->product_parent_id==0){
							$product->orderable = FALSE;
						} else {
							$product->product_in_stock = $parentStock;
						}

					} else {


					}

					$dynChilds++;
					$customfield->display = $html;
					break;

				/*Date variant*/
				case 'D':
					if(empty($customfield->custom_value)) $customfield->custom_value = 'LC2';
					//Customer selects date
					if($customfield->is_input){
						$customfield->display =  '<span class="product_custom_date">' . vmJsApi::jDate ($customfield->customfield_value,$customProductDataName) . '</span>'; //vmJsApi::jDate($field->custom_value, 'field['.$row.'][custom_value]','field_'.$row.'_customvalue').$priceInput;
					}
					//Customer just sees a date
					else {
						$customfield->display =  '<span class="product_custom_date">' . vmJsApi::date ($customfield->customfield_value, $customfield->custom_value, TRUE) . '</span>';
					}

					break;
				/* text area or editor No vmText, only displayed in BE */
				case 'X':
				case 'Y':
					$customfield->display =  $customfield->customfield_value;
					break;
				/* string or integer */
				case 'B':
				case 'S':

					if($customfield->is_list){

						/*if($type=='B'){
							if ($customfield->customfield_value == 0){
								$customfield->customfield_value =  'JNO';
							} else {
								$customfield->customfield_value =  'JYES';
							}
						}*/
						//vmdebug('case S $customfield->is_list',$customfield->customfield_value);
						if(!empty($customfield->is_input)){

							$options = array();
							$values = explode (';', $customfield->custom_value);

							foreach ($values as $key => $val) {
								$options[] = array('value' => $val, 'text' => $val);
							}

							$currentValue = $customfield->customfield_value;

							$customfield->display = JHtml::_ ('select.genericlist', $options, $customProductDataName, NULL, 'value', 'text', $currentValue,$idTag);

							//$customfield->display =  '<input type="text" readonly value="' . vmText::_ ($customfield->customfield_value) . '" name="'.$customProductDataName.'" /> ' . vmText::_ ('COM_VIRTUEMART_CART_PRICE') . $price . ' ';
						} else {
							$customfield->display =  vmText::_ ($customfield->customfield_value);
						}
					} else {
						if(!empty($customfield->is_input)){

							if(!isset($selectList[$customfield->virtuemart_custom_id])) {
								$tmpField = clone($customfield);
								$tmpField->options = null;
								$customfield->options[$customfield->virtuemart_customfield_id] = $tmpField;
								$selectList[$customfield->virtuemart_custom_id] = $k;
								$customfield->customProductDataName = $customProductDataName;
							} else {
								$customfields[$selectList[$customfield->virtuemart_custom_id]]->options[$customfield->virtuemart_customfield_id] = $customfield;
								unset($customfields[$k]);
								//$customfield->options[$customfield->virtuemart_customfield_id] = $customfield;
							}

							/*
							$options = $this->getCustomEmbeddedProductCustomFields($product->allIds,$customfield->virtuemart_custom_id);
							//vmdebug('getProductCustomsFieldCart options',$options,$product->allIds);
							$customfield->options = array();
							foreach ($options as $option) {
								$customfield->options[$option->virtuemart_customfield_id] = $option;
							}
*/
							$default = reset($customfields[$selectList[$customfield->virtuemart_custom_id]]->options);
							foreach ($customfields[$selectList[$customfield->virtuemart_custom_id]]->options as &$productCustom) {
								$price = self::_getCustomPrice($productCustom->customfield_price, $currency, $calculator);
								$productCustom->text = $productCustom->customfield_value . ' ' . $price;
								//$productCustom->formname = '['.$productCustom->virtuemart_customfield_id.'][selected]';
							}

							//$customfield->display
							$customfields[$selectList[$customfield->virtuemart_custom_id]]->display = JHtml::_ ('select.genericlist', $customfields[$selectList[$customfield->virtuemart_custom_id]]->options,
								$customfields[$selectList[$customfield->virtuemart_custom_id]]->customProductDataName,
								'class="vm-chzn-select"', 'virtuemart_customfield_id', 'text', $default->customfield_value,$idTag);	//*/

						} else {
							$customfield->display =  vmText::_ ($customfield->customfield_value);
						}
					}

					break;

				/* parent The parent has a display in the FE?*/
				case 'G':
					//$customfield->display =  '<span class="product_custom_parent">' . vmText::_ ($value) . '</span>';
					break;
				// image
				case 'M':
					$customfield->display =  $this->displayCustomMedia ($customfield->customfield_value);
					break;
				case 'Z':
					$html = '';
					$q = 'SELECT * FROM `#__virtuemart_categories_' . VmConfig::$vmlang . '` as l JOIN `#__virtuemart_categories` AS c using (`virtuemart_category_id`) WHERE `published`=1 AND l.`virtuemart_category_id`= "' . (int)$customfield->customfield_value . '" ';
					$db = JFactory::getDBO();
					$db->setQuery ($q);
					if ($category = $db->loadObject ()) {
						$q = 'SELECT `virtuemart_media_id` FROM `#__virtuemart_category_medias`WHERE `virtuemart_category_id`= "' . $category->virtuemart_category_id . '" ';
						$db->setQuery ($q);
						$thumb = '';
						if ($media_id = $db->loadResult ()) {
							$thumb = $this->displayCustomMedia ($media_id,'category');
						}
						$customfield->display = JHtml::link (JRoute::_ ('index.php?option=com_virtuemart&view=category&virtuemart_category_id=' . $category->virtuemart_category_id), $thumb . ' ' . $category->category_name, array('title' => $category->category_name));
					}
					break;
				case 'R':
					if(empty($customfield->customfield_value)){
						$customfield->display = 'customfield related product has no value';
						break;
					}
					$pModel = VmModel::getModel('product');
					$related = $pModel->getProduct((int)$customfield->customfield_value,FALSE,FALSE,TRUE,1);

					if(!$related) break;

					if (!empty($related->virtuemart_media_id[0])) {
						$thumb = $this->displayCustomMedia ($related->virtuemart_media_id[0]).' ';
					} else {
						$thumb = $this->displayCustomMedia (0).' ';
					}
					//juri::root() For whatever reason, we used this here, maybe it was for the mails
					$customfield->display = JHtml::link (JRoute::_ ('index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $related->virtuemart_product_id . '&virtuemart_category_id=' . $related->virtuemart_category_id), $thumb   . $related->product_name, array('title' => $related->product_name,'target'=>'blank'));

					break;
			}
		}

	}
	/**
	 * There are too many functions doing almost the same for my taste
	 * the results are sometimes slighty different and makes it hard to work with it, therefore here the function for future proxy use
	 *
	 */
	static public function displayProductCustomfieldSelected ($product, $html, $trigger) {

		if(isset($product->param)){
			vmTrace('param found, seek and destroy');
			return false;
		}
		$row = 0;
		if (!class_exists ('shopFunctionsF'))
			require(JPATH_VM_SITE . DS . 'helpers' . DS . 'shopfunctionsf.php');

		$variantmods = isset($product -> customProductData)?$product -> customProductData:$product -> product_attribute;

		if(empty($variantmods)){
			$productDB = VmModel::getModel('product')->getProduct($product->virtuemart_product_id);
			if($productDB){
				$product->customfields = $productDB->customfields;
			}
		}
		if(!is_array($variantmods)){
			$variantmods = json_decode($variantmods,true);
		}

		$productCustoms = array();
		foreach($product->customfields as $prodcustom){

			//We just add the customfields to be shown in the cart to the variantmods
			if(is_object($prodcustom)){
				if($prodcustom->is_cart_attribute and !$prodcustom->is_input){
					$variantmods[$prodcustom->virtuemart_custom_id] = $prodcustom->virtuemart_customfield_id;
				} else if(!empty($variantmods) and !empty($variantmods[$prodcustom->virtuemart_custom_id])){

				}
				$productCustoms[$prodcustom->virtuemart_customfield_id] = $prodcustom;
			}
		}

		foreach ($variantmods as $custom_id => $customfield_ids) {

			if(!is_array($customfield_ids)){
				$customfield_ids = array( $customfield_ids =>false);
			}

			foreach($customfield_ids as $customfield_id=>$params){

				if(empty($productCustoms) or !isset($productCustoms[$customfield_id])){
					continue;
				}
				$productCustom = $productCustoms[$customfield_id];

				//The stored result in vm2.0.14 looks like this {"48":{"textinput":{"comment":"test"}}}
				//and now {"32":[{"invala":"100"}]}
				if (!empty($productCustom)) {
					$html .= ' <span class="product-field-type-' . $productCustom->field_type . '">';
					if ($productCustom->field_type == "E") {

						//$product->productCustom = $productCustom;
						//$product->row = $row;
						//
						if (!class_exists ('vmCustomPlugin'))
							require(JPATH_VM_PLUGINS . DS . 'vmcustomplugin.php');
						JPluginHelper::importPlugin ('vmcustom');
						$dispatcher = JDispatcher::getInstance ();
						//vmdebug('displayProductCustomfieldSelected is PLUGIN use trigger '.$trigger,$product->virtuemart_product_id,$productCustom->virtuemart_customfield_id,$productCustom->custom_element);
						$dispatcher->trigger ($trigger.'VM3', array(&$product, &$productCustom, &$html));

					}
					else {
						//vmdebug('customFieldDisplay $productCustom by self::getProductCustomField $variant: '.$variant.' $selected: '.$selected,$productCustom);
						$value = '';
						if (($productCustom->field_type == "G")) {

							$db = JFactory::getDBO ();
							$db->setQuery ('SELECT  `product_name` FROM `#__virtuemart_products_' . VmConfig::$vmlang . '` WHERE virtuemart_product_id=' . (int)$productCustom->customfield_value);
							$child = $db->loadObject ();
							$value = $child->product_name;
						}
						elseif (($productCustom->field_type == "M")) {
							// 						$html .= $productCustom->custom_title.' '.self::displayCustomMedia($productCustom->custom_value);
							$value = self::displayCustomMedia ($productCustom->customfield_value);
						}
						elseif (($productCustom->field_type == "S")) {
							//$value = $productCustom->custom_title.' '.vmText::_($productCustom->customfield_value);
							$value = $productCustom->customfield_value;
						}
						else {
							// 						$html .= $productCustom->custom_title.' '.$productCustom->custom_value;
							//vmdebug('customFieldDisplay',$productCustom);
							$value = $productCustom->customfield_value;
						}
						$html .= ShopFunctionsF::translateTwoLangKeys ($productCustom->custom_title, $value);
					}
					$html .= '</span><br />';
				}
				else {
					// falldown method if customfield are deleted
					foreach ((array)$customfield_id as $key => $value) {
						$html .= '<br/ >Couldnt find customfield' . ($key ? '<span>' . $key . ' </span>' : '') . $value;
					}
					//vmdebug ('CustomsFieldOrderDisplay, $item->productCustom empty? ' . $variant);
					vmdebug ('customFieldDisplay, $productCustom is EMPTY '.$customfield_id);
				}
			}

		}

		return $html . '</div>';
	}


	/**
	 * TODO This is html and view stuff and MUST NOT be in the model, notice by Max
	 * render custom fields display cart module FE
	 */
	static public function CustomsFieldCartModDisplay ($product) {

		return self::displayProductCustomfieldSelected ($product, '<div class="vm-customfield-mod">', 'plgVmOnViewCartModule');

	}

	/**
	 *  TODO This is html and view stuff and MUST NOT be in the model, notice by Max
	 * render custom fields display cart FE
	 */
	static public function CustomsFieldCartDisplay ($product) {

		return self::displayProductCustomfieldSelected ($product, '<div class="vm-customfield-cart">', 'plgVmOnViewCart');

	}

	/*
	 * render custom fields display order BE/FE
	*/
	static public function CustomsFieldOrderDisplay ($item, $view = 'FE', $absUrl = FALSE) {

		if (!empty($item->product_attribute)) {
			$item->customProductData = json_decode ($item->product_attribute, TRUE);
		}

		return self::displayProductCustomfieldSelected ($item, '<div class="vm-customfield-cart">', 'plgVmDisplayInOrder' . $view);
	}

	function displayCustomMedia ($media_id, $table = 'product', $absUrl = FALSE) {

		if (!class_exists ('TableMedias'))
			require(JPATH_VM_ADMINISTRATOR . DS . 'tables' . DS . 'medias.php');

		$db = JFactory::getDBO ();
		$data = new TableMedias($db);
		$data->load ((int)$media_id);

		if (!class_exists ('VmMediaHandler'))
			require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'mediahandler.php');
		$media = VmMediaHandler::createMedia ($data, $table);

		return $media->displayMediaThumb ('', FALSE, '', TRUE, TRUE, $absUrl);

	}

	static function _getCustomPrice($customPrice, $currency, $calculator) {
		if ((float)$customPrice) {
			$price = strip_tags ($currency->priceDisplay ($calculator->calculateCustomPriceWithTax ($customPrice)));
			if ($customPrice >0) {
				$price ="+".$price;
			}
		}
		else {
			$price = ($customPrice === '') ? '' :  vmText::_ ('COM_VIRTUEMART_CART_PRICE_FREE');
		}
		return $price;
	}

	/**
	 * @param $product
	 * @param $variants ids of the selected variants
	 * @return float
	 */
	public function calculateModificators(&$product) {

		$modificatorSum = 0.0;

		//VmConfig::$echoDebug=true;
		if (!empty($product->customfields)){

			foreach($product->customfields as $k=>$productCustom){
				$selected = -1;

				if(isset($product->cart_item_id)){
					if (!class_exists('VirtueMartCart'))
						require(JPATH_VM_SITE . DS . 'helpers' . DS . 'cart.php');
					$cart = VirtueMartCart::getCart();

					//vmdebug('my $productCustom->customfield_price '.$productCustom->virtuemart_customfield_id,$cart->cartProductsData,$cart->cartProductsData[$product->cart_item_id]['customProductData'][$productCustom->virtuemart_custom_id]);
					if(isset($cart->cartProductsData[$product->cart_item_id]['customProductData'][$productCustom->virtuemart_custom_id][$productCustom->virtuemart_customfield_id])){
						$selected = $cart->cartProductsData[$product->cart_item_id]['customProductData'][$productCustom->virtuemart_custom_id][$productCustom->virtuemart_customfield_id];

					} else if( isset($cart->cartProductsData[$product->cart_item_id]['customProductData'][$productCustom->virtuemart_custom_id])){
						if($cart->cartProductsData[$product->cart_item_id]['customProductData'][$productCustom->virtuemart_custom_id]== $productCustom->virtuemart_customfield_id){
							$selected = $productCustom->virtuemart_customfield_id;	//= 1;

						}
					}
					//vmdebug('my $productCustom->customfield_price',$selected,$productCustom->virtuemart_custom_id,$productCustom->virtuemart_customfield_id,$cart->cartProductsData[$product->cart_item_id]['customProductData']);
				} else {

					$pluginFields = vRequest::getVar ('customProductData', NULL);

					if ($pluginFields == NULL and isset($product->customPlugin)) {
						$pluginFields = json_decode ($product->customPlugin, TRUE);
					}

					if(isset($pluginFields[$product->virtuemart_product_id][$productCustom->virtuemart_custom_id][$productCustom->virtuemart_customfield_id])){
						$selected = $pluginFields[$product->virtuemart_product_id][$productCustom->virtuemart_custom_id][$productCustom->virtuemart_customfield_id];
					} else if (isset($pluginFields[$product->virtuemart_product_id][$productCustom->virtuemart_custom_id])){
						if($pluginFields[$product->virtuemart_product_id][$productCustom->virtuemart_custom_id]== $productCustom->virtuemart_customfield_id){
							$selected = 1;
						}

					}
				}

				if($selected === -1) {
					//VmConfig::$echoDebug = 1;
					//vmdebug('Selected is minus 1');
					continue;
				}

				//vmdebug('my $productCustom->customfield_price',$selected,$productCustom->customfield_price);
				if (!empty($productCustom) and $productCustom->field_type =='E') {

					if(!class_exists('vmCustomPlugin')) require(JPATH_VM_PLUGINS.DS.'vmcustomplugin.php');
					JPluginHelper::importPlugin('vmcustom');
					$dispatcher = JDispatcher::getInstance();
					$dispatcher->trigger('plgVmPrepareCartProduct',array(&$product, &$product->customfields[$k],$selected,&$modificatorSum));
				} else {
					if ($productCustom->customfield_price) {
						//vmdebug('calculateModificators $productCustom->customfield_price ',$productCustom->customfield_price);
						//TODO adding % and more We should use here $this->interpreteMathOp
						$modificatorSum = $modificatorSum + $productCustom->customfield_price;
					}
				}
			}
		}

		return $modificatorSum;
	}


	/* Save and delete from database
	* all product custom_fields and xref
	@ var   $table	: the xref table(eg. product,category ...)
	@array $data	: array of customfields
	@int     $id		: The concerned id (eg. product_id)
	*/
	public function storeProductCustomfields($table,$datas, $id) {

		//vmdebug('storeProductCustomfields',$datas);
		vRequest::vmCheckToken('Invalid token in storeProductCustomfields');
		//Sanitize id
		$id = (int)$id;

		//Table whitelist
		$tableWhiteList = array('product','category','manufacturer');
		if(!in_array($table,$tableWhiteList)) return false;

		// Get old IDS
		$db = JFactory::getDBO();
		$db->setQuery( 'SELECT `virtuemart_customfield_id` FROM `#__virtuemart_'.$table.'_customfields` as `PC` WHERE `PC`.virtuemart_'.$table.'_id ='.$id );
		$old_customfield_ids = $db->loadColumn();

		//vmdebug('storeProductCustomfields',$datas['field']);
		if (array_key_exists('field', $datas)) {

			foreach($datas['field'] as $key => $fields){

				if(!empty($datas['field'][$key]['virtuemart_product_id']) and (int)$datas['field'][$key]['virtuemart_product_id']!=$id){
					//aha the field is from the parent, what we do with it?
					$fields['override'] = (int)$fields['override'];
					$fields['disabler'] = (int)$fields['disabler'];
					if($fields['override']!=0 or $fields['disabler']!=0){
						//If it is set now as override, store it as clone, therefore set the virtuemart_customfield_id = 0
						if($fields['override']!=0){
							$fields['override'] = $fields['virtuemart_customfield_id'];
						}
						if($fields['disabler']!=0){
							$fields['disabler'] = $fields['virtuemart_customfield_id'];
						}
						$fields['virtuemart_customfield_id'] = 0;
						//unset($fields['virtuemart_product_id']);	//why we unset the primary key?
						vmdebug('storeProductCustomfields I am in field from parent and create a clone');
					}
					else {
						//we do not store customfields inherited by the parent, therefore
						vmdebug('storeProductCustomfields I am in field from parent => not storing');
						$key = array_search($fields['virtuemart_customfield_id'], $old_customfield_ids );
						if ($key !== false ){
							//vmdebug('storeProductCustomfields unsetting from $old_customfild_ids',$key);
							unset( $old_customfield_ids[ $key ] );
						}
						continue;
					}
				}

				$fields['virtuemart_'.$table.'_id'] =$id;
				$tableCustomfields = $this->getTable($table.'_customfields');
				$tableCustomfields->setPrimaryKey('virtuemart_product_id');
				if (!empty($datas['customfield_params'][$key]) and !isset($datas['clone']) ) {
					if (array_key_exists( $key,$datas['customfield_params'])) {
						$fields = array_merge ((array)$fields, (array)$datas['customfield_params'][$key]);
					}
				}
				$tableCustomfields->_xParams = 'customfield_params';
				if(!class_exists('VirtueMartModelCustom')) require(JPATH_VM_ADMINISTRATOR.DS.'models'.DS.'custom.php');
				VirtueMartModelCustom::setParameterableByFieldType($tableCustomfields,$fields['field_type'],$fields['custom_element'],$fields['custom_jplugin_id']);
				//vmdebug('Data to store $tableCustomfields->bindChecknStore',$fields,$tableCustomfields);
				$tableCustomfields->bindChecknStore($fields);
				$errors = $tableCustomfields->getErrors();
				foreach($errors as $error){
					vmError($error);
				}
				$key = array_search($fields['virtuemart_customfield_id'], $old_customfield_ids );
				if ($key !== false ) unset( $old_customfield_ids[ $key ] );
// 				vmdebug('datas clone',$old_customfield_ids,$fields);
			}
		} else {
			vmdebug('storeProductCustomfields nothing to store',$datas['field']);
		}
		vmdebug('Delete $old_customfield_ids',$old_customfield_ids);
		if ( count($old_customfield_ids) ) {
			// delete old unused Customfields
			$db->setQuery( 'DELETE FROM `#__virtuemart_'.$table.'_customfields` WHERE `virtuemart_customfield_id` in ("'.implode('","', $old_customfield_ids ).'") ');
			$db->execute();
			vmdebug('Deleted $old_customfield_ids',$old_customfield_ids);
		}


		JPluginHelper::importPlugin('vmcustom');
		$dispatcher = JDispatcher::getInstance();
		if (isset($datas['customfield_params']) and is_array($datas['customfield_params'])) {
			foreach ($datas['customfield_params'] as $key => $plugin_param ) {
				$dispatcher->trigger('plgVmOnStoreProduct', array($datas, $plugin_param ));
			}
		}

	}

	static public function setEditCustomHidden ($customfield, $i) {

		if (!isset($customfield->virtuemart_customfield_id))
			$customfield->virtuemart_customfield_id = '0';
		if (!isset($customfield->virtuemart_product_id))
			$customfield->virtuemart_product_id = '';
		$html = '
			<input type="hidden" value="' . $customfield->field_type . '" name="field[' . $i . '][field_type]" />
			<input type="hidden" value="' . $customfield->custom_element . '" name="field[' . $i . '][custom_element]" />
			<input type="hidden" value="' . $customfield->custom_jplugin_id . '" name="field[' . $i . '][custom_jplugin_id]" />
			<input type="hidden" value="' . $customfield->virtuemart_custom_id . '" name="field[' . $i . '][virtuemart_custom_id]" />
			<input type="hidden" value="' . $customfield->virtuemart_product_id . '" name="field[' . $i . '][virtuemart_product_id]" />
			<input type="hidden" value="' . $customfield->virtuemart_customfield_id . '" name="field[' . $i . '][virtuemart_customfield_id]" />';
		//if($customfield->field_type=='Z'){
			//$html .= '<input type="hidden" value=""' . $customfield->ordering . '"" name="ordering[' . $i . ']" class="ordering">';
			$html .= '<input class="ordering" type="hidden" value="'.$customfield->ordering.'" name="field['.$i .'][ordering]" />';
		//}
			//<input type="hidden" value="' . $customfield->admin_only . '" checked="checked" name="field[' . $i . '][admin_only]" />';
		return $html;

	}

	private $_hidden = array();
	/**
	 * Use this to adjust the hidden fields of the displaycustomHandler to your form
	 *
	 * @param string $name for exampel view
	 * @param string $value for exampel custom
	 */
	public function addHidden ($name, $value = '') {

		$this->_hidden[$name] = $value;
	}

}
// pure php no closing tag
