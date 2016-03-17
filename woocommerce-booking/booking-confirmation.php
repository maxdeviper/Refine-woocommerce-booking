<?php
if ( ! class_exists( 'bkap_booking_confirmation' ) ) {
    class bkap_booking_confirmation {
    
        public function __construct() {
            
            // Add a function to include required files
            add_action( 'init', array( &$this, 'bkap_includes' ), 99 );
            
            // add checkbox in admin
            add_action( 'bkap_after_product_holidays', array( &$this, 'confirmation_checkbox' ), 10, 1 );
            
            // save the checkbox value in post meta record
            add_filter( 'bkap_save_product_settings', array( &$this, 'save_product_settings' ), 10, 2 );
            
            // change the button text
            add_filter( 'woocommerce_product_single_add_to_cart_text', array( &$this, 'change_button_text' ) );
            
            // add to cart validations
            
            // Load payment gateway name.
            add_filter( 'woocommerce_payment_gateways', array( &$this, 'bkap_include_gateway' ) );
            
            // Check if Cart contains any product that requires confirmation
            add_filter( 'woocommerce_cart_needs_payment', array( &$this, 'bkap_cart_requires_confirmation' ), 10, 2 );
            
            // change the payment gateway at Checkout
            add_filter( 'woocommerce_available_payment_gateways', array( &$this, 'bkap_remove_payment_methods' ), 10, 1 );
            
            // Prevent pending being cancelled
            add_filter( 'woocommerce_cancel_unpaid_order', array( $this, 'bkap_prevent_cancel' ), 10, 2 );
            
            // Control the my orders actions.
            add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'bkap_my_orders_actions' ), 10, 2 );
            
            // Add the View Bookings link in Woo->Orders edit orders page
            add_action( 'woocommerce_admin_order_item_headers', array( $this, 'bkap_link_header' ) );
            add_action( 'woocommerce_admin_order_item_values', array( $this, 'bkap_link' ), 10, 3 );
            
            // Re-direct to the View Booking page
            add_action( 'admin_init', array( &$this, 'load_view_booking_page' ) );
            
            // Ajax calls
            add_action( 'init', array( &$this, 'bkap_confirmations_ajax' ) );
            
            // Cart Validations
            add_filter( 'bkap_validate_cart_products', array( &$this, 'bkap_validate_conflicting_products' ), 10, 2 );
            
            // Once the payment is completed, order status changes to any of the below, fire these hooks to ensure the booking status is also updated.
            
            add_action( 'woocommerce_order_status_processing' , array( &$this, 'bkap_update_booking_status' ), 10, 1 );
            add_action( 'woocommerce_order_status_on-hold' , array( &$this, 'bkap_update_booking_status' ), 10, 1 );
            add_action( 'woocommerce_order_status_completed' , array( &$this, 'bkap_update_booking_status' ), 10, 1 );
            	
            // Remove the booking from the order when it's cancelled
            // Happens only if the booking requires confirmation and the order contains multiple bookings
            // which require confirmation
            add_action( 'bkap_booking_pending-confirmation_to_cancelled', array( $this, 'bkap_remove_cancelled_booking' ) );
            
            
        }
        
        /**
         * File Includes
         */
        function bkap_includes() {
            include( 'class-bkap-gateway.php' );
            include( 'class-approve-booking.php' );
            
        }
        
        /**
         * Ajax Calls
         */
        function bkap_confirmations_ajax() {
            // only logged in users can access the admin side and approve bookings
            add_action( 'wp_ajax_bkap_save_booking_status', array( &$this, 'bkap_save_booking_status' ) );
        }
        
        /**
         * Add a Requires Confirmation checkbox in the Booking meta box
         * 
         * @param int $product_id
         */
        function confirmation_checkbox( $product_id ) {
            
            $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
            ?>
       
            <tr>
                <th>
                    <label for="bkap_requires_confirmation"><?php _e( 'Requires Confirmation?', 'woocommerce-booking' ); ?></label>
                </th>
                <td>
                    <?php 
                    $date_show = '';
                    if( isset( $booking_settings[ 'booking_confirmation' ] ) && 'on' == $booking_settings[ 'booking_confirmation' ] ) {
                        $requires_confirmation = 'checked';
                    } else {
                        $requires_confirmation = '';
                    }
                    ?>
                    <input type="checkbox" name="bkap_requires_confirmation" id="bkap_requires_confirmation" <?php echo $requires_confirmation; ?>>
                    <img style="margin-left:361px;"  class="help_tip" width="16" height="16" data-tip="<?php _e( 'Check this box if the booking requires admin approval/confirmation. Payment will not be taken during checkout', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                </td>
            </tr>
            <?php 
        }
        
        /**
         * Save the Requires Confirmation setting in Booking meta box
         * 
         * @param array $booking_settings
         * @param int $product_id
         * @return array $booking_settings
         */
        function save_product_settings( $booking_settings, $product_id ) {
            
            $booking_settings[ 'booking_confirmation' ] = '';
            
            if( isset( $_POST[ 'bkap_requires_confirmation' ] ) ) {
                $booking_settings[ 'booking_confirmation' ] = $_POST[ 'bkap_requires_confirmation' ];
            }
            
            return $booking_settings;
        }
        
        /**
         * Modify the Add to cart button text for products that require confirmations
         * 
         */
        function change_button_text() {
            global $post;
            // Product ID
            $product_id = $post->ID;
            
            $requires_confirmation = bkap_common::bkap_product_requires_confirmation( $product_id );
            
            if( $requires_confirmation ) {
                return __( 'Check Availability', 'woocommerce-booking' );
            } else {
                return __( 'Add to Cart', 'woocommerce-booking' );
            }
            
        }
        
        /**
         * Add Booking Payment Gateway in WooCommerce->Settings->Checkout
         * 
         * @param unknown $gateways
         * @return unknown
         */
        function bkap_include_gateway( $gateways ) {
            
            $gateways[] = 'BKAP_Payment_Gateway';
            
            return $gateways;
            
        }
        
        /**
         * Return true if the cart contains a product that requires confirmation
         * 
         * @param int $needs_payment
         * @param object $cart
         * @return boolean
         */
        function bkap_cart_requires_confirmation( $needs_payment, $cart ) {
            
            if ( ! $needs_payment ) {
                foreach ( $cart->cart_contents as $cart_item ) {
                    $requires_confirmation = bkap_common::bkap_product_requires_confirmation( $cart_item['product_id'] );
                    
                    if( $requires_confirmation ) {
                        $needs_payment = true;
                        break;
                    }
                }
            }
            
            return $needs_payment;
            
        }  

        /**
         * Modify Payment Gateways
         * 
         * Remove the existing payment gateways and add the Bookign payment gateway
         * when the Cart contains a product that requires confirmation.
         * 
         * @param array $available_gateways
         * @return multitype:BKAP_Payment_Gateway
         */
        function bkap_remove_payment_methods( $available_gateways ) {
        
            $cart_requires_confirmation = bkap_common::bkap_cart_requires_confirmation();
            
            if ( $cart_requires_confirmation ) {
                unset( $available_gateways );
        
                $available_gateways = array();
                $available_gateways['bkap-booking-gateway'] = new BKAP_Payment_Gateway();
            }
        
            return $available_gateways;
        }

        /**
         * Prevent Order Cancellation
         * 
         * Prevent WooCommerce from cancelling an order if the order contains
         * an item that is awaiting booking confirmation.
         * 
         * @param boolean $return
         * @param object $order
         * @return boolean|unknown
         */
        function bkap_prevent_cancel( $return, $order ) {
            if ( '1' === get_post_meta( $order->id, '_bkap_pending_confirmation', true ) ) {
                return false;
            }
        
            return $return;
        }
        
        /**
         * Hide the Pay button in My Accounts
         * 
         * Hide the Pay button in My Accounts fr orders that contain
         * an item that's still awaiting booking confirmation.
         * 
         * @param array $actions
         * @param object $order
         * @return array $actions
         */
        function bkap_my_orders_actions( $actions, $order ) {
            global $wpdb;
        
            if ( $order->has_status( 'pending' ) && 'bkap-booking-gateway' === $order->payment_method ) {
                
                $status = array();
                foreach ( $order->get_items() as $order_item_id => $item ) {
                    if ( 'line_item' == $item['type'] ) {
                        
                        $_status = $item[ 'wapbk_booking_status' ];
             			$status[] = $_status;
                 
                    }
                }
        
    			if ( in_array( 'pending-confirmation', $status ) && isset( $actions['pay'] ) ) {
    				unset( $actions['pay'] );
    			} else if ( in_array( 'cancelled', $status ) && isset( $actions['pay'] ) ) {
    			    unset( $actions['pay'] );
    			}
    		}
        
    		return $actions;
    	}
    	
    	/**
    	 * Create a column in WooCommerce->Orders 
    	 * Edit Orders page for each item
    	 */
        function bkap_link_header() {
	       ?><th>&nbsp;</th><?php
		}
		
		/**
		 * Display View Booking Link
		 * 
		 *  Add the View Booking Link for a given item in 
		 *  WooCommerce->orders Edit Orders
		 *  
		 * @param object $_product
		 * @param array $item
		 * @param int $item_id
		 */
		function bkap_link( $_product, $item, $item_id ) {
		    
		    global $wpdb;
		    
		    if ( isset( $_product ) ) {
    		    $product_id = $_product->id;
    		    
    		    if ( isset( $item[ 'wapbk_booking_status' ] ) ) { 
                    $_status = $item[ 'wapbk_booking_status' ];
    		    }
    		    
    		    if ( ( isset( $item[ 'type' ] ) && 'line_item' == $item[ 'type' ] ) && ( ( isset( $_status ) && '' != $_status ) || ( ! isset( $_status ) ) ) ) {
    		        $args = array( 'page' => 'woocommerce_history_page', 'item_id' => $item_id );
    		        ?>
    		        <td>
                        <a href="<?php echo esc_url_raw( add_query_arg( $args, admin_url() . 'admin.php' ) ); ?>"><?php _e( 'View Booking', 'woocommerce-booking' ); ?></a>
    		        </td>
    		        <?php 
    		    } else {
    		        echo '<td></td>';
    		    }
		    } else {
		        echo '<td></td>';
		    }
		    
		}
		
		/**
		 * Re-direct to the Edit Booking page
		 * 
		 * This funtion re-directs to the Edit Booking page when the
		 * View Boking link on WooCommerce->Orders Edit order page is clicked
		 * for a given item.
		 */
		function load_view_booking_page() {
		    $url = '';
		    
		    if ( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'woocommerce_history_page' ) {
		        if ( isset( $_GET[ 'item_id' ] ) && $_GET[ 'item_id' ] != 0 ) {
		            
		            ob_start();
		            $templatefilename = 'approve-booking.php';
		            if ( file_exists( dirname( __FILE__ ) . '/' . $templatefilename ) ) {
		                $template = dirname( __FILE__ ) . '/' . $templatefilename;
		                include( $template );
		            }
		            $content = ob_get_contents();
		            ob_end_clean();
		            
		            $args = array( 'slug'    => 'edit-booking',
                                    'title'   => 'Edit Booking',
                                    'content' => $content );
		            $pg = new bkap_approve_booking ( $args );
		        }
		    }
		}
		
		/**
		 * Update Item status
		 * 
		 * This function updates the item booking status. 
		 * It is called from the Edit Booking page Save button click 
		 */
		function bkap_save_booking_status() {
		    global $wpdb;
		    
		    $item_id = $_POST[ 'item_id' ];
		    $_status = $_POST[ 'status' ];
		    wc_update_order_item_meta( $item_id, '_wapbk_booking_status', $_status );
		    
		    // if the booking has been denied, release the bookings for re-allotment
		    
		    if ( 'cancelled' == $_status ) { 
		    
		        $array = array();
		        
		        // get the order ID
		        $order_id = 0;
		        $query_order_id = "SELECT order_id FROM `". $wpdb->prefix."woocommerce_order_items`
                                    WHERE order_item_id = %d";
		        $get_order_id = $wpdb->get_results( $wpdb->prepare( $query_order_id, $item_id ) );
		        
		        if ( isset( $get_order_id ) && is_array( $get_order_id ) && count( $get_order_id ) > 0 ) {
		            $order_id = $get_order_id[0]->order_id;
		        }
		        
		        //create order object
		        $order = new WC_Order( $order_id );
		        
		        // order details
		        $order_data = $order->get_items();
		        
		        $item_value = $order_data[ $item_id ];
		        
		        $select_query =   "SELECT booking_id FROM `".$wpdb->prefix."booking_order_history`
						  WHERE order_id= %d";
		        $results      =   $wpdb->get_results ( $wpdb->prepare( $select_query, $order_id ) );
		        
		        foreach( $results as $k => $v ) {
		            $b[]                 =   $v->booking_id;
		            $select_query_post   =   "SELECT post_id,id FROM `".$wpdb->prefix."booking_history`
								     WHERE id= %d";
		            $results_post[]      =   $wpdb->get_results( $wpdb->prepare( $select_query_post, $v->booking_id ) );
		            	
		        }
		        
		        if ( isset( $results_post ) && count( $results_post ) > 0 && $results_post != false ) {
		            	
		            foreach( $results_post as $k => $v ) {
		                if ( isset( $v[0]->id ) ) $a[ $v[0]->post_id ][] = $v[0]->id;
		            }
		        }
		        
		        bkap_cancel_order::bkap_reallot_item( $item_value, $array, $a, $b, $order_id );
		    }

		    // create an instance of the WC_Emails class , so emails are sent out to customers
            new WC_Emails();
            if ( 'cancelled' == $_status ) {
		        do_action( 'bkap_booking_pending-confirmation_to_cancelled_notification', $item_id );
		        do_action( 'bkap_booking_pending-confirmation_to_cancelled', $item_id );
		        
            } else if ( 'confirmed' == $_status ) {// if booking has been approved, send email to user
		        do_action( 'bkap_booking_confirmed_notification', $item_id );
		    }
		    die();
		}
		
		/**
		 * Validate bookable products 
		 * 
		 * This function displays a notice and empties the cart if the cart contains
		 * any products that conflict with the new product being added. 
		 * 
		 * @param array $_POST
		 * @param int $product_id
		 * @return string
		 */
		function bkap_validate_conflicting_products( $POST, $product_id ) {
		    
		    $quantity_check_pass = 'yes';
		    // check if the product being added requires confirmation
		    $product_requires_confirmation = bkap_common::bkap_product_requires_confirmation( $product_id );
		    
		    // check if the cart contains a product that requires confirmation
		    $cart_requires_confirmation = bkap_common::bkap_cart_requires_confirmation();
		    
		    $validation_status = 'warn_modify_yes';
		    
		    switch ( $validation_status ) {
		        case 'warn_modify_yes':
		            $conflict = 'NO';
		            
		            if ( count( WC()->cart->cart_contents ) > 0 ) {
		            // if product requires confirmation and cart contains product that does not
    		            if ( $product_requires_confirmation && ! $cart_requires_confirmation ) {
    		                $conflict = 'YES';
    		            }
    		            // if product does not need confirmation and cart contains a product that does
    		            if ( ! $product_requires_confirmation && $cart_requires_confirmation ) {
    		                $conflict = 'YES';
    		            }
    		            // if conflict
    		            if ( 'YES' == $conflict ) {
                            // remove existing products
    		                WC()->cart->empty_cart();
                            
                            // add a notice
    		                $message = bkap_get_book_t( 'book.conflicting-products' );
    		                wc_add_notice( __( $message, 'woocommerce-booking' ), $notice_type = 'notice' );
    		            }
                    }
		            break;
		    }
		    
		    return $quantity_check_pass; 
		}

		/**
		 * Update Booking status to paid
		 * 
		 * Updates the Booking status to paid to ensure they do not remain
		 * in the Unpaid section in Booking->View Bookings
		 * 
		 * @param int $order_id
		 */
		function bkap_update_booking_status ( $order_id ) {
		    
		    $order_obj    =   new WC_order( $order_id );
    		$order_items  =   $order_obj->get_items();
    		foreach( $order_items as $item_key => $item_value ) {

    		    $booking_status = wc_get_order_item_meta( $item_key, '_wapbk_booking_status' );
    		    
    		    if ( isset( $booking_status ) && 'confirmed' == $booking_status ) {
    		        wc_update_order_item_meta( $item_key, '_wapbk_booking_status', 'paid' );
    		    }
    		}
		}
		
		/**
		 * Remove an item frm the other if it has been cancelled by the admin.
		 * 
		 * @param int $item_id
		 */
		function bkap_remove_cancelled_booking( $item_id ) {
		    global $wpdb;
		    
		    $booking  = bkap_common::get_bkap_booking( $item_id );
		    $order = new WC_order( $booking->order_id );
		    $bookings = array();
		    
		    if ( ! empty ( $order ) && is_array( $order->get_items() ) ) {
		        foreach ( $order->get_items() as $order_item_id => $item ) {
		            if ( $order_item_id == $item_id ) {
		                wc_delete_order_item( $order_item_id );
		                $order->calculate_totals();
		                $order->add_order_note( sprintf( __( 'The product %s has been removed from the order because the booking cannot be confirmed.', 'woocommerce-booking' ), $item['name'] ), true );
		            }
		        }
		    }
		}
    } 
}
$bkap_booking_confirmation = new bkap_booking_confirmation(); 
?>