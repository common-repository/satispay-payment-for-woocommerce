<?php
class Satispay_API_Endpoint{
	
	/** Hook WordPress
	*	@return void
	*/
	public function __construct(){
		add_filter('query_vars', array($this, 'add_query_vars'), 0);
		add_action('parse_request', array($this, 'sniff_requests'), 0);
		add_action('init', array($this, 'add_endpoint'), 0);
	}	
	
	/** Add public query vars
	*	@param array $vars List of current public query vars
	*	@return array $vars 
	*/
	public function add_query_vars($vars){
		$vars[] = 'orderid';
		$vars[] = 'status';
		$vars[] = 'module';
		return $vars;
	}
	
	/** Add API Endpoint
	*	@return void
	*/
	public function add_endpoint(){
		add_rewrite_rule('^api/sa/?([0-9]+)?/?','index.php?module=satispay&orderid=$matches[1]&status=$matches[2]','top');
	}
	public function sniff_requests(){
		global $wp;
		if(isset($wp->query_vars['module']) && ($wp->query_vars['module']=="satispay")){
			$this->handle_request();
			exit;
		}
	}
	protected function handle_request(){
		global $woocommerce;
		global $wp;
		$orderid = $wp->query_vars['orderid'];
		$status = strtoupper($wp->query_vars['status']);
		$msg = "Pagamento non effettuato";
		$order = new WC_Order($orderid);
		if ($status=="SUCCESS" ){
			if ($order->status =="pending"){
				$msg =__("Payment successfully completed with Satispay","satispay") . " :satispay callback";
			
				$order->update_status('processing');
				$order->add_order_note($msg);
				$order->reduce_order_stock();
				$woocommerce->cart->empty_cart();
				$order->payment_complete();
			}
			
		}
		
		//Se è andata male devo salvare il pagamento annullato
		if ($status=="FAILURE" ){
			if ($order->status =="pending") {
				$order->update_status('failed');
				$msg =  __("Payment Failed","satispay")  . ":satispay callback";
				$order->add_order_note($msg);
			}
		}
		echo "<h1>" . $status . "</h1>";
		echo $msg;
	}
}