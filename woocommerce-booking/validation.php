<?php
//if(!class_exists('woocommerce_booking')){
//   die();
//}
include_once( 'bkap-common.php' );
include_once( 'lang.php' );

class bkap_validation{

   /**
    *This functions validates the Availability for the selected date and timeslots.
    */
    
	public static function bkap_get_validate_add_cart_item( $passed, $product_id, $qty ) {
		$booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
		
		if ( $booking_settings != '' && ( isset( $booking_settings['booking_enable_date'] ) && $booking_settings['booking_enable_date'] == 'on' ) ) {
			
		    if ( isset( $booking_settings['booking_purchase_without_date'] ) && $booking_settings['booking_purchase_without_date'] == 'on' ) {
				
		        if ( isset( $_POST['wapbk_hidden_date'] ) && $_POST['wapbk_hidden_date'] != "" ) {
					$quantity = bkap_validation::bkap_get_quantity( $product_id );
					
					if ( $quantity == 'yes' ) {
						$passed = true;
					}else {
						$passed = false;
					}
					
				}else $passed = true;
				
			}else {
				
			    if ( isset( $_POST['wapbk_hidden_date'] ) && $_POST['wapbk_hidden_date'] != "" ) {
					$quantity = bkap_validation::bkap_get_quantity( $product_id );
					
					if ( $quantity == 'yes' ) {
						$passed = true;
					}else {
						$passed = false;
					}
					
				}else $passed = false;
			}
		}else {
			$passed = true;
		}
		
		return $passed;
	}
	
	
   /**
	* This function checks the availabilty for the selected date 
	* and timeslots when the product is added to cart.
	* If availability is less then selected it prevents product from 
	* being added to the cart and displays an error message.
	*/
	public static function bkap_get_quantity( $post_id ) {
		global $wpdb,$woocommerce;
		
		$booking_settings = get_post_meta( $post_id , 'woocommerce_booking_settings', true );
		$post_title       = get_post($post_id);
		$date_check       = date( 'Y-m-d', strtotime( $_POST['wapbk_hidden_date'] ) );
        $product          = get_product( $post_id );
		$parent_id        = $product->get_parent();
			
		$saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
		
		if ( isset( $saved_settings ) )	{
			$time_format = $saved_settings->booking_time_format;
		}else {
			$time_format = "12";
		}
		
		$quantity_check_pass = 'yes';
		
		if ( isset( $_POST['variation_id'] ) ) {
			$variation_id = $_POST['variation_id'];
		}else {
			$variation_id = '';
		}
		$_POST[ 'product_id' ] = $post_id;
		
		// before checking lockout validations, confirm that the cart does not contain any conflicting products
		$quantity_check_pass = apply_filters( 'bkap_validate_cart_products', $_POST, $post_id );
		
		if ( $booking_settings['booking_enable_time'] == 'on' ) {
			$type_of_slot = apply_filters( 'bkap_slot_type', $post_id );
			
			if ( $type_of_slot == 'multiple' ) {
				$quantity_check_pass = apply_filters( 'bkap_validate_add_to_cart', $_POST, $post_id );
			}else {
			    do_action( 'bkap_date_time_product_validation' );
			     
			    if ( ! isset( $_POST[ 'validated' ] ) || ( isset( $_POST[ 'validated' ] ) && $_POST[ 'validated' ] == 'NO' ) ) {
			        
    			    if ( isset( $_POST['quantity'] ) ) $item_quantity = $_POST['quantity'];
    				else $item_quantity = 1;
    				
    				if ( isset( $_POST['time_slot'] ) ) {
    					$time_range    = explode( "-", $_POST['time_slot'] );
    					$from_time     = date( 'G:i', strtotime( $time_range[0] ) );
    					
    					if( isset( $time_range[1] ) ) $to_time = date( 'G:i', strtotime( $time_range[1] ) );
    					else $to_time = '';
    					
    				}else {
    					$to_time = '';
    					$from_time = '';
    				}
				
    				if ( $to_time != '' ) {
    					$query     =   "SELECT total_booking, available_booking, start_date FROM `".$wpdb->prefix."booking_history`
                    					WHERE post_id = %d
                    					AND start_date = %s
                    					AND from_time = %s
                    					AND to_time = %s
    							AND status !=  'inactive'";
    					$results   =   $wpdb->get_results( $wpdb->prepare( $query, $post_id, $date_check, $from_time, $to_time ) );
    				}else {
    					$query     =   "SELECT total_booking, available_booking, start_date FROM `".$wpdb->prefix."booking_history`
        								WHERE post_id = %d
        								AND start_date = %s
        								AND from_time = %s
    					            		AND status !=  'inactive'";
        								
    					$prepare_query = $wpdb->prepare( $query, $post_id, $date_check, $from_time );
    					$results = $wpdb->get_results( $prepare_query );
    				}
					
    				if ( isset( $results ) && count( $results ) > 0) {
    						
    					if ( $_POST['time_slot'] != "" ) {
    						// if current format is 12 hour format, then convert the times to 24 hour format to check in database
    						if ( $time_format == '12' ) {
    							$time_exploded   = explode( "-", $_POST['time_slot'] );
    							$from_time       = date( 'h:i A', strtotime( $time_exploded[0] ) );
    							
    							if( isset( $time_exploded[1] ) ) $to_time = date( 'h:i A', strtotime( $time_exploded[1] ) );
    							else $to_time = '';
    								
    							if( $to_time != '' ) $time_slot_to_display = $from_time.' - '.$to_time;
    							else $time_slot_to_display = $from_time;
    							
    						} else {
    								
    							if( $to_time != '' ) $time_slot_to_display = $from_time.' - '.$to_time;
    							else $time_slot_to_display = $from_time;
    						}
    						
                            if ( isset( $parent_id ) && $parent_id != '' && is_array( $item_quantity ) ) {
                                
                                if( $results[0]->available_booking > 0 && $results[0]->available_booking < $item_quantity[$post_id] ) {
    		
                                        $message = $post_title->post_title.bkap_get_book_t('book.limited-booking-msg1') .$results[0]->available_booking.bkap_get_book_t('book.limited-booking-msg2').$time_slot_to_display.'.';
                                        wc_add_notice( __( $message, 'woocommerce-booking' ), $notice_type = 'error' );
                                        $quantity_check_pass = 'no';
                                } elseif ( $results[0]->total_booking > 0 && $results[0]->available_booking == 0 ) {
    
                                        $message = bkap_get_book_t('book.no-booking-msg1').$post_title->post_title.bkap_get_book_t('book.no-booking-msg2').$time_slot_to_display.bkap_get_book_t('book.no-booking-msg3');
                                        wc_add_notice( __( $message, 'woocommerce-booking' ), $notice_type = 'error' );
                                        $quantity_check_pass = 'no';
                                }
                                
                            } else {
                                
                                if ( $results[0]->available_booking > 0 && $results[0]->available_booking < $item_quantity ) {
    
                                        $message = $post_title->post_title.bkap_get_book_t('book.limited-booking-msg1') .$results[0]->available_booking.bkap_get_book_t('book.limited-booking-msg2').$time_slot_to_display.'.';
                                        wc_add_notice( __( $message, 'woocommerce-booking' ), $notice_type = 'error' );
                                        $quantity_check_pass = 'no';
                                } elseif ( $results[0]->total_booking > 0 && $results[0]->available_booking == 0 ) {
    
                                        $message = bkap_get_book_t('book.no-booking-msg1').$post_title->post_title.bkap_get_book_t('book.no-booking-msg2').$time_slot_to_display.bkap_get_book_t('book.no-booking-msg3');
                                        wc_add_notice( __( $message, 'woocommerce-booking' ), $notice_type = 'error');
                                        $quantity_check_pass = 'no';
                                }
                            }
    					}
    				}
    				//check if the same product has been added to the cart for the same dates
    				if ( $quantity_check_pass == "yes" ) {
    						
    					foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
    						$booking      = $values['bkap_booking'];
    						$quantity     = $values['quantity'];
    						$product_id   = $values['product_id'];
    						
    						if ( isset( $booking ) && count( $booking ) > 0 ) {
    							
    						    if ( $product_id == $post_id && $booking[0]['hidden_date'] == $_POST['wapbk_hidden_date'] && isset( $booking[0]['time_slot'] ) && isset( $_POST['time_slot'] ) && ( $booking[0]['time_slot'] == $_POST['time_slot'] ) ) {
    								
    						        if ( isset( $parent_id ) && $parent_id != '' && is_array( $item_quantity ) ) {
    									$total_quantity = $item_quantity[ $post_id ] + $quantity;
    								}else {
    									$total_quantity = $item_quantity + $quantity;
    								}
    								
    								if ( isset( $results ) && count( $results ) > 0 ) {
    		
    									if ( $results[0]->available_booking > 0 && $results[0]->available_booking < $total_quantity ) {
    											
    										$message = $post_title->post_title.bkap_get_book_t('book.limited-booking-msg1') .$results[0]->available_booking.bkap_get_book_t('book.limited-booking-msg2').$time_slot_to_display.'.';
    										wc_add_notice( __( $message, 'woocommerce-booking' ), $notice_type = 'error' );
    										$quantity_check_pass = 'no';
    									}
    								}
    							}
    						}
    					}
    				}
			    } else {
                    $quantity_check_pass = $_POST[ 'quantity_check_pass' ];
                }
			}
		} 
		elseif ( isset( $booking_settings['booking_enable_multiple_day'] ) && $booking_settings['booking_enable_multiple_day'] == 'on' ) {
		    
		    do_action( 'bkap_multiple_days_product_validation' );
		     
		    if ( ! isset( $_POST[ 'validated' ] ) || ( isset( $_POST[ 'validated' ] ) && $_POST[ 'validated' ] == 'NO' ) ) {
				
    			$date_checkout   = date( 'd-n-Y', strtotime( $_POST['wapbk_hidden_date_checkout'] ) );
    			$date_cheeckin   = date( 'd-n-Y', strtotime( $_POST['wapbk_hidden_date'] ) );
    			$order_dates     = bkap_common::bkap_get_betweendays( $date_cheeckin, $date_checkout );
    			$todays_date     = date( 'Y-m-d' );
    	
    			$query_date      = "SELECT DATE_FORMAT(start_date,'%d-%c-%Y') as start_date,DATE_FORMAT(end_date,'%d-%c-%Y') as end_date FROM ".$wpdb->prefix."booking_history
    						       WHERE start_date >='".$todays_date."' AND post_id = '".$post_id."'";
    				
    			$results_date    = $wpdb->get_results( $query_date );
    				
    			$dates_new       = array();
    				
    			foreach ( $results_date as $k => $v ) {
    				$start_date = $v->start_date;
    				$end_date   = $v->end_date;
    				$dates      = bkap_common::bkap_get_betweendays( $start_date, $end_date );
    				$dates_new  = array_merge( $dates,$dates_new );
    			}
    			$dates_new_arr = array_count_values( $dates_new );
    				
    			$lockout = "";
    			
    			if ( isset( $booking_settings['booking_date_lockout'] ) ) {
    				$lockout = $booking_settings['booking_date_lockout'];
    			}
			
    			if ( isset( $_POST['quantity'] ) && is_array( $_POST['quantity'] ) ) {
    				$item_quantity = $_POST['quantity'][$post_id];
    			}else {
    				
    			    if ( isset( $_POST['quantity'] ) ) {
    					$item_quantity = $_POST['quantity'];
    				}else {
    					$item_quantity = 1;
    				}
    				
    			}
			
    			foreach ( $order_dates as $k => $v ) {
    				
    			    if ( array_key_exists( $v,$dates_new_arr ) ) {
    					
    				    if ( $lockout != 0 && $lockout < $dates_new_arr[ $v ] + $item_quantity ) {
    						$available_tickets    = $lockout - $dates_new_arr[ $v ];
    						$message              = $post_title->post_title.bkap_get_book_t('book.limited-booking-date-msg1')	.$available_tickets.bkap_get_book_t('book.limited-booking-date-msg2').$v.'.';
    						wc_add_notice( __( $message, 'woocommerce-booking' ), $notice_type = 'error' );
    						$quantity_check_pass  = 'no';
    					}
    				} else {
    						
    					if ( $lockout != 0 && $lockout < $item_quantity ) {
    						$available_tickets    = $lockout;
    						$message              = $post_title->post_title.bkap_get_book_t('book.limited-booking-date-msg1')	.$available_tickets.bkap_get_book_t('book.limited-booking-date-msg2').$v.'.';
    						wc_add_notice( __( $message, 'woocommerce-booking' ), $notice_type = 'error' );
    						$quantity_check_pass  = 'no';
    					}
    				}
    			}
    			//check if the same product has been added to the cart for the same dates
    			if ( $quantity_check_pass == "yes" ) {
    				
    			    foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
    					
    			        if ( isset( $values['bkap_booking'] ) ) {
    						$booking = $values['bkap_booking'];
    					}
    					
    					$quantity      = $values['quantity'];
    					$product_id    = $values['product_id'];
    	
    					if ( isset( $booking[0]['hidden_date'] ) && isset( $booking[0]['hidden_date_checkout'] ) ) {
    						$hidden_date          = date( 'd-n-Y', strtotime( $booking[0]['hidden_date'] ) );
    						$hidden_date_checkout = date( 'd-n-Y', strtotime( $booking[0]['hidden_date_checkout'] ) );
    						$dates                = bkap_common::bkap_get_betweendays( $hidden_date, $hidden_date_checkout );
    					}
    					if ( $product_id == $post_id ) {
    	
    						foreach ( $order_dates as $k => $v ) {
    							
    						    if ( array_key_exists( $v, $dates_new_arr ) ) {
    								
    						        if ( in_array( $v, $dates ) ) {
    									
    						            if ( $lockout != 0 && $lockout < $dates_new_arr[ $v ] + $item_quantity + $quantity ) {
    										$available_tickets    = $lockout - $dates_new_arr[ $v ];
    										$message              = $post_title->post_title.bkap_get_book_t('book.limited-booking-date-msg1')	.$available_tickets.bkap_get_book_t('book.limited-booking-date-msg2').$v.'.';
    										wc_add_notice( __( $message, 'woocommerce-booking' ), $notice_type = 'error' );
    										$quantity_check_pass  = 'no';
    									}
    								}else {
    									
    								    if ( $lockout != 0 && $lockout < $dates_new_arr[ $v ] + $item_quantity ) {
    										$available_tickets    = $lockout - $dates_new_arr[ $v ];
    										$message              = $post_title->post_title.bkap_get_book_t('book.limited-booking-date-msg1')	.$available_tickets.bkap_get_book_t('book.limited-booking-date-msg2').$v.'.';
    										wc_add_notice( __( $message, 'woocommerce-booking' ), $notice_type = 'error' );
    										$quantity_check_pass  = 'no';
    									}
    								}
    							}else {
    								
    							    if ( in_array( $v, $dates ) ) {
    									
    								    if ( $lockout != 0 && $lockout < $item_quantity + $quantity ) {
    										$available_tickets    = $lockout;
    										$message              = $post_title->post_title.bkap_get_book_t('book.limited-booking-date-msg1')	.$available_tickets.bkap_get_book_t('book.limited-booking-date-msg2').$v.'.';
    										wc_add_notice( __( $message, 'woocommerce-booking' ), $notice_type = 'error' );
    										$quantity_check_pass  = 'no';
    									}
    								}else {
    									
    								    if ( $lockout != 0 && $lockout < $item_quantity ) {
    										$available_tickets    = $lockout;
    										$message              = $post_title->post_title.bkap_get_book_t('book.limited-booking-date-msg1')	.$available_tickets.bkap_get_book_t('book.limited-booking-date-msg2').$v.'.';
    										wc_add_notice( __( $message, 'woocommerce-booking' ), $notice_type = 'error' );
    										$quantity_check_pass  = 'no';
    									}
    								}
    							}
    						}
    					}
    				}
    			}
		    } else {
		        $quantity_check_pass = $_POST[ 'quantity_check_pass' ];
		    }
		} 
		else {
		    
		    do_action( 'bkap_single_days_product_validation' );
		     
		    if ( ! isset( $_POST[ 'validated' ] ) || ( isset( $_POST[ 'validated' ] ) && $_POST[ 'validated' ] == 'NO' ) ) {
    			$query   =   "SELECT total_booking, available_booking, start_date FROM `".$wpdb->prefix."booking_history`
    						 WHERE post_id = %d
    						 AND start_date = %s 
    			             AND status != 'inactive' ";
    			$results =  $wpdb->get_results( $wpdb->prepare( $query, $post_id, $date_check ) );
    	
    			if ( isset( $_POST['quantity'] ) ) {
    				$item_quantity = $_POST['quantity'];
    			}else {
    				$item_quantity = 1;
    			}
    			
    			if ( isset( $results ) && count( $results ) > 0 ) {
                            
                    //Validation for parent products page - Grouped Products  , Here $item_array come Array when order place from the Parent page 
                    if ( isset( $parent_id ) && $parent_id != '' && is_array( $item_quantity ) ) {
                        
                        if( $results[0]->available_booking > 0 && $results[0]->available_booking < $item_quantity[$post_id] ) {
                            
        					$message               = $post_title->post_title.bkap_get_book_t('book.limited-booking-date-msg1')	.$results[0]->available_booking.bkap_get_book_t('book.limited-booking-date-msg2').$results[0]->start_date.'.';
        					wc_add_notice( __( $message, 'woocommerce-booking' ), $notice_type = 'error' );
        					$quantity_check_pass   = 'no';
        					
        				}elseif ( $results[0]->total_booking > 0 && $results[0]->available_booking == 0 ) {
        					
        				    $message               = bkap_get_book_t('book.no-booking-date-msg1').$post_title->post_title.bkap_get_book_t('book.no-booking-date-msg2').$results[0]->start_date.bkap_get_book_t('book.no-booking-date-msg3');
        					wc_add_notice( __( $message, 'woocommerce-booking' ), $notice_type = 'error' );
        					$quantity_check_pass   = 'no';
        				}
        				
                    } else {
        				
                        if( $results[0]->available_booking > 0 && $results[0]->available_booking < $item_quantity ) {
        					
                            $message                = $post_title->post_title.bkap_get_book_t('book.limited-booking-date-msg1')	.$results[0]->available_booking.bkap_get_book_t('book.limited-booking-date-msg2').$results[0]->start_date.'.';
        					wc_add_notice( __( $message, 'woocommerce-booking' ), $notice_type = 'error' );
        					$quantity_check_pass    = 'no';
        					
        				}elseif ( $results[0]->total_booking > 0 && $results[0]->available_booking == 0 ) {
        					
        				    $message                = bkap_get_book_t('book.no-booking-date-msg1').$post_title->post_title.bkap_get_book_t('book.no-booking-date-msg2').$results[0]->start_date.bkap_get_book_t('book.no-booking-date-msg3');
        					wc_add_notice( __( $message, 'woocommerce-booking' ), $notice_type = 'error' );
        					$quantity_check_pass    = 'no';
        				}
                    }
                }
    			if ( $quantity_check_pass == "yes" ) {
    				
    			    foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
    					
    			        if( array_key_exists( 'bkap_booking', $values ) ) {
    						$booking = $values['bkap_booking'];
    					}else {
    						$booking = array();
    					}
    					
    					$quantity = $values['quantity'];
    					$product_id = $values['product_id'];
    					
    					if ( $product_id == $post_id && $booking[0]['hidden_date'] == $_POST['wapbk_hidden_date'] ) {
    						
    					    if ( isset( $parent_id ) && $parent_id != '' && is_array( $item_quantity ) ) {
                                $total_quantity = $item_quantity[ $post_id ] + $quantity;
                            } else {
                                $total_quantity = $item_quantity + $quantity;
                            }
                            
    						if ( isset( $results ) && count( $results ) > 0 ) {
                                 
    						    if ( isset($parent_id) && $parent_id != '' && is_array( $item_quantity ) ) {
                                    if( $results[0]->available_booking > 0 && $results[0]->available_booking < $total_quantity ) {
                                            $message                = $post_title->post_title.bkap_get_book_t('book.limited-booking-date-msg1')	.$results[0]->available_booking.bkap_get_book_t('book.limited-booking-date-msg2').$results[0]->start_date.'.';
                                            wc_add_notice( __( $message, 'woocommerce-booking' ), $notice_type = 'error');
                                            $quantity_check_pass    = 'no';
                                    }
                                 } else {
            							if( $results[0]->available_booking > 0 && $results[0]->available_booking < $total_quantity ) {
            								$message                = $post_title->post_title.bkap_get_book_t('book.limited-booking-date-msg1')	.$results[0]->available_booking.bkap_get_book_t('book.limited-booking-date-msg2').$results[0]->start_date.'.';
            								wc_add_notice( __( $message, 'woocommerce-booking' ), $notice_type = 'error');
            								$quantity_check_pass    = 'no';
            							}
                                 }
                            }
    					}
    				}
    			}
		    } else {
		        $quantity_check_pass = $_POST[ 'quantity_check_pass' ];
		    }
		}
		return $quantity_check_pass;
	}
		
	/**
	 * This function checks availability for date and 
	 * time slot on the cart page when quantity on 
	 * cart page is changed.
	 */
	
	public static function bkap_quantity_check(){
		global $woocommerce, $wpdb;
		// Get the order ID if an order is already pending
		$order_id = absint( WC()->session->order_awaiting_payment );
		
		// An order was already created, this means the validation has been run once already.
		if ( $order_id > 0 && ( $order = wc_get_order( $order_id ) ) && $order->has_status( array( 'pending', 'failed' ) ) ) {
			// Confirm if data is found in the order history table for the given order, then we need to skip the validation
			$check_data      =   "SELECT * FROM `".$wpdb->prefix."booking_order_history`
							     WHERE order_id = %s";
			$results_check   =   $wpdb->get_results ( $wpdb->prepare ( $check_data, $order_id ) );
			
			if ( count ( $results_check ) > 0 ) {
				return;
			}
			
		}	
		
		foreach ( $woocommerce->cart->cart_contents as $key => $value ) {
			$duplicate_of        = bkap_common::bkap_get_product_id( $value['product_id'] );
	
			$booking_settings    = get_post_meta( $duplicate_of , 'woocommerce_booking_settings' , true );
			$post_title          = get_post( $value['product_id'] );
			$date_check          = '';
			
			if ( isset( $value['bkap_booking'][0]['hidden_date'] ) ){
				$date_check = date( 'Y-m-d', strtotime( $value['bkap_booking'][0]['hidden_date'] ) );
			} else{
				$date_check = '';
			}
				
			$saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
			
			if ( isset( $saved_settings ) ){
				$time_format = $saved_settings->booking_time_format;
			} else {
				$time_format = "12";
			}
			
			if( isset( $value['variation_id'] ) )
			{
				$variation_id = $value['variation_id'];
			}else {
				$variation_id = '';
			}
			
			// save the data in $_POST, so it can be accessed in hooks and does not need to be passed each time
			$_POST[ 'product_id' ] = $duplicate_of;
			$_POST[ 'variation_id' ] = $variation_id;
			$_POST[ 'booking_date' ] = $date_check;
			$_POST[ 'quantity' ] = $value[ 'quantity'];
			$_POST[ 'cart_item_key' ] = $key;
			
			if( isset( $booking_settings['booking_enable_time'] ) && $booking_settings['booking_enable_time'] == 'on' ) {
	
				$type_of_slot = apply_filters( 'bkap_slot_type', $duplicate_of );
				
				if( $type_of_slot == 'multiple' ) { 
					do_action( 'bkap_validate_cart_items', $value );
				} else {
					
				    $_POST[ 'time_slot' ] = $value['bkap_booking'][0]['time_slot'];
				    do_action( 'bkap_date_time_cart_validation' );
				    
				    if ( isset( $_POST[ 'validation_status' ] ) ) {
				        $validation_completed = $_POST[ 'validation_status' ];
				    }
				    
				    if ( ! isset( $validation_completed ) || ( isset( $validation_completed ) && $validation_completed == 'NO' ) ) {
				    
    				    if ( isset( $value['bkap_booking'][0]['time_slot'] ) ) {
    						$time_range = explode( "-", $value['bkap_booking'][0]['time_slot'] );
    						$from_time = date( 'G:i', strtotime( $time_range[0] ) );
    						
    						if( isset( $time_range[1] ) ){
    							$to_time = date( 'G:i', strtotime( $time_range[1] ) );
    						} else{
    							$to_time = '';
    						}
    						
    					}else {
    						$to_time      = '';
    						$from_time    = '';
    					}
						
    					if( $to_time != '' ) {
    						$query    = "SELECT total_booking, available_booking, start_date FROM `".$wpdb->prefix."booking_history`
                						WHERE post_id = %d
                						AND start_date = %s
                						AND from_time = %s
                						AND to_time = %s
    							AND status !=  'inactive' ";
    						$results =  $wpdb->get_results( $wpdb->prepare( $query, $duplicate_of, $date_check, $from_time, $to_time ) );
    					}else {
    						$query    = "SELECT total_booking, available_booking, start_date FROM `".$wpdb->prefix."booking_history`
                						WHERE post_id = %d
                						AND start_date = %s
                						AND from_time = %s
    							AND status !=  'inactive' ";
    						$results = $wpdb->get_results( $wpdb->prepare( $query, $duplicate_of, $date_check, $from_time ) );
    					}
					 
    					if ( !$results ) break;
    					else{
    						if ( $value['bkap_booking'][0]['time_slot'] != "" ) {
    							// if current format is 12 hour format, then convert the times to 24 hour format to check in database
    							if ( $time_format == '12' ) {
    								
    							    $time_exploded  = explode( "-", $value['bkap_booking'][0]['time_slot'] );
    								$from_time      = date( 'h:i A', strtotime( $time_exploded[0] ) );
    								
    								if ( isset( $time_range[1] ) ){
    									$to_time = date( 'h:i A', strtotime( $time_exploded[1] ) );
    								} else{
    									$to_time = '';
    								}
    								
    								if ( $to_time != '' ) {
    									$time_slot_to_display = $from_time.' - '.$to_time;
    								}else {
    									$time_slot_to_display = $from_time;
    								}
    								
    							} else {
    								if ( $to_time != '' ) {
    									$time_slot_to_display = $from_time.' - '.$to_time;
    								} else {
    									$time_slot_to_display = $from_time;
    								}
    								
    							}
    							
    							if( $results[0]->available_booking > 0 && $results[0]->available_booking < $value['quantity'] ) {
    								$message = $post_title->post_title.bkap_get_book_t('book.limited-booking-msg1') .$results[0]->available_booking.bkap_get_book_t('book.limited-booking-msg2').$time_slot_to_display.'.';
    								wc_add_notice( __( $message, 'woocommerce-booking' ), $notice_type = 'error' );
    							} elseif ( $results[0]->total_booking > 0 && $results[0]->available_booking == 0 ) {
    								$message = bkap_get_book_t('book.no-booking-msg1').$post_title->post_title.bkap_get_book_t('book.no-booking-msg2').$time_slot_to_display.bkap_get_book_t('book.no-booking-msg3');
    								wc_add_notice( __( $message, 'woocommerce-booking' ), $notice_type = 'error' );
    							}
    						}
    					}
				    }
				}
			} else if ( isset( $booking_settings['booking_enable_multiple_day'] ) && $booking_settings['booking_enable_multiple_day'] == 'on' ) {
			
			//	print_r($value);
				$date_checkout = $date_cheeckin = '';
				
				if ( isset( $value['bkap_booking'][0]['hidden_date_checkout'] ) ) {
					$date_checkout = date( 'd-n-Y', strtotime( $value['bkap_booking'][0]['hidden_date_checkout'] ) );
				}
				
				if ( isset( $value['bkap_booking'][0]['hidden_date'] ) ) {
					$date_cheeckin = date( 'd-n-Y', strtotime( $value['bkap_booking'][0]['hidden_date'] ) );
				}
				
				$_POST[ 'booking_date' ] = $date_cheeckin;
				$_POST[ 'booking_checkout' ] = $date_checkout;
				do_action( 'bkap_multiple_days_cart_validation' );
				
				if ( isset( $_POST[ 'validation_status' ] ) ) {
				    $validation_completed = $_POST[ 'validation_status' ];
				}
				
				if ( ! isset( $validation_completed ) || ( isset( $validation_completed ) && $validation_completed == 'NO' ) ) {
    				$order_dates = bkap_common::bkap_get_betweendays( $date_cheeckin, $date_checkout );
    				$todays_date = date( 'Y-m-d' );
    	
    				$query_date  = "SELECT DATE_FORMAT(start_date,'%d-%c-%Y') as start_date,DATE_FORMAT(end_date,'%d-%c-%Y') as end_date FROM ".$wpdb->prefix."booking_history
    						       WHERE start_date >='".$todays_date."' AND post_id = '".$duplicate_of."'";
    				
    				$results_date = $wpdb->get_results( $query_date );
    				
    				$dates_new    = array();
    					
    				foreach ( $results_date as $k => $v ) {
    					$start_date    = $v->start_date;
    					$end_date      = $v->end_date;
    					$dates         = bkap_common::bkap_get_betweendays( $start_date, $end_date );
    				
    					$dates_new     = array_merge( $dates, $dates_new );
    				}
    				$dates_new_arr     = array_count_values( $dates_new );
    					
    				$lockout           = "";
    				
    				if ( isset( $booking_settings['booking_date_lockout'] ) ) {
    					$lockout = $booking_settings['booking_date_lockout'];
    				}
	
    				foreach ( $order_dates as $k => $v ){
    					
    				    if ( array_key_exists( $v, $dates_new_arr ) ) {
    						
    				        if ( $lockout != 0 && $lockout < $dates_new_arr[$v] + $value['quantity'] ){
    							$available_tickets   = $lockout - $dates_new_arr[ $v ];
    							$message             = $post_title->post_title.bkap_get_book_t('book.limited-booking-date-msg1')	.$available_tickets.bkap_get_book_t('book.limited-booking-date-msg2').$v.'.';
    							wc_add_notice( __( $message, 'woocommerce-booking' ), $notice_type = 'error' );
    						}
    					}else{
    						if ( $lockout != 0 && $lockout < $value['quantity'] ) {
    							$available_tickets   = $lockout;
    							$message             = $post_title->post_title.bkap_get_book_t('book.limited-booking-date-msg1')	.$available_tickets.bkap_get_book_t('book.limited-booking-date-msg2').$v.'.';
    							wc_add_notice( __( $message, 'woocommerce-booking' ), $notice_type = 'error' );
    						}
    					}
    				}
				}
			}else {
			    do_action( 'bkap_single_days_cart_validation' );
			     
			    if ( isset( $_POST[ 'validation_status' ] ) ) {
			        $validation_completed = $_POST[ 'validation_status' ];
			    }
			    if ( ! isset( $validation_completed ) || ( isset( $validation_completed ) && $validation_completed == 'NO' ) ) {
    				$query  =   "SELECT total_booking,available_booking, start_date FROM `".$wpdb->prefix."booking_history`
                				WHERE post_id = %d
                				AND start_date = %s
                				AND status != 'inactive' ";
    				$results =  $wpdb->get_results( $wpdb->prepare( $query, $duplicate_of, $date_check ) );
    	
    				if( !$results ) break;
    				else {
    					if( $results[0]->available_booking > 0 && $results[0]->available_booking < $value['quantity'] ) {
    						$message = $post_title->post_title.bkap_get_book_t('book.limited-booking-date-msg1')	.$results[0]->available_booking.bkap_get_book_t('book.limited-booking-date-msg2').$results[0]->start_date.'.';
    						wc_add_notice( __( $message, 'woocommerce-booking' ), $notice_type = 'error' );
    	
    					} elseif ( $results[0]->total_booking > 0 && $results[0]->available_booking == 0 ) {
    						$message = bkap_get_book_t('book.no-booking-date-msg1').$post_title->post_title.bkap_get_book_t('book.no-booking-date-msg2').$results[0]->start_date.bkap_get_book_t('book.no-booking-date-msg3');
    						wc_add_notice( __( $message, 'woocommerce-booking' ), $notice_type = 'error' );
    	
    					}
    				}
			    }
			}
		}
	}
				
}

?>