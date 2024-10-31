<?php
/*
Plugin Name: Satispay Payment for WooCommerce
Plugin URI: https://www.satispay.com
Description: Integrate Satispay into Woocommerce site. Send and receive money the smart way! 
Version: 1.6
Author: Satispay
Author URI: https://www.satispay.com
Tags: woocommerce, satispay, payment, payment-gateway woocommerce
Requires at least:  4.3
Tested up to: 4.5.3
Stable tag: 4.5.3
License: GPLv3 or later License
URI: http://www.gnu.org/licenses/gpl-3.0.html
*/
ob_start(); 
if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
				add_action('plugins_loaded', 'woocommerce_satispay_init', 0);
				
				add_action('wp_ajax_nopriv_satispaybasket',  'satispaybasket');
				add_action('wp_ajax_satispaybasket', 'satispaybasket');
				add_action('wp_ajax_nopriv_satispaycheckstatus',  'satispaycheckstatus');
				add_action('wp_ajax_satispaycheckstatus', 'satispaycheckstatus');
				add_action('wp_ajax_nopriv_redictectSatispay',  'redictectSatispay');
				add_action('wp_ajax_redictectSatispay', 'redictectSatispay');
				require("callback.php");
				new Satispay_API_Endpoint();
				function woocommerce_satispay_init(){
				  if(!class_exists('WC_Payment_Gateway')) return;
				
				  class WC_Satispay extends WC_Payment_Gateway{
						public function __construct(){
						
							//Register Styles
							add_action( 'wp_enqueue_scripts', array( &$this, 'register_styles' ) );
							
							
							add_action( 'admin_enqueue_scripts', array( &$this, 'register_styles_admin' ) );
							
							$plugin_dir = basename(dirname(__FILE__));
							load_plugin_textdomain( 'satispay', false, $plugin_dir . '/i18n/' );	
							
						
							$this -> id = 'satispay';
							$this -> medthod_title = 'Satispay';
							$this -> has_fields = false;
							
							$this->init_form_fields();
							$this->init_settings();
							$this->versione = 'v1';
							$this->test =  'no';
							$this->enabled = $this->settings['enabled'];
							$this->title = $this->settings['title'];
							$this->description = $this->settings['description'];
							$this->merchant_id = $this->settings['merchant_id'];
							$this->nomenegozio = $this->settings['nomenegozio'];
									
							if ($this->test=="no")  $this -> liveurl = 'https://authservices.satispay.com/online/' . $this->versione . "/";
							else $this -> liveurl = 'https://staging.authservices.satispay.com/online/' . $this->versione . "/";
							
							$this->urlcheck_token = $this -> liveurl . '/wally-services/protocol/authenticated';
							
							if (($this->enabled=="yes") && trim($this->merchant_id)=="" )  {
								$this->settings['enabled'] = "no";
								$this->enabled = "no";
							};
							
							//API: Check Token - NEW 26/06/2016	
							$check_token =checktoken_curl($this->merchant_id,$this->urlcheck_token);
							
							if ($check_token=="no") { 
								$this->settings['enabled'] = "no";
								$this->enabled = "no";
								add_action( 'admin_notices',  array(&$this,'sample_admin_notice__error' ));
							}
							
							if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
									add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
							} else {
								add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
							}
							
							add_action('woocommerce_receipt_satispay', array(&$this, 'receipt_page'));
							
				   		
						
						}
					   function sample_admin_notice__error() {
						$class = 'notice notice-error';
						$message = __('Invalid token! Please enter a valid token to use Satispay as a payment method', "satispay");
					
						printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ); 
					}
					  
						public function get_icon() {
				
								$icon_html = '<img src="' .   plugins_url( 'assets/img/button-grey.png', __FILE__ )  . '"  alt="' . esc_attr( $this->get_title() ) . '" />';
								
								$icon_html .= sprintf( '<a href="%1$s"  onclick="javascript:window.open(\'%1$s\',\'WISatispay\',\'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=1060, height=700\'); return false;" title="' . esc_attr__( 'What Satispay?', 'satispay' ) . '">' . esc_attr__( 'What Satispay?', 'satispay' ) . '</a>', 'https://www.satispay.com/' );
								
								return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
							
							
						}
					
						function init_form_fields(){
							
						   $this -> form_fields = array(
									'enabled' => array(
										'title' => __('Enable/Disable', 'satispay'),
										'type' => 'checkbox',
										'label' => __('Enable Satispay Payment Module.', 'satispay'),
										'default' => 'no'),
										
									
									'merchant_id' => array(
										'title' => __('Token Merchant ID', 'satispay'),
										'type' => 'text',
										'description' => __('Request your security token to satispay for your online shop', 'satispay'),
										 'default' => ''),
										
									'title' => array(
										'title' => __('Title:', 'satispay'),
										'type'=> 'text',
										'description' => __('This controls the title which the user sees during checkout.', 'satispay'),
										'default' => __('Satispay', 'satispay')),
									'description' => array(
										'title' => __('Description:', 'satispay'),
										'type' => 'textarea',
										'description' => __('This controls the description which the user sees during checkout.', 'satispay'),
										'default' => __('Send and receive money the smart way!', 'satispay')),
									
									
									'nomenegozio' => array(
										'title' => __('Name Shop', 'satispay'),
										'type' => 'text'
									)
									
									
								);
						}
				
				
				
					   public function admin_options(){
							echo '<div class="satispay_gateway_form">';
							echo '<h1>'.__('Satispay Payment Gateway', 'satispay'). ': ' .__('The best for your business','satispay').'</h1>';
							echo '<p>'.__('Satispay was created to help you make the most from your business. No risk of counterfeit money or theft; just fast, secure, cheap payments.','satispay').'</p>';
							
							
							if ($this->merchant_id==""){
								echo '<a class="btn-satispaybusiness" href="http://business.satispay.com/signup" target="_blank">' . __("Register your business","satispay") . '</a>';
							}
							else
								{ echo '<a class="btn-satispaybusiness" href="http://business.satispay.com/login" target="_blank">' . __("Go to the Control Panel","satispay") . '</a>';
								}
							echo '<div class="satispay_help">';
							
							echo '<a href="https://www.satispay.com/faq/" target="_blank">' . __("FAQ","satispay") . '</a>';
							
							echo '<a href="https://www.satispay.com/contattaci/" target="_blank">' . __("HELP: Contact us","satispay") . '</a>';
							
							echo '<a href="https://www.satispay.com/costi/" target="_blank">' . __("Price","satispay") . '</a>';
							
							echo '<br/><a href="https://www.facebook.com/satispay/" target="_blank">' . __("Facebook","satispay") . '</a>';
							
							
							echo '</div>';
							echo '</div>';
							
							
							$enabled = $this->enabled;
							$token = $this->merchant_id;
							if (($enabled=="yes") && trim($token)=="" )  {
								echo '<div class="inline error"><p><strong>' . __( 'Warning', 'satispay' ). '</strong>: ' . __("you must enter the token that Satispay sent in order to use the payment method","satispay") . '</p></div>';
								$this->settings['enabled']="no";
							}
							
							
							echo '<table class="form-table">';
							// Generate the HTML For the settings form.
							$this -> generate_settings_html();
							echo '</table>';
						
					
						
				
					}
				
				
					function payment_fields(){
						if($this -> description) echo wpautop(wptexturize($this -> description));
					}
					
					
					function receipt_page($order){
						echo $this -> generate_satispay_form($order);
					}
				
				
			
				
				
				
				
				
				
					public function generate_satispay_form($order_id){
				
					   global $woocommerce;
					   
					   
					 	$nomenegozio = get_bloginfo( 'name' );
					   
						$order = new WC_Order( $order_id );
						$txnid = $order_id.'_'.date("ymds");
				
						$productinfo = __("Buying from","satispay") . " " . strtoupper($nomenegozio) . " - " . __("Order no.","satispay") . " " . $order_id ;
		
				
						$amount = $order -> order_total;
						list ($integer,$decimal) = array_pad(explode(wc_get_price_decimal_separator(),$amount, 2), 2, null);
						$integer_sep =  explode (wc_get_price_thousand_separator(),$integer);
						$integer = $integer_sep[0] .  $integer_sep[1];
							
						$satispay_args = array(
						 // 'key' => $this -> merchant_id,
						  'txnid' => $txnid,
						  'amount' => $order -> order_total,
						  'getInteger' => $integer . "" . $decimal,
						  'productinfo' => $productinfo,
						  'firstname' => $order -> billing_first_name,
						  'lastname' => $order -> billing_last_name,
						  'address1' => $order -> billing_address_1,
						  'address2' => $order -> billing_address_2,
						  'city' => $order -> billing_city,
						  'state' => $order -> billing_state,
						  'country' => $order -> billing_country,
						  'zipcode' => $order -> billing_zip,
						  'email' => $order -> billing_email,
						  'phone' => $order -> billing_phone,
						  'pg' => 'NB'
						  );
				
						$satispay_args_array = array();
						foreach($satispay_args as $key => $value){
						  $satispay_args_array[] = "<input type='hidden' id='$key'  name='$key' value='$value'/>";
						}
						
						$intel = "";
						//echo $order ->billing_country;
						$order->billing_phone = checktelephone($order ->billing_country,$order -> billing_phone);
						
						
						//$form= '<form action="'.$this->liveurl.'" method="post" id="satispay_form">' . implode('', $satispay_args_array);
							$form= '<form   method="post" id="satispay_form"  name="form" >' . implode('', $satispay_args_array);
							
							
							
							
							
							 $form .= '<div class="satispay_class">';
							 
							 
							 $form .=  '
							 
							 
							 <DIV CLASS="loading_satispay"></DIV>
							 
							 <div class="form_satispay"><p> ' . __("To confirm your order", "satispay") . " <b>" . __("enter you mobile number", "satispay") . '</b><br/><small>'  .  __("If you are not registered","satispay") .  ', <a class="link-red" href="' . $this->urlpromo  . '"  target="_blank">' . __("sign up now","satispay")  . '.</a></small></p>';
							 
							 
					
						$form .=  '
						
							<div class="telefono alt error_internal" id="error_internal_0"><div class="message_satispay">' . __("You need to enter your phone number","satispay") . '</div></div>
						
							<input class="number_phone_satispay"  type="tel" value="' . $order -> billing_phone . '">
							<input class="number_phone_satispay_full" type="hidden" name="number_phone_satispay_full">
							<input type="button" class="button-alt btn-submit_satispay"/> 
							
							</div>
							</form>
							
							<!-Inizio Label errori , warning e messaggi-->
							
							<div class="light_satispay alt" id="error_external">
								<div class="message_satispay">' . __("Internal error, please try again later","satispay") . '</div>
							</div>
							
							<div class="light_satispay light_satispay_yellow" id="error_external_49" >
								<div class="message_satispay"> 
									<h3><strong>' . __("The phone number","satispay") . " " .  __("is not linked to a Satispay account","satispay") . '</strong></h3>
				
									<p>' . __("To send money you must first sign up","satispay") .  '<br />
									</p>
										<div class="text-muted vertical" >
										  
											<a target="new" class="btn_satispay" href="https://www.satispay.com/promo/' . $this -> nomenegozio . '">' .  __("Sign up for free to Satispay","satispay") . '</a>
										</div>
									</div>
							</div>    
								
								
							
								
							<div class="light_satispay light_satispay_blue" id="waiting_satispay">
								<div class="message_satispay"> 
									<h3 class="display-inline"><strong>'   . __("The payment request has benn sent","satispay") . '</strong></h3>
					
									<p>'   . __("The payment request has been sent to","satispay") . ' <strong class="numero"></strong>
										<br>'
										.  __("To complete the transaction","satispay") . '<strong> '
										.  __("open the app and confirm the payment","satispay") . '.</strong><br>
									</p>
								</div> 
							</div>
								
						
							
							<div class=" light_satispay light_satispay_red" id="satispay_FAILURE">	
								<div class="message_satispay"> 
								
									 <h3 class="display-inline"><strong>'.  __("Payment cancelled","satispay") . '</strong></h3>'
									
										.  __("The payment was cancelled. Please try again","satispay") . 
										
										'<br/><a class="button cancel"  href="'.$order->get_cancel_order_url().'">'.__('Choose other payment methods', 'satispay').'</a>
										
									
								</div>
							</div>
							
							
							<div class=" light_satispay light_satispay_green" id="satispay_SUCCESS">	
								<div class="message_satispay"> 
									'
										.  __("Transaction completed","satispay") . '<br/>
									'
										.  __("We have sent the payment confirmation to your email", "satispay") .   '<br/>
								</div>
							</div>
						
					
					
							
							
							
					</div>
				
				</form>	
				
				<a class="button cancel"  href="'.$woocommerce->cart->get_cart_url().'">'.__('Returns to cart', 'satispay').'</a>
							<script type="text/javascript">
							jQuery(function(){
									
									
								
									jQuery(".number_phone_satispay").intlTelInput({
										defaultCountry: ["it"],
										preferredCountries: ["it"],
										autoPlaceholder: false,

										 utilsScript: "' . plugins_url( '/lib/intl-tel-input/js/utils.js', __FILE__ ) . '"
									});
										
									
									jQuery(".btn-submit_satispay").click(function() {
										callsatispay();
									});
									
									jQuery(".satispay_class").keypress(function(e) {
										
  										if (e.which == 13) {
											e.preventDefault();
											callsatispay();
										}
									});
									
								
										
							});
							
							
							function callsatispay(){
							
										jQuery(".error_internal").hide();
										jQuery(".light_satispay").hide();
										
										
										var numberfull = jQuery(".number_phone_satispay").intlTelInput("getNumber");
										var description = jQuery("#productinfo").val();
										var txnid = jQuery("#txnid").val();
										var unit = jQuery("#getInteger").val();
										var ajaxurl = "' .  admin_url( 'admin-ajax.php' ) . '";
										
										jQuery.ajax({
											type: "POST",
											url: ajaxurl,
											dataType : "json",
											method : "POST",
											data: {
												action:"satispaybasket",
												verb:"post",
												orderid:"' . $order_id . '",
												numberfull: numberfull,
												description: description,
												txnid: txnid,
												unit: unit
											},
											beforeSend: function(response) {jQuery(".loading_satispay").show(); },
											success: function(response) {
												//console.log(response);
												jQuery(".loading_satispay").hide();
												//C\'è un errore interno, prima dell\'API Satispay
												//console.log(response);
												if ((isSet(response.type)) && (response.type=="internal")){
													//console.log(response.error);
													switch(response.error){
														case 0:
															console.log(response.error);
															jQuery("#error_internal_0").show();
														break;
														default: console.log("no")
													}
												}
												
												
												if ((isSet(response.type)) && (response.type=="external")){
														switch(response.error){
															case 52:
																jQuery("#error_external .message_satispay h3 strong").html("' . __("Internal error, please try again later","satispay") . '");
																jQuery("#error_external").show();
															
															
															case 39:
																jQuery("#error_external .message_satispay h3 strong").html("' . __("The phone number is not formatted correctly","satispay") . '");
																jQuery("#error_external").show();
																
															case 49:
																jQuery("#error_external_49 .message_satispay h3 strong").show();
																jQuery("#error_external_49 .numero").html(numberfull);
																
																
															case 45:
																jQuery("#error_external .message_satispay h3 strong").html("' . __("Internal error, shop does not exist","satispay") . '");
															
															
															default: 
																jQuery("#error_external .message_satispay h3 strong").show("' . __("Internal error, please try again later","satispay") . '");
														}	
													}
												
												//Tutto ok
												if (isSet(response.uuid)){
													 if (response.status == "REQUIRED") {
														jQuery("#waiting_satispay").show();
														jQuery("#waiting_satispay .numero").html(numberfull);
														paymentStatus(response.uuid);
													 }
													
												}
												
											},
											error: function(response) {
												jQuery("#error_external").html("' . __("Internal error, please try again later","satispay") . '");
												jQuery("#error_external").show();
											}
										
										});
								
							}
							
							function paymentStatus(uuid){
								var ajaxurl = "' .  admin_url( 'admin-ajax.php' ) . '";
										
								jQuery.ajax({
									type: "POST",
									url: ajaxurl,
									dataType : "json",
									method : "POST",
									data: {
										action:"satispaycheckstatus",
										verb:"get",
										uuid:uuid,
										orderid:"' . $order_id . '"
									},
									success: function(response) {
										//console.log(response);
										if (response.status == "REQUIRED") {
											setTimeout(paymentStatus(uuid),8000);
										}
										console.log(response);
										if (response.status != "REQUIRED") {
											jQuery("#satispay_"+response.status).show();
											jQuery("#waiting_satispay").hide();
											//jQuery(".form_satispay").hide();
											
											jQuery(".cancel").hide();
										
											var status = response.status;
											if (status=="FAILURE")  jQuery(".message_satispay .cancel").show();
											else {
												jQuery.ajax({
													type: "POST",
													url: ajaxurl,
													method : "POST",
													data: {
														action:"redictectSatispay",
														verb:"get",
														uuid:uuid,
														orderid:"' . $order_id . '",
														status: status 
													},
													success: function(response) {
														if (response!="")
															setTimeout(jQuery(location).attr("href", response),5000);
														
													}
												
												});
											}
											
										}
										
									},
									error: function(response) {
										jQuery("#error_external").html("' . __("Internal error, please try again later","satispay") . '");
										jQuery("#error_external").show();
									}
								
								});
								
								
							
							}
							
							function isSet(iVal){
									return (iVal!=="" && iVal!=null && iVal!==undefined && typeof(iVal) != "undefined") ? true: false;
								}
							</script>
							';
				
							
							return $form;
				
				
					}
					/**
					 * Process the payment and return the result
					 **/
					function process_payment($order_id){
						global $woocommerce;
						$order = new WC_Order( $order_id );
						return array('result' => 'success', 'redirect' => $order->get_checkout_payment_url( true ));
					
						
					}
				
					
					public function satispay_showMessage(){
							if ($this->messageclass!="" && $this->messagecontent!="" )
								echo '<div class="'. $this->messageclass . '"><p>' . $this->messagecontent . '</p></div>';
						
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
					
					
					  function register_styles() {
					   
						
						
						wp_register_style( 'satispay_css', plugins_url( '/assets/css/style.css', __FILE__ ), array(), time(), 'all' );
						wp_enqueue_style( 'satispay_css' );
						
						 wp_register_style( 'satispay_inputnumber_css', plugins_url( '/lib/intl-tel-input/css/intlTelInput.css', __FILE__ ), array(), time(), 'all' );
						 
						  wp_enqueue_style( 'satispay_inputnumber_css' );
						  
						  
						  wp_register_script( 'satispay_inputnumber_js',plugins_url( '/lib/intl-tel-input/js/intlTelInput.min.js', __FILE__ ), array( 'jquery' ), time(),'all' );
						wp_enqueue_script( 'satispay_inputnumber_js' );
						
					}
				
					
					
					 function register_styles_admin() {
						 wp_register_style( 'satispayadmin_css', plugins_url( '/assets/css/style_admin.css', __FILE__ ), array(), time(), 'all' );
						wp_enqueue_style( 'satispayadmin_css' );
					 }
					
				
					
					
				}
				
				
				}
				
				   /**
					 * Add the Gateway to WooCommerce
					 **/
					function woocommerce_add_satispay_gateway($methods) {
						$methods[] = 'WC_Satispay';
						return $methods;
					}
				
					add_filter('woocommerce_payment_gateways', 'woocommerce_add_satispay_gateway' );
				
				
				
					function checktoken_curl ($token, $urlcheck_token ){
				
						$ch = curl_init();
						$httpHeader = array(
							"Content-Type: application/json",
							"Authorization:Bearer " . $token ,
						);
						curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
						curl_setopt($ch, CURLOPT_URL, $urlcheck_token );
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						$result = curl_exec($ch);
						curl_close($ch);
						$return = json_decode($result);
			
						return (isset($return->code)) && (($return->code=="34") || ($return->code=="45")) ? "no" : "ok";
					}
				
				  
				  
				  
				  
				   /**
					 * Check for valid payu server callback
					 **/
				   function satispaybasket(){
					   
						//header('Content-Type: application/json');
					   
						global $woocommerce;
						$satispay = new WC_Satispay();
						$number = $_POST['numberfull'];
						$description = $_POST['description'];
						$unit = $_POST['unit'];
						$txtnid = $_POST['txtnid'];
						$merchandid = $satispay->merchant_id;
						$orderid = $_POST['orderid'];
						
						
						if(isset($number) && ($number!="")){
							
							
							//1-CONTROLLO SE ESISTE GIA' L'UTENTE    -   //USERS METHOD GET
							//echo  $this->merchant_id;
							$httpHeader = array(
								"Content-Type: application/json",
								//"Idempotency-Key: " . $txtnid ,
								"Authorization:Bearer " . $merchandid . "",
							);
							
							//echo $satispay->liveurl . "users";
							$users_get = satispay_curl($satispay->liveurl . "users","GET",array(),$httpHeader);
							
							$lista_user = $users_get->list;
							$found = $users_get->found;
							$uuid= 0;
							
							
							//controllo IN LISTA USER
							if ($found > 0){
								$found = 0;
								foreach($lista_user  as $item){
									
									if($item->phone_number == $number){
										$found = 1;
										$uuid = $item->uuid;
									}
								}
							}
							
							
							
							
							//2-SE NON ESISTE CREO USER
							if ($found == 0 ){
								
								$param = array("phone_number"=>$number);
								$bodyparam = json_encode($param); 
								$httpHeader = array(
									"Content-Type: application/json",
									"Idempotency-Key: " . $txtnid ,
									"Authorization:Bearer " . $merchandid ,
									"Content-Length: " . strlen($bodyparam) 
								);
								
								
								$return = satispay_curl($satispay->liveurl . "users","POST",$bodyparam ,$httpHeader);
								
								if (isset($return->code)){
									$msg_error = "error";
									$return = array("type"=>"external","error"=>$return->code, "message"=>$msg_error);
									
									//echo json_encode($msgerror);
									//wp_die();
								} else {
									
									
									$uuid = $return->uuid;
									
									
									
								}
							}
							
						
							
							
							//3-RECUPERO L'ID E PROSEGUO
							if ($uuid!="0"){
							
							$callbackurl = site_url() . "/index.php?module=satispay&orderid=" . $orderid ;
							
								$param = array(   
										"description"=>$description,
										"currency"=>"EUR",
										"amount"=>$unit,
										"user_uuid"=>$uuid,
										"required_success_email"=>"true",
										"expire_in"=>20,
										"callback_url" => $callbackurl
										);
					
								
								
								$bodyparam = json_encode($param);
								
								//$bodyparam = '';
							
								$httpHeader = array(
									"Content-Type: application/json",
									//"Idempotency-Key: " . $txtnid ,
									"Authorization:Bearer " . $merchandid ,
									"Content-Length: " . strlen($bodyparam) 
								);
					
								$return = satispay_curl($satispay->liveurl . "charges","POST",$bodyparam ,$httpHeader);
							
							}
							
						} else {
								
							$return = array("type"=>"internal","error"=>0, "message"=>__("You need to enter your phone number","satispay"));
							
								
						}
						
						
						echo json_encode ($return);
						wp_die();
				
					}
				  
				  
				  
					function satispaycheckstatus(){
						
						global $woocommerce;
						$satispay = new WC_Satispay();
						$uuid = $_POST['uuid'];
						$orderid = $_POST['orderid'];
						$merchandid = $satispay->merchant_id;
						
						$httpHeader = array(
							"Content-Type: application/json",
							//"Idempotency-Key: " . $txtnid ,
							"Authorization:Bearer " . $merchandid . "",
						);
							
						
						$order = new WC_Order($orderid);
						$return = satispay_curl($satispay->liveurl . "charges/" .$uuid,"GET", array() ,$httpHeader);
						$check = json_encode ($return);
			
						//Se è andato tutto bene devo salvare nel db la transizione
						if ($return->status=="SUCCESS" ){
							if ($order->status =="pending") {
								$msg =__("Payment successfully completed with Satispay","satispay") . ":" . $return->user_short_name . " " . $return->expire_date ;
								$order->update_status( 'processing');
								$order->add_order_note($msg);
								$order->reduce_order_stock();
								$woocommerce->cart->empty_cart();
								$order->payment_complete();
							}
							
						}
						
						//Se è andata male devo salvare il pagamento annullato
						if ($return->status=="FAILURE" ){
							if ($order->status =="pending") {
								$order->update_status( 'failed');
								$msg =  __("Payment Failed","satispay")  . ":" . $return->status_details . " "  . $return->user_short_name . " " .$return->expire_date ;
								$order->add_order_note($msg);
							}
							
						}
						
						
						echo $check ;
						wp_die();
						
					}
					
					
									
					
									
				  
				   function satispay_curl($url,$method,$param,$httpHeader){
						
						$ch = curl_init();
						
						/*
						
						$connect_timeout = 5; //sec
						
						$base_time_limit = (int) ini_get('max_execution_time');
						if ($base_time_limit < 0) {
						$base_time_limit = 0;
						}
						$time_limit = $base_time_limit - $connect_timeout - 2;
						if ($time_limit <= 0) {
						$time_limit = 20; //default
						}
						*/
						//curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
						curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
						//curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connect_timeout);
						//curl_setopt($ch, CURLOPT_TIMEOUT, $time_limit);
						curl_setopt($ch, CURLOPT_URL, $url);
					
						curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
						if (strtoupper($method)=="POST"){
							curl_setopt($ch, CURLOPT_POST, 1);
							curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");   
							curl_setopt($ch, CURLOPT_POSTFIELDS, $param );
						}
						//curl_setopt($ch,CURLOPT_FOLLOWLOCATION,TRUE);
						//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); 
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
						
						$result = curl_exec($ch);
						
						$info = curl_getinfo($ch);
						if (!isset($info['http_code'])) {
						$info['http_code'] = '';
						}
						
						$curl_errno = curl_errno($ch);
						$curl_error = curl_error($ch);
						
						if (curl_errno($ch)) {
							$return= array(
								'http_code' => $info['http_code'],
								//'info' => $info,
								'status' => 'ERROR1',
								'errno' => $curl_errno,
								'error' => $curl_error,
								'result' => NULL
							);
						} else {
							$return = json_decode($result); 
						}
						//var_dump($info);
						//echo $url;
						//wp_die();
						
						curl_close($ch);
						return $return;
					
					}
				
				
				
				
				
				
					function redictectSatispay(){
						global $woocommerce;
						$uuid = $_POST['uuid'];
						$orderid = $_POST['orderid'];
						$order = new WC_Order($orderid);
						$status = $_POST['status'];
						if ($status=="SUCCESS"){
						//wp_redirect(home_url().'/checkout/order-received/'.$order->id.'/?key='.$order->order_key );
						//echo home_url().'/checkout/order-received/'.$order->id.'/?key='.$order->order_key ;
							echo $order->get_checkout_order_received_url();
							//$order->payment_complete();
						}
						/*else 
							echo $order->get_checkout_order_received_url();	*/
							
						wp_die();
					}
					
								
					
					
					
			function checktelephone($countrycode,$phonenumber) {
				
				$countries = array();
				$countries["AF"]=array("name"=>"Afghanistan","d_code"=>"+93");
				$countries["AL"]=array("name"=>"Albania","d_code"=>"+355");
				$countries["DZ"]=array("name"=>"Algeria","d_code"=>"+213");
				$countries["AS"]=array("name"=>"American Samoa","d_code"=>"+1");
				$countries["AD"]=array("name"=>"Andorra","d_code"=>"+376");
				$countries["AO"]=array("name"=>"Angola","d_code"=>"+244");
				$countries["AI"]=array("name"=>"Anguilla","d_code"=>"+1");
				$countries["AG"]=array("name"=>"Antigua","d_code"=>"+1");
				$countries["AR"]=array("name"=>"Argentina","d_code"=>"+54");
				$countries["AM"]=array("name"=>"Armenia","d_code"=>"+374");
				$countries["AW"]=array("name"=>"Aruba","d_code"=>"+297");
				$countries["AU"]=array("name"=>"Australia","d_code"=>"+61");
				$countries["AT"]=array("name"=>"Austria","d_code"=>"+43");
				$countries["AZ"]=array("name"=>"Azerbaijan","d_code"=>"+994");
				$countries["BH"]=array("name"=>"Bahrain","d_code"=>"+973");
				$countries["BD"]=array("name"=>"Bangladesh","d_code"=>"+880");
				$countries["BB"]=array("name"=>"Barbados","d_code"=>"+1");
				$countries["BY"]=array("name"=>"Belarus","d_code"=>"+375");
				$countries["BE"]=array("name"=>"Belgium","d_code"=>"+32");
				$countries["BZ"]=array("name"=>"Belize","d_code"=>"+501");
				$countries["BJ"]=array("name"=>"Benin","d_code"=>"+229");
				$countries["BM"]=array("name"=>"Bermuda","d_code"=>"+1");
				$countries["BT"]=array("name"=>"Bhutan","d_code"=>"+975");
				$countries["BO"]=array("name"=>"Bolivia","d_code"=>"+591");
				$countries["BA"]=array("name"=>"Bosnia and Herzegovina","d_code"=>"+387");
				$countries["BW"]=array("name"=>"Botswana","d_code"=>"+267");
				$countries["BR"]=array("name"=>"Brazil","d_code"=>"+55");
				$countries["IO"]=array("name"=>"British Indian Ocean Territory","d_code"=>"+246");
				$countries["VG"]=array("name"=>"British Virgin Islands","d_code"=>"+1");
				$countries["BN"]=array("name"=>"Brunei","d_code"=>"+673");
				$countries["BG"]=array("name"=>"Bulgaria","d_code"=>"+359");
				$countries["BF"]=array("name"=>"Burkina Faso","d_code"=>"+226");
				$countries["MM"]=array("name"=>"Burma Myanmar" ,"d_code"=>"+95");
				$countries["BI"]=array("name"=>"Burundi","d_code"=>"+257");
				$countries["KH"]=array("name"=>"Cambodia","d_code"=>"+855");
				$countries["CM"]=array("name"=>"Cameroon","d_code"=>"+237");
				$countries["CA"]=array("name"=>"Canada","d_code"=>"+1");
				$countries["CV"]=array("name"=>"Cape Verde","d_code"=>"+238");
				$countries["KY"]=array("name"=>"Cayman Islands","d_code"=>"+1");
				$countries["CF"]=array("name"=>"Central African Republic","d_code"=>"+236");
				$countries["TD"]=array("name"=>"Chad","d_code"=>"+235");
				$countries["CL"]=array("name"=>"Chile","d_code"=>"+56");
				$countries["CN"]=array("name"=>"China","d_code"=>"+86");
				$countries["CO"]=array("name"=>"Colombia","d_code"=>"+57");
				$countries["KM"]=array("name"=>"Comoros","d_code"=>"+269");
				$countries["CK"]=array("name"=>"Cook Islands","d_code"=>"+682");
				$countries["CR"]=array("name"=>"Costa Rica","d_code"=>"+506");
				$countries["CI"]=array("name"=>"Côte d'Ivoire" ,"d_code"=>"+225");
				$countries["HR"]=array("name"=>"Croatia","d_code"=>"+385");
				$countries["CU"]=array("name"=>"Cuba","d_code"=>"+53");
				$countries["CY"]=array("name"=>"Cyprus","d_code"=>"+357");
				$countries["CZ"]=array("name"=>"Czech Republic","d_code"=>"+420");
				$countries["CD"]=array("name"=>"Democratic Republic of Congo","d_code"=>"+243");
				$countries["DK"]=array("name"=>"Denmark","d_code"=>"+45");
				$countries["DJ"]=array("name"=>"Djibouti","d_code"=>"+253");
				$countries["DM"]=array("name"=>"Dominica","d_code"=>"+1");
				$countries["DO"]=array("name"=>"Dominican Republic","d_code"=>"+1");
				$countries["EC"]=array("name"=>"Ecuador","d_code"=>"+593");
				$countries["EG"]=array("name"=>"Egypt","d_code"=>"+20");
				$countries["SV"]=array("name"=>"El Salvador","d_code"=>"+503");
				$countries["GQ"]=array("name"=>"Equatorial Guinea","d_code"=>"+240");
				$countries["ER"]=array("name"=>"Eritrea","d_code"=>"+291");
				$countries["EE"]=array("name"=>"Estonia","d_code"=>"+372");
				$countries["ET"]=array("name"=>"Ethiopia","d_code"=>"+251");
				$countries["FK"]=array("name"=>"Falkland Islands","d_code"=>"+500");
				$countries["FO"]=array("name"=>"Faroe Islands","d_code"=>"+298");
				$countries["FM"]=array("name"=>"Federated States of Micronesia","d_code"=>"+691");
				$countries["FJ"]=array("name"=>"Fiji","d_code"=>"+679");
				$countries["FI"]=array("name"=>"Finland","d_code"=>"+358");
				$countries["FR"]=array("name"=>"France","d_code"=>"+33");
				$countries["GF"]=array("name"=>"French Guiana","d_code"=>"+594");
				$countries["PF"]=array("name"=>"French Polynesia","d_code"=>"+689");
				$countries["GA"]=array("name"=>"Gabon","d_code"=>"+241");
				$countries["GE"]=array("name"=>"Georgia","d_code"=>"+995");
				$countries["DE"]=array("name"=>"Germany","d_code"=>"+49");
				$countries["GH"]=array("name"=>"Ghana","d_code"=>"+233");
				$countries["GI"]=array("name"=>"Gibraltar","d_code"=>"+350");
				$countries["GR"]=array("name"=>"Greece","d_code"=>"+30");
				$countries["GL"]=array("name"=>"Greenland","d_code"=>"+299");
				$countries["GD"]=array("name"=>"Grenada","d_code"=>"+1");
				$countries["GP"]=array("name"=>"Guadeloupe","d_code"=>"+590");
				$countries["GU"]=array("name"=>"Guam","d_code"=>"+1");
				$countries["GT"]=array("name"=>"Guatemala","d_code"=>"+502");
				$countries["GN"]=array("name"=>"Guinea","d_code"=>"+224");
				$countries["GW"]=array("name"=>"Guinea-Bissau","d_code"=>"+245");
				$countries["GY"]=array("name"=>"Guyana","d_code"=>"+592");
				$countries["HT"]=array("name"=>"Haiti","d_code"=>"+509");
				$countries["HN"]=array("name"=>"Honduras","d_code"=>"+504");
				$countries["HK"]=array("name"=>"Hong Kong","d_code"=>"+852");
				$countries["HU"]=array("name"=>"Hungary","d_code"=>"+36");
				$countries["IS"]=array("name"=>"Iceland","d_code"=>"+354");
				$countries["IN"]=array("name"=>"India","d_code"=>"+91");
				$countries["ID"]=array("name"=>"Indonesia","d_code"=>"+62");
				$countries["IR"]=array("name"=>"Iran","d_code"=>"+98");
				$countries["IQ"]=array("name"=>"Iraq","d_code"=>"+964");
				$countries["IE"]=array("name"=>"Ireland","d_code"=>"+353");
				$countries["IL"]=array("name"=>"Israel","d_code"=>"+972");
				$countries["IT"]=array("name"=>"Italy","d_code"=>"+39");
				$countries["JM"]=array("name"=>"Jamaica","d_code"=>"+1");
				$countries["JP"]=array("name"=>"Japan","d_code"=>"+81");
				$countries["JO"]=array("name"=>"Jordan","d_code"=>"+962");
				$countries["KZ"]=array("name"=>"Kazakhstan","d_code"=>"+7");
				$countries["KE"]=array("name"=>"Kenya","d_code"=>"+254");
				$countries["KI"]=array("name"=>"Kiribati","d_code"=>"+686");
				$countries["XK"]=array("name"=>"Kosovo","d_code"=>"+381");
				$countries["KW"]=array("name"=>"Kuwait","d_code"=>"+965");
				$countries["KG"]=array("name"=>"Kyrgyzstan","d_code"=>"+996");
				$countries["LA"]=array("name"=>"Laos","d_code"=>"+856");
				$countries["LV"]=array("name"=>"Latvia","d_code"=>"+371");
				$countries["LB"]=array("name"=>"Lebanon","d_code"=>"+961");
				$countries["LS"]=array("name"=>"Lesotho","d_code"=>"+266");
				$countries["LR"]=array("name"=>"Liberia","d_code"=>"+231");
				$countries["LY"]=array("name"=>"Libya","d_code"=>"+218");
				$countries["LI"]=array("name"=>"Liechtenstein","d_code"=>"+423");
				$countries["LT"]=array("name"=>"Lithuania","d_code"=>"+370");
				$countries["LU"]=array("name"=>"Luxembourg","d_code"=>"+352");
				$countries["MO"]=array("name"=>"Macau","d_code"=>"+853");
				$countries["MK"]=array("name"=>"Macedonia","d_code"=>"+389");
				$countries["MG"]=array("name"=>"Madagascar","d_code"=>"+261");
				$countries["MW"]=array("name"=>"Malawi","d_code"=>"+265");
				$countries["MY"]=array("name"=>"Malaysia","d_code"=>"+60");
				$countries["MV"]=array("name"=>"Maldives","d_code"=>"+960");
				$countries["ML"]=array("name"=>"Mali","d_code"=>"+223");
				$countries["MT"]=array("name"=>"Malta","d_code"=>"+356");
				$countries["MH"]=array("name"=>"Marshall Islands","d_code"=>"+692");
				$countries["MQ"]=array("name"=>"Martinique","d_code"=>"+596");
				$countries["MR"]=array("name"=>"Mauritania","d_code"=>"+222");
				$countries["MU"]=array("name"=>"Mauritius","d_code"=>"+230");
				$countries["YT"]=array("name"=>"Mayotte","d_code"=>"+262");
				$countries["MX"]=array("name"=>"Mexico","d_code"=>"+52");
				$countries["MD"]=array("name"=>"Moldova","d_code"=>"+373");
				$countries["MC"]=array("name"=>"Monaco","d_code"=>"+377");
				$countries["MN"]=array("name"=>"Mongolia","d_code"=>"+976");
				$countries["ME"]=array("name"=>"Montenegro","d_code"=>"+382");
				$countries["MS"]=array("name"=>"Montserrat","d_code"=>"+1");
				$countries["MA"]=array("name"=>"Morocco","d_code"=>"+212");
				$countries["MZ"]=array("name"=>"Mozambique","d_code"=>"+258");
				$countries["NA"]=array("name"=>"Namibia","d_code"=>"+264");
				$countries["NR"]=array("name"=>"Nauru","d_code"=>"+674");
				$countries["NP"]=array("name"=>"Nepal","d_code"=>"+977");
				$countries["NL"]=array("name"=>"Netherlands","d_code"=>"+31");
				$countries["AN"]=array("name"=>"Netherlands Antilles","d_code"=>"+599");
				$countries["NC"]=array("name"=>"New Caledonia","d_code"=>"+687");
				$countries["NZ"]=array("name"=>"New Zealand","d_code"=>"+64");
				$countries["NI"]=array("name"=>"Nicaragua","d_code"=>"+505");
				$countries["NE"]=array("name"=>"Niger","d_code"=>"+227");
				$countries["NG"]=array("name"=>"Nigeria","d_code"=>"+234");
				$countries["NU"]=array("name"=>"Niue","d_code"=>"+683");
				$countries["NF"]=array("name"=>"Norfolk Island","d_code"=>"+672");
				$countries["KP"]=array("name"=>"North Korea","d_code"=>"+850");
				$countries["MP"]=array("name"=>"Northern Mariana Islands","d_code"=>"+1");
				$countries["NO"]=array("name"=>"Norway","d_code"=>"+47");
				$countries["OM"]=array("name"=>"Oman","d_code"=>"+968");
				$countries["PK"]=array("name"=>"Pakistan","d_code"=>"+92");
				$countries["PW"]=array("name"=>"Palau","d_code"=>"+680");
				$countries["PS"]=array("name"=>"Palestine","d_code"=>"+970");
				$countries["PA"]=array("name"=>"Panama","d_code"=>"+507");
				$countries["PG"]=array("name"=>"Papua New Guinea","d_code"=>"+675");
				$countries["PY"]=array("name"=>"Paraguay","d_code"=>"+595");
				$countries["PE"]=array("name"=>"Peru","d_code"=>"+51");
				$countries["PH"]=array("name"=>"Philippines","d_code"=>"+63");
				$countries["PL"]=array("name"=>"Poland","d_code"=>"+48");
				$countries["PT"]=array("name"=>"Portugal","d_code"=>"+351");
				$countries["PR"]=array("name"=>"Puerto Rico","d_code"=>"+1");
				$countries["QA"]=array("name"=>"Qatar","d_code"=>"+974");
				$countries["CG"]=array("name"=>"Republic of the Congo","d_code"=>"+242");
				$countries["RE"]=array("name"=>"Réunion" ,"d_code"=>"+262");
				$countries["RO"]=array("name"=>"Romania","d_code"=>"+40");
				$countries["RU"]=array("name"=>"Russia","d_code"=>"+7");
				$countries["RW"]=array("name"=>"Rwanda","d_code"=>"+250");
				$countries["BL"]=array("name"=>"Saint Barthélemy" ,"d_code"=>"+590");
				$countries["SH"]=array("name"=>"Saint Helena","d_code"=>"+290");
				$countries["KN"]=array("name"=>"Saint Kitts and Nevis","d_code"=>"+1");
				$countries["MF"]=array("name"=>"Saint Martin","d_code"=>"+590");
				$countries["PM"]=array("name"=>"Saint Pierre and Miquelon","d_code"=>"+508");
				$countries["VC"]=array("name"=>"Saint Vincent and the Grenadines","d_code"=>"+1");
				$countries["WS"]=array("name"=>"Samoa","d_code"=>"+685");
				$countries["SM"]=array("name"=>"San Marino","d_code"=>"+378");
				$countries["ST"]=array("name"=>"São Tomé and Príncipe" ,"d_code"=>"+239");
				$countries["SA"]=array("name"=>"Saudi Arabia","d_code"=>"+966");
				$countries["SN"]=array("name"=>"Senegal","d_code"=>"+221");
				$countries["RS"]=array("name"=>"Serbia","d_code"=>"+381");
				$countries["SC"]=array("name"=>"Seychelles","d_code"=>"+248");
				$countries["SL"]=array("name"=>"Sierra Leone","d_code"=>"+232");
				$countries["SG"]=array("name"=>"Singapore","d_code"=>"+65");
				$countries["SK"]=array("name"=>"Slovakia","d_code"=>"+421");
				$countries["SI"]=array("name"=>"Slovenia","d_code"=>"+386");
				$countries["SB"]=array("name"=>"Solomon Islands","d_code"=>"+677");
				$countries["SO"]=array("name"=>"Somalia","d_code"=>"+252");
				$countries["ZA"]=array("name"=>"South Africa","d_code"=>"+27");
				$countries["KR"]=array("name"=>"South Korea","d_code"=>"+82");
				$countries["ES"]=array("name"=>"Spain","d_code"=>"+34");
				$countries["LK"]=array("name"=>"Sri Lanka","d_code"=>"+94");
				$countries["LC"]=array("name"=>"St. Lucia","d_code"=>"+1");
				$countries["SD"]=array("name"=>"Sudan","d_code"=>"+249");
				$countries["SR"]=array("name"=>"Suriname","d_code"=>"+597");
				$countries["SZ"]=array("name"=>"Swaziland","d_code"=>"+268");
				$countries["SE"]=array("name"=>"Sweden","d_code"=>"+46");
				$countries["CH"]=array("name"=>"Switzerland","d_code"=>"+41");
				$countries["SY"]=array("name"=>"Syria","d_code"=>"+963");
				$countries["TW"]=array("name"=>"Taiwan","d_code"=>"+886");
				$countries["TJ"]=array("name"=>"Tajikistan","d_code"=>"+992");
				$countries["TZ"]=array("name"=>"Tanzania","d_code"=>"+255");
				$countries["TH"]=array("name"=>"Thailand","d_code"=>"+66");
				$countries["BS"]=array("name"=>"The Bahamas","d_code"=>"+1");
				$countries["GM"]=array("name"=>"The Gambia","d_code"=>"+220");
				$countries["TL"]=array("name"=>"Timor-Leste","d_code"=>"+670");
				$countries["TG"]=array("name"=>"Togo","d_code"=>"+228");
				$countries["TK"]=array("name"=>"Tokelau","d_code"=>"+690");
				$countries["TO"]=array("name"=>"Tonga","d_code"=>"+676");
				$countries["TT"]=array("name"=>"Trinidad and Tobago","d_code"=>"+1");
				$countries["TN"]=array("name"=>"Tunisia","d_code"=>"+216");
				$countries["TR"]=array("name"=>"Turkey","d_code"=>"+90");
				$countries["TM"]=array("name"=>"Turkmenistan","d_code"=>"+993");
				$countries["TC"]=array("name"=>"Turks and Caicos Islands","d_code"=>"+1");
				$countries["TV"]=array("name"=>"Tuvalu","d_code"=>"+688");
				$countries["UG"]=array("name"=>"Uganda","d_code"=>"+256");
				$countries["UA"]=array("name"=>"Ukraine","d_code"=>"+380");
				$countries["AE"]=array("name"=>"United Arab Emirates","d_code"=>"+971");
				$countries["GB"]=array("name"=>"United Kingdom","d_code"=>"+44");
				$countries["US"]=array("name"=>"United States","d_code"=>"+1");
				$countries["UY"]=array("name"=>"Uruguay","d_code"=>"+598");
				$countries["VI"]=array("name"=>"US Virgin Islands","d_code"=>"+1");
				$countries["UZ"]=array("name"=>"Uzbekistan","d_code"=>"+998");
				$countries["VU"]=array("name"=>"Vanuatu","d_code"=>"+678");
				$countries["VA"]=array("name"=>"Vatican City","d_code"=>"+39");
				$countries["VE"]=array("name"=>"Venezuela","d_code"=>"+58");
				$countries["VN"]=array("name"=>"Vietnam","d_code"=>"+84");
				$countries["WF"]=array("name"=>"Wallis and Futuna","d_code"=>"+681");
				$countries["YE"]=array("name"=>"Yemen","d_code"=>"+967");
				$countries["ZM"]=array("name"=>"Zambia","d_code"=>"+260");
				$countries["ZW"]=array("name"=>"Zimbabwe","d_code"=>"+263");
		
				if (!preg_match('([+][0-9]{6,})',$phonenumber))    return $countries[$countrycode]["d_code"] . $phonenumber;
				else return $phonenumber;
		}
		
	
}
