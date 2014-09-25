<?php 
/*
Plugin Name: WooCommerce Booking Plugin
Plugin URI: http://www.tychesoftwares.com/store/premium-plugins/woocommerce-booking-plugin
Description: This plugin lets you capture the Booking Date & Booking Time for each product thereby allowing your WooCommerce store to effectively function as a Booking system. It allows you to add different time slots for different days, set maximum bookings per time slot, set maximum bookings per day, set global & product specific holidays and much more.
Version: 1.7.6
Author: Ashok Rane
Author URI: http://www.tychesoftwares.com/
*/

/*require 'plugin-updates/plugin-update-checker.php';
$ExampleUpdateChecker = new PluginUpdateChecker(
	'http://www.tychesoftwares.com/plugin-updates/woocommerce-booking-plugin/info.json',
	__FILE__
);*/

global $BookUpdateChecker;
$BookUpdateChecker = '1.7.6';

// this is the URL our updater / license checker pings. This should be the URL of the site with EDD installed
define( 'EDD_SL_STORE_URL_BOOK', 'http://www.tychesoftwares.com/' ); // IMPORTANT: change the name of this constant to something unique to prevent conflicts with other plugins using this system

// the name of your product. This is the title of your product in EDD and should match the download title in EDD exactly
define( 'EDD_SL_ITEM_NAME_BOOK', 'Woocommerce Booking & Appointment Plugin' ); // IMPORTANT: change the name of this constant to something unique to prevent conflicts with other plugins using this system


if ( ! defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'EDD_BOOK_Plugin_Updater' ) ) {
	// load our custom updater if it doesn't already exist
	include( dirname( __FILE__ ) . '/plugin-updates/EDD_BOOK_Plugin_Updater.php' );
}

// retrieve our license key from the DB
$license_key = trim( get_option( 'edd_sample_license_key' ) );

// setup the updater
$edd_updater = new EDD_BOOK_Plugin_Updater( EDD_SL_STORE_URL_BOOK, __FILE__, array(
		'version' 	=> '1.7.6', 		// current version number
		'license' 	=> $license_key, 	// license key (used get_option above to retrieve from DB)
		'item_name' => EDD_SL_ITEM_NAME_BOOK, 	// name of this plugin
		'author' 	=> 'Ashok Rane'  // author of this plugin
)
);

include_once('lang.php');
include_once('bkap-config.php');
include_once('bkap-common.php');
include_once('availability-search.php');
include_once('block-price-booking.php');
include_once('block-booking.php');
include_once('admin-bookings.php');
include_once('validation.php');
include_once('checkout.php');
include_once('cart.php');
include_once('ics.php');
include_once('cancel-order.php');
include_once('booking-process.php');
register_uninstall_hook( __FILE__, 'bkap_woocommerce_booking_delete');

/* ******************************************************************** 
 * This function will Delete all the records of booking plugin and
 *  wp_booking _history table from the database if the plugin is uninstalled. 
 ******************************************************************************/
function bkap_woocommerce_booking_delete(){
	
	global $wpdb;
	$table_name_booking_history = $wpdb->prefix . "booking_history";
	$sql_table_name_booking_history = "DROP TABLE " . $table_name_booking_history ;
	
	$table_name_order_history = $wpdb->prefix . "booking_order_history";
	$sql_table_name_order_history = "DROP TABLE " . $table_name_order_history ;

	$table_name_booking_block_price = $wpdb->prefix . "booking_block_price_meta";
	$sql_table_name_booking_block_price = "DROP TABLE " . $table_name_booking_block_price ;

	$table_name_booking_block_attribute = $wpdb->prefix . "booking_block_price_attribute_meta";
	$sql_table_name_booking_block_attribute = "DROP TABLE " . $table_name_booking_block_attribute ;

	$table_name_block_booking = $wpdb->prefix . "booking_fixed_blocks";
	$sql_table_name_block_booking = "DROP TABLE " . $table_name_block_booking;

	$table_name_booking_variable_lockout = $wpdb->prefix . "booking_variation_lockout_history";
	$sql_table_name_booking_variable_lockout = "DROP TABLE " . $table_name_booking_variable_lockout;
	
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	$wpdb->get_results($sql_table_name_booking_history);
	$wpdb->get_results($sql_table_name_order_history);
	$wpdb->get_results($sql_table_name_booking_block_price);
	$wpdb->get_results($sql_table_name_booking_block_attribute);
	$wpdb->get_results($sql_table_name_block_booking);
	$wpdb->get_results($sql_table_name_booking_variable_lockout);
	
	$sql_table_post_meta = "DELETE FROM `".$wpdb->prefix."postmeta` WHERE meta_key='woocommerce_booking_settings'";
	$results = $wpdb->get_results ( $sql_table_post_meta );
	
	$sql_table_option = "DELETE FROM `".$wpdb->prefix."options` WHERE option_name='woocommerce_booking_global_settings'";
	$results = $wpdb->get_results ($sql_table_option);
}

