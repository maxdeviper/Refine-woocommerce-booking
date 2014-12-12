<?php
class bkap_common{
/*********************************
 * This function returns the function name to display the timeslots on frontend if type of timeslot is Multiple for multiple time slots addon.
 ********************************/
	public static function bkap_ajax_on_select_date() {
		global $post;
		$booking_settings = get_post_meta($post->ID, 'woocommerce_booking_settings', true);
		if(isset($booking_settings['booking_enable_multiple_time']) && $booking_settings['booking_enable_multiple_time'] == "multiple" && is_plugin_active('bkap-multiple-time-slot/multiple-time-slot.php')) {
			return 'multiple_time';
		}
		/*else
		{
			return 'check_for_time_slot';
		}*/
	}

	public static function bkap_get_betweendays($StartDate, $EndDate)
	{
		$Days[] = $StartDate;
		$CurrentDate = $StartDate;
			
		$CurrentDate_timestamp = strtotime($CurrentDate);
		$EndDate_timestamp = strtotime($EndDate);
		if($CurrentDate_timestamp != $EndDate_timestamp)
		{
			while($CurrentDate_timestamp < $EndDate_timestamp)
			{
				$CurrentDate = date("d-n-Y", strtotime("+1 day", strtotime($CurrentDate)));
				$CurrentDate_timestamp = $CurrentDate_timestamp + 86400;
				$Days[] = $CurrentDate;
			}
			array_pop($Days);
		}
		return $Days;
	}
	
	public static function bkap_get_product_id($product_id)
	{
		global $wpdb;
		$duplicate_of = get_post_meta($product_id, '_icl_lang_duplicate_of', true);
	
		if($duplicate_of == '' && $duplicate_of == null){
			//	$duplicate_of = $cart_item['product_id'];
			$post_time = get_post($product_id);
			$id_query = "SELECT ID FROM `".$wpdb->prefix."posts` WHERE post_date =  %s ORDER BY ID LIMIT 1";
			$results_post_id = $wpdb->get_results ( $wpdb->prepare($id_query,$post_time->post_date) );
			if( isset($results_post_id) ) {
				$duplicate_of = $results_post_id[0]->ID;
			}else {
				$duplicate_of = $cart_item['product_id'];
			}
		}
		return $duplicate_of;
	}
	
	public static function bkap_get_price($product_id,$variation_id,$product_type) {
		if ( $product_type == 'variable'){
			$sale_price = get_post_meta( $variation_id, '_sale_price', true);
			if(!isset($sale_price) || $sale_price == '' || $sale_price == 0) {
				$regular_price = get_post_meta( $variation_id, '_regular_price', true);
				$price = $regular_price;
			}else {
				$price = $sale_price;
			}
		}elseif($product_type == 'simple') {
			$sale_price = get_post_meta( $product_id, '_sale_price', true);
			if(!isset($sale_price) || $sale_price == '' || $sale_price == 0) {
				$regular_price = get_post_meta( $product_id, '_regular_price', true);
				$price = $regular_price;
			}else {
				$price = $sale_price;
			}
		}
		return $price;
	}
	
	public static function bkap_get_product_type($product_id) {
		$product = get_product($product_id);
		$product_type = $product->product_type;
		return $product_type;
	}
	
	public static function bkap_multicurrency_price($price,$currency_selected) {	
		global $woocommerce_wpml;
		if($currency_selected != '') {
			$settings = $woocommerce_wpml->get_settings();
			if($settings['enable_multi_currency'] == 2) {
				$custom_post = get_post_meta( $variation_id, '_wcml_custom_prices_status', true);
				if($custom_post == 0) {
					$currencies = $woocommerce_wpml->multi_currency_support->get_currencies();
					foreach($currencies as $ckey => $cval) {
						if($ckey == $currency_selected) {
							$price  = $price * $cval['rate'];
						}
					}
				}
			}
		}
		return $price;
	}
}
?>