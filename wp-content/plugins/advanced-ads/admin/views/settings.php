<?php
/**
 * the view for the settings page
 */

// array with setting tabs for frontend
$setting_tabs = apply_filters('advanced-ads-setting-tabs', array(
	'general' => array(
		'page' => Advanced_Ads_Admin::get_instance()->plugin_screen_hook_suffix,
		'group' => ADVADS_SLUG,
		'tabid' => 'general',
		'title' => __( 'General', 'advanced-ads' )
	)
));
?><div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<?php settings_errors(); ?>
	<div class="nav-tab-wrapper" id="advads-tabs">
		<?php foreach ( $setting_tabs as $_setting_tab_id => $_setting_tab ) : ?>
			<a class="nav-tab" id="<?php echo $_setting_tab_id; ?>-tab"
				href="#top#<?php echo $_setting_tab_id; ?>"><?php echo $_setting_tab['title']; ?></a>
		<?php endforeach; ?>
	</div>
		<?php foreach ( $setting_tabs as $_setting_tab_id => $_setting_tab ) : ?>
			<div id="<?php echo $_setting_tab_id; ?>" class="advads-tab">
				<form class="advads-settings-tab-main-form" method="post" action="options.php">
					<?php
					if ( isset( $_setting_tab['group'] ) ) {
						settings_fields( $_setting_tab['group'] );
					}
					do_settings_sections( $_setting_tab['page'] );

					do_action( 'advanced-ads-settings-form', $_setting_tab_id, $_setting_tab );
					if ( isset( $_setting_tab['group'] ) && 'advanced-ads-licenses' !== $_setting_tab['group'] ){
					    submit_button( __( 'Save settings on this page', 'advanced-ads' ) );
					}
					?>
				</form>
				<?php do_action( 'advanced-ads-settings-tab-after-form', $_setting_tab_id, $_setting_tab ); ?>
			<?php if( 'general' === $_setting_tab_id ) : ?>
			<ul>
			    <li><a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-ads-import-export' ) ); ?>"><?php _e( 'Import &amp; Export', 'advanced-ads' ); ?></a></li>
			</ul>
			<?php endif; ?>
			</div>
		<?php endforeach; ?>
		<?php
			do_action( 'advanced-ads-additional-settings-form' );
			// print the filesystem credentials modal if needed
			Advanced_Ads_Filesystem::get_instance()->print_request_filesystem_credentials_modal();
		?>

</div>
<script>
// menu tabs
jQuery( '#advads-tabs' ).find( 'a' ).click(function () {
	jQuery( '#advads-tabs' ).find( 'a' ).removeClass( 'nav-tab-active' );
	jQuery( '.advads-tab' ).removeClass( 'active' );

	var id = jQuery( this ).attr( 'id' ).replace( '-tab', '' );
	jQuery( '#' + id ).addClass( 'active' );
	jQuery( this ).addClass( 'nav-tab-active' );
});

// activate specific or first tab

var active_tab = window.location.hash.replace( '#top#', '' );
if (active_tab == '' || active_tab == '#_=_') {
	active_tab = jQuery( '.advads-tab' ).attr( 'id' );
}
jQuery( '#' + active_tab ).addClass( 'active' );
jQuery( '#' + active_tab + '-tab' ).addClass( 'nav-tab-active' );
jQuery( '.nav-tab-active' ).click();
// set all tab urls
advads_set_tab_hashes();
</script>