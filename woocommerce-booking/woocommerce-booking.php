<?php 
/*
Plugin Name: WooCommerce Booking & Appointment Plugin
Plugin URI: http://www.tychesoftwares.com/store/premium-plugins/woocommerce-booking-plugin
Description: This plugin lets you capture the Booking Date & Booking Time for each product thereby allowing your WooCommerce store to effectively function as a Booking system. It allows you to add different time slots for different days, set maximum bookings per time slot, set maximum bookings per day, set global & product specific holidays and much more.
Version: 2.5.3
Author: Tyche Softwares
Author URI: http://www.tychesoftwares.com/
*/

global $BookUpdateChecker;
$BookUpdateChecker = '2.5.3';

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
		'version' 	=> '2.5.3', 		// current version number
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
include_once('special-booking-price.php');
include_once('admin-bookings.php');
include_once('validation.php');
include_once('checkout.php');
include_once('cart.php');
include_once('ics.php');
include_once('cancel-order.php');
include_once('booking-process.php');
include_once('global-menu.php');
include_once('booking-box.php');
include_once('timeslot-price.php');
include_once( 'booking-confirmation.php' );
include_once( 'class-booking-email-manager.php' );
include_once( 'variation-lockout.php' );
include_once( 'attribute-lockout.php' );
include_once( 'bkap-calendar-sync.php' );

register_uninstall_hook( __FILE__, 'bkap_woocommerce_booking_delete' );

/**
 * This function will Delete all the records of booking plugin and
 *  wp_booking _history table from the database if the plugin is uninstalled. 
 */
function bkap_woocommerce_booking_delete(){
	
	global $wpdb;
	$table_name_booking_history                = $wpdb->prefix . "booking_history";
	$sql_table_name_booking_history            = "DROP TABLE " . $table_name_booking_history ;
	
	$table_name_order_history                  = $wpdb->prefix . "booking_order_history";
	$sql_table_name_order_history              = "DROP TABLE " . $table_name_order_history ;

	$table_name_booking_block_price            = $wpdb->prefix . "booking_block_price_meta";
	$sql_table_name_booking_block_price        = "DROP TABLE " . $table_name_booking_block_price ;

	$table_name_booking_block_attribute        = $wpdb->prefix . "booking_block_price_attribute_meta";
	$sql_table_name_booking_block_attribute    = "DROP TABLE " . $table_name_booking_block_attribute ;

	$table_name_block_booking                  = $wpdb->prefix . "booking_fixed_blocks";
	$sql_table_name_block_booking              = "DROP TABLE " . $table_name_block_booking;

	$table_name_booking_variable_lockout       = $wpdb->prefix . "booking_variation_lockout_history";
	$sql_table_name_booking_variable_lockout   = "DROP TABLE " . $table_name_booking_variable_lockout;
	
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$wpdb->get_results( $sql_table_name_booking_history );
	$wpdb->get_results( $sql_table_name_order_history );
	$wpdb->get_results( $sql_table_name_booking_block_price );
	$wpdb->get_results( $sql_table_name_booking_block_attribute );
	$wpdb->get_results( $sql_table_name_block_booking );
	$wpdb->get_results( $sql_table_name_booking_variable_lockout );
	
	$sql_table_post_meta                       = "DELETE FROM `".$wpdb->prefix."postmeta` WHERE meta_key='woocommerce_booking_settings'";
	$results                                   = $wpdb->get_results ( $sql_table_post_meta );
	
	$sql_table_option                          = "DELETE FROM `".$wpdb->prefix."options` WHERE option_name='woocommerce_booking_global_settings'";
	$results                                   = $wpdb->get_results ( $sql_table_option );
}

