<?php /* if(!class_exists('woocommerce_booking')){
    die();
}*/
include_once('bkap-common.php');
include_once('lang.php');

class bkap_cancel_order{

	/**********************************************************************
	 * This function will add cancel order button on the “MY ACCOUNT”  page. 
     * For cancelling the order.
	**********************************************************************/
			
	public static function bkap_get_add_cancel_button($order,$action){
		$myaccount_page_id = get_option( 'woocommerce_myaccount_page_id' );
		if ( $myaccount_page_id ) {
			$myaccount_page_url = get_permalink( $myaccount_page_id );
		}	
		if (isset($_GET['order_id']) &&  $_GET['order_id'] == $action->id && $_GET['cancel_order'] == "yes") {
			$order_obj = new WC_Order( $action->id );
			$order_obj->update_status( "cancelled" );
			print('<script type="text/javascript">
				location.href="'.$myaccount_page_url.'";
				</script>');
		}
		if ($action->status != "cancelled") {
			$order['cancel'] = array(
				"url" => apply_filters('woocommerce_get_cancel_order_url', add_query_arg('order_id', $action->id)."&cancel_order=yes"),
				"name" => "Cancel");
		}
		return $order;
	}
	
	/*************************************************************
     * This function deletes booking for the products in order 
     * when the order is cancelled or refunded.
     ************************************************************/
	
	public static function bkap_woocommerce_cancel_order($order_id) {
		
		global $wpdb,$post;
		$array = array();
		$order_obj = new WC_order($order_id);
		$order_items = $order_obj->get_items();
		$select_query = "SELECT booking_id FROM `".$wpdb->prefix."booking_order_history`
						WHERE order_id='".$order_id."'";
		$results = $wpdb->get_results ( $select_query );
		foreach($results as $k => $v) {
			$b[] = $v->booking_id;
			$select_query_post = "SELECT post_id,id FROM `".$wpdb->prefix."booking_history`
								WHERE id='".$v->booking_id."'";
			$results_post[] = $wpdb->get_results($select_query_post);
		}
		if (isset($results_post) && count($results_post) > 0 && $results_post != false) {
			foreach($results_post as $k => $v) {
				if (isset($v[0]->id)) $a[$v[0]->post_id][] = $v[0]->id;
			}	
		}
		$i = 0;
		foreach($order_items as $item_key => $item_value) {
			
			$product_id = bkap_common::bkap_get_product_id($item_value['product_id']);
			
			if(array_key_exists("variation_id",$item_value)) {
				$variation_id = $item_value['variation_id'];
			} else {
				$variation_id = '';
			}
			if(in_array($product_id,(array)$array)) {
			} else {
				$booking_settings = get_post_meta($product_id, 'woocommerce_booking_settings', true);
				$qty = $item_value['qty'];
				if (isset($a[$product_id])) {
					$result = $a[$product_id];
				}
				$e = 0;
				$from_time = '';
				$to_time = '';
				$date_date = '';
				$end_date = '';
				if (isset($result) && count($result) > 0 && $result != false) {
					foreach($result as $k =>$v) {
						$booking_id = $result[$e];
						if(isset($booking_settings['booking_enable_multiple_day']) && $booking_settings['booking_enable_multiple_day'] == 'on') {
							$select_data_query = "SELECT start_date,end_date FROM `".$wpdb->prefix."booking_history`
													WHERE id='".$booking_id."'";
							$results_data = $wpdb->get_results ( $select_data_query );
							$j=0;
							foreach($results_data as $k => $v) {
								$start_date = $results_data[$j]->start_date;
								$end_date = $results_data[$j]->end_date;
								$sql_delete_query = "DELETE FROM `".$wpdb->prefix."booking_history` WHERE id = '".$booking_id."' AND start_date =	'".$start_date."'	AND end_date = '".$end_date."' ";
								$wpdb->query( $sql_delete_query );
								$j++;
							}
						} else if(isset($booking_settings['booking_enable_time']) && $booking_settings['booking_enable_time'] == 'on') {
							$type_of_slot = apply_filters('bkap_slot_type',$product_id);
							if($type_of_slot == 'multiple') {
								do_action('bkap_order_status_cancelled',$order_id,$item_value,$booking_id);
							} else {
								$select_data_query = "SELECT * FROM `".$wpdb->prefix."booking_history`
												WHERE id='".$booking_id."'";
								$results_data = $wpdb->get_results ( $select_data_query );
								$j=0;
								foreach($results_data as $k => $v){
									$start_date = $results_data[$j]->start_date;
									$from_time = $results_data[$j]->from_time;
									$to_time = $results_data[$j]->to_time;
									if($from_time != '' && $to_time != '' || $from_time != ''){
										if($to_time != ''){
											$query = "UPDATE `".$wpdb->prefix."booking_history`
														SET available_booking = available_booking + ".$qty."
														WHERE 
														id = '".$booking_id."' AND
													start_date = '".$start_date."' AND
													from_time = '".$from_time."' AND
													to_time = '".$to_time."'";
											$select = "SELECT * FROM `".$wpdb->prefix."booking_history`
													WHERE post_id = '".$product_id."' AND
													start_date = '".$start_date."' AND
													from_time = '".$from_time."' AND
													to_time = '".$to_time."'";
											$select_results = $wpdb->get_results( $select );
											foreach($select_results as $k => $v){
												$details[$product_id] = $v;
											}
										} else {
											$query = "UPDATE `".$wpdb->prefix."booking_history`
														SET available_booking = available_booking + ".$qty."
														WHERE 
														id = '".$booking_id."' AND
														start_date = '".$start_date."' AND
														from_time = '".$from_time."'";
											$select = "SELECT * FROM `".$wpdb->prefix."booking_history`
													WHERE post_id = '".$product_id."' AND
													start_date = '".$start_date."' AND
													from_time = '".$from_time."'";
											$select_results = $wpdb->get_results( $select );
											foreach($select_results as $k => $v){
												$details[$product_id] = $v;
											}
										}
										$wpdb->query( $query );
									}	
									$j++;
								}
							}
						} else {
							$select_data_query = "SELECT * FROM `".$wpdb->prefix."booking_history`
											WHERE id='".$booking_id."'";
							$results_data = $wpdb->get_results ( $select_data_query );
							$j=0;
							foreach($results_data as $k => $v) {
								$start_date = $results_data[$j]->start_date;
								$from_time = $results_data[$j]->from_time;
								$to_time = $results_data[$j]->to_time;
								$query = "UPDATE `".$wpdb->prefix."booking_history`
											SET available_booking = available_booking + ".$qty."
											WHERE 
											id = '".$booking_id."' AND
											start_date = '".$start_date."' AND
											from_time = '' AND
											to_time = ''";
								$wpdb->query( $query );
							}
							$j++;
						}
						$e++;
					}
				}
			}
			$book_global_settings = json_decode(get_option('woocommerce_booking_global_settings'));		
			$label =  get_option("book.item-meta-date");
			$hidden_date = date('d-n-Y',strtotime($start_date));
			if (isset($booking_settings['booking_time_settings'][$hidden_date])){
				$lockout_settings = $booking_settings['booking_time_settings'][$hidden_date];
            } else {
                 $lockout_settings = array();
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
			if(isset($from_lockout_time[0])){
				$from_hours = $from_lockout_time[0];
            } else {
				$from_hours = '';
            }
			if(isset($from_lockout_time[1])){
				$from_minute = $from_lockout_time[1];
            } else {
				$from_minute = '';
            }
			if($to_time != '') {
				$to_lockout_time = explode(":",$to_time);
				$to_hours = $to_lockout_time[0];
				$to_minute = $to_lockout_time[1];
			} else {
				$to_hours = '';
				$to_minute = '';
			}
			if(count($lockout_settings) > 0) {
				foreach($lockout_settings as $l_key => $l_value) {
					if($l_value['from_slot_hrs'] == $from_hours && $l_value['from_slot_min'] == $from_minute && $l_value['to_slot_hrs'] == $to_hours && $l_value['to_slot_min'] == $to_minute) {
						if (isset($l_value['global_time_check'])){
							$global_timeslot_lockout = $l_value['global_time_check'];
                        }else{
                            $global_timeslot_lockout = '';
                        }
					}
				}
			}
			if($book_global_settings->booking_global_timeslot == 'on' || $global_timeslot_lockout == 'on') {
				$args = array( 'post_type' => 'product', 'posts_per_page' => -1 );
				$product = query_posts( $args );
				foreach($product as $k => $v) {
					$product_ids[] = $v->ID;
				}
				foreach($product_ids as $k => $v) {
					
					$duplicate_of = bkap_common::bkap_get_product_id($v);
					
					$booking_settings = get_post_meta($v, 'woocommerce_booking_settings' , true);
					if(isset($booking_settings['booking_enable_time']) && $booking_settings['booking_enable_time'] == 'on') {
						if(count($details) > 0) {
							if(!array_key_exists($duplicate_of,$details)) {	
								foreach($details as $key => $val) {
									$start_date = $val->start_date;
									$from_time = $val->from_time;
									$to_time = $val->to_time;
									if($to_time != "") {
										$query = "UPDATE `".$wpdb->prefix."booking_history`
												SET available_booking = available_booking + ".$qty."
												WHERE post_id = '".$duplicate_of."' AND
												start_date = '".$start_date."' AND
												from_time = '".$from_time."' AND
												to_time = '".$to_time."'";
										$wpdb->query($query);
									} else {
										$query = "UPDATE `".$wpdb->prefix."booking_history`
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
			$i++;
			$array[] = $product_id;
		}
	}
}
?>