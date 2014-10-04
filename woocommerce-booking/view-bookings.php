<?php
    
class view_bookings{
    
    /*********************************************
    * This function adds a page on View Bookings submenu which displays the orders with the booking details. 
    * The orders which are cancelled or refunded are not displayed.
    ***********************************************************/
   public static function bkap_woocommerce_history_page() {

        if (isset($_GET['action'])) {
	        $action = $_GET['action'];
        } else {
            $action = '';
        }
        if ($action == 'history' || $action == '') {
            $active_settings = "nav-tab-active";
        }

        ?>

        <p></p>
        <?php

        if ( $action == 'history' || $action == '' ) {
        	global $wpdb;

            $query_order = "SELECT DISTINCT order_id FROM `" . $wpdb->prefix . "woocommerce_order_items`  ";
            $order_results = $wpdb->get_results( $query_order );

            $var = $today_checkin_var = $today_checkout_var = $booking_time = "";

            $booking_time_label = get_option('book.item-meta-time');

            foreach ( $order_results as $id_key => $id_value ) {
            	$order = new WC_Order( $id_value->order_id );

                $order_items = $order->get_items();

                $terms = wp_get_object_terms( $id_value->order_id, 'shop_order_status', array('fields' => 'slugs') );
                if( (isset($terms[0]) && $terms[0] != 'cancelled') && (isset($terms[0]) && $terms[0] != 'refunded')) {

                $today_query = "SELECT * FROM `".$wpdb->prefix."booking_history` AS a1,`".$wpdb->prefix."booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a2.order_id = '".$id_value->order_id."'";
                $results_date = $wpdb->get_results ( $today_query );

                $c = 0;
                	foreach ($order_items as $items_key => $items_value ) {
                     	$start_date = $end_date = $booking_time = "";

                       	$booking_time = array();
                        
                        if (isset($items_value[$booking_time_label])) {
         	               $booking_time = explode(",",$items_value[$booking_time_label]);
                        }

                        $duplicate_of = bkap_common::bkap_get_product_id($items_value['product_id']);
                               
                                if ( isset($results_date[$c]->start_date) ) {

                                        if (isset($results_date[$c]) && isset($results_date[$c]->start_date)) $start_date = $results_date[$c]->start_date;

                                        if (isset($results_date[$c]) && isset($results_date[$c]->end_date)) $end_date = $results_date[$c]->end_date;

                                        if ($start_date == '0000-00-00' || $start_date == '1970-01-01') $start_date = '';
                                        if ($end_date == '0000-00-00' || $end_date == '1970-01-01') $end_date = '';
                                        $amount = $items_value['line_total'] + $items_value['line_tax'];
                                        if(is_plugin_active('bkap-printable-tickets/printable-tickets.php')) {
                                                $var_details = apply_filters('bkap_view_bookings',$id_value->order_id,$results_date[$c]->booking_id,$items_value['qty']);
                                        } else {
                                                $var_details = array();
                                        }
                                        if (count($booking_time) > 0) {
                                        foreach ($booking_time as $time_key => $time_value) {	
                                                if(array_key_exists('ticket_id',$var_details) && array_key_exists('security_code',$var_details)) {

                                                        $var .= "<tr>
                                                        <td>".$id_value->order_id."</td>
                                                        <td>".$order->billing_first_name." ".$order->billing_last_name."</td>
                                                        <td>".$items_value['name']."</td>
                                                        <td>".$start_date."</td>
                                                        <td>".$end_date."</td>
                                                        <td>".$time_value."</td>
                                                        <td>".$amount."</td>
                                                        <td>".$order->completed_date."</td>
                                                        ".$var_details['ticket_id']."
                                                        ".$var_details['security_code']."
                                                        <td><a href=\"post.php?post=". $id_value->order_id."&action=edit\">View Order</a></td>
                                                        </tr>";
                                                } else {
                                                        $var .= "<tr>
                                                        <td>".$id_value->order_id."</td>
                                                        <td>".$order->billing_first_name." ".$order->billing_last_name."</td>
                                                        <td>".$items_value['name']."</td>
                                                        <td>".$start_date."</td>
                                                        <td>".$end_date."</td>
                                                        <td>".$time_value."</td>
                                                        <td>".$amount."</td>
                                                        <td>".$order->completed_date."</td>
                                                        <td><a href=\"post.php?post=". $id_value->order_id."&action=edit\">View Order</a></td>
                                                        </tr>";
                                                }
                                                //foreach ($results_date as $key_date => $value_date )
                                                {
                                                        if ( $start_date == date('Y-m-d' , current_time('timestamp') ) ) {

                                                            if(array_key_exists('ticket_id',$var_details) && array_key_exists('security_code',$var_details)) {

                                                                        $today_checkin_var .= "<tr>
                                                                        <td>".$id_value->order_id."</td>
                                                                        <td>".$order->billing_first_name." ".$order->billing_last_name."</td>
                                                                        <td>".$items_value['name']."</td>
                                                                        <td>".$start_date."</td>
                                                                        <td>".$end_date."</td>
                                                                        <td>".$time_value."</td>
                                                                        <td>".$amount."</td>
                                                                        <td>".$order->completed_date."</td>
                                                                        ".$var_details['ticket_id']."
                                                                        ".$var_details['security_code']."
                                                                        <td><a href=\"post.php?post=". $id_value->order_id."&action=edit\">View Order</a></td>
                                                                        </tr>";
                                                            }
                                                                  else {
                                                                        $today_checkin_var .= "<tr>
                                                                        <td>".$id_value->order_id."</td>
                                                                        <td>".$order->billing_first_name." ".$order->billing_last_name."</td>
                                                                        <td>".$items_value['name']."</td>
                                                                        <td>".$start_date."</td>
                                                                        <td>".$end_date."</td>
                                                                        <td>".$time_value."</td>
                                                                        <td>".$amount."</td>
                                                                        <td>".$order->completed_date."</td>
                                                                        <td><a href=\"post.php?post=". $id_value->order_id."&action=edit\">View Order</a></td>
                                                                        </tr>";
                                                                }
                                                        }

                                                        if ( $end_date == date('Y-m-d' , current_time('timestamp') ) ) {
                                                            if(array_key_exists('ticket_id',$var_details) && array_key_exists('security_code',$var_details)) {
                                                                        $today_checkout_var .= "<tr>
                                                                        <td>".$id_value->order_id."</td>
                                                                        <td>".$order->billing_first_name." ".$order->billing_last_name."</td>
                                                                        <td>".$items_value['name']."</td>
                                                                        <td>".$start_date."</td>
                                                                        <td>".$end_date."</td>
                                                                        <td>".$time_value."</td>
                                                                        <td>".$amount."</td>
                                                                        <td>".$order->completed_date."</td>
                                                                        ".$var_details['ticket_id']."
                                                                        ".$var_details['security_code']."
                                                                        <td><a href=\"post.php?post=". $id_value->order_id."&action=edit\">View Order</a></td>
                                                                        </tr>";
                                                                } else {	
                                                                        $today_checkout_var .= "<tr>
                                                                        <td>".$id_value->order_id."</td>
                                                                        <td>".$order->billing_first_name." ".$order->billing_last_name."</td>
                                                                        <td>".$items_value['name']."</td>
                                                                        <td>".$start_date."</td>
                                                                        <td>".$end_date."</td>
                                                                        <td>".$time_value."</td>
                                                                        <td>".$amount."</td>
                                                                        <td>".$order->completed_date."</td>
                                                                        <td><a href=\"post.php?post=". $id_value->order_id."&action=edit\">View Order</a></td>
                                                                        </tr>";
                                                                }
                                                        }
                                                }
                                                if ( $start_date != "" ) $c++;
                                            }
                                        } else {
                                                if(array_key_exists('ticket_id',$var_details) && array_key_exists('security_code',$var_details)) {
                                                                $var .= "<tr>
                                                                <td>".$id_value->order_id."</td>
                                                                <td>".$order->billing_first_name." ".$order->billing_last_name."</td>
                                                                <td>".$items_value['name']."</td>
                                                                <td>".$start_date."</td>
                                                                <td>".$end_date."</td>
                                                                <td></td>
                                                                <td>".$amount."</td>
                                                                <td>".$order->completed_date."</td>
                                                                ".$var_details['ticket_id']."
                                                                ".$var_details['security_code']."
                                                                <td><a href=\"post.php?post=". $id_value->order_id."&action=edit\">View Order</a></td>
                                                                </tr>";
                                                        } else {
                                                                $var .= "<tr>
                                                                <td>".$id_value->order_id."</td>
                                                                <td>".$order->billing_first_name." ".$order->billing_last_name."</td>
                                                                <td>".$items_value['name']."</td>
                                                                <td>".$start_date."</td>
                                                                <td>".$end_date."</td>
                                                                <td></td>
                                                                <td>".$amount."</td>
                                                                <td>".$order->completed_date."</td>
                                                                <td><a href=\"post.php?post=". $id_value->order_id."&action=edit\">View Order</a></td>
                                                                </tr>";
                                                        }

                                                        if ( $start_date == date('Y-m-d' , current_time('timestamp') ) ) {

                                                            if(array_key_exists('ticket_id',$var_details) && array_key_exists('security_code',$var_details)) {
                                                                        $today_checkin_var .= "<tr>
                                                                        <td>".$id_value->order_id."</td>
                                                                        <td>".$order->billing_first_name." ".$order->billing_last_name."</td>
                                                                        <td>".$items_value['name']."</td>
                                                                        <td>".$start_date."</td>
                                                                        <td>".$end_date."</td>
                                                                        <td></td>
                                                                        <td>".$amount."</td>
                                                                        <td>".$order->completed_date."</td>
                                                                        ".$var_details['ticket_id']."
                                                                        ".$var_details['security_code']."
                                                                        <td><a href=\"post.php?post=". $id_value->order_id."&action=edit\">View Order</a></td>
                                                                        </tr>";
                                                                } else {
                                                                        $today_checkin_var .= "<tr>
                                                                        <td>".$id_value->order_id."</td>
                                                                        <td>".$order->billing_first_name." ".$order->billing_last_name."</td>
                                                                        <td>".$items_value['name']."</td>
                                                                        <td>".$start_date."</td>
                                                                        <td>".$end_date."</td>
                                                                        <td></td>
                                                                        <td>".$amount."</td>
                                                                        <td>".$order->completed_date."</td>
                                                                        <td><a href=\"post.php?post=". $id_value->order_id."&action=edit\">View Order</a></td>
                                                                        </tr>";
                                                                }
                                                        }

                                                        if ( $end_date == date('Y-m-d' , current_time('timestamp') ) ) {

                                                                if(array_key_exists('ticket_id',$var_details) && array_key_exists('security_code',$var_details)) {
                                                                        $today_checkout_var .= "<tr>
                                                                        <td>".$id_value->order_id."</td>
                                                                        <td>".$order->billing_first_name." ".$order->billing_last_name."</td>
                                                                        <td>".$items_value['name']."</td>
                                                                        <td>".$start_date."</td>
                                                                        <td>".$end_date."</td>
                                                                        <td></td>
                                                                        <td>".$amount."</td>
                                                                        <td>".$order->completed_date."</td>
                                                                        ".$var_details['ticket_id']."
                                                                        ".$var_details['security_code']."
                                                                        <td><a href=\"post.php?post=". $id_value->order_id."&action=edit\">View Order</a></td>
                                                                        </tr>";
                                                                } else {
                                                                        $today_checkout_var .= "<tr>
                                                                        <td>".$id_value->order_id."</td>
                                                                        <td>".$order->billing_first_name." ".$order->billing_last_name."</td>
                                                                        <td>".$items_value['name']."</td>
                                                                        <td>".$start_date."</td>
                                                                        <td>".$end_date."</td>
                                                                        <td></td>
                                                                        <td>".$amount."</td>
                                                                        <td>".$order->completed_date."</td>
                                                                        <td><a href=\"post.php?post=". $id_value->order_id."&action=edit\">View Order</a></td>
                                                                        </tr>";
                                                                }
                                                        }
                                                        if ( $start_date != "" ) $c++;
                                                }
                                            }
                                    }
                            }
                    }

                    $swf_path = plugins_url()."/woocommerce-booking/TableTools/media/swf/copy_csv_xls.swf";
                    ?>

                    <script>

                    jQuery(document).ready(function() {
                                var oTable = jQuery('.datatable').dataTable( {
                                                "bJQueryUI": true,
                                                "sScrollX": "",
                                                "bSortClasses": false,
                                                "aaSorting": [[0,'desc']],
                                                "bAutoWidth": true,
                                                "bInfo": true,
                                                "sScrollY": "100%",	
                                                "sScrollX": "100%",
                                                "bScrollCollapse": true,
                                                "sPaginationType": "full_numbers",
                                                "bRetrieve": true,
                                                "oLanguage": {
                                                                    "sSearch": "Search:",
                                                                    "sInfo": "Showing _START_ to _END_ of_TOTAL_ entries",
                                                                    "sInfoEmpty": "Showing 0 to 0 of 0 entries",
                                                                    "sZeroRecords": "No matching records found",
                                                                    "sInfoFiltered": "(filtered from _MAX_total entries)",
                                                                    "sEmptyTable": "No data available in table",
                                                                    "sLengthMenu": "Show _MENU_ entries",
                                                                    "oPaginate": {
                                                                                        "sFirst":    "First",
                                                                                        "sPrevious": "Previous",
                                                                                        "sNext":     "Next",
                                                                                        "sLast":     "Last"
                                                                                  }
                                                              },
                                                 "sDom": 'T<"clear"><"H"lfr>t<"F"ip>',
                                         "oTableTools": {
                                                            "sSwfPath": "<?php echo plugins_url(); ?>/woocommerce-booking/TableTools/media/swf/copy_csv_xls_pdf.swf"
                                                        }

                        } );
                } );


                        </script>



                <div style="float: left;">
                <h2><strong>All Bookings</strong></h2>
                </div>
                <div>
                <table id="booking_history" class="display datatable" >
                    <thead>
                        <tr>
                                <th><?php _e( 'Order ID' , 'woocommerce-booking' ); ?></th>
                            <th><?php _e( 'Customer Name' , 'woocommerce-booking' ); ?></th>
                            <th><?php _e( 'Product Name' , 'woocommerce-booking' ); ?></th>
                            <th><?php _e( 'Check-in Date' , 'woocommerce-booking' ); ?></th>
                            <th><?php _e( 'Check-out Date' , 'woocommerce-booking' ); ?></th>
                            <th><?php _e( 'Booking Time' , 'woocommerce-booking' ); ?></th>
                            <th><?php _e( 'Amount' , 'woocommerce-booking' ); ?></th>
                            <th><?php _e( 'Booking Date' , 'woocommerce-booking' ); ?></th>
                            <?php 
                            if (isset($var_details) && count($var_details) > 0 && $var_details != false) {

                                if(array_key_exists('ticket_field',$var_details) && array_key_exists('security_field',$var_details)) {
                                        ?>
                                                        <th><?php _e( $var_details['ticket_field'],'woocommerce_booking' );?></th>
                                        <th><?php _e( $var_details['security_field'],'woocommerce_booking' );?></th>
                                    <?php 
                                    }
                            }
                            ?>
                            <th><?php _e( 'Action' , 'woocommerce-booking' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php echo $var;?>
                    </tbody>
                </table>
                </div>

                <p></p>

                <div style="float: left;padding: 5">
                <h2><strong>Today Check-ins</strong></h2></div>
                <div>
                <table id="booking_history_today_check_in" class="display datatable" >
                    <thead>
                        <tr>
                                <th><?php _e( 'Order ID' , 'woocommerce-booking' ); ?></th>
                            <th><?php _e( 'Customer Name' , 'woocommerce-booking' ); ?></th>
                            <th><?php _e( 'Product Name' , 'woocommerce-booking' ); ?></th>
                            <th><?php _e( 'Check-in Date' , 'woocommerce-booking' ); ?></th>
                            <th><?php _e( 'Check-out Date' , 'woocommerce-booking' ); ?></th>
                            <th><?php _e( 'Booking Time' , 'woocommerce-booking' ); ?></th>
                            <th><?php _e( 'Amount' , 'woocommerce-booking' ); ?></th>
                            <th><?php _e( 'Booking Date' , 'woocommerce-booking' ); ?></th>
                            <?php
                            if (isset($var_details) && count($var_details) > 0 && $var_details != false) { 

                                    if(array_key_exists('ticket_field',$var_details) && array_key_exists('security_field',$var_details)) {
                                        ?>
                                                        <th><?php _e( $var_details['ticket_field'],'woocommerce_booking' );?></th>
                                        <th><?php _e( $var_details['security_field'],'woocommerce_booking' );?></th>
                                    <?php 
                                    }
                            }
                            ?>
                            <th><?php _e( 'Action' , 'woocommerce-booking' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php echo $today_checkin_var;?>
                    </tbody>
                </table>
                </div>

                <p></p>

                <div style="float: left;">
                <h2><strong>Today Check-outs</strong></h2></div>
                <div>
                <table id="booking_history_today_check_out" class="display datatable" >
                    <thead>
                        <tr>
                                <th><?php _e( 'Order ID' , 'woocommerce-booking' ); ?></th>
                            <th><?php _e( 'Customer Name' , 'woocommerce-booking' ); ?></th>
                            <th><?php _e( 'Product Name' , 'woocommerce-booking' ); ?></th>
                            <th><?php _e( 'Check-in Date' , 'woocommerce-booking' ); ?></th>
                            <th><?php _e( 'Check-out Date' , 'woocommerce-booking' ); ?></th>
                            <th><?php _e( 'Booking Time' , 'woocommerce-booking' ); ?></th>
                            <th><?php _e( 'Amount' , 'woocommerce-booking' ); ?></th>
                            <th><?php _e( 'Booking Date' , 'woocommerce-booking' ); ?></th>
                            <?php 
                            if (isset($var_details) && count($var_details) > 0 && $var_details != false) {

                                if(array_key_exists('ticket_field',$var_details) && array_key_exists('security_field',$var_details)) {
                                        ?>
                                                        <th><?php _e( $var_details['ticket_field'],'woocommerce_booking' );?></th>
                                        <th><?php _e( $var_details['security_field'],'woocommerce_booking' );?></th>
                                    <?php 
                                    }
                            }
                            ?>
                            <th><?php _e( 'Action' , 'woocommerce-booking' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php echo $today_checkout_var;?>
                    </tbody>
                </table>
                </div>
        <?php 

        }
      }
 }
?>