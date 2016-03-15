<?php
/**
 * Customer booking confirmed email
 */

echo "= " . $email_heading . " =\n\n";

$order = new WC_order( $booking->order_id );

if ( $order ) {
	echo sprintf( __( 'Hello %s', 'woocommerce-booking' ), $order->billing_first_name ) . "\n\n";
}

echo __(  'Your booking for has been confirmed. The details of your booking are shown below.', 'woocommerce-booking' ) . "\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo sprintf( __( 'Booked: %s', 'woocommerce-booking' ), $booking->product_title() ) . "\n";

echo sprintf( __( 'Booking Start Date: %s', 'woocommerce-booking' ), $booking->item_booking_date ) . "\n";

if ( isset( $booking->item_checkout_date ) && '' != $booking->item_checkout_date ) {
    echo sprintf( __( 'Booking End Date: %s', 'woocommerce-booking' ), $booking->item_checkout_date ) . "\n";
}

if ( isset( $booking->item_booking_time ) && '' != $booking->item_booking_time ) {
    echo sprintf( __( 'Booking Time: %s', 'woocommerce-booking' ), $booking->item_booking_time ) . "\n";
}

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

if ( $order ) {
	if ( $order->status == 'pending' ) {
		echo sprintf( __( 'To pay for this booking please use the following link: %s', 'woocommerce-booking' ), $order->get_checkout_payment_url() ) . "\n\n";
	}

	do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text );

	echo sprintf( __( 'Order number: %s', 'woocommerce-bookings'), $order->get_order_number() ) . "\n";
	echo sprintf( __( 'Order date: %s', 'woocommerce-bookings'), date_i18n( wc_date_format(), strtotime( $order->order_date ) ) ) . "\n";

	do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text );

	echo "\n";

    $downloadable = $order->is_download_permitted();
                
	switch ( $order->status ) {
		case "completed" :
    	    $args = array( 'show_download_links' => $downloadable,
					        'show_sku' => false,
					        'show_purchase_note' => true 
                   );
			echo $order->email_order_items_table( $args );
            break;
		case "processing" :
		    $args = array( 'show_download_links' => $downloadable,
        			        'show_sku' => true,
					        'show_purchase_note' => true 
		          );
		    echo $order->email_order_items_table( $args );
            break;
		default :
		    $args = array( 'show_download_links' => $downloadable,
    				        'show_sku' => true,
					        'show_purchase_note' => false 
		          );
		    echo $order->email_order_items_table( $args );
            break;
	}

	echo "==========\n\n";

	if ( $totals = $order->get_order_item_totals() ) {
		foreach ( $totals as $total ) {
			echo $total['label'] . "\t " . $total['value'] . "\n";
		}
	}

	echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

	do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text );
}

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );