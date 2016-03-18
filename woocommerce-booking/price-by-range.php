<?php 
 
if( session_id() === '' ){
    //session has not started
    session_start();
} 
	/**
	 * Localisation
	 **/
	load_plugin_textdomain( 'bkap_block_booking_price', false, dirname( plugin_basename( __FILE__ ) ) . '/' );

	/**
	 * bkap_deposits class
	 **/
	if ( !class_exists( 'bkap_block_booking_price' ) ) {

		class bkap_block_booking_price {

			public function __construct() {		

				$this->variable_block_price = 0;
				// used to add new settings on the product page booking box
				add_action( 'bkap_after_listing_enabled',               array( &$this, 'bkap_price_range_show_field_settings' ),        10, 1 );
				add_action( 'bkap_add_tabs',                            array( &$this, 'price_by_range_tab' ),                          10, 1 );
				add_action( 'admin_init',                               array( &$this, 'bkap_load_ajax_price_range_admin' ) );
				add_action( 'init',                                     array( &$this, 'bkap_load_ajax_price_range' ) );
				// Save the product settings for variable blocks
				add_filter( 'bkap_save_product_settings',               array( &$this, 'bkap_price_range_product_settings_save' ),      10, 2 );
				// Display the variable block price on the product page
				add_action( 'bkap_display_multiple_day_updated_price',  array( &$this, 'bkap_price_range_show_updated_price' ),         5, 6 );
				add_action( 'woocommerce_before_add_to_cart_button',    array( &$this, 'bkap_price_range_booking_after_add_to_cart' ) );	
				// Copy the exisiting variable blocks to the new product when the product is duplicated
				add_action( 'bkap_product_addon_duplicate',             array( &$this, 'price_range_product_duplicate' ),               10, 2 );
			
			}
			
           /**
            *  This function is used to load ajax functions 
            *  required by price by range of days booking.
            */
			
			function bkap_load_ajax_price_range_admin() {
			    add_action( 'wp_ajax_bkap_save_booking_block_price',           array( &$this, 'bkap_save_booking_block_price' ) );
			    add_action( 'wp_ajax_bkap_delete_price_block',                 array( &$this, 'bkap_delete_price_block' ) );
			    add_action( 'wp_ajax_bkap_delete_all_price_blocks',            array( &$this, 'bkap_delete_all_price_blocks' ) );
			}
			
			function bkap_load_ajax_price_range() {
				
			    if ( !is_user_logged_in() ) {	
					add_action( 'wp_ajax_nopriv_bkap_show_price_div',              array( &$this, 'bkap_show_price_div' ) );
				}  
				else {
					add_action( 'wp_ajax_bkap_show_price_div',                     array( &$this, 'bkap_show_price_div' ) );
				}
			}
			
			/**
			 * This function will ensure that when a new product 
			 * is created as a duplicate of an existing one, then 
			 * the variable blocks are also copied alongwith the 
			 * other settings.
			 */
			function price_range_product_duplicate( $new_id, $old_id ) {
				global $wpdb;
				$product        = get_product( $old_id );
				$product_type   = $product->product_type;
				
				if( $product_type == 'variable' ) {
					$product_attributes    =   get_post_meta( $old_id, '_product_attributes', true );
					$query                 =   "SELECT * FROM `".$wpdb->prefix."booking_block_price_meta`
								                WHERE post_id = '".$old_id."'";
					$results               =   $wpdb->get_results( $query );
					$var                   =   "";
					$i                     =   0;
					
					foreach ( $results as $key => $value ) {
						$insert_booking_block_price   =   "INSERT INTO {$wpdb->prefix}booking_block_price_meta
														  (post_id,minimum_number_of_days,maximum_number_of_days,price_per_day,fixed_price)
    													  VALUE(
														  '{$new_id}',
														  '{$value->minimum_number_of_days}',
														  '{$value->maximum_number_of_days}',
														  '{$value->price_per_day}',
														  '{$value->fixed_price}')";
						$wpdb->query($insert_booking_block_price);
						
						$lastid                       =   $wpdb->insert_id;
				
						$query_attribute              =   "SELECT * FROM `".$wpdb->prefix."booking_block_price_attribute_meta`
                										  WHERE post_id = '".$old_id."'
                										  AND block_id = '".$value->id."'";
						$results_attribute            =   $wpdb->get_results( $query_attribute );
						
						foreach( $results_attribute as $k => $v ) {
							 $insert_booking_block_price_attribute   =   "INSERT INTO {$wpdb->prefix}booking_block_price_attribute_meta
        																 (post_id,block_id,attribute_id,meta_value)
        																 VALUE(
        																 %d,
        																 %d,
        																 %d,
        																 %s)";
    						 $wpdb->query( $wpdb->prepare( $insert_booking_block_price_attribute, $new_id, $lastid , $v->attribute_id, $v->meta_value ) );
						}
					}
				}else if ( $product_type == 'simple' ) {
					$query     =   "SELECT * FROM `".$wpdb->prefix."booking_block_price_meta`
								    WHERE post_id = '".$old_id."'";
					$results   =   $wpdb->get_results( $query );
					
					foreach ( $results as $key => $value ) {
						$insert_booking_block_price   =   "INSERT INTO {$wpdb->prefix}booking_block_price_meta
        													(post_id,minimum_number_of_days,maximum_number_of_days,price_per_day,fixed_price)
        													VALUE(
        													'{$new_id}',
        													'{$value->minimum_number_of_days}',
        													'{$value->maximum_number_of_days}',
        													'{$value->price_per_day}',
        													'{$value->fixed_price}')";
						$wpdb->query( $insert_booking_block_price );
					}
				}
			}
                        
           /**
            *This function set the hidden fields on the frontend
            * product page if the price by range of days is 
            * enabled from admin side. 
            */
			function bkap_price_range_booking_after_add_to_cart() {	
				global $post, $wpdb;
				
 				$booking_settings = get_post_meta( $post->ID, 'woocommerce_booking_settings', true );

				if ( isset( $booking_settings['booking_block_price_enable'] ) && $booking_settings['booking_block_price_enable'] == 'yes' ) {
					echo ' <input type="hidden" id="block_option_enabled_price"  name="block_option_enabled_price" value="on"/>';
				//	echo ' <input type="hidden" id="block_variable_option_price"  name="block_variable_option_price" value=""/>';
					echo ' <input type="hidden" id="wapbk_variation_value"  name="wapbk_variation_value" value=""/>';
				} else {
					echo ' <input type="hidden" id="block_option_enabled_price"  name="block_option_enabled_price" value=""/>';
				}

			}
			
           /**
            * This function display the field settings of the price 
            * by range of the days on product admin page
            */
			function bkap_show_price_div() {
				$div = '
				';
				echo $div;
				die();
			}
                        
			function price_by_range_tab( $product_id ) {
				?>
				<li><a id="block_booking_price"> <?php _e( 'Price by range of days', 'woocommerce-booking' ); ?> </a></li>
				<?php 
			}
			
           /**
            * This function add the price by range of days table on the admin side.
            * It allows to create blocks on the admin product page.
            */
			function bkap_price_range_show_field_settings( $product_id ) {
				global $post, $wpdb;
				?>
				<div id="block_booking_price_page" style="display:none;">
				<table class='form-table'>
					<tr id="block_price">
						<th>
							<label for="booking_block_price"><?php _e( 'Enable Price by range of days:', 'woocommerce-booking' ); ?></label>
						</th>
						<td>
							<?php 
							$booking_settings    = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
							$enabled_s_pricing   = "";
							
							if ( isset( $booking_settings['booking_block_price_enable'] ) ){
								$product_enable_block_price = $booking_settings['booking_block_price_enable'];
							} else {
								$product_enable_block_price = '';
							}
							
							$add_block           = "none";
							$range_button_status = "disabled";
							
							if ( $product_enable_block_price == 'yes' ) {
								$enabled_s_pricing      = "checked";	
								$add_block              = "block";
								$range_button_status    = "";
							}
							?>
							<input type="checkbox" name="booking_block_price_enable" id="booking_block_price_enable" value="yes" <?php echo $enabled_s_pricing;?>></input>
							<img class="help_tip" width="16" height="16" data-tip="<?php _e( 'Enable to charge by range of days.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png"/>
						</td>
					</tr>
				</table>
				<script type="text/javascript">
					jQuery("#booking_block_price_enable").change(function() {
						if (jQuery("#booking_block_price_enable").attr("checked")) {
							jQuery("#add_another_block_price").removeAttr("disabled");
						}
						else {
							jQuery("#add_another_block_price").attr("disabled", "disabled");
							jQuery("#add_block_price").hide();
						}
					});
				</script>
				<p>
				<div id="add_block_price_button">
				<input type="button" class="button-primary" value="<?php _e( 'Add New Range', 'woocommerce-booking' ); ?>" id="add_another_block_price" onclick="bkap_show_price_div()" <?php echo $range_button_status; ?>>
				</div>
				</p>
				<div id="add_block_price" style="display: <?php echo $add_block; ?>;">
				 
				<table class="form-table">
				<tr>
				 	<label for="add_block_label"> <?php _e( 'Enter a Range:', 'woocommerce-booking' ); ?></label>
				</tr>
				<tr>
					<table>
					<input type="hidden" name="id_booking_block" value=""/>
					<tr>
					<?php 
					$product_attributes    = get_post_meta( $product_id, '_product_attributes', true );
					$i                     = 1;
					if($product_attributes != '') {
						foreach( $product_attributes as $key => $value ) {
							print('<td><label for="attribute_'.$i.'_name">'.__( $value["name"] , "woocommerce-booking" ).'</label></td>');
							$i++;
						}
					}
					?>
					 <td><label for="number_of_start_days"> <?php _e( 'Minimum Number of days: ', 'woocommerce-booking' ); ?></label></td>
						<td><label for="number_of_end_days"> <?php _e( 'Maximum Number of days: ', 'woocommerce-booking' ); ?> </label></td>
						<td><label for="price_per_day" > <?php _e( 'Price Per Day: ', 'woocommerce-booking' ); ?></label><br></td>
						<td><label for="fixed_price" ><?php _e( 'Fixed Price: ', 'woocommerce-booking' ); ?></label><br></td>
					</tr>
					<tr>
					<?php 
					$i     = 1;
					$j     = 1;
					$div   = '';
					if( $product_attributes != '' ) {
						
    					foreach( $product_attributes as $key => $value ) {
    						if( isset( $_POST['attributes'] ) &&  $_POST['attributes'] != '' ) {
    							$attributes = explode( "|", $_POST['attributes'] );
    							array_pop( $attributes );
    						
    							$div .= '<td><select name="attribute_'.$i.'" id="attribute_'.$i.'" value="">';
    							$value_array = explode('|',$value['value']);
    						
    							foreach( $value_array as $k => $v ) {	
    						
    								if( substr( $v, -1, 1 ) === ' ' ) {
    									$result = rtrim( $v, " " );
    						
    								} else if ( substr( $v, 0, 1 ) === ' ' ) {
    									$result = preg_replace( "/ /", "", $v, 1 );
    								} else {
    									$result =  $v;
    								}
    								
    								$attr_value = trim( $result );
    								
    								if ( array_key_exists( $j-1, $attributes ) && $result == $attributes[ $j-1 ] ) {
    									$div .= '<option name="attribute_'.$i.'_'.$j.'" id="attribute_'.$i.'_'.$j.'" value="'.htmlspecialchars( $attr_value ).'" selected="selected">'.$result.'</option>';
    								} else {
    									$div .= '<option name="attribute_'.$i.'_'.$j.'" id="attribute_'.$i.'_'.$j.'" value="'.htmlspecialchars( $attr_value ).'">'.$result.'</option>';
    								}
    							}
    							
    							$div .= '</select></td>';
    						} else {
    							$div         .=  '<td><select name="attribute_'.$i.'" id="attribute_'.$i.'" value="">';
    							$value_array  =  explode( '|', $value['value'] );
    							$j            =  1;
    							
    							foreach( $value_array as $k => $v ) {	
    								$attr_value  = trim( $v );
    								$div        .= '<option name="attribute_'.$i.'_'.$j.'" id="attribute_'.$i.'_'.$j.'" value="'.htmlspecialchars( $attr_value ).'">'.$v.'</option>';
    							}
    							$div .= '</select></td>';
    						}
    						$i++;
    						$j++;
    					}
					}
					
					if ( isset( $_POST["number_of_start_days"] ) &&  $_POST["number_of_start_days"] != '' ) {
						$div .= '<td><input type="text" size="5" id="number_of_start_days" name="number_of_start_days" value="'.$_POST["number_of_start_days"].'"></input></td>';
					} else {
						$div .= '<td><input type="text" size="5" id="number_of_start_days" name="number_of_start_days" value=""></input></td>';
					}
					
					if ( isset( $_POST["number_of_end_days"] ) &&  $_POST["number_of_end_days"] != '' ) {
						$div .= '<td><input type="text"  size="5" id="number_of_end_days" name="number_of_end_days" size="10" value="'.$_POST["number_of_end_days"].'"></input></td>';
					} else {
						$div .= '<td><input type="text"  size="5" id="number_of_end_days" name="number_of_end_days" size="10" value=""></input></td>';
					}
					
					if ( isset( $_POST["price_per_day"] ) &&  $_POST["price_per_day"] != '' ) {
						$div .= '<td><input type="text" size="5" id="price_per_day" name="price_per_day" size="10" value="'.$_POST["price_per_day"].'"></input><br></td>';
					} else {
						$div .= '<td><input type="text"  size="5" id="price_per_day" name="price_per_day" size="10" value=""></input><br></td>';
					}
					
					if ( isset( $_POST["fixed_price"] ) &&  $_POST["fixed_price"] != '' ) {
						$div .= '<td><input type="text"  size="5" id="fixed_price" name="fixed_price" size="10" value="'.$_POST["fixed_price"].'"></input><br></td>';
					} else {
						$div .= '<td><input type="text"  size="5" id="fixed_price" name="fixed_price" size="10" value=""></input><br></td>';
					}
					
					if( isset( $_POST['id'] ) && $_POST['id'] != '' ) {
						$div .= '<input type="hidden" id="table_id" name="table_id" value="'.$_POST['id'].'"></input><br>';
					} else {
						$div .= '<input type="hidden" id="table_id" name="table_id"></input><br>';
					}
					
					if( $product_attributes != '' ) {
						$div .= '<input type="hidden" id="attribute_count" name="attribute_count" value="'.count( $product_attributes ).'"></input></td>';
					}
					else {
						$div .= '<input type="hidden" id="attribute_count" name="attribute_count" value="0"></input></td>';
					}
					
					print( $div );
					?>
					</tr>
					<tr>
						<td>
							<input type="button" class="button-primary" value="Save Range" id="save_another" onclick="bkap_save_booking_block_price()"></input>
						</td>
						<td>
							<input type="button" class="button-primary" value="Close" id="close_div" onclick="bkap_close_booking_block()"></input>
						</td>
					</tr>					
					</table>	
				</tr>
				</table>
				</div>
				<?php 
				$this->bkap_booking_block_price_table(); 
				$currency_symbol = get_woocommerce_currency_symbol();
				?>
				<script type="text/javascript">
				function escapeHTML(text) {
					var map = {
						'&': '&amp;',
						'<': '&lt;',
						'>': '&gt;',
						'"': '&quot;',
						"'": '&#039;'
					};
					return text.replace(/[&<>"']/g, function(m) { return map[m]; });
				}
				</script>
				<?php 
				print('<script type="text/javascript">
                	//***************************************
                    // This function will save the created price by range of days block on the admin product page.
                    //***************************************
				function bkap_save_booking_block_price() {
					if (jQuery("#price_per_day").val() == "" && jQuery("#fixed_price").val() == ""){
						alert("'. __( "Both Price cannot be blank.", "woocommerce-booking" ).'");
						return;
					} else if(parseInt(jQuery("#number_of_start_days").val()) == "" || parseInt(jQuery("#number_of_end_days").val()) == ""){
						alert("'. __( "Please enter a valid date range.", "woocommerce-booking" ) .'");
						return;
					} else if(parseInt(jQuery("#number_of_start_days").val()) > parseInt(jQuery("#number_of_end_days").val())){
						alert ("'. __( "Minimum number of days should be less than the Maximum number of days.", "woocommerce-booking" ) .'");
						return;
					}
					var attribute_count = parseInt(jQuery("#attribute_count").val());
					var attributes = "";
					for (i = 1; i <= attribute_count; i++) {
						var attribute_value = (jQuery("#attribute_"+i).val()).trim();
						var block_attribute = attribute_value +"|";
						attributes = attributes + block_attribute;
					}	
					var data = {
							post_id: "'.$post->ID.'",
							attribute: attributes,
							number_of_start_days: jQuery("#number_of_start_days").val(),
							number_of_end_days: jQuery("#number_of_end_days").val(),
							price_per_day: jQuery("#price_per_day").val(),
							fixed_price: jQuery("#fixed_price").val(),
							id: jQuery("#table_id").val(),
							action: "bkap_save_booking_block_price"
							};
	
							jQuery.post("'.get_admin_url().'admin-ajax.php", data, function(response) {
								var num_col = document.getElementById("list_block_price").rows[0].cells.length
								insert_id = response;
								var split_attr = "";
								if (num_col > 5) {
									if (attribute_count > 0) {
										split_attr = attributes.split("|");
									}
								}
								
								if (jQuery("#table_id").val() != "") {
									var row_id = "row_"+insert_id;
						
									var min_days = jQuery("#number_of_start_days").val();
									var max_days = jQuery("#number_of_end_days").val();
									var price_per_day = 0;
									if (jQuery("#price_per_day").val() != "") {
										price_per_day = jQuery("#price_per_day").val();
									}
									var fixed_price = 0;
									if (jQuery("#fixed_price").val() != "") {
										fixed_price = jQuery("#fixed_price").val();
									}
									var table = document.getElementById("list_block_price").rows;
									var x = table[row_id].cells;
									
									var i = 0; 
									var attr_data = "";
									// If it is a variable product then the attributes are the first columns and the attributes need to be included in edit_id
									if (split_attr != "" && split_attr.length > 0) {
										var length = split_attr.length - 1;
										for (i = 0; i < length; i++) {
											x[i].innerHTML = split_attr[i];
											attr_data = split_attr[i] + "&";
										} 	
									}
									attr_data = escapeHTML(attr_data);
									x[i].innerHTML = min_days;
									x[i+1].innerHTML = max_days;
									x[i+2].innerHTML = price_per_day;
									x[i+3].innerHTML = fixed_price;
								
									var edit_id = insert_id + "&" + attribute_count + "&" + '.$post->ID.'+"&" + attr_data + min_days + "&" + max_days + "&" + price_per_day + "&" + fixed_price;
									var edit_data = "<a href=\"javascript:void(0);\" id=\""+edit_id+"\" class=\"edit_block_range\"> <img src=\"'.plugins_url().'/woocommerce-booking/images/edit.png\" alt=\"Edit Price By Range Block\" title=\"Edit Price By Range Block\"></a>";
									var delete_data = "<a href=\"javascript:void(0);\" id=\""+insert_id+"\" class=\"delete_block_range\"> <img src=\"'.plugins_url().'/woocommerce-booking/images/delete.png\" alt=\"Delete Price By Range Block\" title=\"Delete Price By Range Block\"></a>";
									x[i+4].innerHTML = edit_data + delete_data;
								}
								else {
						
									var table = document.getElementById("list_block_price");
									var row = table.insertRow(-1);
									var row_id = "row_"+insert_id;
									row.id = row_id;
									var i = 0;
									var attr_data = "";
									// If it is a variable product then the attributes are the first columns and the attributes need to be included in edit_id
									if (split_attr != "" && split_attr.length > 0) {
										var length = split_attr.length - 1;
										for (i = 0; i < length; i++) {
											var cell0 = row.insertCell(i);
											cell0.innerHTML = split_attr[i];
											attr_data = split_attr[i] + "&";  
										} 	
									}
									attr_data = escapeHTML(attr_data);
									var cell1 = row.insertCell(i);
									cell1.innerHTML = jQuery("#number_of_start_days").val();
						
									var cell2 = row.insertCell(i+1);
									cell2.innerHTML = jQuery("#number_of_end_days").val();
									
									var price_per_day = 0;
									if (jQuery("#price_per_day").val() != "") {
										price_per_day = jQuery("#price_per_day").val();
									}
									
									var cell3 = row.insertCell(i+2);
									
									cell3.innerHTML = price_per_day;
									var fixed_price = 0;
									if (jQuery("#fixed_price").val() != "") {
										fixed_price = jQuery("#fixed_price").val();
									}
									var cell4 = row.insertCell(i+3);
									cell4.innerHTML = fixed_price;
					
									var edit_id = insert_id + "&" + attribute_count + "&" + '.$post->ID.' + "&" + attr_data + jQuery("#number_of_start_days").val() + "&" + jQuery("#number_of_end_days").val() + "&" + price_per_day + "&" + fixed_price;
									var edit_data = "<a href=\"javascript:void(0);\" id=\""+edit_id+"\" class=\"edit_block_range\"> <img src=\"'.plugins_url().'/woocommerce-booking/images/edit.png\" alt=\"Edit Price By Range Block\" title=\"Edit Price By Range Block\"></a>";
						
									var delete_data = "<a href=\"javascript:void(0);\" id=\""+insert_id+"\" class=\"delete_block_range\"> <img src=\"'.plugins_url().'/woocommerce-booking/images/delete.png\" alt=\"Delete Price By Range Block\" title=\"Delete Price By Range Block\"></a>";
									var cell5 = row.insertCell(i+4);
									cell5.innerHTML = edit_data + delete_data;			
								}
						
                                // reset and hide form
								jQuery("#add_block_price").hide();
								jQuery("#number_of_start_days").val("");
								jQuery("#number_of_end_days").val("");
								jQuery("#price_per_day").val("");
								jQuery("#fixed_price").val("");
				                jQuery( "#table_id" ).val("");
                        	});		
					}
                                //*************************************************************
                                // This function calls an ajax function which displays the settings of Price by range feature.
                                //**************************************************************
				function bkap_show_price_div() {
					jQuery("#add_block_price").show();
				}

				</script>');
				?>

				<script type="text/javascript">
                                
                                /******************************************
                                 * This function will hide the creating block div when close button is clicked on admin product page.
                                 ******************************************/
				function bkap_close_booking_block() {
					jQuery("#add_block_price").hide("");
					jQuery("#table_id").val("");

				}
				
				jQuery(document).ready(function() {
					jQuery("table#list_block_price").on('click', '.edit_block_range',function() {
						var passed_id = this.id;
						var exploded_id = passed_id.split('&');
						jQuery("#table_id").val(exploded_id[0]);
						var attribute_count = parseInt(jQuery("#attribute_count").val());
						var attributes = "";
						var n = 3 + parseInt(exploded_id[1]);
						var j = 1;
						for (i = 3; i < n ; i++) {
							var attribute_name = "#attribute_" + j;
							jQuery(attribute_name).val(exploded_id[i]);
							j++;
						}	
			
						jQuery("#number_of_start_days").val(exploded_id[i]);
						jQuery("#number_of_end_days").val(exploded_id[i+1]);
						jQuery("#price_per_day").val(exploded_id[i+2]);
						jQuery("#fixed_price").val(exploded_id[i+3]);
						
						jQuery("#add_block_price").show();
					});

					jQuery("table#list_block_price").on('click','a.delete_block_range',function() {
						var y=confirm('<?php _e( "Are you sure you want to delete this block?", "woocommerce-booking" ); ?>');
						if(y==true){
							var passed_id = this.id;
							var data = {
								details: passed_id,
								action: 'bkap_delete_price_block'
							};
								
							jQuery.post('<?php echo get_admin_url();?>admin-ajax.php', data, function(response) {
						
								jQuery("#row_" + passed_id ).hide();
							});
						}
					});
					jQuery("a.bkap_delete_all_price_blocks").click(function() {
						var y=confirm('<?php _e( "Are you sure you want to delete all the ranges?", "woocommerce-booking" ); ?>');
						if(y==true){
							var passed_id = this.id;
							var data = {
								post_id: passed_id,
								action: "bkap_delete_all_price_blocks"
							};

							jQuery.ajax({
                            url: '<?php echo get_admin_url();?>admin-ajax.php',
                            type: "POST",
                            data : data,
                            beforeSend: function() {
                            },
                            success: function(data, textStatus, xhr) {
								jQuery("table#list_block_price").hide();
								 console.log(data);
                            },
                            error: function(xhr, textStatus, errorThrown) {
                        
                            }
                        });


						}
					});
				});
					
				</script>
				</div>
				<?php 
			}
			
            /**
             * This function are used to delete the created block from the list of the admin product page.
             */
			function bkap_delete_price_block() {
				global $wpdb;
				$sql = "DELETE FROM {$wpdb->prefix}booking_block_price_meta where id = {$_POST['details']}";
 				$wpdb->query( $sql );

				$sql_attribute = "DELETE FROM {$wpdb->prefix}booking_block_price_attribute_meta where block_id = {$_POST['details']}";
				$wpdb->query( $sql_attribute );
				 
				die(); 
			}
                        
            /**
             * This function are used to delete all the created block from the list of the admin product page.
             */
			function bkap_delete_all_price_blocks(){
				global $wpdb;
				$post_id = $_POST[ 'post_id' ];
				
				$table_price_meta = "DELETE FROM `" . $wpdb->prefix . "booking_block_price_meta`
				                        WHERE post_id = %d";
				
				$wpdb->query( $wpdb->prepare( $table_price_meta, $post_id ) );
				
				$table_attribute_meta = "DELETE FROM `". $wpdb->prefix. "booking_block_price_attribute_meta`
				                            WHERE post_id = %d";
				
				$wpdb->query( $wpdb->prepare( $table_attribute_meta, $post_id ) );
								 
				die();

			}
			            
            /**
             * This function is used to register all settings of the block to the database.
             */
			function bkap_save_booking_block_price() {
				global $wpdb;
				
				$post_id    = $_POST['post_id'];
				$id         = $_POST['id'];
				
				if ( isset( $_POST['attribute_count'] ) ) {
					$attribute_count = $_POST['attribute_count']; 
				}
				
				$attributes             = explode( "|", $_POST['attribute'] );
				array_pop( $attributes );
				$minimum_number_of_days = $_POST['number_of_start_days'];
				$maximum_number_of_days = $_POST['number_of_end_days'];
				$price_per_day          = $_POST['price_per_day'];
				$fixed_price            = $_POST['fixed_price'];
				$product_attributes     = get_post_meta( $post_id, '_product_attributes', true );
				$result                 = array();
				
				if ( $post_id != "" && $id == "" ) {
					$insert_booking_block_price    =   "INSERT INTO {$wpdb->prefix}booking_block_price_meta
    													(post_id,minimum_number_of_days,maximum_number_of_days,price_per_day,fixed_price)
    													VALUE(
    													'{$post_id}',
    													'{$minimum_number_of_days}',
    													'{$maximum_number_of_days}',
    													'{$price_per_day}',
    													'{$fixed_price}')";
					$wpdb->query( $insert_booking_block_price );
					// Insert ID needs to be passed back
					$id                    = $wpdb->insert_id;
					$select_id             = 'SELECT MAX(id) as block_id FROM `'.$wpdb->prefix."booking_block_price_meta".'`';
					$results               = $wpdb->get_results($select_id);
					$block_attribute_id    = $results[0]->block_id;
					$i                     = 0;
					if ( $product_attributes !='' ) {
						
					    foreach( $product_attributes as $k => $v )
						{
							$attribute_id                            =   $i+1;
							$meta_value                              =   htmlspecialchars_decode( $attributes[$i] );
							$insert_booking_block_price_attribute    =   "INSERT INTO {$wpdb->prefix}booking_block_price_attribute_meta
    																	(post_id,block_id,attribute_id,meta_value)
    																	VALUE(
    																	'{$post_id}',
    																	'{$block_attribute_id}',
    																	'{$attribute_id}',
    																	'{$meta_value}')";
							$wpdb->query( $insert_booking_block_price_attribute );
							$i++;
						}
					}
				}else {
					$edit_block_price  =   "UPDATE `".$wpdb->prefix."booking_block_price_meta`
    										SET minimum_number_of_days = '".$minimum_number_of_days."',
    										maximum_number_of_days = '".$maximum_number_of_days."',
    										price_per_day = '".$price_per_day."',
    										fixed_price = '".$fixed_price."'
    										WHERE id = '".$id."'";
					$wpdb->query( $edit_block_price );
					$i = 0;
					
					foreach( $product_attributes as $k => $v ) {
						$attribute_id                 =   $i+1;
						$meta_value                   =   htmlspecialchars_decode( $attributes[ $i ] );
						$edit_block_price_attribute   =   "UPDATE `".$wpdb->prefix."booking_block_price_attribute_meta`
                                						  SET meta_value = '".$meta_value."'
                                						  WHERE block_id = '".$id."' AND
                                						  attribute_id = '".$attribute_id."'";
						$wpdb->query( $edit_block_price_attribute );
					}
				}
				echo $id;
				die();
			}
			
			/**
            * This function are used to display the list of the 
            * created price by range of days block on admin product page
            */
			function bkap_booking_block_price_table() {
 				global $post,$wpdb;
 				
 				$currency_symbol = get_woocommerce_currency_symbol();
 				
 				if ( isset( $post ) ) {
					$post_id = $post->ID;
				} else {
					$post_id = $_POST['post_id'];
				}
				
				$product_attributes =   get_post_meta( $post_id, '_product_attributes', true );
				$query              =   "SELECT * FROM `".$wpdb->prefix."booking_block_price_meta`
							             WHERE post_id = %d";
				 
				$results = $wpdb->get_results( $wpdb->prepare( $query,$post_id ) );
		
				$var    =   "";
				$i      =   0;
				
				foreach ( $results as $key => $value ) {
					$var               .=  '<tr id="row_'.$value->id.'">';
					$query_attribute    =  "SELECT * FROM `".$wpdb->prefix."booking_block_price_attribute_meta`
                							WHERE post_id = %d
                							AND block_id = %d";
					 
					$results_attribute =   $wpdb->get_results( $wpdb->prepare( $query_attribute, $post_id, $value->id ) );
					$j                 =   1;
			
					$id                =  '';
					
					foreach( $results_attribute as $k => $v ) {
						$var  .= '<td>'.$v->meta_value.'</td>';
						$id   .= htmlspecialchars( $v->meta_value ) . "&";
						$j++;
					}
					$var .= '<td>'.$value->minimum_number_of_days.'</td>
							<td>'.$value->maximum_number_of_days.'</td>
							<td>'.$currency_symbol.$value->price_per_day.'</td>
							<td>'.$currency_symbol.$value->fixed_price.'</td>
							<td> <a href="javascript:void(0);" id="'.$value->id.'&'.count($product_attributes).'&'.$post_id.'&'.$id.$value->minimum_number_of_days.'&'.$value->maximum_number_of_days.'&'.$value->price_per_day.'&'.$value->fixed_price.'" class="edit_block_range"><img src="'.plugins_url().'/woocommerce-booking/images/edit.png" alt="Edit Block" title="Edit Block"></a>
							<a href="javascript:void(0);" id="'.$value->id.'" class="delete_block_range"> <img src="'.plugins_url().'/woocommerce-booking/images/delete.png" alt="Delete Block" title="Delete Block"></a> </td>
							</tr>';
				}
				?>
				<div id="block_price_booking_table">
					<p>
					<?php _e( 'Booking Days Range', 'woocommerce-booking' ); ?>
					<?php print('<a style="float:right;" href="javascript:void(0);" id="'.$post_id.'" class="bkap_delete_all_price_blocks"> Delete All Ranges</a>');	?>
					<table class='wp-list-table widefat fixed posts' cellspacing='0' id='list_block_price'>
						<tr>
							<?php
							if ( $product_attributes != '' ) {
								foreach ( $product_attributes as $k => $v )
								{?>
									<th> <?php _e( $v["name"], 'woocommerce-booking' ); ?> </th>
								<?php
								}
							}?>
							<th> <?php _e( 'Minimum number of Days', 'woocommerce-booking' ); ?></th>
							<th> <?php _e( 'Maximum number of Days', 'woocommerce-booking' ); ?> </th>
							<th> <?php _e( 'Price per day',          'woocommerce-booking' ); ?> </th>
							<th> <?php _e( 'Fixed price',            'woocommerce-booking' ); ?> </th>
							<th> <?php _e( 'Actions',                'woocommerce-booking' ); ?> </th>    
						</tr>
						<?php 
						if ( isset( $var ) ) {
							echo $var;
						}
						?>
					</table>
					</p>
				</div>					

			<?php
			}
                        
            /**
             * This function will save the settings for the price by range of days feature. 
             */
			function bkap_price_range_product_settings_save( $booking_settings, $product_id ) {
				if ( isset( $_POST['booking_block_price_enable'] ) ) {
						$booking_settings['booking_block_price_enable'] = $_POST['booking_block_price_enable'];
				}
				return $booking_settings;
			}
			
            /**
             * This function checks whether any addons need to be added to the price of the price by range of days. 
             */
			function bkap_get_add_cart_item( $cart_item ) {
				// Adjust price if addons are set
				if ( isset( $cart_item['bkap_booking'] ) ) :
					$extra_cost = 0;
					
					foreach ( $cart_item['bkap_booking'] as $addon ) :
						if ( isset( $addon['price'] ) && $addon['price'] > 0 ) $extra_cost += $addon['price'];
					endforeach;
					
					$cart_item['data']->set_price( $extra_cost );					
				endif;
				
				return $cart_item;
			}

            /**
             * This function is used to show the price updation of the price by range of days on the front end.
             ************************************************************/
			public function bkap_price_range_show_updated_price( $product_id, $product_type, $variation_id_to_fetch, $checkin_date, $checkout_date, $currency_selected ) {
				$variation_id       =   $variation_id_to_fetch;
				$number_of_days     =   strtotime( $checkout_date ) - strtotime( $checkin_date );
				
				$booking_settings   =   get_post_meta( $product_id, 'woocommerce_booking_settings', true );
				$number             =   floor( $number_of_days/( 60 * 60 * 24 ) );
				
				if ( isset( $booking_settings['booking_charge_per_day'] ) && $booking_settings['booking_charge_per_day'] == 'on' ){
					$number = $number + 1;
				}
					
				if( $number == 0 && isset( $booking_settings['booking_same_day'] ) && $booking_settings['booking_same_day'] == 'on' ) {
					$number = 1;
				}
				
				if ( $number <= 0 ) {
					$number = 1;
				}
				
				if ( $product_type == 'variable' ) {
					$variations_selected   = array();
					$string_explode        = '';
					$product_attributes    = get_post_meta( $product_id, '_product_attributes', true );
					$i                     = 0;
					
					foreach( $product_attributes as $key => $value ) {
						
					    if( isset( $_POST['attribute_selected'] ) ) {
							$string_explode = explode( "|", $_POST['attribute_selected'] );
						}else{
							$string_explode = array();
						}
						
						$value_array  = explode( "|", $value['value'] );
						$s_value      = '';
						
						foreach( $string_explode as $sk => $sv ) {
							
						    if( $sv == '' ){
								unset( $string_explode[ $sk ] );
							}
						}
					
						foreach( $value_array as $k => $v ){
							$string1 = str_replace(" ", "", $v );
							
							if(count($string_explode) > 0) {
								$string2 = str_replace( " ", "", $string_explode[ $i+1 ]);
							} else {
								$string2 = '';
							}
							
							if( strtolower( addslashes( $string1 ) ) == strtolower( $string2 ) /* $pos_value != 0*/) {
					
								if( substr( $v, 0, -1 ) === ' ' ) {
									$result                    = rtrim( $v, " " );
									$variations_selected[$i]   = $result;
								}
								
								if( substr( $v, 0, 1 ) === ' ' ) {
									$result                    = preg_replace( "/ /", "", $v, 1 );
									$variations_selected[$i]   = addslashes( $result );
								} else {
									$variations_selected[$i] = addslashes( $v );
								}
							}
						}
						$i++;
					}
				}
				else {
					$variations_selected = array();
				}
				$price = $this->price_range_calculate_price( $product_id, $product_type, $variation_id, $number, $variations_selected );
			
				if ( function_exists('is_bkap_deposits_active') && is_bkap_deposits_active() || function_exists('is_bkap_seasonal_active') && is_bkap_seasonal_active() ) {
					
				    if ( isset( $price ) && $price != '' ) {
						
				        if ( isset( $_POST['variable_blocks'] ) && $_POST['variable_blocks'] == 'Y' ) {
							$_SESSION['variable_block_price'] = $_POST['price'] = $price;
						}else {
							$_SESSION['variable_block_price'] =  $_POST['price'] = '';
						}
					}else {
						$error_message = __( "Please select an option.", "woocommerce-booking" );
						print( 'jQuery( "#show_time_slot" ).html( "' . addslashes( $error_message ) . '");' );
						die();
					}
				}else {
					
				    if ( isset( $_POST['variable_blocks'] ) && $_POST['variable_blocks'] == 'Y' ) {
						$_SESSION['variable_block_price'] = $price;
						$_POST['price']                   = $price;
					}else {
						$_SESSION['variable_block_price'] =  $_POST['price'] = '';
					}
				}
			}
			
			public static function price_range_calculate_price( $product_id, $product_type, $variation_id, $number, $variations_selected ) {
				global $wpdb;
				
				$price          = 0;
				$results_price  = array();
				
				if ( $product_type == 'variable' ) {
					$booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
					
					if ( isset( $booking_settings['booking_block_price_enable'] ) && $booking_settings['booking_block_price_enable'] == 'yes' ){
						$j                    = 1;
						$k                    = 0;
						$attribute_sub_query  = '';
						
						foreach( $variations_selected as $key => $value ) {
							$attribute_sub_query .= " c".$k.".attribute_id = '$j' AND c".$k.".meta_value = '$value' AND";
							$j++;
							$k++;
						}
						
						$query    =   "SELECT c0.block_id FROM `".$wpdb->prefix."booking_block_price_attribute_meta` AS c0
                					  JOIN `".$wpdb->prefix."booking_block_price_attribute_meta` AS c1 ON c1.block_id=c0.block_id
                					  WHERE ".$attribute_sub_query." c0.post_id = %d";
				
						$results  = $wpdb->get_results ( $wpdb->prepare( $query, $product_id ) );
							
						$_POST['fixed_price'] = $_POST['variable_blocks'] = "N";
						$e = 0;
						
						foreach( $results as $k => $v ) {
							$query           =   "SELECT price_per_day, fixed_price, maximum_number_of_days FROM `".$wpdb->prefix."booking_block_price_meta`
							                     WHERE id = %d AND post_id = %d AND minimum_number_of_days <= %d AND maximum_number_of_days >= %d";
				
							$results_array   =   $wpdb->get_results( $wpdb->prepare( $query,$v->block_id, $product_id, $number, $number ) );
							
							if ( isset( $results_array ) && count( $results_array ) > 0 ) {
								$results_price[$e] = $results_array;
							}
							$e++;
						}
						
						if ( isset( $results ) && count( $results ) > 0 && $results != false ) {
							
						    if ( isset( $results_price[0] ) && count( $results_price[0] ) == 0 ) {
								$e = 0;
								
								foreach( $results as $k => $v ) {
									$query         =   "SELECT price_per_day, fixed_price, maximum_number_of_days FROM `".$wpdb->prefix."booking_block_price_meta`
									                   WHERE id = %d AND post_id = %d AND minimum_number_of_days <= %d";
									$results_array =   $wpdb->get_results($wpdb->prepare($query,$v->block_id,$product_id,$number));
									
									if ( isset( $results_array ) && count( $results_array ) > 0 ) {
										$results_price[$e] = $results_array;
									}
									$e++;
								}
								if( isset( $results_price[0] ) && count( $results_price[0] ) == 0 ) {
									
								    if ( $variation_id != '' ) {
										$price = get_post_meta( $variation_id, '_sale_price', true );
										
										if (!isset($price) || $price == '' || $price == 0){
											$price = get_post_meta( $variation_id, '_regular_price', true );
										}
										
										$price .= "-";
									}else {
										$price = 0;
									}
								} else{
									
								    foreach ( $results as $k => $v ) {
										$query            =   "SELECT price_per_day, fixed_price, MAX(maximum_number_of_days) AS maximum_number_of_days FROM `".$wpdb->prefix."booking_block_price_meta`
										                      WHERE id = %d AND post_id = %d AND minimum_number_of_days <= %d";
				
										$results_array    =   $wpdb->get_results( $wpdb->prepare( $query, $v->block_id, $product_id, $number ) );
										
										if ( isset( $results_array ) && count( $results_array ) > 0 ) {
											
										    if ( $results_array[0]->price_per_day != '' && $results_array[0]->fixed_price != '' && $results_array[0]->maximum_number_of_days != '' ) {
												$results_price[ $e ] = $results_array;
											}
										}
										$e++;
									}
									
									foreach( $results_price as $k => $v ){
										
									    if ( !empty( $results_price[ $k ] ) ){
											$_POST['variable_blocks'] = "Y";
											
											if ( $variation_id != '' ) {
												$price = get_post_meta( $variation_id, '_sale_price', true );
												
												if ( !isset( $price ) || $price == '' || $price == 0 ){
													$price = get_post_meta( $variation_id, '_regular_price', true );
												}
												
												$diff_days = '';
												
												if ( $v[0]->maximum_number_of_days < $number ){
													$diff_days = $number - $v[0]->maximum_number_of_days;
													
													if ( $v[0]->fixed_price != 0 ){
														$calc_price           = $v[0]->fixed_price + ( $price * $diff_days );
														$price                = $calc_price . "-fixed";
														$_POST['fixed_price'] = "Y";
													} else{
														$calc_price   = ( $v[0]->price_per_day * $v[0]->maximum_number_of_days ) + ( $price * $diff_days );
														$price        = $calc_price . "-per_day";
													}
												}
											}else {
												$price = 0;
											}
										} else {
											unset( $results_price[ $k ] );
										}
									}
								}
							} else {
								
							    foreach ( $results as $k => $v ) {
									$query         =   "SELECT price_per_day, fixed_price, MAX(maximum_number_of_days) AS maximum_number_of_days FROM `".$wpdb->prefix."booking_block_price_meta`
									                   WHERE id = %d AND post_id = %d AND minimum_number_of_days <= %d";
									$results_array =   $wpdb->get_results( $wpdb->prepare( $query, $v->block_id, $product_id, $number ) );
										
									if ( isset( $results_array ) && count( $results_array ) > 0 ) {
										
									    if ( $results_array[0]->price_per_day != '' && $results_array[0]->fixed_price != '' && $results_array[0]->maximum_number_of_days != '' ) {
											$results_price[ $e ] = $results_array;
										}
									}
									$e++;
								}
								
								foreach ( $results_price as $k => $v ) {
									
								    if ( !empty( $results_price[ $k ] ) ) {
										$_POST['variable_blocks'] = "Y";
										
										if ( $variation_id != '' ) {
										$price = get_post_meta( $variation_id, '_sale_price', true );
											
											if ( !isset( $price ) || $price == '' || $price == 0 ){
												$price = get_post_meta( $variation_id, '_regular_price', true );
											}
											
											$diff_days = '';
											
											if ( $v[0]->maximum_number_of_days < $number ){
												$diff_days = $number - $v[0]->maximum_number_of_days;
												
												if ( $v[0]->fixed_price != 0 ){
													$_POST['fixed_price']  = "Y";
													$calc_price            = $v[0]->fixed_price + ( $price * $diff_days );
													$price                 = $calc_price .  "-fixed";
												} else {
													$calc_price            = ($v[0]->price_per_day * $v[0]->maximum_number_of_days) + ($price * $diff_days);
													$price                 = $calc_price . "-per_day";
												}
												
											} else {
												
											    if ( $v[0]->fixed_price != 0 ) {
													$_POST['fixed_price']  = "Y";
													$calc_price            = $v[0]->fixed_price;
													$price                 = $calc_price . "-fixed";
												} else {
													$calc_price            = $v[0]->price_per_day * $number;
													$price                 = $calc_price . "-per_day";
												}
											}
										}
										else {
											$price = 0;
										}
									} else {
										unset( $results_price[ $k ] );
									}
								}
								if ( count( $results_price ) == 0 ) {
									$price = 0;
									
									if ( $variation_id != '' ) {
										$price = get_post_meta( $variation_id, '_sale_price', true );
										
										if ( !isset( $price ) || $price == '' || $price == 0) {
											$price = get_post_meta( $variation_id, '_regular_price', true );
										}
										$price .= "-";
									}
								}
							}
						}
						else {
							if ( $variation_id != '' ) {
								$price = get_post_meta( $variation_id, '_sale_price', true);
								
								if ( !isset( $price ) || $price == '' || $price == 0 ){
									$price = get_post_meta( $variation_id, '_regular_price', true );
								}
								$price .= "-";
							}
							else {
								$price = 0;
							}
						}
					}
					else {
						if ( $variation_id != '' ) {
							$price = get_post_meta( $variation_id, '_sale_price', true );
							
							if ( !isset( $price ) || $price == '' || $price == 0 ){
								$price = get_post_meta( $variation_id, '_regular_price', true );
							}
							$price .= "-";
						}
						else {
							$price = 0;
						}
					}
				} elseif ( $product_type == 'simple' ) {
					$booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
					
					if ( isset( $booking_settings['booking_block_price_enable'] ) && $booking_settings['booking_block_price_enable'] == 'yes' ) {
				
						$query            =   "SELECT price_per_day, fixed_price, maximum_number_of_days FROM `".$wpdb->prefix."booking_block_price_meta`
						                      WHERE post_id = %d AND minimum_number_of_days <= %d AND maximum_number_of_days >= %d";
							
						$results_price    =   $wpdb->get_results( $wpdb->prepare( $query, $product_id, $number, $number ) );
				
						if ( count( $results_price ) == 0 ) {
							$query           =   "SELECT price_per_day, fixed_price, maximum_number_of_days FROM `".$wpdb->prefix."booking_block_price_meta`
							                     WHERE post_id = %d AND minimum_number_of_days <= %d";
				
							$results_price   =   $wpdb->get_results( $wpdb->prepare( $query, $product_id, $number ) );
								
							if ( count( $results_price ) == 0 ) {
								$price = get_post_meta( $product_id, '_sale_price', true);
								
								if ( !isset( $price ) || $price == '' || $price == 0 ){
									$price = get_post_meta( $product_id, '_regular_price', true );
								}
								$price .= "-";
							} else {
								$query          =   "SELECT MAX(maximum_number_of_days) AS maximum_number_of_days FROM `".$wpdb->prefix."booking_block_price_meta`
								                    WHERE post_id = %d AND minimum_number_of_days <= %d";
									
								$results_block  =   $wpdb->get_results( $wpdb->prepare( $query, $product_id, $number ) );
						
								// Get the block price which is the closest to the number of days we hv
								$price_query    =   "SELECT price_per_day, fixed_price FROM `".$wpdb->prefix."booking_block_price_meta`
												    WHERE post_id = %d AND minimum_number_of_days <= %d AND maximum_number_of_days = %d";
								$results_price  =   $wpdb->get_results( $wpdb->prepare( $price_query, $product_id, $number, $results_block[0]->maximum_number_of_days ) );	
								
								foreach( $results_price as $k => $v ) {
										
									if ( !empty( $results_price[ $k ] ) ) {
										$_POST['variable_blocks'] = "Y";
										$price                    = get_post_meta( $product_id, '_sale_price', true );
										
										if ( !isset( $price ) || $price == '' || $price == 0 ){
											$price = get_post_meta( $product_id, '_regular_price', true );
										}
										
										if ( $results_block[0]->maximum_number_of_days < $number ) {
											$diff_days = $number - $results_block[0]->maximum_number_of_days;
											
											if ( $v->fixed_price != 0 ) {
												$_POST['fixed_price']   = "Y";
												$calc_price             = $v->fixed_price + ( $price * $diff_days );
												$price                  = $calc_price . "-fixed";
											} else {
												$calc_price = ( $v->price_per_day * $results_block[0]->maximum_number_of_days ) + ( $price * $diff_days );
												$price      = $calc_price . "-per_day";
											}
										}
									}else {
										unset( $results_price[ $k ] );
									}
								}
							}
						}else {
							
						    foreach ( $results_price as $k => $v ) {
								
						        if( !empty( $results_price[ $k ] ) ) {
									$_POST['variable_blocks'] = "Y";
									
									if ( $v->fixed_price != 0 ) {
										$_POST['fixed_price'] = "Y";
										$price                = $v->fixed_price;
										$price               .= "-fixed";
										$price               .= "-";
									} else {
										$price                = $v ->price_per_day * $number;
										$price               .= "-per_day";
										$price               .= "-";
									}
								} else {
									unset( $results_price[ $k ] );
								}
							}
						}
					}else {
						$price = get_post_meta( $product_id, '_sale_price', true );
						
						if ( !isset( $price ) || $price == '' || $price == 0 ){
							$price = get_post_meta( $product_id, '_regular_price', true );
						}
						$price .= "-";
					}
				}
				return $price;
			}
		}
	
	}
	$bkap_block_booking_price = new bkap_block_booking_price();
?>