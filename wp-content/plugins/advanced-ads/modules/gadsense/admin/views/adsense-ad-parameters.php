<?php
if ( ! defined( 'WPINC' ) ) {
	die();
}
$is_responsive = ('responsive' == $unit_type) ? true : false;
$is_link_responsive_unit = ('link-responsive' == $unit_type) ? true : false;
$is_matched_content = ('matched-content' == $unit_type) ? true : false;
$use_manual_css = ('manual' == $unit_resize) ? true : false;
if ( $is_responsive || $is_link_responsive_unit || $is_matched_content ) {
    echo '<style type="text/css"> #advanced-ads-ad-parameters-size {display: none;}	</style>';
}

$use_paste_code = true;
$use_paste_code = apply_filters( 'advanced-ads-gadsense-use-pastecode', $use_paste_code );

$db = Advanced_Ads_AdSense_Data::get_instance();
$sizing_array = $db->get_responsive_sizing();

?>
<input type="hidden" id="advads-ad-content-adsense" name="advanced_ad[content]" value="<?php echo esc_attr( $json_content ); ?>" />
<input type="hidden" name="unit_id" id="unit_id" value="<?php echo esc_attr( $unit_id ); ?>" />
<?php if ( $use_paste_code ) : ?>
<div class="advads-adsense-code" <?php if( !empty( $unit_code ) ): echo 'style="display: none;"'; endif; ?>>
	<p class="description"><?php _e( 'Copy the ad code from your AdSense account, paste it into the area below and click on <em>Get details</em>.', 'advanced-ads' ); ?></p>
	<textarea rows="10" cols="40" class="advads-adsense-content"></textarea>
	<button class="button button-primary advads-adsense-submit-code"><?php _e( 'Get details', 'advanced-ads' ); ?></button>&nbsp;&nbsp;
	<div id="pastecode-msg"></div>
</div>
<p class="advads-adsense-show-code" <?php if( empty( $unit_code ) ) : echo 'style="display: none;"'; endif; ?>>
    <a href="#"><?php _e( 'Insert new AdSense code', 'advanced-ads' ); ?></a>
</p>
<?php endif; ?>
<p id="adsense-ad-param-error"></p>
<?php ob_start(); ?>
<label class="label"><?php _e( 'Ad Slot ID', 'advanced-ads' ); ?></label>
<div>
    <input type="text" name="unit-code" id="unit-code" value="<?php echo $unit_code; ?>" />
    <input type="hidden" name="advanced_ad[output][adsense-pub-id]" id="advads-adsense-pub-id" value="" />
    <?php if( $pub_id ) : ?>
	<?php printf(__( 'Publisher ID: %s', 'advanced-ads' ), $pub_id ); ?>
    <?php endif; ?>
</div>
<hr/>
<?php
$unit_code_markup = ob_get_clean();
echo apply_filters( 'advanced-ads-gadsense-unit-code-markup', $unit_code_markup, $unit_code );
if( $pub_id_errors ) : ?>
	    <p>
	<span class="advads-error-message">
	    <?php echo $pub_id_errors; ?>
	</span>
	<?php printf(__( 'Please <a href="%s" target="_blank">change it here</a>.', 'advanced-ads' ), admin_url( 'admin.php?page=advanced-ads-settings#top#adsense' )); ?>
    </p>
<?php endif; ?>
    <label class="label" id="unit-type-block"><?php _e( 'Type', 'advanced-ads' ); ?></label>
    <div>
	<select name="unit-type" id="unit-type">
	    <option value="normal" <?php selected( $unit_type, 'normal' ); ?>><?php _e( 'Normal', 'advanced-ads' ); ?></option>
	    <option value="responsive" <?php selected( $unit_type, 'responsive' ); ?>><?php _e( 'Responsive', 'advanced-ads' ); ?></option>
	    <option value="matched-content" <?php selected( $unit_type, 'matched-content' ); ?>><?php _e( 'Responsive (Matched Content)', 'advanced-ads' ); ?></option>
	    <option value="link" <?php selected( $unit_type, 'link' ); ?>><?php _e( 'Link ads', 'advanced-ads' ); ?></option>
	    <option value="link-responsive" <?php selected( $unit_type, 'link-responsive' ); ?>><?php _e( 'Link ads (Responsive)', 'advanced-ads' ); ?></option>
	    <option value="in-article" <?php selected( $unit_type, 'in-article' ); ?>><?php _e( 'InArticle', 'advanced-ads' ); ?></option>
	    <option value="in-feed" <?php selected( $unit_type, 'in-feed' ); ?>><?php _e( 'InFeed', 'advanced-ads' ); ?></option>
	</select>
	<a href="<?php echo ADVADS_URL . 'manual/adsense-ads/#adsense-ad-types'; ?>" target="_blank"><?php _e( 'manual', 'advanced-ads' ); ?></a>
    </div>
    <hr/>
    <label class="label" <?php if ( ! $is_responsive || 2 > count( $sizing_array ) ) { echo 'style="display: none;"'; } ?> id="resize-label"><?php _e( 'Resizing', 'advanced-ads' ); ?></label>
    <div <?php if ( ! $is_responsive || 2 > count( $sizing_array ) ) { echo 'style="display: none;"'; } ?>>
	<select name="ad-resize-type" id="ad-resize-type">
	<?php foreach ( $sizing_array as $key => $desc ) : ?>
	    <option value="<?php echo $key; ?>" <?php selected( $key, $unit_resize ); ?>><?php echo $desc; ?></option>
	<?php endforeach; ?>
	</select>
    </div>
    <label class="label advads-adsense-layout" <?php if ( 'in-feed' !== $unit_type ) { echo 'style="display: none;"'; } ?> id="advads-adsense-layout"><?php _e( 'Layout', 'advanced-ads' ); ?></label>
    <div <?php if ( 'in-feed' !== $unit_type ) { echo 'style="display: none;"'; } ?>>
	<input name="ad-layout" id="ad-layout" value="<?php echo isset( $layout ) ? $layout : ''; ?>"/>
    </div>
    <label class="label advads-adsense-layout-key" <?php if ( 'in-feed' !== $unit_type ) { echo 'style="display: none;"'; } ?> id="advads-adsense-layout-key"><?php _e( 'Layout-Key', 'advanced-ads' ); ?></label>
    <div <?php if ( 'in-feed' !== $unit_type ) { echo 'style="display: none;"'; } ?>>
	<input name="ad-layout-key" id="ad-layout-key" value="<?php echo isset( $layout_key ) ? $layout_key : ''; ?>"/>
    </div>
    <hr/>
    <?php do_action( 'advanced-ads-gadsense-extra-ad-param', $extra_params, $content, $ad );