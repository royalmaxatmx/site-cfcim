<?php

class Advanced_Ads_Adblock_Finder {

	public function __construct() {
		add_action( 'wp_footer', array( $this, 'print_adblock_check_js' ), 9 );
	}

	public function print_adblock_check_js() {
		$options = Advanced_Ads::get_instance()->options();

		?><script>
		var advanced_ads_adsense_UID = <?php echo isset( $options['ga-UID'] ) ? "'" . esc_js( $options['ga-UID'] ). "'" : 'false' ?>;
		<?php readfile( dirname( __FILE__ ) . '/script.js' ); ?>
		</script><?php
	}
}