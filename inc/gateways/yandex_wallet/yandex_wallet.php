<?php
	function YW_gateway_icon( $gateways ) {
		if ( isset( $gateways['yandex_wallet'] ) ) {
			$url=WP_PLUGIN_URL."/".dirname( plugin_basename( __FILE__ ) );
			$gateways['yandex_wallet']->icon = $url . '/ym_icon.png';
		}
	 
		return $gateways;
	}
	 
	add_filter( 'woocommerce_available_payment_gateways', 'YW_gateway_icon' );

add_action('plugins_loaded', 'woocommerce_YW_payu_init', 0);
function woocommerce_YW_payu_init(){
  if(!class_exists('WC_Payment_Gateway')) return;
 
  class WC_YW_Payu extends WC_Payment_Gateway{
    public function __construct(){
      $this -> id = 'yandex_wallet';
      $this -> method_title  = 'Яндекс.Касса';
      $this -> has_fields = false;
 
      $this -> init_form_fields();
      $this -> init_settings();
 
		$this->title              = $this->get_option( 'title' );
		$this->description        = $this->get_option( 'description' );
      $this -> scid = $this -> settings['scid'];
      $this -> ShopID = $this -> settings['ShopID'];
      $this -> liveurl = '';
 
      $this -> msg['message'] = "";
      $this -> msg['class'] = "";
 
      if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
             } else {
                add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
            }
      add_action('woocommerce_receipt_yandex_wallet', array(&$this, 'receipt_page'));
   }
    function init_form_fields(){
 
       $this -> form_fields = array(
                'enabled' => array(
                    'title' => __('Включить/Выключить','yandex_wallet'),
                    'type' => 'checkbox',
                    'label' => __('Включить модуль оплаты через Яндекс.Кассу','yandex_wallet'),
                    'default' => 'no'),
                'title' => array(
                    'title' => __('Заголовок','yandex_wallet'),
                    'type'=> 'text',
                    'description' => __('Название, которое пользователь видит во время оплаты','yandex_wallet'),
                    'default' => __('Яндекс.Касса','yandex_wallet')),
                'description' => array(
                    'title' => __('Описание','yandex_wallet'),
                    'type' => 'textarea',
                    'description' => __('Описание, которое пользователь видит во время оплаты','yandex_wallet'),
                    'default' => __('Оплата через Яндекс.Кассу','yandex_wallet'))
            );
    }
 
       public function admin_options(){
        echo '<h3>'.__('Оплата через Яндекс.Кассу','yandex_wallet').'</h3>';
        echo '<table class="form-table">';
        $this -> generate_settings_html();
        echo '</table>';
 
    }
 
    /**
     *  There are no payment fields for payu, but we want to show the description if set.
     **/
    function payment_fields(){
        if($this -> description) echo wpautop(wptexturize($this -> description));
    }
    /**
     * Receipt Page
     **/
    function receipt_page($order){
        echo $this -> generate_payu_form($order);
    }
    /**
     * Generate payu button link
     **/
    public function generate_payu_form($order_id){
 
        global $woocommerce;
 
        $order = new WC_Order($order_id);
        $txnid = $order_id;
		$sendurl='https://money.yandex.ru/quickpay/confirm.xml';
       $result ='';
		$result .= '<form name=ShopForm method="POST" id="submit_Yandex_Wallet_payment_form" action="'.$sendurl.'">';
			$result .= '<input type="hidden" name="receiver" value="'.get_option('ym_WallNum').'">';
			$result .= '<input type="hidden" name="formcomment" value="'.get_bloginfo('name').': '.$txnid.'">';
			$result .= '<input type="hidden" name="short-dest" value="'.get_bloginfo('name').': '.$txnid.'">';
			$result .= '<input type="hidden" name="label" value="'.$txnid.'">';
			$result .= '<input type="hidden" name="quickpay-form" value="shop">';
			$result .= '<input type="hidden" name="targets" value="транзакция {'.$txnid.'}">';
			$result .= '<input type="hidden" name="sum" value="'.number_format( $order->order_total, 2, '.', '' ).'" data-type="number" >';
			$result .= '<input type="hidden" name="comment" value="'.$order->customer_note.'" >';
			$result .= '<input type="hidden" name="need-fio" value="false">';
			$result .= '<input type="hidden" name="need-email" value="false" >';
			$result .= '<input type="hidden" name="need-phone" value="false">';
			$result .= '<input type="hidden" name="need-address" value="false">';
			$result .= '<input id="PC" type="radio" name="paymentType" value="PC"><label for="PC">Оплата из кошелька в Яндекс.Деньгах.</label><br/>';
			$result .= '<input id="AC" type="radio" name="paymentType" value="AC"><label for="AC">Оплата с произвольной банковской карты.</label><br/>';
			$result .= '<input type="submit" name="submit-button" value="Перевести">';
		$result .='</form>';
		return $result;
 
    }
    /**
     * Process the payment and return the result
     **/
   function process_payment($order_id){
        $order = new WC_Order($order_id);
		return array('result' => 'success', 'redirect' => $order->get_checkout_payment_url( true ));

    }
 
    
    function showMessage($content){
            return '<div class="box '.$this -> msg['class'].'-box">'.$this -> msg['message'].'</div>'.$content;
        }
     // get all pages
    function get_pages($title = false, $indent = true) {
        $wp_pages = get_pages('sort_column=menu_order');
        $page_list = array();
        if ($title) $page_list[] = $title;
        foreach ($wp_pages as $page) {
            $prefix = '';
            // show indented child pages?
            if ($indent) {
                $has_parent = $page->post_parent;
                while($has_parent) {
                    $prefix .=  ' - ';
                    $next_page = get_page($has_parent);
                    $has_parent = $next_page->post_parent;
                }
            }
            // add to page list array array
            $page_list[$page->ID] = $prefix . $page->post_title;
        }
        return $page_list;
    }
}
   /**
     * Add the Gateway to WooCommerce
     **/
    function woocommerce_add_YW_payu_gateway($methods) {
        $methods[] = 'WC_YW_Payu';
        return $methods;
    }
 
    add_filter('woocommerce_payment_gateways', 'woocommerce_add_YW_payu_gateway' );
}