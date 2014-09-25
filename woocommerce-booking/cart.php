<?php
//if(!class_exists('woocommerce_booking')){
//   die();
//}
class bkap_cart{
	
	/**********************************************************
	 * This function adjust the extra prices for the product with the price calculated from booking plugin.
	*********************************************************/
	public static function bkap_add_cart_item( $cart_item ) {
	
		// Adjust price if addons are set
		global $wpdb;
		if (isset($cart_item['booking'])) :
			
		$extra_cost = 0;
			
		foreach ($cart_item['booking'] as $addon) :
	
		if (isset($addon['price']) && $addon['price']>0) $extra_cost += $addon['price'];
	
		endforeach;
			
		$duplicate_of = get_post_meta($cart_item['product_id'], '_icl_lang_duplicate_of', true);
		if($duplicate_of == '' && $duplicate_of == null){
			//	$duplicate_of = $cart_item['product_id'];
			$post_time = get_post($cart_item['product_id']);
			$id_query = "SELECT ID FROM `".$wpdb->prefix."posts` WHERE post_date = '".$post_time->post_date."' ORDER BY ID LIMIT 1";
			$results_post_id = $wpdb->get_results ( $id_query );
			if( isset($results_post_id) ) {
				$duplicate_of = $results_post_id[0]->ID;
			}else {
				$duplicate_of = $cart_item['product_id'];
			}
		}
		$product = get_product($cart_item['product_id']);
		//	$product = get_product($duplicate_of);
		$product_type = $product->product_type;
			
		if ( $product_type == 'variable'){
			$sale_price = get_post_meta( $cart_item['variation_id'], '_sale_price', true);
			if($sale_price == '') {
				$regular_price = get_post_meta( $cart_item['variation_id'], '_regular_price', true);
				$extra_cost = $extra_cost - $regular_price;
			}else {
				$extra_cost = $extra_cost - $sale_price;
			}
		}elseif($product_type == 'simple') {
			$sale_price = get_post_meta( $cart_item['product_id'], '_sale_price', true);
			//	$sale_price = get_post_meta( $duplicate_of, '_sale_price', true);
			if($sale_price == '') {
				$regular_price = get_post_meta( $cart_item['product_id'], '_regular_price', true);
				//	$regular_price = get_post_meta( $duplicate_of, '_regular_price', true);
				$extra_cost = $extra_cost - $regular_price;
			}else {
				$extra_cost = $extra_cost - $sale_price;
			}
		}
		$cart_item['data']->adjust_price( $extra_cost );
			
		endif;
		//echo "
		//add_cart_item is ";echo "<pre>";print_r($cart_item);echo "</pre>";
		return $cart_item;
	}
		
	/*************************************************
	 * This function returns the cart_item_meta with the booking details of the product when add to cart button is clicked.
	*****************************************************/
	public static function bkap_add_cart_item_data( $cart_item_meta, $product_id ){
		global $wpdb;
		$duplicate_of = get_post_meta($product_id, '_icl_lang_duplicate_of', true);
		if($duplicate_of == '' && $duplicate_of == null){
			//	$duplicate_of = $cart_item['product_id'];
			$post_time = get_post($product_id);
			$id_query = "SELECT ID FROM `".$wpdb->prefix."posts` WHERE post_date = '".$post_time->post_date."' ORDER BY ID LIMIT 1";
			//echo "<pre>";print_r($id_query);echo "</pre>";exit;
			$results_post_id = $wpdb->get_results ( $id_query );
			//	print_r($results_post_id);
			if( isset($results_post_id) ) {
				$duplicate_of = $results_post_id[0]->ID;
			}else {
				$duplicate_of = $product_id;
			}
		}
	
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
			
		//$show_checkout_date_calendar\
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
				
			if ($product_type == 'variable') {
				$sale_price = get_post_meta( $_POST['variation_id'], '_sale_price', true);
				if($sale_price == '') {
					$regular_price = get_post_meta( $_POST['variation_id'], '_regular_price',true);
					$price = $regular_price * $diff_days;
				}else {
					$price = $sale_price * $diff_days;
				}
			}elseif($product_type == 'simple') {
				$sale_price = get_post_meta( $product_id, '_sale_price', true);
	
				if(!isset($sale_price) || $sale_price == '' || $sale_price == 0) {
					$regular_price = get_post_meta($product_id, '_regular_price',true);
	
					$price = $regular_price * $diff_days;
				}else {
					$price = $sale_price * $diff_days;
				}
			}
		} else {
			$price = '';
		}
		//print_r($booking_settings);exit;
		//Round the price if needed
		$round_price = $price;
		$global_settings = json_decode(get_option('woocommerce_booking_global_settings'));
		if (isset($global_settings->enable_rounding) && $global_settings->enable_rounding == "on")
			$round_price = round($price);
		$price = $round_price;
	
