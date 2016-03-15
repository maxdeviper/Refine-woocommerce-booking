<?php 
	/**
	 * Localisation
	 **/
	load_plugin_textdomain( 'bkap_block_booking', false, dirname( plugin_basename( __FILE__ ) ) . '/' );

	/**
	 * bkap_deposits class
	 **/
	if ( !class_exists( 'bkap_block_booking' ) ) {

		class bkap_block_booking {

			public function __construct() {
				// Initialize settings
				register_activation_hook( __FILE__, array( &$this, 'bkap_block_booking_activate' ) );
				
				// used to add new settings on the product page booking box
				add_action( 'bkap_after_listing_enabled',               array( &$this, 'bkap_fixed_block_show_field_settings' ), 5, 1 );
				add_action( 'bkap_add_tabs',                            array( &$this, 'fixed_block_tab' ), 5, 1 );
				add_action( 'init',                                     array( &$this, 'bkap_load_ajax_fixed_block' ) );
				add_filter( 'bkap_save_product_settings',               array( &$this, 'bkap_fixed_block_product_settings_save'), 10, 2 );
				add_action( 'bkap_display_multiple_day_updated_price',  array( &$this, 'bkap_fixed_block_show_updated_price'), 6, 6 );
	
				add_action( 'woocommerce_before_add_to_cart_form',      array( &$this, 'bkap_fixed_block_before_add_to_cart' ) );
				add_action( 'woocommerce_before_add_to_cart_button',    array( &$this, 'bkap_fixed_block_booking_after_add_to_cart' ) );
				
				add_action( 'bkap_display_price_div',                   array( &$this, 'bkap_fixed_block_display_price'), 10, 1 );
				
				$this->days = array(  'any_days' => __( 'Any Days',   'woocommerce-booking' ),
        						             '0' => __( 'Sunday',     'woocommerce-booking' ),
        						             '1' => __( 'Monday',     'woocommerce-booking' ),
        						             '2' => __( 'Tuesday',    'woocommerce-booking' ),
        						             '3' => __( 'Wednesday',  'woocommerce-booking' ),
        						             '4' => __( 'Thursday',   'woocommerce-booking' ),
        						             '5' => __( 'Friday',     'woocommerce-booking' ),
        						             '6' => __( 'Saturday',   'woocommerce-booking' )
				                   );
				
			}
			
            /**
             *This function detects when the booking plugin is activated and creates the table necessary in database,if they do not exists. 
             */
			
			function bkap_block_booking_activate() {
			
				global $wpdb;
				
				$table_name     =   $wpdb->prefix . "booking_fixed_blocks";
				
				$sql            =   "CREATE TABLE IF NOT EXISTS ".$table_name." (
                    				`id` int(11) NOT NULL AUTO_INCREMENT,
                    				`global_id` int(11) NOT NULL,
                    				`post_id` int(11) NOT NULL,
                    				`block_name` varchar(50) NOT NULL,
                                    `number_of_days` int(11) NOT NULL,
                    				`start_day` varchar(50) NOT NULL,
                                    `end_day` varchar(50) NOT NULL,
                    				`price` double NOT NULL,
                    				`block_type` varchar(25) NOT NULL,
                    				PRIMARY KEY (`id`)s
                    				) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 " ;
				
				
				
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				dbDelta( $sql );
			}
		   
		   /**
            *  This function is used to load ajax functions required by fixed block booking.
            */
			
			function bkap_load_ajax_fixed_block() {
				
			    if ( !is_user_logged_in() ) {
					add_action( 'wp_ajax_nopriv_bkap_save_booking_block',  array( &$this, 'bkap_save_booking_block' ) );
					add_action( 'wp_ajax_nopriv_bkap_delete_block',        array( &$this, 'bkap_delete_block' ) );
					add_action( 'wp_ajax_nopriv_bkap_delete_all_blocks',   array( &$this, 'bkap_delete_all_blocks' ) ) ;
				} else {
					add_action( 'wp_ajax_bkap_save_booking_block',         array( &$this, 'bkap_save_booking_block' ) );
					add_action( 'wp_ajax_bkap_delete_block',               array( &$this, 'bkap_delete_block' ) );
					add_action( 'wp_ajax_bkap_delete_all_blocks',          array( &$this, 'bkap_delete_all_blocks' ) );
				}
			}

			function bkap_fixed_block_before_add_to_cart(){
			
			}
			
            /**
             *This function add the fixed block fields on the frontend product page as per the settings selected when Enable Fixed Block Booking is enabled.
             */
			
			function bkap_fixed_block_booking_after_add_to_cart(){	
				global $post, $wpdb, $woocommerce, $woocommerce_wpml;
				
				if ( defined('ICL_LANGUAGE_CODE') ){
				     
				    if( ICL_LANGUAGE_CODE == 'en' ) {
				        $curr_lang = "en";
				    } else{
				        $curr_lang = ICL_LANGUAGE_CODE;
				    }
				}				
 				
				$duplicate_of     =   bkap_common::bkap_get_product_id( $post->ID );
				$booking_settings = get_post_meta( $duplicate_of, 'woocommerce_booking_settings', true );

 				 if ( ( isset( $booking_settings['booking_enable_multiple_day'] ) && $booking_settings['booking_enable_multiple_day'] == 'on' ) && ( isset( $booking_settings['booking_fixed_block_enable'] ) && $booking_settings['booking_fixed_block_enable'] == 'yes' ) ) {
 				 	
 				 	$results = $this->bkap_get_fixed_blocks( $duplicate_of );
					
					foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
									
					       if( array_key_exists( 'bkap_booking', $values ) ) {
									$booking       = $values['bkap_booking'];
									$hidden_date   = $booking[0]['hidden_date'];
									
									if( array_key_exists( "hidden_date_checkout", $booking[0] ) ) {
										$hidden_date_checkout = $booking[0]['hidden_date_checkout'];
									}
							}
								break;
					}
 				 	
					if ( count( $results ) > 0) {

						printf ('

						<label>'. __( "Select Period :", "woocommerce-booking" ).' </label><select name="block_option" id="block_option" >');
						foreach ( $results as $key => $value ) {
						    
						    $name_msg     = 'bkap_fixed_' . $value->id . '_block_name';
						    $block_name   = $value->block_name;
						    $block_name   = $this->bkap_get_translated_texts( $name_msg, $block_name, $curr_lang );
						    
							printf('<option value=%s&%s&%s>%s</option>', $value->start_day, $value->number_of_days, $value->price, $block_name );
						} 
						printf ('</select> <br/> <br/>');
						?>
						<script type="text/javascript">	
						jQuery("#block_option").change(function() {
							if ( jQuery("#block_option").val() != "" ) {
								var passed_id = this.value;
								var exploded_id = passed_id.split('&');
								console.log(exploded_id);
								jQuery("#block_option_start_day").val(exploded_id[0]);
								jQuery("#block_option_number_of_day").val(exploded_id[1]);
								jQuery("#block_option_price").val(exploded_id[2]);
								jQuery("#wapbk_hidden_date").val("");
								jQuery("#wapbk_hidden_date_checkout").val("");
								//jQuery("#show_time_slot").html("");
								jQuery("#show_time_slot").html("");
								jQuery("#booking_calender").datepicker("setDate");
								jQuery("#booking_calender_checkout").datepicker("setDate");
								test_bkap_init_inline();
								bkap_calculate_price();
							}
						});
	
	
						</script>
	
						<?php

						if ( count( $results ) >= 0 ) {
							$sd=$results[0]->start_day;
							$nd=$results[0]->number_of_days;
							$pd=$results[0]->price;
						}
						echo ' <input type="hidden" id="block_option_enabled"  name="block_option_enabled" value="on"/> <input type="hidden" id="block_option_start_day"  name="block_option_start_day" value="'.$sd.'"/> <input type="hidden" id="block_option_number_of_day"  name="block_option_number_of_day" value="'.$nd.'"/><input type="hidden" id="block_option_price"  name="block_option_price" value="'.$pd.'"/>';	
					} else  {
						$number_of_fixed_price_blocks = 0;
						echo ' <input type="hidden" id="block_option_enabled"  name="block_option_enabled" value="off"/> <input type="hidden" id="block_option_start_day"  name="block_option_start_day" value=""/> <input type="hidden" id="block_option_number_of_day"  name="block_option_number_of_day" value=""/><input type="hidden" id="block_option_price"  name="block_option_price" value=""/>';
					}
 				 }
			}

           /**
            * This function display the price after selecting the date on front end.
            */
			
			function bkap_fixed_block_display_price( $product_id ) {
			    global $post;
			    
			    $duplicate_of     =   bkap_common::bkap_get_product_id( $post->ID );
			    $booking_settings = get_post_meta( $duplicate_of, 'woocommerce_booking_settings', true );
			    
				if ( isset( $_POST['booking_fixed_block_enable'] ) && $_POST['booking_partial_payment_radio'] != '' ):
					$currency_symbol   = get_woocommerce_currency_symbol();
        			if( has_filter( 'bkap_show_addon_price' ) ) {
        				$show_price = apply_filters( 'bkap_show_addon_price', '' );
                    } else {
        			    $show_price = 'show';
                    }
					print('<div id="show_addon_price" name="show_addon_price" class="show_addon_price" style="display:'.$show_price.';">'.$currency_symbol.' 0</div>');
				endif;
			}
			
			function fixed_block_tab( $product_id ) {
				?>
				<li><a id="block_booking"> <?php _e( 'Fixed Block Booking', 'woocommerce-booking' ); ?> </a></li>
				<?php 
			}
			
            /**
             * This function add the fixed block table on the admin side.
             * It allows to create blocks on the admin product page.
             */
			
			function bkap_fixed_block_show_field_settings( $product_id ) {
				global $post, $wpdb;
				?>
				<div id="block_booking_page" style="display:none;">
				<table class='form-table'>
					<tr id="fixed_block">
						<th>
							<label for="booking_fixed_block"><?php _e( 'Enable Fixed Block Booking:', 'woocommerce-booking' );?></label>
						</th>
						<td>
							<?php

							$duplicate_of     =   bkap_common::bkap_get_product_id( $post->ID );
							$booking_settings = get_post_meta( $duplicate_of, 'woocommerce_booking_settings', true );
							
							
							$enabled_fixed_blocks        = "";
							$fixed_block_button_status   = "disabled";
							
							if ( isset( $booking_settings['booking_fixed_block_enable'] ) ){
                               $product_enable_fixed_blocks = $booking_settings['booking_fixed_block_enable'];
                            }
							
							if ( isset( $product_enable_fixed_blocks ) && $product_enable_fixed_blocks == 'yes' ) {
								$enabled_fixed_blocks       = "checked";
								$fixed_block_button_status  = "";
							}
							
							?>
							<input type="checkbox" name="booking_fixed_block_enable" id="booking_fixed_block_enable" value="yes" <?php echo $enabled_fixed_blocks;?>></input>
							<img class="help_tip" width="16" height="16" data-tip="<?php _e( 'Enable to allow Fixed Block Pricing for the product.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png"/>
						</td>
					</tr>
				</table>
				<script type="text/javascript">
					jQuery("#booking_fixed_block_enable").change(function() {
						if (jQuery("#booking_fixed_block_enable").attr("checked")) {
							jQuery("#add_another_fixed_block").removeAttr("disabled");
						}
						else {
							jQuery("#add_another_fixed_block").attr("disabled", "disabled");
							jQuery("#add_block").hide();
						}	
					});
				</script>
				<p>
				<div id="add_block_button" name="add_block_button">
				<input type="button" class="button-primary" value="<?php _e( 'Add New Booking Block', 'woocommerce-booking' ); ?>" id="add_another_fixed_block" onclick="bkap_show_div_fixed_blocks()" <?php echo $fixed_block_button_status; ?>>
				</div>
				</p>
				
				<div id="add_block" name="add_block" style="display:none;">
				<table class="form-table">
				<script type="text/javascript">
							jQuery(document).ready(function() {
								var formats = ["d.m.y", "d-m-yyyy","MM d, yy"];
								jQuery("#fixed_block_start_date").datepick({dateFormat: formats[1], monthsToShow: 1, showTrigger: '#calImg'});
							});
							jQuery(document).ready(function() {
								var formats = ["d.m.y", "d-m-yyyy","MM d, yy"];
								jQuery("#fixed_block_end_date").datepick({dateFormat: formats[1], monthsToShow: 1, showTrigger: '#calImg'});
							});
				</script>
				<tr>
				 	<label for="add_block_label"><?php _e( 'Enter a Block:', 'woocommerce-booking' ); ?> </label>
				</tr>
				<tr>
					<table>
					<input type="hidden" name="id_booking_block" value=""/>
					<tr>
						<td><label for="fixed_block_name"><?php _e( 'Booking Block Name: ', 'woocommerce-booking' ); ?></label></td>
						<td><label for="number_of_days" ><?php _e( 'Number of days: ', 'woocommerce-booking' ); ?></label></td>
						<td><label for="fixed_block_start_date" ><?php _e( 'Start Day: ', 'woocommerce-booking' ); ?></label></td>
						<td><label for="fixed_block_end_date" ><?php _e( 'End Day: ', 'woocommerce-booking' ); ?></label></td>
						<td><label for="fixed_block_price" ><?php _e( 'Price: ', 'woocommerce-booking' ); ?></label><br></td>
					</tr>
				<tr>
						<td><input type="text" id="booking_block_name" name="booking_block_name"></input></td>
						<td><input type="text"  size="5" id="number_of_days" name="number_of_days" size="10"></input></td>
						<td><select id="start_day" name="start_day">
						<?php 
						$days = $this->days;
						foreach ( $days as $dkey => $dvalue ) {
							?>
							<option value="<?php echo $dkey; ?>"><?php echo $dvalue; ?></option>
							<?php 
						}
						?>
					</select></td>
						<td><select id="end_day" name="end_day">
						<?php 
						foreach ( $days as $dkey => $dvalue ) {
							?>
							<option value="<?php echo $dkey; ?>"><?php echo $dvalue; ?></option>
							<?php 
						}
						?>
					</select></td>
						<td><input type="text" size="8" id="fixed_block_price" name="fixed_block_price" size="10"></input><br>
</td>
						 					
					<input type="hidden" id="table_id" name="table_id"></input><br>
					</tr>

					<tr>
					
					<td>
					<input type="button" class="button-primary" value="Save Block" id="save_another" onclick="bkap_save_fixed_block()"></input>
					<input type="button" class="button-primary" value="Close" id="close_div" onclick="bkap_close_fixed_block()"></input></td>
					<td colspan="4"></td>
					</tr>					

					</table>	
				</tr>
				</table>
				</div>
				
				<?php $this->bkap_booking_block_table(); ?>
				
				</div>

				<?php
				$currency_symbol = get_woocommerce_currency_symbol();
				$duplicate_of     =   bkap_common::bkap_get_product_id( $post->ID );
				$booking_settings = get_post_meta( $duplicate_of, 'woocommerce_booking_settings', true );
				
				print('<script type="text/javascript">
                                //************************************
                                // This function will save the created fixed block on the admin product page.
                                //************************************
				function bkap_save_fixed_block() {
					if (jQuery("#fixed_block_price").val() == "") {
						alert("'. __("Price cannot be blank.", "woocommerce-booking") .'");
						return;
					}
					var days = [];
					days["any_days"] = "Any Days";
					days["0"] = "Sunday";
					days["1"] = "Monday";
					days["2"] = "Tuesday";
					days["3"] = "Wednesday";
					days["4"] = "Thursday";
					days["5"] = "Friday";
					days["6"] = "Saturday";
						
					var data = {
							post_id: "'.$duplicate_of.'", 
							booking_block_name: jQuery("#booking_block_name").val(),
							start_day: jQuery("#start_day").val(),
							end_day: jQuery("#end_day").val(),
							price: jQuery("#fixed_block_price").val(),
							number_of_days: jQuery("#number_of_days").val(),
							id: jQuery("#table_id").val(),
							action: "bkap_save_booking_block"
							};

							
							jQuery.post("'.get_admin_url().'admin-ajax.php", data, function(response) {
                              	insert_id = response;
								if (jQuery("#table_id").val() != "") {
						
									var row_id = "row_"+insert_id;
						
									var day_num = jQuery("#start_day").val()
									var start_day = days[day_num];
									var day_num = jQuery("#end_day").val();
									var end_day = days[day_num];
						
									var table = document.getElementById("list_blocks").rows;
									var x = table[row_id].cells;
									x[0].innerHTML = jQuery("#booking_block_name").val();
									x[1].innerHTML = jQuery("#number_of_days").val();
									x[2].innerHTML = start_day;
									x[3].innerHTML = end_day;
									x[4].innerHTML = jQuery("#fixed_block_price").val();
									
									var edit_id = insert_id + "&" + jQuery("#start_day").val() + "&" + jQuery("#end_day").val() + "&" + jQuery("#booking_block_name").val() + "&" + jQuery("#fixed_block_price").val() + "&" + jQuery("#number_of_days").val();
									var edit_data = "<a href=\"javascript:void(0);\" id=\""+edit_id+"\" class=\"edit_block\"> <img src=\"'.plugins_url().'/woocommerce-booking/images/edit.png\" alt=\"Edit Fixed Block\" title=\"Edit Fixed Block\"></a>";
									
									var delete_data = "<a href=\"javascript:void(0);\" id=\""+insert_id+"\" class=\"delete_block\"> <img src=\"'.plugins_url().'/woocommerce-booking/images/delete.png\" alt=\"Delete Fixed Block\" title=\"Delete Fixed Block\"></a>";
									x[5].innerHTML = edit_data + delete_data;
								}
								else {						
									var table = document.getElementById("list_blocks");
									var row = table.insertRow(-1);
									var row_id = "row_"+insert_id;
									row.id = row_id;
						
									var day_num = jQuery("#start_day").val()
									var start_day = days[day_num];
									var day_num = jQuery("#end_day").val();
									var end_day = days[day_num];
						
									var cell1 = row.insertCell(0);
									cell1.innerHTML = jQuery("#booking_block_name").val();
						
									var cell2 = row.insertCell(1);
									cell2.innerHTML = jQuery("#number_of_days").val()
									
									var cell3 = row.insertCell(2);
									cell3.innerHTML = start_day;
						
									var cell4 = row.insertCell(3);
									cell4.innerHTML = end_day;
						
									var cell5 = row.insertCell(4);
									cell5.innerHTML = jQuery("#fixed_block_price").val();
						
									var edit_id = insert_id + "&" + jQuery("#start_day").val() + "&" + jQuery("#end_day").val() + "&" + jQuery("#booking_block_name").val() + "&" + jQuery("#fixed_block_price").val() + "&" + jQuery("#number_of_days").val();
							
									var edit_data = "<a href=\"javascript:void(0);\" id=\""+edit_id+"\" class=\"edit_block\"> <img src=\"'.plugins_url().'/woocommerce-booking/images/edit.png\" alt=\"Edit Fixed Block\" title=\"Edit Fixed Block\"></a>";
									var cell6 = row.insertCell(5);
						
									var delete_data = "<a href=\"javascript:void(0);\" id=\""+insert_id+"\" class=\"delete_block\"> <img src=\"'.plugins_url().'/woocommerce-booking/images/delete.png\" alt=\"Delete Fixed Block\" title=\"Delete Fixed Block\"></a>";
									cell6.innerHTML = edit_data + delete_data;
								}
								//	reset the fields	
									jQuery("#booking_block_name").val("");
									jQuery("#number_of_days").val("");
									jQuery("#fixed_block_price").val("");
									jQuery("#start_day").val("");
									jQuery("#end_day").val("");
								 // reset and hide form
									jQuery("#add_block").hide();
									
                          
                        });


				}
				

				</script>');
				?>

				<script type="text/javascript">
				
				
               /**
                * This function displays the div with the fields when 
                * Add new Booking Block button is clicked on the admin 
                * product page
                */
                
				function bkap_show_div_fixed_blocks() {
					jQuery("#fixed_block_name").val("");
					jQuery("#start_day").val("any_days");
					jQuery("#end_day").val("any_days");
					jQuery("#fixed_block_price").val("");
					jQuery("#table_id").val("");
					document.getElementById("add_block").style.display = "block";
					jQuery("#add_block").show();
				}
				
			   /**
                * This function will hide the div when close button is clicked
                */
                
				function bkap_close_fixed_block() {
					document.getElementById("add_block").style.display = "none";
					jQuery("#add_block").closest("form").find("input[type=text], textarea").val("");
					jQuery("#table_id").val("");

				}
				
				jQuery(document).ready(function(){
					
					jQuery("table#list_blocks").on('click', 'a.edit_block',function() {
						
						var passed_id = this.id;
						
						var exploded_id = passed_id.split('&');
				
						jQuery("#booking_block_name").val(exploded_id[3]);
						jQuery("#number_of_days").val(exploded_id[5]);
						jQuery("#start_day").val(exploded_id[1]);
						jQuery("#end_day").val(exploded_id[2]);
						jQuery("#fixed_block_price").val(exploded_id[4]);
						jQuery("#table_id").val(exploded_id[0]);
						//jQuery("#booking_seasonal_pricing_operator").val(exploded_id[5]);

						if (exploded_id[6] == "amount"){ 
                                                    jQuery("input[id=booking_seasonal_pricing_amount]").attr("checked",true);
                                                } else if (exploded_id[6] == "percent"){ 
                                                     jQuery("input[id=booking_seasonal_pricing_percent]").attr("checked",true);
                                                }

						document.getElementById("add_block").style.display = "block";
						jQuery("#add_block").show();
						//alert('test');

					});

					jQuery("table#list_blocks").on('click','a.delete_block',function() {
						var y=confirm('<?php _e( "Are you sure you want to delete this block?", "woocommerce-booking" ); ?>');
						//alert(y);
						if(y==true) {
							var passed_id = this.id;
							var data = {
								details: passed_id,
								action: 'bkap_delete_block'
							};	
							jQuery.post('<?php echo get_admin_url();?>admin-ajax.php', data, function(response) {
								//alert('Got this from the server: ' + response);
								jQuery("#row_" + passed_id ).hide();
							});
						}
					});
					jQuery("a.delete_all_blocks").click(function() {
						var y=confirm('<?php _e("Are you sure you want to delete all the blocks?", "woocommerce-booking"); ?>');
						if(y==true) {
							var passed_id = this.id;
							var data = {
								post_id: passed_id,	
								action: "bkap_delete_all_blocks"
							};
							
							jQuery.ajax({
	                            url: '<?php echo get_admin_url();?>admin-ajax.php',
	                            type: "POST",
	                            data : data,
	                            beforeSend: function() {
	                             //loading	
	
	                            },
	                            success: function(data, textStatus, xhr) {
									jQuery("table#list_blocks").hide();
									 console.log(data);
	                            },
	                            error: function(xhr, textStatus, errorThrown) {
	                              // error status
	                            }
	                        });
						}
					});
				});
					
				</script>
				<?php 
			}
			
			/**
             * This function are used to delete the created block from the list of the admin product page.
             */
			
			function bkap_delete_block(){
				global $wpdb;
				
				echo $_POST['details'];
				$sql = "DELETE FROM {$wpdb->prefix}booking_fixed_blocks where id = {$_POST['details']}";
 				echo $sql;
 				$wpdb->query( $sql );
				die();
			}

            /**
             * This function are used to delete all the created block from the list of the admin product page.
             */
            function bkap_delete_all_blocks(){
				global $wpdb;
				$post_id = $_POST[ 'post_id' ];
				
				$table_fixed_blocks = "DELETE FROM `" . $wpdb->prefix . "booking_fixed_blocks`
				                        WHERE post_id = %d";
				
				$wpdb->query( $wpdb->prepare( $table_fixed_blocks, $post_id ) );
				 
				die();

			}

            /**
             * This function is used to register all settings of the block to the database.
             *******************************/
			
			function bkap_save_booking_block() {
				global $wpdb;
				
				$post_id            =   $_POST['post_id'];
				$booking_block_name =   $_POST['booking_block_name'];
				$start_day          =   $_POST['start_day'];
				$end_day            =   $_POST['end_day'];
				$price              =   $_POST['price'];
				$number_of_days     =   $_POST['number_of_days'];

				$id                 =   $_POST['id'];
					
				if ( ( $post_id !== "" ) && ( $id == "" ) ) {
					
					$insert_booking_block  =   "INSERT INTO {$wpdb->prefix}booking_fixed_blocks
                            					(post_id,global_id,block_name,number_of_days,start_day,end_day,price,block_type)
                            					VALUE(
                            					'{$post_id}',
                            					'',
                            					'{$booking_block_name}',
                            					'{$number_of_days}',
                            					'{$start_day}',
                            					'{$end_day}',
                            					'{$price}',
                            					'LOCAL' )";
					$wpdb->query( $insert_booking_block );
					
					$id                    =   $wpdb->insert_id;
				
				} else {
					
					$edit_season           =   "UPDATE `".$wpdb->prefix."booking_fixed_blocks`
                            					SET block_name = '".$booking_block_name."',
                            					start_day = '".$start_day."',
                            					end_day = '".$end_day."',
                            					number_of_days = '".$number_of_days."',
                            					price = '".$price."'
                            					WHERE id = '".$id."'";

					$wpdb->query( $edit_season );
					
				}
				echo $id;
				die();
			}
			
		   /**
            * This function are used to display the list of the created block on admin product page
            */
			
			function bkap_booking_block_table(){
 				global $post, $wpdb;
 				$post_id = '';
 				$duplicate_of     =   bkap_common::bkap_get_product_id( $post->ID );
 				if ( isset( $duplicate_of ) ) $post_id = $duplicate_of;
				/* AJAX check  */
				
				if( !empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' ) {
					/* special ajax here */
					$post_id = $_POST['post_id'];

				}

				$query              =   "SELECT * FROM `".$wpdb->prefix."booking_fixed_blocks`
							             WHERE post_id = %d";
				 
				$results            =   $wpdb->get_results($wpdb->prepare($query,$post_id));

				$var                =   "";
				$date_name          =   array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");
 	
				$currency_symbol    =   get_woocommerce_currency_symbol();
				
				foreach ( $results as $key => $value ) {
					$var .= '<tr id="row_'.$value->id.'">
							<td>'.$value->block_name.'</td>
							<td>'.$value->number_of_days.'</td>
							<td>'.$this->days[$value->start_day].'</td>
							<td>'.$this->days[$value->end_day].'</td>
							<td>'.$currency_symbol . $value->price.'</td>
							<td> <a href="javascript:void(0);" id="'.$value->id.'&'.$value->start_day.'&'.$value->end_day.'&'.$value->block_name.'&'.$value->price.'&'.$value->number_of_days.'" class="edit_block"> <img src="'.plugins_url().'/woocommerce-booking/images/edit.png" alt="Edit Fixed Block" title="Edit Fixed Block"></a>
							<a href="javascript:void(0);" id="'.$value->id.'" class="delete_block"> <img src="'.plugins_url().'/woocommerce-booking/images/delete.png" alt="Delete Fixed Block" title="Delete Fixed Block"></a> </td>
							</tr>';
				}
				?>
				<div id="block_booking_table">
					<p>
					<?php _e( 'Booking Blocks', 'woocommerce-booking' ); ?>
					<?php print('<a style="float:right;" href="javascript:void(0);" id="'.$post_id.'" class="delete_all_blocks">'.__( "Delete All Blocks", "woocommerce-booking" ).' </a>');?>	
					<table class='wp-list-table widefat fixed posts' cellspacing='0' id='list_blocks'>
						<tr>
							<th> <?php _e( 'Block Name',     'woocommerce-booking' ); ?> </th>
							<th> <?php _e( 'Number of Days', 'woocommerce-booking' ); ?></th>
							<th> <?php _e( 'Start Day',      'woocommerce-booking' ); ?> </th>
							<th> <?php _e( 'End Day',        'woocommerce-booking' ); ?> </th>
							<th> <?php _e( 'Price',          'woocommerce-booking' ); ?> </th>
							<th> <?php _e( 'Actions',        'woocommerce-booking' ); ?> </th>  
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
            * This function will save the settings for the fixed block feature. 
            */
			
			function bkap_fixed_block_product_settings_save( $booking_settings, $product_id ) {
				
			    if( isset( $_POST['booking_fixed_block_enable'] ) ) {
					$booking_settings['booking_fixed_block_enable'] = $_POST['booking_fixed_block_enable'];
				}
				return $booking_settings;
			}
			
		   /**
			* This function checks whether any addons need to be added to the price of the fixed block. 
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
            * This function is used to show the price updation of the fixed block on the front end.
            */
			function bkap_fixed_block_show_updated_price( $product_id, $product_type, $variation_id, $checkin_date, $checkout_date, $currency_selected ) {
				if ( !isset( $_POST['variable_blocks'] ) || ( isset( $_POST['variable_blocks'] ) && $_POST['variable_blocks'] != 'Y' ) ) {
					
				    $duplicate_of     =   bkap_common::bkap_get_product_id( $post->ID );
				    $booking_settings =   get_post_meta( $duplicate_of, 'woocommerce_booking_settings', true );
				    
				   // $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
					
					if ( $product_type == 'variable' ) {
						
					    if ( $variation_id != '' ) {	
							$price = get_post_meta( $variation_id, '_sale_price', true );
							
							if( !isset( $price ) || $price == '' || $price == 0 ){
								$price = get_post_meta( $variation_id, '_regular_price', true );
							}
						
							if ( isset( $booking_settings['booking_fixed_block_enable'] ) && $booking_settings['booking_fixed_block_enable']  == "yes" ) {
								$price += $_POST['block_option_price'];
							}
						}
						else {
							$price = 0;
						}
					} elseif ( $product_type == 'simple' ) {
						
					    if ( isset( $booking_settings['booking_fixed_block_enable'] ) && $booking_settings['booking_fixed_block_enable']  == "yes" ) {
							$price = $_POST['block_option_price'];
						}else {
							$price = get_post_meta( $product_id, '_sale_price', true );
							
							if(!isset($price) || $price == '' || $price == 0){
								$price = get_post_meta( $product_id, '_regular_price', true );
							}
						}
					}
				}else {
					
				    if ( isset( $_POST['price'] ) ) {
						$price = $_POST['price'];
					}
				}
				$error_message = __( "Please select an option.", "woocommerce-booking" );
				if ( function_exists( 'is_bkap_deposits_active' ) && is_bkap_deposits_active() || function_exists( 'is_bkap_seasonal_active' ) && is_bkap_seasonal_active() ) {
					if ( isset( $price ) && $price != '' ) {
						$_POST['price'] = $price;
					}
					else {
					    print( 'jQuery( "#show_time_slot" ).html( "' . addslashes( $error_message ) . '");' );
						die();
					}
				}
				else {
					if ( isset( $price ) && $price != '' ) {
						$_POST['price'] = $price;
					}
					else {
					    print( 'jQuery( "#show_time_slot" ).html( "' . addslashes( $error_message ) . '");' );
						die();
					}
				}
			}
			
		   /**
			* This function return the count of the fixed block created from the admin side.
            */
			static function bkap_get_fixed_blocks_count( $post_id ) {
				global $wpdb;
				
				$query      = "SELECT * FROM `".$wpdb->prefix."booking_fixed_blocks` WHERE post_id = %d";
				$results    = $wpdb->get_results($wpdb->prepare($query,$post_id));
				
				return count( $results );
			}
			
			/**
             * This function will get the fixed block added in the admin side.
             */
			static function bkap_get_fixed_blocks($post_id) {
				global $wpdb;
				$query      = "SELECT * FROM `".$wpdb->prefix."booking_fixed_blocks` WHERE post_id = %d";
				$results    = $wpdb->get_results( $wpdb->prepare( $query, $post_id ) );
			
				return $results;
			}
			
			/**
			 * This function will return translated string.
			 */			
			function bkap_get_translated_texts ( $get_translated_text, $message, $language ) {
			    
			        if (function_exists('icl_register_string')) {
    			        if ( $language == 'en' ) {
    			            return $message;
    			        } else {
    			            global $wpdb;
    			            $context = 'BKAP';
    			            $translated = '';
    			            $results = $wpdb->get_results($wpdb->prepare("
    			                SELECT s.name, s.value, t.value AS translation_value, t.status
    			                FROM  {$wpdb->prefix}icl_strings s
    			                LEFT JOIN {$wpdb->prefix}icl_string_translations t ON s.id = t.string_id
    			                WHERE s.context = %s
    			                AND (t.language = %s OR t.language IS NULL)
    			                ", $context, $language), ARRAY_A);
    			            
    			                foreach ($results as $each_entry) {
            			                if ($each_entry['name'] == $get_translated_text) {
                			                if ($each_entry['translation_value']) {
                			                    $translated = $each_entry['translation_value'];
                			                } else {
                			                    $translated = $each_entry['value'];
                			                }
            			                }
    			                }
    			                return $translated;
    			         }
			          }else {
			              return $message;
			          }
		  }
			
		}
	}
	$bkap_block_booking = new bkap_block_booking();
 
?>