<?php
/**
 * Admin new booking email
 */
$order = new WC_order( $booking->order_id );

echo "= " . $email_heading . " =\n\n";

if ( bkap_common::bkap_order_requires_confirmation( $order ) && 'pending-confirmation' == $booking->item_booking_status ) {
	$opening_paragraph = __( 'A booking has been made by %s and is awaiting your approval. The details of this booking are as follows:', 'woocommerce-booking' );
} else {
	$opening_paragraph = __( 'A new booking has been made by %s. The details of this booking are as follows:', 'woocommerce-booking' );
}

if ( $order && $order->billing_first_name && $order->billing_last_name ) {
	echo sprintf( $opening_paragraph, $order->billing_first_name . ' ' . $order->billing_last_name ) . "\n\n";
}

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

if ( bkap_common::bkap_order_requires_confirmation( $order ) && 'pending-confirmation' == $booking->item_booking_status ) {
	echo __( 'This booking is awaiting your approval. Please check it and inform the customer if the date is available or not.', 'woocommerce-booking' ) . "\n\n";
}

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
