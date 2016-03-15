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
        		
                    <div id="bkap_update_event_message"></div>
        			<p id="bkap_add_order">
						<a href="<?php echo esc_url( add_query_arg( 'post_type', 'shop_order', admin_url( 'post-new.php' ) ) ); ?>" class="button-secondary"><?php _e( 'Create Booking', 'woocommerce-booking' ); ?></a>
        			     <a href="
						    <?php echo isset ( $_GET['booking_view'] ) && $_GET['booking_view'] == "booking_calender" ? admin_url( 'admin.php?page=woocommerce_history_page' ) : esc_url( add_query_arg( 'booking_view', 'booking_calender' ) ) ;  ?>" 
    						class="button-secondary">
    						<?php isset ( $_GET['booking_view'] ) && $_GET['booking_view'] == "booking_calender" ? _e( 'View Booking Listing', 'woocommerce-booking' )  : _e ( 'Calendar View', 'woocommerce-booking' );  ?>
						</a>
        			<?php 
        			if ( !isset( $_GET['booking_view'] ) ) {
                        $gcal = new BKAP_Gcal();
        			    $total_bookings_to_export = bkap_common::bkap_get_total_bookings_to_export();
        			    if( $gcal->get_api_mode() == "directly" && get_option( 'bkap_admin_add_to_calendar_view_booking' ) == 'on' ) {
        			    ?>
        			    
        			    <input type="button" class="button-secondary" id="bkap_admin_add_to_calendar_booking" style="float:right;" value="<?php _e( 'Add to Google Calendar', 'woocommerce-booking' ); ?>">
                            
                            <script type="text/javascript">
                            jQuery( document ).ready( function(){ 
                                jQuery( "#bkap_admin_add_to_calendar_booking" ).on( 'click', function() {
                                	<?php if ( count( $total_bookings_to_export ) > 0 ) {?>
                                        var orders_to_export = "<?php echo count( $total_bookings_to_export ); ?>";
                                        jQuery( "#bkap_update_event_message" ).html( "Total orders to export " +  orders_to_export + " ... " );
                                        var data = {
                                     		   action: "bkap_admin_booking_calendar_events"
                            		    };
                                        jQuery.post( "<?php echo get_admin_url(); ?>/admin-ajax.php", data, function( response ) {
                                     	   jQuery( "#bkap_update_event_message" ).html( "All events are added to the Google calendar. Please refresh your Google Calendar." );
                                	    });
                            	    <?php } else {?>
                            	    jQuery( "#bkap_update_event_message" ).html( "No pending orders left to be exported." );
                            	    <?php }?>
                                });
                            });
                            </script>
                        <?php 
                        }
                        ?>
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

		include_once( 'class-view-bookings-table.php' );
		$bookings_table = new WAPBK_View_Bookings_Table();
		
		if ( isset( $_GET['download'] ) && ( $_GET['download'] == 'data.csv' ) ) {
		    $report = $bookings_table->bookings_data();
	   		$csv     = view_bookings::generate_csv( $report );
	   		
	   		header("Content-type: application/x-msdownload");
	        header("Content-Disposition: attachment; filename=data.csv");
	        header("Pragma: no-cache");
	        header("Expires: 0");
	        
	   		echo $csv;
	   		exit;
		}else if( isset( $_GET['download'] ) && ( $_GET['download'] == 'data.print' ) ) {
		    $report = $bookings_table->bookings_data();
			
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
			    $the_order          = wc_get_order( $value->ID );
			    $currency           = $the_order->get_order_currency();
			    $currency_symbol    = get_woocommerce_currency_symbol( $currency );
			     
				$print_data_row_data .= "<tr>
        								<td style='border:1px solid black;padding:5px;'>".$value->ID."</td>
        								<td style='border:1px solid black;padding:5px;'>".$value->name."</td>
        								<td style='border:1px solid black;padding:5px;'>".$value->product_name."</td>
        								<td style='border:1px solid black;padding:5px;'>".$value->checkin_date."</td>
        								<td style='border:1px solid black;padding:5px;'>".$value->checkout_date."</td>
        								<td style='border:1px solid black;padding:5px;'>".$value->booking_time."</td>
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
   	
   	function generate_csv( $report ) {
   		
  		// Column Names
   		$csv               = 'Order ID,Customer Name,Product Name,Check-in Date, Check-out Date,Booking Time,Quantity,Amount, Order Date';
   		$csv              .= "\n";
   		
   		foreach ( $report as $key => $value ) {
   			// Order ID
   			$order_id         = $value->ID;
   			// Customer Name
   			$customer_name    = $value->name;
   			// Product Name
   			$product_name     = $value->product_name;
   			// Check-in Date
   			$checkin_date     = $value->checkin_date;
   			// Checkout Date
   			$checkout_date    = $value->checkout_date;
   			// Booking Time
   			$time             = $value->booking_time;
   			// Quantity & amount
   			$selected_quantity= $value->quantity;
   			$amount           = $value->amount;
   			// Order Date
   			$order_date       = $value->order_date;
   			// Currency Symbol
   			// The order currency is fetched to ensure the correct currency is displayed if the site uses multi-currencies
   			$the_order          = wc_get_order( $value->ID );
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