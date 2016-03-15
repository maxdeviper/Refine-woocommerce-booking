<?php 
class bkap_gcal_sync_settings {

    /**
     * Callback for the gcal general settings section
     */
    public static function bkap_gcal_sync_general_settings_callback() {}
    
    public static function bkap_calendar_event_location_callback( $args ) {
        $google_calendar_location = get_option( 'bkap_calendar_event_location' );
        echo '<input type="text" name="bkap_calendar_event_location" id="bkap_calendar_event_location" value="' . $google_calendar_location . '" />';
        $html = '<label for="bkap_calendar_event_location"> ' . $args[0] . '</label>';
        echo $html;
    }
    
    public static function bkap_calendar_event_summary_callback( $args ) {
        $gcal_summary = get_option( 'bkap_calendar_event_summary' );
        echo '<input id="bkap_calendar_event_summary" name="bkap_calendar_event_summary" value="' . $gcal_summary . '" size="90" name="gcal_summary" type="text"/>';
    }
    
    public static function bkap_calendar_event_description_callback( $args ) {
        $gcal_description = get_option( 'bkap_calendar_event_description' );
        echo '<textarea id="bkap_calendar_event_description" name="bkap_calendar_event_description" cols="90" rows="4" name="gcal_description">' . $gcal_description .'</textarea>';
        $html = '<label for="bkap_calendar_event_description"> ' . $args[0] . '</label>';
        echo $html;
    }
    
    public static function bkap_calendar_sync_customer_settings_callback() {}
    
    public static function bkap_add_to_calendar_order_received_page_callback( $args ) {
        $add_to_calendar_order_received = "";
        if ( get_option( 'bkap_add_to_calendar_order_received_page' ) == 'on' ) {
            $add_to_calendar_order_received = "checked";
        }
         
        echo '<input type="checkbox" name="bkap_add_to_calendar_order_received_page" id="bkap_add_to_calendar_order_received_page" class="day-checkbox" value="on" ' . $add_to_calendar_order_received . ' />';
        $html = '<label for="bkap_add_to_calendar_order_received_page"> ' . $args[0] . '</label>';
        echo $html;
    }
    
    public static function bkap_add_to_calendar_customer_email_callback( $args ) {
        $add_to_calendar_customer_email = "";
        if ( get_option( 'bkap_add_to_calendar_customer_email' ) == 'on' ) {
            $add_to_calendar_customer_email = "checked";
        }
         
        echo '<input type="checkbox" name="bkap_add_to_calendar_customer_email" id="bkap_add_to_calendar_customer_email" class="day-checkbox" value="on" ' . $add_to_calendar_customer_email . ' />';
        $html = '<label for="bkap_add_to_calendar_customer_email"> ' . $args[0] . '</label>';
        echo $html;
    }
    
    public static function bkap_add_to_calendar_my_account_page_callback( $args ) {
        $bkap_add_to_calendar_my_account_page = "";
        if ( get_option( 'bkap_add_to_calendar_my_account_page' ) == 'on' ) {
            $bkap_add_to_calendar_my_account_page = "checked";
        }
         
        echo '<input type="checkbox" name="bkap_add_to_calendar_my_account_page" id="bkap_add_to_calendar_my_account_page" class="day-checkbox" value="on" ' . $bkap_add_to_calendar_my_account_page . ' />';
        $html = '<label for="bkap_add_to_calendar_my_account_page"> ' . $args[0] . '</label>';
        echo $html;
    }
    
    public static function bkap_calendar_in_same_window_callback( $args ) {
        $google_calendar_same_window = "";
        if ( get_option( 'bkap_calendar_in_same_window' ) == 'on' ) {
            $google_calendar_same_window = "checked";
        }
         
        echo '<input type="checkbox" name="bkap_calendar_in_same_window" id="bkap_calendar_in_same_window" class="day-checkbox" value="on" ' . $google_calendar_same_window . ' />';
        $html = '<label for="bkap_calendar_in_same_window"> ' . $args[0] . '</label>';
        echo $html;
    }
    
    public static function bkap_calendar_sync_admin_settings_section_callback() {}
    
