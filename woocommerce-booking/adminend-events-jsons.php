<?php

    $url    =   dirname(__FILE__);
    $my_url =   explode('wp-content' , $url);
    $path   =   $my_url[0];

    include_once $path . 'wp-load.php';
    global $wpdb;
    
    $booking_query  =   "SELECT *,a2.order_id FROM `".$wpdb->prefix."booking_history` AS a1,`".$wpdb->prefix."booking_order_history` AS a2 WHERE a1.id = a2.booking_id ORDER BY a2.order_id DESC";
    $results        =   $wpdb->get_results($booking_query);
 
    $data           =   array();

    foreach ( $results as $key => $value ) {
	    
	    $order = new WC_Order( $value->order_id );
	    
	    if( isset( $order->post_status ) && ( $order->post_status != 'wc-cancelled' ) && ( $order->post_status != 'wc-refunded' ) && ( $order->post_status != 'trash' ) && ( $order->post_status != '' ) && ( $order->post_status != 'wc-failed' ) ) {
    	    
	        $product_name = html_entity_decode( get_the_title( $value->post_id ) , ENT_COMPAT, 'UTF-8' );

            if( $value->from_time != "" && $value->to_time != "" ){ // this condition is used for adding from and to time slots.

                $date_from_time         =  $value->start_date;
    	        $date_from_time        .=  " ".$value->from_time; 
    	        $post_from_timestamp    =  strtotime( $date_from_time ); 
    	        $post_from_date         =  date ( 'Y-m-d H:i:s',$post_from_timestamp );
    	         	       
    	        $date_to_time           = $value->start_date;
    	        $date_to_time          .= " ".$value->to_time;
    	        $post_to_timestamp      = strtotime( $date_to_time );
    	        $post_to_date           = date ( 'Y-m-d H:i:s',$post_to_timestamp );
    	        
                array_push( $data, array(
                    	        'id'       =>  $value->order_id,
                    	        'title'    =>  $product_name,
                    	        'start'    =>  $post_from_date,
                    	        'end'      =>  $post_to_date,
                    	        'value'    =>  $value
                    	        )
                          );
    	    } else if( $value->from_time != "" ){ // this condition is used for adding only from time slots.
                $date_from_time      =  $value->start_date;
                $date_from_time     .=  " ".$value->from_time; 
                $post_from_timestamp =  strtotime( $date_from_time ); 
                $post_from_date      =  date ( 'Y-m-d H:i:s',$post_from_timestamp );
                
                $time                =  strtotime($value->from_time);
                $endTime             =  date("H:i", strtotime('+30 minutes', $time));
                
                $date_to_time        =  $value->start_date;
                $date_to_time       .=  " ".$endTime;
                $post_to_timestamp   =  strtotime( $date_to_time );
                $post_to_date        =  date ( 'Y-m-d H:i:s',$post_to_timestamp );
                
                array_push( $data, array(
                                 'id'       =>  $value->order_id,
                                 'title'    =>  $product_name,
                                 'start'    =>  $post_from_date,
                                 'end'      =>  $post_to_date,
                                 'value'    =>  $value
                                 )
                          );
    	    } else {
        	    array_push( $data, array(
                        	    'id'       =>  $value->order_id,
                        	    'title'    =>  $product_name,
                        	    'start'    =>  $value->start_date,
                        	    'end'      =>  $value->end_date,
                        	    'value'    =>  $value
                        	    )
        	              );
    	    }
	    }
	}
 
    //var_dump($data);
    echo json_encode( $data );
 
?>