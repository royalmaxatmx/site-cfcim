<?php

/**
 * checks for various things
 *
 * @since 1.6.9
 */
class Advanced_Ads_Checks {

	/**
	 * php version minimum 5.3
	 *
	 * @return bool true if 5.3 and higher
	 */
	 public static function php_version_minimum(){

		if (version_compare(phpversion(), '5.3', '>=')) {
			return true;
		}

		return false;
	 }

	/**
	 * caching used
	 *
	 * @return bool true if active
	 */
	 public static function cache(){
		if ( ( defined( 'WP_CACHE' ) && WP_CACHE ) // general cache constant
			|| defined('W3TC') // W3 Total Cache
			|| function_exists( 'wp_super_cache_text_domain' ) // WP SUper Cache
			|| defined( 'WP_ROCKET_SLUG' ) //WP Rocket
			|| defined( 'WPFC_WP_CONTENT_DIR' ) //WP Fastest Cache
			|| class_exists( 'HyperCache', false ) // Hyper Cache
			|| defined( 'CE_CACHE_DIR' ) // Cache Enabler
		){
			return true;
		}

		return false;
	 }

	 /**
	  * WordPress update available
	  *
	  * @return bool true if WordPress update available
	  */
	 public static function wp_update_available(){

		$update_data = wp_get_update_data();
		$count = absint( $update_data['counts']['wordpress'] );

		if( $count ){
			return true;
		}

		return false;
	 }

	 /**
	  * any plugin updates available
	  *
	  * @return bool true if plugin updates are available
	  */
	 public static function plugin_updates_available(){

		$update_data = wp_get_update_data();
		$count = absint( $update_data['counts']['plugins'] );

		if( $count ){
			return true;
		}

		return false;
	 }

	 /**
	  * check if license keys are missing or invalid
	  *
	  * @since 1.6.6
	  * @update 1.6.9 moved from Advanced_Ads_Plugin
	  * @return true if there are missing licenses
	  */
	public static function licenses_invalid(){

	    $add_ons = apply_filters( 'advanced-ads-add-ons', array() );
	    
	    if( $add_ons === array() ) {
		    return false;
	    }

	    foreach( $add_ons as $_add_on_key => $_add_on ){
		    $status = Advanced_Ads_Admin_Licenses::get_instance()->get_license_status( $_add_on['options_slug'] );
		    
		    // don’t check if license is valid
		    if( $status === 'valid' ) {
			    continue;
		    }

		    // retrieve our license key from the DB
		    $licenses = Advanced_Ads_Admin_Licenses::get_instance()->get_licenses();

		    $license_key = isset($licenses[$_add_on_key]) ? $licenses[$_add_on_key] : false;

		    if( ! $license_key || $status !== 'valid' ){
			    return true;
		    }
	    }

	    return false;
	}

	/**
	 * check if license keys are going to expire within next 14 days
	 *
	 * @since 1.6.6
	 * @update 1.6.9 moved from Advanced_Ads_Plugin
	 * @return true if there are expiring licenses
	 */
	public static function licenses_expire(){

	    $add_ons = apply_filters( 'advanced-ads-add-ons', array() );

	    if( $add_ons === array() ) {
		    return false;
	    }

	    $now = time();

	    foreach( $add_ons as $_add_on_key => $_add_on ){
		    // don’t display error for invalid licenses
		    if(Advanced_Ads_Admin_Licenses::get_instance()->get_license_status( $_add_on['options_slug'] ) === 'invalid' ) {
			    continue;
		    }

		    $expiry_date = Advanced_Ads_Admin_Licenses::get_instance()->get_license_expires( $_add_on['options_slug'] );

		    if( $expiry_date && 'lifetime' !== $expiry_date ){
			    $expiry_date_t = strtotime( $expiry_date );
			    $in_two_weeks = time() + ( WEEK_IN_SECONDS * 2) ;
			    // check if expiry date is within next comming 2 weeks
			    if( $expiry_date_t < $in_two_weeks && $expiry_date_t >= $now ){
				    return true;
			    }

		    }
	    }

	    return false;
	}

	/**
	 * check if license keys are already expired
	 *
	 * @since 1.6.6
	 * @update 1.6.9 moved from Advanced_Ads_Plugin
	 * @return true if there are expired licenses
	 */
	public static function licenses_expired(){

	    $add_ons = apply_filters( 'advanced-ads-add-ons', array() );

	    if( $add_ons === array() ) {
		    return false;
	    }

	    $now = time();

	    foreach( $add_ons as $_add_on_key => $_add_on ){
		    // don’t display error for invalid licenses
		    if(Advanced_Ads_Admin_Licenses::get_instance()->get_license_status( $_add_on['options_slug'] ) === 'invalid' ) {
			    continue;
		    }

		    $expiry_date = Advanced_Ads_Admin_Licenses::get_instance()->get_license_expires( $_add_on['options_slug'] );

		    if( $expiry_date && 'lifetime' !== $expiry_date && strtotime( $expiry_date ) < $now ){
			    return true;
		    }
	    }

	    return false;
	}

	/**
	 * Autoptimize plugin installed
	 *   can change ad tags, especially inline css and scripts
	 *
	 * @link https://wordpress.org/plugins/autoptimize/
	 * @return bool true if Autoptimize is installed
	 */
	public static function active_autoptimize(){

		if( defined( 'AUTOPTIMIZE_CACHE_DIR' ) ){
			return true;
		}

		return false;
	}

	/**
	 * check for additional conflicting plugins
	 *
	 * @return arr $plugins names of conflicting plugins
	 */
	public static function conflicting_plugins(){

		$conflicting_plugins = array();

		if( defined( 'Publicize_Base' )){ // JetPack Publicize module
			$conflicting_plugins[] = 'Jetpack – Publicize';
		}

		return $conflicting_plugins;
	}
	
	/**
	 * check if any of the global hide ads options is set
	 * 
	 * @since 1.7.10
	 * @return bool
	 */
	public static function ads_disabled(){
		$options = Advanced_Ads::get_instance()->options();
		if( isset( $options['disabled-ads'] ) && is_array( $options['disabled-ads'] ) ){
			foreach( $options['disabled-ads'] as $_value ){
				if( !empty( $_value ) ){
					return true;
				}
			}
		}
		return false;
	}
	
	
	/**
	 * check for potential jQuery errors
	 * only script, so no return, but direct output
	 * 
	 */
	public static function jquery_ui_conflict(){
	    ?>
	    <div id="advads-jqueryui-conflict-message" style="display:none;" class="message error"><p><?php printf( __( 'Possible conflict between jQueryUI library, used by Advanced Ads and other libraries (probably <a href="%s">Twitter Bootstrap</a>). This might lead to misfortunate formats in forms, but should not damage features.', 'advanced-ads' ), 'http://getbootstrap.com/javascript/#js-noconflict' ); ?></p></div>
	    <script>// string from jquery-ui source code
		jQuery(document).ready(function(){
		    var needle = 'var g="string"==typeof f,h=c.call(arguments,1)';
		    if ( jQuery.fn.button.toString().indexOf( needle ) === -1 || jQuery.fn.tooltip.toString().indexOf( needle ) === -1 ) {
			    jQuery( '#advads-jqueryui-conflict-message' ).show();
		    }
		});
	    </script><?php
	}
}