<div>
    <h2>Edit Booking</h2>
</div>
<?php 
global $wpdb;

// create the status array
$booking_status = array( 'pending-confirmation' => 'pending-confirmation',
                            'confirmed'         => 'confirmed',
                            'paid'              => 'paid',
                            'cancelled'         => 'cancelled'                        
                    );


$item_id = 0;
$order_id = 0;
if (isset( $_GET[ 'item_id' ] ) && $_GET[ 'item_id' ] != 0 ) {
    $item_id = $_GET[ 'item_id' ];
}

$query_order_id = "SELECT order_id FROM `". $wpdb->prefix."woocommerce_order_items`
                    WHERE order_item_id = %d";
$get_order_id = $wpdb->get_results( $wpdb->prepare( $query_order_id, $item_id ) );

if ( isset( $get_order_id ) && is_array( $get_order_id ) && count( $get_order_id ) > 0 ) {
    $order_id = $get_order_id[0]->order_id;
}
// get the order details from post
$post_data = get_post( $order_id );

//create order object
$order = new WC_Order( $order_id );

// order details
$order_data = $order->get_items();

$start_date_label = get_option( 'book.item-meta-date' );
$end_date_label = get_option( 'checkout.item-meta-date' );
$time_label = get_option( 'book.item-meta-time' );

$product_name = '';
$booking_start_date = '';
$booking_end_date = '';
$booking_time = '';

foreach ( $order_data as $item_key => $item_value ) {
    if ( $item_key == $item_id ) {
        $product_name = $item_value['name'];
        $product_id = $item_value[ 'product_id' ];
        if( isset( $item_value[ $start_date_label ] ) && '' != $item_value[ $start_date_label ] ) {
            $booking_start_date = $item_value[ $start_date_label ];
        }
        
        if( isset( $item_value[ $end_date_label ] ) && '' != $item_value[ $end_date_label ] ) {
            $booking_end_date = $item_value[ $end_date_label ];
        }
        
        if( isset( $item_value[ $time_label ] ) && '' != $item_value[ $time_label ] ) {
            $booking_time = $item_value[ $time_label ];
        }
        
    }
}
// get the booking status
$item_booking_status = wc_get_order_item_meta( $item_id, '_wapbk_booking_status' );

// handle a blank status
if ( ( isset( $item_booking_status ) && '' == $item_booking_status ) || ! isset( $item_booking_status ) ) {
    $item_booking_status = 'paid';
} 
// customer details
//$customer_id = get_post_meta( $order_id, '_customer_user', true );
$customer_id = $order->user_id;

if ( isset( $customer_id ) && 0 == $customer_id ) {
    $customer_login = 'Guest';
} else {
    $customer_details = get_userdata( $customer_id );
    $customer_login = $customer_details->data->user_login;
}

// name
$customer_name = $order->billing_first_name . " " . $order->billing_last_name;
//billing address
$customer_address = $order->get_formatted_billing_address();
// replace the <br/> tag with HTML entities &#10; Line Feed and &#13; Carriage Return
$customer_address = str_replace( '<br/>', '&#13;&#10;', $customer_address );
// billing email
$customer_email = $order->billing_email;
// phone
$customer_phone = $order->billing_phone;

