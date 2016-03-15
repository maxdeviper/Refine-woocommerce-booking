<?php 
//if (is_woocommerce_active())
{
	/**
	 * Localisation
	 **/
	load_plugin_textdomain( 'bkap_admin_bookings', false, dirname( plugin_basename( __FILE__ ) ) . '/' );

	include_once( 'bkap-common.php' );
	include_once( 'fixed-block.php' );
	
	/**
	 * bkap_admin_bookings class
	 **/
	if ( !class_exists( 'bkap_admin_bookings' ) ) {

		class bkap_admin_bookings {

			public function __construct() {
				// Initialize settings.
				register_activation_hook( __FILE__,                         array( &$this, 'bkap_admin_bookings_activate' ) );
				
				// Scripts
				add_action( 'admin_enqueue_scripts',                        array( &$this, 'bkap_admin_booking_enqueue_scripts_css' ) );
				add_action( 'admin_enqueue_scripts',                        array( &$this, 'bkap_admin_booking_enqueue_scripts_js' ) );
				add_action( 'init',                                         array( &$this, 'bkap_admin_load_ajax' ) );
				add_action( 'wp_ajax_woocommerce_remove_order_item_meta',   array( &$this, 'bkap_woocommerce_ajax_remove_order_item_meta_admin' ), 10, 3 );
				
				// Removes the booking data when a line item is deleted.
				add_action( 'wp_ajax_woocommerce_remove_order_item',        array(&$this,'bkap_woocommerce_ajax_remove_order_item_admin' ));
				
				// Used to add new settings on the product page booking box.
				add_action('wp_ajax_woocommerce_add_order_item_meta',       array(&$this, 'bkap_booking_order_box') );
				
				// Saves the item meta when clicked on the save button for the items.
				add_action('wp_ajax_woocommerce_save_order_items',          array(&$this, 'bkap_update_order_details'), 10, 2 );
				
			}
			
            /************************************************************************* 
            * This function is used to load ajax functions required by manual booking.
            *************************************************************************/
			
			function bkap_admin_load_ajax() {
				if ( !is_user_logged_in() ) {
					add_action( 'wp_ajax_nopriv_bkap_check_for_time_slot_admin',   array( &$this, 'bkap_check_for_time_slot_admin' ) );
					add_action( 'wp_ajax_nopriv_bkap_insert_admin_date',           array( 'bkap_booking_process', 'bkap_insert_date' ) );
				} else {
					add_action( 'wp_ajax_bkap_check_for_time_slot_admin',          array( &$this, 'bkap_check_for_time_slot_admin' ) );
					add_action( 'wp_ajax_bkap_insert_admin_date',                  array( 'bkap_booking_process', 'bkap_insert_date' ) );
				}
			}
             /******************************************************************** 
             * This function detects when the booking plugin is activated
             * And creates the table necessary in database,if they do not exists. 
             ********************************************************************/
                        
			function bkap_admin_bookings_activate() {
			
				global $wpdb;
				
				$table_name =   $wpdb->prefix . "";
				$sql        =   "" ;
				
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				
				dbDelta( $sql );
				
			}
			
            /*********************************************************************************
            *  This function is used to get the product from the typed name in the admin side.
            *********************************************************************************/
			
			function bkap_get_post_by_title( $post_title, $output = OBJECT ) {
				global $wpdb;
				
				$post   =   $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type='product'", $post_title ) );
				
				if ( $post )
					return get_post( $post, $output );
				
				return null;
			}
                        
            /**************************************************************
            * This function returns the number of bookings done for a date.
            **************************************************************/
			
			function bkap_get_date_lockout( $start_date, $post_id ){
				global $wpdb,$post;
				
				$duplicate_of   =   bkap_common::bkap_get_product_id( $post_id );
				$date_lockout   =   "SELECT sum(total_booking) - sum(available_booking) AS bookings_done FROM `".$wpdb->prefix."booking_history`
								    WHERE start_date= %s AND post_id= %d";
				
				$results_date_lock  =   $wpdb->get_results( $wpdb->prepare( $date_lockout, $start_date, $duplicate_of ) );
				$bookings_done      =   $results_date_lock[0]->bookings_done;
				
				return $bookings_done;
			}
			
            /*********************************************************************************************************
            * This function will add booking fields to the product when Add Meta button is clicked on edit order page.
            *********************************************************************************************************/
			
			function bkap_booking_order_box() {
				global $woocommerce, $wpdb;
				
				check_ajax_referer( 'order-item', 'security' );
				
				$meta_id_next   =   '';
				$meta_id        =   '';
				$meta_id        =   woocommerce_add_order_item_meta( absint( $_POST['order_item_id'] ), __( 'Name', 'woocommerce' ), __( 'Value', 'woocommerce' ) );
				$bookings_added =   woocommerce_get_order_item_meta( $_POST['order_item_id'], get_option( "book.date-label" ), true );

				if( $bookings_added == '' ) {
					if ( $meta_id ) {
						$check_query      =   "SELECT meta_value AS product_id FROM `".$wpdb->prefix."woocommerce_order_itemmeta`
										      WHERE meta_key ='_product_id'
										      AND order_item_id = %d";
						$results_check    =   $wpdb->get_results ( $wpdb->prepare( $check_query, $_POST['order_item_id'] ) );
						$product_id       =   $results_check[0]->product_id;
					
						$prod_id          =   bkap_common::bkap_get_product_id( $product_id );
						
						$cart_item_key    =   $_POST['order_item_id'];
						$product_settings =   get_post_meta( $prod_id, 'woocommerce_booking_settings', true );
						$i = 0;
					
						echo '<input type="hidden" id="order_item_ids"  name="order_item_ids" value="'.$_POST['order_item_id'].'"/>';
				 		
				 		if ( ( isset( $product_settings[ 'booking_enable_multiple_day' ] ) && $product_settings[ 'booking_enable_multiple_day' ] == 'on' ) && ( isset( $product_settings[ 'booking_fixed_block_enable' ] ) && $product_settings[ 'booking_fixed_block_enable' ] == 'yes' ) )  {
 				 	        $results   =   bkap_block_booking::bkap_get_fixed_blocks( $prod_id );
							
							if ( count( $results ) > 0 ) {	
								echo '<tr data-meta_id="'.$meta_id.'"><td><input type="text" name="meta_key[' . $meta_id . ']" value="Select Period" /></td><td><select name="meta_value[' . $meta_id . ']" id="admin_block_option_'.$_POST['order_item_id'].'">';
								
								foreach ( $results as $key => $value ) {
									echo '<option id = '.$value->start_day.'&'.$value->number_of_days.'&'.$value->price.' value="'.$value->block_name.'">'.$value->block_name.'</option>';
								} 
								echo '</select></td><td width="1%"><button class="remove_order_item_meta button">&times;</button></td></tr>';

								$meta_id_start  =   $meta_id + 1;
								$meta_id_end    =   $meta_id_start +  1;
								 
								echo '<input type="hidden" id="meta_id_start"  name="meta_id_start" value="'.$meta_id_start.'"/>';
								echo '<input type="hidden" id="meta_id_end"  name="meta_id_end" value="'.$meta_id_end.'"/>';
								
								?>
								
								<script type="text/javascript">	
								var order_item_id = jQuery("#order_item_ids").val();
								jQuery("#admin_block_option_"+order_item_id).change(function() {
				
									if ( jQuery("#admin_block_option_"+order_item_id).val() != "" ) {
										var passed_id = jQuery(this).children(":selected").attr("id");
										var exploded_id = passed_id.split('&');
										console.log(exploded_id);
										var meta_id_start = jQuery("#meta_id_start").val();
										var meta_id_end = jQuery("#meta_id_end").val();
										jQuery("#admin_block_option_start_day_"+order_item_id).val(exploded_id[0]);
										jQuery("#admin_block_option_number_of_day_"+order_item_id).val(exploded_id[1]);
										jQuery("#admin_block_option_price_"+order_item_id).val(exploded_id[2]);
										jQuery("#wapbk_admin_hidden_date_"+order_item_id).val("");
										jQuery("#wapbk_admin_hidden_date_checkout_"+order_item_id).val("");
				
										jQuery("#admin_booking_calender_"+ meta_id_start).datepicker("setDate");
										jQuery("#admin_booking_calender_checkout_" + meta_id_end).datepicker("setDate");
									}
								});
								</script>
			
								<?php
										
								if ( count( $results ) >= 0 ) {
									$sd=$results[0]->start_day;
									$nd=$results[0]->number_of_days;
									$pd=$results[0]->price;
								}
								echo ' <input type="hidden" id="admin_block_option_enabled_'.$_POST['order_item_id'].'"  name="admin_block_option_enabled_'.$_POST['order_item_id'].'" value="on"/> 
		
								<input type="hidden" id="admin_block_option_start_day_'.$_POST['order_item_id'].'"  name="admin_block_option_start_day_'.$_POST['order_item_id'].'" value="'.$sd.'"/> 
								
								<input type="hidden" id="admin_block_option_number_of_day_'.$_POST['order_item_id'].'"  name="admin_block_option_number_of_day_'.$_POST['order_item_id'].'" value="'.$nd.'"/>
							
								<input type="hidden" id="admin_block_option_price_'.$_POST['order_item_id'].'"name="admin_block_option_price_'.$_POST['order_item_id'].'" value="'.$pd.'"/>';	
							}
							else {
								$number_of_fixed_price_blocks   =   0;
								echo ' <input type="hidden" id="admin_block_option_enabled_'.$_POST['order_item_id'].'"  name="admin_block_option_enabled_'.$_POST['order_item_id'].'" value="off"/>
								
								<input type="hidden" id="admin_block_option_start_day_'.$_POST['order_item_id'].'"  name="admin_block_option_start_day_'.$_POST['order_item_id'].'" value=""/> 
								
								<input type="hidden" id="admin_block_option_number_of_day_'.$_POST['order_item_id'].'"  name="admin_block_option_number_of_day_'.$_POST['order_item_id'].'" value=""/>
								
								<input type="hidden" id="admin_block_option_price_'.$_POST['order_item_id'].'"  name="admin_block_option_price_'.$_POST['order_item_id'].'" value=""/>';
							}
							$meta_ids[ $cart_item_key ][ $i ]    =   $meta_id;
							$meta_id                             =   $meta_id + 1;
							$i++;
		 				 }
						if ( isset( $product_settings['booking_enable_date'] ) && $product_settings['booking_enable_date'] == 'on' ):
							$saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
							
							if ( $saved_settings == '' ) {
								$saved_settings                         = new stdClass();
								$saved_settings->booking_date_format    = 'd MM, yy';
								$saved_settings->booking_time_format    = '12';
								$saved_settings->booking_months         = '1';
							}
							$meta_ids[ $cart_item_key ][ $i ]           = $meta_id;
							$i++;
							
							echo '<tr data-meta_id="'.$meta_id.'"><td><input type="text" name="meta_key[' . $meta_id . ']" value="'. __(get_option("book.item-meta-date"), "woocommerce-booking").'" /></td><td><input type="text" name="meta_value[' . $meta_id . ']" value="" /></td><td width="1%"><button class="remove_order_item_meta button">&times;</button></td></tr>';
							
							if ( isset( $saved_settings->booking_global_holidays ) ) {
								$book_global_holidays = $saved_settings->booking_global_holidays;
								$book_global_holidays = substr( $book_global_holidays, 0, strlen( $book_global_holidays ) );
								$book_global_holidays = '"'.str_replace( ',', '","', $book_global_holidays ).'"';
							} else {
								$book_global_holidays = '';
							}
							
							print( '<input type="hidden" name="wapbk_admin_booking_global_holidays_'.$_POST['order_item_id'].'" id="wapbk_admin_booking_global_holidays_'.$_POST['order_item_id'].'" value=\''.$book_global_holidays.'\'>' );
							
							if ( $product_settings != '' ) :
							
							$booking_dates_arr = $product_settings['booking_specific_date']; // fetch specific booking dates
							
							$booking_dates_str = "";
							$meta_id_next      = 0;
						
							if ( $product_settings['booking_specific_booking'] == "on" ) {
								if( !empty( $booking_dates_arr ) ){
									foreach ( $booking_dates_arr as $k => $v ) {
										$booking_dates_str .= '"'.$v.'",';
									}
								}
								$booking_dates_str = substr( $booking_dates_str, 0, strlen( $booking_dates_str )-1 );
							}
							print( '<input type="hidden" name="wapbk_admin_booking_dates_'.$_POST['order_item_id'].'" id="wapbk_admin_booking_dates_'.$_POST['order_item_id'].'" value=\''.$booking_dates_str.'\'>' );
							
							// Global or Product level minimum Multiple day setting
							$enable_min_multiple_day =   '';
							$minimum_multiple_day    =   1;
							
							if ( isset( $product_settings['enable_minimum_day_booking_multiple'] ) && $product_settings['enable_minimum_day_booking_multiple'] == "on" ) {
								$enable_min_multiple_day    =   $product_settings['enable_minimum_day_booking_multiple'];
								$minimum_multiple_day       =   $product_settings['booking_minimum_number_days_multiple'];
							}
							elseif ( isset( $saved_settings->minimum_day_booking ) && $saved_settings->minimum_day_booking == "on" ) {
								$enable_min_multiple_day    =   $saved_settings->minimum_day_booking;
								$minimum_multiple_day       =   $saved_settings->global_booking_minimum_number_days;
							}
							
							print('<input type="hidden" id="wapbk_enable_min_multiple_day" name="wapbk_enable_min_multiple_day" value="'.$enable_min_multiple_day.'">');
							print('<input type="hidden" id="wapbk_multiple_day_minimum" name="wapbk_multiple_day_minimum" value="'.$minimum_multiple_day.'">');	
							
							$booking_holidays_string = '"'.str_replace(',', '","', $product_settings['booking_product_holiday']).'"';
							
							print('<input type="hidden" name="wapbk_admin_booking_holidays_'.$_POST['order_item_id'].'" id="wapbk_admin_booking_holidays_'.$_POST['order_item_id'].'" value=\''.$booking_holidays_string.'\'>');
							
							$default = "Y"; //Default settings
							if ( ( isset( $product_settings['booking_recurring_booking'] ) && $product_settings['booking_recurring_booking'] == "on" ) || ( isset( $product_settings['booking_specific_booking'] ) && $product_settings['booking_specific_booking'] == "on" ) )
							    $default = "N";
							
							foreach ( $product_settings['booking_recurring'] as $wkey => $wval ) {
								if ( $default == "Y" ) {
									print( '<input type="hidden" name="wapbk_admin_'.$wkey.'_'.$_POST['order_item_id'].'" id="wapbk_admin_'.$wkey.'_'.$_POST['order_item_id'].'" value="on">' );
								}
								else {
									if ( $product_settings['booking_recurring_booking'] == "on" ) {
										print( '<input type="hidden" name="wapbk_admin_'.$wkey.'_'.$_POST['order_item_id'].'" id="wapbk_admin_'.$wkey.'_'.$_POST['order_item_id'].'" value="'.$wval.'">' );
									} else {
										print( '<input type="hidden" name="wapbk_admin_'.$wkey.'_'.$_POST['order_item_id'].'" id="wapbk_admin_'.$wkey.'_'.$_POST['order_item_id'].'" value="">' );
									}
								}
							}
							
							if ( isset( $product_settings['booking_time_settings'] ) ) {
								print( '<input type="hidden" name="wapbk_admin_booking_times_'.$_POST['order_item_id'].'" id="wapbk_admin_booking_times_'.$_POST['order_item_id'].'" value="YES">' );
							} 
							else {
								print( '<input type="hidden" name="wapbk_admin_booking_times_'.$_POST['order_item_id'].'" id="wapbk_admin_booking_times_'.$_POST['order_item_id'].'" value="NO">' );
							}
							
							if ( isset( $product_settings['booking_enable_multiple_day'] ) ) {
								print( '<input type="hidden" id="wapbk_admin_multiple_day_booking_'.$_POST['order_item_id'].'" name="wapbk_admin_multiple_day_booking_'.$_POST['order_item_id'].'" value="'.$product_settings['booking_enable_multiple_day'].'"/>' );
							} 
							else {
								print( '<input type="hidden" id="wapbk_admin_multiple_day_booking_'.$_POST['order_item_id'].'" name="wapbk_admin_multiple_day_booking_'.$_POST['order_item_id'].'" value=""/>' );
							}
								
							// set mindate and maxdate based on the Bookable time period
							if ( isset( $product_settings['booking_date_range_type'] ) && $product_settings['booking_date_range_type'] == 'fixed_range' ) {
								$min_date = $days = '';

								if ( isset( $product_settings['booking_start_date_range'] ) ) { // check if the range start date is a past dat, if yes then we need to set the mindate to today
									$current_time   =   current_time( 'timestamp' );
									$current_date  =   date("d-m-Y", $current_time);
									$range_start   =   date("d-m-Y",strtotime($product_settings['booking_start_date_range']));
									$current_date1 =   new DateTime($current_date);
									$range_start1  =   new DateTime($range_start);
							
									$diff_days     =   $current_date1->diff($range_start1);
									
									// the diff days is always a positive number. However, if the start date is in the future then invert = 0, else = 1
									if ( $diff_days->invert == 0 ) {
										$min_date = date( "j-n-Y", strtotime( $product_settings['booking_start_date_range'] ) );
									}
									else {
										$min_date = date( "j-n-Y", strtotime( $current_date) );
									}
									$date2 = new DateTime( $min_date );
								}
								
								if ( isset( $product_settings['booking_start_date_range'] ) && isset( $product_settings['booking_end_date_range'] ) ) {
								    // calculate the end date of the range based on the number of years it is set to recurr
								    if ( isset( $product_settings[ 'recurring_booking_range' ] ) && 'on' == $product_settings[ 'recurring_booking_range' ] ) {
								        if ( isset( $product_settings[ 'booking_range_recurring_years' ] ) && is_numeric( $product_settings[ 'booking_range_recurring_years' ] ) && $product_settings[ 'booking_range_recurring_years' ] > 0 ) {
								            $end_date = date( 'j-n-Y', strtotime( '+' . $product_settings[ 'booking_range_recurring_years' ] . 'years', strtotime( $product_settings[ 'booking_end_date_range' ] ) ) );
								        }
								    }
								    
								    // create a hidden field which will contain a list of start and end ranges ( for the years to come).
								    $fixed_date_range = '"' . $product_settings[ 'booking_start_date_range' ] . '","' . $product_settings[ 'booking_end_date_range' ] . '"';
								    
								    if ( isset( $product_settings[ 'booking_range_recurring_years' ] ) && is_numeric( $product_settings[ 'booking_range_recurring_years' ] ) && $product_settings[ 'booking_range_recurring_years' ] > 0 ) {
								        for ( $i = 1; $i <= $product_settings[ 'booking_range_recurring_years' ]; $i++ ) {
								            $start_range = date( 'j-n-Y', strtotime( '+' . $i . 'years', strtotime( $product_settings[ 'booking_start_date_range' ] ) ) );
								            $end_range = date( 'j-n-Y', strtotime( '+' . $i . 'years', strtotime( $product_settings[ 'booking_end_date_range' ] ) ) );
								            $fixed_date_range .= ',"' . $start_range . '","' . $end_range . '"';
								        }
								    }
								    print( "<input type='hidden' id='wapbk_admin_fixed_range_" . $_POST[ 'order_item_id' ] . "' name='wapbk_admin_fixed_range_" . $_POST[ 'order_item_id' ] . "' value='" . $fixed_date_range . "'>" );
								    
								    // set the maxdate to the end date of the range for the last year to be enabled
								    $days = date ( "j-n-Y", strtotime ( $end_date ) );
								    
								}
							}
							else {
								$min_date = $days = '';
								if ( isset( $product_settings['booking_minimum_number_days'] ) ) {
									$current_time      =   current_time( 'timestamp' ); // Wordpress Time
									$advance_seconds   =   $product_settings['booking_minimum_number_days'] *60 *60; // Convert the advance period to seconds and add it to the current time
									$cut_off_timestamp =   $current_time + $advance_seconds;
									$cut_off_date      =   date("d-m-Y", $cut_off_timestamp);
									$min_date          =   date("j-n-Y",strtotime($cut_off_date));
								}
								if ( isset( $product_settings['booking_maximum_number_days'] ) ) {
									$days = $product_settings['booking_maximum_number_days'];
								}
							}
							print( '<input type="hidden" name="wapbk_admin_minimumOrderDays_'.$_POST['order_item_id'].'" id="wapbk_admin_minimumOrderDays_'.$_POST['order_item_id'].'" value="'.$min_date.'">' );
							print( '<input type="hidden" name="wapbk_admin_number_of_dates_'.$_POST['order_item_id'].'" id="wapbk_admin_number_of_dates_'.$_POST['order_item_id'].'" value="'.$days.'">' );
							
							if ( isset( $product_settings['booking_enable_time'] ) ) {
								print( '<input type="hidden" name="wapbk_admin_bookingEnableTime_'.$_POST['order_item_id'].'" id="wapbk_admin_bookingEnableTime_'.$_POST['order_item_id'].'" value="'.$product_settings['booking_enable_time'].'">' );
							} 
							else {
								print( '<input type="hidden" name="wapbk_admin_bookingEnableTime_'.$_POST['order_item_id'].'" id="wapbk_admin_bookingEnableTime_'.$_POST['order_item_id'].'" value="">' );
							}
							
							if ( isset( $product_settings['booking_recurring_booking'] ) ) {
								print( '<input type="hidden" name="wapbk_admin_recurringDays_'.$_POST['order_item_id'].'" id="wapbk_admin_recurringDays_'.$_POST['order_item_id'].'" value="'.$product_settings['booking_recurring_booking'].'">' );
							} 
							else {
								print('<input type="hidden" name="wapbk_admin_recurringDays_'.$_POST['order_item_id'].'" id="wapbk_admin_recurringDays_'.$_POST['order_item_id'].'" value="">');
							}
							
							if ( isset( $product_settings['booking_specific_booking'] ) ) {
								print( '<input type="hidden" name="wapbk_admin_specificDates_'.$_POST['order_item_id'].'" id="wapbk_admin_specificDates_'.$_POST['order_item_id'].'" value="'.$product_settings['booking_specific_booking'].'">' );
							}
							else {
								print( '<input type="hidden" name="wapbk_admin_specificDates_'.$_POST['order_item_id'].'" id="wapbk_admin_specificDates_'.$_POST['order_item_id'].'" value="">' );
							}
				
							$lockout_query   =   "SELECT DISTINCT start_date FROM `".$wpdb->prefix."booking_history`
										         WHERE post_id= %d
										         AND total_booking > 0
										         AND available_booking = 0";
							$results_lockout =   $wpdb->get_results ( $wpdb->prepare($lockout_query,$prod_id) );
						
							$lockout_query   =   "SELECT DISTINCT start_date FROM `".$wpdb->prefix."booking_history`
												 WHERE post_id= %d
												 AND available_booking > 0";
				
							$results_lock    =   $wpdb->get_results ($wpdb->prepare($lockout_query,$prod_id));
							$lockout_date    =   '';
					
							foreach( $results_lock as $key => $value ) {
								$start_date     =   $value->start_date;
								$bookings_done  =   $this->bkap_get_date_lockout( $start_date, $prod_id );
								
								if( $bookings_done >= $product_settings['booking_date_lockout'] ) {
									$lockout       = explode( "-", $start_date );
									$lockout_date .= '"'.intval($lockout[2])."-".intval($lockout[1])."-".$lockout[0].'",';
								}
							}
				
							$lockout_str = substr( $lockout_date, 0, strlen( $lockout_date )-1 );
							foreach ( $results_lockout as $k => $v ) {
								foreach( $results_lock as $key => $value ) {
									if ( $v->start_date == $value->start_date ) {
										$date_lockout         = "SELECT COUNT(start_date) FROM `".$wpdb->prefix."booking_history`
														        WHERE post_id= %d
														        AND start_date= %s
														        AND available_booking = 0";
										$results_date_lock    = $wpdb->get_results($wpdb->prepare($date_lockout,$prod_id,$v->start_date));
									
										if ( $product_settings['booking_date_lockout'] > $results_date_lock[0]->{'COUNT(start_date)'} ) unset( $results_lockout[ $k ] );	
									} 
								}
							}

							$lockout_dates_str = "";

							foreach ( $results_lockout as $k => $v ) {
								$lockout_temp       =   $v->start_date;
								$lockout            =   explode("-",$lockout_temp);
								$lockout_dates_str .=   '"'.intval($lockout[2])."-".intval($lockout[1])."-".$lockout[0].'",';
								$lockout_temp       =   "";
							}
							
							$lockout_dates_str      =   substr($lockout_dates_str,0,strlen($lockout_dates_str)-1);
							$lockout_dates          =   $lockout_dates_str.",".$lockout_str;
							print( '<input type="hidden" name="wapbk_admin_lockout_days_'.$_POST['order_item_id'].'" id="wapbk_admin_lockout_days_'.$_POST['order_item_id'].'" value=\''.$lockout_dates.'\'>' );
							$todays_date     = date( 'Y-m-d' );
				
							$query_date      = "SELECT DATE_FORMAT(start_date,'%d-%c-%Y') as start_date,DATE_FORMAT(end_date,'%d-%c-%Y') as end_date FROM ".$wpdb->prefix."booking_history WHERE start_date >='".$todays_date."' AND post_id = '".$prod_id."'";
							$results_date    = $wpdb->get_results($query_date);
						
							$dates_new       = array();
							$booked_dates    = array();
							
							foreach( $results_date as $k => $v ) {
								$start_date  = $v->start_date;
								$end_date    = $v->end_date;
								$dates       = bkap_common::bkap_get_betweendays( $start_date, $end_date );
								$dates_new   = array_merge( $dates, $dates_new );
							}
						
						    //	Enable the start date for the booking period for checkout
							foreach ( $results_date as $k => $v ){
								$start_date     =   $v->start_date;
								$end_date       =   $v->end_date;
								$new_start      =   strtotime( "+1 day", strtotime( $start_date ) );
								$new_start      =   date( "d-m-Y", $new_start );
								$dates          =   bkap_common::bkap_get_betweendays( $new_start, $end_date );
								$booked_dates   =   array_merge( $dates,$booked_dates );
							}
					
							$dates_new_arr       =   array_count_values( $dates_new );
							$booked_dates_arr    =   array_count_values( $booked_dates );
							$lockout             =   "";
							
							if ( isset( $product_settings['booking_date_lockout'] ) )   {
								$lockout         =   $product_settings['booking_date_lockout'];
							}
				
							$new_arr_str =   '';
							
							foreach( $dates_new_arr as $k => $v ) {
								if( $v >= $lockout && $lockout != 0 ) {
									$date_temp     = $k;
									$date          = explode("-",$date_temp);
									$new_arr_str  .= '"'.intval($date[0])."-".intval($date[1])."-".$date[2].'",';
									$date_temp     = "";
								}
							}
							
							$new_arr_str = substr( $new_arr_str, 0, strlen( $new_arr_str )-1 );
							print( "<input type='hidden' id='wapbk_admin_hidden_booked_dates_".$_POST['order_item_id']."' name='wapbk_admin_hidden_booked_dates_".$_POST['order_item_id']."' value='".$new_arr_str."'/>" );
							
							//checkout calendar booked dates
							$blocked_dates       =   array();
							$booked_dates_str    =   "";
							
							foreach ( $booked_dates_arr as $k => $v ) {
								if( $v >= $lockout && $lockout != 0 ) {
									$date_temp                     =   $k;
									$date                          =   explode( "-", $date_temp );
									$date_without_zero_prefixed    =   intval( $date[0])."-".intval( $date[1] )."-".$date[2];
									$booked_dates_str             .=   '"'.intval( $date[0] )."-".intval( $date[1] )."-".$date[2].'",';
									$date_temp                     =   "";
									$blocked_dates[]               =   $date_without_zero_prefixed;
								}
							}
							
							if ( isset( $booked_dates_str ) ) {
								$booked_dates_str   =   substr( $booked_dates_str, 0, strlen( $booked_dates_str )-1 );
							} else {
								$booked_dates_str   =   "";
							}
							
							print( "<input type='hidden' id='wapbk_admin_hidden_booked_dates_checkout_".$_POST['order_item_id']."' name='wapbk_admin_hidden_booked_dates_checkout_".$_POST['order_item_id']."' value='".$booked_dates_str."'/>" );
							print( '<input type="hidden" id="wapbk_admin_hidden_date_'.$_POST['order_item_id'].'" name="wapbk_admin_hidden_date_'.$_POST['order_item_id'].'" />' );
							print( '<input type="hidden" id="wapbk_admin_hidden_date_checkout_'.$_POST['order_item_id'].'" name="wapbk_admin_hidden_date_checkout_'.$_POST['order_item_id'].'" />' );
							print( '<input type="hidden" id="wapbk_admin_diff_days_'.$_POST['order_item_id'].'" name="wapbk_admin_hidden_diff_days_'.$_POST['order_item_id'].'" />' );
						endif;
					
					$method_to_show    =   'bkap_check_for_time_slot_admin';
					
					$options_checkin   =   $options_checkout = array();
					$js_code           =   $blocked_dates_hidden_var = '';
					$block_dates       =   array();
					$block_dates       =   (array) apply_filters( 'bkap_block_dates', $prod_id , $blocked_dates );
					if ( isset( $block_dates ) && count( $block_dates ) > 0) {
						$i        =   1;
						$bvalue   =   array();
						$add_day  =   '';
						$same_day =   '';
						
						foreach ( $block_dates as $bkey => $bvalue ) {
							if ( is_array( $bvalue ) && isset( $bvalue['dates'] ) && count( $bvalue['dates'] ) > 0 ) {
								$blocked_dates_str  =   '"'.implode('","', $bvalue['dates']).'"';
							}
							else {
								$blocked_dates_str  =   "";
							}
							
							$field_name  =   $i;
							
							if ( ( is_array( $bvalue ) && isset( $bvalue['field_name'] ) && $bvalue['field_name'] != '' ) ) {
								$field_name =   $bvalue['field_name'];
							}
							
							$fld_name        =   'woobkap_'.str_replace(' ','_', $field_name);
							$fld_name_admin  =   $fld_name.'_'.$_POST['order_item_id'];
							print( "<input type='hidden' id='".$fld_name_admin."' name='".$fld_name_admin."' value='".$blocked_dates_str."'/>" );
							$i++;
							
							if( is_array( $bvalue ) && isset( $bvalue['add_days_to_charge_booking'] ) ) {
								$add_day    =   $bvalue['add_days_to_charge_booking'];
							}
							
							if( $add_day == '' ) {
								$add_day    =   0;
							}
							
							print( "<input type='hidden' id='add_days_".$_POST['order_item_id']."' name='add_days_".$_POST['order_item_id']."' value='".$add_day."'/>" );
							
							if( is_array( $bvalue ) && isset( $bvalue['same_day_booking'] ) ) {
								$same_day   =   $bvalue['same_day_booking'];
							}
							
							print( "<input type='hidden' id='wapbk_admin_same_day_".$_POST['order_item_id']."' name='wapbk_admin_same_day_".$_POST['order_item_id']."' value='".$same_day."'/>" );
						}
							
						if ( isset( $bvalue['date_label'] ) && $bvalue['date_label'] != '' ) {
							$date_label  =   $bvalue['date_label'];
						}
						else {
							$date_label  =   'Unavailable for Booking';
						}
						
        				$js_code        =     '
        						              var '.$fld_name_admin.' = eval("["+jQuery("#'.$fld_name_admin.'").val()+"]");
        						              for (i = 0; i < '.$fld_name_admin.'.length; i++) {
        						      	          if( jQuery.inArray(d + "-" + (m+1) + "-" + y,'.$fld_name_admin.') != -1 ) {
        								          return [false, "", "'.$date_label.'"];
        							              }
        						              }
        						              ';
						
						$js_block_date  =     '
                        					  var '.$fld_name_admin.' = eval("["+jQuery("#'.$fld_name_admin.'").val()+"]");
						                      var date = new_end = new Date(CheckinDate);
						                      var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
					                           	for (var i = 1; i<= count;i++) {
							                     if( jQuery.inArray(d + "-" + (m+1) + "-" + y,'.$fld_name_admin.') != -1 ) {
								                    jQuery("#wapbk_hidden_date_checkout").val("");
								                    jQuery("#booking_calender_checkout").val("");
								                    jQuery( ".single_add_to_cart_button" ).hide();
								                    jQuery( ".quantity" ).hide();
								                    CalculatePrice = "N";
								                    alert("Some of the dates in the selected range are on rent. Please try another date range.");
								                    break;
							                     }
						                          new_end = new Date(ad(new_end,1));
						                          var m = new_end.getMonth(), d = new_end.getDate(), y = new_end.getFullYear();
						                        }
									           ';
					}
					
					if ( isset( $product_settings['booking_enable_multiple_day'] ) && $product_settings['booking_enable_multiple_day'] == 'on' ) {
						$meta_id_next                     =   $meta_id + 1;
						$meta_ids[ $cart_item_key ][ $i ] =   $meta_id_next;
						
						echo '<tr data-meta_id="'.$meta_id_next.'"><td><input type="text" name="meta_key[' . $meta_id_next. ']" value="'.strip_tags(get_option("checkout.item-meta-date")).'" /></td><td><input type="text" name="meta_value[' . $meta_id_next. ']" value="" /></td><td width="1%"><button class="remove_order_item_meta button">&times;</button></td></tr>';
						
						$options_checkout[]               =   "minDate: 1";
						$options_checkin[]                =   'onClose: function( selectedDate, inst ) {
						                                        var monthValue = inst.selectedMonth+1;
                                        						var dayValue = inst.selectedDay;
                                        						var yearValue = inst.selectedYear;
                                        						
                                        						var current_sel_dt = dayValue + "-" + monthValue + "-" + yearValue;
                                        						
                                        						jQuery("#wapbk_admin_hidden_date_'.$_POST['order_item_id'].'").val(current_sel_dt);
                                        						jQuery( "tr[data-meta_id=\"'.$meta_id_next.'\"]" ).show();
                                        						if(jQuery("#admin_block_option_enabled_'.$_POST['order_item_id'].'").val() == "on") {
                                        							var nod= parseInt(jQuery("#admin_block_option_number_of_day_'.$_POST['order_item_id'].'").val(),10);										
                                        							if(jQuery("#wapbk_admin_hidden_date_'.$_POST['order_item_id'].'").val() != "") {
                                        								var num_of_day= jQuery("#admin_block_option_number_of_day_'.$_POST['order_item_id'].'").val();
                                        								var split = jQuery("#wapbk_admin_hidden_date_'.$_POST['order_item_id'].'").val().split("-");
                                        								split[1] = split[1] - 1;		
                                        								var minDate = new Date(split[2],split[1],split[0]);
                                        									
                                        								minDate.setDate(minDate.getDate() + nod ); 
                                        								console.log(minDate);
                                        														 
                                        			   					jQuery("input[name=\"meta_value['.$meta_id_next.']\"]").datepicker("setDate",minDate);
                                        							}
                                        						} else {
                                        							if (jQuery("#wapbk_admin_hidden_date_'.$_POST['order_item_id'].'").val() != "") {
                                        								if(jQuery("#wapbk_admin_same_day_'.$_POST['order_item_id'].'").val() == "on") {
                                        									if (jQuery("#wapbk_admin_hidden_date_'.$_POST['order_item_id'].'").val() != "") {
                                        										var split = jQuery("#wapbk_admin_hidden_date_'.$_POST['order_item_id'].'").val().split("-");
                                        										split[1] = split[1] - 1;
                                        										var minDate = new Date(split[2],split[1],split[0]);
                                        										minDate.setDate(minDate.getDate());
                                        										jQuery( "input[name=\"meta_value['.$meta_id_next.']\"]" ).datepicker( "option", "minDate", minDate);
                                        									}
                                        								} else {
                                        									var split = jQuery("#wapbk_admin_hidden_date_'.$_POST['order_item_id'].'").val().split("-");
                                        									split[1] = split[1] - 1;
                                        									var minDate = new Date(split[2],split[1],split[0]);
                                        							
                                        									if (jQuery("#wapbk_enable_min_multiple_day").val() == "on") {
                                                                                var minimum_multiple_day = jQuery("#wapbk_multiple_day_minimum").val();
                                                                                if(minimum_multiple_day == 0 || !minimum_multiple_day) {
                                                                                	minimum_multiple_day = 1;
                                                                                }
                                                                            	minDate.setDate(minDate.getDate() + parseInt(minimum_multiple_day));
                                                                           	} 
                                                                           	else {
                                                                            	minDate.setDate(minDate.getDate() + 1);
                                                                            }
                                        									jQuery( "input[name=\"meta_value['.$meta_id_next.']\"]" ).datepicker( "option", "minDate", minDate);
                                        								}
                                        							}
                                        						}
                                        						
						                                       }';
						$options_checkout[]               =   "onSelect: bkap_get_per_night_price";
						$options_checkin[]                =   "onSelect: bkap_set_checkin_date";
						$options_checkout[]               =   "beforeShowDay: bkap_check_booked_dates";
						$options_checkin[]                =   "beforeShowDay: bkap_check_booked_dates";
					}
					else if( isset( $product_settings['booking_enable_time'] ) && $product_settings['booking_enable_time'] == 'on' ) {
						$meta_id_next                     =   $meta_id + 1;
						$meta_ids[ $cart_item_key ][ $i ] =   $meta_id_next;
						
						echo '<tr data-meta_id="'.$meta_id_next.'">
								<td><input type="text" name="meta_key[' . $meta_id_next. ']" value="'.get_option('book.time-label').'" /></td>
								<td><input type="text" name="meta_value[' . $meta_id_next. ']" value="" /></td>
								<td width="1%"><button class="remove_order_item_meta button">&times;</button></td>
								</tr>';
						
						$options_checkin[]    =   "beforeShowDay: bkap_show_book";
						$options_checkin[]    =   "onSelect: bkap_show_times";
						$options_checkin[]    =   'onClose: function( selectedDate, inst ) {
                            						var monthValue = inst.selectedMonth+1;
                            						var dayValue = inst.selectedDay;
                            						var yearValue = inst.selectedYear;
                            						
                            						var current_sel_dt = dayValue + "-" + monthValue + "-" + yearValue;
                            						
                            						jQuery("#wapbk_admin_hidden_date_'.$_POST['order_item_id'].'").val(current_sel_dt);
                            						jQuery( "tr[data-meta_id=\"'.$meta_id_next.'\"]" ).show();
                            					  }';
					} else {
						$options_checkin[]    =   'onClose: function( selectedDate, inst ) {
                            						var monthValue = inst.selectedMonth+1;
                            						var dayValue = inst.selectedDay;
                            						var yearValue = inst.selectedYear;
                            						
                            						var current_sel_dt = dayValue + "-" + monthValue + "-" + yearValue;
                            						
                            						jQuery("#wapbk_admin_hidden_date_'.$_POST['order_item_id'].'").val(current_sel_dt);
                            					  }';
						$options_checkin[]    =  "beforeShowDay: bkap_show_book";
						$options_checkin[]    =   "onSelect: bkap_show_times";
					}	
					
					$options_checkin_str   =   '';
					
					if ( count( $options_checkin ) > 0 ) {
						$options_checkin_str  =   implode( ',', $options_checkin );
					}
					
					$options_checkout_str  =   '';
					
					if ( count( $options_checkout ) > 0 ) {
						$options_checkout_str = implode( ',', $options_checkout );
					}
					
					$meta_ids_str  =   '';

					if ( count( $meta_ids ) > 0 ) {
						$meta_ids_str = implode( ',', $meta_ids[ $cart_item_key ] );
					}
					echo '<input type="hidden" name="meta_ids'.$cart_item_key.'" id="meta_ids'.$cart_item_key.'" value="'.$meta_ids_str.'" />';

					print('
        					<script type="text/javascript">
        					jQuery(document).ready(function() {	
        							jQuery.extend(jQuery.datepicker, { afterShow: function(event) {
        								jQuery.datepicker._getInst(event.target).dpDiv.css("z-index", 9999);
        							}});
        						
        						var today = new Date();
        						jQuery(function() {
        						jQuery( "tr[data-meta_id=\"'.$meta_id_next.'\"]" ).hide();
        					    jQuery( "input[name=\"meta_value['.$meta_id.']\"]" ).datepicker({
        							beforeShow: avd,
        							dateFormat: "'.$saved_settings->booking_date_format.'",						
        							numberOfMonths: parseInt('.$saved_settings->booking_months.'),
        							'.$options_checkin_str.' ,
        					}).focus(function (event) {
        						jQuery.datepicker.afterShow(event);
        					});
        					});
        					jQuery( "input[name=\"meta_value['.$meta_id.']\"]" ).wrap("<div class=\"hasDatepicker\"></div>");
        					jQuery( "input[name=\"meta_value['.$meta_id.']\"]" ).attr("id","admin_booking_calender_'.$meta_id.'");
        					jQuery( "input[name=\"meta_value['.$meta_id_next.']\"]" ).attr("id","admin_booking_calender_checkout_'.$meta_id_next.'");
        					});
        				
					   ' );
					
					if ( isset( $product_settings['booking_enable_multiple_day'] ) && $product_settings['booking_enable_multiple_day'] == 'on' ) {
						print ( 'jQuery("input[name=\"meta_value['.$meta_id_next.']\"]").datepicker({
									dateFormat: "'.$saved_settings->booking_date_format.'",
									numberOfMonths: parseInt('.$saved_settings->booking_months.'),
									'.$options_checkout_str.' ,
									onClose: function( selectedDate ) {
									jQuery( "input[name=\"meta_value['.$meta_id.']\"]" ).datepicker( "option", "maxDate", selectedDate );
						},
						}).focus(function (event) {
							jQuery.datepicker.afterShow(event);
						});
						jQuery( "input[name=\"meta_value['.$meta_id_next.']\"]" ).wrap("<div class=\"hasDatepicker\"></div>");
						' );
					}
					
					print( '//********************************************************************************
                            // It will used to show the booked dates in the calendar for multiple day booking.
                            //********************************************************************************
					
					function bkap_check_booked_dates(date) {
						var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
						var holidayDates = eval("["+jQuery("#wapbk_admin_booking_holidays_'.$_POST['order_item_id'].'").val()+"]");
						var globalHolidays = eval("["+jQuery("#wapbk_admin_booking_global_holidays_'.$_POST['order_item_id'].'").val()+"]");
						var bookedDates=eval("[" + jQuery("#wapbk_admin_hidden_booked_dates_'.$_POST['order_item_id'].'").val() + "]");
						var bookedDatesCheckout = eval("["+jQuery("#wapbk_admin_hidden_booked_dates_checkout_'.$_POST['order_item_id'].'").val()+"]");

						var block_option_start_day= jQuery("#admin_block_option_start_day_'.$_POST['order_item_id'].'").val();
					 	var block_option_price= jQuery("#admin_block_option_price_'.$_POST['order_item_id'].'").val();
					
						for (iii = 0; iii < globalHolidays.length; iii++) {
							if( jQuery.inArray(d + "-" + (m+1) + "-" + y,globalHolidays) != -1 ) {
								return [false, "", "Holiday"];
							}
						}
						for (ii = 0; ii < holidayDates.length; ii++) {
							if( jQuery.inArray(d + "-" + (m+1) + "-" + y,holidayDates) != -1 ) {
								return [false, "", "Holiday"];
							}
						}
						var id_booking = jQuery(this).attr("id");
						if (id_booking == "admin_booking_calender_'.$meta_id.'" || id_booking == "inline_calendar") {
							for (iii = 0; iii < bookedDates.length; iii++) {
								if( jQuery.inArray(d + "-" + (m+1) + "-" + y,bookedDates) != -1 ) {
									return [false, "", "Unavailable for Booking"];
								}
							}
						}	
						if (id_booking == "admin_booking_calender_checkout_'.$meta_id_next.'" || id_booking == "inline_calendar_checkout")  {
							for (iii = 0; iii < bookedDatesCheckout.length; iii++) {
								if( jQuery.inArray(d + "-" + (m+1) + "-" + y,bookedDatesCheckout) != -1 ) {
									return [false, "", "Unavailable for Booking"];
								}
							}
						}
						var block_option_enabled= jQuery("#admin_block_option_enabled_'.$_POST['order_item_id'].'").val();
	
						if (block_option_enabled =="on") {
							if ( id_booking == "admin_booking_calender_'.$meta_id.'" || id_booking == "inline_calendar" ) {
						   		if (block_option_start_day == date.getDay() || block_option_start_day == "any_days") {
					              return [true];
					            } else {
									return [false];
					            }
				       		}
				       		var bcc_date=jQuery( "input[name=\"meta_value['.$meta_id_next.']\"]").datepicker("getDate");
							
							if(bcc_date != null) {
								var dd = bcc_date.getDate();
								var mm = bcc_date.getMonth()+1; //January is 0!
								var yyyy = bcc_date.getFullYear();
								var checkout = dd + "-" + mm + "-"+ yyyy;
								jQuery("#wapbk_admin_hidden_date_checkout_'.$_POST['order_item_id'].'").val(checkout);

						   		if (id_booking == "admin_booking_calender_checkout_'.$meta_id_next.'" || id_booking == "inline_calendar_checkout"){

				       			if (Date.parse(bcc_date) === Date.parse(date)){
				       					return [true];
				       			}else {
				       					return [false];
				       			}
							}
				       		}
				       	}
						'.$js_code.'
					    // if a fixed date range is enabled, then check if the date lies in the range and enable/disable accordingly
					    if ( jQuery( "#wapbk_admin_fixed_range_' . $_POST[ 'order_item_id' ] .'" ).length > 0 ) {
					       var in_range = fixed_range( date );
					       return in_range;
					    }
						return [true];
					}
					
					//************************************************************************************************************
                    //This function disables the dates in the calendar for holidays, global holidays set and for which lockout is reached for Single day booking feature.
                    //************************************************************************************************************
					
					function bkap_show_book(date) {
						var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
						var deliveryDates = eval("["+jQuery("#wapbk_admin_booking_dates_'.$_POST['order_item_id'].'").val()+"]");
						
						var holidayDates = eval("["+jQuery("#wapbk_admin_booking_holidays_'.$_POST['order_item_id'].'").val()+"]");
							
						var globalHolidays = eval("["+jQuery("#wapbk_admin_booking_global_holidays_'.$_POST['order_item_id'].'").val()+"]");
						
						//Lockout Dates
						var lockoutdates = eval("["+jQuery("#wapbk_admin_lockout_days_'.$_POST['order_item_id'].'").val()+"]");
						
						var bookedDates = eval("["+jQuery("#wapbk_admin_hidden_booked_dates_'.$_POST['order_item_id'].'").val()+"]");
						var dt = new Date();
						var today = dt.getMonth() + "-" + dt.getDate() + "-" + dt.getFullYear();
						for (iii = 0; iii < lockoutdates.length; iii++) {
							if( jQuery.inArray(d + "-" + (m+1) + "-" + y,lockoutdates) != -1 ) {
								return [false, "", "Booked"];
	
							}
						}	
						
						for (iii = 0; iii < globalHolidays.length; iii++) {
							if( jQuery.inArray(d + "-" + (m+1) + "-" + y,globalHolidays) != -1 ) {
								return [false, "", "Holiday"];
							}
						}
						
						for (ii = 0; ii < holidayDates.length; ii++) {
							if( jQuery.inArray(d + "-" + (m+1) + "-" + y,holidayDates) != -1 ) {
								return [false, "", "Holiday"];
							}
						}
					
						for (i = 0; i < bookedDates.length; i++) {
							if( jQuery.inArray(d + "-" + (m+1) + "-" + y,bookedDates) != -1 ) {
								return [false, "", "Unavailable for Booking"];
							}
						}
						 	
						for (i = 0; i < deliveryDates.length; i++) {
							if( jQuery.inArray(d + "-" + (m+1) + "-" + y,deliveryDates) != -1 ) {
								return [true];
							}
						}
                        // if a fixed date range is enabled, then check if the date lies in the range and enable/disable accordingly
					    if ( jQuery( "#wapbk_admin_fixed_range_' . $_POST[ 'order_item_id' ] .'" ).length > 0 ) {
					       var in_range = fixed_range( date );
					       return in_range;
					    }
						var day = "booking_weekday_" + date.getDay();
						var name = day+"_"+'.$_POST['order_item_id'].';
						if (jQuery("#wapbk_admin_"+name).val() == "on") {
							return [true];
						}
						return [false];
					}
					    
					//***********************************************************************************************************
                    //This function calls an ajax when a date is selected which displays the time slots on frontend product page.
                    //***********************************************************************************************************
					    
					function bkap_show_times(date,inst) {
						var monthValue = inst.selectedMonth+1;
						var dayValue = inst.selectedDay;
						var yearValue = inst.selectedYear;

						var current_dt = dayValue + "-" + monthValue + "-" + yearValue;
						var sold_individually = jQuery("#wapbk_sold_individually_'.$_POST['order_item_id'].'").val();
						jQuery("#wapbk_admin_hidden_date_'.$_POST['order_item_id'].'").val(current_dt);
						if (jQuery("#wapbk_admin_bookingEnableTime_'.$_POST['order_item_id'].'").val() == "on" && jQuery("#wapbk_admin_booking_times_'.$_POST['order_item_id'].'").val() != "") {
							var time_slots_arr = jQuery("#wapbk_admin_booking_times_'.$_POST['order_item_id'].'").val();
							var data = {
								current_date: current_dt,
								post_id: "'.$prod_id.'",
								action: "'.$method_to_show.'"
								
								};
										
								jQuery.post("'.get_admin_url().'admin-ajax.php", data, function(response) {
								jQuery( "tr[data-meta_id=\"'.$meta_id_next.'\"]" ).show();
						
								var select = jQuery("<select style=\"width:100%;\">");
								select.append(jQuery("<option>").val("Choose a Time").html("'.get_option('book.time-select-option').'"));
							
								var time_slots = response.split("|");
						
								for (var i = 0; i <= time_slots.length; ++i)  {
									if(time_slots[i] != "" && time_slots[i] != null)
										select.append(jQuery("<option>").val(time_slots[i]).html(time_slots[i]));
								}
								 
								select.val(1).attr({name: "meta_value['.$meta_id_next.']"}).change(function(){
								    
								});
								jQuery("input[name=\"meta_value['.$meta_id_next.']\"]").replaceWith(select);
								jQuery( "#ajax_img" ).hide();
								jQuery("#show_time_slot").html(response);
								jQuery("#time_slot").change(function()
								{
									if ( jQuery("#time_slot").val() != "" ) {
										jQuery( ".single_add_to_cart_button" ).show();
										if(sold_individually == "yes") {
											jQuery( ".quantity" ).hide();
										} else {
											jQuery( ".quantity" ).show();
										}
							
									} else if ( jQuery("#time_slot").val() == "" ) {
										jQuery( ".single_add_to_cart_button" ).hide();
										jQuery( ".quantity" ).hide();
									}
								})
								
							});
						} else {
							if ( jQuery("#wapbk_admin_hidden_date_'.$_POST['order_item_id'].'").val() != "" ) {
								var data = {
								current_date: current_dt,
								post_id: "'.$prod_id.'", 
								action: "bkap_insert_admin_date"
								};
								jQuery.post("'.get_admin_url().'/admin-ajax.php", data, function(response)
								{
									jQuery( ".single_add_to_cart_button" ).show();
									if(sold_individually == "yes") {
										jQuery( ".quantity" ).hide();
									} else {
										jQuery( ".quantity" ).show();
									}
							
								});
							} else if ( jQuery("#wapbk_admin_hidden_date_'.$_POST['order_item_id'].'").val() == "" ) {
								jQuery( ".single_add_to_cart_button" ).hide();
								jQuery( ".quantity" ).hide();
							}
						}
					}
					//**********************************************************************************************************************************
                    //This functions checks if the selected date range does not have product holidays or global holidays and sets the hidden date field.
                    //**********************************************************************************************************************************
					    
					function bkap_set_checkin_date(date,inst) {
						var monthValue = inst.selectedMonth+1;
						var dayValue = inst.selectedDay;
						var yearValue = inst.selectedYear;

						var current_dt = dayValue + "-" + monthValue + "-" + yearValue;
						jQuery("#wapbk_admin_hidden_date_'.$_POST['order_item_id'].'").val(current_dt);
						// Check if any date in the selected date range is unavailable
						if (jQuery("#wapbk_admin_hidden_date_'.$_POST['order_item_id'].'").val() != "" ) {
							var CalculatePrice = "Y";
							var split = jQuery("#wapbk_admin_hidden_date_'.$_POST['order_item_id'].'").val().split("-");
							split[1] = split[1] - 1;		
							var CheckinDate = new Date(split[2],split[1],split[0]);
								
							var split = jQuery("#wapbk_admin_hidden_date_checkout_'.$_POST['order_item_id'].'").val().split("-");
							split[1] = split[1] - 1;
							var CheckoutDate = new Date(split[2],split[1],split[0]);
								
							var date = new_end = new Date(CheckinDate);
							var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
								
							var bookedDates = eval("["+jQuery("#wapbk_admin_hidden_booked_dates_'.$_POST['order_item_id'].'").val()+"]");
							var holidayDates = eval("["+jQuery("#wapbk_admin_booking_holidays_'.$_POST['order_item_id'].'").val()+"]");
							var globalHolidays = eval("["+jQuery("#wapbk_admin_booking_global_holidays_'.$_POST['order_item_id'].'").val()+"]");
						
							var count = gd(CheckinDate, CheckoutDate, "days");
							//Locked Dates
							for (var i = 1; i<= count;i++) {
									if( jQuery.inArray(d + "-" + (m+1) + "-" + y,bookedDates) != -1 ) {
										jQuery("#wapbk_admin_hidden_date_'.$_POST['order_item_id'].'").val("");
										jQuery("input[name=\"meta_value['.$meta_id.']\"]").val("");
										jQuery( ".single_add_to_cart_button" ).hide();
										jQuery( ".quantity" ).hide();
										CalculatePrice = "N";
										alert("Some of the dates in the selected range are unavailable. Please try another date range.");
										break;
									}
								new_end = new Date(ad(new_end,1));
								var m = new_end.getMonth(), d = new_end.getDate(), y = new_end.getFullYear();													
								}
							//Global Holidays
							var date = new_end = new Date(CheckinDate);
							var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
						
							for (var i = 1; i<= count;i++) {
									if( jQuery.inArray(d + "-" + (m+1) + "-" + y,globalHolidays) != -1 ) {
										jQuery("#wapbk_admin_hidden_date_'.$_POST['order_item_id'].'").val("");
										jQuery("input[name=\"meta_value['.$meta_id.']\"]").val("");
										CalculatePrice = "N";
										alert("Some of the dates in the selected range are unavailable. Please try another date range.");
										break;
									}
								new_end = new Date(ad(new_end,1));
								var m = new_end.getMonth(), d = new_end.getDate(), y = new_end.getFullYear();													
								}
							//Product Holidays
							var date = new_end = new Date(CheckinDate);
							var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
						
							for (var i = 1; i<= count;i++) {
									if( jQuery.inArray(d + "-" + (m+1) + "-" + y,holidayDates) != -1 ) {
										jQuery("#wapbk_admin_hidden_date_'.$_POST['order_item_id'].'").val("");
										jQuery("input[name=\"meta_value['.$meta_id.']\"]").val("");
										CalculatePrice = "N";
										alert("Some of the dates in the selected range are unavailable. Please try another date range.");
										break;
									}
								new_end = new Date(ad(new_end,1));
								var m = new_end.getMonth(), d = new_end.getDate(), y = new_end.getFullYear();													
								}
							'.$js_block_date.'
						//	if (CalculatePrice == "Y") calculate_price();
						}
					}
					    
                    //**************************************************
                    // This function sets the hidden checkout date for Multiple day booking feature.
                    //***************************************************
					    
					function bkap_get_per_night_price(date,inst) {
						var monthValue = inst.selectedMonth+1;
						var dayValue = inst.selectedDay;
						var yearValue = inst.selectedYear;

						var current_dt = dayValue + "-" + monthValue + "-" + yearValue;
						jQuery("#wapbk_admin_hidden_date_checkout_'.$_POST['order_item_id'].'").val(current_dt);
													
					}
					
					</script>
					');
					
						endif;
					}
				} else {
					echo '<tr data-meta_id="'.$meta_id.'"><td><input type="text" name="meta_key[' . $meta_id . ']" value="" /><textarea name="meta_key[' . $meta_id . ']" value="" /></td><td width="1%"><button class="remove_order_item_meta button">&times;</button></td></tr>';
				}
				die();
				
			}
			
			/************************************************************************************
            * This function displays the timeslots for the selected date on the admin order page.
            ************************************************************************************/
			
			function bkap_check_for_time_slot_admin() {
				
			    if( isset( $_POST['checkin_date'] ) ){
		         	  $checkin_date =   $_POST['checkin_date'];
		          	}else{
		          	 $checkin_date =   '';
				}
				
				$current_date   =   $_POST['current_date'];
				$post_id        =   $_POST['post_id'];
				$drop_down      =   bkap_booking_process::get_time_slot($current_date,$post_id,$checkin_date);
				
				echo $drop_down;
				die();
			}
			
			/*****************************************************************************
            * It delete the item(Product) from the item meta box from the orders page
            * it calls the cancel order function, and cancel booking is done for same.
            *****************************************************************************/
			
			function bkap_woocommerce_ajax_remove_order_item_meta_admin(){
				
			    $args = array(
				    'meta_query' => array(
				                    array( 'key' => $_POST['meta_id'] )
						                 )
				);
				$my_query   =   new WP_Query( $args );
				global $wpdb;
				$results    =   $wpdb->get_results( "select order_item_id from `".$wpdb->prefix."woocommerce_order_itemmeta` where meta_id = ".$_POST['meta_id']." ", ARRAY_A );				
				$order_id   =   $wpdb->get_results( "select order_id from `".$wpdb->prefix."woocommerce_order_items` where order_item_id = ".$results[0]['order_item_id']." ", ARRAY_A );
				
				$this->bkap_woocommerce_cancel_order( $order_id[0]['order_id'],$results[0]['order_item_id'] );
			}
            
            /*************************************************************************
            * It delete the item(Product) from the item List from the orders page
            * it calls the cancel order function, and cancel booking is done for same.
            *************************************************************************/
			
			function bkap_woocommerce_ajax_remove_order_item_admin() {
				global $woocommerce, $wpdb;
		
				check_ajax_referer( 'order-item', 'security' );
			
				$order_item_ids = $_POST['order_item_ids'];
		
				if ( sizeof( $order_item_ids ) > 0 ) {
				    
					if ( is_array( $order_item_ids ) ) {

					    foreach( $order_item_ids as $id ) {
							$order_id = $wpdb->get_results( "select order_id from `".$wpdb->prefix."woocommerce_order_items` where order_item_id = ".$id." ", ARRAY_A );
							$this->bkap_woocommerce_cancel_order($order_id[0]['order_id'],$id);
						}
						
					}
					else {
						$order_id = $wpdb->get_results( "select order_id from `".$wpdb->prefix."woocommerce_order_items` where order_item_id = ".$order_item_ids." ", ARRAY_A );
						$this->bkap_woocommerce_cancel_order($order_id[0]['order_id'],$order_item_ids);
					}
				}
			}
                        
            /******************************************************************************************************************
            * This function deletes booking for the products in order when the order is cancelled or refunded and when item is deleted from the meta.
            *******************************************************************************************************************/
			
			function bkap_woocommerce_cancel_order( $order_id, $order_item_id ){
				global $wpdb,$post;
				$array              =   array();
				$order_obj          =   new WC_order( $order_id );
				$order_items        =   $order_obj->get_items();
			
				$saved_settings     =   json_decode( get_option( 'woocommerce_booking_global_settings' ) );
				$date_format        =   $saved_settings->booking_date_format;
				$select_query       =   "SELECT booking_id FROM `".$wpdb->prefix."booking_order_history`
								        WHERE order_id= %d";
				$results            =   $wpdb->get_results ( $wpdb->prepare( $select_query, $order_id ));
			
				$post_id            =   woocommerce_get_order_item_meta( $order_item_id, "_product_id", true );
				$booking_settings   =   get_post_meta( $post_id, 'woocommerce_booking_settings', true );
				$checkin_date       =   woocommerce_get_order_item_meta( $order_item_id, get_option('book.item-meta-date'), true );
				
				if( $date_format == 'dd/mm/y' ) {
					$date_explode = explode( "/", $checkin_date );
					$start_date = date( 'Y-m-d', mktime( 0, 0, 0, $date_explode[1], $date_explode[0], $date_explode[2] ) );
				}
				else {
					$start_date = date( "Y-m-d", strtotime( $checkin_date ) );
				}

				if ( isset( $booking_settings['booking_enable_multiple_day'] ) && $booking_settings['booking_enable_multiple_day'] == 'on' ) {
					$checkout_date = woocommerce_get_order_item_meta( $order_item_id, strip_tags( get_option('checkout.item-meta-date') ), true );
					
					if( $date_format == 'dd/mm/y' ) {
						$end_date_explode =   explode( "/", $checkout_date );
						$end_date         =   date( 'Y-m-d', mktime( 0, 0, 0, $end_date_explode[1], $end_date_explode[0], $end_date_explode[2] ) );
					}
					else {
						$end_date = date( "Y-m-d", strtotime( $checkout_date ) );	
					}
					
					foreach( $results as $k => $v ) {
						$b[]                  =   $v->booking_id;
						$select_query_post    =   "SELECT post_id,id FROM `".$wpdb->prefix."booking_history`
												  WHERE id= %d AND start_date= %s AND end_date = %s AND post_id= %d";
						$results_post[]       =   $wpdb->get_results($wpdb->prepare($select_query_post,$v->booking_id,$start_date,$end_date,$post_id));	
					}
					
				} 
				else if( isset( $booking_settings['booking_enable_time'] ) && $booking_settings['booking_enable_time'] == 'on' ) {
					$timeslot  =   woocommerce_get_order_item_meta( $order_item_id, get_option('book.item-meta-time'), true );
					$time_slot =   explode( "-", $timeslot );
					$from_time =   date( "G:i", strtotime( $time_slot[0] ) );
					$to_time   =   date( "G:i", strtotime( $time_slot[1] ) );
				
					foreach( $results as $k => $v ) {
						$b[]                  =   $v->booking_id;
						$select_query_post    =   "SELECT post_id,id FROM `".$wpdb->prefix."booking_history`
												  WHERE id= %d AND start_date= %s AND from_time = %s AND to_time = %s AND post_id=%d";
						$results_post[]       =   $wpdb->get_results($wpdb->prepare($select_query_post,$v->booking_id,$start_date,$from_time,$to_time,$post_id));
					}
					
				} else {
				    
					foreach($results as $k => $v) {
						$b[]                  =   $v->booking_id;
						$select_query_post    =   "SELECT post_id,id FROM `".$wpdb->prefix."booking_history`
									              WHERE id= %d AND start_date= %s AND post_id=%d";
						$results_post[]       =   $wpdb->get_results($wpdb->prepare($select_query_post,$v->booking_id,$start_date,$post_id));
					}
					
				}
				
				foreach( $results_post as $k => $v ) {
					$a[ $v[0]->post_id ][] = $v[0]->id;
				}
				
				$i = 0;
				
				foreach( $order_items as $item_key => $item_value ) {
					$product_id    =   bkap_common::bkap_get_product_id( $item_value['product_id'] );
			
					if( in_array( $product_id,(array)$array ) ) {
					} else {
						$booking_settings =   get_post_meta($product_id, 'woocommerce_booking_settings', true);
						$qty              =   $item_value['qty'];
						$result           =   $a[$product_id];
						$e                =   0;
						
						foreach( $result as $k =>$v ) {
							$booking_id  =   $result[ $e ];
							if( $booking_settings['booking_enable_multiple_day'] == 'on' ) {
								$select_data_query  =   "SELECT start_date,end_date FROM `".$wpdb->prefix."booking_history`
														WHERE id='".$booking_id."'";
								$results_data       =   $wpdb->get_results ( $select_data_query );
								$j                  =   0;
								
								foreach( $results_data as $k => $v ) {
									$start_date        =   $results_data[ $j ]->start_date;
									$end_date          =   $results_data[$j]->end_date;
									
									// delete from the booking history table
									$sql_delete_query  =   "DELETE FROM `".$wpdb->prefix."booking_history` WHERE id = '".$booking_id."' AND start_date =	'".$start_date."' AND end_date = '".$end_date."' ";
									$wpdb->query( $sql_delete_query );
									
									$j++;
								}
								
								// delete from the order history table
								$sql_delete_query       =   "DELETE FROM `".$wpdb->prefix."booking_order_history` WHERE booking_id = '".$booking_id."'";
								$wpdb->query( $sql_delete_query );
								
							} else if( $booking_settings['booking_enable_time'] == 'on' ) {
								$type_of_slot = apply_filters( 'bkap_slot_type', $product_id );

								if( $type_of_slot == 'multiple' ) {
									do_action( 'bkap_order_status_cancelled', $order_id, $item_value, $booking_id );
								} else {
									$select_data_query =   "SELECT * FROM `".$wpdb->prefix."booking_history`
									                       WHERE id='".$booking_id."'";
									$results_data      =   $wpdb->get_results ( $select_data_query );
									//print_r($results_data);
									$j                 =   0;
									
									foreach( $results_data as $k => $v ) {
										$start_date   =   $results_data[ $j ]->start_date;
										$from_time    =   $results_data[ $j ]->from_time;
										$to_time      =   $results_data[ $j ]->to_time;
										
										// Update booking history table
										if( $from_time != '' && $to_time != '' || $from_time != '' ) {
											
										    if( $to_time != '' ) {
												$query = "UPDATE `".$wpdb->prefix."booking_history`
												            SET available_booking = available_booking + ".$qty."
												            WHERE
												            id = '".$booking_id."' AND
												            start_date = '".$start_date."' AND
												            from_time = '".$from_time."' AND
												            to_time = '".$to_time."'";
											} else {
												$query = "UPDATE `".$wpdb->prefix."booking_history`
												            SET available_booking = available_booking + ".$qty."
												            WHERE
												            id = '".$booking_id."' AND
												            start_date = '".$start_date."' AND
												            from_time = '".$from_time."'";
											}
											
											$wpdb->query( $query );
										}
										
										$j++;
									}
									
									// delete the record from the order history table
									$sql_delete_query  =   "DELETE FROM `".$wpdb->prefix."booking_order_history` WHERE booking_id = '".$booking_id."'";
									$wpdb->query( $sql_delete_query );
									
								}
							} else {
								$select_data_query  =   "SELECT * FROM `".$wpdb->prefix."booking_history`
								                        WHERE id='".$booking_id."'";
								$results_data       =   $wpdb->get_results ( $select_data_query );
								$j                  =   0;
								
								foreach( $results_data as $k => $v ) {
									$start_date    =   $results_data[ $j ]->start_date;
									$from_time     =   $results_data[ $j ]->from_time;
									$to_time       =   $results_data[ $j ]->to_time;
									
									// Update booking history table
									$query         =   "UPDATE `".$wpdb->prefix."booking_history`
    												    SET available_booking = available_booking + ".$qty."
    												    WHERE
    												    id = '".$booking_id."' AND
    												    start_date = '".$start_date."' AND
    												    from_time = '' AND
    												    to_time = ''";
									$wpdb->query( $query );
								}
								
								// delete from the order history table
								$sql_delete_query   =   "DELETE FROM `".$wpdb->prefix."booking_order_history` WHERE booking_id = '".$booking_id."'";
								$wpdb->query( $sql_delete_query );
								
								$j++;
							}
							$e++;
						}
					}
					$i++;
					$array[]   =   $product_id;
				}
			}
			
			function bkap_new_function( $this_a ){
				
				echo $this_a;
			}
            
            /*****************************************************************************************************************
            * This function will add bookings for the product, when save order button is clicked on the admin edit order page.
            *****************************************************************************************************************/
			function bkap_update_order_details() {
				check_ajax_referer( 'order-item', 'security' );
				
				global $wpdb, $woocommerce;
				$order_query            =   "SELECT * FROM `".$wpdb->prefix."booking_order_history`
								            WHERE order_id = '".$_POST['order_id']."' ";
				
				$existing_order_result  =   $wpdb->get_results( $order_query );
				$new_order              =   false;
				$edit_order             =   true;
				
				if ( count( $existing_order_result ) == 0 ) { 
					$new_order     =   true;
					$edit_order    =   false; 
				}
				
				$order          =   new WC_Order( $_POST['order_id'] );	
				$item_values    =   $order->get_items();	
				$items          =   array();
				
				parse_str( $_POST['items'], $items );
			
				foreach ( $item_values as $cart_item_key => $values ) {	
					$order_item_id = array();
					if( isset( $items['order_item_id'] ) ) {
						$order_item_id = $items['order_item_id'];
					}
				
					foreach ( $order_item_id as $oid_key => $oid_value ) {
						
					    if ( $cart_item_key == $oid_value ) {
							$meta                =   $order->has_meta( $oid_value );
							$existing_quantity   =   woocommerce_get_order_item_meta( $oid_value, '_qty', true );
							$order_item_qty      =   $items['order_item_qty'];
							$quantity            =   $order_item_qty[ $oid_value ];
							$post_id             =   bkap_common::bkap_get_product_id( $values['product_id'] );
							$product             =   get_product($post_id);
							$product_type        =   $product->product_type;
							
							if( isset( $items['meta_key'] ) ) {
									$meta_keys = $items['meta_key'];
							}
							else {
								$meta_keys = array();
							}
							
							if( isset( $items['meta_value'] ) ) {
								$meta_values = $items['meta_value'];
							}
							else {
								$meta_values = array();
							}
						
							$item_key            =   $items['order_item_id'];
							$line_subtotal       =   $items['line_subtotal'];
							$booking             =   array();
							$variation_id        =   $values['variation_id'];
							$order_key_exists    =   '';
							
							if( isset( $items['meta_ids'.$oid_value] ) ) {
								$meta_ids = explode( ",", $items['meta_ids'.$oid_value] );
							}
							else {
								$id_query   =   "SELECT meta_id FROM `".$wpdb->prefix."woocommerce_order_itemmeta` WHERE order_item_id = '".$oid_value."'";
								$results    =   $wpdb->get_results ( $id_query );
								$i          =   0;
								$meta_ids   =   array();
								
								foreach( $results as $k => $v ) {
									$meta_ids[] = $v->meta_id;
									$i++;
								}
								
							}
								
							foreach ( $meta as $mk_key => $mk_value ) {
								if ( $mk_value['meta_key'] == get_option('book.item-meta-date') || $mk_value['meta_key'] == strip_tags( get_option("checkout.item-meta-date") ) && in_array( $mk_value['meta_id'], $meta_ids) ) {
									$key               =   $mk_value['meta_key'];
									$meta_id           =   $mk_value['meta_id'];
									
									$booking[$key]     =   $mk_value['meta_value'];
									$order_key_exists  =   'Y';
								}
							}
								
							if( count( $booking ) == 0 ) {
								foreach ( $meta_keys as $mk_key => $mk_value ) {
									foreach ( $meta_values as $mv_key => $mv_value ) {
										if ( $mk_key == $mv_key && in_array( $mk_key, $meta_ids ) ) {
											$booking[ $mk_value ]    =   $mv_value;
											$order_key_exists        =   'N';
										}
									}
								}
							}
							
							$booking_settings                =   get_post_meta( $post_id, 'woocommerce_booking_settings', true);
								
							$date_name                       =   get_option('book.item-meta-date');
				
							$number_of_fixed_price_blocks    =   bkap_block_booking::bkap_get_fixed_blocks_count( $post_id );
							
							$check_out_name                  =   strip_tags( get_option("checkout.item-meta-date") );
							
							if ( isset( $booking_settings['booking_fixed_block_enable'] ) && $booking_settings['booking_fixed_block_enable'] == 'yes' &&  ( isset( $number_of_fixed_price_blocks ) && $number_of_fixed_price_blocks > 0 ) && isset( $booking[$check_out_name] ) && $booking[ $check_out_name ] != "" ) {
								if ( isset( $booking[ $date_name ] ) && $booking[ $date_name ] != "" ) {
									$date_select = $booking[ $date_name ];
										woocommerce_update_order_item_meta( $oid_value, $date_name, sanitize_text_field( $date_select, true ) );
								}
							}
								
							$date_checkout_select    =   '';
							$details                 =   array();
							
							if ( isset( $booking[ $check_out_name ] ) && $booking[ $check_out_name ] != "" ) {
								$date_checkout_select = $booking[ $check_out_name ];
								woocommerce_update_order_item_meta( $oid_value, $check_out_name, sanitize_text_field( $date_checkout_select, true ) );
								
								if ( $order_key_exists == 'Y' ) {
									$total_price               =   $line_subtotal[ $oid_value ];
									$line_subtotal[ $oid_value ] =   $total_price;
								} 
								else  {
								    
									if ( isset( $items['wapbk_admin_hidden_date_checkout_'.$oid_value] ) ) {
										$checkout_date = $items['wapbk_admin_hidden_date_checkout_'.$oid_value];
									}
									
									if ( isset( $items['wapbk_admin_hidden_date_'.$oid_value] ) ) {
										$checkin_date = $items['wapbk_admin_hidden_date_'.$oid_value];
									}
									
									$days = ( strtotime( $checkout_date ) - strtotime( $checkin_date ) ) / (60*60*24);
									
									if ( isset( $items['wapbk_admin_same_day_'.$oid_value] ) && $items['wapbk_admin_same_day_'.$oid_value] == 'on' ) {
										if ( $days >= 0 ) {
											if( isset( $items['add_days_'.$oid_value] ) ) {
												$days   =   $days + $items['add_days_'.$oid_value];
											}
											$total_price = $days * $line_subtotal[ $oid_value ];
										}
									}
									 else {
										if ( $days > 0 ) {
											if( isset( $items['add_days_'.$oid_value] ) ) {
												$days = $days + $items['add_days_'.$oid_value];
											}
											$total_price = $days * $line_subtotal[ $oid_value ];
										}
									}
									
									if ( isset( $booking_settings['booking_fixed_block_enable'] ) && $booking_settings['booking_fixed_block_enable'] == 'yes'  &&( isset( $number_of_fixed_price_blocks ) && $number_of_fixed_price_blocks > 0 ) ) {
										if( isset( $items['admin_block_option_price_'.$oid_value] ) ) {
											$total_price = $items['admin_block_option_price_'.$oid_value] * $quantity;
										}
										else {
											$total_price = '';
										}
									}
									else if( isset( $booking_settings['booking_block_price_enable'] ) && $booking_settings['booking_block_price_enable'] == 'yes' ) {
										if ( $product_type == 'variable' ) {
											$_product            =   new WC_Product_Variation( $variation_id );
											$var_attributes      =   $_product->get_variation_attributes( );	
											$attribute_names     =   str_replace( "-", " ", $var_attributes );
										}
										else {
											$attribute_names     =   array();
										}
										$get_price        =   bkap_block_booking_price::price_range_calculate_price($post_id,$product_type,$values['variation_id'],$days,$attribute_names);
									
										$price_exploded   =   explode("-", $get_price);
										$total_price      =   '';
										$total_price      =   $price_exploded[0] * $quantity;
									}
								}
								
								// Round the price if rounding is enabled
								$global_settings = json_decode(get_option('woocommerce_booking_global_settings'));
								
								if ( isset( $global_settings->enable_rounding ) && $global_settings->enable_rounding == "on" ) {
									$round_price   =   round( $total_price );
									$total_price   =   $round_price;
								}
								
								$line_subtotal[ $oid_value ]    =   $total_price;
								
								$query_update_subtotal          =   "UPDATE `".$wpdb->prefix."woocommerce_order_itemmeta`
															        SET meta_value = '".woocommerce_clean( $total_price )."'
															        WHERE order_item_id = '".$oid_value."'
															        AND meta_key = '_line_subtotal'";
								$wpdb->query( $query_update_subtotal );
									
								$items['line_subtotal']         =   $line_subtotal;
						
								$query_update_total             =  "UPDATE `".$wpdb->prefix."woocommerce_order_itemmeta`
														           SET meta_value = '".woocommerce_clean( $total_price )."'
														           WHERE order_item_id = '".$oid_value."'
														           AND meta_key = '_line_total'";
								$wpdb->query( $query_update_total );
							
								$items['line_total']            =   $line_subtotal;
							}
							$time_name               =   get_option('book.item-meta-time');
							$items_string            =   http_build_query( $items );
					
							$query_update_quantity   =   "UPDATE `".$wpdb->prefix."woocommerce_order_itemmeta`
														  SET meta_value = '".woocommerce_clean( $quantity )."'
														  WHERE order_item_id = '".$oid_value."'
														  AND meta_key = '_qty'";
															
							$wpdb->query( $query_update_quantity );
											
							$_POST['items'] = $items_string;
							if ( isset( $booking[ $time_name ]) && $booking[ $time_name ] != "" ) {
								$time_select    =   $booking[ $time_name ];
								$time_exploded  =   explode( "-", $time_select );
								$saved_settings =   json_decode( get_option('woocommerce_booking_global_settings') );
								
								if ( isset( $saved_settings ) ) {
									$time_format = $saved_settings->booking_time_format;
								} 
								else {
									$time_format = "12";
								}
								
								$time_slot_to_display   =   '';
								$from_time              =   trim( $time_exploded[0] );
								
								if( isset( $time_exploded[1] ) ) {
									$to_time = trim( $time_exploded[1] );
								}
								else {
									$to_time = '';
								}
								
								if ( $time_format == '12' ) {
									$from_time = date( 'h:i A', strtotime( $time_exploded[0] ) );
									if( isset( $time_exploded[1] ) ) {
										$to_time = date( 'h:i A', strtotime( $time_exploded[1] ) );
									}
								}
								
								$query_from_time = date( 'G:i', strtotime( $time_exploded[0] ) );
								
								if( isset( $time_exploded[1] ) ) {
									$query_to_time = date( 'G:i', strtotime( $time_exploded[1] ) );
								}
								else {
									$query_to_time = '';
								}
								
								if( $to_time != '' ) {
									$time_slot_to_display = $from_time.' - '.$to_time;
								} 
								else {
									$time_slot_to_display = $from_time;
								}
								
								woocommerce_update_order_item_meta( $cart_item_key,  $time_name, $time_slot_to_display );
							}
						
							$order_item_qty[$cart_item_key]  =   $quantity;
							$_POST['order_item_qty']         =   $order_item_qty;
							$saved_settings                  =   json_decode(get_option('woocommerce_booking_global_settings'));
							$date_format                     =   $saved_settings->booking_date_format;
							
							if ( $new_order == false && $edit_order == true ) { 
								if ( isset( $booking_settings['booking_enable_multiple_day'] ) && $booking_settings['booking_enable_multiple_day'] == 'on' ) {
									$booking_ids = array();
									if( array_key_exists( $check_out_name, $booking ) ) {
										
									    if( $date_format == 'dd/mm/y' ) {
											$date_explode    =   explode( "/", $booking[ $date_name ] );
											$start_date      =   date( 'Y-m-d', mktime( 0, 0, 0, $date_explode[1], $date_explode[0], $date_explode[2] ) );
										} 
										else {
											$date_str    =   str_replace( ",", "", $booking[ $date_name ] );
											$start_date  =   date( 'Y-m-d', strtotime( $date_str ) );
										}
										
										if( $date_format == 'dd/mm/y' ) {
											$checkout_date_explode   =   explode( "/", $booking[ $check_out_name ] );
											$end_date                =   date( 'Y-m-d', mktime( 0, 0, 0, $checkout_date_explode[1], $checkout_date_explode[0], $checkout_date_explode[2] ) );
										}
										else {
											$checkout_date_str   =   str_replace( ",", "", $booking[ $check_out_name ] );
											$end_date            =   date( 'Y-m-d', strtotime( $checkout_date_str ) );
										}
										
										$query_result             =   "SELECT COUNT(*) as bookings_done FROM `".$wpdb->prefix."booking_history`
													                   WHERE
													                   start_date = '".$start_date."' 
													                   AND end_date = '".$end_date."' 
													                   AND post_id = ".$post_id;
								
										$item_results_lockout     =   $wpdb->get_results( $query_result );
										$lockout                  =   "";
										
										if ( isset( $booking_settings['booking_date_lockout'] ) ) {
											$lockout = $booking_settings['booking_date_lockout'];
										}
										
										if( count( $item_results_lockout ) > 0 ) {
											$booking_available = $lockout - $item_results_lockout[0]->bookings_done;
											
											foreach ( $existing_order_result as $ekey => $evalue ) {
												$booking_id         =   $evalue->booking_id;
												$query              =   "SELECT * FROM `".$wpdb->prefix."booking_history`
															            WHERE id = $booking_id ";
												$item_results       =   $wpdb->get_results( $query );
												if( count( $item_results ) > 0) {
													$booking_ids[] = $booking_id;
												}
											}
											
											if ( $order_key_exists == 'Y' ) {
												if ($existing_quantity < $quantity) {
													$quantity = $quantity - $existing_quantity;
													if( $quantity <= $booking_available ) {
														for ( $i = $existing_quantity; $i <= $quantity; $i++ ) {
															$query           = "INSERT INTO `".$wpdb->prefix."booking_history`
																                (post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
                																VALUES (
                																'".$post_id."',
                																'',
                																'".$start_date."',
                																'".$end_date."',
                																'',
                																'',
                																'0',
                																'0' )";
															$wpdb->query( $query );
															$new_booking_id  =   $wpdb->insert_id;
														}
														$order_query = "INSERT INTO `".$wpdb->prefix."booking_order_history`
																		(order_id,booking_id)
																		VALUES (
																		'".$_POST['order_id']."',
																		'".$new_booking_id."' )";
														$wpdb->query( $order_query );
													} 
													else if( $lockout != 0 ) {
														$query_date   =   "DELETE FROM `".$wpdb->prefix."woocommerce_order_itemmeta` 
																		  WHERE order_item_id = ".$oid_value." 
																		  AND meta_key = 'Name'";
														$wpdb->query( $query_date );
														
														$post         =   get_post( $post_id );
													    $title        =   $post->post_title;
														
														print('
															  <script type="text/javascript">
															     alert("The item you changed the quantity for '.$title.', exceeds the quantity available for your selected dates. Please reduce the quantity or remove it from your order."); 
															     //window.history.back();
															     //return;
														      </script>
														    ');
													}
												} 
												elseif ( $existing_quantity > $quantity ) {
													for ( $i = $quantity; $i < $existing_quantity; $i++ ) {
														$query    =   "DELETE FROM `".$wpdb->prefix."booking_history` 
																	   WHERE id = ".$booking_ids[$i];
														$wpdb->query( $query );
													}
												}	
											} 
											else if ( $order_key_exists == 'N' ) {
												if( $quantity <= $booking_available ) {
													
												    for ( $i = $existing_quantity; $i <= $quantity; $i++ ) {
    													$query         =    "INSERT INTO `".$wpdb->prefix."booking_history`
    														                (post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
    														                VALUES (
                															'".$post_id."',
                															'',
                															'".$start_date."',
                															'".$end_date."',
                															'',
                															'',
                															'0',
                															'0' )";
														$wpdb->query( $query );
														$new_booking_id =   $wpdb->insert_id;
													}
													
													$order_query   =   "INSERT INTO `".$wpdb->prefix."booking_order_history`
																	   (order_id,booking_id)
																	   VALUES (
																	   '".$_POST['order_id']."',
																	   '".$new_booking_id."' )";
													$wpdb->query( $order_query );
												} 
												else if( $lockout != 0 ) {
													$query_date    =   "DELETE FROM `".$wpdb->prefix."woocommerce_order_itemmeta`
																	   WHERE order_item_id = ".$oid_value." 
																	   AND meta_key = 'Name'";
													$wpdb->query( $query_date );
															
													$title         =   $post->post_title;
													print('
															<script type="text/javascript">
        														alert("The item you changed the quantity for '.$title.', exceeds the quantity available for your selected dates. Please reduce the quantity or remove it from your order.");
        														//window.history.back();
        														//return;
														    </script>
													     ');
													}
												}
											}
										}
									} 
									else if ( isset( $booking_settings['booking_enable_time'] ) && $booking_settings['booking_enable_time'] == 'on' ) {	
										if( array_key_exists( $date_name, $booking ) ) {
										    
											if( $date_format == 'dd/mm/y' ) {
												$date_explode   =   explode( "/", $booking[ $date_name ] );
												$start_date     =   date( 'Y-m-d', mktime( 0, 0, 0, $date_explode[1], $date_explode[0], $date_explode[2] ) );
											}
											else {
												$date_str       = str_replace( ",", "", $booking[ $date_name ] );
												$start_date     = date( 'Y-m-d', strtotime( $date_str ) );
											}
											
										}
										
										if( array_key_exists( $time_name, $booking ) ) {
											$time_slot               =   explode( "-", $booking[$time_name] );
											$from_time               =   date("G:i",strtotime($time_slot[0]));
											$to_time                 =   date("G:i",strtotime($time_slot[1]));
											$query_result            =   "SELECT available_booking,total_booking FROM `".$wpdb->prefix."booking_history`
																            WHERE start_date = '".$start_date."' 
																            AND from_time = '".$from_time."' 
        																    AND to_time = '".$to_time."' 
        																    AND post_id = ".$post_id." 
        																    AND total_booking > 0";
											
											$item_results_lockout    =   $wpdb->get_results( $query_result );
											
											if( count( $item_results_lockout ) > 0 ) {
												$booking_available  =   $item_results_lockout[0]->available_booking;
												if ( $order_key_exists == 'Y' ) {
													if ( $existing_quantity < $quantity ) {
														if( $quantity <= $booking_available ) {
															$qty = $quantity - $existing_quantity;
															if( $to_time != "" ) {
																$query          =   "UPDATE `".$wpdb->prefix."booking_history`
        																			SET available_booking = available_booking - ".$qty."
        																			WHERE post_id = '".$post_id."' AND
        																			start_date = '".$start_date."' AND
        																			from_time = '".$from_time."' AND
        																			to_time = '".$to_time."' AND
        																			total_booking > 0";
																$wpdb->query( $query );
											
															    $select          =   "SELECT * FROM `".$wpdb->prefix."booking_history`
        																		     WHERE post_id = '".$post_id."' AND
        																		     start_date = '".$start_date."' AND
        																		     from_time = '".$from_time."' AND
        																		     to_time = '".$to_time."' ";
																$select_results  =   $wpdb->get_results( $select );
																
																foreach( $select_results as $k => $v ) {
																	$details[ $post_id ] = $v;
																}
																
															} else {
																$query  =   "UPDATE `".$wpdb->prefix."booking_history`
																			SET available_booking = available_booking - ".$qty."
																			WHERE post_id = '".$post_id."' AND
																			start_date = '".$start_date."' AND
																			from_time = '".$from_time."' AND
																			total_booking > 0";
																$wpdb->query( $query );
												
																$select =   "SELECT * FROM `".$wpdb->prefix."booking_history`
																			WHERE post_id = '".$post_id."' AND
																			start_date = '".$start_date."' AND
																			from_time = '".$from_time."'";
																$select_results = $wpdb->get_results( $select );
																
																foreach( $select_results as $k => $v ) {
																	$details[ $post_id ] = $v;
																}
															}
														} 
														else {
															$query_date  =   "DELETE FROM `".$wpdb->prefix."woocommerce_order_itemmeta` 
																			 WHERE order_item_id = ".$oid_value." AND meta_key = 'Name'";
															$wpdb->query( $query_date );
														
															$post        =   get_post( $post_id );
														
															$title       =   $post->post_title;
															print('
    																<script type="text/javascript">
        																alert("The item you changed the quantity for '.$title.', exceeds the quantity available for your selected date and time slot. Please reduce the quantity or remove it from your order."); 
        																//window.history.back();
        																//return;
    																</script>
															    ');
														}
													} elseif ( $existing_quantity > $quantity ) {
														$qty = $existing_quantity - $quantity;
														if( $to_time != "" ) {
															$query           =   "UPDATE `".$wpdb->prefix."booking_history`
            																		SET available_booking = available_booking + ".$qty."
            																		WHERE post_id = '".$post_id."' AND
            																		start_date = '".$start_date."' AND
            																		from_time = '".$from_time."' AND
            																		to_time = '".$to_time."' AND
            																		total_booking > 0";
															$wpdb->query( $query );
											
															$select          =   "SELECT * FROM `".$wpdb->prefix."booking_history`
            																		WHERE post_id = '".$post_id."' AND
            																		start_date = '".$start_date."' AND
            																		from_time = '".$from_time."' AND
            																		to_time = '".$to_time."' ";
															$select_results  =   $wpdb->get_results( $select );
															
															foreach( $select_results as $k => $v ) {
																$details[ $post_id ] = $v;
															}
															
														}else {
															$query           =   "UPDATE `".$wpdb->prefix."booking_history`
            																		SET available_booking = available_booking + ".$qty."
            																		WHERE post_id = '".$post_id."' AND
            																		start_date = '".$start_date."' AND
            																		from_time = '".$from_time."' AND
            																		total_booking > 0";
															$wpdb->query( $query );
												
															$select          =   "SELECT * FROM `".$wpdb->prefix."booking_history`
            																		WHERE post_id = '".$post_id."' AND
            																		start_date = '".$start_date."' AND
            																		from_time = '".$from_time."'";
															$select_results  =   $wpdb->get_results( $select );
															
															foreach( $select_results as $k => $v ) {
																$details[ $post_id ] = $v;
															}
															
														}
													}
												} else if ( $order_key_exists == 'N' ) {
													if( $quantity <= $booking_available ) {
														if( $to_time != "" ) {
															$query           =   "UPDATE `".$wpdb->prefix."booking_history`
            																		SET available_booking = available_booking - ".$quantity."
            																		WHERE post_id = '".$post_id."' AND
            																		start_date = '".$start_date."' AND
            																		from_time = '".$from_time."' AND
            																		to_time = '".$to_time."' AND
            																		total_booking > 0";
															$wpdb->query( $query );
													
															$select          =   "SELECT * FROM `".$wpdb->prefix."booking_history`
            																		WHERE post_id = '".$post_id."' AND
            																		start_date = '".$start_date."' AND
            																		from_time = '".$from_time."' AND
            																		to_time = '".$to_time."' ";
															$select_results  =   $wpdb->get_results( $select );
															
															foreach( $select_results as $k => $v ) {
																$details[ $post_id ] = $v;
															}
															
														} else {
															$query           =   "UPDATE `".$wpdb->prefix."booking_history`
            																		SET available_booking = available_booking - ".$quantity."
            																		WHERE post_id = '".$post_id."' AND
            																		start_date = '".$start_date."' AND
            																		from_time = '".$from_time."' AND
            																		total_booking > 0";
															$wpdb->query( $query );
										
															$select          =   "SELECT * FROM `".$wpdb->prefix."booking_history`
            																		WHERE post_id = '".$post_id."' AND
            																		start_date = '".$start_date."' AND
            																		from_time = '".$from_time."'";
															$select_results  =   $wpdb->get_results( $select );
															
															foreach( $select_results as $k => $v ) {
																$details[ $post_id ] = $v;
															}
														}
													} else {
														$query_date   =   "DELETE FROM `".$wpdb->prefix."woocommerce_order_itemmeta` 
															WHERE order_item_id = ".$oid_value." AND meta_key = 'Name'";
														$wpdb->query( $query_date );
												
														$post         =   get_post( $post_id );
												
														$title        =   $post->post_title;
														print('
    														  <script type="text/javascript">
    															alert("The item you changed the quantity for '.$title.', exceeds the quantity available for your selected date and time slot. Please reduce the quantity or remove it from your order."); 
    															//window.history.back();
    															//return;
    														  </script>
														     ');
													}
												}
											}
										}
									} else {
										
									    if( $date_format == 'dd/mm/y' ) {
											$date_explode    =   explode( "/", $booking[$date_name] );
											$start_date      =   date( 'Y-m-d', mktime( 0, 0, 0, $date_explode[1], $date_explode[0], $date_explode[2] ) );
										}
										else {
											$date_str        =   str_replace( ",", "", $booking[ $date_name ] );
											$start_date      =   date( 'Y-m-d', strtotime( $date_str ) );
										}
										
										$query_result         =    "SELECT available_booking,total_booking FROM `".$wpdb->prefix."booking_history`
												                   WHERE start_date = '".$start_date."' AND post_id = ".$post_id." AND total_booking > 0";
										$item_results_lockout =    $wpdb->get_results( $query_result );
										
										if( count( $item_results_lockout ) > 0 ) {
											$booking_available = $item_results_lockout[0]->available_booking;
											if ( $order_key_exists == 'Y' ) {
												if ( $existing_quantity < $quantity ) {
													if( $quantity <= $booking_available ) {
														$qty      =   $quantity - $existing_quantity;
														$query    =   "UPDATE `".$wpdb->prefix."booking_history`
																	   SET available_booking = available_booking - ".$qty."
																	   WHERE post_id = '".$post_id."' AND
																	   start_date = '".$start_date."' AND
																	   total_booking > 0";
														$wpdb->query( $query );
													} else {
														$query_date   =   "DELETE FROM `".$wpdb->prefix."woocommerce_order_itemmeta` 
															              WHERE order_item_id = ".$oid_value." AND meta_key = 'Name'";
														$wpdb->query( $query_date );
														$post         =   get_post( $post_id );
													
														$title        =   $post->post_title;
														print('
    															<script type="text/javascript">
        															alert("The item you changed the quantity for '.$title.', exceeds the quantity available for your selected date. Please reduce the quantity or remove it from your order."); 
        															//window.history.back();
        															//return;
    															</script>
														    ');
													}
												} else if ( $existing_quantity > $quantity ) {
													$qty   =   $existing_quantity - $quantity;
													$query =   "UPDATE `".$wpdb->prefix."booking_history`
																SET available_booking = available_booking + ".$qty."
																WHERE post_id = '".$post_id."' AND
																start_date = '".$start_date."' AND
																total_booking > 0";
													$wpdb->query( $query );
												}
											} else if ( $order_key_exists == 'N' ) {
												if( $quantity <= $booking_available ) {
													$query     =   "UPDATE `".$wpdb->prefix."booking_history`
																    SET available_booking = available_booking - ".$quantity."
																    WHERE post_id = '".$post_id."' AND
																    start_date = '".$start_date."' AND
																    total_booking > 0";
													$wpdb->query( $query );
												} else {
													$query_date    =   "DELETE FROM `".$wpdb->prefix."woocommerce_order_itemmeta` 
															            WHERE 
															            order_item_id = ".$oid_value." AND meta_key = 'Name'";
													$wpdb->query( $query_date );
													$post = get_post( $post_id );
														
													$title = $post->post_title;
													print('
															<script type="text/javascript">
    															alert("The item you changed the quantity for '.$title.', exceeds the quantity available for your selected date. Please reduce the quantity or remove it from your order."); 
    															//window.history.back();
    															//return;
														    </script>
													    ');
												}
											}
										}
									}
							} 
							else {
								if ( isset( $items['wapbk_admin_hidden_date_'.$oid_value] ) ) :
									$hidden_date = $items['wapbk_admin_hidden_date_'.$oid_value];
									
									if( $date_format == 'dd/mm/y' ) {
										$date_explode =   explode( "-", $hidden_date );
										$date_query   =   date( 'Y-m-d', mktime( 0, 0, 0, $date_explode[1], $date_explode[0], $date_explode[2] ) );
									}
									else {
										$date_str     =   str_replace( ",", "", $hidden_date );
										$date_query   =   date( 'Y-m-d', strtotime( $date_str ) );
									}
									
									if( isset( $items['wapbk_admin_hidden_date_checkout_'.$oid_value] ) && $items['wapbk_admin_hidden_date_checkout_'.$oid_value] != "" ) {
										$date_checkout = $items['wapbk_admin_hidden_date_checkout_'.$oid_value];
										
										if( $date_format == 'dd/mm/y' ) {
											$checkout_date_explode   =   explode( "-", $date_checkout );
											$date_checkout_query     =   date( 'Y-m-d', mktime( 0, 0, 0, $checkout_date_explode[1], $checkout_date_explode[0], $checkout_date_explode[2] ) );
										} 
										else {
											$checkout_date_str = str_replace( ",", "", $date_checkout );
											$date_checkout_query = date( 'Y-m-d', strtotime( $checkout_date_str ) );
										}
										
									}
									
									if ( isset( $booking_settings['booking_enable_multiple_day'] )&& $booking_settings['booking_enable_multiple_day'] == 'on' ) {
										$query_result         =   "SELECT COUNT(*) as bookings_done FROM `".$wpdb->prefix."booking_history`
												                  WHERE
												                  start_date = '".$date_query."' AND end_date = '".$date_checkout_query."' AND post_id = ".$post_id;
										
										$item_results_lockout =   $wpdb->get_results( $query_result );
										$lockout              =   "";
										
										if ( isset( $booking_settings['booking_date_lockout'] ) ) {
											$lockout = $booking_settings['booking_date_lockout'];
										}
										
										if( count( $item_results_lockout ) > 0 ) {
											$booking_available = $lockout - $item_results_lockout[0]->bookings_done;
											
											if( $quantity <= $booking_available ) {
												for ( $i = $existing_quantity; $i <= $quantity; $i++ ) {
													$query             =   "INSERT INTO `".$wpdb->prefix."booking_history`
                        													(post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
                        													VALUES (
                        													'".$post_id."',
                        													'',
                        													'".$date_query."',
                        													'".$date_checkout_query."',
                        													'',
                        													'',
                        													'0',
                        													'0' )";
													$wpdb->query( $query );
													
													$new_booking_id    =   $wpdb->insert_id;
												}
												$order_query    =   "INSERT INTO `".$wpdb->prefix."booking_order_history`
                        										    (order_id,booking_id)
                        											VALUES (
                        											'".$_POST['order_id']."',
                        											'".$new_booking_id."' )";
												$wpdb->query( $order_query );
											} 
											else if( $lockout != 0 ) {
												$query_date =   "DELETE FROM `".$wpdb->prefix."woocommerce_order_itemmeta`
																WHERE
																order_item_id = ".$oid_value." AND meta_key = 'Name'";
												$wpdb->query( $query_date );
												
												$post       =   get_post( $post_id );
												$title      =   $post->post_title;
												print('
    													<script type="text/javascript">
        													alert("The item you changed the quantity for '.$title.', exceeds the quantity available for your selected dates. Please reduce the quantity or remove it from your order.");
        													//window.history.back();
        													//return;
    													</script>
												    ');
										
											}
										}
									} 
									else {
										if( isset( $booking[ $time_name ] ) && $booking[ $time_name ] != "" ) {
											$time_slot = explode( "-", $booking[ $time_name ] );
											$from_time = date( "G:i", strtotime( $time_slot[0] ) );
											
											if( isset( $time_slot[1] ) ){
												$to_time = date( "G:i", strtotime( $time_slot[1] ) );
											} else {
												$to_time = '';
											}
											
											$query_result            =   "SELECT available_booking,total_booking FROM `".$wpdb->prefix."booking_history`
																            WHERE
																            start_date = '".$date_query."' AND from_time = '".$from_time."' AND to_time = '".$to_time."' AND post_id = ".$post_id." AND total_booking > 0";
											$item_results_lockout    =   $wpdb->get_results( $query_result );
											
											if( count( $item_results_lockout ) > 0 ) {
												$booking_available = $item_results_lockout[0]->available_booking;
												if( $quantity <= $booking_available ) {
													if( $to_time != "" ) {
														$query            =   "UPDATE `".$wpdb->prefix."booking_history`
                    															SET available_booking = available_booking - ".$quantity."
                    															WHERE post_id = '".$post_id."' AND
                    															start_date = '".$date_query."' AND
                    															from_time = '".$from_time."' AND
                    															to_time = '".$to_time."' AND
                    															total_booking > 0";
														$wpdb->query( $query );
													
														$select           =   "SELECT * FROM `".$wpdb->prefix."booking_history`
                    															WHERE post_id = '".$post_id."' AND
                    															start_date = '".$date_query."' AND
                    															from_time = '".$from_time."' AND
                    															to_time = '".$to_time."' ";
														$select_results   =   $wpdb->get_results( $select );
														
														foreach( $select_results as $k => $v ) {
															$details[ $post_id ] = $v;
														}
														
													} 
													else {
														$query            =   "UPDATE `".$wpdb->prefix."booking_history`
                    															SET available_booking = available_booking - ".$quantity."
                    															WHERE post_id = '".$post_id."' AND
                    															start_date = '".$date_query."' AND
                    															from_time = '".$from_time."' AND
                    															total_booking > 0";
														$wpdb->query( $query );
										
														$select           =   "SELECT * FROM `".$wpdb->prefix."booking_history`
                        														WHERE post_id = '".$post_id."' AND
                        														start_date = '".$date_query."' AND
                        														from_time = '".$from_time."'";
														$select_results   =   $wpdb->get_results( $select );
														
														foreach( $select_results as $k => $v ) {	
															$details[ $post_id ] = $v;
														}
													}
												} else {
													$query_date    =   "DELETE FROM `".$wpdb->prefix."woocommerce_order_itemmeta` 
															             WHERE 
															             order_item_id = ".$oid_value." AND meta_key = 'Name'";
													$wpdb->query( $query_date );
													$post          =   get_post( $post_id );
										
													$title         =   $post->post_title;
													print('
															<script type="text/javascript">
    															alert("The item you changed the quantity for '.$title.', exceeds the quantity available for your selected date and time slot. Please reduce the quantity or remove it from your order."); 
    															//window.history.back();
    															//return;
														    </script>
													      ');
										
												}
											}
											if( $query_to_time != '' ) {
												$order_select_query =   "SELECT id FROM `".$wpdb->prefix."booking_history`
																		WHERE post_id = '".$post_id."' AND
																		start_date = '".$date_query."' AND
																		from_time = '".$query_from_time."' AND
																		to_time = '".$query_to_time."' ";
												$order_results      =   $wpdb->get_results( $order_select_query );
											} else {
												$order_select_query =   "SELECT id FROM `".$wpdb->prefix."booking_history`
																		WHERE post_id = '".$post_id."' AND
																		start_date = '".$date_query."' AND
																		from_time = '".$query_from_time."'";
												$order_results      =   $wpdb->get_results( $order_select_query );
											}
										} 
										else {
											$query_result            =   "SELECT available_booking,total_booking FROM `".$wpdb->prefix."booking_history`
															             WHERE
															             start_date = '".$date_query."' AND post_id = ".$post_id." AND total_booking > 0";
											$item_results_lockout    =   $wpdb->get_results( $query_result );
											$booking_available       =   $item_results_lockout[0]->available_booking;
											
											if( $quantity <= $booking_available ) {
												$query      =   "UPDATE `".$wpdb->prefix."booking_history`
    															SET available_booking = available_booking - ".$quantity."
    															WHERE post_id = '".$post_id."' AND
    															start_date = '".$date_query."' AND
    															total_booking > 0";
												$wpdb->query( $query );
											} 
											else  {
												$query_date =   "DELETE FROM `".$wpdb->prefix."woocommerce_order_itemmeta` 
        														WHERE 
        														order_item_id = ".$oid_value." AND meta_key = 'Name'";
												$wpdb->query( $query_date );
												$post       =   get_post( $post_id );
										
												$title      =   $post->post_title;
												print('
													   <script type="text/javascript">
    														alert("The item you changed the quantity for '.$title.', exceeds the quantity available for your selected date. Please reduce the quantity or remove it from your order."); 
    														//window.history.back();
    														//return;
													   </script>
												     ');
										
											}
											$order_select_query = "SELECT id FROM `".$wpdb->prefix."booking_history`
																	WHERE post_id = '".$post_id."' AND
																	start_date = '".$date_query."'";
											$order_results = $wpdb->get_results( $order_select_query );
										}
										$j = 0;
										foreach( $order_results as $k => $v ) {
											$booking_id  =  $order_results[ $j ]->id;
											$order_query =  "INSERT INTO `".$wpdb->prefix."booking_order_history`
                											(order_id,booking_id)
                											VALUES (
                											'".$_POST['order_id']."',
                											'".$booking_id."' )";
											$wpdb->query( $order_query );
											$j++;
										}
									}	
								endif;
							}
							
							if ( $new_order == false && $edit_order == true ) {
							     if( array_key_exists( $date_name, $booking ) ) {
							         
							         if( $date_format == 'dd/mm/y' ) {
							             $date_explode   =   explode( "/", $booking[ $date_name ] );
							             $hidden_date    =   date( 'Y-m-d', mktime( 0, 0, 0, $date_explode[1], $date_explode[0], $date_explode[2] ) );
							         }    
							         else {
							             $date_str       =   str_replace( ",", "", $booking[ $date_name ] );
							             $hidden_date    =   date( 'Y-m-d', strtotime( $date_str ) );
							         }
							         
							     }
							     
							     if( array_key_exists( $time_name, $booking ) ) {
							         $time_slot          =   explode( "-", $booking[ $time_name ] );
							         $query_from_time    =   date( "G:i", strtotime( $time_slot[0] ) );
							         $query_to_time      =   date( "G:i", strtotime( $time_slot[1] ) );
							     }
							}
							else {
							    if ( isset( $items['wapbk_admin_hidden_date_'.$oid_value] ) ) {
							         $hidden_date = $items['wapbk_admin_hidden_date_'.$oid_value];
							    }
							    
							    if ( isset( $hidden_date ) && $hidden_date != '' ) {
    							    if( $date_format == 'dd/mm/y' ) {
    							        $date_explode    =   explode( "-", $hidden_date );
    							        $hidden_date     =   date( 'Y-m-d', mktime( 0, 0, 0, $date_explode[1], $date_explode[0], $date_explode[2] ) );
    							    }
    							    else {
    							        $date_str    =   str_replace(",", "", $hidden_date );
    							        $hidden_date =   date( 'Y-m-d', strtotime( $date_str ) );
    							    }
    							    
    							    if( isset( $booking[ $time_name ] ) && $booking[ $time_name ] != "" ) {
    							        $time_slot       =   explode( "-", $booking[ $time_name ] );
    							        $query_from_time =   date( "G:i", strtotime( $time_slot[0] ) );
    
    							        if( isset( $time_slot[1] ) ) {
    							            $query_to_time   =   date( "G:i", strtotime( $time_slot[1] ) );
    							        } else {
    							            $query_to_time   =   '';
    							        }
    							        
    							    }
							    }
							}
							$book_global_settings    =   json_decode( get_option('woocommerce_booking_global_settings') );
							$booking_settings        =   get_post_meta( $post_id, 'woocommerce_booking_settings' , true );
							if( isset( $booking_settings['booking_time_settings'] ) ){
							    
							    if ( isset( $booking_settings['booking_time_settings'][ $hidden_date ] ) ) $lockout_settings = $booking_settings['booking_time_settings'][ $hidden_date ];
							    else $lockout_settings = array();
							    
							    if( count( $lockout_settings ) == 0 ){
							        $week_day    =   date( 'l', strtotime( $hidden_date ) );
							        $weekdays    =   bkap_get_book_arrays('weekdays');
							        $weekday     =   array_search($week_day,$weekdays);
							        
							        if ( isset( $booking_settings['booking_time_settings'][ $weekday ] ) ) $lockout_settings = $booking_settings['booking_time_settings'][ $weekday ];
							        else $lockout_settings = array();
							    }
							    
							    if( isset( $query_from_time ) && $query_from_time != '' ) {
							         $from_lockout_time  =   explode( ":", $query_from_time );
							         $from_hours         =   $from_lockout_time[0];
							         $from_minute        =   $from_lockout_time[1];
							    }
							    else {
							        $from_hours  =   '';
							        $from_minute =   '';
							    }
							    
							    if( isset( $query_to_time ) && $query_to_time != '' ) {
							        $to_lockout_time =   explode( ":", $query_to_time );
							        $to_hours        =   $to_lockout_time[0];
							        $to_minute       =   $to_lockout_time[1];
							    }else {
							        $to_hours    =   '';
							        $to_minute   =   '';
							    }
							    
							    foreach( $lockout_settings as $l_key => $l_value ) {
							        if( $l_value['from_slot_hrs'] == $from_hours && $l_value['from_slot_min'] == $from_minute && $l_value['to_slot_hrs'] == $to_hours && $l_value['to_slot_min'] == $to_minute ) {
							            
							            if ( isset( $l_value['global_time_check'] ) ){
							                $global_timeslot_lockout = $l_value['global_time_check'];
							            } else{
							                $global_timeslot_lockout = '';
							            }
							            
							        }
							    }
							}
							if( isset( $book_global_settings->booking_global_timeslot ) && $book_global_settings->booking_global_timeslot == 'on' || isset( $global_timeslot_lockout ) && $global_timeslot_lockout == 'on' ) {
							    $args    =   array( 'post_type' => 'product', 'posts_per_page' => -1 );
							    $product =   query_posts( $args );
							    
							    foreach( $product as $k => $v ){
							        $product_ids[]   =   $v->ID;
							    }
							    
							    foreach( $product_ids as $k => $v ){
							        $duplicate_of        =   bkap_common::bkap_get_product_id( $v );
							        	
							        $booking_settings    =   get_post_meta( $v, 'woocommerce_booking_settings' , true );
							        if( isset( $booking_settings['booking_enable_time'] ) && $booking_settings['booking_enable_time'] == 'on' ) {
							            if( !array_key_exists( $duplicate_of, $details ) ) {
							                    foreach( $details as $key => $val ){   
							                    $booking_settings    =   get_post_meta( $duplicate_of, 'woocommerce_booking_settings', true );
							
							                    $start_date          =   $val->start_date;
							                    $from_time           =   $val->from_time;
							                    $to_time             =   $val->to_time;
							                    
							                    if( $to_time != "" ){
							                        $query   =   "UPDATE `".$wpdb->prefix."booking_history`
            									                   SET available_booking = available_booking - ".$quantity."
            									                   WHERE post_id = '".$duplicate_of."' AND
            									                   start_date = '".$hidden_date."' AND
            									                   from_time = '".$from_time."' AND
            									                   to_time = '".$to_time."' ";
							                        $updated = $wpdb->query( $query );
							                        if( $updated == 0 ) {
							                            if( $val->weekday == '' ) {
							                                $week_day    =   date( 'l', strtotime( $hidden_date ) );
							                                $weekdays    =   bkap_get_book_arrays('weekdays');
							                                $weekday     =   array_search( $week_day, $weekdays );
							                                //echo $weekday;exit;
							                            } else {
							                                $weekday = $val->weekday;
							                            }
							                            $results =   array();
							                            $query   =   "SELECT * FROM `".$wpdb->prefix."booking_history`
                    												 WHERE post_id = %s
                    												 AND weekday = %s
                    												 AND start_date = '0000-00-00'";
							
							                            $results =   $wpdb->get_results( $wpdb->prepare( $query, $duplicate_of, $weekday ) );
							                            
							                            if ( !$results ) break;
							                            else {
							                                foreach( $results as $r_key => $r_val ) {
							                                    if( $from_time == $r_val->from_time && $to_time == $r_val->to_time ) {
							                                        $available_booking   =   $r_val->available_booking - $quantity;
							                                        $query_insert        =   "INSERT INTO `".$wpdb->prefix."booking_history`
                                        													(post_id,weekday,start_date,from_time,to_time,total_booking,available_booking)
                                        													VALUES (
                                        													'".$duplicate_of."',
                                        													'".$weekday."',
                                        													'".$start_date."',
                                        													'".$r_val->from_time."',
                                        													'".$r_val->to_time."',
                                        													'".$r_val->total_booking."',
                                        													'".$available_booking."' )";
							
							                                        $wpdb->query( $query_insert );
							
							                                    }else {
							                                        $from_lockout_time   =   explode( ":", $r_val->from_time );
							                                        $from_hours          =   $from_lockout_time[0];
							                                        $from_minute         =   $from_lockout_time[1];
							                                        
							                                        if( isset( $query_to_time ) && $query_to_time != '' ) {
							                                            $to_lockout_time =   explode(":",$r_val->to_time);
							                                            $to_hours        =   $to_lockout_time[0];
							                                            $to_minute       =   '';
							                                            if ( isset($to_lockout_time[1] ) ) {
							                                            	$to_minute   =   $to_lockout_time[1];
							                                            }
							                                        }
							                                        
							                                        foreach( $lockout_settings as $l_key => $l_value ) {
							                                            if( $l_value['from_slot_hrs'] == $from_hours && $l_value['from_slot_min'] == $from_minute && $l_value['to_slot_hrs'] == $to_hours && $l_value['to_slot_min'] == $to_minute ) {
							                                                $query_insert    =   "INSERT INTO `".$wpdb->prefix."booking_history`
                                        															(post_id,weekday,start_date,from_time,to_time,total_booking,available_booking)
                                        															VALUES (
                                        															'".$duplicate_of."',
                                        															'".$weekday."',
                                        															'".$start_date."',
                                        															'".$r_val->from_time."',
                                        															'".$r_val->to_time."',
                                        															'".$r_val->total_booking."',
                                        															'".$r_val->available_booking."' )";
							                                                $wpdb->query( $query_insert );
							                                            }
							                                        }
							                                    }
							                                }
							                            }
							                        }
							                    }else {
							                        $query   =   "UPDATE `".$wpdb->prefix."booking_history`
                    												SET available_booking = available_booking - ".$quantity."
                    												WHERE post_id = '".$duplicate_of."' AND
                    												start_date = '".$hidden_date."' AND
                    												from_time = '".$from_time."'
                    												AND to_time = ''";
							                        	
							                        $updated =   $wpdb->query( $query );
							                        if( $updated == 0 ) {
							                            
							                            if( $val->weekday == '' ) {
							                                $week_day    =   date( 'l', strtotime( $hidden_date ) );
							                                $weekdays    =   bkap_get_book_arrays('weekdays');
							                                $weekday     =   array_search( $week_day, $weekdays );
							
							                            } else {
							                                $weekday     =   $val->weekday;
							                            }
							                            
							                            $results      =   array();
							                            $query       =   "SELECT * FROM `".$wpdb->prefix."booking_history`
                                										  WHERE post_id = %d
                                										  AND weekday = %s
                                										  AND start_date = '0000-00-00'
                                										  AND to_time = '' ";
							                            $results     =   $wpdb->get_results( $wpdb->prepare( $query, $duplicate_of, $weekday ) );

							                            if ( !$results ) break;
							                            else {
							                                foreach( $results as $r_key => $r_val ) {
							                                    if( $from_time == $r_val->from_time ) {
							                                        $available_booking   =   $r_val->available_booking - $quantity;
							                                        $query_insert        =   "INSERT INTO `".$wpdb->prefix."booking_history`
                                            													(post_id,weekday,start_date,from_time,total_booking,available_booking)
                                            													VALUES (
                                            													'".$duplicate_of."',
                                            													'".$weekday."',
                                            													'".$start_date."',
                                            													'".$r_val->from_time."',
                                            													'".$r_val->total_booking."',
                                            													'".$available_booking."' )";
							                                        $wpdb->query( $query_insert );
							                                    }else {
							                                        $from_lockout_time   =   explode( ":", $r_val->from_time );
							                                        $from_hours          =   $from_lockout_time[0];
							                                        $from_minute         =   $from_lockout_time[1];
							                                        foreach( $lockout_settings as $l_key => $l_value ) {
							                                            if( $l_value['from_slot_hrs'] == $from_hours && $l_value['from_slot_min'] == $from_minute ) {
							                                                $query_insert    =   "INSERT INTO `".$wpdb->prefix."booking_history`
                                    															(post_id,weekday,start_date,from_time,total_booking,available_booking)
                                    															VALUES (
                                    															'".$duplicate_of."',
                                    															'".$weekday."',
                                    															'".$start_date."',
                                    															'".$r_val->from_time."',
                                    															'".$r_val->available_booking."',
                                    															'".$r_val->available_booking."' )";
							                                                $wpdb->query( $query_insert );
							                                            }
							                                        }
							                                    }
							                                }
							                            }
							                        }
							                    }
							                }
							            }
							        }
							    }
							}
						}
					}
				}
			}
			
			/*********************************************************
            * This function include css files required for admin side.
            *********************************************************/
			function bkap_admin_booking_enqueue_scripts_css(){	
			    	
				if( get_post_type() == 'shop_order' ) {
					$calendar_theme        =   json_decode( get_option( 'woocommerce_booking_global_settings' ) );
					$calendar_theme_sel    =   "";
					if ( isset( $calendar_theme ) ) {
						$calendar_theme_sel = $calendar_theme->booking_themes;
					}
					if ( $calendar_theme_sel == "" ) {
						$calendar_theme_sel = 'smoothness';
					}
					wp_enqueue_style( 'jquery-ui', plugins_url( '/css/themes/'.$calendar_theme_sel.'/jquery-ui.css', __FILE__ ) , '', '', false );
					
					wp_enqueue_style( 'jquery.ui.theme', plugins_url( '/css/themes/'.$calendar_theme_sel.'/jquery.ui.theme.css', __FILE__ ) , '', '', false );
				}
			}
			/********************************************************
            * This function include JS files required for admin side.
            ********************************************************/
			function bkap_admin_booking_enqueue_scripts_js() {
				if( get_post_type() == 'shop_order' ) {
			
					wp_register_script( 'datepick', plugins_url().'/woocommerce-booking/js/jquery.datepick.js' );
					wp_enqueue_script( 'datepick' );
					
					wp_enqueue_script(
							'initialize-datepicker-admin.js',
							plugins_url('/js/initialize-datepicker-admin.js', __FILE__),
							'',
							'',
							false
					);
					
					if( isset( $_GET['lang'] ) && $_GET['lang'] != '' && $_GET['lang'] != null ) {
						$curr_lang = $_GET['lang'];
					} 
					else {
						$current_language = json_decode( get_option('woocommerce_booking_global_settings') );
						if ( isset( $current_language ) ) {
							$curr_lang = $current_language->booking_language;
						} else {
							$curr_lang = "en-GB";
						}
					}
					wp_enqueue_script( "$curr_lang", plugins_url( "/js/i18n/jquery.ui.datepicker-$curr_lang.js", __FILE__ ), '', '', false );
				}
			}
			
		}
	
	}
	$bkap_admin_bookings = new bkap_admin_bookings();
}
?>