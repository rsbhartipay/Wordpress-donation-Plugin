<?php
/**
 * Plugin Name: bhartipay Payment Donation
 * Plugin URI: https://github.com/bhartipay/
 * Description: This plugin allow you to accept donation payments using bhartipay. This plugin will add a simple form that user will fill, when he clicks on submit he will redirected to bhartipay website to complete his transaction and on completion his payment, bhartipay will send that user back to your website along with transactions details. This plugin uses server-to-server verification to add additional security layer for validating transactions. Admin can also see all transaction details with payment status by going to "bhartipay Payment Details" from menu in admin.
 * Version: 1.0
 * Author: bhartipay
 * Author URI: http://bhartipay.com/
 * Text Domain: bhartipay Payments
 */

//ini_set('display_errors','On');
register_activation_hook(__FILE__, 'bhartipay_activation');
register_deactivation_hook(__FILE__, 'bhartipay_deactivation');

// do not conflict with WooCommerce bhartipay Plugin Callback
if(!isset($_GET["wc-api"])){
	add_action('init', 'bhartipay_donation_response');
}

add_shortcode( 'bhartipaydonation', 'bhartipay_donation_handler' );
// add_action('admin_post_nopriv_bhartipay_donation_request','bhartipay_donation_handler');
// add_action('admin_post_bhartipay_donation_request','bhartipay_donation_handler');



add_action( 'wp_footer', function() {
   if ( !empty($_POST['RESPONSE_CODE'] )) {
$current_url=current_location();
      // fire the custom action
//$url = get_home_url(); 
if ($_POST['STATUS']=="Captured") {
	echo "<script>swal({
     title: 'Wow!',
     text: 'We Have received your payment.',
     icon: 'success',
     type: 'success'
 }).then(function() {
     window.location = '$url';
 });</script>";
}
    else{
    	echo "<script>swal({
     title: 'Wow!',
     text: 'We have not received your payment.',
     icon: 'error',
     type: 'failure'
 }).then(function() {
     window.location = '$current_url';
 });</script>";
    }	
     
   }
} );


if(isset($_GET['donation_msg']) && $_GET['donation_msg'] != ""){
	add_action('the_content', 'bhartipayDonationShowMessage');
}

function bhartipayDonationShowMessage($content){
	return '<div class="box">'.htmlentities(urldecode($_GET['donation_msg'])).'</div>'.$content;
}
	
// css for admin
add_action('admin_head', 'my_custom_fonts');

function my_custom_fonts() {
  echo '<style>
   .toplevel_page_bhartipay_options_page img{
      width:19px;
    } 
  </style>';
}
function current_location()
{
    if (isset($_SERVER['HTTPS']) &&
        ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
        isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
        $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
        $protocol = 'https://';
    } else {
        $protocol = 'http://';
    }
    return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}


function wpdocs_theme_name_scripts() {
    wp_enqueue_script( 'sweetalert', 'https://unpkg.com/sweetalert/dist/sweetalert.min.js', array(), '1.0.0', false );
}
add_action( 'wp_enqueue_scripts', 'wpdocs_theme_name_scripts' );
//admin css


