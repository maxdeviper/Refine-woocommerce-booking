<?php

add_action( 'widgets_init', 'bkap_widgets_init' );

/****************************
 * This function initialize the wideget , and register the same.
 *****************************/
function bkap_widgets_init() {
	include_once( "widget-product-search.php" );
	register_widget( 'Custom_WooCommerce_Widget_Product_Search' );
}

function check_in_range( $start_date, $end_date, $date_from_user ) {
    // Convert to timestamp
    $start_ts   =   strtotime( $start_date );
    $end_ts     =   strtotime( $end_date );
    $user_ts    =   strtotime( $date_from_user );

    // Check that user date is between start & end
    return ( ( $user_ts >= $start_ts ) && ( $user_ts <= $end_ts ) );
}

function check_in_range_abp( $start_date, $end_date, $date_from_user ) {
    // Convert to timestamp
    $start_ts           =   strtotime( $start_date );
    $end_ts             =   strtotime( $end_date );
    $user_ts            =   strtotime($date_from_user);
    $return_value       =   array() ;
    $new_week_days_arr  =   array();
    
    while ( $start_ts <= $end_ts ) {
        $new_week_days_arr []   =   $start_date;
        $start_ts               =   strtotime( '+1 day', $start_ts );
        $start_date             =   date( "j-n-Y", $start_ts );
    }

    foreach ( $new_week_days_arr as $weekday_key => $weekday_value ){

        $week_day_value = strtotime( $weekday_value );

        if ( $week_day_value == $user_ts ){
            $return_value [ $weekday_value ] = true;
        }else if ( $week_day_value >= $user_ts ){
            $return_value [ $weekday_value ] = true;
        }else {
            $return_value [ $weekday_value ] = false;
        }
    }
    return $return_value;
}

function check_in_range_weekdays( $start_date, $end_date, $recurring_selected_weekdays ) {
    
    $start_ts           =   strtotime( $start_date );
    $end_ts             =   strtotime( $end_date );
    $return_value       =   array();
    $new_week_days_arr  =   array();
    
    while ( $start_ts <= $end_ts ) {

        if ( !in_array( date( 'w', $start_ts ), $new_week_days_arr ) ) {
            $new_week_days_arr [] = date( 'w', $start_ts );
        }else if (!in_array( date( 'w',$end_ts ), $new_week_days_arr ) ) {
            $new_week_days_arr [] =  date( 'w',$end_ts );
        }

        $start_ts = strtotime( '+1 day', $start_ts );
    }

    foreach ( $recurring_selected_weekdays as $weekday_key => $weekday_value ){

        $week_day_value = substr( $weekday_key, -1 );
        
        if ( $weekday_value == 'on' && in_array ( $week_day_value, $new_week_days_arr ) ){ 
            $return_value [] = true;
        }else{
            $return_value [] = false;
        }
    }
    return $return_value;
}

function check_in_range_holidays( $start_date, $end_date, $recurring_selected_weekdays ) {

    $start_ts           =   strtotime( $start_date );
    $end_ts             =   strtotime( $end_date );
    $return_value       =   array();
    $new_week_days_arr  =   array();

    while ($start_ts <= $end_ts) {

        $new_week_days_arr []   =   $start_date;
        $start_ts               =   strtotime( '+1 day', $start_ts );
        $start_date             =   date( "j-n-Y", $start_ts );
    }
    
    foreach ( $new_week_days_arr as $weekday_key => $weekday_value ){

        $week_day_value = strtotime( $weekday_value );
        
        if ( in_array ($weekday_value, $recurring_selected_weekdays ) ){ 
            $return_value [ $weekday_value ] = true;
        }else{

            $return_value [ $weekday_value ] = false;
        }
    }
    return $return_value;
}

