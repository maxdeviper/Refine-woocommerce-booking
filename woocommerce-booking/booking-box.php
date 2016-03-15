<?php 
include_once('bkap-common.php');

class bkap_booking_box_class{
        
	/****************************************************
    * This function updates the booking settings for each 
    * product in the wp_postmeta table in the database . 
    * It will be called when update / publish button clicked on admin side.
    *****************************************************/
       public static function bkap_process_bookings_box( $post_id, $post ) {

               global $wpdb;

               // Save Bookings
               $product_bookings        =   array();
               $duplicate_of            =   bkap_common::bkap_get_product_id( $post_id );
               
               $woo_booking_dates       =   get_post_meta(  $duplicate_of, 'woocommerce_booking_settings', true );
            
               $enable_inline_calendar  =   $enable_date = $enable_multiple_day = $specific_booking_chk = $recurring_booking_chk = $enable_minimum_day_booking_multiple = "";

               if ( isset( $_POST['enable_inline_calendar'] ) ) {
                       $enable_inline_calendar = $_POST['enable_inline_calendar'];
               }
               
               if ( isset( $_POST['booking_enable_date'] ) ) {
                       $enable_date = $_POST['booking_enable_date'];
               }

               if ( isset( $_POST['booking_enable_multiple_day'] ) ) {
                       $enable_multiple_day = $_POST['booking_enable_multiple_day'];
               }
               
               //product level - minimum booking for multiple days
               if ( isset( $_POST['minimum_day_booking_multiple'] ) ) {
               	       $enable_minimum_day_booking_multiple = $_POST['minimum_day_booking_multiple'];
               }
               
               $booking_minimum_number_days_multiple = 0;
               
               if ( isset( $_POST['booking_minimum_number_days_multiple'] ) ) {
               	      $booking_minimum_number_days_multiple = $_POST['booking_minimum_number_days_multiple'];
               }
               
               if ( isset( $_POST['booking_specific_booking'] ) ) {
                       $specific_booking_chk = $_POST['booking_specific_booking'];
               }

               if ( isset( $_POST['booking_recurring_booking'] ) ) {
                       $recurring_booking_chk = $_POST['booking_recurring_booking'];
               }
               
               $weekdays = bkap_get_book_arrays( 'weekdays' );
               
               // If time is enabled.. then we need to make sure that atleast one booking method is enabled.
               // If not then pls enable all the weekdays by default
               
               if ( isset( $_POST['booking_enable_time'] ) && $_POST['booking_enable_time'] == 'on' ) {
               	
                   if ( $recurring_booking_chk == '' && $specific_booking_chk == '' ) {
               		
                       foreach ( $weekdays as $n => $day_name ) {
               			$_POST[ $n ] = 'on';
               		   }
               		$recurring_booking_chk = 'on';
               	   }
               }
               $booking_days    =   array();
               $new_day_arr     =   array();
               
               foreach ( $weekdays as $n => $day_name ) {
               		
                   if ( isset( $woo_booking_dates['booking_recurring'] ) && count( $woo_booking_dates['booking_recurring'] ) > 1 ) {
               			
                       if ( isset( $_POST[ $n ] ) && $_POST[ $n ] == 'on' || isset( $_POST[ $n ] ) && $_POST[ $n ] == '' ) {
               				$new_day_arr[ $n ] = $_POST[ $n ];
               			}
               			
               		   if ( isset( $_POST[ $n ] ) && $_POST[ $n ] == 'on' ) {
               				$booking_days[ $n ] = $_POST[ $n ];
               		   }else {
               				$booking_days[ $n ] = $woo_booking_dates['booking_recurring'][ $n ];
               		   }
               		   
               	    }else {
               			
               	        if ( isset( $_POST[ $n ] ) ) {
               				$new_day_arr[ $n ]   =   $_POST[ $n ];
               				$booking_days[ $n ]  =   $_POST[ $n ];
               			} else {
               				$new_day_arr[ $n ]   =   $booking_days[ $n ] = '';
               			}
               			
               		}
               }

               $specific_booking = '';
               
               if ( isset( $_POST['booking_specific_date_booking'] ) ) {
                       $specific_booking = $_POST['booking_specific_date_booking'];
               }
               
               if ( $specific_booking != '' ) {
                       $specific_booking_dates = explode( ",", $specific_booking );
               }else{
                       $specific_booking_dates = array();
               }
               
               $specific_stored_days = array();
               
               if ( isset( $woo_booking_dates['booking_specific_date'] ) && count( $woo_booking_dates['booking_specific_date'] ) > 0 ) $specific_stored_days = $woo_booking_dates['booking_specific_date'];

               foreach ( $specific_booking_dates as $key => $value ) {
                       if ( trim( $value != "" ) ) $specific_stored_days[] = $value;
               }
               
               $without_date = $lockout_date = $product_holiday = $enable_time = $slot_count_value = '';
               
               if ( isset( $_POST['booking_purchase_without_date'] ) ) {
                       $without_date = $_POST['booking_purchase_without_date'];
               }
               
               if ( isset( $_POST['booking_lockout_date'] ) ) {
                       $lockout_date = $_POST['booking_lockout_date'];
               }
               
               if( isset( $_POST['booking_product_holiday'] ) ) {
                       $product_holiday = $_POST['booking_product_holiday'];
               }
               
               if ( isset( $_POST['booking_enable_time'] ) ) {
                       $enable_time = $_POST['booking_enable_time'];
               }
               
               if( isset( $_POST['wapbk_slot_count'] ) ) {
                       $slot_count          =   explode( "[", $_POST['wapbk_slot_count'] );
                       $slot_count_value    =   intval( $slot_count[1] );
               }
               
               $date_time_settings  =   array();
               $time_settings       =   array();
               if( $specific_booking != "" ) {
                       
                   foreach ( $specific_booking_dates as $day_key => $day_value ) {
                               $date_tmstmp = strtotime( $day_value );
                               $date_save   = date( 'Y-m-d', $date_tmstmp );
                                       if ( isset( $_POST['booking_enable_time'] ) && $_POST['booking_enable_time'] == "on" ) {
                                               $j=1;
                                               
                                               if( isset( $woo_booking_dates['booking_time_settings'] ) && is_array( $woo_booking_dates['booking_time_settings'] ) ) {
                                                       
                                                   if ( array_key_exists( $day_value, $woo_booking_dates['booking_time_settings'] ) ) {
                                                               
                                                           foreach ( $woo_booking_dates['booking_time_settings'][$day_value] as $dtkey => $dtvalue ) {
                                                                       $date_time_settings[ $day_value ][ $j ] = $dtvalue;
                                                                       $j++;
                                                           }
                                                   }
                                               }
                                               
                                               $k = 1;
                                               for( $i=( $j + 1 ); $i <= ( $j + $slot_count_value ); $i++ ) {
                                                       if( isset( $_POST['booking_from_slot_hrs'][ $k ]) && $_POST['booking_from_slot_hrs'][ $k ] != 0 ) {
                                                               $time_settings['from_slot_hrs']  =   $_POST['booking_from_slot_hrs'][ $k ];
                                                               $time_settings['from_slot_min']  =   $_POST['booking_from_slot_min'][ $k ];
                                                               $time_settings['to_slot_hrs']    =   $_POST['booking_to_slot_hrs'][ $k ];
                                                               $time_settings['to_slot_min']    =   $_POST['booking_to_slot_min'][ $k ];
                                                               $time_settings['booking_notes']  =   $_POST['booking_time_note'][ $k ];
                                                               $time_settings['lockout_slot']   =   $_POST['booking_lockout_time'][ $k ];
                                                               
                                                               if( isset( $_POST['booking_global_check_lockout'][ $k ] ) ) {
                                                                       $time_settings['global_time_check'] = $_POST['booking_global_check_lockout'][ $k ];
                                                               } else {
                                                                       $time_settings['global_time_check'] = '';
                                                               }
                                                               
                                                               $date_time_settings[ $day_value ][ $i ]  =   $time_settings;
                                                               $from_time                               =   $_POST['booking_from_slot_hrs'][ $k ].":".$_POST['booking_from_slot_min'][ $k ];
                                                               $to_time                                 =   "";
                                                               
                                                               if( isset( $_POST['booking_to_slot_hrs'][ $k ] ) && $_POST['booking_to_slot_hrs'][ $k ] != 0 ) {
                                                                       $to_time = $_POST['booking_to_slot_hrs'][ $k ].":".$_POST['booking_to_slot_min'][ $k ];
                                                               }

                                                               $insert              =   "YES";
                                                               $available_booking   =   $_POST['booking_lockout_time'][ $k ];
                                                               // Fetch the orignal lockout value set, so that the available bookings can be re-calculated
                                                               $query_lockout       =   "SELECT available_booking, total_booking, status FROM `".$wpdb->prefix."booking_history`
                                                              						    WHERE post_id = '".$duplicate_of."'
                                                             						    AND start_date = '".$date_save."'
                                                             						    AND from_time = '".$from_time."'
                                                             						    AND to_time = '".$to_time."'";
                                                               
                                                               $lockout_results     =   $wpdb->get_results( $query_lockout );
                                                               $change_in_lockout   =   0;
                                                               
                                                               if ( isset( $lockout_results ) && count( $lockout_results ) > 0 ) {
                                                               		// If more than 1 record is present, find the active record
                                                               		foreach ( $lockout_results as $key => $value ) {
                                                               			if ( $value->status == '' ) {
                                                               				$insert              =   "NO";
                                                               				$change_in_lockout   =   $_POST['booking_lockout_time'][ $k ] - $value->total_booking;
                                                               				break;
                                                               			}
                                                               			else {
                                                               				$change_in_lockout   =   $_POST['booking_lockout_time'][ $k  ] - $value->total_booking;
                                                               				$available_booking   =   $value->available_booking + $change_in_lockout;
                                                               			}
                                                               		}
                                                               }
                                                                 
                                                               if ( $insert == "YES" ) {
	                                                               	$query_insert  =   "INSERT INTO `".$wpdb->prefix."booking_history`
                    	                                                               (post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
                    	                                                               VALUES (
                    	                                                               '".$duplicate_of."',
                    	                                                               '',
                    	                                                               '".$date_save."',
                    	                                                               '0000-00-00',
                    	                                                               '".$from_time."',
                    	                                                               '".$to_time."',
                    	                                                               '".$_POST['booking_lockout_time'][$k]."',
                    	                                                               '".$available_booking."' )";
	                                                               	$wpdb->query( $query_insert );
                                                               }
                                                               else {
	                                                               	$query_update  =   "UPDATE `".$wpdb->prefix."booking_history`
					                                                               	   SET total_booking = '".$_POST['booking_lockout_time'][$k]."',
					                                                               	   available_booking = available_booking + '".$change_in_lockout."'
					                                                               	   WHERE post_id = '".$duplicate_of."'
					                                                               	   AND start_date = '".$date_save."'
					                                                               	   AND from_time = '".$from_time."'
					                                                               	   AND to_time = '".$to_time."'
	                                                               					   AND status = ''";
	                                                               	$wpdb->query( $query_update );
                                                               }
                                                       }
                                                       $k++;
                                               }
                                       }
                                       else {
                                       	$insert             =   "YES";
                                       	$available_booking  =   $_POST['booking_lockout_date'];
                                       	// Fetch the orignal lockout value set, so that the available bookings can be re-calculated
                                       	$query_lockout      =   "SELECT available_booking, total_booking, status FROM `".$wpdb->prefix."booking_history`
					                                       	    WHERE post_id = '".$duplicate_of."'
					                                       	    AND start_date = '".$date_save."'";
                                       	 
                                       	$lockout_results    =   $wpdb->get_results($query_lockout);
                                       
                                       	$change_in_lockout  =   0;
                                       	
                                       	if ( isset( $lockout_results ) && count( $lockout_results ) > 0 ) {
                                       		// If more than 1 record is present, find the active record
                                       		foreach ( $lockout_results as $key => $value ) {
                                       			
                                       		    if ( $value->status == '' ) {
                                       				$insert              =   "NO";
                                       				$change_in_lockout   =   $_POST['booking_lockout_date'] - $value->total_booking;
                                       				break;
                                       			}
                                       			else {
                                       				$change_in_lockout   =   $_POST['booking_lockout_date'] - $value->total_booking;
                                       				$available_booking   =   $value->available_booking + $change_in_lockout;
                                       			}
                                       			
                                       		}
                                       	}
                                       
                                       	if ( $insert == "YES" ) {
                                       		$query_insert  =   "INSERT INTO `".$wpdb->prefix."booking_history`
                                                           		(post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
                                                           		VALUES (
                                                           		'".$duplicate_of."',
                                                           		'',
                                                           		'".$date_save."',
                                                           		'0000-00-00',
                                                           		'',
                                                           		'',
                                                           		'".$_POST['booking_lockout_date']."',
                                                           		'".$available_booking."' )";
                                       		$wpdb->query( $query_insert );
                                       	}
                                       	else {
                                       		// Update the existing record so that lockout is managed and orders do not go missing frm the View bookings page
                                       		$query_update  =   "UPDATE `".$wpdb->prefix."booking_history`
    				                                       		SET total_booking = '".$_POST['booking_lockout_date']."',
    				                                       		available_booking = available_booking + '".$change_in_lockout."'
    				                                       		WHERE post_id = '".$duplicate_of."'
    				                                       		AND start_date = '".$date_save."'
    				                                       		AND status = ''";
                                       		$wpdb->query( $query_update );
                                       	}
                                       	
                                   }

                               }
                       }
                       if ( count($new_day_arr) >= 1 ) {
                               
                           foreach ( $new_day_arr as $wkey => $wvalue ) {
                                       
                                   if( $wvalue == 'on' ) {
                                               
                                           if ( isset( $_POST['booking_enable_time'] ) && $_POST['booking_enable_time'] == "on" ) {
                                                       $j = 1;
                                                       
                                                       if( isset( $woo_booking_dates['booking_time_settings'] ) && is_array( $woo_booking_dates['booking_time_settings'] ) ) {
                                                               
                                                           if ( array_key_exists( $wkey, $woo_booking_dates['booking_time_settings'] ) ) {
                                                                       
                                                                   foreach ( $woo_booking_dates['booking_time_settings'][ $wkey ] as $dtkey => $dtvalue ) {
                                                                               $date_time_settings[ $wkey ][ $j ] = $dtvalue;
                                                                               $j++;
                                                                   }
                                                           }
                                                       }
                                                       
                                                       $k = 1;
                                                       for( $i = ( $j + 1 ); $i <= ( $j + $slot_count_value ); $i++ ) {                                                           
                                                               if( isset( $_POST['booking_from_slot_hrs'][ $k ] ) && $_POST['booking_from_slot_hrs'][ $k ] != 0 ) {
                                                                       $time_settings['from_slot_hrs']  =   $_POST['booking_from_slot_hrs'][ $k ];
                                                                       $time_settings['from_slot_min']  =   $_POST['booking_from_slot_min'][ $k ];
                                                                       $time_settings['to_slot_hrs']    =   $_POST['booking_to_slot_hrs'][ $k ];
                                                                       $time_settings['to_slot_min']    =   $_POST['booking_to_slot_min'][ $k ];
                                                                       $time_settings['booking_notes']  =   $_POST['booking_time_note'][ $k ];
                                                                       $time_settings['lockout_slot']   =   $_POST['booking_lockout_time'][ $k ];
                                                                       
                                                                       if( isset( $_POST['booking_global_check_lockout'][ $k ] ) ) {
                                                                               $time_settings['global_time_check'] = $_POST['booking_global_check_lockout'][ $k ];
                                                                       } else {
                                                                               $time_settings['global_time_check'] = '';
                                                                       }
                                                                       
                                                                       $date_time_settings[ $wkey ][ $i ]   =   $time_settings;
                                                                       $from_time                           =   $_POST['booking_from_slot_hrs'][ $k ].":".$_POST['booking_from_slot_min'][ $k ];
                                                                       $to_time                             =   "";
                                                                       
                                                                       if( isset( $_POST['booking_to_slot_hrs'][ $k ] ) && $_POST['booking_to_slot_hrs'][ $k ] != 0 ) {
                                                                               $to_time = $_POST['booking_to_slot_hrs'][ $k ].":".$_POST['booking_to_slot_min'][ $k ];
                                                                       }
                                                                       
                                                                       $insert              =   "YES";
                                                                       $available_booking   =   $_POST['booking_lockout_time'][ $k ];
                                                                       // Fetch the orignal lockout value set, so that the available bookings can be re-calculated
                                                                       $query_lockout       =   "SELECT total_booking, available_booking,status FROM `".$wpdb->prefix."booking_history`
                                                                               					WHERE post_id = '".$duplicate_of."'
                                                                               					AND weekday = '".$wkey."'
                                                                               					AND start_date = '0000-00-00'
                                                                               					AND from_time = '".$from_time."'
                                                                               					AND to_time = '".$to_time."'";
                                                                       $lockout_results     =   $wpdb->get_results($query_lockout);
                                                                       $change_in_lockout   =   0;
                                                                       
                                                                       if ( isset( $lockout_results ) && count( $lockout_results ) > 0 ) {
	                                                                       	// If more than 1 record is present, find the active record
	                                                                       	foreach ( $lockout_results as $key => $value ) {
	                                                                       		
	                                                                       	    if ( $value->status == '' ) {
	                                                                       			$insert              =   "NO";
	                                                                       			$change_in_lockout   =   $_POST['booking_lockout_time'][ $k ] - $value->total_booking;
	                                                                       			break;
	                                                                       		}
	                                                                       		else {
	                                                                       			$change_in_lockout = $_POST['booking_lockout_time'][ $k ] - $value->total_booking;
	                                                                       			$available_booking = $value->available_booking + $change_in_lockout;
	                                                                       		}
	                                                                       	}
                                                                       }
                                                                       
                                                                       if ( $insert == "YES" ) {
	                                                                       	$query_insert  =   "INSERT INTO `".$wpdb->prefix."booking_history`
    					                                                                       	(post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
    					                                                                       	VALUES (
    					                                                                       	'".$duplicate_of."',
    					                                                                       	'".$wkey."',
    					                                                                       	'0000-00-00',
    					                                                                       	'0000-00-00',
    					                                                                       	'".$from_time."',
    					                                                                       	'".$to_time."',
    					                                                                       	'".$_POST['booking_lockout_time'][$k]."',
    					                                                                       	'".$available_booking."' )";
	                                                                       	$wpdb->query( $query_insert );
                                                                       }
                                                                       else {
	                                                                       	// Update the existing record so that lockout is managed and orders do not go missing frm the View bookings page
	                                                                       	$query_update  =   "UPDATE `".$wpdb->prefix."booking_history`
        				                                                                       	SET total_booking = '".$_POST['booking_lockout_date']."',
        				                                                                       	available_booking = available_booking + '".$change_in_lockout."'
        				                                                                       	WHERE post_id = '".$duplicate_of."'
        				                                                                       	AND weekday = '".$wkey."'
        				                                                                       	AND start_date = '0000-00-00'
        				                                                                       	AND status = ''";
	                                                                       	$wpdb->query( $query_update );
                                                                       }
																		// Update the existing records for the dates
																		$query_update     =   "UPDATE `".$wpdb->prefix."booking_history`
        																						SET total_booking = '".$_POST['booking_lockout_time'][$k]."',
        																						available_booking = available_booking + '".$change_in_lockout."',
        																						status = ''
        																						WHERE post_id = '".$duplicate_of."'
        																						AND weekday = '".$wkey."'
        																						AND from_time = '".$from_time."'
        																						AND to_time = '".$to_time."'
        																						AND start_date <> '0000-00-00'";
																		$wpdb->query( $query_update );
                                                               }	
                                                               $k++;	
                                                       }
                                               } else {
		                                               	$insert               =   "YES";
		                                               	$available_booking    =   $_POST['booking_lockout_date'];
		                                               	// Fetch the orignal lockout value set, so that the available bookings can be re-calculated
		                                               	$query_lockout        =   "SELECT total_booking,available_booking,status FROM `".$wpdb->prefix."booking_history`
        							                                              WHERE post_id = '".$duplicate_of."'
        							                                              AND weekday = '".$wkey."'
        							                                              AND start_date = '0000-00-00'";
		                                               	$lockout_results      =   $wpdb->get_results($query_lockout);
		                                               	$change_in_lockout    =   0;
		                                               	
		                                               if ( isset( $lockout_results ) && count( $lockout_results ) > 0 ) {
															// If more than 1 record is present, find the active record
															foreach ( $lockout_results as $key => $value ) {
															    
																if ( $value->status == '' ) {
																	$insert            =   "NO";
																	$change_in_lockout =   $_POST['booking_lockout_date'] - $value->total_booking;
																	break;
																}
																else {
																	$change_in_lockout =   $_POST['booking_lockout_date'] - $value->total_booking;
																	$available_booking =   $value->available_booking + $change_in_lockout;
																}
															}
														}
														
														if ( $insert == "YES" ) {
															$query_insert    =  "INSERT INTO `".$wpdb->prefix."booking_history`
                    															(post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
                    															VALUES (
                    															'".$duplicate_of."',
                    															'".$wkey."',
                    															'0000-00-00',
                    															'0000-00-00',
                    															'',
                    															'',
                    															'".$_POST['booking_lockout_date']."',
                    															'".$available_booking."' )";
															$wpdb->query( $query_insert );
														}
														else {
															// Update the existing record so that lockout is managed and orders do not go missing frm the View bookings page
															$query_update    =  "UPDATE `".$wpdb->prefix."booking_history`
                    															SET total_booking = '".$_POST['booking_lockout_date']."',
                    															available_booking = available_booking + '".$change_in_lockout."'
                    															WHERE post_id = '".$duplicate_of."'
                    															AND weekday = '".$wkey."'
                    															AND start_date = '0000-00-00'
                    															AND status = ''";
															$wpdb->query( $query_update );
														}
                                                       // Update the existing records for the dates
                                                       $query_update        =   "UPDATE `".$wpdb->prefix."booking_history`
    					                                                        SET total_booking = '".$_POST['booking_lockout_date']."',
    					                                                        available_booking = available_booking + '".$change_in_lockout."',
    					                                                        status = ''
    					                                                        WHERE post_id = '".$duplicate_of."'
    					                                                        AND weekday = '".$wkey."'
                                                           					    AND start_date <> '0000-00-00'";
    					                                                      
                                                       $wpdb->query( $query_update );
                                               }

                                       }
                               }
                       }

                       $new_time_settings   = $woo_booking_dates;
                       $date_time_settings  = ( array ) apply_filters( 'bkap_save_slot_field_settings', $date_time_settings, $duplicate_of );
                       
                       foreach ( $date_time_settings as $dtkey => $dtvalue ) {
                   	  		$new_time_settings['booking_time_settings'][ $dtkey ] = $dtvalue;
                       }
                       
					// Booking Time Period Tab
                       $minimum_number_days = $maximum_number_days = $booking_date_range_type = $booking_start_date_range = $booking_end_date_range = '';
                       $booking_range_recurring = '';
                       $booking_range_recurring_years = '';
                       if ( isset( $_POST['booking_minimum_number_days'] ) ) {
                       	$minimum_number_days = $_POST['booking_minimum_number_days'];
                       }
                       
                       if ( isset( $_POST['booking_maximum_number_days'] ) ){
                       	$maximum_number_days = $_POST['booking_maximum_number_days'];
                       }
                       
                       if ( isset( $_POST['booking_date_range_type'] ) ) {
                       		$booking_date_range_type = $_POST['booking_date_range_type'];
                       }
                       
                       if ( isset( $_POST['booking_start_date_range'] ) ) {
                       		$booking_start_date_range = $_POST['booking_start_date_range'];
                       }
                       
                       if ( isset( $_POST['booking_end_date_range'] ) ) {
                       		$booking_end_date_range = $_POST['booking_end_date_range'];
                       }
                       
                       if ( isset( $_POST[ 'booking_range_recurring' ] ) ) {
                           $booking_range_recurring = $_POST[ 'booking_range_recurring' ];
                       }
                        
                       if ( isset( $_POST[ 'booking_range_recurring_years' ] ) ) {
                           $booking_range_recurring_years = $_POST[ 'booking_range_recurring_years' ];
                       }
              
               $booking_settings                                            = array();
               $booking_settings[ 'booking_enable_date' ]                     = $enable_date;
               $booking_settings[ 'enable_inline_calendar' ]                  = $enable_inline_calendar;
               $booking_settings[ 'booking_enable_multiple_day' ]             = $enable_multiple_day;
               $booking_settings[ 'enable_minimum_day_booking_multiple' ]     = $enable_minimum_day_booking_multiple;
               $booking_settings[ 'booking_minimum_number_days_multiple' ]    = $booking_minimum_number_days_multiple;
               $booking_settings[ 'booking_specific_booking' ]                = $specific_booking_chk;
               $booking_settings[ 'booking_recurring_booking' ]               = $recurring_booking_chk;
               $booking_settings[ 'booking_recurring' ]                       = $booking_days;
               $booking_settings[ 'booking_specific_date' ]                   = $specific_stored_days;
               $booking_settings[ 'booking_minimum_number_days' ]             = $minimum_number_days;
               $booking_settings[ 'booking_maximum_number_days' ]             = $maximum_number_days;
               $booking_settings[ 'booking_purchase_without_date' ]           = $without_date;
               $booking_settings[ 'booking_date_lockout' ]                    = $lockout_date;
               $booking_settings[ 'booking_product_holiday' ]                 = $product_holiday;
               $booking_settings[ 'booking_enable_time' ]                     = $enable_time;
               $booking_settings[ 'booking_date_range_type' ]                 = $booking_date_range_type;
               $booking_settings[ 'booking_start_date_range' ]                = $booking_start_date_range;
               $booking_settings[ 'booking_end_date_range' ]                  = $booking_end_date_range;
               $booking_settings[ 'recurring_booking_range' ]                 = $booking_range_recurring;
               $booking_settings[ 'booking_range_recurring_years' ]         = $booking_range_recurring_years;
               
               if ( isset( $new_time_settings['booking_time_settings'] ) ) {
                   $booking_settings['booking_time_settings'] = $new_time_settings['booking_time_settings'];
               }else{ 
                   $booking_settings['booking_time_settings'] = '';
               }
               
               $booking_settings = (array) apply_filters( 'bkap_save_product_settings', $booking_settings, $duplicate_of );
              
               update_post_meta( $duplicate_of, 'woocommerce_booking_settings', $booking_settings );
       }
    
       /*******************************************
        *This function adds a meta box for booking settings on product page.
        ******************************************/
       public static function bkap_booking_box() {
	       add_meta_box( 'woocommerce-booking', __( 'Booking', 'woocommerce-booking' ), array( 'bkap_booking_box_class', 'bkap_meta_box' ), 'product', 'normal', 'core' );
       }
       
       public static function bkap_print_js() {
       		if ( get_post_type() == 'product' ) {
       	?>
       			<script type="text/javascript">
       				
       	        	jQuery(document).ready(function () {

       	            	jQuery("#tabbed-nav").zozoTabs({
       	                	theme: "silver",
       	                	orientation: "vertical",
       	                	position: "top-left",
       	                	size: "medium",
       	                	animation: {
       	                    	easing: "easeInOutExpo",
       	                    	duration: 400,
       	                    	effects: "slideV"
       	                	},
       	            });
       	        });
          	   </script>
           		<?php
       		}
       	}
                   

       /**********************************************
        * This function displays the settings for the product in the Booking meta box on the admin product page.
        ********************************************/
       public static function bkap_meta_box() {

               ?>
               <script type="text/javascript">

               // On Radio Button Selection
               jQuery(document).ready(function(){
            	               jQuery("table#list_bookings_specific a.remove_time_data, table#list_bookings_recurring a.remove_time_data").click(function() {
                                              
                                        var y=confirm('Are you sure you want to delete this time slot?');
                                        if(y==true) {
                                                var passed_id = this.id;
                                                var exploded_id = passed_id.split('&');
                                                var data = {
                                                                details: passed_id,
                                                                action: 'bkap_remove_time_slot'
                                                };

                                                jQuery.post('<?php echo get_admin_url();?>admin-ajax.php', data, function(response)
                                                {
                                                        jQuery("#row_" + exploded_id[0] + "_" + exploded_id[2] ).hide();
                                                });
                                        }

                       });

                       jQuery("table#list_bookings_specific a.remove_day_data, table#list_bookings_recurring a.remove_day_data").click(function() {
         
                                               var y=confirm('Are you sure you want to delete this day?');
                                               if(y==true) {
                                                       var passed_id = this.id;
                                                       var exploded_id = passed_id.split('&');
                                                       var data = {
                                                                       details: passed_id,
                                                                       action: 'bkap_remove_day'
                                                       };
                                                   
                                                       jQuery.post('<?php echo get_admin_url();?>admin-ajax.php', data, function(response) {
                                                               jQuery("#row_" + exploded_id[0]).hide();
                                                       });

                                               }
                                       });

                       jQuery("table#list_bookings_specific a.remove_specific_data").click(function() {
                                     
                                               var y=confirm('Are you sure you want to delete all the specific date records?');
                                               if(y==true) {
                                                       var passed_id = this.id;
                                                       var data = {
                                                                       details: passed_id,
                                                                       action: 'bkap_remove_specific'
                                                       };
                                     
                                                       jQuery.post('<?php echo get_admin_url();?>admin-ajax.php', data, function(response) {
                                     
                                                                               jQuery("table#list_bookings_specific").hide();
                                                                       });
                                               }
                                       });

                       jQuery("table#list_bookings_recurring a.remove_recurring_data").click(function() {
                                  
                                               var y=confirm('Are you sure you want to delete all the recurring weekday records?');
                                               if(y==true) {
                                                       var passed_id = this.id;
                                  
                                                       var data = {
                                                                       details: passed_id,
                                                                       action: 'bkap_remove_recurring'
                                                       };
                                  
                                                       jQuery.post('<?php echo get_admin_url();?>admin-ajax.php', data, function(response) {
                                  
                                                                               jQuery("table#list_bookings_recurring").hide();
                                                                       }); 
                                               }
                                       });

                       jQuery("#booking_enable_multiple_day").change(function() {
                               if(jQuery('#booking_enable_multiple_day').attr('checked')) {
                                       jQuery('#booking_method').hide();
                                       jQuery('#booking_time').hide();
                                       jQuery('#add_button').hide();
                                       jQuery('#booking_enable_weekday').hide();
                                       jQuery('#selective_booking').hide();
                                       jQuery('#purchase_without_date').hide();
                                       jQuery('#multiple_day_minimum').show();
              							jQuery('#multiple_day_minimum_days').show();
                               } else {
                                       jQuery('#booking_method').show();
                                       jQuery('#inline_calender').show();
                                       jQuery('#booking_time').show();
                                       jQuery('#add_button').show();
                                       jQuery('#booking_enable_weekday').show();
                                       jQuery('#selective_booking').show();
                                       jQuery('#purchase_without_date').show();
                                       jQuery('#multiple_day_minimum').hide();
              							jQuery('#multiple_day_minimum_days').hide();
                               }
                       });
               });
               /******************************************
               * This function displays a new div to add timeslots on the admin product page when Add timeslot button is clicked.
                *******************************************/
               function bkap_add_new_div( id ){

                       var exploded_id      =   id.split('[');
                       var new_var          =   parseInt( exploded_id[1] ) + parseInt(1);
                       var new_html_var     =   jQuery('#time_slot_empty').html();
                       var re               =   new RegExp( '\\[0\\]',"g" );
                       new_html_var         =   new_html_var.replace( re, "["+new_var+"]" );

                       jQuery("#time_slot").append(new_html_var);
                       jQuery('#add_another').attr("onclick","bkap_add_new_div('["+new_var+"]')");
               }
               </script>
               <div id='tabbed-nav'>
               		<ul>
			              <li><a id="addnew"> <?php _e( 'Booking Options', 'woocommerce-booking' );?> </a></li>
			              <li><a id="settings"><?php _e( 'Settings', 'woocommerce-booking' ); ?></a></li>
			              <li><a id="date_range"> <?php _e( 'Bookable Time Period', 'woocommerce-booking' );?> </a></li>
			              <li><a id="list"> <?php _e( 'Manage Dates, Time Slots', 'woocommerce-booking' );?> </a></li>
			              <?php
					           	global $post, $wpdb;
					            $duplicate_of = bkap_common::bkap_get_product_id($post->ID);
			              		do_action('bkap_add_tabs',$duplicate_of); 
			              ?>
			         </ul>
			        <div>
               <div id="date_time">
               <table class="form-table">
               
               <?php 
               do_action( 'bkap_before_enable_booking', $duplicate_of );
               $booking_settings    =   get_post_meta( $duplicate_of, 'woocommerce_booking_settings', true );
               
               $enable_time_checked =   '';
               
               if ( isset( $booking_settings['booking_enable_time'] ) && $booking_settings['booking_enable_time'] == 'on' ) {
                       $enable_time_checked = ' checked ';
               }
               ?>
                       <tr>
                       		<th>
                       			<label for="booking_enable_date"> <?php _e( 'Enable Booking Date:', 'woocommerce-booking' );?> </label>
                       		</th>
                       		<td>
                       <?php 
                       $enable_date =   '';
                       if ( isset( $booking_settings['booking_enable_date'] ) && $booking_settings['booking_enable_date'] == 'on' ) {
                               $enable_date = 'checked';
                       }
                       ?>
                       <input type="checkbox" id="booking_enable_date" name="booking_enable_date" <?php echo $enable_date;?> >
                       <img class="help_tip" width="16" height="16" style="margin-left:375px;" data-tip="<?php _e('Enable Booking Date on Products Page', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                       </td>
               </tr>
               <?php 
               do_action( 'bkap_before_enable_multiple_days', $duplicate_of );
               ?>
               <tr>
                       <th>
                       <label for="booking_enable_multiple_day"> <?php _e( 'Allow multiple day booking:', 'woocommerce-booking' );?> </label>
                       </th>
                       <td>
                       <?php 
                       $enable_multiple_day = '';
                       $booking_method_div = $booking_time_div = 'table-row';
                       $purchase_without_date = 'show';
                       $minimum_days_div_show= 'none';
                       $multiple_minimum_days_show = 'none';
                       if( isset($booking_settings['booking_enable_multiple_day']) && $booking_settings['booking_enable_multiple_day'] == 'on' ) {
                               $enable_multiple_day = 'checked';
                               $booking_method_div = 'none';
                               $booking_time_div = 'none';
                               $purchase_without_date = 'none';
                               $multiple_minimum_days_show = 'none';
                               $minimum_days_div_show = 'block';
                       }
                       ?>
                       <input type="checkbox" id="booking_enable_multiple_day" name="booking_enable_multiple_day" onClick="minimum_days_method(this)" <?php echo $enable_multiple_day;?> >
                       <img class="help_tip" width="16" height="16"  style="margin-left:375px;" data-tip="<?php _e( 'Enable Multiple day Bookings on Products Page', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                       </td>
               </tr>

               </table>
               <script type="text/javascript">
		           function minimum_days_method(chk) {
                   		if ( jQuery( "input[name='booking_enable_multiple_day']").attr("checked")) {
                        	document.getElementById("minimum_booking_days").style.display = "block";
                        }
                        if ( !jQuery( "input[name='booking_enable_multiple_day']").attr("checked") ) {
                        	document.getElementById("minimum_booking_days").style.display = "none";
                        }
                   }
				</script>
                
                <div id="minimum_booking_days" name="minimum_booking_days" style="display:<?php echo $minimum_days_div_show; ?>;">
				<table class="form-table">
                                <tr id="multiple_day_minimum" style="display:<?php if(isset($multiple_day_minimum)){ echo $multiple_day_minimum; }?>;">
                                    <th>
                                            <label for="minimum_day_booking_multiple"><?php _e( 'Minimum Day Booking For Multiple Days:', 'woocommerce-booking' ); ?></label>
                                    </th>
                                    <td>
                                            <?php
                                                        $minimum_booking_multiple_checked = "";
                                                        $multiple_minimum_days_show = 'none';
                                                        if (isset($booking_settings['enable_minimum_day_booking_multiple']) && $booking_settings['enable_minimum_day_booking_multiple'] == 'on'){
                                                                $minimum_booking_multiple_checked = 'checked';
                                                                $multiple_minimum_days_show = 'block';
                                                        }
                                                ?>
                                            <input type="checkbox" id="minimum_day_booking_multiple" name="minimum_day_booking_multiple" <?php echo $minimum_booking_multiple_checked; ?> onClick="multiple_minimum_days(this)" <?php echo $minimum_booking_multiple_checked; ?>/>
                                            <img class="help_tip" width="16" height="16" style="margin-left:376px;" data-tip="<?php _e( 'Select the checkbox if you want multiple day bookings to span for a minimum of more than 1 night always.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                                    </td>
                                </tr>
                                </table>    
                                 </div>
                                                                
                               <script type="text/javascript">
                                        function multiple_minimum_days(chk) {
                                                if ( jQuery( "input[name='minimum_day_booking_multiple']").attr("checked")) {
                                                        document.getElementById("multiple_minimum_days").style.display = "block";

                                                }
                                                if ( !jQuery( "input[name='booking_enable_multiple_day']").attr("checked") ) {
                                                        document.getElementById("multiple_minimum_days").style.display = "none";
                                                    }
                                                if ( !jQuery( "input[name='minimum_day_booking_multiple']").attr("checked") ) {
                                                        document.getElementById("multiple_minimum_days").style.display = "none";

                                                }
                                        }
				</script>
                                         
                                 <div id="multiple_minimum_days" name="multiple_minimum_days" style="display:<?php echo $multiple_minimum_days_show; ?>;">
				<table class="form-table">
                                    <tr id="multiple_day_minimum_days" style="display:<?php  if(isset($multiple_day_minimum)){ echo $multiple_day_minimum; }  ;?>;">
                                    <th>
                                         <label for="booking_minimum_number_days_multiple"><?php _e( 'Minimum number of days to book for multiple day bookings:', 'woocommerce-booking' ); ?></label>
                                    </th>
                                    <td>
                                        <?php 
                                            $minimum_day_multiple = "";
                                            if ( isset($booking_settings['booking_minimum_number_days_multiple']) && $booking_settings['booking_minimum_number_days_multiple'] != "" )
                                                $minimum_day_multiple = $booking_settings['booking_minimum_number_days_multiple'];
                                            else
                                                $minimum_day_multiple = "0";	
                	                       ?>
                                         <input type="text" name="booking_minimum_number_days_multiple" id="booking_minimum_number_days_multiple" value="<?php echo $minimum_day_multiple;?>" >
                                         <img class="help_tip" width="16" height="16" style="margin-left:202px;" data-tip="<?php _e( 'The minimum number of booking days you want to book for multiple days booking. For example, if you take minimum 2 days of booking, add 2 in the field here.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                                    </td>
                                </tr>
                                    </table>
                                </div>
                                <table class="form-table">
               <?php do_action( 'bkap_before_booking_method_select', $duplicate_of );
               ?>
               <tr id="booking_method" style="display:<?php echo $booking_method_div;?>;">
                       <th>
                       <label for="booking_method_select"> <?php _e( 'Select Booking Method(s):', 'woocommerce-booking' ); ?></label>
                       </th>
                       <td>
                       
                       <?php 
                       $specific_booking_chk    =   '';
                       $recurring_div_show      =   $specific_dates_div_show = 'none';
                       
                       if( ( isset( $booking_settings['booking_specific_booking'] ) && $booking_settings['booking_specific_booking'] == 'on' ) && $booking_settings['booking_enable_multiple_day'] != 'on' ) {
                               $specific_booking_chk    =   'checked';
                               $specific_dates_div_show =   'block';
                       }
                       
                       $recurring_booking = '';
                       
                       if( ( isset( $booking_settings['booking_recurring_booking'] ) && $booking_settings['booking_recurring_booking'] == 'on' ) && $booking_settings['booking_enable_multiple_day'] != 'on' ) {
                               $recurring_booking   =   'checked';
                               $recurring_div_show  =   'block';
                       }
                       ?>
                       <?php _e( 'Current Booking Method:', 'woocommerce-booking' ); ?>
                       <?php 
                       if ( $specific_booking_chk != 'checked' && $recurring_booking != 'checked' ) _e( "None", "woocommerce-booking" );
                       if ( $specific_booking_chk == 'checked' && $recurring_booking == 'checked' ) _e( "Specific Dates, Recurring Weekdays", "woocommerce-booking" );
                       if ( $specific_booking_chk == 'checked' && $recurring_booking != 'checked')  _e( "Specific Dates", "woocommerce-booking" );
                       if ( $specific_booking_chk != 'checked' && $recurring_booking == 'checked')  _e( "Recurring Weekdays", "woocommerce-booking" );
                       ?> 
                       <br><br>
                       <input type="checkbox" name="booking_specific_booking" id="booking_specific_booking" onClick="bkap_book_method(this)" <?php echo $specific_booking_chk; ?>> <?php _e( 'Specific Dates', 'woocommerce-booking' ); ?> </input>
                       <img style="margin-left:290px;"  class="help_tip" width="16" height="16" data-tip="<?php _e( 'Please enable/disable the specific booking dates and recurring weekdays using these checkboxes. Upon checking them, you shall be able to further select dates or weekdays.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" /><br>
                       <input type="checkbox" name="booking_recurring_booking" id="booking_recurring_booking" onClick="bkap_book_method(this)" <?php echo $recurring_booking; ?> > <?php _e( 'Recurring Weekdays', 'woocommerce-booking' ); ?> </input>
                       </td>
               </tr>
               </table>

            <script type="text/javascript">
                /*************************************
                 * this function checks which booking method is selected on the admin product page
                 ***************************************/
                    function bkap_book_method(chk) {
                            if ( jQuery( "input[name='booking_specific_booking']").attr("checked")) {
                                    document.getElementById("selective_booking").style.display = "block";
                                    document.getElementById("booking_enable_weekday").style.display = "none";
                            }
                            if (jQuery( "input[name='booking_recurring_booking']").attr("checked")) {
                                    document.getElementById("booking_enable_weekday").style.display = "block";
                                    document.getElementById("selective_booking").style.display = "none";
                            }
                            if ( jQuery( "input[name='booking_specific_booking']").attr("checked") && jQuery( "input[name='booking_recurring_booking']").attr("checked")) {
                                    document.getElementById("booking_enable_weekday").style.display = "block";
                                    document.getElementById("selective_booking").style.display = "block";
                            }
                            if ( !jQuery( "input[name='booking_specific_booking']").attr("checked") && !jQuery( "input[name='booking_recurring_booking']").attr("checked")) {
                                    document.getElementById("booking_enable_weekday").style.display = "none";
                                    document.getElementById("selective_booking").style.display = "none";
                            }
                    }
               </script>

               <div id="booking_enable_weekday" name="booking_enable_weekday" style="display:<?php echo $recurring_div_show; ?>;">
               <table class="form-table">
               <tr>
                       <th>
                       <label for="booking_enable_weekday_dates"> <?php _e( 'Booking Days:', 'woocommerce-booking' );?> </label>
                       </th>
                       <td>
                       <fieldset class="days-fieldset">
                                       <legend><?php _e( 'Days:', 'woocommerce-booking' ); ?></legend>
                                       <?php 
                                       $weekdays = bkap_get_book_arrays('weekdays');
                                       foreach ( $weekdays as $n => $day_name) {
                                               print('<input type="checkbox" name="'.$n.'" id="'.$n.'" />
                                               <label for="'.$day_name.'">'.__( $day_name, 'woocommerce-booking' ).'</label>
                                               <br>');
                                       }?>
                                       </fieldset>
                       <img class="help_tip" width="16" height="16" style="margin-left:225px;" data-tip="<?php _e( 'Select Weekdays', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                       </td>
               </tr>
               </table>
               </div>

               <div id="selective_booking" name="selective_booking" style="display:<?php echo $specific_dates_div_show; ?>;">
               <table class="form-table">
               <script type="text/javascript">
                                       jQuery(document).ready(function() {
                                       var formats = ["d.m.y", "d-m-yyyy","MM d, yy"];
                                       jQuery("#booking_specific_date_booking").datepick({dateFormat: formats[1], multiSelect: 999, monthsToShow: 1, showTrigger: '#calImg'});
                                       });
               </script>
               <tr>
                       <th>
                       <label for="booking_specific_date_booking"><?php _e( 'Specific Date Booking:', 'woocommerce-booking' ); ?></label>
                       </th>
                       <td>
                       <textarea rows="4" cols="40" name="booking_specific_date_booking" id="booking_specific_date_booking"></textarea>
                       <img class="help_tip" width="16" height="16" style="margin-left:25px;vertical-align:top;" data-tip="<?php _e( 'Select the specific dates that you want to enable for booking', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" style="vertical-align:top;"/>
                       </td>
               </tr>
               </table>
               </div>

               <table class="form-table">
               <tr>
                       <th>
                       <label for="booking_lockout_date"><?php _e( 'Lockout Date after X orders:', 'woocommerce-booking' ); ?></label>
                       </th>
                       <td>
                       
                       <?php 
                       $lockout_date = "";
                       if ( isset( $booking_settings['booking_date_lockout'] ) && $booking_settings['booking_date_lockout'] != "" ) {
                               $lockout_date = $booking_settings['booking_date_lockout'];
                               //sanitize_text_field( $lockout_date, true )
                       } else {
                               $lockout_date = "60";
                       }
                       ?>
                       
                       <input type="text" name="booking_lockout_date" id="booking_lockout_date" value="<?php echo sanitize_text_field( $lockout_date, true );?>" >
                       <img class="help_tip" width="16" height="16" style="margin-left:203px;" data-tip="<?php _e('Set this field if you want to place a limit on maximum bookings on any given date. If you can manage up to 15 bookings in a day, set this value to 15. Once 15 orders have been booked, then that date will not be available for further bookings.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" /></td>
               </tr>
               <?php 
               do_action( 'bkap_before_enable_time', $duplicate_of );
               ?>
               <tr>
               <th><hr></th>
               <td><hr></td>
               </tr>
               <tr id="booking_time" style="display:<?php echo $booking_time_div; ?>;">
                       <th>
                       <label for="booking_enable_time"><?php _e( 'Enable Booking Time:', 'woocommerce-booking' ); ?></label>
                       </th>
                       <td>
                       <?php 
                       $enable_time         =   "";
                       $time_button_status  =   'disabled';
                       if( isset( $booking_settings['booking_enable_time'] ) && $booking_settings['booking_enable_time'] == "on" ) {
                               $enable_time         =   "checked";
                               $time_button_status  =   '';
                       }
                       ?>
                       <input type="checkbox" name="booking_enable_time" id="booking_enable_time" <?php echo $enable_time;?> onClick="bkap_timeslot(this)">
                       <img class="help_tip" width="16" height="16" style="margin-left:379px;" data-tip="<?php _e( 'Enable time (or time slots) on the product. Add any number of booking time slots once you have checked this. You can manage the Time Slots using the Manage Dates, Time Slots tab', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                       </td>
               </tr>
               <?php
                       do_action( 'bkap_after_time_enabled', $duplicate_of );
               ?>
               </table>
                <div id="booking_warning" style="display:none;">
            		<b>The bookings would be available on all weekdays as none are selected. You can change that by selecting a booking method above.</b>
             	</div>
               <script type="text/javascript">
                   /**************************************************
                    * This function displays the Add Time slot button when Enable time slot setting is checked.
                    *****************************************************/
                       function bkap_timeslot(chk) {
                               if ( !jQuery( "input[name='booking_enable_time']" ).attr( "checked" ) ) {
                               		document.getElementById( "time_slot" ).style.display = "none";
                                    jQuery( "#add_another" ).attr( "disabled", "disabled" );
                               }
                               if( jQuery( "input[name='booking_enable_time']" ).attr( "checked" ) ) {
                               	// if no booking method is selected, select the recurring method and enable all weekdays
                            	   if ( !jQuery( "input[name='booking_specific_booking']" ).attr( "checked" ) && !jQuery( "input[name='booking_recurring_booking']" ).attr( "checked" ) ) {
                           		   		jQuery( '#booking_warning' ).show( function() {
                      		          		jQuery(this).fadeOut( 5000 );
                      		   			});
                            	   } 
                                   document.getElementById( "time_slot" ).style.display = "block";
                                   jQuery( "#add_another" ).removeAttr( "disabled" );
                               }
                       }
               </script>

               <div id="time_slot_empty" name="time_slot_empty" style="display:none;">
               <table class="form-table">
               <tr>
                       <th>
                       <label for="time_slot_label"><?php _e( 'Enter a Time Slot:', 'woocommerce-booking' ); ?></label>
                       </th>
                       <td>
                       <?php _e( 'From: ', 'woocommerce-booking' ); ?>
                       <select name="booking_from_slot_hrs[0]" id="booking_from_slot_hrs[0]">
               <?php
               			printf("<option value='0'>".__( 'Hours', 'woocommerce-booking' )."</option>\n");
                       for ( $i=0; $i<24; $i++ ) {
                               printf( "<option %s value='%s'>%s</option>\n",
                               selected( $i, '', false ),
                               esc_attr( $i ),
                               $i
                               );
                       }
               ?>
               </select>

               <select name="booking_from_slot_min[0]" id="booking_from_slot_min[0]">
               <?php
               printf("<option value='00'>".__( 'Minutes', 'woocommerce-booking' )."</option>\n");
                       for ( $i=0; $i<60; $i++ ) {
                           if ( $i < 10 ) {
                               $i = '0'.$i;
                           }
                               printf( "<option %s value='%s'>%s</option>\n",
                               selected( $i, '', false ),
                               esc_attr( $i ),
                               $i
                               );
                       }
               ?>
               </select>
               <?php _e( 'To: ', 'woocommerce-booking'); ?>
                       <select name="booking_to_slot_hrs[0]" id="booking_to_slot_hrs[0]">
               <?php
               printf("<option value='0'>".__( 'Hours', 'woocommerce-booking' )."</option>\n");
                       for ( $i=0; $i<24; $i++ ) {
                               printf( "<option %s value='%s'>%s</option>\n",
                               selected( $i, '', false ),
                               esc_attr( $i ),
                               $i
                               );
                       }
               ?>
               </select>

               <select name="booking_to_slot_min[0]" id="booking_to_slot_min[0]">
               <?php
               printf("<option value='00'>".__( 'Minutes', 'woocommerce-booking' )."</option>\n");
                       
                       for ( $i=0; $i<60; $i++ ) {
                           
                           if ( $i < 10 ) {
                               $i = '0'.$i;
                           }
                           
                               printf( "<option %s value='%s'>%s</option>\n",
                               selected( $i, '', false ),
                               esc_attr( $i ),
                               $i
                               );
                       }
               ?>
               </select>
               <img class="help_tip" width="16" height="16" data-tip="<?php _e( 'If do not want a time range, please leave the To Hours and To Minutes set to 0.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
				<br><br>
               <label for="booking_lockout_time"><?php _e( 'Lockout time slot after X orders:', 'woocommerce-booking' ); ?></label><br>
                       <input type="text" style="width:50px;" name="booking_lockout_time[0]" id="booking_lockout_time[0]" value="30" />
                       <input type="hidden" id="wapbk_slot_count" name="wapbk_slot_count" value="[0]" />
                       <img class="help_tip" width="16" height="16" style="margin-left:349px;" data-tip="<?php _e( 'Please enter a number to limit the number of bookings for this time slot. Setting it to blanks or 0 will ensure unlimited bookings.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                       <br/>
				<?php do_action( 'bkap_after_lockout_time', $duplicate_of );?>
                       <label for="booking_global_check_lockout"><?php _e( 'Make Unavailable for other products once lockout is reached')?></label><br/>
                       <input type="checkbox" name="booking_global_check_lockout[0]" id="booking_global_check_lockout[0]">
                       <img class="help_tip" width="16" height="16" style="margin-left:383px;" data-tip="<?php _e( 'Please select this checkbox if you want this time slot to be unavailable for all products once the lockout is reached.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                      
               <br/><br/>
               <label for="booking_time_note"><?php _e( 'Note (optional)', 'woocommerce-booking'); ?></label><br>
               <textarea class="short" name="booking_time_note[0]" id="booking_time_note[0]" rows="2" cols="40"></textarea>

               </td>

               </tr>
               </table>
               </div>

               <div id="time_slot" name="time_slot">
               </div>

               <p>
               <div id="add_button" name="add_button" style="display:<?php echo $booking_time_div; ?>;">
               <input type="button" class="button-primary" value="<?php _e( 'Add Time Slot', 'woocommerce-booking' ); ?>" id="add_another" onclick="bkap_add_new_div('[0')" <?php echo $time_button_status;?>>
               </div>
               </p>

               </div>
               <div id="booking_settings" style="display:none;">
               <table class="form-table">
               <tr id="inline_calender" style="display:show">
                       <th>
                       <label for="enable_inline_calendar"> <?php _e( 'Enable Inline Calendar:', 'woocommerce-booking' );?> </label>
                       </th>
                       <td>
                       <?php 
                       $enable_inline_calendar = '';
                       
                       if( isset( $booking_settings['enable_inline_calendar'] ) && $booking_settings['enable_inline_calendar'] == 'on' ) {
                               $enable_inline_calendar= 'checked';
                       }
                       
                       ?>
                       <input type="checkbox" id="enable_inline_calendar" name="enable_inline_calendar" <?php echo $enable_inline_calendar;?> >
                       <img class="help_tip" width="16" height="16" style="margin-left:361px;"data-tip="<?php _e( 'Enable Inline Calendar on Products Page', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                       </td>
               </tr>
               <?php 
               do_action( 'bkap_before_purchase_without_date', $duplicate_of );
               ?>
               <tr id="purchase_without_date" style="display:<?php echo $purchase_without_date?>;">
                       <th>
                       <label for="booking_purchase_without_date"><?php _e( 'Purchase without choosing a date:', 'woocommerce-booking' ); ?></label>
                       </th>
                       <td>
                       <?php 
                       $date_show = '';
                       if( isset( $booking_settings['booking_purchase_without_date'] ) && $booking_settings['booking_purchase_without_date'] == 'on' ) {
                               $without_date = 'checked';
                       } else {
                               $without_date = '';
                       }
                       ?>
                       <input type="checkbox" name="booking_purchase_without_date" id="booking_purchase_without_date" <?php echo $without_date; ?>>
                       <img style="margin-left:361px;"  class="help_tip" width="16" height="16" data-tip="<?php _e( 'Enables your customers to purchase without choosing a date. Select this option if you want the ADD TO CART button always visible on the product page.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                       </td>
               </tr>
               <?php 
               do_action( 'bkap_before_product_holidays', $duplicate_of );
               ?>				
               <script type="text/javascript">
                                       jQuery(document).ready(function() {
                                       var formats = ["d.m.y", "d-m-yyyy","MM d, yy"];
                                       jQuery("#booking_product_holiday").datepick({dateFormat: formats[1], multiSelect: 999, monthsToShow: 1, showTrigger: '#calImg'});
                                       });
               </script>
               <tr>
                       <th>
                       <label for="booking_product_holiday"><?php _e( 'Select Holidays / Exclude Days / Black-out days:', 'woocommerce-booking' ); ?></label>
                       </th>
                       <td>
                       <?php 
                       $product_holiday = "";
                       if ( isset( $booking_settings['booking_product_holiday'] ) && $booking_settings['booking_product_holiday'] != "" ) {
                               $product_holiday = $booking_settings['booking_product_holiday'];
                       }
                       ?>
                       <textarea rows="4" cols="40" name="booking_product_holiday" id="booking_product_holiday"><?php echo $product_holiday; ?></textarea>
                       <img class="help_tip" width="16" height="16" style="margin-left:10px;vertical-align:top;" data-tip="<?php _e( 'Select dates for which the booking will be completely disabled only for this product. Please click on the date in calendar to add or delete the date from the holiday list.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" style="vertical-align:top;"/>
                       </td>
               </tr>
                <?php 
               do_action( 'bkap_after_product_holidays', $duplicate_of );
               ?>               
               </table>
               </div>
				<div id="date_range_tab" style="display:none;">
               		<table class="form-table">
               			<?php 
               				do_action( 'bkap_before_range_selection_radio', $duplicate_of );
               			?>
               			<tr class="bkap_booking_range">
						<th><?php _e( 'Booking Type:', 'woocommerce-booking' ); ?></th>
						<td>
							<?php 
							$fixed_range = $all_year = '';
							if ( isset( $booking_settings['booking_date_range_type'] ) && $booking_settings['booking_date_range_type'] == "fixed_range" ) {
								$fixed_range = 'checked';
							}
							if ( isset( $booking_settings['booking_date_range_type'] ) && $booking_settings['booking_date_range_type'] == "all_year" ) {
								$all_year = 'checked';
							}
							if ( $fixed_range == "" && $all_year == '' ) {
								$all_year = 'checked';
							}
							?>
							<input type="radio" name="booking_date_range_type" id="booking_date_range_type" value="fixed_range" <?php echo $fixed_range;?>><?php ('&nbsp'. _e( 'Fixed booking period by dates ( March 1 to October 31)', 'woocommerce-booking' ) ); ?> </input><br>
							<input type="radio" name="booking_date_range_type" id="booking_date_range_type" value="all_year" <?php echo $all_year;?>><?php ('&nbsp'. _e( 'Book all year round', 'woocommerce-booking' ) );?> </input>
							<img class="help_tip" style="margin-left:220px;" width="16" height="16" data-tip="<?php _e( 'Please choose a booking type. It could be a specific date range or an all the year round booking.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png"/>
						</td>
						</tr>
               			 <?php 
              			 do_action( 'bkap_before_minimum_days', $duplicate_of );?>
               			<tr>
                       		<th>
                       			<label for="booking_minimum_number_days"><?php _e( 'Advance Booking Period (in hours):', 'woocommerce-booking' ); ?></label>
                       		</th>
                       		<td>
                       			<?php 
		                       	$min_days = 0;
		                       	if ( isset( $booking_settings['booking_minimum_number_days'] ) && $booking_settings['booking_minimum_number_days'] != "" ) {
		                        	$min_days = $booking_settings['booking_minimum_number_days'];
		                       	}
		                       	?>
		                       	<input type="text" style="width:90px;" name="booking_minimum_number_days" id="booking_minimum_number_days" value="<?php echo sanitize_text_field( $min_days, true );?>" >
		                       	<img class="help_tip" style="margin-left:268px;" width="16" height="16" data-tip="<?php _e( 'Enable Booking after X number of days from current date. The customer can select a booking date that is available only after the minimum days that are entered here. For example, if you need 3 days advance notice for a booking, enter 3 here.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
		                    </td>
		               </tr>
		               <?php 
		               do_action( 'bkap_before_number_of_dates', $duplicate_of );
		               ?>
		               <tr>
		               		<th>
		                       <label for="booking_maximum_number_days"><?php _e( 'Number of Dates to choose:', 'woocommerce-booking' ); ?></label>
	                       	</th>
	                       	<td>
		                       <?php 
		                       $max_date = "";
		                       
		                       if ( isset( $booking_settings['booking_maximum_number_days'] ) && $booking_settings['booking_maximum_number_days'] != "" ) {
		                               $max_date = $booking_settings['booking_maximum_number_days'];
		                       } else {
		                               $max_date = "30";
		                       }
		                       		
		                       ?>
		                       <input type="text" style="width:90px;" name="booking_maximum_number_days" id="booking_maximum_number_days" value="<?php echo sanitize_text_field( $max_date, true );?>" >
		                       <img class="help_tip" style="margin-left:268px;" width="16" height="16" data-tip="<?php _e( 'The maximum number of booking dates you want to be available for your customers to choose from. For example, if you take only 2 months booking in advance, enter 60 here.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
		                    </td>
               			</tr>
               			<?php 
		               do_action( 'bkap_before_booking_start_date_range', $duplicate_of );
		               ?>
		               <tr><th></th><td>
		               		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e( 'OR', 'woocommerce-booking' ); ?>
		               		</td>
		         		</tr>
               			<tr>
		               		<th>
		                       <label for="booking_start_date_range"><?php _e( 'Bookings Start on:', 'woocommerce-booking');?></label>
	                       	</th>
	                       	<td>
		                       <?php 
		                       $booking_start_date = "";
		                       
		                       if ( isset( $booking_settings['booking_start_date_range']) && $booking_settings['booking_start_date_range'] != "" ) {
		                               $booking_start_date = $booking_settings['booking_start_date_range'];
		                       } else {
		                               $booking_start_date = "";
		                       }
		                       		
		                       ?>
		                       <input type="text" style="width:150px;" name="booking_start_date_range" id="booking_start_date_range" value="<?php echo sanitize_text_field( $booking_start_date, true );?>" >
		                       <img class="help_tip" style="margin-left:210px;" width="16" height="16" data-tip="<?php _e( 'The start date of the bookable block. For e.g. if you want to take bookings for the period of March to october, then the date here should be March 1', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
		                    </td>
               			</tr>
               			<?php 
		              	 do_action( 'bkap_before_booking_end_date_range', $duplicate_of );
		               ?>
               			<tr>
		               		<th>
		                       <label for="booking_end_date_range"><?php _e( 'Bookings End on:', 'woocommerce-booking' ); ?></label>
	                       	</th>
	                       	<td>
		                       <?php 
		                       $booking_end_date = "";
		                       
		                       if ( isset( $booking_settings['booking_end_date_range']) && $booking_settings['booking_end_date_range'] != "" ) {
		                               $booking_end_date = $booking_settings['booking_end_date_range'];
		                       } else {
		                               $booking_end_date = "";
		                       }		
		                       
		                       ?>
		                       <input type="text" style="width:150px;" name="booking_end_date_range" id="booking_end_date_range" value="<?php echo sanitize_text_field( $booking_end_date, true );?>" >
		                       <img class="help_tip" style="margin-left:210px;" width="16" height="16" data-tip="<?php _e( 'The end date of the bookable block. For e.g. if you want to take bookings for the period of March to october, then the date here should be October 31.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
		                    </td>
               			</tr>
               			<?php 
               			do_action( 'bkap_before_recurring_date_range', $duplicate_of );
               			?>
               		 	<tr>
		               		<th>
		                       <label for="booking_range_recurring"><?php _e( 'Yearly Recurring Date Range:', 'woocommerce-booking' ); ?></label>
	                       	</th>
	                       	<td>
		                       <?php 
		                       $recurring_booking_range = "";
		                       if ( isset( $booking_settings[ 'recurring_booking_range' ] ) && $booking_settings[ 'recurring_booking_range' ] == "on" ) {
		                               $recurring_booking_range = 'checked';
		                       } else {
		                               $recurring_booking_range = "";
		                       }		
		                       ?>
		                       <input type="checkbox" id="booking_range_recurring" name="booking_range_recurring" <?php echo $recurring_booking_range; ?> >
		                       <img class="help_tip" style="margin-left:342px;" width="16" height="16" data-tip="<?php _e( 'Please check the checkbox if you want the bookable date range to be recurring every year.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
		                    </td>
               			</tr>
               			<?php
		              	 do_action( 'bkap_after_recurring_date_range', $duplicate_of );
		               ?>
                        <tr>
		               		<th>
		                       <label for="booking_range_recurring_years"><?php _e( 'Number of Years to recur:', 'woocommerce-booking' ); ?></label>
	                       	</th>
	                       	<td>
		                       <?php 
                               $recurring_years = "";
		                       if ( isset( $booking_settings[ 'booking_range_recurring_years' ] ) && $booking_settings[ 'booking_range_recurring_years' ] != "" ) {
		                               $recurring_years = $booking_settings[ 'booking_range_recurring_years' ];
		                       }
		                       ?>
		                       <input type="text" style="width:90px;" id="booking_range_recurring_years" name="booking_range_recurring_years" value="<?php echo sanitize_text_field( $recurring_years, true );?>" >
		                       <img class="help_tip" style="margin-left:271px;" width="16" height="16" data-tip="<?php _e( 'If you wish the fixed bookable range to be used for the next 3 years, then please set the field to 3.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
		                    </td>
               			</tr>
               			<?php
                            do_action( 'bkap_after_recurring_years_range', $duplicate_of );
		                ?>
               			<script type="text/javascript">
                        	jQuery(document).ready(function() {
                            	var formats = ["d.m.y", "d-m-yyyy","MM d, yy"];
                            	jQuery("#booking_end_date_range").datepick({dateFormat: formats[1], monthsToShow: 1, showTrigger: '#calImg'});
                            	jQuery("#booking_start_date_range").datepick({dateFormat: formats[1], monthsToShow: 1, showTrigger: '#calImg'});
                            });
              			</script>
               		</table>
               </div>
               
               <div id="listing_page" style="display:none;" >
               <table class='wp-list-table widefat fixed posts' cellspacing='0' id='list_bookings_specific'>
                       <tr>
                               <?php _e( 'Specific Date Time Slots', 'woocommerce-booking' ); ?>
                       </tr>
                       <tr>
                               <th> <?php _e('Day', 'woocommerce-booking');?> </th>
                               <th> <?php _e('Start Time', 'woocommerce-booking');?> </th>
                               <th> <?php _e('End Time', 'woocommerce-booking');?> </th>
                               <th> <?php _e('Note', 'woocommerce-booking');?> </th>
                               <th> <?php _e('Maximum Bookings', 'woocommerce-booking');?> </th>
                               <th> <?php _e('Global Check', 'woocommerce_booking');?> </th>
                               <?php do_action( 'bkap_add_column_names', $duplicate_of ); ?>
                               <?php print('<th> <a href="javascript:void(0);" id="'.$duplicate_of.'" class="remove_specific_data">'. __( "Delete All", "woocommerce-booking" ).'</a> </th>');?>
                       </tr>

                       <?php 	
                       $var             =   "";	
                       $currency_symbol =   get_woocommerce_currency_symbol();
                       
                       if ( isset( $booking_settings['booking_time_settings'] ) && $booking_settings['booking_time_settings'] != '' ) :
                       
                       		foreach( $booking_settings['booking_time_settings'] as $key => $value ) {
		                       	$slot_price   =   array();
		                       	$slot_price   =   (array) apply_filters( 'bkap_add_column_value', $slot_price,$value );
		                       	$i            =   0;
                       	        
                       	        if ( substr( $key, 0, 7 ) != "booking" ) {
                                       $date_disp = $key;
                                       
                                       foreach( $value as $date_key => $date_value ) {
                                               print('<tr id="row_'.$date_key.'_'.$date_disp.'" >');
                                               print('<td> '.$date_disp.' </td>');
                                               print('<td> '.$date_value['from_slot_hrs'].':'.$date_value['from_slot_min'].' </td>');
                                               print('<td> '.$date_value['to_slot_hrs'].':'.$date_value['to_slot_min'].' </td>');
                                               print('<td> '.$date_value['booking_notes'].' </td>');
                                               print('<td> '.$date_value['lockout_slot'].' </td>');
                                               print('<td> '.$date_value['global_time_check'].' </td>');
                                               if ( isset( $slot_price ) && count( $slot_price ) > 0 ) {
                                               		if ( $slot_price[ $i ] != '' ) {
                                               			print('<td> '.$currency_symbol . " " . $slot_price[ $i ].' </td>');
                                               		}
                                               		else {
                                               			print('<td></td>');
                                               		}
                                               }
                                               print('<td> <a href="javascript:void(0);" id="'.$date_key.'&'.$duplicate_of.'&'.$date_disp.'&'.$date_value['from_slot_hrs'].':'.$date_value['from_slot_min'].'&'.$date_value['to_slot_hrs'].':'.$date_value['to_slot_min'].'" class="remove_time_data"> <img src="'.plugins_url().'/woocommerce-booking/images/delete.png" alt="Remove Time Slot" title="Remove Time Slot"></a> </td>');
                                               print('</tr>');
                                               $i++;
                                       }

                               	} elseif ( substr( $key, 0, 7 ) == "booking" ) {
                                       $date_pass   =   $key;
                                       $weekdays    =   bkap_get_book_arrays( 'weekdays' );
                                       $date_disp   =   $weekdays[ $key ];
                                       //$price = $prices[$key."_price"];
                                       
                                       foreach( $value as $date_key => $date_value ) {
                                               $global_time_check = '';
                                               if( isset( $date_value['global_time_check'] ) ) {
                                                       $global_time_check = $date_value['global_time_check'];
                                               }
                                               $var .= '<tr id="row_'.$date_key.'_'.$date_pass.'" >
                                               <td> '.$date_disp.' </td>
                                               <td> '.$date_value['from_slot_hrs'].':'.$date_value['from_slot_min'].' </td>
                                               <td> '.$date_value['to_slot_hrs'].':'.$date_value['to_slot_min'].' </td>
                                               <td> '.$date_value['booking_notes'].' </td>
                                               <td> '.$date_value['lockout_slot'].' </td>
                                               <td> '.$global_time_check.' </td>';
                                               if ( isset( $slot_price ) && count( $slot_price ) > 0 ) {
                                               		$var .= '<td> '.$currency_symbol . " " . $slot_price[ $i ].' </td>';
                                               }
                                               $var .= '<td> <a href="javascript:void(0);" id="'.$date_key.'&'.$duplicate_of.'&'.$date_pass.'&'.$date_value['from_slot_hrs'].':'.$date_value['from_slot_min'].'&'.$date_value['to_slot_hrs'].':'.$date_value['to_slot_min'].'" class="remove_time_data"> <img src="'.plugins_url().'/woocommerce-booking/images/delete.png" alt="Remove Time Slot" title="Remove Time Slot"></a> </td>
                                               </tr>';
                                               $i++;
                                       }
                               }
                       }
                       endif;
                       if ( isset( $booking_settings['booking_enable_multiple_day'] ) && $booking_settings['booking_enable_multiple_day'] != 'on' ) :
                               $query   =   "SELECT * FROM `".$wpdb->prefix."booking_history`
                                            WHERE post_id = %d AND from_time='' AND to_time='' AND end_date='0000-00-00' AND status != 'inactive'";
                               $results =   $wpdb->get_results ( $wpdb->prepare( $query, $duplicate_of ) );

                               foreach ( $results as $key => $value ) {
                                       if ( substr( $value->weekday, 0, 7 ) != "booking" ) {
                                               $date_key = date( 'j-n-Y', strtotime( $value->start_date ) );
                                               print('<tr id="row_'.$date_key.'" >');
                                               print('<td> '.$date_key.' </td>');
                                               print('<td> &nbsp; </td>');
                                               print('<td> &nbsp; </td>');
                                               print('<td> &nbsp; </td>');
                                               print('<td> '.$value->total_booking.' </td>');
                                               print('<td> &nbsp; </td>');
                                               print('<td> <a href="javascript:void(0);" id="'.$date_key.'&'.$duplicate_of.'" class="remove_day_data"> <img src="'.plugins_url().'/woocommerce-booking/images/delete.png" alt="Remove Date" title="Remove Date"></a> </td>');
                                               print('</tr>');	
                                       } elseif ( substr( $value->weekday, 0, 7 ) == "booking" && $value->start_date == "0000-00-00" ) {
                                               $weekdays    = bkap_get_book_arrays( 'weekdays' );
                                               //$price = $prices[$value->weekday."_price"];
                                               $date_disp   = $weekdays[ $value->weekday ];
                                               $var        .= '<tr id="row_'.$value->weekday.'" >
                                                                   <td> '.$date_disp.' </td>
                                                                   <td>  &nbsp; </td>
                                                                   <td>  &nbsp; </td>
                                                                   <td>  &nbsp; </td>
                                                                   <td> '.$value->total_booking.' </td>
                                                                   <td>  &nbsp; </td>
                                                                   <td> <a href="javascript:void(0);" id="'.$value->weekday.'&'.$duplicate_of.'" class="remove_day_data"> <img src="'.plugins_url().'/woocommerce-booking/images/delete.png" alt="Remove Day" title="Remove Day"></a> </td>
                                                               </tr>';
                                       }
                               }
                       endif;
                       ?>

               </table>

               <p>
               <table class='wp-list-table widefat fixed posts' cellspacing='0' id='list_bookings_recurring'>
                       <tr>
                               <?php _e( 'Recurring Days Time Slots', 'woocommerce-ac' ); ?>
                       </tr>
                       <tr>
                               <th> <?php _e('Day',                 'woocommerce-booking');?> </th>
                               <th> <?php _e('Start Time',          'woocommerce-booking');?> </th>
                               <th> <?php _e('End Time',            'woocommerce-booking');?> </th>
                               <th> <?php _e('Note',                'woocommerce-booking');?> </th> 
                               <th> <?php _e('Maximum Bookings',    'woocommerce-booking');?> </th>
                               <th> <?php _e('Global Check',        'woocommerce-booking');?>
                               <?php do_action( 'bkap_add_column_names', $duplicate_of ); ?>
                               <?php print('<th> <a href="javascript:void(0);" id="'.$duplicate_of.'" class="remove_recurring_data">'.__( "Delete All", "woocommerce-booking" ).'</a> </th>'); ?>
                       </tr>
               <?php 
               if ( isset( $var ) ){
                       echo $var;
               }
               ?>
               </table>
               </p>
               </div>
                          <?php 
               		do_action( 'bkap_after_listing_enabled', $duplicate_of );
               ?>
               </div>
				</div>
				<?php 
       }     
    }// end of class
    
?>