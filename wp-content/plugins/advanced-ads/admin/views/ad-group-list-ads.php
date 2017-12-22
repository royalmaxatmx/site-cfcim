<table class="advads-group-ads">
<thead><tr><th><?php _e( 'Ad', 'advanced-ads' );
	    ?></th><th colspan="2"><?php _e('weight', 'advanced-ads'); ?></th></tr></thead>
	    <tbody>
<?php
if (count($ad_form_rows)) {
    foreach ($ad_form_rows as $_row) {
	echo $_row;
    }
}
?>
	    </tbody>
    </table>

<?php if ( $ads_for_select ): ?>
	<fieldset class="advads-group-add-ad">
		<legend><?php _e( 'New Ad', 'advanced-ads' ); ?></legend>
		<select class="advads-group-add-ad-list-ads">
			<?php foreach ( $ads_for_select as $_ad_id => $_ad_title ) {
				echo '<option value="advads-groups[' . $group->id . '][ads][' . $_ad_id . ']">' . $_ad_title . '</option>';
			} ?>
		</select>
		<?php echo $new_ad_weights; ?>
		<button type="button" class="button"><?php _e( 'add', 'advanced-ads' ); ?></button>
	</fieldset>
<?php endif;