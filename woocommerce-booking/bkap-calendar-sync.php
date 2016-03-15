<?php 
// Add a new interval of 24 hours
add_filter( 'cron_schedules', 'woocommerce_bkap_add_cron_schedule' );

function woocommerce_bkap_add_cron_schedule( $schedules ) {
    
    $schedules['24_hrs'] = array(
        'interval' => 86400,  // 24 hours in seconds
        'display'  => __( 'Once Every Day' ),
    );
    return $schedules;
}

// Schedule an action if it's not already scheduled
if ( ! wp_next_scheduled( 'woocommerce_bkap_import_events' ) ) {
    wp_schedule_event( time(), '24_hrs', 'woocommerce_bkap_import_events' );
}

// Hook into that action that'll fire every 5 minutes
add_action( 'woocommerce_bkap_import_events', 'bkap_import_events_cron' );
function bkap_import_events_cron() {
    $calendar_sync = new bkap_calendar_sync();
    $calendar_sync->bkap_setup_import();
}

class bkap_calendar_sync {
    public function __construct() {
        $this->gcal_api = false;
        add_action( 'init', array( $this, 'bkap_setup_gcal_sync' ), 10 );
        $this->plugin_dir = plugin_dir_path( __FILE__ );
        $this->plugin_url = plugins_url( basename( dirname( __FILE__ ) ) );
        
        add_action( 'woocommerce_checkout_update_order_meta', array( &$this, 'bkap_google_calendar_update_order_meta' ), 11 );
        
        add_action( 'woocommerce_order_item_meta_end', array( &$this, 'bkap_add_to_woo_pages' ), 11, 3 );
        
        if( get_option( 'bkap_add_to_calendar_customer_email' ) == 'on' ) {
            add_action( 'woocommerce_order_item_meta_end', array( &$this, 'bkap_add_to_calendar_customer'), 12, 4 );
        }
        
        if( get_option( 'bkap_admin_add_to_calendar_email_notification' ) == 'on' && get_option( 'bkap_calendar_sync_integration_mode' ) == 'manually' ) {
            add_action( 'woocommerce_order_item_meta_end', array( &$this, 'bkap_add_to_calendar_admin'), 13, 4 );
        }
        
        add_action( 'wp_ajax_bkap_save_ics_url_feed', array( &$this, 'bkap_save_ics_url_feed' ) );
        
        add_action( 'wp_ajax_bkap_delete_ics_url_feed', array( &$this, 'bkap_delete_ics_url_feed' ) );
        
        add_action( 'wp_ajax_bkap_import_events', array( &$this, 'bkap_setup_import' ) );
        
        add_action( 'wp_ajax_bkap_admin_booking_calendar_events', array( &$this, 'bkap_admin_booking_calendar_events' ) );
        
        require_once $this->plugin_dir . 'includes/iCal/SG_iCal.php';
    }
    
    function bkap_setup_gcal_sync () {
        // GCal Integration
        $this->gcal_api = false;
        // Allow forced disabling in case of emergency
        require_once $this->plugin_dir . '/includes/class.gcal.php';
        $this->gcal_api = new BKAP_Gcal();
    }
    
