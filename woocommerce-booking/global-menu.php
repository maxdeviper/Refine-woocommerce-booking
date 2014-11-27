<?php 

include_once('view-bookings.php');
include_once('license.php');

class global_menu{
    
    /**********************************************************
    * This function adds the Booking settings  menu in the 
    * sidebar admin woocommerce.
    ***********************************************************/
    public static function bkap_woocommerce_booking_admin_menu(){
            
            add_menu_page( 'Booking','Booking','manage_woocommerce', 'booking_settings',array('global_menu', 'bkap_woocommerce_booking_page' ));
            $page = add_submenu_page('booking_settings', __( 'Settings', 'woocommerce-booking' ), __( 'Settings', 'woocommerce-booking' ), 'manage_woocommerce', 'woocommerce_booking_page', array('global_menu', 'bkap_woocommerce_booking_page' ));
            $page = add_submenu_page('booking_settings', __( 'View Bookings', 'woocommerce-booking' ), __( 'View Bookings', 'woocommerce-booking' ), 'manage_woocommerce', 'woocommerce_history_page', array('view_bookings', 'bkap_woocommerce_history_page' ));
            $page = add_submenu_page('booking_settings', __( 'Activate License', 'woocommerce-booking' ), __( 'Activate License', 'woocommerce-booking' ), 'manage_woocommerce', 'booking_license_page', array('bkap_license', 'bkap_get_edd_sample_license_page' ));
            remove_submenu_page('booking_settings','booking_settings');
            do_action('bkap_add_submenu');
    }
    
    
	/********************************************************************
	 * This function displays the global settings for the booking products.
	 *******************************************************************/
	 public static function bkap_woocommerce_booking_page() {

        if (isset($_GET['action'])) {
                $action = $_GET['action'];
        } else {
                $action = '';
        }
        if ($action == 'settings' || $action == '') {
                $active_settings = "nav-tab-active";
        } else {
                $active_settings = '';
        }

        if ($action == 'labels') {
                $active_labels = "nav-tab-active";
        } else {
                $active_labels = '';
        }

        ?>

        <h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
        <a href="admin.php?page=woocommerce_booking_page&action=settings" class="nav-tab <?php echo $active_settings; ?>"> <?php _e( 'Global Booking Settings', 'woocommerce-ac' );?> </a>
        <a href="admin.php?page=woocommerce_booking_page&action=labels" class="nav-tab <?php echo $active_labels; ?>"> <?php _e( 'Booking Labels', 'woocommerce-ac' );?> </a>
<!-- 	<a href="admin.php?page=woocommerce_booking_page&action=reminders_settings" class="nav-tab <?php echo $active_reminders_settings; ?>"> <?php _e( 'Email Reminders', 'woocommerce-ac' );?> </a> -->
        </h2>

        <?php

                if( $action == 'labels'){

                $labels_product_page = array(
                        'book.date-label'=>'Check-in Date',
                        'checkout.date-label'=>'Check-out Date',     		
                        'book.time-label'=> 'Booking Time' );
                $labels_order_page = array(
                        'book.item-meta-date'=>'Check-in Date',
                        'checkout.item-meta-date'=>'Check-out Date',
                        'book.item-meta-time'=>'Booking Time');
                $labels_cart_page = array(
                        'book.item-cart-date'=>'Check-in Date',
                        'checkout.item-cart-date'=>'Check-out Date',
                        'book.item-cart-time'=>'Booking Time');

                if ( isset( $_POST['wapbk_booking_settings_frm'] ) && $_POST['wapbk_booking_settings_frm'] == 'labelsave' ) { 

                        foreach($labels_product_page as $key=>$label){
                                update_option($key, $_POST[str_replace(".","_",$key)]);
                        }
                        foreach($labels_order_page as $key=>$label){
                                update_option($key, $_POST[str_replace(".","_",$key)]);
                        }
                        foreach($labels_cart_page as $key=>$label){
                                update_option($key, $_POST[str_replace(".","_",$key)]);
                        }
                ?>
                <div id="message" class="updated fade"><p><strong><?php _e( 'Your settings have been saved.', 'woocommerce-booking' ); ?></strong></p></div>
                <?php } ?>

                <div id="content">
                          <form method="post" action="" id="booking_settings">
                                  <input type="hidden" name="wapbk_booking_settings_frm" value="labelsave">

                                  <div id="poststuff">
                                                <div class="postbox">
                                                        <h3 class="hndle"><?php _e( 'Labels', 'woocommerce-booking' ); ?></h3>
                                                        <div>
                                                          <table class="form-table">
                                                          <tr> 
                                                                <td colspan="2"><h2><strong><?php _e( 'Labels on product page', 'woocommerce-booking' ); ?></strong></h2></td>
                                                                <td><img style="margin-right:550px;" class="help_tip" width="16" height="16" data-tip="<?php _e('This sets the Labels on the Product Page.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" /></td>
                                                          </tr>
                                                                <?php foreach ($labels_product_page as $key=>$label): 
                                                                        $value = get_option($key);
                                                                ?>
                                                                <tr>
                                                                        <th>
                                                                                <label for="booking_language"><b><?php _e($label, 'woocommerce-booking'); ?></b></label>
                                                                        </th>
                                                                        <td>
                                                                                <input id="<?php echo $key?>" name="<?php echo $key?>" value="<?php echo $value;?>" >
                                                                        </td>
                                                                </tr>
                                                                <?php endforeach;?>
                                                                <tr> 
                                                                <td colspan="2"><h2><strong><?php _e( 'Labels on order received page and in email notification', 'woocommerce-booking' ); ?></strong></h2></td>
                                                                <td><img style="margin-right:550px;" class="help_tip" width="16" height="16" data-tip="<?php _e('This sets the Labels on the Order Recieved and Email Notification page.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" /></td>
                                                                </tr>
                                                                <?php foreach ($labels_order_page as $key=>$label): 
                                                                        $value = get_option($key);
                                                                ?>
                                                                <tr>
                                                                        <th>
                                                                                <label for="booking_language"><b><?php _e($label, 'woocommerce-booking'); ?></b></label>
                                                                        </th>
                                                                        <td>
                                                                                <input id="<?php echo $key?>" name="<?php echo $key?>" value="<?php echo $value;?>" >
                                                                        </td>
                                                                </tr>
                                                                <?php endforeach;?>
                                                                <tr> 
                                                                <td colspan="2"><h2><strong><?php _e( 'Labels on Cart & Check-out Page', 'woocommerce-booking' ); ?></strong></h2></td>
                                                                <td><img style="margin-right:550px;" class="help_tip" width="16" height="16" data-tip="<?php _e('This sets the Label on the Cart and the Checkout page.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" /></td>
                                                                </tr>
                                                                <?php foreach ($labels_cart_page as $key=>$label): 
                                                                        $value = get_option($key);
                                                                ?>
                                                                <tr>
                                                                        <th>
                                                                                <label for="booking_language"><b><?php _e($label, 'woocommerce-booking'); ?></b></label>
                                                                        </th>
                                                                        <td>
                                                                                <input id="<?php echo $key?>" name="<?php echo $key?>" value="<?php echo $value;?>" >
                                                                        </td>
                                                                </tr>
                                                                <?php endforeach;?>
                                                                <tr>
                                                                        <th>
                                                                        <input type="submit" name="Submit" class="button-primary" value="<?php _e( 'Save Changes', 'woocommerce-booking' ); ?>" />
                                                                        </th>
                                                                </tr>
                                                                </table>
                                                        </div>
                                                </div>
                                        </div>
                                </form>
                </div>
                <?php							
        }		

        if( $action == 'settings' || $action == '' ) {
                // Save the field values
                if ( isset( $_POST['wapbk_booking_settings_frm'] ) && $_POST['wapbk_booking_settings_frm'] == 'save' ) {
                        $calendar_theme = trim($_POST['wapbk_calendar_theme']);
                        $calendar_themes = bkap_get_book_arrays('calendar_themes');
                        $calendar_theme_name = $calendar_themes[$calendar_theme];

                        $booking_settings = new stdClass();
                        $booking_settings->booking_language = $_POST['booking_language'];
                        $booking_settings->booking_date_format = $_POST['booking_date_format'];
                        $booking_settings->booking_time_format = $_POST['booking_time_format'];
                        $booking_settings->booking_months = $_POST['booking_months'];	
                        $booking_settings->booking_calendar_day = $_POST['booking_calendar_day'];	
                        if (isset($_POST['booking_enable_rounding'])){
                                $booking_settings->enable_rounding = $_POST['booking_enable_rounding'];
                        }else{
                                $booking_settings->enable_rounding = '';
                        }
                        if(isset($_POST['booking_add_to_calendar'])){
                                $booking_settings->booking_export = $_POST['booking_add_to_calendar'];													
                        }else{						
                                $booking_settings->booking_export = '';
                        }
                        if(isset($_POST['booking_add_to_email'])){
                                $booking_settings->booking_attachment = $_POST['booking_add_to_email'];
                        }else{
                                $booking_settings->booking_attachment = '';
                        }
                        $booking_settings->booking_themes = $calendar_theme;
                        $booking_settings->booking_global_holidays = $_POST['booking_global_holidays'];
                        if(isset($_POST['booking_global_timeslot'])){
                                $booking_settings->booking_global_timeslot = $_POST['booking_global_timeslot'];
                        }else{
                                $booking_settings->booking_global_timeslot = '';
                        }
                        if(isset($_POST['booking_global_selection'])){
                                $booking_settings->booking_global_selection = $_POST['booking_global_selection'];
                        }else{
                                $booking_settings->booking_global_selection = '';
                        }
                        if (isset($_POST['minimum_day_booking'])) {
                        	$booking_settings->minimum_day_booking = $_POST['minimum_day_booking'];
                        }
                        else {
                        	$booking_settings->minimum_day_booking = '';
                        }
                        if (isset($_POST['global_booking_minimum_number_days'])) {
                        	$booking_settings->global_booking_minimum_number_days = $_POST['global_booking_minimum_number_days'];
                        }
                        else {
                        	$booking_settings->global_booking_minimum_number_days = '';
                        }
                        $booking_settings = apply_filters( 'bkap_save_global_settings', $booking_settings);
                        $woocommerce_booking_settings = json_encode($booking_settings);

                        update_option('woocommerce_booking_global_settings',$woocommerce_booking_settings);
                        //exit;
                }
                ?>

                <?php if ( isset( $_POST['wapbk_booking_settings_frm'] ) && $_POST['wapbk_booking_settings_frm'] == 'save' ) { ?>
                <div id="message" class="updated fade"><p><strong><?php _e( 'Your settings have been saved.', 'woocommerce-booking' ); ?></strong></p></div>
                <?php } ?>

                <?php 
                $saved_settings = json_decode(get_option('woocommerce_booking_global_settings'));
                ?>
                <div id="content">
                          <form method="post" action="" id="booking_settings">
                                  <input type="hidden" name="wapbk_booking_settings_frm" value="save">
                                  <input type="hidden" name="wapbk_calendar_theme" id="wapbk_calendar_theme" value="<?php if (isset($saved_settings)) echo $saved_settings->booking_themes;?>">
                                  <div id="poststuff">
                                                <div class="postbox">
                                                        <h3 class="hndle"><?php _e( 'Settings', 'woocommerce-booking' ); ?></h3>
                                                        <div>
                                                          <table class="form-table">

                                                                <tr>
                                                                        <th>
                                                                                <label for="booking_language"><b><?php _e('Language:', 'woocommerce-booking'); ?></b></label>
                                                                        </th>
                                                                        <td>
                                                                                <select id="booking_language" name="booking_language">
                                                                                <?php
                                                                                $language_selected = "";
                                                                                if (isset($saved_settings->booking_language)) {
                                                                                        $language_selected = $saved_settings->booking_language;
                                                                                }

                                                                                if ( $language_selected == "" ) $language_selected = "en-GB";
                                                                                $languages = bkap_get_book_arrays('languages');

                                                                                foreach ( $languages as $key => $value ) {
                                                                                        $sel = "";
                                                                                        if ($key == $language_selected) {
                                                                                                $sel = " selected ";
                                                                                        }
                                                                                        echo "<option value='$key' $sel>$value</option>";
                                                                                }
                                                                                ?>
                                                                                </select>
                                                                                <img class="help_tip" width="16" height="16" data-tip="<?php _e('Choose the language for your booking calendar.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                                                                        </td>
                                                                </tr>

                                                                <tr>
                                                                        <th>
                                                                                <label for="booking_date_format"><b><?php _e('Date Format:', 'woocommerce-booking');?></b></label>
                                                                        </th>
                                                                        <td>
                                                                                <select id="booking_date_format" name="booking_date_format">
                                                                                <?php
                                                                                if (isset($saved_settings)) { 
                                                                                        $date_format = $saved_settings->booking_date_format;
                                                                                } else {
                                                                                        $date_format = "";
                                                                                }
                                                                                $date_formats = bkap_get_book_arrays('date_formats');
                                                                                foreach ($date_formats as $k => $format) {
                                                                                        printf( "<option %s value='%s'>%s</option>\n",
                                                                                                        selected( $k, $date_format, false ),
                                                                                                        esc_attr( $k ),
                                                                                                        date($format)
                                                                                        );
                                                                                }
                                                                                ?>
                                                                                </select>
                                                                                <img class="help_tip" width="16" height="16" data-tip="<?php _e('The format in which the booking date appears to the customers on the product page once the date is selected', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                                                                        </td>
                                                                </tr>

                                                                <tr>
                                                                        <th>
                                                                                <label for="booking_time_format"><b><?php _e('Time Format:', 'woocommerce-booking');?></b></label>
                                                                        </th>
                                                                        <td>
                                                                                <select id="booking_time_format" name="booking_time_format">
                                                                                <?php
                                                                                $time_format = ""; 
                                                                                if (isset($saved_settings)) {
                                                                                        $time_format = $saved_settings->booking_time_format;
                                                                                }
                                                                                $time_formats = bkap_get_book_arrays('time_formats');
                                                                                foreach ($time_formats as $k => $format) {
                                                                                        printf( "<option %s value='%s'>%s</option>\n",
                                                                                                        selected( $k, $time_format, false ),
                                                                                                        esc_attr( $k ),
                                                                                                        $format
                                                                                        );
                                                                                }
                                                                                ?>
                                                                                </select>
                                                                                <img class="help_tip" width="16" height="16" data-tip="<?php _e('The format in which booking time appears to the customers on the product page once the time / time slot is selected', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                                                                        </td>
                                                                </tr>

                                                                <tr>
                                                                        <th>
                                                                                <label for="booking_months"><b><?php _e('Number of months to show in calendar:','woocommerce-booking');?></b></label>
                                                                        </th>
                                                                        <td>
                                                                                <?php 
                                                                                $no_months_1 = "";
                                                                                $no_months_2 = "";
                                                                                if (isset($saved_settings)) {
                                                                                        if ( $saved_settings->booking_months == 1) {
                                                                                                $no_months_1 = "selected";
                                                                                                $no_months_2 = "";
                                                                                        } elseif ( $saved_settings->booking_months == 2) {
                                                                                                $no_months_2 = "selected";
                                                                                                $no_months_1 = "";
                                                                                        }
                                                                                }
                                                                                ?>
                                                                                <select id="booking_months" name="booking_months">
                                                                                <option <?php echo $no_months_1;?> value="1"> 1 </option>
                                                                                <option <?php echo $no_months_2;?> value="2"> 2 </option>
                                                                                </select>
                                                                                <img class="help_tip" width="16" height="16" data-tip="<?php _e('The number of months to be shown on the calendar. If the booking dates spans across 2 months, then dates of 2 months can be shown simultaneously without the need to press Next or Back buttons.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                                                                        </td>
                                                                </tr>
                                                                <tr>
                                                                        <th>
                                                                                <label for="booking_calendar_day"><b><?php _e('First Day on Calendar:', 'woocommerce-booking'); ?></b></label>
                                                                        </th>
                                                                        <td>
                                                                                <select id="booking_calendar_day" name="booking_calendar_day">
                                                                                <?php
                                                                                $day_selected = "";
                                                                                if (isset($saved_settings->booking_calendar_day)) {
                                                                                        $day_selected = $saved_settings->booking_calendar_day;
                                                                                }

                                                                                if ( $day_selected == "" ) $day_selected = get_option('start_of_week');
                                                                                $days = bkap_get_book_arrays('days');
                                                                                foreach ( $days as $key => $value ) {
                                                                                        $sel = "";
                                                                                        if ($key == $day_selected) {
                                                                                                $sel = " selected ";
                                                                                        }
                                                                                        echo "<option value='$key' $sel>$value</option>";
                                                                                }
                                                                                ?>
                                                                                </select>
                                                                                <img class="help_tip" width="16" height="16" data-tip="<?php _e('Choose the language for your booking calendar.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                                                                        </td>
                                                                </tr>
                                                                <tr>
                                                                        <th>
                                                                                <label for="booking_add_to_calendar"><b><?php _e('Show "Add to Calendar" button on Order Received page:', 'woocommerce-booking');?></b></label>
                                                                        </th>
                                                                        <td>
                                                                                <?php
                                                                                $export_ics = ""; 
                                                                                if (isset($saved_settings->booking_export) && $saved_settings->booking_export == 'on') {
                                                                                        $export_ics = 'checked';
                                                                                }

                                                                                ?>
                                                                                <input type="checkbox" id="booking_add_to_calendar" name="booking_add_to_calendar" <?php echo $export_ics; ?>/>
                                                                                <img class="help_tip" width="16" height="16" data-tip="<?php _e('Shows the \'Add to Calendar\' button on the Order Received page. On clicking the button, an ICS file will be downloaded.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                                                                        </td>
                                                                </tr>

                                                                <tr>
                                                                        <th>
                                                                                <label for="booking_add_to_email"><b><?php _e('Send bookings as attachments (ICS files) in email notifications:', 'woocommerce-booking');?></b></label>
                                                                        </th>
                                                                        <td>
                                                                                <?php
                                                                                $email_ics = ""; 
                                                                                if (isset($saved_settings->booking_attachment) && $saved_settings->booking_attachment == 'on') {
                                                                                        $email_ics = 'checked';
                                                                                }

                                                                                ?>
                                                                                <input type="checkbox" id="booking_add_to_email" name="booking_add_to_email" <?php echo $email_ics; ?>/>
                                                                                <img class="help_tip" width="16" height="16" data-tip="<?php _e('Allow customers to export bookings as ICS file after placing an order. Sends ICS files as attachments in email notifications.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                                                                        </td>
                                                                </tr>
                                                                <tr>
                                                                        <th>
                                                                                <label for="booking_theme"><b><?php _e('Preview Theme & Language:','woocommerce-booking');?></b></label>
                                                                        </th>
                                                                        <td>
                                                                                <?php 
                                                                                $global_holidays = "";
                                                                                if (isset($saved_settings)) {
                                                                                        if ( $saved_settings->booking_global_holidays != "" ) {
                                                                                                $global_holidays = "addDates: ['".str_replace(",", "','", $saved_settings->booking_global_holidays)."']";
                                                                                        }
                                                                                }
                                                                                ?>

                                                                                <img style="margin-left:250px;" class="help_tip" width="16" height="16" data-tip="<?php _e('Select the theme for the calendar. You can choose a theme which blends with the design of your website.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png"/>

                                                                                <div>

                                                                                <script type="text/javascript">

                                                                                  jQuery(document).ready(function() {

                                                                                                jQuery("#booking_new_switcher").themeswitcher({
                                                                                                onclose: function() {
                                                                                                        var cookie_name = this.cookiename;
                                                                                                        jQuery("input#wapbk_calendar_theme").val(jQuery.cookie(cookie_name));
                                                                                                },
                                                                                                imgpath: "<?php echo plugins_url().'/woocommerce-booking/images/';?>",
                                                                                                loadTheme: "smoothness"
                                                                                            });

                                                                                                var date = new Date();
                                                                                                jQuery.datepicker.setDefaults( jQuery.datepicker.regional[ "en-GB" ] );
                                                                                                jQuery('#booking_switcher').multiDatesPicker({
                                                                                                        dateFormat: "d-m-yy",
                                                                                                        altField: "#booking_global_holidays",
                                                                                                        <?php echo $global_holidays;?>
                                                                                                });

                                                                                                jQuery(function() {


                                                                                                jQuery.datepicker.setDefaults( jQuery.datepicker.regional[ "" ] );
                                                                                                jQuery( "#booking_switcher" ).datepicker( jQuery.datepicker.regional[ "en-GB" ] );
                                                                                                jQuery( "#booking_new_switcher" ).datepicker( jQuery.datepicker.regional[ "<?php echo $language_selected;?>" ] );
                                                                                                jQuery( "#booking_language" ).change(function() {
                                                                                                jQuery( "#booking_new_switcher" ).datepicker( "option",
                                                                                                jQuery.datepicker.regional[ jQuery(this).val() ] );

                                                                                                });
                                                                                                jQuery(".ui-datepicker-inline").css("font-size","1.4em");
                                                                                       
                                                                                                });
                                                                                           });
                                                                                </script>

                                                                                <div id="booking_new_switcher" name="booking_new_switcher"></div>
                                                                        </td>
                                                                </tr>

                                                                <tr>
                                                                        <th>
                                                                                <label for="booking_global_holidays"><b><?php _e('Select Holidays / Exclude Days / Black-out days:', 'woocommerce-booking');?></b></label>
                                                                        </th>
                                                                        <td>
                                                                                <textarea rows="4" cols="80" name="booking_global_holidays" id="booking_global_holidays"></textarea>
                                                                                <img class="help_tip" width="16" height="16" data-tip="<?php _e('Select dates for which the booking will be completely disabled for all the products in your WooCommerce store.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" style="vertical-align:top;"/><br>
                                                                                Please click on the date in calendar to add or delete the date from the holiday list.
                                                                                <div id="booking_switcher" name="booking_switcher"></div>
                                                                        </td>
                                                                </tr>
                                                                <tr>
                                                                        <th>
                                                                                <label for="booking_global_timeslot"><b><?php _e('Global Time Slot Booking:', 'woocommerce-booking');?></b></label>
                                                                        </th>
                                                                <td>
                                                                        <?php
                                                                        $global_timeslot = ""; 
                                                                        if (isset($saved_settings->booking_global_timeslot) && $saved_settings->booking_global_timeslot == 'on') {
                                                                                $global_timeslot = "checked";
                                                                        }
                                                                        ?>
                                                                        <input type="checkbox" id="booking_global_timeslot" name="booking_global_timeslot" <?php echo $global_timeslot; ?>/>
                                                                        <img class="help_tip" width="16" height="16" data-tip="<?php _e('Please select this checkbox if you want ALL time slots to be unavailable for booking in all products once the lockout for that time slot is reached for any product.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" style="vertical-align:top;"/><br>
                                                                </td>
                                                                </tr>

                                                                <tr>
                                                                        <th>
                                                                                <label for="booking_enable_rounding"><b><?php _e('Enable Rounding of Prices:', 'woocommerce-booking');?></b></label>
                                                                        </th>
                                                                        <td>
                                                                                <?php
                                                                                $rounding = ""; 
                                                                                if (isset($saved_settings->enable_rounding) && $saved_settings->enable_rounding == 'on') {
                                                                                        $rounding = 'checked';
                                                                                }

                                                                                ?>
                                                                                <input type="checkbox" id="booking_enable_rounding" name="booking_enable_rounding" <?php echo $rounding; ?>/>
                                                                                <img class="help_tip" w`idth="16" height="16" data-tip="<?php _e('Rounds the Price to the nearest Integer value.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                                                                        </td>
                                                                </tr>
                                                                <tr>
                                                                        <th>
                                                                                <label for="booking_global_selection"><b><?php _e('Duplicate dates from first product in the cart to other products:', 'woocommerce-booking');?></b></label>
                                                                        </th>
                                                                <td>
                                                                        <?php
                                                                        $global_selection = ""; 
                                                                        if (isset($saved_settings->booking_global_selection) && $saved_settings->booking_global_selection == 'on'){
                                                                                $global_selection = "checked";
                                                                        }
                                                                        ?>
                                                                        <input type="checkbox" id="booking_global_selection" name="booking_global_selection" <?php echo $global_selection; ?>/>
                                                                        <img class="help_tip" width="16" height="16" data-tip="<?php _e('Please select this checkbox if you want to select the date globally for All products once selected for a product and added to cart.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" style="vertical-align:top;"/><br>
                                                                </td>
                                                                </tr>
                                                                <tr>
                                                                                            <th>
                                                                                                <label for="minimum_day_booking"><b><?php _e('Minimum Day Booking:', 'woocommerce-booking');?></b></label>
                                                                                            </th>
                                                                                            <td>
                                                                                                <?php
                                                                                                        $minimum_booking_checked = "";
                                                                                                        $minimum_days_div_show = 'none';
                                                                                                        if (isset($saved_settings->minimum_day_booking) && $saved_settings->minimum_day_booking == 'on') {
                                                                                                                $minimum_booking_checked = 'checked';
                                                                                                                $minimum_days_div_show = 'block';
                                                                                                        }
                                                                                                ?>
                                                                                            <input type="checkbox" id="minimum_day_booking" name="minimum_day_booking" <?php echo $minimum_booking_checked; ?>  onClick="minimum_days_method(this)" <?php echo $minimum_booking_checked; ?>/>
                                                                                            <img class="help_tip" width="16" height="16" data-tip="<?php _e('Enter minimum days of booking for Multiple days booking.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                                                                                         </td>
                                                                                        </tr>
                                                                                  </table>
                                                                                        
                                                                                    <script type="text/javascript">
                                                                                                function minimum_days_method(chk)
                                                                                                {
                                                                                                        if ( jQuery( "input[name='minimum_day_booking']").attr("checked"))
                                                                                                        {
                                                                                                                document.getElementById("minimum_booking_days").style.display = "block";

                                                                                                        }
                                                                                                        if ( !jQuery( "input[name='minimum_day_booking']").attr("checked") )
                                                                                                        {
                                                                                                                document.getElementById("minimum_booking_days").style.display = "none";

                                                                                                        }
                                                                                                }
                                                                                        </script>

                                                                                        
                                                                                        <div id="minimum_booking_days" name="minimum_booking_days" style="display:<?php echo $minimum_days_div_show; ?>;">
                                                                                        <table class="form-table">

                                                                                        <tr>
                                                                                            <th>
                                                                                                    <label for="global_booking_minimum_number_days"><b><?php _e( 'Minimum number of days to choose:', 'woocommerce-booking');?></b></label>
                                                                                            </th>
                                                                                            <td>
                                                                                                <?php 
                                                                                                        $minimum_day = "";
                                                                                                    if ( isset($saved_settings->global_booking_minimum_number_days) && $saved_settings->global_booking_minimum_number_days != "" ) {
                                                                                                            $minimum_day = $saved_settings->global_booking_minimum_number_days;
                                                                                                    }
                                                                                                    else {
                                                                                                            $minimum_day = "0";
                                                                                                    }		
                                            //                                                  ?>
                                                                                                    <input type="text" name="global_booking_minimum_number_days" id="global_booking_minimum_number_days" value="<?php echo $minimum_day;?>" >
                                                                                                <img class="help_tip" width="16" height="16" data-tip="<?php _e('The minimum number days you want to be booked for multiple day booking. For example, if you require minimum 2 days for booking, enter the value 2 in this field.', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                                                                                            </td>
                                                                                        </tr>
                                                                                        
                                                                                            </table>
                                                                                        </div>
                                                                                        <table class="form-table">
                                                                                
                                                                <?php do_action('bkap_after_global_holiday_field');?>
                                                                <tr>
                                                                        <th>
                                                                        <input type="submit" name="Submit" class="button-primary" value="<?php _e( 'Save Changes', 'woocommerce-booking' ); ?>" />
                                                                        </th>
                                                                </tr>

                                                          </table>
                                                        </div>
                                                </div>
                                        </div>
                                </form>
                </div>

                <?php 
        }
   }
}// End of the class

?>