    public static function bkap_calendar_sync_integration_mode_callback( $args ) {
        $sync_directly = "";
        $sync_manually = "";
        $sync_disable = "checked";
        if ( get_option( 'bkap_calendar_sync_integration_mode' ) == 'manually' ) {
            $sync_manually = "checked";
            $sync_disable = "";
        } else if( get_option( 'bkap_calendar_sync_integration_mode' ) == 'directly' ) {
            $sync_directly = "checked";
            $sync_disable = "";
        }
        echo '<input type="radio" name="bkap_calendar_sync_integration_mode" id="bkap_calendar_sync_integration_mode" value="directly" ' . $sync_directly . '/>' . __( 'Sync Automatically', 'woocommerce-booking' ) . '&nbsp;&nbsp;
            <input type="radio" name="bkap_calendar_sync_integration_mode" id="bkap_calendar_sync_integration_mode" value="manually" ' . $sync_manually . '/>' . __( 'Sync Manually', 'woocommerce-booking' ) . '&nbsp;&nbsp;
            <input type="radio" name="bkap_calendar_sync_integration_mode" id="bkap_calendar_sync_integration_mode" value="disabled" ' . $sync_disable . '/>' . __( 'Disabled', 'woocommerce-booking' );
    
        $html = '<label for="bkap_calendar_sync_integration_mode"> ' . $args[0] . '</label>';
        echo $html;
        ?>
        <script type="text/javascript">
            jQuery( document ).ready( function() {
                var isChecked = jQuery( "#bkap_calendar_sync_integration_mode:checked" ).val();
                if( isChecked == "directly" ) {
                   i = 0;
                   jQuery( ".form-table" ).each( function() {
                        if( i == 2 ) {
                            k = 0;
                            var row = jQuery( this ).find( "tr" );
                            jQuery.each( row , function() {
                                if( k == 7 ) {
                                    jQuery( this ).fadeOut();
                                } else {
                                    jQuery( this ).fadeIn();
                                }
                                k++;
                            });
                        } else {
                            jQuery( this ).fadeIn();
                        }
                        i++;
                    } );
                } else if( isChecked == "manually" ) {
                    i = 0;
                    jQuery( ".form-table" ).each( function() {
                        if( i == 2 ) {
                            k = 0;
                            var row = jQuery( this ).find( "tr" );
                            jQuery.each( row , function() {
                                if( k != 7 && k != 0 ) {
                                    jQuery( this ).fadeOut();
                                } else {
                                    jQuery( this ).fadeIn();
                                }
                                k++;
                            });
                        } else {
                            jQuery( this ).fadeIn();
                        }
                        i++;
                    });
                } else if( isChecked == "disabled" ) {
                    i = 0;
                    jQuery( ".form-table" ).each( function() {
                        if( i == 2 ) {
                            k = 0;
                            var row = jQuery( this ).find( "tr" );
                            jQuery.each( row , function() {
                                if( k != 0 ) {
                                    jQuery( this ).fadeOut();
                                } else {
                                    jQuery( this ).fadeIn();
                                }
                                k++;
                            });
                        } else {
                            jQuery( this ).fadeIn();
                        }
                        i++;
                    });
                }
                jQuery( "input[type=radio][id=bkap_calendar_sync_integration_mode]" ).change( function() {
                    var isChecked = jQuery( this ).val();
                    if( isChecked == "directly" ) {
                        i = 0;
                        jQuery( ".form-table" ).each( function() {
                            if( i == 2 ) {
                                k = 0;
                                var row = jQuery( this ).find( "tr" );
                                jQuery.each( row , function() {
                                    if( k == 7 ) {
                                        jQuery( this ).fadeOut();
                                    } else {
                                        jQuery( this ).fadeIn();
                                    }
                                    k++;
                                });
                            } else {
                                jQuery( this ).fadeIn();
                            }
                            i++;
                        } );
                    } else if( isChecked == "manually" ) {
                        i = 0;
                        jQuery( ".form-table" ).each( function() {
                            if( i == 2 ) {
                                k = 0;
                                var row = jQuery( this ).find( "tr" );
                                jQuery.each( row , function() {
                                    if( k != 7 && k != 0 ) {
                                        jQuery( this ).fadeOut();
                                    } else {
                                        jQuery( this ).fadeIn();
                                    }
                                    k++;
                                });
                            } else {
                                jQuery( this ).fadeIn();
                            }
                            i++;
                        });
                    } else if( isChecked == "disabled" ) {
                        i = 0;
                        jQuery( ".form-table" ).each( function() {
                            if( i == 2 ) {
                                k = 0;
                                var row = jQuery( this ).find( "tr" );
                                jQuery.each( row , function() {
                                    if( k != 0 ) {
                                        jQuery( this ).fadeOut();
                                    } else {
                                        jQuery( this ).fadeIn();
                                    }
                                    k++;
                                });
                            } else {
                                jQuery( this ).fadeIn();
                            }
                            i++;
                        });
                    }
                })
            });
        </script>
        <?php 
    }
    
