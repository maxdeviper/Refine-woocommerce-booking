<?php /**
 * Booking is confirmed
 *
 * An email sent to the user when a booking is confirmed.
 *
 * @class 		BKAP_Email_Booking_Confirmed
 * @extends 	WC_Email
 */
class BKAP_Email_Booking_Confirmed extends WC_Email {
    
    /**
     * Constructor
     */
    function __construct() {
    
        $this->id 				= 'bkap_booking_confirmed';
        $this->title 			= __( 'Booking Confirmed', 'woocommerce-booking' );
        $this->description		= __( 'Booking confirmed emails are sent when the status of a booking goes to confirmed.', 'woocommerce-booking' );
    
        $this->heading 			= __( 'Booking Confirmed', 'woocommerce-booking' );
        $this->subject      	= __( '[{blogname}] Your booking of "{product_title}" has been confirmed (Order {order_number}) - {order_date}', 'woocommerce-booking' );
    
        $this->template_html 	= 'emails/customer-booking-confirmed.php';
        $this->template_plain 	= 'emails/plain/customer-booking-confirmed.php';
    
        // Triggers for this email
        add_action( 'bkap_booking_confirmed_notification', array( $this, 'trigger' ) );
    
        // Call parent constructor
        parent::__construct();
    
        // Other settings
        $this->template_base = BKAP_BOOKINGS_TEMPLATE_PATH;
    }
    
    function trigger( $item_id ) {
        if ( $item_id ) {
            $this->object    = bkap_common::get_bkap_booking( $item_id );
            
            $key = array_search( '{product_title}', $this->find );
            if ( false !== $key ) {
                unset( $this->find[ $key ] );
                unset( $this->replace[ $key ] );
            }
            
            $this->find[]    = '{product_title}';
            $this->replace[] = $this->object->product_title;
            
            if ( $this->object->order_id ) {
                $this->find[]    = '{order_date}';
                $this->replace[] = date_i18n( wc_date_format(), strtotime( $this->object->order_date ) );
    
                $this->find[]    = '{order_number}';
                $this->replace[] = $this->object->order_id;
    
                $this->recipient = $this->object->billing_email;
            } else {
                $this->find[]    = '{order_date}';
                $this->replace[] = date_i18n( wc_date_format(), strtotime( $this->object->item_hidden_date ) );
    
                $this->find[]    = '{order_number}';
                $this->replace[] = __( 'N/A', 'woocommerce-booking' );
    
                if ( $this->object->customer_id && ( $customer = get_user_by( 'id', $this->object->customer_id ) ) ) {
                    $this->recipient = $customer->user_email;
                }
            }
        }
    
    /*    if ( $booking_id ) {
            $this->object    = get_wc_booking( $booking_id );
    
            if ( ! $this->object->get_order() ) {
                return;
            }
    
    
    
            $this->find[]    = '{product_title}';
            $this->replace[] = $this->object->get_product()->get_title();
    
            $this->find[]    = '{order_date}';
            $this->replace[] = date_i18n( wc_date_format(), strtotime( $this->object->get_order()->order_date ) );
    
            $this->find[]    = '{order_number}';
            $this->replace[] = $this->object->get_order()->get_order_number();
        }*/
    
        if ( ! $this->get_recipient() ) {
            return;
        }
    
        $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
    }
    
    function get_content_html() {
        ob_start();
        wc_get_template( $this->template_html, array(
        'booking' 		=> $this->object,
        'email_heading' => $this->get_heading(),
        'sent_to_admin' => false,
        'plain_text'    => false
        ), 'woocommerce-booking/', $this->template_base );
        return ob_get_clean();
    }
    
    function get_content_plain() {
        ob_start();
        wc_get_template( $this->template_plain, array(
        'booking' 		=> $this->object,
        'email_heading' => $this->get_heading(),
        'sent_to_admin' => false,
        'plain_text'    => true
        ), 'woocommerce-booking/', $this->template_base );
        return ob_get_clean();
    }
    
    function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' 		=> __( 'Enable/Disable', 'woocommerce-booking' ),
                'type' 			=> 'checkbox',
                'label' 		=> __( 'Enable this email notification', 'woocommerce-booking' ),
                'default' 		=> 'yes'
            ),
            'subject' => array(
                'title' 		=> __( 'Subject', 'woocommerce-booking' ),
                'type' 			=> 'text',
                'description' 	=> sprintf( __( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', 'woocommerce-booking' ), $this->subject ),
                'placeholder' 	=> '',
                'default' 		=> ''
            ),
            'heading' => array(
                'title' 		=> __( 'Email Heading', 'woocommerce-booking' ),
                'type' 			=> 'text',
                'description' 	=> sprintf( __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.', 'woocommerce-booking' ), $this->heading ),
                'placeholder' 	=> '',
                'default' 		=> ''
            ),
            'email_type' => array(
                'title' 		=> __( 'Email type', 'woocommerce-booking' ),
                'type' 			=> 'select',
                'description' 	=> __( 'Choose which format of email to send.', 'woocommerce-booking' ),
                'default' 		=> 'html',
                'class'			=> 'email_type',
                'options'		=> array(
                    'plain'		 	=> __( 'Plain text', 'woocommerce-booking' ),
                    'html' 			=> __( 'HTML', 'woocommerce-booking' ),
                    'multipart' 	=> __( 'Multipart', 'woocommerce-booking' ),
                )
            )
        );
    }
}
return new BKAP_Email_Booking_Confirmed();
?>