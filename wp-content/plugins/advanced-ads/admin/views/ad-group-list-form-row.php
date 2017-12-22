<?php // open form if this was the last edited
$hidden = ( isset( $_POST['advads-last-edited-group'] ) && $group->id == $_POST['advads-last-edited-group'] ) ? '' : ' hidden';
?><tr class="advads-ad-group-form<?php echo $hidden; ?>">
    <td colspan="3">
	<?php // name
	ob_start();
	?><input type="text" name="advads-groups[<?php echo $group->id; ?>][name]" value="<?php echo $group->name; ?>"/><?php
	$option_content = ob_get_clean();
	
	Advanced_Ads_Admin_Options::render_option( 
		'group-name static', 
		__( 'Name', 'advanced-ads' ),
		$option_content );
	
	// description
	/* removed in 1.8 since we never saw it being used and so it just took space
	ob_start();
	?><input type="text" name="advads-groups[<?php echo $group->id; ?>][description]" value="<?php echo $group->description; ?>"/><?php
	$option_content = ob_get_clean();
	
	Advanced_Ads_Admin_Options::render_option( 
		'group-description', 
		__( 'Description', 'advanced-ads' ),
		$option_content );
	 */
	
	// type
	ob_start();
	?><div class="advads-ad-group-type"><?php foreach ( $this->types as $_type_key => $_type ) :
			?><label title="<?php echo $_type['description']; ?>"><input type="radio" name="advads-groups[<?php echo $group->id;
				?>][type]" value="<?php echo $_type_key; ?>" <?php checked( $group->type, $_type_key )?>/><?php
				echo $_type['title']; ?></label>
                <!--p class="description"><?php echo $_type['description']; ?></p-->
            <?php
		endforeach; ?>
	</div><?php
	$option_content = ob_get_clean();
	
	Advanced_Ads_Admin_Options::render_option( 
		'group-type static', 
		__( 'Type', 'advanced-ads' ),
		$option_content );
	
	// group number
	ob_start();
	?><select name="advads-groups[<?php echo $group->id; ?>][ad_count]"><?php
		    $max = ( count( $ad_form_rows ) >= 10 ) ? count( $ad_form_rows ) + 2 : 10;
		    for ( $i = 1; $i <= $max; $i++ ) : ?>
		    <option <?php selected( $group->ad_count, $i ); ?>><?php echo $i; ?></option>
		<?php endfor;
		    ?><option <?php selected( $group->ad_count, 'all' ); ?> value="all"><?php _ex('all', 'option to display all ads in an ad groups', 'advanced-ads'); ?></option>
		    </select><?php
	$option_content = ob_get_clean();
	
	Advanced_Ads_Admin_Options::render_option( 
		'group-number advads-group-type-default advads-group-type-ordered', 
		__( 'Visible ads', 'advanced-ads' ),
		$option_content,
		__( 'Number of ads that are visible at the same time', 'advanced-ads' ) );
        
	do_action( 'advanced-ads-group-form-options', $group );
	
	ob_start();
	include ADVADS_BASE_PATH . 'admin/views/ad-group-list-ads.php';
	$option_content = ob_get_clean();
	Advanced_Ads_Admin_Options::render_option( 
		'group-ads static', 
		__( 'Ads', 'advanced-ads' ),
		$option_content );

    ?></td>
</tr>