    public static function bkap_sync_calendar_instructions_callback( ) {
        echo 'To set up Google Calendar API, please click on "Show me how" link and carefully follow these steps:
            <span class="description" ><a href="#bkap-instructions" id="show_instructions" data-target="api-instructions" class="bkap-info_trigger" title="' . __ ( 'Click to toggle instructions', 'woocommerce-booking') . '">' . __( 'Show me how', 'woocommerce-booking' ) . '</a></span>';
        ?> <div class="description bkap-info_target api-instructions" style="display: none;">
                <ul style="list-style-type:decimal;">
                    <li><?php _e( 'Google Calendar API requires php V5.3+ and some php extensions.', 'woocommerce-booking' ) ?> </li>
                    <li><?php printf( __( 'Go to Google APIs console by clicking %s. Login to your Google account if you are not already logged in.', 'woocommerce-booking' ), '<a href="https://code.google.com/apis/console/" target="_blank">https://code.google.com/apis/console/</a>' ) ?></li>
                    <li><?php _e( 'Create a new project using the left side pane. Click on \'Home\' option. Name the project "Bookings" (or use your chosen name instead).', 'woocommerce-booking' ) ?></li>
                    <li><?php _e( 'Click on API Manager from left side pane.', 'woocommerce-booking' ) ?></li>
                    <li><?php _e( 'Click "Calendar API" under Google Apps APIs and Enable the API.', 'woocommerce-booking' ) ?></li>
                    <li><?php _e( 'Go to "Credentials" menu in the left side pane and click on "New Credentials" dropdown.', 'woocommerce-booking' ) ?></li>
                    <li><?php _e( 'Click on "OAuth client ID" option. Then click on Configure consent screen.', 'woocommerce-booking' )?></li>
                    <li><?php _e( 'Enter a Product Name, e.g. Bookings and Appointments, inside the opening pop-up. Click Save.', 'woocommerce-booking' ) ?></li>
                    <li><?php _e( 'Select "Web Application" option, enter the Web client name and create the client ID.', 'woocommerce-booking' )?></li>
                    <li><?php _e( 'Click on New Credentials dropdown and select "Service account key".', 'woocommerce-booking' ) ?></li>
                    <li><?php _e( 'Click "Service account" and select "New service account" and enter the name. Now select key type as "P12" and create the service account.', 'woocommerce-booking' ) ?></li>
                    <li><?php _e( 'A file with extension .p12 will be downloaded.', 'woocommerce-booking' ) ?></li>
                    <li><?php printf( __( 'Using your FTP client program, copy this key file to folder: %s . This file is required as you will grant access to your Google Calendar account even if you are not online. So this file serves as a proof of your consent to access to your Google calendar account. Note: This file cannot be uploaded in any other way. If you do not have FTP access, ask the website admin to do it for you.', 'woocommerce-booking' ), plugin_dir_path( __FILE__ ) .'gcal/key/' ) ?></li>
                    <li><?php _e( 'Enter the name of the key file to "Key file name" setting of Booking. Exclude the extention .p12.', 'woocommerce-booking' ) ?></li>
                    <li><?php _e( 'Copy "Email address" setting from Manage service account of Google apis console and paste it to "Service account email address" setting of Booking.', 'woocommerce-booking' ) ?></li>
                    <li><?php printf( __( 'Open your Google Calendar by clicking this link: %s', 'woocommerce-booking' ), '<a href="https://www.google.com/calendar/render" target="_blank">https://www.google.com/calendar/render</a>' ) ?></li>
                    <li><?php _e( 'Create a new Calendar by selecting "my Calendars > Create new calendar" on left side pane. <b>Try NOT to use your primary calendar.</b>', 'woocommerce-booking' ) ?></li>
                    <li><?php _e( 'Give a name to the new calendar, e.g. Bookings calendar. <b>Check that Calendar Time Zone setting matches with time zone setting of your WordPress website.</b> Otherwise there will be a time shift.', 'woocommerce-booking' ) ?></li>		
                    <li><?php _e( 'Paste already copied "Email address" setting from Manage service account of Google apis console to "Person" field under "Share with specific person".', 'woocommerce-booking' ) ?></li>
                    <li><?php _e( 'Set "Permission Settings" of this person as "Make changes to events".', 'woocommerce-booking' ) ?></li>
                    <li><?php _e( 'Click "Add Person".', 'woocommerce-booking' ) ?></li>
                    <li><?php _e( 'Click "Create Calendar".', 'woocommerce-booking' ) ?></li>
                    <li><?php _e( 'Select the created calendar and click "Calendar settings".', 'woocommerce-booking' ) ?></li>
                    <li><?php _e( 'Copy "Calendar ID" value on Calendar Address row.', 'woocommerce-booking' ) ?></li>
                    <li><?php _e( 'Paste this value to "Calendar to be used" field of Booking settings.', 'woocommerce-booking' ) ?></li>
                    <li><?php _e( 'Select the desired Integration mode: Sync Automatically or Sync Manually.', 'woocommerce-booking' ) ?></li>
                    <li><?php _e( 'Click "Save Settings" on Booking settings.', 'woocommerce-booking' ) ?></li>
                    <li><?php _e( 'After these stages, you have set up Google Calendar API. To test the connection, click the "Test Connection" link.', 'woocommerce-booking' ) ?></li>
                    <li><?php _e( 'If you get a success message, you should see a test event inserted to the Google Calendar and you are ready to go. If you get an error message, double check your settings.', 'woocommerce-booking' ) ?></li>
                </ul>
            </div>
            <script type="text/javascript">
                function toggle_target (e) {
                	if ( e && e.preventDefault ) { 
                        e.preventDefault();
                    }
                	if ( e && e.stopPropagation ) {
                        e.stopPropagation();
                    }
                	var target = jQuery(".bkap-info_target.api-instructions" );
                	if ( !target.length ) {
                        return false;
                    }
                    
                	if ( target.is( ":visible" ) ) {
                        target.hide( "fast" );
                    } else {
                        target.show( "fast" );
                    }
                
                	return false;
                }
                jQuery(function () {
                	jQuery(document).on("click", ".bkap-info_trigger", toggle_target);
                });
            </script>
            <?php
        }
        
