<?php
    
class view_bookings{
    
	public function __construct() {
		add_action( 'admin_init', array( &$this, 'bkap_data_export' ) );
		
	}
	
   /**
    * This function adds a page on View Bookings submenu which displays the orders with the booking details. 
    * The orders which are cancelled or refunded are not displayed.
    */
   public static function bkap_woocommerce_history_page() {

        if ( isset( $_GET['action'] ) ) {
	        $action = $_GET['action'];
        } else {
            $action = '';
        }
        
        if ( $action == 'history' || $action == '' ) {
            $active_settings = "nav-tab-active";
        }

        if ( $action == 'history' || $action == '' ) {
        	global $wpdb;
        	
        	include_once( 'class-view-bookings-table.php' );
        	$bookings_table = new WAPBK_View_Bookings_Table();
        	$bookings_table->bkap_prepare_items();
        	if ( !isset( $_GET[ 'item_id' ] ) || ( isset( $_GET[ 'item_id' ] ) && $_GET[ 'item_id' ] == 0 ) ) {
        	?>
        	<div class="wrap">
        	<h2><?php _e( 'All Bookings', 'woocommerce-booking' ); ?></h2>
        		<?php do_action( 'bkap_bookings_page_top' ); ?>
        		
        		<form id="bkap-view-bookings" method="get" action="<?php echo admin_url( 'admin.php?page=woocommerce_history_page' ); ?>">
        			<p id="bkap_add_order">
						<a href="<?php echo esc_url( add_query_arg( 'post_type', 'shop_order', admin_url( 'post-new.php' ) ) ); ?>" class="button-secondary"><?php _e( 'Create Booking', 'woocommerce-booking' ); ?></a>
        			     <a href="
						    <?php echo isset ( $_GET['booking_view'] ) && $_GET['booking_view'] == "booking_calender" ? admin_url( 'admin.php?page=woocommerce_history_page' ) : esc_url( add_query_arg( 'booking_view', 'booking_calender' ) ) ;  ?>" 
    						class="button-secondary">
    						<?php isset ( $_GET['booking_view'] ) && $_GET['booking_view'] == "booking_calender" ? _e( 'View Booking Listing', 'woocommerce-booking' )  : _e ( 'Calendar View', 'woocommerce-booking' );  ?>
						</a>
        			<?php 
        			if ( !isset( $_GET['booking_view'] ) ) {?>
        			    <a href="<?php echo esc_url( add_query_arg( 'download', 'data.print' ) ); ?>" style="float:right;" class="button-secondary"><?php _e( 'Print', 'woocommerce-booking' ); ?></a>
						<a href="<?php echo esc_url( add_query_arg( 'download', 'data.csv' ) ); ?>" style="float:right;" class="button-secondary"><?php _e( 'CSV', 'woocommerce-booking' ); ?></a>
						<?php }?>
					</p>
		
					<input type="hidden" name="page" value="woocommerce_history_page" />

					<?php if ( isset($_GET['booking_view'] ) && ( $_GET['booking_view'] == 'booking_calender' ) ) {
                        ?>
                            <h2><?php _e( 'Calendar View', 'woocommerce-booking' ); ?></h2>
                            <div id='calendar'></div>
                        <?php }else{
                     ?>
					<?php $bookings_table->views() ?>
					
					<?php $bookings_table->advanced_filters(); ?>
					<?php $bookings_table->display() ?>
				    <?php } ?>
				
					
        			</form>
				<?php do_action( 'bkap_bookings_page_bottom' ); ?>
        	</div>
        	<?php 
        	}
        }
   }
   
    
   	public function bkap_data_export() {	
		global $wpdb;
		
		$tab_status = '';
		
		if ( isset( $_GET['status'] ) ) {
			$tab_status = $_GET['status'];
		}
		
		if ( isset( $_GET['download'] ) && ( $_GET['download'] == 'data.csv' ) ) {
			$report  = view_bookings::generate_data( $tab_status );
	   		$csv     = view_bookings::generate_csv( $report );
	   		
	   		header("Content-type: application/x-msdownload");
	        header("Content-Disposition: attachment; filename=data.csv");
	        header("Pragma: no-cache");
	        header("Expires: 0");
	        
	   		echo $csv;
	   		exit;
		}else if( isset( $_GET['download'] ) && ( $_GET['download'] == 'data.print' ) ) {
			$report              = view_bookings::generate_data($tab_status);
			
			$print_data_columns  = "
                					<tr>
                						<th style='border:1px solid black;padding:5px;'>".__( 'Order ID', 'woocommerce-booking' )."</th>
                						<th style='border:1px solid black;padding:5px;'>".__( 'Customer Name', 'woocommerce-booking' )."</th>
                						<th style='border:1px solid black;padding:5px;'>".__( 'Product Name', 'woocommerce-booking' )."</th>
                						<th style='border:1px solid black;padding:5px;'>".__( 'Check-in Date', 'woocommerce-booking' )."</th>
                						<th style='border:1px solid black;padding:5px;'>".__( 'Check-out Date', 'woocommerce-booking' )."</th>
                						<th style='border:1px solid black;padding:5px;'>".__( 'Booking Time', 'woocommerce-booking' )."</th>
                						<th style='border:1px solid black;padding:5px;'>".__( 'Quantity', 'woocommerce-booking' )."</th>
                						<th style='border:1px solid black;padding:5px;'>".__( 'Amount', 'woocommerce-booking' )."</th>
                						<th style='border:1px solid black;padding:5px;'>".__( 'Order Date', 'woocommerce-booking' )."</th>
                					</tr>";
			$print_data_row_data =  '';
			
			foreach ( $report as $key => $value ) {
			    // Currency Symbol
			    // The order currency is fetched to ensure the correct currency is displayed if the site uses multi-currencies
			    $the_order          = wc_get_order( $value->order_id );
			    $currency           = $the_order->get_order_currency();
			    $currency_symbol    = get_woocommerce_currency_symbol( $currency );
			     
				$print_data_row_data .= "<tr>
        								<td style='border:1px solid black;padding:5px;'>".$value->order_id."</td>
        								<td style='border:1px solid black;padding:5px;'>".$value->customer_name."</td>
        								<td style='border:1px solid black;padding:5px;'>".$value->product_name."</td>
        								<td style='border:1px solid black;padding:5px;'>".$value->checkin_date."</td>
        								<td style='border:1px solid black;padding:5px;'>".$value->checkout_date."</td>
        								<td style='border:1px solid black;padding:5px;'>".$value->time."</td>
        								<td style='border:1px solid black;padding:5px;'>".$value->quantity."</td>
        								<td style='border:1px solid black;padding:5px;'>".$currency_symbol . $value->amount."</td>
        								<td style='border:1px solid black;padding:5px;'>".$value->order_date."</td>
        								</tr>";
			}
			$print_data_columns  =   apply_filters( 'bkap_view_bookings_print_columns', $print_data_columns );
			$print_data_row_data =   apply_filters( 'bkap_view_bookings_print_rows', $print_data_row_data, $report );
			$print_data          =   "<table style='border:1px solid black;border-collapse:collapse;'>" . $print_data_columns . $print_data_row_data . "</table>";
			echo $print_data;
			?>
			
			<?php 
			exit;
		} 
   	}
   	