/***********************************
*Modify current search by adding where clause to cquery fetching posts
************************************/
function bkap_get_custom_posts($where, $query){
	
    global $wpdb;
	
	$booking_table =   $wpdb->prefix . "booking_history";
	$meta_table    =   $wpdb->prefix . "postmeta";
	$post_table    =   $wpdb->prefix . "posts";
	
	if( !empty( $_GET["w_check_in"] )  && $query->is_main_query() ){
		
	    $chkin        =   $_GET["w_checkin"];  
		$chkout       =   $_GET["w_checkout"];
	
		$start_date   =   $chkin;
		$end_date     =   $chkout;
	
		$language_selected    =   '';
		
		if ( defined('ICL_LANGUAGE_CODE') )
		{
			if( constant('ICL_LANGUAGE_CODE') != '' )
			{
				$wpml_current_language = constant('ICL_LANGUAGE_CODE');
				if ( !empty( $wpml_current_language ) ) {
					$language_selected = $wpml_current_language;
				}
			}
		}
		if( $language_selected != '' )
		{
			$icl_table = $wpdb->prefix . "icl_translations";
						
			$get_product_ids =  "Select id From $post_table WHERE post_type = 'product' AND post_status = 'publish' AND $wpdb->posts.ID IN
			                    (SELECT b.post_id FROM $booking_table AS b WHERE ('$start_date' between b.start_date and date_sub(b.end_date,INTERVAL 1 DAY))
			                    or
			                    ('$end_date' between b.start_date and date_sub(b.end_date,INTERVAL 1 DAY))
                    			or
                    			(b.start_date between '$start_date' and '$end_date')
                    			or
                    			b.start_date = '$start_date'
                    			) and $wpdb->posts.ID NOT IN(SELECT post_id from $meta_table
                    			where meta_key = 'woocommerce_booking_settings' and meta_value LIKE '%booking_enable_date\";s:0%') and $wpdb->posts.ID NOT IN
                    			(SELECT a.id FROM $post_table AS a LEFT JOIN $meta_table AS b
                    			ON
                    			a.id = b.post_id
                    			AND
                    			( b.meta_key = 'woocommerce_booking_settings' )
                    			WHERE
                    			b.post_id IS NULL) AND $wpdb->posts.ID IN
                    			(SELECT element_id FROM $icl_table
                    			WHERE language_code = '".ICL_LANGUAGE_CODE."')";
			
			$results_date        =   $wpdb->get_results( $get_product_ids );
			 
			$new_arr_product_id  =   array();
			 
			foreach( $results_date as $product_id_key => $product_id_value ){
			
			$booking_settings    =   get_post_meta( $product_id_value->id, 'woocommerce_booking_settings', true );
			 
			if ( isset( $booking_settings['booking_enable_multiple_day'] ) && $booking_settings['booking_enable_multiple_day'] == 'on' ) {
			 
    			$query_date    =   "SELECT DATE_FORMAT(start_date,'%d-%c-%Y') as start_date,DATE_FORMAT(end_date,'%d-%c-%Y') as end_date FROM ".$wpdb->prefix."booking_history
    			                   WHERE (start_date >='".$chkin."' OR end_date >='".$chkout."') AND post_id = '".$product_id_value->id."'";
    			
    	        $results_date  =   $wpdb->get_results( $query_date );
			
    	  
    	        $dates_new     =   array();
    	        $booked_dates  =   array();
	            
	            if ( isset( $results_date ) && count( $results_date ) > 0 && $results_date != false ) {
        			
	                foreach( $results_date as $k => $v ) {
            			$start_date_lockout  =   $v->start_date;
            			$end_date_lockout    =   $v->end_date;
            			$dates               =   bkap_common::bkap_get_betweendays( $start_date_lockout, $end_date_lockout );
            			$dates_new           =   array_merge( $dates, $dates_new );
        			}
        			
			    }
    			
    			$dates_new_arr   =   array_count_values( $dates_new );
    			
    			$lockout         =   0 ;
    			
    			if ( isset( $booking_settings[ 'booking_date_lockout' ] ) ) {
    			     $lockout    =   $booking_settings[ 'booking_date_lockout' ];
    			}
    			
    			foreach( $dates_new_arr as $k => $v ) {
    			    
    			    if( $v >= $lockout && $lockout != 0 ) {
    			     
    			         if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
    			             $new_arr_product_id[] = $product_id_value->id;
    			         }
    			 
    			    }
    			}
			}else {
			    $lockout_query   =   "SELECT DISTINCT start_date FROM `".$wpdb->prefix."booking_history`
                    			     WHERE post_id= %d
                    			     AND total_booking > 0
                    			     AND available_booking <= 0";
			    $results_lockout =   $wpdb->get_results ( $wpdb->prepare( $lockout_query, $product_id_value->id ) );
			
			    $lockout_query   =   "SELECT DISTINCT start_date FROM `".$wpdb->prefix."booking_history`
                    			     WHERE post_id= %d
                    			     AND available_booking > 0";
			    $results_lock    =   $wpdb->get_results ( $wpdb->prepare( $lockout_query, $product_id_value->id ) );
		        
			    $lockout_date    =   '';
			    	
			    foreach ( $results_lockout as $k => $v ) {

			        foreach( $results_lock as $key => $value ) {
			              
			            if ( $v->start_date == $value->start_date ) {
			                 $date_lockout       =   "SELECT COUNT(start_date) FROM `".$wpdb->prefix."booking_history`
                    			                     WHERE post_id= %d
                    							     AND start_date= %s
                    							     AND available_booking = 0";
			                 $results_date_lock  =   $wpdb->get_results( $wpdb->prepare( $date_lockout, $product_id_value->id, $v->start_date ) );
			                 
			                 if ( $booking_settings['booking_date_lockout'] > $results_date_lock[0]->{'COUNT(start_date)'} ) {
			                     unset( $results_lockout[ $k ] );			                     
        	                 }        	                 
			            }
			        }
			     }
			                	
			    foreach ( $results_lockout as $k => $v ) {
			     
			         if ( $v->start_date == $start_date ){

			             if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {			                 
			                 $new_arr_product_id[] = $product_id_value->id;			                 
			             }
			         }
			     }
			 }
		}
			
	    // this query is for spefic dates product when searched out of the specified date range
	            $get_all_product_ids      =   "Select id From $post_table WHERE post_type = 'product' AND post_status = 'publish' AND $wpdb->posts.ID NOT IN(SELECT post_id from $meta_table
                                    		  where meta_key = 'woocommerce_booking_settings' and meta_value LIKE '%booking_enable_date\";s:0%') and $wpdb->posts.ID NOT IN
                                    		  (SELECT a.id FROM $post_table AS a LEFT JOIN $meta_table AS b
                                    		  ON
                                    		  a.id = b.post_id
                                    		  AND
                                    		  ( b.meta_key = 'woocommerce_booking_settings' )
                                    		  WHERE
                                    		  b.post_id IS NULL )";
    		    $results_date             =   $wpdb->get_results( $get_all_product_ids );
    
    		    $global_settings          =   json_decode( get_option( 'woocommerce_booking_global_settings' ) );
    		    $book_global_holidays_arr =   array();
    		    
    		    if ( isset( $global_settings->booking_global_holidays ) ) {
    		        $book_global_holidays      =   $global_settings->booking_global_holidays;
    	            $book_global_holidays_arr  =   explode( ",", $book_global_holidays );
                }
		                 
                foreach( $results_date as $product_id_key => $product_id_value ){
                /*******
                * This is for the product which have recurring and specififc both enabled.
                */
                $booking_settings       =   get_post_meta( $product_id_value->id, 'woocommerce_booking_settings', true );
                $specific_dates_check   =   $booking_settings[ 'booking_specific_booking' ];
                $recurring_check        =   $booking_settings[ 'booking_recurring_booking' ];
                $return_value           =   array();
                
                if ( isset( $specific_dates_check ) && $specific_dates_check == 'on' && isset( $recurring_check ) && $recurring_check == 'on' ){

                $selected_specific_dates            =   $booking_settings[ 'booking_specific_date' ];
                $recurring_selected_weekdays        =   $booking_settings[ 'booking_recurring' ];
    
                $specific_advanced_booking_period   =   $booking_settings[ 'booking_minimum_number_days' ];
                $check_advanced_booking_period      =   array();
                $min_date                           =   '';
    
    
                if ( isset( $specific_advanced_booking_period ) && $specific_advanced_booking_period > 0) {
                    
                    $current_time       =   current_time( 'timestamp' );
                    // Convert the advance period to seconds and add it to the current time
                    $advance_seconds    =   $booking_settings['booking_minimum_number_days'] *60 *60;
                    $cut_off_timestamp  =   $current_time + $advance_seconds;
                    $cut_off_date       =   date( "d-m-Y", $cut_off_timestamp );
                    $min_date           =   date( "j-n-Y", strtotime( $cut_off_date ) );
    
                    if ( isset( $booking_settings['booking_maximum_number_days'] ) ) {
                        $days   =   $booking_settings['booking_maximum_number_days'];
                    }
    		                         
                    // check mindate is today.. if yes, then check if all time slots are past, if yes, then set mindate to tomorrow
                    if ( isset ( $booking_settings['booking_enable_time'] ) && $booking_settings['booking_enable_time'] == 'on' ) {
                        $current_date       =   date( 'j-n-Y', $current_time );
                            $last_slot_hrs  =   $current_slot_hrs = $last_slot_min = 0;
                            
                            if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $min_date, $booking_settings['booking_time_settings'] ) ) {
                                
                                foreach ( $booking_settings['booking_time_settings'][ $min_date ] as $key => $value ) {
                                    $current_slot_hrs = $value['from_slot_hrs'];
                                        
                                        if ( $current_slot_hrs > $last_slot_hrs ) {
                                             $last_slot_hrs = $current_slot_hrs;
                                             $last_slot_min = $value['to_slot_min'];
                                        }
                                }
                                
                            }else {
                            // Get the weekday as it might be a recurring day setup
                            $weekday            =   date( 'w', strtotime( $min_date ) );
                            $booking_weekday    =   'booking_weekday_' . $weekday;
                            
                            if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $booking_weekday, $booking_settings['booking_time_settings'] ) ) {
                                
                                foreach ( $booking_settings['booking_time_settings'][$booking_weekday] as $key => $value ) {
                                    $current_slot_hrs = $value['from_slot_hrs'];

                                        if ( $current_slot_hrs > $last_slot_hrs ) {
                                            $last_slot_hrs = $current_slot_hrs;
                                            $last_slot_min = $value['to_slot_min'];
                                        }
                                    }
                                }
                            }
                            
                            $last_slot              =   $last_slot_hrs . ':' . $last_slot_min;
                            $advance_booking_hrs    =   0;
                            
                            if ( isset( $booking_settings['booking_minimum_number_days'] ) && $booking_settings['booking_minimum_number_days'] != '' ) {
                                $advance_booking_hrs = $booking_settings['booking_minimum_number_days'];
                            }
                            
                            $booking_date2  =   $min_date . $last_slot;
                            $booking_date2  =   date( 'Y-m-d G:i', strtotime( $booking_date2 ) );
                            $date2          =   new DateTime( $booking_date2 );
                            $booking_date1  =   date( 'Y-m-d G:i', $current_time );
                            $date1          =   new DateTime( $booking_date1 );
                            $difference     =   $date2->diff( $date1 );
    
                            if ( $difference->days > 0 ) {
                                $difference->h += $difference->days * 24;
                            }
          
                            if ( $difference->invert == 0 || $difference->h < $advance_booking_hrs ) {
                                $min_date = date( 'j-n-Y', strtotime( $min_date . '+1 day' ) );
                            }
                        }
    
                        $check_advanced_booking_period  = check_in_range_abp ( $start_date, $end_date, $min_date );
                            
                            if ( !in_array( true, $check_advanced_booking_period, true ) ){
                                
                                if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
                                    $new_arr_product_id[] = $product_id_value->id;
                                }
                            }
                        }
    
    
                        $new_end_date   =   date( "j-n-Y", strtotime( $end_date ) );
                        $new_start_date =   date( "j-n-Y", strtotime( $start_date ) );
                            
                            foreach ( $selected_specific_dates as $date_key => $date_value ){
                                $return_value [] = check_in_range( $new_start_date, $new_end_date, $date_value );
                            }
    
                        $return_value_recurring = check_in_range_weekdays ( $start_date, $end_date, $recurring_selected_weekdays );
    
    
                        if ( !in_array( true, $return_value, true ) && !in_array( true, $return_value_recurring, true )){
                            
                            if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
                                $new_arr_product_id[] = $product_id_value->id;
                            }
    
                        }
    
                        /****
                        * This check if product have any holidays:
                        */
    
                        $product_holidays = $booking_settings[ 'booking_product_holiday' ];
    
                        if( isset( $product_holidays ) && $product_holidays != '' ){
                            $return_value           =   array();
                            $product_holidays_arr   =   explode( ",", $product_holidays );
    
                            $new_end_date           =   date( "j-n-Y", strtotime( $end_date ) );
                            $new_start_date         =   date( "j-n-Y", strtotime( $start_date ) );
                            
                                if( !empty( $product_holidays_arr ) ){
                                    $return_value = check_in_range_holidays( $new_start_date, $new_end_date, $product_holidays_arr );
    
    	                        }
    
                                if ( !in_array( false, $return_value, true ) ){
    
    	                            if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
    		                            $new_arr_product_id[] = $product_id_value->id;
    	                            }
                                }
                        }
    
                        /*****
                        * Check for global holidays
                        */
                        if( !empty ( $book_global_holidays_arr ) ){
                            $return_value   =   array();
                            $new_end_date   =   date( "j-n-Y", strtotime( $end_date ) );
                            $new_start_date =   date( "j-n-Y", strtotime( $start_date ) );
    
                            $return_value   =   check_in_range_holidays( $new_start_date, $new_end_date, $book_global_holidays_arr );
    
                            if ( !in_array( false, $return_value, true ) ){
    
                                if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
                                    $new_arr_product_id[] = $product_id_value->id;
                                }
                            }
                        }
                  } else if( isset( $booking_settings['booking_enable_multiple_day'] ) && $booking_settings['booking_enable_multiple_day'] == "on" ) {
    
                    $multiple_advanced_booking_period   =   $booking_settings[ 'booking_minimum_number_days' ];
                    $check_advanced_booking_period      =   array();
                    $min_date                           =   '';
    
                    if ( isset( $multiple_advanced_booking_period ) && $multiple_advanced_booking_period > 0) {
                        $current_time = current_time( 'timestamp' );
                        // Convert the advance period to seconds and add it to the current time
                        $advance_seconds    =   $booking_settings[ 'booking_minimum_number_days' ] *60 *60;
                        $cut_off_timestamp  =   $current_time + $advance_seconds;
                        $cut_off_date       =   date( "d-m-Y", $cut_off_timestamp );
                        $min_date           =   date( "j-n-Y", strtotime( $cut_off_date ) );
    
                        if ( isset( $booking_settings['booking_maximum_number_days'] ) ) {
                            $days = $booking_settings['booking_maximum_number_days'];
                        }
    		                                     
                        // check mindate is today.. if yes, then check if all time slots are past, if yes, then set mindate to tomorrow
                        if ( isset ( $booking_settings['booking_enable_time'] ) && $booking_settings['booking_enable_time'] == 'on' ) {
                            $current_date   =   date( 'j-n-Y', $current_time );
                            $last_slot_hrs  =   $current_slot_hrs = $last_slot_min = 0;
                            
                            if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $min_date, $booking_settings['booking_time_settings'] ) ) {
                                
                                foreach ( $booking_settings['booking_time_settings'][$min_date] as $key => $value ) {                                    
                                    $current_slot_hrs   =   $value['from_slot_hrs'];

                                        if ( $current_slot_hrs > $last_slot_hrs ) {
                                            $last_slot_hrs  =   $current_slot_hrs;
                                            $last_slot_min  =   $value['to_slot_min'];
                                        }
                                }
                          }else {
                                    // Get the weekday as it might be a recurring day setup
                                    $weekday            =   date( 'w', strtotime( $min_date ) );
                                    $booking_weekday    =   'booking_weekday_' . $weekday;
                                    
                                    if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $booking_weekday, $booking_settings['booking_time_settings'] ) ) {

                                        foreach ( $booking_settings['booking_time_settings'][ $booking_weekday ] as $key => $value ) {
                                            $current_slot_hrs = $value['from_slot_hrs'];
                                            
                                            if ( $current_slot_hrs > $last_slot_hrs ) {
                                                $last_slot_hrs  =   $current_slot_hrs;
                                                $last_slot_min  =   $value['to_slot_min'];
                                            }
                                        }
                                    }
                            }
                            
                            $last_slot              =   $last_slot_hrs . ':' . $last_slot_min;
                            $advance_booking_hrs    =   0;
                            
                            if ( isset( $booking_settings['booking_minimum_number_days'] ) && $booking_settings['booking_minimum_number_days'] != '' ) {
                                $advance_booking_hrs = $booking_settings['booking_minimum_number_days'];
                            }
                            
                            $booking_date2  =   $min_date . $last_slot;
                            $booking_date2  =   date( 'Y-m-d G:i', strtotime( $booking_date2 ) );
                            $date2          =   new DateTime( $booking_date2 );
                            $booking_date1  =   date( 'Y-m-d G:i', $current_time );
                            $date1          =   new DateTime( $booking_date1 );
                            $difference     =   $date2->diff( $date1 );
                
                            if ( $difference->days > 0 ) {
                                $difference->h += $difference->days * 24;
                            }
                		                                                 
                            if ( $difference->invert == 0 || $difference->h < $advance_booking_hrs ) {
                                $min_date = date( 'j-n-Y', strtotime( $min_date . '+1 day' ) );
                            }
                        }
                
                        $new_end_date   =   date( "j-n-Y", strtotime( $end_date ) );
                        $new_start_date =   date( "j-n-Y", strtotime( $start_date ) );
                
                        $check_advanced_booking_period = check_in_range_abp ( $new_start_date, $new_end_date, $min_date );
                
                        if ( in_array( false, $check_advanced_booking_period, true ) ){
                            
                            if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
                                $new_arr_product_id[] = $product_id_value->id;
                            }
                        }
                    }
            
                    $product_holidays   =   $booking_settings[ 'booking_product_holiday' ];
            
                    if( isset( $product_holidays ) && $product_holidays != ''){
                    $return_value           =   array();
                    $product_holidays_arr   =   explode( ",", $product_holidays );
                
                    $new_end_date           =   date( "j-n-Y", strtotime( $end_date ) );
                    $new_start_date         =   date( "j-n-Y", strtotime( $start_date ) );
                
                    $return_value           =   check_in_range_holidays( $new_start_date, $new_end_date, $product_holidays_arr );
                    
                     if ( in_array( true, $return_value, true ) ){
                    
                            if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
                                 $new_arr_product_id[] = $product_id_value->id;
                            }
                        }
                    }
    
                     /*****
                     * Check for global holidays
                     */
                      if( !empty( $book_global_holidays_arr ) ){
                        $return_value   =   array();
                        $new_end_date   =   date( "j-n-Y", strtotime( $end_date ) );
                        $new_start_date =   date( "j-n-Y", strtotime( $start_date ) );
                        
                        $return_value   =   check_in_range_holidays( $new_start_date, $new_end_date, $book_global_holidays_arr );
                        
                        if ( in_array( true, $return_value, true ) ){
                            
                            if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
                                $new_arr_product_id[] = $product_id_value->id;
                            }
                        }
                    }
    
                }else{
                    // specific dates only
                    if ( isset( $specific_dates_check ) && $specific_dates_check == 'on' ){
        
                            if( isset( $booking_settings[ 'booking_specific_date' ] ) && !empty( $booking_settings[ 'booking_specific_date' ] ) ){
                                
                                $selected_specific_dates            =   $booking_settings[ 'booking_specific_date' ];
                                
                                $specific_advanced_booking_period   =   $booking_settings[ 'booking_minimum_number_days' ];
                                $check_advanced_booking_period      =   array();
                                $min_date                           =   '';
                                                                
                                if ( isset( $specific_advanced_booking_period ) && $specific_advanced_booking_period > 0) {
                                    $current_time       =   current_time( 'timestamp' );
                                    // Convert the advance period to seconds and add it to the current time
                                    $advance_seconds    =   $booking_settings['booking_minimum_number_days'] *60 *60;
                                    $cut_off_timestamp  =   $current_time + $advance_seconds;
                                    $cut_off_date       =   date( "d-m-Y", $cut_off_timestamp );
                                    $min_date           =   date( "j-n-Y", strtotime( $cut_off_date ) );
                                    
                                    if ( isset($booking_settings['booking_maximum_number_days'])) {
                                        $days = $booking_settings['booking_maximum_number_days'];
                                    }
                                    
                                    // check mindate is today.. if yes, then check if all time slots are past, if yes, then set mindate to tomorrow
                                    if ( isset ( $booking_settings['booking_enable_time'] ) && $booking_settings['booking_enable_time'] == 'on' ) {
                                        $current_date   =   date( 'j-n-Y', $current_time );
                                        $last_slot_hrs  =   $current_slot_hrs = $last_slot_min = 0;
                                        
                                        if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $min_date, $booking_settings['booking_time_settings'] ) ) {
                                            
                                            foreach ( $booking_settings['booking_time_settings'][$min_date] as $key => $value ) {
                                                $current_slot_hrs = $value['from_slot_hrs'];

                                                if ( $current_slot_hrs > $last_slot_hrs ) {
                                                    $last_slot_hrs  =   $current_slot_hrs;
                                                    $last_slot_min  =   $value['to_slot_min'];
                                                }
                                            }
                                        }else {
                                        // Get the weekday as it might be a recurring day setup
                                        $weekday            =   date( 'w', strtotime( $min_date ) );
                                        $booking_weekday    =   'booking_weekday_' . $weekday;
                                        
                                        if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $booking_weekday, $booking_settings['booking_time_settings'] ) ) {
                                            
                                            foreach ( $booking_settings['booking_time_settings'][$booking_weekday] as $key => $value ) {
                                                $current_slot_hrs = $value['from_slot_hrs'];

                                                if ( $current_slot_hrs > $last_slot_hrs ) {
                                                    $last_slot_hrs  =   $current_slot_hrs;
                                                    $last_slot_min  =   $value['to_slot_min'];
                                                }
                                            }
                                        }
                                    }
                                    $last_slot              =   $last_slot_hrs . ':' . $last_slot_min;
                                    $advance_booking_hrs    =   0;
                                    
                                    if ( isset( $booking_settings['booking_minimum_number_days'] ) && $booking_settings['booking_minimum_number_days'] != '' ) {
                                        $advance_booking_hrs = $booking_settings['booking_minimum_number_days'];
                                    }
                                    
                                    $booking_date2  =   $min_date . $last_slot;
                                    $booking_date2  =   date( 'Y-m-d G:i', strtotime( $booking_date2 ) );
                                    $date2          =   new DateTime( $booking_date2 );
                                    $booking_date1  =   date( 'Y-m-d G:i', $current_time );
                                    $date1          =   new DateTime( $booking_date1 );
                                    $difference     =   $date2->diff( $date1 );
                                    
                                    if ( $difference->days > 0 ) {
                                        $difference->h += $difference->days * 24;
                                    }
                                    
                                    if ( $difference->invert == 0 || $difference->h < $advance_booking_hrs ) {
                                        $min_date = date( 'j-n-Y', strtotime( $min_date . '+1 day' ) );
                                    }
                                }
                
                                $new_end_date   =   date( "j-n-Y", strtotime( $end_date ) );
                                $new_start_date =   date( "j-n-Y", strtotime( $start_date ) );
                                
                                $check_advanced_booking_period  = check_in_range_abp ( $new_start_date, $new_end_date, $min_date );
                                
                                if ( !in_array( true, $check_advanced_booking_period, true ) ){

                                    if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
                                        $new_arr_product_id[] = $product_id_value->id;
                                    }
                                }
                            }
                
                            $new_end_date   =   date( "j-n-Y", strtotime( $end_date ) );
                            $new_start_date =   date( "j-n-Y", strtotime( $start_date ) );
                            
                            foreach ( $selected_specific_dates as $date_key => $date_value ){
                                $return_value [] = check_in_range( $new_start_date, $new_end_date, $date_value );
                            }
                            
                            if ( !in_array( true, $return_value, true ) ){
                                if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
                                    $new_arr_product_id[] = $product_id_value->id;
                                }
                                
                            }
                        }
    
                         /****
                         * This check if Specific date's product have any holidays:
                         */   
    
                        $product_holidays = $booking_settings[ 'booking_product_holiday' ];
    
                        if( isset( $product_holidays ) && $product_holidays != '' ){
                            $return_value           =   array();
                            $product_holidays_arr   =   explode( ",", $product_holidays );
                            
                            $new_end_date           =   date( "j-n-Y", strtotime( $end_date ) );
                            $new_start_date         =   date( "j-n-Y", strtotime( $start_date ) );
                            
                            if( !empty( $product_holidays_arr ) ){
                                $return_value       =   check_in_range_holidays( $new_start_date, $new_end_date, $product_holidays_arr );
                            }
                            
                            if ( !in_array( false, $return_value, true ) ){
                                
                                if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
                                    $new_arr_product_id[] = $product_id_value->id;
                                }
                            }
                        }
    
                        /****
                        * Check for global holidays
                        */
                        if( !empty ( $book_global_holidays_arr ) ){
                            $return_value   =   array();
                            $new_end_date   =   date( "j-n-Y", strtotime( $end_date ) );
                            $new_start_date =   date( "j-n-Y", strtotime( $start_date ) );
                            
                            $return_value   =   check_in_range_holidays( $new_start_date, $new_end_date, $book_global_holidays_arr );
                            
                            if ( !in_array( false, $return_value, true ) ){
                                
                                if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
                                    $new_arr_product_id[] = $product_id_value->id;
                                }
                            }
                        }                                   
                    }else {
                        // recurring days only
                        $recurring_check                    =   $booking_settings[ 'booking_recurring_booking' ];
                        $recurring_advanced_booking_period  =   $booking_settings[ 'booking_minimum_number_days' ];
                        $check_advanced_booking_period      =   array();
                        $min_date                           =   $recurring_selected_weekdays = '';
            
                        if( $recurring_check == 'on' ){
                
                            if ( isset( $recurring_advanced_booking_period ) && $recurring_advanced_booking_period > 0) {
                                
                                $current_time       =   current_time( 'timestamp' );
                                // Convert the advance period to seconds and add it to the current time
                                $advance_seconds    =   $booking_settings['booking_minimum_number_days'] *60 *60;
                                $cut_off_timestamp  =   $current_time + $advance_seconds;
                                $cut_off_date       =    date( "d-m-Y", $cut_off_timestamp );
                                $min_date           =   date( "j-n-Y", strtotime( $cut_off_date ) );
                                
                                if ( isset($booking_settings['booking_maximum_number_days'])) {
                                    $days = $booking_settings['booking_maximum_number_days'];
                                }
                                
                                // check mindate is today.. if yes, then check if all time slots are past, if yes, then set mindate to tomorrow
                                if ( isset ( $booking_settings['booking_enable_time'] ) && $booking_settings['booking_enable_time'] == 'on' ) {
                                    $current_date   =   date( 'j-n-Y', $current_time );
                                    $last_slot_hrs  =   $current_slot_hrs = $last_slot_min = 0;
                                    
                                    if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $min_date, $booking_settings['booking_time_settings'] ) ) {
                                        
                                        foreach ( $booking_settings['booking_time_settings'][$min_date] as $key => $value ) {
                                            $current_slot_hrs   =   $value['from_slot_hrs'];
                                            
                                            if ( $current_slot_hrs > $last_slot_hrs ) {
                                                $last_slot_hrs  =   $current_slot_hrs;
                                                $last_slot_min  =   $value['to_slot_min'];
                                            }
                                        }
                                    }
                                else {
                                // Get the weekday as it might be a recurring day setup
                                $weekday            =   date( 'w', strtotime( $min_date ) );
                                $booking_weekday    =   'booking_weekday_' . $weekday;
                                
                                if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $booking_weekday, $booking_settings['booking_time_settings'] ) ) {

                                    foreach ( $booking_settings['booking_time_settings'][$booking_weekday] as $key => $value ) {
                                        $current_slot_hrs   =   $value['from_slot_hrs'];

                                        if ( $current_slot_hrs > $last_slot_hrs ) {
                                            $last_slot_hrs  =   $current_slot_hrs;
                                            $last_slot_min  =   $value['to_slot_min'];
                                        }
                                    }
                                }
                            }
                            $last_slot              =   $last_slot_hrs . ':' . $last_slot_min;
                            $advance_booking_hrs    =   0;
                            
                            if ( isset( $booking_settings['booking_minimum_number_days'] ) && $booking_settings['booking_minimum_number_days'] != '' ) {
                                $advance_booking_hrs = $booking_settings['booking_minimum_number_days'];
                            }
                            
                            $booking_date2  =   $min_date . $last_slot;
                            $booking_date2  =   date( 'Y-m-d G:i', strtotime( $booking_date2 ) );
                            $date2          =   new DateTime( $booking_date2 );
                            $booking_date1  =   date( 'Y-m-d G:i', $current_time );
                            $date1          =   new DateTime( $booking_date1 );
                            $difference     =   $date2->diff( $date1 );
                            
                            if ( $difference->days > 0 ) {
                                $difference->h += $difference->days * 24;
                            }
                		                                 
                            if ( $difference->invert == 0 || $difference->h < $advance_booking_hrs ) {
                                $min_date = date( 'j-n-Y', strtotime( $min_date . '+1 day' ) );
                            }
                        }
                
                        $new_end_date   =   date( "j-n-Y", strtotime( $end_date ) );
                        $new_start_date =   date( "j-n-Y", strtotime( $start_date ) );
                
                        $check_advanced_booking_period = check_in_range_abp ( $new_start_date, $new_end_date, $min_date );
                
                        if ( !in_array( true, $check_advanced_booking_period, true ) ){
                            
                            if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {                                
                                $new_arr_product_id [] = $product_id_value->id;
                            }
                        }
                    }
    
                    $recurring_selected_weekdays    =   $booking_settings[ 'booking_recurring' ];
                    $return_value                   =   check_in_range_weekdays ( $start_date, $end_date, $recurring_selected_weekdays );
    
    
                    if ( !in_array( true, $return_value, true ) ){
                        
                        if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
                            $new_arr_product_id[] = $product_id_value->id;
                        }
                
                    }
                    
                    /****
                    * This check if recurring week days product have any holidays:
                    */
                    $product_holidays = $booking_settings[ 'booking_product_holiday' ];
    
                    if( isset( $product_holidays ) && $product_holidays != ''){
                        $return_value           =   array();
                        $product_holidays_arr   =   explode( ",", $product_holidays );
    
                        $new_end_date           =   date( "j-n-Y", strtotime( $end_date ) );
                        $new_start_date         =   date( "j-n-Y", strtotime( $start_date ) );
                        
                        if( !empty( $product_holidays_arr ) ){
                            $return_value = check_in_range_holidays( $new_start_date, $new_end_date, $product_holidays_arr );
                        }
    
                        if ( !in_array( false, $return_value, true ) ){
    
                            if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
                                $new_arr_product_id[] = $product_id_value->id;
                        }
                    }
                 }
    
                  /****
                  * Check for global holidays
                  */
                  if( !empty ( $book_global_holidays_arr ) ){
                      $return_value     =    array();
                      $new_end_date     =    date( "j-n-Y", strtotime( $end_date ) );
                      $new_start_date   =    date( "j-n-Y", strtotime( $start_date ) );
            
                       $return_value    =    check_in_range_holidays( $new_start_date, $new_end_date, $book_global_holidays_arr );
            
                        if ( !in_array( false, $return_value, true ) ){
            
                            if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
                                $new_arr_product_id[] = $product_id_value->id;
                            }
                        }
                  }
               }
             }
           }
        }

            $val    =   "'";
            $val    .=  implode( "','", $new_arr_product_id );
            $val    .=  "'";
         
            $where  =   " AND($wpdb->posts.post_type = 'product'and $wpdb->posts.post_status = 'publish') AND $wpdb->posts.ID NOT IN
            			( $val ) AND $wpdb->posts.ID NOT IN( SELECT post_id from $meta_table
            			where meta_key = 'woocommerce_booking_settings' and meta_value LIKE '%booking_enable_date\";s:0%') AND $wpdb->posts.ID NOT IN(SELECT a.id
            			FROM $post_table AS a
            			LEFT JOIN $meta_table AS b ON a.id = b.post_id
            			AND (
            			b.meta_key =  'woocommerce_booking_settings'
            			)
            			WHERE b.post_id IS NULL ) AND $wpdb->posts.ID IN
            	       (SELECT element_id FROM $icl_table
            	       WHERE language_code = '".ICL_LANGUAGE_CODE."') ";
		
		
		}
		else {
		        $get_product_ids  =   "Select id From $post_table WHERE post_type = 'product' AND post_status = 'publish' AND $wpdb->posts.ID IN 
                    		          (SELECT b.post_id FROM $booking_table AS b WHERE ('$start_date' between b.start_date and date_sub(b.end_date,INTERVAL 1 DAY)) 
                    		          or 
                    		          ('$end_date' between b.start_date and date_sub(b.end_date,INTERVAL 1 DAY)) 
                    		          or 
                    		          (b.start_date between '$start_date' and '$end_date') 
                    		          or 
                    		          b.start_date = '$start_date' 
                    		          ) and $wpdb->posts.ID NOT IN(SELECT post_id from $meta_table 
                    		          where meta_key = 'woocommerce_booking_settings' and meta_value LIKE '%booking_enable_date\";s:0%') and $wpdb->posts.ID NOT IN
                    	              (SELECT a.id FROM $post_table AS a LEFT JOIN $meta_table AS b 
                    	              ON 
                    	              a.id = b.post_id 
                    	              AND 
                    	              ( b.meta_key = 'woocommerce_booking_settings' ) 
                    	              WHERE 
                    	              b.post_id IS NULL)";
        	    $results_date     =   $wpdb->get_results( $get_product_ids );
        	    
        	    $new_arr_product_id = array();
        	    
        	    foreach( $results_date as $product_id_key => $product_id_value ){
		        
        	    $booking_settings = get_post_meta($product_id_value->id, 'woocommerce_booking_settings', true);
        	    
        	    if ( isset( $booking_settings['booking_enable_multiple_day'] ) && $booking_settings['booking_enable_multiple_day'] == 'on' ) {
        	    
        	        $query_date    =   "SELECT DATE_FORMAT(start_date,'%d-%c-%Y') as start_date,DATE_FORMAT(end_date,'%d-%c-%Y') as end_date FROM ".$wpdb->prefix."booking_history
    						           WHERE (start_date >='".$chkin."' OR end_date >='".$chkout."') AND post_id = '".$product_id_value->id."'";
        	       
        	        $results_date  =   $wpdb->get_results($query_date);
        	       
        	        
        	        $dates_new     =   array();
        	        $booked_dates  =   array();
        	        
        	        if ( isset( $results_date ) && count( $results_date ) > 0 && $results_date != false ) {
        	            
        	            foreach( $results_date as $k => $v ) {
        	                $start_date_lockout    =   $v->start_date;
        	                $end_date_lockout      =   $v->end_date;
        	                $dates                 =   bkap_common::bkap_get_betweendays( $start_date_lockout, $end_date_lockout );
        	                $dates_new             =   array_merge( $dates, $dates_new );
        	                
        	            }
        	        }
        	         
         	        $dates_new_arr     =   array_count_values( $dates_new );
         	        
        	        $lockout           =   0;
         	        
    		        if ( isset( $booking_settings['booking_date_lockout'] ) ) {
     		            $lockout = $booking_settings[ 'booking_date_lockout' ]; 		           
     		        }
    		        
     		        foreach( $dates_new_arr as $k => $v ) {
     		            if( $v >= $lockout && $lockout != 0 ) {
     		                
     		                if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) { 
     		                    $new_arr_product_id[] = $product_id_value->id;
     		                }
    	                
     		            }
     		         }
        	    }else {
        	        $lockout_query     =   "SELECT DISTINCT start_date FROM `".$wpdb->prefix."booking_history`
									       WHERE post_id= %d
									       AND total_booking > 0
									       AND available_booking <= 0";
        	        $results_lockout   =   $wpdb->get_results ( $wpdb->prepare($lockout_query,$product_id_value->id) );
        	         
        	        $lockout_query     =   "SELECT DISTINCT start_date FROM `".$wpdb->prefix."booking_history`
					                       WHERE post_id= %d
					                       AND available_booking > 0";
        	        $results_lock      =   $wpdb->get_results ( $wpdb->prepare( $lockout_query,$product_id_value->id ) );
        	        
        	        $lockout_date      =   '';
        	        
        	        foreach ( $results_lockout as $k => $v ) {
        	            
        	            foreach( $results_lock as $key => $value ) {

        	                if ( $v->start_date == $value->start_date ) {
        	                       $date_lockout       =   "SELECT COUNT(start_date) FROM `".$wpdb->prefix."booking_history`
													       WHERE post_id= %d
													       AND start_date= %s
													       AND available_booking = 0";
        	                       $results_date_lock  =   $wpdb->get_results($wpdb->prepare($date_lockout,$product_id_value->id,$v->start_date));
        	                       
        	                       if ( $booking_settings['booking_date_lockout'] > $results_date_lock[0]->{'COUNT(start_date)'} ) {
        	                           unset( $results_lockout[ $k ] );
        	                       }
        	                }
        	            }
        	        }
        	        
        	        foreach ( $results_lockout as $k => $v ) {
        	            
        	            if ( $v->start_date == $start_date ){ 
        	                if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
            	                $new_arr_product_id[] = $product_id_value->id;
            	            }
        	            }
        	        }
        	    }
		    }
		    
		    // this query is for spefic dates product when searched out of the specified date range
		    $get_all_product_ids      =     "Select id From $post_table WHERE post_type = 'product' AND post_status = 'publish' AND $wpdb->posts.ID NOT IN(SELECT post_id from $meta_table
                                		    where meta_key = 'woocommerce_booking_settings' and meta_value LIKE '%booking_enable_date\";s:0%') and $wpdb->posts.ID NOT IN
                                		    (SELECT a.id FROM $post_table AS a LEFT JOIN $meta_table AS b
                                		    ON
                                		    a.id = b.post_id
                                		    AND
                                		    ( b.meta_key = 'woocommerce_booking_settings' )
                                		    WHERE
                                		    b.post_id IS NULL )";
		    $results_date             =     $wpdb->get_results( $get_all_product_ids );
		    
		    $global_settings          =     json_decode( get_option( 'woocommerce_booking_global_settings' ) );
		    $book_global_holidays_arr =     array();
		    
		    if (isset($global_settings->booking_global_holidays)) {
		        $book_global_holidays     =   $global_settings->booking_global_holidays;
		        $book_global_holidays_arr =   explode( ",", $book_global_holidays );
		    }
		     
		    foreach( $results_date as $product_id_key => $product_id_value ){
		         /****
		         * This is for the product which have recurring and specififc both enabled.
		         */
		        $booking_settings     =   get_post_meta( $product_id_value->id, 'woocommerce_booking_settings', true );
		        
		        if ( isset( $booking_settings['booking_date_range_type'] ) && $booking_settings['booking_date_range_type'] == 'fixed_range' ) {
		            $return_value_start = $return_value_end = array();
		        
		            $range_start_date  =  date( "j-n-Y", strtotime( $booking_settings['booking_start_date_range'] ) );
		            $range_end_date    =  date( "j-n-Y", strtotime( $booking_settings[ 'booking_end_date_range' ] ) ) ;
		             
		            $search_end_date   =  date( "j-n-Y", strtotime( $end_date ) ); //date( "j-n-Y", strtotime( $end_date ) );
		            $search_start_date =  date( "j-n-Y", strtotime( $start_date ) );
		        
		            $return_value_start [] = check_in_range( $range_start_date, $range_end_date, $search_start_date );
		            $return_value_end   [] = check_in_range( $range_start_date, $range_end_date, $search_end_date );
		        
		            $specific_dates_check =   $booking_settings[ 'booking_specific_booking' ];
		            $recurring_check      =   $booking_settings[ 'booking_recurring_booking' ];
		        
		        
		            if ( ( isset( $specific_dates_check ) && $specific_dates_check == 'on' ) || ( isset( $recurring_check ) && $recurring_check == 'on' ) ){
		        
		                 
		                if ( !in_array( true, $return_value_start, true ) && !in_array( true, $return_value_end, true ) ){
		        
		                    if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
		                        $new_arr_product_id[] = $product_id_value->id;
		                    }
		                }
		            }else{
		        
		                if ( !in_array( true, $return_value_start, true ) || !in_array( true, $return_value_end, true ) ){
		        
		                    if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
		                        $new_arr_product_id[] = $product_id_value->id;
		                    }
		                }
		            }
		        }
		        
		        $specific_dates_check =   $booking_settings[ 'booking_specific_booking' ];
		        $recurring_check      =   $booking_settings[ 'booking_recurring_booking' ];
		        $return_value         =   array();
		        
		        if ( isset( $specific_dates_check ) && $specific_dates_check == 'on' && isset( $recurring_check ) && $recurring_check == 'on' ){
		            $selected_specific_dates          =   $booking_settings[ 'booking_specific_date' ];
		            $recurring_selected_weekdays      =   $booking_settings[ 'booking_recurring' ];
		            
		            $specific_advanced_booking_period =   $booking_settings[ 'booking_minimum_number_days' ];
		            $check_advanced_booking_period    =   array();
		            $min_date                         =   '';
		            
		            if ( isset( $specific_advanced_booking_period ) && $specific_advanced_booking_period > 0) {
		                $current_time         =   current_time( 'timestamp' );
		                // Convert the advance period to seconds and add it to the current time
		                $advance_seconds      =   $booking_settings['booking_minimum_number_days'] *60 *60;
		                $cut_off_timestamp    =   $current_time + $advance_seconds;
		                $cut_off_date         =   date( "d-m-Y", $cut_off_timestamp );
		                $min_date             =   date( "j-n-Y", strtotime( $cut_off_date ) );
		            
		                if ( isset($booking_settings['booking_maximum_number_days'])) {
		                    $days = $booking_settings['booking_maximum_number_days'];
		                }
		                 
		                // check mindate is today.. if yes, then check if all time slots are past, if yes, then set mindate to tomorrow
		                if ( isset ( $booking_settings['booking_enable_time'] ) && $booking_settings['booking_enable_time'] == 'on' ) {
		                    $current_date     =   date( 'j-n-Y', $current_time );
		                    $last_slot_hrs    =   $current_slot_hrs = $last_slot_min = 0;
		                    
		                    if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $min_date, $booking_settings['booking_time_settings'] ) ) {
		                        
		                        foreach ( $booking_settings['booking_time_settings'][$min_date] as $key => $value ) {
		                            $current_slot_hrs     =   $value['from_slot_hrs'];
		                            
		                            if ( $current_slot_hrs > $last_slot_hrs ) {
		                                $last_slot_hrs    =   $current_slot_hrs;
		                                $last_slot_min    =   $value['to_slot_min'];
		                            }
		                        }
		                    }
		                    else {
		                        // Get the weekday as it might be a recurring day setup
		                        $weekday          =   date( 'w', strtotime( $min_date ) );
		                        $booking_weekday  =   'booking_weekday_' . $weekday;
		                        
		                        if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $booking_weekday, $booking_settings['booking_time_settings'] ) ) {
		                            
		                            foreach ( $booking_settings['booking_time_settings'][$booking_weekday] as $key => $value ) {
		                                $current_slot_hrs     =   $value['from_slot_hrs'];
		                                
		                                if ( $current_slot_hrs > $last_slot_hrs ) {
		                                    $last_slot_hrs    =   $current_slot_hrs;
		                                    $last_slot_min    =   $value['to_slot_min'];
		                                }
		                            }
		                        }
		                    }
		                    
		                    $last_slot            =   $last_slot_hrs . ':' . $last_slot_min;
		                    $advance_booking_hrs  =   0;
		                    
		                    if ( isset( $booking_settings['booking_minimum_number_days'] ) && $booking_settings['booking_minimum_number_days'] != '' ) {
		                        $advance_booking_hrs = $booking_settings['booking_minimum_number_days'];
		                    }
		                    
		                    $booking_date2    =   $min_date . $last_slot;
		                    $booking_date2    =   date( 'Y-m-d G:i', strtotime( $booking_date2 ) );
		                    $date2            =   new DateTime( $booking_date2 );
		                    $booking_date1    =   date( 'Y-m-d G:i', $current_time );
		                    $date1            =   new DateTime( $booking_date1 );
		                    $difference       =   $date2->diff( $date1 );
		            
		                    if ( $difference->days > 0 ) {
		                        $difference->h += $difference->days * 24;
		                    }
		                     
		                    if ( $difference->invert == 0 || $difference->h < $advance_booking_hrs ) {
		                        $min_date = date( 'j-n-Y', strtotime( $min_date . '+1 day' ) );
		                    }
		                }
		            
		                $check_advanced_booking_period  = check_in_range_abp ( $start_date, $end_date, $min_date );
		                
		                if ( !in_array( true, $check_advanced_booking_period, true ) ){
		                    
		                    if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) { 
		                        $new_arr_product_id[] = $product_id_value->id;
		                    }
		                }
		            }
		            
		            
		            $new_end_date     =   date( "j-n-Y", strtotime( $end_date ) );
		            $new_start_date   =   date( "j-n-Y", strtotime( $start_date ) );
		            
		            foreach ( $selected_specific_dates as $date_key => $date_value ){
		                $return_value [] = check_in_range( $new_start_date, $new_end_date, $date_value );
		            }
		            
		            $return_value_recurring = check_in_range_weekdays ( $start_date, $end_date, $recurring_selected_weekdays );
		            
		            
                    if ( !in_array( true, $return_value, true ) && !in_array( true, $return_value_recurring, true )){ 
		                
                        if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) { 
		                    $new_arr_product_id[] = $product_id_value->id;
		                }
		                
		            }
		            
		            /****
		             * This check if product have any holidays:
		             */
		            
		            $product_holidays = $booking_settings[ 'booking_product_holiday' ];
		            
		            if( isset( $product_holidays ) && $product_holidays != '' ){
		                $return_value         =   array();
		                $product_holidays_arr =   explode( ",", $product_holidays );
		            
		                $new_end_date         =   date( "j-n-Y", strtotime( $end_date ) );
		                $new_start_date       =   date( "j-n-Y", strtotime( $start_date ) );
		                
		                if( !empty( $product_holidays_arr ) ){
		                    $return_value = check_in_range_holidays( $new_start_date, $new_end_date, $product_holidays_arr );		            
		                }
		            
		                if ( !in_array( false, $return_value, true ) ){ 
		            
		                    if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) { 
		                        $new_arr_product_id[] = $product_id_value->id;
		                    }
		                }
		            }
		            
		            /****
		             * Check for global holidays
		             */
		            if( !empty ( $book_global_holidays_arr ) ){
		                $return_value     =   array();
		                $new_end_date     =   date( "j-n-Y", strtotime( $end_date ) );
		                $new_start_date   =   date( "j-n-Y", strtotime( $start_date ) );
		                
		                $return_value     =   check_in_range_holidays( $new_start_date, $new_end_date, $book_global_holidays_arr );
		                
		                if ( !in_array( false, $return_value, true ) ){ 
		            
		                    if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) { 
		                        $new_arr_product_id[] = $product_id_value->id;
		                    }
		                }
		            }
		            
		        } else if( isset( $booking_settings['booking_enable_multiple_day'] ) && $booking_settings['booking_enable_multiple_day'] == "on" ) {
		            
		            $multiple_advanced_booking_period =   $booking_settings[ 'booking_minimum_number_days' ];
		            $check_advanced_booking_period    =   array();
		            $min_date                         =   '';
		            
		            if ( isset( $multiple_advanced_booking_period ) && $multiple_advanced_booking_period > 0) {
		                $current_time         =   current_time( 'timestamp' );
		                // Convert the advance period to seconds and add it to the current time
		                $advance_seconds      =   $booking_settings[ 'booking_minimum_number_days' ] *60 *60;
		                $cut_off_timestamp    =   $current_time + $advance_seconds;
		                $cut_off_date         =   date( "d-m-Y", $cut_off_timestamp );
		                $min_date             =   date( "j-n-Y", strtotime( $cut_off_date ) );
		            
		                if ( isset( $booking_settings['booking_maximum_number_days'] ) ) {
		                    $days = $booking_settings['booking_maximum_number_days'];
		                }
		                 
		                // check mindate is today.. if yes, then check if all time slots are past, if yes, then set mindate to tomorrow
		                if ( isset ( $booking_settings['booking_enable_time'] ) && $booking_settings['booking_enable_time'] == 'on' ) {
		                    $current_date     =   date( 'j-n-Y', $current_time );
		                    $last_slot_hrs    =   $current_slot_hrs = $last_slot_min = 0;
		                    
		                    if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $min_date, $booking_settings['booking_time_settings'] ) ) {
		                        
		                        foreach ( $booking_settings['booking_time_settings'][$min_date] as $key => $value ) {
		                            $current_slot_hrs     =   $value['from_slot_hrs'];
		                            
		                            if ( $current_slot_hrs > $last_slot_hrs ) {
		                                $last_slot_hrs    =   $current_slot_hrs;
		                                $last_slot_min    =   $value['to_slot_min'];
		                            }
		                        }
		                    }
		                    else {
		                        // Get the weekday as it might be a recurring day setup
		                        $weekday          =   date( 'w', strtotime( $min_date ) );
		                        $booking_weekday  =   'booking_weekday_' . $weekday;
		                        
		                        if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $booking_weekday, $booking_settings['booking_time_settings'] ) ) {
		                            
		                            foreach ( $booking_settings['booking_time_settings'][$booking_weekday] as $key => $value ) {
		                                $current_slot_hrs     =   $value['from_slot_hrs'];
		                                
		                                if ( $current_slot_hrs > $last_slot_hrs ) {
		                                    $last_slot_hrs    =   $current_slot_hrs;
		                                    $last_slot_min    =   $value['to_slot_min'];
		                                }
		                            }
		                        }
		                    }
		                    $last_slot            =   $last_slot_hrs . ':' . $last_slot_min;
		                    $advance_booking_hrs  =   0;
		                    
		                    if ( isset( $booking_settings['booking_minimum_number_days'] ) && $booking_settings['booking_minimum_number_days'] != '' ) {
		                        $advance_booking_hrs = $booking_settings['booking_minimum_number_days'];
		                    }
		                    $booking_date2 =  $min_date . $last_slot;
		                    $booking_date2 =  date( 'Y-m-d G:i', strtotime( $booking_date2 ) );
		                    $date2         =  new DateTime( $booking_date2 );
		                    $booking_date1 =  date( 'Y-m-d G:i', $current_time );
		                    $date1         =  new DateTime( $booking_date1 );
		                    $difference    =  $date2->diff( $date1 );
		            
		                    if ( $difference->days > 0 ) {
		                        $difference->h += $difference->days * 24;
		                    }
		                     
		                    if ( $difference->invert == 0 || $difference->h < $advance_booking_hrs ) {
		                        $min_date = date( 'j-n-Y', strtotime( $min_date . '+1 day' ) );
		                    }
		                }
		            
		                $new_end_date     =   date( "j-n-Y", strtotime( $end_date ) );
		                $new_start_date   =   date( "j-n-Y", strtotime( $start_date ) );
		            
		                $check_advanced_booking_period = check_in_range_abp ( $new_start_date, $new_end_date, $min_date );
		            
		                if ( in_array( false, $check_advanced_booking_period, true ) ){
		                    
		                    if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
		                        $new_arr_product_id[] = $product_id_value->id;
		                    }
		                }
		            }
		            
		            $product_holidays = $booking_settings[ 'booking_product_holiday' ];
		            
		            if( isset( $product_holidays ) && $product_holidays != ''){
		                $return_value         =   array();
		                $product_holidays_arr =   explode( ",", $product_holidays );
		            
		                $new_end_date         =   date( "j-n-Y", strtotime( $end_date ) );
		                $new_start_date       =   date( "j-n-Y", strtotime( $start_date ) );
		            
		                $return_value         =   check_in_range_holidays( $new_start_date, $new_end_date, $product_holidays_arr );
		            
		                if ( in_array( true, $return_value, true ) ){ 
		            
		                    if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
		                        $new_arr_product_id[] = $product_id_value->id;
		                    }
		               }
		            }
		            
		            /****
		             * Check for global holidays
		             */
		            if( !empty ($book_global_holidays_arr) ){
		                $return_value     =   array();
		                $new_end_date     =   date( "j-n-Y", strtotime( $end_date ) );
		                $new_start_date   =   date( "j-n-Y", strtotime( $start_date ) );
		                
		                $return_value     =   check_in_range_holidays( $new_start_date, $new_end_date, $book_global_holidays_arr );
		                
		                if ( in_array( true, $return_value, true ) ){
		            
		                    if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) { 
		                        $new_arr_product_id[] = $product_id_value->id;
		                    }
		                }
		            }
		            
		        }else{
		            // specific dates only
		            if ( isset( $specific_dates_check ) && $specific_dates_check == 'on' ){
		            
		                if( isset( $booking_settings[ 'booking_specific_date' ] ) && !empty( $booking_settings[ 'booking_specific_date' ] ) ){
		            
		                    $selected_specific_dates          =   $booking_settings[ 'booking_specific_date' ];
		                    
		                    $specific_advanced_booking_period =   $booking_settings[ 'booking_minimum_number_days' ];
		                    $check_advanced_booking_period    =   array();
		                    $min_date                         =   '';		                    
		                    
		                    if ( isset( $specific_advanced_booking_period ) && $specific_advanced_booking_period > 0) {
		                        $current_time         =   current_time( 'timestamp' );
		                        // Convert the advance period to seconds and add it to the current time
		                        $advance_seconds      =   $booking_settings['booking_minimum_number_days'] *60 *60;
		                        $cut_off_timestamp    =   $current_time + $advance_seconds;
		                        $cut_off_date         =   date( "d-m-Y", $cut_off_timestamp );
		                        $min_date             =   date( "j-n-Y", strtotime( $cut_off_date ) );
		                    
		                        if ( isset($booking_settings['booking_maximum_number_days'])) {
		                            $days     =   $booking_settings['booking_maximum_number_days'];
		                        }
		                         
		                        // check mindate is today.. if yes, then check if all time slots are past, if yes, then set mindate to tomorrow
		                        if ( isset ( $booking_settings['booking_enable_time'] ) && $booking_settings['booking_enable_time'] == 'on' ) {
		                            $current_date     =   date( 'j-n-Y', $current_time );
		                            $last_slot_hrs    =   $current_slot_hrs = $last_slot_min = 0;
		                            
		                            if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $min_date, $booking_settings['booking_time_settings'] ) ) {
		                                
		                                foreach ( $booking_settings['booking_time_settings'][$min_date] as $key => $value ) {
		                                    $current_slot_hrs     =   $value['from_slot_hrs'];
		                                    
		                                    if ( $current_slot_hrs > $last_slot_hrs ) {
		                                        $last_slot_hrs    =   $current_slot_hrs;
		                                        $last_slot_min    =   $value['to_slot_min'];
		                                    }
		                                }
		                            }
		                            else {
		                                // Get the weekday as it might be a recurring day setup
		                                $weekday          =   date( 'w', strtotime( $min_date ) );
		                                $booking_weekday  =   'booking_weekday_' . $weekday;
		                                
		                                if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $booking_weekday, $booking_settings['booking_time_settings'] ) ) {
		                                    
		                                    foreach ( $booking_settings['booking_time_settings'][$booking_weekday] as $key => $value ) {
		                                        $current_slot_hrs     =   $value['from_slot_hrs'];
		                                        
		                                        if ( $current_slot_hrs > $last_slot_hrs ) {
		                                            $last_slot_hrs    =   $current_slot_hrs;
		                                            $last_slot_min    =   $value['to_slot_min'];
		                                        }
		                                    }
		                                }
		                            }
		                            $last_slot            =   $last_slot_hrs . ':' . $last_slot_min;
		                            $advance_booking_hrs  =   0;
		                            
		                            if ( isset( $booking_settings['booking_minimum_number_days'] ) && $booking_settings['booking_minimum_number_days'] != '' ) {
		                                $advance_booking_hrs = $booking_settings['booking_minimum_number_days'];
		                            }
		                            
		                            $booking_date2    =   $min_date . $last_slot;
		                            $booking_date2    =   date( 'Y-m-d G:i', strtotime( $booking_date2 ) );
		                            $date2            =   new DateTime( $booking_date2 );
		                            $booking_date1    =   date( 'Y-m-d G:i', $current_time );
		                            $date1            =   new DateTime( $booking_date1 );
		                            $difference       =   $date2->diff( $date1 );
		                    
		                            if ( $difference->days > 0 ) {
		                                $difference->h += $difference->days * 24;
		                            }
		                             
		                            if ( $difference->invert == 0 || $difference->h < $advance_booking_hrs ) {
		                                $min_date = date( 'j-n-Y', strtotime( $min_date . '+1 day' ) );
		                            }
		                        }
		                    
		                        $new_end_date     =   date( "j-n-Y", strtotime( $end_date ) );
		                        $new_start_date   =   date( "j-n-Y", strtotime( $start_date ) );
		                    
		                        $check_advanced_booking_period  = check_in_range_abp ( $new_start_date, $new_end_date, $min_date );
		                        
		                        if ( !in_array( true, $check_advanced_booking_period, true ) ){
		                            
		                            if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) { 
		                                $new_arr_product_id[] = $product_id_value->id;
		                            }
		                        }
		                    }
		            
		                    $new_end_date     =   date( "j-n-Y", strtotime( $end_date ) );
		                    $new_start_date   =   date( "j-n-Y", strtotime( $start_date ) );
		                    
		                    foreach ( $selected_specific_dates as $date_key => $date_value ){
		                        $return_value [] = check_in_range( $new_start_date, $new_end_date, $date_value );
		                    }
		                    
		                    if ( !in_array( true, $return_value, true ) ){ 
		                        
		                        if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) { 
		                            $new_arr_product_id[] = $product_id_value->id;
		                        }
		                        
		                    }
		                }
		            
		                /****
		                * This check if Specific date's product have any holidays:
		                */
		            
		                $product_holidays = $booking_settings[ 'booking_product_holiday' ];
		            
		                if( isset( $product_holidays ) && $product_holidays != '' ){
		                    $return_value         =   array();
		                    $product_holidays_arr =   explode( ",", $product_holidays );
		            
		                    $new_end_date         =   date( "j-n-Y", strtotime( $end_date ) );
		                    $new_start_date       =   date( "j-n-Y", strtotime( $start_date ) );
		                    
		                    if( !empty( $product_holidays_arr ) ){
		                        $return_value     =   check_in_range_holidays( $new_start_date, $new_end_date, $product_holidays_arr );
		                    }
		            
		                    if ( !in_array( false, $return_value, true ) ){ 
		            
		                        if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
		                            $new_arr_product_id[] = $product_id_value->id;
		                        }
		                    }
		                }
		                
		                /****
		                * Check for global holidays
		                */
		                if( !empty ( $book_global_holidays_arr ) ){
		                    $return_value     =   array();
		                    $new_end_date     =   date( "j-n-Y", strtotime( $end_date ) );
		                    $new_start_date   =   date( "j-n-Y", strtotime( $start_date ) );
		                
		                    $return_value     =   check_in_range_holidays( $new_start_date, $new_end_date, $book_global_holidays_arr );
		                
		                    if ( !in_array( false, $return_value, true ) ){ 
		            
		                        if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) { 
		                            $new_arr_product_id[] = $product_id_value->id;
		                        }
		                    }
		                }
		            }else {
		                // recurring days only
		                $recurring_check                      =   $booking_settings[ 'booking_recurring_booking' ];
		                $recurring_advanced_booking_period    =   $booking_settings[ 'booking_minimum_number_days' ];
		                $check_advanced_booking_period        =   array();
		                $min_date                             =   $recurring_selected_weekdays = '';
		                
		                if( $recurring_check == 'on' ){
		                    
		                    if ( isset( $recurring_advanced_booking_period ) && $recurring_advanced_booking_period > 0) {
		                    
		                        $current_time         =   current_time( 'timestamp' );
		                        // Convert the advance period to seconds and add it to the current time
		                        $advance_seconds      =   $booking_settings['booking_minimum_number_days'] *60 *60;
		                        $cut_off_timestamp    =   $current_time + $advance_seconds;
		                        $cut_off_date         =   date( "d-m-Y", $cut_off_timestamp );
		                        $min_date             =   date( "j-n-Y", strtotime( $cut_off_date ) );
		                    
		                        if ( isset($booking_settings['booking_maximum_number_days'])) {
		                            $days = $booking_settings['booking_maximum_number_days'];
		                        }
		                         
		                        // check mindate is today.. if yes, then check if all time slots are past, if yes, then set mindate to tomorrow
		                        if ( isset ( $booking_settings['booking_enable_time'] ) && $booking_settings['booking_enable_time'] == 'on' ) {
		                            $current_date     =   date( 'j-n-Y', $current_time );
		                            $last_slot_hrs    =   $current_slot_hrs = $last_slot_min = 0;
		                            
		                            if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $min_date, $booking_settings['booking_time_settings'] ) ) {
		                                
		                                foreach ( $booking_settings['booking_time_settings'][$min_date] as $key => $value ) {
		                                    $current_slot_hrs     =   $value['from_slot_hrs'];
		                                    
		                                    if ( $current_slot_hrs > $last_slot_hrs ) {
		                                        $last_slot_hrs    =   $current_slot_hrs;
		                                        $last_slot_min    =   $value['to_slot_min'];
		                                    }
		                                }
		                            }
		                            else {
		                                // Get the weekday as it might be a recurring day setup
		                                $weekday          =   date( 'w', strtotime( $min_date ) );
		                                $booking_weekday  =   'booking_weekday_' . $weekday;
		                                
		                                if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $booking_weekday, $booking_settings['booking_time_settings'] ) ) {
		                                    
		                                    foreach ( $booking_settings['booking_time_settings'][$booking_weekday] as $key => $value ) {
		                                        $current_slot_hrs     =   $value['from_slot_hrs'];
		                                        
		                                        if ( $current_slot_hrs > $last_slot_hrs ) {
		                                            $last_slot_hrs    =   $current_slot_hrs;
		                                            $last_slot_min    =   $value['to_slot_min'];
		                                        }
		                                    }
		                                }
		                            }
		                            $last_slot            =   $last_slot_hrs . ':' . $last_slot_min;
		                            $advance_booking_hrs  =   0;
		                            
		                            if ( isset( $booking_settings['booking_minimum_number_days'] ) && $booking_settings['booking_minimum_number_days'] != '' ) {
		                                $advance_booking_hrs = $booking_settings['booking_minimum_number_days'];
		                            }
		                            
		                            $booking_date2    =   $min_date . $last_slot;
		                            $booking_date2    =   date( 'Y-m-d G:i', strtotime( $booking_date2 ) );
		                            $date2            =   new DateTime( $booking_date2 );
		                            $booking_date1    =   date( 'Y-m-d G:i', $current_time );
		                            $date1            =   new DateTime( $booking_date1 );
		                            $difference       =   $date2->diff( $date1 );
		                    
		                            if ( $difference->days > 0 ) {
		                                $difference->h += $difference->days * 24;
		                            }
		                             
		                            if ( $difference->invert == 0 || $difference->h < $advance_booking_hrs ) {
		                                $min_date = date( 'j-n-Y', strtotime( $min_date . '+1 day' ) );
		                            }
		                        }
		                    
		                        $new_end_date     =   date( "j-n-Y", strtotime( $end_date ) );
		                        $new_start_date   =   date( "j-n-Y", strtotime( $start_date ) );
		                    
		                        $check_advanced_booking_period = check_in_range_abp ( $new_start_date, $new_end_date, $min_date );
		                    
		                        if ( !in_array( true, $check_advanced_booking_period, true ) ){
		                    
		                            if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) { 
		                    
		                                $new_arr_product_id [] = $product_id_value->id;
		                            }
		                        }
		                    }
		                    
		                    $recurring_selected_weekdays  =   $booking_settings[ 'booking_recurring' ];
		                    $return_value                 =   check_in_range_weekdays ( $start_date, $end_date, $recurring_selected_weekdays );
		                
		                
		                    if ( !in_array( true, $return_value, true ) ){ 
		                        
		                        if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) { 
		                            $new_arr_product_id[] = $product_id_value->id;
		                        }
		                        
		                    }
		                     
		                     /****
		                     * This check if recurring week days product have any holidays:
		                     */
		                    $product_holidays = $booking_settings[ 'booking_product_holiday' ];
		                
		                    if( isset( $product_holidays ) && $product_holidays != ''){
		                        $return_value         =   array();
		                        $product_holidays_arr =   explode( ",", $product_holidays );
		                
		                        $new_end_date         =   date( "j-n-Y", strtotime( $end_date ) );
		                        $new_start_date       =   date( "j-n-Y", strtotime( $start_date ) );
		                        
		                        if( !empty($product_holidays_arr)){
		                            $return_value     =   check_in_range_holidays( $new_start_date, $new_end_date, $product_holidays_arr );
		                        }
		                
		                        if ( !in_array( false, $return_value, true ) ){ 
		                
		                            if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) { 
		                                $new_arr_product_id[] = $product_id_value->id;
		                            }
		                        }
		                    }
		                    
		                     /****
		                     * Check for global holidays
		                     */
		                    if( !empty ( $book_global_holidays_arr ) ){
		                        $return_value     =   array();
		                        $new_end_date     =   date( "j-n-Y", strtotime( $end_date ) );
		                        $new_start_date   =   date( "j-n-Y", strtotime( $start_date ) );
		                    
		                        $return_value     =   check_in_range_holidays( $new_start_date, $new_end_date, $book_global_holidays_arr );
		                    
		                     if ( !in_array( false, $return_value, true ) ){ 
		                
		                            if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) { 
		                                $new_arr_product_id[] = $product_id_value->id;
		                            }
		                        }
		                    }
		                }
		            }
		        }
		    }
		    
		    $val  =  "'";
 		    $val .=  implode( "','", $new_arr_product_id );
 		    $val .=  "'";
 		    
 		    $where   =   " AND($wpdb->posts.post_type = 'product'and $wpdb->posts.post_status = 'publish') AND $wpdb->posts.ID NOT IN
    					( $val ) AND $wpdb->posts.ID NOT IN( SELECT post_id from $meta_table
    					where meta_key = 'woocommerce_booking_settings' and meta_value LIKE '%booking_enable_date\";s:0%') AND $wpdb->posts.ID NOT IN(SELECT a.id
    					FROM $post_table AS a
    					LEFT JOIN $meta_table AS b ON a.id = b.post_id
    					AND (
    					b.meta_key =  'woocommerce_booking_settings'
    					)
    					WHERE b.post_id IS NULL ) ";
		
		}
		
	}
	return $where;

}
add_filter( 'posts_where','bkap_get_custom_posts', 10, 2 );
