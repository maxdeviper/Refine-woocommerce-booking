<?php
//if(!class_exists('woocommerce_booking')){
//   die();
//}

include_once( 'bkap-common.php' );

class bkap_cart{
	
	/**********************************************************
	 * This function adjust the extra prices for the product 
	 * with the price calculated from booking plugin.
	*********************************************************/
	public static function bkap_add_cart_item( $cart_item ) {
	
		// Adjust price if addons are set
		global $wpdb;
		
		if ( isset( $cart_item['bkap_booking'] ) ) :
			
			$extra_cost = 0;
				
			foreach ( $cart_item['bkap_booking'] as $addon ) :
		
				if ( isset( $addon['price'] ) && $addon['price']>0 ) $extra_cost += $addon['price'];
	
			endforeach;
			
			$duplicate_of    =   bkap_common::bkap_get_product_id( $cart_item['product_id'] );
			$product         =   get_product( $cart_item['product_id'] );
		
			$product_type    =   $product->product_type;
				
			$variation_id    =   0;
			
			if ( $product_type == 'variable' ) {
				$variation_id = $cart_item['variation_id'];
			}
			
			$price       =   bkap_common::bkap_get_price( $cart_item['product_id'], $variation_id, $product_type );
			$extra_cost  =   $extra_cost - $price;
			$cart_item['data']->adjust_price( $extra_cost );
				
		endif;
		
		return $cart_item;
	}
		
	/*************************************************
	 * This function returns the cart_item_meta with 
	 * the booking details of the product when add to 
	 * cart button is clicked.
	*****************************************************/
	public static function bkap_add_cart_item_data( $cart_item_meta, $product_id ){
		global $wpdb;
		
		$duplicate_of = bkap_common::bkap_get_product_id( $product_id );
	
		if ( isset( $_POST['booking_calender'] ) ) {
			$date_disp = $_POST['booking_calender'];
		}
		
		if ( isset($_POST['time_slot'] ) ) {
			$time_disp = $_POST['time_slot'];
		}
		
		if ( isset( $_POST['wapbk_hidden_date'] ) ) {
			$hidden_date = $_POST['wapbk_hidden_date'];
		}
	
		$booking_settings     = get_post_meta( $duplicate_of, 'woocommerce_booking_settings', true );			
		$product              = get_product( $product_id );	
		$product_type         = $product->product_type;
		
		// Initialize the variables
		$date_disp_checkout = '';
		$hidden_date_checkout = '';
		$diff_days = 1;
		if ( isset( $booking_settings['booking_enable_multiple_day'] ) && $booking_settings['booking_enable_multiple_day'] == 'on' ) {
			
		    if ( isset( $_POST['booking_calender_checkout'] ) ) {
				$date_disp_checkout = $_POST['booking_calender_checkout'];
			}
			
			if ( isset( $_POST['wapbk_hidden_date_checkout'] ) ) {
				$hidden_date_checkout = $_POST['wapbk_hidden_date_checkout'];
			}
			
			if ( isset( $_POST['wapbk_diff_days'] ) ) {
				$diff_days = $_POST['wapbk_diff_days'];
			}
			
			$variation_id = 0;
			
			if ( $product_type == 'variable' ) {
				$variation_id = $_POST['variation_id'];
			}
			
		} 
	
		$global_settings  =   json_decode( get_option( 'woocommerce_booking_global_settings' ) );
		
		if ( isset( $date_disp ) ) {
			$cart_arr = array();
			
			if ( isset( $date_disp ) ) {
				$cart_arr['date'] = $date_disp;
			}
			
			if ( isset( $time_disp ) ) {
				$cart_arr['time_slot'] = $time_disp;
			}
			
			if ( isset($hidden_date ) ) {
				$cart_arr['hidden_date'] = $hidden_date;
			}
			
			$cart_arr['date_checkout'] = $date_disp_checkout;
			$cart_arr['hidden_date_checkout'] = $hidden_date_checkout;
			
			if ( isset( $_POST['variation_id'] ) ) {
				$variation_id = $_POST['variation_id'];
			}
			else {
				$variation_id = '0';
			}
            
			$price = 0;
			if ( isset( $_POST[ 'bkap_price_charged' ] ) && is_numeric( $_POST[ 'bkap_price_charged' ] ) ) {
                $price = $_POST[ 'bkap_price_charged' ];
            } else { // It's a string when the product is a grouped product
                	
                $price_array = explode( ',', $_POST[ 'bkap_price_charged' ] );
                foreach ($price_array as $array_k => $array_v ) {
                    $per_product_array = explode( ':', $array_v );
            
                    if ( $per_product_array[ 0 ] == $product_id ) {
                        $price = $per_product_array[ 1 ];
                        $child_product_id = $per_product_array[ 0 ];
                        break;
                    }
                }
            }
			$gf_options_price = 0;
			$wpa_options_price = 0;
				
			// GF Compatibility
			if ( isset( $_POST[ 'bkap_gf_options_total' ] ) && $_POST[ 'bkap_gf_options_total' ] != 0 ) {
			    $gf_options_price = $_POST[ 'bkap_gf_options_total' ];
			}
				
			//Woo Product Addons compatibility
			if( isset( $global_settings->woo_product_addon_price ) && $global_settings->woo_product_addon_price == 'on' ) {
			    $wpa_options_price = bkap_common::woo_product_addons_compatibility_cart( $diff_days, $cart_item_meta );
			}
				
			$final_price = $price + $gf_options_price + $wpa_options_price;
			
			// Set the price per quantity as Woocommerce multiplies the price set with the qty
            if ( isset( $_POST[ 'quantity' ] ) && is_array( $_POST[ 'quantity' ] ) ) {
			    foreach ( $_POST[ 'quantity' ] as $qty_key => $qty_value ) {
			        if ( $qty_key == $product_id ) {
			            $final_price = $final_price / $qty_value;
			        }
			    }
			} else {
			    if ( isset( $_POST[ 'quantity' ] ) && $_POST[ 'quantity' ] > 1 ) {
                    $final_price = $final_price / $_POST[ 'quantity' ];
			    }
			}
			$cart_arr[ 'price' ] = $final_price;
			$cart_arr                    =   (array) apply_filters('bkap_addon_add_cart_item_data', $cart_arr, $product_id, $variation_id,$cart_item_meta );
			
			$cart_item_meta['bkap_booking'][] =   $cart_arr;
		}
		return $cart_item_meta;
	}

