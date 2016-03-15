<?php

class bkap_license{
    
   /**
    * This function add the license page in the Booking menu.
    */
   Public static function bkap_get_edd_sample_license_page() {
           $license =   get_option( 'edd_sample_license_key' );
           $status 	=   get_option( 'edd_sample_license_status' );

           ?>
           <div class="wrap">
                   <h2><?php _e( 'Plugin License Options', 'woocommerce-booking' ); ?></h2>
                   <form method="post" action="options.php">

                           <?php settings_fields('edd_sample_license'); ?>

                           <table class="form-table">
                                   <tbody>
                                           <tr valign="top">	
                                                   <th scope="row" valign="top">
                                                           <?php _e( 'License Key' , 'woocommerce-booking'); ?>
                                                   </th>
                                                   <td>
                                                           <input id="edd_sample_license_key" name="edd_sample_license_key" type="text" class="regular-text" value="<?php esc_attr_e( $license ); ?>" />
                                                           <label class="description" for="edd_sample_license_key"><?php _e( 'Enter your license key' , 'woocommerce-booking'); ?></label>
                                                   </td>
                                           </tr>
                                           <?php if( false !== $license ) { ?>
                                                   <tr valign="top">	
                                                           <th scope="row" valign="top">
                                                                   <?php _e( 'Activate License', 'woocommerce-booking' ); ?>
                                                           </th>
                                                           <td>
                                                                   <?php if( $status !== false && $status == 'valid' ) { ?>
                                                                           <span style="color:green;"><?php _e( 'active' , 'woocommerce-booking'); ?></span>
                                                                           <?php wp_nonce_field( 'edd_sample_nonce', 'edd_sample_nonce' ); ?>
                                                                           <input type="submit" class="button-secondary" name="edd_license_deactivate" value="<?php _e( 'Deactivate License', 'woocommerce-booking' ); ?>"/>
                                                                   <?php } else {
                                                                           wp_nonce_field( 'edd_sample_nonce', 'edd_sample_nonce' ); ?>
                                                                           <input type="submit" class="button-secondary" name="edd_license_activate" value="<?php _e('Activate License', 'woocommerce-booking'); ?>"/>
                                                                   <?php } ?>
                                                           </td>
                                                   </tr>
                                           <?php } ?>
                                   </tbody>
                           </table>	
                           <?php submit_button(); ?>

                   </form>
           <?php
   }
   
   /**
    * This function will store the license key in database of the site once the plugin is installed and the license key saved.
    */
    Public static function bkap_edd_sample_register_option() {
            // creates our settings in the options table
            register_setting( 'edd_sample_license', 'edd_sample_license_key', array( 'bkap_license', 'bkap_get_edd_sanitize_license' ) );
    }
    
   /**
    * This function will check the license entered using an API call to the store website.
    *  And if its valid it will activate the license. 
    */
    Public static function bkap_edd_sample_activate_license() {

           // listen for our activate button to be clicked
           if( isset( $_POST['edd_license_activate'] ) ) {

                   // run a quick security check
                   if( ! check_admin_referer( 'edd_sample_nonce', 'edd_sample_nonce' ) )
                           return; // get out if we didn't click the Activate button

                   // retrieve the license from the database
                   $license = trim( get_option( 'edd_sample_license_key' ) );


                   // data to send in our API request
                   $api_params = array(
                                   'edd_action' => 'activate_license',
                                   'license' 	=> $license,
                                   'item_name'  => urlencode( EDD_SL_ITEM_NAME_BOOK ) // the name of our product in EDD
                   );

                   // Call the custom API.
                   $response = wp_remote_get( esc_url_raw( add_query_arg( $api_params, EDD_SL_STORE_URL_BOOK ) ), array( 'timeout' => 15, 'sslverify' => false ) );

                   // make sure the response came back okay
                   if ( is_wp_error( $response ) )
                           return false;

                   // decode the license data
                   $license_data = json_decode( wp_remote_retrieve_body( $response ) );

                   // $license_data->license will be either "active" or "inactive"

                   update_option( 'edd_sample_license_status', $license_data->license );

           }
   }
    
  /**
   * Illustrates how to deactivate a license key.
   * This will descrease the site count.
   */

   Public static function bkap_edd_sample_deactivate_license() {

           // listen for our activate button to be clicked
           if( isset( $_POST['edd_license_deactivate'] ) ) {

                   // run a quick security check
                   if( ! check_admin_referer( 'edd_sample_nonce', 'edd_sample_nonce' ) )
                           return; // get out if we didn't click the Activate button

                   // retrieve the license from the database
                   $license = trim( get_option( 'edd_sample_license_key' ) );


                   // data to send in our API request
                   $api_params = array(
                                   'edd_action' => 'deactivate_license',
                                   'license' 	=> $license,
                                   'item_name'  => urlencode( EDD_SL_ITEM_NAME_BOOK ) // the name of our product in EDD
                   );

                   // Call the custom API.
                   $response = wp_remote_get( esc_url_raw( add_query_arg( $api_params, EDD_SL_STORE_URL_BOOK ) ), array( 'timeout' => 15, 'sslverify' => false ) );

                   // make sure the response came back okay
                   if ( is_wp_error( $response ) )
                           return false;

                   // decode the license data
                   $license_data = json_decode( wp_remote_retrieve_body( $response ) );

                   // $license_data->license will be either "deactivated" or "failed"
                   if( $license_data->license == 'deactivated' )
                           delete_option( 'edd_sample_license_status' );

           }
   }
   
   /**
    * This illustrates how to check if a license key is still valid. 
    * The updater checks this,so this is only needed if you want to do something custom.
    */

   static function bkap_edd_sample_check_license() {

           global $wp_version;

           $license = trim( get_option( 'edd_sample_license_key' ) );

           $api_params = array(
                           'edd_action' => 'check_license',
                           'license' => $license,
                           'item_name' => urlencode( EDD_SL_ITEM_NAME_BOOK )
           );

           // Call the custom API.
           $response = wp_remote_get( esc_url_raw( add_query_arg( $api_params, EDD_SL_STORE_URL_BOOK ) ), array( 'timeout' => 15, 'sslverify' => false ) );


           if ( is_wp_error( $response ) )
                   return false;

           $license_data = json_decode( wp_remote_retrieve_body( $response ) );

           if( $license_data->license == 'valid' ) {
                   echo 'valid'; exit;
                   // this license is still valid
           } else {
                   echo 'invalid'; exit;
                   // this license is no longer valid
           }
   }
   
   /**
    * This function  checks if a new license has been entered , 
    * if yes plugin must be reactivated.
    */
   	
   static function bkap_get_edd_sanitize_license( $new ) {
           $old = get_option( 'edd_sample_license_key' );
           
           if( $old && $old != $new ) {
                   delete_option( 'edd_sample_license_status' ); // new license has been entered, so must reactivate
           }
           return $new;
   }
}

?>