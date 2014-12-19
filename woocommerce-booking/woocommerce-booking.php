<?php 
/*
Plugin Name: WooCommerce Booking & Appointment Plugin
Plugin URI: http://www.tychesoftwares.com/store/premium-plugins/woocommerce-booking-plugin
Description: This plugin lets you capture the Booking Date & Booking Time for each product thereby allowing your WooCommerce store to effectively function as a Booking system. It allows you to add different time slots for different days, set maximum bookings per time slot, set maximum bookings per day, set global & product specific holidays and much more.
Version: 2.0
Author: Ashok Rane
Author URI: http://www.tychesoftwares.com/
*/

/*require 'plugin-updates/plugin-update-checker.php';
$ExampleUpdateChecker = new PluginUpdateChecker(
	'http://www.tychesoftwares.com/plugin-updates/woocommerce-booking-plugin/info.json',
	__FILE__
);*/

global $BookUpdateChecker;
$BookUpdateChecker = '2.0';

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
		'version' 	=> '2.0', 		// current version number
		'license' 	=> $license_key, 	// license key (used get_option above to retrieve from DB)
		'item_name' => EDD_SL_ITEM_NAME_BOOK, 	// name of this plugin
		'author' 	=> 'Ashok Rane'  // author of this plugin
)
);

include_once('lang.php');
include_once('bkap-config.php');
include_once('bkap-common.php');
include_once('availability-search.php');
include_once('price-by-range.php');
include_once('fixed-block.php');
include_once('admin-bookings.php');
include_once('validation.php');
include_once('checkout.php');
include_once('cart.php');
include_once('ics.php');
include_once('cancel-order.php');
include_once('booking-process.php');
include_once('global-menu.php');
include_once('booking-box.php');
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

