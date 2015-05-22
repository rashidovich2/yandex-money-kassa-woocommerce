<?php

/**
 * Plugin Name: yandexmoney_wp_woocommerce
 * Plugin URI: https://github.com/aTastyCookie/yandexmoney_wp_woocommerce
 * Description: Yandex.Kassa
 */

include_once 'yandex/yandex.php';
include_once 'bank/bank.php';
include_once 'terminal/terminal.php';
include_once 'webmoney/webmoney.php';
include_once 'yandex_wallet/yandex_wallet.php';



add_action('admin_menu', 'register_yandexMoney_submenu_page');


function register_yandexMoney_submenu_page() {
	$hook = add_submenu_page( 'woocommerce', 'Яндекс.Деньги Настройка', 'Яндекс.Деньги Настройка', 'manage_options', 'yandex_money_menu', 'yandexMoney_submenu_page_callback' ); 
	add_action('load-'.$hook,'yandexMoney_settings_save');
}
function yandexMoney_settings_save(){

}
function yandexMoney_update_option(){
	// echo '<pre>';
	// var_dump($_POST);
	// echo '</pre>';
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
function yandexMoney_submenu_page_callback() {
 if ( $_SERVER['REQUEST_METHOD'] == 'POST' && $_POST[ 'my_submit_hidden' ] == 'Y' ) {
                yandexMoney_update_option();
        ?>
                <div class="updated"><p><strong><?php echo "Настройки сохранены"; ?></strong></p></div><?php
        } ?>

<div class="wrap">
	<script>
		jQuery('document').ready(function ($) {
			$('.selmet').change(function (){
				switch ($(this).val()) {
					case '0':
						$('#ym_Wallet').hide()
						$('#ym_Plat').show()
						break;
					case '1':
						$('#ym_Plat').hide()
						$('#ym_Wallet').show()
						break;
				}
			})
		})
	</script>
	<h2>Настройки Яндекс.Деньги</h2>
	<form method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
			<input type="hidden" name="my_submit_hidden" value="Y">
			<div id="ym_get_funds">
				<h3>Как вы хотите получать средства?</h3>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">На расчетный счет организации с заключением договора с Яндекс.Деньгами</th>
						<td><input class="selmet" type="radio" name="ym_Funds" value="0" <?php echo get_option('ym_Funds')=='0'?'checked="checked"':''; ?> /></td>
					</tr>
					<tr valign="top">
						<th scope="row">Зачисление электронных денег на кошелек Яндекс Денег</th>
						<td><input class="selmet" type="radio" name="ym_Funds" value="1" <?php echo get_option('ym_Funds')=='1'?'checked="checked"':''; ?> /></td>
					</tr>
				</table>
			</div>
			<div id="ym_Plat" <?php echo get_option('ym_Funds')=='1'?'style="display:none;"':'style="display:block;"'; ?>>
				<tr valign="top">
					<th scope="row"><h3>Выберите способы оплаты:</h3></th>
				</tr>
				<table class="form-table">
					<?php
						global $woocommerce;
						foreach ($woocommerce->payment_gateways->payment_gateways as $obj) {
							$not=array("bacs","cheque","cod","paypal","yandex_wallet");
							if (!in_array($obj->id,$not)) {
								?>
								<tr valign="top">
									<th scope="row"><?php echo $obj->title; ?></th>
									<?php $arr = get_option("woocommerce_".$obj->id."_settings"); ?>
									<td><input type="checkbox" name="<?php echo "woocommerce_".$obj->id."_settings"; ?>" value="yes" <?php echo $arr['enabled']=='yes'?'checked="checked"':''; ?> /></td>
								</tr>
								<?php
							}
						}
					?>
					<tr valign="top">
						<th scope="row">E-mail для ежедневных уведомлений для сверки платежей</th>
						<td><input type="text" name="ym_Email" value="<?php echo get_option('ym_Email'); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row">Секретное слово (shopPassword) для обмена сообщениями</th>
						<td><input type="text" name="ym_Secret" value="<?php echo get_option('ym_Secret'); ?>" /></td>
					</tr>
				</table>
				<h3>Данные, выдаваемые при подключении к системе Яндекс.Деньги:</h3>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">paymentAvisoUrl and checkUrl<br/><span style="line-height: 1;font-weight: normal;font-style: italic;font-size: 12px;">Генерируются автоматически для Вашего сайта<span></th>
						<td><code><?php echo 'https://'.$_SERVER['HTTP_HOST']. '/?yandex_money=check'; ?></code></td>
					</tr>
					<tr valign="top">
						<th scope="row">Демо режим<br/><span style="line-height: 1;font-weight: normal;font-style: italic;font-size: 12px;">Включить демо режим для тестирования<span></th>
						<td><input type="checkbox" name="ym_Demo" <?php echo get_option('ym_Demo')=='on'?'checked="checked"':''; ?> /></td>
					</tr>
					<tr valign="top">
						<th scope="row">Scid<br/><span style="line-height: 1;font-weight: normal;font-style: italic;font-size: 12px;">Номер витрины магазина ЦПП<span></th>
						<td><input type="text" name="ym_Scid" value="<?php echo get_option('ym_Scid'); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row">ShopID<br/><span style="line-height: 1;font-weight: normal;font-style: italic;font-size: 12px;">Номер магазина ЦПП<span></th>
						<td><input type="text" name="ym_ShopID" value="<?php echo get_option('ym_ShopID'); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row">shopPassword<br/><span style="line-height: 1;font-weight: normal;font-style: italic;font-size: 12px;">Устанавливается при регистрации магазина в системе Яндекс.Деньги<span></th>
						<td><input type="text" name="ym_shopPassword" value="<?php echo get_option('ym_shopPassword'); ?>" /></td>
					</tr>
				</table>
			</div>
		<div id="ym_Wallet" <?php echo get_option('ym_Funds')=='0'?'style="display:none;"':'style="display:block;"'; ?>>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Номер счета в платежной системе Яндекс.Деньги</th>
					<td><input type="text" name="ym_WallNum" value="<?php echo get_option('ym_WallNum'); ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Ссылка для приема HTTP-уведомлений<br/><span style="line-height: 1;font-weight: normal;font-style: italic;font-size: 12px;">*эту ссылку нужно указать здесь: <a href="https://sp-money.yandex.ru/myservices/online.xml">https://sp-money.yandex.ru/myservices/online.xml</a><span></th>
					<td><code><?php echo 'https://'.$_SERVER['HTTP_HOST']. '/?yandex_money=check'; ?></code></td>
				</tr>
				<tr valign="top">
					<th scope="row">Секретное слово для обмена сообщениями<br/><span style="line-height: 1;font-weight: normal;font-style: italic;font-size: 12px;">*необходимо скопировать отсюда: <a href="https://sp-money.yandex.ru/myservices/online.xml">https://sp-money.yandex.ru/myservices/online.xml</a><span></th>
					<td><input type="text" name="ym_Secret" value="<?php echo get_option('ym_Secret'); ?>" /></td>
				</tr>
			</table>
		</div>
		<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" /></p>
	</form>
</div>
<?php
}

add_action('parse_request', 'YMcheckPayment');

function YMcheckPayment()
{
	global $wpdb;
	if ($_REQUEST['yandex_money'] == 'check') {
		//file_put_contents('log.txt', var_export($_REQUEST, true)."\r\n----------\r\n", FILE_APPEND | LOCK_EX);
		if ($_REQUEST['notification_type'] == 'card-incoming' || $_REQUEST['notification_type'] == 'p2p-incoming'){
			$hash = sha1($_REQUEST['notification_type'].'&'.$_REQUEST['operation_id'].'&'.$_REQUEST['amount'].'&'.$_REQUEST['currency'].'&'.
						$_REQUEST['datetime'].'&'.$_REQUEST['sender'].'&'.$_REQUEST['codepro'].'&'.get_option('ym_Secret').'&'.$_REQUEST['label']);
			//file_put_contents('log.txt', var_export($hash, true)."\r\n----------\r\n", FILE_APPEND | LOCK_EX);
			if ($_REQUEST['test_notification'] != 'true' && $hash == $_REQUEST['sha1_hash']){
				$order_w = new WC_Order( $_REQUEST['label'] );
				$order_w->update_status('processing', __( 'Платеж успешно оплачен', 'woocommerce' ));
				$order_w->reduce_order_stock();
			}
		} else {
			$hash = md5($_POST['action'].';'.$_POST['orderSumAmount'].';'.$_POST['orderSumCurrencyPaycash'].';'.
						$_POST['orderSumBankPaycash'].';'.$_POST['shopId'].';'.$_POST['invoiceId'].';'.
						$_POST['customerNumber'].';'.get_option('ym_shopPassword'));
			//file_put_contents('log.txt', var_export($hash, true)."\r\n----------\r\n", FILE_APPEND | LOCK_EX);
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
			//file_put_contents('log.txt', var_export($answer, true)."\r\n----------\r\n", FILE_APPEND | LOCK_EX);
			die($answer);
		}
	}
}


