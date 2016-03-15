<?php
if ( !class_exists( 'bkap_timeslot_price' ) ) {
	
    class bkap_timeslot_price {
		
        public function __construct() {
			// Add the Time Slot Price field in the Time Slot div
			add_action( 'bkap_after_lockout_time',           array( &$this, 'timeslot_price_field' ) );
			// Save the price in the post meta record
			add_filter( 'bkap_save_slot_field_settings',     array( &$this, 'timeslot_price_save' ), 10, 2 );
			// Add the time slot price column name in the View/Delete Tab
			add_action( 'bkap_add_column_names',             array( &$this, 'timeslot_price_column_name' ), 10, 1 );
			// Add the prices in each row 
			add_filter( 'bkap_add_column_value',             array( &$this, 'timeslot_price_column_value' ), 10, 2);
			// Display the price div if different prices are enabled for time slots
			add_action( 'bkap_display_price_div',            array( &$this, 'timeslot_price_div' ), 20, 1 );
			// Print hidden fields
			add_action( 'bkap_print_hidden_fields',          array( &$this, 'timeslot_hidden_fields' ), 10, 1 );
			// Display updated price on the product page
			add_action( 'bkap_display_updated_addon_price',  array( &$this, 'timeslot_display_updated_price' ), 3, 3 );
			
		}
			
		function timeslot_price_field() {
		?>
			<br>
			<label for="booking_price_time"><?php _e( 'Price for the time slot:', 'woocommerce-booking' ); ?></label><br>
			<input type="text" style="width:100px;" name="booking_price_time[0]" id="booking_price_time[0]" value="" />
			<input type="hidden" id="slot_count" name="slot_count" value="[0]" />
			<img class="help_tip" width="16" height="16" style="margin-left:300px;" data-tip="<?php _e( 'Please enter price for the timeslot.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
			<br><br>
			<?php
		}
			
		function timeslot_price_save( $date_time_settings, $post_id ) {
			$woo_booking_dates   = get_post_meta( $post_id, 'woocommerce_booking_settings', true );
			$weekdays            = bkap_get_book_arrays( 'weekdays' );
			
			foreach ( $weekdays as $n => $day_name ) {
				
			    if ( isset( $_POST[ $n ] ) && $_POST[ $n ] == 'on' || isset( $_POST[ $n ] ) && $_POST[ $n ] == '' ) {
                	$new_day_arr[ $n ] = $_POST[ $n ];
                }
			}
		
			$slot_count          = explode( "[", $_POST['wapbk_slot_count'] );
			$slot_count_value    = intval( $slot_count[1] );
			$specific_booking    = array();
			
			if ( isset( $_POST['booking_specific_date_booking'] ) ) {
				$specific_booking = $_POST['booking_specific_date_booking'];
			}
		
			$specific_booking_dates = explode( ",", $specific_booking );
		
			if( $specific_booking != "" ) {
				
			    foreach ( $specific_booking_dates as $day_key => $day_value ) {
					$date_tmstmp = strtotime($day_value);
					$date_save = date( 'Y-m-d', $date_tmstmp );
					
					if ( isset( $_POST['booking_enable_time'] ) && $_POST['booking_enable_time'] == "on" ) {
						$j=1;
						
						if ( isset( $woo_booking_dates['booking_time_settings'] ) && is_array( $woo_booking_dates['booking_time_settings'] ) ) { 
							
						    if ( array_key_exists( $day_value, $woo_booking_dates['booking_time_settings'] ) ) {
								
						        foreach ( $woo_booking_dates['booking_time_settings'][ $day_value ] as $dtkey => $dtvalue ) {
									$date_time_settings[ $day_value ][ $j ] = $dtvalue;
									$j++;
								}
							}
						}
						$k = 1;
						
						for( $i = ( $j + 1 ); $i <= ( $j + $slot_count_value ); $i++ ) {
							
						    if( $_POST['booking_from_slot_hrs'][ $k ] != 0 ) {
								$time_settings['slot_price']        = $_POST['booking_price_time'][ $k ];
								$date_time_settings[ $day_value ][ $i ] = array_merge( $time_settings, $date_time_settings[ $day_value ][ $i ] );
							}
							$k++;
						}
					}
				}
			}
			if ( isset( $new_day_arr ) && count( $new_day_arr ) > 0 ) {
				
			    foreach ( $new_day_arr as $wkey => $wvalue ) {
					
			        if( $wvalue == 'on' ) {
						
			            if ( isset( $_POST['booking_enable_time'] ) && $_POST['booking_enable_time'] == "on" ) {
							$j=1;
							
							if ( isset( $woo_booking_dates['booking_time_settings'] ) && is_array( $woo_booking_dates['booking_time_settings'] ) ) { 
								
							    if ( array_key_exists( $wkey, $woo_booking_dates['booking_time_settings'] ) ) {
									
							        foreach ( $woo_booking_dates['booking_time_settings'][ $wkey ] as $dtkey => $dtvalue ) {
										$date_time_settings[ $wkey ][ $j ] = $dtvalue;
										$j++;
									}
								}
							}
							$k = 1;
							
							for( $i =( $j + 1 ); $i <= ( $j + $slot_count_value ); $i++ ) {
								
							    if( isset( $_POST['booking_from_slot_hrs'][ $k ] ) && $_POST['booking_from_slot_hrs'][ $k ] != 0 ) {
									$time_settings['slot_price']   = $_POST['booking_price_time'][ $k ];
									$date_time_settings[ $wkey ][ $i ] = array_merge( $time_settings, $date_time_settings[ $wkey ][ $i ] );
								}
								$k++;
							}
						}
					}
				}
			}
			return $date_time_settings;
		}
		
		function timeslot_price_column_name( $product_id ) {
			$booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true);
			
			if ( isset( $booking_settings['booking_enable_time'] ) && $booking_settings['booking_enable_time'] == 'on' ) {
				?>
				<th> <?php _e( 'Time Slot Price', 'woocommerce-booking' ); ?> </th>
				<?php
			} 
		}
		
		function timeslot_price_column_value( $slot_price, $date_time_settings ) {
			
		    foreach ( $date_time_settings as $k => $v ) {
				
		        if ( isset( $v['slot_price'] ) ) {
					$slot_price[] = $v['slot_price'];
				}else {
					$slot_price[] = 0;
				}
			}
			return $slot_price;
		}
		
		function timeslot_price_div( $product_id ) {
			$variable_timeslot_price     = bkap_timeslot_price::get_timeslot_variable_price( $product_id );
			$currency_symbol             = get_woocommerce_currency_symbol();
		    if( has_filter( 'bkap_show_addon_price' ) ) {
				$show_price = apply_filters( 'bkap_show_addon_price', '' );
            } else {
			    $show_price = 'show';
            }
			$print_code                  = '<div id=\"show_addon_price\" name=\"show_addon_price\" class=\"show_addon_price\" style=\"display:'.$show_price.';\">'.$currency_symbol.'0<\/div>';
		
			if( isset( $variable_timeslot_price ) && $variable_timeslot_price == 'yes' ):
				print('<script type="text/javascript">
					if (jQuery("#show_addon_price").length == 0) {
						document.write("'.$print_code.'");
					} 
					</script>');
			endif;
		}
		
		function timeslot_hidden_fields( $product_id ) {
			$variable_timeslot_price = bkap_timeslot_price::get_timeslot_variable_price( $product_id );
			print('<input type="hidden" id="wapbk_hidden_variable_timeslot_price" name="wapbk_hidden_variable_timeslot_price" value="'.$variable_timeslot_price.'">');
		}
		
		function get_timeslot_variable_price( $product_id ) {
			$slot_price          = array();
			$booking_settings    = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
			
			if ( isset( $booking_settings['booking_enable_time'] ) && $booking_settings['booking_enable_time'] == 'on' ) {
				$time_slot_arr = $booking_settings['booking_time_settings'];
				
				if( $time_slot_arr == '' || $time_slot_arr == '' ) {
					$time_slot_arr = array();
				}
				
				foreach ( $time_slot_arr as $key => $value ) {
					
				    foreach ( $value as $k => $v ) {
						
				        if ( isset( $v['slot_price'] ) && $v['slot_price'] > 0 ) {
							$slot_price[] = $v['slot_price'];
						}
					}
				}
			}
			
			$variable_timeslot_price = 'no';
			
			if( count( $slot_price ) > 0 ) {
				$variable_timeslot_price = 'yes';
			}
			return $variable_timeslot_price;
		}
		
		function timeslot_display_updated_price( $product_id, $booking_date, $variation_id ) {
			$global_settings     = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
			// product type
			$_product            = get_product($product_id);
			$product_type        = $_product->product_type;
			// Time slot
			$time_slot           = '';
			
			if ( isset( $_POST['timeslot_value'] ) ) {
				$time_slot = $_POST['timeslot_value'];
			}
			if ( $product_type == 'grouped' ) {
			
				$currency_symbol    = get_woocommerce_currency_symbol();
				$has_children       = $price_str = "";
				$price_arr          = array();
				
				if ($_product->has_child()) {
					$has_children = "yes";
					$child_ids = $_product->get_children();
				}
				
				$quantity_grp_str = $_POST['quantity'];
			
				$quantity_array = explode( ",", $quantity_grp_str );
				$i              = 0;
				$raw_price_str  = '';
				foreach ( $child_ids as $k => $v ) {
					$child_product         = get_product( $v );
					$product_type_child    = $child_product->product_type;
					$time_slot_price       = $this->get_price( $v, 0, $product_type_child, $booking_date, $time_slot, 'product' );
					$final_price           = $time_slot_price * $quantity_array[ $i ];
					$raw_price             = $final_price;
					
					if ( function_exists( 'icl_object_id' ) ) {
					    global $woocommerce_wpml;
					    // Multi currency is enabled
					    if ( isset( $woocommerce_wpml->settings[ 'enable_multi_currency' ] ) && $woocommerce_wpml->settings[ 'enable_multi_currency' ] == '2' ) {
					        $raw_price = apply_filters( 'wcml_raw_price_amount', $final_price );
                            $final_price = apply_filters( 'wcml_formatted_price', $final_price );
					    } else {
					        $final_price = wc_price( $final_price );
					    }
					} else {
						$final_price = wc_price( $final_price );
					}
					
					$raw_price_str .= $v . ":" . $raw_price . ",";
					$price_str .= $child_product->get_title() . ": " . $final_price . "<br>";
					$i++;
				}
				$time_slot_price = $price_str;
			}
			else {
				$time_slot_price = $this->get_price( $product_id, $variation_id, $product_type, $booking_date, $time_slot, 'product' );
			}
			if ( ( $time_slot_price == "" || $time_slot_price == 0 ) && ( isset( $_POST['special_booking_price'] ) && $_POST['special_booking_price'] != "" ) ){
				$time_slot_price = $_POST['special_booking_price'];
				$raw_price_str = $_POST[ 'grouped_raw_price' ];
			}
			
			if ( ( function_exists( 'is_bkap_seasonal_active' ) && is_bkap_seasonal_active() ) || ( function_exists('is_bkap_deposits_active') && is_bkap_deposits_active() ) || ( function_exists('is_bkap_multi_time_active') && is_bkap_multi_time_active() ) ) {
				$_POST['price'] = $time_slot_price;
			}else {
			    if ( $product_type != 'grouped' ) {
    			    if ( isset( $_POST['quantity'] ) && $_POST['quantity'] != 0 ) {
    					$time_slot_price = $time_slot_price * $_POST['quantity'];
    				}
    				
    				if ( isset( $global_settings->enable_rounding ) && $global_settings->enable_rounding == "on" ) {
    					$time_slot_price = round( $time_slot_price );
    				}
    				
    				// Save the actual Bookable amount, as a raw amount
    				// If Multi currency is enabled, convert the amount before saving it
    				$total_price = $time_slot_price;
    				if ( function_exists( 'icl_object_id' ) ) {
    				    $total_price = apply_filters( 'wcml_raw_price_amount', $time_slot_price );
    				}
    				print( 'jQuery( "#total_price_calculated" ).val(' . $total_price . ');' );
    				// save the price in a hidden field to be used later
    				print( 'jQuery( "#bkap_price_charged" ).val(' . $total_price. ');' );
    				
    				// format the price
    				$formatted_price = wc_price( $time_slot_price );
    				
    				if ( function_exists('icl_object_id') ) {
    				    global $woocommerce_wpml;
    				    // Multi currency is enabled
    				    if ( isset( $woocommerce_wpml->settings[ 'enable_multi_currency' ] ) && $woocommerce_wpml->settings[ 'enable_multi_currency' ] == '2' ) {
    					   $formatted_price = apply_filters( 'wcml_formatted_price', $time_slot_price );
    				    }
    				}
			    } else {
			        $formatted_price = $time_slot_price;
			        print( 'jQuery( "#total_price_calculated" ).val("' . addslashes( $raw_price_str ) . '");' );
			        // save the price in a hidden field to be used later
			        print( 'jQuery( "#bkap_price_charged" ).val("' . addslashes( $raw_price_str ) . '");' );
			    } 
				
				// display the price on the front end product page
				print( 'jQuery( "#show_addon_price" ).html( "' . addslashes( $formatted_price ) . '");' );
				die();
			}
		}
		
		function add_cart_item( $cart_item ) {
			global $wpdb;
			
			$product_type = 'simple';
			
			if ( $cart_item['variation_id'] != '' ) {
				$product_type = 'variable';
			}
			
		/*	if(isset($_POST['special_booking_price']) && $_POST['special_booking_price'] != "" && $_POST['special_booking_price'] != 0){
				$price = $_POST['special_booking_price'];
			}
			else{*/
				$price = bkap_common::bkap_get_price( $cart_item['product_id'], $cart_item['variation_id'], $product_type );
		//	}
			
			// Adjust price if addons are set
			if ( isset( $cart_item['bkap_booking'] ) ) :
				$extra_cost = 0;
				
				foreach ( $cart_item['bkap_booking'] as $addon ) :
					
					if ( isset( $addon['price'] ) && $addon['price'] > 0 ) {
						$extra_cost += $addon['price'];
					}
				endforeach;
		
				$extra_cost = $extra_cost - $price;
				$cart_item['data']->adjust_price( $extra_cost );
			endif;		
			
			return $cart_item;
		}
		
		function get_price( $product_id, $variation_id, $product_type, $booking_date, $time_slot, $called_from ) {
			// set the slot price as the product base price
			$time_slot_price = $time_slot_price_total = 0;
			
			if (isset($_POST['special_booking_price']) && $_POST['special_booking_price'] != "" && $_POST['special_booking_price'] != 0){
				$time_slot_price = $_POST['special_booking_price'];
			}else{
				$time_slot_price = bkap_common::bkap_get_price( $product_id, $variation_id, $product_type );
			}
			
			$booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
			
			if ( $time_slot != '' ) {
				// Check if multiple time slots are enabled
				$seperator_pos = strpos($time_slot,",");
				
				if ( isset( $seperator_pos ) && $seperator_pos != "" ) {
					$time_slot_array = explode( ",", $time_slot );
				}else {
					$time_slot_array   = array();
					$time_slot_array[] = $time_slot;
				}
				
				for ( $i = 0; $i < count( $time_slot_array ); $i++ ) {
					// split the time slot into from and to time
					$timeslot_explode      = explode( '-', $time_slot_array[ $i ] );
					$timeslot_explode[0]   = date( 'G:i', strtotime( $timeslot_explode[0] ) );
					
					if ( isset( $timeslot_explode[1] ) && $timeslot_explode[1] != '' ) {
						$timeslot_explode[1] = date( 'G:i', strtotime( $timeslot_explode[1] ) );
					}
					// split frm hrs in hrs and min
					$from_hrs  = explode( ':', $timeslot_explode[0] );
					// similarly for to time, but first default it to 0:00, so it works for open ended time slots as well
					$to_hrs    = array(
							'0' => '0',
							'1' => '00');
					
					if ( isset( $timeslot_explode[1] ) && $timeslot_explode[1] != '' ) {
						$to_hrs = explode( ':', $timeslot_explode[1] );
					}
					
					if ( isset( $booking_settings['booking_time_settings'] ) && count( $booking_settings['booking_time_settings'] ) > 0 ) {
						
					    foreach ($booking_settings['booking_time_settings'] as $key => $value ) {
							// match the booking date as specific overrides recurring
							$booking_date_to_check = date( 'j-n-Y', strtotime( $booking_date ) );
							
							if ( $key == $booking_date_to_check ) {
								
							    foreach ( $value as $k => $v ) {
									$price = 0;
									
									// match the time slot
									if ( ( intval( $from_hrs[0] ) == intval( $v['from_slot_hrs'] ) ) && ( intval( $from_hrs[1] ) == intval( $v['from_slot_min'] ) ) && ( intval( $to_hrs[0] ) == intval( $v['to_slot_hrs'] ) ) && ( intval( $to_hrs[1] ) == intval( $v['to_slot_min'] ) ) ) {
										
										// fetch and save the price
										if ( isset( $v['slot_price'] ) && $v['slot_price'] != '' ) {
											$price = $v['slot_price'];
											
											if ( isset( $called_from ) && $called_from == 'cart' ) {
												$price = apply_filters( 'wcml_raw_price_amount', $v['slot_price'] );
											}
											
											$time_slot_price_total += $price;
											
										} else {
											$time_slot_price_total += $time_slot_price;
										}
									}
								}
							}else {
								// Get the weekday
								$weekday            =   date( 'w', strtotime( $booking_date ) );
								$booking_weekday    =   'booking_weekday_' . $weekday;
								//match the weekday
								if ( $key == $booking_weekday ) {
									
								    foreach ( $value as $k => $v ) {
										$price = 0;
										
										// match the time slot
										if ( ( intval( $from_hrs[0] ) == intval( $v['from_slot_hrs'] ) ) && ( intval( $from_hrs[1] ) == intval( $v['from_slot_min'] ) ) && ( intval( $to_hrs[0] ) == intval($v['to_slot_hrs'])) && (intval($to_hrs[1]) == intval($v['to_slot_min']))) {
											
											// fetch and save the price
											if ( isset($v['slot_price']) && $v['slot_price'] != '') {
												$price = $v['slot_price'];
												
												if ( isset( $called_from ) && $called_from == 'cart' ) {
													$price = apply_filters( 'wcml_raw_price_amount', $v['slot_price'] );
												}
												
												$time_slot_price_total += $price;
											} else {
												$time_slot_price_total += $time_slot_price;
											}
										}
									}
								}
							}
						}
					}
				}
				if ($time_slot_price_total != 0) {
					$time_slot_price = $time_slot_price_total;
					$time_slot_price = $time_slot_price / count($time_slot_array);
				}
			}
			return $time_slot_price;
		}
	}
}
$bkap_timeslot_price = new bkap_timeslot_price();