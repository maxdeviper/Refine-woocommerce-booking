<?php
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

class bkap_common{
    
    /**
     * Return function name to be executed when multiple time slots are enabled.
     * 
     * This function returns the function name to display the timeslots on the 
     * frontend if type of timeslot is Multiple for multiple time slots addon.
     * 
     * @return str
     */
	public static function bkap_ajax_on_select_date() {
		global $post;
		
		$booking_settings = get_post_meta( $post->ID, 'woocommerce_booking_settings', true );
		
		if( isset( $booking_settings['booking_enable_multiple_time'] ) && $booking_settings['booking_enable_multiple_time'] == "multiple" && function_exists('is_bkap_multi_time_active') && is_bkap_multi_time_active() ) {
			return 'multiple_time';
		}
	}

	/**
	 * Return an array of dates that fall in a date range
	 * 
	 * This function returns an array of dates that falls
	 * in a date range in the d-n-Y format.
	 * 
	 * @param $StartDate d-n-Y format
	 * $EndDate d-n-Y format
	 * 
	 * @return $Days array
	 */
	public static function bkap_get_betweendays( $StartDate, $EndDate ) {
		$Days[]                   =   $StartDate;
		$CurrentDate              =   $StartDate;
			
		$CurrentDate_timestamp    =   strtotime($CurrentDate);
		$EndDate_timestamp        =   strtotime($EndDate);
		
		if( $CurrentDate_timestamp != $EndDate_timestamp )
		{
			while( $CurrentDate_timestamp < $EndDate_timestamp )
			{
				$CurrentDate            =   date( "d-n-Y", strtotime( "+1 day", strtotime( $CurrentDate ) ) );
				$CurrentDate_timestamp  =   $CurrentDate_timestamp + 86400;
				$Days[]                 =   $CurrentDate;
			}
			array_pop( $Days );
		}
		return $Days;
	}
	
	/**
	 * Send the Base language product ID
	 * 
	 * This function has been written as a part of making the Booking plugin
	 * compatible with WPML. It returns the base language Product ID when WPML
	 * is enabled. 
	 * 
	 * @param $product_id int
	 * @return $base_product_id int
	 */
	public static function bkap_get_product_id( $product_id ) {
	    $base_product_id = $product_id;
	    // If WPML is enabled, the make sure that the base language product ID is used to calculate the availability
	    if ( function_exists( 'icl_object_id' ) ) {
	        global $sitepress;
	        $default_lang = $sitepress->get_default_language();
	        $base_product_id = icl_object_id( $product_id, 'product', false, $default_lang );
	        // The base product ID is blanks when the product is being created.
	        if (! isset( $base_product_id ) || ( isset( $base_product_id ) && $base_product_id == '' ) ) {
	            $base_product_id = $product_id;
	        }
	    } 
		return $base_product_id;
	}
	
	/**
	 * Return Woocommerce price
	 * 
	 * This function returns the Woocommerce price applicable for a product.
	 * 
	 * @param $product_id int
	 * $variation_id int
	 * $product_type str
	 * 
	 * @return $price
	 */
	public static function bkap_get_price( $product_id, $variation_id, $product_type ) {
		$price = 0;
		
		if ( $product_type == 'variable' ){
			$sale_price = get_post_meta( $variation_id, '_sale_price', true );
			
			if( !isset( $sale_price ) || $sale_price == '' || $sale_price == 0 ) {
				$regular_price  =   get_post_meta( $variation_id, '_regular_price', true );
				$price          =   $regular_price;
			}else {
				$price          =   $sale_price;
			}
			
		} elseif( $product_type == 'simple' ) {
			$sale_price = get_post_meta( $product_id, '_sale_price', true );
			
			if( !isset( $sale_price ) || $sale_price == '' || $sale_price == 0 ) {
				$regular_price  =   get_post_meta( $product_id, '_regular_price', true );
				$price          =   $regular_price;
			}else {
				$price          =   $sale_price;
			}
		}
		return $price;
	}
	
	/**
	 * Return product type
	 * 
	 * Returns the Product type based on the ID received
	 * 
	 * @params $product_id int
	 * @return $product_type str
	 */
	public static function bkap_get_product_type($product_id) {
		$product      =   get_product( $product_id );
		$product_type =   $product->product_type;
		
		return $product_type;
	}
	
	/**
	 * Returns the WooCommerce Product Addons Options total
	 * 
	 * This function returns the WooCommerce Product Addons
	 * options total selected by a user for a given product.
	 * 
	 * @param $diff_days int
	 * $cart_item_meta array
	 * 
	 * @return $addons_price int
	 */
	public static function woo_product_addons_compatibility_cart( $diff_days, $cart_item_meta ) {
	    $addons_price = 0;
	    if( class_exists('WC_Product_Addons') ) {
			$single_addon_price = 0;
		 	
		 	if( isset( $cart_item_meta['addons'] ) ) {
				$product_addons = $cart_item_meta['addons'];
				
				foreach( $product_addons as $key => $val ) {
					$single_addon_price += $val['price'];
				}
				
				if( isset( $diff_days ) && $diff_days > 1 && $single_addon_price > 0 ) {
					$diff_days         -=  1;
					$single_addon_price =  $single_addon_price * $diff_days;
					$addons_price      +=  $single_addon_price;
				}
					
			}
		}
		return $addons_price;
	}
	