    function bkap_google_calendar_update_order_meta( $order_id ) {
        
        global $wpdb;
        
        $gcal = new BKAP_Gcal();
        
        if( $gcal->get_api_mode() == "directly" ) {
            
            $order_item_ids   =   array();
            $sub_query        =   "";
            
            foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
                
                if ( isset( $values[ 'bkap_booking' ] ) ) {
                    
                    $_data    =   $values[ 'data' ];
                    $_booking    =   $values[ 'bkap_booking' ][0];
                    
                    $post_title  =   $_data->post->post_title;
                    // Fetch line item
                    if ( count( $order_item_ids ) > 0 ) {
                        $order_item_ids_to_exclude  = implode( ",", $order_item_ids );
                        $sub_query                  = " AND order_item_id NOT IN (".$order_item_ids_to_exclude.")";
                    }
                    
                    $query               =   "SELECT order_item_id,order_id FROM `".$wpdb->prefix."woocommerce_order_items`
						              WHERE order_id = %s AND order_item_name = %s".$sub_query;
                    
                    $results             =   $wpdb->get_results( $wpdb->prepare( $query, $order_id, $post_title ) );
                    
                    $order_item_ids[]    =   $results[0]->order_item_id;
                    
                    // check the booking status, if pending confirmation, then do not insert event in the calendar
                    $booking_status = wc_get_order_item_meta( $results[0]->order_item_id, '_wapbk_booking_status' );
                    
                    if ( ( isset( $booking_status ) && 'pending-confirmation' != $booking_status ) || ( ! isset( $booking_status )) ) {
                    
                        $event_details = array();
                        
                        $event_details[ 'hidden_booking_date' ] = $_booking[ 'hidden_date' ];
                        
                        if ( isset( $_booking[ 'hidden_date_checkout' ] ) && $_booking[ 'hidden_date_checkout' ] != '' ) {
                            $event_details[ 'hidden_checkout_date' ] = $_booking[ 'hidden_date_checkout' ];
                        }
                        
                        if ( isset( $_booking[ 'time_slot' ] ) && $_booking[ 'time_slot' ] != '' ) {
                            $event_details[ 'time_slot' ] = $_booking[ 'time_slot' ];
                        }
                        
                        $event_details[ 'billing_email' ] = $_POST[ 'billing_email' ];
                        $event_details[ 'billing_first_name' ] = $_POST[ 'billing_first_name' ];
                        $event_details[ 'billing_last_name' ] = $_POST[ 'billing_last_name' ];
                        $event_details[ 'billing_address_1' ] = $_POST[ 'billing_address_1' ];
                        $event_details[ 'billing_address_2' ] = $_POST[ 'billing_address_2' ];
                        $event_details[ 'billing_city' ] = $_POST[ 'billing_city' ];
                    
                        $event_details[ 'billing_phone' ] = $_POST[ 'billing_phone' ];
                        $event_details[ 'order_comments' ] = $_POST[ 'order_comments' ];
                        $event_details[ 'order_id' ] = $order_id;
                        
                        
                        if ( isset( $_POST[ 'shipping_first_name' ] ) && $_POST[ 'shipping_first_name' ] != '' ) {
                            $event_details[ 'shipping_first_name' ] = $_POST[ 'shipping_first_name' ];
                        }
                        if ( isset( $_POST[ 'shipping_last_name' ] ) && $_POST[ 'shipping_last_name' ] != '' ) {
                            $event_details[ 'shipping_last_name' ] = $_POST[ 'shipping_last_name' ];
                        }
                        if( isset( $_POST[ 'shipping_address_1' ] ) && $_POST[ 'shipping_address_1' ] != '' ) {
                            $event_details[ 'shipping_address_1' ] = $_POST[ 'shipping_address_1' ];
                        }
                        if ( isset( $_POST[ 'shipping_address_2' ] ) && $_POST[ 'shipping_address_2' ] != '' ) {
                            $event_details[ 'shipping_address_2' ] = $_POST[ 'shipping_address_2' ];
                        }
                        if ( isset( $_POST[ 'shipping_city' ] ) && $_POST[ 'shipping_city' ] != '' ) { 
                            $event_details[ 'shipping_city' ] = $_POST[ 'shipping_city' ];
                        }
                        
                        $event_details[ 'product_name' ] = $post_title;
                        $event_details[ 'product_qty' ] = $values[ 'quantity' ];
                        
                        $event_details[ 'product_total' ] = $_booking[ 'price' ] * $values[ 'quantity' ];
                        
                        $gcal->insert_event( $event_details, $results[0]->order_item_id, false );
                    }
                }
            }
     
        }
    }
    
    function bkap_add_to_calendar_customer( $item_id, $item, $order, $sent_admin = false ) {
        if ( ! is_account_page() && ! is_wc_endpoint_url( 'order-received' ) && false === $sent_admin ) {
            
            // check if it's a bookable product
            $bookable = bkap_common::bkap_get_bookable_status( $item[ 'product_id' ] );
            
            if ( $bookable ) {
                $bkap = $this->bkap_create_gcal_obj( $item_id, $item, $order );
                $this->bkap_add_buttons_emails( $bkap );
            }
            
        }
    }
    
    function bkap_add_to_calendar_admin( $item_id, $item, $order, $sent_admin = true ) {
        if ( ! is_account_page() && ! is_wc_endpoint_url( 'order-received' ) && true === $sent_admin ) {
            
            // check if it's a bookable product
            $bookable = bkap_common::bkap_get_bookable_status( $item[ 'product_id' ] );
            
            if ( $bookable ) {
                $bkap = $this->bkap_create_gcal_obj( $item_id, $item, $order );
                $this->bkap_add_buttons_emails( $bkap );
            }
        }
    }
    
    function bkap_add_to_woo_pages( $item_id, $item, $order ) {
        
        if ( is_account_page() && 'on' == get_option( 'bkap_add_to_calendar_my_account_page' ) ) {
            
            // check if it's a bookable product
            $bookable = bkap_common::bkap_get_bookable_status( $item[ 'product_id' ] );
            
            if ( $bookable ) {
                wp_enqueue_style( 'gcal_sync_style', plugins_url( '/css/calendar-sync.css', __FILE__ ) , '', '', false );
                $bkap = $this->bkap_create_gcal_obj( $item_id, $item, $order );
                $this->bkap_add_buttons( $bkap );
            }
            
        }
        if( is_wc_endpoint_url( 'order-received' ) && 'on' == get_option( 'bkap_add_to_calendar_order_received_page' ) ) {
            
            // check if it's a bookable product
            $bookable = bkap_common::bkap_get_bookable_status( $item[ 'product_id' ] );
            
            if ( $bookable ) {
                wp_enqueue_style( 'gcal_sync_style', plugins_url( '/css/calendar-sync.css', __FILE__ ) , '', '', false );
                $bkap = $this->bkap_create_gcal_obj( $item_id, $item, $order );
                $this->bkap_add_buttons( $bkap );
            }
        }
        
    }
    
    function bkap_create_gcal_obj( $item_id, $item, $order_details ) {

        $order_data = get_post_meta( $order_details->id );
        $order = new WC_Order( $order_details->id );
        
        $bkap = new stdClass();
         
        $bkap->item_id = $item_id;
        
        if ( isset( $item[ 'wapbk_booking_date' ] ) && $item[ 'wapbk_booking_date' ] != '' ) {
            $bkap->start = $item[ 'wapbk_booking_date' ];
        }
        
        $bkap->client_address = __( $order_data[ '_shipping_address_1' ][ 0 ] . " " . $order_data[ '_shipping_address_2' ][ 0 ] , 'woocommerce-booking' );
        $bkap->client_city = __( $order_data[ '_shipping_city' ][ 0 ], 'woocommerce-booking' );
         
        if ( isset( $item[ 'wapbk_checkout_date' ] ) && $item[ 'wapbk_checkout_date' ] != '' ) {
            $bkap->end = $item[ 'wapbk_checkout_date' ];
        } else {
            $bkap->end = $item[ 'wapbk_booking_date' ];
        }
         
        if( isset( $item[ 'wapbk_time_slot' ] ) && $item[ 'wapbk_time_slot' ] != '' ) {
            $timeslot = explode( " - ", $item[ 'wapbk_time_slot' ] );
            $from_time = date( "H:i", strtotime( $timeslot[ 0 ] ) );
            
            if( isset( $timeslot[ 1 ] ) && $timeslot[ 1 ] != '' ) {
                $to_time = date( "H:i", strtotime( $timeslot[ 1 ] ) );
                $bkap->end_time = $to_time;
                $time_end = explode( ':', $to_time );
            } else {
                $bkap->end_time = $from_time;
                $time_end = explode( ':', $from_time );
            }
            
            $bkap->start_time = $from_time;
        } else {
            $bkap->start_time = "";
            $bkap->end_time = "";
        }
            
        $bkap->client_email = $order_data[ '_billing_email' ][ 0 ];
        $bkap->client_name = $order_data[ '_shipping_first_name' ][ 0 ] . " " . $order_data[ '_shipping_last_name' ][ 0 ];
        $bkap->client_address = $order_data[ '_shipping_address_1' ][ 0 ]  . " " . $order_data[ '_shipping_address_2' ][ 0 ];
        $bkap->client_phone = $order_data[ '_billing_phone' ][ 0 ];
        $bkap->order_note  = $order->customer_note;
        
        $product = $product_with_qty = "";
        
        $product = $item[ 'name' ];
        $product_with_qty = $item[ 'name' ] . "(QTY: " . $item[ 'qty' ] . ") ";
         
        $bkap->order_total  = $order->get_total();
        $bkap->product = $product;
        $bkap->product_with_qty = $product_with_qty;
        $bkap->order_date_time = $order->post->post_date;
        $order_date = date( "Y-m-d", strtotime( $order->post->post_date ) );
        $bkap->order_date = $order_date;
        $bkap->id = $order_details->id;
          
        return $bkap;
        
    }
    
    function bkap_add_buttons_emails( $bkap ) {
        
        $gcal = new BKAP_Gcal();
        $href = $gcal->gcal( $bkap );
        $other_calendar_href = $gcal->other_cal( $bkap );
        
        $target = '_blank';
        
        if( get_option( 'bkap_calendar_in_same_window' ) == 'on' ) {
            $target = '_self';
        } else {
            $target = '_blank';
        }
        
        ?>
        <form method="post" action="<?php echo $href; ?>" target= "<?php echo $target; ?>" id="add_to_google_calendar_form">
            <input type="submit" id="add_to_google_calendar" name="add_to_google_calendar" value="<?php _e( 'Add to Google Calendar', 'woocommerce-booking' ); ?>" />
        </form>
        <form method="post" action="<?php echo $other_calendar_href; ?>" target="<?php echo $target; ?>" id="add_to_other_calendar_form">
            <input type="submit" id="download_ics" name="download_ics" value="<?php _e( 'Add to other Calendar', 'woocommerce-booking' ); ?>" />
        </form>
                
        <?php 
    }
    
    function bkap_add_buttons( $bkap ) {
        
        $gcal = new BKAP_Gcal();
        $href = $gcal->gcal( $bkap );
        $other_calendar_href = $gcal->other_cal( $bkap );
        
        $target = '_blank';
        
        if( get_option( 'bkap_calendar_in_same_window' ) == 'on' ) {
            $target = '_self';
        } else {
            $target = '_blank';
        }
                
        ?>
        <div class="add_to_calendar">
            <button onclick="myFunction( <?php echo $bkap->item_id; ?> )" class="dropbtn">Add To Calendar<i class="claret"></i></button>
            <div id="add_to_calendar_menu_<?php echo $bkap->item_id; ?>" class="add_to_calendar-content">
                <a href="<?php echo $href; ?>" target= "<?php echo $target; ?>" id="add_to_google_calendar" ><img class="icon" src="<?php echo plugins_url(); ?>/woocommerce-booking/images/google-icon.ico">Add to Google Calendar</a>
                <a href="<?php echo $other_calendar_href; ?>" target="<?php echo $target; ?>" id="add_to_other_calendar" ><img class="icon" src="<?php echo plugins_url(); ?>/woocommerce-booking/images/calendar-icon.ico">Add to other Calendar</a>
            </div>
        </div>

        <script type="text/javascript">
        /* When the user clicks on the button, 
        toggle between hiding and showing the dropdown content */

        function myFunction( chk ) {
            document.getElementById( "add_to_calendar_menu_"+ chk ).classList.toggle( "show" );
        }
        // Close the dropdown if the user clicks outside of it
        window.onclick = function(event) {
            if ( !event.target.matches( '.dropbtn' ) ) {
                var dropdowns = document.getElementsByClassName( "dropdown-add_to_calendar-content" );
        		var i;
        		for ( i = 0; i < dropdowns.length; i++ ) {
        		    var openDropdown = dropdowns[i];
    		    	if ( openDropdown.classList.contains( 'show' ) ) {
    		    	    openDropdown.classList.remove( 'show' );
    		    	}
        		}
        	}
        }

        </script>
        <?php 

    }
        
    function bkap_save_ics_url_feed() {
        $ics_table_content = '';
        if( isset( $_POST[ 'ics_url' ] ) ) {
            $ics_url = $_POST[ 'ics_url' ];
        } else {
            $ics_url = '';
        }
    
        if( $ics_url != '' ) {
            $ics_feed_urls = get_option( 'bkap_ics_feed_urls' );
            if( $ics_feed_urls == '' || $ics_feed_urls == '{}' || $ics_feed_urls == '[]' || $ics_feed_urls == 'null' ) {
                $ics_feed_urls = array();
            }
    
            if( !in_array( $ics_url, $ics_feed_urls ) ) {
                array_push( $ics_feed_urls, $ics_url );
                update_option( 'bkap_ics_feed_urls', $ics_feed_urls );
                $ics_table_content = 'yes';
            }
        }
    
        echo $ics_table_content;
        die();
    }
    
    function bkap_delete_ics_url_feed() {
        $ics_table_content = '';
        if( isset( $_POST[ 'ics_feed_key' ] ) ) {
            $ics_url_key = $_POST[ 'ics_feed_key' ];
        } else {
            $ics_url_key = '';
        }
    
        if( $ics_url_key != '' ) {
            $ics_feed_urls = get_option( 'bkap_ics_feed_urls' );
            if( $ics_feed_urls == '' || $ics_feed_urls == '{}' || $ics_feed_urls == '[]' || $ics_feed_urls == 'null' ) {
                $ics_feed_urls = array();
            }
    
            unset( $ics_feed_urls[ $ics_url_key ] );
            update_option( 'bkap_ics_feed_urls', $ics_feed_urls );
            $ics_table_content = 'yes';
        }
    
        echo $ics_table_content;
        die();
    }
    
    public function bkap_setup_import() {
            
        global $date_formats;
        global $wpdb;
        
        $global_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
        
        if( isset( $_POST[ 'ics_feed_key' ] ) ) {
            $ics_url_key = $_POST[ 'ics_feed_key' ];
        } else {
            $ics_url_key = '';
        }
         
        $ics_feed_urls = get_option( 'bkap_ics_feed_urls' );
        if( $ics_feed_urls == '' || $ics_feed_urls == '{}' || $ics_feed_urls == '[]' || $ics_feed_urls == 'null' ) {
            $ics_feed_urls = array();
        }
        
        if( count( $ics_feed_urls ) > 0 && isset( $ics_feed_urls[ $ics_url_key ] ) ) {
            $ics_feed = $ics_feed_urls[ $ics_url_key ];
        } else { 
            $ics_feed = '';
        }
        
        if ( $ics_feed == '' && count( $_POST ) <= 0 ) { // it means it was called using cron, so we need to auto import for all the calendars saved
            if ( isset( $ics_feed_urls ) && count( $ics_feed_urls ) > 0 ) {
                
                foreach ( $ics_feed_urls as $ics_feed ) {
                    $ical = new SG_iCalReader( $ics_feed );
                    $ical_array = $ical->getEvents();
                    $this->bkap_import_events( $ical_array );
                }
            }
        } else {
            $ical = new SG_iCalReader( $ics_feed );
            $ical_array = $ical->getEvents();
            
            $this->bkap_import_events( $ical_array );
            
        }
        
        die();
    }
    
    public function bkap_import_events( $ical_array ) {
        
        global $wpdb;
        
        $event_uids = get_option( 'bkap_event_uids_ids' );
        if( $event_uids == '' || $event_uids == '{}' || $event_uids == '[]' || $event_uids == 'null' ) {
            $event_uids = array();
        }
        
        if( isset( $ical_array ) ) {
        
            // get the last stored event count
            $options_query = "SELECT option_name FROM `" . $wpdb->prefix. "options`
                                        WHERE option_name like 'bkap_imported_events_%'";
        
            $results = $wpdb->get_results( $options_query );
        
            if (isset( $results ) && count( $results ) > 0 ) {
                $last_count = 0;
                foreach ( $results as $results_key => $option_name ) {
                    $explode_array = explode( '_', $option_name->option_name );
                    $current_id = $explode_array[3];
                    
                    if ( $last_count < $current_id ) {
                        $last_count = $current_id;
                    }
                }
                
                $i = $last_count + 1;
                
            } else {
                $i = 0;
            }
            foreach( $ical_array as $key_event => $value_event ) {

                //Do stuff with the event $event
                if( !in_array( $value_event->uid, $event_uids ) ) {

                    $option_name = 'bkap_imported_events_' . $i;        
                    add_option( $option_name, json_encode( $value_event ) );
                    
                    array_push( $event_uids, $value_event->uid );
                    update_option( 'bkap_event_uids_ids', $event_uids );
        
        
                }
                $i++;
            }
            echo "All the Events are Imported.";
        }
        
    }
    
    public function bkap_admin_booking_calendar_events() {
        
        global $wpdb;
        $total_orders_to_export = bkap_common::bkap_get_total_bookings_to_export();
        
        $gcal = new BKAP_Gcal();
        $current_time = current_time( 'timestamp' );
        
        $event_item_ids = get_option( 'bkap_event_item_ids' );
        if( $event_item_ids == '' || $event_item_ids == '{}' || $event_item_ids == '[]' || $event_item_ids == 'null' ) {
            $event_item_ids = array();
        }
         
        if ( isset( $total_orders_to_export ) && count( $total_orders_to_export ) > 0 ) {
            foreach( $total_orders_to_export as $order_id => $value ) {
                $data = get_post_meta( $order_id );
                foreach ( $value as $item_id ) {
                    
                    if ( !in_array( $item_id, $event_item_ids ) ) {
                        $event_details = array();
                        
                        $order = new WC_Order( $order_id );
                    
                        $item_booking_date = wc_get_order_item_meta( $item_id, '_wapbk_booking_date' );
                        $item_checkout_date = wc_get_order_item_meta( $item_id, '_wapbk_checkout_date' );
                        $item_booking_time = wc_get_order_item_meta( $item_id, '_wapbk_time_slot' );
                        
                        $product_id = wc_get_order_item_meta( $item_id, '_product_id' );
                        $quantity = wc_get_order_item_meta( $item_id, '_qty' );
                        
                        $booking_status = wc_get_order_item_meta( $item_id, '_wapbk_booking_status' );
                        
                        if ( ( isset( $booking_status ) && 'pending-confirmation' != $booking_status ) || ( ! isset( $booking_status )) ) {
                        
                            if ( isset( $item_booking_date ) && strtotime( $item_booking_date ) > $current_time ) {
                                $event_details[ 'hidden_booking_date' ] = $item_booking_date;
                            }
                        
                        
                            if ( isset( $item_checkout_date ) && $item_checkout_date != '' ) {
                                $event_details[ 'hidden_checkout_date' ] = $item_checkout_date;
                            }
                            
                            if ( isset( $item_booking_time ) && $item_booking_time != '' ) {
                                $event_details[ 'time_slot' ] = $item_booking_time;
                            }
                        
                            $event_details[ 'billing_email' ] = $data[ '_billing_email' ][ 0 ];
                            $event_details[ 'billing_address_1' ] = $data[ '_billing_address_1' ][ 0 ];
                            $event_details[ 'billing_address_2' ] = $data[ '_billing_address_2' ][ 0 ];
                            $event_details[ 'billing_city' ] = $data[ '_billing_city' ][ 0 ];
                            $event_details[ 'order_id' ] = $order_id;
                            
                            $event_details[ 'shipping_first_name' ] = $data[ '_shipping_first_name' ][ 0 ];
                            $event_details[ 'shipping_last_name' ] = $data[ '_shipping_last_name' ][ 0 ];
                            $event_details[ 'shipping_address_1' ] = $data[ '_shipping_address_1' ][ 0 ];
                            $event_details[ 'shipping_address_2' ] = $data[ '_shipping_address_2' ][ 0 ];
                            $event_details[ 'billing_phone' ] = $data[ '_billing_phone' ][ 0 ];
                            $event_details[ 'order_comments' ]  = $order->customer_note;
                            
                            $_product = wc_get_product( $product_id );
                             
                            $event_details[ 'product_name' ] = $_product->get_title();
                            $event_details[ 'product_qty' ] = $quantity;
                            $event_details[ 'product_total' ] = wc_get_order_item_meta( $item_id, '_line_total' );
                            
                            $gcal->insert_event( $event_details, $item_id, false );
                        }
                    }
                }
            }
        }
        die();
    }
}// end of class
$bkap_calendar_sync = new bkap_calendar_sync();