        public static function bkap_calendar_key_file_name_callback( $args ) {
            $gcal_key_file_arr = get_option( 'bkap_calendar_details_1' );
            if( isset( $gcal_key_file_arr[ 'bkap_calendar_key_file_name' ] ) ) {
                $gcal_key_file = $gcal_key_file_arr[ 'bkap_calendar_key_file_name' ];
            } else {
                $gcal_key_file = '';
            }
            echo '<input id="bkap_calendar_details_1[bkap_calendar_key_file_name]" name= "bkap_calendar_details_1[bkap_calendar_key_file_name]" value="' . $gcal_key_file .'" size="90" name="gcal_key_file" type="text" />';
            $html = '<label for="bkap_calendar_key_file_name"> ' . $args[0] . '</label>';
            echo $html;
        }
        
        public static function bkap_calendar_service_acc_email_address_callback( $args ) {
            $gcal_service_account_arr = get_option( 'bkap_calendar_details_1' );
            if( isset( $gcal_service_account_arr[ 'bkap_calendar_service_acc_email_address' ] ) ) {
                $gcal_service_account = $gcal_service_account_arr[ 'bkap_calendar_service_acc_email_address' ];
            } else {
                $gcal_service_account = '';
            }
        
            echo '<input id="bkap_calendar_details_1[bkap_calendar_service_acc_email_address]" name="bkap_calendar_details_1[bkap_calendar_service_acc_email_address]" value="' . $gcal_service_account . '" size="90" name="gcal_service_account" type="text"/>';
            $html = '<label for="bkap_calendar_service_acc_email_address"> ' . $args[0] . '</label>';
            echo $html;
        }
        