	/**
	 * Checks if the product requires booking confirmation from admin
	 *
	 * If the Product is a bookable product and requires confirmation,
	 * returns true else returns false
	 *
	 * @param int $product_id
	 * @return boolean
	 */
	public static function bkap_product_requires_confirmation( $product_id ) {
	    $product = get_product( $product_id );
	     
	    // Booking Settings
	    $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
	     
	    if (
	        is_object( $product )
	        && isset( $booking_settings[ 'booking_enable_date' ] ) && 'on' == $booking_settings[ 'booking_enable_date' ]
	        && isset( $booking_settings[ 'booking_confirmation' ] ) && 'on' == $booking_settings[ 'booking_confirmation' ]
	    ) {
	        return true;
	    }
	     
	    return false;
	}
	
	/**
	 * Checks if Cart contains bookable products that require confirmation
	 *
	 * Returns true if Cart contains any bookable products that require
	 * confirmation, else returns false.
	 *
	 * @return boolean
	 */
	public static function bkap_cart_requires_confirmation() {
	     
	    $requires = false;
	     
	    foreach ( WC()->cart->cart_contents as $item ) {
	         
	        $requires_confirmation = bkap_common::bkap_product_requires_confirmation( $item['product_id'] );
	         
	        if ( $requires_confirmation ) {
	            $requires = true;
	            break;
	        }
	    }
	     
	    return $requires;
	
	}
	
	/**
	 *
	 * @param unknown $order
	 * @return boolean
	 */
	public static function bkap_order_requires_confirmation( $order ) {
	    $requires = false;
	
	    if ( $order ) {
	        foreach ( $order->get_items() as $item ) {
	            if ( bkap_common::bkap_product_requires_confirmation( $item['product_id'] ) ) {
	                $requires = true;
	                break;
	            }
	        }
	    }
	
	    return $requires;
	}
	
	/**
	 *
	 * @param unknown $item_id
	 * @return stdClass
	 */
	public static function get_bkap_booking( $item_id ) {
	     
	    global $wpdb;
	     
	    $booking_object = new stdClass();
	     
	    $start_date_label = get_option( 'book.item-meta-date' );
	    $end_date_label = get_option( 'checkout.item-meta-date' );
	    $time_label = get_option( 'book.item-meta-time' );
	
	    // order ID
	    $query_order_id = "SELECT order_id FROM `". $wpdb->prefix."woocommerce_order_items`
                            WHERE order_item_id = %d";
	    $get_order_id = $wpdb->get_results( $wpdb->prepare( $query_order_id, $item_id ) );
	     
	    $order_id = 0;
	    if ( isset( $get_order_id ) && is_array( $get_order_id ) && count( $get_order_id ) > 0 ) {
	        $order_id = $get_order_id[0]->order_id;
	    }
	    $booking_object->order_id = $order_id;
	     
	    $order = new WC_order( $order_id );
	     
	    // order date
	    $post_data = get_post( $order_id );
	    $booking_object->order_date = $post_data->post_date;
	     
	    // product ID
	    $booking_object->product_id = wc_get_order_item_meta( $item_id, '_product_id' );
	     
	    // product name
	    $_product = get_product( $booking_object->product_id );
	    $booking_object->product_title = $_product->get_title();
	     
	    // get the booking status
	    $booking_object->item_booking_status = wc_get_order_item_meta( $item_id, '_wapbk_booking_status' );
	     
	    // get the hidden booking date and time
	    $booking_object->item_hidden_date = wc_get_order_item_meta( $item_id, '_wapbk_booking_date' );
	    $booking_object->item_hidden_checkout_date = wc_get_order_item_meta( $item_id, '_wapbk_checkout_date' );
	    $booking_object->item_hidden_time = wc_get_order_item_meta( $item_id, '_wapbk_time_slot' );
	
	    // get the booking date and time to be displayed
	    $booking_object->item_booking_date = wc_get_order_item_meta( $item_id, $start_date_label );
	    $booking_object->item_checkout_date = wc_get_order_item_meta( $item_id, $end_date_label );
	    $booking_object->item_booking_time = wc_get_order_item_meta( $item_id, $time_label );
	     
	    // email adress
	    $booking_object->billing_email = $order->billing_email;
	     
	    // customer ID
	    $booking_object->customer_id = $order->user_id;
	
	    return $booking_object;
	
	}
	
	/**
	 * Returns the number of time slots present for a date.
	 * The date needs to be passed in the j-n-Y format
	 * 
	 * @param int $product_id
	 * @param str $date_check_in
	 * @return number
	 */
	public static function bkap_get_number_of_slots( $product_id, $date_check_in ) {
	     
	    // Booking settings
	    $booking_settings =   get_post_meta( $product_id , 'woocommerce_booking_settings' , true );
	    	
	    $number_of_slots = 0;
	    // find the number of slots present for this date/day
	    if ( is_array( $booking_settings[ 'booking_time_settings' ] ) && count( $booking_settings[ 'booking_time_settings' ] ) > 0 ) {
	        if ( array_key_exists( $date_check_in, $booking_settings[ 'booking_time_settings' ] ) ) {
	            $number_of_slots = count( $booking_settings[ 'booking_time_settings' ][ $date_check_in ] );
	        } else { // it's a recurring weekday
	            $weekday            =   date( 'w', strtotime( $date_check_in ) );
	            $booking_weekday    =   'booking_weekday_' . $weekday;
	            if( array_key_exists( $booking_weekday, $booking_settings[ 'booking_time_settings' ] ) ) {
	                $number_of_slots = count( $booking_settings[ 'booking_time_settings' ][ $booking_weekday ] );
	            }
	        }
	    }
	    return $number_of_slots;    
	}
}
?>