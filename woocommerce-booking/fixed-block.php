<?php 
	/**
	 * Localisation
	 **/
	load_plugin_textdomain('bkap_block_booking', false, dirname( plugin_basename( __FILE__ ) ) . '/');

	/**
	 * bkap_deposits class
	 **/
	if (!class_exists('bkap_block_booking')) {

		class bkap_block_booking {

			public function __construct() {
				// Initialize settings
				register_activation_hook( __FILE__, array(&$this, 'bkap_block_booking_activate'));
				
				// used to add new settings on the product page booking box
				add_action('bkap_after_listing_enabled', array(&$this, 'bkap_fixed_block_show_field_settings'));
				add_action('init', array(&$this, 'bkap_load_ajax_fixed_block'));
				add_filter('bkap_save_product_settings', array(&$this, 'bkap_fixed_block_product_settings_save'), 10, 2);
				add_action('bkap_display_multiple_day_updated_price', array(&$this, 'bkap_fixed_block_show_updated_price'),5,6);
				add_filter('bkap_addon_add_cart_item_data', array(&$this, 'bkap_fixed_block_add_cart_item_data'), 5, 3);
				add_filter('bkap_get_cart_item_from_session', array(&$this, 'bkap_fixed_block_get_cart_item_from_session'),11,2);

				add_action( 'woocommerce_before_add_to_cart_form', array(&$this, 'bkap_fixed_block_before_add_to_cart'));
				add_action( 'woocommerce_before_add_to_cart_button', array(&$this, 'bkap_fixed_block_booking_after_add_to_cart'));

			//	add_filter('bkap_get_item_data', array(&$this, 'bkap_get_item_data'), 10, 2 );
				add_action('bkap_deposits_update_order', array(&$this, 'bkap_fixed_block_order_item_meta'), 10,2);
				add_action('bkap_display_price_div', array(&$this, 'bkap_fixed_block_display_price'),10,1);
				
				$this->days = array( 'any_days' => 'Any Days',
						'0' => 'Sunday',
						'1' => 'Monday',
						'2' => 'Tuesday',
						'3' => 'Wednesday',
						'4' => 'Thursday',
						'5' => 'Friday',
						'6' => 'Saturday'
				);
				
			}
			
                        /*******************************
                         *This function detects when the booking plugin is activated and creates the table necessary in database,if they do not exists. 
                         ******************************/
			function bkap_block_booking_activate() {
			
				global $wpdb;
				
				$table_name = $wpdb->prefix . "booking_fixed_blocks";
				
				$sql = "CREATE TABLE IF NOT EXISTS ".$table_name." (
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
				
				
				
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
			}
			/******************************************
                         *  This function is used to load ajax functions required by fixed block booking.
                         ******************************************/
			function bkap_load_ajax_fixed_block() {
				if ( !is_user_logged_in() ) {
					add_action('wp_ajax_nopriv_bkap_save_booking_block',  array(&$this,'bkap_save_booking_block'));
					add_action('wp_ajax_nopriv_bkap_booking_block_table',  array(&$this,'bkap_booking_block_table'));
					add_action('wp_ajax_nopriv_bkap_delete_block',  array(&$this,'bkap_delete_block'));
					add_action('wp_ajax_nopriv_bkap_delete_all_blocks',  array(&$this,'bkap_delete_all_blocks'));
					add_action('wp_ajax_nopriv_save_global_season',  array(&$this,'save_global_season'));
					add_action('wp_ajax_nopriv_delete_global_season',  array(&$this,'delete_global_season'));
					add_action('wp_ajax_nopriv_delete_all_global_seasons',  array(&$this,'delete_all_global_seasons'));
				} else {
					add_action('wp_ajax_bkap_save_booking_block',  array(&$this,'bkap_save_booking_block'));
					add_action('wp_ajax_bkap_booking_block_table',  array(&$this,'bkap_booking_block_table'));
					add_action('wp_ajax_bkap_delete_block',  array(&$this,'bkap_delete_block'));
					add_action('wp_ajax_bkap_delete_all_blocks',  array(&$this,'bkap_delete_all_blocks'));
					add_action('wp_ajax_save_global_season',  array(&$this,'save_global_season'));
					add_action('wp_ajax_delete_global_season',  array(&$this,'delete_global_season'));
					add_action('wp_ajax_delete_all_global_seasons',  array(&$this,'delete_all_global_seasons'));
				}
			}

			function bkap_fixed_block_before_add_to_cart(){
			
			}
                        /*******************************************
                         *This function add the fixed block fields on the frontend product page as per the settings selected when Enable Fixed Block Booking is enabled.
                         *****************************************/
			function bkap_fixed_block_booking_after_add_to_cart(){	
				global $post, $wpdb, $woocommerce;
 				$booking_settings = get_post_meta($post->ID, 'woocommerce_booking_settings', true);

 				 if ((isset($booking_settings['booking_enable_multiple_day']) && $booking_settings['booking_enable_multiple_day'] == 'on') && (isset($booking_settings['booking_fixed_block_enable']) && $booking_settings['booking_fixed_block_enable'] == 'yes' )) {
 				 	
 				 	$results = $this->bkap_get_fixed_blocks($post->ID);
					foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
								//print_r($values);exit;
								if(array_key_exists('booking',$values)) {
									$booking = $values['booking'];
									$hidden_date = $booking[0]['hidden_date'];
									if(array_key_exists("hidden_date_checkout",$booking[0])) {
										$hidden_date_checkout = $booking[0]['hidden_date_checkout'];
									}
								}
								break;
								//print_r($hidden_date_checkout);
							}
 				 	
					if (count($results) > 0) {

						printf ('

						<label> Select Period : </label><select name="block_option" id="block_option" >');
						foreach ($results as $key => $value) {
							printf('<option value=%s&%s&%s>%s</option>',$value->start_day,$value->number_of_days,$value->price,$value->block_name);
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
								
							}
						});
	
	
						</script>
	
						<?php

						if (count($results)>=0) {
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

                        /*********************************************
                        * This function display the price after selecting the date on front end.
                         ****************************************************/
			function bkap_fixed_block_display_price($product_id) {
				$booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true);
				if(isset($_POST['booking_fixed_block_enable']) && $_POST['booking_partial_payment_radio']!=''):
					$currency_symbol = get_woocommerce_currency_symbol();
					$show_price = 'show';
					print('<div id="show_addon_price" name="show_addon_price" class="show_addon_price" style="display:'.$show_price.';">'.$currency_symbol.' 0</div>');
				endif;
			}
                        /**********************************************
                         * This function add the fixed block table on the admin side.
                         * It allows to create blocks on the admin product page.
                         *************************************************/
			function bkap_fixed_block_show_field_settings($product_id) {
				global $post, $wpdb;
				?>
				<script type="text/javascript">
					jQuery(".woo-nav-tab-wrapper").append("<a href=\"javascript:void(0);\" class=\"nav-tab\" id=\"block_booking\" onclick=\"bkap_tab_pay_display_2('block_booking')\"> <?php _e( 'Fixed Block Booking', 'woocommerce-booking' );?> </a>");
					
                                                /*********************************
                                                 * This function display the settings field of fixed block booking, and hide the other tab settings.
                                                 *******************************/
                                                function bkap_tab_pay_display_2(id){
						 
						jQuery( "#block_booking_page").show();
						jQuery( "#reminder_wrapper" ).hide();
						jQuery( "#payments_page").hide();
						jQuery( "#seasonal_pricing" ).hide();
						jQuery( "#tours_page").hide();
						jQuery( "#rental_page" ).hide();
						jQuery( "#date_time" ).hide();
						jQuery( "#listing_page" ).hide();
						jQuery( "#block_booking_price_page" ).hide();
						jQuery( "#list" ).attr("class","nav-tab");
						jQuery( "#addnew" ).attr("class","nav-tab");
						jQuery( "#tours" ).attr("class","nav-tab");
						jQuery( "#seasonalpricing" ).attr("class","nav-tab");
						jQuery( "#rental" ).attr("class","nav-tab");
						jQuery( "#payments" ).attr("class","nav-tab");
						jQuery( "#reminder" ).attr("class","nav-tab");
						jQuery( "#block_booking_price" ).attr("class","nav-tab");
						jQuery( "#block_booking" ).attr("class","nav-tab nav-tab-active");
				 
					}
				</script>
				<div id="block_booking_page" style="display:none;">
				<table class='form-table'>
					<tr id="fixed_block">
						<th>
							<label for="booking_fixed_block"><b><?php _e( 'Enable Fixed Block Booking', 'bkap_block_booking');?></b></label>
						</th>
						<td>
							<?php 
							$booking_settings = get_post_meta($product_id, 'woocommerce_booking_settings', true);
							$enabled_s_pricing = "";
							$add_block_button_show = 'none';
							if(isset($booking_settings['booking_fixed_block_enable'])){
                                                            $product_enable_seasonal_pricing = $booking_settings['booking_fixed_block_enable'];
                                                        }
							
							if(isset($product_enable_seasonal_pricing) && $product_enable_seasonal_pricing == 'yes') {
								$enabled_s_pricing = "checked";
								$add_block_button_show = 'block';
								
							}
							?>
							<input type="checkbox" name="booking_fixed_block_enable" id="booking_fixed_block_enable" value="yes" <?php echo $enabled_s_pricing;?>></input>
							<img class="help_tip" width="16" height="16" data-tip="<?php _e('Enable to allow Fixed Block Pricing for the product.', 'bkap_block_booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png"/>
						</td>
					</tr>
				</table>
				<script type="text/javascript">
					jQuery("#booking_fixed_block_enable").change(function() {
						jQuery("#add_block_button").toggle();
						if(!jQuery('#booking_fixed_block_enable').attr('checked')) {
							document.getElementById("add_block").style.display = "none";
						}
						});
				</script>
				<p>
				<div id="add_block_button" name="add_block_button" style="display:<?php echo $add_block_button_show; ?>;">
				<input type="button" class="button-primary" value="Add New Booking Block" id="add_another" onclick="bkap_show_div_fixed_blocks()">
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
				 	<label for="add_block_label"><b> Enter a Block: </b></label>
				</tr>
				<tr>
					<table>
					<input type="hidden" name="id_booking_block" value=""/>
					<tr>
						<td><b><label for="fixed_block_name"><?php _e( 'Booking Block Name: ', 'bkap_block_booking');?></b></label></td>
						<td><b><label for="number_of_days" ><?php _e( 'Number of days: ', 'bkap_block_booking');?></b></label></td>
						<td><b><label for="fixed_block_start_date" ><?php _e( 'Start Day: ', 'bkap_block_booking');?></b></label></td>
						<td><b><label for="fixed_block_end_date" ><?php _e( 'End Day: ', 'bkap_block_booking');?></b></label></td>
						<td><b><label for="fixed_block_price" ><?php _e( 'Price: ', 'bkap_block_booking');?></b></label><br></td>
					</tr>
				<tr>
						<td><input type="text" id="booking_block_name" name="booking_block_name"></input></td>
						<td><input type="text" id="number_of_days" name="number_of_days" size="10"></input></td>
						<td><select id="start_day" name="start_day">
						<?php 
						$days = $this->days;
						foreach ($days as $dkey => $dvalue) {
							?>
							<option value="<?php echo $dkey; ?>"><?php echo $dvalue; ?></option>
							<?php 
						}
						?>
					</select></td>
						<td><select id="end_day" name="end_day">
						<?php 
						foreach ($days as $dkey => $dvalue) {
							?>
							<option value="<?php echo $dkey; ?>"><?php echo $dvalue; ?></option>
							<?php 
						}
						?>
					</select></td>
						<td><input type="text" id="fixed_block_price" name="fixed_block_price" size="10"></input><br>
</td>
						 					
					<input type="hidden" id="table_id" name="table_id"></input><br>
					</tr>

					<tr>
					
					<td>
					<input type="button" class="button-primary" value="Save Block" id="save_another" onclick="bkap_save_booking_block()"></input>
					<input type="button" class="button-primary" value="Close" id="close_div" onclick="bkap_close_booking_block()"></input></td>
					<td colspan="4"></td>
					</tr>					

					</table>	
				</tr>
				</table>
				</div>
				
				<?php $this->bkap_booking_block_table(); ?>
				
				</div>

				<?php
				 
				print('<script type="text/javascript">
                                //************************************
                                // This function will save the created fixed block on the admin product page.
                                //************************************
				function bkap_save_booking_block() {
					if (jQuery("#fixed_block_price").val() == "") {
						alert("Price cannot be blank.");
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
							post_id: "'.$post->ID.'", 
							booking_block_name: jQuery("#booking_block_name").val(),
							start_day: jQuery("#start_day").val(),
							end_day: jQuery("#end_day").val(),
							price: jQuery("#fixed_block_price").val(),
							number_of_days: jQuery("#number_of_days").val(),
							id: jQuery("#table_id").val(),
							action: "bkap_save_booking_block"
							};

							
							jQuery.post("'.get_admin_url().'/admin-ajax.php", data, function(response) {
                                              
                               // 	alert(response);
						
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
									
									x[5].innerHTML = edit_data;
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
							//		alert(edit_id);
									var edit_data = "<a href=\"javascript:void(0);\" id=\""+edit_id+"\" class=\"edit_block\"> <img src=\"'.plugins_url().'/woocommerce-booking/images/edit.png\" alt=\"Edit Fixed Block\" title=\"Edit Fixed Block\"></a>";
									var cell6 = row.insertCell(5);
									cell6.innerHTML = edit_data;
									
									var delete_data = "<a href=\"javascript:void(0);\" id=\""+insert_id+"\" class=\"delete_block\"> <img src=\"'.plugins_url().'/woocommerce-booking/images/delete.png\" alt=\"Delete Fixed Block\" title=\"Delete Fixed Block\"></a>"; 
									var cell7 = row.insertCell(6);
									cell7.innerHTML = delete_data;
						
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
				
				
                /*******************************************************
                * This function displays the div with the fields when 
                Add new Booking Block button is clicked on the admin 
                product page
                ********************************************************/
				function bkap_show_div_fixed_blocks() {
					jQuery("#fixed_block_name").val("");
					jQuery("#start_day").val("any_days");
					jQuery("#end_day").val("any_days");
					jQuery("#fixed_block_price").val("");
					jQuery("#table_id").val("");
					document.getElementById("add_block").style.display = "block";
					jQuery("#add_block").show();
				}
				/******************************************
                                 * This function will hide the div when close button is clicked
                                 ******************************************/
				function bkap_close_booking_block() {
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
						var y=confirm('Are you sure you want to delete this block?');
						//alert(y);
						if(y==true) {
							var passed_id = this.id;
							var data = {
								details: passed_id,
								action: 'bkap_delete_block'
							};	
							jQuery.post('<?php echo get_admin_url();?>/admin-ajax.php', data, function(response) {
								//alert('Got this from the server: ' + response);
								jQuery("#row_" + passed_id ).hide();
							});
						}
					});
					jQuery("table#list_blocks a.delete_all_blocks").click(function() {
						var y=confirm('Are you sure you want to delete all the blocks?');
						if(y==true) {
							//var passed_id = this.id;
							//	alert(exploded_id);
							var data = {
								//details: passed_id,
								action: "bkap_delete_all_blocks"
							};
							/*jQuery.post('<?php echo get_admin_url();?>/admin-ajax.php', data, function(response)
							{
								//	alert('Got this from the server: ' + response);
								console.log(response);
								jQuery("table#list_blocks").hide();
							});*/

							jQuery.ajax({
                            url: '<?php echo get_admin_url();?>/admin-ajax.php',
                            type: "POST",
                            data : data,

                            // dataType: "html",
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
			/*******************************
                         * This function are used to delete the created block from the list of the admin product page.
                         ******************************/
			function bkap_delete_block(){
				global $wpdb;
				echo $_POST['details'];
				$sql="DELETE FROM {$wpdb->prefix}booking_fixed_blocks where id = {$_POST['details']}";
 				echo $sql;
 				$wpdb->query($sql);
				die();
			}

			/*******************************
                         * This function are used to delete all the created block from the list of the admin product page.
                         ******************************/
                        function bkap_delete_all_blocks(){
				global $wpdb;
				$sql="Truncate table wp_booking_fixed_blocks";
				
				$wpdb->query($sql);
				 
				die();

			}

                        /********************************
                         * This function is used to register all settings of the block to the database.
                         *******************************/
			function bkap_save_booking_block() {
				// print_r($_POST);exit;
				 
				global $wpdb;
				$post_id = $_POST['post_id'];
				$booking_block_name = $_POST['booking_block_name'];
				$start_day = $_POST['start_day'];
				$end_day = $_POST['end_day'];
				$price = $_POST['price'];
				$number_of_days = $_POST['number_of_days'];

				$id = $_POST['id'];
					
				//$start_season_date = date('Y-m-d',strtotime($start_date));
				//$end_season_date = date('Y-m-d',strtotime($end_date));
			
				if ( ($post_id !== "") && ($id == "")) {
					
					$insert_booking_block = "INSERT INTO {$wpdb->prefix}booking_fixed_blocks
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
					$wpdb->query($insert_booking_block);
					
					$id = $wpdb->insert_id;
				
				} else {
					
					$edit_season = "UPDATE `".$wpdb->prefix."booking_fixed_blocks`
					SET block_name = '".$booking_block_name."',
					start_day = '".$start_day."',
					end_day = '".$end_day."',
					number_of_days = '".$number_of_days."',
					price = '".$price."'
					WHERE id = '".$id."'";

					$wpdb->query($edit_season);
					
				}
				echo $id;
				die();
			}
			/*******************************************
                         * This function are used to display the list of the created block on admin product page
                         *******************************************/
			function bkap_booking_block_table(){
 				global $post, $wpdb;
 				$post_id='';
 				if (isset($post->ID)) $post_id=$post->ID;
				/* AJAX check  */
				if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
					/* special ajax here */
					//die($content);
					//echo 'this is ajax ='. 
					$post_id=$_POST['post_id'];

				}

				$query = "SELECT * FROM `".$wpdb->prefix."booking_fixed_blocks`
							WHERE post_id = %d";
				 
				$results = $wpdb->get_results($wpdb->prepare($query,$post_id));

				$var = "";
				$date_name = array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");
 
				foreach ($results as $key => $value) {
					$var .= '<tr id="row_'.$value->id.'">
							<td>'.$value->block_name.'</td>
							<td>'.$value->number_of_days.'</td>
							<td>'.$this->days[$value->start_day].'</td>
							<td>'.$this->days[$value->end_day].'</td>
							<td>'.$value->price.'</td>
							<td> <a href="javascript:void(0);" id="'.$value->id.'&'.$value->start_day.'&'.$value->end_day.'&'.$value->block_name.'&'.$value->price.'&'.$value->number_of_days.'" class="edit_block"> <img src="'.plugins_url().'/woocommerce-booking/images/edit.png" alt="Edit Fixed Block" title="Edit Fixed Block"></a> </td>
							<td> <a href="javascript:void(0);" id="'.$value->id.'" class="delete_block"> <img src="'.plugins_url().'/woocommerce-booking/images/delete.png" alt="Delete Fixed Block" title="Delete Fixed Block"></a> </td>
							</tr>';
				}
				?>
				<div id="block_booking_table">
					<p>
					<table class='wp-list-table widefat fixed posts' cellspacing='0' id='list_blocks'>
						<tr>
							<b>Booking Blocks</b>
						</tr>	
						<tr>
							<th> <?php _e('Block Name', 'bkap_block_booking');?> </th>
							<th> <?php _e('Number of Days', 'bkap_block_booking');?></th>
							<th> <?php _e('Start Day', 'bkap_block_booking');?> </th>
							<th> <?php _e('End Day', 'bkap_block_booking');?> </th>
							<th> <?php _e('Price', 'bkap_block_booking');?> </th>
							<th> Edit </th>
							<?php print('<th> <a href="javascript:void(0);" id="'.$post_id.'" class="delete_all_blocks"> Delete All </a> </th>');	?>  
						</tr>
						<?php 
						if (isset($var)) {
							echo $var;
						}
						?>
					</table>
					</p>
				</div>					

			<?php
			}
                        /****************************************
                         * This function will save the settings for the fixed block feature. 
                         ****************************************/
			function bkap_fixed_block_product_settings_save($booking_settings, $product_id) {
				if(isset($_POST['booking_fixed_block_enable'])) {
					$booking_settings['booking_fixed_block_enable'] = $_POST['booking_fixed_block_enable'];
				}
				return $booking_settings;
			}
			
                        /************************************
                         * This function return fixed block details when add to cart button click on front end.
                         *************************************/
			function bkap_fixed_block_add_cart_item_data($cart_arr, $product_id, $variation_id)
			{
				if (!isset($_POST['variable_blocks']) || (isset($_POST['variable_blocks']) && $_POST['variable_blocks'] != 'Y')):
					$currency_symbol = get_woocommerce_currency_symbol();
					$booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true);
	
					$fixed_blocks_count = $this->bkap_get_fixed_blocks_count($product_id);
					if(isset($booking_settings['booking_fixed_block_enable']) && $booking_settings['booking_fixed_block_enable'] == "yes" && $fixed_blocks_count > 0) {
						$product = get_product($product_id);
						$product_type = $product->product_type;
						
						if ( $product_type == 'variable') {
							$price = get_post_meta( $variation_id, '_sale_price', true);
							if($price == '') {
								$price = get_post_meta( $variation_id, '_regular_price', true);
							}
						} elseif($product_type == 'simple') {
							$price = get_post_meta( $product_id, '_sale_price', true);
							if($price == '') {
								$price = get_post_meta( $product_id, '_regular_price', true);
							}
						}
						$date_disp = $_POST['booking_calender'];
				
						$hidden_date = $_POST['wapbk_hidden_date'];
						if (isset($booking_settings['booking_fixed_block_enable']) && $booking_settings['booking_fixed_block_enable'] == "yes" && isset($cart_arr['price'])) :
	                                            if($product_type == 'variable'){ //Make this chnage
	                                                $price = $price + $_POST['block_option_price'];
	                                            } else{
	                                                $price =0;
													$price = $_POST['block_option_price'];
	                                            }
	                                            $diff_days=1; 
						endif;
	
						if (isset($booking_settings['booking_enable_multiple_day']) && $booking_settings['booking_enable_multiple_day'] == 'on') {	
							$diff_days = $_POST['wapbk_diff_days'];
							if (isset($booking_settings['booking_fixed_block_enable'])&& $booking_settings['booking_fixed_block_enable'] == "yes")  {
								$total = $price;
							}
							else {
								$price = $price * $diff_days;
							}
							
						}
						else {
							if (isset($booking_settings['booking_fixed_block_enable']) && $booking_settings['booking_fixed_block_enable'] == "yes") {
								$total = $price ;
							} 
						}
						$global_settings = json_decode(get_option('woocommerce_booking_global_settings'));
						if (isset($global_settings->enable_rounding) && $global_settings->enable_rounding == "on") {
							$price = round($price);
						}
					}
					if (function_exists('is_bkap_deposits_active') && is_bkap_deposits_active() || function_exists('is_bkap_seasonal_active') && is_bkap_seasonal_active()) {
						if (isset($price) && $price != '') {
							$_POST['price'] = $price;
						}
					}
					else {
						if (isset($price) && $price != '') {
							$cart_arr['price'] = $price;
						}
					}
					//Round the price if needed
						
					$global_settings = json_decode(get_option('woocommerce_booking_global_settings'));
					if (isset($global_settings->enable_rounding) && $global_settings->enable_rounding == "on" && isset($cart_arr['price'])) {
						$cart_arr['price'] = round($cart_arr['price']);
					}
				endif;
				
				return $cart_arr;
			}
			
                        /***********************************
                         * This function adjust the prices calculated from the plugin in the cart session.
                         ***********************************/
			function bkap_fixed_block_get_cart_item_from_session( $cart_item, $values ) {
				$booking_settings = get_post_meta($cart_item['product_id'], 'woocommerce_booking_settings', true);
				if(isset($booking_settings['booking_fixed_block_enable']) && $booking_settings['booking_fixed_block_enable'] == 'yes '&&is_plugin_active('bkap-deposits/deposits.php')) {
					if (isset($values['booking'])) :
					$cart_item['booking'] = $values['booking'];
					
					if($cart_item['booking'][0]['date'] != '') {
						if(isset($booking_settings['booking_fixed_block_enable'])) {
							$cart_item = $this->bkap_get_add_cart_item( $cart_item );
						}
					}
					endif;
				}
				return $cart_item;
			}
			
                        /*********************************************
                         * This function checks whether any addons need to be added to the price of the fixed block. 
                         *********************************************/
			function bkap_get_add_cart_item( $cart_item ) {
				// Adjust price if addons are set

				if (isset($cart_item['booking'])) :
					$extra_cost = 0;
					foreach ($cart_item['booking'] as $addon) :
							if (isset($addon['price']) && $addon['price']>0) $extra_cost += $addon['price'];
					endforeach;
								
					$cart_item['data']->set_price($extra_cost);
					
				endif;
				return $cart_item;
			}
                        
                        /**************************************
                         * This function displays the fixed block details on cart page, checkout page.
                         ************************************/
			function bkap_get_item_data( $other_data, $cart_item ) {
				$global_settings = json_decode(get_option('woocommerce_booking_global_settings'));
				$booking_settings = get_post_meta( $cart_item['product_id'], 'woocommerce_booking_settings', true);
				if(isset($booking_settings['booking_fixed_block_enable']) && $booking_settings['booking_fixed_block_enable'] == 'yes') {
					if(isset($booking_settings['booking_partial_payment_enable']) && $booking_settings['booking_partial_payment_enable'] =='yes' &&  is_plugin_active('bkap-deposits/deposits.php')) {
						$currency_symbol = get_woocommerce_currency_symbol();
						if (isset($cart_item['booking']))  {
							$price = '';
							foreach ($cart_item['booking'] as $booking)  {
								if(isset($booking_settings['booking_partial_payment_radio'])) {
									if(isset($cart_item['quantity'])) {
										if (isset($global_settings->enable_rounding) && $global_settings->enable_rounding == "on") {
											$booking['Total'] = round($booking['Total'] * $cart_item['quantity']);
											$booking['Deposit'] = round($booking['Deposit'] * $cart_item['quantity']);
											$booking['Remaining'] = round($booking['Remaining'] * $cart_item['quantity']);
										} else {
											$booking['Total'] = $booking['Total'] * $cart_item['quantity'];
											$booking['Deposit'] = $booking['Deposit'] * $cart_item['quantity'];
											$booking['Remaining'] = $booking['Remaining'] * $cart_item['quantity'];
										}
									}
								}
								$price .= "<br> ".book_t('book.item-partial-total').": $currency_symbol".$booking['Total']."<br> ".book_t('book.item-partial-deposit').": $currency_symbol".$booking['Deposit']."<br>".book_t('book.item-partial-remaining').": 
								$currency_symbol".$booking['Remaining'];
							}
						}
						$other_data[] = array(
						'name'    => book_t('book.partial-payment-heading'),
						'display' => $price
					);
					}
				}
				return $other_data;
			}
			/*******************************************
                         * This function updates the database for the fixed block details and add fixed block fields on the order received page,
                         *  and woocommerce edit order when order is placed for woocommerce version greater than 2.0.
                         **********************************************/
			function bkap_fixed_block_order_item_meta( $values,$order) {
				global $wpdb;
				$currency_symbol = get_woocommerce_currency_symbol();
				$product_id = $values['product_id'];
				$quantity = $values['quantity'];
				$booking = $values['booking'];
				$order_item_id = $order->order_item_id;
				$order_id = $order->order_id;
				$global_settings = json_decode(get_option('woocommerce_booking_global_settings'));
				$booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true);
				if (isset($booking_settings['booking_fixed_block_enable']) && isset($booking_settings['booking_partial_payment_radio']) && is_plugin_active("bkap-deposits/deposits.php")){

					if (isset($global_settings->enable_rounding) && $global_settings->enable_rounding == "on") {
						woocommerce_add_order_item_meta($order_item_id,  book_t('book.item-partial-total'), $currency_symbol.round($values['booking'][0]['Total'] *$values['quantity']), true );
						woocommerce_add_order_item_meta($order_item_id,  book_t('book.item-partial-deposit'), $currency_symbol.round($values['booking'][0]['Deposit']* $values['quantity']), true );
						woocommerce_add_order_item_meta($order_item_id,  book_t('book.item-partial-remaining'), $currency_symbol.round($values['booking'][0]['Remaining']* $values['quantity']), true );
					} else {
						woocommerce_add_order_item_meta($order_item_id,  book_t('book.item-partial-total'), $currency_symbol.$values['booking'][0]['Total'] *$values['quantity'], true );
						woocommerce_add_order_item_meta($order_item_id,  book_t('book.item-partial-deposit'), $currency_symbol.$values['booking'][0]['Deposit']* $values['quantity'], true );
						woocommerce_add_order_item_meta($order_item_id,  book_t('book.item-partial-remaining'), $currency_symbol.$values['booking'][0]['Remaining']* $values['quantity'], true );
					}
				}
			}
                        /***********************************************************
                         * This function is used to show the price updation of the fixed block on the front end.
                         ************************************************************/
			function bkap_fixed_block_show_updated_price($product_id,$product_type,$variation_id,$checkin_date,$checkout_date,$currency_selected) {
				if (!isset($_POST['variable_blocks']) || (isset($_POST['variable_blocks']) && $_POST['variable_blocks'] != 'Y')):
					$booking_settings = get_post_meta($product_id, 'woocommerce_booking_settings', true);
					if ($product_type == 'variable') {
						
						$price = get_post_meta( $variation_id, '_sale_price', true);
						if(!isset($price) || $price == '' || $price == 0){
							$price = get_post_meta( $variation_id, '_regular_price', true);
						}
					
						if (isset($booking_settings['booking_fixed_block_enable']) && $booking_settings['booking_fixed_block_enable']  == "yes") {
							$price += $_POST['block_option_price'];
						} 
					} elseif ($product_type == 'simple') {
						if (isset($booking_settings['booking_fixed_block_enable']) && $booking_settings['booking_fixed_block_enable']  == "yes") {
							$price = $_POST['block_option_price'];
						}
						else {
							$price = get_post_meta( $product_id, '_sale_price', true);
							if(!isset($price) || $price == '' || $price == 0){
								$price = get_post_meta( $product_id, '_regular_price', true);
							}
						}
					}
					if (function_exists('is_bkap_deposits_active') && is_bkap_deposits_active() || function_exists('is_bkap_seasonal_active') && is_bkap_seasonal_active()) {
						$_POST['price'] = $price;
					}
					else {
						$price = bkap_common::bkap_multicurrency_price($price,$currency_selected);
						echo $price;
						die();
					}
				endif;
			}
			
                        /**********************************************
                         * This function return the count of the fixed block created from the admin side.
                         *********************************************/
			function bkap_get_fixed_blocks_count($post_id) {
				global $wpdb;
				$query = "SELECT * FROM `".$wpdb->prefix."booking_fixed_blocks` WHERE post_id = %d";
				$results = $wpdb->get_results($wpdb->prepare($query,$post_id));
				
				return count($results);
			}
			/********************************
                         * This function will get the fixed block added in the admin side.
                         ***********************************/
			function bkap_get_fixed_blocks($post_id) {
				global $wpdb;
				$query = "SELECT * FROM `".$wpdb->prefix."booking_fixed_blocks` WHERE post_id = %d";
				$results = $wpdb->get_results($wpdb->prepare($query,$post_id));
			
				return $results;
			}
		}
	}
	$bkap_block_booking = new bkap_block_booking();
 
?>