   	function generate_data($tab_status) {
   		global $wpdb;
   		
   		$results = array();
		$current_time = current_time( 'timestamp' );
		$current_date = date( "Y-m-d", $current_time );
		
		if ( $tab_status == 'future' ) {
		    
			$booking_query   = "SELECT *,a2.order_id FROM `".$wpdb->prefix."booking_history` AS a1,`".$wpdb->prefix."booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.start_date >= '".$current_date."' ORDER BY a2.order_id DESC";
			$query_results         = $wpdb->get_results( $booking_query );
		}else if ( $tab_status == 'today_checkin' ) {
		    
			$booking_query   = "SELECT *,a2.order_id FROM `".$wpdb->prefix."booking_history` AS a1,`".$wpdb->prefix."booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.start_date = '".$current_date."' ORDER BY a2.order_id DESC";
			$query_results         = $wpdb->get_results( $booking_query );
		}else if ( $tab_status == 'today_checkout' ) {
		    
			$booking_query   = "SELECT *,a2.order_id FROM `".$wpdb->prefix."booking_history` AS a1,`".$wpdb->prefix."booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.end_date = '".$current_date."' ORDER BY a2.order_id DESC";
			$query_results         = $wpdb->get_results( $booking_query );
		} else {
		    
			$booking_query   = "SELECT *,a2.order_id FROM `".$wpdb->prefix."booking_history` AS a1,`".$wpdb->prefix."booking_order_history` AS a2 WHERE a1.id = a2.booking_id ORDER BY a2.order_id DESC";
			$query_results         = $wpdb->get_results( $booking_query );
		}
		
		$results = array();
		
		$_status = $tab_status;
		
		switch ( $_status ) {
		    case 'pending_confirmation':
		        $_status = 'pending-confirmation';
		        break;
		    case 'unpaid':
		        $_status = 'confirmed';
		        break;
		    case 'future':
		        $_status = 'paid';
		        break;
		    case 'today_checkout':
		        $_status = 'paid';
		        break;
		    case 'today_checkin':
		        $_status = 'paid';
		        break;
		    default:
		        $_status = '';
		        break;
		
		}
		
		foreach ( $query_results as $key => $value ) {
		    $order   =   new WC_Order( $value->order_id );
		    $get_items = $order->get_items();
		
		    foreach( $get_items as $item_id => $item_values ) {
		        $booking_status = '';
		        if ( $value->post_id == $item_values[ 'product_id' ] ) {
		            if ( isset( $item_values[ 'wapbk_booking_status' ] ) )  {
		                $booking_status = $item_values[ 'wapbk_booking_status' ];
		            }
		
		            if ( isset( $booking_status ) ) {
		                if ( $_status == $booking_status ) {
		                    $results[] = $query_results[ $key ];
		                } else if ( ( $booking_status != 'confirmed' && $booking_status != 'pending-confirmation' ) && ( 'paid' == $_status ) ) {
		                    $results[] = $query_results[ $key ];
		                } else if( $booking_status != 'cancelled' && '' == $_status ) {
		                    $results[] = $query_results[ $key ];
		                }
		            }
		        }
		    }
		
		}
		
		// Date formats
		$date_formats     = bkap_get_book_arrays( 'date_formats' );
		// get the global settings to find the date & time formats
		$global_settings  = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
		$date_format_set  = $date_formats[ $global_settings->booking_date_format ];
		//Time format
		$time_format_set  = $global_settings->booking_time_format;
		$report           = array();
		$i                = 0;
		
		foreach ( $results as $key => $value ) {
			$checkout_date   = $time = '';
			$order           = new WC_Order( $value->order_id );
			
			// check if the order is refunded, trashed or cancelled
			if( isset( $order->post_status ) && ( $order->post_status != 'wc-cancelled' ) && ( $order->post_status != 'wc-refunded' ) && ( $order->post_status != 'trash' ) && ( $order->post_status != '' ) && ( $order->post_status != 'wc-failed' ) ) {
				
			    $report[ $i ]                   = new stdClass();
				// Order ID
				$report[ $i ]->order_id         = $value->order_id;
				// Booking ID
				$report[ $i ]->booking_id       = $value->booking_id;
				// Customer Name
				$report[ $i ]->customer_name    = $order->billing_first_name . " " . $order->billing_last_name;
				// Product ID
				$report[ $i ]->product_id       = $value->post_id;
				// Product Name
				$report[ $i ]->product_name     = get_the_title( $value->post_id );
				// Check-in Date
				$report[ $i ]->checkin_date     = date( $date_format_set, strtotime( $value->start_date ) );
				
				// Checkout Date
				if ( $value->end_date != '1970-01-01' && $value->end_date != '0000-00-00' ) {
					$report[ $i ]->checkout_date = date( $date_format_set, strtotime( $value->end_date ) );
				}else {
					$report[ $i ]->checkout_date = '';
				}
				
				// Booking Time
				$time = '';
				
				if ( $value->from_time != "" ) {
					$time = $value->from_time;
				}
				
				if ( $value->to_time != "" ) {
					$time .=  " - " . $value->to_time;
				}
				
				if ( $time != '' ) {
					
				    if ( $time_format_set == 12 ) {
						$from_time    = date( 'h:i A', strtotime( $value->from_time ) );
						$to_time      = date( 'h:i A', strtotime( $value->to_time ) );
						$time         = $from_time . " - " . $to_time;
					}
				}
				
				$report[$i]->time   = $time;
				// Quantity & amount
				$get_quantity       = $order->get_items();
				// The array needs to be reversed as we r displaying the last item first
				$get_quantity       = array_reverse( $get_quantity, true );
				
				foreach( $get_quantity as $k => $v ) {
					$product_exists = 'NO';
					$duplicate_of = bkap_common::bkap_get_product_id( $v[ 'product_id' ] );
					
					if ( $duplicate_of == $value->post_id ) {
						
					    foreach ( $report as $book_key => $book_value ) {
							
					        if ( $book_value->order_id == $value->order_id && $duplicate_of == $book_value->product_id ) {
								
					            if ( isset( $book_value->item_id ) && $k == $book_value->item_id ) {
									$product_exists = 'YES';
								}
							}
						} 
						if ( $product_exists == 'NO' ) {
							
						    $selected_quantity   = $v['qty'];
							$amount              = $v['line_total'] + $v['line_tax'];
							$report[$i]->item_id = $k;
							break;
						}
					}
				}
				
				$report[ $i ]->quantity     = $selected_quantity;
				$report[ $i ]->amount       = $amount;
				// Order Date
				$report[ $i ]->order_date   = $order->completed_date;
				$i++;
			}
		}
		
		//sort for order Id
		if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'ID' ) {
    		
		    if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
    				usort( $report, array( __CLASS__ , "bkap_order_id_asc" ) );
    		}else {
    				usort( $report, array( __CLASS__ , "bkap_order_id_dsc" ) );
    		}
		}
		// sort for amount
		else if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'amount' ) {
    		
		    if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
    				usort( $report, array( __CLASS__ , "bkap_amount_asc" ) );
    		}else {
    				usort( $report, array( __CLASS__ , "bkap_amount_dsc" ) );
    		}
		}
		// sort for qty
		else if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'quantity' ) {
    		
		    if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
    				usort( $report, array( __CLASS__ ,"bkap_quantity_asc" ) );
    		}else {
    				usort( $report,  array( __CLASS__ ,"bkap_quantity_dsc" ) );
    		}
		}
		// sort for order date
		else if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'order_date' ) {
    		
		    if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
    				usort( $report, array( __CLASS__ , "bkap_order_date_asc" ) );
    		}else {
    				usort( $report, array( __CLASS__ , "bkap_order_date_dsc" ) );
			} 
		}
		// sort for booking/checkin date
		else if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'checkin_date' ) {
		    
		    if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
				usort( $report, array( __CLASS__ , "bkap_checkin_date_asc" ) );
			}else {
				usort( $report, array( __CLASS__ , "bkap_checkin_date_dsc" ) );
			}
		}
		// sort for check out date
		else if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'checkout_date' ) {
    		
		    if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
    				usort( $report, array( __CLASS__ , "bkap_checkout_date_asc" ) );
    		 }else {
    				usort( $report, array( __CLASS__ , "bkap_checkout_date_dsc" ) );
			}
		}
		// sort for customer name
		else if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'name' ) {
		    if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
				usort( $report, array( __CLASS__ , "bkap_name_asc" ) );
			}else {
				usort( $report, array( __CLASS__ , "bkap_name_dsc" ) );
			}
		}
		// sort for product name
		else if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'product_name' ) {
		    if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
				usort( $report, array( __CLASS__ , "bkap_product_name_asc" ) );
			}else {
				usort( $report, array( __CLASS__ , "bkap_product_name_dsc" ) );
			}
		}

		$search_results = array();
		
		if ( isset( $_GET['s'] ) && $_GET['s'] != '' ) {
		
		    $date            = '';
			// strtotime does not support all date formats. hence it is suggested to use the "DateTime date_create_from_format" fn 
			$date_formats    = bkap_get_book_arrays( 'date_formats' );
			// get the global settings to find the date formats
			$global_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
			$date_format_set = $date_formats[ $global_settings->booking_date_format ];
			$date_formatted  = date_create_from_format( $date_format_set, $_GET['s'] );
			
			if ( isset( $date_formatted ) && $date_formatted != '' ) {
				$date = date_format( $date_formatted, 'Y-m-d' );
			}
			
		    $time = $from_time = $to_time = '';
		    
		    if ( strpos( $_GET['s'], '-' ) ) {
		        $time_array = explode( '-', $_GET['s'] );
		        
		        if ( isset( $time_array[0] ) && $time_array[0] != '' ) {
		            $from_time = date( 'G:i', strtotime( trim( $time_array[0] ) ) );
		        }
		        
		        if ( isset( $time_array[1] ) && $time_array[1] !== '' ) {
		            $to_time = date( 'G:i', strtotime( trim( $time_array[1] ) ) );
		        }
		        
		        $time = $from_time . " - " . $to_time;
		    }
		    
		    foreach ( $report as $key => $value ) {
		        
		        if ( is_numeric($_GET['s'] ) ) {
		            
		            if ( $value->order_id == $_GET['s'] ) {
		                $search_results[] = $report[ $key ];
		            }
		        }
		        else {
		            
		            foreach ( $value as $k => $v ) {
		                
		                if ( $k == 'checkin_date' || $k == 'checkout_date' && $date != '' ) {
		                    $date_value_formatted = date_create_from_format( $date_format_set, $v );	
		                    
		                    if ( isset( $date_value_formatted ) && $date_value_formatted != '' ) {
		                        $date_value = date_format( $date_value_formatted, 'Y-m-d' );
		                        
		                        if ( stripos( $date_value, $date ) !== false ) {
		                        	$search_results[] = $report[ $key ];
		                        }
		                    }
		                }else if ( $k == 'booking_time' ) {
		                    if ( isset( $v ) && $v != '' && $time != '' ) {
		                        if ( stripos( $v, $time ) !== false ) {
		                            $search_results[] = $report[ $key ];
		                        }
		                    }
		                }
		                else {
		                    if ( stripos( $v, $_GET['s'] ) !== false ) {
		                        $search_results[] = $report[ $key ];
		                    }
		                }
		            }
		        }
		    }
		    
		    if ( is_array( $search_results ) && count( $search_results ) > 0 ) {
		        $report = $search_results;
		    }
		    else {
		        $report = array();
		    }
		    
		}
		return apply_filters( 'bkap_bookings_export_data', $report );
   	}
   	
   	function bkap_order_id_asc( $value1, $value2 ) {
   	    return $value1->order_id - $value2->order_id;
   	}
   	
   	function bkap_order_id_dsc( $value1, $value2 ) {
   	    return $value2->order_id - $value1->order_id;
   	}
   	
   	function bkap_amount_asc( $value1, $value2 ) {
   	    return $value1->amount - $value2->amount;
   	}
   	
   	function bkap_amount_dsc ( $value1, $value2 ) {
   	    return $value2->amount - $value1->amount;
   	}
   	
   	function bkap_quantity_asc( $value1, $value2 ) {
   	    return $value1->quantity - $value2->quantity;
   	}
   	
   	function bkap_quantity_dsc( $value1, $value2 ) {
   	    return $value2->quantity - $value1->quantity;
   	}
   	
   	function bkap_order_date_asc( $value1, $value2 ) {
   	    return strtotime( $value1->order_date ) - strtotime( $value2->order_date );
   	}
   	 
   	function bkap_order_date_dsc( $value1, $value2 ) {
   	    return strtotime( $value2->order_date ) - strtotime( $value1->order_date );
   	}
   	
   	function bkap_checkin_date_asc( $value1, $value2 ) {
   	    return strtotime( $value1->checkin_date) - strtotime( $value2->checkin_date );
   	}
   	
   	function bkap_checkin_date_dsc( $value1, $value2 ) {
   	    return strtotime( $value2->checkin_date ) - strtotime( $value1->checkin_date );
   	}
   	
   	
   	function bkap_checkout_date_asc( $value1, $value2 ) {
   	    return strtotime( $value1->checkout_date ) - strtotime( $value2->checkout_date );
   	}
   	
   	function bkap_checkout_date_dsc( $value1, $value2 ) {
   	    return strtotime( $value2->checkout_date ) - strtotime( $value1->checkout_date );
   	}
   	
   	function bkap_name_asc( $value1, $value2 ) {
   	    return strcasecmp( $value1->customer_name, $value2->customer_name );
   	}
   	
   	function bkap_name_dsc( $value1, $value2 ) {
   	    return strcasecmp( $value2->customer_name, $value1->customer_name );
   	}
   	
   	function bkap_product_name_asc( $value1, $value2 ) {
   	    return strcasecmp( $value1->product_name, $value2->product_name );
   	}
   	
   	function bkap_product_name_dsc( $value1, $value2 ) {
   	    return strcasecmp( $value2->product_name, $value1->product_name );
   	}
   	
   	function generate_csv( $report ) {
   		
  		// Column Names
   		$csv               = 'Order ID,Customer Name,Product Name,Check-in Date, Check-out Date,Booking Time,Quantity,Amount, Order Date';
   		$csv              .= "\n";
   		
   		foreach ( $report as $key => $value ) {
   			// Order ID
   			$order_id         = $value->order_id;
   			// Customer Name
   			$customer_name    = $value->customer_name;
   			// Product Name
   			$product_name     = $value->product_name;
   			// Check-in Date
   			$checkin_date     = $value->checkin_date;
   			// Checkout Date
   			$checkout_date    = $value->checkout_date;
   			// Booking Time
   			$time             = $value->time;
   			// Quantity & amount
   			$selected_quantity= $value->quantity;
   			$amount           = $value->amount;
   			// Order Date
   			$order_date       = $value->order_date;
   			// Currency Symbol
   			// The order currency is fetched to ensure the correct currency is displayed if the site uses multi-currencies
   			$the_order          = wc_get_order( $value->order_id );
   			$currency           = $the_order->get_order_currency();
   			$currency_symbol    = get_woocommerce_currency_symbol( $currency );
   			
   			// Create the data row
   			$csv             .= $order_id . ',' . $customer_name . ',' . $product_name . ',"' . $checkin_date . '","' . $checkout_date . '","' . $time . '",' . $selected_quantity . ',' . $currency_symbol . $amount . ',' . $order_date;
   			$csv             .= "\n";  
   		}
   		$csv = apply_filters( 'bkap_bookings_csv_data', $csv, $report );
   		return $csv;
   	}
   	
}

$view_bookings = new view_bookings();