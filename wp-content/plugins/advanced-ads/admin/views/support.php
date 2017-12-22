<?php
/**
 * the view for the support page
 */
?><div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <p><?php _e( 'Please fix the red highlighted issues on this page or try to understand their consequences before contacting support.', 'advanced-ads' ); ?></p>

    <?php $messages = array();
    if( ! Advanced_Ads_Checks::php_version_minimum() ) :
	    $messages[] = sprintf(__( 'Your <strong>PHP version (%s) is too low</strong>. Advanced Ads is built for PHP 5.3 and higher. It might work, but updating PHP is highly recommended. Please ask your hosting provider for more information.', 'advanced-ads' ), phpversion() );
    endif;
    if( Advanced_Ads_Checks::cache() && ! defined( 'AAP_VERSION' ) ) :
	    $messages[] = sprintf(__( 'Your <strong>website uses cache</strong>. Some dynamic features like ad rotation or visitor conditions might not work properly. Use the cache-busting feature of <a href="%s" target="_blank">Advanced Ads Pro</a> to load ads dynamically.', 'advanced-ads' ), ADVADS_URL . 'add-ons/advanced-ads-pro/#utm_source=advanced-ads&utm_medium=link&utm_campaign=support' );
    endif;
    if( Advanced_Ads_Checks::wp_update_available() ) :
	    $messages[] = __( 'There is a <strong>new WordPress version available</strong>. Please update.', 'advanced-ads' );
    endif;
    if( Advanced_Ads_Checks::plugin_updates_available() ) :
	    $messages[] = __( 'There are <strong>plugin updates available</strong>. Please update.', 'advanced-ads' );
    endif;
/*    if( Advanced_Ads_Checks::licenses_invalid() ) :
	    $messages[] = sprintf( __( 'One or more license keys for <strong>Advanced Ads add-ons are invalid or missing</strong>. Please add valid license keys <a href="%s">here</a>.', 'advanced-ads' ), admin_url( 'admin.php?page=advanced-ads-settings#top#licenses' ) );
    endif;
    if( Advanced_Ads_Checks::licenses_expired() ) :
	    $messages[] = sprintf( __( '<strong>Advanced Ads</strong> license(s) expired. Support and updates are disabled. Please visit <a href="%s"> the license page</a> for more information.', 'advanced-ads' ), admin_url( 'admin.php?page=advanced-ads-settings#top#licenses' ) );
    endif;*/
    if( Advanced_Ads_Checks::active_autoptimize() && ! defined( 'AAP_VERSION' ) ) :
	    $messages[] = sprintf(__( '<strong>Autoptimize plugin detected</strong>. While this plugin is great for site performance, it is known to alter code, including scripts from ad networks. <a href="%s" target="_blank">Advanced Ads Pro</a> has a build-in support for Autoptimize.', 'advanced-ads' ), ADVADS_URL . 'add-ons/advanced-ads-pro/#utm_source=advanced-ads&utm_medium=link&utm_campaign=support');
    endif;
    if( count( Advanced_Ads_Checks::conflicting_plugins() ) ) :
	    $messages[] = sprintf(__( 'Plugins that are known to cause (partial) problems: <strong>%1$s</strong>. <a href="%2$s" target="_blank">Learn more</a>.', 'advanced-ads' ), implode( ', ', Advanced_Ads_Checks::conflicting_plugins() ), ADVADS_URL . 'manual/known-plugin-conflicts/#utm_source=advanced-ads&utm_medium=link&utm_campaign=support');
    endif;
    if( Advanced_Ads_Checks::ads_disabled() ) :
	    $messages[] = sprintf(__( 'Ads are disabled for all or some pages. See "disabled ads" in <a href="%s">settings</a>.', 'advanced-ads' ), admin_url('admin.php?page=advanced-ads-settings#top#general') );
    endif;
    Advanced_Ads_Checks::jquery_ui_conflict();

    $messages = apply_filters( 'advanced-ads-support-messages', $messages );
    
    if( count( $messages )) :
	foreach( $messages as $_message ) :
	?><div class="message error"><p><?php echo $_message; ?></p></div><?php
	endforeach;
    endif; ?>
    <h2><?php _e( 'Possible Issues', 'advanced-ads' ); ?></h2>
    <ul>
	<li><a href="<?php echo ADVADS_URL; ?>manual/ads-not-showing-up/#utm_source=advanced-ads&utm_medium=link&utm_campaign=support"><?php _e( 'Ads not showing up', 'advanced-ads' ); ?></a></li>
	<li><a href="<?php echo ADVADS_URL; ?>manual-category/purchase-licenses/#utm_source=advanced-ads&utm_medium=link&utm_campaign=support"><?php _e( 'Purchase & Licenses', 'advanced-ads' ); ?></a></li>
	<li><a href="<?php echo ADVADS_URL; ?>manual-category/troubleshooting/#utm_source=advanced-ads&utm_medium=link&utm_campaign=support"><?php _e( 'General Issues', 'advanced-ads' ); ?></a></li>
	<li><a href="<?php echo ADVADS_URL; ?>manual-category/add-on-issues/#utm_source=advanced-ads&utm_medium=link&utm_campaign=support"><?php _e( 'Issues with Add-Ons', 'advanced-ads' ); ?></a></li>
    </ul>
    <p><?php _e( 'Use the following form to search for solutions in the manual on wpadvancedads.com', 'advanced-ads' ); ?></p>
    <form action="https://wpadvancedads.com/" method="get" class="advads-support-form">
	<input type="search" name="s"/>
	<input type="submit" class="button button-primary" value="<?php _e( 'search', 'advanced-ads' ); ?>">
    </form>
    <p><?php printf(__( 'Take a look at more common issues or contact us directly through the <a href="%s" target="_blank">support page</a>.', 'advanced-ads' ), ADVADS_URL . 'support/#utm_source=advanced-ads&utm_medium=link&utm_campaign=support' ); ?></p>