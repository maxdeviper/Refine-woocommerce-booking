<?php
//if(!class_exists('woocommerce_booking')){
//   die();
//}
include_once('bkap-common.php');
class bkap_cart{
	
	/**********************************************************
	 * This function adjust the extra prices for the product 
	 * with the price calculated from booking plugin.
	*********************************************************/
	public static function bkap_add_cart_item( $cart_item ) {
	
		// Adjust price if addons are set
		global $wpdb;
		if (isset($cart_item['booking'])) :
			
			$extra_cost = 0;
				
			foreach ($cart_item['booking'] as $addon) :
		
				if (isset($addon['price']) && $addon['price']>0) $extra_cost += $addon['price'];
	
			endforeach;
			
			$duplicate_of = bkap_common::bkap_get_product_id($cart_item['product_id']);
			$product = get_product($cart_item['product_id']);
		
			$product_type = $product->product_type;
				
			$variation_id = 0;
			if ($product_type == 'variable') {
				$variation_id = $cart_item['variation_id'];
			}
			$price = bkap_common::bkap_get_price($cart_item['product_id'],$variation_id,$product_type);
			$extra_cost = $extra_cost - $price;
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
		
		$duplicate_of = bkap_common::bkap_get_product_id($product_id);
	
		if (isset($_POST['booking_calender'])) {
			$date_disp = $_POST['booking_calender'];
		}
		if (isset($_POST['time_slot'])) {
			$time_disp = $_POST['time_slot'];
		}
		if (isset($_POST['wapbk_hidden_date'])) {
			$hidden_date = $_POST['wapbk_hidden_date'];
		}
	
		$booking_settings = get_post_meta($duplicate_of, 'woocommerce_booking_settings', true);
			
		$product = get_product($product_id);
	
		$product_type = $product->product_type;
		if(isset($booking_settings['booking_enable_multiple_day']) && $booking_settings['booking_enable_multiple_day'] == 'on') {
			if(isset($_POST['booking_calender_checkout'])) {
				$date_disp_checkout = $_POST['booking_calender_checkout'];
			}
			if(isset($_POST['wapbk_hidden_date_checkout'])) {
				$hidden_date_checkout = $_POST['wapbk_hidden_date_checkout'];
			}
			$diff_days = '';
			if(isset($_POST['wapbk_diff_days'])) {
				$diff_days = $_POST['wapbk_diff_days'];
			}
			$variation_id = 0;
			if ($product_type == 'variable') {
				$variation_id = $_POST['variation_id'];
			}
			$price = bkap_common::bkap_get_price($product_id, $variation_id, $product_type);
			$price = $price * $diff_days;
		} 
		else {
			$price = '';
		}
	
		//Round the price if needed
		$round_price = $price;
		$global_settings = json_decode(get_option('woocommerce_booking_global_settings'));
		if (isset($global_settings->enable_rounding) && $global_settings->enable_rounding == "on")
			$round_price = round($price);
		$price = $round_price;
	
		if (isset($date_disp)) {
			$cart_arr = array();
			if (isset($date_disp)) {
				$cart_arr['date'] = $date_disp;
			}
			if (isset($time_disp)) {
				$cart_arr['time_slot'] = $time_disp;
			}
			if (isset($hidden_date)) {
				$cart_arr['hidden_date'] = $hidden_date;
			}
			if ($booking_settings['booking_enable_multiple_day'] == 'on') {
				$cart_arr['date_checkout'] = $date_disp_checkout;
				$cart_arr['hidden_date_checkout'] = $hidden_date_checkout;
				$cart_arr['price'] = $price;
			} 
			else if(isset($booking_settings['booking_recurring_booking']) && $booking_settings['booking_recurring_booking'] == 'on') {
				$cart_arr['price'] = $price;
			}
			if (isset($_POST['variation_id'])) {
				$variation_id = $_POST['variation_id'];
			}
			else {
				$variation_id = '0';
			}
		
			$cart_arr = (array) apply_filters('bkap_addon_add_cart_item_data', $cart_arr, $product_id, $variation_id);
		
			$cart_item_meta['booking'][] = $cart_arr;
		}
		
		return $cart_item_meta;
	}