//if (is_woocommerce_active())
{
	/**
	 * Localisation
	 **/
	load_plugin_textdomain('woocommerce-booking', false, dirname( plugin_basename( __FILE__ ) ) . '/');

	/**
	 * woocommerce_booking class
	 **/
	if (!class_exists('woocommerce_booking')) {

		class woocommerce_booking {
			
			public function __construct() {
				
				
				//include_once('arrays.php');
				// Initialize settings
				register_activation_hook( __FILE__, array(&$this, 'bkap_bookings_activate'));
				add_action( 'plugins_loaded', array(&$this, 'bkap_bookings_update_db_check'));
				// Ajax calls
				add_action('init', array(&$this, 'bkap_book_load_ajax'));
				// WordPress Administration Menu
				add_action('admin_menu', array(&$this, 'bkap_woocommerce_booking_admin_menu'));
				
				// Display Booking Box on Add/Edit Products Page
				add_action('add_meta_boxes', array(&$this, 'bkap_booking_box'));
				
				// Processing Bookings
				add_action('woocommerce_process_product_meta', array(&$this, 'bkap_process_bookings_box'), 1, 2);
				
				// Scripts
				add_action( 'admin_enqueue_scripts', array(&$this, 'bkap_my_enqueue_scripts_css' ));
				add_action( 'admin_enqueue_scripts', array(&$this, 'bkap_my_enqueue_scripts_js' ));
				
				//add_action( 'woocommerce_before_main_content', array(&$this, 'front_side_scripts_js'));
				//add_action( 'woocommerce_before_main_content', array(&$this, 'front_side_scripts_css'));
				add_action( 'woocommerce_before_single_product', array(&$this, 'bkap_front_side_scripts_js'));
				add_action( 'woocommerce_before_single_product', array(&$this, 'bkap_front_side_scripts_css'));
				
				// Display on Products Page
				add_action( 'woocommerce_before_add_to_cart_form', array('bkap_booking_process', 'bkap_before_add_to_cart'));
				add_action( 'woocommerce_before_add_to_cart_button', array('bkap_booking_process', 'bkap_booking_after_add_to_cart'));
				
				// Ajax Calls
			//	require_once( ABSPATH . "wp-includes/pluggable.php" );
				
				add_action('wp_ajax_bkap_remove_time_slot', array(&$this, 'bkap_remove_time_slot'));
				add_action('wp_ajax_bkap_remove_day', array(&$this, 'bkap_remove_day'));
				add_action('wp_ajax_bkap_remove_specific', array(&$this, 'bkap_remove_specific'));
				add_action('wp_ajax_bkap_remove_recurring', array(&$this, 'bkap_remove_recurring'));
				
				add_filter('woocommerce_add_cart_item_data', array(bkap_cart, 'bkap_add_cart_item_data'), 10, 2);
				add_filter('woocommerce_get_cart_item_from_session', array(bkap_cart, 'bkap_get_cart_item_from_session'), 10, 2);
				add_filter( 'woocommerce_get_item_data', array(bkap_cart, 'bkap_get_item_data'), 10, 2 );
				
				//$show_checkout_date_calendar = 1;
				if (isset($booking_settings['booking_enable_multiple_day']) && $booking_settings['booking_enable_multiple_day'] == 'on') {
					add_filter( 'woocommerce_add_cart_item', array(bkap_cart, 'bkap_add_cart_item'), 10, 1 );
				}
				add_action( 'woocommerce_checkout_update_order_meta', array(bkap_checkout, 'bkap_order_item_meta'), 10, 2);
			//	add_action( 'woocommerce_order_item_meta', array(&$this, 'bkap_add_order_item_meta'), 10, 2 );
				add_action('woocommerce_before_checkout_process', array(bkap_validation, 'bkap_quantity_check'));
				add_filter( 'woocommerce_add_to_cart_validation', array(bkap_validation, 'bkap_get_validate_add_cart_item'), 10, 3 );
				add_action('woocommerce_order_status_cancelled' , array('bkap_cancel_order','bkap_woocommerce_cancel_order'),10,1);
				add_action('woocommerce_order_status_refunded' , array(&$this,'bkap_woocommerce_cancel_order'),10,1);
				add_action('woocommerce_duplicate_product' , array(&$this,'bkap_product_duplicate'),10,2);
				add_action('woocommerce_check_cart_items', array(bkap_validation,'bkap_quantity_check'));
				
				//Export date to ics file from order received page
				$saved_settings = json_decode(get_option('woocommerce_booking_global_settings'));
				if (isset($saved_settings->booking_export) && $saved_settings->booking_export == 'on') {
					add_filter('woocommerce_order_details_after_order_table', array('bkap_ics', 'bkap_export_to_ics'), 10, 3 );
				}
				
				//Add order details as an attachment
				if (isset($saved_settings->booking_attachment) && $saved_settings->booking_attachment == 'on') {
					add_filter('woocommerce_email_attachments', array('bkap_ics','bkap_email_attachment'), 10, 3 );
				}
				
				add_action('admin_init', array(&$this, 'bkap_edd_sample_register_option'));
				add_action('admin_init', array(&$this, 'bkap_edd_sample_deactivate_license'));
				add_action('admin_init', array(&$this, 'bkap_edd_sample_activate_license'));	
				add_filter('woocommerce_my_account_my_orders_actions', array('bkap_cancel_order', 'bkap_get_add_cancel_button'), 10, 3 );
				add_filter('add_to_cart_fragments', array(&$this, 'bkap_get_woo_cart_widget_subtotal'));
			}
			
                        
			/***************************************************************** 
                         * This function is used to load ajax functions required by plugin.
                         *******************************************************************/
			function bkap_book_load_ajax() {
				if ( !is_user_logged_in() ){
					add_action('wp_ajax_nopriv_bkap_get_per_night_price', array('bkap_booking_process', 'bkap_get_per_night_price'));
					add_action('wp_ajax_nopriv_bkap_check_for_time_slot', array('bkap_booking_process', 'bkap_check_for_time_slot'));
					//add_action('wp_ajax_nopriv_check_for_prices', array(&$this, 'check_for_prices'));
					add_action('wp_ajax_bkap_nopriv_insert_date', array('bkap_booking_process', 'bkap_insert_date'));
					add_action('wp_ajax_nopriv_bkap_call_addon_price', array('bkap_booking_process', 'bkap_call_addon_price'));
					//add_action('wp_ajax_nopriv_display_results', array(&$this, 'display_results'));
				} else{
					add_action('wp_ajax_bkap_get_per_night_price', array('bkap_booking_process', 'bkap_get_per_night_price'));
					add_action('wp_ajax_bkap_check_for_time_slot', array('bkap_booking_process', 'bkap_check_for_time_slot'));
					//	add_action('wp_ajax_check_for_prices', array(&$this, 'check_for_prices'));
					add_action('wp_ajax_bkap_insert_date', array('bkap_booking_process', 'bkap_insert_date'));
					add_action('wp_ajax_bkap_call_addon_price', array('bkap_booking_process', 'bkap_call_addon_price'));
					//add_action('wp_ajax_display_results', array(&$this, 'display_results'));
				}
			}
                        
                        /************************************************************************************** 
                         * This function will check the license entered using an API call to the store website.
                         *  And if its valid it will activate the license. 
                        *************************************************************************************/
			function bkap_edd_sample_activate_license() {
					
				// listen for our activate button to be clicked
				if( isset( $_POST['edd_license_activate'] ) ) {
						
					// run a quick security check
					if( ! check_admin_referer( 'edd_sample_nonce', 'edd_sample_nonce' ) )
						return; // get out if we didn't click the Activate button
						
					// retrieve the license from the database
					$license = trim( get_option( 'edd_sample_license_key' ) );
			
						
					// data to send in our API request
					$api_params = array(
							'edd_action'=> 'activate_license',
							'license' 	=> $license,
							'item_name' => urlencode( EDD_SL_ITEM_NAME_BOOK ) // the name of our product in EDD
					);
						
					// Call the custom API.
					$response = wp_remote_get( add_query_arg( $api_params, EDD_SL_STORE_URL_BOOK ), array( 'timeout' => 15, 'sslverify' => false ) );
						
					// make sure the response came back okay
					if ( is_wp_error( $response ) )
						return false;
						
					// decode the license data
					$license_data = json_decode( wp_remote_retrieve_body( $response ) );
						
					// $license_data->license will be either "active" or "inactive"
						
					update_option( 'edd_sample_license_status', $license_data->license );
						
				}
			}
				
				
			/***********************************************
			 * Illustrates how to deactivate a license key.
			* This will descrease the site count.
			***********************************************/
				
			function bkap_edd_sample_deactivate_license() {
					
				// listen for our activate button to be clicked
				if( isset( $_POST['edd_license_deactivate'] ) ) {
						
					// run a quick security check
					if( ! check_admin_referer( 'edd_sample_nonce', 'edd_sample_nonce' ) )
						return; // get out if we didn't click the Activate button
						
					// retrieve the license from the database
					$license = trim( get_option( 'edd_sample_license_key' ) );
			
						
					// data to send in our API request
					$api_params = array(
							'edd_action'=> 'deactivate_license',
							'license' 	=> $license,
							'item_name' => urlencode( EDD_SL_ITEM_NAME_BOOK ) // the name of our product in EDD
					);
						
					// Call the custom API.
					$response = wp_remote_get( add_query_arg( $api_params, EDD_SL_STORE_URL_BOOK ), array( 'timeout' => 15, 'sslverify' => false ) );
						
					// make sure the response came back okay
					if ( is_wp_error( $response ) )
						return false;
						
					// decode the license data
					$license_data = json_decode( wp_remote_retrieve_body( $response ) );
						
					// $license_data->license will be either "deactivated" or "failed"
					if( $license_data->license == 'deactivated' )
						delete_option( 'edd_sample_license_status' );
						
				}
			}
				
				
				
			/************************************
			 * This illustrates how to check if a license key is still valid. 
                         * The updater checks this,so this is only needed if you want to do something custom.
			*************************************/
				
			function bkap_edd_sample_check_license() {
					
				global $wp_version;
					
				$license = trim( get_option( 'edd_sample_license_key' ) );
					
				$api_params = array(
						'edd_action' => 'check_license',
						'license' => $license,
						'item_name' => urlencode( EDD_SL_ITEM_NAME_BOOK )
				);
					
				// Call the custom API.
				$response = wp_remote_get( add_query_arg( $api_params, EDD_SL_STORE_URL_BOOK ), array( 'timeout' => 15, 'sslverify' => false ) );
					
					
				if ( is_wp_error( $response ) )
					return false;
					
				$license_data = json_decode( wp_remote_retrieve_body( $response ) );
					
				if( $license_data->license == 'valid' ) {
					echo 'valid'; exit;
					// this license is still valid
				} else {
					echo 'invalid'; exit;
					// this license is no longer valid
				}
			}
                        
                        /*****************************************************************
                        * This function will store the license key in database of the site once the plugin is installed and the license key saved.
                        ************************************************************/
			function bkap_edd_sample_register_option() {
				// creates our settings in the options table
				register_setting('edd_sample_license', 'edd_sample_license_key', array(&$this, 'bkap_get_edd_sanitize_license' ));
			}
                        
			/****************************************************************************
                         * This function  checks if a new license has been entered , 
                         * if yes plugin must be reactivated.
                         *********************************************************************/	
			function bkap_get_edd_sanitize_license( $new ) {
				$old = get_option( 'edd_sample_license_key' );
				if( $old && $old != $new ) {
					delete_option( 'edd_sample_license_status' ); // new license has been entered, so must reactivate
				}
				return $new;
			}
			
                        /**************************************************
                         * This function add the license page in the Booking menu.
                         *********************************************/
			function bkap_get_edd_sample_license_page() {
				$license 	= get_option( 'edd_sample_license_key' );
				$status 	= get_option( 'edd_sample_license_status' );
			
				?>
				<div class="wrap">
					<h2><?php _e('Plugin License Options'); ?></h2>
					<form method="post" action="options.php">
					
						<?php settings_fields('edd_sample_license'); ?>
						
						<table class="form-table">
							<tbody>
								<tr valign="top">	
									<th scope="row" valign="top">
										<?php _e('License Key'); ?>
									</th>
									<td>
										<input id="edd_sample_license_key" name="edd_sample_license_key" type="text" class="regular-text" value="<?php esc_attr_e( $license ); ?>" />
										<label class="description" for="edd_sample_license_key"><?php _e('Enter your license key'); ?></label>
									</td>
								</tr>
								<?php if( false !== $license ) { ?>
									<tr valign="top">	
										<th scope="row" valign="top">
											<?php _e('Activate License'); ?>
										</th>
										<td>
											<?php if( $status !== false && $status == 'valid' ) { ?>
												<span style="color:green;"><?php _e('active'); ?></span>
												<?php wp_nonce_field( 'edd_sample_nonce', 'edd_sample_nonce' ); ?>
												<input type="submit" class="button-secondary" name="edd_license_deactivate" value="<?php _e('Deactivate License'); ?>"/>
											<?php } else {
												wp_nonce_field( 'edd_sample_nonce', 'edd_sample_nonce' ); ?>
												<input type="submit" class="button-secondary" name="edd_license_activate" value="<?php _e('Activate License'); ?>"/>
											<?php } ?>
										</td>
									</tr>
								<?php } ?>
							</tbody>
						</table>	
						<?php submit_button(); ?>
					
					</form>
				<?php
			}
              
                        
                        /***************************************
                         * This function duplicates the booking settings of the original product to the new product.
                         ***************************************/ 
                        function bkap_product_duplicate($new_id, $post) {
				global $wpdb;
				$old_id = $post->ID;
				$duplicate_query = "SELECT * FROM `".$wpdb->prefix."booking_history` WHERE post_id = ".$old_id."";
				$results_date = $wpdb->get_results ( $duplicate_query );
				foreach($results_date as $key => $value) {
					$query_insert = "INSERT INTO `".$wpdb->prefix."booking_history`
					(post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
					VALUES (
					'".$new_id."',
					'".$value->weekday."',
					'".$value->start_date."',
					'".$value->end_date."',
					'".$value->from_time."',
					'".$value->to_time."',
					'".$value->total_booking."',
					'".$value->total_booking."' )";
					$wpdb->query( $query_insert );
				}
			}
			/************************************
                         *This function displays the updated price in the cart widget.
                         * 
                         ************************************/
			function bkap_get_woo_cart_widget_subtotal( $fragments ) {
				global $woocommerce;
					
				$price = 0;
				foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
					if (isset($values['booking'])) $booking = $values['booking'];
					if (isset($booking[0]['price']) && $booking[0]['price'] != '') $price += ($booking[0]['price']) * $values['quantity'];
					else {
						if ($values['variation_id'] == '') $product_type = $values['data']->product_type;
						else $product_type = $values['data']->parent->product_type;
					
						if ($product_type == 'variable') {
							
                                                    $sale_price = get_post_meta( $values['variation_id'], '_sale_price', true);
							if($sale_price == '') {
								$regular_price = get_post_meta( $values['variation_id'], '_regular_price',true);
								$price += $regular_price * $values['quantity'];
							} else {
								$price += $sale_price * $values['quantity'];
							}
						} elseif($product_type == 'simple') {
							$sale_price = get_post_meta( $values['product_id'], '_sale_price', true);
			
							if(!isset($sale_price) || $sale_price == '' || $sale_price == 0) {
                                                            
								$regular_price = get_post_meta($values['product_id'], '_regular_price',true);
			
								$price += $regular_price * $values['quantity'];
							} else {
								$price += $sale_price * $values['quantity'];
							}
						}
					}
				}
			
				$saved_settings = json_decode(get_option('woocommerce_booking_global_settings'));
				if (isset($saved_settings->enable_rounding) && $saved_settings->enable_rounding == "on")
					$total_price = round($price);
				else $total_price = number_format($price,2);
				
				ob_start();
				$currency_symbol = get_woocommerce_currency_symbol();
				print('<p class="total"><strong>Subtotal:</strong> <span class="amount">'.$currency_symbol.$total_price.'</span></p>');
					
				$fragments['p.total'] = ob_get_clean();
					
				return $fragments;
			}
			
                            
			
                        /****************************************************
                         *  This function is executed when the plugin is updated using the Automatic Updater. 
                         *  The function then calls the bookings_activate function which will check the table structures for the plugin and make any changes if necessary.
                         ******************************************************/
			function bkap_bookings_update_db_check() {
				global $booking_plugin_version, $BookUpdateChecker;
		
				$booking_plugin_version = $BookUpdateChecker;
				
				if ($booking_plugin_version == "1.7.6") {
					$this->bkap_bookings_activate();
				}
			}
			
                        /*********************************************************
                         * This function detects when the booking plugin is activated and creates all the tables necessary in database,if they do not exists. 
                         *********************************************************/
			function bkap_bookings_activate() {
				
				global $wpdb;
				
				$table_name = $wpdb->prefix . "booking_history";
				
				$sql = "CREATE TABLE IF NOT EXISTS $table_name (
						`id` int(11) NOT NULL AUTO_INCREMENT,
						`post_id` int(11) NOT NULL,
  						`weekday` varchar(50) NOT NULL,
  						`start_date` date NOT NULL,
  						`end_date` date NOT NULL,
						`from_time` varchar(50) NOT NULL,
						`to_time` varchar(50) NOT NULL,
						`total_booking` int(11) NOT NULL,
						`available_booking` int(11) NOT NULL,
						PRIMARY KEY (`id`)
				) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1" ;
				
				$order_table_name = $wpdb->prefix . "booking_order_history";
				$order_sql = "CREATE TABLE IF NOT EXISTS $order_table_name (
							`id` int(11) NOT NULL AUTO_INCREMENT,
							`order_id` int(11) NOT NULL,
							`booking_id` int(11) NOT NULL,
							PRIMARY KEY (`id`)
				)ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1" ;

				$table_name_price = $wpdb->prefix . "booking_block_price_meta";

				$sql_price = "CREATE TABLE IF NOT EXISTS ".$table_name_price." (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`post_id` int(11) NOT NULL,
                `minimum_number_of_days` int(11) NOT NULL,
				`maximum_number_of_days` int(11) NOT NULL,
                `price_per_day` double NOT NULL,
				`fixed_price` double NOT NULL,
				 PRIMARY KEY (`id`)
				) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 " ;
				
				$table_name_meta = $wpdb->prefix . "booking_block_price_attribute_meta";
				
				$sql_meta = "CREATE TABLE IF NOT EXISTS ".$table_name_meta." (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`post_id` int(11) NOT NULL,
					`block_id` int(11) NOT NULL,
					`attribute_id` varchar(50) NOT NULL,
					`meta_value` varchar(500) NOT NULL,
					 PRIMARY KEY (`id`)
					) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 " ;

				$block_table_name = $wpdb->prefix . "booking_fixed_blocks";
				
				$blocks_sql = "CREATE TABLE IF NOT EXISTS ".$block_table_name." (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`global_id` int(11) NOT NULL,
				`post_id` int(11) NOT NULL,
				`block_name` varchar(50) NOT NULL,
				`number_of_days` int(11) NOT NULL,
				`start_day` varchar(50) NOT NULL,
				`end_day` varchar(50) NOT NULL,
				`price` double NOT NULL,
				`block_type` varchar(25) NOT NULL,
				PRIMARY KEY (`id`)
				) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 " ;
				
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
				dbDelta($order_sql);
				dbDelta($sql_price);
				dbDelta($sql_meta);
				dbDelta($blocks_sql);
				update_option('woocommerce_booking_db_version','1.7.6');
			
				$check_table_query = "SHOW COLUMNS FROM $table_name LIKE 'end_date'";
				
				$results = $wpdb->get_results ( $check_table_query );
				if (count($results) == 0) {
					$alter_table_query = "ALTER TABLE $table_name
											ADD `end_date` date AFTER  `start_date`";
					$wpdb->get_results ( $alter_table_query );
				}
				$alter_block_table_query = "ALTER TABLE `$block_table_name` CHANGE `price` `price` DECIMAL(10,2) NOT NULL;";
				$wpdb->get_results ( $alter_block_table_query );
				
				//Set default labels
				add_option('book.date-label','Start Date');
				add_option('checkout.date-label','<br>End Date');
				add_option('book.time-label','Booking Time');
				
				add_option('book.item-meta-date','Start Date');
				add_option('checkout.item-meta-date','End Date');
				add_option('book.item-meta-time','Booking Time');
				
				add_option('book.item-cart-date','Start Date');
				add_option('checkout.item-cart-date','End Date');
				add_option('book.item-cart-time','Booking Time');
			}
			
                        /**********************************************************
                         * This function adds the Booking settings  menu in the sidebar admin woocommerce.
                         **************************************************/
			function bkap_woocommerce_booking_admin_menu(){
			
				add_menu_page( 'Booking','Booking','manage_woocommerce', 'booking_settings',array(&$this, 'bkap_woocommerce_booking_page' ));
				$page = add_submenu_page('booking_settings', __( 'Settings', 'woocommerce-booking' ), __( 'Settings', 'woocommerce-booking' ), 'manage_woocommerce', 'woocommerce_booking_page', array(&$this, 'bkap_woocommerce_booking_page' ));
				$page = add_submenu_page('booking_settings', __( 'View Bookings', 'woocommerce-booking' ), __( 'View Bookings', 'woocommerce-booking' ), 'manage_woocommerce', 'woocommerce_history_page', array(&$this, 'bkap_woocommerce_history_page' ));
				$page = add_submenu_page('booking_settings', __( 'Activate License', 'woocommerce-booking' ), __( 'Activate License', 'woocommerce-booking' ), 'manage_woocommerce', 'booking_license_page', array(&$this, 'bkap_get_edd_sample_license_page' ));
				remove_submenu_page('booking_settings','booking_settings');
				do_action('bkap_add_submenu');
			}
			
                        /*********************************************
                         * This function adds a page on View Bookings submenu which displays the orders with the booking details. 
                         * The orders which are cancelled or refunded are not displayed.
                         ***********************************************************/
			function bkap_woocommerce_history_page() {
				
				if (isset($_GET['action'])) {
					$action = $_GET['action'];
				} else {
					$action = '';
				}
				if ($action == 'history' || $action == '') {
					$active_settings = "nav-tab-active";
				}
					
				?>
				
				<p></p>
												
				<!-- <h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
				<a href="admin.php?page=woocommerce_history_page&action=history" class="nav-tab <?php echo $active_settings; ?>"> <?php _e( 'Booking History', 'woocommerce-ac' );?> </a>
				</h2> -->
				
				<?php
				
				if ( $action == 'history' || $action == '' ) {
					global $wpdb;
						
					$query_order = "SELECT DISTINCT order_id FROM `" . $wpdb->prefix . "woocommerce_order_items`  ";
					$order_results = $wpdb->get_results( $query_order );
					
					$var = $today_checkin_var = $today_checkout_var = $booking_time = "";
					
					$booking_time_label = get_option('book.item-meta-time');
				//	echo $booking_time_label;
					
					foreach ( $order_results as $id_key => $id_value ) {
						$order = new WC_Order( $id_value->order_id );
						
						$order_items = $order->get_items();
						
						$terms = wp_get_object_terms( $id_value->order_id, 'shop_order_status', array('fields' => 'slugs') );
						if( (isset($terms[0]) && $terms[0] != 'cancelled') && (isset($terms[0]) && $terms[0] != 'refunded')) {
						
                                                $today_query = "SELECT * FROM `".$wpdb->prefix."booking_history` AS a1,`".$wpdb->prefix."booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a2.order_id = '".$id_value->order_id."'";
						$results_date = $wpdb->get_results ( $today_query );

						$c = 0;
						foreach ($order_items as $items_key => $items_value ) {
							$start_date = $end_date = $booking_time = "";
							
							$booking_time = array();
							//print_r($items_value);
							if (isset($items_value[$booking_time_label])) {
								$booking_time = explode(",",$items_value[$booking_time_label]);
							}
							
							$duplicate_of = get_post_meta($items_value['product_id'], '_icl_lang_duplicate_of', true);
							if($duplicate_of == '' && $duplicate_of == null) {
								$post_time = get_post($items_value['product_id']);
								if (isset($post_time)) {
									$id_query = "SELECT ID FROM `".$wpdb->prefix."posts` WHERE post_date = '".$post_time->post_date."' ORDER BY ID LIMIT 1";
									$results_post_id = $wpdb->get_results ( $id_query );
									if( isset($results_post_id) ) {
										$duplicate_of = $results_post_id[0]->ID;
									} else {
										$duplicate_of = $items_value['product_id'];
									}
								} else {
									$duplicate_of = $items_value['product_id'];
								}
							}
						//	echo "<pre>";echo $id_value->order_id; print_r($booking_time);echo "</pre>";
							if ( isset($results_date[$c]->start_date) ) {
								
                                                                if (isset($results_date[$c]) && isset($results_date[$c]->start_date)) $start_date = $results_date[$c]->start_date;
								
								if (isset($results_date[$c]) && isset($results_date[$c]->end_date)) $end_date = $results_date[$c]->end_date;
								
								if ($start_date == '0000-00-00' || $start_date == '1970-01-01') $start_date = '';
								if ($end_date == '0000-00-00' || $end_date == '1970-01-01') $end_date = '';
								$amount = $items_value['line_total'] + $items_value['line_tax'];
								if(is_plugin_active('bkap-printable-tickets/printable-tickets.php')) {
									$var_details = apply_filters('bkap_view_bookings',$id_value->order_id,$results_date[$c]->booking_id,$items_value['qty']);
								} else {
									$var_details = array();
								}
								if (count($booking_time) > 0) {
								foreach ($booking_time as $time_key => $time_value) {	
									if(array_key_exists('ticket_id',$var_details) && array_key_exists('security_code',$var_details)) {
									
										$var .= "<tr>
										<td>".$id_value->order_id."</td>
										<td>".$order->billing_first_name." ".$order->billing_last_name."</td>
										<td>".$items_value['name']."</td>
										<td>".$start_date."</td>
										<td>".$end_date."</td>
										<td>".$time_value."</td>
										<td>".$amount."</td>
										<td>".$order->completed_date."</td>
										".$var_details['ticket_id']."
										".$var_details['security_code']."
										<td><a href=\"post.php?post=". $id_value->order_id."&action=edit\">View Order</a></td>
										</tr>";
									} else {
										$var .= "<tr>
										<td>".$id_value->order_id."</td>
										<td>".$order->billing_first_name." ".$order->billing_last_name."</td>
										<td>".$items_value['name']."</td>
										<td>".$start_date."</td>
										<td>".$end_date."</td>
										<td>".$time_value."</td>
										<td>".$amount."</td>
										<td>".$order->completed_date."</td>
										<td><a href=\"post.php?post=". $id_value->order_id."&action=edit\">View Order</a></td>
										</tr>";
									}
									//foreach ($results_date as $key_date => $value_date )
									{
										/*$start_date_r = $end_date_r = '';
										if (isset($value_date->start_date)) $start_date_r = $value_date->start_date;
										if (isset($value_date->end_date)) $end_date_r = $value_date->end_date;
										
										if ($start_date_r == '0000-00-00' || $start_date_r == '1970-01-01') $start_date_r = '';
										if ($end_date_r == '0000-00-00' || $end_date_r == '1970-01-01') $end_date_r = '';*/
											
										if ( $start_date == date('Y-m-d' , current_time('timestamp') ) ) {
										
                                                                                    if(array_key_exists('ticket_id',$var_details) && array_key_exists('security_code',$var_details)) {
												
                                                                                                $today_checkin_var .= "<tr>
												<td>".$id_value->order_id."</td>
												<td>".$order->billing_first_name." ".$order->billing_last_name."</td>
												<td>".$items_value['name']."</td>
												<td>".$start_date."</td>
												<td>".$end_date."</td>
												<td>".$time_value."</td>
												<td>".$amount."</td>
												<td>".$order->completed_date."</td>
												".$var_details['ticket_id']."
												".$var_details['security_code']."
												<td><a href=\"post.php?post=". $id_value->order_id."&action=edit\">View Order</a></td>
												</tr>";
											} else {
												$today_checkin_var .= "<tr>
												<td>".$id_value->order_id."</td>
												<td>".$order->billing_first_name." ".$order->billing_last_name."</td>
												<td>".$items_value['name']."</td>
												<td>".$start_date."</td>
												<td>".$end_date."</td>
												<td>".$time_value."</td>
												<td>".$amount."</td>
												<td>".$order->completed_date."</td>
												<td><a href=\"post.php?post=". $id_value->order_id."&action=edit\">View Order</a></td>
												</tr>";
											}
										}
										
										if ( $end_date == date('Y-m-d' , current_time('timestamp') ) ) {
                                                                                    if(array_key_exists('ticket_id',$var_details) && array_key_exists('security_code',$var_details)) {
												$today_checkout_var .= "<tr>
												<td>".$id_value->order_id."</td>
												<td>".$order->billing_first_name." ".$order->billing_last_name."</td>
												<td>".$items_value['name']."</td>
												<td>".$start_date."</td>
												<td>".$end_date."</td>
												<td>".$time_value."</td>
												<td>".$amount."</td>
												<td>".$order->completed_date."</td>
												".$var_details['ticket_id']."
												".$var_details['security_code']."
												<td><a href=\"post.php?post=". $id_value->order_id."&action=edit\">View Order</a></td>
												</tr>";
											} else {	
												$today_checkout_var .= "<tr>
												<td>".$id_value->order_id."</td>
												<td>".$order->billing_first_name." ".$order->billing_last_name."</td>
												<td>".$items_value['name']."</td>
												<td>".$start_date."</td>
												<td>".$end_date."</td>
												<td>".$time_value."</td>
												<td>".$amount."</td>
												<td>".$order->completed_date."</td>
												<td><a href=\"post.php?post=". $id_value->order_id."&action=edit\">View Order</a></td>
												</tr>";
											}
										}
									}
									if ( $start_date != "" ) $c++;
                                                                    }
								} else {
								if(array_key_exists('ticket_id',$var_details) && array_key_exists('security_code',$var_details)) {
										$var .= "<tr>
										<td>".$id_value->order_id."</td>
										<td>".$order->billing_first_name." ".$order->billing_last_name."</td>
										<td>".$items_value['name']."</td>
										<td>".$start_date."</td>
										<td>".$end_date."</td>
										<td></td>
										<td>".$amount."</td>
										<td>".$order->completed_date."</td>
										".$var_details['ticket_id']."
										".$var_details['security_code']."
										<td><a href=\"post.php?post=". $id_value->order_id."&action=edit\">View Order</a></td>
										</tr>";
									} else {
										$var .= "<tr>
										<td>".$id_value->order_id."</td>
										<td>".$order->billing_first_name." ".$order->billing_last_name."</td>
										<td>".$items_value['name']."</td>
										<td>".$start_date."</td>
										<td>".$end_date."</td>
										<td></td>
										<td>".$amount."</td>
										<td>".$order->completed_date."</td>
										<td><a href=\"post.php?post=". $id_value->order_id."&action=edit\">View Order</a></td>
										</tr>";
									}
										
									if ( $start_date == date('Y-m-d' , current_time('timestamp') ) ) {
                                                                            
                                                                            if(array_key_exists('ticket_id',$var_details) && array_key_exists('security_code',$var_details)) {
											$today_checkin_var .= "<tr>
											<td>".$id_value->order_id."</td>
											<td>".$order->billing_first_name." ".$order->billing_last_name."</td>
											<td>".$items_value['name']."</td>
											<td>".$start_date."</td>
											<td>".$end_date."</td>
											<td></td>
											<td>".$amount."</td>
											<td>".$order->completed_date."</td>
											".$var_details['ticket_id']."
											".$var_details['security_code']."
											<td><a href=\"post.php?post=". $id_value->order_id."&action=edit\">View Order</a></td>
											</tr>";
										} else {
											$today_checkin_var .= "<tr>
											<td>".$id_value->order_id."</td>
											<td>".$order->billing_first_name." ".$order->billing_last_name."</td>
											<td>".$items_value['name']."</td>
											<td>".$start_date."</td>
											<td>".$end_date."</td>
											<td></td>
											<td>".$amount."</td>
											<td>".$order->completed_date."</td>
											<td><a href=\"post.php?post=". $id_value->order_id."&action=edit\">View Order</a></td>
											</tr>";
										}
									}
									
									if ( $end_date == date('Y-m-d' , current_time('timestamp') ) ) {
										
                                                                                if(array_key_exists('ticket_id',$var_details) && array_key_exists('security_code',$var_details)) {
											$today_checkout_var .= "<tr>
											<td>".$id_value->order_id."</td>
											<td>".$order->billing_first_name." ".$order->billing_last_name."</td>
											<td>".$items_value['name']."</td>
											<td>".$start_date."</td>
											<td>".$end_date."</td>
											<td></td>
											<td>".$amount."</td>
											<td>".$order->completed_date."</td>
											".$var_details['ticket_id']."
											".$var_details['security_code']."
											<td><a href=\"post.php?post=". $id_value->order_id."&action=edit\">View Order</a></td>
											</tr>";
										} else {
											$today_checkout_var .= "<tr>
											<td>".$id_value->order_id."</td>
											<td>".$order->billing_first_name." ".$order->billing_last_name."</td>
											<td>".$items_value['name']."</td>
											<td>".$start_date."</td>
											<td>".$end_date."</td>
											<td></td>
											<td>".$amount."</td>
											<td>".$order->completed_date."</td>
											<td><a href=\"post.php?post=". $id_value->order_id."&action=edit\">View Order</a></td>
											</tr>";
										}
									}
									if ( $start_date != "" ) $c++;
								}
							}
						}
					}
				}
						
                                $swf_path = plugins_url()."/woocommerce-booking/TableTools/media/swf/copy_csv_xls.swf";
                                ?>
									
						<script>
						
						jQuery(document).ready(function() {
						 	var oTable = jQuery('.datatable').dataTable( {
									"bJQueryUI": true,
									"sScrollX": "",
									"bSortClasses": false,
									"aaSorting": [[0,'desc']],
									"bAutoWidth": true,
									"bInfo": true,
									"sScrollY": "100%",	
									"sScrollX": "100%",
									"bScrollCollapse": true,
									"sPaginationType": "full_numbers",
									"bRetrieve": true,
									"oLanguage": {
													"sSearch": "Search:",
													"sInfo": "Showing _START_ to _END_ of_TOTAL_ entries",
													"sInfoEmpty": "Showing 0 to 0 of 0 entries",
													"sZeroRecords": "No matching records found",
													"sInfoFiltered": "(filtered from _MAX_total entries)",
													"sEmptyTable": "No data available in table",
													"sLengthMenu": "Show _MENU_ entries",
													"oPaginate": {
																	"sFirst":    "First",
																	"sPrevious": "Previous",
																	"sNext":     "Next",
																	"sLast":     "Last"
																  }
												 },
									 "sDom": 'T<"clear"><"H"lfr>t<"F"ip>',
							         "oTableTools": {
									            "sSwfPath": "<?php echo plugins_url(); ?>/woocommerce-booking/TableTools/media/swf/copy_csv_xls_pdf.swf"
                                                    			        }
									 
						} );
					} );
						
						       
                                </script>
						
						
						
						<div style="float: left;">
						<h2><strong>All Bookings</strong></h2>
						</div>
						<div>
						<table id="booking_history" class="display datatable" >
						    <thead>
						        <tr>
						        	<th><?php _e( 'Order ID' , 'woocommerce-booking' ); ?></th>
						            <th><?php _e( 'Customer Name' , 'woocommerce-booking' ); ?></th>
						            <th><?php _e( 'Product Name' , 'woocommerce-booking' ); ?></th>
						            <th><?php _e( 'Check-in Date' , 'woocommerce-booking' ); ?></th>
						            <th><?php _e( 'Check-out Date' , 'woocommerce-booking' ); ?></th>
						            <th><?php _e( 'Booking Time' , 'woocommerce-booking' ); ?></th>
						            <th><?php _e( 'Amount' , 'woocommerce-booking' ); ?></th>
						            <th><?php _e( 'Booking Date' , 'woocommerce-booking' ); ?></th>
						            <?php 
						            if (isset($var_details) && count($var_details) > 0 && $var_details != false) {
							            
                                                                if(array_key_exists('ticket_field',$var_details) && array_key_exists('security_field',$var_details)) {
							            	?>
											<th><?php _e( $var_details['ticket_field'],'woocommerce_booking' );?></th>
							            	<th><?php _e( $var_details['security_field'],'woocommerce_booking' );?></th>
							            <?php 
							            }
						            }
						            ?>
						            <th><?php _e( 'Action' , 'woocommerce-booking' ); ?></th>
						        </tr>
						    </thead>
						    <tbody>
					            <?php echo $var;?>
						    </tbody>
						</table>
						</div>
						
						<p></p>
						
						<div style="float: left;padding: 5">
						<h2><strong>Today Check-ins</strong></h2></div>
						<div>
						<table id="booking_history_today_check_in" class="display datatable" >
						    <thead>
						        <tr>
						        	<th><?php _e( 'Order ID' , 'woocommerce-booking' ); ?></th>
						            <th><?php _e( 'Customer Name' , 'woocommerce-booking' ); ?></th>
						            <th><?php _e( 'Product Name' , 'woocommerce-booking' ); ?></th>
						            <th><?php _e( 'Check-in Date' , 'woocommerce-booking' ); ?></th>
						            <th><?php _e( 'Check-out Date' , 'woocommerce-booking' ); ?></th>
						            <th><?php _e( 'Booking Time' , 'woocommerce-booking' ); ?></th>
						            <th><?php _e( 'Amount' , 'woocommerce-booking' ); ?></th>
						            <th><?php _e( 'Booking Date' , 'woocommerce-booking' ); ?></th>
						            <?php
						            if (isset($var_details) && count($var_details) > 0 && $var_details != false) { 
							            
                                                                    if(array_key_exists('ticket_field',$var_details) && array_key_exists('security_field',$var_details)) {
							            	?>
											<th><?php _e( $var_details['ticket_field'],'woocommerce_booking' );?></th>
							            	<th><?php _e( $var_details['security_field'],'woocommerce_booking' );?></th>
							            <?php 
							            }
						            }
						            ?>
						            <th><?php _e( 'Action' , 'woocommerce-booking' ); ?></th>
						        </tr>
						    </thead>
						    <tbody>
					            <?php echo $today_checkin_var;?>
						    </tbody>
						</table>
						</div>
						
						<p></p>
						
						<div style="float: left;">
						<h2><strong>Today Check-outs</strong></h2></div>
						<div>
						<table id="booking_history_today_check_out" class="display datatable" >
						    <thead>
						        <tr>
						        	<th><?php _e( 'Order ID' , 'woocommerce-booking' ); ?></th>
						            <th><?php _e( 'Customer Name' , 'woocommerce-booking' ); ?></th>
						            <th><?php _e( 'Product Name' , 'woocommerce-booking' ); ?></th>
						            <th><?php _e( 'Check-in Date' , 'woocommerce-booking' ); ?></th>
						            <th><?php _e( 'Check-out Date' , 'woocommerce-booking' ); ?></th>
						            <th><?php _e( 'Booking Time' , 'woocommerce-booking' ); ?></th>
						            <th><?php _e( 'Amount' , 'woocommerce-booking' ); ?></th>
						            <th><?php _e( 'Booking Date' , 'woocommerce-booking' ); ?></th>
						            <?php 
						            if (isset($var_details) && count($var_details) > 0 && $var_details != false) {
							            
                                                                if(array_key_exists('ticket_field',$var_details) && array_key_exists('security_field',$var_details)) {
							            	?>
											<th><?php _e( $var_details['ticket_field'],'woocommerce_booking' );?></th>
							            	<th><?php _e( $var_details['security_field'],'woocommerce_booking' );?></th>
							            <?php 
							            }
						            }
						            ?>
						            <th><?php _e( 'Action' , 'woocommerce-booking' ); ?></th>
						        </tr>
						    </thead>
						    <tbody>
					            <?php echo $today_checkout_var;?>
						    </tbody>
						</table>
						</div>
					<?php 
					
				}
			}
			/**************************************************************
                         * This function displays the global settings for the booking products.
                         *******************************************************************/
			function bkap_woocommerce_booking_page() {
				
				if (isset($_GET['action'])) {
					$action = $_GET['action'];
				} else {
					$action = '';
				}
				if ($action == 'settings' || $action == '') {
					$active_settings = "nav-tab-active";
				} else {
					$active_settings = '';
				}
					
				if ($action == 'labels') {
					$active_labels = "nav-tab-active";
				} else {
					$active_labels = '';
				}
			
				?>
								
				<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
				<a href="admin.php?page=woocommerce_booking_page&action=settings" class="nav-tab <?php echo $active_settings; ?>"> <?php _e( 'Global Booking Settings', 'woocommerce-ac' );?> </a>
				<a href="admin.php?page=woocommerce_booking_page&action=labels" class="nav-tab <?php echo $active_labels; ?>"> <?php _e( 'Booking Labels', 'woocommerce-ac' );?> </a>
			<!-- 	<a href="admin.php?page=woocommerce_booking_page&action=reminders_settings" class="nav-tab <?php echo $active_reminders_settings; ?>"> <?php _e( 'Email Reminders', 'woocommerce-ac' );?> </a> -->
				</h2>
				
				<?php
			/*	if( $action == 'reminders_settings'){
						
					if (isset($_GET["p"])) $p = $_GET["p"];
					else $p = '';
					if (isset($_GET["id"])) $id = $_GET["id"];
					else $id = '';
						
					if($p == "update"){
						$all_reminders = array_values(json_decode(get_option("globalemailreminders"),true));
				
						if(!$all_reminders){
							$all_reminders = array ();
						}
						//echo "<pre>";print_r(stripslashes($_POST["erem_subject"]));echo "</pre>";exit;
						$id = $_POST["id"];
						$subject = stripslashes($_POST["erem_subject"]);
						$message = stripslashes($_POST["erem_message"]);
						$days = $_POST["erem_days"];
						$hours = $_POST["erem_hours"];
						$minutes = $_POST["erem_minutes"];
						$total_minutes = $minutes + ($hours * 60) + (($days * 24 )*60);
						$email = $_POST["erem_email"];
				
				
				
				
						if($id != ""){
							$all_reminders[$id]["subject"] =  $subject;
							$all_reminders[$id]["message"] =  $message;
							$all_reminders[$id]["days"] =  $days;
							$all_reminders[$id]["hours"] =  $hours;
							$all_reminders[$id]["minutes"] =  $minutes;
							$all_reminders[$id]["total_minutes"] =  $total_minutes;
							$all_reminders[$id]["email"] =  $email;
						}else{
								
							array_push($all_reminders,
									array('subject' => $subject,
											'message' => $message,
											'days' => $days,
											'hours' => $hours,
											'minutes' => $minutes,
											'total_minutes' => $total_minutes,
											'email' => $email) );
						}
				
						update_option( "globalemailreminders", json_encode($all_reminders) );
				
					}if($p == "delete"){
						$all_reminders = array_values(json_decode(get_option("globalemailreminders"),true));
						unset($all_reminders[$id]);
						update_option( "globalemailreminders", json_encode($all_reminders) );
					}
					?>
				                 <style type="text/css">
								#wpfooter{
									display:none !important;	
								}
								</style>
				                <a href="admin.php?page=woocommerce_booking_page&action=reminders_settings&p=manage" > <?php _e( 'New Email Reminders', 'woocommerce-ac' );?> </a>
				                <?php
								if( $p == 'manage'){	
							//	$all_reminders = array_values(json_decode(get_option("globalemailreminders"),true));
								$email_reminders = json_decode(get_option("globalemailreminders"),true);
								if ($email_reminders != '') $all_reminders = array_values($email_reminders);
								else $all_reminders = '';
								
								if (isset($all_reminders[$id]["subject"])) $subject = $all_reminders[$id]["subject"];
								else $subject = '';
								
								if (isset($all_reminders[$id]["message"])) $message = $all_reminders[$id]["message"];
								else $message = '';
								
								if (isset($all_reminders[$id]["days"])) $days = $all_reminders[$id]["days"];
								else $days = '';
								
								if (isset($all_reminders[$id]["hours"])) $hours = $all_reminders[$id]["hours"];
								else $hours = '';
								
								if (isset($all_reminders[$id]["minutes"])) $minutes = $all_reminders[$id]["minutes"];
								else $minutes = '';
								
								if (isset($all_reminders[$id]["email"])) $email_copy = $all_reminders[$id]["email"];
								else $email_copy = '';
								?>               
				                <div id="global_reminder_manage">
				                <form name="gerem_form" id="gerem_form" action="admin.php?page=woocommerce_booking_page&action=reminders_settings&p=update" method="post">
				                <table class='wp-list-table widefat fixed posts' cellspacing='0' >
				                    <tr>
				                        <td width="30%">Subject</td>
				                        <td width="70%">
				                        <input type="text" style="width: 400px;" name="erem_subject" id="erem_subject" value="<?php echo $subject; ?>" />
				                        <img class="help_tip" width="16" height="16" data-tip="<?php _e('Subject', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
				                        </td>
				                    </tr>
				                    <tr>
				                        <td>Message<br/><i>Available short codes<br/>First Name = [first_name]<br/>Last Name = [last_name]<br/>Booking Date = [date]<br/>Booking Time = [time]<br/>Shop Name = [shop_name]<br/>Shop URL = [shop_url]<br/>Service = [service]<br/>Order Number = [order_number]</i><br/></td>
				                        <td>
				                            <textarea rows='15' style="width: 100%;" name="erem_message" id="erem_message"><?php echo $message; ?></textarea>
				                        <img class="help_tip" width="16" height="16" data-tip="<?php _e('Message', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
				                        </td>
				                    </tr>
				                    <tr>
				                        <td>Time slot</td>
				                        <td>
				                        <select name="erem_days">
				                        	<option value="0">-Days-</option>
				                        	<?php
				                            for($x = 1; $x < 360 ; $x++){
												if($days == $x){
													echo ('<option value="'.$x.'" selected="selected">'.$x.'</option>');
												}else{
													echo ('<option value="'.$x.'">'.$x.'</option>');
												}
												
											}
											?> 
				                                                   
				                        </select>
				                        <select name="erem_hours">
				                        	<option value="0">-Hours-</option>
				                        	<?php
				                            for($x = 1; $x < 24 ; $x++){
												if($hours == $x){
													echo ('<option value="'.$x.'" selected="selected">'.$x.'</option>');
												}else{
													echo ('<option value="'.$x.'">'.$x.'</option>');
												}																
											}
											?>                            
				                        </select>
				                        <select name="erem_minutes">
				                        	<option value="0">-Minutes-</option>
				                        	<?php
				                            for($x = 1; $x < 60 ; $x++){
												if($minutes == $x){
													echo ('<option value="'.$x.'" selected="selected">'.$x.'</option>');
												}else{
													echo ('<option value="'.$x.'">'.$x.'</option>');
												}
											}
											?>                            
				                        </select>
				                        <img class="help_tip" width="16" height="16" data-tip="<?php _e('Time slot', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
				                        </tr>
				                    </tr>
				                    <tr>
				                        <td>Extra email address to send a copy to (separated by comma)</td>
				                        <td>
				                        <input type="text" style="width: 400px;" name="erem_email" id="erem_email" value="<?php echo $email_copy; ?>" />
				                        <img class="help_tip" width="16" height="16" data-tip="<?php _e('Extra email address to send a copy to (separated by comma)', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
				                        </td>
				                    </tr>
				                    <tr>
				                        <td><input type="hidden" name="id" id="id" value="<?php echo $_GET["id"]; ?>" /></td>
				                        <td>
				                        <?php if($id != ""){
											echo ('<input type="submit" value="Update Reminder" />');	
										}else{
											echo ('<input type="submit" value="Add Reminder" />');	
										}?>
				                        </td>
				                    </tr>
				                </table>
				                </form>
				                </div>
				                <?php
								}else if( $p == 'view'){
									$all_reminders = array_values(json_decode(get_option("globalemailreminders"),true));						
								?>
				                
				                <div id="global_reminder_view">
				                <table class='wp-list-table widefat fixed posts' cellspacing='0' >
				                    <tr>
				                        <th width="30%">Subject</th>
				                        <td width="70%"><?php echo $all_reminders[$id]["subject"]; ?></td>
				                    </tr>
				                    <tr>
				                        <th>Message</th>
				                        <td><?php echo $all_reminders[$id]["message"]; ?></td>
				                    </tr>
				                    <tr>
				                        <th>Time slot</th>
				                        <td><?php echo $all_reminders[$id]["days"]; ?>D / <?php echo $all_reminders[$id]["hours"]; ?>H / <?php echo $all_reminders[$id]["minutes"]; ?>M</tr>
				                    </tr>
				                    <tr>
				                        <th>Extra email address to send a copy to (separated by comma)</th>
				                        <td><?php echo $all_reminders[$id]["email"]; ?></td>
				                    </tr>
				                </table>
				                </div>
				                <?php
								}
								?>
				                <p>&nbsp;</p>
				                All Global Reminders
				                <table class="form-table" width="95%">
				                	<tr>
				                    	<th width="10">#</th>
				                        <th>Subject</th>
				                        <th width="100">Time slot</th>
				                        <th width="50">View</th>
				                        <th width="50">Update</th>
				                        <th width="50">Delete</th>
				                    </tr>
				                    <?php 
				                    $email_reminders = json_decode(get_option("globalemailreminders"),true);
				                    if ($email_reminders != '') $all_reminders = array_values($email_reminders);
				                    else $all_reminders = '';
								//	$all_reminders = array_values(json_decode(get_option("globalemailreminders"),true));
									$count = 0;
									if($all_reminders)
									foreach ($all_reminders as $reminders){
										
									?>
									<tr>
				                    	<td width="10"  valign="top"><?php echo($count + 1); ?></td>
				                        <td  valign="top"><?php echo $reminders["subject"]; ?></td>
				                        <td width="100"><?php echo $reminders["days"]; ?> Days <br/> <?php echo $reminders["hours"]; ?> Hours <br/> <?php echo $reminders["minutes"]; ?> Minutes</td>
				                        <td  valign="top">
				                        <a href="admin.php?page=woocommerce_booking_page&action=reminders_settings&p=view&id=<?php echo($count); ?>"> <?php _e( 'view', 'woocommerce-booking' );?> </a>
				                        </td>
				                        <td  valign="top">
				                        <a href="admin.php?page=woocommerce_booking_page&action=reminders_settings&p=manage&id=<?php echo($count); ?>"> <?php _e( 'update', 'woocommerce-booking' );?> </a>
				                        </td>
				                        <td valign="top"><a href="admin.php?page=woocommerce_booking_page&action=reminders_settings&p=delete&id=<?php echo($count); ?>" onclick="return confirm('Are you sure you want to delete this ?')">Delete</a></td>
				                    </tr>
				                    <?php	
									$count++;
									}
									?>
				                </table>
				                </div>				    
				                <?php	
								}*/
								
				if( $action == 'labels'){
				
					$labels_product_page = array(
						'book.date-label'=>'Check-in Date',
						'checkout.date-label'=>'Check-out Date',     		
						'book.time-label'=> 'Booking Time' );
					$labels_order_page = array(
						'book.item-meta-date'=>'Check-in Date',
						'checkout.item-meta-date'=>'Check-out Date',
						'book.item-meta-time'=>'Booking Time');
					$labels_cart_page = array(
						'book.item-cart-date'=>'Check-in Date',
						'checkout.item-cart-date'=>'Check-out Date',
						'book.item-cart-time'=>'Booking Time');
					
					if ( isset( $_POST['wapbk_booking_settings_frm'] ) && $_POST['wapbk_booking_settings_frm'] == 'labelsave' ) { 
						
						foreach($labels_product_page as $key=>$label){
							update_option($key, $_POST[str_replace(".","_",$key)]);
						}
						foreach($labels_order_page as $key=>$label){
							update_option($key, $_POST[str_replace(".","_",$key)]);
						}
						foreach($labels_cart_page as $key=>$label){
							update_option($key, $_POST[str_replace(".","_",$key)]);
						}
					?>
					<div id="message" class="updated fade"><p><strong><?php _e( 'Your settings have been saved.', 'woocommerce-booking' ); ?></strong></p></div>
					<?php } ?>
				
					<div id="content">
						  <form method="post" action="" id="booking_settings">
							  <input type="hidden" name="wapbk_booking_settings_frm" value="labelsave">
							 
							  <div id="poststuff">
									<div class="postbox">
										<h3 class="hndle"><?php _e( 'Labels', 'woocommerce-booking' ); ?></h3>
										<div>
										  <table class="form-table">
										  <tr> 
											<td colspan="2"><h2><strong><?php _e( 'Labels on product page', 'woocommerce-booking' ); ?></strong></h2></td>
											<td><img style="margin-right:550px;" class="help_tip" width="16" height="16" data-tip="<?php _e('This sets the Labels on the Product Page.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" /></td>
										  </tr>
											<?php foreach ($labels_product_page as $key=>$label): 
												$value = get_option($key);
											?>
											<tr>
												<th>
													<label for="booking_language"><b><?php _e($label, 'woocommerce-booking'); ?></b></label>
												</th>
												<td>
													<input id="<?php echo $key?>" name="<?php echo $key?>" value="<?php echo $value;?>" >
												</td>
											</tr>
											<?php endforeach;?>
											<tr> 
											<td colspan="2"><h2><strong><?php _e( 'Labels on order received page and in email notification', 'woocommerce-booking' ); ?></strong></h2></td>
											<td><img style="margin-right:550px;" class="help_tip" width="16" height="16" data-tip="<?php _e('This sets the Labels on the Order Recieved and Email Notification page.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" /></td>
											</tr>
											<?php foreach ($labels_order_page as $key=>$label): 
												$value = get_option($key);
											?>
											<tr>
												<th>
													<label for="booking_language"><b><?php _e($label, 'woocommerce-booking'); ?></b></label>
												</th>
												<td>
													<input id="<?php echo $key?>" name="<?php echo $key?>" value="<?php echo $value;?>" >
												</td>
											</tr>
											<?php endforeach;?>
											<tr> 
											<td colspan="2"><h2><strong><?php _e( 'Labels on Cart & Check-out Page', 'woocommerce-booking' ); ?></strong></h2></td>
											<td><img style="margin-right:550px;" class="help_tip" width="16" height="16" data-tip="<?php _e('This sets the Label on the Cart and the Checkout page.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" /></td>
											</tr>
											<?php foreach ($labels_cart_page as $key=>$label): 
												$value = get_option($key);
											?>
											<tr>
												<th>
													<label for="booking_language"><b><?php _e($label, 'woocommerce-booking'); ?></b></label>
												</th>
												<td>
													<input id="<?php echo $key?>" name="<?php echo $key?>" value="<?php echo $value;?>" >
												</td>
											</tr>
											<?php endforeach;?>
											<tr>
												<th>
												<input type="submit" name="Submit" class="button-primary" value="<?php _e( 'Save Changes', 'woocommerce-booking' ); ?>" />
												</th>
											</tr>
											</table>
										</div>
									</div>
								</div>
							</form>
					</div>
					<?php							
				}		

				if( $action == 'settings' || $action == '' ) {
					// Save the field values
					if ( isset( $_POST['wapbk_booking_settings_frm'] ) && $_POST['wapbk_booking_settings_frm'] == 'save' ) {
						$calendar_theme = trim($_POST['wapbk_calendar_theme']);
						$calendar_themes = bkap_get_book_arrays('calendar_themes');
						$calendar_theme_name = $calendar_themes[$calendar_theme];
						
						$booking_settings = new stdClass();
						$booking_settings->booking_language = $_POST['booking_language'];
						$booking_settings->booking_date_format = $_POST['booking_date_format'];
						$booking_settings->booking_time_format = $_POST['booking_time_format'];
						$booking_settings->booking_months = $_POST['booking_months'];	
						$booking_settings->booking_calendar_day = $_POST['booking_calendar_day'];	
						if (isset($_POST['booking_enable_rounding'])){
							$booking_settings->enable_rounding = $_POST['booking_enable_rounding'];
                                                }else{
							$booking_settings->enable_rounding = '';
                                                }
						if(isset($_POST['booking_add_to_calendar'])){
							$booking_settings->booking_export = $_POST['booking_add_to_calendar'];													
                                                }else{						
							$booking_settings->booking_export = '';
                                                }
						if(isset($_POST['booking_add_to_email'])){
							$booking_settings->booking_attachment = $_POST['booking_add_to_email'];
                                                }else{
							$booking_settings->booking_attachment = '';
                                                }
						$booking_settings->booking_themes = $calendar_theme;
						$booking_settings->booking_global_holidays = $_POST['booking_global_holidays'];
						if(isset($_POST['booking_global_timeslot'])){
							$booking_settings->booking_global_timeslot = $_POST['booking_global_timeslot'];
                                                }else{
							$booking_settings->booking_global_timeslot = '';
                                                }
						if(isset($_POST['booking_global_selection'])){
							$booking_settings->booking_global_selection = $_POST['booking_global_selection'];
                                                }else{
							$booking_settings->booking_global_selection = '';
                                                }
						$booking_settings = apply_filters( 'bkap_save_global_settings', $booking_settings);
						$woocommerce_booking_settings = json_encode($booking_settings);
							
						update_option('woocommerce_booking_global_settings',$woocommerce_booking_settings);
						//exit;
					}
					?>
								
					<?php if ( isset( $_POST['wapbk_booking_settings_frm'] ) && $_POST['wapbk_booking_settings_frm'] == 'save' ) { ?>
					<div id="message" class="updated fade"><p><strong><?php _e( 'Your settings have been saved.', 'woocommerce-booking' ); ?></strong></p></div>
					<?php } ?>
					
					<?php 
					$saved_settings = json_decode(get_option('woocommerce_booking_global_settings'));
					?>
					<div id="content">
						  <form method="post" action="" id="booking_settings">
						  	  <input type="hidden" name="wapbk_booking_settings_frm" value="save">
						  	  <input type="hidden" name="wapbk_calendar_theme" id="wapbk_calendar_theme" value="<?php if (isset($saved_settings)) echo $saved_settings->booking_themes;?>">
							  <div id="poststuff">
									<div class="postbox">
										<h3 class="hndle"><?php _e( 'Settings', 'woocommerce-booking' ); ?></h3>
										<div>
										  <table class="form-table">
										  
										  	<tr>
										  		<th>
										  			<label for="booking_language"><b><?php _e('Language:', 'woocommerce-booking'); ?></b></label>
										  		</th>
										  		<td>
										  			<select id="booking_language" name="booking_language">
										  			<?php
										  			$language_selected = "";
										  			if (isset($saved_settings->booking_language)) {
										  				$language_selected = $saved_settings->booking_language;
										  			}
										  			
										  			if ( $language_selected == "" ) $language_selected = "en-GB";
													$languages = bkap_get_book_arrays('languages');
										  			
										  			foreach ( $languages as $key => $value ) {
										  				$sel = "";
										  				if ($key == $language_selected) {
										  					$sel = " selected ";
										  				}
										  				echo "<option value='$key' $sel>$value</option>";
										  			}
										  			?>
										  			</select>
										  			<img class="help_tip" width="16" height="16" data-tip="<?php _e('Choose the language for your booking calendar.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
										  		</td>
										  	</tr>
										  	
										  	<tr>
										  		<th>
										  			<label for="booking_date_format"><b><?php _e('Date Format:', 'woocommerce-booking');?></b></label>
										  		</th>
										  		<td>
										  			<select id="booking_date_format" name="booking_date_format">
										  			<?php
										  			if (isset($saved_settings)) { 
										  				$date_format = $saved_settings->booking_date_format;
										  			} else {
										  				$date_format = "";
										  			}
													$date_formats = bkap_get_book_arrays('date_formats');
										  			foreach ($date_formats as $k => $format) {
										  				printf( "<option %s value='%s'>%s</option>\n",
										  						selected( $k, $date_format, false ),
										  						esc_attr( $k ),
										  						date($format)
										  				);
										  			}
										  			?>
										  			</select>
										  			<img class="help_tip" width="16" height="16" data-tip="<?php _e('The format in which the booking date appears to the customers on the product page once the date is selected', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
										  		</td>
										  	</tr>
										  	
										  	<tr>
										  		<th>
										  			<label for="booking_time_format"><b><?php _e('Time Format:', 'woocommerce-booking');?></b></label>
										  		</th>
										  		<td>
										  			<select id="booking_time_format" name="booking_time_format">
										  			<?php
										  			$time_format = ""; 
										  			if (isset($saved_settings)) {
										  				$time_format = $saved_settings->booking_time_format;
										  			}
													$time_formats = bkap_get_book_arrays('time_formats');
										  			foreach ($time_formats as $k => $format) {
										  				printf( "<option %s value='%s'>%s</option>\n",
										  						selected( $k, $time_format, false ),
										  						esc_attr( $k ),
										  						$format
										  				);
										  			}
										  			?>
										  			</select>
										  			<img class="help_tip" width="16" height="16" data-tip="<?php _e('The format in which booking time appears to the customers on the product page once the time / time slot is selected', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
										  		</td>
										  	</tr>
										  	
										  	<tr>
										  		<th>
										  			<label for="booking_months"><b><?php _e('Number of months to show in calendar:','woocommerce-booking');?></b></label>
										  		</th>
										  		<td>
										  			<?php 
										  			$no_months_1 = "";
										  			$no_months_2 = "";
										  			if (isset($saved_settings)) {
											  			if ( $saved_settings->booking_months == 1) {
											  				$no_months_1 = "selected";
											  				$no_months_2 = "";
											  			} elseif ( $saved_settings->booking_months == 2) {
											  				$no_months_2 = "selected";
											  				$no_months_1 = "";
											  			}
										  			}
										  			?>
										  			<select id="booking_months" name="booking_months">
										  			<option <?php echo $no_months_1;?> value="1"> 1 </option>
										  			<option <?php echo $no_months_2;?> value="2"> 2 </option>
										  			</select>
										  			<img class="help_tip" width="16" height="16" data-tip="<?php _e('The number of months to be shown on the calendar. If the booking dates spans across 2 months, then dates of 2 months can be shown simultaneously without the need to press Next or Back buttons.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
										  		</td>
										  	</tr>
										  	<tr>
										  		<th>
										  			<label for="booking_calendar_day"><b><?php _e('First Day on Calendar:', 'woocommerce-booking'); ?></b></label>
										  		</th>
										  		<td>
										  			<select id="booking_calendar_day" name="booking_calendar_day">
										  			<?php
										  			$day_selected = "";
										  			if (isset($saved_settings->booking_calendar_day)) {
										  				$day_selected = $saved_settings->booking_calendar_day;
										  			}
										  			
										  			if ( $day_selected == "" ) $day_selected = get_option('start_of_week');
										  			$days = bkap_get_book_arrays('days');
										  			foreach ( $days as $key => $value ) {
										  				$sel = "";
										  				if ($key == $day_selected) {
										  					$sel = " selected ";
										  				}
										  				echo "<option value='$key' $sel>$value</option>";
										  			}
										  			?>
										  			</select>
										  			<img class="help_tip" width="16" height="16" data-tip="<?php _e('Choose the language for your booking calendar.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
										  		</td>
										  	</tr>
										  	<tr>
										  		<th>
										  			<label for="booking_add_to_calendar"><b><?php _e('Show "Add to Calendar" button on Order Received page:', 'woocommerce-booking');?></b></label>
										  		</th>
										  		<td>
										  			<?php
										  			$export_ics = ""; 
									  				if (isset($saved_settings->booking_export) && $saved_settings->booking_export == 'on') {
									  					$export_ics = 'checked';
									  				}

										  			?>
										  			<input type="checkbox" id="booking_add_to_calendar" name="booking_add_to_calendar" <?php echo $export_ics; ?>/>
										  			<img class="help_tip" width="16" height="16" data-tip="<?php _e('Shows the \'Add to Calendar\' button on the Order Received page. On clicking the button, an ICS file will be downloaded.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
										  		</td>
										  	</tr>
										  	
										  	<tr>
										  		<th>
										  			<label for="booking_add_to_email"><b><?php _e('Send bookings as attachments (ICS files) in email notifications:', 'woocommerce-booking');?></b></label>
										  		</th>
										  		<td>
										  			<?php
										  			$email_ics = ""; 
									  				if (isset($saved_settings->booking_attachment) && $saved_settings->booking_attachment == 'on') {
									  					$email_ics = 'checked';
									  				}

										  			?>
										  			<input type="checkbox" id="booking_add_to_email" name="booking_add_to_email" <?php echo $email_ics; ?>/>
										  			<img class="help_tip" width="16" height="16" data-tip="<?php _e('Allow customers to export bookings as ICS file after placing an order. Sends ICS files as attachments in email notifications.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
										  		</td>
										  	</tr>
											<tr>
										  		<th>
										  			<label for="booking_theme"><b><?php _e('Preview Theme & Language:','woocommerce-booking');?></b></label>
										  		</th>
										  		<td>
													<?php 
										  			$global_holidays = "";
										  			if (isset($saved_settings)) {
											  			if ( $saved_settings->booking_global_holidays != "" ) {
											  				$global_holidays = "addDates: ['".str_replace(",", "','", $saved_settings->booking_global_holidays)."']";
														}
										  			}
										  			?>
										  			
										  			<img style="margin-left:250px;" class="help_tip" width="16" height="16" data-tip="<?php _e('Select the theme for the calendar. You can choose a theme which blends with the design of your website.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png"/>

										  			<div>
										  	
													<script type="text/javascript">
														
													  jQuery(document).ready(function() {
														  	
															jQuery("#booking_new_switcher").themeswitcher({
														    	onclose: function() {
														    		var cookie_name = this.cookiename;
														    		jQuery("input#wapbk_calendar_theme").val(jQuery.cookie(cookie_name));
														    	},
														    	imgpath: "<?php echo plugins_url().'/woocommerce-booking/images/';?>",
														    	loadTheme: "smoothness"
														    });
													
															var date = new Date();
															jQuery.datepicker.setDefaults( jQuery.datepicker.regional[ "en-GB" ] );
															jQuery('#booking_switcher').multiDatesPicker({
																dateFormat: "d-m-yy",
																altField: "#booking_global_holidays",
																<?php echo $global_holidays;?>
															});
															
															jQuery(function() {
															
															
															jQuery.datepicker.setDefaults( jQuery.datepicker.regional[ "" ] );
															jQuery( "#booking_switcher" ).datepicker( jQuery.datepicker.regional[ "en-GB" ] );
															jQuery( "#booking_new_switcher" ).datepicker( jQuery.datepicker.regional[ "<?php echo $language_selected;?>" ] );
															jQuery( "#booking_language" ).change(function() {
															jQuery( "#booking_new_switcher" ).datepicker( "option",
															jQuery.datepicker.regional[ jQuery(this).val() ] );
															
															});
															jQuery(".ui-datepicker-inline").css("font-size","1.4em");
														//	jQuery( "#booking_language" ).change(function() {
														//	jQuery( "#booking_switcher" ).datepicker( "option",
														//	jQuery.datepicker.regional[ "en-GB" ] );
															});
														//	});
															
															/*function append_date(date,inst)
															{
																var monthValue = inst.selectedMonth+1;
																var dayValue = inst.selectedDay;
																var yearValue = inst.selectedYear;

																var current_dt = dayValue + "-" + monthValue + "-" + yearValue;

																jQuery('#booking_global_holidays').append(current_dt+",");
															}*/

															//jQuery('#booking_global_holidays').multiDatesPicker();
													  });
													</script>
													
													<div id="booking_new_switcher" name="booking_new_switcher"></div>
										  		</td>
										  	</tr>
										  	
										  	<tr>
										  		<th>
										  			<label for="booking_global_holidays"><b><?php _e('Select Holidays / Exclude Days / Black-out days:', 'woocommerce-booking');?></b></label>
										  		</th>
										  		<td>
										  			<textarea rows="4" cols="80" name="booking_global_holidays" id="booking_global_holidays"></textarea>
										  			<img class="help_tip" width="16" height="16" data-tip="<?php _e('Select dates for which the booking will be completely disabled for all the products in your WooCommerce store.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" style="vertical-align:top;"/><br>
										  			Please click on the date in calendar to add or delete the date from the holiday list.
													<div id="booking_switcher" name="booking_switcher"></div>
										  		</td>
										  	</tr>
										  	<tr>
												<th>
													<label for="booking_global_timeslot"><b><?php _e('Global Time Slot Booking:', 'woocommerce-booking');?></b></label>
												</th>
											<td>
												<?php
												$global_timeslot = ""; 
												if (isset($saved_settings->booking_global_timeslot) && $saved_settings->booking_global_timeslot == 'on') {
													$global_timeslot = "checked";
												}
												?>
												<input type="checkbox" id="booking_global_timeslot" name="booking_global_timeslot" <?php echo $global_timeslot; ?>/>
												<img class="help_tip" width="16" height="16" data-tip="<?php _e('Please select this checkbox if you want ALL time slots to be unavailable for booking in all products once the lockout for that time slot is reached for any product.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" style="vertical-align:top;"/><br>
											</td>
											</tr>
											
										  	<tr>
										  		<th>
										  			<label for="booking_enable_rounding"><b><?php _e('Enable Rounding of Prices:', 'woocommerce-booking');?></b></label>
										  		</th>
										  		<td>
										  			<?php
										  			$rounding = ""; 
									  				if (isset($saved_settings->enable_rounding) && $saved_settings->enable_rounding == 'on') {
									  					$rounding = 'checked';
									  				}

										  			?>
										  			<input type="checkbox" id="booking_enable_rounding" name="booking_enable_rounding" <?php echo $rounding; ?>/>
										  			<img class="help_tip" w`idth="16" height="16" data-tip="<?php _e('Rounds the Price to the nearest Integer value.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
										  		</td>
										  	</tr>
											<tr>
												<th>
													<label for="booking_global_selection"><b><?php _e('Duplicate dates from first product in the cart to other products:', 'woocommerce-booking');?></b></label>
												</th>
											<td>
												<?php
												$global_selection = ""; 
												if (isset($saved_settings->booking_global_selection) && $saved_settings->booking_global_selection == 'on'){
													$global_selection = "checked";
												}
												?>
												<input type="checkbox" id="booking_global_selection" name="booking_global_selection" <?php echo $global_selection; ?>/>
												<img class="help_tip" width="16" height="16" data-tip="<?php _e('Please select this checkbox if you want to select the date globally for All products once selected for a product and added to cart.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" style="vertical-align:top;"/><br>
											</td>
											</tr>
											<?php do_action('bkap_after_global_holiday_field');?>
										  	<tr>
										  		<th>
										  		<input type="submit" name="Submit" class="button-primary" value="<?php _e( 'Save Changes', 'woocommerce-booking' ); ?>" />
										  		</th>
										  	</tr>
										  	
										  </table>
										</div>
									</div>
								</div>
							</form>
					</div>
										  
					<?php 
				}
			}
			/*************************************************************
                         * This function include css files required for admin side.
                         ***********************************************************/
			function bkap_my_enqueue_scripts_css() {
			
				if ( get_post_type() == 'product'  || (isset($_GET['page']) && $_GET['page'] == 'woocommerce_booking_page' ) || 
					(isset($_GET['page']) && $_GET['page'] == 'woocommerce_history_page' ) || (isset($_GET['page']) && $_GET['page'] == 'operator_bookings') || (isset($_GET['page']) && $_GET['page'] == 'woocommerce_availability_page')) {
					wp_enqueue_style( 'booking', plugins_url('/css/booking.css', __FILE__ ) , '', '', false);
					wp_enqueue_style( 'datepick', plugins_url('/css/jquery.datepick.css', __FILE__ ) , '', '', false);
					
					wp_enqueue_style( 'woocommerce_admin_styles', plugins_url() . '/woocommerce/assets/css/admin.css' );
				
					$calendar_theme = 'base';
					wp_enqueue_style( 'jquery-ui', "http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/$calendar_theme/jquery-ui.css" , '', '', false);
				
					wp_enqueue_style( 'TableTools', plugins_url('/TableTools/media/css/TableTools.css', __FILE__ ) , '', '', false);
				}
				if((isset($_GET['page']) && $_GET['page'] == 'woocommerce_booking_page' ) ||
						(isset($_GET['page']) && $_GET['page'] == 'woocommerce_history_page' )) {
					wp_enqueue_style( 'dataTable', plugins_url('/css/data.table.css', __FILE__ ) , '', '', false);
				}
			}
			
                        /******************************************************
                         * This function includes js files required for admin side.
                         ******************************************************/
			function bkap_my_enqueue_scripts_js() {
				
				if ( get_post_type() == 'product'  || (isset ($_GET['page']) && $_GET['page'] == 'woocommerce_booking_page') || (isset ($_GET['page']) && $_GET['page'] == 'woocommerce_availability_page') ) {
					wp_enqueue_script( 'jquery' );
					
					wp_deregister_script( 'jqueryui');
					wp_enqueue_script( 'jqueryui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js', '', '', false );
					
					wp_enqueue_script( 'jquery-ui-datepicker' );
					
					wp_register_script( 'multiDatepicker', plugins_url().'/woocommerce-booking/js/jquery-ui.multidatespicker.js');
					wp_enqueue_script( 'multiDatepicker' );
					
					wp_register_script( 'datepick', plugins_url().'/woocommerce-booking/js/jquery.datepick.js');
					wp_enqueue_script( 'datepick' );
					
					$current_language = json_decode(get_option('woocommerce_booking_global_settings'));
					if (isset($current_language)) {
						$curr_lang = $current_language->booking_language;
					} else {
						$curr_lang = "";
					}
					if ( $curr_lang == "" ) $curr_lang = "en-GB";
					
				}
				
				// below files are only to be included on booking settings page
				if (isset($_GET['page']) && $_GET['page'] == 'woocommerce_booking_page') {
					wp_register_script( 'woocommerce_admin', plugins_url() . '/woocommerce/assets/js/admin/woocommerce_admin.js', array('jquery', 'jquery-ui-widget', 'jquery-ui-core'));
					wp_enqueue_script( 'woocommerce_admin' );
					wp_enqueue_script( 'themeswitcher', plugins_url('/js/jquery.themeswitcher.min.js', __FILE__), '', '', false );
					wp_enqueue_script("lang", plugins_url("/js/i18n/jquery-ui-i18n.js", __FILE__), '', '', false);
					
					wp_enqueue_script(
							'jquery-tip',
							plugins_url('/js/jquery.tipTip.minified.js', __FILE__),
							'',
							'',
							false
					);
				}
				
				if (isset($_GET['page']) && $_GET['page'] == 'woocommerce_history_page' || (isset($_GET['page']) && $_GET['page'] == 'operator_bookings')) {
					wp_register_script( 'dataTable', plugins_url().'/woocommerce-booking/js/jquery.dataTables.js');
					wp_enqueue_script( 'dataTable' );
					
					wp_register_script( 'TableTools', plugins_url().'/woocommerce-booking/TableTools/media/js/TableTools.js');
					wp_enqueue_script( 'TableTools' );
						
					wp_register_script( 'ZeroClip', plugins_url().'/woocommerce-booking/TableTools/media/js/ZeroClipboard.js');
					wp_enqueue_script( 'ZeroClip' );
					
					/*wp_register_script( 'woocommerce_admin', plugins_url() . '/woocommerce/assets/js/admin/woocommerce_admin.js', array('jquery', 'jquery-ui-widget', 'jquery-ui-core'));
					wp_enqueue_script( 'woocommerce_admin' );*/
				}
			}
                        
                        /******************************************************
                         * This function includes js files required for frontend.
                         ******************************************************/
			
			function bkap_front_side_scripts_js() {
			
				if( is_product() || is_page()) {
					wp_enqueue_script(
							'initialize-datepicker.js',
							plugins_url('/js/initialize-datepicker.js', __FILE__),
							'',
							'',
							false
					);
					wp_enqueue_script( 'jquery' );
					
					wp_enqueue_script( 'jquery-ui-datepicker' );
				//	wp_deregister_script( 'jquery-ui');
				//	wp_enqueue_script( 'jquery-ui-js','http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.23/jquery-ui.min.js', '', '', false );
					
					if(isset($_GET['lang']) && $_GET['lang'] != '' && $_GET['lang'] != null) {
						$curr_lang = $_GET['lang'];
					} else {
						$current_language = json_decode(get_option('woocommerce_booking_global_settings'));
						if (isset($current_language)) {
							$curr_lang = $current_language->booking_language;
						} else {
							$curr_lang = "";
						}
						if ( $curr_lang == "" ) $curr_lang = "en-GB";
					}
					wp_enqueue_script("$curr_lang", plugins_url("/js/i18n/jquery.ui.datepicker-$curr_lang.js", __FILE__), '', '', false);
				}
			}
			
                        /******************************************************
                         * This function includes css files required for frontend.
                         ******************************************************/
			function bkap_front_side_scripts_css() {
					
				$calendar_theme = json_decode(get_option('woocommerce_booking_global_settings'));
				$calendar_theme_sel = "";
				if (isset($calendar_theme)) {
					$calendar_theme_sel = $calendar_theme->booking_themes;
				}
				if ( $calendar_theme_sel == "" ) $calendar_theme_sel = 'smoothness';
				//wp_enqueue_style( 'jquery-ui', "http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/$calendar_theme_sel/jquery-ui.css" , '', '', false);
				wp_enqueue_style( 'jquery-ui', plugins_url('/css/themes/'.$calendar_theme_sel.'/jquery-ui.css', __FILE__ ) , '', '', false);
				wp_enqueue_style( 'booking', plugins_url('/css/booking.css', __FILE__ ) , '', '', false);
			}
			/*******************************************
                         *This function adds a meta box for booking settings on product page.
                         ******************************************/
			function bkap_booking_box() {
				
				add_meta_box( 'woocommerce-booking', __('Booking', 'woocommerce-booking'), array(&$this, 'bkap_meta_box'),'product');
			}
			
                        /**********************************************
                         * This function displays the settings for the product in the Booking meta box on the admin product page.
                         ********************************************/
			function bkap_meta_box() {
				
				?>
				<script type="text/javascript">

				// On Radio Button Selection
				jQuery(document).ready(function(){
			/*		jQuery( "input[name='booking_method_select']" ).change(function() {
						if ( jQuery( "input[name='booking_method_select']:checked" ).val() == "booking_specific_booking" )
						{
							jQuery( "#selective_booking" ).show();
							jQuery( "#booking_enable_weekday" ).hide();
						}
						else if ( jQuery( "input[name='booking_method_select']:checked" ).val() == "booking_recurring_booking" )
						{
							jQuery( "#selective_booking" ).hide();
							jQuery( "#booking_enable_weekday" ).show();
						}
							
						
					}); */ 

                                                        jQuery("table#list_bookings_specific a.remove_time_data, table#list_bookings_recurring a.remove_time_data").click(function() {
								//alert('hello there');
								var y=confirm('Are you sure you want to delete this time slot?');
								if(y==true) {
									var passed_id = this.id;
									var exploded_id = passed_id.split('&');
									var data = {
											details: passed_id,
											action: 'bkap_remove_time_slot'
									};

									jQuery.post('<?php echo get_admin_url();?>/admin-ajax.php', data, function(response)
									{
										//alert('Got this from the server: ' + response);
										jQuery("#row_" + exploded_id[0] + "_" + exploded_id[2] ).hide();
									});
								}
								
					});

					jQuery("table#list_bookings_specific a.remove_day_data, table#list_bookings_recurring a.remove_day_data").click(function() {
							//	alert('hello there');
								var y=confirm('Are you sure you want to delete this day?');
								if(y==true) {
									var passed_id = this.id;
									var exploded_id = passed_id.split('&');
									var data = {
											details: passed_id,
											action: 'bkap_remove_day'
									};
									//alert('hello there');
									jQuery.post('<?php echo get_admin_url();?>/admin-ajax.php', data, function(response) {
												//alert('Got this from the server: ' + response);
										jQuery("#row_" + exploded_id[0]).hide();
									});
								
								}
							});

					jQuery("table#list_bookings_specific a.remove_specific_data").click(function() {
							//	alert('hello there');
								var y=confirm('Are you sure you want to delete all the specific date records?');
								if(y==true) {
									var passed_id = this.id;
								//	alert(passed_id);
								//	var exploded_id = passed_id.split('&');
									var data = {
											details: passed_id,
											action: 'bkap_remove_specific'
									};
								//	alert('hello there');
									jQuery.post('<?php echo get_admin_url();?>/admin-ajax.php', data, function(response) {
												//alert('Got this from the server: ' + response);
												jQuery("table#list_bookings_specific").hide();
											});
								}
							});
					
					jQuery("table#list_bookings_recurring a.remove_recurring_data").click(function() {
							//	alert('hello there');
								var y=confirm('Are you sure you want to delete all the recurring weekday records?');
								if(y==true) {
									var passed_id = this.id;
								//	var exploded_id = passed_id.split('&');
									var data = {
											details: passed_id,
											action: 'bkap_remove_recurring'
									};
								//	alert('hello there');
									jQuery.post('<?php echo get_admin_url();?>/admin-ajax.php', data, function(response) {
												//alert('Got this from the server: ' + response);
												jQuery("table#list_bookings_recurring").hide();
											}); 
								}
							});
					
					jQuery("#booking_enable_multiple_day").change(function() {
						if(jQuery('#booking_enable_multiple_day').attr('checked')) {
							jQuery('#booking_method').hide();
							jQuery('#booking_time').hide();
							jQuery('#booking_enable_weekday').hide();
							jQuery('#selective_booking').hide();
							jQuery('#purchase_without_date').hide();
						} else {
							jQuery('#booking_method').show();
							jQuery('#inline_calender').show();
							jQuery('#booking_time').show();
							jQuery('#booking_enable_weekday').show();
							jQuery('#selective_booking').show();
							jQuery('#purchase_without_date').show();
						}
					});
				});
				/******************************************
                                * This function displays a new div to add timeslots on the admin product page when Add timeslot button is clicked.
                                 *******************************************/
				function bkap_add_new_div(id){

					var exploded_id = id.split('[');
					var new_var = parseInt(exploded_id[1]) + parseInt(1);
					var new_html_var = jQuery('#time_slot_empty').html();
					var re = new RegExp('\\[0\\]',"g");
					new_html_var = new_html_var.replace(re, "["+new_var+"]");
					
					jQuery("#time_slot").append(new_html_var);
					jQuery('#add_another').attr("onclick","bkap_add_new_div('["+new_var+"]')");
				}
                                /*****************************************************
                                * This function handles the display of each tab for booking settings on the admin booking page.
                                 *****************************************************/    
				function bkap_tabs_display(id){

					if( id == "addnew" ) {
					//	jQuery( "#reminder_wrapper" ).hide();
						jQuery( "#date_time" ).show();
						jQuery( "#listing_page" ).hide();
						jQuery( "#payments_page" ).hide();
						jQuery( "#tours_page" ).hide();
						jQuery( "#rental_page" ).hide();
						jQuery( "#seasonal_pricing" ).hide();
						jQuery( "#block_booking_price_page" ).hide();
						jQuery( "#block_booking_page").hide();
						jQuery( "#addnew" ).attr("class","nav-tab nav-tab-active");
					//	jQuery( "#reminder" ).attr("class","nav-tab");
						jQuery( "#list" ).attr("class","nav-tab");
						jQuery( "#rental" ).attr("class","nav-tab");
						jQuery( "#tours" ).attr("class","nav-tab");
						jQuery( "#seasonalpricing" ).attr("class","nav-tab");
						jQuery( "#payments" ).attr("class","nav-tab");
						jQuery( "#block_booking_price" ).attr("class","nav-tab");
						jQuery( "#block_booking" ).attr("class","nav-tab");
					} else if( id == "list" ) {
				//		jQuery( "#reminder_wrapper" ).hide();
						jQuery( "#date_time" ).hide();
						jQuery( "#rental_page" ).hide();
						jQuery( "#seasonal_pricing" ).hide();
						jQuery( "#payments_page" ).hide();
						jQuery( "#tours_page" ).hide();
						jQuery( "#listing_page" ).show();
						jQuery( "#block_booking_price_page" ).hide();
						jQuery( "#block_booking_page").hide();
						jQuery( "#list" ).attr("class","nav-tab nav-tab-active");
						jQuery( "#addnew" ).attr("class","nav-tab");
					//	jQuery( "#reminder" ).attr("class","nav-tab");
						jQuery( "#rental" ).attr("class","nav-tab");
						jQuery( "#tours" ).attr("class","nav-tab");
						jQuery( "#seasonalpricing" ).attr("class","nav-tab");
						jQuery( "#payments" ).attr("class","nav-tab");
						jQuery( "#block_booking_price" ).attr("class","nav-tab");
						jQuery( "#block_booking" ).attr("class","nav-tab");
					}
				/*	else if( (id == "reminder") | (id == "reminder_manage_link") | (id == "reminder_view_link") | (id == "reminder_update_link") )
					{
						jQuery( "#reminder_wrapper" ).show();
						jQuery( "#date_time" ).hide();
						jQuery( "#rental_page" ).hide();
						jQuery( "#seasonal_pricing" ).hide();
						jQuery( "#payments_page" ).hide();
						jQuery( "#tours_page" ).hide();
						jQuery( "#listing_page" ).hide();
						jQuery( "#list" ).attr("class","nav-tab");
						jQuery( "#addnew" ).attr("class","nav-tab");
						jQuery( "#reminder" ).attr("class","nav-tab nav-tab-active");
						jQuery( "#rental" ).attr("class","nav-tab");
						jQuery( "#tours" ).attr("class","nav-tab");
						jQuery( "#seasonalpricing" ).attr("class","nav-tab");
						jQuery( "#payments" ).attr("class","nav-tab");
						
						if((id == "reminder_manage_link") | (id == "reminder_update_link"))
						{
							jQuery( "#reminder_manage" ).show();
							jQuery( "#reminder_view" ).hide();
						}
						else if(id == "reminder_view_link")
						{
							jQuery( "#reminder_view" ).show();
							jQuery( "#reminder_manage" ).hide();
						}					
					}*/
				}

				</script>
				
	<!-- 	 	<form id="booking_form" method="post" action="">  -->
				<h1 class="nav-tab-wrapper woo-nav-tab-wrapper">
				<a href="javascript:void(0);" class="nav-tab nav-tab-active" id="addnew" onclick="bkap_tabs_display('addnew')"> <?php _e( 'Booking Options', 'woocommerce-booking' );?> </a>
				<a href="javascript:void(0);" class="nav-tab " id="list" onclick="bkap_tabs_display('list')"> <?php _e( 'View/Delete Booking Dates, Time Slots', 'woocommerce-booking' );?> </a>
		<!-- 	<a href="javascript:void(0);" class="nav-tab " id="reminder" onclick="tabs_display('reminder')"> <?php _e( 'Email Reminder', 'woocommerce-booking' );?> </a> -->
				</h1>
				
		<!-- 	<div id="reminder_wrapper" style="display:none;" >                       



<div id="obj_wrapper">

</div>
<input type="button" onClick="diplicate_obj('','','0','0','0','')" value="Add New Reminder" />
<input type="hidden" id="diplicate_obj_count" value="0"/>                       
       
<script type="text/javascript">
function diplicate_obj (subject, message, days, hours, minutes, email) {
	
	var diplicate_obj_count = document.getElementById("diplicate_obj_count");
	var count = parseInt(diplicate_obj_count.value);	
	var obj_wrapper_name = "obj_wrapper";
	
	if(count != 0){
		obj_wrapper_name = "obj_wrapper_"+(count-1);
	}
	
	var obj_wrapper = document.getElementById(obj_wrapper_name);	
		
	var obj = "";
	//alert(message);
	obj += "<table id='tbl_"+obj_wrapper_name+"' class='wp-list-table widefat fixed posts email_remind_table' cellspacing='0' >";
	obj += "<tr><td colspan='2' align='right'><a href='#all_reminders' onclick='javascript:hideReminders(\"tbl_"+obj_wrapper_name+"\")'>&#10006; Close</a></td></tr>"	
	obj += "<tr>";
	obj += "<td width='30%'>Subject</td>";
	obj += "<td width='70%'>";
	obj += "<input type='text' style='width: 400px;' name='erem_subject["+count+"]' value='"+subject+"' />";
	obj += "<img class='help_tip' width='16' height='16' data-tip='<?php _e('Subject', 'woocommerce-booking');?>' src='<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png' />";
	obj += "</td>";
	obj += "</tr>";
	obj += "<tr>";
	obj += "<td>Message<br/><i>Available short codes<br/>First Name = [first_name]<br/>Last Name = [last_name]<br/>Booking Date = [date]<br/>Booking Time = [time]<br/>Shop Name = [shop_name]<br/>Shop URL = [shop_url]<br/>Service = [service]<br/>Order Number = [order_number]</i><br/></td>";
	obj += "<td>";
	obj += "<textarea rows='15' style='width: 100%;' name='erem_message["+count+"]' >"+message+"</textarea>";
	obj += "<img class='help_tip' width='16' height='16' data-tip='<?php _e('Message', 'woocommerce-booking');?>' src='<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png' />";
	obj += "</td>";
	obj += "</tr>";
	obj += "<tr>";
	obj += "<td>Time slot</td>";
	obj += "<td>";
	obj += "<select name='erem_days["+count+"]'>";
	obj += "<option value='0'>-Days-</option>";
	<?php
	for($x = 1; $x < 365 ; $x++){		
		echo("if(days == ".$x."){");
		echo("obj += \"<option value='".$x."' selected='selected'>".$x."</option>\";");
		echo("}else{");
		echo("obj += \"<option value='".$x."'>".$x."</option>\";");
		echo("}");					
	}
	?>	
	

									  
	obj += "</select>";
	obj += "<select name='erem_hours["+count+"]'>";
	obj += "<option value='0'>-Hours-</option>";
	<?php
	for($x = 1; $x < 24 ; $x++){
		echo("if(hours == ".$x."){");
		echo("obj += \"<option value='".$x."' selected='selected'>".$x."</option>\";");
		echo("}else{");
		echo("obj += \"<option value='".$x."'>".$x."</option>\";");
		echo("}");
	}
	?>						  
	obj += "</select>";
	obj += "<select name='erem_minutes["+count+"]'>";
	obj += "<option value='0'>-Minutes-</option>";
	<?php
	for($x = 1; $x < 60 ; $x++){
		echo("if(minutes == ".$x."){");
		echo("obj += \"<option value='".$x."' selected='selected'>".$x."</option>\";");
		echo("}else{");
		echo("obj += \"<option value='".$x."'>".$x."</option>\";");
		echo("}");	
	}
	?>						
	obj += "</select>";
	obj += "<img class='help_tip' width='16' height='16' data-tip='<?php _e('Time slot', 'woocommerce-booking');?>' src='<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png' />";
	obj += "</tr>";
	obj += "</tr>";
	obj += "<tr>";
	obj += "<td>Extra email address to send a copy to (separated by comma)</td>";
	obj += "<td>";
	obj += "<input type='text' style='width: 400px;' name='erem_email["+count+"]' value='"+email+"' />";
	obj += "<img class='help_tip' width='16' height='16' data-tip='<?php _e('Extra email address to send a copy to (separated by comma)', 'woocommerce-booking');?>' src='<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png' />";
	obj += "</td>";
	obj += "</tr>";	
	obj += "</table>";
	obj += "<p> </p>";
	obj += "<div id='obj_wrapper_"+count+"'></div>";	
				
	obj_wrapper.innerHTML = obj_wrapper.innerHTML+obj+"<p> </p>";	
	diplicate_obj_count.value = count+1;		
}


function showReminders (obj){
	//document.getElementsByClassName('.email_remind_table').style.backgroundColor	
	var obj1 = document.getElementById(obj);
	obj1.style.display = "block";
}
function hideReminders (obj){
	var obj1 = document.getElementById(obj);
	obj1.style.display = "none";
}

function deleteReminders (obj1, obj2){
	
	var r = confirm("Are you sure you want to delete this ?");
	if (r == true){
		document.getElementById(obj1).remove();
		document.getElementById(obj2).remove();
	}
}


</script>    

<?php
global $post;
$all_reminders = get_post_meta($post->ID, 'woocommerce_booking_emailreminders', true);
//echo $all_reminders;exit;
if(isset($all_reminders)) {
	$all_reminders = array_values(json_decode($all_reminders,true));
	
	?>
<p>&nbsp;</p>
All Reminders
<table class="form-table" width="95%" id="all_reminders">
    <tr>
        <th width="10">#</th>
        <th>Subject</th>
        <th width="100">Time slot</th>
        <th width="100">View/Update</th>
        <th width="50">Delete</th>
    </tr>	
	<?php
	$count = 0;
	foreach($all_reminders as $reminder){			
		$wrapper_id = "";
		echo ("<script type='text/javascript'>");
		
		if($count == "0"){
			$wrapper_id = "obj_wrapper";
			
		}else{
			$wrapper_id = "obj_wrapper_".($count-1) ;
		}
		
		$reminder_message = $reminder["message"];
		//echo $reminder["message"];exit;
		echo ("diplicate_obj('".$reminder["subject"]."',
		'".$reminder_message."' ,
		'".$reminder["days"]."' ,
		'".$reminder["hours"]."' ,
		'".$reminder["minutes"]."' ,
		'".$reminder["email"]."');");
		
		echo ("var obj_wrapper = document.getElementById('tbl_".$wrapper_id."');");
		echo ("obj_wrapper.style.display = 'none';");
		
		echo ("</script>");
		
		?>
    <tr id="email_reminder_view_row_<?php echo $count; ?>">
        <td width="10"  valign="top"><?php echo($count + 1); ?></td>
        <td  valign="top"><?php echo $reminder["subject"]; ?></td>
        <td width="100"><?php echo $reminder["days"]; ?> Days <br/> <?php echo $reminder["hours"]; ?> Hours <br/> <?php echo $reminder["minutes"]; ?> Minutes</td>        
        <td  valign="top">
        <a href="#<?php echo $wrapper_id; ?>" onclick="javascript:showReminders('tbl_<?php echo $wrapper_id; ?>');"> <?php _e( 'Edit', 'woocommerce-booking' );?> </a>
        </td>
        <td valign="top"><a href="#all_reminders" onclick="javascript:deleteReminders('email_reminder_view_row_<?php echo $count; ?>','tbl_<?php echo $wrapper_id; ?>');">Delete</a></td>
    </tr>		
		<?php
		$count++;
	}
?>
</table> 
<?php
}
?>
                </div> -->
                
				<div id="date_time">
				<table class="form-table">
				<?php 
				global $post, $wpdb;
				$duplicate_of = get_post_meta($post->ID, '_icl_lang_duplicate_of', true);
				if($duplicate_of == '' && $duplicate_of == null) {
					$post_time = get_post($post->ID);
					$id_query = "SELECT ID FROM `".$wpdb->prefix."posts` WHERE post_date = '".$post_time->post_date."' ORDER BY ID LIMIT 1";
					$results_post_id = $wpdb->get_results ( $id_query );
					if( isset($results_post_id) ) {
						$duplicate_of = $results_post_id[0]->ID;
					} else {
						$duplicate_of = $post->ID;
					}
					//$duplicate_of = $item_value['product_id'];
				}
				do_action('bkap_before_enable_booking', $duplicate_of);
				$booking_settings = get_post_meta($duplicate_of, 'woocommerce_booking_settings', true);
				$add_button_show = 'none';
				$enable_time_checked = '';
				if (isset($booking_settings['booking_enable_time']) && $booking_settings['booking_enable_time'] == 'on') {
					$add_button_show = 'block';
					$enable_time_checked = ' checked ';
				}
					
				?>
					<tr>
					<th>
					<label for="booking_enable_date"  style="color: brown"> <b> <?php _e( 'Enable Booking Date:', 'woocommerce-booking' );?> </b> </label>
					</th>
					<td>
					<?php 
					$enable_date = '';
					if( isset($booking_settings['booking_enable_date']) && $booking_settings['booking_enable_date'] == 'on' ) {
						$enable_date = 'checked';
					}
					?>
					<input type="checkbox" id="booking_enable_date" name="booking_enable_date" <?php echo $enable_date;?> >
					<img class="help_tip" width="16" height="16" data-tip="<?php _e('Enable Booking Date on Products Page', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
					</td>
				</tr>
				<?php 
				do_action('bkap_before_enable_multiple_days', $duplicate_of);
				?>
				<tr>
					<th>
					<label for="booking_enable_multiple_day"  style="color: brown"> <b> <?php _e( 'Allow multiple day booking:', 'woocommerce-booking' );?> </b> </label>
					</th>
					<td>
					<?php 
					$enable_multiple_day = '';
					$booking_method_div = $booking_time_div = 'table-row';
					$purchase_without_date = 'show';
					if( isset($booking_settings['booking_enable_multiple_day']) && $booking_settings['booking_enable_multiple_day'] == 'on' ) {
						$enable_multiple_day = 'checked';
						$booking_method_div = 'none';
						$booking_time_div = 'none';
						$purchase_without_date = 'none';
					}
					?>
					<input type="checkbox" id="booking_enable_multiple_day" name="booking_enable_multiple_day" <?php echo $enable_multiple_day;?> >
					<img class="help_tip" width="16" height="16" data-tip="<?php _e('Enable Multiple day Bookings on Products Page', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
					</td>
				</tr>
				
				<?php /* if(!isset($booking_settings['booking_enable_multiple_day']) || $booking_settings['booking_enable_multiple_day'] != 'on')
					$show = "show";
					else
					$show = "none"; */?>
					<tr id="inline_calender" style="display:show">
					<th>
					<label for="enable_inline_calendar"> <b> <?php _e( 'Enable Inline Calendar:', 'woocommerce-booking' );?> </b> </label>
					</th>
					<td>
					<?php 
					$enable_inline_calendar = '';
					if( isset($booking_settings['enable_inline_calendar']) && $booking_settings['enable_inline_calendar'] == 'on' ) {
						$enable_inline_calendar= 'checked';
					}
					?>
					<input type="checkbox" id="enable_inline_calendar" name="enable_inline_calendar" <?php echo $enable_inline_calendar;?> >
					<img class="help_tip" width="16" height="16" data-tip="<?php _e('Enable Inline Calendar on Products Page', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
					</td>
				</tr>
				<?php do_action('bkap_before_booking_method_select', $duplicate_of);
				?>
				<tr id="booking_method" style="display:<?php echo $booking_method_div;?>;">
					<th>
					<label for="booking_method_select"> <b> <?php _e( 'Select Booking Method(s):', 'woocommerce-booking');?></b></label>
					</th>
					<td>
					<?php 
					$specific_booking_chk = '';
					$recurring_div_show = $specific_dates_div_show = 'none';
					if( (isset($booking_settings['booking_specific_booking']) && $booking_settings['booking_specific_booking'] == 'on') && $booking_settings['booking_enable_multiple_day'] != 'on' ) {
						$specific_booking_chk = 'checked';
						$specific_dates_div_show = 'block';
					}
					$recurring_booking = '';
					if( (isset($booking_settings['booking_recurring_booking']) && $booking_settings['booking_recurring_booking'] == 'on') && $booking_settings['booking_enable_multiple_day'] != 'on' ) {
						$recurring_booking = 'checked';
						$recurring_div_show = 'block';
					}
					?>
					<b>Current Booking Method: </b>
					<?php 
					if ($specific_booking_chk != 'checked' && $recurring_booking != 'checked') echo "None";
					if ($specific_booking_chk == 'checked' && $recurring_booking == 'checked') echo "Specific Dates, Recurring Weekdays";
					if ($specific_booking_chk == 'checked' && $recurring_booking != 'checked') echo "Specific Dates";
					if ($specific_booking_chk != 'checked' && $recurring_booking == 'checked') echo "Recurring Weekdays";
					?> 
					<br>
					<input type="checkbox" name="booking_specific_booking" id="booking_specific_booking" onClick="bkap_book_method(this)" <?php echo $specific_booking_chk; ?>> <b> <?php _e('Specific Dates', 'woocommerce-booking');?> </b></input>
					<img style="margin-left:40px;"  class="help_tip" width="16" height="16" data-tip="<?php _e('Please enable/disable the specific booking dates and recurring weekdays using these checkboxes. Upon checking them, you shall be able to further select dates or weekdays.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" /><br>
					<input type="checkbox" name="booking_recurring_booking" id="booking_recurring_booking" onClick="bkap_book_method(this)" <?php echo $recurring_booking; ?> > <b> <?php _e('Recurring Weekdays', 'woocommerce-booking');?> </b></input><br>
					<i>Details of current weekdays and specific dates are available in the second tab.</i>
					
					</td>
				</tr>
				</table>
				
							<script type="text/javascript">
                                                            /*************************************
                                                             * this function checks which booking method is selected on the admin product page
                                                             ***************************************/
								function bkap_book_method(chk) {
									if ( jQuery( "input[name='booking_specific_booking']").attr("checked")) {
										document.getElementById("selective_booking").style.display = "block";
										document.getElementById("booking_enable_weekday").style.display = "none";
									}
									if (jQuery( "input[name='booking_recurring_booking']").attr("checked")) {
										document.getElementById("booking_enable_weekday").style.display = "block";
										document.getElementById("selective_booking").style.display = "none";
									}
									if ( jQuery( "input[name='booking_specific_booking']").attr("checked") && jQuery( "input[name='booking_recurring_booking']").attr("checked")) {
										document.getElementById("booking_enable_weekday").style.display = "block";
										document.getElementById("selective_booking").style.display = "block";
									}
									if ( !jQuery( "input[name='booking_specific_booking']").attr("checked") && !jQuery( "input[name='booking_recurring_booking']").attr("checked")) {
										document.getElementById("booking_enable_weekday").style.display = "none";
										document.getElementById("selective_booking").style.display = "none";
									}
								}
								</script>

				<div id="booking_enable_weekday" name="booking_enable_weekday" style="display:<?php echo $recurring_div_show; ?>;">
				<table class="form-table">
				<tr>
					<th>
					<label for="booking_enable_weekday_dates"> <b> <?php _e( 'Booking Days:', 'woocommerce-booking' );?> </b> </label>
					</th>
					<td>
					<fieldset class="days-fieldset">
							<legend><b>Days:</b></legend>
							<?php 
							$weekdays = bkap_get_book_arrays('weekdays');
							foreach ( $weekdays as $n => $day_name) {
								print('<input type="checkbox" name="'.$n.'" id="'.$n.'" />
								<label for="'.$day_name.'">'.$day_name.'</label>
								<br>');
							}?>
							</fieldset>
					<img class="help_tip" width="16" height="16" data-tip="<?php _e('Select Weekdays', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
					</td>
				</tr>
				</table>
				</div>
				
				<div id="selective_booking" name="selective_booking" style="display:<?php echo $specific_dates_div_show; ?>;">
				<table class="form-table">
				<script type="text/javascript">
							jQuery(document).ready(function() {
							var formats = ["d.m.y", "d-m-yyyy","MM d, yy"];
							jQuery("#booking_specific_date_booking").datepick({dateFormat: formats[1], multiSelect: 999, monthsToShow: 1, showTrigger: '#calImg'});
							});
				</script>
				<tr>
					<th>
					<label for="booking_specific_date_booking"><b><?php _e( 'Specific Date Booking:', 'woocommerce-booking');?></b></label>
					</th>
					<td>
					<textarea rows="4" cols="80" name="booking_specific_date_booking" id="booking_specific_date_booking"></textarea>
					<img class="help_tip" width="16" height="16" data-tip="<?php _e('Select the specific dates that you want to enable for booking', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" style="vertical-align:top;"/>
					</td>
				</tr>
				</table>
				</div>
				
				<table class="form-table">
				<tr>
					<th>
					<label for="booking_lockout_date"><b><?php _e( 'Lockout Date after X orders:', 'woocommerce-booking');?></b></label>
					</th>
					<td>
					<?php 
					$lockout_date = "";
					if ( isset($booking_settings['booking_date_lockout']) && $booking_settings['booking_date_lockout'] != "" ) {
						$lockout_date = $booking_settings['booking_date_lockout'];
                                                //sanitize_text_field( $lockout_date, true )
					} else {
						$lockout_date = "60";
					}
					?>
					<input type="text" name="booking_lockout_date" id="booking_lockout_date" value="<?php echo sanitize_text_field( $lockout_date, true );?>" >
					<img class="help_tip" width="16" height="16" data-tip="<?php _e('Set this field if you want to place a limit on maximum bookings on any given date. If you can manage up to 15 bookings in a day, set this value to 15. Once 15 orders have been booked, then that date will not be available for further bookings.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
					</td>
				</tr>
				<?php 
				do_action('bkap_before_minimum_days', $duplicate_of);?>
				<tr>
					<th>
					<label for="booking_minimum_number_days"><b><?php _e( 'Minimum Booking time (in days):', 'woocommerce-booking');?></b></label>
					</th>
					<td>
					<?php 
					$min_days = 0;
					if ( isset($booking_settings['booking_minimum_number_days']) && $booking_settings['booking_minimum_number_days'] != "" ) {
						$min_days = $booking_settings['booking_minimum_number_days'];
					}
					?>
					<input type="text" name="booking_minimum_number_days" id="booking_minimum_number_days" value="<?php echo sanitize_text_field( $min_days, true );?>" >
					<img class="help_tip" width="16" height="16" data-tip="<?php _e('Enable Booking after X number of days from current date. The customer can select a booking date that is available only after the minimum days that are entered here. For example, if you need 3 days advance notice for a booking, enter 3 here.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
					</td>
				</tr>
				<?php 
				do_action('bkap_before_number_of_dates', $duplicate_of);
				?>
				<tr>
					<th>
					<label for="booking_maximum_number_days"><b><?php _e( 'Number of Dates to choose:', 'woocommerce-booking');?></b></label>
					</th>
					<td>
					<?php 
					$max_date = "";
					if ( isset($booking_settings['booking_maximum_number_days']) && $booking_settings['booking_maximum_number_days'] != "" ) {
						$max_date = $booking_settings['booking_maximum_number_days'];
					} else {
						$max_date = "30";
					}		
					?>
					<input type="text" name="booking_maximum_number_days" id="booking_maximum_number_days" value="<?php echo sanitize_text_field( $max_date, true );?>" >
					<img class="help_tip" width="16" height="16" data-tip="<?php _e('The maximum number of booking dates you want to be available for your customers to choose from. For example, if you take only 2 months booking in advance, enter 60 here.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
					</td>
				</tr>
				<?php 
				do_action('bkap_before_purchase_without_date', $duplicate_of);
				?>
				<tr id="purchase_without_date" style="display:<?php echo $purchase_without_date?>;">
					<th>
					<label for="booking_purchase_without_date"><b><?php _e( 'Purchase without choosing a date:', 'woocommerce-booking');?></b></label>
					</th>
					<td>
					<?php 
					$date_show = '';
					if( isset($booking_settings['booking_purchase_without_date']) && $booking_settings['booking_purchase_without_date'] == 'on' ) {
						$without_date = 'checked';
					} else {
						$without_date = '';
					}
					?>
					<input type="checkbox" name="booking_purchase_without_date" id="booking_purchase_without_date" <?php echo $without_date; ?>> 
					<img style="margin-left:40px;"  class="help_tip" width="16" height="16" data-tip="<?php _e('Enables your customers to purchase without choosing a date. This is useful in cases where the customer wants to gift the item.');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" /><br>
					<i>Useful if you want your customers to be able to purchase the item without choosing the date or as a Gift. Select this option if you want the ADD TO CART button always visible on the product page.</i>
					</td>
				</tr>
				<?php 
				do_action('bkap_before_product_holidays', $duplicate_of);
				?>				
				<script type="text/javascript">
							jQuery(document).ready(function() {
							var formats = ["d.m.y", "d-m-yyyy","MM d, yy"];
							jQuery("#booking_product_holiday").datepick({dateFormat: formats[1], multiSelect: 999, monthsToShow: 1, showTrigger: '#calImg'});
							});
				</script>
				<tr>
					<th>
					<label for="booking_product_holiday"><b><?php _e( 'Select Holidays / Exclude Days / Black-out days:', 'woocommerce-booking');?></b></label>
					</th>
					<td>
					<?php 
					$product_holiday = "";
					if ( isset($booking_settings['booking_product_holiday']) && $booking_settings['booking_product_holiday'] != "" ) {
						$product_holiday = $booking_settings['booking_product_holiday'];
					}
					?>
					<textarea rows="4" cols="80" name="booking_product_holiday" id="booking_product_holiday"><?php echo $product_holiday; ?></textarea>
					<img class="help_tip" width="16" height="16" data-tip="<?php _e('Select dates for which the booking will be completely disabled only for this product.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" style="vertical-align:top;"/><br>
					<i>Please click on the date in calendar to add or delete the date from the holiday list.</i>
					</td>
				</tr>
				<?php 
				do_action('bkap_before_enable_time', $duplicate_of);
				?>
				<tr id="booking_time" style="display:<?php echo $booking_time_div; ?>;">
					<th>
					<label for="booking_enable_time" style="color: brown"><b><?php _e( 'Enable Booking Time:', 'woocommerce-booking');?></b></label>
					</th>
					<td>
					<?php 
					$enable_time = "";
					if( isset($booking_settings['booking_enable_time']) && $booking_settings['booking_enable_time'] == "on" ) {
						$enable_time = "checked";
					}
					?>
					<input type="checkbox" name="booking_enable_time" id="booking_enable_time" <?php echo $enable_time;?> onClick="bkap_timeslot(this)">
					<img class="help_tip" width="16" height="16" data-tip="<?php _e('Enable time (or time slots) on the product. Add any number of booking time slots once you have checked this.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" /><br>
					<i><font color="brown">Please select the checkbox to add Time Slots<br>
					You can manage the Time Slots using the "View/Delete Booking Dates, Time Slots" tab shown above, next to "Booking Options".</i></font>
					</td>
				</tr>
				<?php
					do_action('bkap_after_time_enabled',$duplicate_of);
				?>
				</table>
				<script type="text/javascript">
                                    /**************************************************
                                     * This function displays the Add Time slot button when Enable time slot setting is checked.
                                     *****************************************************/
					function bkap_timeslot(chk) {
						jQuery("#add_button").toggle();
						if ( !jQuery( "input[name='booking_enable_time']").attr("checked")) {
							document.getElementById("time_slot").style.display = "none";
                                                }
						if( jQuery( "input[name='booking_enable_time']").attr("checked")) {
							document.getElementById("time_slot").style.display = "block";
						}
					}
				</script>
					
				<div id="time_slot_empty" name="time_slot_empty" style="display:none;">
				<table class="form-table">
				<tr>
					<th>
					<label for="time_slot_label"><b><?php _e( 'Enter a Time Slot:')?></b></label>
					</th>
					<td>
					<b><?php _e( 'From: ', 'woocommerce-booking');?></b>
					<select name="booking_from_slot_hrs[0]" id="booking_from_slot_hrs[0]">
    				<?php
					for ($i=0;$i<24;$i++) {
						printf( "<option %s value='%s'>%s</option>\n",
						selected( $i, '', false ),
						esc_attr( $i ),
						$i
						);
                                        }
    				?>
    				</select> Hours
    				
    				<select name="booking_from_slot_min[0]" id="booking_from_slot_min[0]">
    				<?php
					for ($i=0;$i<60;$i++) {
                                            if ( $i < 10 ) {
                                            	$i = '0'.$i;
                                            }
						printf( "<option %s value='%s'>%s</option>\n",
						selected( $i, '', false ),
						esc_attr( $i ),
						$i
						);
                                        }
    				?>
    				</select> Minutes
    				&nbsp;&nbsp;&nbsp;
    				
    				<b><?php _e( 'To: ', 'woocommerce-booking');?></b>
					<select name="booking_to_slot_hrs[0]" id="booking_to_slot_hrs[0]">
    				<?php
					for ($i=0;$i<24;$i++) {
						printf( "<option %s value='%s'>%s</option>\n",
						selected( $i, '', false ),
						esc_attr( $i ),
						$i
						);
                                        }
    				?>
    				</select> Hours
    				
    				<select name="booking_to_slot_min[0]" id="booking_to_slot_min[0]">
    				<?php
					for ($i=0;$i<60;$i++) {
                                            if ( $i < 10 ) {
                                            	$i = '0'.$i;
                                            }
						printf( "<option %s value='%s'>%s</option>\n",
						selected( $i, '', false ),
						esc_attr( $i ),
						$i
						);
                                        }
    				?>
    				</select> Minutes
    				<br>
    				<i>If do not want a time range, please leave the To Hours and To Minutes unchanged (set to 0).</i><br><br/>
    				
    				<label for="booking_lockout_time"><b><?php _e( 'Lockout time slot after X orders:')?></b></label><br>
					<input type="text" name="booking_lockout_time[0]" id="booking_lockout_time[0]" value="30" />
					<input type="hidden" id="wapbk_slot_count" name="wapbk_slot_count" value="[0]" /><br>
					<i>Please enter a number to limit the number of bookings for this time slot. This time slot will be shown on the website <b>only when the lockout field value is greater than 0.</b></i><br>
					<br/>
					
					<label for="booking_global_check_lockout"><b><?php _e( 'Make Unavailable for other products once lockout is reached')?></b></label><br/>
					<input type="checkbox" name="booking_global_check_lockout[0]" id="booking_global_check_lockout[0]">
					<i>Please select this checkbox if you want this time slot to be unavailable for all products once the lockout is reached.</i>
					
    				<br/><br/>
    				<label for="booking_time_note"><b><?php _e('Note (optional)', 'woocommerce-booking')?></b></label><br>
    				<textarea class="short" name="booking_time_note[0]" id="booking_time_note[0]" rows="2" cols="50"></textarea>

    				</td>
					
				</tr>
				</table>
				</div>
				
				<div id="time_slot" name="time_slot">
				</div>
				
				<p>
				<div id="add_button" name="add_button" style="display:<?php echo $add_button_show; ?>;">
				<input type="button" class="button-primary" value="Add Time Slot" id="add_another" onclick="bkap_add_new_div('[0')">
				</div>
				</p>
				
				<!-- <input type="submit" name="save_booking" value="<?php _e('Save Booking', 'woocommerce-booking');?>" class="button-primary"> -->
				
				</div>
				
				<div id="listing_page" style="display:none;" >
				<table class='wp-list-table widefat fixed posts' cellspacing='0' id='list_bookings_specific'>
					<tr>
						<b>Specific Date Time Slots</b>
					</tr>
					<tr>
						<th> <?php _e('Day', 'woocommerce-booking');?> </th>
						<th> <?php _e('Start Time', 'woocommerce-booking');?> </th>
						<th> <?php _e('End Time', 'woocommerce-booking');?> </th>
						<th> <?php _e('Note', 'woocommerce-booking');?> </th>
						<th> <?php _e('Maximum Bookings', 'woocommerce-booking');?> </th>
						<th> <?php _e('Global Check', 'woocommerce_booking');?> </th>
						<?php print('<th> <a href="javascript:void(0);" id="'.$duplicate_of.'" class="remove_specific_data">Delete All </a> </th>');?>
					</tr>
					
					<?php 	
					$var = "";		
					//$prices = $booking_settings['booking_recurring_prices'];
					//print_r($prices);
					if ( isset($booking_settings['booking_time_settings']) && $booking_settings['booking_time_settings'] != '' ) :
					foreach( $booking_settings['booking_time_settings'] as $key => $value ) {
						if ( substr($key,0,7) != "booking" ) {
							$date_disp = $key;
							foreach( $value as $date_key => $date_value ) {
								print('<tr id="row_'.$date_key.'_'.$date_disp.'" >');
								print('<td> '.$date_disp.' </td>');
								print('<td> '.$date_value['from_slot_hrs'].':'.$date_value['from_slot_min'].' </td>');
								print('<td> '.$date_value['to_slot_hrs'].':'.$date_value['to_slot_min'].' </td>');
								//print('<td>  &nbsp; </td>');
								print('<td> '.$date_value['booking_notes'].' </td>');
								print('<td> '.$date_value['lockout_slot'].' </td>');
								print('<td> '.$date_value['global_time_check'].' </td>');
								print('<td> <a href="javascript:void(0);" id="'.$date_key.'&'.$duplicate_of.'&'.$date_disp.'&'.$date_value['from_slot_hrs'].':'.$date_value['from_slot_min'].'&'.$date_value['to_slot_hrs'].':'.$date_value['to_slot_min'].'" class="remove_time_data"> <img src="'.plugins_url().'/woocommerce-booking/images/delete.png" alt="Remove Time Slot" title="Remove Time Slot"></a> </td>');
								print('</tr>');
							}
							
						} elseif ( substr($key,0,7) == "booking" ) {
							$date_pass = $key;
							$weekdays = bkap_get_book_arrays('weekdays');
							$date_disp = $weekdays[$key];
							//$price = $prices[$key."_price"];
							foreach( $value as $date_key => $date_value ) {
								$global_time_check = '';
								if(isset($date_value['global_time_check']))
									$global_time_check = $date_value['global_time_check'];
								$var .= '<tr id="row_'.$date_key.'_'.$date_pass.'" >
								<td> '.$date_disp.' </td>
								<td> '.$date_value['from_slot_hrs'].':'.$date_value['from_slot_min'].' </td>
								<td> '.$date_value['to_slot_hrs'].':'.$date_value['to_slot_min'].' </td>
								<td> '.$date_value['booking_notes'].' </td>
								<td> '.$date_value['lockout_slot'].' </td>
								<td> '.$global_time_check.' </td>
								<td> <a href="javascript:void(0);" id="'.$date_key.'&'.$duplicate_of.'&'.$date_pass.'&'.$date_value['from_slot_hrs'].':'.$date_value['from_slot_min'].'&'.$date_value['to_slot_hrs'].':'.$date_value['to_slot_min'].'" class="remove_time_data"> <img src="'.plugins_url().'/woocommerce-booking/images/delete.png" alt="Remove Time Slot" title="Remove Time Slot"></a> </td>
								</tr>';
							}
						}
					}
					endif;
					if ( isset($booking_settings['booking_enable_multiple_day']) && $booking_settings['booking_enable_multiple_day'] != 'on' ) :
						$query = "SELECT * FROM `".$wpdb->prefix."booking_history`
						WHERE post_id='".$duplicate_of."' AND from_time='' AND to_time='' AND end_date='0000-00-00'";
						$results = $wpdb->get_results ( $query );
						
						foreach ( $results as $key => $value ) {
							if (substr($value->weekday, 0, 7) != "booking") {
								$date_key = date('j-n-Y',strtotime($value->start_date));
								print('<tr id="row_'.$date_key.'" >');
								print('<td> '.$date_key.' </td>');
								print('<td> &nbsp; </td>');
								print('<td> &nbsp; </td>');
								print('<td> &nbsp; </td>');
								print('<td> '.$value->total_booking.' </td>');
								print('<td> &nbsp; </td>');
								print('<td> <a href="javascript:void(0);" id="'.$date_key.'&'.$duplicate_of.'" class="remove_day_data"> <img src="'.plugins_url().'/woocommerce-booking/images/delete.png" alt="Remove Date" title="Remove Date"></a> </td>');
								print('</tr>');	
							} elseif (substr($value->weekday, 0, 7) == "booking" && $value->start_date == "0000-00-00") {
								$weekdays = bkap_get_book_arrays('weekdays');
								//$price = $prices[$value->weekday."_price"];
								$date_disp = $weekdays[$value->weekday];
								$var .= '<tr id="row_'.$value->weekday.'" >
									<td> '.$date_disp.' </td>
									<td>  &nbsp; </td>
									<td>  &nbsp; </td>
									<td>  &nbsp; </td>
									<td> '.$value->total_booking.' </td>
									<td>  &nbsp; </td>
								 	<td> <a href="javascript:void(0);" id="'.$value->weekday.'&'.$duplicate_of.'" class="remove_day_data"> <img src="'.plugins_url().'/woocommerce-booking/images/delete.png" alt="Remove Day" title="Remove Day"></a> </td>
									</tr>';
							}
						}
					endif;
					?>
				
				</table>
				
				<p>
				<table class='wp-list-table widefat fixed posts' cellspacing='0' id='list_bookings_recurring'>
					<tr>
						<b>Recurring Days Time Slots</b>
					</tr>
					<tr>
						<th> <?php _e('Day', 'woocommerce-booking');?> </th>
						<th> <?php _e('Start Time', 'woocommerce-booking');?> </th>
						<th> <?php _e('End Time', 'woocommerce-booking');?> </th>
						<th> <?php _e('Note', 'woocommerce-booking');?> </th> 
						<th> <?php _e('Maximum Bookings', 'woocommerce-booking');?> </th>
						<th> <?php _e('Global Check', 'woocommerce-booking');?>
						<?php print('<th> <a href="javascript:void(0);" id="'.$duplicate_of.'" class="remove_recurring_data"> Delete All </a> </th>');	?>
					</tr>
				<?php 
				if (isset($var)){
					echo $var;
				}
				?>
				</table>
				</p>
				</div>
				<?php 
					do_action('bkap_after_listing_enabled', $duplicate_of);
				?>
			<!--  	</form>  -->
				<?php
			}
			/****************************************************
                         * This function updates the booking settings for each product in the wp_postmeta table in the database . 
                         * It will be called when update / publish button clicked on admin side.
                         *****************************************************/
			function bkap_process_bookings_box( $post_id, $post ) {
				
				global $wpdb;
			
				//Save Email Reminders
		/*		$subject = '';
				if (isset($_POST["erem_subject"])) $subject = $_POST["erem_subject"];
				$message = '';//echo "<pre>";print_r(($_POST["erem_message"][0]));echo "</pre>";exit;
				if (isset($_POST["erem_message"])) $message = stripslashes($_POST["erem_message"][0]);//stripslashes($_POST["erem_message"])str_replace(array("\n", "\r"), '', $_POST["erem_message"]);
				$days = '';
				if (isset($_POST["erem_days"])) $days = $_POST["erem_days"];
				$hours =  '';
				if (isset($_POST["erem_hours"])) $hours = $_POST["erem_hours"];
				$minutes = '';
				if (isset($_POST["erem_minutes"])) $minutes = $_POST["erem_minutes"];
				$email = '';
				if (isset($_POST["erem_email"])) $email = $_POST["erem_email"];
				
				$all_reminders = array();
				
				$count = 0;
				if (isset($subject) && $subject != '')
				{
					foreach($subject as $v){
						if(trim($v) != "")
						{
							$total_minutes = $minutes[$count] + ($hours[$count] * 60) + (($days[$count] * 24 )*60);
							$all_reminders[$count] = array('subject' => $subject[$count],
									'message' => $message[$count],
									'days' => $days[$count],
									'hours' => $hours[$count],
									'minutes' => $minutes[$count],
									'total_minutes' => $total_minutes,
									'email' => $email[$count]);
								
							$count++;
						}
					}
				}
				//$all_reminders_str = str_replace('\r\n', "", json_encode($all_reminders));
				$sts = update_post_meta($post_id, 'woocommerce_booking_emailreminders', json_encode($all_reminders) );*/
				
				// Save Bookings
				$product_bookings = array();
				$duplicate_of = get_post_meta($post_id, '_icl_lang_duplicate_of', true);
				if($duplicate_of == '' && $duplicate_of == null) {
					$post_time = get_post($post_id);
					$id_query = "SELECT ID FROM `".$wpdb->prefix."posts` WHERE post_date = '".$post_time->post_date."' ORDER BY ID LIMIT 1";
					$results_post_id = $wpdb->get_results ( $id_query );
					if( isset($results_post_id) ) {
						$duplicate_of = $results_post_id[0]->ID;
					} else {
						$duplicate_of = $post_id;
					}
					//$duplicate_of = $item_value['product_id'];
				}
				$woo_booking_dates = get_post_meta($duplicate_of, 'woocommerce_booking_settings', true);
				//$booking_settings = get_post_meta($post_id, 'woocommerce_booking_settings', true);
				//print_r($woo_booking_dates);
				
				$enable_inline_calendar = $enable_date = $enable_multiple_day = $specific_booking_chk = $recurring_booking = "";
				
				if(isset($_POST['enable_inline_calendar'])) {
				
					$enable_inline_calendar = $_POST['enable_inline_calendar'];
                                }
				if (isset($_POST['booking_enable_date'])) {
					$enable_date = $_POST['booking_enable_date'];
				}
				
				if (isset($_POST['booking_enable_multiple_day'])) {
					$enable_multiple_day = $_POST['booking_enable_multiple_day'];
				}
				
				if (isset($_POST['booking_specific_booking'])) {
					$specific_booking_chk = $_POST['booking_specific_booking'];
				}
				
				$recurring_booking="";
				if (isset($_POST['booking_recurring_booking'])) {
					$recurring_booking = $_POST['booking_recurring_booking'];
				}
				 
				$booking_days = array();
				$new_day_arr = array();
				$weekdays = bkap_get_book_arrays('weekdays');
				foreach ($weekdays as $n => $day_name) {
					if ( isset($woo_booking_dates['booking_recurring']) && count($woo_booking_dates['booking_recurring']) > 1 ) {
						if ( isset($_POST[$n]) && $_POST[$n] == 'on' || isset($_POST[$n]) && $_POST[$n] == '') {
							$new_day_arr[$n] = $_POST[$n];
						}
						if ( isset($_POST[$n]) && $_POST[$n] == 'on' ) {
							$booking_days[$n] = $_POST[$n];
						} else {
							$booking_days[$n] = $woo_booking_dates['booking_recurring'][$n];
						}
					} else {
						if (isset($_POST[$n])) {
							$new_day_arr[$n] = $_POST[$n];
							$booking_days[$n] = $_POST[$n];
						} else $new_day_arr[$n] = $booking_days[$n] = '';
                                            }
					/*if ( isset($woo_booking_dates['booking_recurring_prices']) && count($woo_booking_dates['booking_recurring_prices']) > 1 )
					{
						if ( isset($_POST[$n."_price"]) && $_POST[$n."_price"] != '' )
						{
							$new_day_arr_price[$n."_price"] = $_POST[$n."_price"];
						}
						else 
						{
							$new_day_arr_price[$n."_price"] = $woo_booking_dates['booking_recurring_prices'][$n."_price"];
						}
					}
					else
					{
						if (isset($_POST[$n."_price"]))
						{
							$new_day_arr_price[$n."_price"] = $_POST[$n."_price"];
						}
						else $new_day_arr_price[$n."_price"] = '';
					}*/
				}

			 
				$specific_booking = '';
				if (isset($_POST['booking_specific_date_booking'])) {
					$specific_booking = $_POST['booking_specific_date_booking'];
				}
				if($specific_booking != '') {
					$specific_booking_dates = explode(",",$specific_booking);
                                }else{
					$specific_booking_dates = array();
                                }
				$specific_stored_days = array();
				if( isset($woo_booking_dates['booking_specific_date']) && count($woo_booking_dates['booking_specific_date']) > 0) $specific_stored_days = $woo_booking_dates['booking_specific_date'];
			
				foreach ( $specific_booking_dates as $key => $value ) {
					if (trim($value != "")) $specific_stored_days[] = $value;
				}
				if(isset($_POST['booking_minimum_number_days'])) {
			 		$minimum_number_days = $_POST['booking_minimum_number_days'];
                                }else {
					$minimum_number_days = '';
                                }
				if(isset($_POST['booking_maximum_number_days'])){
					$maximum_number_days = $_POST['booking_maximum_number_days'];
                                }else {
					$maximum_number_days = '';
                                }
				$without_date="";
				if (isset($_POST['booking_purchase_without_date'])) {
					$without_date = $_POST['booking_purchase_without_date'];
				}
				$lockout_date = '';
				if(isset($_POST['booking_lockout_date']))
					$lockout_date = $_POST['booking_lockout_date'];
				$product_holiday = '';
				if(isset($_POST['booking_product_holiday']))
					$product_holiday = $_POST['booking_product_holiday'];
			
				$enable_time = '';
				if (isset($_POST['booking_enable_time'])) {
					$enable_time = $_POST['booking_enable_time'];
				}
				$slot_count_value = '';
				if(isset($_POST['wapbk_slot_count'])) {
					$slot_count = explode("[", $_POST['wapbk_slot_count']);
					$slot_count_value = intval($slot_count[1]);
				}
				$date_time_settings = array();
				$time_settings = array();
				if( $specific_booking != "" ) {
					foreach ( $specific_booking_dates as $day_key => $day_value ) {
						$date_tmstmp = strtotime($day_value);
						$date_save = date('Y-m-d',$date_tmstmp);
							if (isset($_POST['booking_enable_time']) && $_POST['booking_enable_time'] == "on") {
								$j=1;
								if(isset($woo_booking_dates['booking_time_settings']) && is_array($woo_booking_dates['booking_time_settings'])) {
									if (array_key_exists($day_value,$woo_booking_dates['booking_time_settings'])) {
										foreach ( $woo_booking_dates['booking_time_settings'][$day_value] as $dtkey => $dtvalue ) {
											$date_time_settings[$day_value][$j] = $dtvalue;
											$j++;
										}
									}
								}
								$k = 1;
								for($i=($j + 1); $i<=($j + $slot_count_value); $i++) {
									if( isset($_POST['booking_from_slot_hrs'][$k]) && $_POST['booking_from_slot_hrs'][$k] != 0 ) {
										$time_settings['from_slot_hrs'] = $_POST['booking_from_slot_hrs'][$k];
										$time_settings['from_slot_min'] = $_POST['booking_from_slot_min'][$k];
										$time_settings['to_slot_hrs'] = $_POST['booking_to_slot_hrs'][$k];
										$time_settings['to_slot_min'] = $_POST['booking_to_slot_min'][$k];
										$time_settings['booking_notes'] = $_POST['booking_time_note'][$k];
										$time_settings['lockout_slot'] = $_POST['booking_lockout_time'][$k];
										if(isset($_POST['booking_global_check_lockout'][$k])) {
											$time_settings['global_time_check'] = $_POST['booking_global_check_lockout'][$k];
										} else {
											$time_settings['global_time_check'] = '';
										}
										$date_time_settings[$day_value][$i] = $time_settings;
										$from_time = $_POST['booking_from_slot_hrs'][$k].":".$_POST['booking_from_slot_min'][$k];
										$to_time = "";
										if(isset($_POST['booking_to_slot_hrs'][$k]) && $_POST['booking_to_slot_hrs'][$k] != 0 ) {
											$to_time = $_POST['booking_to_slot_hrs'][$k].":".$_POST['booking_to_slot_min'][$k];
										}

										$query_delete = "DELETE FROM `".$wpdb->prefix."booking_history`
													WHERE post_id = '".$duplicate_of."'
													AND start_date = '".$date_save."'
													AND from_time = ''
													AND to_time = ''";
										$wpdb->query($query_delete);
										$query_insert = "INSERT INTO `".$wpdb->prefix."booking_history`
													 (post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
													 VALUES (
													 '".$duplicate_of."',
													 '',
													 '".$date_save."',
													 '0000-00-00',
													 '".$from_time."',
													 '".$to_time."',
													 '".$_POST['booking_lockout_time'][$k]."',
													 '".$_POST['booking_lockout_time'][$k]."' )";
										$wpdb->query( $query_insert );
									}
									$k++;
								}
							} else {
								$query_delete = "DELETE FROM `".$wpdb->prefix."booking_history`
													WHERE post_id = '".$duplicate_of."'
													AND start_date = '".$date_save."'";
									$wpdb->query($query_delete);
								$query_insert = "INSERT INTO `".$wpdb->prefix."booking_history`
											(post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
											VALUES (
											'".$duplicate_of."',
											'',
											'".$date_save."',
											'0000-00-00',
											'',
											'',
											'".$_POST['booking_lockout_date']."',
											'".$_POST['booking_lockout_date']."' )";
								$wpdb->query( $query_insert );
							}
						
						}
					}
					if ( count($new_day_arr) >= 1 ) {
						foreach ( $new_day_arr as $wkey => $wvalue ) {
							if( $wvalue == 'on' ) {
								if (isset($_POST['booking_enable_time']) && $_POST['booking_enable_time'] == "on") {
									$j=1;
									if(isset($woo_booking_dates['booking_time_settings']) && is_array($woo_booking_dates['booking_time_settings'])) {
										if (array_key_exists($wkey,$woo_booking_dates['booking_time_settings'])) {
											foreach ( $woo_booking_dates['booking_time_settings'][$wkey] as $dtkey => $dtvalue ) {
												$date_time_settings[$wkey][$j] = $dtvalue;
												$j++;
											}
										}
									}
									$k = 1;
									for($i=($j + 1); $i<=($j + $slot_count_value); $i++) {
										if(isset($_POST['booking_from_slot_hrs'][$k]) && $_POST['booking_from_slot_hrs'][$k] != 0 ) {
											$time_settings['from_slot_hrs'] = $_POST['booking_from_slot_hrs'][$k];
											$time_settings['from_slot_min'] = $_POST['booking_from_slot_min'][$k];
											$time_settings['to_slot_hrs'] = $_POST['booking_to_slot_hrs'][$k];
											$time_settings['to_slot_min'] = $_POST['booking_to_slot_min'][$k];
											$time_settings['booking_notes'] = $_POST['booking_time_note'][$k];
											$time_settings['lockout_slot'] = $_POST['booking_lockout_time'][$k];
											if(isset($_POST['booking_global_check_lockout'][$k])) {
												$time_settings['global_time_check'] = $_POST['booking_global_check_lockout'][$k];
											} else {
												$time_settings['global_time_check'] = '';
											}
											$date_time_settings[$wkey][$i] = $time_settings;
											$from_time = $_POST['booking_from_slot_hrs'][$k].":".$_POST['booking_from_slot_min'][$k];
											$to_time = "";
											if(isset($_POST['booking_to_slot_hrs'][$k]) && $_POST['booking_to_slot_hrs'][$k] != 0 ) {
												$to_time = $_POST['booking_to_slot_hrs'][$k].":".$_POST['booking_to_slot_min'][$k];
											}
										
											$query_delete = "DELETE FROM `".$wpdb->prefix."booking_history`
													WHERE post_id = '".$duplicate_of."'
													AND weekday = '".$wkey."'
													AND from_time = ''
													AND to_time = ''";
											$wpdb->query($query_delete);
											
											$query_insert_week = "INSERT INTO `".$wpdb->prefix."booking_history`
														(post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
														VALUES (
														'".$duplicate_of."',
														'".$wkey."',
														'0000-00-00',
														'0000-00-00',
														'".$from_time."',
														'".$to_time."',
														'".$_POST['booking_lockout_time'][$k]."',
														'".$_POST['booking_lockout_time'][$k]."') ";
											$wpdb->query( $query_insert_week );
										}	
										$k++;	
									}
								} else {
									$query_delete = "DELETE FROM `".$wpdb->prefix."booking_history`
													WHERE post_id = '".$duplicate_of."'
													AND weekday = '".$wkey."'";
									$wpdb->query($query_delete);
									$query_insert_week = "INSERT INTO `".$wpdb->prefix."booking_history`
													(post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
													VALUES (
													'".$duplicate_of."',
													'".$wkey."',
													'0000-00-00',
													'0000-00-00',
													'',
													'',
													'".$_POST['booking_lockout_date']."',
													'".$_POST['booking_lockout_date']."') ";
									$wpdb->query( $query_insert_week );
								}
						
							}
						}
					}
				
					$new_time_settings = $woo_booking_dates;
					//if ( count($woo_booking_dates) > 1 )
					{
						foreach ( $date_time_settings as $dtkey => $dtvalue ) {
							$new_time_settings['booking_time_settings'][$dtkey] = $dtvalue;
						}
					}
					 
				//echo $enable_inline_calendar;exit;
				$booking_settings = array();
				$booking_settings['booking_enable_date'] = $enable_date;
				$booking_settings['enable_inline_calendar'] = $enable_inline_calendar;
				$booking_settings['booking_enable_multiple_day'] = $enable_multiple_day;
				$booking_settings['booking_specific_booking'] = $specific_booking_chk;
				$booking_settings['booking_recurring_booking'] = $recurring_booking;
				$booking_settings['booking_recurring'] = $booking_days;
				//$booking_settings['booking_recurring_prices'] = $new_day_arr_price;
				$booking_settings['booking_specific_date'] = $specific_stored_days;
				$booking_settings['booking_minimum_number_days'] = $minimum_number_days;
				$booking_settings['booking_maximum_number_days'] = $maximum_number_days;
				$booking_settings['booking_purchase_without_date'] = $without_date;
				$booking_settings['booking_date_lockout'] = $lockout_date;
				$booking_settings['booking_product_holiday'] = $product_holiday;
				$booking_settings['booking_enable_time'] = $enable_time;
				if (isset($new_time_settings['booking_time_settings'])) {
                                    $booking_settings['booking_time_settings'] = $new_time_settings['booking_time_settings'];
                                }else{ 
                                    $booking_settings['booking_time_settings'] = '';
                                }
				$booking_settings = (array) apply_filters( 'bkap_save_product_settings', $booking_settings, $duplicate_of );
				//echo "<pre>"; print_r($booking_settings); echo "</pre>"; exit;
				update_post_meta($duplicate_of, 'woocommerce_booking_settings', $booking_settings);
			}
			
                        /*********************************************
                         * This function returns the number of bookings done for a date.
                         *********************************************/
			function bkap_get_date_lockout($start_date) {
				global $wpdb,$post;
				$duplicate_of = get_post_meta($post->ID, '_icl_lang_duplicate_of', true);
				if($duplicate_of == '' && $duplicate_of == null) {
					$post_time = get_post($post->ID);
					$id_query = "SELECT ID FROM `".$wpdb->prefix."posts` WHERE post_date = '".$post_time->post_date."' ORDER BY ID LIMIT 1";
					$results_post_id = $wpdb->get_results ( $id_query );
					if( isset($results_post_id) ) {
						$duplicate_of = $results_post_id[0]->ID;
					} else {
						$duplicate_of = $post->ID;
					}
					//$duplicate_of = $item_value['product_id'];
				}
				$date_lockout = "SELECT sum(total_booking) - sum(available_booking) AS bookings_done FROM `".$wpdb->prefix."booking_history`
				WHERE start_date='".$start_date."' AND post_id='".$duplicate_of."'";
					//echo $date_lockout;
				$results_date_lock = $wpdb->get_results($date_lockout);
					//print_r($results_date_lock);
				$bookings_done = $results_date_lock[0]->bookings_done;
				return $bookings_done;
			}
                      
			/*function check_for_prices() {
				//echo "here";
				$booking_settings = get_post_meta($_POST['post_id'],'woocommerce_booking_settings',true);
				//$recurring_prices = $booking_settings['booking_recurring_prices'];
				$day_to_check = date("w",strtotime($_POST['current_date']));
				$price = $recurring_prices['booking_weekday_'.$day_to_check.'_price'];
				//echo "here".$price;
				if($price == '' || $price == 0){
					$product = get_product($_POST['post_id']);
					$product_type = $product->product_type;
					if ($product_type == 'variable'){
					//	print_r($_POST);
						$variation_id_to_fetch = $this->get_selected_variation_id($_POST['post_id'], $_POST);
						if ($variation_id_to_fetch != ""){
							$sale_price = get_post_meta( $variation_id_to_fetch, '_sale_price', true);
							if($sale_price == ''){
								$regular_price = get_post_meta( $variation_id_to_fetch, '_regular_price',true);
								echo $regular_price;
							} else{
								echo $sale_price;
							}
						} else echo "Please select an option."; 
					} elseif ($product_type == 'simple'){
						$sale_price = get_post_meta( $_POST['post_id'], '_sale_price', true);
						if($sale_price == '')
						{
							$regular_price = get_post_meta( $_POST['post_id'], '_regular_price',true);
							echo $regular_price;
						} else{
							echo $sale_price;
						}
					}
				} else {
					echo $price;
				}
				die();
			}
			*/
			/*****************************************
                         * This function updates the database for the booking details and add booking fields on the order received page,
                         *  and woocommerce edit order when order is placed for woocommerce version below 2.0.
                         *******************************************/
		/*	function bkap_add_order_item_meta( $item_meta, $cart_item ) {
					
				// Add the fields
				global $wpdb;
				
				$quantity = $cart_item['quantity'];
					
				$post_id = $cart_item['product_id'];
					
				if (isset($cart_item['booking'])) :
					
					foreach ($cart_item['booking'] as $booking) :
					
						$date_select = $booking['date'];
						$name = get_option('book.item-meta-date');
						$item_meta->add( $name, $date_select );

						if ($booking['time_slot'] != "") {
							$time_select = $booking['time_slot'];

							$saved_settings = json_decode(get_option('woocommerce_booking_global_settings'));
							$time_format = $saved_settings->booking_time_format;
							if ($time_format == "" OR $time_format == "NULL") $time_format = "12";
							$time_slot_to_display = $booking['time_slot'];
							if ($time_format == '12'){
								$time_exploded = explode("-", $time_slot_to_display);
								$from_time = date('h:i A', strtotime($time_exploded[0]));
								$to_time = date('h:i A', strtotime($time_exploded[1]));
								$time_slot_to_display = $from_time.' - '.$to_time;
							}
							
							$time_exploded = explode("-", $time_select);
							$name = get_option('book.item-meta-time');
							$item_meta->add( $name, $time_slot_to_display );
						}		
						$hidden_date = $booking['hidden_date'];
						$date_query = date('Y-m-d', strtotime($hidden_date));
							
						$query = "UPDATE `".$wpdb->prefix."booking_history`
							SET available_booking = available_booking - ".$quantity."
							WHERE post_id = '".$post_id."' AND
							start_date = '".$date_query."' AND
							from_time = '".trim($time_exploded[0])."' AND
							to_time = '".trim($time_exploded[1])."' ";
						$wpdb->query( $query );
					
						if (mysql_affected_rows($wpdb) == 0){
							$from_time = date('H:i', strtotime($time_exploded[0]));
							$to_time = date('H:i', strtotime($time_exploded[1]));
							$query = "UPDATE `".$wpdb->prefix."booking_history`
										SET available_booking = available_booking - ".$quantity."
										WHERE post_id = '".$post_id."' AND
										start_date = '".$date_query."' AND
										from_time = '".$from_time."' AND
										to_time = '".$to_time."' ";
											
							$wpdb->query( $query );
						}
						
					endforeach;
					
				endif;
			}*/
					                        /*****************************************************
                         * This function deletes a single time slot from View/Delete Booking date, Timeslots.
                         ******************************************************/
			function bkap_remove_time_slot() {
				
				global $wpdb;
				
				if(isset($_POST['details'])) {
					$details = explode("&", $_POST['details']);
				
				$date_delete = $details[2];
				$date_db = date('Y-m-d', strtotime($date_delete));
				$id_delete = $details[0];
				$book_details = get_post_meta($details[1], 'woocommerce_booking_settings', true);
				
				unset($book_details[booking_time_settings][$date_delete][$id_delete]);
				if( count($book_details[booking_time_settings][$date_delete]) == 0 ) {
					unset($book_details[booking_time_settings][$date_delete]);
					if ( substr($date_delete,0,7) == "booking" ) {
						$book_details[booking_recurring][$date_delete] = '';
					} elseif ( substr($date_delete,0,7) != "booking" ) {
						$key_date = array_search($date_delete, $book_details[booking_specific_date]);
						unset($book_details[booking_specific_date][$key_date]);
					}
				}
				update_post_meta($details[1], 'woocommerce_booking_settings', $book_details);
			
				if ( substr($date_delete,0,7) != "booking" ) {
					if ($details[4] == "0:00") $details[4] = "";
					$delete_query = "DELETE FROM `".$wpdb->prefix."booking_history`
								 WHERE
								 post_id = '".$details[1]."' AND
								 start_date = '".$date_db."' AND
								 from_time = '".$details[3]."' AND
								 to_time = '".$details[4]."' ";
					//echo $delete_query;
					$wpdb->query($delete_query);
					
					if ($details[3] != "") $from_time = date('h:i A', strtotime($details[3]));
					if ($details[4] != "") $to_time = date('h:i A', strtotime($details[4]));

					$delete_query = "DELETE FROM `".$wpdb->prefix."booking_history`
								WHERE
								post_id = '".$details[1]."' AND
								start_date = '".$date_db."' AND
								from_time = '".$from_time."' AND
								to_time = '".$to_time."' ";
								
					$wpdb->query($delete_query);
						
				}elseif ( substr($date_delete,0,7) == "booking" ) {
					if ($details[4] == "0:00") $details[4] = "";
					$delete_query = "DELETE FROM `".$wpdb->prefix."booking_history`
								 WHERE
								 post_id = '".$details[1]."' AND
								 weekday = '".$date_delete."' AND
								 from_time = '".$details[3]."' AND
								 to_time = '".$details[4]."' ";
					//echo $delete_query;
					$wpdb->query($delete_query);
					
					if ($details[3] != ""){
                                            $from_time = date('h:i A', strtotime($details[3]));
                                        }
					if ($details[4] != "") {
                                            $to_time = date('h:i A', strtotime($details[4]));
                                        }
					$delete_query = "DELETE FROM `".$wpdb->prefix."booking_history`
								WHERE
								post_id = '".$details[1]."' AND
								weekday = '".$date_delete."' AND
								from_time = '".$from_time."' AND
								to_time = '".$to_time."' ";
								
					$wpdb->query($delete_query);
				}
                            }
				
			}
			/************************************************
                         * This function deletes a single day from View/Delete Booking date, Timeslots.
                         ************************************************/
			function bkap_remove_day() {
			
				global $wpdb;
			
				if(isset($_POST['details'])) {
				$details = explode("&", $_POST['details']);
				$date_delete = $details[0];
				$book_details = get_post_meta($details[1], 'woocommerce_booking_settings', true);
				
				if ( substr($date_delete,0,7) != "booking" ) {
					$date_db = date('Y-m-d', strtotime($date_delete));
					
					$key_date = array_search($date_delete, $book_details[booking_specific_date]);
					unset($book_details[booking_specific_date][$key_date]);
					
					$delete_query = "DELETE FROM `".$wpdb->prefix."booking_history`
									WHERE
									post_id = '".$details[1]."' AND
									start_date = '".$date_db."' ";
					//echo $delete_query;
					$wpdb->query($delete_query);
						
				} elseif ( substr($date_delete,0,7) == "booking" ) {
					$book_details[booking_recurring][$date_delete] = '';
					$delete_query = "DELETE FROM `".$wpdb->prefix."booking_history`
									WHERE
									post_id = '".$details[1]."' AND
									weekday = '".$date_delete."' ";
					//echo $delete_query;
					$wpdb->query($delete_query);
						
				}
				update_post_meta($details[1], 'woocommerce_booking_settings', $book_details);
				}
			}
		/********************************************************
                 * This function deletes all dates from View/Delete Booking date, Timeslots of specific day method.
                 ********************************************************/	
		function bkap_remove_specific() {
				
				global $wpdb;
				
				if(isset($_POST['details'])) {
				$details = $_POST['details'];
				$book_details = get_post_meta($details, 'woocommerce_booking_settings', true);
			
				foreach( $book_details[booking_specific_date] as $key => $value ) {
					if (array_key_exists($value,$book_details[booking_time_settings])) unset($book_details[booking_time_settings][$value]);
				}
				unset($book_details[booking_specific_date]);
				update_post_meta($details, 'woocommerce_booking_settings', $book_details);

				$delete_query = "DELETE FROM `".$wpdb->prefix."booking_history`
								 WHERE
								 post_id = '".$details."' AND
								 weekday = '' ";
				//echo $delete_query;
				$wpdb->query($delete_query);
				}
			}
			/**********************************************************************
                         * This function deletes all Days from View/Delete Booking date, Timeslots of specific day method.
                         ************************************************************************/
			function bkap_remove_recurring() {
			
			
				global $wpdb;
				
				if(isset($_POST['details'])) {
				$details = $_POST['details'];
				$book_details = get_post_meta($details, 'woocommerce_booking_settings', true);
				$weekdays = bkap_get_book_arrays('weekdays');
				foreach ($weekdays as $n => $day_name) {
					if (array_key_exists($n,$book_details[booking_time_settings])) unset($book_details[booking_time_settings][$n]);
					$book_details[booking_recurring][$n] = '';
					$delete_query = "DELETE FROM `".$wpdb->prefix."booking_history`
									WHERE
									post_id = '".$details."' AND
									weekday = '".$n."' ";
					$wpdb->query($delete_query);
				}
				
				update_post_meta($details, 'woocommerce_booking_settings', $book_details);
				}
			
			}
                  
		}		
	}
	
	$woocommerce_booking = new woocommerce_booking();
	
}
