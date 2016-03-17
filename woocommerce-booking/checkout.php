<?php 
//if(!class_exists('woocommerce_booking')){
//   die();
//}
include_once( 'bkap-common.php' );
class bkap_checkout{

	/**
	 * Hide the hardcoded item meta records frm being 
	 * displayed on the admin orders page
	 */ 
    
	public static function bkap_hidden_order_itemmeta( $arr ){
		$arr[] = '_wapbk_checkout_date';
		$arr[] = '_wapbk_booking_date';
		$arr[] = '_wapbk_time_slot';
		$arr[] = '_wapbk_booking_status';
		
		return $arr;
	}
	
   /**
	* This function updates the database for the booking 
	* details and adds booking fields on the Order Received page,
	* Woocommerce->Orders when an order is placed for Woocommerce
	* version breater than 2.0.
	*/
	
	public static function bkap_order_item_meta( $item_meta, $cart_item ) {
		if ( version_compare( WOOCOMMERCE_VERSION, "2.0.0" ) < 0 ) {
			return;
		}
		
		// Add the fields
		global $wpdb;	
		global $woocommerce;
		
		$booking_data_present =   "NO";
		
		// check if data is already present for this order in the booking_order_history table
		// if yes, then set variable to ensure we don't re-create records for the plugin tables.
		$check_data           =   "SELECT * FROM `".$wpdb->prefix."booking_order_history`
		                          WHERE order_id = %s";
		$results_check        =   $wpdb->get_results ( $wpdb->prepare ( $check_data, $item_meta ) );
		
		if ( count ( $results_check ) > 0 ) {
			$booking_data_present = "YES";
		}
		
		$order_item_ids   =   array();
		$sub_query        =   "";
		$ticket_content   =   array();
		$i                =   0;
		
		foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
			$_product    =   $values['data'];
			$parent_id   =   $_product->get_parent();
			
			if( array_key_exists( "variation_id", $values ) ){
				$variation_id = $values['variation_id'];
			}
			else {
				$variation_id = '';
			}
			
			if ( isset( $values['bkap_booking'] ) ) {
				$booking = $values['bkap_booking'];
			}
			
			$post_id     =   bkap_common::bkap_get_product_id( $values['product_id'] );
			
			$quantity    =   $values['quantity'];
			
			// to accomodate the change where attribute values are taken as quantity
				
			// Product Attributes - Booking Settings
			$attribute_booking_data = get_post_meta( $post_id, '_bkap_attribute_settings', true );
				
			// attribute values in the order
			$order_attr = $values[ 'variation' ];
				
			if ( is_array( $attribute_booking_data ) && count( $attribute_booking_data ) > 0 ) {
			    $attr_qty = 0;
			    foreach ( $attribute_booking_data as $attr_name => $attr_settings ) {
			        $attr_name = 'attribute_' . $attr_name;
			        // check if the setting is on
			        if ( isset( $attr_settings[ 'booking_lockout_as_value' ] ) && 'on' == $attr_settings[ 'booking_lockout_as_value' ] ) {
			            if ( array_key_exists( $attr_name, $order_attr ) && $order_attr[ $attr_name ]  != 0 ) {
			                $attr_qty += $order_attr[ $attr_name ];
			            }
			        }
			    }
			    if ( isset( $attr_qty ) && $attr_qty > 0 ){
			        $attr_qty = $attr_qty * $values[ 'quantity' ];
			    }
			}
				
			if ( isset( $attr_qty ) && $attr_qty > 0 ){
			    $quantity = $attr_qty;
			}
				
			$post_title  =   $_product->get_title();
			
			// Fetch line item
			if ( count( $order_item_ids ) > 0 ) {
				$order_item_ids_to_exclude  = implode( ",", $order_item_ids );
				$sub_query                  = " AND order_item_id NOT IN (".$order_item_ids_to_exclude.")";
			}
				
			$query               =   "SELECT order_item_id,order_id FROM `".$wpdb->prefix."woocommerce_order_items`
						              WHERE order_id = %s AND order_item_name = %s".$sub_query;
		
			$results             =   $wpdb->get_results( $wpdb->prepare( $query, $item_meta, $post_title ) );
		
			$order_item_ids[]    =   $results[0]->order_item_id;
			$order_id            =   $results[0]->order_id;
			$order_obj           =   new WC_order( $order_id );
			$details             =   array();
			$product_ids         =   array();
		
			$order_items = $order_obj->get_items();
				
			$type_of_slot = apply_filters( 'bkap_slot_type', $post_id );
			do_action( 'bkap_update_order', $values,$results[0] );
		
			if( $type_of_slot == 'multiple' ) {
				$hidden_date = $booking[0]['hidden_date'];
				do_action( 'bkap_update_booking_history', $values, $results[0] );
			}
			else {
				if ( isset( $values['bkap_booking'] ) ) :
					$booking_settings  =   get_post_meta( $post_id, 'woocommerce_booking_settings', true );
					$details           =   array();
					
					if ( 'bkap-booking-gateway' == WC()->session->get( 'chosen_payment_method' ) ) {
					    wc_add_order_item_meta( $results[0]->order_item_id, '_wapbk_booking_status', 'pending-confirmation' );
					} else {
					    wc_add_order_item_meta( $results[0]->order_item_id, '_wapbk_booking_status', 'confirmed' );
					}
					
					if ( $booking[0]['date'] != "" ) {
						$name         =   get_option( 'book.item-meta-date' );
						$date_select  =   $booking[0]['date'];
						
						wc_add_order_item_meta( $results[0]->order_item_id, $name, sanitize_text_field( $date_select, true ) );
					}
					
					if ( array_key_exists( 'hidden_date', $booking[0] ) && $booking[0]['hidden_date'] != "" ) {
						// save the date in Y-m-d format
						$date_booking = date( 'Y-m-d', strtotime( $booking[0]['hidden_date'] ) );
						wc_add_order_item_meta( $results[0]->order_item_id, '_wapbk_booking_date', sanitize_text_field( $date_booking, true ) );
					}
					
					if ( array_key_exists( 'date_checkout', $booking[0] ) && $booking[0]['date_checkout'] != "" ) {
		
						if ( $booking_settings['booking_enable_multiple_day'] == 'on' ) {
							$name_checkout           =   get_option( 'checkout.item-meta-date' );
							$date_select_checkout    =   $booking[0]['date_checkout'];
						
							wc_add_order_item_meta( $results[0]->order_item_id, $name_checkout, sanitize_text_field( $date_select_checkout, true ) );
						}
					}
					
					if ( array_key_exists( 'hidden_date_checkout', $booking[0] ) && $booking[0]['hidden_date_checkout'] != "" ) {
							
						if ( $booking_settings['booking_enable_multiple_day'] == 'on' ) {
							// save the date in Y-m-d format
							$date_booking = date( 'Y-m-d', strtotime( $booking[0]['hidden_date_checkout'] ) );
							wc_add_order_item_meta( $results[0]->order_item_id, '_wapbk_checkout_date', sanitize_text_field( $date_booking, true ) );
						}
					}
					
					if ( array_key_exists( 'time_slot', $booking[0] ) && $booking[0]['time_slot'] != "" ) {
						$time_slot_to_display     =   '';
						$time_select              =   $booking[0]['time_slot'];
						$time_exploded            =   explode( "-", $time_select );
					
						$saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
						
						if ( isset( $saved_settings ) ) {
							$time_format = $saved_settings->booking_time_format;
						}else{
							$time_format = "12";
						}
						
						$time_slot_to_display = '';
						$from_time = trim($time_exploded[0]);
						
						if( isset( $time_exploded[1] ) ){
							$to_time = trim( $time_exploded[1] );
						}else{
							$to_time = '';
						}
						
						if ( $time_format == '12' ) {
							$from_time = date( 'h:i A', strtotime( $time_exploded[0] ) );
							if( isset( $time_exploded[1] ) )$to_time = date( 'h:i A', strtotime( $time_exploded[1] ) );
						}
						
						$query_from_time  =   date( 'G:i', strtotime( $time_exploded[0] ) );
						$meta_data_format =   $query_from_time;
						
						if( isset( $time_exploded[1] ) ){
							$query_to_time       = date( 'G:i', strtotime( $time_exploded[1] ) );
							$meta_data_format   .= ' - ' . $query_to_time;
						}else{
							$query_to_time       = '';
						}
						
						if( $to_time != '' ) {
							$time_slot_to_display = $from_time.' - '.$to_time;
						}else {
							$time_slot_to_display = $from_time;
						}
						
						wc_add_order_item_meta( $results[0]->order_item_id,  get_option( 'book.item-meta-time' ), $time_slot_to_display, true );
						wc_add_order_item_meta( $results[0]->order_item_id,  '_wapbk_time_slot', $meta_data_format, true );
							
					}
					
					// if yes, then execute code above for all items. Below code is not needed as it has already been executed once
					if ( $booking_data_present == "YES" ) {
						continue;
					}
					
					$hidden_date   =   $booking[0]['hidden_date'];
					$date_query    =   date( 'Y-m-d', strtotime( $hidden_date ) );
					
					if( array_key_exists( 'hidden_date_checkout', $booking[0] ) ) {
						$date_checkout        =   $booking[0]['hidden_date_checkout'];
						$date_checkout_query  =   date( 'Y-m-d', strtotime( $date_checkout ) );
					}
		
					if ( isset( $booking_settings['booking_enable_multiple_day'] )&& $booking_settings['booking_enable_multiple_day'] == 'on' ) {
						
					    for ( $i = 0; $i < $quantity; $i++ ) {
							$query           =   "INSERT INTO `".$wpdb->prefix."booking_history`
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
							
							$new_booking_id  = $wpdb->insert_id;
							
							//Insert records for parent products - Grouped Products
							if ( isset( $parent_id ) && $parent_id != '' ) {
								$query_parent   =   "INSERT INTO `".$wpdb->prefix."booking_history`
    												(post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
    												VALUES (
    												'".$parent_id."',
    												'',
    												'".$date_query."',
    												'".$date_checkout_query."',
    												'',
    												'',
    												'0',
    												'0' )";
								$wpdb->query( $query_parent );
							}
						}
						
						$order_query  =   "INSERT INTO `".$wpdb->prefix."booking_order_history`
                    					  (order_id,booking_id)
                    					  VALUES (
                    					  '".$order_id."',
                    					  '".$new_booking_id."' )";
						$wpdb->query( $order_query );
					} 
					else {
						
					    if( isset( $booking[0]['time_slot'] ) && $booking[0]['time_slot'] != "" ) {
							
					        if( $query_to_time != "" ) {
								// same product time slots over lapping
								$booking_settings   =   get_post_meta( $post_id, 'woocommerce_booking_settings' , true );
								$query              =   "SELECT from_time, to_time  FROM `".$wpdb->prefix."booking_history`
                    									WHERE post_id = '".$post_id."' AND
                    									start_date = '".$date_query."' AND
                    									status !=  'inactive' ";
								$get_all_time_slots = $wpdb->get_results( $query );
								
								foreach( $get_all_time_slots as $time_slot_key => $time_slot_value ){
								
								    $query_from_time_time_stamp         = strtotime( $query_from_time );
								    $query_to_time_time_stamp           = strtotime( $query_to_time );
								    $time_slot_value_from_time_stamp    = strtotime( $time_slot_value->from_time );
								    $time_slot_value_to_time_stamp      = strtotime( $time_slot_value->to_time );
								 
								    if( $query_to_time_time_stamp > $time_slot_value_from_time_stamp && $query_from_time_time_stamp < $time_slot_value_to_time_stamp ){
								
								        if ( $time_slot_value_from_time_stamp != $query_from_time_time_stamp || $time_slot_value_to_time_stamp != $query_to_time_time_stamp ) {
								            $query  =   "UPDATE `".$wpdb->prefix."booking_history`
                        								SET available_booking = available_booking - ".$quantity."
                        								WHERE post_id = '".$post_id."' AND
                        								start_date = '".$date_query."' AND
                        								from_time = '".$time_slot_value->from_time."' AND
                        								to_time = '".$time_slot_value->to_time."' AND
                        								total_booking > 0";
								            $wpdb->query( $query );
								        }
								    }
								}
								$query  =   "UPDATE `".$wpdb->prefix."booking_history`
            								SET available_booking = available_booking - ".$quantity."
            								WHERE post_id = '".$post_id."' AND
            								start_date = '".$date_query."' AND
            								from_time = '".$query_from_time."' AND
            								to_time = '".$query_to_time."' AND
            								total_booking > 0";
								$wpdb->query( $query );
								
								//Update records for parent products - Grouped Products
								if ( isset( $parent_id ) && $parent_id != '' ) {
									$query     =   "UPDATE `".$wpdb->prefix."booking_history`
    												SET available_booking = available_booking - ".$quantity."
    												WHERE post_id = '".$parent_id."' AND
    												start_date = '".$date_query."' AND
    												from_time = '".$query_from_time."' AND
    												to_time = '".$query_to_time."' AND
    												total_booking > 0";
									$wpdb->query( $query );
								}
								$select         =   "SELECT * FROM `".$wpdb->prefix."booking_history`
                    								WHERE post_id = %d AND
                    								start_date = %s AND
                    								from_time = %s AND
                    								to_time = %s";
								$select_results =   $wpdb->get_results( $wpdb->prepare( $select, $post_id, $date_query, $query_from_time, $query_to_time ) );
								foreach( $select_results as $k => $v ) {
									$details[ $post_id ] = $v;
								}
								
								
							}
							else {
								$query  =   "UPDATE `".$wpdb->prefix."booking_history`
            								SET available_booking = available_booking - ".$quantity."
            								WHERE post_id = '".$post_id."' AND
            								start_date = '".$date_query."' AND
            								from_time = '".$query_from_time."' AND
            								total_booking > 0";
								$wpdb->query( $query );
								
								//Update records for parent products - Grouped Products
								if ( isset( $parent_id) && $parent_id != '' ) {
									$query     =   "UPDATE `".$wpdb->prefix."booking_history`
    												SET available_booking = available_booking - ".$quantity."
    												WHERE post_id = '".$parent_id."' AND
    												start_date = '".$date_query."' AND
    												from_time = '".$query_from_time."' AND
    												total_booking > 0";
									$wpdb->query( $query );
								}
								$select         =   "SELECT * FROM `".$wpdb->prefix."booking_history`
                    								WHERE post_id =  %d AND
                    								start_date = %s AND
                    								from_time = %s";
								$select_results =   $wpdb->get_results( $wpdb->prepare( $select, $post_id, $date_query, $query_from_time ) );
								foreach( $select_results as $k => $v ) {
									$details[$post_id] = $v;
								}
								
								
							}
						}
						else {
							$query   =   "UPDATE `".$wpdb->prefix."booking_history`
										 SET available_booking = available_booking - ".$quantity."
										 WHERE post_id = '".$post_id."' AND
										 start_date = '".$date_query."' AND
										 total_booking > 0";
							$wpdb->query( $query );
							
							//Update records for parent products - Grouped Products
							if ( isset( $parent_id ) && $parent_id != '' ) {
								$query  =   "UPDATE `".$wpdb->prefix."booking_history`
											SET available_booking = available_booking - ".$quantity."
											WHERE post_id = '".$parent_id."' AND
											start_date = '".$date_query."' AND
											total_booking > 0";
								$wpdb->query( $query );
								$update_one_time_singe_day_parent = 'true';
							}
						}
		
					}
					if ( isset ( $booking_settings['booking_enable_multiple_day'] ) && $booking_settings['booking_enable_multiple_day'] != 'on'  ) {
						
					    if ( isset ( $booking[0]['time_slot'] ) && $booking[0]['time_slot'] != "" && array_key_exists ( 'date', $booking[0] ) ) {
							
					        if ( isset ( $query_to_time ) && $query_to_time != '' ) {
								$order_select_query     =   "SELECT id FROM `".$wpdb->prefix."booking_history`
    														WHERE post_id = %d AND
    														start_date = %s AND
    														from_time = %s AND
    														to_time = %s AND 
    														status = ''";
								$order_results = $wpdb->get_results ( $wpdb->prepare ( $order_select_query, $post_id, $date_query, $query_from_time, $query_to_time ) );
							}  else {
								$order_select_query     =   "SELECT id FROM `".$wpdb->prefix."booking_history`
    														WHERE post_id = %d AND
    														start_date = %s AND
    														from_time = %s AND
    														status = ''";
								$order_results = $wpdb->get_results ( $wpdb->prepare ( $order_select_query, $post_id, $date_query, $query_from_time ) );
							}
						} else {
							$order_select_query  =   "SELECT id FROM `".$wpdb->prefix."booking_history`
													 WHERE post_id = %d AND
													 start_date = %s AND
													 status = ''";
							$order_results = $wpdb->get_results ( $wpdb->prepare ( $order_select_query, $post_id, $date_query ) );
						}
						$j = 0;
						
						foreach ( $order_results as $k => $v ) {
							$booking_id  =   $order_results[$j]->id;
							$order_query =   "INSERT INTO `".$wpdb->prefix."booking_order_history`
											 (order_id,booking_id)
											 VALUES (
											 '".$order_id."',
											 '".$booking_id."' )";
							$wpdb->query( $order_query );
							$j++;
						}
					}
				endif;
			}
			
			$book_global_settings    = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
			$booking_settings        = get_post_meta( $post_id, 'woocommerce_booking_settings' , true );
			
			if( isset( $booking_settings['booking_time_settings'] ) && isset( $hidden_date ) ){
				
			    if ( isset( $booking_settings['booking_time_settings'][ $hidden_date ] ) ) $lockout_settings = $booking_settings['booking_time_settings'][ $hidden_date ];
				else $lockout_settings = array();
				
				if ( count( $lockout_settings ) == 0 ){
					$week_day  =   date( 'l', strtotime( $hidden_date ) );
					$weekdays  =   bkap_get_book_arrays( 'weekdays' );
					$weekday   =   array_search( $week_day, $weekdays );
					
					if ( isset( $booking_settings['booking_time_settings'][$weekday] ) ) $lockout_settings = $booking_settings['booking_time_settings'][ $weekday ];
					else $lockout_settings = array();
				}
				
				$from_hours = $from_minute = $to_hours = $to_minute = '';
				
				if ( isset( $query_from_time ) ) {
				    $from_lockout_time =   explode( ":", $query_from_time );
					$from_hours        =   $from_lockout_time[0];
					$from_minute       =   $from_lockout_time[1];
					
					if ( isset( $query_to_time ) && $query_to_time != '' ) {
						$to_lockout_time  = explode( ":", $query_to_time );
						$to_hours         = $to_lockout_time[0];
						$to_minute        = $to_lockout_time[1];
					}
				}
				
				foreach ( $lockout_settings as $l_key => $l_value ) {
					
				    if ( $l_value['from_slot_hrs'] == $from_hours && $l_value['from_slot_min'] == $from_minute && $l_value['to_slot_hrs'] == $to_hours && $l_value['to_slot_min'] == $to_minute ) {
						
				        if ( isset( $l_value['global_time_check'] ) ){
							$global_timeslot_lockout = $l_value['global_time_check'];
						} else{
							$global_timeslot_lockout = '';
						}
						
					}
				}
			}
			if ( isset( $book_global_settings->booking_global_timeslot ) && $book_global_settings->booking_global_timeslot == 'on' || isset( $global_timeslot_lockout ) && $global_timeslot_lockout == 'on' ) {
				$args       = array( 'post_type' => 'product', 'posts_per_page' => -1 );
				$product    = query_posts( $args );
				
				foreach($product as $k => $v){
					$product_ids[] = $v->ID;
				}
				
				foreach( $product_ids as $k => $v ){
					$duplicate_of  = bkap_common::bkap_get_product_id( $v );
					
					$booking_settings = get_post_meta( $v, 'woocommerce_booking_settings' , true );
					
					if( isset( $booking_settings['booking_enable_time'] ) && $booking_settings['booking_enable_time'] == 'on' ) {
				
						if( !array_key_exists( $duplicate_of,$details ) ) {
							
						    foreach( $details as $key => $val ){
								$booking_settings   = get_post_meta( $duplicate_of, 'woocommerce_booking_settings', true );
						
								$start_date         = $val->start_date;
								$from_time          = $val->from_time;
								$to_time            = $val->to_time;
								
								if( $to_time != "" ){
								    
								    // global time slots over lapping
								    $get_all_time_slots =   array();
								    
								    $insert             =   "NO";
								    
								    $query              =   "SELECT *  FROM `".$wpdb->prefix."booking_history`
                        									WHERE post_id = '".$duplicate_of."' AND
                        									start_date = '".$start_date."' AND
                        									available_booking > 0  ";
								    $get_all_time_slots =   $wpdb->get_results( $query );
								    
								    if( !$get_all_time_slots ){
								        $insert             =   "YES";
								        $query              =   "SELECT * FROM `".$wpdb->prefix."booking_history`
                    									        WHERE post_id = %d
                    								            AND weekday = %s
                    								            AND start_date = '0000-00-00' ";
								        $get_all_time_slots =   $wpdb->get_results( $wpdb->prepare( $query, $duplicate_of, $weekday ) );
								    }
								    
								    foreach( $get_all_time_slots as $time_slot_key => $time_slot_value){
								    
								        if( "YES" == $insert ){
								    
								            $query_insert   =   "INSERT INTO `".$wpdb->prefix."booking_history`
																(post_id,weekday,start_date,from_time,to_time,total_booking,available_booking)
																VALUES (
																'".$duplicate_of."',
																'".$weekday."',
																'".$start_date."',
																'".$time_slot_value->from_time."',
																'".$time_slot_value->to_time."',
																'".$time_slot_value->total_booking."',
																'".$time_slot_value->available_booking."' ) ";
								    
								            $wpdb->query( $query_insert );
								    
								        }
								    
								        $query_from_time_time_stamp         = strtotime( $from_time );
								        $query_to_time_time_stamp           = strtotime( $to_time );
								        $time_slot_value_from_time_stamp    = strtotime( $time_slot_value->from_time );
								        $time_slot_value_to_time_stamp      = strtotime( $time_slot_value->to_time );
								    
								        if( $query_to_time_time_stamp > $time_slot_value_from_time_stamp && $query_from_time_time_stamp < $time_slot_value_to_time_stamp ){
								    
								            if ( $time_slot_value_from_time_stamp != $query_from_time_time_stamp || $time_slot_value_to_time_stamp != $query_to_time_time_stamp ) {
								    
								                $query  =   "UPDATE `".$wpdb->prefix."booking_history`
                             								SET available_booking = available_booking - ".$quantity."
                             								WHERE post_id = '".$duplicate_of."' AND
                             								start_date = '".$start_date."' AND
                             								from_time = '".$time_slot_value->from_time."' AND
                             								to_time = '".$time_slot_value->to_time."' AND
                             								total_booking > 0";
								                $wpdb->query( $query );
								            }
								        }
								    }					    
								    
								    $query      =   "UPDATE `".$wpdb->prefix."booking_history`
                									SET available_booking = available_booking - ".$quantity."
                									WHERE post_id = '".$duplicate_of."' AND
                									start_date = '".$date_query."' AND
                									from_time = '".$from_time."' AND
                									to_time = '".$to_time."' ";
									$updated   = $wpdb->query( $query );
									
									if( $updated == 0 ) {
										
									    if($val->weekday == '') {
											$week_day    =   date( 'l', strtotime( $date_query ) );
											$weekdays    =   bkap_get_book_arrays( 'weekdays' );
											$weekday     =   array_search( $week_day, $weekdays );
											//echo $weekday;exit;
										} else {
											$weekday = $val->weekday;
										}
										
										$results  =   array();
										$query    =   "SELECT * FROM `".$wpdb->prefix."booking_history`
    												  WHERE post_id = %s
    												  AND weekday = %s
    												  AND start_date = '0000-00-00'";
								
										$results =    $wpdb->get_results( $wpdb->prepare( $query, $duplicate_of, $weekday ) );
										
										if ( !$results ) break;
										else {
											
										    foreach( $results as $r_key => $r_val ) {
												
										        if( $from_time == $r_val->from_time && $to_time == $r_val->to_time ) {
													$available_booking =   $r_val->available_booking - $quantity;
													$query_insert      =   "INSERT INTO `".$wpdb->prefix."booking_history`
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
	
												} else {
													$from_lockout_time = explode ( ":", $r_val->from_time );
													
													if ( isset( $from_lockout_time[0] ) ){
													   $from_hours = $from_lockout_time[0];
													}else{
													    $from_hours= " ";
													}
													
													if ( isset( $from_lockout_time[1] ) ){
													   $from_minute = $from_lockout_time[1];
													}else{
													    $from_minute = " ";
													}
													// default to blanks
													$to_hours = $to_minute = '';
													
													if ( isset( $r_val->to_time ) && $r_val->to_time != '' ) {
														$to_lockout_time = explode( ":", $r_val->to_time );
														
														if ( isset ( $to_lockout_time[0] ) ) {
															$to_hours = $to_lockout_time[0];
														}
														
														if ( isset ( $to_lockout_time[1] ) ) {
															$to_minute = $to_lockout_time[1];
														}
													}
													foreach ( $lockout_settings as $l_key => $l_value ) {
														
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
									$query     =   "UPDATE `".$wpdb->prefix."booking_history`
    												SET available_booking = available_booking - ".$quantity."
    												WHERE post_id = '".$duplicate_of."' AND
    												start_date = '".$date_query."' AND
    												from_time = '".$from_time."'
    												AND to_time = ''";
									
									$updated   =   $wpdb->query( $query );
									
									if ( $updated == 0 ) {
										if ( $val->weekday == '' ) {
											$week_day    =   date( 'l', strtotime( $date_query ) );
											$weekdays    =   bkap_get_book_arrays( 'weekdays' );
											$weekday     =   array_search( $week_day, $weekdays );
										
										} else {
											$weekday = $val->weekday;
										}
										
										$results  =   array();
										$query    =   "SELECT * FROM `".$wpdb->prefix."booking_history`
                									  WHERE post_id = %d
                									  AND weekday = %s
                									  AND start_date = '0000-00-00'
                									  AND to_time = '' ";
										$results  =   $wpdb->get_results( $wpdb->prepare( $query, $duplicate_of, $weekday ) );
										
										if ( !$results ) break;
										else {
											foreach( $results as $r_key => $r_val ) {
												
											    if ( $from_time == $r_val->from_time ) {
													$available_booking     =   $r_val->available_booking - $quantity;
													$query_insert          =   "INSERT INTO `".$wpdb->prefix."booking_history`
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
													$from_lockout_time     = explode( ":", $r_val->from_time );
													$from_hours            = $from_lockout_time[0];
													$from_minute           = $from_lockout_time[1];
													
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
			$ticket          =   array( apply_filters( 'bkap_send_ticket', $values,$order_obj ) );
			$ticket_content  =   array_merge( $ticket_content, $ticket );
			$i++;
			
			$start_date_label     = get_option( 'book.item-meta-date' );
			$checkout_date_label  = get_option( 'checkout.item-meta-date' );
			$time_slot_lable      = get_option( 'book.item-meta-time' );
				
			// The below code needs to be run only if WooCommerce verison is > 2.5
			if ( version_compare( WOOCOMMERCE_VERSION, "2.5" ) < 0 ) {
			    continue;
			} else {
    			// Code where the Booking dates and time slots dates are not displayed in the customer new order email from WooCommerce version 2.5
    			$cache_key       = WC_Cache_Helper::get_cache_prefix( 'orders' ) . 'item_meta_array_' . $results[ 0 ]->order_item_id;
    			$item_meta_array = wp_cache_get( $cache_key, 'orders' );
    			if ( false !== $item_meta_array ) {
    			    $metadata        = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value, meta_id FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id = %d AND meta_key IN (%s,%s,%s,%s,%s,%s) ORDER BY meta_id", absint( $results[ 0 ]->order_item_id ), $start_date_label, '_wapbk_booking_date',  $checkout_date_label, '_wapbk_checkout_date', $time_slot_lable, '_wapbk_time_slot'  ) );
    			    foreach ( $metadata as $metadata_row ) {
    			        $item_meta_array[ $metadata_row->meta_id ] = (object) array( 'key' => $metadata_row->meta_key, 'value' => $metadata_row->meta_value );
    			    }
    			    wp_cache_set( $cache_key, $item_meta_array, 'orders' );
    			}
			}
			
		}
	
		do_action( 'bkap_send_email', $ticket_content );
	}	 
}
?>