        public static function bkap_calendar_id_callback( $args ) {
            $gcal_selected_calendar_arr = get_option( 'bkap_calendar_details_1' );
            if( isset( $gcal_selected_calendar_arr[ 'bkap_calendar_id' ] ) ) {
                $gcal_selected_calendar = $gcal_selected_calendar_arr[ 'bkap_calendar_id' ];
            } else {
                $gcal_selected_calendar = '';
            }
            echo '<input id="bkap_calendar_details_1[bkap_calendar_id]" name="bkap_calendar_details_1[bkap_calendar_id]" value="' . $gcal_selected_calendar . '" size="90" name="gcal_selected_calendar" type="text" />';
            $html = '<label for="bkap_calendar_id"> ' . $args[0] . '</label>';
            echo $html;
        }
        
        public static function bkap_calendar_test_connection_callback() {
            echo "<script type='text/javascript'>
                jQuery( document ).on( 'click', '#test_connection', function( e ) {
                    e.preventDefault();
                    var data = {
                        gcal_api_test_result: '',
                        gcal_api_pre_test: '',
                        gcal_api_test: 1,
                        action: 'display_nag'
                    };
                    jQuery( '#test_connection_ajax_loader' ).show();
                    jQuery.post( '" . get_admin_url() . "/admin-ajax.php', data, function( response ) {
                        jQuery( '#test_connection_message' ).html( response );
                        jQuery( '#test_connection_ajax_loader' ).hide();
                    });
            
            
            });
            </script>";
            print "<a href='admin.php?page=woocommerce_booking_page&action=calendar_sync_settings' id='test_connection'>" . __( 'Test Connection', 'woocommerce-booking' ) . "</a>
                <img src='" . plugins_url() . "/woocommerce-booking/images/ajax-loader.gif' id='test_connection_ajax_loader'>";
            print "<div id='test_connection_message'></div>";
        }
        
        public static function bkap_admin_add_to_calendar_view_booking_callback( $args ) {
            $bkap_admin_add_to_calendar_view_bookings = "";
            if( get_option( 'bkap_admin_add_to_calendar_view_booking' ) == "on" ) {
                $bkap_admin_add_to_calendar_view_bookings = "checked";
            }
            echo '<input type="checkbox" name="bkap_admin_add_to_calendar_view_booking" id="bkap_admin_add_to_calendar_view_booking" value="on" ' . $bkap_admin_add_to_calendar_view_bookings . ' />';
            $html = '<label for="bkap_admin_add_to_calendar_view_booking"> ' . $args[0] . '</label>';
            echo $html;
        }
        
        public static function bkap_admin_add_to_calendar_email_notification_callback( $args ) {
            $bkap_admin_add_to_calendar_email_notification = "";
            if( get_option( 'bkap_admin_add_to_calendar_email_notification' ) == "on" ) {
                $bkap_admin_add_to_calendar_email_notification = "checked";
            }
            echo '<input type="checkbox" name="bkap_admin_add_to_calendar_email_notification" id="bkap_admin_add_to_calendar_email_notification" value="on" ' . $bkap_admin_add_to_calendar_email_notification . ' />';
            $html = '<label for="bkap_admin_add_to_calendar_email_notification"> ' . $args[0] . '</label>';
            echo $html;
        }
        
        public static function bkap_calendar_import_ics_feeds_section_callback() {
            echo 'Events will be imported using the ICS Feed url. Each event will create a new WooCommerce Order. The event\'s date & time will be set as the item\'s Booking Date & Time. <br>Lockout will be updated for the product for the set Booking Date & Time.';
        }
        
