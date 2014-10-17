<?php /* if(!class_exists('woocommerce_booking')){
    die();
}*/
include_once('bkap-common.php');
include_once('lang.php');
//exit;
class bkap_ics{
	
	  /**************************************
       * This fuction adds Add to calendar button on Order recieved page which when clicked download the ICS file with booking details.
       **********************************/
	   
		public static function bkap_export_to_ics($order){
			global $woocommerce,$wpdb;
			//exit;
			$order_obj = new WC_Order( $order->id );
			$order_items = $order_obj->get_items();
			//echo "order<pre>";print_r($order_items);echo "</pre>";
			$today_query = "SELECT * FROM `".$wpdb->prefix."booking_history` AS a1,`".$wpdb->prefix."booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a2.order_id = %d";
			$results_date = $wpdb->get_results ( $wpdb->prepare($today_query,$order->id) );
			$c = 0;
			if($results_date) {
				foreach ($order_items as $item_key => $item_value) {
					$duplicate_of = get_post_meta($item_value['product_id'], '_icl_lang_duplicate_of', true);
					if($duplicate_of == '' && $duplicate_of == null) {
						$post_time = get_post($item_value['product_id']);
						$id_query = "SELECT ID FROM `".$wpdb->prefix."posts` WHERE post_date = %s ORDER BY ID LIMIT 1";
						$results_post_id = $wpdb->get_results ($wpdb->prepare($id_query,$post_time->post_date));
						if( isset($results_post_id) ) {
							$duplicate_of = $results_post_id[0]->ID;
						} else {
							$duplicate_of = $item_value['product_id'];
						}
					}
					$booking_settings = get_post_meta($item_value['product_id'], 'woocommerce_booking_settings', true);
					if (isset($booking_settings['booking_enable_date']) && $booking_settings['booking_enable_date'] == 'on' && isset($booking_settings['booking_enable_multiple_day']) && $booking_settings['booking_enable_multiple_day'] == '') {
						$book_global_settings = json_decode(get_option('woocommerce_booking_global_settings'));
						for ( $c = 0; $c < count($results_date); $c++ ) {
							if ( $results_date[$c]->post_id == $duplicate_of ) {
								$dt = new DateTime($results_date[$c]->start_date);
								$time = 0;			
								$time_start = 0;
								$time_end = 0;
								if (isset($booking_settings['booking_enable_time']) && $booking_settings['booking_enable_time'] == 'on') {
									$time_start = explode(':', $results_date[$c]->from_time);
									$time_end = explode(':', $results_date[$c]->to_time);
								}
								//if (isset($time_start[1])) 
								{
									$start_timestamp = strtotime($dt->format('Y-m-d')) + $time_start[0]*60*60 + $time_start[1]*60 + (time() - current_time('timestamp'));
								}		
								if (isset($time_end[1])) {
									$end_timestamp = strtotime($dt->format('Y-m-d')) + $time_end[0]*60*60 + $time_end[1]*60 + (time() - current_time('timestamp'));
								} else {
									$end_timestamp = '';
								}
								?>
								
								<form method="post" action="<?php echo plugins_url("/export-ics.php", __FILE__ );?>" id="export_to_ics">
									<input type="hidden" id="book_date_start" name="book_date_start" value="<?php echo $start_timestamp; ?>" />
									<input type="hidden" id="book_date_end" name="book_date_end" value="<?php echo $end_timestamp; ?>" />
									
									<!-- <input type="hidden" id="key_no_<?php echo $date_key; ?>" name="key_no_<?php echo $date_key; ?>" value="<?php echo $date_key; ?>" /> -->
									
									<input type="hidden" id="current_time" name="current_time" value="<?php echo current_time('timestamp'); ?>" />
									<input type="hidden" id="book_name" name="book_name" value="<?php echo $item_value['name']; ?>" />
									
									<input type="submit" id="exp_ics" name="exp_ics" value="<?php _e( 'Add to Calendar', 'woocommerce-booking' ); ?>" /> (<?php echo $item_value['name']; ?>)
									
								</form>
								<?php 
							}
						}
					} elseif (isset($booking_settings['booking_enable_date']) && $booking_settings['booking_enable_date'] == 'on' && isset($booking_settings['booking_enable_multiple_day']) && $booking_settings['booking_enable_multiple_day'] == 'on') {
						$book_global_settings = json_decode(get_option('woocommerce_booking_global_settings'));
						for ( $c = 0; $c < count($results_date); $c++ ) {
							if ( $results_date[$c]->post_id == $duplicate_of ) {
                                $dt_start = new DateTime($results_date[$c]->start_date);
								$dt_end = new DateTime($results_date[$c]->end_date);		
								$start_timestamp = strtotime($dt_start->format('Y-m-d'));
								$end_timestamp = strtotime($dt_end->format('Y-m-d'));
								?>
								<form method="post" action="<?php echo plugins_url("/export-ics.php", __FILE__ );?>" id="export_to_ics">	
									<input type="hidden" id="book_date_start" name="book_date_start" value="<?php echo $start_timestamp; ?>" />
									<input type="hidden" id="book_date_end" name="book_date_end" value="<?php echo $end_timestamp; ?>" />
									<input type="hidden" id="current_time" name="current_time" value="<?php echo current_time('timestamp'); ?>" />
									<input type="hidden" id="book_name" name="book_name" value="<?php echo $item_value['name']; ?>" />	
									<input type="submit" id="exp_ics" name="exp_csv" value="<?php _e( 'Add to Calendar', 'woocommerce-booking' ); ?>" /> (<?php echo $item_value['name']; ?>)
								</form>
								<?php 
							}
						}
					}
				}
			}
							
		}
                                                
