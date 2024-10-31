<?php
   /*
   Plugin Name: PrismPay for WooCommerce
   Description: Extends WooCommerce to Process Payments with MCS Gateway
   Version: 1.0
   Plugin URI: http://democarts.info/
   Author: PrismPay
   Author URI: https://prismpay.com/
   License: Under GPL
   */
   
   add_action('plugins_loaded', 'woocommerce_prismpay_init', 0);
   
   function woocommerce_prismpay_init() {
   
      if ( !class_exists( 'WC_Payment_Gateway' ) ) 
         return;
   
      /**
      * Localisation
      */
      load_plugin_textdomain('error', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');
   
      /**
      * PrismPay Payment Gateway class
      */
      class WC_Gateway_PrismPay extends WC_Payment_Gateway 
      {
         protected $msg = array();
   
         public function __construct(){
   
   		$this->id					= 		'prismpay';
   		$this->method_title			= 		__('PrismPay', 'error');
   		$this->icon					= 		WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/logo.gif';
   		$this->has_fields       	= 		true;
   
   		$this->init_form_fields();
   		$this->init_settings();
   
   		$this->title				= 		$this->settings['title'];
   		$this->description			= 		$this->settings['description'];
   		$this->account_id			= 		$this->settings['account_id'];
   		$this->mode					= 		$this->settings['working_mode'];
   		$this->success_message		= 		$this->settings['success_message'];
   		$this->failed_message		= 		$this->settings['failed_message'];
   		$this->liveurl				= 		'https://prod.prismpay.com/api/api.asmx';
   		$this->msg['message']		= 		"";
   		$this->msg['class']			= 		"";
   
            if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
             } else {
                add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
            }
   
            add_action('woocommerce_receipt_authorizeaim', array(&$this, 'receipt_page'));
            add_action('woocommerce_thankyou_authorizeaim',array(&$this, 'thankyou_page'));
         }
   
         function init_form_fields()
         {
   
            $this->form_fields = array(
               'enabled'      => array(
                     'title'        => __('Enable/Disable', 'error'),
                     'type'         => 'checkbox',
                     'label'        => __('Enable PrismPay Payment Module.', 'error'),
                     'default'      => 'no'),
               'title'        => array(
                     'title'        => __('Title:', 'error'),
                     'type'         => 'text',
                     'description'  => __('This controls the title which the user sees during checkout.', 'error'),
                     'default'      => __('PrismPay', 'error')),
               'description'  => array(
                     'title'        => __('Description:', 'error'),
                     'type'         => 'textarea',
                     'description'  => __('This controls the description which the user sees during checkout.', 'error'),
                     'default'      => __('Pay securely by Credit / Debit Card or e-checks through PrismPay.', 'error')),
               'account_id'     => array(
                     'title'        => __('Account ID', 'error'),
                     'type'         => 'text',
                     'description'  => __('PrismPay Account ID')),		
               'success_message' => array(
                     'title'        => __('Transaction Success Message', 'error'),
                     'type'         => 'textarea',
                     'description'=>  __('Message to be displayed on successful transaction.', 'error'),
                     'default'      => __('Your payment has been procssed successfully.', 'error')),
               'failed_message'  => array(
                     'title'        => __('Transaction Failed Message', 'error'),
                     'type'         => 'textarea',
                     'description'  =>  __('Message to be displayed on failed transaction.', 'error'),
                     'default'      => __('Your transaction has been declined.', 'error')),
               'working_mode'    => array(
                     'title'        => __('API Mode'),
                     'type'         => 'select',
               	  'options'      => array('false'=>'Live Mode', 'true'=>'Test/Sandbox Mode'),
                     'description'  => "Live/Test Mode" )
            );
         }
   
         /**
          * Admin Panel Options
          * 
         **/
         public function admin_options()
         {
            echo '<h3>'.__('PrismPay Payment Gateway', 'error').'</h3>';
            echo '<p>'.__('PrismPay is the most popular payment gateway for online payment processing').'</p>';
            echo '<table class="form-table">';
            $this->generate_settings_html();
            echo '</table>';
   
         }
   
         /**
         *  Fields for Authorize.net AIM
         **/
         function payment_fields()
         {
            if ( $this->description ) 
               echo wpautop(wptexturize($this->description));
   
   			$years = "<option value=''>YY</option>";
   			for($a = date('Y'); $a <= date('Y')+10; $a++)
   			{
   				$years .= "<option value='". $a ."'>". $a ."</option>";
   			}
   
   			$UserID 		= 	get_current_user_id();
   			$profiles 		= 	get_option('_wp_prismpay_user_payment_profile_' . $UserID);
   			?>
<script type="text/javascript">
   function PP_Change_Payment_Method(val) {
       if (val == 1) {
           document.getElementById('CreditCardMethod_PP').style.display = 'block';
           document.getElementById('E_Check_Method_PP').style.display = 'none';
       } else {
           document.getElementById('CreditCardMethod_PP').style.display = 'none';
           document.getElementById('E_Check_Method_PP').style.display = 'block';
       }
   }
   
   function PP_Change_Profile_Payment(check) {
       if (check == "" || check.length <= 0) {
           document.getElementById('CreditCardMethod2_PP').style.display = 'block';
       } else {
           document.getElementById('CreditCardMethod2_PP').style.display = 'none';
       }
   }
</script>
<table border="0">
   <tr>
      <td width="120">Payment Method: </td>
      <td width="220">
         <select id="pp_payment_method" name="pp_payment_method" onchange="javascript:PP_Change_Payment_Method(this.value);" style="-webkit-appearance: none; -moz-appearance: none; text-indent: 1px; text-overflow:''">
            <option value="1">Credit Card</option>
         </select>
      </td>
   </tr>
</table>
<div id="CreditCardMethod_PP">
   <?php 
      echo $profiles = unserialize($profiles);
      if(is_array($profiles) && sizeof($profiles) > 0){ 
      ?>
   <table border="0">
      <tr>
         <td width="150">Saved Profiles: </td>
         <td>
            <select id="pp_payment_profiles" name="pp_payment_profiles" onchange="javascript:PP_Change_Profile_Payment(this.value);">
               <option value="">Select Profile</option>
               <?php foreach($profiles as $profile){ ?>
               <option value="<?php echo $profile['userprofileid']; ?>||<?php echo $profile['last4digits']; ?>">
                  <?php echo $profile['last4digits']; ?> -
                  <?php echo $profile['paytype']; ?>
               </option>
               <?php } ?>
            </select>
         </td>
      </tr>
   </table>
   <?php } ?>
   <div id="CreditCardMethod2_PP">
      <table border="0">
         <tr>
            <td width="120">Credit Card: </td>
            <td width="220">
               <input type="text" name="pp_credircard" maxlength="16" />
            </td>
         </tr>
         <tr>
            <td>Expiry: </td>
            <td>
               <select name="pp_mm">
                  <option value="">MM</option>
                  <option value="01">Jan</option>
                  <option value="02">Feb</option>
                  <option value="03">Mar</option>
                  <option value="04">Apr</option>
                  <option value="05">May</option>
                  <option value="06">Jun</option>
                  <option value="07">Jul</option>
                  <option value="08">Aug</option>
                  <option value="09">Sep</option>
                  <option value="10">Oct</option>
                  <option value="11">Nov</option>
                  <option value="12">Dec</option>
               </select>
               &nbsp;&nbsp;
               <select name="pp_yy">
               <?php echo $years; ?>
               </select>
               <br />
            </td>
         </tr>
         <tr>
            <td>CVV: </td>
            <td>
               <input type="text" name="pp_cvv" maxlength="4" style="width:80px;" />
            </td>
         </tr>
         <?php if(isset($UserID) && $UserID > 0){ ?>
         <?php } ?>
      </table>
   </div>
</div>
<div id="E_Check_Method_PP" style="display:none">
   <table border="0">
      <tr>
         <td width="150">First/Last Name:</td>
         <td>
            <input type='text' name='pp_f_name' id='f_name' value='' />/ &nbsp;
            <input type='text' name='pp_l_name' id='l_name' value='' />
         </td>
      </tr>
      <tr>
         <td>Account Type:</td>
         <td>
            <select name='pp_check_acc_type' id='pp_check_acc_type'>
               <option value='1'>Checking</option>
               <option value='2'>Savings</option>
            </select>
         </td>
      </tr>
      <tr>
         <td>Account Number:</td>
         <td>
            <input type='text' name='pp_check_acc_number' id='pp_check_acc_number' value='' size='30' />
         </td>
      </tr>
      <tr>
         <td>Check Number:</td>
         <td>
            <input type='text' name='pp_check_number' id='pp_check_number' value='' size='30' />
         </td>
      </tr>
      <tr>
         <td>Routing Number:</td>
         <td>
            <input type='text' name='pp_check_routing_number' id='pp_check_routing_number' value='' size='30' />
         </td>
      </tr>
      <tr>
         <td valign="top">Example:</td>
         <td><img src='<?php echo WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) ?>/images/check.gif' border='0' /></td>
      </tr>
   </table>
</div>
<?php
   }
   
   /*
   * Basic Card validation
   */
   
   public function validate_fields()
   {
   global $woocommerce;
   
   $pp_payment_method = sanitize_text_field($_POST['pp_payment_method']);
   
   $pp_payment_profiles = sanitize_text_field($_POST['pp_payment_profiles']);
   
   $pp_credircard = sanitize_text_field($_POST['pp_credircard']);
   
   $pp_mm = sanitize_text_field($_POST['pp_mm']);
   $pp_yy = sanitize_text_field($_POST['pp_yy']);
   $pp_cvv = sanitize_text_field($_POST['pp_cvv']);
   
   $pp_f_name = sanitize_text_field($_POST['pp_f_name']);
   $pp_l_name = sanitize_text_field($_POST['pp_l_name']);
   $pp_check_acc_number = sanitize_text_field($_POST['pp_check_acc_number']);
   $pp_check_number = sanitize_text_field($_POST['pp_check_number']);
   $pp_check_routing_number = sanitize_text_field($_POST['pp_check_routing_number']);
   
   if(isset($pp_payment_method) && ($pp_payment_method == 1))
   {
   if( $pp_payment_profiles == "" )
   {
   
   if (!$this->isCreditCardNumber($pp_credircard)) 
   {
   wc_add_notice(__('(Credit Card Number) is not valid.', 'error')); 
   
   }
   if (!$this->isCorrectExpireMonth($pp_mm))
   {
   wc_add_notice(__('(Card Expiry Month) is not valid.', 'error')); 
   }
   if (!$this->isCorrectExpireYear($pp_yy))    
   {
   wc_add_notice(__('(Card Expiry Year) is not valid.', 'error')); 
   }
   if (!$this->isCCVNumber($pp_cvv))
   {
   wc_add_notice(__('(Card Verification Number) is not valid.', 'error')); 
   }
   
   }
   
   } else
   {
   if(!isset($pp_f_name) || empty($pp_f_name))
   {
   wc_add_notice(__('(First Name) required.', 'error')); 
   }
   if(!isset($pp_l_name) || empty($pp_l_name))
   {
   wc_add_notice(__('(Last Name) required.', 'error')); 
   }
   if(!isset($pp_check_acc_number) || empty($pp_check_acc_number))
   {
   wc_add_notice(__('(Account Number) required.', 'error')); 
   }
   if(!isset($pp_check_number) || empty($pp_check_number))
   {
   wc_add_notice(__('(Check Number) required.', 'error')); 
   }
   if(!isset($pp_check_routing_number) || empty($pp_check_routing_number))
   {
   wc_add_notice(__('(Routing Number) required.', 'error')); 
   }
   }
   }
   
   /*
   * Check card 
   */
   private function isCreditCardNumber($toCheck) 
   {
      if (!is_numeric($toCheck))
         return false;
   
     $number = preg_replace('/[^0-9]+/', '', $toCheck);
     $strlen = strlen($number);
     $sum    = 0;
   
     if ($strlen < 13)
         return false; 
   
     for ($i=0; $i < $strlen; $i++)
     {
         $digit = substr($number, $strlen - $i - 1, 1);
         if($i % 2 == 1)
         {
             $sub_total = $digit * 2;
             if($sub_total > 9)
             {
                 $sub_total = 1 + ($sub_total - 10);
             }
         } 
         else 
         {
             $sub_total = $digit;
         }
         $sum += $sub_total;
     }
   
     if ($sum > 0 AND $sum % 10 == 0)
         return true; 
   
     return false;
   }
   
   private function isCCVNumber($toCheck) 
   {
      $length = strlen($toCheck);
      return is_numeric($toCheck) AND $length > 2 AND $length < 5;
   }
   
   /*
   * Check expiry date
   */
   private function isCorrectExpireMonth($mm) 
   {
   
      if ( is_numeric($mm) && !empty($mm) ){
         return true;
      }
      return false;
   }
   private function isCorrectExpireYear($yy) 
   {
   
     if ( is_numeric($yy) && !empty($yy) ){
         return true;
      }
      return false;
   }
   
   public function thankyou_page($order_id) 
   {
   
   }
   
   /**
   * Receipt Page
   **/
   function receipt_page($order)
   {
      echo '<p>'.__('Thank you for your order.', 'error').'</p>';
   
   }
   
   /**
    * Process the payment and return the result
   **/
   function process_payment($order_id)
   {
   
   global $woocommerce;
   $order = new WC_Order($order_id);
     $process_url = $this->liveurl;
   $pp_payment_method = sanitize_text_field($_POST['pp_payment_method']);
   if(isset($pp_payment_method) && $pp_payment_method == 1)
   {
   $pp_credircard  = sanitize_text_field($_POST['pp_credircard']);
   
   if (!$this->isCreditCardNumber($pp_credircard))
   return;
   
   $xml = $this->generate_prismpay_params_cc($order);
   $args = $xml;
   $response = wp_remote_post( $process_url, $args); // Use By Jafar as WordPress Recommend this function instead of using curl. Dated: 21 Feb, 2020
   
   $xml = new SimpleXMLElement($response); 
   $xml->registerXPathNamespace("soap", "http://www.w3.org/2003/05/soap-envelope");
   $body = $xml->xpath("//soap:Body");
   try
   {			
   
   $response = $body[0]->CreditSale_SoapResponse->CreditSale_SoapResult;
   $MCSTransactionID = $response->MCSTransactionID;
   $ProcessorTransactionID = $response->ProcessorTransactionID;				
   $responseCode = $response->Result->ResultCode;
   $ResultDetail = $response->Result->ResultDetail;	
   $status 			= 	$response->status;
   $responseRes 		= 	$response;
   
   if(($responseCode==0))
   {
     $order->payment_complete();
     $woocommerce->cart->empty_cart();
     $order->add_order_note($this->success_message. ". " . $ResultDetail . 'Transaction ID: '. $order->orderid );
     unset($_SESSION['order_awaiting_payment']);
     return array(
   			 'result'   => 'success',
   		   'redirect' => $this->get_return_url( $order )
   	   );
   }
   else{
   
   $str = $ResultDetail->asXML();
   $order->add_order_note($this->failed_message . " " . $str );
   wc_add_notice( $str , 'error');
   }
   
   }
   
   catch(ServiceError $e)
             {
                 $errorMsg ="Your transaction has been declined. Gateway Response: ".$e;
   
   }
   }
   
     else {
   
         $order->add_order_note($this->failed_message);
         $order->update_status('failed');
          wc_add_notice(  "declined", 'error' );
     }
   
   }
   
   /* PrismPay Parameters for Profile Sale .... */
   public function generate_prismpay_params_pf($order)
   {
   if($this->mode == 'true'){
   $account_id = "807014";
   }
   else{
   $account_id = $this->account_id;
   }
   $var_pp_payment_profiles = sanitize_text_field($_POST['pp_payment_profiles']); 
   $UserProfiles 	= 	$var_pp_payment_profiles;
   $UserProfiles 	= 	explode("||",$UserProfiles);
   $ProfileID 		= 	$UserProfiles[0];
   $Last4	 		= 	$UserProfiles[1];		
   
   $prismpay_params = array(
   'acctid' 			=> 		$account_id,
   'subid' 			=> 		$this->sub_account_id,
   'amount' 			=> 		$order->order_total,
   'userprofileid' 	=> 		$ProfileID,
   'last4digits' 		=> 		$Last4,
   'merchantpin' 		=>  	$this->merchant_pin,
   'billaddress' => array(
   				'addr1' => $order->billing_address_1,
   				'addr2' => $order->billing_address_2,
   				'addr3' => "",
   				'city' => $order->billing_city,
   				'state' => $order->billing_state,
   				'zip' => $order->billing_postcode,
   				'country' => $order->billing_country
   			),
   'shipaddress' => array(
   				'addr1' => $order->shipping_address_1,
   				'addr2' => $order->shipping_address_2,
   				'addr3' => "",
   				'city' => $order->shipping_city,
   				'state' => $order->shipping_state,
   				'zip' => $order->shipping_postcode,
   				'country' => $order->shipping_country
   			),
   "customizedfields" => array(
   				'ip' => $_SERVER['REMOTE_ADDR']
   			),
   "cvv2" => "0",
   "authonly" => "0",
   "encryptedreadertype" => "0",
   "cardpresent" => "0",
   "cardreaderpresent" => "0",
   "accttype" => "0",
   "profileactiontype" => "2",
   "manualrecurring" => "0",
   "avs_override" => "0",
   "cvv2_override" => "0",
   "loadbalance_override" => "0",
   "duplicate_override" => "0",
   "accountlookupflag" => "0",
   "accountlookupflag" => "0",
   "conveniencefeeflag" => "0"
   );
   
   return $prismpay_params;
   }
   
   /* PrismPay Parameters for ACH Payments .... */
   
   public function generate_prismpay_params_cc($order)
   {
   if($this->mode == 'true'){
   
   $account_id = "807014";
   }
   else{
   $account_id = $this->account_id;
   }
   
   $var_pp_credircard  = sanitize_text_field($_POST['pp_credircard']);
   $var_pp_mm			= sanitize_text_field($_POST['pp_mm']);
   $var_pp_yy			= sanitize_text_field($_POST['pp_yy']);
   $var_pp_cvv			= sanitize_text_field($_POST['pp_cvv']);
   
   $xml_string = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
   <soap:Header>
      <AuthHeader xmlns="https://MyCardStorage.com/">
         <UserName>MFUser</UserName>
         <Password>slYC8T#fhnK0tLp5</Password>
      </AuthHeader>
   </soap:Header>
   <soap:Body>
      <CreditSale_Soap xmlns="https://MyCardStorage.com/">
         <creditCardSale>
            <ServiceSecurity>
               <ServiceUserName>MF</ServiceUserName>
               <ServicePassword>kZJ33HgBhH$NFFdvE</ServicePassword>
               <MCSAccountID>'.$account_id.'</MCSAccountID>
            </ServiceSecurity>
            <TokenData>
               <Token> $billAddr1 $billAddr2</Token>
               <Last4>5454</Last4>
               <CardNumber>'.$var_pp_credircard.'</CardNumber>
               <ExpirationMonth>'.$var_pp_mm.'</ExpirationMonth>
               <ExpirationYear>'.$var_pp_yy.'</ExpirationYear>
               <NickName>'.$order->billing_first_name . " " . $order->billing_last_name.'</NickName>
               <FirstName>'.$order->billing_first_name . " " . $order->billing_last_name.'</FirstName>
               <StreetAddress>'.$order->billing_address_1.'</StreetAddress>
               <ZipCode>'.$order->shipping_postcode.'</ZipCode>
               <CVV>'.$var_pp_cvv.'</CVV>
            </TokenData>
            <TransactionData>
               <Amount>'.$order->order_total.'</Amount>
               <MCSTransactionID>0</MCSTransactionID>
               <Custom5>iscustomerportal</Custom5>
               <GatewayID>1</GatewayID>
            </TransactionData>
         </creditCardSale>
      </CreditSale_Soap>
   </soap:Body>
</soap:Envelope>
'; return $xml_string; } } /** * Add this Gateway to WooCommerce **/ function woocommerce_prismpay_gateway($methods) { $methods[] = 'WC_Gateway_PrismPay'; return $methods; } add_filter('woocommerce_payment_gateways', 'woocommerce_prismpay_gateway' ); }