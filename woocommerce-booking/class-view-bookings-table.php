<?php 

// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WAPBK_View_Bookings_Table extends WP_List_Table {

	/**
	 * Number of results to show per page
	 *
	 * @var string
	 * 
	 */
	public $per_page = 30;

	/**
	 * URL of this page
	 *
	 * @var string
	 * 
	 */
	public $base_url;

	/**
	 * Total number of bookings
	 *
	 * @var int
	 * 
	 */
	public $total_count;

	/**
	 * Total number of bookings from today onwards
	 *
	 * @var int
	 * 
	 */
	public $future_count;

	/**
	 * Total number of check-ins today
	 *
	 * @var int
	 * 
	 */
	public $today_checkin_count;

	/**
	 * Total number of checkouts today
	 *
	 * @var int
	 * 
	 */
	public $today_checkout_count;

	/**
	 * Total Number of bookings awaiting confirmation
	 * 
	 *  @var int
	 */
	public $pending_confirmation;
	
	/**
	 * Total number of unpaid bookings
	 * 
	 * @var int
	 */
	public $unpaid;
	/**
	* Total number of Bookings Imported from GCal
	*/
	public $gcal_reserved;
	/**
	 * Get things started
	 *
	 * @see WP_List_Table::__construct()
	 */
	public function __construct() {

		global $status, $page;

		// Set parent defaults
		parent::__construct( array(
				'ajax'      => false             			// Does this table support ajax?
		) );

		$this->get_booking_counts();
		$this->base_url = admin_url( 'admin.php?page=woocommerce_history_page' );
	}
	
	public function bkap_prepare_items() {

		$columns  = $this->get_columns();
		$hidden   = array(); // No hidden columns
		$sortable = $this->get_sortable_columns();
		$data     = $this->bookings_data();
		$status   = isset( $_GET['status'] ) ? $_GET['status'] : 'any';
		
		$this->_column_headers = array( $columns, $hidden, $sortable);
		
		switch ( $status ) {
			case 'future':
				$total_items = $this->future_count;
				break;
			case 'today_checkin':
				$total_items = $this->today_checkin_count;
				break;
			case 'today_checkout':
				$total_items = $this->today_checkout_count;
				break;
			case 'any':
				$total_items = $this->total_count;
				break;
			case 'pending-confirmation':
			    $total_items = $this->pending_confirmation;
			    break;
			case 'unpaid':
			    $total_items = $this->unpaid;
			    break;
		    case 'gcal_reserved':
		        $total_items = $this->gcal_reserved;
		        break;
			default:
				$total_items = $this->total_count;
		}
		
		$this->items = $data;
		
		$this->set_pagination_args( array(
				'total_items' => $total_items,                  	// WE have to calculate the total number of items
				'per_page'    => $this->per_page,                     	// WE have to determine how many items to show on a page
				'total_pages' => ceil( $total_items / $this->per_page )   // WE have to calculate the total number of pages
		)
		);
		
	}
	
	public function get_views() {
	
		$current                  = isset( $_GET['status'] ) ? $_GET['status'] : '';
		$total_count              = '&nbsp;<span class="count">(' . $this->total_count    . ')</span>';
		$future_count             = '&nbsp;<span class="count">(' . $this->future_count  . ')</span>';
		$today_checkin_count      = '&nbsp;<span class="count">(' . $this->today_checkin_count . ')</span>';
		$today_checkout_count     = '&nbsp;<span class="count">(' . $this->today_checkout_count   . ')</span>';
		$unpaid                   = '&nbsp;<span class="count">(' . $this->unpaid   . ')</span>';
		$pending_confirmation     = '&nbsp;<span class="count">(' . $this->pending_confirmation   . ')</span>';
		$reserved_by_gcal         = '&nbsp;<span calss="count">(' . $this->gcal_reserved  . ')</span>';
		
		$views = array(
				'all'		=> sprintf( '<a href="%s"%s>%s</a>', remove_query_arg( array( 'status', 'paged' ) ), $current === 'all' || $current == '' ? ' class="current"' : '', __( 'All', 'woocommerce-booking' ) . $total_count ),
				'future'	=> sprintf( '<a href="%s"%s>%s</a>', add_query_arg( array( 'status' => 'future', 'paged' => FALSE ) ), $current === 'future' ? ' class="current"' : '', __( 'Bookings From Today Onwards', 'woocommerce-booking' ) . $future_count ),
				'today_checkin'	=> sprintf( '<a href="%s"%s>%s</a>', add_query_arg( array( 'status' => 'today_checkin', 'paged' => FALSE ) ), $current === 'today_checkin' ? ' class="current"' : '', __( 'Todays Check-ins', 'woocommerce-booking' ) . $today_checkin_count ),
				'today_checkout'	=> sprintf( '<a href="%s"%s>%s</a>', add_query_arg( array( 'status' => 'today_checkout', 'paged' => FALSE ) ), $current === 'today_checkout' ? ' class="current"' : '', __( 'Todays Check-outs', 'woocommerce-booking' ) . $today_checkout_count ),
                'unpaid'	=> sprintf( '<a href="%s"%s>%s</a>', add_query_arg( array( 'status' => 'unpaid', 'paged' => FALSE ) ), $current === 'unpaid' ? ' class="current"' : '', __( 'Unpaid', 'woocommerce-booking' ) . $unpaid ),
                'pending_confirmation'	=> sprintf( '<a href="%s"%s>%s</a>', add_query_arg( array( 'status' => 'pending_confirmation', 'paged' => FALSE ) ), $current === 'pending_confirmation' ? ' class="current"' : '', __( 'Pending Confirmation', 'woocommerce-booking' ) . $pending_confirmation ),
                'gcal_reservations'	=> sprintf( '<a href="%s"%s>%s</a>', add_query_arg( array( 'status' => 'gcal_reservations', 'paged' => FALSE ) ), $current === 'gcal_reservations' ? ' class="current"' : '', __( 'Reserved By GCal', 'woocommerce-booking' ) . $reserved_by_gcal )
		);
	
		return apply_filters( 'bkap_bookings_table_views', $views );
	}
	
	
	public function get_columns() {
		$columns = array(
				'ID'     		=> __( 'Order ID', 'woocommerce-booking' ),
				'name'  		=> __( 'Customer Name', 'woocommerce-booking' ),
				'product_name'  => __( 'Product Name', 'woocommerce-booking' ),
				'checkin_date'  => __( 'Check-in Date', 'woocommerce-booking' ),
				'checkout_date' => __( 'Check-out Date', 'woocommerce-booking' ),
				'booking_time'  => __( 'Booking Time', 'woocommerce-booking' ),
				'quantity'  	=> __( 'Quantity', 'woocommerce-booking' ),
				'amount'  		=> __( 'Amount', 'woocommerce-booking' ),
				'order_date'  	=> __( 'Order Date', 'woocommerce-booking' ),
				'actions'  		=> __( 'Actions', 'woocommerce-booking' )
		);
		
		return apply_filters( 'bkap_view_bookings_table_columns', $columns );
	}
	
	public function get_sortable_columns() {
		$columns = array(
				'ID' 			=> array( 'ID', true ),
				'amount'		=> array( 'amount',false),
				'quantity'		=> array( 'quantity',false),
				'order_date'	=> array( 'order_date',false),
				'checkin_date'	=> array( 'checkin_date',false),
				'checkout_date'	=> array( 'checkout_date',false),
				'name'			=> array( 'name',false),
				'product_name' 	=> array( 'product_name',false)
		);
		return apply_filters( 'bkap_view_bookings_sortable_columns', $columns );
		
	}
	public function advanced_filters() {
		$search       = isset( $_GET['search'] )  ? sanitize_text_field( $_GET['search'] ) : null;
		$status       = isset( $_GET['status'] )      ? $_GET['status'] : '';
		?>
			<div id="view-bookings-filters">
				<?php $this->search_box( __( 'Search', 'woocommerce-booking' ), 'bkap-bookings' ); ?>
			</div>
	
	<?php
	}
		
	public function search_box( $text, $input_id ) {
		
		if ( empty( $_REQUEST['s'] ) && !$this->has_items() ){
			return;
		}
		$input_id = $input_id . '-search-input';
		
		if ( ! empty( $_REQUEST['orderby'] ) )
			echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
		if ( ! empty( $_REQUEST['order'] ) )
			echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />';
		?>
				<p class="search-box">
					<?php do_action( 'booking_search' ); ?>
					<label class="screen-reader-text" for="<?php echo $input_id ?>"><?php echo $text; ?>:</label>
					<input type="search" id="<?php echo $input_id ?>" name="s" value="<?php _admin_search_query(); ?>" />
					<?php submit_button( $text, 'button', false, false, array('ID' => 'search-submit' ) ); ?><br/>
				</p>
		<?php
	}
	
	public function get_booking_counts() {
	
	//	global $wp_query;
	
		$args = array();
	
		if( isset( $_GET['user'] ) ) {
			$args['user'] = urldecode( $_GET['user'] );
		} elseif( isset( $_GET['s'] ) ) {
			$args['s'] = urldecode( $_GET['s'] );
		}
	
		if ( ! empty( $_GET['start-date'] ) ) {
			$args['start-date'] = urldecode( $_GET['start-date'] );
		}
	
		if ( ! empty( $_GET['end-date'] ) ) {
			$args['end-date'] = urldecode( $_GET['end-date'] );
		}
	
		$bookings_count               = $this->bkap_count_bookings( $args );
		
		$this->total_count            = $bookings_count['total_count'];
		$this->future_count           = $bookings_count['future_count'];
		$this->today_checkin_count    = $bookings_count['today_checkin_count'];
		$this->today_checkout_count   = $bookings_count['today_checkout_count'];
		$this->unpaid                 = $bookings_count[ 'unpaid' ];
		$this->pending_confirmation   = $bookings_count[ 'pending_confirmation' ];
		$this->gcal_reserved          = $bookings_count[ 'gcal_reservations' ];
		
	}
	
	public function bkap_count_bookings( $args ) {
		global $wpdb;
		
		$bookings_count = array(
				'total_count' => 0,
				'future_count' => 0,
				'today_checkin_count' => 0,
				'today_checkout_count' => 0,
                'unpaid' => 0,
                'pending_confirmation' => 0,
                'gcal_reservations' => 0
				);
		
		//Today's date
		$current_time = current_time( 'timestamp' );
		$current_date = date( "Y-m-d", $current_time );
		    
		$start_date   = $end_date = '';
		
		if ( isset( $args['start-date'] ) ) {
			$start_date = $args['start-date'];
		}
		
		if ( isset( $args['end-date'] ) ) {
			$end_date = $args['end-date'];
		}
		
		if ( $start_date != '' && $end_date != '' && $start_date != '1970-01-01' && $end_date != '1970-01-01' ) {
			
		}
		else {
			$today_query = "SELECT a2.order_id,a1.start_date,a1.end_date,a1.post_id FROM `".$wpdb->prefix."booking_history` AS a1,`".$wpdb->prefix."booking_order_history` AS a2 WHERE a1.id = a2.booking_id";
		}
		
		$results_date = $wpdb->get_results ( $today_query );
		
		foreach ( $results_date as $key => $value ) {
			$post_data = get_post( $value->order_id );
			
			if ( isset( $post_data->post_status ) && $post_data->post_status != 'wc-refunded' && $post_data->post_status != 'trash' && $post_data->post_status != 'wc-cancelled' && $post_data->post_status != '' && $post_data->post_status != 'wc-failed' ) {
				
				// Order details
				$order   =   new WC_Order( $value->order_id );
				$created_via = get_post_meta( $value->order_id, '_created_via', true );
				
				$get_items = $order->get_items();
				
				foreach( $get_items as $item_id => $item_values ) {
				    $booking_status = '';
				    
				    if ( $value->post_id == $item_values[ 'product_id' ] ) {
				        if ( isset( $item_values[ 'wapbk_booking_status' ] ) ) {
				            $booking_status = $item_values[ 'wapbk_booking_status' ];
				        }
				
				        if ( isset( $booking_status ) ) {
				            // if it's not cancelled, add it to the All count 
				            if ( 'cancelled' != $booking_status ) {
				                $bookings_count['total_count'] += 1;
				            }
				            
				            // Unpaid count
				            if ( 'confirmed' == $booking_status ) {
				                $bookings_count[ 'unpaid' ] += 1;
				            } else if( 'pending-confirmation' == $booking_status ) { // pending confirmation count
				                $bookings_count[ 'pending_confirmation' ] += 1;
				            } else if ( 'paid' == $booking_status || '' == $booking_status ) {
				                
				                if ( $value->start_date >= $current_date ) { // future count
				                    $bookings_count['future_count'] += 1;
				                }
				                if ( $value->start_date == $current_date ) { // today's checkin's
				                    $bookings_count['today_checkin_count'] += 1;
				                }
				                if ( $value->end_date == $current_date ) { // today's checkouts
				                    $bookings_count['today_checkout_count'] += 1;
				                }
				                
				            }
				            if ( isset( $created_via ) && $created_via == 'GCal' ) {
				                $bookings_count[ 'gcal_reservations' ] += 1;
				            }
				        }
				    }
				}
				
			}
		}
		
		return $bookings_count;
	}
	
	public function bookings_data() { 
		global $wpdb;
		
		$return_bookings  = array();
		
		if ( isset( $_GET[ 'paged' ] ) && $_GET[ 'paged' ] > 1 ) {
		    $page_number = $_GET[ 'paged' ] - 1;
		} else {
		    $page_number = 0;
		}
		
		$per_page         = $this->per_page;
		
		$results          = array();
		$current_time     = current_time( 'timestamp' );
		$current_date     = date( "Y-m-d", $current_time );
	
	   if ( isset( $_GET['status'] ) && $_GET['status'] == 'future' ) {
			$booking_query   = "SELECT *,a2.order_id FROM `".$wpdb->prefix."booking_history` AS a1,`".$wpdb->prefix."booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.start_date >= '".$current_date."' ORDER BY a2.order_id DESC";
			$query_results         = $wpdb->get_results( $booking_query );
		}
		else if ( isset( $_GET['status'] ) && $_GET['status'] == 'today_checkin' ) {
			$booking_query   = "SELECT *,a2.order_id FROM `".$wpdb->prefix."booking_history` AS a1,`".$wpdb->prefix."booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.start_date = '".$current_date."' ORDER BY a2.order_id DESC";
			$query_results         = $wpdb->get_results( $booking_query );
		}
		else if ( isset( $_GET['status'] ) && $_GET['status'] == 'today_checkout' ) {
			$booking_query   = "SELECT *,a2.order_id FROM `".$wpdb->prefix."booking_history` AS a1,`".$wpdb->prefix."booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.end_date = '".$current_date."' ORDER BY a2.order_id DESC";
			$query_results         = $wpdb->get_results( $booking_query );
		} else {
			$booking_query   = "SELECT *,a2.order_id FROM `".$wpdb->prefix."booking_history` AS a1,`".$wpdb->prefix."booking_order_history` AS a2 WHERE a1.id = a2.booking_id ORDER BY a2.order_id DESC";
			$query_results   = $wpdb->get_results( $booking_query );
		}
		if( count( $query_results ) > $per_page ) {
    		$query_results = array_chunk( $query_results, $per_page );
    		$query_results = $query_results[ $page_number ];
		}
		
		$results = array();
		
		$_status = '';
		if ( isset( $_GET[ 'status' ] ) ) {
            $_status = $_GET[ 'status' ];
		}
		
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
	        case 'gcal_reservations':
	            $_status = 'GCal';
	            break;
		    default:
		        $_status = '';
		        break;
		
		}
		
		foreach ( $query_results as $key => $value ) {
		    $order   =   new WC_Order( $value->order_id );
		    $get_items = $order->get_items();
		
		    $created_via = get_post_meta( $value->order_id, '_created_via', true );
		    
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
		                } else if( 'GCal' == $_status && ( $_status == $created_via ) ) {
		                    $results[] = $query_results[ $key ];
		                }
		            }
		        }
		    }
		
		}
		
		$i = 0;
		
		foreach ( $results as $key => $value ) {
			$time    =   '';
			// Order details		
			$order   =   new WC_Order( $value->order_id );
			// check if the order is refunded, trashed or cancelled
			
			if( isset( $order->post_status ) && ( $order->post_status != '' ) && ( $order->post_status != 'wc-cancelled' ) && ( $order->post_status != 'wc-refunded' ) && ( $order->post_status != 'trash' ) && ( $order->post_status != 'wc-failed' ) ) {
				$return_bookings[$i]        =   new stdClass();
				$return_bookings[$i]->name  =   $order->billing_first_name . " " . $order->billing_last_name;
				$get_quantity               =   $order->get_items();
				
				// The array needs to be reversed as we r displaying the last item first
				$get_quantity = array_reverse( $get_quantity, true );
				
				foreach( $get_quantity as $k => $v ) {
					
				    $product_exists = 'NO';
				    $duplicate_of = bkap_common::bkap_get_product_id( $v[ 'product_id' ] );
				    
					if ( $duplicate_of == $value->post_id ) {
						
					    foreach ($return_bookings as $book_key => $book_value ) {
							
					        if ( isset ( $book_value->ID ) && $book_value->ID == $value->order_id && $duplicate_of == $book_value->product_id ) {
								
					            if ( isset ( $book_value->item_id ) && $k == $book_value->item_id ) {
									$product_exists = 'YES';
								}
							}
						}
						
						if ( $product_exists == 'NO' ) {
							$selected_quantity                 = $v['qty'];
							$amount                            = $v['line_total'] + $v['line_tax'];
							$return_bookings[ $i ]->item_id    = $k;
							break;
						}
					}
				}
				
				$product_name = get_the_title($value->post_id);
				
				// Populate the array
				$return_bookings[ $i ]->ID            = $value->order_id;
				$return_bookings[ $i ]->booking_id    = $value->booking_id;
				$return_bookings[ $i ]->product_id    = $value->post_id;
				$return_bookings[ $i ]->product_name  = $product_name;
				$return_bookings[ $i ]->checkin_date  = $value->start_date;
				$return_bookings[ $i ]->checkout_date = $value->end_date;
				if ( $value->from_time != "" ) {
					$time = $value->from_time;
				}
				if ( $value->to_time != "" ) {
					$time .=  " - " . $value->to_time;
				}
				$return_bookings[ $i ]->booking_time    = $time;
				$return_bookings[ $i ]->quantity        = $selected_quantity;
				$return_bookings[ $i ]->amount          = $amount;
				$return_bookings[ $i ]->order_date      = $order->completed_date;
				$i++;
			}
		}
		
		//sort for order Id
		if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'ID' ) {
		    if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
				usort( $return_bookings, array( __CLASS__ , "bkap_class_order_id_asc" ) );
			}
			else {
				usort( $return_bookings, array( __CLASS__ , "bkap_class_order_id_dsc" ) );
			}
		}
		
		// sort for amount
		else if ( isset($_GET['orderby'] ) && $_GET['orderby'] == 'amount' ) {
		    if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
		  		usort( $return_bookings, array( __CLASS__ , "bkap_class_amount_asc" ) ); 
			}
			else {
				usort( $return_bookings, array( __CLASS__ , "bkap_class_amount_dsc" ) );
			}
		}
		
		// sort for qty
		else if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'quantity' ) {
		    if ( isset( $_GET['order' ]) && $_GET['order'] == 'asc' ) {
				usort( $return_bookings, array( __CLASS__ , "bkap_class_quantity_asc" ) ); 
			}
			else {
				usort( $return_bookings, array( __CLASS__ , "bkap_class_quantity_dsc" ) );
			}
		}
		
		// sort for order date
		else if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'order_date' ) {
		    if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
				usort( $return_bookings, array( __CLASS__ , "bkap_class_order_date_asc" ) ); 
			}
			else {
				usort( $return_bookings, array( __CLASS__ , "bkap_class_order_date_dsc" ) );
			}
		}
		
		// sort for booking/checkin date
		else if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'checkin_date' ) {
		    if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
				usort( $return_bookings, array( __CLASS__ , "bkap_class_checkin_date_asc" ) );
			}
			else {
				usort( $return_bookings, array( __CLASS__ , "bkap_class_checkin_date_dsc" ) );
			}
		}
		
		// sort for check out date
		else if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'checkout_date' ) {
		    if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
				usort( $return_bookings, array(__CLASS__ , "bkap_class_checkout_date_asc" ) );
			}
			else {
				usort( $return_bookings, array(__CLASS__ ,"bkap_class_checkout_date_dsc" ) );
			}
		}
		
		// sort for customer name
		else if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'name' ) {
		if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
				usort( $return_bookings, array( __CLASS__ , "bkap_class_name_asc" ) );
			}
			else {
				usort( $return_bookings, array( __CLASS__ ,"bkap_class_name_dsc" ) );
			}
		}
		// sort for product name
		else if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'product_name' ) {
		if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
				usort( $return_bookings, array( __CLASS__ ,"bkap_class_product_name_asc" ) );
			}
			else {
				usort( $return_bookings, array( __CLASS__ ,"bkap_class_product_name_dsc" ) );
			}
		}
		$search_results = array();
		
		if ( isset( $_GET['s'] ) && $_GET['s'] != '' ) {
			$date            =   '';
			// strtotime does not support all date formats. hence it is suggested to use the "DateTime date_create_from_format" fn 
			$date_formats    =   bkap_get_book_arrays( 'date_formats' );
			// get the global settings to find the date formats
			$global_settings =   json_decode( get_option( 'woocommerce_booking_global_settings' ) );
			$date_format_set =   $date_formats[ $global_settings->booking_date_format ];
			$date_formatted  =   date_create_from_format( $date_format_set, $_GET['s'] );
			
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
			foreach ( $return_bookings as $key => $value ) {
				
			    if ( is_numeric( $_GET['s'] ) ) {
					
			        if ( $value->ID == $_GET['s'] ) {
						$search_results[] = $return_bookings[ $key ];
					}
				}
				else {
					foreach ( $value as $k => $v ) {
						
					    if ( $k == 'checkin_date' || $k == 'checkout_date' && $date != '' ) {
							
					        if ( stripos( $v, $date ) !== false ) {
							 $search_results[] = $return_bookings[ $key ];
							}
						}else if ( $k == 'booking_time' ) {
							
						    if ( isset( $v ) && $v != '' && $time != '' ) {
								
						        if ( stripos( $v, $time ) !== false ) {
									$search_results[] = $return_bookings[ $key ];
								}
							}
						}else {
							if ( stripos( $v, $_GET['s'] ) !== false ) {
								$search_results[] = $return_bookings[ $key ];
							}
						}
					}
				}
			}
			if ( is_array( $search_results ) && count( $search_results ) > 0 ) {
				$return_bookings = $search_results;
			}
			else {
				$return_bookings = array();
			}	
		}
		
		return apply_filters( 'bkap_bookings_table_data', $return_bookings );
	}
	
	function bkap_class_order_id_asc ( $value1, $value2 ) {
	    return $value1->ID - $value2->ID;
	}
	
	function bkap_class_order_id_dsc( $value1, $value2 ) {
	    return $value2->ID - $value1->ID;
	}
	
	function bkap_class_amount_asc( $value1, $value2 ) {
	    return $value1->amount - $value2->amount;
	}
	
	function bkap_class_amount_dsc( $value1, $value2 ) {
	    return $value2->amount - $value1->amount;
	}
	
	function bkap_class_quantity_asc ( $value1, $value2 ) {
	    return $value1->quantity - $value2->quantity;
	}
	function bkap_class_quantity_dsc( $value1, $value2 ) {
	    return $value2->quantity - $value1->quantity;
	}
	
	function bkap_class_order_date_asc( $value1, $value2 ) {
	    return strtotime( $value1->order_date ) - strtotime( $value2->order_date );
	}
	function bkap_class_order_date_dsc ( $value1, $value2 ) {
	    return strtotime( $value2->order_date ) - strtotime( $value1->order_date );
	}
	
	function bkap_class_checkin_date_asc( $value1, $value2 ) {
	    return strtotime($value1->checkin_date) - strtotime( $value2->checkin_date );
	}
	function bkap_class_checkin_date_dsc( $value1, $value2 ) {
	    return strtotime( $value2->checkin_date ) - strtotime( $value1->checkin_date );
	}
	
	function bkap_class_checkout_date_asc( $value1, $value2 ) {
	    return strtotime( $value1->checkout_date ) - strtotime( $value2->checkout_date );
	}
	
	function bkap_class_checkout_date_dsc( $value1, $value2 ) {
	    return strtotime( $value2->checkout_date ) - strtotime( $value1->checkout_date );
	}
	
	function bkap_class_name_asc( $value1, $value2 ) {
	    return strcasecmp( $value1->name,$value2->name );
	}
	
	function bkap_class_name_dsc ( $value1, $value2 ) {
	    return strcasecmp( $value2->name,$value1->name );
	}
	
	function bkap_class_product_name_asc( $value1, $value2 ) {
	    return strcasecmp( $value1->product_name,$value2->product_name );
	}
	
	function bkap_class_product_name_dsc ( $value1, $value2 ) {
	    return strcasecmp( $value2->product_name,$value1->product_name );
	}
	
	public function column_default( $booking, $column_name ) {
		
	    switch ( $column_name ) {
			
	        case 'ID' :
				$value = '<a href="post.php?post='.$booking->ID.'&action=edit">'.$booking->ID.'</a>';
				break;
			
			case 'checkin_date' :
				$date               = strtotime( $booking->checkin_date );
				$date_formats       = bkap_get_book_arrays( 'date_formats' );
				// get the global settings to find the date formats
				$global_settings    = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
				$date_format_set    = $date_formats[ $global_settings->booking_date_format ];
				$value              = date( $date_format_set, $date );
				break;
			
			case 'checkout_date' :
				if ( $booking->checkout_date != '0000-00-00' ) {
					$date              = strtotime( $booking->checkout_date );
					$date_formats      = bkap_get_book_arrays( 'date_formats' );
					// get the global settings to find the date formats
					$global_settings   = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
					$date_format_set   = $date_formats[$global_settings->booking_date_format];
					$value             = date( $date_format_set, $date );
				}
				else {
					$value = "";
				}
				break;
			
			case 'booking_time' :
				if ( $booking->booking_time != '' ) {
					// get the global settings to find the date formats
					$global_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
					
					if ( $global_settings->booking_time_format == 12 ) {
						$time_array   =   explode( '-', $booking->booking_time );
						$from_time    =   date( 'h:i A', strtotime( $time_array[0] ) );
					    $value        =   $from_time ;
						
						if ( isset( $time_array[1] ) && $time_array[1] != '' ){
						  $to_time    =   date( 'h:i A',strtotime( $time_array[1] ) );
						  $value      =   $from_time . " - " . $to_time;
						}
					}else {
						$value        =   $booking->booking_time;
					}
				}else {
					$value = '';
				}
				break;
			
			case 'amount' :
				$amount             = ! empty( $booking->amount ) ? $booking->amount : 0;
				// The order currency is fetched to ensure the correct currency is displayed if the site uses multi-currencies
				$the_order          = wc_get_order( $booking->ID );
				$currency           = $the_order->get_order_currency();
				$currency_symbol    = get_woocommerce_currency_symbol( $currency );
				$value              = $currency_symbol . number_format( $amount, 2 );
				break;
			
			case 'actions' :
	 			$value = '<a href="post.php?post='.$booking->ID.'&action=edit">View Order</a>';
				break;
			
			default:
				$value = isset( $booking->$column_name ) ? $booking->$column_name : '';
				break;
	
		}
		
		return apply_filters( 'bkap_booking_table_column_default', $value, $booking, $column_name );
	}
	
	
}
?>