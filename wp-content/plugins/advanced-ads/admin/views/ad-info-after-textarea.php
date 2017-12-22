<?php
if ( defined ( 'WP_DEBUG' ) && WP_DEBUG &&
	( $error = Advanced_Ads_Admin_Ad_Type::check_ad_dom_is_not_valid( $ad ) ) ) : ?>
	<p class="advads-error-message">
		<?php _e( 'The code of this ad might not work properly with the <em>Content</em> placement.', 'advanced-ads' ); 
		?>&nbsp;<?php printf(__( 'Reach out to <a href="%s">support</a> to get help.', 'advanced-ads' ), admin_url('admin.php?page=advanced-ads-support') ); 
		if ( true === WP_DEBUG ) : ?>
			<span style="white-space:pre-wrap"><?php echo $error; ?></span>
		<?php endif;
		?>
	</p>
<?php endif;

do_action( 'advanced-ads-ad-params-below-textarea', $ad );
?>