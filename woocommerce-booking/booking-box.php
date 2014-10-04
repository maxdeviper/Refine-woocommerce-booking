<?php 
include_once('bkap-common.php');

class bkap_booking_box_class{
        
	/****************************************************
    * This function updates the booking settings for each 
    * product in the wp_postmeta table in the database . 
    * It will be called when update / publish button clicked on admin side.
    *****************************************************/
       public static function bkap_process_bookings_box( $post_id, $post ) {

               global $wpdb;

               // Save Bookings
               $product_bookings = array();
               $duplicate_of = bkap_common::bkap_get_product_id($post_id);
               
               $woo_booking_dates = get_post_meta($duplicate_of, 'woocommerce_booking_settings', true);
            
               $enable_inline_calendar = $enable_date = $enable_multiple_day = $specific_booking_chk = $recurring_booking_chk = "";

               if(isset($_POST['enable_inline_calendar'])) {
                       $enable_inline_calendar = $_POST['enable_inline_calendar'];
               }
               if (isset($_POST['booking_enable_date'])) {
                       $enable_date = $_POST['booking_enable_date'];
               }

               if (isset($_POST['booking_enable_multiple_day'])) {
                       $enable_multiple_day = $_POST['booking_enable_multiple_day'];
               }

               if (isset($_POST['booking_specific_booking'])) {
                       $specific_booking_chk = $_POST['booking_specific_booking'];
               }

               if (isset($_POST['booking_recurring_booking'])) {
                       $recurring_booking_chk = $_POST['booking_recurring_booking'];
               }

               $booking_days = array();
               $new_day_arr = array();
               $weekdays = bkap_get_book_arrays('weekdays');
               foreach ($weekdays as $n => $day_name) {
                       if ( isset($woo_booking_dates['booking_recurring']) && count($woo_booking_dates['booking_recurring']) > 1 ) {
                               if ( isset($_POST[$n]) && $_POST[$n] == 'on' || isset($_POST[$n]) && $_POST[$n] == '') {
                                       $new_day_arr[$n] = $_POST[$n];
                               }
                               if ( isset($_POST[$n]) && $_POST[$n] == 'on' ) {
                                       $booking_days[$n] = $_POST[$n];
                               } else {
                                       $booking_days[$n] = $woo_booking_dates['booking_recurring'][$n];
                               }
                       } 
                       else {
                               if (isset($_POST[$n])) {
                                       $new_day_arr[$n] = $_POST[$n];
                                       $booking_days[$n] = $_POST[$n];
                               } else $new_day_arr[$n] = $booking_days[$n] = '';
                       }
               }

               $specific_booking = '';
               if (isset($_POST['booking_specific_date_booking'])) {
                       $specific_booking = $_POST['booking_specific_date_booking'];
               }
               if($specific_booking != '') {
                       $specific_booking_dates = explode(",",$specific_booking);
               }else{
                       $specific_booking_dates = array();
               }
               $specific_stored_days = array();
               if( isset($woo_booking_dates['booking_specific_date']) && count($woo_booking_dates['booking_specific_date']) > 0) $specific_stored_days = $woo_booking_dates['booking_specific_date'];

               foreach ( $specific_booking_dates as $key => $value ) {
                       if (trim($value != "")) $specific_stored_days[] = $value;
               }
               $minimum_number_days = $maximum_number_days = $without_date = $lockout_date = $product_holiday = $enable_time = $slot_count_value = '';
               if(isset($_POST['booking_minimum_number_days'])) {
                       $minimum_number_days = $_POST['booking_minimum_number_days'];
               }
               if(isset($_POST['booking_maximum_number_days'])){
                       $maximum_number_days = $_POST['booking_maximum_number_days'];
               }
               if (isset($_POST['booking_purchase_without_date'])) {
                       $without_date = $_POST['booking_purchase_without_date'];
               }
               if(isset($_POST['booking_lockout_date'])) {
                       $lockout_date = $_POST['booking_lockout_date'];
               }
               if(isset($_POST['booking_product_holiday'])) {
                       $product_holiday = $_POST['booking_product_holiday'];
               }
               if (isset($_POST['booking_enable_time'])) {
                       $enable_time = $_POST['booking_enable_time'];
               }
               if(isset($_POST['wapbk_slot_count'])) {
                       $slot_count = explode("[", $_POST['wapbk_slot_count']);
                       $slot_count_value = intval($slot_count[1]);
               }
               
               $date_time_settings = array();
               $time_settings = array();
               if( $specific_booking != "" ) {
                       foreach ( $specific_booking_dates as $day_key => $day_value ) {
                               $date_tmstmp = strtotime($day_value);
                               $date_save = date('Y-m-d',$date_tmstmp);
                                       if (isset($_POST['booking_enable_time']) && $_POST['booking_enable_time'] == "on") {
                                               $j=1;
                                               if(isset($woo_booking_dates['booking_time_settings']) && is_array($woo_booking_dates['booking_time_settings'])) {
                                                       if (array_key_exists($day_value,$woo_booking_dates['booking_time_settings'])) {
                                                               foreach ( $woo_booking_dates['booking_time_settings'][$day_value] as $dtkey => $dtvalue ) {
                                                                       $date_time_settings[$day_value][$j] = $dtvalue;
                                                                       $j++;
                                                               }
                                                       }
                                               }
                                               $k = 1;
                                               for($i=($j + 1); $i<=($j + $slot_count_value); $i++) {
                                                       if( isset($_POST['booking_from_slot_hrs'][$k]) && $_POST['booking_from_slot_hrs'][$k] != 0 ) {
                                                               $time_settings['from_slot_hrs'] = $_POST['booking_from_slot_hrs'][$k];
                                                               $time_settings['from_slot_min'] = $_POST['booking_from_slot_min'][$k];
                                                               $time_settings['to_slot_hrs'] = $_POST['booking_to_slot_hrs'][$k];
                                                               $time_settings['to_slot_min'] = $_POST['booking_to_slot_min'][$k];
                                                               $time_settings['booking_notes'] = $_POST['booking_time_note'][$k];
                                                               $time_settings['lockout_slot'] = $_POST['booking_lockout_time'][$k];
                                                               if(isset($_POST['booking_global_check_lockout'][$k])) {
                                                                       $time_settings['global_time_check'] = $_POST['booking_global_check_lockout'][$k];
                                                               } else {
                                                                       $time_settings['global_time_check'] = '';
                                                               }
                                                               $date_time_settings[$day_value][$i] = $time_settings;
                                                               $from_time = $_POST['booking_from_slot_hrs'][$k].":".$_POST['booking_from_slot_min'][$k];
                                                               $to_time = "";
                                                               if(isset($_POST['booking_to_slot_hrs'][$k]) && $_POST['booking_to_slot_hrs'][$k] != 0 ) {
                                                                       $to_time = $_POST['booking_to_slot_hrs'][$k].":".$_POST['booking_to_slot_min'][$k];
                                                               }

                                                               $query_delete = "DELETE FROM `".$wpdb->prefix."booking_history`
                                                                                       WHERE post_id = '".$duplicate_of."'
                                                                                       AND start_date = '".$date_save."'
                                                                                       AND from_time = ''
                                                                                       AND to_time = ''";
                                                               $wpdb->query($query_delete);
                                                               $query_insert = "INSERT INTO `".$wpdb->prefix."booking_history`
                                                                                        (post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
                                                                                        VALUES (
                                                                                        '".$duplicate_of."',
                                                                                        '',
                                                                                        '".$date_save."',
                                                                                        '0000-00-00',
                                                                                        '".$from_time."',
                                                                                        '".$to_time."',
                                                                                        '".$_POST['booking_lockout_time'][$k]."',
                                                                                        '".$_POST['booking_lockout_time'][$k]."' )";
                                                               $wpdb->query( $query_insert );
                                                       }
                                                       $k++;
                                               }
                                       } else {
                                               $query_delete = "DELETE FROM `".$wpdb->prefix."booking_history`
                                                                                       WHERE post_id = '".$duplicate_of."'
                                                                                       AND start_date = '".$date_save."'";
                                                       $wpdb->query($query_delete);
                                               $query_insert = "INSERT INTO `".$wpdb->prefix."booking_history`
                                                                       (post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
                                                                       VALUES (
                                                                       '".$duplicate_of."',
                                                                       '',
                                                                       '".$date_save."',
                                                                       '0000-00-00',
                                                                       '',
                                                                       '',
                                                                       '".$_POST['booking_lockout_date']."',
                                                                       '".$_POST['booking_lockout_date']."' )";
                                               $wpdb->query( $query_insert );
                                       }

                               }
                       }
                       if ( count($new_day_arr) >= 1 ) {
                               foreach ( $new_day_arr as $wkey => $wvalue ) {
                                       if( $wvalue == 'on' ) {
                                               if (isset($_POST['booking_enable_time']) && $_POST['booking_enable_time'] == "on") {
                                                       $j=1;
                                                       if(isset($woo_booking_dates['booking_time_settings']) && is_array($woo_booking_dates['booking_time_settings'])) {
                                                               if (array_key_exists($wkey,$woo_booking_dates['booking_time_settings'])) {
                                                                       foreach ( $woo_booking_dates['booking_time_settings'][$wkey] as $dtkey => $dtvalue ) {
                                                                               $date_time_settings[$wkey][$j] = $dtvalue;
                                                                               $j++;
                                                                       }
                                                               }
                                                       }
                                                       $k = 1;
                                                       for($i=($j + 1); $i<=($j + $slot_count_value); $i++) {
                                                               if(isset($_POST['booking_from_slot_hrs'][$k]) && $_POST['booking_from_slot_hrs'][$k] != 0 ) {
                                                                       $time_settings['from_slot_hrs'] = $_POST['booking_from_slot_hrs'][$k];
                                                                       $time_settings['from_slot_min'] = $_POST['booking_from_slot_min'][$k];
                                                                       $time_settings['to_slot_hrs'] = $_POST['booking_to_slot_hrs'][$k];
                                                                       $time_settings['to_slot_min'] = $_POST['booking_to_slot_min'][$k];
                                                                       $time_settings['booking_notes'] = $_POST['booking_time_note'][$k];
                                                                       $time_settings['lockout_slot'] = $_POST['booking_lockout_time'][$k];
                                                                       if(isset($_POST['booking_global_check_lockout'][$k])) {
                                                                               $time_settings['global_time_check'] = $_POST['booking_global_check_lockout'][$k];
                                                                       } else {
                                                                               $time_settings['global_time_check'] = '';
                                                                       }
                                                                       $date_time_settings[$wkey][$i] = $time_settings;
                                                                       $from_time = $_POST['booking_from_slot_hrs'][$k].":".$_POST['booking_from_slot_min'][$k];
                                                                       $to_time = "";
                                                                       if(isset($_POST['booking_to_slot_hrs'][$k]) && $_POST['booking_to_slot_hrs'][$k] != 0 ) {
                                                                               $to_time = $_POST['booking_to_slot_hrs'][$k].":".$_POST['booking_to_slot_min'][$k];
                                                                       }

                                                                       $query_delete = "DELETE FROM `".$wpdb->prefix."booking_history`
                                                                                       WHERE post_id = '".$duplicate_of."'
                                                                                       AND weekday = '".$wkey."'
                                                                                       AND from_time = ''
                                                                                       AND to_time = ''";
                                                                       $wpdb->query($query_delete);

                                                                       $query_insert_week = "INSERT INTO `".$wpdb->prefix."booking_history`
                                                                                               (post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
                                                                                               VALUES (
                                                                                               '".$duplicate_of."',
                                                                                               '".$wkey."',
                                                                                               '0000-00-00',
                                                                                               '0000-00-00',
                                                                                               '".$from_time."',
                                                                                               '".$to_time."',
                                                                                               '".$_POST['booking_lockout_time'][$k]."',
                                                                                               '".$_POST['booking_lockout_time'][$k]."') ";
                                                                       $wpdb->query( $query_insert_week );
                                                               }	
                                                               $k++;	
                                                       }
                                               } else {
                                                       $query_delete = "DELETE FROM `".$wpdb->prefix."booking_history`
                                                                                       WHERE post_id = '".$duplicate_of."'
                                                                                       AND weekday = '".$wkey."'";
                                                       $wpdb->query($query_delete);
                                                       $query_insert_week = "INSERT INTO `".$wpdb->prefix."booking_history`
                                                                                       (post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
                                                                                       VALUES (
                                                                                       '".$duplicate_of."',
                                                                                       '".$wkey."',
                                                                                       '0000-00-00',
                                                                                       '0000-00-00',
                                                                                       '',
                                                                                       '',
                                                                                       '".$_POST['booking_lockout_date']."',
                                                                                       '".$_POST['booking_lockout_date']."') ";
                                                       $wpdb->query( $query_insert_week );
                                               }

                                       }
                               }
                       }

                       $new_time_settings = $woo_booking_dates;
                       foreach ( $date_time_settings as $dtkey => $dtvalue ) {
                   	  		$new_time_settings['booking_time_settings'][$dtkey] = $dtvalue;
                       }

              
               $booking_settings = array();
               $booking_settings['booking_enable_date'] = $enable_date;
               $booking_settings['enable_inline_calendar'] = $enable_inline_calendar;
               $booking_settings['booking_enable_multiple_day'] = $enable_multiple_day;
               $booking_settings['booking_specific_booking'] = $specific_booking_chk;
               $booking_settings['booking_recurring_booking'] = $recurring_booking_chk;
               $booking_settings['booking_recurring'] = $booking_days;
               $booking_settings['booking_specific_date'] = $specific_stored_days;
               $booking_settings['booking_minimum_number_days'] = $minimum_number_days;
               $booking_settings['booking_maximum_number_days'] = $maximum_number_days;
               $booking_settings['booking_purchase_without_date'] = $without_date;
               $booking_settings['booking_date_lockout'] = $lockout_date;
               $booking_settings['booking_product_holiday'] = $product_holiday;
               $booking_settings['booking_enable_time'] = $enable_time;
               if (isset($new_time_settings['booking_time_settings'])) {
                   $booking_settings['booking_time_settings'] = $new_time_settings['booking_time_settings'];
               }else{ 
                   $booking_settings['booking_time_settings'] = '';
               }
               $booking_settings = (array) apply_filters( 'bkap_save_product_settings', $booking_settings, $duplicate_of );
              
               update_post_meta($duplicate_of, 'woocommerce_booking_settings', $booking_settings);
       }
    
       /*******************************************
        *This function adds a meta box for booking settings on product page.
        ******************************************/
       public static function bkap_booking_box() {

               add_meta_box( 'woocommerce-booking', __('Booking', 'woocommerce-booking'), array('bkap_booking_box_class', 'bkap_meta_box'),'product');
       }

       /**********************************************
        * This function displays the settings for the product in the Booking meta box on the admin product page.
        ********************************************/
       public static function bkap_meta_box() {

               ?>
               <script type="text/javascript">

               // On Radio Button Selection
               jQuery(document).ready(function(){
                                    jQuery("table#list_bookings_specific a.remove_time_data, table#list_bookings_recurring a.remove_time_data").click(function() {
                                              
                                        var y=confirm('Are you sure you want to delete this time slot?');
                                        if(y==true) {
                                                var passed_id = this.id;
                                                var exploded_id = passed_id.split('&');
                                                var data = {
                                                                details: passed_id,
                                                                action: 'bkap_remove_time_slot'
                                                };

                                                jQuery.post('<?php echo get_admin_url();?>/admin-ajax.php', data, function(response)
                                                {
                                                        jQuery("#row_" + exploded_id[0] + "_" + exploded_id[2] ).hide();
                                                });
                                        }

                       });

                       jQuery("table#list_bookings_specific a.remove_day_data, table#list_bookings_recurring a.remove_day_data").click(function() {
         
                                               var y=confirm('Are you sure you want to delete this day?');
                                               if(y==true) {
                                                       var passed_id = this.id;
                                                       var exploded_id = passed_id.split('&');
                                                       var data = {
                                                                       details: passed_id,
                                                                       action: 'bkap_remove_day'
                                                       };
                                                   
                                                       jQuery.post('<?php echo get_admin_url();?>/admin-ajax.php', data, function(response) {
                                                               jQuery("#row_" + exploded_id[0]).hide();
                                                       });

                                               }
                                       });

                       jQuery("table#list_bookings_specific a.remove_specific_data").click(function() {
                                     
                                               var y=confirm('Are you sure you want to delete all the specific date records?');
                                               if(y==true) {
                                                       var passed_id = this.id;
                                                       var data = {
                                                                       details: passed_id,
                                                                       action: 'bkap_remove_specific'
                                                       };
                                     
                                                       jQuery.post('<?php echo get_admin_url();?>/admin-ajax.php', data, function(response) {
                                     
                                                                               jQuery("table#list_bookings_specific").hide();
                                                                       });
                                               }
                                       });

                       jQuery("table#list_bookings_recurring a.remove_recurring_data").click(function() {
                                  
                                               var y=confirm('Are you sure you want to delete all the recurring weekday records?');
                                               if(y==true) {
                                                       var passed_id = this.id;
                                  
                                                       var data = {
                                                                       details: passed_id,
                                                                       action: 'bkap_remove_recurring'
                                                       };
                                  
                                                       jQuery.post('<?php echo get_admin_url();?>/admin-ajax.php', data, function(response) {
                                  
                                                                               jQuery("table#list_bookings_recurring").hide();
                                                                       }); 
                                               }
                                       });

                       jQuery("#booking_enable_multiple_day").change(function() {
                               if(jQuery('#booking_enable_multiple_day').attr('checked')) {
                                       jQuery('#booking_method').hide();
                                       jQuery('#booking_time').hide();
                                       jQuery('#booking_enable_weekday').hide();
                                       jQuery('#selective_booking').hide();
                                       jQuery('#purchase_without_date').hide();
                               } else {
                                       jQuery('#booking_method').show();
                                       jQuery('#inline_calender').show();
                                       jQuery('#booking_time').show();
                                       jQuery('#booking_enable_weekday').show();
                                       jQuery('#selective_booking').show();
                                       jQuery('#purchase_without_date').show();
                               }
                       });
               });
               /******************************************
               * This function displays a new div to add timeslots on the admin product page when Add timeslot button is clicked.
                *******************************************/
               function bkap_add_new_div(id){

                       var exploded_id = id.split('[');
                       var new_var = parseInt(exploded_id[1]) + parseInt(1);
                       var new_html_var = jQuery('#time_slot_empty').html();
                       var re = new RegExp('\\[0\\]',"g");
                       new_html_var = new_html_var.replace(re, "["+new_var+"]");

                       jQuery("#time_slot").append(new_html_var);
                       jQuery('#add_another').attr("onclick","bkap_add_new_div('["+new_var+"]')");
               }
               /*****************************************************
               * This function handles the display of each tab for booking settings on the admin booking page.
                *****************************************************/    
               function bkap_tabs_display(id){

                       if( id == "addnew" ) {
                       //	jQuery( "#reminder_wrapper" ).hide();
                               jQuery( "#date_time" ).show();
                               jQuery( "#listing_page" ).hide();
                               jQuery( "#payments_page" ).hide();
                               jQuery( "#tours_page" ).hide();
                               jQuery( "#rental_page" ).hide();
                               jQuery( "#seasonal_pricing" ).hide();
                               jQuery( "#block_booking_price_page" ).hide();
                               jQuery( "#block_booking_page").hide();
                               jQuery( "#addnew" ).attr("class","nav-tab nav-tab-active");
                       //	jQuery( "#reminder" ).attr("class","nav-tab");
                               jQuery( "#list" ).attr("class","nav-tab");
                               jQuery( "#rental" ).attr("class","nav-tab");
                               jQuery( "#tours" ).attr("class","nav-tab");
                               jQuery( "#seasonalpricing" ).attr("class","nav-tab");
                               jQuery( "#payments" ).attr("class","nav-tab");
                               jQuery( "#block_booking_price" ).attr("class","nav-tab");
                               jQuery( "#block_booking" ).attr("class","nav-tab");
                       } else if( id == "list" ) {
               //		jQuery( "#reminder_wrapper" ).hide();
                               jQuery( "#date_time" ).hide();
                               jQuery( "#rental_page" ).hide();
                               jQuery( "#seasonal_pricing" ).hide();
                               jQuery( "#payments_page" ).hide();
                               jQuery( "#tours_page" ).hide();
                               jQuery( "#listing_page" ).show();
                               jQuery( "#block_booking_price_page" ).hide();
                               jQuery( "#block_booking_page").hide();
                               jQuery( "#list" ).attr("class","nav-tab nav-tab-active");
                               jQuery( "#addnew" ).attr("class","nav-tab");
                       //	jQuery( "#reminder" ).attr("class","nav-tab");
                               jQuery( "#rental" ).attr("class","nav-tab");
                               jQuery( "#tours" ).attr("class","nav-tab");
                               jQuery( "#seasonalpricing" ).attr("class","nav-tab");
                               jQuery( "#payments" ).attr("class","nav-tab");
                               jQuery( "#block_booking_price" ).attr("class","nav-tab");
                               jQuery( "#block_booking" ).attr("class","nav-tab");
                       }
               }

               </script>

<!-- 	 	<form id="booking_form" method="post" action="">  -->
               <h1 class="nav-tab-wrapper woo-nav-tab-wrapper">
               <a href="javascript:void(0);" class="nav-tab nav-tab-active" id="addnew" onclick="bkap_tabs_display('addnew')"> <?php _e( 'Booking Options', 'woocommerce-booking' );?> </a>
               <a href="javascript:void(0);" class="nav-tab " id="list" onclick="bkap_tabs_display('list')"> <?php _e( 'View/Delete Booking Dates, Time Slots', 'woocommerce-booking' );?> </a>
<!-- 	<a href="javascript:void(0);" class="nav-tab " id="reminder" onclick="tabs_display('reminder')"> <?php _e( 'Email Reminder', 'woocommerce-booking' );?> </a> -->
               </h1>
              
               <div id="date_time">
               <table class="form-table">
               <?php 
               global $post, $wpdb;
               $duplicate_of = bkap_common::bkap_get_product_id($post->ID);
               
               do_action('bkap_before_enable_booking', $duplicate_of);
               $booking_settings = get_post_meta($duplicate_of, 'woocommerce_booking_settings', true);
               $add_button_show = 'none';
               $enable_time_checked = '';
               if (isset($booking_settings['booking_enable_time']) && $booking_settings['booking_enable_time'] == 'on') {
                       $add_button_show = 'block';
                       $enable_time_checked = ' checked ';
               }

               ?>
                       <tr>
                       <th>
                       <label for="booking_enable_date"  style="color: brown"> <b> <?php _e( 'Enable Booking Date:', 'woocommerce-booking' );?> </b> </label>
                       </th>
                       <td>
                       <?php 
                       $enable_date = '';
                       if( isset($booking_settings['booking_enable_date']) && $booking_settings['booking_enable_date'] == 'on' ) {
                               $enable_date = 'checked';
                       }
                       ?>
                       <input type="checkbox" id="booking_enable_date" name="booking_enable_date" <?php echo $enable_date;?> >
                       <img class="help_tip" width="16" height="16" data-tip="<?php _e('Enable Booking Date on Products Page', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                       </td>
               </tr>
               <?php 
               do_action('bkap_before_enable_multiple_days', $duplicate_of);
               ?>
               <tr>
                       <th>
                       <label for="booking_enable_multiple_day"  style="color: brown"> <b> <?php _e( 'Allow multiple day booking:', 'woocommerce-booking' );?> </b> </label>
                       </th>
                       <td>
                       <?php 
                       $enable_multiple_day = '';
                       $booking_method_div = $booking_time_div = 'table-row';
                       $purchase_without_date = 'show';
                       if( isset($booking_settings['booking_enable_multiple_day']) && $booking_settings['booking_enable_multiple_day'] == 'on' ) {
                               $enable_multiple_day = 'checked';
                               $booking_method_div = 'none';
                               $booking_time_div = 'none';
                               $purchase_without_date = 'none';
                       }
                       ?>
                       <input type="checkbox" id="booking_enable_multiple_day" name="booking_enable_multiple_day" <?php echo $enable_multiple_day;?> >
                       <img class="help_tip" width="16" height="16" data-tip="<?php _e('Enable Multiple day Bookings on Products Page', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                       </td>
               </tr>

               <tr id="inline_calender" style="display:show">
                       <th>
                       <label for="enable_inline_calendar"> <b> <?php _e( 'Enable Inline Calendar:', 'woocommerce-booking' );?> </b> </label>
                       </th>
                       <td>
                       <?php 
                       $enable_inline_calendar = '';
                       if( isset($booking_settings['enable_inline_calendar']) && $booking_settings['enable_inline_calendar'] == 'on' ) {
                               $enable_inline_calendar= 'checked';
                       }
                       ?>
                       <input type="checkbox" id="enable_inline_calendar" name="enable_inline_calendar" <?php echo $enable_inline_calendar;?> >
                       <img class="help_tip" width="16" height="16" data-tip="<?php _e('Enable Inline Calendar on Products Page', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                       </td>
               </tr>
               <?php do_action('bkap_before_booking_method_select', $duplicate_of);
               ?>
               <tr id="booking_method" style="display:<?php echo $booking_method_div;?>;">
                       <th>
                       <label for="booking_method_select"> <b> <?php _e( 'Select Booking Method(s):', 'woocommerce-booking');?></b></label>
                       </th>
                       <td>
                       <?php 
                       $specific_booking_chk = '';
                       $recurring_div_show = $specific_dates_div_show = 'none';
                       if( (isset($booking_settings['booking_specific_booking']) && $booking_settings['booking_specific_booking'] == 'on') && $booking_settings['booking_enable_multiple_day'] != 'on' ) {
                               $specific_booking_chk = 'checked';
                               $specific_dates_div_show = 'block';
                       }
                       $recurring_booking = '';
                       if( (isset($booking_settings['booking_recurring_booking']) && $booking_settings['booking_recurring_booking'] == 'on') && $booking_settings['booking_enable_multiple_day'] != 'on' ) {
                               $recurring_booking = 'checked';
                               $recurring_div_show = 'block';
                       }
                       ?>
                       <b>Current Booking Method: </b>
                       <?php 
                       if ($specific_booking_chk != 'checked' && $recurring_booking != 'checked') echo "None";
                       if ($specific_booking_chk == 'checked' && $recurring_booking == 'checked') echo "Specific Dates, Recurring Weekdays";
                       if ($specific_booking_chk == 'checked' && $recurring_booking != 'checked') echo "Specific Dates";
                       if ($specific_booking_chk != 'checked' && $recurring_booking == 'checked') echo "Recurring Weekdays";
                       ?> 
                       <br>
                       <input type="checkbox" name="booking_specific_booking" id="booking_specific_booking" onClick="bkap_book_method(this)" <?php echo $specific_booking_chk; ?>> <b> <?php _e('Specific Dates', 'woocommerce-booking');?> </b></input>
                       <img style="margin-left:40px;"  class="help_tip" width="16" height="16" data-tip="<?php _e('Please enable/disable the specific booking dates and recurring weekdays using these checkboxes. Upon checking them, you shall be able to further select dates or weekdays.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" /><br>
                       <input type="checkbox" name="booking_recurring_booking" id="booking_recurring_booking" onClick="bkap_book_method(this)" <?php echo $recurring_booking; ?> > <b> <?php _e('Recurring Weekdays', 'woocommerce-booking');?> </b></input><br>
                       <i>Details of current weekdays and specific dates are available in the second tab.</i>

                       </td>
               </tr>
               </table>

            <script type="text/javascript">
                /*************************************
                 * this function checks which booking method is selected on the admin product page
                 ***************************************/
                    function bkap_book_method(chk) {
                            if ( jQuery( "input[name='booking_specific_booking']").attr("checked")) {
                                    document.getElementById("selective_booking").style.display = "block";
                                    document.getElementById("booking_enable_weekday").style.display = "none";
                            }
                            if (jQuery( "input[name='booking_recurring_booking']").attr("checked")) {
                                    document.getElementById("booking_enable_weekday").style.display = "block";
                                    document.getElementById("selective_booking").style.display = "none";
                            }
                            if ( jQuery( "input[name='booking_specific_booking']").attr("checked") && jQuery( "input[name='booking_recurring_booking']").attr("checked")) {
                                    document.getElementById("booking_enable_weekday").style.display = "block";
                                    document.getElementById("selective_booking").style.display = "block";
                            }
                            if ( !jQuery( "input[name='booking_specific_booking']").attr("checked") && !jQuery( "input[name='booking_recurring_booking']").attr("checked")) {
                                    document.getElementById("booking_enable_weekday").style.display = "none";
                                    document.getElementById("selective_booking").style.display = "none";
                            }
                    }
               </script>

               <div id="booking_enable_weekday" name="booking_enable_weekday" style="display:<?php echo $recurring_div_show; ?>;">
               <table class="form-table">
               <tr>
                       <th>
                       <label for="booking_enable_weekday_dates"> <b> <?php _e( 'Booking Days:', 'woocommerce-booking' );?> </b> </label>
                       </th>
                       <td>
                       <fieldset class="days-fieldset">
                                       <legend><b>Days:</b></legend>
                                       <?php 
                                       $weekdays = bkap_get_book_arrays('weekdays');
                                       foreach ( $weekdays as $n => $day_name) {
                                               print('<input type="checkbox" name="'.$n.'" id="'.$n.'" />
                                               <label for="'.$day_name.'">'.$day_name.'</label>
                                               <br>');
                                       }?>
                                       </fieldset>
                       <img class="help_tip" width="16" height="16" data-tip="<?php _e('Select Weekdays', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                       </td>
               </tr>
               </table>
               </div>

               <div id="selective_booking" name="selective_booking" style="display:<?php echo $specific_dates_div_show; ?>;">
               <table class="form-table">
               <script type="text/javascript">
                                       jQuery(document).ready(function() {
                                       var formats = ["d.m.y", "d-m-yyyy","MM d, yy"];
                                       jQuery("#booking_specific_date_booking").datepick({dateFormat: formats[1], multiSelect: 999, monthsToShow: 1, showTrigger: '#calImg'});
                                       });
               </script>
               <tr>
                       <th>
                       <label for="booking_specific_date_booking"><b><?php _e( 'Specific Date Booking:', 'woocommerce-booking');?></b></label>
                       </th>
                       <td>
                       <textarea rows="4" cols="80" name="booking_specific_date_booking" id="booking_specific_date_booking"></textarea>
                       <img class="help_tip" width="16" height="16" data-tip="<?php _e('Select the specific dates that you want to enable for booking', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" style="vertical-align:top;"/>
                       </td>
               </tr>
               </table>
               </div>

               <table class="form-table">
               <tr>
                       <th>
                       <label for="booking_lockout_date"><b><?php _e( 'Lockout Date after X orders:', 'woocommerce-booking');?></b></label>
                       </th>
                       <td>
                       <?php 
                       $lockout_date = "";
                       if ( isset($booking_settings['booking_date_lockout']) && $booking_settings['booking_date_lockout'] != "" ) {
                               $lockout_date = $booking_settings['booking_date_lockout'];
                               //sanitize_text_field( $lockout_date, true )
                       } else {
                               $lockout_date = "60";
                       }
                       ?>
                       <input type="text" name="booking_lockout_date" id="booking_lockout_date" value="<?php echo sanitize_text_field( $lockout_date, true );?>" >
                       <img class="help_tip" width="16" height="16" data-tip="<?php _e('Set this field if you want to place a limit on maximum bookings on any given date. If you can manage up to 15 bookings in a day, set this value to 15. Once 15 orders have been booked, then that date will not be available for further bookings.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                       </td>
               </tr>
               <?php 
               do_action('bkap_before_minimum_days', $duplicate_of);?>
               <tr>
                       <th>
                       <label for="booking_minimum_number_days"><b><?php _e( 'Minimum Booking time (in days):', 'woocommerce-booking');?></b></label>
                       </th>
                       <td>
                       <?php 
                       $min_days = 0;
                       if ( isset($booking_settings['booking_minimum_number_days']) && $booking_settings['booking_minimum_number_days'] != "" ) {
                               $min_days = $booking_settings['booking_minimum_number_days'];
                       }
                       ?>
                       <input type="text" name="booking_minimum_number_days" id="booking_minimum_number_days" value="<?php echo sanitize_text_field( $min_days, true );?>" >
                       <img class="help_tip" width="16" height="16" data-tip="<?php _e('Enable Booking after X number of days from current date. The customer can select a booking date that is available only after the minimum days that are entered here. For example, if you need 3 days advance notice for a booking, enter 3 here.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                       </td>
               </tr>
               <?php 
               do_action('bkap_before_number_of_dates', $duplicate_of);
               ?>
               <tr>
                       <th>
                       <label for="booking_maximum_number_days"><b><?php _e( 'Number of Dates to choose:', 'woocommerce-booking');?></b></label>
                       </th>
                       <td>
                       <?php 
                       $max_date = "";
                       if ( isset($booking_settings['booking_maximum_number_days']) && $booking_settings['booking_maximum_number_days'] != "" ) {
                               $max_date = $booking_settings['booking_maximum_number_days'];
                       } else {
                               $max_date = "30";
                       }		
                       ?>
                       <input type="text" name="booking_maximum_number_days" id="booking_maximum_number_days" value="<?php echo sanitize_text_field( $max_date, true );?>" >
                       <img class="help_tip" width="16" height="16" data-tip="<?php _e('The maximum number of booking dates you want to be available for your customers to choose from. For example, if you take only 2 months booking in advance, enter 60 here.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                       </td>
               </tr>
               <?php 
               do_action('bkap_before_purchase_without_date', $duplicate_of);
               ?>
               <tr id="purchase_without_date" style="display:<?php echo $purchase_without_date?>;">
                       <th>
                       <label for="booking_purchase_without_date"><b><?php _e( 'Purchase without choosing a date:', 'woocommerce-booking');?></b></label>
                       </th>
                       <td>
                       <?php 
                       $date_show = '';
                       if( isset($booking_settings['booking_purchase_without_date']) && $booking_settings['booking_purchase_without_date'] == 'on' ) {
                               $without_date = 'checked';
                       } else {
                               $without_date = '';
                       }
                       ?>
                       <input type="checkbox" name="booking_purchase_without_date" id="booking_purchase_without_date" <?php echo $without_date; ?>> 
                       <img style="margin-left:40px;"  class="help_tip" width="16" height="16" data-tip="<?php _e('Enables your customers to purchase without choosing a date. This is useful in cases where the customer wants to gift the item.');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" /><br>
                       <i>Useful if you want your customers to be able to purchase the item without choosing the date or as a Gift. Select this option if you want the ADD TO CART button always visible on the product page.</i>
                       </td>
               </tr>
               <?php 
               do_action('bkap_before_product_holidays', $duplicate_of);
               ?>				
               <script type="text/javascript">
                                       jQuery(document).ready(function() {
                                       var formats = ["d.m.y", "d-m-yyyy","MM d, yy"];
                                       jQuery("#booking_product_holiday").datepick({dateFormat: formats[1], multiSelect: 999, monthsToShow: 1, showTrigger: '#calImg'});
                                       });
               </script>
               <tr>
                       <th>
                       <label for="booking_product_holiday"><b><?php _e( 'Select Holidays / Exclude Days / Black-out days:', 'woocommerce-booking');?></b></label>
                       </th>
                       <td>
                       <?php 
                       $product_holiday = "";
                       if ( isset($booking_settings['booking_product_holiday']) && $booking_settings['booking_product_holiday'] != "" ) {
                               $product_holiday = $booking_settings['booking_product_holiday'];
                       }
                       ?>
                       <textarea rows="4" cols="80" name="booking_product_holiday" id="booking_product_holiday"><?php echo $product_holiday; ?></textarea>
                       <img class="help_tip" width="16" height="16" data-tip="<?php _e('Select dates for which the booking will be completely disabled only for this product.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" style="vertical-align:top;"/><br>
                       <i>Please click on the date in calendar to add or delete the date from the holiday list.</i>
                       </td>
               </tr>
               <?php 
               do_action('bkap_before_enable_time', $duplicate_of);
               ?>
               <tr id="booking_time" style="display:<?php echo $booking_time_div; ?>;">
                       <th>
                       <label for="booking_enable_time" style="color: brown"><b><?php _e( 'Enable Booking Time:', 'woocommerce-booking');?></b></label>
                       </th>
                       <td>
                       <?php 
                       $enable_time = "";
                       if( isset($booking_settings['booking_enable_time']) && $booking_settings['booking_enable_time'] == "on" ) {
                               $enable_time = "checked";
                       }
                       ?>
                       <input type="checkbox" name="booking_enable_time" id="booking_enable_time" <?php echo $enable_time;?> onClick="bkap_timeslot(this)">
                       <img class="help_tip" width="16" height="16" data-tip="<?php _e('Enable time (or time slots) on the product. Add any number of booking time slots once you have checked this.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" /><br>
                       <i><font color="brown">Please select the checkbox to add Time Slots<br>
                       You can manage the Time Slots using the "View/Delete Booking Dates, Time Slots" tab shown above, next to "Booking Options".</i></font>
                       </td>
               </tr>
               <?php
                       do_action('bkap_after_time_enabled',$duplicate_of);
               ?>
               </table>
               <script type="text/javascript">
                   /**************************************************
                    * This function displays the Add Time slot button when Enable time slot setting is checked.
                    *****************************************************/
                       function bkap_timeslot(chk) {
                               jQuery("#add_button").toggle();
                               if ( !jQuery( "input[name='booking_enable_time']").attr("checked")) {
                                       document.getElementById("time_slot").style.display = "none";
                               }
                               if( jQuery( "input[name='booking_enable_time']").attr("checked")) {
                                       document.getElementById("time_slot").style.display = "block";
                               }
                       }
               </script>

               <div id="time_slot_empty" name="time_slot_empty" style="display:none;">
               <table class="form-table">
               <tr>
                       <th>
                       <label for="time_slot_label"><b><?php _e( 'Enter a Time Slot:')?></b></label>
                       </th>
                       <td>
                       <b><?php _e( 'From: ', 'woocommerce-booking');?></b>
                       <select name="booking_from_slot_hrs[0]" id="booking_from_slot_hrs[0]">
               <?php
                       for ($i=0;$i<24;$i++) {
                               printf( "<option %s value='%s'>%s</option>\n",
                               selected( $i, '', false ),
                               esc_attr( $i ),
                               $i
                               );
                       }
               ?>
               </select> Hours

               <select name="booking_from_slot_min[0]" id="booking_from_slot_min[0]">
               <?php
                       for ($i=0;$i<60;$i++) {
                           if ( $i < 10 ) {
                               $i = '0'.$i;
                           }
                               printf( "<option %s value='%s'>%s</option>\n",
                               selected( $i, '', false ),
                               esc_attr( $i ),
                               $i
                               );
                       }
               ?>
               </select> Minutes
               &nbsp;&nbsp;&nbsp;

               <b><?php _e( 'To: ', 'woocommerce-booking');?></b>
                       <select name="booking_to_slot_hrs[0]" id="booking_to_slot_hrs[0]">
               <?php
                       for ($i=0;$i<24;$i++) {
                               printf( "<option %s value='%s'>%s</option>\n",
                               selected( $i, '', false ),
                               esc_attr( $i ),
                               $i
                               );
                       }
               ?>
               </select> Hours

               <select name="booking_to_slot_min[0]" id="booking_to_slot_min[0]">
               <?php
                       for ($i=0;$i<60;$i++) {
                           if ( $i < 10 ) {
                               $i = '0'.$i;
                           }
                               printf( "<option %s value='%s'>%s</option>\n",
                               selected( $i, '', false ),
                               esc_attr( $i ),
                               $i
                               );
                       }
               ?>
               </select> Minutes
               <br>
               <i>If do not want a time range, please leave the To Hours and To Minutes unchanged (set to 0).</i><br><br/>

               <label for="booking_lockout_time"><b><?php _e( 'Lockout time slot after X orders:')?></b></label><br>
                       <input type="text" name="booking_lockout_time[0]" id="booking_lockout_time[0]" value="30" />
                       <input type="hidden" id="wapbk_slot_count" name="wapbk_slot_count" value="[0]" /><br>
                       <i>Please enter a number to limit the number of bookings for this time slot. This time slot will be shown on the website <b>only when the lockout field value is greater than 0.</b></i><br>
                       <br/>

                       <label for="booking_global_check_lockout"><b><?php _e( 'Make Unavailable for other products once lockout is reached')?></b></label><br/>
                       <input type="checkbox" name="booking_global_check_lockout[0]" id="booking_global_check_lockout[0]">
                       <i>Please select this checkbox if you want this time slot to be unavailable for all products once the lockout is reached.</i>

               <br/><br/>
               <label for="booking_time_note"><b><?php _e('Note (optional)', 'woocommerce-booking')?></b></label><br>
               <textarea class="short" name="booking_time_note[0]" id="booking_time_note[0]" rows="2" cols="50"></textarea>

               </td>

               </tr>
               </table>
               </div>

               <div id="time_slot" name="time_slot">
               </div>

               <p>
               <div id="add_button" name="add_button" style="display:<?php echo $add_button_show; ?>;">
               <input type="button" class="button-primary" value="Add Time Slot" id="add_another" onclick="bkap_add_new_div('[0')">
               </div>
               </p>

               <!-- <input type="submit" name="save_booking" value="<?php _e('Save Booking', 'woocommerce-booking');?>" class="button-primary"> -->

               </div>

               <div id="listing_page" style="display:none;" >
               <table class='wp-list-table widefat fixed posts' cellspacing='0' id='list_bookings_specific'>
                       <tr>
                               <b>Specific Date Time Slots</b>
                       </tr>
                       <tr>
                               <th> <?php _e('Day', 'woocommerce-booking');?> </th>
                               <th> <?php _e('Start Time', 'woocommerce-booking');?> </th>
                               <th> <?php _e('End Time', 'woocommerce-booking');?> </th>
                               <th> <?php _e('Note', 'woocommerce-booking');?> </th>
                               <th> <?php _e('Maximum Bookings', 'woocommerce-booking');?> </th>
                               <th> <?php _e('Global Check', 'woocommerce_booking');?> </th>
                               <?php print('<th> <a href="javascript:void(0);" id="'.$duplicate_of.'" class="remove_specific_data">Delete All </a> </th>');?>
                       </tr>

                       <?php 	
                       $var = "";		
                       //$prices = $booking_settings['booking_recurring_prices'];
                       //print_r($prices);
                       if ( isset($booking_settings['booking_time_settings']) && $booking_settings['booking_time_settings'] != '' ) :
                       foreach( $booking_settings['booking_time_settings'] as $key => $value ) {
                               if ( substr($key,0,7) != "booking" ) {
                                       $date_disp = $key;
                                       foreach( $value as $date_key => $date_value ) {
                                               print('<tr id="row_'.$date_key.'_'.$date_disp.'" >');
                                               print('<td> '.$date_disp.' </td>');
                                               print('<td> '.$date_value['from_slot_hrs'].':'.$date_value['from_slot_min'].' </td>');
                                               print('<td> '.$date_value['to_slot_hrs'].':'.$date_value['to_slot_min'].' </td>');
                                               //print('<td>  &nbsp; </td>');
                                               print('<td> '.$date_value['booking_notes'].' </td>');
                                               print('<td> '.$date_value['lockout_slot'].' </td>');
                                               print('<td> '.$date_value['global_time_check'].' </td>');
                                               print('<td> <a href="javascript:void(0);" id="'.$date_key.'&'.$duplicate_of.'&'.$date_disp.'&'.$date_value['from_slot_hrs'].':'.$date_value['from_slot_min'].'&'.$date_value['to_slot_hrs'].':'.$date_value['to_slot_min'].'" class="remove_time_data"> <img src="'.plugins_url().'/woocommerce-booking/images/delete.png" alt="Remove Time Slot" title="Remove Time Slot"></a> </td>');
                                               print('</tr>');
                                       }

                               } elseif ( substr($key,0,7) == "booking" ) {
                                       $date_pass = $key;
                                       $weekdays = bkap_get_book_arrays('weekdays');
                                       $date_disp = $weekdays[$key];
                                       //$price = $prices[$key."_price"];
                                       foreach( $value as $date_key => $date_value ) {
                                               $global_time_check = '';
                                               if(isset($date_value['global_time_check']))
                                                       $global_time_check = $date_value['global_time_check'];
                                               $var .= '<tr id="row_'.$date_key.'_'.$date_pass.'" >
                                               <td> '.$date_disp.' </td>
                                               <td> '.$date_value['from_slot_hrs'].':'.$date_value['from_slot_min'].' </td>
                                               <td> '.$date_value['to_slot_hrs'].':'.$date_value['to_slot_min'].' </td>
                                               <td> '.$date_value['booking_notes'].' </td>
                                               <td> '.$date_value['lockout_slot'].' </td>
                                               <td> '.$global_time_check.' </td>
                                               <td> <a href="javascript:void(0);" id="'.$date_key.'&'.$duplicate_of.'&'.$date_pass.'&'.$date_value['from_slot_hrs'].':'.$date_value['from_slot_min'].'&'.$date_value['to_slot_hrs'].':'.$date_value['to_slot_min'].'" class="remove_time_data"> <img src="'.plugins_url().'/woocommerce-booking/images/delete.png" alt="Remove Time Slot" title="Remove Time Slot"></a> </td>
                                               </tr>';
                                       }
                               }
                       }
                       endif;
                       if ( isset($booking_settings['booking_enable_multiple_day']) && $booking_settings['booking_enable_multiple_day'] != 'on' ) :
                               $query = "SELECT * FROM `".$wpdb->prefix."booking_history`
                               WHERE post_id='".$duplicate_of."' AND from_time='' AND to_time='' AND end_date='0000-00-00'";
                               $results = $wpdb->get_results ( $query );

                               foreach ( $results as $key => $value ) {
                                       if (substr($value->weekday, 0, 7) != "booking") {
                                               $date_key = date('j-n-Y',strtotime($value->start_date));
                                               print('<tr id="row_'.$date_key.'" >');
                                               print('<td> '.$date_key.' </td>');
                                               print('<td> &nbsp; </td>');
                                               print('<td> &nbsp; </td>');
                                               print('<td> &nbsp; </td>');
                                               print('<td> '.$value->total_booking.' </td>');
                                               print('<td> &nbsp; </td>');
                                               print('<td> <a href="javascript:void(0);" id="'.$date_key.'&'.$duplicate_of.'" class="remove_day_data"> <img src="'.plugins_url().'/woocommerce-booking/images/delete.png" alt="Remove Date" title="Remove Date"></a> </td>');
                                               print('</tr>');	
                                       } elseif (substr($value->weekday, 0, 7) == "booking" && $value->start_date == "0000-00-00") {
                                               $weekdays = bkap_get_book_arrays('weekdays');
                                               //$price = $prices[$value->weekday."_price"];
                                               $date_disp = $weekdays[$value->weekday];
                                               $var .= '<tr id="row_'.$value->weekday.'" >
                                                       <td> '.$date_disp.' </td>
                                                       <td>  &nbsp; </td>
                                                       <td>  &nbsp; </td>
                                                       <td>  &nbsp; </td>
                                                       <td> '.$value->total_booking.' </td>
                                                       <td>  &nbsp; </td>
                                                       <td> <a href="javascript:void(0);" id="'.$value->weekday.'&'.$duplicate_of.'" class="remove_day_data"> <img src="'.plugins_url().'/woocommerce-booking/images/delete.png" alt="Remove Day" title="Remove Day"></a> </td>
                                                       </tr>';
                                       }
                               }
                       endif;
                       ?>

               </table>

               <p>
               <table class='wp-list-table widefat fixed posts' cellspacing='0' id='list_bookings_recurring'>
                       <tr>
                               <b>Recurring Days Time Slots</b>
                       </tr>
                       <tr>
                               <th> <?php _e('Day', 'woocommerce-booking');?> </th>
                               <th> <?php _e('Start Time', 'woocommerce-booking');?> </th>
                               <th> <?php _e('End Time', 'woocommerce-booking');?> </th>
                               <th> <?php _e('Note', 'woocommerce-booking');?> </th> 
                               <th> <?php _e('Maximum Bookings', 'woocommerce-booking');?> </th>
                               <th> <?php _e('Global Check', 'woocommerce-booking');?>
                               <?php print('<th> <a href="javascript:void(0);" id="'.$duplicate_of.'" class="remove_recurring_data"> Delete All </a> </th>');	?>
                       </tr>
               <?php 
               if (isset($var)){
                       echo $var;
               }
               ?>
               </table>
               </p>
               </div>
               <?php 
                       do_action('bkap_after_listing_enabled', $duplicate_of);
               ?>
       <!--  	</form>  -->
               <?php
       }
       
       
        
    }// end of class
    
?>