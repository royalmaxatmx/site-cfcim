<select id="advads-filter-type">
	<option value="">- <?php _e( 'all ad types', 'advanced-ads' ); ?> -</option>
</select>
<select id="advads-filter-size">
	<option value="">- <?php _e( 'all ad sizes', 'advanced-ads' ); ?> -</option>
</select>
<select id="advads-filter-date">
	<option value="">- <?php _e( 'all ad dates', 'advanced-ads' ); ?> -</option>
	<?php
	foreach ( $timing_filter as $_key => $_item ) {
		printf( '<option value="%s" style="display:none;">%s</option>', $_key, $_item );
	}
	?>
</select>
<select id="advads-filter-group">
	<option value="">- <?php _e( 'all ad groups', 'advanced-ads' ); ?> -</option>
</select>