function is_booking_active()
{
	if (is_plugin_active('woocommerce-booking/woocommerce-booking.php')) {
		return true;
	}
	else {
		return false;
	}
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
				
				// Initialize settings
				register_activation_hook( __FILE__, array(&$this, 'bkap_bookings_activate'));
				add_action( 'plugins_loaded', array(&$this, 'bkap_bookings_update_db_check'));
				// Ajax calls
				add_action('init', array(&$this, 'bkap_book_load_ajax'));
				// WordPress Administration Menu
				add_action('admin_menu', array('global_menu', 'bkap_woocommerce_booking_admin_menu'));
				
				// Display Booking Box on Add/Edit Products Page
				add_action('add_meta_boxes', array('bkap_booking_box_class', 'bkap_booking_box'));
				
				// Processing Bookings
				add_action('woocommerce_process_product_meta', array('bkap_booking_box_class', 'bkap_process_bookings_box'), 1, 2);
				
				// Scripts
				add_action( 'admin_enqueue_scripts', array(&$this, 'bkap_my_enqueue_scripts_css' ));
				add_action( 'admin_enqueue_scripts', array(&$this, 'bkap_my_enqueue_scripts_js' ));
				
				add_action( 'woocommerce_before_single_product', array(&$this, 'bkap_front_side_scripts_js'));
				add_action( 'woocommerce_before_single_product', array(&$this, 'bkap_front_side_scripts_css'));
				
				// Display on Products Page
				add_action( 'woocommerce_before_add_to_cart_form', array('bkap_booking_process', 'bkap_before_add_to_cart'));
				add_action( 'woocommerce_before_add_to_cart_button', array('bkap_booking_process', 'bkap_booking_after_add_to_cart'));
			
				add_action('wp_ajax_bkap_remove_time_slot', array(&$this, 'bkap_remove_time_slot'));
				add_action('wp_ajax_bkap_remove_day', array(&$this, 'bkap_remove_day'));
				add_action('wp_ajax_bkap_remove_specific', array(&$this, 'bkap_remove_specific'));
				add_action('wp_ajax_bkap_remove_recurring', array(&$this, 'bkap_remove_recurring'));
				
				add_filter('woocommerce_add_cart_item_data', array('bkap_cart', 'bkap_add_cart_item_data'), 11, 2);
				add_filter('woocommerce_get_cart_item_from_session', array('bkap_cart', 'bkap_get_cart_item_from_session'), 11, 2);
				add_filter( 'woocommerce_get_item_data', array('bkap_cart', 'bkap_get_item_data_booking'), 10, 2 );
				
				if (isset($booking_settings['booking_enable_multiple_day']) && $booking_settings['booking_enable_multiple_day'] == 'on') {
					add_filter( 'woocommerce_add_cart_item', array('bkap_cart', 'bkap_add_cart_item'), 10, 1 );
				}
				add_action( 'woocommerce_checkout_update_order_meta', array('bkap_checkout', 'bkap_order_item_meta'), 10, 2);
				add_action('woocommerce_before_checkout_process', array('bkap_validation', 'bkap_quantity_check'));
				add_filter( 'woocommerce_add_to_cart_validation', array('bkap_validation', 'bkap_get_validate_add_cart_item'), 10, 3 );
				add_action('woocommerce_order_status_cancelled' , array('bkap_cancel_order','bkap_woocommerce_cancel_order'),10,1);
				add_action('woocommerce_order_status_refunded' , array('bkap_cancel_order','bkap_woocommerce_cancel_order'),10,1);
				add_action('woocommerce_duplicate_product' , array(&$this,'bkap_product_duplicate'),10,2);
				add_action('woocommerce_check_cart_items', array('bkap_validation','bkap_quantity_check'));
				
				//Export date to ics file from order received page
				$saved_settings = json_decode(get_option('woocommerce_booking_global_settings'));
				if (isset($saved_settings->booking_export) && $saved_settings->booking_export == 'on') {
					add_filter('woocommerce_order_details_after_order_table', array('bkap_ics', 'bkap_export_to_ics'), 10, 3 );
				}
				
				//Add order details as an attachment
				if (isset($saved_settings->booking_attachment) && $saved_settings->booking_attachment == 'on') {
					add_filter('woocommerce_email_attachments', array('bkap_ics','bkap_email_attachment'), 10, 3 );
				}
				
				add_action('admin_init', array('bkap_license', 'bkap_edd_sample_register_option'));
				add_action('admin_init', array('bkap_license', 'bkap_edd_sample_deactivate_license'));
				add_action('admin_init', array('bkap_license', 'bkap_edd_sample_activate_license'));	
				add_filter('woocommerce_my_account_my_orders_actions', array('bkap_cancel_order', 'bkap_get_add_cancel_button'), 10, 3 );
				add_filter('add_to_cart_fragments', array('bkap_cart', 'bkap_woo_cart_widget_subtotal'));
			}
			
			/***************************************************************** 
            * This function is used to load ajax functions required by plugin.
            *******************************************************************/
			function bkap_book_load_ajax() {
				if ( !is_user_logged_in() ){
					add_action('wp_ajax_nopriv_bkap_get_per_night_price', array('bkap_booking_process', 'bkap_get_per_night_price'));
					add_action('wp_ajax_nopriv_bkap_check_for_time_slot', array('bkap_booking_process', 'bkap_check_for_time_slot'));
					add_action('wp_ajax_bkap_nopriv_insert_date', array('bkap_booking_process', 'bkap_insert_date'));
					add_action('wp_ajax_nopriv_bkap_call_addon_price', array('bkap_booking_process', 'bkap_call_addon_price'));
					add_action('wp_ajax_nopriv_bkap_js', array('bkap_booking_process', 'bkap_js'));		
				} else{
					add_action('wp_ajax_bkap_get_per_night_price', array('bkap_booking_process', 'bkap_get_per_night_price'));
					add_action('wp_ajax_bkap_check_for_time_slot', array('bkap_booking_process', 'bkap_check_for_time_slot'));
					add_action('wp_ajax_bkap_insert_date', array('bkap_booking_process', 'bkap_insert_date'));
					add_action('wp_ajax_bkap_call_addon_price', array('bkap_booking_process', 'bkap_call_addon_price'));
					add_action('wp_ajax_bkap_js', array('bkap_booking_process', 'bkap_js'));
				}
			}
                        
            /************************************************
            * This function duplicates the booking settings 
            * of the original product to the new product.
            ************************************************/ 
            function bkap_product_duplicate($new_id, $post) {
				global $wpdb;
				$old_id = $post->ID;
				$duplicate_query = "SELECT * FROM `".$wpdb->prefix."booking_history` WHERE post_id = %d";
				$results_date = $wpdb->get_results ( $wpdb->prepare($duplicate_query,$old_id) );
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
				do_action('bkap_product_addon_duplicate',$new_id,$old_id);
			}
			/***************************************************************
            *  This function is executed when the plugin is updated using 
            *  the Automatic Updater. It calls the bookings_activate function 
            *  which will check the table structures for the plugin and 
            *  make any changes if necessary.
            ***************************************************************/
			function bkap_bookings_update_db_check() {
				global $booking_plugin_version, $BookUpdateChecker;
		
				$booking_plugin_version = $BookUpdateChecker;
				
				if ($booking_plugin_version == "2.0") {
					$this->bkap_bookings_activate();
				}
			}
			
            /************************************************************
            * This function detects when the booking plugin is activated 
            * and creates all the tables necessary in database,
            * if they do not exists. 
            ************************************************************/
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
				update_option('woocommerce_booking_db_version','2.0');
			
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
			
            /*********************************************
            * This function returns the number of bookings done for a date.
            *********************************************/
			function bkap_get_date_lockout($start_date) {
				global $wpdb,$post;
				$duplicate_of = bkap_common::bkap_get_product_id($post->ID);
				
				$date_lockout = "SELECT sum(total_booking) - sum(available_booking) AS bookings_done FROM `".$wpdb->prefix."booking_history`
								WHERE start_date= %s AND post_id= %d";
					
				$results_date_lock = $wpdb->get_results($wpdb->prepare($date_lockout,$start_date,$duplicate_of));
					
				$bookings_done = $results_date_lock[0]->bookings_done;
				return $bookings_done;
			}
                      
			/*****************************************************
            * This function deletes a single time slot from 
            * View/Delete Booking date, Timeslots.
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
            * This function deletes a single day from 
            * View/Delete Booking date, Timeslots.
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
		/*************************************************************************
        * This function deletes all dates from View/Delete Booking date, Timeslots
        * of specific day method.
        *************************************************************************/	
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
				$wpdb->query($delete_query);
				}
			}
			/**********************************************************************
            * This function deletes all Days from View/Delete Booking date, Timeslots 
            * of specific day method.
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