		if (isset($date_disp)) {
			/*	$cart_arr = array(	'date' 		=> $date_disp,
			 'time_slot' => $time_disp,
					'hidden_date' => $hidden_date
			);*/
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
			} else if(isset($booking_settings['booking_recurring_booking']) && $booking_settings['booking_recurring_booking'] == 'on') {
				$cart_arr['price'] = $price;
			}
			if (isset($_POST['variation_id'])) $variation_id = $_POST['variation_id'];
			else $variation_id = '0';
			$type_of_slot = apply_filters('bkap_slot_type',$product_id);
			if($type_of_slot == 'multiple') {
				$cart_arr = (array) apply_filters('bkap_multiple_add_cart_item_data', $cart_item_meta, $product_id);
			}else {
				$cart_arr = (array) apply_filters('bkap_add_cart_item_data', $cart_arr, $product_id);
	
				if(is_plugin_active('bkap-seasonal-pricing/seasonal_pricing.php'))
					$cart_arr = (array) apply_filters('bkap_addon_add_cart_item_data', $cart_arr, $product_id, $variation_id);
	
				//print_r($cart_arr);exit;
			}
			$cart_item_meta['booking'][] = $cart_arr;
		}
	
		//echo "add_cart_item_data is ";echo "<pre>";print_r($cart_item_meta);echo "</prE>";exit;
		return $cart_item_meta;
	}

	/**********************************************
	 *  This function adjust the prices calculated from the plugin in the cart session.
	************************************************/
	function bkap_get_cart_item_from_session( $cart_item, $values ) {
	
		global $wpdb;
		$duplicate_of = get_post_meta($cart_item['product_id'], '_icl_lang_duplicate_of', true);
		if($duplicate_of == '' && $duplicate_of == null) {
			//	$duplicate_of = $cart_item['product_id'];
			$post_time = get_post($cart_item['product_id']);
			$id_query = "SELECT ID FROM `".$wpdb->prefix."posts` WHERE post_date = '".$post_time->post_date."' ORDER BY ID LIMIT 1";
			//echo "<pre>";print_r($id_query);echo "</pre>";exit;
			$results_post_id = $wpdb->get_results ( $id_query );
			//	print_r($results_post_id);
			if( isset($results_post_id) ) {
				$duplicate_of = $results_post_id[0]->ID;
			}else {
				$duplicate_of = $cart_item['product_id'];
			}
		}
		if (isset($values['booking'])) :
			
		$cart_item['booking'] = $values['booking'];
		//print_r($cart_item);
		$booking_settings = get_post_meta($duplicate_of, 'woocommerce_booking_settings', true);
		//$show_checkout_date_calendar = 1;
		if (isset($booking_settings['booking_enable_multiple_day']) && $booking_settings['booking_enable_multiple_day'] == 'on') {
			$cart_item = bkap_cart::bkap_get_add_cart_item( $cart_item );
		}
		$type_of_slot = apply_filters('bkap_slot_type',$cart_item['product_id']);
		if($type_of_slot == 'multiple') {
			$cart_item = (array) apply_filters('bkap_get_cart_item_from_session', $cart_item , $values);
		}else {
			$cart_item = (array) apply_filters('bkap_get_cart_item_from_session', $cart_item , $values);
		}
		endif;
		//echo "get_cart_item_from_session ";echo "<pre>";print_r($cart_item);echo "</prE>";exit;
		return $cart_item;
	}
	
	/**************************************
                         * This function displays the Booking details on cart page, checkout page.
                         ************************************/
				public static function bkap_get_item_data( $other_data, $cart_item ) {
				global $wpdb;
				//echo "<pre>";print_r($cart_item);echo "</pre>";//exit;
				if (isset($cart_item['booking'])) :
					$duplicate_of = get_post_meta($cart_item['product_id'], '_icl_lang_duplicate_of', true);
					if($duplicate_of == '' && $duplicate_of == null) {
					//	$duplicate_of = $cart_item['product_id'];
						$post_time = get_post($cart_item['product_id']);
						$id_query = "SELECT ID FROM `".$wpdb->prefix."posts` WHERE post_date = '".$post_time->post_date."' ORDER BY ID LIMIT 1";
						$results_post_id = $wpdb->get_results ( $id_query );
					//	print_r($results_post_id);
						if( isset($results_post_id) ) {
							$duplicate_of = $results_post_id[0]->ID;
						} else {
							$duplicate_of = $cart_item['product_id'];
						}
					}
				//	echo $duplicate_of;
					foreach ($cart_item['booking'] as $booking) :
					
						$name = get_option('book.item-cart-date');
						//if ($booking['price']>0) $name .= ' (' . woocommerce_price($booking['price']) . ')';
						if (isset($booking['date']) && $booking['date'] != "") {
							$other_data[] = array(
									'name'    => $name,
									'display' => $booking['date']
							);
						}
						if (isset($booking['date_checkout']) && $booking['date_checkout'] != "") {
							//$show_checkout_date_calendar = 1;
						//	$booking_settings = get_post_meta($cart_item['product_id'], 'woocommerce_booking_settings', true);
							$booking_settings = get_post_meta($duplicate_of, 'woocommerce_booking_settings', true);
							//print_r($booking_settings);
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
						//	if ($time_format == "" OR $time_format == "NULL") $time_format = "12";
							if ($time_format == '12') {
								$time_exploded = explode("-", $time_slot_to_display);
								$from_time = date('h:i A', strtotime($time_exploded[0]));
								if (isset($time_exploded[1])) $to_time = date('h:i A', strtotime($time_exploded[1]));
								else $to_time = "";
								if ($to_time != "") $time_slot_to_display = $from_time.' - '.$to_time;
								else $time_slot_to_display = $from_time;
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
					/*	$price = $cart_item['booking']['0']['price'];
						$price -= $cart_item['line_total']; 
						$cart_item['data']->adjust_price( $price );*/
				//		echo "price is : " . $price;echo "<pre>";print_r($other_data);print_r($cart_item);echo "</pre>";//exit;
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
} 
?>