function is_booking_active() {
	
    if ( is_plugin_active( 'woocommerce-booking/woocommerce-booking.php' ) ) {
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
	//load_plugin_textdomain('woocommerce-booking', false, dirname( plugin_basename( __FILE__ ) ) . '/');
    // For language translation
    function  bkap_update_po_file(){
        $domain = 'woocommerce-booking';
        $locale = apply_filters( 'plugin_locale', get_locale(), $domain );
        
        if ( $loaded = load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '-' . $locale . '.mo' ) ) {
            return $loaded;
        } else {
            load_plugin_textdomain( $domain, FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
        }
    }
    
	/**
	 * woocommerce_booking class
	 **/
	if ( !class_exists( 'woocommerce_booking' ) ) {

		class woocommerce_booking {
			
			public function __construct() {
				
			    define( 'BKAP_BOOKINGS_TEMPLATE_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/templates/' );
			    
				// Initialize settings
				register_activation_hook( __FILE__,                     array( &$this, 'bkap_bookings_activate' ) );
				//Add plugin doc and forum link in description
				add_filter( 'plugin_row_meta',                          array( &$this, 'bkap_plugin_row_meta' ), 10, 2 );				
				add_action( 'admin_init',                               array( &$this, 'bkap_bookings_update_db_check' ) );				
				// Ajax calls
				add_action( 'init',                                     array( &$this, 'bkap_book_load_ajax' ) );
				// WordPress Administration Menu
				add_action( 'admin_menu',                               array('global_menu', 'bkap_woocommerce_booking_admin_menu' ) );				
				// Display Booking Box on Add/Edit Products Page
				add_action( 'add_meta_boxes',                           array( 'bkap_booking_box_class', 'bkap_booking_box' ), 10 );				
				// Processing Bookings
				add_action( 'woocommerce_process_product_meta',         array( 'bkap_booking_box_class', 'bkap_process_bookings_box' ), 1, 2 );				
				// Vertical tabs
				add_action( 'admin_head',                               array( $this, 'bkap_vertical_my_enqueue_scripts_css' ) );
				add_action( 'admin_footer',                             array( 'bkap_booking_box_class', 'bkap_print_js' ) );				
				// Scripts
				add_action( 'admin_enqueue_scripts',                    array(&$this, 'bkap_my_enqueue_scripts_css' ) );
				add_action( 'admin_enqueue_scripts',                    array(&$this, 'bkap_my_enqueue_scripts_js' ) );				
				add_action( 'woocommerce_before_single_product',        array( &$this, 'bkap_front_side_scripts_js' ) );
				add_action( 'woocommerce_before_single_product',        array( &$this, 'bkap_front_side_scripts_css' ) );
				
				//Language Translation
				add_action( 'init', 'bkap_update_po_file' );				
				// Display on Products Page
				add_action( 'woocommerce_before_add_to_cart_form',      array( 'bkap_booking_process', 'bkap_before_add_to_cart' ) );
				add_action( 'woocommerce_before_add_to_cart_button',    array( 'bkap_booking_process', 'bkap_booking_after_add_to_cart' ) );			
				add_action( 'wp_ajax_bkap_remove_time_slot',            array( &$this, 'bkap_remove_time_slot' ) );
				add_action( 'wp_ajax_bkap_remove_day',                  array( &$this, 'bkap_remove_day' ) );
				add_action( 'wp_ajax_bkap_remove_specific',             array( &$this, 'bkap_remove_specific' ) );
				add_action( 'wp_ajax_bkap_remove_recurring',            array( &$this, 'bkap_remove_recurring' ) );				
				add_filter( 'woocommerce_add_cart_item_data',           array( 'bkap_cart', 'bkap_add_cart_item_data' ), 25, 2);
				add_filter( 'woocommerce_get_cart_item_from_session',   array( 'bkap_cart', 'bkap_get_cart_item_from_session' ), 25, 2);
				add_filter( 'woocommerce_get_item_data',                array( 'bkap_cart', 'bkap_get_item_data_booking' ), 25, 2 );
				
				if ( isset( $booking_settings['booking_enable_multiple_day'] ) && $booking_settings['booking_enable_multiple_day'] == 'on' ) {
					add_filter( 'woocommerce_add_cart_item',            array( 'bkap_cart', 'bkap_add_cart_item' ), 10, 1 );
				}
				
				add_action( 'woocommerce_checkout_update_order_meta',   array( 'bkap_checkout', 'bkap_order_item_meta' ), 10, 2);
				add_action( 'woocommerce_before_checkout_process',      array( 'bkap_validation', 'bkap_quantity_check' ) );
				add_filter( 'woocommerce_add_to_cart_validation',       array( 'bkap_validation', 'bkap_get_validate_add_cart_item' ), 10, 3 );
				add_action( 'woocommerce_order_status_cancelled' ,      array( 'bkap_cancel_order', 'bkap_woocommerce_cancel_order' ), 10, 1 );
				add_action( 'woocommerce_order_status_refunded' ,       array( 'bkap_cancel_order', 'bkap_woocommerce_cancel_order' ), 10, 1 );
				add_action( 'woocommerce_duplicate_product' ,           array( &$this, 'bkap_product_duplicate' ), 10, 2 );
				add_action( 'woocommerce_check_cart_items',             array( 'bkap_validation', 'bkap_quantity_check' ) );
				
				//Export date to ics file from order received page
				$saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
				
				if ( isset( $saved_settings->booking_export ) && $saved_settings->booking_export == 'on' ) {
					add_filter( 'woocommerce_order_details_after_order_table', array( 'bkap_ics', 'bkap_export_to_ics' ), 10, 3 );
				}
				
				//Add order details as an attachment
				if ( isset( $saved_settings->booking_attachment ) && $saved_settings->booking_attachment == 'on' ) {
					add_filter( 'woocommerce_email_attachments', array( 'bkap_ics', 'bkap_email_attachment' ), 10, 3 );
				}
				
				add_action( 'admin_init',                               array( 'bkap_license', 'bkap_edd_sample_register_option' ) );
				add_action( 'admin_init',                               array( 'bkap_license', 'bkap_edd_sample_deactivate_license' ) );
				add_action( 'admin_init',                               array( 'bkap_license', 'bkap_edd_sample_activate_license' ) );	
				add_filter( 'woocommerce_my_account_my_orders_actions', array( 'bkap_cancel_order', 'bkap_get_add_cancel_button' ), 10, 3 );
				add_filter( 'add_to_cart_fragments',                    array( 'bkap_cart', 'bkap_woo_cart_widget_subtotal' ) );
				// Hide the hardcoded item meta records frm being displayed on the admin orders page
				add_filter( 'woocommerce_hidden_order_itemmeta',        array( 'bkap_checkout', 'bkap_hidden_order_itemmeta'), 10, 1 );
				// Translating Block name of Fixed Block booking.
				add_action('admin_init',                                array( &$this, 'bkap_register_fixed_block_string_for_wpml') );
				// Gcal Settings tab
				add_action( 'admin_init',                               array( 'global_menu', 'bkap_gcal_settings' ), 10 );
			}
			
			/**
			 * Show row meta on the plugin screen.
			 *
			 * @param	mixed $links Plugin Row Meta
			 * @param	mixed $file  Plugin Base file
			 * @return	array
			 */
			public static function bkap_plugin_row_meta( $links, $file ) {
			    $plugin_base_name = plugin_basename(__FILE__);
			    
			    if ( $file == $plugin_base_name ) {
			        $row_meta = array(
			            'docs'    => '<a href="' . esc_url( apply_filters( 'woocommerce_booking_and_appointment_docs_url', 'https://www.tychesoftwares.com/woocommerce-booking-plugin-documentation/' ) ) . '" title="' . esc_attr( __( 'View WooCommerce Booking & Appointment Documentation', 'woocommerce-booking' ) ) . '">' . __( 'Docs', 'woocommerce-booking' ) . '</a>',
			            'support' => '<a href="' . esc_url( apply_filters( 'woocommerce_booking_and_appointment_support_url', 'https://www.tychesoftwares.com/forums/forum/woocommerce-booking-appointment-plugin/' ) ) . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'woocommerce-booking' ) ) . '">' . __( 'Support Forums', 'woocommerce-booking' ) . '</a>',
			        );
			        	
			        return array_merge( $links, $row_meta );
			    }
			    	
			    return (array) $links;
			}
			
		   /**
            * This function is used to load ajax functions required by plugin.
            */
			function bkap_book_load_ajax() {
				
			    if ( !is_user_logged_in() ){
					add_action( 'wp_ajax_nopriv_bkap_get_per_night_price',         array( 'bkap_booking_process', 'bkap_get_per_night_price' ) );
					add_action( 'wp_ajax_nopriv_bkap_check_for_time_slot',         array( 'bkap_booking_process', 'bkap_check_for_time_slot' ) );
					add_action( 'wp_ajax_nopriv_bkap_insert_date',                 array( 'bkap_booking_process', 'bkap_insert_date' ) );
					add_action( 'wp_ajax_nopriv_bkap_call_addon_price',            array( 'bkap_booking_process', 'bkap_call_addon_price' ) );
					add_action( 'wp_ajax_nopriv_bkap_js',                          array( 'bkap_booking_process', 'bkap_js' ) );
					add_action( 'wp_ajax_nopriv_bkap_get_date_lockout',            array( 'bkap_booking_process', 'bkap_get_date_lockout' ) );
					add_action( 'wp_ajax_nopriv_bkap_get_time_lockout',            array( 'bkap_booking_process', 'bkap_get_time_lockout' ) );
					add_action( 'wp_ajax_nopriv_save_widget_dates',                array( 'Custom_WooCommerce_Widget_Product_Search', 'save_widget_dates' ) );
					add_action( 'wp_ajax_nopriv_bkap_booking_calender_content',    array( &$this, 'bkap_booking_calender_content' ) );
					add_action( 'wp_ajax_nopriv_bkap_get_fixed_block_inline_date', array( 'bkap_booking_process', 'bkap_get_fixed_block_inline_date' ) );
				} else{
					add_action( 'wp_ajax_bkap_get_per_night_price',                array( 'bkap_booking_process', 'bkap_get_per_night_price' ) );
					add_action( 'wp_ajax_bkap_check_for_time_slot',                array( 'bkap_booking_process', 'bkap_check_for_time_slot' ) );
					add_action( 'wp_ajax_bkap_insert_date',                        array( 'bkap_booking_process', 'bkap_insert_date' ) );
					add_action( 'wp_ajax_bkap_call_addon_price',                   array( 'bkap_booking_process', 'bkap_call_addon_price' ) );
					add_action( 'wp_ajax_bkap_js',                                 array( 'bkap_booking_process', 'bkap_js' ) );
					add_action( 'wp_ajax_bkap_get_date_lockout',                   array( 'bkap_booking_process', 'bkap_get_date_lockout' ) );
					add_action( 'wp_ajax_bkap_get_time_lockout',                   array( 'bkap_booking_process', 'bkap_get_time_lockout' ) );
					add_action( 'wp_ajax_save_widget_dates',                       array( 'Custom_WooCommerce_Widget_Product_Search', 'save_widget_dates' ) );
					add_action( 'wp_ajax_bkap_booking_calender_content',           array( &$this, 'bkap_booking_calender_content' ) );
					add_action( 'wp_ajax_bkap_save_attribute_data',                array( 'bkap_attributes', 'bkap_save_attribute_data' ) );
					add_action( 'wp_ajax_bkap_get_fixed_block_inline_date', array( 'bkap_booking_process', 'bkap_get_fixed_block_inline_date' ) );
					add_action( 'wp_ajax_bkap_discard_imported_event',             array( 'import_bookings', 'bkap_discard_imported_event' ) );
					add_action( 'wp_ajax_bkap_map_imported_event',                 array( 'import_bookings', 'bkap_map_imported_event' ) );
				}
			}
                        
            /**
             * This function duplicates the booking settings 
             * of the original product to the new product.
             */ 
            function bkap_product_duplicate( $new_id, $post ) {
				global $wpdb;
				
				$old_id             = $post->ID;
				$duplicate_query    = "SELECT * FROM `".$wpdb->prefix."booking_history` WHERE post_id = %d AND status = '' " ;
				$results_date       = $wpdb->get_results ( $wpdb->prepare( $duplicate_query, $old_id ) );
				
				foreach ( $results_date as $key => $value ) {
					$query_insert  =   "INSERT INTO `".$wpdb->prefix."booking_history`
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
				do_action( 'bkap_product_addon_duplicate', $new_id, $old_id );
			}
			
			/**
             *  This function is executed when the plugin is updated using 
             *  the Automatic Updater. It calls the bookings_activate function 
             *  which will check the table structures for the plugin and 
             *  make any changes if necessary.
             */
			function bkap_bookings_update_db_check() {
				global $booking_plugin_version, $BookUpdateChecker;
				global $wpdb;
				
				$booking_plugin_version = get_option( 'woocommerce_booking_db_version' );
				if ( $booking_plugin_version != $this->get_booking_version() ) {
				    
				    // Introducing the ability enable/disable the charging of GF options on a per day basis above 2.4.4
				    // Set it to 'ON' by default
				    if ( $booking_plugin_version <= '2.4.4' ) {
				    
				        $global_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
				        if ( ! isset( $global_settings->woo_gf_product_addon_option_price ) ) {
				            $global_settings->woo_gf_product_addon_option_price = 'on';
				            update_option( 'woocommerce_booking_global_settings', json_encode( $global_settings ) );
				        }
				    }
				    
					$table_name        = $wpdb->prefix . "booking_history";
					$check_table_query = "SHOW COLUMNS FROM $table_name LIKE 'status'";
					
					$results = $wpdb->get_results ( $check_table_query );
					
					if ( count( $results ) == 0 ) {
						$alter_table_query    =   "ALTER TABLE $table_name
						                          ADD `status` varchar(20) NOT NULL AFTER  `available_booking`";
						$wpdb->get_results ( $alter_table_query );
					}
					
					update_option( 'woocommerce_booking_db_version', '2.5.3' );
					// Add an option to change the "Choose a Time" text in the time slot dropdown
					add_option( 'book.time-select-option', 'Choose a Time' );
					// Add an option to change ICS file name
					add_option( 'book.ics-file-name', 'Mycal' );
					// Get the option setting to check if adbp has been updated to hrs for existing users
					$booking_abp_hrs = get_option( 'woocommerce_booking_abp_hrs' );
					
					if ( $booking_abp_hrs != 'HOURS' ) {
						// For all the existing bookable products, modify the ABP to hours instead of days
						$args     = array( 'post_type' => 'product', 'posts_per_page' => -1 );
						$product  = query_posts( $args );
						
						foreach( $product as $k => $v ){
							$product_ids[] = $v->ID;
						}
						
						foreach( $product_ids as $k => $v ){
							$booking_settings = get_post_meta( $v, 'woocommerce_booking_settings' , true );
							
							if ( isset( $booking_settings ) && isset( $booking_settings['booking_enable_date'] ) && $booking_settings['booking_enable_date'] == 'on' ) {
								
							    if ( isset( $booking_settings['booking_minimum_number_days'] ) && $booking_settings['booking_minimum_number_days'] > 0 ) {
									$advance_period_hrs                                = $booking_settings['booking_minimum_number_days'] * 24;
									$booking_settings['booking_minimum_number_days']   = $advance_period_hrs;
									update_post_meta( $v, 'woocommerce_booking_settings', $booking_settings );
								}
							}
						}
						update_option( 'woocommerce_booking_abp_hrs', 'HOURS' );
					}

					// Get the option setting to check if tables are set to utf8 charset
					$alter_queries = get_option( 'woocommerce_booking_alter_queries' );
						
					if ( $alter_queries != 'yes' ) {
						// For all the existing bookable products, modify the ABP to hours instead of days
						$table_name           = $wpdb->prefix . "booking_history";
						$sql_alter            = "ALTER TABLE $table_name CONVERT TO CHARACTER SET utf8" ;
						$wpdb->get_results ( $sql_alter );
						
						$order_table_name     = $wpdb->prefix . "booking_order_history";
						$order_alter_sql      = "ALTER TABLE $order_table_name CONVERT TO CHARACTER SET utf8" ;
						$wpdb->get_results ( $order_alter_sql );
						
						$table_name_price     = $wpdb->prefix . "booking_block_price_meta";
						$sql_alter_price      = "ALTER TABLE $table_name_price CONVERT TO CHARACTER SET utf8" ;
						$wpdb->get_results ( $sql_alter_price );
				
						$table_name_meta      = $wpdb->prefix . "booking_block_price_attribute_meta";
						$sql_alter_meta       = "ALTER TABLE $table_name_meta CONVERT TO CHARACTER SET utf8" ;
						$wpdb->get_results ( $sql_alter_meta );

						$block_table_name     = $wpdb->prefix . "booking_fixed_blocks";
						$blocks_alter_sql     = "ALTER TABLE $block_table_name CONVERT TO CHARACTER SET utf8" ;
						$wpdb->get_results ( $blocks_alter_sql );
						
						update_option( 'woocommerce_booking_alter_queries', 'yes' );
					}
				}
			}
			
			/**
			 * This function returns the booking plugin version number
			 */
			function get_booking_version() {
				$plugin_data    = get_plugin_data( __FILE__ );
				$plugin_version = $plugin_data['Version'];
				return $plugin_version;
			}
			
            /**
             * This function detects when the booking plugin is activated 
             * and creates all the tables necessary in database,
             * if they do not exists. 
             */
			function bkap_bookings_activate() {
				
				global $wpdb;
				
				$table_name         =   $wpdb->prefix . "booking_history";
				
				$sql                =   "CREATE TABLE IF NOT EXISTS $table_name (
                						`id` int(11) NOT NULL AUTO_INCREMENT,
                						`post_id` int(11) NOT NULL,
                  						`weekday` varchar(50) NOT NULL,
                  						`start_date` date NOT NULL,
                  						`end_date` date NOT NULL,
                						`from_time` varchar(50) NOT NULL,
                						`to_time` varchar(50) NOT NULL,
                						`total_booking` int(11) NOT NULL,
                						`available_booking` int(11) NOT NULL,
                						`status` varchar(20) NOT NULL,
                						PRIMARY KEY (`id`)
                				) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1" ;
				
				$order_table_name   =   $wpdb->prefix . "booking_order_history";
				$order_sql          =   "CREATE TABLE IF NOT EXISTS $order_table_name (
            							`id` int(11) NOT NULL AUTO_INCREMENT,
            							`order_id` int(11) NOT NULL,
            							`booking_id` int(11) NOT NULL,
            							PRIMARY KEY (`id`)
            				)ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1" ;

				$table_name_price   =   $wpdb->prefix . "booking_block_price_meta";

				$sql_price          =   "CREATE TABLE IF NOT EXISTS ".$table_name_price." (
                        				`id` int(11) NOT NULL AUTO_INCREMENT,
                        				`post_id` int(11) NOT NULL,
                                        `minimum_number_of_days` int(11) NOT NULL,
                        				`maximum_number_of_days` int(11) NOT NULL,
                                        `price_per_day` double NOT NULL,
                        				`fixed_price` double NOT NULL,
                        				 PRIMARY KEY (`id`)
                        				) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 " ;
				
				$table_name_meta    =   $wpdb->prefix . "booking_block_price_attribute_meta";
				
				$sql_meta           =   "CREATE TABLE IF NOT EXISTS ".$table_name_meta." (
                    					`id` int(11) NOT NULL AUTO_INCREMENT,
                    					`post_id` int(11) NOT NULL,
                    					`block_id` int(11) NOT NULL,
                    					`attribute_id` varchar(50) NOT NULL,
                    					`meta_value` varchar(500) NOT NULL,
                    					 PRIMARY KEY (`id`)
                    					) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 " ;

				$block_table_name   =   $wpdb->prefix . "booking_fixed_blocks";
				
				$blocks_sql         =   "CREATE TABLE IF NOT EXISTS ".$block_table_name." (
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
				
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				
				dbDelta( $sql );
				dbDelta( $order_sql );
				dbDelta( $sql_price );
				dbDelta( $sql_meta );
				dbDelta( $blocks_sql );
				update_option( 'woocommerce_booking_db_version', '2.5.3' );
				update_option( 'woocommerce_booking_abp_hrs', 'HOURS' );
				$check_table_query = "SHOW COLUMNS FROM $table_name LIKE 'end_date'";
				
				$results = $wpdb->get_results ( $check_table_query );
				
				if ( count( $results ) == 0 ) {
					$alter_table_query     =   "ALTER TABLE $table_name
											   ADD `end_date` date AFTER  `start_date`";
					$wpdb->get_results ( $alter_table_query );
				}
				
				$alter_block_table_query   =   "ALTER TABLE `$block_table_name` CHANGE `price` `price` DECIMAL(10,2) NOT NULL;";
				$wpdb->get_results ( $alter_block_table_query );
				
				//Set default labels
				add_option( 'book.date-label',          'Start Date');
				add_option( 'checkout.date-label',      '<br>End Date');
				add_option( 'book.time-label',          'Booking Time');
				add_option( 'book.time-select-option',  'Choose a Time');
				
				add_option( 'book.item-meta-date',      'Start Date');
				add_option( 'checkout.item-meta-date',  'End Date');
				add_option( 'book.item-meta-time',      'Booking Time');
				add_option( 'book.ics-file-name',       'Mycal');
				
				add_option( 'book.item-cart-date',      'Start Date');
				add_option( 'checkout.item-cart-date',  'End Date');
				add_option( 'book.item-cart-time',      'Booking Time');
				
				//Set default global booking settings
				$booking_settings                                       = new stdClass();
				$booking_settings->booking_language                     = 'en-GB';
				$booking_settings->booking_date_format                  = 'mm/dd/y';
				$booking_settings->booking_time_format                  = '12';
				$booking_settings->booking_months                       = $booking_settings->booking_calendar_day = '1';
				$booking_settings->global_booking_minimum_number_days   = '0';
				$booking_settings->booking_availability_display         = $booking_settings->minimum_day_booking = $booking_settings->booking_global_selection = $booking_settings->booking_global_timeslot = '';
				$booking_settings->booking_export                       = $booking_settings->enable_rounding = $booking_settings->woo_product_addon_price = $booking_settings->booking_global_holidays = '';
				$booking_settings->booking_themes                       = 'smoothness';
				$booking_global_settings                                = json_encode( $booking_settings );
				add_option( 'woocommerce_booking_global_settings', $booking_global_settings );
			}
			
			function bkap_vertical_my_enqueue_scripts_css() {
				if ( get_post_type() == 'product' ) {
                    $plugin_version_number = get_option('woocommerce_booking_db_version');
                    
					wp_enqueue_style( 'bkap-tabstyle-1', plugins_url('/css/zozo.tabs.min.css', __FILE__), '', $plugin_version_number, false );
					wp_enqueue_style( 'bkap-tabstyle-2', plugins_url('/css/style.css', __FILE__), '', $plugin_version_number, false );
				}
			}
			
            /**
             * This function include css files required for admin side.
             */
			function bkap_my_enqueue_scripts_css() {
			
			    $plugin_version_number = get_option( 'woocommerce_booking_db_version' );
			    
				if ( get_post_type() == 'product'  || ( isset( $_GET['page'] ) && $_GET['page'] == 'woocommerce_booking_page' ) || 
					( isset( $_GET['page'] ) && $_GET['page'] == 'woocommerce_history_page' ) || ( isset( $_GET['page'] ) && $_GET['page'] == 'operator_bookings' ) || ( isset($_GET['page'] ) && $_GET['page'] == 'woocommerce_availability_page' ) ) {
					
                    wp_enqueue_style( 'bkap-booking', plugins_url('/css/booking.css', __FILE__ ) , '', $plugin_version_number , false );
					wp_enqueue_style( 'bkap-datepick', plugins_url('/css/jquery.datepick.css', __FILE__ ) , '', $plugin_version_number, false );
					
					wp_enqueue_style( 'bkap-woocommerce_admin_styles', plugins_url() . '/woocommerce/assets/css/admin.css', '', $plugin_version_number, false );
				
					$calendar_theme = 'base';
					wp_enqueue_style( 'bkap-jquery-ui', "http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/$calendar_theme/jquery-ui.css" , '', $plugin_version_number, false );	
				}
				
				if ( ( isset( $_GET['page'] ) && $_GET['page'] == 'woocommerce_booking_page' ) ||
						( isset( $_GET['page'] ) && $_GET['page'] == 'woocommerce_history_page' ) ||
				        ( isset( $_GET['page'] ) && $_GET['page'] == 'woocommerce_import_page' ) ) {
					wp_enqueue_style( 'bkap-data', plugins_url('/css/view.booking.style.css', __FILE__ ) , '', $plugin_version_number, false );
					
					wp_enqueue_style( 'bkap-fullcalendar-css', plugins_url().'/woocommerce-booking/js/fullcalendar/fullcalendar.css' );
						
					$calendar_theme        = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
					$calendar_theme_sel    = "";
					
					if ( isset( $calendar_theme ) ) {
					    $calendar_theme_sel = $calendar_theme->booking_themes;
					}
					if ( $calendar_theme_sel == "" ) $calendar_theme_sel = 'smoothness';
					
					// this is used for displying the full calender theme.
					wp_enqueue_style( 'ui-css', plugins_url('/css/themes/'.$calendar_theme_sel.'/jquery.ui.theme.css', __FILE__ ) , '', $plugin_version_number, false );
					
					// this is for displying the full calender view.
					wp_enqueue_style( 'full-css', plugins_url( '/js/fullcalendar/fullcalendar.css', __FILE__ ) );
						
					// this is used for displying the hover effect in calendar view.
					wp_enqueue_style( 'bkap-qtip-css', plugins_url( '/css/jquery.qtip.min.css', __FILE__ ), array() );
				}
			}
			
            /**
             * This function includes js files required for admin side.
             */
			function bkap_my_enqueue_scripts_js() {
				$plugin_version_number = get_option( 'woocommerce_booking_db_version' );
            	
            	if ( get_post_type() == 'product'  || ( isset ( $_GET['page'] ) && $_GET['page'] == 'woocommerce_booking_page' ) || ( isset ( $_GET['page'] ) && $_GET['page'] == 'woocommerce_availability_page' ) ) {
                	wp_enqueue_script( 'jquery' );
					wp_deregister_script( 'jqueryui');
					wp_enqueue_script( 'bkap-jqueryui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js', '', $plugin_version_number, false );
					
					wp_enqueue_script( 'jquery-ui-datepicker' );
					
					wp_register_script( 'multiDatepicker', plugins_url().'/woocommerce-booking/js/jquery-ui.multidatespicker.js', '', $plugin_version_number, false );
					wp_enqueue_script( 'multiDatepicker' );
					
					wp_register_script( 'datepick', plugins_url().'/woocommerce-booking/js/jquery.datepick.js', '', $plugin_version_number, false );
					wp_enqueue_script( 'datepick' );
					wp_enqueue_script( 'bkap-tabsjquery', plugins_url().'/woocommerce-booking/js/zozo.tabs.min.js', '', $plugin_version_number, false );
					
					$current_language = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
					
					if ( isset( $current_language ) ) {
						$curr_lang = $current_language->booking_language;
					} else {
						$curr_lang = "";
					}
					if ( $curr_lang == "" ) {
						$curr_lang = "en-GB";
					}
				}
				
				// below files are only to be included on booking settings page
				if ( isset( $_GET['page'] ) && $_GET['page'] == 'woocommerce_booking_page' ) {
					wp_register_script( 'bkap-woocommerce_admin', plugins_url() . '/woocommerce/assets/js/admin/woocommerce_admin.js', array( 'jquery', 'jquery-ui-widget', 'jquery-ui-core' ), $plugin_version_number , false );
					wp_enqueue_script( 'bkap-woocommerce_admin' );
					wp_enqueue_script( 'bkap-themeswitcher', plugins_url( '/js/jquery.themeswitcher.min.js', __FILE__ ), '', $plugin_version_number, false );
					wp_enqueue_script( "bkap-lang", plugins_url( "/js/i18n/jquery-ui-i18n.js", __FILE__ ), '', $plugin_version_number, false );
					
					wp_enqueue_script(
							'bkap-jquery-tip',
							plugins_url( '/js/jquery.tipTip.minified.js', __FILE__ ),
							'',
							$plugin_version_number,
							false
					);
				}
				
				if ( isset( $_GET['page'] ) && $_GET['page'] == 'woocommerce_history_page' || ( isset( $_GET['page'] ) && $_GET['page'] == 'operator_bookings' ) ) {
					wp_register_script( 'bkap-dataTable', plugins_url().'/woocommerce-booking/js/jquery.dataTables.js', '', $plugin_version_number, false );
					wp_enqueue_script( 'bkap-dataTable' );
					
					wp_enqueue_script( 'jquery' );
					
					wp_register_script( 'moment-js', plugins_url( '/js/fullcalendar/lib/moment.min.js', __FILE__ ) );
					wp_register_script( 'full-js', plugins_url( '/js/fullcalendar/fullcalendar.min.js', __FILE__ ) );
					
					wp_register_script( 'bkap-images-loaded', plugins_url( '/js/imagesloaded.pkg.min.js', __FILE__ ) );
					wp_register_script( 'bkap-qtip', plugins_url( '/js/jquery.qtip.min.js', __FILE__ ), array( 'jquery', 'bkap-images-loaded' ) );
						
					wp_enqueue_script( 'booking-calender-js', plugins_url( '/js/booking-calender.js', __FILE__ ), array( 'jquery', 'bkap-qtip' ,'moment-js', 'full-js', 'bkap-images-loaded', 'jquery-ui-core','jquery-ui-widget','jquery-ui-position', 'jquery-ui-selectmenu' ) );
					
					
					$this->localize_script();
				}
			}
			
			public static function localize_script(){
			    $js_vars                 = array();
			    $schema                  = is_ssl() ? 'https':'http';
			    $js_vars['ajaxurl']      = admin_url( 'admin-ajax.php', $schema );
			    $js_vars['pluginurl']    = plugins_url().'/woocommerce-booking/adminend-events-jsons.php';
			    wp_localize_script( 'booking-calender-js', 'bkap', $js_vars );
			}
			
			/**
			 * Called during AJAX request for qtip content for a calendar item
			 */
			public static function bkap_booking_calender_content(){
			
			    $content         = '';
			    $date_formats    = bkap_get_book_arrays( 'date_formats' );
			    // get the global settings to find the date formats
			    $global_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
			    $date_format_set = $date_formats[ $global_settings->booking_date_format ];
			    	
			    if( !empty( $_REQUEST['order_id'] ) && ! empty( $_REQUEST[ 'event_value' ] ) ){
			        $order                       =   new WC_Order( $_REQUEST[ 'order_id' ] );
			        $order_items                 =   $order->get_items();
			        $attribute_name              =   '';
			        $attribute_selected_value    =   '';
			        
			        $value[]                     =   $_REQUEST[ 'event_value' ];
			        $content                     =   "<table>
                    			                     <tr> <td> <strong>Order: </strong></td><td><a href=\"post.php?post=". $order->id."&action=edit\">#".$order->id." </a> </td> </tr>
                    			                     <tr> <td> <strong>Product Name:</strong></td><td> ".get_the_title( $value[0]['post_id'] )."</td> </tr>
                    			                     <tr> <td> <strong>Customer Name:</strong></td><td> ".$order->billing_first_name . " " . $order->billing_last_name ."</td> </tr>
                    			                     " ;
			        
			        foreach ( $order_items as $item ) {
			             
			            if ( $item[ 'variation_id' ] != ''){
			                 
			                $variation_product               = get_post_meta( $item[ 'product_id' ] );
			                $product_variation_array_string  = $variation_product[ '_product_attributes' ];
			                $product_variation_array         = unserialize( $product_variation_array_string[0] );
			                 
			                foreach ( $product_variation_array as $product_variation_key => $product_variation_value ) {
			                     
			                    if ( array_key_exists( $product_variation_key, $item ) ){
			                        $attribute_name              = $product_variation_value[ 'name' ];
			                        $attribute_selected_value    = $item [ $product_variation_key ];
			                        $content                    .= " <tr> <td> <strong>".$attribute_name.":</strong></td> <td> ".$attribute_selected_value."</td> </tr> ";
			                    }
			                }
			            }
			        }
			        			        	
			        if ( isset( $value[ 0 ][ 'start_date' ] ) && $value[ 0 ][ 'start_date' ] != '0000-00-00' ){
			            $date        = strtotime( $value[ 0 ][ 'start_date' ] );
			            $value_date  = date($date_format_set,$date);
			            $content    .= " <tr> <td> <strong>Start Date:</strong></td><td> ".$value_date."</td> </tr>";
			        }
			        	
			        if ( isset( $value[ 0 ][ 'end_date' ] ) && $value[ 0 ][ 'end_date' ] != '0000-00-00' ){
			            $date            = strtotime( $value[ 0 ][ 'end_date' ] );
			            $value_end_date  = date($date_format_set,$date);
			            $content        .= " <tr> <td> <strong>End Date:</strong></td><td> ".$value_end_date."</td> </tr> ";
			        }
			        	
			        // Booking Time
			        $time = '';
			        if ( isset( $value[ 0 ][ 'from_time' ] ) && $value[ 0 ][ 'from_time' ] != "" && isset( $value[ 0 ][ 'to_time' ] ) && $value[0]['to_time'] != "" ) {
			        if ( $global_settings->booking_time_format == 12 ) {
			                $to_time     = '';
			                $from_time   = date( 'h:i A', strtotime( $value[0]['from_time'] ) );
			                $time        = $from_time ;
			                
			                if ( isset( $value[0]['to_time'] ) && $value[0]['to_time'] != '' ){
			                    $to_time = date( 'h:i A', strtotime( $value[0]['to_time'] ) );
			                    $time    = $from_time . " - " . $to_time;
			                }
			                 
			            }else {
			                $time = $time = $value[0]['from_time'] . " - " . $value[0]['to_time'];
			            }
			            
			            $content .= "<tr> <td> <strong>Time:</strong></td><td> ".$time."</td> </tr>";
			            
			        }else if ( isset( $value[ 0 ][ 'from_time' ] ) && $value[ 0 ][ 'from_time' ] != "" ) {
			        if ( $global_settings->booking_time_format == 12 ) {
			                
			                $to_time = '';
			                $from_time = date( 'h:i A', strtotime( $value[0]['from_time'] ) );
			                $time = $from_time. " - Open-end" ;
			            }else {
			                $time = $time = $value[0]['from_time'] ." - Open-end";
			            }
			            $content .= "<tr> <td> <strong>Time:</strong></td><td> ".$time."</td> </tr>";
			        }
			        
			        $content .= '</table>';
			        	
			        if ( $value[0]['post_id'] ){
			            $post_image = get_the_post_thumbnail( $value[0]['post_id'], array( 100, 100 ) );
			            
			            if ( !empty( $post_image ) ){
			                $content = '<div style="float:left; margin:0px 5px 5px 0px; ">'.$post_image.'</div>'.$content;
			            }
			        }
			    }

			    echo $content;
			    die();
			}
                        
            /**
             * This function includes js files required for frontend.
             */
			
			function bkap_front_side_scripts_js() {
				global $post;
				
				if ( is_product() || is_page() ) {
					$booking_settings = get_post_meta($post->ID,'woocommerce_booking_settings',true);
					
					if ( isset( $booking_settings['booking_enable_date'] ) && $booking_settings['booking_enable_date'] == 'on' ) {
						$plugin_version_number = get_option( 'woocommerce_booking_db_version' );
						
						wp_enqueue_script(
								'bkap-initialize-datepicker.js',
								plugins_url( '/js/initialize-datepicker.js', __FILE__ ),
								'',
								$plugin_version_number,
								false
						);
						wp_enqueue_script( 'jquery' );
						
						wp_enqueue_script( 'jquery-ui-datepicker' );
						
						if ( defined('ICL_LANGUAGE_CODE') ){
							
							if( ICL_LANGUAGE_CODE == 'en' ) {
							    $curr_lang = "en-GB";
							} else{
							    $curr_lang = ICL_LANGUAGE_CODE;
							}
						} else {
							$current_language = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
							
							if ( isset( $current_language ) ) {
								$curr_lang = $current_language->booking_language;
							} else {
								$curr_lang = "";
							}
							
							if ( $curr_lang == "" ) $curr_lang = "en-GB";
						}
						wp_enqueue_script( "$curr_lang", plugins_url( "/js/i18n/jquery.ui.datepicker-$curr_lang.js", __FILE__ ), '', $plugin_version_number, false );
					}
				}
			}
			
            /**
             * This function includes css files required for frontend.
             */
			function bkap_front_side_scripts_css() {
				global $post;
				
				if ( is_product() || is_page()) {
					$booking_settings = get_post_meta( $post->ID, 'woocommerce_booking_settings', true );
					
					if ( isset( $booking_settings['booking_enable_date'] ) && $booking_settings['booking_enable_date'] == 'on' ) {
						$plugin_version_number    = get_option( 'woocommerce_booking_db_version' );
						$calendar_theme           = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
						$calendar_theme_sel       = "";
						
						if ( isset( $calendar_theme ) ) {
							$calendar_theme_sel = $calendar_theme->booking_themes;
						}
						
						if ( $calendar_theme_sel == "" ) {
							$calendar_theme_sel = 'smoothness';
						}
						
						wp_enqueue_style( 'bkap-jquery-ui', plugins_url('/css/themes/'.$calendar_theme_sel.'/jquery-ui.css', __FILE__ ) , '', $plugin_version_number, false );
						wp_enqueue_style( 'bkap-booking', plugins_url('/css/booking.css', __FILE__ ) , '', $plugin_version_number, false );
					}
				}
			}
			
           /**
            * This function returns the number of bookings done for a date.
            */
			function bkap_get_date_lockout( $start_date ) {
				global $wpdb,$post;
				
				$duplicate_of       =   bkap_common::bkap_get_product_id( $post->ID );
				
				$date_lockout       =   "SELECT sum(total_booking) - sum(available_booking) AS bookings_done FROM `".$wpdb->prefix."booking_history`
								        WHERE start_date= %s AND post_id= %d";
					
				$results_date_lock  = $wpdb->get_results( $wpdb->prepare( $date_lockout, $start_date, $duplicate_of ) );
					
				$bookings_done      = $results_date_lock[0]->bookings_done;
				return $bookings_done;
			}
                      
		   /**
            * This function updates to "Inactive" a single time slot 
            * from View/Delete Booking date, Timeslots.
            */
			function bkap_remove_time_slot() {
				global $wpdb;
				
				if( isset( $_POST['details'] ) ) {
					$details       = explode( "&", $_POST['details'] );
				
					$date_delete   = $details[2];
					$date_db       = date( 'Y-m-d', strtotime( $date_delete ) );
					$id_delete     = $details[0];
					$book_details  = get_post_meta( $details[1], 'woocommerce_booking_settings', true );
				
					unset( $book_details[booking_time_settings][ $date_delete ][ $id_delete ] );
					
					if ( count( $book_details[booking_time_settings][ $date_delete ] ) == 0 ) {
						
					    unset($book_details[booking_time_settings][$date_delete]);
						
						if ( substr( $date_delete, 0, 7 ) == "booking" ) {
							$book_details[booking_recurring][ $date_delete ] = '';
						} elseif ( substr( $date_delete, 0, 7 ) != "booking" ) {
							$key_date = array_search( $date_delete, $book_details[booking_specific_date] );
							unset( $book_details[booking_specific_date][ $key_date ] );
						}
					}
					update_post_meta( $details[1], 'woocommerce_booking_settings', $book_details );
				
					if ( substr( $date_delete, 0, 7 ) != "booking" ) {
						if ( $details[4] == "0:00" ) {
							$details[4] = "";
						}
					
						$update_status_query  =   "UPDATE `".$wpdb->prefix."booking_history`
            										SET status = 'inactive'
            										WHERE post_id = '".$details[1]."'
            									 	AND start_date = '".$date_db."'
            									 	AND from_time = '".$details[3]."'
            									 	AND to_time = '".$details[4]."' ";
					
						$wpdb->query( $update_status_query );
						
					}
					elseif ( substr( $date_delete, 0, 7 ) == "booking" ) {
						if ( $details[4] == "0:00" ) {
							$details[4] = "";
						}
				
						$update_status_query  =   "UPDATE `".$wpdb->prefix."booking_history`
            										SET status = 'inactive'
            										WHERE post_id = '".$details[1]."'
            										AND weekday = '".$date_delete."'
            										AND from_time = '".$details[3]."'
            										AND to_time = '".$details[4]."' ";
						$wpdb->query( $update_status_query );
					}
            	}	
			}
			
		   /**
            * This function updates to "Inactive" a single day 
            * from View/Delete Booking date, Timeslots.
            */
			function bkap_remove_day() {
			
				global $wpdb;
			
				if ( isset( $_POST['details'] ) ) {
				
				    $details        = explode( "&", $_POST['details'] );
    				$date_delete    = $details[0];
    				$book_details   = get_post_meta( $details[1], 'woocommerce_booking_settings', true );
				
				if ( substr( $date_delete, 0, 7 ) != "booking" ) {
					$date_db = date( 'Y-m-d', strtotime( $date_delete ) );
					
					$key_date = array_search( $date_delete, $book_details[booking_specific_date] );
					unset( $book_details[booking_specific_date][ $key_date ] );
					
					$update_status_query   =   "UPDATE `".$wpdb->prefix."booking_history`
    											SET status = 'inactive'
    											WHERE post_id = '".$details[1]."'
    											AND start_date = '".$date_db."'";
					$wpdb->query( $update_status_query );
						
				} elseif ( substr( $date_delete, 0, 7 ) == "booking" ) {
					$book_details[booking_recurring][ $date_delete ] = '';
					$update_status_query = "UPDATE `".$wpdb->prefix."booking_history`
											SET status = 'inactive'
											WHERE post_id = '".$details[1]."'
											AND weekday = '".$date_delete."'";
					$wpdb->query( $update_status_query );
						
				}
				update_post_meta( $details[1], 'woocommerce_booking_settings', $book_details );
				}
			}
			
	   /**
        * This function updates all dates to "Inactive" from View/Delete Booking date, 
        * Timeslots of specific day method.
        */	
		function bkap_remove_specific() {
				
				global $wpdb;
				
				if( isset( $_POST['details'] ) ) {
				
				    $details        = $_POST['details'];
				    $book_details   = get_post_meta( $details, 'woocommerce_booking_settings', true );
			
    				foreach ( $book_details[booking_specific_date] as $key => $value ) {
    					if ( array_key_exists( $value, $book_details[booking_time_settings] ) ) unset( $book_details[booking_time_settings][ $value ] );
    				}
    				unset( $book_details[booking_specific_date] );
    				update_post_meta( $details, 'woocommerce_booking_settings', $book_details );
    
    				$update_status_query    =   "UPDATE `".$wpdb->prefix."booking_history`
        										SET status = 'inactive'
        										WHERE post_id = '".$details."'
        										AND weekday = ''";
    				$wpdb->query( $update_status_query );
				}
			}
		   
		   /**
            * This function updates all days to "Inactive"  from View/Delete Booking 
            * date, Timeslots of recurring day method.
            */
			function bkap_remove_recurring() {		
				global $wpdb;
				
				if( isset( $_POST['details'] ) ) {
				$details        = $_POST['details'];
				$book_details   = get_post_meta( $details, 'woocommerce_booking_settings', true );
				$weekdays       = bkap_get_book_arrays( 'weekdays' );
				
				foreach ( $weekdays as $n => $day_name ) {
					
				    if ( array_key_exists($n,$book_details[booking_time_settings] ) ) {
						unset( $book_details[booking_time_settings][ $n ] );
					}
					
					$book_details[booking_recurring][ $n ] =   '';
			
					$update_status_query                   =   "UPDATE `".$wpdb->prefix."booking_history`
                    											SET status = 'inactive'
                    											WHERE post_id = '".$details."'
                    											AND weekday = '".$n."'";
					$wpdb->query( $update_status_query );
				}
				
				update_post_meta( $details, 'woocommerce_booking_settings', $book_details );
				}
			
			}
			/*
			 * This function used to register Fixed block booking's Block name string to WPML
			 * Like : Body, subject, Wc header text
			 *
			 * Since : 2.5.3
			 */
			function bkap_register_fixed_block_string_for_wpml() {
			
			    if ( function_exists('icl_register_string') ) {
			
			        global $wpdb;
			        $context             = 'BKAP';
			        $fixed_block_table   = $wpdb->prefix . 'booking_fixed_blocks';
			        $result              = $wpdb->get_results("SELECT * FROM $fixed_block_table");
			        
			        foreach ($result as $each_block) {
			            
			            $name_msg = 'bkap_fixed_' . $each_block->id . '_block_name';
			            $value_msg = $each_block->block_name;
			            			            
			            icl_register_string( $context, $name_msg, $value_msg ); //for registering message
			

			       }
			    }
			}
                  
		}		
	}
	
	$woocommerce_booking = new woocommerce_booking();
	
}