?>

    <h2>Booking Details</h2>
    Order number: <a href="<?php echo admin_url( 'post.php?post=' . $order_id . '&action=edit' ); ?>">#<?php echo $order_id; ?></a>
    
    <div id="updated_message" class="updated fade" style="display:none;"><p><strong><?php _e( 'Your settings have been saved.', 'woocommerce-booking' ); ?></strong></p></div>
    <div id="first" style="height: 230px;">
        <div id="general_details" style="width:50%;max-width:550px;float:left;">
            <h4>General Details</h4>
            <table  style="width:100%;max-width:450px;">
                <tr>
                    <th style="vertical-align:top;float:left;">
        				<label for="order_id"><?php _e( 'Order ID', 'woocommerce-booking' ); ?></label>
        			</th>
                    <td>
                        <input type="text" style="width:100%;max-width:200px;" name="order_id" id="order_id" value="<?php echo $order_id; ?>" readonly>
                    </td>
                </tr>
                
                <tr>
                    <th style="vertical-align:top;float:left;">
        				<label for="order_date"><?php _e( 'Date Created', 'woocommerce-booking' ); ?></label>
        			</th>
                    <td>
                        <input type="text" style="width:100%;max-width:200px;" name="order_date" id="order_date" value="<?php echo date( 'M d, Y H:i A', strtotime( $post_data->post_date ) ); ?>" readonly>
                    </td>
                </tr>
                
                <tr>
                    <th style="vertical-align:top;float:left;">
        				<label for="booking_status"><?php _e( 'Booking Status', 'woocommerce-booking' ); ?></label>
        			</th>
                    <td>
                        <?php
                        $field_status = 'disabled';
                        $requires_confirmation = bkap_common::bkap_product_requires_confirmation( $product_id );
                        if ( $requires_confirmation ) {
                            $field_status = '';
                        } 
                        ?>
                        <select id="booking_status" name="booking_status" <?php echo $field_status; ?> style="width:200px;">
                            <?php
                            foreach ( $booking_status as $key => $value ) {
                                $selected_attr = '';
                                if ( $value == $item_booking_status ) {
                                    $selected_attr = 'selected';
                                }
                                printf( "<option %s value='%s'>%s</option>\n",
                                    esc_attr( $selected_attr ),
                                    esc_attr( $value ),
                                    $value
                                );
                            }
                            ?>
                        </select>
                        
                    </td>
                </tr>
                
                <tr>
                    <th style="vertical-align:top;float:left;">
        				<label for="customer"><?php _e( 'Customer', 'woocommerce-booking' ); ?></label>
        			</th>
                    <td>
                        <input type="text" style="width:100%;max-width:200px;" name="customer" id="customer" value="<?php echo $customer_login; ?>" readonly>
                    </td>
                </tr>
            
             <?php  ?>
            </table>
        </div>
        
        <div id="customer_details" style="width:50%;max-width:550px;float:right;">
            <h4>Customer Details</h4>
            <table style="width:100%;max-width:450px;">
                <tr>
                    <th style="vertical-align:top;float:left;">
        				<label for="customer_name"><?php _e( 'Name', 'woocommerce-booking' ); ?></label>
        			</th>
                    <td>
                        <input type="text" style="width:100%;max-width:200px;" name="customer_name" id="customer_name" value="<?php echo $customer_name; ?>" readonly>
                    </td>
                </tr>
                
                <tr>
                    <th style="vertical-align:top;float:left;">
        				<label for="customer_address"><?php _e( 'Address', 'woocommerce-booking' ); ?></label>
        			</th>
                    <td>
                        <textarea style="width:100%;max-width:200px;" rows="5" name="customer_address" id="customer_address" readonly><?php echo $customer_address; ?></textarea>
                    </td>
                </tr>
                
                <tr>
                    <th style="vertical-align:top;float:left;">
        				<label for="customer_email"><?php _e( 'Email', 'woocommerce-booking' ); ?></label>
        			</th>
                    <td>
                        <input type="text" style="width:100%;max-width:200px;" name="customer_email" id="customer_email" value="<?php echo $customer_email; ?>" readonly>
                    </td>
                </tr>
                
                <tr>
                    <th style="vertical-align:top;float:left;">
        				<label for="customer_phone"><?php _e( 'Phone', 'woocommerce-booking' ); ?></label>
        			</th>
                    <td>
                        <input type="text" style="width:100%;max-width:200px;" name="customer_phone" id="customer_phone" value="<?php echo $customer_phone; ?>" readonly>
                    </td>
                </tr>
            </table>
        </div>
    
    </div>

    <div id="booking_details" style="width:100%;max-width:450px;float:left;">
        <h4>Booking Details</h4>
        <table>
            <tr>
                <th style="vertical-align:top;float:left;">
    				<label for="product_booked"><?php _e( 'Product Booked', 'woocommerce-booking' ); ?></label>
    			</th>
                <td>
                    <input type="text" style="width:100%;max-width:200px;" name="product_booked" id="product_booked" value="<?php echo $product_name; ?>" readonly>
                </td>
            </tr>
            
			<tr>
                <th style="vertical-align:top;float:left;">
    				<label for="start_date"><?php _e( 'Booking Start Date', 'woocommerce-booking' ); ?></label>
				</th>
                <td>
                    <input type="text" style="width:100%;max-width:200px;" name="start_date" id="start_date" value="<?php echo $booking_start_date; ?>" readonly>
                </td>
            </tr>
            <?php 
            if ( isset ( $booking_end_date ) && '' != $booking_end_date ) {
            ?>
                <tr>
                    <th style="vertical-align:top;float:left;">
        				<label for="end_date"><?php _e( 'Booking End Date', 'woocommerce-booking' ); ?></label>
    				</th>
                    <td>
                        <input type="text" style="width:100%;max-width:200px;" name="end_date" id="end_date" value="<?php echo $booking_end_date; ?>" readonly>
                    </td>
                </tr>
            <?php 
            }
            ?>
            
            <?php 
            if ( isset ( $booking_time ) && '' != $booking_time ) {
            ?>
                <tr>
                    <th style="vertical-align:top;float:left;">
        				<label for="booking_time"><?php _e( 'Booking Time', 'woocommerce-booking' ); ?></label>
    				</th>
                    <td>
                        <input type="text" style="width:100%;max-width:200px;" name="booking_time" id="booking_time" value="<?php echo $booking_time; ?>" readonly>
                    </td>
                </tr>
            <?php 
            }
            ?>
        </table>
        <br>
        <input type="button" class="button-primary" id="save_status" name="save_status" value="<?php _e( 'Save', 'woocommerce-booking' ); ?>" onclick="bkap_save_booking_status(<?php echo $item_id;?>)" />
    </div>
    
    
    
    <script type="text/javascript">
    function bkap_save_booking_status( item_id ) {
    	var data = {
				item_id: item_id,
				status: jQuery( '#booking_status' ).val(),
				action: 'bkap_save_booking_status'
			};
			jQuery.post( '<?php echo get_admin_url(); ?>admin-ajax.php', data, function( response ) {
				document.getElementById( "updated_message" ).style.display = "block";
			});
    }
    </script>
