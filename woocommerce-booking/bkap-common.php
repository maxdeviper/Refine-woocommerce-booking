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
	    $booking_object->orer_date = $post_data->post_date;
	     
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
	
	/**
	 * Checks whether a product is bookable or no
	 * 
	 * @param int $product_id
	 * @return bool $bookable
	 */
	public static function bkap_get_bookable_status( $product_id ) {
	     
	    $bookable = false;
	     
	    // Booking settings
	    $booking_settings =   get_post_meta( $product_id , 'woocommerce_booking_settings' , true );
	     
	    if( isset( $booking_settings ) && isset( $booking_settings[ 'booking_enable_date' ] ) && 'on' == $booking_settings[ 'booking_enable_date' ] ) {
	        $bookable = true;
	    }
	     
	    return $bookable;
	}
	
	/**
	 * Get all products and variations and sort alphbetically, return in array (title, id)
	 * 
	 * @return array $full_product_list
	 */
	public static function get_woocommerce_product_list() {
	    $full_product_list = array();
	
	    $args       = array( 'post_type' => array('product', 'product_variation'), 'posts_per_page' => -1 );
	    $product    = query_posts( $args );
	
	    foreach ( $product as $k => $value ) {
	        $theid = $value->ID;
	        $product = new WC_Product( $theid );
	
	        if ( 'product_variation' == $value->post_type ) {
	            $parent_id = $value->post_parent;
	            // ignore orphan variations
	            if( 0 == $parent_id ) {
	                continue;
	            }
	            $duplicate_of = bkap_common::bkap_get_product_id( $parent_id );
	
	            $is_bookable = bkap_common::bkap_get_bookable_status( $duplicate_of );
	        } else {
	            $parent_id = 0;
	            $duplicate_of = bkap_common::bkap_get_product_id( $theid );
	            $is_bookable = bkap_common::bkap_get_bookable_status( $duplicate_of );
	        }
	
	        if ( $is_bookable ) {
	
	            $_product = wc_get_product( $theid );
	            $thetitle = $_product->get_formatted_name();
	
	            $full_product_list[] = array($thetitle, $theid);
	        }
	
	    }
	
	    // sort into alphabetical order, by title
	    sort($full_product_list);
	    return $full_product_list;
	
	}
	
	/**
	 * Adds item meta for bookable products
	 * 
	 * @param int $item_id
	 * @param int $product_id
	 * @param array $booking_data
	 * @param bool $gcal_import
	 */
	public static function bkap_update_order_item_meta( $item_id, $product_id, $booking_data, $gcal_import = false ) {
	
	    $booking_settings  =   get_post_meta( $product_id, 'woocommerce_booking_settings', true );
	
	    if ( $gcal_import ) {
	        wc_add_order_item_meta( $item_id, '_wapbk_booking_status', 'paid' );
	    } else {
	        if ( 'bkap-booking-gateway' == WC()->session->get( 'chosen_payment_method' ) ) {
	            wc_add_order_item_meta( $item_id, '_wapbk_booking_status', 'pending-confirmation' );
	        } else {
	            wc_add_order_item_meta( $item_id, '_wapbk_booking_status', 'confirmed' );
	        }
	    }
	     
	    if ( $booking_data['date'] != "" ) {
	        $name         =   get_option( 'book.item-meta-date' );
	        $date_select  =   $booking_data['date'];
	
	        wc_add_order_item_meta( $item_id, $name, sanitize_text_field( $date_select, true ) );
	    }
	     
	    if ( array_key_exists( 'hidden_date', $booking_data ) && $booking_data['hidden_date'] != "" ) {
	        // save the date in Y-m-d format
	        $date_booking = date( 'Y-m-d', strtotime( $booking_data['hidden_date'] ) );
	        wc_add_order_item_meta( $item_id, '_wapbk_booking_date', sanitize_text_field( $date_booking, true ) );
	    }
	     
	    if ( array_key_exists( 'date_checkout', $booking_data ) && $booking_data['date_checkout'] != "" ) {
	
	        if ( $booking_settings['booking_enable_multiple_day'] == 'on' ) {
	            $name_checkout           =   get_option( 'checkout.item-meta-date' );
	            $date_select_checkout    =   $booking_data['date_checkout'];
	
	            wc_add_order_item_meta( $item_id, $name_checkout, sanitize_text_field( $date_select_checkout, true ) );
	        }
	    }
	     
	    if ( array_key_exists( 'hidden_date_checkout', $booking_data ) && $booking_data['hidden_date_checkout'] != "" ) {
	         
	        if ( $booking_settings['booking_enable_multiple_day'] == 'on' ) {
	            // save the date in Y-m-d format
	            $date_booking = date( 'Y-m-d', strtotime( $booking_data['hidden_date_checkout'] ) );
	            wc_add_order_item_meta( $item_id, '_wapbk_checkout_date', sanitize_text_field( $date_booking, true ) );
	        }
	    }
	     
	    if ( array_key_exists( 'time_slot', $booking_data ) && $booking_data['time_slot'] != "" ) {
	        $time_slot_to_display     =   '';
	        $time_select              =   $booking_data['time_slot'];
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
	
	        wc_add_order_item_meta( $item_id,  get_option( 'book.item-meta-time' ), $time_slot_to_display, true );
	        wc_add_order_item_meta( $item_id,  '_wapbk_time_slot', $meta_data_format, true );
	         
	    }
	
	}
	
	/**
	 * Creates a list of orders that are not yet exported to GCal
	 * 
	 * @return array $total_orders_to_export
	 */
	public static function bkap_get_total_bookings_to_export() {
	
	    global $wpdb;
	    $total_orders_to_export = array();
	    $event_items = get_option( 'bkap_event_item_ids' );
	    if( $event_items == '' || $event_items == '{}' || $event_items == '[]' || $event_items == 'null' ) {
	        $event_items = array();
	    }
	
	    $current_time = current_time( 'timestamp' );
	
	    $bkap_query = "SELECT ID FROM `" . $wpdb->prefix . "posts` WHERE post_type = 'shop_order'";
	    $results = $wpdb->get_results( $bkap_query );
	
	    $total_orders_to_export = array();
	
	    foreach ( $results as $key => $value ) {
	        $order = new WC_Order( $value->ID );
	
	        if( isset( $order->post_status ) && ( $order->post_status != 'wc-cancelled' ) && ( $order->post_status != 'wc-refunded' ) && ( $order->post_status != 'trash' ) && ( $order->post_status != '' ) ) {
	
	            $get_items = $order->get_items();
                $i = 0;
                
	            foreach( $get_items as $item_id => $item_values ) {
	
	                $booking_status = '';
	                $booking_date = '';
	
	                if( !in_array( $item_id, $event_items ) ) {
	
	                    if ( isset( $item_values[ 'wapbk_booking_status' ] ) ) {
	                        $booking_status = $item_values[ 'wapbk_booking_status' ];
	                    }
	                    if ( isset( $item_values[ 'wapbk_booking_date' ] ) ) {
	                        $booking_date = strtotime( $item_values[ 'wapbk_booking_date' ] );
	                    }
	                    if ( isset( $booking_status ) && $booking_status != 'pending-confirmation' && isset( $booking_date ) && $booking_date != '' && $booking_date >= $current_time ) {
	                        $total_orders_to_export[ $value->ID ][ $i ] = $item_id;
	                    }
	                    $i++;
	                }
	            }
	        }
	    }
	    return $total_orders_to_export;
	}
}
?>