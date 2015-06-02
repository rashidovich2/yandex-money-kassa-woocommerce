<?php
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