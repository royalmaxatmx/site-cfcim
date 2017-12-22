<!--noptimize--><script>
// Output script that makes full-screen mode.
if ( typeof advanced_ads_full_screen_debug !== 'function' ) {
	function advanced_ads_full_screen_debug( ad ) {
		if ( ! ad || ! document.body ) { return; }

		var ad_full = document.createElement( 'div' );
		ad_full.style.cssText = '<?php echo $style_full; ?>';
		ad_full.ondblclick = function() {
			this.parentNode.removeChild( this );
		}
		ad_full.innerHTML = ad.innerHTML;
		document.body.appendChild( ad_full );
	}
}
</script><!--/noptimize-->
<div id="<?php echo $wrapper_id; ?>" style="<?php echo $style; ?>" ondblclick="advanced_ads_full_screen_debug( this );">
<strong><?php _e( 'Ad debug output', 'advanced-ads' ); ?></strong>
<?php echo '<br /><br />' . implode( '<br /><br />', $content ); ?>
<br /><br /><a style="color: green;" href="<?php echo ADVADS_URL; ?>manual/ad-debug-mode/#utm_source=advanced-ads&utm_medium=link&utm_campaign=ad-debug-mode" target="_blank"><?php _e( 'Find solutions in the manual', 'advanced-ads' ); ?></a>
</div>