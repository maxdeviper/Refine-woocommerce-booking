<?php
/**
 * Customer booking confirmed email
 */

echo "= " . $email_heading . " =\n\n";

$order = new WC_order( $booking->order_id );
if ( $order ) {
	echo sprintf( __( 'Hello %s', 'woocommerce-booking' ), $order->billing_first_name ) . "\n\n";
}

echo __(  'We are sorry to say that your booking could not be confirmed and has been cancelled. The details of the cancelled booking can be found below.', 'woocommerce-booking' ) . "\n\n";

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

echo __( 'Please contact us if you have any questions or concerns.', 'woocommerce-booking' ) . "\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );