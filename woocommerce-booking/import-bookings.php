<?php 

class import_bookings {
    
    public function bkap_woocommerce_import_page() {
        
        global $wpdb;
        
        $plugin_path = plugin_dir_path( __FILE__ );
        include_once( $plugin_path . '/includes/class-import-bookings-table.php' );
        $import_bookings_table = new WAPBK_Import_Bookings_Table();
        $import_bookings_table->bkap_prepare_items();
        
        ?>
        <div class="wrap">
            <h2><?php _e( 'Imported Bookings', 'woocommerce-booking' ); ?></h2>
    		
    		<?php do_action( 'bkap_import_bookings_page_top' ); ?>
                    		
    		<form id="bkap-import-bookings" method="get" action="<?php echo admin_url( 'admin.php?page=woocommerce_import_page' ); ?>">
                <div id="display_notice"></div>
                <p id="bkap_add_order">
        			<a href="<?php echo esc_url( add_query_arg( 'post_type', 'shop_order', admin_url( 'post-new.php' ) ) ); ?>" class="button-secondary"><?php _e( 'Create Booking', 'woocommerce-booking' ); ?></a>
                </p>
    
    			<input type="hidden" name="page" value="woocommerce_import_page" />
    			
    			<?php $import_bookings_table->views() ?>
    					
    			<?php $import_bookings_table->advanced_filters(); ?>
    			<?php $import_bookings_table->display() ?>
    				
    		</form>
			<?php do_action( 'bkap_import_bookings_page_bottom' ); ?>
		</div>
		
		<script type="text/javascript">
		jQuery( document ).on( "click", "#discard_event", function () {
			var y = confirm( "Are you sure you want to discard this event?" );

			if ( true == y ) {
				var passed_id = this.name;
                var exploded_id = passed_id.split('_');

                var ID = exploded_id[2];
				var data = {
						ID: ID,
						action: 'bkap_discard_imported_event'
				};

				jQuery.post('<?php echo get_admin_url();?>admin-ajax.php', data, function( response ) {
					window.location.replace("<?php echo admin_url( 'admin.php?page=woocommerce_import_page' ); ?>");
                });
			}
		});

		jQuery( document ).on( "click", "#map_event", function () {
			var passed_id = this.name;
			var exploded_id = passed_id.split( '_' );

			var ID = exploded_id[2];
			var selectID = "import_event_" + ID;

			var product_id_selected = document.getElementById( selectID ).value;

			var data = {
					ID: ID,
					product_id: product_id_selected,
					action: 'bkap_map_imported_event'
			};

			jQuery.post('<?php echo get_admin_url();?>admin-ajax.php', data, function( response ) {
				if ( '' == response ) {
					window.location.replace("<?php echo admin_url( 'admin.php?page=woocommerce_import_page' ); ?>");
				} else {
					jQuery( "#display_notice" ).html( response );
				}
            });
			
		});
		</script>
			<?php             	
    }
    
    public function bkap_discard_imported_event() {
        $import_id = $_POST[ 'ID' ];
        $option_name = 'bkap_imported_events_' . $import_id;
        delete_option( $option_name );
    }
    
    public function bkap_map_imported_event() {
        
        // default notices to blanks
        $notice = '';
        global $date_formats;
        
        // default  variables
        $backdated_event = 0; // it's a future event
        $validation_check = 0; // product is available for desired quantity
        
        $global_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
        
        $date_format_to_display = $global_settings->booking_date_format;
        $time_format_to_display = $global_settings->booking_time_format;
        
        $import_id = $_POST[ 'ID' ];
        $option_name = 'bkap_imported_events_' . $import_id;
        
        $imported_event_details = json_decode( get_option( $option_name ) );
        
        $product_id = $_POST[ 'product_id' ];
        
        // add the booking details
        
        if ( !current_time( 'timestamp' ) ) {
            $tdif = 0;
        } else {
            $tdif = current_time( 'timestamp' ) - time();
        }
        
        // default the variables
        $booking_date_to_display = '';
        $checkout_date_to_display = '';
        $booking_from_time = '';
        $booking_to_time = '';
        
        if( $imported_event_details->end != "" && $imported_event_details->start != "" ) {
            $event_start = $imported_event_details->start + $tdif;
            $event_end = $imported_event_details->end + $tdif;

            $booking_date_to_display = date( $date_formats[ $date_format_to_display ], $event_start );
            $checkout_date_to_display = date( $date_formats[ $date_format_to_display ], $event_end );
                     
            if( $event_end >= current_time( 'timestamp' ) && $event_start >= current_time( 'timestamp' ) ) {
                        
                if ( $time_format_to_display == '12' ) {
                    $booking_from_time = date( "h:i A", $event_start );
                    $booking_to_time = date( "h:i A", $event_end );
                } else {
                    $booking_from_time = date( "H:i", $event_start );
                    $booking_to_time = date( "H:i", $event_end );
                }
                        
            } else {
                $backdated_event = 1;
            }
                    
        } else if( $imported_event_details->start != "" && $imported_event_details->end == "" ) {
            
            $event_start = $imported_event_details->start + $tdif;
            $booking_date_to_display = date( $date_formats[ $date_format_to_display ], $event_start );
             
            if( $event_start >= current_time( 'timestamp' ) ) {
            
                if ( $time_format_to_display == '12' ) {
                    $booking_from_time = date( "h:i A", $event_start );
                } else {
                    $booking_from_time = date( "H:i", $event_start );
                }
            
            } else {
                $backdated_event = 1;
            }
            
        }
                
        $quantity = 1;
        
        /* Validate the booking details. Check if the product is available in the desired quantity for the given date and time */
        $_product = wc_get_product( $product_id );
        
        $variationsArray = array();
        
        // if the product ID has a parent post Id, then it means it's a variable product
        $variation_id = 0;
        if ( isset( $_product->variation_id ) && 0 != $_product->variation_id ) {
            $parent_id = $_product->parent->id;
            $variation_id = $product_id;
            
            $parent_product = wc_get_product( $parent_id );
            $variations_list = $parent_product->get_available_variations();
            
            foreach ( $variations_list as $variation ) {
                if ( $variation[ 'variation_id' ] == $product_id ) {
                    $variationsArray[ 'variation' ] = $variation[ 'attributes' ];
                }
            }
            
            // Product Attributes - Booking Settings
            $attribute_booking_data = get_post_meta( $parent_id, '_bkap_attribute_settings', true );
            
            if ( is_array( $attribute_booking_data ) && count( $attribute_booking_data ) > 0 ) {
                $quantity = 0;           
                foreach ( $attribute_booking_data as $attr_name => $attr_settings ) {
                    
                    if ( isset( $attr_settings[ 'booking_lockout_as_value' ] ) && 'on' == $attr_settings[ 'booking_lockout_as_value' ] ) {
                        if ( in_array( $attr_name, $variationsArray[ 'variation' ] ) ) {
                            $quantity += $variationsArray[ 'variation' ][ $attr_name ];
                        }
                    }
                }
            }
            
        } else {
            $parent_id = $product_id;
        }
        
        $hidden_date = date( 'Y-m-d', strtotime( $booking_date_to_display ) );
        
        if ( $booking_from_time != '' && $booking_to_time != '' && $booking_from_time != $booking_to_time ) {
            
            $from_hrs = date( 'G:i', strtotime( $booking_from_time ) );
            $to_hrs = date( 'G:i', strtotime( $booking_to_time ) );
            
            $availability = bkap_booking_process::bkap_get_time_availability( $parent_id, $hidden_date, $from_hrs, $to_hrs, 'YES' );
           
            if ( $availability > 0 || $availability == 'Unlimited' ) {
                if ( $availability > 0 ) {
                    $new_availability = $availability - $quantity;
                    if ( $new_availability < 0 ) {
                        $validation_check = 1; // product is unavailable for the desired quantity
                    }
                }
            } else {
                $validation_check = 1; // product is not available
            }
            
        } else {
            
            $hidden_checkout_date = '';
            if ( $checkout_date_to_display != '' ) {
                $hidden_checkout_date = date( 'Y-m-d', strtotime( $checkout_date_to_display ) );
            }
            $bookings_placed = '';
            $attr_bookings_placed = '';
            $availability = bkap_booking_process::bkap_get_date_availability( $parent_id, $variation_id, $hidden_date, $booking_date_to_display, $bookings_placed, $attr_bookings_placed, false );
            
            if ( $availability > 0 || $availability == 'Unlimited' ) {
                 if ( $availability > 0 ) {
                     $new_availability = $availability - $quantity;
                     if ( $new_availability < 0 ) {
                         $validation_check = 1; // product is unavailable for the desired quantity
                     }
                 }
            } else {
                $validation_check = 1; // product is not available
            }
        }
        
        if ( 0 == $backdated_event && 0 == $validation_check ) {
            
            // create an order
            
            $args = array( 'status' => 'processing',
                'customer_note' => $imported_event_details->summary,
                'created_via' => 'GCal' );
            
            $order = wc_create_order( $args );
            
            $order_id = $order->id;
            
            if ( isset( $imported_event_details->summary ) && $imported_event_details->summary != '' ) {
                $order->add_order_note( $imported_event_details->summary );
            }
            if ( isset( $imported_event_details->description ) && $imported_event_details->description != '' ) {
                $order->add_order_note( $imported_event_details->description );
            }
            $order->add_order_note( 'Reserved by GCal' );
            
            // add the product to the order 
        
            $item_id = $order->add_product( $_product, $quantity, $variationsArray );
            
            // insert records to ensure we're aware the item has been imported
            $event_items = get_option( 'bkap_event_item_ids' );
            if( $event_items == '' || $event_items == '{}' || $event_items == '[]' || $event_items == 'null' ) {
                $event_items = array();
            }
            array_push( $event_items, $item_id );
            update_option( 'bkap_event_item_ids', $event_items );
            
            // calculate order totals
            $order->calculate_totals();
            
            // create the booking details array
            $booking[ 'date' ] = $booking_date_to_display;
            $booking[ 'hidden_date' ] = date( 'j-n-Y', strtotime( $booking_date_to_display ) );
            
            $hidden_checkout_date = '';
            if ( isset( $checkout_date_to_display ) && '' != $checkout_date_to_display ) {
                $hidden_checkout_date = date( 'j-n-Y', strtotime( $checkout_date_to_display ) );
            } 
            $booking[ 'date_checkout' ] = $checkout_date_to_display;
            $booking[ 'hidden_date_checkout' ] = $hidden_checkout_date;
            
            if ( isset( $booking_from_time ) && '' != $booking_from_time && $booking_from_time != $booking_to_time ) {
                $time_slot = $booking_from_time;
                if ( isset( $booking_to_time ) && '' != $booking_to_time ) {
                    $time_slot .= ' - ' . $booking_to_time;
                }
                $booking[ 'time_slot' ] = $time_slot;
            }
            
            if ( isset( $parent_id ) && 0 != $parent_id ) {
                $meta_update_id = $parent_id;
            } else {
                $meta_update_id = $product_id;
            }
            bkap_common::bkap_update_order_item_meta( $item_id, $meta_update_id, $booking, true );
        
            // adjust lockout
            $product = wc_get_product( $meta_update_id );
            $parent_id = $product->get_parent(); // for grouped products
            
            $details = bkap_checkout::bkap_update_lockout( $order_id, $meta_update_id, $parent_id, $quantity, $booking );
            
            // update the global time slot lockout
            if ( isset( $booking[ 'time_slot' ] ) && '' != $booking[ 'time_slot' ] ) {
                bkap_checkout::bkap_update_global_lockout( $meta_update_id, $quantity, $details, $booking );
            }
        
            // finally move the imported event details to item meta and delete the record from wp_options table
            $archive_events = 0; // 0 - archive (move from wp_options to item meta), 1 - delete from wp_options and don't save as item meta
            
            if ( 0 == $archive_events ) {
                
                // save as item meta for future reference
                wc_add_order_item_meta( $item_id, '_gcal_event_reference', $imported_event_details );
                // delete the data frm wp_options
                delete_option( $option_name );
                
            } else if ( 1 == $archive_events ) {
                
                // delete the data from wp_options
                delete_option( $option_name );
            } 
        }
        
        if ( 1 == $backdated_event ) {
            $message = __( 'Back Dated Events cannot be imported. Please discard them.', 'woocommerce-booking' );
            $notice = '<div class="error"><p>' . sprintf( __( '%s', 'woocommerce-booking' ), $message ) . '</p></div>';
        }
        
        if ( 1 == $validation_check ) {
            
            $message = __( 'The product is not available for the given date for the desired quantity.', 'woocommerce-booking' );

            $notice = '<div class="error"><p>' . sprintf( __( '%s', 'woocommerce-booking' ), $message ) . '</p></div>';
        }
        echo $notice;
        die();
    }
    
} // end of class