function bhartipay_activation() {
	global $wpdb, $wp_rewrite;
	$settings = bhartipay_settings_list();
	foreach ($settings as $setting) {
		add_option($setting['name'], $setting['value']);
	}
	add_option( 'bhartipay_donation_details_url', '', '', 'yes' );
	$post_date = date( "Y-m-d H:i:s" );
	$post_date_gmt = gmdate( "Y-m-d H:i:s" );

	$ebs_pages = array(
		'bhartipay-page' => array(
			'name' => 'bhartipay Transaction Details page',
			'title' => 'bhartipay Transaction Details page',
			'tag' => '[bhartipay_donation_details]',
			'option' => 'bhartipay_donation_details_url'
		),
	);
	
	$newpages = false;
	
	$bhartipay_page_id = $wpdb->get_var("SELECT id FROM `" . $wpdb->posts . "` WHERE `post_content` LIKE '%" . $bhartipay_pages['bhartipay-page']['tag'] . "%'	AND `post_type` != 'revision'");
	if(empty($bhartipay_page_id)){
		$bhartipay_page_id = wp_insert_post( array(
			'post_title'	=>	$bhartipay_pages['bhartipay-page']['title'],
			'post_type'		=>	'page',
			'post_name'		=>	$bhartipay_pages['bhartipay-page']['name'],
			'comment_status'=> 'closed',
			'ping_status'	=>	'closed',
			'post_content' =>	$bhartipay_pages['bhartipay-page']['tag'],
			'post_status'	=>	'publish',
			'post_author'	=>	1,
			'menu_order'	=>	0
		));
		$newpages = true;
	}

	update_option( $bhartipay_pages['bhartipay-page']['option'], _get_page_link($bhartipay_page_id) );
	unset($bhartipay_pages['bhartipay-page']);
	
	$table_name = $wpdb->prefix . "bhartipay_donation";
	$sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
				`id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
				`name` varchar(255),
				`email` varchar(255),
				`phone` varchar(255),
				`address` varchar(255),
				`city` varchar(255),
				`country` varchar(255),
				`state` varchar(255),
				`zip` varchar(255),
				`amount` varchar(255),
				`payment_status` varchar(255),
				`date` datetime
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta($sql);

	if($newpages){
		wp_cache_delete( 'all_page_ids', 'pages' );
		$wp_rewrite->flush_rules();
	}
}

function bhartipay_deactivation() {
	$settings = bhartipay_settings_list();
	foreach ($settings as $setting) {
		delete_option($setting['name']);
	}
}

function bhartipay_settings_list(){
	$settings = array(
		array(
			'display' => 'Merchant ID',
			'name'    => 'bhartipay_merchant_id',
			'value'   => '',
			'type'    => 'textbox',
			'hint'    => 'Merchant Id Provided by bhartipay'
		),
		array(
			'display' => 'Merchant Key',
			'name'    => 'bhartipay_merchant_key',
			'value'   => '',
			'type'    => 'textbox',
			'hint'    => 'Merchant Secret Key Provided by bhartipay'
		),
		array(
			'display' => 'Website',
			'name'    => 'bhartipay_website',
			'value'   => '',
			'type'    => 'textbox',
			'hint'    => 'Website Name Provided by bhartipay'
		),
		array(
			'display' => 'Industry Type',
			'name'    => 'bhartipay_industry_type_id',
			'value'   => '',
			'type'    => 'textbox',
			'hint'    => 'Industry Type Provided by bhartipay'
		),
		array(
			'display' => 'Channel ID',
			'name'    => 'bhartipay_channel_id',
			'value'   => 'WEB',
			'type'    => 'textbox',
			'hint'    => 'Channel ID Provided by bhartipay e.g. WEB/WAP'
		),
		array(
			'display' => 'Transaction URL',
			'name'    => 'transaction_url',
			'value'   => '',
			'type'    => 'textbox',
			'hint'    => 'Transaction URL Provided by bhartipay'
		),
		array(
			'display' => 'Transaction Status URL',
			'name'    => 'transaction_status_url',
			'value'   => '',
			'type'    => 'textbox',
			'hint'    => 'Transaction Status URL Provided by bhartipay'
		),
		array(
			'display' => 'Default Amount',
			'name'    => 'bhartipay_amount',
			'value'   => '100',
			'type'    => 'textbox',
			'hint'    => 'the default donation amount, WITHOUT currency signs -- ie. 100'
		),
		array(
			'display' => 'Default Button/Link Text',
			'name'    => 'bhartipay_content',
			'value'   => 'bhartipay',
			'type'    => 'textbox',
			'hint'    => 'the default text to be used for buttons or links if none is provided'
		)				
	);
	return $settings;
}


if (is_admin()) {
	add_action( 'admin_menu', 'bhartipay_admin_menu' );
	add_action( 'admin_init', 'bhartipay_register_settings' );
}


function bhartipay_admin_menu() {
	add_menu_page('bhartipay Donation', 'bhartipay Donation', 'manage_options', 'bhartipay_options_page', 'bhartipay_options_page', plugin_dir_url(__FILE__).'assets/logo.ico');
	//, plugin_dir_url(__FILE__).'assets/logo.ico'

	add_submenu_page('bhartipay_options_page', 'bhartipay Donation Settings', 'Settings', 'manage_options', 'bhartipay_options_page');

	add_submenu_page('bhartipay_options_page', 'bhartipay Donation Payment Details', 'Payment Details', 'manage_options', 'wp_bhartipay_donation', 'wp_bhartipay_donation_listings_page');
	
	require_once(dirname(__FILE__) . '/bhartipay-donation-listings.php');
}


function bhartipay_options_page() {
	echo	'<div class="wrap">
				<h1>bhartipay Configuarations</h1>
				<form method="post" action="options.php">';
					wp_nonce_field('update-options');
					echo '<table class="form-table">';
						$settings = bhartipay_settings_list();
						foreach($settings as $setting){
						echo '<tr valign="top"><th scope="row">'.$setting['display'].'</th><td>';

							if ($setting['type']=='radio') {
								echo $setting['yes'].' <input type="'.$setting['type'].'" name="'.$setting['name'].'" value="1" '.(get_option($setting['name']) == 1 ? 'checked="checked"' : "").' />';
								echo $setting['no'].' <input type="'.$setting['type'].'" name="'.$setting['name'].'" value="0" '.(get_option($setting['name']) == 0 ? 'checked="checked"' : "").' />';
		
							} elseif ($setting['type']=='select') {
								echo '<select name="'.$setting['name'].'">';
								foreach ($setting['values'] as $value=>$name) {
									echo '<option value="'.$value.'" ' .(get_option($setting['name'])==$value? '  selected="selected"' : ''). '>'.$name.'</option>';
								}
								echo '</select>';

							} else {
								echo '<input type="'.$setting['type'].'" name="'.$setting['name'].'" value="'.get_option($setting['name']).'" />';
							}

							echo '<p class="description" id="tagline-description">'.$setting['hint'].'</p>';
							echo '</td></tr>';
						}

						echo '<tr>
									<td colspan="2" align="center">
										<input type="submit" class="button-primary" value="Save Changes" />
										<input type="hidden" name="action" value="update" />';
										echo '<input type="hidden" name="page_options" value="';
										foreach ($settings as $setting) {
											echo $setting['name'].',';
										}
										echo '" />
									</td>
								</tr>

								<tr>
								</tr>
							</table>
						</form>';

			$last_updated = "";
			$path = plugin_dir_path( __FILE__ ) . "/bhartipay_version.txt";
			if(file_exists($path)){
				$handle = fopen($path, "r");
				if($handle !== false){
					$date = fread($handle, 10); // i.e. DD-MM-YYYY or 25-04-2018
					$last_updated = '<p>Last Updated: '. date("d F Y", strtotime($date)) .'</p>';
				}
			}

			include( ABSPATH . WPINC . '/version.php' );
			$footer_text = '<hr/><div class="text-center">'.$last_updated.'<p>Wordpress Version: '. $wp_version .'</p></div><hr/>';

			echo $footer_text.'</div>';
}


function bhartipay_register_settings() {
	$settings = bhartipay_settings_list();
	foreach ($settings as $setting) {
		register_setting($setting['name'], $setting['value']);
	}
}

function bhartipay_donation_handler(){

	if(isset($_REQUEST["action"]) && $_REQUEST["action"] == "bhartipay_donation_request"){
		return bhartipay_donation_submit();
	} else {
		return bhartipay_donation_form();
	}
}

function bhartipay_donation_form(){
	$current_url = "//".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	$html = ""; 
	$html = '<form name="frmTransaction" method="post">
					<p>
						<label for="donor_name">Name:</label>
						<input type="text" name="donor_name" maxlength="255" value="" required/>
					</p>
					<p>
						<label for="donor_email">Email:</label>
						<input type="text" name="donor_email" maxlength="255" value="" required />
					</p>
					<p>
						<label for="donor_phone">Phone:</label>
						<input type="text" name="donor_phone" maxlength="15" value="" required />
					</p>
					<p>
						<label for="donor_amount">Amount:</label>
						<input type="text" name="donor_amount" maxlength="10" value="'.trim(get_option('bhartipay_amount')).'" required />
					</p>
					<p>
						<label for="donor_address">Address:</label>
						<input type="text" name="donor_address" maxlength="255" value="" required />
					</p>
					<p>
						<label for="donor_city">City:</label>
						<input type="text" name="donor_city" maxlength="255" value="" required />
					</p>
					<p>
						<label for="donor_state">State:</label>
						<input type="text" name="donor_state" maxlength="255" value="" required />
					</p>
					<p>
						<label for="donor_postal_code">Postal Code:</label>
						<input type="text" name="donor_postal_code" maxlength="10" value="" required />
					</p>
					<p>
						<label for="donor_country">Country:</label>
						<input type="text" name="donor_country" maxlength="255" value="" required />
					</p>
					<p>
						<input type="hidden" name="action" value="bhartipay_donation_request">
						<input type="submit" value="' . trim(get_option('bhartipay_content')) .'" required />
					</p>
				</form>';
	
	return $html;
}


function bhartipay_donation_submit(){

	$valid = true; // default input validation flag
	$html = '';
	$msg = '';
			
	if( trim($_POST['donor_name']) != ''){
		$donor_name = $_POST['donor_name'];
	} else {
		$valid = false;
		$msg.= 'Name is required </br>';
	}
			
	if( trim($_POST['donor_email']) != ''){
		$donor_email = $_POST['donor_email'];
		if( preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/" , $donor_email)){}
		else{
			$valid = false;
			$msg.= 'Invalid email format </br>';
		}
	} else {
		$valid = false;
		$msg.= 'E-mail is required </br>';
	}
				
	if( trim($_POST['donor_amount']) != ''){
		$donor_amount = $_POST['donor_amount'];
		if( (is_numeric($donor_amount)) && ( (strlen($donor_amount) > '1') || (strlen($donor_amount) == '1')) ){}
		else{
			$valid = false;
			$msg.= 'Amount cannot be less then $1</br>';
		}
	} else {
		$valid = false;
		$msg.= 'Amount is required </br>';
	}

	if($valid){
		

		require_once(dirname(__FILE__) . '/lib/bppg_helper.php');
		global $wpdb;

		$table_name = $wpdb->prefix . "bhartipay_donation";
		$data = array(
					'name' => sanitize_text_field($_POST['donor_name']),
					'email' => sanitize_email($_POST['donor_email']),
					'phone' => sanitize_text_field($_POST['donor_phone']),
					'address' => sanitize_text_field($_POST['donor_address']),
					'city' => sanitize_text_field($_POST['donor_city']),
					'country' => sanitize_text_field($_POST['donor_country']),
					'state' => sanitize_text_field($_POST['donor_state']),
					'zip' => sanitize_text_field($_POST['donor_postal_code']),
					'amount' => sanitize_text_field($_POST['donor_amount']),
					'payment_status' => 'Pending Payment',
					'date' => date('Y-m-d H:i:s'),
				);

		$result = $wpdb->insert($table_name, $data);

		if(!$result){
			throw new Exception($wpdb->last_error);
		}

		$order_id = $wpdb->insert_id;

		// $order_id = 'TEST_'.strtotime("now").'-'.$order_id; //just for testing

		//For Post Request

		//End Post Request
       //echo get_option('bhartipay_merchant_id');exit;
 		$post_params = array(
			'PAY_ID' => trim(get_option('bhartipay_merchant_id')),
				'ORDER_ID' =>$order_id,
				'RETURN_URL' =>get_permalink(),
				'CUST_EMAIL'=>sanitize_email($_POST['donor_email']),
				'CUST_NAME' =>sanitize_text_field($_POST['donor_name']),
				'CUST_STREET_ADDRESS1'=>sanitize_text_field($_POST['donor_address']),
				'CUST_CITY' =>sanitize_text_field($_POST['donor_city']),
				'CUST_STATE' => sanitize_text_field($_POST['donor_state']),
				'CUST_COUNTRY' =>sanitize_text_field($_POST['donor_country']),
				'CUST_ZIP' =>sanitize_text_field($_POST['donor_postal_code']),
				'CUST_PHONE'=>sanitize_text_field($_POST['donor_phone']),
				'CURRENCY_CODE' =>356,
				'AMOUNT'        =>sanitize_text_field($_POST['donor_amount']*100),
				'PRODUCT_DESC' =>'Donation Collection' ,
				'CUST_SHIP_STREET_ADDRESS1' =>'',
				'CUST_SHIP_CITY'  => '',
				'CUST_SHIP_STATE' =>'',
				'CUST_SHIP_COUNTRY'=>'',
				'CUST_SHIP_ZIP'  =>'',
				'CUST_SHIP_PHONE'=>'',
				'CUST_SHIP_NAME' =>'',
				'TXNTYPE'      =>'SALE',                            
		);		
	
		$post_params["HASH"] = PayDonation::getHashFromArray(	$post_params,
																				trim(get_option('bhartipay_merchant_key'))
																			);
      //print_r($post_params);exit;
		$form_action = trim(get_option('transaction_url'));

		$html = "<center><h1>Please do not refresh this page...</h1></center>";

		
		$html .= '<form method="post" action="'.$form_action.'" name="f1">';

		foreach($post_params as $k=>$v){
			$html .= '<input type="hidden" name="'.$k.'" value="'.$v.'">';
		}

		$html .= "</form>";
		$html .= '<script type="text/javascript">document.f1.submit();</script>';
		return $html;

	} else {
		return $msg;
	}
}

function bhartipay_donation_meta_box() {
	$screens = array( 'bhartipaydonation' );
	
	foreach ( $screens as $screen ) {
		add_meta_box(  'myplugin_sectionid', __( 'bhartipay', 'myplugin_textdomain' ),'bhartipay_donation_meta_box_callback', $screen, 'normal','high' );
	}
}

add_action( 'add_meta_boxes', 'bhartipay_donation_meta_box' );

function bhartipay_donation_meta_box_callback($post) {
	echo "admin";
}

function bhartipay_donation_response(){
	global $wpdb;

	if($_POST['STATUS']=="Captured")
{
		require_once(dirname(__FILE__) . '/lib/bppg_helper.php');
		global $wpdb;

		$bhartipay_merchant_key = trim(get_option('bhartipay_merchant_key'));
		$bhartipay_merchant_id = trim(get_option('bhartipay_merchant_id'));
		$transaction_status_url = trim(get_option('transaction_status_url'));
		?>
		<script>swal("Thank you for your order. Your transaction has been successful.");</script>
		<?php

   $msg = "Thank you for your order. Your transaction has been successful.";
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "bhartipay_donation SET payment_status = 'Complete Payment' WHERE  id =%d", sanitize_text_field($_POST['ORDER_ID'])));
				
				}
// 				else{

// $msg = "It seems some issue in server to server communication. Kindly connect with administrator.";
// 					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "bhartipay_donation SET payment_status = 'Fraud Payment' WHERE id = %d", sanitize_text_field($_POST['ORDER_ID'])));
// 				}



				

}


		// if(bhartipayDonation::verifychecksum_e($_POST, $bhartipay_merchant_key, $_POST['CHECKSUMHASH']) === "TRUE") {
			
		// 	if($_POST['RESPCODE'] == "01"){

		// 		// Create an array having all required parameters for status query.
		// 		$requestParamList = array("MID" => $bhartipay_merchant_id, "ORDERID" => $_POST['ORDERID']);

		// 		// $_POST['ORDERID'] = substr($_POST['ORDERID'], strpos($_POST['ORDERID'], "-") + 1); // just for testing
				
		// 		$StatusCheckSum = bhartipayDonation::getChecksumFromArray($requestParamList, $bhartipay_merchant_key);

		// 		$requestParamList['CHECKSUMHASH'] = $StatusCheckSum;

		// 		$responseParamList = bhartipayDonation::callNewAPI($transaction_status_url, $requestParamList);

		// 		if($responseParamList['STATUS'] == 'TXN_SUCCESS' && $responseParamList['TXNAMOUNT'] == $_POST['TXNAMOUNT']) {
		// 			$msg = "Thank you for your order. Your transaction has been successful.";
		// 			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "bhartipay_donation SET payment_status = 'Complete Payment' WHERE  id = %d", sanitize_text_field($_POST['ORDERID'])));
				
		// 		} else  {
		// 			$msg = "It seems some issue in server to server communication. Kindly connect with administrator.";
		// 			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "bhartipay_donation SET payment_status = 'Fraud Payment' WHERE id = %d", sanitize_text_field($_POST['ORDERID'])));
		// 		}

		// 	} else {
		// 		$msg = "Thank You. However, the transaction has been Failed For Reason: " . sanitize_text_field($_POST['RESPMSG']);
		// 		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "bhartipay_donation SET payment_status = 'Cancelled Payment' WHERE id = %d", sanitize_text_field($_POST['ORDERID'])));
		// 	}
		// } else {
		// 	$msg = "Security error!";
		// 	$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "bhartipay_donation SET payment_status = 'Payment Error' WHERE  id = %d", sanitize_text_field($_POST['ORDERID'])));
		// }

		// $redirect_url = get_site_url() . '/' . get_permalink(get_the_ID());
		// //echo $redirect_url ."<br />";
		// $redirect_url = add_query_arg( array('donation_msg'=> urlencode($msg)));
		// wp_redirect( $redirect_url,301 );
		// exit;
	// }
// }


/*
* Code to test Curl
*/
if(isset($_GET['bhartipay_action']) && $_GET['bhartipay_action'] == "curltest"){
	add_action('the_content', 'curltest_donation');
}

function curltest_donation($content){

	// phpinfo();exit;
	$debug = array();

	if(!function_exists("curl_init")){
		$debug[0]["info"][] = "cURL extension is either not available or disabled. Check phpinfo for more info.";

	// if curl is enable then see if outgoing URLs are blocked or not
	} else {

		// if any specific URL passed to test for
		if(isset($_GET["url"]) && $_GET["url"] != ""){
			$testing_urls = array(esc_url_raw($_GET["url"]));
		
		} else {

			// this site homepage URL
			$server = get_site_url();

			$testing_urls = array(
											$server,
											"https://www.gstatic.com/generate_204",
											get_option('transaction_status_url')
										);
		}

		// loop over all URLs, maintain debug log for each response received
		foreach($testing_urls as $key=>$url){

			$debug[$key]["info"][] = "Connecting to <b>" . $url . "</b> using cURL";
			
			$response = wp_remote_get($url);

			if ( is_array( $response ) ) {

				$http_code = wp_remote_retrieve_response_code($response);
				$debug[$key]["info"][] = "cURL executed succcessfully.";
				$debug[$key]["info"][] = "HTTP Response Code: <b>". $http_code . "</b>";

				// $debug[$key]["content"] = $res;

			} else {
				$debug[$key]["info"][] = "Connection Failed !!";
				$debug[$key]["info"][] = "Error: <b>" . $response->get_error_message() . "</b>";
				break;
			}
		}
	}

	$content = "<center><h1>cURL Test for bhartipay Donation Plugin</h1></center><hr/>";
	foreach($debug as $k=>$v){
		$content .= "<ul>";
		foreach($v["info"] as $info){
			$content .= "<li>".$info."</li>";
		}
		$content .= "</ul>";

		// echo "<div style='display:none;'>" . $v["content"] . "</div>";
		$content .= "<hr/>";
	}

	return $content;
}
/*
* Code to test Curl
*/