	/**********************************************
	 *  This function adjust the prices calculated 
	 *  from the plugin in the cart session.
	************************************************/
	function bkap_get_cart_item_from_session( $cart_item, $values ) {
	
		global $wpdb;
		$duplicate_of = bkap_common::bkap_get_product_id($cart_item['product_id']);
		
		if (isset($values['booking'])) :
			
		$cart_item['booking'] = $values['booking'];
		
			$booking_settings = get_post_meta($duplicate_of, 'woocommerce_booking_settings', true);
	
			if (isset($booking_settings['booking_enable_multiple_day']) && $booking_settings['booking_enable_multiple_day'] == 'on') {
				$cart_item = bkap_cart::bkap_add_cart_item( $cart_item );
			}
			$type_of_slot = apply_filters('bkap_slot_type',$cart_item['product_id']);
			if($type_of_slot == 'multiple') {
				$cart_item = (array) apply_filters('bkap_get_cart_item_from_session', $cart_item , $values);
			}
			else {
				$cart_item = (array) apply_filters('bkap_get_cart_item_from_session', $cart_item , $values);
			}
		endif;
		return $cart_item;
	}
	
	/**************************************
     * This function displays the Booking 
     * details on cart page, checkout page.
    ************************************/
	public static function bkap_get_item_data_booking( $other_data, $cart_item ) {

		global $wpdb;
			
		if (isset($cart_item['booking'])) :
			$duplicate_of = bkap_common::bkap_get_product_id($cart_item['product_id']);
			
			foreach ($cart_item['booking'] as $booking) :
			
				$name = get_option('book.item-cart-date');
			
				if (isset($booking['date']) && $booking['date'] != "") {
					$other_data[] = array(
							'name'    => $name,
							'display' => $booking['date']
					);
				}
				if (isset($booking['date_checkout']) && $booking['date_checkout'] != "") {
					$booking_settings = get_post_meta($duplicate_of, 'woocommerce_booking_settings', true);

					if ($booking_settings['booking_enable_multiple_day'] == 'on') {
						$other_data[] = array(
								'name'    => get_option('checkout.item-cart-date'),
								'display' => $booking['date_checkout']
						);						
					}
				}
				if (isset($booking['time_slot']) && $booking['time_slot'] != "") {
					$saved_settings = json_decode(get_option('woocommerce_booking_global_settings'));
					if (isset($saved_settings)){
						$time_format = $saved_settings->booking_time_format;
					}else {
						$time_format = "12";
					}
					$time_slot_to_display = $booking['time_slot'];
				
					if ($time_format == '12') {
						$time_exploded = explode("-", $time_slot_to_display);
						$from_time = date('h:i A', strtotime($time_exploded[0]));
						if (isset($time_exploded[1])) { 
							$to_time = date('h:i A', strtotime($time_exploded[1]));
						}
						else {
							$to_time = "";
						}
						if ($to_time != "") {
							$time_slot_to_display = $from_time.' - '.$to_time;
						}
						else {
							$time_slot_to_display = $from_time;
						}
					}
					$type_of_slot = apply_filters('bkap_slot_type',$cart_item['product_id']);
					if($type_of_slot != 'multiple') {
						$name = get_option('book.item-cart-time');
						$other_data[] = array(
							'name'    => $name,
							'display' => $time_slot_to_display
						);
					}
				}
				$type_of_slot = apply_filters('bkap_slot_type',$cart_item['product_id']);
				if($type_of_slot == 'multiple') {
					$other_data = apply_filters('bkap_timeslot_get_item_data',$other_data, $cart_item);
				}else {
					$other_data = apply_filters('bkap_get_item_data',$other_data, $cart_item);
				}
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
			if (isset($values['booking'])) {
				$booking = $values['booking'];
			}
			if (isset($booking[0]['price']) && $booking[0]['price'] != '') {
				$price += ($booking[0]['price']) * $values['quantity'];
			}
			else {
				if ($values['variation_id'] == '') {
					$product_type = $values['data']->product_type;
				}
				else {
					$product_type = $values['data']->parent->product_type;
				}
				
				$variation_id = 0;
				if ($product_type == 'variable') {
					$variation_id = $values['variation_id'];
				}
				$book_price = bkap_common::bkap_get_price($values['product_id'], $variation_id, $product_type);
				$price += $book_price * $values['quantity'];
			}
		}
			
		$saved_settings = json_decode(get_option('woocommerce_booking_global_settings'));
		if (isset($saved_settings->enable_rounding) && $saved_settings->enable_rounding == "on") {
			$total_price = round($price);
		}
		else {
			$total_price = number_format($price,2);
		}
	
		ob_start();
		$currency_symbol = get_woocommerce_currency_symbol();
		print('<p class="total"><strong>Subtotal:</strong> <span class="amount">'.$currency_symbol.$total_price.'</span></p>');
			
		$fragments['p.total'] = ob_get_clean();
			
		return $fragments;
	}
							
} 
?>