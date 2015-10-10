<?php
/**
 * @package	HikaShop for Joomla!
 * @version	2.3.4
 * @author	hikashop.com
 * @copyright	(C) 2010-2014 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php
class plgHikashoppaymentkaznachey extends hikashopPaymentPlugin
{
	public $paymentKaznacheyUrl = "http://payment.kaznachey.net/api/PaymentInterface/";
	public	$MerchantGuid;
	public	$MerchantSecretKey;

	var $accepted_currencies = array(
		'AUD','BRL','CAD','EUR','GBP','JPY','USD','NZD','CHF','HKD','SGD','SEK',
		'DKK','PLN','NOK','HUF','CZK','MXN','MYR','PHP','TWD','THB','ILS','TRY','UAH','RUB'
	);

	var $multiple = true;
	var $name = 'kaznachey';
	var $doc_form = 'kaznachey';

	function __construct(&$subject, $config) {
		parent::__construct($subject, $config);
	}
	
	function checkPaymentDisplay(&$method, &$order) {
		$this->MerchantGuid = $method->payment_params->MerchantGuid;
		$this->MerchantSecretKey = $method->payment_params->MerchantSecretKey;
		if($cc_types = $this->GetMerchnatInfo()){
			if($cc_types["PaySystems"]){
			$select = '<br><select name="cc_type" id="cc_type">';
				foreach ($cc_types["PaySystems"] as $paysystem){
					$select .= '<option value="'.$paysystem['Id'].'">'.$paysystem['PaySystemName'].'</option>';
				}
			$select .= '</select>';
			}
			$cc_agreed = "<br><input type='checkbox' class='form-checkbox' name='cc_agreed' id='cc_agreed' checked><label for='edit-panes-payment-details-cc-agreed'><a href='$cc_types[TermToUse]' target='_blank'>Согласен с условиями использования</a></label>";
			
			$html .= '<script type="text/javascript">';
			$html .= "//<![CDATA[
			jQuery(document).ready(function(a){function c(){var b=a('#cc_type').val();a('#checkoutForm').find('.cc_agreed_h').remove().end().append('<input type=hidden name=cc_agreed class=cc_agreed_h value='+b+' />')}var b=a('#cc_agreed');b.click(function(){b.is(':checked')?a('.cart-summary').find('.red').remove():b.next().after('<span class=red>Примите условие!</span>')});a('#cc_type').change(function(){c()});c()
			});
			jQuery(document).ready(function () {
				jQuery(\"[id^='hikashop_credit_card_kaznachey_']\").first().after(jQuery('#kzn_box')); 
			});
			//]]>";
			$html .= '</script>';
		}
			print '<div style="display:none"><div id="kzn_box">'.$select . $cc_agreed . $html .'</div></div>';
		return true; 
	}

	function onBeforeOrderCreate(&$order,&$do){
		if(parent::onBeforeOrderCreate($order, $do) === true)
			return true;

		if(empty($this->payment_params->MerchantGuid) || empty($this->payment_params->MerchantSecretKey)) {
			$this->app->enqueueMessage('Please check your &quot;kaznachey&quot; plugin configuration');
			$do = false;
		}
	}

	function onAfterOrderConfirm(&$order, &$methods, $method_id) {
		parent::onAfterOrderConfirm($order, $methods, $method_id);

		if($this->currency->currency_locale['int_frac_digits'] > 2)
			$this->currency->currency_locale['int_frac_digits'] = 2;

		$notify_url = HIKASHOP_LIVE.'index.php?option=com_hikashop&ctrl=checkout&task=notify&notif_payment='.$this->name.'&tmpl=component&lang='.$this->locale . $this->url_itemid;
		$return_url = HIKASHOP_LIVE.'index.php?option=com_hikashop&ctrl=checkout&task=after_end&order_id='.$order->order_id . $this->url_itemid;
		$cancel_url = HIKASHOP_LIVE.'index.php?option=com_hikashop&ctrl=order&task=cancel_order&order_id='.$order->order_id . $this->url_itemid;

		$cc_type = JRequest::getVar('cc_type', '');
		$this->MerchantSecretKey = $this->payment_params->MerchantSecretKey;
		$request["MerchantGuid"] = $this->MerchantGuid = $this->payment_params->MerchantGuid;
		$request['SelectedPaySystemId'] = isset($cc_type) ? $cc_type : $this->GetMerchnatInfo(false, true);
		$request['Currency'] = $this->currency->currency_code;
		$request['Language'] = $this->locale;
		
		$sum=$qty=0;
		foreach($order->cart->products as $product) {
			if($product->order_product_option_parent_id) continue;
			$request['Products'][] = array(
				"ProductId" => $product->product_id,
				"ProductName" => substr(strip_tags($product->order_product_name), 0, 127),
				"ProductPrice" => round($product->order_product_price, (int)$this->currency->currency_locale['int_frac_digits']),
				"ProductItemsNum" => $product->order_product_quantity,
				"ImageUrl" => '',
			);
			$sum += round($product->order_product_price, (int)$this->currency->currency_locale['int_frac_digits']) * $product->order_product_quantity;
			$qty += $product->order_product_quantity;
		}
		
		$amount = round($order->cart->full_total->prices[0]->price_value_with_tax, (int)$this->currency->currency_locale['int_frac_digits']);

		if($sum != $amount){
		$sum += $order_info_total = (int) ($amount - $sum);
		$request['Products'][] = array(
			"ProductId" => '1',
			"ProductName" => 'Delivery',
			"ProductPrice" => $order_info_total,
			"ProductItemsNum" => 1,
			"ImageUrl" => '',
		);
		$qty++;
		}
	$BuyerCountry = @$order->cart->shipping_address->address_state->zone_name;
	$BuyerFirstname = @$order->cart->shipping_address->address_firstname;
	$BuyerLastname = @$order->cart->shipping_address->address_lastname;
	
	$BuyerStreet = $order->cart->shipping_address->address_street;
	$BuyerCity = @$order->cart->shipping_address->address_city;

	$request['PaymentDetails'] = array(
       "MerchantInternalPaymentId"=>$order->order_id,
       "MerchantInternalUserId"=>$order->order_user_id,
       "EMail"=>$this->user->user_email,
       "PhoneNumber"=> $order->cart->shipping_address->address_telephone,
       "CustomMerchantInfo"=>"",
       "StatusUrl"=>"$notify_url",
       "ReturnUrl"=>"$return_url",
       "BuyerCountry"=>"$BuyerCountry",
       "BuyerFirstname"=>"$BuyerFirstname",
       "BuyerPatronymic"=>"",
       "BuyerLastname"=>"$BuyerLastname",
       "BuyerStreet"=>"$BuyerStreet",
       "BuyerZone"=>"",
       "BuyerZip"=>"",
       "BuyerCity"=>"$BuyerCity",

       "DeliveryFirstname"=>"$BuyerFirstname",
       "DeliveryLastname"=>"$BuyerLastname",
       "DeliveryZip"=>"",
       "DeliveryCountry"=>"$BuyerCountry",
       "DeliveryPatronymic"=>"",
       "DeliveryStreet"=>"$BuyerStreet",
       "DeliveryCity"=>"$BuyerCity",
       "DeliveryZone"=>"",
    );
	
	$request["Signature"] = md5(strtoupper($request["MerchantGuid"]) .
		number_format($sum, 2, ".", "") . 
		$request["SelectedPaySystemId"] . 
		$request["PaymentDetails"]["EMail"] . 
		$request["PaymentDetails"]["PhoneNumber"] . 
		$request["PaymentDetails"]["MerchantInternalUserId"] . 
		$request["PaymentDetails"]["MerchantInternalPaymentId"] . 
		strtoupper($request["Language"]) . 
		strtoupper($request["Currency"]) . 
		strtoupper($this->MerchantSecretKey));
	
		$response = $this->sendRequestKaznachey(json_encode($request), "CreatePaymentEx");
		$result = json_decode($response, true);

		if($result['ErrorCode'] != 0){
  			JController::setRedirect($fail_url, 'Ошибка транзакции' );
			JController::redirect(); 
		}else{
			print base64_decode($result["ExternalForm"]); die;
		}
		
		return $this->showPage('end');
	}
	
	function onPaymentNotification(&$statuses) {
	
	$request_json = file_get_contents('php://input');
	$request = json_decode($request_json, true);

	$request_sign = md5($request["ErrorCode"].
		$request["OrderId"].
		$request["MerchantInternalPaymentId"]. 
		$request["MerchantInternalUserId"]. 
		number_format($request["OrderSum"],2,".",""). 
		number_format($request["Sum"],2,".",""). 
		strtoupper($request["Currency"]). 
		$request["CustomMerchantInfo"]. 
		strtoupper($this->payment_params->MerchantSecretKey));
	
		if($request['SignatureEx'] == $request_sign) {
			$order_id = $request["MerchantInternalPaymentId"];
			
			$this->modifyOrder($order_id, $this->payment_params->verified_status);
			return true;
		}
	
	}

	function onPaymentConfiguration(&$element) {
		$subtask = JRequest::getCmd('subtask', '');

		parent::onPaymentConfiguration($element);

		if(empty($element->payment_params->MerchantGuid)) {	
			$app = JFactory::getApplication();
			$app->enqueueMessage(JText::sprintf('ENTER_INFO_REGISTER_IF_NEEDED', 'Kaznachey', JText::_('HIKA_EMAIL'), 'Kaznachey', 'https://www.kaznachey.ua/'));
		}
	}

	function getPaymentDefaultValues(&$element) {
	
		$element->payment_name = 'Кредитная карта Visa/MC, Webmoney, Liqpay, Qiwi... (www.kaznachey.ua)';
		$element->payment_description='';
		$element->payment_images = 'MasterCard,VISA,Webmoney,Liqpay,Qiwi';

		$element->payment_params->MerchantGuid = '';
		$element->payment_params->MerchantSecretKey = '';
		$element->payment_params->pending_status = 'created';
	}

	protected function displayLogos($logo_list)
    {
        $img = "";
        
        if (!(empty($logo_list))) {
            $url = JURI::root() . str_replace('\\', '/', str_replace(JPATH_ROOT, '', dirname(__FILE__))) . '/';
            if (!is_array($logo_list))
                $logo_list = (array) $logo_list;
            foreach ($logo_list as $logo) {
                $alt_text = substr($logo, 0, strpos($logo, '.'));
                $img .= '<img align="middle" src="' . $url . $logo . '"  alt="' . $alt_text . '" /> ';
            }
        }
        return $img;
    }
	
	function GetMerchnatInfo($id = false, $first = false){
		$requestMerchantInfo = Array(
			"MerchantGuid"=>$this->MerchantGuid,
			"Signature" => md5(strtoupper($this->MerchantGuid) . strtoupper($this->MerchantSecretKey))
		);

		$resMerchantInfo = json_decode($this->sendRequestKaznachey(json_encode($requestMerchantInfo), 'GetMerchatInformation'),true); 
		if($first){
			return $resMerchantInfo["PaySystems"][0]['Id'];
		}elseif($id)
		{
			foreach ($resMerchantInfo["PaySystems"] as $key=>$paysystem)
			{
				if($paysystem['Id'] == $id){
					return $paysystem;
				}
			}
		}else{
			return $resMerchantInfo;
		}
	}

	protected function sendRequestKaznachey($jsonData, $method){
		$curl = curl_init();
		if (!$curl)
			return false;

		curl_setopt($curl, CURLOPT_URL, $this->paymentKaznacheyUrl . $method);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER,
			array("Expect: ", "Content-Type: application/json; charset=UTF-8", 'Content-Length: '
				. strlen($jsonData)));
		curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, True);
		$response = curl_exec($curl);
		curl_close($curl);

		return $response;
	}
}
