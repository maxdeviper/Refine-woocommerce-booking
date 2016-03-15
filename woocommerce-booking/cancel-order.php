<?php
/* if(!class_exists('woocommerce_booking')){
    die();
}*/

include_once( 'bkap-common.php' );
include_once( 'lang.php' );

class bkap_cancel_order{

	/**********************************************************************
	 * This function will add cancel order button on the “MY ACCOUNT”  page. 
     * For cancelling the order.
	**********************************************************************/
			
	public static function bkap_get_add_cancel_button( $order, $action ){
		$myaccount_page_id = get_option( 'woocommerce_myaccount_page_id' );
		
		if ( $myaccount_page_id ) {
			$myaccount_page_url = get_permalink( $myaccount_page_id );
		}
			
		if ( isset( $_GET['order_id'] ) &&  $_GET['order_id'] == $action->id && $_GET['cancel_order'] == "yes" ) {
			$order_obj = new WC_Order( $action->id );
			$order_obj->update_status( "cancelled" );
			print('<script type="text/javascript">
				    location.href="'.$myaccount_page_url.'";
				   </script>');
		}
		
		if ( $action->status != "cancelled" ) {
			$order['cancel'] = array( "url"   => apply_filters( 'woocommerce_get_cancel_order_url', add_query_arg( 'order_id', $action->id )."&cancel_order=yes"),
				                      "name"  => "Cancel" );
		}
		
		return $order;
	}
	
	/*************************************************************
     * This function deletes booking for the products in order 
     * when the order is cancelled or refunded.
     ************************************************************/
	
	public static function bkap_woocommerce_cancel_order( $order_id ) {
		global $wpdb,$post;
		
		$array        =   array();
		$order_obj    =   new WC_order( $order_id );
		$order_items  =   $order_obj->get_items();
		$select_query =   "SELECT booking_id FROM `".$wpdb->prefix."booking_order_history`
						  WHERE order_id= %d";
		$results      =   $wpdb->get_results ( $wpdb->prepare( $select_query, $order_id ) );
		
        $booking_details = array();
		
        foreach ( $results as $key => $value ) {
		    
		    $select_query_post   =   "SELECT post_id,start_date, end_date, from_time, to_time FROM `".$wpdb->prefix."booking_history`
								     WHERE id= %d";
		    	
		    $results_post      =   $wpdb->get_results( $wpdb->prepare( $select_query_post, $value->booking_id ) );
		    
		    $booking_info = array( 'post_id' => $results_post[0]->post_id,
		                           'start_date' => $results_post[0]->start_date,
		                           'end_date' => $results_post[0]->end_date,
		                           'from_time' => $results_post[0]->from_time,
		                           'to_time' => $results_post[0]->to_time 
		                      );
		    $booking_details[ $value->booking_id ] = $booking_info;
		    
		}
		
		$i = 0;
		
		foreach( $order_items as $item_key => $item_value ) {
		    // check the booking status, if the status is cancelled, do not re-allot the item as that has already been done
		    $_status = $item_value[ 'wapbk_booking_status' ];
		    if ( ( isset( $_status ) && $_status != 'cancelled' ) || ! isset( $_status ) ) {
		        
		        // find the correct booking ID from the results array and pass the same
		        foreach ( $booking_details as $booking_id => $booking_data ) {
		            if ( $item_value[ 'product_id' ] == $booking_data['post_id'] ) {
		                // cross check the date and time as well as the product can be added to the cart more than once with different booking details
		                if ( $item_value[ 'wapbk_booking_date' ] == $booking_data[ 'start_date' ] ) {
		        
		                    $time = $booking_data[ 'from_time' ] . ' - ' . $booking_data[ 'to_time' ];
		                    if ( isset( $item_value[ 'wapbk_checkout_date' ] ) && ( $item_value[ 'wapbk_checkout_date' ] == $booking_data[ 'end_date' ] ) ) {
		                        $item_booking_id = $booking_id;
		                        break;
		                    } else if( isset( $item_value[ 'wapbk_time_slot' ] ) && ( $item_value[ 'wapbk_time_slot'] == $time ) ) {
		                        $item_booking_id = $booking_id;
		                        break;
		                    } else {
		                        $item_booking_id = $booking_id;
		                        break;
		                    }
		                }
		            }
		        }
		        self::bkap_reallot_item( $item_value, $item_booking_id, $order_id );
		        $i++;
		        $product_id      =   bkap_common::bkap_get_product_id( $item_value['product_id'] );
		     
		    }
		}
	}
	
	/**
	 * Re-allots the booking date and/or time for each item in the order
	 * 
	 * @param array $item_value
	 * @param int $booking_id
	 * @param int $order_id
	 */
	public static function bkap_reallot_item( $item_value, $booking_id, $order_id ) {
	    global $wpdb;
	    global $post;
	     
	    $product_id      =   bkap_common::bkap_get_product_id( $item_value['product_id'] );
	     
	    $_product        =   get_product( $product_id );
	    $parent_id       =   $_product->get_parent();
	
	    if( array_key_exists( "variation_id", $item_value ) ) {
	        $variation_id = $item_value['variation_id'];
	    } else {
	        $variation_id = '';
	    }
	
        $booking_settings   =   get_post_meta( $product_id, 'woocommerce_booking_settings', true );
        $qty                =   $item_value['qty'];
	         
        if ( isset( $variation_id ) && $variation_id != 0 ) {
            // Product Attributes - Booking Settings
            $attribute_booking_data = get_post_meta( $product_id, '_bkap_attribute_settings', true );
        
            if ( is_array( $attribute_booking_data ) && count( $attribute_booking_data ) > 0 ) {
                $attr_qty = 0;
                foreach ( $attribute_booking_data as $attr_name => $attr_settings ) {
                     
                    // check if the setting is on
                    if ( isset( $attr_settings[ 'booking_lockout_as_value' ] ) && 'on' == $attr_settings[ 'booking_lockout_as_value' ] ) {
                        if ( array_key_exists( $attr_name, $item_value ) && $item_value[ $attr_name ]  != 0 ) {
                            $attr_qty += $item_value[ $attr_name ];
                        }
                    }
                }
                if ( isset( $attr_qty ) && $attr_qty > 0 ){
                    $attr_qty = $attr_qty * $item_value['qty'];
                }
            }
        }
	         
        if ( isset( $attr_qty ) && $attr_qty > 0 ){
            $qty = $attr_qty;
        }
         
        $from_time  =   '';
        $to_time    =   '';
        $date_date  =   '';
        $end_date   =   '';
	                 
        if( isset( $booking_settings['booking_enable_multiple_day'] ) && $booking_settings['booking_enable_multiple_day'] == 'on' ) {
             
            if ( isset( $parent_id ) && $parent_id != '' ) {
                 
                // double the qty as we need to delete records for the child product as well as the parent product
                $qty               +=   $qty;
                $booking_id        +=   1;
                $first_record_id    =   $booking_id - $qty;
                $first_record_id   +=   1;
                $select_data_query  =   "DELETE FROM `".$wpdb->prefix."booking_history`
												WHERE ID BETWEEN %d AND %d";
                $results_data       =   $wpdb->query( $wpdb->prepare( $select_data_query, $first_record_id, $booking_id ) );
            }

            // if parent ID is not found, means its a normal product
            else {
                // DELETE the records using the ID in the booking history table.
                // The ID in the order history table, is the last record inserted for the order, so find the first ID by subtracting the qty
                $first_record_id    =   $booking_id - $qty;
                 
                $first_record_id   +=   1;
                 
                $select_data_query  =   "DELETE FROM `".$wpdb->prefix."booking_history`
											WHERE ID BETWEEN %d AND %d";
                $results_data       =   $wpdb->query( $wpdb->prepare( $select_data_query, $first_record_id, $booking_id ) );
                 
                 
            }

        } else if( isset( $booking_settings['booking_enable_time'] ) && $booking_settings['booking_enable_time'] == 'on' ) {
            $type_of_slot = apply_filters( 'bkap_slot_type', $product_id );

            if( $type_of_slot == 'multiple' ) {
                do_action( 'bkap_order_status_cancelled', $order_id, $item_value, $booking_id );
            }else {
                $select_data_query  =   "SELECT * FROM `".$wpdb->prefix."booking_history`
									        WHERE id= %d";
                $results_data       =   $wpdb->get_results ( $wpdb->prepare( $select_data_query, $booking_id ) );
                $j                  =   0;
                 
                foreach( $results_data as $k => $v ){
                    $start_date    =   $results_data[ $j ]->start_date;
                    $from_time     =   $results_data[ $j ]->from_time;
                    $to_time       =   $results_data[ $j ]->to_time;

                    if ( $from_time != '' && $to_time != '' || $from_time != '' ){
                        $parent_query = "";
                        if($to_time != ''){
                             
                             
                            //over lapaing time slots free booking product level
                            $query = "SELECT from_time, to_time, available_booking  FROM `".$wpdb->prefix."booking_history`
						                   WHERE post_id = '".$product_id."' AND
						                   start_date = '".$start_date."' ";
                            $get_all_time_slots = $wpdb->get_results( $query );

                            foreach( $get_all_time_slots as $time_slot_key => $time_slot_value){
                                 
                                $query_from_time_time_stamp = strtotime($from_time);
                                $query_to_time_time_stamp = strtotime($to_time);
                                $time_slot_value_from_time_stamp = strtotime($time_slot_value->from_time);
                                $time_slot_value_to_time_stamp = strtotime($time_slot_value->to_time);

                                $revised_available_booking = $time_slot_value->available_booking + $qty;
                                 
                                if( $query_to_time_time_stamp > $time_slot_value_from_time_stamp && $query_from_time_time_stamp < $time_slot_value_to_time_stamp ){

                                    if ( $time_slot_value_from_time_stamp != $query_from_time_time_stamp || $time_slot_value_to_time_stamp != $query_to_time_time_stamp ) {
                                        $query = "UPDATE `".$wpdb->prefix."booking_history`
    								                SET available_booking = ".$revised_available_booking."
    								                WHERE post_id = '".$product_id."' AND
    								                start_date = '".$start_date."' AND
    								                from_time = '".$time_slot_value->from_time."' AND
    								                to_time = '".$time_slot_value->to_time."' AND
    								                total_booking > 0";

                                        $wpdb->query( $query );
                                    }
                                }
                            }
                            $query = "UPDATE `".$wpdb->prefix."booking_history`
											SET available_booking = available_booking + ".$qty."
											WHERE
											id = '".$booking_id."' AND
										start_date = '".$start_date."' AND
										from_time = '".$from_time."' AND
										to_time = '".$to_time."'";
                            //Update records for parent products - Grouped Products
                            if ( isset( $parent_id ) && $parent_id != '' ) {
                                $parent_query   =   "UPDATE `".$wpdb->prefix."booking_history`
												         SET available_booking = available_booking + ".$qty."
												         WHERE
												         post_id = '".$parent_id."' AND
												         start_date = '".$start_date."' AND
												         from_time = '".$from_time."' AND
												         to_time = '".$to_time."'";
                                $select         =   "SELECT * FROM `".$wpdb->prefix."booking_history`
														WHERE post_id = %d AND
														start_date = %s AND
														from_time = %s AND
														to_time = %s";
                                $select_results =   $wpdb->get_results( $wpdb->prepare( $select, $parent_id, $start_date, $from_time, $to_time ) );
                                 
                                foreach( $select_results as $k => $v ) {
                                    $details[ $product_id ] = $v;
                                }
                            }

                            $select          =   "SELECT * FROM `".$wpdb->prefix."booking_history`
    												 WHERE post_id = %d AND
    												 start_date = %s AND
    												 from_time = %s AND
    												 to_time = %s";
                            $select_results  =   $wpdb->get_results( $wpdb->prepare( $select, $product_id, $start_date, $from_time, $to_time ) );

                            foreach( $select_results as $k => $v ) {
                                $details[ $product_id ] = $v;
                            }

                        } else {
                            $query   =   "UPDATE `".$wpdb->prefix."booking_history`
											  SET available_booking = available_booking + ".$qty."
											  WHERE
											  id = '".$booking_id."' AND
											  start_date = '".$start_date."' AND
											  from_time = '".$from_time."'";
                             
                            //Update records for parent products - Grouped Products
                            if ( isset( $parent_id ) && $parent_id != '' ) {
                                $parent_query   =   "UPDATE `".$wpdb->prefix."booking_history`
														SET available_booking = available_booking + ".$qty."
														WHERE
														post_id = '".$parent_id."' AND
														start_date = '".$start_date."' AND
														from_time = '".$from_time."'";
                                $select         =   "SELECT * FROM `".$wpdb->prefix."booking_history`
														WHERE post_id = %d AND
														start_date = %s AND
														from_time = %s";
                                $select_results =   $wpdb->get_results( $wpdb->prepare( $select, $parent_id, $start_date, $from_time ) );
                                 
                                foreach( $select_results as $k => $v ) {
                                    $details[$product_id] = $v;
                                }
                            }

                            $select          =   "SELECT * FROM `".$wpdb->prefix."booking_history`
													 WHERE post_id = %d AND
													 start_date = %s AND
													 from_time = %s";
                            $select_results  =   $wpdb->get_results( $wpdb->prepare($select,$product_id,$start_date,$from_time) );

                            foreach( $select_results as $k => $v ) {
                                $details[ $product_id ] = $v;
                            }
                        }
                        $wpdb->query( $query );
                        $wpdb->query( $parent_query );
                    }
                    $j++;
                }
            }
        } else {
            $select_data_query   =   "SELECT * FROM `".$wpdb->prefix."booking_history`
								         WHERE id= %d";
            $results_data        =   $wpdb->get_results ( $wpdb->prepare( $select_data_query, $booking_id ) );
            $j                   =   0;

            foreach( $results_data as $k => $v ) {
                $start_date     =   $results_data[$j]->start_date;
                $from_time      =   $results_data[$j]->from_time;
                $to_time        =   $results_data[$j]->to_time;
                $query          =   "UPDATE `".$wpdb->prefix."booking_history`
										SET available_booking = available_booking + ".$qty."
										WHERE
										id = '".$booking_id."' AND
										start_date = '".$start_date."' AND
										from_time = '' AND
										to_time = ''";
                $wpdb->query( $query );
                 
                //Update records for parent products - Grouped Products
                if ( isset( $parent_id ) && $parent_id != '' ) {
                    $parent_query  =   "UPDATE `".$wpdb->prefix."booking_history`
									        SET available_booking = available_booking + ".$qty."
											WHERE
											post_id = '".$parent_id."' AND
											start_date = '".$start_date."' AND
											from_time = '' AND
											to_time = ''";
                    $wpdb->query( $parent_query );
                }
            }
            $j++;
        }
	                
	    $book_global_settings    =   json_decode( get_option( 'woocommerce_booking_global_settings' ) );
	    $global_timeslot_lockout =   '';
	    $label                   =   get_option( "book.item-meta-date" );
	    $hidden_date             =   '';
	
	    if ( isset( $start_date ) && $start_date != '' ) {
	        $hidden_date = date( 'd-n-Y', strtotime( $start_date ) );
	    }
	
	    if ( isset( $booking_settings['booking_time_settings'][ $hidden_date ] ) ){
	        $lockout_settings = $booking_settings['booking_time_settings'][ $hidden_date ];
	    } else {
	        $lockout_settings = array();
	    }
	     
	    if(count($lockout_settings) == 0){
	        $week_day = date('l',strtotime($hidden_date));
	        $weekdays = bkap_get_book_arrays('weekdays');
	        $weekday = array_search($week_day,$weekdays);
	        if (isset($booking_settings['booking_time_settings'][$weekday])) $lockout_settings = $booking_settings['booking_time_settings'][$weekday];
	        else $lockout_settings = array();
	    }
	     
	    if(count($lockout_settings) > 0) {
	        $week_day = date('l',strtotime($hidden_date));
	        $weekdays = bkap_get_book_arrays('weekdays');
	        $weekday = array_search($week_day,$weekdays);
	        if (isset($booking_settings['booking_time_settings'][$weekday])){
	            $lockout_settings = $booking_settings['booking_time_settings'][$weekday];
	        } else {
	            $lockout_settings = array();
	        }
	         
	    }
	
	    $from_lockout_time = explode(":",$from_time);
	    if( isset( $from_lockout_time[0] ) ){
	        $from_hours = $from_lockout_time[0];
	    } else {
	        $from_hours = '';
	    }
	     
	    if( isset( $from_lockout_time[1] ) ){
	        $from_minute = $from_lockout_time[1];
	    } else {
	        $from_minute = '';
	    }
	     
	    if( $to_time != '' ) {
	        $to_lockout_time    =   explode( ":", $to_time );
	        $to_hours           =   $to_lockout_time[0];
	        $to_minute          =   $to_lockout_time[1];
	    } else {
	        $to_hours           =   '';
	        $to_minute          =   '';
	    }
	
	    if( count( $lockout_settings ) > 0 ) {
	         
	        foreach( $lockout_settings as $l_key => $l_value ) {
	
	            if( $l_value['from_slot_hrs'] == $from_hours && $l_value['from_slot_min'] == $from_minute && $l_value['to_slot_hrs'] == $to_hours && $l_value['to_slot_min'] == $to_minute ) {
	                 
	                if ( isset($l_value['global_time_check'] ) ){
	                    $global_timeslot_lockout = $l_value['global_time_check'];
	                }else{
	                    $global_timeslot_lockout = '';
	                }
	                 
	            }
	        }
	    }
	
	    if(isset($book_global_settings->booking_global_timeslot) && $book_global_settings->booking_global_timeslot == 'on' || isset($global_timeslot_lockout) && $global_timeslot_lockout == 'on') {
	         
	        $args = array( 'post_type' => 'product', 'posts_per_page' => -1 );
	        $product = query_posts( $args );
	        foreach($product as $k => $v) {
	            $product_ids[] = $v->ID;
	        }
	         
	        foreach( $product_ids as $k => $v ) {
	
	            $duplicate_of      =   bkap_common::bkap_get_product_id( $v );
	
	            $booking_settings  =   get_post_meta( $v, 'woocommerce_booking_settings', true );
	
	            if ( isset( $booking_settings['booking_enable_time'] ) && $booking_settings['booking_enable_time'] == 'on' ) {
	                 
	                if ( isset( $details ) && count( $details ) > 0 ) {
	
	                    if ( !array_key_exists( $duplicate_of, $details ) ) {
	                         
	                        foreach( $details as $key => $val ) {
	                            $start_date    =   $val->start_date;
	                            $from_time     =   $val->from_time;
	                            $to_time       =   $val->to_time;
	                            $revised_available_booking = '';
	                            if($to_time != "") {
	
	                                //over lapaing time slots free booking product level
	                                $query = "SELECT from_time, to_time, available_booking  FROM `".$wpdb->prefix."booking_history`
									                   WHERE post_id = '".$duplicate_of."' AND
									                   start_date = '".$start_date."' ";
	                                $get_all_time_slots = $wpdb->get_results( $query );
	                                 
	                                foreach( $get_all_time_slots as $time_slot_key => $time_slot_value){
	
	                                    $query_from_time_time_stamp = strtotime($from_time);
	                                    $query_to_time_time_stamp = strtotime($to_time);
	                                    $time_slot_value_from_time_stamp = strtotime($time_slot_value->from_time);
	                                    $time_slot_value_to_time_stamp = strtotime($time_slot_value->to_time);
	
	                                    if( $query_to_time_time_stamp > $time_slot_value_from_time_stamp && $query_from_time_time_stamp < $time_slot_value_to_time_stamp ){
	                                         
	                                        if ( $time_slot_value_from_time_stamp != $query_from_time_time_stamp || $time_slot_value_to_time_stamp != $query_to_time_time_stamp ) {
	                                            $query = "UPDATE `".$wpdb->prefix."booking_history`
                								                SET available_booking = available_booking + ".$qty."
                								                WHERE post_id = '".$duplicate_of."' AND
                								                start_date = '".$start_date."' AND
                								                from_time = '".$time_slot_value->from_time."' AND
                								                to_time = '".$time_slot_value->to_time."' AND
                								                total_booking > 0";
	                                             
	                                            $wpdb->query( $query );
	                                        }
	                                    }
	                                }
	
	                                $query = "UPDATE `".$wpdb->prefix."booking_history`
												SET available_booking = available_booking + ".$qty."
												WHERE post_id = '".$duplicate_of."' AND
												start_date = '".$start_date."' AND
												from_time = '".$from_time."' AND
												to_time = '".$to_time."'";
	                                $wpdb->query($query);
	                            } else {
	                                $query    =   "UPDATE `".$wpdb->prefix."booking_history`
    												  SET available_booking = available_booking + ".$qty."
    												  WHERE post_id = '".$duplicate_of."' AND
    												  start_date = '".$start_date."' AND
    												  from_time = '".$from_time."'";
	                                $wpdb->query( $query );
	                            }
	
	                        }
	                    }
	                }
	            }
	        }
	    }
	}
	
}
?>