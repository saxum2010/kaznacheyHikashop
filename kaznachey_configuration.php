<?php
/**
 * @package	HikaShop for Joomla!
 * @version	2.3.4
 * @author	hikashop.com
 * @copyright	(C) 2010-2014 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?>
<tr>
	<td class="key">
		<label for="data[payment][payment_params][MerchantGuid]"><?php
			echo JText::_( 'Идентификатор мерчанта' );
		?></label>
	</td>
	<td>
		<input type="text" name="data[payment][payment_params][MerchantGuid]" value="<?php echo $this->escape(@$this->element->payment_params->MerchantGuid); ?>" />
	</td>
</tr>
<tr>
	<td class="key">
		<label for="data[payment][payment_params][MerchantSecretKey]"><?php
			echo JText::_( 'Секретный ключ мерчанта' );
		?></label>
	</td>
	<td>
		<input type="text" name="data[payment][payment_params][MerchantSecretKey]" value="<?php echo $this->escape(@$this->element->payment_params->MerchantSecretKey); ?>" />
	</td>
</tr>
<tr>
	<td class="key">
		<label for="data[payment][payment_params][return_url]"><?php
			echo JText::_('RETURN_URL');
		?></label>
	</td>
	<td>
		<input type="text" name="data[payment][payment_params][return_url]" value="<?php echo $this->escape(@$this->element->payment_params->return_url); ?>" />
	</td>
</tr>
<tr>
	<td class="key">
		<label for="data[payment][payment_params][pending_status]"><?php
			echo JText::_('PENDING_STATUS');
		?></label>
	</td>
	<td><?php
		echo $this->data['order_statuses']->display("data[payment][payment_params][pending_status]", @$this->element->payment_params->pending_status);
	?></td>
</tr>
<tr>
	<td class="key">
		<label for="data[payment][payment_params][rm]"><?php
			echo 'Return method';
		?></label>
	</td>
	<td><?php
		if(!isset($this->element->payment_params->rm))
			$this->element->payment_params->rm = 1;
		echo JHTML::_('hikaselect.booleanlist', "data[payment][payment_params][rm]" , '', $this->element->payment_params->rm);
	?></td>
</tr>
