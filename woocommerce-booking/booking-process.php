<?php /* if(!class_exists('woocommerce_booking')){
    die();
}*/
include_once('bkap-common.php');
include_once('lang.php');
//exit;
class bkap_booking_process {

	/******************************************************
    *  This function will disable the quantity and add to cart button on the frontend,
    *  if the “Enable Booking” is ‘on’ from admin product page,and if "Purchase without choosing date" is disable.
    *************************************************************/		
		
	public static function bkap_before_add_to_cart() {
		global $post,$wpdb;
		$booking_settings = get_post_meta($post->ID, 'woocommerce_booking_settings', true);
		if ( $booking_settings != '' && (isset($booking_settings['booking_enable_date']) && $booking_settings['booking_enable_date'] == 'on') && (isset($booking_settings['booking_purchase_without_date']) && $booking_settings['booking_purchase_without_date'] != 'on')) {
		?>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery( ".single_add_to_cart_button" ).hide();
					jQuery( ".payment_type" ).hide();
					jQuery( ".quantity" ).hide();
					jQuery(".partial_message").hide();
				})
			</script>
		<?php 
		}
	}

	/**************************************************
	* This function add the Booking fields on the frontend product page as per the settings selected when Enable Booking is enabled.
    *************************************************/
			
	public static function bkap_booking_after_add_to_cart() {
		global $post, $wpdb,$woocommerce;
		$duplicate_of = get_post_meta($post->ID, '_icl_lang_duplicate_of', true);
		if($duplicate_of == '' && $duplicate_of == null) {
			$post_time = get_post($post->ID);
			$id_query = "SELECT ID FROM `".$wpdb->prefix."posts` WHERE post_date = '".$post->post_date."' ORDER BY ID LIMIT 1";
			$results_post_id = $wpdb->get_results ( $id_query );
			if( isset($results_post_id) ) {
				$duplicate_of = $results_post_id[0]->ID;
			} else {
				$duplicate_of = $post->ID;
			}
		}
		do_action('bkap_print_hidden_fields',$duplicate_of);
		$method_to_show = 'bkap_check_for_time_slot';
		$get_method = bkap_common::bkap_ajax_on_select_date();
		if(isset($get_method) && $get_method == 'multiple_time') {
			$method_to_show = apply_filters('bkap_function_slot','');
		} else {
			$method_to_show = 'bkap_check_for_time_slot';
		}
		$booking_settings = get_post_meta($duplicate_of, 'woocommerce_booking_settings', true);
		$global_settings = json_decode(get_option('woocommerce_booking_global_settings'));
		$sold_individually = get_post_meta($post->ID, '_sold_individually', true);
		print('<input type="hidden" id="wapbk_sold_individually" name="wapbk_sold_individually" value="'.$sold_individually.'">');

		//	default global settings
		if ($global_settings == '') {
			$global_settings = new stdClass();
			$global_settings->booking_date_format = 'd MM, yy';
			$global_settings->booking_time_format = '12';
			$global_settings->booking_months = '1';
		}
		//rounding settings
		$rounding = "";
		if (isset($global_settings->enable_rounding) && $global_settings->enable_rounding == "on") {
			$rounding = "yes";
        } else {
			$rounding = "no";
        }
		print('<input type="hidden" id="wapbk_round_price" name="wapbk_round_price" value="'.$rounding.'">');

		if (isset($global_settings->booking_global_selection) && $global_settings->booking_global_selection == "on"){
			$selection = "yes";
        } else {
            $selection = "no";
		}
		print('<input type="hidden" id="wapbk_global_selection" name="wapbk_global_selection" value="'.$selection.'">');
				
		if ( $booking_settings != '' ) {
			// fetch specific booking dates
			if(isset($booking_settings['booking_specific_date'])){
				$booking_dates_arr = $booking_settings['booking_specific_date'];
            } else { 
				$booking_dates_arr = array();
            }
			$booking_dates_str = "";
			if (isset($booking_settings['booking_specific_booking']) && $booking_settings['booking_specific_booking'] == "on"){
				if(!empty($booking_dates_arr)){
					foreach ($booking_dates_arr as $k => $v) {
						$booking_dates_str .= '"'.$v.'",';
					}					
                }
                $booking_dates_str = substr($booking_dates_str,0,strlen($booking_dates_str)-1);		
			}
			print('<input type="hidden" name="wapbk_booking_dates" id="wapbk_booking_dates" value=\''.$booking_dates_str.'\'>');

			if (isset($global_settings->booking_global_holidays)) {
				$book_global_holidays = $global_settings->booking_global_holidays;
				$book_global_holidays = substr($book_global_holidays,0,strlen($book_global_holidays));
				$book_global_holidays = '"'.str_replace(',', '","', $book_global_holidays).'"';
			} else {
				$book_global_holidays = "";
			}
			print('<input type="hidden" name="wapbk_booking_global_holidays" id="wapbk_booking_global_holidays" value=\''.$book_global_holidays.'\'>');
				
			$booking_holidays_string = '"'.str_replace(',', '","', $booking_settings['booking_product_holiday']).'"';
			print('<input type="hidden" name="wapbk_booking_holidays" id="wapbk_booking_holidays" value=\''.$booking_holidays_string.'\'>');
				
			//Default settings
			$default = "Y";
			if ((isset($booking_settings['booking_recurring_booking']) && $booking_settings['booking_recurring_booking'] == "on") || (isset($booking_settings['booking_specific_booking']) && $booking_settings['booking_specific_booking'] == "on")) {
				$default = "N";
			}

			foreach ($booking_settings['booking_recurring'] as $wkey => $wval) {
				if ($default == "Y") {
					print('<input type="hidden" name="wapbk_'.$wkey.'" id="wapbk_'.$wkey.'" value="on">');
				} else {
					if ($booking_settings['booking_recurring_booking'] == "on"){
						print('<input type="hidden" name="wapbk_'.$wkey.'" id="wapbk_'.$wkey.'" value="'.$wval.'">');
					} else {
						print('<input type="hidden" name="wapbk_'.$wkey.'" id="wapbk_'.$wkey.'" value="">');
					}
				}
			}

			if (isset($booking_settings['booking_time_settings'])) {
				print('<input type="hidden" name="wapbk_booking_times" id="wapbk_booking_times" value=\''.$booking_settings['booking_time_settings'].'\'>');
			} else {
				print('<input type="hidden" name="wapbk_booking_times" id="wapbk_booking_times" value="">');
			}
								
			if (isset($booking_settings['booking_minimum_number_days'])) {
				print('<input type="hidden" name="wapbk_minimumOrderDays" id="wapbk_minimumOrderDays" value="'.$booking_settings['booking_minimum_number_days'].'">');
			} else {
				print('<input type="hidden" name="wapbk_minimumOrderDays" id="wapbk_minimumOrderDays" value="">');
			}

			if (isset($booking_settings['booking_maximum_number_days'])) {
		 		print('<input type="hidden" name="wapbk_number_of_dates" id="wapbk_number_of_dates" value="'.$booking_settings['booking_maximum_number_days'].'">');
			} else {
				print('<input type="hidden" name="wapbk_number_of_dates" id="wapbk_number_of_dates" value="">');
			}

			if (isset($booking_settings['booking_enable_time'])) {
		 		print('<input type="hidden" name="wapbk_bookingEnableTime" id="wapbk_bookingEnableTime" value="'.$booking_settings['booking_enable_time'].'">');
			} else {
				print('<input type="hidden" name="wapbk_bookingEnableTime" id="wapbk_bookingEnableTime" value="">');
			}

			if (isset($booking_settings['booking_recurring_booking'])) {
		 		print('<input type="hidden" name="wapbk_recurringDays" id="wapbk_recurringDays" value="'.$booking_settings['booking_recurring_booking'].'">');
			} else {
				print('<input type="hidden" name="wapbk_recurringDays" id="wapbk_recurringDays" value="">');
			}

			if (isset($booking_settings['booking_specific_booking'])) {
		 		print('<input type="hidden" name="wapbk_specificDates" id="wapbk_specificDates" value="'.$booking_settings['booking_specific_booking'].'">');
			} else {
				print('<input type="hidden" name="wapbk_specificDates" id="wapbk_specificDates" value="">');
			} 		
		}
		//Lockout Dates
		$lockout_query = "SELECT DISTINCT start_date FROM `".$wpdb->prefix."booking_history`
								WHERE post_id='".$duplicate_of."'
								AND total_booking > 0
								AND available_booking = 0";
		$results_lockout = $wpdb->get_results ( $lockout_query );
				
		$lockout_query = "SELECT DISTINCT start_date FROM `".$wpdb->prefix."booking_history`
				WHERE post_id='".$duplicate_of."'
				AND available_booking > 0";
		$results_lock = $wpdb->get_results ( $lockout_query );
		$lockout_date = '';
		//print_r($results_lock);exit;
		/*foreach($results_lock as $key => $value) {
			$start_date = $value->start_date;
			$bookings_done = $this->get_date_lockout($start_date);
			if($bookings_done >= $booking_settings['booking_date_lockout']) {
				$lockout = explode("-",$start_date);
				$lockout_date .= '"'.intval($lockout[2])."-".intval($lockout[1])."-".$lockout[0].'",';
			}
		}
		$lockout_str = substr($lockout_date,0,strlen($lockout_date)-1);*/
		foreach ($results_lockout as $k => $v) {
			foreach($results_lock as $key => $value) {
				if ($v->start_date == $value->start_date) {
					$date_lockout = "SELECT COUNT(start_date) FROM `".$wpdb->prefix."booking_history`
												WHERE post_id='".$duplicate_of."'
												AND start_date='".$v->start_date."'
												AND available_booking = 0";
					$results_date_lock = $wpdb->get_results($date_lockout);
					if ($booking_settings['booking_date_lockout'] > $results_date_lock[0]->{'COUNT(start_date)'}) {
						unset($results_lockout[$k]);	
					}
				} 
			}
		}
		$lockout_dates_str = "";
		foreach ($results_lockout as $k => $v) {
			$lockout_temp = $v->start_date;
			$lockout = explode("-",$lockout_temp);
			$lockout_dates_str .= '"'.intval($lockout[2])."-".intval($lockout[1])."-".$lockout[0].'",';
			$lockout_temp = "";
		}
		$lockout_dates_str = substr($lockout_dates_str,0,strlen($lockout_dates_str)-1);
		$lockout_dates = $lockout_dates_str;
		print('<input type="hidden" name="wapbk_lockout_days" id="wapbk_lockout_days" value=\''.$lockout_dates.'\'>');

		$todays_date = date('Y-m-d');
		$query_date ="SELECT DATE_FORMAT(start_date,'%d-%c-%Y') as start_date,DATE_FORMAT(end_date,'%d-%c-%Y') as end_date FROM ".$wpdb->prefix."booking_history				WHERE (start_date >='".$todays_date."' OR end_date >='".$todays_date."') AND post_id = '".$duplicate_of."'";
		$results_date = $wpdb->get_results($query_date);
		$dates_new = array();
		$booked_dates = array();
		foreach($results_date as $k => $v) {
			$start_date = $v->start_date;
			$end_date = $v->end_date;
			$dates = bkap_common::bkap_get_betweendays($start_date, $end_date);
			$dates_new = array_merge($dates,$dates_new);
		}
		//Enable the start date for the booking period for checkout
		foreach ($results_date as $k => $v) {
			$start_date = $v->start_date;
			$end_date = $v->end_date;
			$new_start = strtotime("+1 day", strtotime($start_date));
			$new_start = date("d-m-Y",$new_start);
			$dates = bkap_common::bkap_get_betweendays($new_start, $end_date);
			$booked_dates = array_merge($dates,$booked_dates);
		}
		$dates_new_arr = array_count_values($dates_new);
		$booked_dates_arr = array_count_values($booked_dates);
		$lockout = "";
		if (isset($booking_settings['booking_date_lockout'])) {
			$lockout = $booking_settings['booking_date_lockout'];
		}
		$new_arr_str = '';
		foreach($dates_new_arr as $k => $v) {
			if($v >= $lockout && $lockout != 0) {
				$date_temp = $k;
				$date = explode("-",$date_temp);
				$new_arr_str .= '"'.intval($date[0])."-".intval($date[1])."-".$date[2].'",';
				$date_temp = "";
			}
		}
		$new_arr_str = substr($new_arr_str,0,strlen($new_arr_str)-1);
		print("<input type='hidden' id='wapbk_hidden_booked_dates' name='wapbk_hidden_booked_dates' value='".$new_arr_str."'/>");

		//checkout calendar booked dates
		$blocked_dates = array();
		$booked_dates_str = "";
		foreach ($booked_dates_arr as $k => $v) {
			if($v >= $lockout && $lockout != 0) {
				$date_temp = $k;
				$date = explode("-",$date_temp);
				$date_without_zero_prefixed = intval($date[0])."-".intval($date[1])."-".$date[2];
				$booked_dates_str .= '"'.intval($date[0])."-".intval($date[1])."-".$date[2].'",';
				$date_temp = "";
				$blocked_dates[] = $date_without_zero_prefixed;
			}
		}
		if (isset($booked_dates_str)) {
			$booked_dates_str = substr($booked_dates_str,0,strlen($booked_dates_str)-1);
		} else {
			$booked_dates_str = "";
		}
		print("<input type='hidden' id='wapbk_hidden_booked_dates_checkout' name='wapbk_hidden_booked_dates_checkout' value='".$booked_dates_str."'/>");
				
		if(isset($booking_settings['booking_recurring'])) {
			$recurring_date = $booking_settings['booking_recurring'];
        } else {
			$recurring_date = array();
        }

		if(isset($booking_settings['booking_specific_date'])) {
			$specific_date = $booking_settings['booking_specific_date'];
        } else {
			$specific_date = array();
        }
		
		if(isset($booking_settings['booking_product_holiday'])) {
            $holiday_array = explode(',',$booking_settings['booking_product_holiday']);
        }

		if(isset($global_settings->booking_global_holidays)) {
			$global_holidays = explode(',',$global_settings->booking_global_holidays);
		} else {
			$global_holidays = array();
		}

		$current_date = date('d-m-Y');
		$current_day = date('N',strtotime($current_date)); 
		if(isset($booking_settings['booking_minimum_number_days'])) {
			$min_date = date('j-n-Y', strtotime('+'.$booking_settings['booking_minimum_number_days'].' day',strtotime($current_date)));
        } else {
			$min_date = '';
        }

		$i = 0;
        if(isset($specific_date) && $specific_date != '') {
			foreach($specific_date as $key => $val) {
				$min_specific = date('j-n-Y',min(array_map('strtotime', $specific_date)));
				if(strtotime($min_specific) < strtotime($specific_date[$i])) {
					unset($specific_date[$i]);
					if(in_array($min_specific, $holiday_array) || in_array($min_specific,$global_holidays)) {
						unset($specific_date[array_search($min_specific,$specific_date)]);
                    }
                }
                $i++;
			}
		}
		
		$first_enable_day = '';
		$default_date = '';
		$min_day = date('N',strtotime($min_date)); 
		$default_date_recurring = $min_date;
		if(isset($booking_settings['booking_enable_multiple_day']) && $booking_settings['booking_enable_multiple_day'] != 'on' && isset($booking_settings['booking_recurring_booking']) && $booking_settings['booking_recurring_booking'] == 'on') {          
			for($i = 0;; $i++) {
				if(isset($recurring_date['booking_weekday_'.$min_day]) && $recurring_date['booking_weekday_'.$min_day] == 'on') {
					if(in_array($default_date_recurring, $holiday_array) || in_array($default_date_recurring,$global_holidays)) {
						if($min_day < 6) {
							$min_day = $min_day + 1;
						} else {
							$min_day = $min_day - $min_day;
						}
						$default_date_recurring = date('j-n-Y', strtotime('+1day',strtotime($default_date_recurring)));
					} else {
						break;
					}
				} else {
					if($min_day < 6) {
						$min_day = $min_day + 1;
					} else {
						$min_day = $min_day - $min_day;
					}
					$default_date_recurring =  date('j-n-Y', strtotime('+1day',strtotime($default_date_recurring)));
				}
			}
		}
				
		if($first_enable_day != '' && $booking_settings['booking_recurring_booking'] == 'on' && $booking_settings['booking_specific_booking'] == 'on') {
			$default_date_recurring= date('d-m-Y', strtotime('+'.$first_enable_day.' day',strtotime($min_date)));
			if(strtotime($default_date_recurring) < strtotime($min_specific)) {
				$default_date  = $default_date_recurring;
			} else {
				$default_date = $min_specific;
            }
		} else if(isset($booking_settings['booking_specific_booking']) && $booking_settings['booking_specific_booking'] == 'on' && $booking_settings['booking_recurring_booking'] != 'on') {
			$default_date = $min_specific;
        } else if(isset($booking_settings['booking_recurring_booking']) && $booking_settings['booking_recurring_booking'] == 'on' && $booking_settings['booking_specific_booking'] != 'on') {
			$default_date  = $default_date_recurring;
        }
		print("<input type='hidden' id='wapbk_hidden_default_date' name='wapbk_hidden_default_date' value='".$default_date."'/>");
                                
		if ( isset($booking_settings['booking_enable_date']) && $booking_settings['booking_enable_date'] == 'on' ) {
			print ('<label style="margin-top:5em;">'.get_option("book.date-label").': </label><input type="text" id="booking_calender" name="booking_calender" class="booking_calender" style="cursor: text!important;" readonly/>
							<img src="'.plugins_url().'/woocommerce-booking/images/cal.gif" width="20" height="20" style="cursor:pointer!important;" id ="checkin_cal"/><div id="inline_calendar"></div>');
			$options_checkin = $options_checkout = array();
			$options_checkin_calendar = '';
			if (isset($booking_settings['booking_enable_multiple_day']) && $booking_settings['booking_enable_multiple_day'] == 'on') {
				print ('<label>'.get_option("checkout.date-label").': </label><input type="text" id="booking_calender_checkout" name="booking_calender_checkout" class="booking_calender" style="cursor: text!important;" readonly/>
									<img src="'.plugins_url().'/woocommerce-booking/images/cal.gif" width="20" height="20" style="cursor:pointer!important;" id ="checkout_cal"/><div id="inline_calendar_checkout"></div>');
				if (isset($booking_settings['enable_inline_calendar']) && $booking_settings['enable_inline_calendar'] == 'on') {
					$options_checkout[] = "minDate: 1";
					$options_checkin_calendar = 'jQuery("#inline_calendar").datepicker("option", "onSelect",function(date,inst)  {
						var monthValue = inst.selectedMonth+1;
						var dayValue = inst.selectedDay;
						var yearValue = inst.selectedYear;
						var current_dt = dayValue + "-" + monthValue + "-" + yearValue;
						if(jQuery("#wapbk_same_day").val() == "on") {
							if (current_dt != "") {
								var split = current_dt.split("-");
								split[1] = split[1] - 1;
								var minDate = new Date(split[2],split[1],split[0]);
								minDate.setDate(minDate.getDate());
								jQuery( "#inline_calendar_checkout" ).datepicker( "option", "minDate", minDate);
							}
						} else {	
							if (current_dt != "") {
								var split = current_dt.split("-");
								split[1] = split[1] - 1;
								var minDate = new Date(split[2],split[1],split[0]);
								minDate.setDate(minDate.getDate() + 1);
								jQuery( "#inline_calendar_checkout" ).datepicker( "option", "minDate", minDate);
							}
						}
						jQuery("#wapbk_hidden_date").val(current_dt); });';
					$options_checkout[] = "onSelect: bkap_get_per_night_price";
					$options_checkin[] = "onSelect: bkap_set_checkin_date";
					$options_checkout[] = "beforeShowDay: bkap_check_booked_dates";
					$options_checkin[] = "beforeShowDay: bkap_check_booked_dates";
				} else {
					$options_checkout[] = "minDate: 1";
					$options_checkin[] = 'onClose: function( selectedDate ) {
						if (jQuery("#block_option_enabled").val()=="on") {
							var nod= parseInt(jQuery("#block_option_number_of_day").val(),10);										
							if (jQuery("#wapbk_hidden_date").val() != "") {
								var num_of_day= jQuery("#block_option_number_of_day").val();
								var split = jQuery("#wapbk_hidden_date").val().split("-");
								split[1] = split[1] - 1;		
								var minDate = new Date(split[2],split[1],split[0]);	
								minDate.setDate(minDate.getDate() + nod ); 
								jQuery("#booking_calender_checkout").datepicker("setDate",minDate);
								bkap_calculate_price();
							}
						} else {
							if (jQuery("#wapbk_hidden_date").val() != "") {				
								if(jQuery("#wapbk_same_day").val() == "on") {
									if (jQuery("#wapbk_hidden_date").val() != "") {				
										var split = jQuery("#wapbk_hidden_date").val().split("-");
										split[1] = split[1] - 1;		
										var minDate = new Date(split[2],split[1],split[0]);
										minDate.setDate(minDate.getDate()); 
										jQuery( "#booking_calender_checkout" ).datepicker( "option", "minDate", minDate);
									}
									} else {
										var split = jQuery("#wapbk_hidden_date").val().split("-");
										split[1] = split[1] - 1;		
										var minDate = new Date(split[2],split[1],split[0]);
										minDate.setDate(minDate.getDate() + 1); 
										jQuery( "#booking_calender_checkout" ).datepicker( "option", "minDate", minDate);
									}
								}
							}
						}';
						$options_checkout[] = "onSelect: bkap_get_per_night_price";
						$options_checkin[] = "onSelect: bkap_set_checkin_date";
						$options_checkout[] = "beforeShowDay: bkap_check_booked_dates";
						$options_checkin[] = "beforeShowDay: bkap_check_booked_dates";
					}
				} else {
					$options_checkin[] = "beforeShowDay: bkap_show_book";
					$options_checkin[] = "onSelect: bkap_show_times";
				}
				$options_checkin_str = '';
				if (count($options_checkin) > 0) {
					$options_checkin_str = implode(',', $options_checkin);
				}
				$options_checkout_str = '';
				if (count($options_checkout) > 0){
					$options_checkout_str = implode(',', $options_checkout);
				}
				$product = get_product($post->ID);
				$product_type = $product->product_type;
				$attribute_change_var = '';
				if ($product_type == 'variable'){
					$variations = $product->get_available_variations();
					$attributes = $product->get_variation_attributes();
					$attribute_fields_str = "";
					$attribute_name = "";
					$attribute_value = "";
					$attribute_value_selected = "";
					$attribute_fields = array();
					$i = 0;
					foreach ($variations as $var_key => $var_val) {
						foreach ($var_val['attributes'] as $a_key => $a_val) {
							if (!in_array($a_key, $attribute_fields)) {
								$attribute_fields[] = $a_key;
								$attribute_fields_str .= ",\"$a_key\": jQuery(\"[name='$a_key']\").val() ";
								$key = str_replace("attribute_","",$a_key);
								$attribute_value .= "attribute_values =  attribute_values + '|' + jQuery('#".$key."').val();";
								$attribute_value_selected .= "attribute_selected =  attribute_selected + '|' + jQuery('#".$key." :selected').text();";
								$on_change_attributes[] = $a_key;
							}
							$i++;
						}
					}
					$on_change_attributes_str = implode(',#',$on_change_attributes);
					$on_change_attributes_str = settype(str_replace("attribute_","",$on_change_attributes_str),'string');
					$attribute_change_var = 'jQuery(document).on("change",jQuery("#'.$on_change_attributes_str.'"),function() {
						if (jQuery("#wapbk_hidden_date").val() != "" && jQuery("#wapbk_hidden_date_checkout").val() != "") bkap_calculate_price();
					});';
					print("<input type='hidden' id='wapbk_hidden_booked_dates' name='wapbk_hidden_booked_dates'/>");					
					print("<input type='hidden' id='wapbk_hidden_booked_dates_checkout' name='wapbk_hidden_booked_dates_checkout'/>");
				} elseif ($product_type == 'simple') {
					$attribute_fields_str = ",\"tyche\": 1";
				}
				$js_code = $blocked_dates_hidden_var = '';
				$block_dates = array();
				$block_dates = (array) apply_filters( 'bkap_block_dates', $post->ID , $blocked_dates );
				if (isset($block_dates) && count($block_dates) > 0) {
					$i = 1;
					$bvalue = array();
					$add_day = '';
					$same_day = '';
					$date_label = '';
					foreach ($block_dates as $bkey => $bvalue) {
						if (isset($bvalue['dates']) && count($bvalue['dates']) > 0){
							$blocked_dates_str = '"'.implode('","', $bvalue['dates']).'"';
                        } else {
							$blocked_dates_str = "";
                        }
						$field_name = $i;
						if ( ( isset($bvalue['field_name']) && $bvalue['field_name'] != '' ) ) {
							$field_name = $bvalue['field_name'];
						}
						$fld_name = 'woobkap_'.str_replace(' ','_', $field_name);
						$blocked_dates_hidden_var .= "<input type='hidden' id='".$fld_name."' name='".$fld_name."' value='".$blocked_dates_str."'/>";
						$i++;
						if(isset($bvalue['add_days_to_charge_booking'])){
							$add_day = $bvalue['add_days_to_charge_booking'];
						}
						if($add_day == '') {
							$add_day = 0;
						}
						if(isset($bvalue['same_day_booking'])) {
							$same_day = $bvalue['same_day_booking'];
                        } else {
							$same_day = '';
                        }
						print("<input type='hidden' id='wapbk_same_day' name='wapbk_same_day' value='".$same_day."'/>");
					}
					if (isset($bvalue['date_label']) && $bvalue['date_label'] != '') {
						$date_label = $bvalue['date_label'];
                    } else {
						$date_label = 'Unavailable for Booking';
                    }
					$js_code = 'var '.$fld_name.' = eval("["+jQuery("#'.$fld_name.'").val()+"]");
						for (i = 0; i < '.$fld_name.'.length; i++) {
							if( jQuery.inArray(d + "-" + (m+1) + "-" + y,'.$fld_name.') != -1 ) {
								return [false, "", "'.$date_label.'"];
							}
						}';
					$js_block_date  = '
						var '.$fld_name.' = eval("["+jQuery("#'.$fld_name.'").val()+"]");
						var date = new_end = new Date(CheckinDate);
						var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
						for (var i = 1; i<= count;i++) {
							if( jQuery.inArray(d + "-" + (m+1) + "-" + y,'.$fld_name.') != -1 ) {
								jQuery("#wapbk_hidden_date_checkout").val("");
								jQuery("#booking_calender_checkout").val("");
								jQuery( ".single_add_to_cart_button" ).hide();
								jQuery( ".quantity" ).hide();
								CalculatePrice = "N";
								alert("Some of the dates in the selected range are on rent. Please try another date range.");
								break;
							}
							new_end = new Date(ad(new_end,1));
							var m = new_end.getMonth(), d = new_end.getDate(), y = new_end.getFullYear();
						}';
				}
						
				print ('<div id="show_time_slot" name="show_time_slot" class="show_time_slot"> </div>
						<input type="hidden" id="total_price_calculated" name="total_price_calculated"/>
						<input type="hidden" id="wapbk_multiple_day_booking" name="wapbk_multiple_day_booking" value="'.$booking_settings['booking_enable_multiple_day'].'"/>');
				
				if (!isset($booking_settings['booking_enable_multiple_day'])) {
					do_action('bkap_display_price_div',$post->ID);
				}
						
				if (isset($booking_settings['booking_enable_multiple_day']) && $booking_settings['booking_enable_multiple_day'] != "on") {
					$type_of_slot = apply_filters('bkap_slot_type',$post->ID);
					if(isset($type_of_slot) && $type_of_slot != 'multiple') {
						do_action('bkap_display_price_div',$post->ID);
					}
					$currency_symbol = get_woocommerce_currency_symbol();
					$addon_price = 'var data = {
							id: '.$duplicate_of.',
							details: jQuery("#wapbk_hidden_date").val(),
							action: "bkap_call_addon_price"
							'.$attribute_fields_str.'
						};
						jQuery.post("'.get_admin_url().'/admin-ajax.php", data, function(amt) {
							if (jQuery("#wapbk_round_price").val() == "yes") {
								var price = Math.round(amt);
							} else{
								var price = parseFloat(amt).toFixed(2);
							}
							jQuery("#show_addon_price").html("'.$currency_symbol.'" + price);
						});';
					if ($product_type == 'variable') {
						$attribute_change_single_day_var = 'jQuery(document).on("change",jQuery("#'.$on_change_attributes_str.'"),function()
							{
								if (jQuery("#wapbk_hidden_date").val() != "")  {
								'.$addon_price.'
							}});';
					} else {
						$attribute_change_single_day_var = '';
					}
					$do_slot = 'jQuery("input[name=\"bkap_timeslot[]\"]").change(function() {
						var seasonal = jQuery("#seasonal").val();
						if(seasonal == "yes") {
							var adjustment = eval("["+jQuery("#adjustment").val()+"]");
							var value = jQuery("#adjustment_amount_or_percent").val();
							var operator = jQuery("#adjustment_operator").val();
							var operator_array = operator.split(",");
							var value_array = value.split(",");
							var count_value = adjustment.length;
						}
						var length = jQuery("group1 input[type=checkbox]:checked").length;
						var id = this.id;
						var product_price = parseInt(jQuery("#wapbk_price").val());
						var symbol = jQuery("#wapbk_symbol").val();
						var price = jQuery("#show_price").html();
							price = price.replace(symbol,"");
						var new_price = parseInt(price);
						var sold_individually = jQuery("#wapbk_sold_individually").val();
						if(jQuery("input[name=\"bkap_timeslot[]\"]:checked").length > 0) {
							jQuery( ".single_add_to_cart_button" ).show();
							if(sold_individually == "yes") {
								jQuery( ".quantity" ).hide();
							} else {
								jQuery( ".quantity" ).show();
							}
						} else {
							jQuery( ".single_add_to_cart_button" ).hide();
							jQuery( ".quantity" ).hide();
						}
						if ( jQuery("#"+ id).is(":checked")) {
							if(seasonal == "yes") {
								var price_new = new_price + product_price;
								for(var i=0;i<count_value;i++) {
									if(value_array[i] == "percent") {
										adjustment[i] = adjustment[i] * product_price;
										if(operator_array[i] == "add") {
											price_new = price_new + adjustment[i];
										} else if(operator_array[i] == "subtract") {
											price_new = price_new + adjustment[i];
										}
									} else if(value_array[i] == "amount") {
										if(operator_array[i] == "add") {
											price_new = price_new + adjustment[i];
										} else {
											price_new = price_new + adjustment[i];
										}								
									} else {
										var price_new = new_price + product_price;
									}
								}	
							} else {
								var price_new = new_price + product_price;
							}
						} else {
							if(seasonal == "yes") {
								var price_new = new_price - product_price;
								for(var i=0;i<count_value;i++) {
									if(value_array[i] == "percent") {
										adjustment[i] = adjustment[i] * product_price;
										if(operator_array[i] == "add") {
											price_new = price_new - adjustment[i];
										} else if(operator_array[i] == "subtract") {
											price_new = price_new - adjustment[i];
										}
									} else if(value_array[i] == "amount") {
										if(operator_array[i] == "add") {
											price_new = price_new - adjustment[i];
										} else {
											price_new = price_new - adjustment[i];
										}								
									} else{
										var price_new = new_price - product_price;
									}
								}	
							} else {
								var price_new = new_price - product_price;
							}
						}
						jQuery("#show_price").html(symbol+" "+price_new);
						jQuery("#wapbk_hidden_price").val(price_new);
						});';
					$quantity_change_var = '';
				} else {
					$addon_price = "";
					$attribute_change_single_day_var = "";
					$do_slot = "";
					$currency_symbol = get_woocommerce_currency_symbol();
					print("<input type='hidden' id='wapbk_currency' name='wapbk_currency' value='".$currency_symbol."'/>");
					$quantity_change_var =  'jQuery("form.cart").on("change", "input.qty", function(){
						bkap_calculate_price();
					});';
				}
				
				$day_selected = "";
				if(isset($global_settings->booking_calendar_day))  {
					$day_selected = $global_settings->booking_calendar_day;
				} else {
					$day_selected = get_option("start_of_week");
				}
					
				if (isset($booking_settings['enable_inline_calendar']) && $booking_settings['enable_inline_calendar'] == 'on'){
					$current_language = json_decode(get_option('woocommerce_booking_global_settings'));
                    if (isset($current_language)) {
						$curr_lang = $current_language->booking_language;
                    } else {
						$curr_lang = "en-GB";
					}
					$hidden_date = '';
					$hidden_date_checkout = '';
					global $bkap_block_booking;
					$number_of_fixed_price_blocks = $bkap_block_booking->bkap_get_fixed_blocks_count($post->ID);

					if (isset($booking_settings['booking_partial_payment_enable']) && $booking_settings['booking_partial_payment_enable'] =='yes' && $booking_settings['booking_partial_payment_radio'] == 'value' && is_plugin_active('bkap-deposits/deposits.php') && !isset($booking_settings['booking_fixed_block_enable']) && !isset($booking_settings['booking_block_price_enable'])) {
						$price_value = 'if(sold_individually == "yes") {
								var total_price = parseFloat(response);
							} else {
								var total_price = parseFloat(response) * parseInt(quantity);
							}';
					}
					elseif (isset($booking_settings['booking_partial_payment_enable']) && $booking_settings['booking_partial_payment_enable'] =='yes' && $booking_settings['booking_partial_payment_radio']=='percent' && is_plugin_active('bkap-deposits/deposits.php') && !isset($booking_settings['booking_fixed_block_enable']) && !isset($booking_settings['booking_block_price_enable'])) {
						$price_value = 'if(sold_individually == "yes") {
								var total_price = parseInt(diffDays) * parseFloat(response);
							} else {
								var total_price = parseInt(diffDays) * parseFloat(response) * parseInt(quantity);
							}';
					}
					elseif (isset($booking_settings['booking_fixed_block_enable']) && $booking_settings['booking_fixed_block_enable'] =='yes' && (isset($number_of_fixed_price_blocks) && $number_of_fixed_price_blocks > 0)) {
						$price_value = 'if(sold_individually == "yes") {
								var total_price = parseFloat(response);
							} else {
								var total_price = parseFloat(response) * parseInt(quantity);
							}';
					}
					else {
						$price_value = 'if(sold_individually == "yes") {
								var total_price = parseInt(diffDays) * parseFloat(response);
							} else {
								var total_price = parseInt(diffDays) * parseFloat(response) * parseInt(quantity);
							}';
					}
					
					if (isset($global_settings->booking_global_selection) && $global_settings->booking_global_selection == "on") {
						foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
							if(array_key_exists('booking',$values)) {
								$booking = $values['booking'];
								$hidden_date = $booking[0]['hidden_date'];
								if(array_key_exists("hidden_date_checkout",$booking[0])) {
									$hidden_date_checkout = $booking[0]['hidden_date_checkout'];
								}
							}
							break;
						}
					}
					
					print('<input type="hidden" id="wapbk_hidden_date" name="wapbk_hidden_date" value="'.$hidden_date.'"/>
						<input type="hidden" id="wapbk_hidden_date_checkout" name="wapbk_hidden_date_checkout" value="'.$hidden_date_checkout.'"/>
						<input type="hidden" id="wapbk_diff_days" name="wapbk_diff_days" />
						'.$blocked_dates_hidden_var.'
						<div id="ajax_img" name="ajax_img"> <img src="'.plugins_url().'/woocommerce-booking/images/ajax-loader.gif"> </div>
						
						<script type="text/javascript">
							jQuery( "#ajax_img" ).hide();
							jQuery(document).ready(function() {
								'.$attribute_change_var.' 
								'.$quantity_change_var.'
								'.$attribute_change_single_day_var.' 
								var formats = ["d.m.y", "d-m-yy","MM d, yy"];
								var split = jQuery("#wapbk_hidden_default_date").val().split("-");
								split[1] = split[1] - 1;		
								var default_date = new Date(split[2],split[1],split[0]);
								jQuery.extend(jQuery.datepicker, { afterShow: function(event) {
									jQuery.datepicker._getInst(event.target).dpDiv.css("z-index", 9999);
								}});
								jQuery(function() {
									jQuery("#inline_calendar").datepicker({
										beforeShow: avd,
										defaultDate: default_date,
										minDate:jQuery("#wapbk_minimumOrderDays").val(),
										maxDate:jQuery("#wapbk_number_of_dates").val(),
										altField: "#booking_calender",
										dateFormat: "'.$global_settings->booking_date_format.'",
										numberOfMonths: parseInt('.$global_settings->booking_months.'),
										'.$options_checkin_str.' ,
									}).focus(function (event){
										jQuery.datepicker.afterShow(event);
								});
								if(jQuery("#wapbk_global_selection").val() == "yes" && jQuery("#block_option_enabled").val() != "on") {
									var split = jQuery("#wapbk_hidden_date").val().split("-");
									split[1] = split[1] - 1;		
									var CheckinDate = new Date(split[2],split[1],split[0]);
									var timestamp = Date.parse(CheckinDate); 
									if (isNaN(timestamp) == false) { 
										var default_date_selection = new Date(timestamp);
										jQuery("#inline_calendar").datepicker("setDate",default_date_selection);
									}
								}
								jQuery("#inline_calendar").datepicker("option",jQuery.datepicker.regional[ "'.$curr_lang.'" ]);
								jQuery("#inline_calendar").datepicker("option", "dateFormat","'.$global_settings->booking_date_format.'");
								jQuery("#inline_calendar").datepicker("option", "firstDay","'.$day_selected.'");
								'.$options_checkin_calendar.'
							});
							jQuery("#ui-datepicker-div").wrap("<div class=\"hasDatepicker\"></div>");');
				} else {
					global $bkap_block_booking;
					$number_of_fixed_price_blocks = $bkap_block_booking->bkap_get_fixed_blocks_count($post->ID);
					if (isset($booking_settings['booking_partial_payment_enable']) && $booking_settings['booking_partial_payment_enable'] =='yes' && $booking_settings['booking_partial_payment_radio'] == 'value' && is_plugin_active('bkap-deposits/deposits.php') && !isset($booking_settings['booking_fixed_block_enable']) && !isset($booking_settings['booking_block_price_enable'])) {
						$price_value = 'if(sold_individually == "yes"){
								var total_price = parseFloat(response);
							} else {
								var total_price = parseFloat(response) * parseInt(quantity);
						}';
					}
					elseif (isset($booking_settings['booking_partial_payment_enable']) && $booking_settings['booking_partial_payment_enable'] =='yes' && $booking_settings['booking_partial_payment_radio']=='percent' && is_plugin_active('bkap-deposits/deposits.php') && !isset($booking_settings['booking_fixed_block_enable']) && !isset($booking_settings['booking_block_price_enable'])) {
						$price_value = 'if(sold_individually == "yes"){
							var total_price = parseInt(diffDays) * parseFloat(response);
						} else{
							var total_price = parseInt(diffDays) * parseFloat(response) * parseInt(quantity);
						}';
					}
					elseif (isset($booking_settings['booking_fixed_block_enable']) && $booking_settings['booking_fixed_block_enable'] =='yes' && (isset($number_of_fixed_price_blocks) && $number_of_fixed_price_blocks > 0)) {
						$price_value = 'if(sold_individually == "yes"){
								var total_price = parseFloat(response);
							} else {
								var total_price = parseFloat(response) * parseInt(quantity);
							}';
					}
					else {
						$price_value = 'if(sold_individually == "yes"){
    							var total_price = parseInt(diffDays) * parseFloat(response);
							} else {
								var total_price = parseInt(diffDays) * parseFloat(response) * parseInt(quantity);
							}';
					}
					
					$hidden_date = '';
					$hidden_date_checkout = '';
					if (isset($global_settings->booking_global_selection) && $global_settings->booking_global_selection == "on") {
						foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
							if(array_key_exists('booking',$values)) {
								$booking = $values['booking'];
								$hidden_date = $booking[0]['hidden_date'];
								if(array_key_exists("hidden_date_checkout",$booking[0])){
									$hidden_date_checkout = $booking[0]['hidden_date_checkout'];
								}
							}
							break;
						}
					}
					print('<input type="hidden" id="wapbk_hidden_date" name="wapbk_hidden_date" value="'.$hidden_date.'"/>
							<input type="hidden" id="wapbk_hidden_date_checkout" name="wapbk_hidden_date_checkout" value="'.$hidden_date_checkout.'"/>
							<input type="hidden" id="wapbk_diff_days" name="wapbk_diff_days" />
							'.$blocked_dates_hidden_var.'
							<div id="ajax_img" name="ajax_img"> <img src="'.plugins_url().'/woocommerce-booking/images/ajax-loader.gif"> </div>

							<script type="text/javascript">
								jQuery( "#ajax_img" ).hide();
								jQuery(document).ready(function() {
									'.$attribute_change_var.' 
									'.$quantity_change_var.'
									'.$attribute_change_single_day_var.' 
									var formats = ["d.m.y", "d-m-yy","MM d, yy"];
							        var split = jQuery("#wapbk_hidden_default_date").val().split("-");
								    split[1] = split[1] - 1;		
								    var default_date = new Date(split[2],split[1],split[0]);
									jQuery.extend(jQuery.datepicker, { afterShow: function(event){
										jQuery.datepicker._getInst(event.target).dpDiv.css("z-index", 9999);
									}});
									jQuery("#booking_calender").datepicker({
										beforeShow: avd,
                                        defaultDate: default_date,
										dateFormat: "'.$global_settings->booking_date_format.'",
										numberOfMonths: parseInt('.$global_settings->booking_months.'),
										firstDay: parseInt('.$day_selected.'),
										'.$options_checkin_str.' ,
									}).focus(function (event){
										jQuery.datepicker.afterShow(event);
									});
                                                                        
									if(jQuery("#wapbk_global_selection").val() == "yes" && jQuery("#block_option_enabled").val() != "on") {
										var split = jQuery("#wapbk_hidden_date").val().split("-");
										split[1] = split[1] - 1;		
										var CheckinDate = new Date(split[2],split[1],split[0]);
										var timestamp = Date.parse(CheckinDate); 
										if (isNaN(timestamp) == false) { 
											var default_date = new Date(timestamp);
											jQuery("#booking_calender").datepicker("setDate",default_date);
										}
									}
								jQuery("#ui-datepicker-div").wrap("<div class=\"hasDatepicker\"></div>");
								jQuery("#checkin_cal").click(function() {
									jQuery("#booking_calender").datepicker("show");
								});');
					}
					//from here
					if ($booking_settings['booking_enable_multiple_day'] == 'on'){
						if (isset($booking_settings['enable_inline_calendar']) && $booking_settings['enable_inline_calendar'] == 'on') {
							print ('jQuery(document).ready(function(){
									jQuery("#inline_calendar_checkout").datepicker({
										dateFormat: "'.$global_settings->booking_date_format.'",
										numberOfMonths: parseInt('.$global_settings->booking_months.'),
										'.$options_checkout_str.' ,
										altField: "#booking_calender_checkout",
										onClose: function( selectedDate ) {
											jQuery( "#inline_calendar" ).datepicker( "option", "maxDate", selectedDate );
										},
										}).focus(function (event){
											jQuery.datepicker.afterShow(event);
										});
										if(jQuery("#wapbk_global_selection").val() == "yes" && jQuery("#block_option_enabled").val() != "on") {
											var split = jQuery("#wapbk_hidden_date_checkout").val().split("-");
											split[1] = split[1] - 1;		
											var CheckoutDate = new Date(split[2],split[1],split[0]);
											var timestamp = Date.parse(CheckoutDate);
											if (isNaN(timestamp) == false)  { 
												var default_date = new Date(timestamp);
												jQuery("#inline_calendar_checkout").datepicker("setDate",default_date);
												bkap_calculate_price();
											}
										}
										jQuery("#checkout_cal").click(function() {
										jQuery("#inline_calendar_checkout").datepicker("show");
								});
								jQuery("#inline_calendar_checkout").datepicker("option", "firstDay","'.$day_selected.'");
							});
							');
						}else {
							print ('jQuery("#booking_calender_checkout").datepicker({
								dateFormat: "'.$global_settings->booking_date_format.'",
								numberOfMonths: parseInt('.$global_settings->booking_months.'),
								firstDay: '.$day_selected.',
								'.$options_checkout_str.' , 
								onClose: function( selectedDate ) {
									jQuery( "#booking_calender" ).datepicker( "option", "maxDate", selectedDate );
								},
								}).focus(function (event){
									jQuery.datepicker.afterShow(event);
								}); 
								if(jQuery("#wapbk_global_selection").val() == "yes" && jQuery("#block_option_enabled").val() != "on") {
									var split = jQuery("#wapbk_hidden_date_checkout").val().split("-");
									split[1] = split[1] - 1;		
									var CheckoutDate = new Date(split[2],split[1],split[0]);
									var timestamp = Date.parse(CheckoutDate);
									if (isNaN(timestamp) == false) { 
										var default_date = new Date(timestamp);
										jQuery("#booking_calender_checkout").datepicker("setDate",default_date);
										bkap_calculate_price();
									}
								}
								jQuery("#checkout_cal").click(function() {
								jQuery("#booking_calender_checkout").datepicker("show");
							});');
						}
					}
					
					$currency_symbol = get_woocommerce_currency_symbol();
					print('});
						//**********************************************
                        // This function disables the dates in the calendar for holidays, global holidays set and for which lockout is reached for Multiple day booking feature.
                        //***************************************************

						function bkap_check_booked_dates(date) {
							var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
							var holidayDates = eval("["+jQuery("#wapbk_booking_holidays").val()+"]");
							var globalHolidays = eval("["+jQuery("#wapbk_booking_global_holidays").val()+"]");
							var bookedDates = eval("["+jQuery("#wapbk_hidden_booked_dates").val()+"]");	
							var bookedDatesCheckout = eval("["+jQuery("#wapbk_hidden_booked_dates_checkout").val()+"]");
							var block_option_start_day= jQuery("#block_option_start_day").val();
						 	var block_option_price= jQuery("#block_option_price").val();
							for (iii = 0; iii < globalHolidays.length; iii++) {
								if( jQuery.inArray(d + "-" + (m+1) + "-" + y,globalHolidays) != -1 ) {
									return [false, "", "'.__("Holiday","woocommerce-booking").'"];
								}
							}
							for (ii = 0; ii < holidayDates.length; ii++) {
								if( jQuery.inArray(d + "-" + (m+1) + "-" + y,holidayDates) != -1 ) {
									return [false, "","'.__("Holiday","woocommerce-booking").'"];
								}
							}
							var id_booking = jQuery(this).attr("id");
							if (id_booking == "booking_calender" || id_booking == "inline_calendar") {
								for (iii = 0; iii < bookedDates.length; iii++) {
									//alert(bookedDates);
									if( jQuery.inArray(d + "-" + (m+1) + "-" + y,bookedDates) != -1 ){
										return [false, "", "'.__("Unavailable for Booking","woocommerce-booking").'"];
									}
								}
							}
							if (id_booking == "booking_calender_checkout" || id_booking == "inline_calendar_checkout") {
								for (iii = 0; iii < bookedDatesCheckout.length; iii++) {
									//alert(bookedDates);
									if( jQuery.inArray(d + "-" + (m+1) + "-" + y,bookedDatesCheckout) != -1 ) {
										return [false, "", "'.__("Unavailable for Booking","woocommerce-booking").'"];
									}
								}
							}
							var block_option_enabled= jQuery("#block_option_enabled").val();

							if (block_option_enabled =="on") {
								if ( id_booking == "booking_calender" || id_booking == "inline_calendar" ) {
									if (block_option_start_day == date.getDay()) {
										return [true];
									} else {
					            		return [false];
									}
								}
								var bcc_date=jQuery( "#booking_calender_checkout").datepicker("getDate");
								var dd = bcc_date.getDate();
								var mm = bcc_date.getMonth()+1; //January is 0!
								var yyyy = bcc_date.getFullYear();
								var checkout = dd + "-" + mm + "-"+ yyyy;
								jQuery("#wapbk_hidden_date_checkout").val(checkout);

				       			if (id_booking == "booking_calender_checkout" || id_booking == "inline_calendar_checkout") {
									if (Date.parse(bcc_date) === Date.parse(date)){
				       					return [true];
									} else{
				       					return [false];
				       				}
				       			}
				       		}
							'.$js_code.' 
							return [true];
						}
                        
						// ***************************************************
                        //This function disables the dates in the calendar for holidays, global holidays set and for which lockout is reached for Single day booking	feature.
						//***********************************
						
						function bkap_show_book(date){
							var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
							// .html() is used when we have zip code groups enabled
							var deliveryDates = eval("["+jQuery("#wapbk_booking_dates").val()+"]");	
							var holidayDates = eval("["+jQuery("#wapbk_booking_holidays").val()+"]");
							var globalHolidays = eval("["+jQuery("#wapbk_booking_global_holidays").val()+"]");
						
							//Lockout Dates
							var lockoutdates = eval("["+jQuery("#wapbk_lockout_days").val()+"]");
							var bookedDates = eval("["+jQuery("#wapbk_hidden_booked_dates").val()+"]");
							var dt = new Date();
							var today = dt.getMonth() + "-" + dt.getDate() + "-" + dt.getFullYear();
							for (iii = 0; iii < lockoutdates.length; iii++) {
								if( jQuery.inArray(d + "-" + (m+1) + "-" + y,lockoutdates) != -1 ) {
									return [false, "", "'.__("Booked","woocommerce-booking").'"];
								}
							}	
						
							for (iii = 0; iii < globalHolidays.length; iii++) {
								if( jQuery.inArray(d + "-" + (m+1) + "-" + y,globalHolidays) != -1 ){
									return [false, "", "'.__("Holiday","woocommerce-booking").'"];
								}
							}
						
							for (ii = 0; ii < holidayDates.length; ii++) {
								if( jQuery.inArray(d + "-" + (m+1) + "-" + y,holidayDates) != -1 ) {
									return [false, "","'.__("Holiday","woocommerce-booking").'"];
								}
							}
					
							for (i = 0; i < bookedDates.length; i++) {
								if( jQuery.inArray(d + "-" + (m+1) + "-" + y,bookedDates) != -1 ) {
									return [false, "","'.__("Unavailable for Booking","woocommerce-booking").'"];
								}
							}
							'.$js_code.' 	
							for (i = 0; i < deliveryDates.length; i++) {
								if( jQuery.inArray(d + "-" + (m+1) + "-" + y,deliveryDates) != -1 ){
									return [true];
								}
							}
							var day = "booking_weekday_" + date.getDay();
							if (jQuery("#wapbk_"+day).val() == "on"){
									return [true];
							}
							return [false];
						}
                                    
						//********************************************************
						//This function calls an ajax when a date is selected which displays the time slots on frontend product page.
                        //**************************************************
					
						function bkap_show_times(date,inst) {
							var monthValue = inst.selectedMonth+1;
							var dayValue = inst.selectedDay;
							var yearValue = inst.selectedYear;

							var current_dt = dayValue + "-" + monthValue + "-" + yearValue;
							var sold_individually = jQuery("#wapbk_sold_individually").val();
							var quantity = jQuery("input[class=\"input-text qty text\"]").attr("value");

							jQuery("#wapbk_hidden_date").val(current_dt);
							/*if (jQuery("#wapbk_recurringDays").val() == "on" && jQuery("#wapbk_recurringDays").val() != "") {
								jQuery( "#ajax_img" ).show();
								var data = {
									current_date: current_dt,
									post_id: "'.$duplicate_of.'", 
									action: "check_for_prices"
									'.$attribute_fields_str.'
								};
								jQuery.post("'.get_admin_url().'/admin-ajax.php", data, function(response){
									//	alert("Got this from the server: " + response);
									jQuery( "#ajax_img" ).hide();
									if(response != ""){
										if(sold_individually == "yes"){   
											var total_price = parseFloat(response);
										}else {
											var total_price = parseFloat(response) * parseInt(quantity);
										}
										jQuery("#show_prices").html("'.$currency_symbol.'" + total_price);
										jQuery("#recurring_price").val(total_price);
									}
								});
							}*/
							if (jQuery("#wapbk_bookingEnableTime").val() == "on" && jQuery("#wapbk_booking_times").val() != "") {
								jQuery( "#ajax_img" ).show();
								var time_slots_arr = jQuery("#wapbk_booking_times").val();
								var data = {
									current_date: current_dt,
									post_id: "'.$duplicate_of.'", 
									action: "'.$method_to_show.'"
									'.$attribute_fields_str.'
								};
								jQuery.post("'.get_admin_url().'/admin-ajax.php", data, function(response) {
									jQuery( "#ajax_img" ).hide();
									jQuery("#show_time_slot").html(response);
									jQuery("#time_slot").change(function() {
										if ( jQuery("#time_slot").val() != "" ) {
											jQuery( ".single_add_to_cart_button" ).show();
                                            jQuery( ".payment_type" ).show();
											if(sold_individually == "yes") {
												jQuery( ".quantity" ).hide();
												jQuery( ".payment_type" ).hide();
												jQuery(".partial_message").hide();
											} else {
												jQuery( ".quantity" ).show();
												jQuery( ".payment_type" ).show();
											}
										} else if ( jQuery("#time_slot").val() == "" ) {
											jQuery( ".single_add_to_cart_button" ).hide();
											jQuery( ".quantity" ).hide();
                                            jQuery( ".payment_type" ).hide();
											jQuery(".partial_message").hide();
										}
									})
									'.$do_slot.'
								});
							} else {
								if ( jQuery("#wapbk_hidden_date").val() != "" ) {
									var data = {
										current_date: current_dt,
										post_id: "'.$duplicate_of.'",
										action: "bkap_insert_date"
										'.$attribute_fields_str.'
									};
									jQuery.post("'.get_admin_url().'/admin-ajax.php", data, function(response){
									jQuery( ".single_add_to_cart_button" ).show();
                                    jQuery( ".payment_type" ).show();
									if(sold_individually == "yes") {
										jQuery( ".quantity" ).hide();
                                    } else {
										jQuery( ".quantity" ).show();
									}
								});
								} else if ( jQuery("#wapbk_hidden_date").val() == "" ) {
									jQuery( ".single_add_to_cart_button" ).hide();
									jQuery( ".quantity" ).hide();
                                    jQuery( ".payment_type" ).hide()
									jQuery(".partial_message").hide();
								}
							}'.$addon_price.'
							bkap_set_partial_payment_deposit(monthValue,dayValue,yearValue);
						}
						 //************************************************
				         //This function enables the partial and full payment depending on Deposits after prior to X days setting when Partial Deposits Addon is active.
					     //************************************************
						
						function bkap_set_partial_payment_deposit(monthValue,dayValue,yearValue) {
							var deposit_days_value =jQuery("#wapbk_hidden_deposit_days").val();	
							var diff = 1;
							var currentDate = new Date();
							var dd = currentDate.getDate();
							var mm = currentDate.getMonth()+1;
							var yy = currentDate.getFullYear();
	
		 					var dateDiff = function ( d1, d2 ) {
							    var diff = Math.abs(d1 - d2);
							    if (Math.floor(diff/86400000)) {
							        return Math.floor(diff/86400000) ;
							    /*} else if (Math.floor(diff/3600000)) {
								    return Math.floor(diff/3600000) + " hours";
								 } else if (Math.floor(diff/60000)) {
									return Math.floor(diff/60000) + " minutes";
								*/ } else {
									return 0;
								 }
							};
							var calendar_date=dayValue + "," + monthValue + "," + yearValue;
							var current_date=dd + "," + mm + "," + yy;
							var dt_interval= dateDiff(new Date(yy,mm,dd), new Date(yearValue,monthValue,dayValue)) // -> 12 days	
							jQuery(".payment_type.partial input:radio").attr("disabled", false);
						 	jQuery(".partial_message").hide();
							if (! ( deposit_days_value=="" || deposit_days_value== 0) ) {
								if (dt_interval < deposit_days_value){
									jQuery(".payment_type input:radio:not(:disabled):first-child").attr("checked", true);
									jQuery(".payment_type.partial input:radio").attr("disabled", true);
							 		jQuery(".partial_message").show();
								}
							}
						}

						//******************************************
						//This functions checks if the selected date range does not have product holidays or global holidays and sets the hidden date field.
						//********************************************
					
						function bkap_set_checkin_date(date,inst){
							var monthValue = inst.selectedMonth+1;
							var dayValue = inst.selectedDay;
							var yearValue = inst.selectedYear;

							var current_dt = dayValue + "-" + monthValue + "-" + yearValue;
							jQuery("#wapbk_hidden_date").val(current_dt);
							// Check if any date in the selected date range is unavailable
							if (jQuery("#wapbk_hidden_date").val() != "" && jQuery("#wapbk_hidden_date_checkout").val() != "") {
								var CalculatePrice = "Y";
								var split = jQuery("#wapbk_hidden_date").val().split("-");
								split[1] = split[1] - 1;		
								var CheckinDate = new Date(split[2],split[1],split[0]);
								
								var split = jQuery("#wapbk_hidden_date_checkout").val().split("-");
								split[1] = split[1] - 1;
								var CheckoutDate = new Date(split[2],split[1],split[0]);
								
								var date = new_end = new Date(CheckinDate);
								var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
								
								var bookedDates = eval("["+jQuery("#wapbk_hidden_booked_dates").val()+"]");
								var holidayDates = eval("["+jQuery("#wapbk_booking_holidays").val()+"]");
								var globalHolidays = eval("["+jQuery("#wapbk_booking_global_holidays").val()+"]");
						
								var count = gd(CheckinDate, CheckoutDate, "days");
								//Locked Dates
								for (var i = 1; i<= count;i++) {
									if( jQuery.inArray(d + "-" + (m+1) + "-" + y,bookedDates) != -1 ) {
										jQuery("#wapbk_hidden_date").val("");
										jQuery("#booking_calender").val("");
										jQuery( ".single_add_to_cart_button" ).hide();
										jQuery( ".quantity" ).hide();
										CalculatePrice = "N";
										alert("Some of the dates in the selected range are unavailable. Please try another date range.");
										break;
									}
									new_end = new Date(ad(new_end,1));
									var m = new_end.getMonth(), d = new_end.getDate(), y = new_end.getFullYear();													
								}
								//Global Holidays
								var date = new_end = new Date(CheckinDate);
								var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
							
								for (var i = 1; i<= count;i++){
									if( jQuery.inArray(d + "-" + (m+1) + "-" + y,globalHolidays) != -1 ) {
										jQuery("#wapbk_hidden_date").val("");
										jQuery("#booking_calender").val("");
										jQuery( ".single_add_to_cart_button" ).hide();
										jQuery( ".quantity" ).hide();
										CalculatePrice = "N";
										alert("Some of the dates in the selected range are unavailable. Please try another date range.");
										break;
									}
									new_end = new Date(ad(new_end,1));
									var m = new_end.getMonth(), d = new_end.getDate(), y = new_end.getFullYear();													
								}
								//Product Holidays
								var date = new_end = new Date(CheckinDate);
								var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
						
								for (var i = 1; i<= count;i++){
									if( jQuery.inArray(d + "-" + (m+1) + "-" + y,holidayDates) != -1 ) {
										jQuery("#wapbk_hidden_date").val("");
										jQuery("#booking_calender").val("");
										jQuery( ".single_add_to_cart_button" ).hide();
										jQuery( ".quantity" ).hide();
										CalculatePrice = "N";
										alert("Some of the dates in the selected range are unavailable. Please try another date range.");
										break;
									}
									new_end = new Date(ad(new_end,1));
									var m = new_end.getMonth(), d = new_end.getDate(), y = new_end.getFullYear();													
								}
								if (CalculatePrice == "Y") bkap_calculate_price();
							}
						}

						//************************************
						//This function sets the hidden checkout date for Multiple day booking feature.
                        //***********************************
					
						function bkap_get_per_night_price(date,inst){
							var monthValue = inst.selectedMonth+1;
							var dayValue = inst.selectedDay;
							var yearValue = inst.selectedYear;
							var current_dt = dayValue + "-" + monthValue + "-" + yearValue;
							jQuery("#wapbk_hidden_date_checkout").val(current_dt);
							//bkap_set_partial_payment_deposit(monthValue,dayValue,yearValue);
							bkap_calculate_price();
						}
                                       
						//***********************************
                        //This function add an ajax call to calculate price and displays the price on the frontend product page for Multiple day booking feature.
						//************************************
					
						function bkap_calculate_price(){
							// Check if any date in the selected date range is unavailable
							var CalculatePrice = "Y";				
							var split = jQuery("#wapbk_hidden_date").val().split("-");
							bkap_set_partial_payment_deposit(split[1],split[0],split[2]);
							split[1] = split[1] - 1;		
							var CheckinDate = new Date(split[2],split[1],split[0]);
						
						
							var split = jQuery("#wapbk_hidden_date_checkout").val().split("-");
							split[1] = split[1] - 1;
							var CheckoutDate = new Date(split[2],split[1],split[0]);
							//alert(CheckoutDate);
							var date = new_end = new Date(CheckinDate);
							var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
						
							var bookedDates = eval("["+jQuery("#wapbk_hidden_booked_dates").val()+"]");
							var holidayDates = eval("["+jQuery("#wapbk_booking_holidays").val()+"]");
							var globalHolidays = eval("["+jQuery("#wapbk_booking_global_holidays").val()+"]");
					
							var count = gd(CheckinDate, CheckoutDate, "days");
					
							for (var i = 1; i<= count;i++){
								if( jQuery.inArray(d + "-" + (m+1) + "-" + y,bookedDates) != -1 ){
									jQuery("#wapbk_hidden_date_checkout").val("");
									jQuery("#booking_calender_checkout").val("");
									jQuery( ".single_add_to_cart_button" ).hide();
									jQuery( ".quantity" ).hide();
									CalculatePrice = "N";
									alert("Some of the dates in the selected range are unavailable. Please try another date range.");
									break;
								}
								new_end = new Date(ad(new_end,1));
								var m = new_end.getMonth(), d = new_end.getDate(), y = new_end.getFullYear();
							}

							//Global Holidays
							var date = new_end = new Date(CheckinDate);
							var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
							//	alert(new_end);
							for (var i = 1; i<= count;i++){
								if( jQuery.inArray(d + "-" + (m+1) + "-" + y,globalHolidays) != -1 ){
									jQuery("#wapbk_hidden_date_checkout").val("");
									jQuery("#booking_calender_checkout").val("");
									jQuery( ".single_add_to_cart_button" ).hide();
									jQuery( ".quantity" ).hide();
									CalculatePrice = "N";
									alert("Some of the dates in the selected range are unavailable. Please try another date range.");
									break;
								}
								new_end = new Date(ad(new_end,1));
								var m = new_end.getMonth(), d = new_end.getDate(), y = new_end.getFullYear();
							}
							//Product Holidays
							var date = new_end = new Date(CheckinDate);
							var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
							//	alert(new_end);
							for (var i = 1; i<= count;i++){
								if( jQuery.inArray(d + "-" + (m+1) + "-" + y,holidayDates) != -1 ) {
									jQuery("#wapbk_hidden_date_checkout").val("");
									jQuery("#booking_calender_checkout").val("");
									jQuery( ".single_add_to_cart_button" ).hide();
									jQuery( ".quantity" ).hide();
									CalculatePrice = "N";
									alert("Some of the dates in the selected range are unavailable. Please try another date range.");
									break;
								}
								new_end = new Date(ad(new_end,1));
								var m = new_end.getMonth(), d = new_end.getDate(), y = new_end.getFullYear();
							}
							'.$js_block_date.'
							// Calculate the price	
							if (CalculatePrice == "Y") {
								//alert(block_option_price);
								var oneDay = 24*60*60*1000; // hours*minutes*seconds*milliseconds
								var sold_individually = jQuery("#wapbk_sold_individually").val();
								var firstDate = CheckinDate;
								var secondDate = CheckoutDate;
								var value_charge = '.$add_day.';
								var diffDays = Math.abs((firstDate.getTime() - secondDate.getTime())/(oneDay));
								diffDays = diffDays + value_charge;
								jQuery("#wapbk_diff_days").val(diffDays);
								var quantity = jQuery("input[class=\"input-text qty text\"]").attr("value");
								jQuery( "#ajax_img" ).show();
								var data = {
									current_date: jQuery("#wapbk_hidden_date_checkout").val(),
									checkin_date: jQuery("#wapbk_hidden_date").val(),
									attribute_selected: jQuery("#wapbk_variation_value").val(),
									block_option_price: jQuery("#block_option_price").val(),
									post_id: "'.$duplicate_of.'", 
									action: "bkap_get_per_night_price",
									product_type: "'.$product_type.'"
									'.$attribute_fields_str.' 
									
								};
								jQuery.post("'.get_admin_url().'/admin-ajax.php", data, function(response) {
									jQuery( "#ajax_img" ).hide();		
									if (isNaN(parseInt(response))) {
										jQuery("#show_time_slot").html(response)
									} else {
										if (jQuery("#block_option_enabled_price").val() == "on") {
											var split_str = response;
											var exploded = split_str.split("-");
											var price_type = exploded[1];
											if(price_type == "fixed" || price_type == "per_day") {
												if(sold_individually == "yes") {
													var total_price = parseFloat(exploded[0]);
                                                } else {
													var total_price = parseFloat(exploded[0]) * parseInt(quantity);
                                                }
											} else {
												if(sold_individually == "yes") {
													var total_price = parseInt(diffDays) * parseFloat(exploded[0]);
												} else {
													var total_price = parseInt(diffDays) * parseFloat(exploded[0]) * parseInt(quantity);
												}
											}
											jQuery("#block_variable_option_price").val(parseFloat(exploded[0])+","+price_type+","+exploded[2]);
										} else {
										'.$price_value.'
									}	
									if (jQuery("#wapbk_round_price").val() == "yes") {
										var price = Math.round(total_price);
									} else if (jQuery("#wapbk_round_price").val() == "no") {
										var price = parseFloat(total_price).toFixed(2);
									}		
									//alert(price);
									jQuery("#show_time_slot").html("'.$currency_symbol.'" + price);
									jQuery("#total_price_calculated").val(price);
								}
								jQuery( ".single_add_to_cart_button" ).show();
                                jQuery( ".payment_type" ).show();
								if(sold_individually == "yes") {
									jQuery( ".quantity" ).hide();
								}else {
									jQuery( ".quantity" ).show();
								}
							}); 
						}
					}
					</script>');
				}
		if (isset($booking_settings['booking_partial_payment_enable']) && $booking_settings['booking_partial_payment_enable'] == "yes") {
			if ( isset($booking_settings['allow_full_payment']) && $booking_settings['allow_full_payment'] == "yes") {		
				?>
				<label class="payment_type partial"><input type="radio" checked="" class="payment_type" name="payment_type" value="partial_payment"><?php __('Partial Payment','woocommerce-booking')?></label>
				<label class="payment_type full"><input type="radio" class="payment_type" name="payment_type" value="full_payment"> <?php __('Full Payment','woocommerce-booking')?></label>
				<?php 
				if (isset($global_settings->partial_payment_disabled_message)) { ?>
					<div class="partial_message">
						<p> 
							 <?php echo $global_settings->partial_payment_disabled_message ?>
						</p>
					</div>	
					<?php
				} 
			}
		}
		do_action("bkap_before_add_to_cart_button",$booking_settings);
	}

	/***********************************************
	* This function displays the prices calculated from other Addons on frontend product page.
    **************************************************/
	public static function bkap_call_addon_price(){
		//	global $post;
		$product_id = $_POST['id'];
		$booking_date_format = $_POST['details'];
		$booking_date = date('Y-m-d',strtotime($booking_date_format));
		$product = get_product($product_id);
		$product_type = $product->product_type;
		if ( $product_type == 'variable') {
			$variation_id =  bkap_booking_process::bkap_get_selected_variation_id($product_id, $_POST);
        } else {
            $variation_id = "0";
        }
		$booking_settings = get_post_meta($product_id, 'woocommerce_booking_settings', true);
		if (isset($booking_settings['booking_partial_payment_enable']) && $booking_settings['booking_partial_payment_radio']!='' &&  is_plugin_active('bkap-deposits/deposits.php')) {
			$price = apply_filters("bkap_add_updated_addon_price",$product_id,$booking_date,$variation_id);
			do_action('bkap_deposits_display_updated_price',$product_id,$variation_id,$price);
		} else {
			do_action('bkap_display_updated_addon_price',$product_id,$booking_date,$variation_id);
		}
	}
			
	/**********************************
	* This function displays the price calculated on the frontend product page for Multiple day booking feature.
    ******************************************/
			
	public static function bkap_get_per_night_price() {
		global $wpdb;
		$product_type = $_POST['product_type'];
		$product_id = $_POST['post_id'];
		$check_in_date = $_POST['checkin_date'];
		$check_out_date = $_POST['current_date'];
		if ($product_type == 'variable') {
			$variation_id_to_fetch =  bkap_booking_process::bkap_get_selected_variation_id($product_id, $_POST);
        } else {
			$variation_id_to_fetch = 0;
		}
		$checkin_date = date('Y-m-d',strtotime($check_in_date));
		$checkout_date = date('Y-m-d',strtotime($check_out_date));
		do_action("bkap_display_multiple_day_updated_price",$product_id,$product_type,$variation_id_to_fetch,$checkin_date,$checkout_date);
				
		$booking_settings = get_post_meta($product_id, 'woocommerce_booking_settings', true);
		if(isset($booking_settings['booking_block_price_enable']) && $booking_settings['booking_block_price_enable'] == 'yes') {
			do_action('bkap_display_block_updated_price',$product_id,$product_type,$variation_id_to_fetch,$checkin_date,$checkout_date);
			exit;
		} else if (isset($booking_settings['booking_fixed_block_enable']) && $booking_settings['booking_fixed_block_enable'] == "yes") {
			$price = $_POST['block_option_price'];
			do_action('bkap_fixed_block_display_updated_price',$product_id,$variation_id_to_fetch,$price);
			exit;
		} else if (isset($booking_settings['booking_partial_payment_enable']) && is_plugin_active('bkap-deposits/deposits.php')) {
			$price = apply_filters("bkap_add_multiple_day_updated_price",$product_id,$product_type,$variation_id_to_fetch,$checkin_date,$checkout_date);
			do_action('bkap_deposits_display_updated_price',$product_id,$variation_id_to_fetch,$price);
			exit;
		}
		if ($product_type == 'variable'){
			$variation_id_to_fetch =  bkap_booking_process::bkap_get_selected_variation_id($product_id, $_POST);
        } else {
            $variation_id_to_fetch = 0;
        }
		if ($product_type == 'variable') {
			$variation_id_to_fetch =  bkap_booking_process::bkap_get_selected_variation_id($product_id, $_POST);
			if ($variation_id_to_fetch != ""){
				$sale_price = get_post_meta( $variation_id_to_fetch, '_sale_price', true);
				if($sale_price == '') {
					$regular_price = get_post_meta( $variation_id_to_fetch, '_regular_price',true);
					echo $regular_price;
				} else {
					echo $sale_price;
				}
			}
			else {
				echo "Please select an option."; 
			}
		} elseif ($product_type == 'simple') {
			$sale_price = get_post_meta( $_POST['post_id'], '_sale_price', true);
			if($sale_price == '') {
				$regular_price = get_post_meta( $_POST['post_id'], '_regular_price',true);
				echo $regular_price;
			} else {
				echo $sale_price;
			}
		}
		die();
	}
	
	/******************************************************
	* This function adds the booking date selected on the frontend product page for recurring booking method when the date is selected.
    *****************************************************/
	
	public static function bkap_insert_date() {
		global $wpdb;
		$current_date = $_POST['current_date'];
		$date_to_check = date('Y-m-d', strtotime($current_date));
		$day_check = "booking_weekday_".date('w', strtotime($current_date));
		$post_id = $_POST['post_id'];
		$product = get_product($post_id);
		$product_type = $product->product_type;
		if ( $product_type == 'variable') {
			$variation_id =  bkap_booking_process::bkap_get_selected_variation_id($post_id, $_POST);
		} else {
			$variation_id = "";
		}
		$check_query = "SELECT * FROM `".$wpdb->prefix."booking_history`
							WHERE start_date='".$date_to_check."'
							AND post_id='".$post_id."'
							AND available_booking > 0";
		$results_check = $wpdb->get_results ( $check_query );
		if ( !$results_check ) {
			$check_day_query = "SELECT * FROM `".$wpdb->prefix."booking_history`
									WHERE weekday='".$day_check."'
									AND post_id='".$post_id."'
									AND start_date='0000-00-00'
									AND available_booking > 0";
			$results_day_check = $wpdb->get_results ( $check_day_query );	
			if (!$results_day_check) {
				$check_day_query = "SELECT * FROM `".$wpdb->prefix."booking_history`
										WHERE weekday='".$day_check."'
										AND post_id='".$post_id."'
										AND start_date='0000-00-00'
										AND total_booking = 0 
										AND available_booking = 0";
				$results_day_check = $wpdb->get_results ( $check_day_query );	
			}
			foreach ( $results_day_check as $key => $value ) {
				$insert_date = "INSERT INTO `".$wpdb->prefix."booking_history`
										(post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
										VALUES (
										'".$post_id."',
										'".$day_check."',
										'".$date_to_check."',
										'0000-00-00',
										'',
										'',
										'".$value->total_booking."',
										'".$value->available_booking."' )";
				$wpdb->query( $insert_date );
			}
		}
		die();
	}

	/***********************************************
     * This function displays the timeslots for the selected date on the frontend page when Enable time slot is enabled.
     ************************************************/
			
	function bkap_check_for_time_slot() {			
		global $wpdb;
		$saved_settings = json_decode(get_option('woocommerce_booking_global_settings'));
		if (isset($saved_settings)) {
			$time_format = $saved_settings->booking_time_format;
		} else {
			$time_format = '12';
		}
		$time_format_value = 'G:i';
		if ($time_format == '12') {
			$time_format_to_show = 'h:i A';
		} else {
			$time_format_to_show = 'H:i';
		}
		$current_date = $_POST['current_date'];
		$date_to_check = date('Y-m-d', strtotime($current_date));
		$day_check = "booking_weekday_".date('w', strtotime($current_date));
		$from_time_value = '';
		$from_time = '';
		$post_id = $_POST['post_id'];
		$product = get_product($post_id);
		$product_type = $product->product_type;
		if ( $product_type == 'variable') {
			$variation_id =  bkap_booking_process::bkap_get_selected_variation_id($post_id, $_POST);
        } else { 
			$variation_id = "";
        }
		$check_query = "SELECT * FROM `".$wpdb->prefix."booking_history`
								WHERE start_date='".$date_to_check."'
								AND post_id='".$post_id."'
								AND available_booking > 0 ORDER BY STR_TO_DATE(from_time,'%H:%i')
								";
		$results_check = $wpdb->get_results ( $check_query );
		if ( count($results_check) > 0 ) {
			$drop_down = "<label>".get_option('book.time-label').": </label><select name='time_slot' id='time_slot' class='time_slot'>";
			$drop_down .= "<option value=''>".__( 'Choose a Time', 'woocommerce-booking')."</option>";
			$specific = "N";
			foreach ( $results_check as $key => $value ) {
				if ($value->weekday == "") {
					$specific = "Y";
					if ($value->from_time != '') {
						$from_time = date($time_format_to_show, strtotime($value->from_time));
						$from_time_value = date($time_format_value, strtotime($value->from_time));
					}
					$to_time = $value->to_time;
					if( $to_time != '' ) {
						$to_time = date($time_format_to_show, strtotime($value->to_time));
						$to_time_value = date($time_format_value, strtotime($value->to_time));
						$drop_down .= "<option value='".$from_time_value." - ".$to_time_value."'>".$from_time."-".$to_time."</option>";
					} else {
						$drop_down .= "<option value='".$from_time_value."'>".$from_time."</option>";
					}
				}
			}
			if ($specific == "N") {
				foreach ( $results_check as $key => $value ) {
					if ($value->from_time != '') {
						$from_time = date($time_format_to_show, strtotime($value->from_time));
						$from_time_value = date($time_format_value, strtotime($value->from_time));
					}
					$to_time = $value->to_time;
					if( $to_time != '' ) {
						$to_time = date($time_format_to_show, strtotime($value->to_time));
						$to_time_value = date($time_format_value, strtotime($value->to_time));
						$drop_down .= "<option value='".$from_time_value." - ".$to_time_value."'>".$from_time."-".$to_time."</option>";
					} else {
						if ($value->from_time != '') {
						$drop_down .= "<option value='".$from_time_value."'>".$from_time."</option>";
					}
				}	
			}
			$check_day_query = "SELECT * FROM `".$wpdb->prefix."booking_history`
											WHERE weekday='".$day_check."'
											AND post_id='".$post_id."'
											AND start_date='0000-00-00'
											AND available_booking > 0 ORDER BY STR_TO_DATE(from_time,'%H:%i')";
			$results_day_check = $wpdb->get_results ( $check_day_query );
			//remove duplicate time slots that have available booking set to 0
			foreach ($results_day_check as $k => $v) {
				$from_time_qry = date($time_format_value, strtotime($v->from_time));
				if ($v->to_time != '') {
					$to_time_qry = date($time_format_value, strtotime($v->to_time));
				} else {
					$to_time_qry = "";
				}
				$time_check_query = "SELECT * FROM `".$wpdb->prefix."booking_history`
										WHERE start_date='".$date_to_check."'
										AND post_id='".$post_id."'
										AND from_time='".$from_time_qry."'
										AND to_time='".$to_time_qry."' ORDER BY STR_TO_DATE(from_time,'%H:%i')";
				$results_time_check = $wpdb->get_results ( $time_check_query );
				if (count($results_time_check) > 0) {
					unset($results_day_check[$k]);
				}
			}
			//remove duplicate time slots that have available booking > 0
			foreach ($results_day_check as $k => $v) {
				foreach ($results_check as $key => $value) {
					if ($v->from_time != '' && $v->to_time != '') {
						$from_time_chk = date($time_format_value, strtotime($v->from_time));
						if ($value->from_time == $from_time_chk) {
							if ($v->to_time != ''){
								$to_time_chk = date($time_format_value, strtotime($v->to_time));
                            }
							if ($value->to_time == $to_time_chk){
								unset($results_day_check[$k]);
                            }
						}
					} else {
						if($v->from_time == $value->from_time) {
							if ($v->to_time == $value->to_time) {
								unset($results_day_check[$k]);
							}
						}
					}
				}
			}
			foreach ( $results_day_check as $key => $value ) {
				if ($value->from_time != '') {
					$from_time = date($time_format_to_show, strtotime($value->from_time));
					$from_time_value = date($time_format_value, strtotime($value->from_time));
				}
				$to_time = $value->to_time;
				if ( $to_time != '' ) {
					$to_time = date($time_format_to_show, strtotime($value->to_time));
					$to_time_value = date($time_format_value, strtotime($value->to_time));
					$drop_down .= "<option value='".$from_time_value." - ".$to_time_value."'>".$from_time."-".$to_time."</option>";
				} else {
					if ($value->from_time != '') {
						$drop_down .= "<option value='".$from_time_value."'>".$from_time."</option>";
					}
				}
					$insert_date = "INSERT INTO `".$wpdb->prefix."booking_history`
											(post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
											VALUES (
											'".$post_id."',
											'".$day_check."',
											'".$date_to_check."',
											'0000-00-00',
											'".$from_time_value."',
											'".$to_time_value."',
											'".$value->total_booking."',
											'".$value->available_booking."' )";
					$wpdb->query( $insert_date );
				}
			}
		} else {
			$check_day_query = "SELECT * FROM `".$wpdb->prefix."booking_history`
										 WHERE weekday='".$day_check."'
										 AND post_id='".$post_id."'
										 AND start_date='0000-00-00'
										 AND available_booking > 0 ORDER BY STR_TO_DATE(from_time,'%H:%i')";
			$results_day_check = $wpdb->get_results ( $check_day_query );
			if (!$results_day_check) {
				$check_day_query = "SELECT * FROM `".$wpdb->prefix."booking_history`
											WHERE weekday='".$day_check."'
											AND post_id='".$post_id."'
											AND start_date='0000-00-00'
											AND total_booking = 0
											AND available_booking = 0 ORDER BY STR_TO_DATE(from_time,'%H:%i')";
				$results_day_check = $wpdb->get_results ( $check_day_query );
			}
			if ($results_day_check) {
				$drop_down = "<label>".get_option('book.time-label'). ": </label><select name='time_slot' id='time_slot' class='time_slot'>";
				$drop_down .= "<option value=''>" . __( 'Choose a Time', 'woocommerce-booking') . "</option>";
				foreach ( $results_day_check as $key => $value ) {
					if ($value->from_time != '') {
						$from_time = date($time_format_to_show, strtotime($value->from_time));
						$from_time_value = date($time_format_value, strtotime($value->from_time));
					} else {
						$from_time = $from_time_value = "";
					}
					$to_time = $value->to_time;
					if ( $to_time != '' ) {
						$to_time = date($time_format_to_show, strtotime($value->to_time));
						$to_time_value = date($time_format_value, strtotime($value->to_time));
						$drop_down .= "<option value='".$from_time_value." - ".$to_time_value."'>".$from_time."-".$to_time."</option>";
					} else  {
						$drop_down .= "<option value='".$from_time_value."'>".$from_time."</option>";
						$to_time = $to_time_value = "";
					}
					$insert_date = "INSERT INTO `".$wpdb->prefix."booking_history`
											(post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
											VALUES (
											'".$post_id."',
											'".$day_check."',
											'".$date_to_check."',
											'0000-00-00',
											'".$from_time_value."',
											'".$to_time_value."',
											'".$value->total_booking."',
											'".$value->available_booking."' )";
					$wpdb->query( $insert_date );
				}
			} else {
				$check_query = "SELECT * FROM `".$wpdb->prefix."booking_history`
										WHERE start_date='".$date_to_check."'
										AND post_id='".$post_id."'
										AND total_booking = 0
										AND available_booking = 0 ORDER BY STR_TO_DATE(from_time,'%H:%i')
							";
				$results_check = $wpdb->get_results ( $check_query );
				$drop_down = "<label>".get_option('book.time-label'). ": </label><select name='time_slot' id='time_slot' class='time_slot'>";
				$drop_down .= "<option value=''>" . __( 'Choose a Time', 'woocommerce-booking') . "</option>";
				foreach ( $results_check as $key => $value ) {
					if ($value->from_time != '') {
						$from_time = date($time_format_to_show, strtotime($value->from_time));
						$from_time_value = date($time_format_value, strtotime($value->from_time));
					} else {
						$from_time = $from_time_value = "";
					}						
					$to_time = $value->to_time;
					if ( $to_time != '' ) {
						$to_time = date($time_format_to_show, strtotime($value->to_time));
						$to_time_value = date($time_format_value, strtotime($value->to_time));
						$drop_down .= "<option value='".$from_time_value." - ".$to_time_value."'>".$from_time."-".$to_time."</option>";
					} else {
						$drop_down .= "<option value='".$from_time_value."'>".$from_time."</option>";
						$to_time = $to_time_value = "";
					}
				}
			}
		}
		echo $drop_down;
		die();
	}

	/**************************************************
	*This function is used to fetch the variation id of selected attributed on the front end.
    ******************************************************/

	public static function bkap_get_selected_variation_id($product_id, $post_data) {
		global $wpdb;
		$product = get_product($product_id);
		$variations = $product->get_available_variations();
		$attributes = $product->get_variation_attributes();
		$attribute_fields_str = "";
		$attribute_fields = array();
		$variation_id_arr = $variation_id_exclude = array();
		foreach ($variations as $var_key => $var_val) {
			$attribute_sub_query = '';
			$variation_id = $var_val['variation_id'];
			foreach ($var_val['attributes'] as $a_key => $a_val){
				$attribute_name = $a_key;
				// for each attribute, we are checking the value selected by the user
				if (isset($post_data[$attribute_name])){
					$attribute_sub_query[] = " (`meta_key` = '$attribute_name' AND `meta_value` = '$post_data[$attribute_name]')  ";
					$attribute_sub_query_str = " (`meta_key` = '$attribute_name' AND (`meta_value` = '$post_data[$attribute_name]' OR `meta_value` = ''))  ";
					$check_price_query = "SELECT * FROM `".$wpdb->prefix."postmeta`
												WHERE 
												$attribute_sub_query_str 
												AND 
												post_id='".$variation_id."' ";
					$results_price_check = $wpdb->get_results ( $check_price_query );
					// if no records are found, then that variation_id is put in exclude array
					if (count($results_price_check) > 0){
						if (!in_array($variation_id, $variation_id_arr))
							$variation_id_arr[] = $variation_id;
						} else {
							if (!in_array($variation_id, $variation_id_exclude))
								$variation_id_exclude[] = $variation_id;
						}
					}
				}
			}
			// here we remove all variation ids from the $variation_id_arr that are present in the $variation_id_exclude array
			// this should leave us with only 1 variation id
			$variation_id_final = array_diff($variation_id_arr, $variation_id_exclude);
			$variation_id_to_fetch = array_pop($variation_id_final);
			return $variation_id_to_fetch;
		}
	}
?>