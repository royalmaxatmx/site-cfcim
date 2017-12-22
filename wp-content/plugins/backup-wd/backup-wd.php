<?php
/**
 * Plugin Name:     BackUp WD
 * Plugin URI:      https://web-dorado.com/products/wordpress-backup-wd.html
 * Description:     Backup WD is an easy-to-use, fully functional backup plugin that allows to backup your website.  
 * Version: 1.0.10
 * Author:          WebDorado
 * Author URI:      https://web-dorado.com
 * License: GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 **/
 
setlocale(LC_ALL, 'en_US.UTF-8');
define( 'BUWD_MAIN_FILE', plugin_basename( __FILE__ ) );
define( 'BUWD_DIR', dirname( __FILE__ ) );
define( 'BUWD_URL', plugins_url( plugin_basename( dirname( __FILE__ ) ) ) );
define( 'BUWD_VERSION', '1.0.8' );
define( 'BUWD_PREFIX', 'buwd' );

if( !class_exists("DoradoWeb") ){
    require_once(BUWD_DIR . '/wd/start.php');
}

global $buwd_plugin_options;
$buwd_plugin_options = array (
    "prefix" => "buwd",
    "wd_plugin_id" => 185,
    "plugin_title" => "BackUp WD",
    "plugin_wordpress_slug" => "backup-wd",
    "plugin_dir" => BUWD_DIR,
    "plugin_main_file" => __FILE__,
    "description" => '',
    "plugin_features" => array(
   ),
   "user_guide" => array(
   ),
   "overview_welcome_image" => null,
   "video_youtube_id" => "",
   "plugin_wd_url" => "",
   "plugin_wd_demo_link" => "",
   "plugin_wd_addons_link" => "",
   "after_subscribe" => "admin.php?page=buwd_jobs",
   "plugin_wizard_link" => "",
   "plugin_menu_title" => __('Backup WD', 'buwd'),
   "plugin_menu_icon" => BUWD_URL . '/public/images/menu_logo.png',
   "deactivate" => true,
   "subscribe" => true,
   "custom_post" => false,
   "menu_capability" => "buwd_edit",
   "menu_position" => null,
);
dorado_web_init($buwd_plugin_options);

if (version_compare(PHP_VERSION, '5.5.0') >= 0){
    require_once BUWD_DIR . '/vendor/autoload.php';

    require_once( BUWD_DIR . '/includes/buwd.php' );
    require_once( BUWD_DIR . '/includes/buwd-options.php' );

    add_action( 'plugins_loaded', array( 'Buwd', 'get_instance' ) );

    require_once( BUWD_DIR . '/includes/buwd-admin.php' );
    register_activation_hook( __FILE__, array( 'Buwd_Admin', 'activate' ) );
    add_action( 'plugins_loaded', array( 'Buwd_Admin', 'get_instance' ) );

    if (class_exists("WP_REST_Controller")) {
        require_once('buwd-rest.php');
        add_action('rest_api_init', function () {

            $rest = new BUWD_Rest();
            $rest->register_routes();
        });
    }

    //deactivation hook
    register_deactivation_hook(__FILE__, array('Buwd_Admin', 'deactivate'));
}
else{
    add_action( 'admin_notices', 'buwd_php_version_admin_notice' );
}

function buwd_php_version_admin_notice() {
    ?>
    <div class="notice notice-error">
        <h3>Backup WD</h3>
        <p><?php _e( 'This version of the plugin requires PHP 5.5.0 or higher.', 'buwd' ); ?></p>
        <p><?php _e( 'We recommend you to update PHP or ask your hosting provider to do that.', 'buwd' );?></p>
    </div>
    <?php
}
