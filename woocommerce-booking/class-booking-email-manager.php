<?php /**
 * Handles email sending
 */
class BKAP_Email_Manager {

	/**
	 * Constructor sets up actions
	 */
	public function __construct() {
	    
	    add_action( 'woocommerce_checkout_order_processed', array( &$this, 'init_confirmation_emails' ), 10, 2 );
		add_filter( 'woocommerce_email_classes', array( &$this, 'bkap_init_emails' ) );
		
		// Email Actions
		$email_actions = array(
		    // New & Pending Confirmation
		    'bkap_pending_booking',
            'bkap_admin_new_booking',
		
		    // Confirmed
		    'bkap_booking_confirmed',
		
		    // Cancelled
		    'bkap_booking_pending-confirmation_to_cancelled',
		 );

		foreach ( $email_actions as $action ) {
		/*    if ( version_compare( WC_VERSION, '2.3', '<' ) ) {
		        add_action( $action, array( $GLOBALS['woocommerce'], 'send_transactional_email' ), 10, 10 );
		    } else {*/
		        add_action( $action, array( 'WC_Emails', 'send_transactional_email' ), 10, 10 );
		//    }
		}
		
	//	add_filter( 'woocommerce_email_attachments', array( $this, 'attach_ics_file' ), 10, 3 );
		
		add_filter( 'woocommerce_template_directory', array( $this, 'bkap_template_directory' ), 10, 2 );
		
	}
	
	function init_confirmation_emails( $order_id, $posted ) {
	    
	    if ( isset( $order_id ) && 0 != $order_id ) {
	        $order = new WC_order( $order_id );
	        $requires = bkap_common::bkap_order_requires_confirmation( $order );
	        
	        if ( $requires ) {
	            new WC_Emails();
	            do_action( 'bkap_pending_booking_notification', $order_id );
	        }
	    }
	}
	
	public function bkap_init_emails( $emails ) {
	    
	    if ( ! isset( $emails[ 'BKAP_Email_New_Booking' ] ) ) {
	        $emails[ 'BKAP_Email_New_Booking' ] = include( 'emails/class-bkap-email-new-booking.php' );
	    }
	
        if ( ! isset( $emails[ 'BKAP_Email_Booking_Confirmed' ] ) ) {
	        $emails[ 'BKAP_Email_Booking_Confirmed' ] = include( 'emails/class-bkap-email-booking-confirmed.php' );
	    }
	
	    if ( ! isset( $emails[ 'BKAP_Email_Booking_Cancelled' ] ) ) {
	        $emails[ 'BKAP_Email_Booking_Cancelled' ] = include( 'emails/class-bkap-email-booking-cancelled.php' );
	    }
	
	    return $emails;
	}
	
	public function bkap_template_directory( $directory, $template ) {
	    if ( false !== strpos( $template, '-booking' ) ) {
	        return 'woocommerce-booking';
	    }
	
	    return $directory;
	}
	
}// end of class
new BKAP_Email_Manager();
?>
