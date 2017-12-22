<select id="advads-placements-item-<?php echo $_placement_slug; ?>" name="advads[placements][<?php echo $_placement_slug; ?>][item]">
    <option value=""><?php _e( '--not selected--', 'advanced-ads' ); ?></option>
	<?php if ( isset($items['groups']) ) : ?>
	<optgroup label="<?php _e( 'Ad Groups', 'advanced-ads' ); ?>">
	    <?php foreach ( $items['groups'] as $_item_id => $_item_title ) : ?>
		<option value="<?php echo $_item_id; ?>" <?php if ( isset($_placement['item']) ) { selected( $_item_id, $_placement['item'] ); } ?>><?php echo $_item_title; ?></option>
	<?php endforeach; ?>
	</optgroup>
	<?php endif; ?>
	<?php if ( isset($items['ads']) ) : ?>
	<optgroup label="<?php _e( 'Ads', 'advanced-ads' ); ?>">
	<?php foreach ( $items['ads'] as $_item_id => $_item_title ) : ?>
		<option value="<?php echo $_item_id; ?>" <?php if ( isset($_placement['item']) ) { selected( $_item_id, $_placement['item'] ); } ?>><?php echo $_item_title; ?></option>
	<?php endforeach; ?>
	</optgroup>
	<?php endif; ?>
</select>