        public static function bkap_ics_feed_url_instructions_callback() {
            echo 'To set up Import events using ics feed urls, please click on "Show me how" link and carefully follow these steps:
                <span class="ics-feed-description" ><a href="#bkap-ics-feed-instructions" id="show_instructions" data-target="api-instructions" class="bkap_ics_feed-info_trigger" title="' . __ ( 'Click to toggle instructions', 'woocommerce-booking' ) . '">' . __( 'Show me how', 'woocommerce-booking' ) . '</a></span>';
            ?> 
            <div class="ics-feed-description bkap_ics_feed-info_target api-instructions" style="display: none;">
                    <ul style="list-style-type:decimal;">
                        <li><?php printf( __( 'Open your Google Calendar by clicking this link: %s', 'woocommerce-booking' ), '<a href="https://www.google.com/calendar/render" target="_blank">https://www.google.com/calendar/render</a>' ) ?></li>
                        <li><?php _e( 'Select the calendar to be imported and click "Calendar settings".', 'woocommerce-booking' ) ?></li>
                        <li><?php _e( 'Click on "ICAL" button in Calendar Address option.', 'woocommerce-booking' ) ?></li>		
                        <li><?php _e( 'Copy the basic.ics file URL.', 'woocommerce-booking' ) ?></li>
                        <li><?php _e( 'Paste this link in the text box under Google Calendar Sync tab -> Import Events section.', 'woocommerce-booking' ) ?></li>
                        <li><?php _e( 'Save the URL.', 'woocommerce-booking' ) ?></li>
                        <li><?php _e( 'Click on "Import Events" button to import the events from the calendar.', 'woocommerce-booking' ) ?></li>
                        <li><?php _e( 'You can import multiple calendars by using ics feeds. Add them using the Add New Ics Feed url button.', 'woocommerce-booking' ) ?></li>
                    </ul>
                </div>
                <script type="text/javascript">
                    function bkap_ics_feed_toggle_target (e) {
                    	if ( e && e.preventDefault ) { 
                            e.preventDefault();
                        }
                    	if ( e && e.stopPropagation ) {
                            e.stopPropagation();
                        }
                    	var target = jQuery( ".bkap_ics_feed-info_target.api-instructions" );
                    	if ( !target.length ) {
                            return false;
                        }
                        
                    	if ( target.is( ":visible" ) ) {
                            target.hide( "fast" );
                        } else {
                            target.show( "fast" );
                        }
                    
                    	return false;
                    }
                    jQuery( function () { 
                    	jQuery(document).on( "click", ".bkap_ics_feed-info_trigger", bkap_ics_feed_toggle_target );
                    });
                </script>
                <?php
            }
                
