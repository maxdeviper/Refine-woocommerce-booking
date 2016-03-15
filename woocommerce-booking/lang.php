<?php	
global $book_translations, $book_lang;

/**
 * This function is used to call the string defined for translation.
 */
function bkap_get_book_t( $str ) {
	global $book_translations, $book_lang;
        
        $book_lang          = 'en';
        $book_translations  = array(
	'en' => array(
	
		// Labels for Booking Date & Booking Time on the product page
		'book.date-label'     		=> __( "Start Date",          "woocommerce-booking" ), 
		'checkout.date-label'     	=>   ( "<br>".__( "End Date", "woocommerce-booking" ) ), 
		'book.time-label'     		=> __( "Booking Time",        "woocommerce-booking" ),
		'book.item-comments'		=> __( "Comments",            "woocommerce-booking" ),
			
		// Message shown on checkout page if the Quantity exceeds the maximum available bookings for that time slot
		// Message Text: "product name" has only "X" tickets available for the time slot "from-to hours"
		'book.limited-booking-msg1'	=> __( " has only ",                              "woocommerce-booking" ),
		'book.limited-booking-msg2'	=> __( " tickets available for the time slot ",   "woocommerce-booking" ),
		
		//Message shown on checkout page if the time slot for the chosen date has been fully booked
		//Message Text: "For "product name", the time slot of "time slot" has been fully booked. Please try another time slot.  
		'book.no-booking-msg1'		=> __( "For ",                                                    "woocommerce-booking" ),
		'book.no-booking-msg2'		=> __( ", the timeslot of ",                                      "woocommerce-booking" ),
		'book.no-booking-msg3'		=> __( " has been fully booked. Please try another time slot.",   "woocommerce-booking" ),
		
		// Labels for Booking Date & Booking Time on the "Order Received" page on the web and in the notification email to customer & admin
		'book.item-meta-date'		=> __( "Start Date",   "woocommerce-booking" ),
		'checkout.item-meta-date'	=> __( "End Date",     "woocommerce-booking" ),
		'book.item-meta-time'		=> __( "Booking Time", "woocommerce-booking" ),
			
		// Labels for Booking Date & Booking Time on the Cart Page and the Checkout page
		'book.item-cart-date'		=> __( "Start Date",   "woocommerce-booking" ),
		'checkout.item-cart-date'	=> __( "End Date",     "woocommerce-booking" ),
		'book.item-cart-time'		=> __( "Booking Time", "woocommerce-booking" ),
			
		// Message shown on checkout page if the Quantity exceeds the maximum available bookings for that date
		// Message Text: "product name" has only "X" tickets available for the date "date"
		'book.limited-booking-date-msg1'	=> __( " has only ",                       "woocommerce-booking" ),
		'book.limited-booking-date-msg2'	=> __( " tickets available for the date ", "woocommerce-booking" ),
		
		//Message shown on checkout page if the chosen date has been fully booked
		//Message Text: "For "product name", the date of "date" has been fully booked. Please try another date.
		'book.no-booking-date-msg1'		=> __( "For ",                                               "woocommerce-booking" ),
		'book.no-booking-date-msg2'		=> __( ", the date of ",                                     "woocommerce-booking" ),
		'book.no-booking-date-msg3'		=> __( " has been fully booked. Please try another date.",   "woocommerce-booking" ),
		
		//Labels for partial payment in partial payment addon
		'book.item-partial-total'	  => __( "Total ",                "woocommerce-booking" ),
		'book.item-partial-deposit'	  => __( "Partial Deposit ",      "woocommerce-booking" ),
		'book.item-partial-remaining' => __( "Amount Remaining",      "woocommerce-booking" ),
		'book.partial-payment-heading'=> __( "Partial Payment",       "woocommerce-booking" ),
		
		//Labels for full payment in partial payment addon
		'book.item-total-total'	    => __( "Total ",          "woocommerce-booking" ),
		'book.item-total-deposit'	=> __( "Total Deposit ",  "woocommerce-booking" ),
		'book.item-total-remaining'	=> __( "Amount Remaining","woocommerce-booking" ),
		'book.total-payment-heading'=> __( "Total Payment",   "woocommerce-booking" ),
	    
	    //Labels for dynamic stock display
		'book.stock-total'               => __( " stock total",                           "woocommerce-booking" ),
		'book.available-stock'           => __( " bookings are available on ",            "woocommerce-booking" ),
		'book.available-stock-time-msg1' => __( " bookings are available for the slot ",  "woocommerce-booking" ),
		'book.available-stock-time-msg2' => __( " on ",                                   "woocommerce-booking" ),
	    
	    //Labels for security deposits payment in partial payment addon
	    'book.item-security-total'	    => "Total ",
	    'book.item-security-deposit'	=> "Security Deposit ",
	    'book.item-security-remaining'	=> "Product Price ",
	    'book.total-security-heading'	=> "Security Deposit",
	    
	    // Message to be displayed on the Product page when conflicting products are added.
	    'book.conflicting-products'	   => __( "You cannot add products requiring Booking confirmation along with other products that do not need a confirmation. The existing products have been removed from your cart.", "woocommerce-booking" ),
	),
	
	);
	
	return $book_translations[ $book_lang ][ $str ];
}

?>