		/******************************
         * This function attach the ICS file with the booking details in the email sent to user and admin.
         ********************************/
		
		public static function bkap_email_attachment ( $other, $order_id, $order ) {
			global $wpdb;				
			$order_obj = new WC_Order( $order->id );	
			$order_items = $order_obj->get_items();	
			$random_hash = md5(date('r', time()));	
			$today_query = "SELECT * FROM `".$wpdb->prefix."booking_history` AS a1,`".$wpdb->prefix."booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a2.order_id = %d";
			$results_date = $wpdb->get_results ($wpdb->prepare($today_query,$order->id));
			$file = array();
			$c = 0;
			foreach ($order_items as $item_key => $item_value) {
				$duplicate_of = get_post_meta($item_value['product_id'], '_icl_lang_duplicate_of', true);
				if($duplicate_of == '' && $duplicate_of == null) {
					$post_time = get_post($item_value['product_id']);
					$id_query = "SELECT ID FROM `".$wpdb->prefix."posts` WHERE post_date = %s ORDER BY ID LIMIT 1";
					$results_post_id = $wpdb->get_results ($wpdb->prepare($id_query,$post_time->post_date));
					if( isset($results_post_id) ) {
						$duplicate_of = $results_post_id[0]->ID;
					} else {
						$duplicate_of = $item_value['product_id'];
					}
				}
				$booking_settings = get_post_meta($item_value['product_id'], 'woocommerce_booking_settings', true);
				if ((isset($booking_settings['booking_enable_date']) && $booking_settings['booking_enable_date'] == 'on') && (isset($booking_settings['booking_enable_multiple_day']) && $booking_settings['booking_enable_multiple_day'] == '')) {
					$book_global_settings = json_decode(get_option('woocommerce_booking_global_settings'));
					//foreach ( $results_date as $date_key => $date_value )
					for ( $c = 0; $c < count($results_date); $c++ ) {
						if ( $results_date[$c]->post_id == $duplicate_of ) {
							$dt = new DateTime($results_date[$c]->start_date);
							//$dt = new DateTime($date_value->start_date);
							//$dt = new DateTime($item_value['Check-in Date']);
							$time = 0;
							$time_start = 0;
							$time_end = 0;
							if ($booking_settings['booking_enable_time'] == 'on') {	
								$time_start = explode(':', $results_date[$c]->from_time);
								$time_end = explode(':', $results_date[$c]->to_time);
							}
							$start_timestamp = strtotime($dt->format('Y-m-d')) + $time_start[0]*60*60 + $time_start[1]*60 + (time() - current_time('timestamp'));
							if(isset($time_end[1])) {
								$end_timestamp = strtotime($dt->format('Y-m-d')) + $time_end[0]*60*60 + $time_end[1]*60 + (time() - current_time('timestamp'));
							} else {
								$end_timestamp = 0;
							}
							$icsString = "
BEGIN:VCALENDAR
PRODID:-//Events Calendar//iCal4j 1.0//EN
VERSION:2.0
CALSCALE:GREGORIAN
BEGIN:VEVENT
DTSTART:".date('Ymd\THis\Z',$start_timestamp)."
DTEND:".date('Ymd\THis\Z',$end_timestamp)."
DTSTAMP:".date('Ymd\THis\Z',current_time('timestamp'))."
UID:".(uniqid())."
DESCRIPTION:".$item_value['name']."
SUMMARY:".$item_value['name']."
END:VEVENT
END:VCALENDAR";
							$file[$c] = 'MyCal_'.$c.'.ics';
							// Append a new person to the file								
							$current = $icsString;
								
							// Write the contents back to the file
							file_put_contents($file[$c], $current);	
								//$c++;
						}
					}
				} elseif ((isset($booking_settings['booking_enable_date']) && $booking_settings['booking_enable_date'] == 'on') && (isset($booking_settings['booking_enable_multiple_day']) && $booking_settings['booking_enable_multiple_day'] == 'on')) {
					$book_global_settings = json_decode(get_option('woocommerce_booking_global_settings'));
					//foreach ( $results_date as $date_key => $date_value )
					for ( $c = 0; $c < count($results_date); $c++ ) {
						if ( $results_date[$c]->post_id == $duplicate_of ) {
							$dt_start = new DateTime($results_date[$c]->start_date);
							$dt_end = new DateTime($results_date[$c]->end_date);
							$start_timestamp = strtotime($dt_start->format('Y-m-d'));
							$end_timestamp = strtotime($dt_end->format('Y-m-d'));
							$icsString = "
BEGIN:VCALENDAR
PRODID:-//Events Calendar//iCal4j 1.0//EN
VERSION:2.0
CALSCALE:GREGORIAN
BEGIN:VEVENT
DTSTART:".date('Ymd\THis\Z',$start_timestamp)."
DTEND:".date('Ymd\THis\Z',$end_timestamp)."
DTSTAMP:".date('Ymd\THis\Z',current_time('timestamp'))."
UID:".(uniqid())."
DESCRIPTION:".$item_value['name']."
SUMMARY:".$item_value['name']."
END:VEVENT
END:VCALENDAR";
							$file[$c] = 'MyCal_'.$c.'.ics';					
							// Append a new person to the file
							$current = $icsString;
								
							// Write the contents back to the file
							file_put_contents($file[$c], $current);
						}
					}
				}	
			}
			return $file;
		}
	}
?>