            public static function bkap_ics_feed_url_callback( $args ) {
                echo '<table id="bkap_ics_url_list">';
                $ics_feed_urls = get_option( 'bkap_ics_feed_urls' );
                if( $ics_feed_urls == '' || $ics_feed_urls == '{}' || $ics_feed_urls == '[]' || $ics_feed_urls == 'null' ) {
                    $ics_feed_urls = array();
                }
                
                if( count( $ics_feed_urls ) > 0 ) {
                    foreach ( $ics_feed_urls as $key => $value ) {
                        echo "<tr id='$key'>
                            <td class='ics_feed_url'>
                                <input type='text' id='bkap_ics_fee_url_$key' size='60' value='" . $value. "'>
                            </td>
                            <td class='ics_feed_url'>
                                <input type='button' value='Save' id='save_ics_url' class='save_button' name='$key' disabled='disabled'>
                            </td>
                            <td class='ics_feed_url'>
                                <input type='button' class='save_button' id='$key' name='import_ics' value='Import Events'>
                            </td>
                            <td class='ics_feed_url'>
                                <input type='button' class='save_button' id='$key' value='Delete' name='delete_ics_feed'>
                            </td>
                            <td class='ics_feed_url'>
                                <div id='import_event_message' style='display:none;'>
                                    <img src='" . plugins_url() . "/woocommerce-booking/images/ajax-loader.gif'>
                                </div>
                                <div id='success_message' ></div>
                            </td>
                        </tr>";
                    }
                } else {
                    echo "<tr id='0' >
                        <td class='ics_feed_url'>
                            <input type='text' id='bkap_ics_fee_url_0' size='60' >
                        </td>
                        <td class='ics_feed_url'>
                            <input type='button' value='Save' id='save_ics_url' class='save_button' name='0' >
                        </td>
                        <td class='ics_feed_url'>
                            <input type='button' class='save_button' id='0' name='import_ics' value='Import Events' disabled='disabled'>
                        </td>
                        <td class='ics_feed_url'>
                            <input type='button' class='save_button' id='0' name='delete_ics_feed' value='Delete' disabled='disabled'>
                        </td>
                        <td class='ics_feed_url'>
                            <div id='import_event_message' style='display:none;'>
                                <img src='" . plugins_url() . "/woocommerce-booking/images/ajax-loader.gif'>
                            </div>
                            <div id='success_message' ></div>
                        </td>
                    </tr>";
                }
                echo'</table>';
                
                echo "<input type='button' class='save_button' id='add_new_ics_feed' name='add_new_ics_feed' value='Add New Ics feed url'>";
                echo "<script type='text/javascript'>
                    jQuery( document ).ready( function() {
                        
                        jQuery( '#add_new_ics_feed' ).on( 'click', function() {
                            var rowCount = jQuery( '#bkap_ics_url_list tr' ).length;
                            jQuery( '#bkap_ics_url_list' ).append( '<tr id=\'' + rowCount + '\'><td class=\'ics_feed_url\'><input type=\'text\' id=\'bkap_ics_fee_url_' + rowCount + '\' size=\'60\' ></td><td class=\'ics_feed_url\'><input type=\'button\' value=\'Save\' id=\'save_ics_url\' class=\'save_button\' name=\'' + rowCount + '\'></td><td class=\'ics_feed_url\'><input type=\'button\' class=\'save_button\' id=\'' + rowCount + '\' name=\'import_ics\' value=\'Import Events\' disabled=\'disabled\'></td><td class=\'ics_feed_url\'><input type=\'button\' class=\'save_button\' id=\'' + rowCount + '\' value=\'Delete\' disabled=\'disabled\'  name=\'delete_ics_feed\' ></td><td class=\'ics_feed_url\'><div id=\'import_event_message\' style=\'display:none;\'><img src=\'" . plugins_url() . "/woocommerce-booking/images/ajax-loader.gif\'></div><div id=\'success_message\' ></div></td></tr>' );
                        });
                    
                        jQuery( document ).on( 'click', '#save_ics_url', function() {
                            var key = jQuery( this ).attr( 'name' );
                            var data = {
                                ics_url: jQuery( '#bkap_ics_fee_url_' + key ).val(),
                                action: 'bkap_save_ics_url_feed'
                            };
                            jQuery.post( '" . get_admin_url() . "/admin-ajax.php', data, function( response ) {
                                if( response == 'yes' ) {
                                    jQuery( 'input[name=\'' + key + '\']' ).attr( 'disabled','disabled' );
                                    jQuery( 'input[id=\'' + key + '\']' ).removeAttr( 'disabled' );
                                } 
                            });
                        });
                        
                        jQuery( document ).on( 'click', 'input[type=\'button\'][name=\'delete_ics_feed\']', function() {
                            var key = jQuery( this ).attr( 'id' );
                            var data = {
                                ics_feed_key: key,
                                action: 'bkap_delete_ics_url_feed'
                            };
                            jQuery.post( '" . get_admin_url() . "/admin-ajax.php', data, function( response ) {
                                if( response == 'yes' ) {
                                    jQuery( 'table#bkap_ics_url_list tr#' + key ).remove();
                                } 
                            });
                        });
                        
                        jQuery( document ).on( 'click', 'input[type=\'button\'][name=\'import_ics\']', function() {
                            jQuery( '#import_event_message' ).show();
                            var key = jQuery( this ).attr( 'id' );
                            var data = {
                                ics_feed_key: key,
                                action: 'bkap_import_events'
                            };
                            jQuery.post( '" . get_admin_url() . "/admin-ajax.php', data, function( response ) {
                                jQuery( '#import_event_message' ).hide();
                                jQuery( '#success_message' ).html( response );  
                                jQuery( '#success_message' ).fadeIn();
                                setTimeout( function() {
                                    jQuery( '#success_message' ).fadeOut();
                                },3000 );
                            });
                        });
                    });
                </script>";
            }
        
        
} // End of class
?>