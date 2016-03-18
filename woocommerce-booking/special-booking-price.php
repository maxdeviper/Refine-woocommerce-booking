<?php
// 	bkap_get_per_night_price -- multiple days booking
//  bkap_call_addon_price    -- For single day booking
    
    /**
	 * Localisation
	 */
	load_plugin_textdomain( 'bkap_special_booking_price', false, dirname( plugin_basename( __FILE__ ) ) . '/' );

	/**
	 * bkap_deposits class
	 **/
	if ( !class_exists( 'bkap_special_booking_price' ) ) {

		class bkap_special_booking_price {

			public function __construct() {
				add_action( 'bkap_after_listing_enabled',               array( &$this, 'bkap_special_booking_price_show_field_settings' ), 4, 1 );
				add_action( 'bkap_add_tabs',                            array( &$this, 'bkap_special_booking_price_tab' ), 4, 1 );
				add_action( 'admin_init',                               array( &$this, 'bkap_load_ajax_special_booking_price_block' ) );
				    
				// Display updated price on the product page
				add_action( 'bkap_display_updated_addon_price',         array( &$this, 'special_booking_display_updated_price' ), 2, 3 );
				
				// Display the price div if different prices are enabled for time slots
				add_action('bkap_display_price_div',                    array( &$this, 'special_booking_price_div' ), 21, 1 );
				
				// Display the multiple days specia  booking price on the product page
				add_action('bkap_display_multiple_day_updated_price',   array( &$this, 'bkap_special_booking_show_multiple_updated_price' ), 7, 6 );

			}
			
			function bkap_special_booking_price_tab( $product_id ) {
				?>
				<li><a id="special_booking"> <?php _e( 'Special Pricing', 'woocommerce-booking' );?> </a></li>
				<?php 
			}
						
			/**
			 *   This function is used to load ajax functions required by weekday block booking.
			 */
			function bkap_load_ajax_special_booking_price_block() {
				
				add_action( 'wp_ajax_bkap_save_special_booking_price',         array( &$this, 'bkap_save_special_booking_price' ) );
				add_action( 'wp_ajax_bkap_delete_special_booking',             array( &$this, 'bkap_delete_special_booking' ) );
				add_action( 'wp_ajax_bkap_delete_all_special_booking',         array( &$this, 'bkap_delete_all_special_booking' ) );
				
			}
						
		   /**
			* This function add the Weekday and Holiday booking table on the admin side.
			* It allows to create block on the admin product page.
			*/
			function bkap_special_booking_price_show_field_settings( $product_id ) {
				global $post, $wpdb;
				
				$weekdays                   = bkap_get_book_arrays( 'weekdays' );
				$currency_symbol            = get_woocommerce_currency_symbol();
				$special_price_str          = "";		
				
				$booking_special_prices     = get_post_meta( $post->ID, 'booking_special_price', true );
				$booking_special_price_cnt  = count( $booking_special_prices );
				
				if ( is_array( $booking_special_prices ) && $booking_special_price_cnt > 0 ){ 
					
				    foreach ($booking_special_prices as $key => $value) { 
						$special_price_str .= '<tr id="row_'.$key.'">';
						
						if( $value['booking_special_weekday'] != "" ){
							$weekday_value       =   $value['booking_special_weekday'];
							$special_price_str  .=   '<td>'.$weekdays[$weekday_value].'</td>';
						}else if ( $value['booking_special_date'] != "" ){
							$special_price_str  .=   '<td>'.$value['booking_special_date'].'</td>';
						}else{
							$special_price_str  .=   '<td>&nbsp;</td>';
						}
						
						$special_price_str      .=   '<td>'.$currency_symbol . $value['booking_special_price'].'</td>
                            						 <td> <a href="javascript:void(0);" id="'.$key.'&'.$value['booking_special_weekday'].'&'.$value['booking_special_date'].'&'.$value['booking_special_price'].'" class="special_booking_edit_block"> <img src="'.plugins_url().'/woocommerce-booking/images/edit.png" alt="Edit Special Booking" title="'.__( "Edit Special Booking Price" , "woocommerce-booking" ).'"></a>
                            						 <a href="javascript:void(0);" id="'.$key.'" class="special_booking_delete_block"> <img src="'.plugins_url().'/woocommerce-booking/images/delete.png" alt="Delete All Special Prices" title="'. __( "Delete All Special Prices", "woocommerce-booking" ).'"></a> </td>
                            						 </tr>';
                   }
				}
								
			?>
			<div id="special_booking_page" style="display:none;">
				<table class='form-table'>
					<tr id="special_booking_price_row">
						<th width="35%">
							<label for="special_booking_price_block"><?php _e( 'Select Days:', 'woocommerce-booking' ); ?></label>
						</th>
						<td width="5%">&nbsp;</td>
						<th style="width:175px;">
							<script type="text/javascript">
								jQuery(document).ready(function() {
									var formats = ["d.m.y", "d-mm-yyyy", "MM d, yy"];
									jQuery("#special_booking_date").datepick({dateFormat: formats[1], 
																			  monthsToShow: 1, 
																			  showTrigger: '#calImg', 
																			  onSelect: function ()
																				{
																					if(jQuery("#special_booking_date").val() != ""){
																						jQuery("#special_booking_weekday").val("");
																						jQuery("#special_booking_weekday").attr('disabled','disabled');
																					}
																					else{
																						jQuery("#special_booking_weekday").removeAttr('disabled');
																					}
																					//Then add this line to trigger the change event manually.
																					jQuery(this).change();
																				}
																			});
								});
							</script>
							<label for="special_booking_date"><?php _e( 'Select Date:', 'woocommerce-booking' ); ?></label>
						</th>
						<td width="1%">&nbsp;</td>
						<th width="20%">
							<label for="special_booking_price"><?php _e( 'Price:', 'woocommerce-booking' ); ?></label>
						</th>
					</tr>
					<tr>
						<td>
							<select name="special_booking_weekday" id="special_booking_weekday">
								<option value=""><?php _e( 'Select Weekday', 'woocommerce-booking' ); ?></option>
								<?php foreach ( $weekdays as $n => $day_name ) { ?>
									<option value="<?php _e( $n, 'woocommerce-booking' ); ?>"><?php _e( $day_name, 'woocommerce-booking' ); ?></option>
								<?php } ?>
							</select>
							
							<img class="help_tip" width="16" height="16" data-tip="<?php _e( 'Select Weekday to set Price for', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png"/>
						</td>
						<td width="5%">&nbsp;</td>
						<td>
							<input type="text" name="special_booking_date" id="special_booking_date" readonly style="background-color: white;width: 100px;"> 
							<img class="help_tip" width="16" height="16" data-tip="<?php _e( 'Select Date to set Price for', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png"/>
						</td>
						<td width="1%">&nbsp;</td>
						<td>
							<input type="text" name="special_booking_price" id="special_booking_price" style="width: 75px;"> 
							<img class="help_tip" width="16" height="16" data-tip="<?php _e( 'Set price for the selected day / date', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png"/>
						</td>
					</tr>
					
					<tr>
						<td>
							<input type="button" class="button-primary" value="<?php _e( 'Add Special Price', 'woocommerce-booking' ); ?>" id="save_special_booking" onclick="bkap_save_special_booking()"></input>
							<input type="button" class="button-primary" value="Cancel" id="cancel_special_booking" onclick="bkap_cancel_special_booking_update()" style="display:none;"></input>
						<td colspan="4"><input type="hidden" name="special_booking_id" id="special_booking_id"></td>
					</tr>					

				</table>
				
				<div id="special_booking_price_table">
					<p>
					<?php _e( 'Special Booking Prices', 'woocommerce-booking' ); ?>
					<?php print('<a style="float:right;" href="javascript:void(0);" id="'.$post->ID.'" class="delete_all_special_booking"> Delete All Special Prices </a>');	?>	
					<table class='wp-list-table widefat fixed posts' cellspacing='0' id='list_special_bookings'>
						<tr>
							<th> <?php _e( 'Day / Date', 'woocommerce-booking' ); ?> </th>
							<th> <?php _e( 'Price',      'woocommerce-booking' ); ?></th>
							<th> <?php _e( 'Actions',    'woocommerce-booking' ); ?> </th> 
						</tr>
						<?php 
						if ( isset( $special_price_str ) ) { 
							echo $special_price_str;
						}
						?>
					</table>
					</p>
				</div>	
				</div>
							
				<script type="text/javascript">
				//******* This function will save the created weekday block on the admin product page.  ***********************************
				function bkap_save_special_booking() {
					if ( jQuery("#special_booking_weekday").val() == "" && jQuery("#special_booking_date").val() == "" ) {
						alert ('<?php _e( "Please select weekday or date for special pricing", "woocommerce-booking" ); ?>');
						return;
					}
					
					if ( jQuery("#special_booking_price").val() == "" ) {
						alert ('<?php _e( "Price cannot be blank." , "woocommerce-booking" ); ?>');
						return;
					}
					
					var data = {
							post_id: "<?php echo $post->ID ?>", 
							booking_weekday: jQuery("#special_booking_weekday").val(),
							weekday_date: jQuery("#special_booking_date").val(),
							price: jQuery("#special_booking_price").val(),
							special_booking_id : jQuery("#special_booking_id").val(),
							action: "bkap_save_special_booking_price"
							};
														
					jQuery.post("<?php echo get_admin_url() ?>admin-ajax.php", data, function(response) { 
						
						var special_booking_id = jQuery("#special_booking_id").val();
						var special_booking_weekday = jQuery("#special_booking_weekday option:selected").text();
						var special_booking_weekday_value = jQuery("#special_booking_weekday").val();
						var special_booking_date = jQuery("#special_booking_date").val();
						var special_booking_price = jQuery("#special_booking_price").val();
						
						if( special_booking_id != "" ){ // Update new values
							/*var updated_row = document.getElementById("row_"+special_booking_id);
							var tds = updated_row.cells;
							
							if(special_booking_weekday != "Select Weekday"){ tds[0].innerHTML = special_booking_weekday; }
							else if(special_booking_date != ""){ 
								var formated_date_arr = special_booking_date.split("-");
								tds[0].innerHTML = formated_date_arr[2]+"-"+formated_date_arr[1]+"-"+formated_date_arr[0];
							}
							tds[1].innerHTML = "<?php echo $currency_symbol; ?>" + jQuery("#special_booking_price").val();*/
							
							jQuery("table#list_special_bookings").html("<tr><th> <?php _e( 'Day/Date', 'woocommerce-booking' ); ?> </th><th> <?php _e( 'Price', 'woocommerce-booking' ); ?></th><th> <?php _e( 'Actions', 'woocommerce-booking' ); ?> </th></tr>"+response);
							
						}
						else{ // Add new row 
							var table = document.getElementById("list_special_bookings");
							var row = table.insertRow(-1);
							var insert_id = <?php echo $booking_special_price_cnt; ?>;
							var row_id = "row_"+insert_id;
							row.id = row_id;
							var formated_date = "";
							
							var cell1 = row.insertCell(0);
							if(special_booking_weekday != "" && special_booking_weekday != "Select Weekday"){ cell1.innerHTML = special_booking_weekday; }
							else if(special_booking_date != ""){ 
								var formated_date_arr = special_booking_date.split("-");
								formated_date = formated_date_arr[2]+"-"+formated_date_arr[1]+"-"+formated_date_arr[0]; 
								cell1.innerHTML = formated_date;
							}
				
							var cell2 = row.insertCell(1);
							cell2.innerHTML = "<?php echo $currency_symbol; ?>" + special_booking_price;
														
							var edit_id = insert_id + "&" + special_booking_weekday_value + "&" + formated_date + "&" + special_booking_price;
							var edit_data = "<a href=\"javascript:void(0);\" id=\""+edit_id+"\" class=\"special_booking_edit_block\"> <img src=\"<?php echo plugins_url() ?>/woocommerce-booking/images/edit.png\" alt=\"Edit Special Booking\" title=\"Edit Fixed Block\"></a>";
							var delete_data = "<a href=\"javascript:void(0);\" id=\""+insert_id+"\" class=\"special_booking_delete_block\"> <img src=\"<?php echo plugins_url() ?>/woocommerce-booking/images/delete.png\" alt=\"Delete Special Booking\" title=\"Delete Fixed Block\"></a>";
							
							var cell3 = row.insertCell(2);
							cell3.innerHTML = edit_data + delete_data;
												
						}
												
						bkap_cancel_special_booking_update();
						
					})								
				}
				
				function bkap_cancel_special_booking_update() {
					jQuery("#special_booking_weekday").val("");
					jQuery("#special_booking_date").val("");
					jQuery("#special_booking_price").val("");
					jQuery("#special_booking_id").val("");
					
					jQuery("#special_booking_weekday").removeAttr('disabled');
					jQuery("#special_booking_date").removeAttr('disabled');
					
					jQuery("#save_special_booking").val("Add Special Booking");
					jQuery("#cancel_special_booking").hide();
				}
				
				jQuery(document).ready(function(){
					// To display data in after clicking on edit
					jQuery("table#list_special_bookings").on('click', 'a.special_booking_edit_block',function() {
						
						var passed_id = this.id;
						
						var exploded_id = passed_id.split('&');
						var formated_date = "";
						
						if(exploded_id[2] != ""){
							var formated_date_arr = exploded_id[2].split("-");
							formated_date = formated_date_arr[2]+"-"+formated_date_arr[1]+"-"+formated_date_arr[0]; 
							jQuery("#special_booking_date").removeAttr('disabled');
							jQuery("#special_booking_weekday").attr('disabled','disabled');
						}			
						else{
							jQuery("#special_booking_date").attr('disabled','disabled');
							jQuery("#special_booking_weekday").removeAttr('disabled');
						}						
												
						jQuery("#special_booking_weekday").val(exploded_id[1]);
						jQuery("#special_booking_date").val(formated_date);
						jQuery("#special_booking_price").val(exploded_id[3]);
						jQuery("#special_booking_id").val(exploded_id[0]);
						
						jQuery("#save_special_booking").val("Update Special Booking");
						jQuery("#cancel_special_booking").show();
																											
					});
				});
				
				// To delete selected record
				jQuery("table#list_special_bookings").on('click', 'a.special_booking_delete_block',function() {
					var y = confirm('<?php _e("Are you sure you want to delete this special booking?", "wocommerce-booking"); ?>');
					//alert(y);
					if(y==true) {
						var passed_id = this.id;
						var data = {
							post_id: "<?php echo $post->ID ?>", 
							special_booking_id: passed_id,
							action: 'bkap_delete_special_booking'
						};	
						jQuery.post('<?php echo get_admin_url();?>admin-ajax.php', data, function(response) {
							//alert('Got this from the server: ' + response);
							//jQuery("#row_" + passed_id ).hide();
							jQuery("table#list_special_bookings").html("<tr><th> <?php _e( 'Day/Date', 'woocommerce-booking' ); ?> </th><th> <?php _e( 'Price', 'woocommerce-booking' ); ?></th><th> <?php _e( 'Actions', 'woocommerce-booking' ); ?> </th></tr>"+response);
						});
					}
				});
				
				// Delete All Special Bookings
				jQuery("a.delete_all_special_booking").on('click', function() {
					var y=confirm('<?php _e("Are you sure you want to delete all the special prices?", "woocommerce-booking"); ?>');
					if(y==true) {
						
						var data = {
							post_id: "<?php echo $post->ID ?>", 
							action: "bkap_delete_all_special_booking"
						};
						
						jQuery.ajax({
							url: '<?php echo get_admin_url();?>admin-ajax.php',
							type: "POST",
							data : data,
							beforeSend: function() {
							 //loading	

							},
							success: function(data, textStatus, xhr) {
								jQuery("table#list_special_bookings").html("<tr><th> <?php _e( 'Day/Date', 'woocommerce-booking' ); ?> </th><th> <?php _e( 'Price', 'woocommerce-booking' ); ?></th><th> <?php _e( 'Actions', 'woocommerce-booking' ); ?> </th></tr>");
							},
							error: function(xhr, textStatus, errorThrown) {
							  // error status
							}
						});
					}
				});
				
				jQuery("#special_booking_weekday").on('change', function() {
					if(jQuery("#special_booking_weekday").val() != ""){
						jQuery("#special_booking_date").val("");
						jQuery("#special_booking_date").attr('disabled','disabled');
					}
					else{
						jQuery("#special_booking_date").removeAttr('disabled');
					}
				});
				
				</script>
				
			<?php
			}
			
			/**
			 * This function is used to add/update special booking
			 */
			function bkap_save_special_booking_price(){
				global $wpdb;
				
				$post_id                = $_POST['post_id'];
				$booking_weekday        = $_POST['booking_weekday'];
				$weekday_date           = $_POST['weekday_date'];
				$price                  = $_POST['price'];
				$special_booking_id     = $_POST['special_booking_id'];
				
				$booking_special_prices = get_post_meta( $post_id, 'booking_special_price', true );
				
				if ( $special_booking_id != "" ){ // Update existing record
					$cnt = $special_booking_id;
				}
				else { // Add New record
					
				    if( is_array( $booking_special_prices ) && count( $booking_special_prices ) > 0 ){
						$cnt = count( $booking_special_prices );
					}else{
					    $cnt = 0; 
					}
				}
				
				if ( $weekday_date != "" ){
					list( $day, $month, $year ) = explode( "-", $weekday_date );
					$weekday_date = date( "Y-m-d", mktime( 0, 0, 0, $month, $day, $year ) );	
				}
				
				$booking_special_prices[ $cnt ]['booking_special_weekday']  = $booking_weekday;
				$booking_special_prices[ $cnt ]['booking_special_date']     = $weekday_date;
				$booking_special_prices[ $cnt ]['booking_special_price']    = $price;
				//echo "<pre>"; print_r($booking_special_prices); echo "</pre>";
				update_post_meta( $post_id, 'booking_special_price', $booking_special_prices );
				
				$weekdays                   = bkap_get_book_arrays( 'weekdays' );
				$currency_symbol            = get_woocommerce_currency_symbol();
				$special_price_str          = "";	
				
				$booking_special_price_cnt  = count( $booking_special_prices );
				
				if ( is_array( $booking_special_prices ) && $booking_special_price_cnt > 0 ){ 
					
				    foreach ( $booking_special_prices as $key => $value ) { 
						$special_price_str .= '<tr id="row_'.$key.'">';
						
						if( $value['booking_special_weekday'] != "" ){
							$weekday_value = $value['booking_special_weekday'];
							$special_price_str .= '<td>'.$weekdays[$weekday_value].'</td>';
						}else if ( $value['booking_special_date'] != "" ){
							$special_price_str .= '<td>'.$value['booking_special_date'].'</td>';
						}else{
							$special_price_str .= '<td>&nbsp;</td>';
						}	
						
						$special_price_str    .=  '<td>'.$currency_symbol . $value['booking_special_price'].'</td>
                            						<td> <a href="javascript:void(0);" id="'.$key.'&'.$value['booking_special_weekday'].'&'.$value['booking_special_date'].'&'.$value['booking_special_price'].'" class="special_booking_edit_block"> <img src="'.plugins_url().'/woocommerce-booking/images/edit.png" alt="Edit Special Booking" title="Edit Special Booking Price"></a>
                            						<a href="javascript:void(0);" id="'.$key.'" class="special_booking_delete_block"> <img src="'.plugins_url().'/woocommerce-booking/images/delete.png" alt="Delete Special Booking Price" title="Delete Special Booking"></a> </td>
                            						</tr>';
					}
				}
				
				echo $special_price_str;
			}
			
			/**
			 * This function is used to delete selected special booking 
			 */
			function bkap_delete_special_booking(){
				global $wpdb;
				
				$post_id                    = $_POST['post_id'];
				$special_booking_id         = $_POST['special_booking_id'];
								
				$booking_special_prices     = get_post_meta( $post_id, 'booking_special_price', true );
				$new_booking_special_prices = array();
				
				foreach ( $booking_special_prices as $key => $value ) { 
					if( $key != $special_booking_id ){
						$new_booking_special_prices[] = $value;
					}
				}
				
				update_post_meta( $post_id, 'booking_special_price', $new_booking_special_prices );
				
				$weekdays                   = bkap_get_book_arrays('weekdays');
				$currency_symbol            = get_woocommerce_currency_symbol();
				$special_price_str          = "";	
				
				$booking_special_price_cnt  = count( $new_booking_special_prices );
				
				if ( is_array( $booking_special_prices ) && $booking_special_price_cnt > 0 ){ 
					
				    foreach ( $new_booking_special_prices as $key => $value ) { 
						$special_price_str .= '<tr id="row_'.$key.'">';
						
						if( $value['booking_special_weekday'] != "" ){
							$weekday_value      = $value['booking_special_weekday'];
							$special_price_str .= '<td>'.$weekdays[$weekday_value].'</td>';
						}else if ( $value['booking_special_date'] != "" ){
							$special_price_str .= '<td>'.$value['booking_special_date'].'</td>';
						}else{
							$special_price_str .= '<td>&nbsp;</td>';
						}	
						
						$special_price_str .=  '<td>'.$currency_symbol . $value['booking_special_price'].'</td>
                        						<td> <a href="javascript:void(0);" id="'.$key.'&'.$value['booking_special_weekday'].'&'.$value['booking_special_date'].'&'.$value['booking_special_price'].'" class="special_booking_edit_block"> <img src="'.plugins_url().'/woocommerce-booking/images/edit.png" alt="Edit Special Booking" title="Edit Special Booking Price"></a>
                        						<a href="javascript:void(0);" id="'.$key.'" class="special_booking_delete_block"> <img src="'.plugins_url().'/woocommerce-booking/images/delete.png" alt="Delete Special Booking Price" title="Delete Special Booking"></a> </td>
                        						</tr>';
					}
				}
				
				echo $special_price_str;
			}
			
			/**
			 * This function is used to delete all special booking
             */
			
			function bkap_delete_all_special_booking(){
				$post_id = $_POST['post_id'];
				//update_post_meta($post_id, 'booking_special_price', "");
				delete_post_meta( $post_id, 'booking_special_price' ); 
			}
			
			/**
			 * This function is used to display price of product 
			 */
			function special_booking_price_div(){
    			if( has_filter( 'bkap_show_addon_price' ) ) {
    				$show_price = apply_filters( 'bkap_show_addon_price', '' );
                } else {
    			    $show_price = 'show';
                }
				$print_code = '<div id=\"show_addon_price\" name=\"show_addon_price\" class=\"show_addon_price\" style=\"display:'.$show_price.';\"><\/div>';
				
				print('<script type="text/javascript">
					if (jQuery("#show_addon_price").length == 0) {
						document.write("'.$print_code.'");
					} 
					</script>');
			}
			
			/**
			 * This function is used to updated price of product
			 */			
			function special_booking_display_updated_price( $product_id, $booking_date, $variation_id ){ 
			     $_product       = get_product( $product_id );
				 $product_type   = $_product->product_type;
				 
				if ( $product_type == 'grouped' ) {
					$special_price_present = 'NO';
					$currency_symbol       = get_woocommerce_currency_symbol();
					$has_children          = '';
					$price_str             = '';
					$raw_price_str         = '';
					$price_arr             = array();
					
					if ( $_product->has_child() ) {
						$has_children = "yes";
						$child_ids    = $_product->get_children();
					}
					
					$quantity_grp_str  = $_POST['quantity'];
				
					$quantity_array    = explode(",",$quantity_grp_str);
					$i                 = 0;
					
					foreach ( $child_ids as $k => $v ) {
						$final_price              = 0;
						$child_product            = get_product( $v );
						$product_type_child       = $child_product->product_type;
						$product_price            = bkap_common::bkap_get_price( $v, 0, 'simple' );
						$special_booking_price    = $this->get_price( $v, $booking_date );
						
						if ( isset( $special_booking_price ) && $special_booking_price != 0 && $special_booking_price != '' ) {
							$special_price_present   = 'YES';
							$final_price             = $special_booking_price * trim( $quantity_array[ $i ] );
							$raw_price               = $final_price;
							
							if ( function_exists( 'icl_object_id' ) ) {
							    global $woocommerce_wpml;
							    // Multi currency is enabled
							    if ( isset( $woocommerce_wpml->settings[ 'enable_multi_currency' ] ) && $woocommerce_wpml->settings[ 'enable_multi_currency' ] == '2' ) {
							        $raw_price = apply_filters( 'wcml_raw_price_amount', $final_price );
								    $final_price = apply_filters( 'wcml_formatted_price', $final_price );
							    } else {
							        $final_price = wc_price( $final_price );
							    }
							} else {
								$final_price = wc_price( $final_price );
							}
							
							$raw_price_str .= $v . ":" . $raw_price . ",";
							$price_str .= $child_product->get_title() . ": " . $final_price . "<br>";
							
						}else {
							$final_price = $product_price * trim( $quantity_array[$i] );
							$raw_price = $final_price;
							if ( function_exists( 'icl_object_id' ) ) {
							    global $woocommerce_wpml;
							    // Multi currency is enabled
							    if ( isset( $woocommerce_wpml->settings[ 'enable_multi_currency' ] ) && $woocommerce_wpml->settings[ 'enable_multi_currency' ] == '2' ) {
							        $raw_price = apply_filters( 'wcml_raw_price_amount', $final_price );
								    $final_price = apply_filters( 'wcml_formatted_price', $final_price );
							    } else {
							        $final_price = wc_price( $final_price );
							    }
							} else {
								$final_price = wc_price( $final_price );
							}
							
							$raw_price_str .= $v . ":" . $raw_price . ",";
							$price_str .= $child_product->get_title() . ": " . $final_price . "<br>";
						}
						$i++;
					}
					if ( isset( $price_str ) && $price_str != '' ) {
						$special_booking_price = $price_str;
						
						if ( $special_price_present == 'YES' ) {
							$_POST['special_booking_price'] = $special_booking_price;
							$_POST[ 'grouped_raw_price' ] = $raw_price_str;
						}
					}
				}else {
					$special_booking_price = $this->get_price($product_id,$booking_date);
					if ( isset( $special_booking_price ) && $special_booking_price != '' && $special_booking_price != 0 ) {
						$_POST['special_booking_price'] = $special_booking_price;
					}
				}		
			}
			
			function get_price( $product_id, $booking_date ) {
				$weekdays               = bkap_get_book_arrays( 'weekdays' );
				$booking_special_prices = get_post_meta( $product_id, 'booking_special_price', true );
				$special_booking_price  = 0;
				
				if ( is_array( $booking_special_prices ) && count( $booking_special_prices ) > 0 ){
					
				    foreach ( $booking_special_prices as $key => $values ){
						list($year, $month, $day) =   explode( "-", $booking_date );
						$booking_day              =   date( "l", mktime( 0, 0, 0, $month, $day, $year ) );
						
						if ( isset( $values['booking_special_weekday'] ) &&  isset( $weekdays[ $values['booking_special_weekday'] ] ) && $booking_day == $weekdays[ $values['booking_special_weekday'] ] ){
							$special_booking_price = $values['booking_special_price'];
						}
					}
					
					foreach ( $booking_special_prices as $key => $values ){
						list( $year, $month, $day ) = explode( "-", $booking_date );
						
						if ( $values['booking_special_date'] == $booking_date ){
							$special_booking_price = $values['booking_special_price'];
						}
					}
						
				}
				return $special_booking_price;
			}
			
			function add_cart_item( $cart_item ) {
		
				global $wpdb;
				
				$product_type = 'simple';
				if ( $cart_item['variation_id'] != '' ) {
					$product_type = 'variable';
				}
				$price = bkap_common::bkap_get_price( $cart_item['product_id'], $cart_item['variation_id'], $product_type );
				// Adjust price if addons are set
				if ( isset( $cart_item['bkap_booking'] ) ) :
					$extra_cost = 0;
					
					foreach ( $cart_item['bkap_booking'] as $addon ) :
						
						if ( isset( $addon['price'] ) && $addon['price'] > 0 ) {
							$extra_cost += $addon['price'];
						}
					endforeach;
					
					$extra_cost = $extra_cost - $price;
					$cart_item['data']->adjust_price( $extra_cost );
				endif;
		
				return $cart_item;
			}
			
			/**
			 * This function is used to updated price of product for Multiple dates
			 */
			
			function bkap_special_booking_show_multiple_updated_price($product_id,$product_type,$variation_id_to_fetch,$checkin_date,$checkout_date,$currency_selected){
				$global_settings    = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
				$number_of_days     = strtotime( $checkout_date ) - strtotime( $checkin_date );
				$number             = floor( $number_of_days/( 60 * 60 * 24 ) );
				
				if ( $number == 0 ) {
					$number = 1;
				}
				$booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
				
				if ( function_exists( 'is_bkap_rental_active' ) && is_bkap_rental_active() && ( isset( $booking_settings ) && isset( $booking_settings['booking_charge_per_day'] ) &&  $booking_settings['booking_charge_per_day'] == 'on' ) ){
					if ( strtotime( $checkout_date ) > strtotime( $checkin_date) ) {
						$number++;
					}
				}
				$booking_special_prices = get_post_meta($product_id, 'booking_special_price', true);
					
				if ( isset( $_POST['price'] ) && ( isset( $booking_settings['booking_fixed_block_enable'] ) && $booking_settings['booking_fixed_block_enable'] == 'yes' ) ) {
					$price = $_POST['price'];
					// Divide the block price by number of nights so per day price is calculated for the block and then added up to form the total
					if( is_array( $booking_special_prices ) && count( $booking_special_prices) > 0 ){
						$price = $price / $number;
					}
					$number = 1;
				}else if ( isset( $_POST['price'] ) && ( isset( $booking_settings['booking_block_price_enable'] ) && $booking_settings['booking_block_price_enable'] == 'yes' ) ) { 
					$str_pos = strpos($_POST['price'],'-');
					
					if ( isset( $str_pos ) && $str_pos != '' ) {
						$price_type   =  explode( "-", $_POST['price'] );
						$price        =  $price_type[0]/$number;
					}else {
						$price = $_POST['price'];
					}
				}else{
					$price = bkap_common::bkap_get_price( $product_id, $variation_id_to_fetch, $product_type );
				}
				
				$weekdays                           = bkap_get_book_arrays( 'weekdays' );
				
				$special_multiple_day_booking_price = 0;
				$startDate                          = $checkin_date;
				
				if ( is_array( $booking_special_prices ) && count( $booking_special_prices ) > 0 ){
					
					// If rental is active, then we have chk price for all selected days
					if ( function_exists('is_bkap_rental_active') && is_bkap_rental_active() && ( isset( $booking_settings ) && isset( $booking_settings['booking_charge_per_day'] ) && $booking_settings['booking_charge_per_day'] == 'on' ) ){ 
						$endDate = strtotime( $checkout_date ) + ( 60 * 24 * 24 );
					}else { 
						$endDate = strtotime( $checkout_date ); 
					}
					
					while ( strtotime( $startDate ) < $endDate ) {
						$special_price = $price; 
						
						foreach ( $booking_special_prices as $key => $values ){
							list( $year, $month, $day ) = explode( "-", $startDate );
							$startDate1 =  date("l", mktime(0, 0, 0, $month, $day, $year));
							
							if ( isset( $values['booking_special_weekday'] ) &&  isset( $weekdays[ $values['booking_special_weekday'] ] ) && $startDate1 == $weekdays[ $values['booking_special_weekday'] ] ){
								$special_price = $values['booking_special_price'];
							}
						}
						foreach( $booking_special_prices as $key => $values ){
							//list($year, $month, $day) = explode("-", $startDate);
							if( $values['booking_special_date'] == $startDate ){
								$special_price = $values['booking_special_price'];
							}
						}
						//echo $special_multiple_day_booking_price."------";
						$special_multiple_day_booking_price  += $special_price;
						$startDate                            = date ( "Y-m-d", strtotime( "+1 day", strtotime( $startDate ) ) );
					}
										
					// Don't divide price by no. of days if Fixed block 
					if ( ( isset( $booking_settings['booking_fixed_block_enable'] ) && $booking_settings['booking_fixed_block_enable'] == 'yes' ) ){  
						$special_multiple_day_booking_price = $special_multiple_day_booking_price;
					}else{ 
						$special_multiple_day_booking_price = $special_multiple_day_booking_price / $number;
					}
					
					$_SESSION['special_multiple_day_booking_price']    = $_POST['special_multiple_day_booking_price']  = $special_multiple_day_booking_price;
					$_SESSION['booking_multiple_days_count']           = $_POST['booking_multiple_days_count']         = $number;
				}
				else {	
					$special_multiple_day_booking_price                = $price;
					$_SESSION['special_multiple_day_booking_price']    = $_SESSION['booking_multiple_days_count'] = '';
				}		 
					
				if ( function_exists('is_bkap_deposits_active') && is_bkap_deposits_active() || function_exists('is_bkap_seasonal_active') && is_bkap_seasonal_active() ) {
					
				    if ( isset( $special_multiple_day_booking_price ) && $special_multiple_day_booking_price != '' ) {
						$_POST['price'] = $special_multiple_day_booking_price;
					}
					else {
						$error_message = __( "Please select an option.", "woocommerce-booking" );
						print( 'jQuery( "#show_time_slot" ).html( "' . addslashes( $error_message ) . '");' );
						die();
					}
				}else{
					// the filter is applied to make the plugin wpml multi currency compatible
					// as a prt of that, we need to ensure that the final price is sent to the filter
					if ( isset( $number ) && $number > 1 ) {
						$special_multiple_day_booking_price = $special_multiple_day_booking_price * $number;
					}
					if ( isset( $_POST['quantity'] ) && $_POST['quantity'] > 0 ) {
						$special_multiple_day_booking_price = $special_multiple_day_booking_price * $_POST['quantity'];
					}
					if ( isset( $global_settings->enable_rounding ) && $global_settings->enable_rounding == "on" ) {
						$special_multiple_day_booking_price = round($special_multiple_day_booking_price);
					}
					
					// Save the actual Bookable amount, as a raw amount
					// If Multi currency is enabled, convert the amount before saving it
					$total_price = $special_multiple_day_booking_price;
					if ( function_exists( 'icl_object_id' ) ) {
					    $total_price = apply_filters( 'wcml_raw_price_amount', $special_multiple_day_booking_price );
					}
					print( 'jQuery( "#total_price_calculated" ).val(' . $total_price . ');' );
					// save the price in a hidden field to be used later
					print( 'jQuery( "#bkap_price_charged" ).val(' . $total_price . ');' );
					
					// format the price	
					$formatted_price = wc_price( $special_multiple_day_booking_price );
					if ( function_exists('icl_object_id') ) {
                        global $woocommerce_wpml;
					    
					    // Multi currency is enabled
					    if ( isset( $woocommerce_wpml->settings[ 'enable_multi_currency' ] ) && $woocommerce_wpml->settings[ 'enable_multi_currency' ] == '2' ) {
                            $formatted_price = apply_filters( 'wcml_formatted_price', $special_multiple_day_booking_price );
					    }
					} 
					
					// display the price on the front end product page
					print( 'jQuery( "#show_time_slot" ).html( "' . addslashes( $formatted_price ) . '");' );
					die();
				}
			}
						
		} // EOF Class
	} // EOF if class exist
		
	$bkap_special_booking_price = new bkap_special_booking_price();
?>