<?php 

include_once('view-bookings.php');
include_once('license.php');
include_once( 'includes/bkap-calendar-sync-settings.php' );
include_once( 'import-bookings.php' );


class global_menu{
    
   /**
    * This function adds the Booking settings  menu in the 
    * sidebar admin woocommerce.
    */
    public static function bkap_woocommerce_booking_admin_menu(){
    	add_menu_page( 'Booking', 'Booking', 'manage_woocommerce', 'booking_settings', array( 'global_menu', 'bkap_woocommerce_booking_page' ) );
    	$page = add_submenu_page( 'booking_settings', __( 'View Bookings',     'woocommerce-booking' ), __( 'View Bookings',       'woocommerce-booking' ), 'manage_woocommerce', 'woocommerce_history_page',  array( 'view_bookings', 'bkap_woocommerce_history_page' ) );
    	$page = add_submenu_page( 'booking_settings', __( 'Import Bookings',   'woocommerce-booking' ), __( 'Import Bookings',     'woocommerce-booking' ), 'manage_woocommerce', 'woocommerce_import_page',   array( 'import_bookings', 'bkap_woocommerce_import_page' ) );
    	$page = add_submenu_page( 'booking_settings', __( 'Settings',          'woocommerce-booking' ), __( 'Settings',            'woocommerce-booking' ), 'manage_woocommerce', 'woocommerce_booking_page',  array( 'global_menu',   'bkap_woocommerce_booking_page' ) );
    	$page = add_submenu_page( 'booking_settings', __( 'Activate License',  'woocommerce-booking' ), __( 'Activate License',    'woocommerce-booking' ), 'manage_woocommerce', 'booking_license_page',      array( 'bkap_license',  'bkap_get_edd_sample_license_page' ) );
    	remove_submenu_page( 'booking_settings', 'booking_settings' );
    	do_action( 'bkap_add_submenu' );
    }
    
