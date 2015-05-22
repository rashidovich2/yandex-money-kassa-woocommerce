<?php
/**
 * Plugin Name: yandexmoney_wp_woocommerce
 * Plugin URI: https://github.com/systemo-biz/yandex-money-kassa-woocommerce
 * Description: Yandex.Kassa
 */

include_once 'inc/gateways/yandex/yandex.php';
include_once 'inc/gateways/bank/bank.php';
include_once 'inc/gateways/terminal/terminal.php';
include_once 'inc/gateways/webmoney/webmoney.php';
include_once 'inc/gateways/yandex_wallet/yandex_wallet.php';
include_once 'inc/wc_submenu.php';

function yandexMoney_update_option(){
	if (isset($_POST["ym_Funds"])){update_option("ym_Funds",$_POST["ym_Funds"]);}
	if (isset($_POST["ym_Email"])){update_option("ym_Email",$_POST["ym_Email"]);}
	if (isset($_POST["ym_WallNum"])){update_option("ym_WallNum",$_POST["ym_WallNum"]);}
	if (isset($_POST["ym_Secret"])){update_option("ym_Secret",$_POST["ym_Secret"]);}
	if (isset($_POST["ym_Demo"])){update_option("ym_Demo",'on');} else {update_option("ym_Demo",'off');}
	if (isset($_POST["ym_Scid"])){update_option("ym_Scid",$_POST["ym_Scid"]);}
	if (isset($_POST["ym_ShopID"])){update_option("ym_ShopID",$_POST["ym_ShopID"]);}
	if (isset($_POST["ym_shopPassword"])){update_option("ym_shopPassword",$_POST["ym_shopPassword"]);}
	global $woocommerce;
	foreach ($woocommerce->payment_gateways->payment_gateways as $obj) {
		$not=array("bacs","cheque","cod","paypal");
		if (!in_array($obj->id,$not)) {
			$set = get_option("woocommerce_".$obj->id."_settings");
			$set['enabled'] = "no";
			var_dump($_POST["woocommerce_".$obj->id."_settings"]);
			if (get_option('ym_Funds')=='0' && $obj->id != "yandex_wallet"){
				if (isset($_POST["woocommerce_".$obj->id."_settings"]) && $_POST["woocommerce_".$obj->id."_settings"] == "yes"){
					$set['enabled'] = "yes";
				}
			} else {
				if (get_option('ym_Funds')=='1' && $obj->id == "yandex_wallet"){
					$set['enabled'] = "yes";
				}
			}
			update_option("woocommerce_".$obj->id."_settings",$set);
		}
	}

}

add_action('parse_request', 'YMcheckPayment');
function YMcheckPayment()
{
	global $wpdb;
	if ($_REQUEST['yandex_money'] == 'check') {
		if ($_REQUEST['notification_type'] == 'card-incoming' || $_REQUEST['notification_type'] == 'p2p-incoming'){
			$hash = sha1($_REQUEST['notification_type'].'&'.$_REQUEST['operation_id'].'&'.$_REQUEST['amount'].'&'.$_REQUEST['currency'].'&'.
						$_REQUEST['datetime'].'&'.$_REQUEST['sender'].'&'.$_REQUEST['codepro'].'&'.get_option('ym_Secret').'&'.$_REQUEST['label']);
			if ($_REQUEST['test_notification'] != 'true' && $hash == $_REQUEST['sha1_hash']){
				$order_w = new WC_Order( $_REQUEST['label'] );
				$order_w->update_status('processing', __( 'Платеж успешно оплачен', 'woocommerce' ));
				$order_w->reduce_order_stock();
			}
		} else {
			$hash = md5($_POST['action'].';'.$_POST['orderSumAmount'].';'.$_POST['orderSumCurrencyPaycash'].';'.
						$_POST['orderSumBankPaycash'].';'.$_POST['shopId'].';'.$_POST['invoiceId'].';'.
						$_POST['customerNumber'].';'.get_option('ym_shopPassword'));
			header('Content-Type: application/xml');
			if (strtolower($hash) != strtolower($_POST['md5']) and (isset($_POST['md5']))) { // !=
				$code = 1;
				echo '<?xml version="1.0" encoding="UTF-8"?><checkOrderResponse performedDatetime="'. $_POST['requestDatetime'] .'" code="'.$code.'"'. ' invoiceId="'. $_POST['invoiceId'] .'" shopId="'. get_option('ym_ShopID') .'" message="bad md5"/>';
			} else {
				$order = $wpdb->get_row('SELECT * FROM '.$wpdb->prefix.'posts WHERE ID = '.(int)$_POST['customerNumber']);
				$order_summ = get_post_meta($order->ID,'_order_total',true);
				if (!$order) {
					$code = 200;
					$answer = '<?xml version="1.0" encoding="UTF-8"?><checkOrderResponse performedDatetime="'. $_POST['requestDatetime'] .'" code="'.$code.'"'. ' invoiceId="'. $_POST['invoiceId'] .'" shopId="'. get_option('ym_ShopID') .'" message="wrong customerNumber"/>';
				} elseif ($order_summ != $_POST['orderSumAmount']) { // !=
					$code = 100;
					$answer = '<?xml version="1.0" encoding="UTF-8"?><checkOrderResponse performedDatetime="'. $_POST['requestDatetime'] .'" code="'.$code.'"'. ' invoiceId="'. $_POST['invoiceId'] .'" shopId="'. get_option('ym_ShopID') .'" message="wrong orderSumAmount"/>';
				} else {
					$code = 0;
					if ($_POST['action'] == 'paymentAviso') {
						$order_w = new WC_Order( $order->ID );
						$order_w->update_status('processing', __( 'Платеж успешно оплачен', 'woocommerce' ));
						$order_w->reduce_order_stock();
						$answer = '<?xml version="1.0" encoding="UTF-8"?><paymentAvisoResponse performedDatetime="'.date('c').'" code="'.$code.'" invoiceId="'.$_POST['invoiceId'].'" shopId="'.get_option('ym_ShopID').'" />';
					}
					else{
						$answer = '<?xml version="1.0" encoding="UTF-8"?><checkOrderResponse performedDatetime="'.date('c').'" code="'.$code.'" invoiceId="'.$_POST['invoiceId'].'" shopId="'.get_option('ym_ShopID').'" />';
					}
				}
			}
			die($answer);
		}
	}
}