	/**********************************************
	 *  This function adjust the prices calculated 
	 *  from the plugin in the cart session.
	************************************************/
	public static function bkap_get_cart_item_from_session( $cart_item, $values ) {
		global $wpdb;
		
		$duplicate_of = bkap_common::bkap_get_product_id($cart_item['product_id']);
		
		if ( isset( $values['bkap_booking'] ) ) :
			
		    $cart_item['bkap_booking'] =  $values['bkap_booking'];
		
			$booking_settings     =  get_post_meta( $duplicate_of, 'woocommerce_booking_settings', true );
	
			$cart_item = bkap_cart::bkap_add_cart_item( $cart_item );
			
			$cart_item = (array) apply_filters( 'bkap_get_cart_item_from_session', $cart_item, $values );
		
		endif;
		
		return $cart_item;
	}
	
	/**************************************
     * This function displays the Booking 
     * details on cart page, checkout page.
    ************************************/
	public static function bkap_get_item_data_booking( $other_data, $cart_item ) {
		global $wpdb;
			
		if ( isset( $cart_item['bkap_booking'] ) ) :
			$duplicate_of = bkap_common::bkap_get_product_id( $cart_item['product_id'] );
			
			foreach ( $cart_item['bkap_booking'] as $booking ) :
			
				$name = __(get_option( 'book.item-cart-date' ), "woocommerce-booking" );
			
				if ( isset( $booking['date'] ) && $booking['date'] != "" ) {
					$other_data[] = array( 'name'    => $name,
							               'display' => $booking['date']
					);
				}
				
				if ( isset( $booking['date_checkout'] ) && $booking['date_checkout'] != "" ) {
					$booking_settings = get_post_meta( $duplicate_of, 'woocommerce_booking_settings', true );

					if ( $booking_settings['booking_enable_multiple_day'] == 'on' ) {
					    $name_checkout    =   __( get_option( 'checkout.item-cart-date' ), "woocommerce-booking" );
						$other_data[]     =   array('name'    => $name_checkout,
								                    'display' => $booking['date_checkout']
						);						
					}
				}
				
				if ( isset( $booking['time_slot'] ) && $booking['time_slot'] != "" ) {
					$saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
					
					if ( isset( $saved_settings ) ){
						$time_format = $saved_settings->booking_time_format;
					}else {
						$time_format = "12";
					}
					
					$time_slot_to_display = $booking['time_slot'];
				
					if ( $time_format == '12' ) {
						$time_exploded    =   explode( "-", $time_slot_to_display );
						$from_time        =   date( 'h:i A', strtotime( $time_exploded[0] ) );
						
						if ( isset( $time_exploded[1] ) ) { 
							$to_time = date( 'h:i A', strtotime( $time_exploded[1] ) );
						}
						else {
							$to_time = "";
						}
						
						if ( $to_time != "" ) {
							$time_slot_to_display = $from_time.' - '.$to_time;
						}
						else {
							$time_slot_to_display = $from_time;
						}
					}
					
					$type_of_slot = apply_filters( 'bkap_slot_type', $cart_item['product_id'] );
					
					if( $type_of_slot != 'multiple' ) {
						$name         =   __( get_option( 'book.item-cart-time' ), "woocommerce-booking" );
						$other_data[] =   array('name'    => $name,
							                    'display' => $time_slot_to_display
						);
					}
				}
				$other_data = apply_filters( 'bkap_get_item_data', $other_data, $cart_item );
			endforeach; 
			
		endif;
		
		return $other_data;
	}
			
/******************************************************
 * This function modified the product price in the 
 * Woocommerce cart widget
 *****************************************************/
	public static function bkap_woo_cart_widget_subtotal( $fragments ) {
		
		global $woocommerce;
		
		$price = 0;
		foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
			
		    if ( isset( $values['bkap_booking'] ) ) {
				$booking = $values['bkap_booking'];
			}
			
			if ( isset( $booking[0]['price'] ) && $booking[0]['price'] != 0 ) {
				$price += ( $booking[0]['price'] ) * $values['quantity'];
			} else {
				
			    if ( $values['variation_id'] == '' ) {
					$product_type = $values['data']->product_type;
				} else {
					$product_type = $values['data']->parent->product_type;
				}
		
				$variation_id = 0;
				
				if ( $product_type == 'variable' ) {
					$variation_id = $values['variation_id'];
				}
				
				$book_price = bkap_common::bkap_get_price( $values['product_id'], $variation_id, $product_type );
		
				$price += $book_price * $values['quantity'];
			}
			
            if ( class_exists( 'woocommerce_gravityforms' ) ) {
				
                foreach ($values['_gravity_form_lead'] as $k => $v ) {
			        if ( strpos( $v, '|' ) > 0 ) {
			            $string_explode = explode( '|', $v );
			            $price_for_selected_quanity = $string_explode[1] * $values['quantity'];
			            $price += $price_for_selected_quanity;
			        }
			    }
			}
			
			if ( class_exists( 'WC_Product_Addons' ) ) {
				
				// If PD addon is enabled, then pls dont add the addon amounts as it gives incorrect subtotals
				if ( function_exists( 'is_bkap_deposits_active' ) && is_bkap_deposits_active() ) {
						
				} else if ( isset( $values['addons'] ) && count( $values['addons'] > 0 ) ) {
					
				    foreach ( $values['addons'] as $k => $v ) {
						$price += $v['price'];
					}
				}
			}
		}
			
		$saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
		
		if ( isset( $saved_settings->enable_rounding ) && $saved_settings->enable_rounding == "on" ) {
			$total_price = round( $price );
		} else {
			$total_price = number_format( $price, 2 );
		}
		
		ob_start();
		$currency_symbol = get_woocommerce_currency_symbol();
		print( '<p class="total"><strong>Subtotal:</strong> <span class="amount">'.$currency_symbol.$total_price.'</span></p>' );
			
		$fragments['p.total'] = ob_get_clean();
			
		return $fragments;
	}
							
} 
?>