	/**
	 * This function displays the global settings for the booking products.
	 */
	 public static function bkap_woocommerce_booking_page() {

        if ( isset( $_GET['action'] ) ) {
                $action = $_GET['action'];
        } else {
                $action = '';
        }
        
        $active_settings = '';
        $active_labels = '';
        $addon_settings = '';
        $cal_sync_settings = '';
        
        switch ( $action ) {
            case 'settings':
                $active_settings = "nav-tab-active";
                break;
            case 'labels':
                $active_labels = "nav-tab-active";
                break;
            case 'addon_settings':
                $addon_settings = "nav-tab-active";
                break;
            case 'calendar_sync_settings':
                $cal_sync_settings = "nav-tab-active";
                break;
            default:
                $active_settings = "nav-tab-active";
                break;
        }
        ?>

        <h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
        <a href="admin.php?page=woocommerce_booking_page&action=settings" class="nav-tab <?php echo $active_settings; ?>"> <?php _e( 'Global Booking Settings', 'woocommerce-booking' );?> </a>
        <a href="admin.php?page=woocommerce_booking_page&action=labels" class="nav-tab <?php echo $active_labels; ?>"> <?php _e( 'Booking Labels', 'woocommerce-booking' );?> </a>
<!-- 	<a href="admin.php?page=woocommerce_booking_page&action=reminders_settings" class="nav-tab <?php echo $active_reminders_settings; ?>"> <?php _e( 'Email Reminders', 'woocommerce-booking' );?> </a> -->
        <a href="admin.php?page=woocommerce_booking_page&action=addon_settings" class="nav-tab <?php echo $addon_settings; ?>"> <?php _e( 'Addon Settings', 'woocommerce-booking' );?> </a>
        <a href="admin.php?page=woocommerce_booking_page&action=calendar_sync_settings" class="nav-tab <?php echo $cal_sync_settings; ?>"> <?php _e( 'Google Calendar Sync', 'woocommerce-booking' );?> </a>
        
        <?php
        do_action( 'bkap_add_global_settings_tab' );
        ?>
        </h2>

        <?php 
        if ( $action == 'addon_settings' ) {
        	// check if any addons are active
        	if ( function_exists( 'is_bkap_send_friend_active' ) && is_bkap_send_friend_active() ) {
        		?>
           		<p><?php _e( 'Change settings for the addons to the Booking & Appointment Plugin for WooCommerce.', 'woocommerce-booking' ); ?></p>
           		<?php
           		do_action( 'bkap_add_addon_settings' );
        	} else {
        		?>
        		<p> <?php _e( 'No addons are currently active for the Booking & Appointment Plugin for WooCommerce.', 'woocommerce-booking' ); ?></p>
        		<?php 
        	}
        }

        if ( 'calendar_sync_settings' ==  $action ) {
            print( '<div id="content">
                    <form method="post" action="options.php">' );
            settings_fields( "bkap_gcal_sync_settings" );
            do_settings_sections( "bkap_gcal_sync_settings_page" );
            submit_button ( __( 'Save Settings', 'woocommerce-booking' ), 'primary', 'save', true );
            print('</form>
		              </div>');
        }
        
        if( $action == 'labels'){

                $labels_product_page = array(
                        'book.date-label'         => __( 'Check-in Date', 'woocommerce-booking' ),
                        'checkout.date-label'     => __( 'Check-out Date', 'woocommerce-booking' ),     		
                        'book.time-label'         => __( 'Booking Time', 'woocommerce-booking' ),
                		'book.time-select-option' => __( 'Choose Time Text', 'woocommerce-booking' )
                );
                
                $labels_order_page = array(
                        'book.item-meta-date'    => __( 'Check-in Date', 'woocommerce-booking' ),
                        'checkout.item-meta-date'=> __( 'Check-out Date', 'woocommerce-booking' ),
                        'book.item-meta-time'    => __( 'Booking Time', 'woocommerce-booking' ),
                		'book.ics-file-name'     => __( 'ICS File Name', 'woocommerce-booking' )
                );
                
                $labels_cart_page = array(
                        'book.item-cart-date'    => __( 'Check-in Date', 'woocommerce-booking' ),
                        'checkout.item-cart-date'=> __( 'Check-out Date', 'woocommerce-booking' ),
                        'book.item-cart-time'    => __( 'Booking Time', 'woocommerce-booking' )
                );

                if ( isset( $_POST['wapbk_booking_settings_frm'] ) && $_POST['wapbk_booking_settings_frm'] == 'labelsave' ) { 

                        foreach( $labels_product_page as $key => $label ){
                                update_option( $key, $_POST[str_replace( ".", "_", $key )] );
                        }
                        foreach( $labels_order_page as $key => $label ){
                                update_option( $key, $_POST[str_replace( ".", "_", $key )] );
                        }
                        foreach( $labels_cart_page as $key => $label ){
                                update_option( $key, $_POST[str_replace( ".", "_", $key )] );
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
                                                                <td colspan="2" style="padding: 0px margin-left:10px;"><h2><strong><?php _e( 'Labels on product page', 'woocommerce-booking' ); ?></strong></h2></td>
                                                                <td><img style="margin-right:550px;" class="help_tip" width="16" height="16" data-tip="<?php _e( 'This sets the Labels on the Product Page.', 'woocommerce-booking' );?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" /></td>
                                                          </tr>
                                                                <?php foreach ( $labels_product_page as $key => $label ): 
                                                                        $value = get_option( $key );
                                                                ?>
                                                                <tr>
                                                                        <td>
                                                                                <label for="booking_language"><b><?php _e( $label, 'woocommerce-booking' ); ?></b></label>
                                                                        </td>
                                                                        <td>
                                                                                <input id="<?php echo $key?>" name="<?php echo $key?>" value="<?php echo $value;?>" >
                                                                        </td>
                                                                </tr>
                                                                <?php endforeach;?>
                                                                <tr> 
                                                                <td colspan="2" style="padding: 0px margin-left:10px;"><h2><strong><?php _e( 'Labels on order received page and in email notification', 'woocommerce-booking' ); ?></strong></h2></td>
                                                                <td><img style="margin-right:550px;" class="help_tip" width="16" height="16" data-tip="<?php _e( 'This sets the Labels on the Order Recieved and Email Notification page.', 'woocommerce-booking' );?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" /></td>
                                                                </tr>
                                                                <?php foreach ( $labels_order_page as $key => $label ): 
                                                                        $value = get_option( $key );
                                                                ?>
                                                                <tr>
                                                                        <td>
                                                                                <label for="booking_language"><b><?php _e( $label, 'woocommerce-booking' ); ?></b></label>
                                                                        </td>
                                                                        <td>
                                                                                <input id="<?php echo $key?>" name="<?php echo $key?>" value="<?php echo $value;?>" >
                                                                        </td>
                                                                </tr>
                                                                <?php endforeach;?>
                                                                <tr> 
                                                                <td colspan="2" style="padding: 0px margin-left:10px;"><h2><strong><?php _e( 'Labels on Cart & Check-out Page', 'woocommerce-booking' ); ?></strong></h2></td>
                                                                <td><img style="margin-right:550px;" class="help_tip" width="16" height="16" data-tip="<?php _e( 'This sets the Label on the Cart and the Checkout page.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" /></td>
                                                                </tr>
                                                                <?php foreach ( $labels_cart_page as $key => $label ): 
                                                                        $value = get_option( $key );
                                                                ?>
                                                                <tr>
                                                                        <td>
                                                                                <label for="booking_language"><b><?php _e( $label, 'woocommerce-booking' ); ?></b></label>
                                                                        </td>
                                                                        <td>
                                                                                <input id="<?php echo $key?>" name="<?php echo $key?>" value="<?php echo $value;?>" >
                                                                        </td>
                                                                </tr>
                                                                <?php endforeach;?>
                                                                <tr>
                                                                        <td>
                                                                        <input type="submit" name="Submit" class="button-primary" value="<?php _e( 'Save Changes', 'woocommerce-booking' ); ?>" />
                                                                        </td>
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
                        $calendar_theme                             = trim( $_POST['wapbk_calendar_theme'] );
                        $calendar_themes                            = bkap_get_book_arrays( 'calendar_themes' );
                        $calendar_theme_name                        = $calendar_themes[ $calendar_theme ];

                        $booking_settings                           = new stdClass();
                        $booking_settings->booking_language         = $_POST['booking_language'];
                        $booking_settings->booking_date_format      = $_POST['booking_date_format'];
                        $booking_settings->booking_time_format      = $_POST['booking_time_format'];
                        $booking_settings->booking_months           = $_POST['booking_months'];	
                        $booking_settings->booking_calendar_day     = $_POST['booking_calendar_day'];	
                        // default to blanks
                        $booking_settings->enable_rounding                      = '';
                        $booking_settings->booking_export                       = '';
                        $booking_settings->booking_export                       = '';
                        $booking_settings->booking_global_timeslot              = '';
                        $booking_settings->booking_global_selection             = '';
                        $booking_settings->minimum_day_booking                  = '';
                        $booking_settings->global_booking_minimum_number_days   = '';
                        $booking_settings->booking_availability_display         = '';
                        $booking_settings->woo_product_addon_price              = '';
                        $booking_settings->woo_gf_product_addon_option_price    = '';
                        
                        if ( isset( $_POST['booking_enable_rounding'] ) ){
                                $booking_settings->enable_rounding = $_POST['booking_enable_rounding'];
                        }
                        
                        if( isset( $_POST['booking_add_to_calendar'] ) ){
                                $booking_settings->booking_export = $_POST['booking_add_to_calendar'];													
                        }
                        
                        if( isset( $_POST['booking_add_to_email'] ) ){
                                $booking_settings->booking_attachment = $_POST['booking_add_to_email'];
                        }
                        
                        $booking_settings->booking_themes           = $calendar_theme;
                        $booking_settings->booking_global_holidays  = $_POST['booking_global_holidays'];
                        
                        if ( isset( $_POST['booking_global_timeslot'] ) ){
                                $booking_settings->booking_global_timeslot = $_POST['booking_global_timeslot'];
                        }
                        
                        if ( isset( $_POST['booking_global_selection'] ) ){
                                $booking_settings->booking_global_selection = $_POST['booking_global_selection'];
                        }
                        
                        if ( isset( $_POST['minimum_day_booking'] ) ) {
                        	$booking_settings->minimum_day_booking = $_POST['minimum_day_booking'];
                        }
                        
                        if ( isset( $_POST['global_booking_minimum_number_days'] ) ) {
                        	$booking_settings->global_booking_minimum_number_days = $_POST['global_booking_minimum_number_days'];
                        }
                        
                      	if ( isset( $_POST['booking_availability_display'] ) ) {
                        	$booking_settings->booking_availability_display = $_POST['booking_availability_display'];
                        }
                        
                        if ( isset( $_POST['woo_product_addon_price'] ) ) {
                        	$booking_settings->woo_product_addon_price = $_POST['woo_product_addon_price'];
                        }
                        
                        if ( isset( $_POST['woo_gf_product_addon_option_price'] ) ) {
                            $booking_settings->woo_gf_product_addon_option_price = $_POST[ 'woo_gf_product_addon_option_price' ];
                        }
                        
                       	$booking_settings               = apply_filters( 'bkap_save_global_settings', $booking_settings );
                        $woocommerce_booking_settings   = json_encode( $booking_settings );

                        update_option( 'woocommerce_booking_global_settings', $woocommerce_booking_settings );
                }
                ?>

                <?php if ( isset( $_POST['wapbk_booking_settings_frm'] ) && $_POST['wapbk_booking_settings_frm'] == 'save' ) { ?>
                <div id="message" class="updated fade"><p><strong><?php _e( 'Your settings have been saved.', 'woocommerce-booking' ); ?></strong></p></div>
                <?php } ?>

                <?php 
                $saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
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
                                                                    <td>
                                                                        <label for="booking_language"><b><?php _e( 'Language:', 'woocommerce-booking' ); ?></b></label>
                                                                    </td>
                                                                    <td>
                                                                        <select id="booking_language" name="booking_language">
                                                                                <?php
                                                                                $language_selected = "";
                                                                                
                                                                                if ( isset( $saved_settings->booking_language ) ) {
                                                                                        $language_selected = $saved_settings->booking_language;
                                                                                }

                                                                                if ( $language_selected == "" ) $language_selected = "en-GB";
                                                                                $languages = bkap_get_book_arrays('languages');

                                                                                foreach ( $languages as $key => $value ) {
                                                                                        $sel = "";
                                                                                        
                                                                                        if ( $key == $language_selected ) {
                                                                                                $sel = " selected ";
                                                                                        }
                                                                                        echo "<option value='$key' $sel>$value</option>";
                                                                                }
                                                                                ?>
                                                                         </select>
                                                                         <img class="help_tip" width="16" height="16" data-tip="<?php _e( 'Choose the language for your booking calendar.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                                                                     </td>
                                                                  </tr>

                                                                  <tr>
                                                                     <td>
                                                                         <label for="booking_date_format"><b><?php _e( 'Date Format:', 'woocommerce-booking' ); ?></b></label>
                                                                     </td>
                                                                     <td>
                                                                         <select id="booking_date_format" name="booking_date_format">
                                                                                <?php
                                                                                if ( isset( $saved_settings ) ) { 
                                                                                        $date_format = $saved_settings->booking_date_format;
                                                                                } else {
                                                                                        $date_format = "";
                                                                                }
                                                                                $date_formats = bkap_get_book_arrays('date_formats');
                                                                                
                                                                                foreach ( $date_formats as $k => $format ) {
                                                                                        printf( "<option %s value='%s'>%s</option>\n",
                                                                                                        selected( $k, $date_format, false ),
                                                                                                        esc_attr( $k ),
                                                                                                        date( $format )
                                                                                        );
                                                                                }
                                                                                ?>
                                                                           </select>
                                                                           <img class="help_tip" width="16" height="16" data-tip="<?php _e('The format in which the booking date appears to the customers on the product page once the date is selected', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                                                                     </td>
                                                                 </tr>

                                                                 <tr>
                                                                     <td>
                                                                         <label for="booking_time_format"><b><?php _e( 'Time Format:', 'woocommerce-booking' ); ?></b></label>
                                                                     </td>
                                                                     <td>
                                                                         <select id="booking_time_format" name="booking_time_format">
                                                                                <?php
                                                                                $time_format = ""; 
                                                                                
                                                                                if ( isset( $saved_settings ) ) {
                                                                                        $time_format = $saved_settings->booking_time_format;
                                                                                }
                                                                                $time_formats = bkap_get_book_arrays('time_formats');
                                                                                
                                                                                foreach ( $time_formats as $k => $format ) {
                                                                                        printf( "<option %s value='%s'>%s</option>\n",
                                                                                                        selected( $k, $time_format, false ),
                                                                                                        esc_attr( $k ),
                                                                                                        __( $format, "woocommerce-booking" )
                                                                                        );
                                                                                }
                                                                                ?>
                                                                          </select>
                                                                          <img class="help_tip" width="16" height="16" data-tip="<?php _e( 'The format in which booking time appears to the customers on the product page once the time / time slot is selected', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                                                                      </td>
                                                                 </tr>

                                                                 <tr>
                                                                     <td>
                                                                         <label for="booking_months"><b><?php _e( 'Number of months to show in calendar:', 'woocommerce-booking' ); ?></b></label>
                                                                    </td>
                                                                    <td>
                                                                                <?php 
                                                                                $no_months_1 = "";
                                                                                $no_months_2 = "";
                                                                                if ( isset( $saved_settings ) ) {
                                                                                        
                                                                                        if ( $saved_settings->booking_months == 1 ) {
                                                                                                $no_months_1 = "selected";
                                                                                                $no_months_2 = "";
                                                                                        } elseif ( $saved_settings->booking_months == 2 ) {
                                                                                                $no_months_2 = "selected";
                                                                                                $no_months_1 = "";
                                                                                        }
                                                                                }
                                                                                ?>
                                                                           <select id="booking_months" name="booking_months">
                                                                                <option <?php echo $no_months_1;?> value="1"> 1 </option>
                                                                                <option <?php echo $no_months_2;?> value="2"> 2 </option>
                                                                           </select>
                                                                           <img class="help_tip" width="16" height="16" data-tip="<?php _e( 'The number of months to be shown on the calendar. If the booking dates spans across 2 months, then dates of 2 months can be shown simultaneously without the need to press Next or Back buttons.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                                                                     </td>
                                                                 </tr>
                                                                  <tr>
                                                                     <td>
                                                                         <label for="booking_calendar_day"><b><?php _e( 'First Day on Calendar:', 'woocommerce-booking' ); ?></b></label>
                                                                    </td>
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
                                                                                        
                                                                                        if ( $key == $day_selected ) {
                                                                                                $sel = " selected ";
                                                                                        }
                                                                                         echo "<option value='$key' $sel>".__($value, 'woocommerce-booking')."</option>";
                                                                                }
                                                                                ?>
                                                                          </select>
                                                                          <img class="help_tip" width="16" height="16" data-tip="<?php _e( 'Choose the language for your booking calendar.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                                                                     </td>
                                                                 </tr>
                                                                  <tr>
                                                                     <td>
                                                                        <label for="booking_add_to_calendar"><b><?php _e( 'Show "Add to Calendar" button on Order Received page:', 'woocommerce-booking' ); ?></b></label>
                                                                     </td>
                                                                      <td>
                                                                                <?php
                                                                                $export_ics = ""; 
                                                                                if ( isset( $saved_settings->booking_export ) && $saved_settings->booking_export == 'on' ) {
                                                                                        $export_ics = 'checked';
                                                                                }

                                                                                ?>
                                                                           <input type="checkbox" id="booking_add_to_calendar" name="booking_add_to_calendar" <?php echo $export_ics; ?>/>
                                                                           <img class="help_tip" width="16" height="16" data-tip="<?php _e( 'Shows the \'Add to Calendar\' button on the Order Received page. On clicking the button, an ICS file will be downloaded.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                                                                      </td>
                                                                  </tr>

                                                                   <tr>
                                                                       <td>
                                                                          <label for="booking_add_to_email"><b><?php _e( 'Send bookings as attachments (ICS files) in email notifications:', 'woocommerce-booking' ); ?></b></label>
                                                                       </td>
                                                                        <td>
                                                                                <?php
                                                                                $email_ics = ""; 
                                                                                if ( isset( $saved_settings->booking_attachment ) && $saved_settings->booking_attachment == 'on' ) {
                                                                                        $email_ics = 'checked';
                                                                                }

                                                                                ?>
                                                                             <input type="checkbox" id="booking_add_to_email" name="booking_add_to_email" <?php echo $email_ics; ?>/>
                                                                             <img class="help_tip" width="16" height="16" data-tip="<?php _e( 'Allow customers to export bookings as ICS file after placing an order. Sends ICS files as attachments in email notifications.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                                                                        </td>
                                                                    </tr>
                                                                     <tr>
                                                                         <td style="vertical-align: top;">
                                                                               <label for="booking_theme"><b><?php _e( 'Preview Theme & Language:', 'woocommerce-booking' ); ?></b></label>
                                                                         </td>
                                                                         <td>
                                                                                <?php 
                                                                                $global_holidays = "";
                                                                                
                                                                                if ( isset( $saved_settings ) ) {
                                                                                        
                                                                                    if ( $saved_settings->booking_global_holidays != "" ) {
                                                                                                $global_holidays = "addDates: ['".str_replace(",", "','", $saved_settings->booking_global_holidays)."']";
                                                                                    }
                                                                                }
                                                                                ?>

                                                                                <img style="margin-left:250px;" class="help_tip" width="16" height="16" data-tip="<?php _e( 'Select the theme for the calendar. You can choose a theme which blends with the design of your website.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png"/>

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
                                                                        <td style="vertical-align: top;">
                                                                                <label for="booking_global_holidays"><b><?php _e('Select Holidays / Exclude Days / Black-out days:', 'woocommerce-booking');?></b></label>
                                                                        </td>
                                                                        <td>
                                                                                <textarea rows="4" cols="80" name="booking_global_holidays" id="booking_global_holidays"></textarea>
                                                                                <img class="help_tip" width="16" height="16" data-tip="<?php _e( 'Select dates for which the booking will be completely disabled for all the products in your WooCommerce store.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" style="vertical-align:top;"/><br>
                                                                                <?php _e( "Please click on the date in calendar to add or delete the date from the holiday list.", "woocommerce-booking" ); ?>
                                                                                <div id="booking_switcher" name="booking_switcher"></div>
                                                                        </td>
                                                                    </tr>
                                                                     <tr>
                                                                        <td>
                                                                            <label for="booking_global_timeslot"><b><?php _e( 'Global Time Slot Booking:', 'woocommerce-booking' ); ?></b></label>
                                                                        </td>
                                                                        <td>
                                                                            <?php
                                                                            $global_timeslot = ""; 
                                                                            
                                                                            if ( isset( $saved_settings->booking_global_timeslot ) && $saved_settings->booking_global_timeslot == 'on' ) {
                                                                                $global_timeslot = "checked";
                                                                            }
                                                                            ?>
                                                                            <input type="checkbox" id="booking_global_timeslot" name="booking_global_timeslot" <?php echo $global_timeslot; ?>/>
                                                                            <img class="help_tip" width="16" height="16" data-tip="<?php _e( 'Please select this checkbox if you want ALL time slots to be unavailable for booking in all products once the lockout for that time slot is reached for any product.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" style="vertical-align:top;"/><br>
                                                                        </td>
                                                                    </tr>

                                                                     <tr>
                                                                        <td>
                                                                            <label for="booking_enable_rounding"><b><?php _e( 'Enable Rounding of Prices:', 'woocommerce-booking' ); ?></b></label>
                                                                        </td>
                                                                        <td>
                                                                                <?php
                                                                                $rounding = ""; 
                                                                                
                                                                                if ( isset( $saved_settings->enable_rounding ) && $saved_settings->enable_rounding == 'on' ) {
                                                                                        $rounding = 'checked';
                                                                                }

                                                                                ?>
                                                                                <input type="checkbox" id="booking_enable_rounding" name="booking_enable_rounding" <?php echo $rounding; ?>/>
                                                                                <img class="help_tip" w`idth="16" height="16" data-tip="<?php _e( 'Rounds the Price to the nearest Integer value.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                                                                        </td>
                                                                    </tr>
                                                                     <tr>
                                                                        <td>
                                                                            <label for="booking_global_selection"><b><?php _e( 'Duplicate dates from first product in the cart to other products:', 'woocommerce-booking' ); ?></b></label>
                                                                        </td>
                                                                        <td>
                                                                             <?php
                                                                             $global_selection = ""; 
                                                                             
                                                                             if ( isset( $saved_settings->booking_global_selection ) && $saved_settings->booking_global_selection == 'on' ){
                                                                                $global_selection = "checked";
                                                                             }
                                                                             ?>
                                                                             <input type="checkbox" id="booking_global_selection" name="booking_global_selection" <?php echo $global_selection; ?>/>
                                                                             <img class="help_tip" width="16" height="16" data-tip="<?php _e( 'Please select this checkbox if you want to select the date globally for All products once selected for a product and added to cart.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" style="vertical-align:top;"/><br>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                	    <td>
                                                                    	    <label for="booking_availability_display"><b><?php _e( 'Enable Availability Display on the Product page:', 'woocommerce-booking' ); ?></b></label>
                                                                        </td>
                                                                        <td>
                                                                            <?php
                                                                            $availability_display = ""; 
                                                                            
                                                                            if ( isset( $saved_settings->booking_availability_display ) && $saved_settings->booking_availability_display == 'on' ){
                                                                                $availability_display = "checked";
                                                                            }
                                                                            ?>
                                                                            <input type="checkbox" id="booking_availability_display" name="booking_availability_display" <?php echo $availability_display; ?>/>
                                                                            <img class="help_tip" width="16" height="16" data-tip="<?php _e( 'Please select this checkbox if you want to display the number of bookings available for a given product on a given date and time.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" style="vertical-align:top;"/><br>
                                                                        </td>
                                                                   </tr>
                                                                
                                                                    <tr>
                                                                	   <td width="20%">
                                                                    	   <label for="woo_product_addon_option_price"><b><?php _e( 'Charge WooCommerce Product Addons options on a Per Day Basis:', 'woocommerce-booking' ); ?></b></label>
                                                                       </td>
                                                                        <td>
                                                                            <?php
                                                                            $woo_product_addon_price = ""; 
                                                                            
                                                                            if ( isset( $saved_settings->woo_product_addon_price ) && $saved_settings->woo_product_addon_price == 'on' ){
                                                                                $woo_product_addon_price = "checked";
                                                                            }
                                                                        
                                                                            ?>
                                                                            <input type="checkbox" id="woo_product_addon_price" name="woo_product_addon_price" <?php echo $woo_product_addon_price; ?>/>
                                                                            <img class="help_tip" width="16" height="16" data-tip="<?php _e( 'Please select this checkbox if you want to multiply the option price of WooCommerce Product Addons with the number of booking days for Multiple Day Booking.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" style="vertical-align:top;"/><br>
                                                                        </td>
                                                                    </tr>
                                                                
                                                                    <tr>
                                                                	   <td width="20%">
                                                                    	   <label for="woo_gf_product_addon_option_price"><b><?php _e( 'Charge WooCommerce Gravity Forms Product Addons options on a Per Day Basis:', 'woocommerce-booking' ); ?></b></label>
                                                                       </td>
                                                                        <td>
                                                                            <?php
                                                                            $woo_gf_product_addon_option_price = ""; 
                                                                            
                                                                            if ( isset( $saved_settings->woo_gf_product_addon_option_price ) && $saved_settings->woo_gf_product_addon_option_price == 'on' ){
                                                                                $woo_gf_product_addon_option_price = "checked";
                                                                            }
                                                                        
                                                                            ?>
                                                                            <input type="checkbox" id="woo_gf_product_addon_option_price" name="woo_gf_product_addon_option_price" <?php echo $woo_gf_product_addon_option_price; ?>/>
                                                                            <img class="help_tip" width="16" height="16" data-tip="<?php _e( 'Please select this checkbox if you want to multiply the option price of WooCommerce Gravity Forms Product Addons with the number of booking days for Multiple Day Booking.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" style="vertical-align:top;"/><br>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>
                                                                            <label for="minimum_day_booking"><b><?php _e( 'Minimum Day Booking:', 'woocommerce-booking' ); ?></b></label>
                                                                        </td>
                                                                         <td>
                                                                              <?php
                                                                              $minimum_booking_checked  = "";
                                                                              $minimum_days_div_show    = 'none';
                                                                              
                                                                              if ( isset( $saved_settings->minimum_day_booking ) && $saved_settings->minimum_day_booking == 'on' ) {
                                                                                  $minimum_booking_checked  = 'checked';
                                                                                  $minimum_days_div_show    = 'block';
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
                                                                                                <td>
                                                                                                    <label for="global_booking_minimum_number_days"><b><?php _e( 'Minimum number of days to choose:', 'woocommerce-booking' ); ?></b></label>
                                                                                                </td>
                                                                                                <td>
                                                                                                    <?php 
                                                                                                        $minimum_day = "";
                                                                                                        if ( isset( $saved_settings->global_booking_minimum_number_days ) && $saved_settings->global_booking_minimum_number_days != "" ) {
                                                                                                            $minimum_day = $saved_settings->global_booking_minimum_number_days;
                                                                                                        } else {
                                                                                                            $minimum_day = "0";
                                                                                                        }		
                                                                                                    ?>
                                                                                                    <input type="text" name="global_booking_minimum_number_days" id="global_booking_minimum_number_days" value="<?php echo $minimum_day;?>" >
                                                                                                    <img class="help_tip" width="16" height="16" data-tip="<?php _e( 'The minimum number days you want to be booked for multiple day booking. For example, if you require minimum 2 days for booking, enter the value 2 in this field.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                                                                                               </td>
                                                                                            </tr>      
                                                                                        </table>
                                                                                 </div>
                                                                                        <table class="form-table">
                                                                                
                                                                                           <?php do_action( 'bkap_after_global_holiday_field' );?>
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
   
   public static function bkap_gcal_settings() {
        
       // First, we register a section. This is necessary since all future options must belong to one.
       add_settings_section(
       'bkap_gcal_sync_general_settings_section',		// ID used to identify this section and with which to register options
       __( 'General Settings', 'woocommerce-booking' ),		// Title to be displayed on the administration page
       array( 'bkap_gcal_sync_settings', 'bkap_gcal_sync_general_settings_callback' ),		// Callback used to render the description of the section
       'bkap_gcal_sync_settings_page'				// Page on which to add this section of options
       );
   
       add_settings_field(
       'bkap_calendar_event_location',
       __( 'Event Location', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_calendar_event_location_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_gcal_sync_general_settings_section',
       array ( __( '<br>Enter the text that will be used as location field in event of the Calendar. If left empty, website description is sent instead. <br><i>Note: You can use ADDRESS and CITY placeholders which will be replaced by their real values.</i>', 'woocommerce-booking' ) )
       );
   
       add_settings_field(
       'bkap_calendar_event_summary',
       __( 'Event summary (name)', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_calendar_event_summary_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_gcal_sync_general_settings_section'
           );
   
       add_settings_field(
       'bkap_calendar_event_description',
       __( 'Event Description', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_calendar_event_description_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_gcal_sync_general_settings_section',
       array( '<br>For the above 2 fields, you can use the following placeholders which will be replaced by their real values:&nbsp;SITE_NAME, CLIENT, PRODUCT_NAME, PRODUCT_WITH_QTY, ORDER_DATE_TIME, ORDER_DATE, ORDER_NUMBER, PRICE, PHONE, NOTE, ADDRESS, EMAIL (Client\'s email)	', 'woocommerce-booking' )
       );
   
       add_settings_section(
       'bkap_calendar_sync_customer_settings_section',
       __( 'Customer Add to Calendar button Settings', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_calendar_sync_customer_settings_callback' ),
       'bkap_gcal_sync_settings_page'
           );
   
       add_settings_field(
       'bkap_add_to_calendar_order_received_page',
       __( 'Show Add to Calendar button on Order received page', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_add_to_calendar_order_received_page_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_calendar_sync_customer_settings_section',
       array ( __( 'Show Add to Calendar button on the Order Received page for the customers.', 'woocommerce-booking' ) )
       );
   
       add_settings_field(
       'bkap_add_to_calendar_customer_email',
       __( 'Show Add to Calendar button in the Customer notification email', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_add_to_calendar_customer_email_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_calendar_sync_customer_settings_section',
       array ( __( 'Show Add to Calendar button in the Customer notification email.', 'woocommerce-booking' ) )
       );
   
       add_settings_field(
       'bkap_add_to_calendar_my_account_page',
       __( 'Show Add to Calendar button on My account', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_add_to_calendar_my_account_page_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_calendar_sync_customer_settings_section',
       array ( __( 'Show Add to Calendar button on My account page for the customers.', 'woocommerce-booking' ) )
       );
   
       add_settings_field(
       'bkap_calendar_in_same_window',
       __( 'Open Calendar in Same Window', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_calendar_in_same_window_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_calendar_sync_customer_settings_section',
       array ( __( 'As default, the Calendar is opened in a new tab or window. If you check this option, user will be redirected to the Calendar from the same page, without opening a new tab or window.', 'woocommerce-booking' ) )
       );
   
       add_settings_section(
       'bkap_calendar_sync_admin_settings_section',
       __( 'Admin Calendar Sync Settings', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_calendar_sync_admin_settings_section_callback' ),
       'bkap_gcal_sync_settings_page'
           );
   
       add_settings_field(
       'bkap_calendar_sync_integration_mode',
       __( 'Integration Mode', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_calendar_sync_integration_mode_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_calendar_sync_admin_settings_section',
       array ( __( '<br>Select method of integration. "Sync Automatically" will add the booking events to the Google calendar, which is set in the "Calendar to be used" field, automatically when a customer places an order. <br>"Sync Manually" will add an "Add to Calendar" button in emails received by admin on New customer order and on the View Booking Calendar page.<br>"Disabled" will disable the integration with Google Calendar.<br>Note: Import of the events will work manually using .ics link.', 'woocommerce-booking' ) )
       );
   
       add_settings_field(
       'bkap_sync_calendar_instructions',
       __( 'Instructions', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_sync_calendar_instructions_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_calendar_sync_admin_settings_section'
           );
   
   
       add_settings_field(
       'bkap_calendar_key_file_name',
       __( 'Key file name', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_calendar_key_file_name_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_calendar_sync_admin_settings_section',
       array( '<br>Enter key file name here without extention, e.g. ab12345678901234567890-privatekey.', 'woocommerce-booking' )
       );
   
       add_settings_field(
       'bkap_calendar_service_acc_email_address',
       __( 'Service account email address', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_calendar_service_acc_email_address_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_calendar_sync_admin_settings_section',
       array( '<br>Enter Service account email address here, e.g. 1234567890@developer.gserviceaccount.com.', 'woocommerce-booking' )
       );
   
       add_settings_field(
       'bkap_calendar_id',
       __( 'Calendar to be used', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_calendar_id_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_calendar_sync_admin_settings_section',
       array( '<br>Enter the ID of the calendar in which your deliveries will be saved, e.g. abcdefg1234567890@group.calendar.google.com.', 'woocommerce-booking' )
       );
   
       add_settings_field(
       'bkap_calendar_test_connection',
       '',
       array( 'bkap_gcal_sync_settings', 'bkap_calendar_test_connection_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_calendar_sync_admin_settings_section'
           );
   
       add_settings_field(
       'bkap_admin_add_to_calendar_view_booking',
       __( 'Show Add to Calendar button on View Bookings page', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_admin_add_to_calendar_view_booking_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_calendar_sync_admin_settings_section',
       array( 'Show "Add to Calendar" button on the Booking -> View Bookings page.<br><i>Note: This button can be used to export the already placed orders with future bookings from the current date to the calendar used above.</i>', 'woocommerce-booking' )
       );
   
       add_settings_field(
       'bkap_admin_add_to_calendar_email_notification',
       __( 'Show Add to Calendar button in New Order email notification', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_admin_add_to_calendar_email_notification_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_calendar_sync_admin_settings_section',
       array( 'Show "Add to Calendar" button in the New Order email notification.', 'woocommerce-booking' )
       );
   
       add_settings_section(
       'bkap_calendar_import_ics_feeds_section',
       __( 'Import Events', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_calendar_import_ics_feeds_section_callback' ),
       'bkap_gcal_sync_settings_page'
           );
   
       add_settings_field(
       'bkap_ics_feed_url_instructions',
       __( 'Instructions', 'woocommerce-booking'  ),
       array( 'bkap_gcal_sync_settings', 'bkap_ics_feed_url_instructions_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_calendar_import_ics_feeds_section'
           );
   
       add_settings_field(
       'bkap_ics_feed_url',
       __( 'iCalendar/.ics Feed URL', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_ics_feed_url_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_calendar_import_ics_feeds_section'
           );
   
       register_setting(
       'bkap_gcal_sync_settings',
       'bkap_calendar_event_location'
           );
   
       register_setting(
       'bkap_gcal_sync_settings',
       'bkap_calendar_event_summary'
           );
   
       register_setting(
       'bkap_gcal_sync_settings',
       'bkap_calendar_event_description'
           );
   
       register_setting(
       'bkap_gcal_sync_settings',
       'bkap_add_to_calendar_order_received_page'
           );
   
       register_setting(
       'bkap_gcal_sync_settings',
       'bkap_add_to_calendar_customer_email'
           );
   
       register_setting(
       'bkap_gcal_sync_settings',
       'bkap_add_to_calendar_my_account_page'
           );
   
       register_setting(
       'bkap_gcal_sync_settings',
       'bkap_calendar_in_same_window'
           );
   
       register_setting(
       'bkap_gcal_sync_settings',
       'bkap_calendar_sync_integration_mode'
           );
   
       register_setting(
       'bkap_gcal_sync_settings',
       'bkap_calendar_details_1'
           );
   
       register_setting(
       'bkap_gcal_sync_settings',
       'bkap_admin_add_to_calendar_view_booking'
           );
   
       register_setting(
       'bkap_gcal_sync_settings',
       'bkap_admin_add_to_calendar_email_notification'
           );
   
       register_setting(
       'bkap_gcal_sync_settings',
       'bkap_ics_feed_url_instructions'
           );
   
       register_setting(
       'bkap_gcal_sync_settings',
       'bkap_ics_feed_url'
           );
   }
}// End of the class

?>