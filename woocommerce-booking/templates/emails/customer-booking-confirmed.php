<?php
/**
 * Customer booking confirmed email
 */
?>

<?php do_action( 'woocommerce_email_header', $email_heading ); ?>

<?php 
$order = new WC_order( $booking->order_id );
if ( $order ) : ?>
	<p><?php printf( __( 'Hello %s', 'woocommerce-booking' ), $order->billing_first_name ); ?></p>
<?php endif; ?>

<p><?php _e( 'Your booking has been confirmed. The details of your booking are shown below.', 'woocommerce-booking' ); ?></p>

<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;" border="1" bordercolor="#eee">
	<tbody>
		<tr>
			<th scope="row" style="text-align:left; border: 1px solid #eee;"><?php _e( 'Booked Product', 'woocommerce-booking' ); ?></th>
			<td style="text-align:left; border: 1px solid #eee;"><?php echo $booking->product_title; ?></td>
		</tr>
		<tr>
			<th style="text-align:left; border: 1px solid #eee;" scope="row"><?php _e( 'Booking Start Date', 'woocommerce-booking' ); ?></th>
			<td style="text-align:left; border: 1px solid #eee;"><?php echo $booking->item_booking_date; ?></td>
		</tr>
		<?php
		if ( isset( $booking->item_checkout_date ) && '' != $booking->item_checkout_date ) { 
    		?>
    		<tr>
    			<th style="text-align:left; border: 1px solid #eee;" scope="row"><?php _e( 'Booking End Date', 'woocommerce-booking' ); ?></th>
    			<td style="text-align:left; border: 1px solid #eee;"><?php echo $booking->item_checkout_date ?></td>
    		</tr>
    		<?php 
		}
		if ( isset( $booking->item_booking_time ) && '' != $booking->item_booking_time ) {
	    ?>
    		<tr>
    			<th style="text-align:left; border: 1px solid #eee;" scope="row"><?php _e( 'Booking Time', 'woocommerce-booking' ); ?></th>
    			<td style="text-align:left; border: 1px solid #eee;"><?php echo $booking->item_booking_time ?></td>
    		</tr>
		<?php 
		}
		?>
	</tbody>
</table>

<?php if ( $order ) : ?>

	<?php if ( $order->status == 'pending' ) : ?>
		<p><?php printf( __( 'To pay for this booking please use the following link: %s', 'woocommerce-booking' ), '<a href="' . esc_url( $order->get_checkout_payment_url() ) . '">' . __( 'Pay for booking', 'woocommerce-booking' ) . '</a>' ); ?></p>
	<?php endif; ?>

	<?php do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text ); ?>

	<h2><?php echo __( 'Order', 'woocommerce-booking' ) . ': ' . $order->get_order_number(); ?> (<?php printf( '<time datetime="%s">%s</time>', date_i18n( 'c', strtotime( $order->order_date ) ), date_i18n( wc_date_format(), strtotime( $order->order_date ) ) ); ?>)</h2>

	<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;" border="1" bordercolor="#eee">
		<thead>
			<tr>
				<th scope="col" style="text-align:left; border: 1px solid #eee;"><?php _e( 'Product', 'woocommerce-booking' ); ?></th>
				<th scope="col" style="text-align:left; border: 1px solid #eee;"><?php _e( 'Quantity', 'woocommerce-booking' ); ?></th>
				<th scope="col" style="text-align:left; border: 1px solid #eee;"><?php _e( 'Price', 'woocommerce-booking' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
                $downloadable = $order->is_download_permitted();
                
				switch ( $order->status ) {
					case "completed" :
					    $args = array( 'show_download_links' => $downloadable,
					                   'show_sku' => false,
					                   'show_purchase_note' => true 
					           );
						echo $order->email_order_items_table( $args );
                        break;
					case "processing" :
					    $args = array( 'show_download_links' => $downloadable,
					                   'show_sku' => true,
					                   'show_purchase_note' => true 
					           );
					    echo $order->email_order_items_table( $args );
                        break;
					default :
					    $args = array( 'show_download_links' => $downloadable,
					                   'show_sku' => true,
					                   'show_purchase_note' => false 
					           );
					    echo $order->email_order_items_table( $args );
                        break;
				}
			?>
		</tbody>
		<tfoot>
			<?php
				if ( $totals = $order->get_order_item_totals() ) {
					$i = 0;
					foreach ( $totals as $total ) {
						$i++;
						?><tr>
							<th scope="row" colspan="2" style="text-align:left; border: 1px solid #eee; <?php if ( $i == 1 ) echo 'border-top-width: 4px;'; ?>"><?php echo $total['label']; ?></th>
							<td style="text-align:left; border: 1px solid #eee; <?php if ( $i == 1 ) echo 'border-top-width: 4px;'; ?>"><?php echo $total['value']; ?></td>
						</tr><?php
					}
				}
			?>
		</tfoot>
	</table>

	<?php do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text ); ?>

	<?php do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text ); ?>

<?php endif; ?>

<?php do_action( 'woocommerce_email_footer' ); ?>
