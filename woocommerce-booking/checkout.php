<?php 
//if(!class_exists('woocommerce_booking')){
//   die();
//}

class bkap_checkout{

	/*******************************************************
	 * * This function updates the database for the booking details and add booking fields on the order received page,
	*  and woocommerce edit order when order is placed for woocommerce version breater than 2.0.
	******************************************************/
	public static function bkap_order_item_meta( $item_meta, $cart_item ) {
		if ( version_compare( WOOCOMMERCE_VERSION, "2.0.0" ) < 0 ) {
			return;
		}
		// Add the fields
		global $wpdb;
	
		global $woocommerce;
	
		//print_r($product_id);
		//exit;
		$order_item_ids = array();
		$sub_query = "";
		$ticket_content = array();
		$i = 0;
		foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
			$_product = $values['data'];
			//print_r($_product);
			if(array_key_exists("variation_id",$values)){
				$variation_id = $values['variation_id'];
			}else {
				$variation_id = '';
			}
			if (isset($values['booking'])) $booking = $values['booking'];
			$quantity = $values['quantity'];
			$post_id = get_post_meta($values['product_id'], '_icl_lang_duplicate_of', true);
			if($post_id == '' && $post_id == null) {
				$post_time = get_post($values['product_id']);
				$id_query = "SELECT ID FROM `".$wpdb->prefix."posts` WHERE post_date = '".$post_time->post_date."' ORDER BY ID LIMIT 1";
				$results_post_id = $wpdb->get_results ( $id_query );
				if( $results_post_id ) {
					$post_id = $results_post_id[0]->ID;;
				}else {
					$post_id = $values['product_id'];
				}
			}
			//$post_id = $values['product_id'];
			$post_title = $_product->get_title();
			// Fetch line item
			//echo addslashes($post_title);
			if (count($order_item_ids) > 0) {
				$order_item_ids_to_exclude = implode(",", $order_item_ids);
				$sub_query = " AND order_item_id NOT IN (".$order_item_ids_to_exclude.")";
			}
				
			$query = "SELECT order_item_id,order_id FROM `".$wpdb->prefix."woocommerce_order_items`
			WHERE order_id = '".$item_meta."' AND order_item_name = '".addslashes($post_title)."'".$sub_query;
			//echo $query;exit;
			$results = $wpdb->get_results( $query );
			//print_r($results);echo "<br>";
			$order_item_ids[] = $results[0]->order_item_id;
			$order_id = $results[0]->order_id;
			$order_obj = new WC_order($order_id);
			$details = array();
			$product_ids = array();
			//print_r($order_obj);
			$order_items = $order_obj->get_items();
				
			$type_of_slot = apply_filters('bkap_slot_type',$post_id);
			if(is_plugin_active('bkap-tour-operators/tour_operators_addon.php')) {
				do_action('bkap_operator_update_order',$values,$results[0]);
			}
			$booking_settings = get_post_meta( $post_id, 'woocommerce_booking_settings', true);
			if(isset($booking_settings['booking_partial_payment_enable']) && isset($booking_settings['booking_partial_payment_radio']) && $booking_settings['booking_partial_payment_radio']!='' &&  is_plugin_active('bkap-deposits/deposits.php')) {
				do_action('bkap_deposits_update_order',$values,$results[0]);
			}
			if($type_of_slot == 'multiple') {
				do_action('bkap_update_booking_history',$values,$results[0]);
			}else{
				if (isset($values['booking'])) :
				//	print_r($values['booking']);exit;
				$details = array();
				if ($booking[0]['date'] != "") {
					$name = get_option('book.item-meta-date');
					$date_select = $booking[0]['date'];
						
					woocommerce_add_order_item_meta( $results[0]->order_item_id, $name, sanitize_text_field( $date_select, true ) );
				}
				if (array_key_exists('date_checkout',$booking[0]) && $booking[0]['date_checkout'] != "") {
					$booking_settings = get_post_meta($post_id, 'woocommerce_booking_settings', true);
	
					if ($booking_settings['booking_enable_multiple_day'] == 'on') {
						$name_checkout = get_option('checkout.item-meta-date');
						$date_select_checkout = $booking[0]['date_checkout'];
						//echo $results[0]->order_item_id;exit;
						woocommerce_add_order_item_meta( $results[0]->order_item_id, $name_checkout, sanitize_text_field( $date_select_checkout, true ) );
					}
				}
				if (array_key_exists('time_slot',$booking[0]) && $booking[0]['time_slot'] != "") {
					$time_slot_to_display = '';
					$time_select = $booking[0]['time_slot'];
					$time_exploded = explode("-", $time_select);
					//print_r($time_exploded);
					$saved_settings = json_decode(get_option('woocommerce_booking_global_settings'));
					if (isset($saved_settings)) {
						$time_format = $saved_settings->booking_time_format;
					}else{
						$time_format = "12";
					}
					$time_slot_to_display = '';
					$from_time = trim($time_exploded[0]);
					if(isset($time_exploded[1])){
						$to_time = trim($time_exploded[1]);
					}else{
						$to_time = '';
					}
					if ($time_format == '12') {
						$from_time = date('h:i A', strtotime($time_exploded[0]));
						if(isset($time_exploded[1]))$to_time = date('h:i A', strtotime($time_exploded[1]));
					}
					$query_from_time = date('G:i', strtotime($time_exploded[0]));
					if(isset($time_exploded[1])){
						$query_to_time = date('G:i', strtotime($time_exploded[1]));
					}else{
						$query_to_time = '';
					}
					if($to_time != '') {
						$time_slot_to_display = $from_time.' - '.$to_time;
					}else {
						$time_slot_to_display = $from_time;
					}
					woocommerce_add_order_item_meta( $results[0]->order_item_id,  get_option('book.item-meta-time'), $time_slot_to_display, true );
						
				}
				$hidden_date = $booking[0]['hidden_date'];
				$date_query = date('Y-m-d', strtotime($hidden_date));
				if(array_key_exists('hidden_date_checkout',$booking[0])) {
					$date_checkout = $booking[0]['hidden_date_checkout'];
					$date_checkout_query = date('Y-m-d',strtotime($date_checkout));
				}
	
				if (isset($booking_settings['booking_enable_multiple_day'])&& $booking_settings['booking_enable_multiple_day'] == 'on') {
					for ($i = 0; $i < $quantity; $i++) {
						$query = "INSERT INTO `".$wpdb->prefix."booking_history`
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
					}
					$new_booking_id = mysql_insert_id();
					$order_query = "INSERT INTO `".$wpdb->prefix."booking_order_history`
					(order_id,booking_id)
					VALUES (
					'".$order_id."',
					'".$new_booking_id."' )";
					$wpdb->query( $order_query );
				} else {
					if(isset($booking[0]['time_slot']) && $booking[0]['time_slot'] != "") {
						if($query_to_time != "") {
							$query = "UPDATE `".$wpdb->prefix."booking_history`
							SET available_booking = available_booking - ".$quantity."
							WHERE post_id = '".$post_id."' AND
							start_date = '".$date_query."' AND
							from_time = '".$query_from_time."' AND
							to_time = '".$query_to_time."' AND
							total_booking > 0";
							$wpdb->query( $query );
								
							$select = "SELECT * FROM `".$wpdb->prefix."booking_history`
							WHERE post_id = '".$post_id."' AND
							start_date = '".$date_query."' AND
							from_time = '".$query_from_time."' AND
							to_time = '".$query_to_time."' ";
							$select_results = $wpdb->get_results( $select );
							foreach($select_results as $k => $v) {
								$details[$post_id] = $v;
							}
						}else {
							$query = "UPDATE `".$wpdb->prefix."booking_history`
							SET available_booking = available_booking - ".$quantity."
							WHERE post_id = '".$post_id."' AND
							start_date = '".$date_query."' AND
							from_time = '".$query_from_time."' AND
							total_booking > 0";
							$wpdb->query( $query );
								
							$select = "SELECT * FROM `".$wpdb->prefix."booking_history`
							WHERE post_id = '".$post_id."' AND
							start_date = '".$date_query."' AND
							from_time = '".$query_from_time."'";
							$select_results = $wpdb->get_results( $select );
							foreach($select_results as $k => $v) {
								$details[$post_id] = $v;
							}
						}
					}else {
						$query = "UPDATE `".$wpdb->prefix."booking_history`
						SET available_booking = available_booking - ".$quantity."
						WHERE post_id = '".$post_id."' AND
						start_date = '".$date_query."' AND
						total_booking > 0";
						$wpdb->query( $query );
					}
	
				}
	
				if (isset($booking_settings['booking_enable_multiple_day']) && $booking_settings['booking_enable_multiple_day'] != 'on') {
					if(array_key_exists('date',$booking[0]) && $booking[0]['time_slot'] != "") {
						if($query_to_time != '') {
							$order_select_query = "SELECT id FROM `".$wpdb->prefix."booking_history`
							WHERE post_id = '".$post_id."' AND
							start_date = '".$date_query."' AND
							from_time = '".$query_from_time."' AND
							to_time = '".$query_to_time."' ";
							$order_results = $wpdb->get_results( $order_select_query );
						}else {
							$order_select_query = "SELECT id FROM `".$wpdb->prefix."booking_history`
							WHERE post_id = '".$post_id."' AND
							start_date = '".$date_query."' AND
							from_time = '".$query_from_time."'";
							$order_results = $wpdb->get_results( $order_select_query );
						}
					} else {
						$order_select_query = "SELECT id FROM `".$wpdb->prefix."booking_history`
						WHERE post_id = '".$post_id."' AND
						start_date = '".$date_query."'";
						$order_results = $wpdb->get_results( $order_select_query );
					}
					$j = 0;
					foreach($order_results as $k => $v) {
						$booking_id = $order_results[$j]->id;
						$order_query = "INSERT INTO `".$wpdb->prefix."booking_order_history`
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
			$book_global_settings = json_decode(get_option('woocommerce_booking_global_settings'));
			$booking_settings = get_post_meta($post_id, 'woocommerce_booking_settings' , true);
			if(isset($booking_settings['booking_time_settings'])){
				if (isset($booking_settings['booking_time_settings'][$hidden_date])) $lockout_settings = $booking_settings['booking_time_settings'][$hidden_date];
				else $lockout_settings = array();
				if(count($lockout_settings) == 0){
					$week_day = date('l',strtotime($hidden_date));
					$weekdays = bkap_get_book_arrays('weekdays');
					$weekday = array_search($week_day,$weekdays);
					if (isset($booking_settings['booking_time_settings'][$weekday])) $lockout_settings = $booking_settings['booking_time_settings'][$weekday];
					else $lockout_settings = array();
				}
				$from_lockout_time = explode(":",$query_from_time);
				$from_hours = $from_lockout_time[0];
				$from_minute = $from_lockout_time[1];
				if(isset($query_to_time) && $query_to_time != '') {
					$to_lockout_time = explode(":",$query_to_time);
					$to_hours = $to_lockout_time[0];
					$to_minute = $to_lockout_time[1];
				}else {
					$to_hours = '';
					$to_minute = '';
				}
				foreach($lockout_settings as $l_key => $l_value) {
					if($l_value['from_slot_hrs'] == $from_hours && $l_value['from_slot_min'] == $from_minute && $l_value['to_slot_hrs'] == $to_hours && $l_value['to_slot_min'] == $to_minute) {
						if (isset($l_value['global_time_check'])){
							$global_timeslot_lockout = $l_value['global_time_check'];
						} else{
							$global_timeslot_lockout = '';
						}
					}
				}
			}
			if(isset($book_global_settings->booking_global_timeslot) && $book_global_settings->booking_global_timeslot == 'on' || $global_timeslot_lockout == 'on') {
				$args = array( 'post_type' => 'product', 'posts_per_page' => -1 );
				$product = query_posts( $args );
				foreach($product as $k => $v){
					$product_ids[] = $v->ID;
				}
				foreach($product_ids as $k => $v){
					$duplicate_of = get_post_meta($v, '_icl_lang_duplicate_of', true);
					if($duplicate_of == '' && $duplicate_of == null) {
						$post_time = get_post($v);
						$id_query = "SELECT ID FROM `".$wpdb->prefix."posts` WHERE post_date = '".$post_time->post_date."' ORDER BY ID LIMIT 1";
						$results_post_id = $wpdb->get_results ( $id_query );
						if( isset($results_post_id) ) {
							$duplicate_of = $results_post_id[0]->ID;
						}else {
							$duplicate_of = $v;
						}
						//$duplicate_of = $item_value['product_id'];
					}
					$booking_settings = get_post_meta($v, 'woocommerce_booking_settings' , true);
					if(isset($booking_settings['booking_enable_time']) && $booking_settings['booking_enable_time'] == 'on') {
						//echo "<pre>";print_r($details);echo "</pre>";exit;
						if(!array_key_exists($duplicate_of,$details)) {
							foreach($details as $key => $val){
								$booking_settings = get_post_meta($duplicate_of, 'woocommerce_booking_settings', true);
								//echo"<pre>";print_r($booking_settings);echo"</pre>";exit;
								$start_date = $val->start_date;
								$from_time = $val->from_time;
								$to_time = $val->to_time;
								if($to_time != ""){
									$query = "UPDATE `".$wpdb->prefix."booking_history`
									SET available_booking = available_booking - ".$quantity."
									WHERE post_id = '".$duplicate_of."' AND
									start_date = '".$date_query."' AND
									from_time = '".$from_time."' AND
									to_time = '".$to_time."' ";
									$updated = $wpdb->query( $query );
									if($updated == 0) {
										if($val->weekday == '') {
											$week_day = date('l',strtotime($date_query));
											$weekdays = bkap_get_book_arrays('weekdays');
											$weekday = array_search($week_day,$weekdays);
											//echo $weekday;exit;
										} else {
											$weekday = $val->weekday;
										}
										$results = array();
										$query = "SELECT * FROM `".$wpdb->prefix."booking_history`
										WHERE post_id = '".$duplicate_of."'
										AND weekday = '".$weekday."'";
										//echo $query;exit;
										$results = $wpdb->get_results( $query );
										if (!$results) break;
										else {
											//print_r($results);exit;
											foreach($results as $r_key => $r_val) {
												if($from_time == $r_val->from_time && $to_time == $r_val->to_time) {
													$available_booking = $r_val->available_booking - $quantity;
													$query_insert = "INSERT INTO `".$wpdb->prefix."booking_history`
													(post_id,weekday,start_date,from_time,to_time,total_booking,available_booking)
													VALUES (
													'".$duplicate_of."',
													'".$weekday."',
													'".$start_date."',
													'".$r_val->from_time."',
													'".$r_val->to_time."',
													'".$r_val->available_booking."',
													'".$available_booking."' )";
													//echo $query_insert;exit;
													$wpdb->query( $query_insert );
	
												}else {
													$from_lockout_time = explode(":",$r_val->from_time);
													$from_hours = $from_lockout_time[0];
													$from_minute = $from_lockout_time[1];
													if(isset($query_to_time) && $query_to_time != '') {
														$to_lockout_time = explode(":",$r_val->to_time);
														$to_hours = $to_lockout_time[0];
														$to_minute = $to_lockout_time[1];
													}
													foreach($lockout_settings as $l_key => $l_value) {
														if($l_value['from_slot_hrs'] == $from_hours && $l_value['from_slot_min'] == $from_minute && $l_value['to_slot_hrs'] == $to_hours && $l_value['to_slot_min'] == $to_minute) {
															$query_insert = "INSERT INTO `".$wpdb->prefix."booking_history`
															(post_id,weekday,start_date,from_time,to_time,total_booking,available_booking)
															VALUES (
															'".$duplicate_of."',
															'".$weekday."',
															'".$start_date."',
															'".$r_val->from_time."',
															'".$r_val->to_time."',
															'".$r_val->available_booking."',
															'".$r_val->available_booking."' )";
															$wpdb->query( $query_insert );
														}
													}
												}
											}
										}
									}
								}else {
									$query = "UPDATE `".$wpdb->prefix."booking_history`
									SET available_booking = available_booking - ".$quantity."
									WHERE post_id = '".$duplicate_of."' AND
									start_date = '".$date_query."' AND
									from_time = '".$from_time."'
									AND to_time = ''";
									//$wpdb->query( $query );
									$updated = $wpdb->query( $query );
									if($updated == 0) {
										if($val->weekday == '') {
											$week_day = date('l',strtotime($date_query));
											$weekdays = bkap_get_book_arrays('weekdays');
											$weekday = array_search($week_day,$weekdays);
											//echo $weekday;exit;
										} else {
											$weekday = $val->weekday;
										}
										$results= array();
										$query = "SELECT * FROM `".$wpdb->prefix."booking_history`
										WHERE post_id = '".$duplicate_of."'
										AND weekday = '".$weekday."'
										AND to_time = '' ";
										$results = $wpdb->get_results( $query );
										if (!$results) break;
										else {
											foreach($results as $r_key => $r_val) {
												if($from_time == $r_val->from_time) {
													$available_booking = $r_val->available_booking - $quantity;
													$query_insert = "INSERT INTO `".$wpdb->prefix."booking_history`
													(post_id,weekday,start_date,from_time,total_booking,available_booking)
													VALUES (
													'".$duplicate_of."',
													'".$weekday."',
													'".$start_date."',
													'".$r_val->from_time."',
													'".$r_val->available_booking."',
													'".$available_booking."' )";
													$wpdb->query( $query_insert );
												}else {
													$from_lockout_time = explode(":",$r_val->from_time);
													$from_hours = $from_lockout_time[0];
													$from_minute = $from_lockout_time[1];
													foreach($lockout_settings as $l_key => $l_value) {
														if($l_value['from_slot_hrs'] == $from_hours && $l_value['from_slot_min'] == $from_minute) {
															$query_insert = "INSERT INTO `".$wpdb->prefix."booking_history`
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
			$ticket = array(apply_filters('bkap_send_ticket',$values,$order_obj));
			$ticket_content = array_merge($ticket_content,$ticket);
			$i++;
		}
		//exit;//print_r($ticket_content);exit;
		do_action('bkap_send_email',$ticket_content);
	}	
}
?>