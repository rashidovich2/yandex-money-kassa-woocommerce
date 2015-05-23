<?php
add_action('admin_menu', 'register_yandexMoney_submenu_page');
function register_yandexMoney_submenu_page() {
	$hook = add_submenu_page( 'woocommerce', 'Яндекс.Деньги Настройка', 'Яндекс.Деньги Настройка', 'manage_options', 'yandex_money_menu', 'yandexMoney_submenu_page_callback' ); 
	add_action('load-'.$hook,'yandexMoney_settings_save');
}
function yandexMoney_settings_save(){

}
function yandexMoney_submenu_page_callback() {
	if ( $_SERVER['REQUEST_METHOD'] == 'POST' && $_POST[ 'my_submit_hidden' ] == 'Y' ) {
		yandexMoney_update_option();?>
		<div class="updated"><p><strong><?php echo "Настройки сохранены"; ?></strong></p></div>
	<?php } ?>

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
<?php }